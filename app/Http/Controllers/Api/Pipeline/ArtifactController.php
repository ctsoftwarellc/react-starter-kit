<?php

namespace App\Http\Controllers\Api\Pipeline;

use App\Http\Controllers\Controller;
use App\Http\Resources\Pipeline\ArtifactResource;
use App\Modules\AppPlatform\Models\Application;
use App\Modules\Pipeline\Models\Artifact;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ArtifactController extends Controller
{
    public function index(Application $application): AnonymousResourceCollection
    {
        return ArtifactResource::collection(
            $application->artifacts()->with(['pipelineRun.pipeline'])->latest()->paginate(15),
        );
    }

    public function show(Artifact $artifact): ArtifactResource
    {
        return new ArtifactResource($artifact->load(['pipelineRun.pipeline.application', 'application']));
    }
}
