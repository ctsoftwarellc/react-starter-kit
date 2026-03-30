<?php

namespace Tests\Unit\Modules\Networking\Services;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Models\RuntimeProfile;
use App\Modules\Networking\Services\CaddyConfigGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @preserveGlobalState disabled
 */
class CaddyConfigGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_routes_for_verified_primary_domains(): void
    {
        $environment = Environment::factory()->create();
        RuntimeProfile::factory()->create([
            'application_id' => $environment->application_id,
            'config' => ['web_port' => 9000],
        ]);
        $environment->update(['runtime_profile_id' => RuntimeProfile::query()->latest('created_at')->value('id')]);

        $environment->domains()->createMany([
            [
                'hostname' => 'app.example.test',
                'is_primary' => true,
                'is_verified' => true,
                'verification_token' => 'token-1',
            ],
            [
                'hostname' => 'www.example.test',
                'is_primary' => false,
                'is_verified' => true,
                'verification_token' => 'token-2',
            ],
        ]);

        $config = (new CaddyConfigGenerator)->generateForEnvironment($environment->fresh());

        $this->assertStringContainsString('app.example.test {', $config);
        $this->assertStringContainsString('www.example.test {', $config);
        $this->assertSame(2, substr_count($config, 'reverse_proxy 127.0.0.1:9000'));
    }

    public function test_it_excludes_unverified_domains(): void
    {
        $environment = Environment::factory()->create();
        $environment->domains()->createMany([
            [
                'hostname' => 'verified.example.test',
                'is_primary' => true,
                'is_verified' => true,
                'verification_token' => 'token-1',
            ],
            [
                'hostname' => 'pending.example.test',
                'is_primary' => false,
                'is_verified' => false,
                'verification_token' => 'token-2',
            ],
        ]);

        $config = (new CaddyConfigGenerator)->generateForEnvironment($environment->fresh());

        $this->assertStringContainsString('verified.example.test', $config);
        $this->assertStringNotContainsString('pending.example.test', $config);
    }
}
