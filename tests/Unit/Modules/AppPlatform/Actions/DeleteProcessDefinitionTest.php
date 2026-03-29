<?php

namespace Tests\Unit\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\Actions\DeleteProcessDefinition;
use App\Modules\AppPlatform\Events\EnvironmentConfigChanged;
use App\Modules\AppPlatform\Models\ProcessDefinition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeleteProcessDefinitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_process_definition(): void
    {
        Event::fake();

        $process = ProcessDefinition::factory()->create();

        (new DeleteProcessDefinition)->execute($process);

        $this->assertDatabaseMissing('process_definitions', ['id' => $process->id]);
        Event::assertDispatched(EnvironmentConfigChanged::class);
    }
}
