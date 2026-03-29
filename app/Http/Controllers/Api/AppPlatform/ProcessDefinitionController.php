<?php

namespace App\Http\Controllers\Api\AppPlatform;

use App\Http\Controllers\Controller;
use App\Http\Requests\AppPlatform\ProcessDefinitionRequest;
use App\Http\Resources\AppPlatform\ProcessDefinitionResource;
use App\Modules\AppPlatform\Actions\CreateProcessDefinition;
use App\Modules\AppPlatform\Actions\DeleteProcessDefinition;
use App\Modules\AppPlatform\Actions\UpdateProcessDefinition;
use App\Modules\AppPlatform\DTOs\ProcessDefinitionData;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\AppPlatform\Models\ProcessDefinition;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ProcessDefinitionController extends Controller
{
    public function index(Environment $environment): AnonymousResourceCollection
    {
        return ProcessDefinitionResource::collection($environment->processDefinitions()->latest()->get());
    }

    public function store(ProcessDefinitionRequest $request, Environment $environment): ProcessDefinitionResource
    {
        $processDefinition = (new CreateProcessDefinition)->execute(
            $environment,
            ProcessDefinitionData::from($request->validated()),
        );

        return new ProcessDefinitionResource($processDefinition);
    }

    public function update(ProcessDefinitionRequest $request, Environment $environment, ProcessDefinition $processDefinition): ProcessDefinitionResource
    {
        abort_if($processDefinition->environment_id !== $environment->id, 404);

        $processDefinition = (new UpdateProcessDefinition)->execute(
            $processDefinition,
            ProcessDefinitionData::from($request->validated()),
        );

        return new ProcessDefinitionResource($processDefinition);
    }

    public function destroy(Environment $environment, ProcessDefinition $processDefinition): Response
    {
        abort_if($processDefinition->environment_id !== $environment->id, 404);

        (new DeleteProcessDefinition)->execute($processDefinition);

        return response()->noContent();
    }
}
