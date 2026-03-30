<?php

namespace App\Http\Controllers\Api\Deployment;

use App\Http\Controllers\Controller;
use App\Http\Resources\Deployment\ReleaseResource;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Models\Release;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReleaseController extends Controller
{
    public function index(Environment $environment): AnonymousResourceCollection
    {
        return ReleaseResource::collection(
            $environment->releases()
                ->with(['artifact.pipelineRun.pipeline'])
                ->latestVersionFirst()
                ->paginate(15),
        );
    }

    public function show(Release $release): ReleaseResource
    {
        return new ReleaseResource($release->load(['artifact.pipelineRun.pipeline.application', 'environment.application']));
    }
}
