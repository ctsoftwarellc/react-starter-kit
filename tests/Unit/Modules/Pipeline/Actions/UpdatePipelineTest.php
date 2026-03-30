<?php

namespace Tests\Unit\Modules\Pipeline\Actions;

use App\Modules\Pipeline\Actions\UpdatePipeline;
use App\Modules\Pipeline\DTOs\PipelineDefinitionData;
use App\Modules\Pipeline\Models\Pipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdatePipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_pipeline_definition_and_triggers(): void
    {
        $pipeline = Pipeline::factory()->create();

        $updated = (new UpdatePipeline)->execute($pipeline, new PipelineDefinitionData(
            name: 'Release',
            definition: [
                'artifact' => false,
                'stages' => [
                    [
                        'name' => 'lint',
                        'jobs' => [
                            [
                                'name' => 'pint',
                                'commands' => ['./vendor/bin/pint --test'],
                            ],
                        ],
                    ],
                ],
            ],
            isActive: false,
            triggerBranches: ['develop'],
            triggerEvents: ['manual'],
        ));

        $this->assertSame('Release', $updated->name);
        $this->assertFalse($updated->is_active);
        $this->assertSame(['develop'], $updated->trigger_branches);
        $this->assertSame(['manual'], $updated->trigger_events);
        $this->assertSame('lint', $updated->definition['stages'][0]['name']);
    }
}
