<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\AppPlatform\CreateEnvironmentRequest;
use App\Http\Requests\AppPlatform\CreateSecretRequest;
use App\Http\Requests\AppPlatform\ProcessDefinitionRequest;
use App\Http\Requests\AppPlatform\SetEnvironmentVariableRequest;
use App\Http\Requests\AppPlatform\UpdateEnvironmentRequest;
use App\Http\Requests\AppPlatform\UpdateSecretRequest;
use App\Modules\AppPlatform\Actions\CreateEnvironment;
use App\Modules\AppPlatform\Actions\CreateProcessDefinition;
use App\Modules\AppPlatform\Actions\CreateSecret;
use App\Modules\AppPlatform\Actions\DeleteEnvironment;
use App\Modules\AppPlatform\Actions\DeleteEnvironmentVariable;
use App\Modules\AppPlatform\Actions\DeleteProcessDefinition;
use App\Modules\AppPlatform\Actions\DeleteSecret;
use App\Modules\AppPlatform\Actions\RevealSecret;
use App\Modules\AppPlatform\Actions\SetEnvironmentVariable;
use App\Modules\AppPlatform\Actions\UpdateEnvironment;
use App\Modules\AppPlatform\Actions\UpdateProcessDefinition;
use App\Modules\AppPlatform\Actions\UpdateSecret;
use App\Modules\AppPlatform\DTOs\CreateEnvironmentData;
use App\Modules\AppPlatform\DTOs\CreateSecretData;
use App\Modules\AppPlatform\DTOs\ProcessDefinitionData;
use App\Modules\AppPlatform\DTOs\SetEnvironmentVariableData;
use App\Modules\AppPlatform\DTOs\UpdateEnvironmentData;
use App\Modules\AppPlatform\DTOs\UpdateSecretData;
use App\Modules\AppPlatform\Models\Application;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\AppPlatform\Models\EnvironmentVariable;
use App\Modules\AppPlatform\Models\ProcessDefinition;
use App\Modules\AppPlatform\Models\Secret;
use App\Modules\Infrastructure\Models\Cluster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EnvironmentWebController extends Controller
{
    public function store(CreateEnvironmentRequest $request, Application $application): RedirectResponse
    {
        $environment = (new CreateEnvironment)->execute(
            $application,
            CreateEnvironmentData::from($request->validated()),
        );

        return redirect()->route('environments.show', $environment);
    }

    public function show(Environment $environment): Response
    {
        $environment->load(['application.project', 'cluster', 'variables', 'secrets', 'processDefinitions']);

        return Inertia::render('environments/show', [
            'environment' => $environment,
            'application' => $environment->application,
            'clusters' => Cluster::latest()->get(),
        ]);
    }

    public function update(UpdateEnvironmentRequest $request, Environment $environment): RedirectResponse
    {
        (new UpdateEnvironment)->execute(
            $environment,
            UpdateEnvironmentData::from($request->validated()),
        );

        return back();
    }

    public function destroy(Environment $environment): RedirectResponse
    {
        $application = $environment->application;

        (new DeleteEnvironment)->execute($environment);

        return redirect()->route('applications.show', $application);
    }

    public function storeVariable(SetEnvironmentVariableRequest $request, Environment $environment): RedirectResponse
    {
        (new SetEnvironmentVariable)->execute($environment, SetEnvironmentVariableData::from($request->validated()));

        return back();
    }

    public function updateVariable(SetEnvironmentVariableRequest $request, Environment $environment, EnvironmentVariable $variable): RedirectResponse
    {
        abort_if($variable->environment_id !== $environment->id, 404);

        (new SetEnvironmentVariable)->execute($environment, SetEnvironmentVariableData::from($request->validated()));

        return back();
    }

    public function destroyVariable(Environment $environment, EnvironmentVariable $variable): RedirectResponse
    {
        abort_if($variable->environment_id !== $environment->id, 404);

        (new DeleteEnvironmentVariable)->execute($variable);

        return back();
    }

    public function storeSecret(CreateSecretRequest $request, Environment $environment): RedirectResponse
    {
        (new CreateSecret)->execute($environment, CreateSecretData::from($request->validated()));

        return back();
    }

    public function updateSecret(UpdateSecretRequest $request, Environment $environment, Secret $secret): RedirectResponse
    {
        abort_if($secret->environment_id !== $environment->id, 404);

        (new UpdateSecret)->execute($secret, UpdateSecretData::from($request->validated()));

        return back();
    }

    public function destroySecret(Environment $environment, Secret $secret): RedirectResponse
    {
        abort_if($secret->environment_id !== $environment->id, 404);

        (new DeleteSecret)->execute($secret);

        return back();
    }

    public function revealSecret(Environment $environment, Secret $secret): JsonResponse
    {
        abort_if($secret->environment_id !== $environment->id, 404);

        return response()->json([
            'value' => (new RevealSecret)->execute($secret),
        ]);
    }

    public function storeProcess(ProcessDefinitionRequest $request, Environment $environment): RedirectResponse
    {
        (new CreateProcessDefinition)->execute($environment, ProcessDefinitionData::from($request->validated()));

        return back();
    }

    public function updateProcess(ProcessDefinitionRequest $request, Environment $environment, ProcessDefinition $processDefinition): RedirectResponse
    {
        abort_if($processDefinition->environment_id !== $environment->id, 404);

        (new UpdateProcessDefinition)->execute($processDefinition, ProcessDefinitionData::from($request->validated()));

        return back();
    }

    public function destroyProcess(Environment $environment, ProcessDefinition $processDefinition): RedirectResponse
    {
        abort_if($processDefinition->environment_id !== $environment->id, 404);

        (new DeleteProcessDefinition)->execute($processDefinition);

        return back();
    }
}
