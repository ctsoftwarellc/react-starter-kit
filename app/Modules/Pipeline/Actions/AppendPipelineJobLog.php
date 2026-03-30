<?php

namespace App\Modules\Pipeline\Actions;

use App\Modules\Pipeline\Models\PipelineJob;
use App\Modules\Pipeline\Services\LogStreamer;

class AppendPipelineJobLog
{
    public function __construct(
        private readonly LogStreamer $streamer = new LogStreamer,
    ) {}

    public function execute(PipelineJob $job, string $chunk): void
    {
        $this->streamer->append($job, $chunk);
    }
}
