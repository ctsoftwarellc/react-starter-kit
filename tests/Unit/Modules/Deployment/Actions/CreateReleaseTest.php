<?php

namespace Tests\Unit\Modules\Deployment\Actions;

use App\Modules\AppPlatform\Models\Application;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Actions\CreateRelease;
use App\Modules\Deployment\Enums\ReleaseStatus;
use App\Modules\Deployment\Enums\RuntimeProfileStack;
use App\Modules\Deployment\Models\Release;
use App\Modules\Deployment\Models\RuntimeProfile;
use App\Modules\Pipeline\Models\Artifact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateReleaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_release_with_incremented_version_and_config_snapshot(): void
    {
        $application = Application::factory()->create([
            'settings' => ['php_version' => '8.4'],
        ]);
        $runtimeProfile = RuntimeProfile::factory()->create([
            'application_id' => $application->id,
            'stack' => RuntimeProfileStack::PhpFpm,
            'config' => [
                'shared_dirs' => ['storage', 'public/uploads'],
                'writable_dirs' => ['storage', 'bootstrap/cache'],
                'pre_activate' => ['php artisan migrate --force'],
                'post_activate' => ['php artisan queue:restart'],
            ],
        ]);
        $environment = Environment::factory()->create([
            'application_id' => $application->id,
            'runtime_profile_id' => $runtimeProfile->id,
        ]);
        Artifact::factory()->create(['application_id' => $application->id]);

        Release::factory()->create([
            'environment_id' => $environment->id,
            'artifact_id' => Artifact::factory()->create(['application_id' => $application->id])->id,
            'version' => 2,
        ]);

        $environment->variables()->create([
            'key' => 'APP_ENV',
            'value' => 'production',
            'is_build_arg' => false,
        ]);
        $environment->secrets()->create([
            'key' => 'APP_KEY',
            'encrypted_value' => 'base64:secret-value',
            'version' => 1,
        ]);
        $environment->processDefinitions()->create([
            'type' => 'web',
            'command' => 'php-fpm',
            'instances' => 2,
        ]);

        $artifact = Artifact::factory()->create(['application_id' => $application->id]);

        $release = (new CreateRelease)->execute($environment->fresh(), $artifact);

        $this->assertDatabaseHas('releases', [
            'id' => $release->id,
            'environment_id' => $environment->id,
            'artifact_id' => $artifact->id,
            'version' => 3,
            'status' => ReleaseStatus::Pending->value,
        ]);

        $this->assertSame('production', $release->config_snapshot['env_vars']['APP_ENV']);
        $this->assertSame(['APP_KEY'], $release->config_snapshot['secrets']);
        $this->assertSame('web', $release->config_snapshot['processes'][0]['type']);
        $this->assertSame('php', $release->config_snapshot['runtime']);
        $this->assertSame('8.4', $release->config_snapshot['php_version']);
        $this->assertSame('php-fpm', $release->config_snapshot['runtime_stack']);
        $this->assertSame(['storage', 'public/uploads'], $release->config_snapshot['shared_dirs']);
    }

    public function test_it_snapshots_secret_keys_not_secret_values(): void
    {
        $application = Application::factory()->create();
        $environment = Environment::factory()->create(['application_id' => $application->id]);
        $artifact = Artifact::factory()->create(['application_id' => $application->id]);

        $environment->secrets()->create([
            'key' => 'DB_PASSWORD',
            'encrypted_value' => 'super-secret-value',
            'version' => 1,
        ]);

        $release = (new CreateRelease)->execute($environment->fresh(), $artifact);

        $this->assertSame(['DB_PASSWORD'], $release->config_snapshot['secrets']);
        $this->assertNotContains('super-secret-value', $release->config_snapshot['secrets']);
    }
}
