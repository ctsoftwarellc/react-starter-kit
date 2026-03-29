<?php

namespace Tests\Unit\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\Actions\SetEnvironmentVariable;
use App\Modules\AppPlatform\DTOs\SetEnvironmentVariableData;
use App\Modules\AppPlatform\Events\EnvironmentConfigChanged;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\AppPlatform\Models\EnvironmentVariable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SetEnvironmentVariableTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_variable(): void
    {
        Event::fake();

        $environment = Environment::factory()->create();

        $variable = (new SetEnvironmentVariable)->execute($environment, new SetEnvironmentVariableData(
            key: 'APP_ENV',
            value: 'production',
            isBuildArg: false,
        ));

        $this->assertDatabaseHas('environment_variables', [
            'id' => $variable->id,
            'environment_id' => $environment->id,
            'key' => 'APP_ENV',
        ]);
        Event::assertDispatched(EnvironmentConfigChanged::class);
    }

    public function test_it_updates_existing_variable_by_key_without_leaving_old_key_behind(): void
    {
        Event::fake();

        $environment = Environment::factory()->create();
        $variable = EnvironmentVariable::factory()->create([
            'environment_id' => $environment->id,
            'key' => 'OLD_KEY',
            'value' => 'old',
        ]);

        $updated = (new SetEnvironmentVariable)->execute($environment, new SetEnvironmentVariableData(
            key: 'NEW_KEY',
            value: 'new',
            isBuildArg: true,
        ), $variable);

        $this->assertSame($variable->id, $updated->id);
        $this->assertDatabaseMissing('environment_variables', [
            'environment_id' => $environment->id,
            'key' => 'OLD_KEY',
        ]);
        $this->assertDatabaseHas('environment_variables', [
            'id' => $variable->id,
            'key' => 'NEW_KEY',
            'value' => 'new',
            'is_build_arg' => true,
        ]);
        $this->assertSame(1, EnvironmentVariable::where('environment_id', $environment->id)->count());
    }
}
