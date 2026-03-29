<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\AppPlatform\CreateApplicationRequest;
use App\Http\Requests\AppPlatform\UpdateApplicationRequest;
use App\Modules\AppPlatform\Actions\CreateApplication;
use App\Modules\AppPlatform\Actions\DeleteApplication;
use App\Modules\AppPlatform\Actions\UpdateApplication;
use App\Modules\AppPlatform\DTOs\CreateApplicationData;
use App\Modules\AppPlatform\DTOs\UpdateApplicationData;
use App\Modules\AppPlatform\Models\Application;
use App\Modules\AppPlatform\Models\GitConnection;
use App\Modules\AppPlatform\Models\Project;
use App\Modules\Infrastructure\Models\Cluster;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationWebController extends Controller
{
    public function create(Project $project): Response
    {
        return Inertia::render('applications/create', [
            'project' => $project,
            'gitConnections' => GitConnection::latest()->get(),
            'clusters' => Cluster::latest()->get(),
        ]);
    }

    public function store(CreateApplicationRequest $request, Project $project): RedirectResponse
    {
        $application = (new CreateApplication)->execute(
            $project,
            CreateApplicationData::from($request->validated()),
        );

        return redirect()->route('applications.show', $application);
    }

    public function show(Application $application): Response
    {
        $application->load(['project', 'gitConnection', 'environments.cluster', 'webhooks']);

        return Inertia::render('applications/show', [
            'application' => $application,
            'project' => $application->project,
            'gitConnections' => GitConnection::latest()->get(),
            'clusters' => Cluster::latest()->get(),
        ]);
    }

    public function update(UpdateApplicationRequest $request, Application $application): RedirectResponse
    {
        (new UpdateApplication)->execute(
            $application,
            UpdateApplicationData::from($request->validated()),
        );

        return redirect()->route('applications.show', $application->fresh());
    }

    public function destroy(Application $application): RedirectResponse
    {
        $project = $application->project;

        (new DeleteApplication)->execute($application);

        return redirect()->route('projects.show', $project);
    }
}
