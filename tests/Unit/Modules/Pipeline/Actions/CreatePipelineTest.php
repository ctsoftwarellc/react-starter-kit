<?php

namespace Tests\Unit\Modules\Pipeline\Actions;

use App\Modules\AppPlatform\Models\Application;
use App\Modules\Pipeline\Actions\CreatePipeline;
use App\Modules\Pipeline\DTOs\PipelineDefinitionData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class CreatePipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_pipeline_with_valid_definition(): void
    {
        $application = Application::factory()->create();

        $pipeline = (new CreatePipeline)->execute($application, new PipelineDefinitionData(
            name: 'Deploy',
            definition: $this->definition(),
            isActive: true,
            triggerBranches: ['main', 'release'],
            triggerEvents: ['push', 'manual'],
        ));

        $this->assertDatabaseHas('pipelines', [
            'id' => $pipeline->id,
            'application_id' => $application->id,
            'name' => 'Deploy',
        ]);
        $this->assertTrue($pipeline->is_active);
        $this->assertSame(['main', 'release'], $pipeline->trigger_branches);
        $this->assertSame(['push', 'manual'], $pipeline->trigger_events);
    }

    public function test_it_rejects_an_invalid_pipeline_definition(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new CreatePipeline)->execute(Application::factory()->create(), new PipelineDefinitionData(
            name: 'Broken',
            definition: ['stages' => []],
        ));
    }

    private function definition(): array
    {
        return [
            'artifact' => true,
            'stages' => [
                [
                    'name' => 'build',
                    'jobs' => [
                        [
                            'name' => 'test',
                            'commands' => ['php artisan test'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
