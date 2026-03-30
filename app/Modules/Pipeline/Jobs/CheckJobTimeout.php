<?php

namespace App\Modules\Pipeline\Jobs;

use App\Modules\Pipeline\Actions\FailPipelineJob;
use App\Modules\Pipeline\DTOs\FailPipelineJobData;
use App\Modules\Pipeline\Enums\PipelineJobStatus;
use App\Modules\Pipeline\Models\PipelineJob;
use App\Support\Enums\QueueName;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class CheckJobTimeout implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 60;

    public function handle(): void
    {
        $threshold = now()->subSeconds((int) config('helm.pipeline.default_job_timeout', 900));

        PipelineJob::query()
            ->where('status', PipelineJobStatus::Running->value)
            ->whereNotNull('started_at')
            ->where('started_at', '<=', $threshold)
            ->each(function (PipelineJob $job): void {
                (new FailPipelineJob)->execute($job, new FailPipelineJobData(
                    status: PipelineJobStatus::TimedOut,
                    exitCode: 124,
                ));
            });
    }

    public function failed(Throwable $exception): void
    {
        report($exception);
    }

    public function queue(): string
    {
        return QueueName::Pipeline->value;
    }
}
