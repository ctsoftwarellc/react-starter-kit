<?php

namespace App\Modules\Pipeline\Events;

use App\Modules\Pipeline\Models\PipelineJob;

class PipelineJobCompleted
{
    public function __construct(public PipelineJob $pipelineJob) {}
}
