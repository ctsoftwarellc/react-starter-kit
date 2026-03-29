<?php

namespace Tests\Unit\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\Actions\UpdateProcessDefinition;
use App\Modules\AppPlatform\DTOs\ProcessDefinitionData;
use App\Modules\AppPlatform\Enums\ProcessType;
use App\Modules\AppPlatform\Models\ProcessDefinition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateProcessDefinitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_process_definition(): void
    {
        $process = ProcessDefinition::factory()->create();

        $updated = (new UpdateProcessDefinition)->execute($process, new ProcessDefinitionData(
            type: ProcessType::Scheduler,
            command: 'php artisan schedule:run',
            instances: 3,
        ));

        $this->assertSame(ProcessType::Scheduler, $updated->type);
        $this->assertSame('php artisan schedule:run', $updated->command);
        $this->assertSame(3, $updated->instances);
    }
}
