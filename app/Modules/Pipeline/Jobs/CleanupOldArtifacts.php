<?php

namespace App\Modules\Pipeline\Jobs;

use App\Modules\Pipeline\Enums\ArtifactStatus;
use App\Modules\Pipeline\Models\Artifact;
use App\Support\Enums\QueueName;
use App\Support\Services\ObjectStorage\ObjectStorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class CleanupOldArtifacts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    public function handle(): void
    {
        $cutoff = now()->subDays((int) config('helm.retention.artifacts_days', 30));
        $storage = new ObjectStorageService;

        Artifact::query()
            ->whereIn('status', [
                ArtifactStatus::Ready->value,
                ArtifactStatus::Deployed->value,
                ArtifactStatus::Superseded->value,
            ])
            ->where('updated_at', '<=', $cutoff)
            ->each(function (Artifact $artifact) use ($storage): void {
                if ($artifact->storage_path !== null) {
                    $storage->delete('artifacts/'.$artifact->storage_path);
                }

                if (in_array($artifact->status, [ArtifactStatus::Ready, ArtifactStatus::Deployed], true)) {
                    $artifact->transitionTo(ArtifactStatus::Expired);

                    return;
                }

                $artifact->transitionTo(ArtifactStatus::Expired);
            });
    }

    public function failed(Throwable $exception): void
    {
        report($exception);
    }

    public function queue(): string
    {
        return QueueName::Maintenance->value;
    }
}
