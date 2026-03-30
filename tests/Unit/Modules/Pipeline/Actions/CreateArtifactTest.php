<?php

namespace Tests\Unit\Modules\Pipeline\Actions;

use App\Modules\Pipeline\Actions\CreateArtifact;
use App\Modules\Pipeline\Enums\ArtifactStatus;
use App\Modules\Pipeline\Events\ArtifactCreated;
use App\Modules\Pipeline\Models\PipelineJob;
use App\Modules\Pipeline\Models\PipelineRun;
use App\Support\Services\ObjectStorage\ObjectStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class CreateArtifactTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uploads_an_artifact_and_marks_it_ready(): void
    {
        Event::fake();
        Storage::fake('artifacts');

        $job = PipelineJob::factory()->create([
            'pipeline_run_id' => PipelineRun::factory()->create([
                'definition_snapshot' => [
                    'artifact' => true,
                    'stages' => [[
                        'name' => 'build',
                        'jobs' => [[
                            'name' => 'build',
                            'commands' => ['npm run build'],
                        ]],
                    ]],
                ],
            ])->id,
        ]);

        $artifact = (new CreateArtifact(new ObjectStorageService))->execute($job->fresh('pipelineRun.pipeline'), 'artifact-bytes', ['source' => 'runner']);

        $this->assertEquals(ArtifactStatus::Ready, $artifact->status);
        $this->assertTrue(Storage::disk('artifacts')->exists('artifacts/'.$artifact->storage_path));
        Event::assertDispatched(ArtifactCreated::class, fn (ArtifactCreated $event) => $event->artifact->id === $artifact->id);
    }

    public function test_it_marks_the_artifact_failed_when_upload_fails(): void
    {
        $job = PipelineJob::factory()->create([
            'pipeline_run_id' => PipelineRun::factory()->create([
                'definition_snapshot' => [
                    'artifact' => true,
                    'stages' => [[
                        'name' => 'build',
                        'jobs' => [[
                            'name' => 'build',
                            'commands' => ['npm run build'],
                        ]],
                    ]],
                ],
            ])->id,
        ]);

        $storage = new class extends ObjectStorageService
        {
            public function putArtifact(string $path, mixed $contents): bool
            {
                return false;
            }
        };

        try {
            (new CreateArtifact($storage))->execute($job->fresh('pipelineRun.pipeline'), 'artifact-bytes');
            $this->fail('Expected artifact upload to fail.');
        } catch (RuntimeException $exception) {
            $artifact = $job->fresh('pipelineRun')->pipelineRun->artifact;

            $this->assertSame('Failed to upload artifact to object storage.', $exception->getMessage());
            $this->assertEquals(ArtifactStatus::Failed, $artifact->status);
        }
    }
}
