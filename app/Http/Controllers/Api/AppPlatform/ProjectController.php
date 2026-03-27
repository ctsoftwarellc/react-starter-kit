<?php

namespace App\Http\Controllers\Api\AppPlatform;

use App\Http\Controllers\Controller;
use App\Http\Requests\AppPlatform\CreateProjectRequest;
use App\Http\Requests\AppPlatform\UpdateProjectRequest;
use App\Http\Resources\AppPlatform\ProjectResource;
use App\Modules\AppPlatform\Actions\CreateProject;
use App\Modules\AppPlatform\Actions\DeleteProject;
use App\Modules\AppPlatform\Actions\UpdateProject;
use App\Modules\AppPlatform\DTOs\CreateProjectData;
use App\Modules\AppPlatform\DTOs\UpdateProjectData;
use App\Modules\AppPlatform\Models\Project;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ProjectController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $projects = Project::latest()->paginate(15);

        return ProjectResource::collection($projects);
    }

    public function store(CreateProjectRequest $request): ProjectResource
    {
        $project = (new CreateProject)->execute(
            CreateProjectData::from($request->validated()),
        );

        return new ProjectResource($project);
    }

    public function show(Project $project): ProjectResource
    {
        return new ProjectResource($project);
    }

    public function update(UpdateProjectRequest $request, Project $project): ProjectResource
    {
        $project = (new UpdateProject)->execute(
            $project,
            UpdateProjectData::from($request->validated()),
        );

        return new ProjectResource($project);
    }

    public function destroy(Project $project): Response
    {
        (new DeleteProject)->execute($project);

        return response()->noContent();
    }
}
