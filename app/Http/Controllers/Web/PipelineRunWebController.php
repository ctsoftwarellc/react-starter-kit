<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\Pipeline\ArtifactResource;
use App\Http\Resources\Pipeline\PipelineJobResource;
use App\Http\Resources\Pipeline\PipelineResource;
use App\Http\Resources\Pipeline\PipelineRunResource;
use App\Modules\Pipeline\Models\PipelineRun;
use Inertia\Inertia;
use Inertia\Response;

class PipelineRunWebController extends Controller
{
    public function show(PipelineRun $pipelineRun): Response
    {
        $pipelineRun->load(['pipeline.application.project', 'environment', 'jobs.runner', 'artifact']);

        $stages = $pipelineRun->jobs
            ->groupBy('stage')
            ->map(fn ($jobs, $name) => [
                'name' => $name,
                'jobs' => PipelineJobResource::collection($jobs)->resolve(),
            ])
            ->values()
            ->all();

        return Inertia::render('pipeline-runs/show', [
            'pipelineRun' => (new PipelineRunResource($pipelineRun))->resolve(),
            'pipeline' => (new PipelineResource($pipelineRun->pipeline))->resolve(),
            'application' => $pipelineRun->pipeline->application,
            'stages' => $stages,
            'artifact' => $pipelineRun->artifact ? (new ArtifactResource($pipelineRun->artifact))->resolve() : null,
        ]);
    }
}
