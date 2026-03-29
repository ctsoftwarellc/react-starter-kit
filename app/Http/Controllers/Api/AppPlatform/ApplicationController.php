<?php

namespace App\Http\Controllers\Api\AppPlatform;

use App\Http\Controllers\Controller;
use App\Http\Requests\AppPlatform\CreateApplicationRequest;
use App\Http\Requests\AppPlatform\UpdateApplicationRequest;
use App\Http\Resources\AppPlatform\ApplicationResource;
use App\Modules\AppPlatform\Actions\CreateApplication;
use App\Modules\AppPlatform\Actions\DeleteApplication;
use App\Modules\AppPlatform\Actions\UpdateApplication;
use App\Modules\AppPlatform\DTOs\CreateApplicationData;
use App\Modules\AppPlatform\DTOs\UpdateApplicationData;
use App\Modules\AppPlatform\Models\Application;
use App\Modules\AppPlatform\Models\Project;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ApplicationController extends Controller
{
    public function index(Project $project): AnonymousResourceCollection
    {
        return ApplicationResource::collection(
            $project->applications()->with('gitConnection')->latest()->paginate(15),
        );
    }

    public function store(CreateApplicationRequest $request, Project $project): ApplicationResource
    {
        $application = (new CreateApplication)->execute(
            $project,
            CreateApplicationData::from($request->validated()),
        );

        return new ApplicationResource($application->load(['project', 'gitConnection']));
    }

    public function show(Application $application): ApplicationResource
    {
        return new ApplicationResource($application->load(['project', 'gitConnection', 'environments.cluster']));
    }

    public function update(UpdateApplicationRequest $request, Application $application): ApplicationResource
    {
        $application = (new UpdateApplication)->execute(
            $application,
            UpdateApplicationData::from($request->validated()),
        );

        return new ApplicationResource($application->load(['project', 'gitConnection']));
    }

    public function destroy(Application $application): Response
    {
        (new DeleteApplication)->execute($application);

        return response()->noContent();
    }
}
