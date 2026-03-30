<?php

namespace App\Modules\Pipeline\Actions;

use App\Modules\Pipeline\Models\PipelineRun;

class RetryPipelineRun
{
    public function execute(PipelineRun $run): PipelineRun
    {
        return (new TriggerPipelineRun)->execute($run->pipeline, $run->toRetryData());
    }
}
