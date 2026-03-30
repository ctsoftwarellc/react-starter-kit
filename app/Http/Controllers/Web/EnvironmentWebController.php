<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\AppPlatform\CreateEnvironmentRequest;
use App\Http\Requests\AppPlatform\CreateSecretRequest;
use App\Http\Requests\AppPlatform\ProcessDefinitionRequest;
use App\Http\Requests\AppPlatform\SetEnvironmentVariableRequest;
use App\Http\Requests\AppPlatform\UpdateEnvironmentRequest;
use App\Http\Requests\AppPlatform\UpdateSecretRequest;
use App\Http\Requests\Deployment\ApplyRuntimeProfileRequest;
use App\Http\Requests\Deployment\ExecuteRemoteCommandRequest;
use App\Http\Requests\Deployment\InitiateDeploymentRequest;
use App\Http\Requests\Deployment\RollbackDeploymentRequest;
use App\Http\Requests\Deployment\RuntimeProfileRequest;
use App\Http\Requests\Deployment\ServerRoleProfileRequest;
use App\Http\Requests\Deployment\UpsertHealthCheckRequest;
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
use App\Modules\Deployment\Actions\ApplyRuntimeProfile;
use App\Modules\Deployment\Actions\CreateRuntimeProfile;
use App\Modules\Deployment\Actions\ExecuteRemoteCommand;
use App\Modules\Deployment\Actions\InitiateDeployment;
use App\Modules\Deployment\Actions\RollbackDeployment;
use App\Modules\Deployment\Actions\SaveServerRoleProfile;
use App\Modules\Deployment\Actions\UpdateRuntimeProfile;
use App\Modules\Deployment\Actions\UpsertHealthCheck;
use App\Modules\Deployment\Enums\DeploymentStrategy;
use App\Modules\Deployment\Models\RuntimeProfile;
use App\Modules\Deployment\Models\ServerRoleProfile;
use App\Modules\Deployment\Services\EnvironmentPageDataBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

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
        return Inertia::render('environments/show', (new EnvironmentPageDataBuilder)->execute($environment));
    }

    public function deploy(InitiateDeploymentRequest $request, Environment $environment): RedirectResponse
    {
        $artifact = $environment->application->artifacts()->whereKey($request->validated('artifact_id'))->firstOrFail();

        $deployment = (new InitiateDeployment)->execute(
            $environment,
            $artifact,
            DeploymentStrategy::from($request->validated('strategy')),
            $request->user(),
        );

        return redirect()->route('deployments.show', $deployment);
    }

    public function rollback(RollbackDeploymentRequest $request, Environment $environment): RedirectResponse
    {
        $release = $environment->releases()->whereKey($request->validated('release_id'))->firstOrFail();

        $deployment = (new RollbackDeployment)->execute($environment, $release, $request->user());

        return redirect()->route('deployments.show', $deployment);
    }

    public function upsertHealthCheck(UpsertHealthCheckRequest $request, Environment $environment): RedirectResponse
    {
        (new UpsertHealthCheck)->execute($environment, $request->validated());

        return back();
    }

    public function storeRuntimeProfile(RuntimeProfileRequest $request, Environment $environment): RedirectResponse
    {
        (new CreateRuntimeProfile)->execute($environment->application, $request->validated());

        return back();
    }

    public function updateRuntimeProfile(RuntimeProfileRequest $request, Environment $environment, RuntimeProfile $runtimeProfile): RedirectResponse
    {
        (new UpdateRuntimeProfile)->execute($runtimeProfile, $request->validated());

        return back();
    }

    public function applyRuntimeProfile(ApplyRuntimeProfileRequest $request, Environment $environment): RedirectResponse
    {
        $runtimeProfile = $environment->application->runtimeProfiles()->whereKey($request->validated('runtime_profile_id'))->firstOrFail();

        (new ApplyRuntimeProfile)->execute($environment, $runtimeProfile);

        return back();
    }

    public function upsertServerRoleProfile(ServerRoleProfileRequest $request, Environment $environment): RedirectResponse
    {
        (new SaveServerRoleProfile)->execute($environment, $request->validated());

        return back();
    }

    public function updateServerRoleProfile(ServerRoleProfileRequest $request, Environment $environment, ServerRoleProfile $serverRoleProfile): RedirectResponse
    {
        (new SaveServerRoleProfile)->execute($environment, $request->validated(), $serverRoleProfile);

        return back();
    }

    public function executeRemoteCommand(ExecuteRemoteCommandRequest $request, Environment $environment): RedirectResponse
    {
        try {
            (new ExecuteRemoteCommand)->execute($environment, $request->validated());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['command' => $exception->getMessage()]);
        }

        return back();
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
        (new SetEnvironmentVariable)->execute($environment, SetEnvironmentVariableData::from($request->validated()), $variable);

        return back();
    }

    public function destroyVariable(Environment $environment, EnvironmentVariable $variable): RedirectResponse
    {
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
        (new UpdateSecret)->execute($secret, UpdateSecretData::from($request->validated()));

        return back();
    }

    public function destroySecret(Environment $environment, Secret $secret): RedirectResponse
    {
        (new DeleteSecret)->execute($secret);

        return back();
    }

    public function revealSecret(Environment $environment, Secret $secret): JsonResponse
    {
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
        (new UpdateProcessDefinition)->execute($processDefinition, ProcessDefinitionData::from($request->validated()));

        return back();
    }

    public function destroyProcess(Environment $environment, ProcessDefinition $processDefinition): RedirectResponse
    {
        (new DeleteProcessDefinition)->execute($processDefinition);

        return back();
    }
}
