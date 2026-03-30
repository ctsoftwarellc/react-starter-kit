<?php

namespace App\Modules\Pipeline\Events;

use App\Modules\Pipeline\Models\PipelineRun;

class PipelineRunCompleted
{
    public function __construct(public PipelineRun $pipelineRun) {}
}
