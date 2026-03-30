<?php

namespace App\Http\Controllers\Api\Pipeline;

use App\Http\Controllers\Controller;
use App\Http\Resources\Pipeline\PipelineRunResource;
use App\Modules\Pipeline\Actions\CancelPipelineRun;
use App\Modules\Pipeline\Actions\RetryPipelineRun;
use App\Modules\Pipeline\Models\PipelineRun;

class PipelineRunController extends Controller
{
    public function show(PipelineRun $pipelineRun): PipelineRunResource
    {
        return new PipelineRunResource($pipelineRun->load(['pipeline.application', 'environment', 'jobs.runner', 'artifact']));
    }

    public function cancel(PipelineRun $pipelineRun): PipelineRunResource
    {
        $pipelineRun = (new CancelPipelineRun)->execute($pipelineRun);

        return new PipelineRunResource($pipelineRun->load(['pipeline.application', 'environment', 'jobs.runner', 'artifact']));
    }

    public function retry(PipelineRun $pipelineRun): PipelineRunResource
    {
        $pipelineRun->loadMissing('pipeline');
        $retry = (new RetryPipelineRun)->execute($pipelineRun);

        return new PipelineRunResource($retry->load(['pipeline.application', 'environment', 'jobs.runner', 'artifact']));
    }
}
