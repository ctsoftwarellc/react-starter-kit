<?php

namespace App\Modules\Pipeline\Actions;

use App\Modules\Pipeline\Enums\ArtifactStatus;
use App\Modules\Pipeline\Events\ArtifactCreated;
use App\Modules\Pipeline\Models\Artifact;
use App\Modules\Pipeline\Models\PipelineJob;
use App\Support\Services\ObjectStorage\ObjectStorageService;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class CreateArtifact
{
    public function __construct(
        private readonly ObjectStorageService $storage = new ObjectStorageService,
    ) {}

    public function execute(PipelineJob $job, string $artifactContents, ?array $metadata = null): Artifact
    {
        $job->loadMissing('pipelineRun.pipeline');
        $definition = $job->pipelineRun->definition_snapshot;

        if (! (bool) ($definition['artifact'] ?? false)) {
            throw new RuntimeException('This pipeline run is not configured to produce an artifact.');
        }

        $hash = hash('sha256', $artifactContents);
        $size = strlen($artifactContents);
        $path = $job->pipeline_run_id.'/'.$hash.'.tar.gz';

        $artifact = DB::transaction(function () use ($job, $metadata) {
            return Artifact::firstOrCreate(
                ['pipeline_run_id' => $job->pipeline_run_id],
                [
                    'application_id' => $job->pipelineRun->pipeline->application_id,
                    'status' => ArtifactStatus::Building,
                    'metadata' => $metadata ?? [],
                ],
            );
        });

        try {
            $uploaded = $this->storage->putArtifact($path, $artifactContents);

            if (! $uploaded) {
                throw new RuntimeException('Failed to upload artifact to object storage.');
            }

            return DB::transaction(function () use ($artifact, $hash, $size, $path, $metadata) {
                $artifact->forceFill([
                    'storage_path' => $path,
                    'content_hash' => $hash,
                    'size_bytes' => $size,
                    'metadata' => array_merge($artifact->metadata ?? [], $metadata ?? []),
                ])->save();

                $artifact->transitionTo(ArtifactStatus::Ready);

                event(new ArtifactCreated($artifact->fresh()));

                return $artifact->fresh();
            });
        } catch (Throwable $exception) {
            DB::transaction(function () use ($artifact, $metadata) {
                if ($artifact->status === ArtifactStatus::Building) {
                    $artifact->forceFill([
                        'metadata' => array_merge($artifact->metadata ?? [], $metadata ?? []),
                    ])->save();
                    $artifact->transitionTo(ArtifactStatus::Failed);
                }
            });

            throw $exception;
        }
    }
}
