<?php

namespace App\Http\Controllers\Api\AppPlatform;

use App\Http\Controllers\Controller;
use App\Http\Requests\AppPlatform\SetEnvironmentVariableRequest;
use App\Http\Resources\AppPlatform\EnvironmentVariableResource;
use App\Modules\AppPlatform\Actions\DeleteEnvironmentVariable;
use App\Modules\AppPlatform\Actions\SetEnvironmentVariable;
use App\Modules\AppPlatform\DTOs\SetEnvironmentVariableData;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\AppPlatform\Models\EnvironmentVariable;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class EnvironmentVariableController extends Controller
{
    public function index(Environment $environment): AnonymousResourceCollection
    {
        return EnvironmentVariableResource::collection($environment->variables()->latest()->get());
    }

    public function store(SetEnvironmentVariableRequest $request, Environment $environment): EnvironmentVariableResource
    {
        $variable = (new SetEnvironmentVariable)->execute(
            $environment,
            SetEnvironmentVariableData::from($request->validated()),
        );

        return new EnvironmentVariableResource($variable);
    }

    public function update(SetEnvironmentVariableRequest $request, Environment $environment, EnvironmentVariable $variable): EnvironmentVariableResource
    {
        $variable = (new SetEnvironmentVariable)->execute(
            $environment,
            SetEnvironmentVariableData::from($request->validated()),
            $variable,
        );

        return new EnvironmentVariableResource($variable);
    }

    public function destroy(Environment $environment, EnvironmentVariable $variable): Response
    {
        (new DeleteEnvironmentVariable)->execute($variable);

        return response()->noContent();
    }
}
