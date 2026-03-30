<?php

namespace App\Modules\Pipeline\Services;

use App\Modules\Pipeline\Models\PipelineJob;
use App\Support\Services\ObjectStorage\ObjectStorageService;

class LogStreamer
{
    public function __construct(
        private readonly ObjectStorageService $storage = new ObjectStorageService,
    ) {}

    public function append(PipelineJob $job, string $chunk): string
    {
        $path = $job->log_path ?? $this->defaultPath($job);

        if ($job->log_path !== $path) {
            $job->forceFill(['log_path' => $path])->save();
        }

        $this->storage->appendLog($path, $chunk);

        return $path;
    }

    public function read(PipelineJob $job, ?int $offset = null, ?int $length = null): array|string
    {
        if ($job->log_path === null || ! $this->storage->exists('logs/'.$job->log_path)) {
            return $length === null && $offset === null ? '' : ['content' => '', 'offset' => 0, 'length' => 0];
        }

        $contents = $this->storage->getLog($job->log_path) ?? '';

        if ($offset === null && $length === null) {
            return $contents;
        }

        $offset = max(0, $offset ?? 0);
        $length = $length ?? max(0, strlen($contents) - $offset);
        $slice = substr($contents, $offset, $length) ?: '';

        return [
            'content' => $slice,
            'offset' => $offset,
            'length' => strlen($slice),
        ];
    }

    private function defaultPath(PipelineJob $job): string
    {
        return $job->pipeline_run_id.'/'.$job->id.'.log';
    }
}
