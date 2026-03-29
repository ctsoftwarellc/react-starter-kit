<?php

namespace Tests\Unit\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\Actions\CreateProcessDefinition;
use App\Modules\AppPlatform\DTOs\ProcessDefinitionData;
use App\Modules\AppPlatform\Enums\ProcessType;
use App\Modules\AppPlatform\Events\EnvironmentConfigChanged;
use App\Modules\AppPlatform\Models\Environment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateProcessDefinitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_process_definition(): void
    {
        Event::fake();

        $environment = Environment::factory()->create();

        $process = (new CreateProcessDefinition)->execute($environment, new ProcessDefinitionData(
            type: ProcessType::Worker,
            command: 'php artisan queue:work',
            instances: 2,
        ));

        $this->assertDatabaseHas('process_definitions', [
            'id' => $process->id,
            'environment_id' => $environment->id,
            'instances' => 2,
        ]);
        Event::assertDispatched(EnvironmentConfigChanged::class);
    }
}
