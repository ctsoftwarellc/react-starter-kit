<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\AppPlatform\CreateProjectRequest;
use App\Http\Requests\AppPlatform\UpdateProjectRequest;
use App\Modules\AppPlatform\Actions\CreateProject;
use App\Modules\AppPlatform\Actions\DeleteProject;
use App\Modules\AppPlatform\Actions\UpdateProject;
use App\Modules\AppPlatform\DTOs\CreateProjectData;
use App\Modules\AppPlatform\DTOs\UpdateProjectData;
use App\Modules\AppPlatform\Models\Project;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProjectWebController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('projects/index', [
            'projects' => Project::latest()->paginate(15),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('projects/create');
    }

    public function store(CreateProjectRequest $request): RedirectResponse
    {
        $project = (new CreateProject)->execute(
            CreateProjectData::from($request->validated()),
        );

        return redirect()->route('projects.show', $project);
    }

    public function show(Project $project): Response
    {
        return Inertia::render('projects/show', [
            'project' => $project,
        ]);
    }

    public function edit(Project $project): Response
    {
        return Inertia::render('projects/edit', [
            'project' => $project,
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        (new UpdateProject)->execute(
            $project,
            UpdateProjectData::from($request->validated()),
        );

        return redirect()->route('projects.show', $project->fresh());
    }

    public function destroy(Project $project): RedirectResponse
    {
        (new DeleteProject)->execute($project);

        return redirect()->route('projects.index');
    }
}
