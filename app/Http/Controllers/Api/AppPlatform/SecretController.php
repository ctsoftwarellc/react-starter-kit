<?php

namespace App\Http\Controllers\Api\AppPlatform;

use App\Http\Controllers\Controller;
use App\Http\Requests\AppPlatform\CreateSecretRequest;
use App\Http\Requests\AppPlatform\UpdateSecretRequest;
use App\Http\Resources\AppPlatform\SecretResource;
use App\Modules\AppPlatform\Actions\CreateSecret;
use App\Modules\AppPlatform\Actions\DeleteSecret;
use App\Modules\AppPlatform\Actions\RevealSecret;
use App\Modules\AppPlatform\Actions\UpdateSecret;
use App\Modules\AppPlatform\DTOs\CreateSecretData;
use App\Modules\AppPlatform\DTOs\UpdateSecretData;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\AppPlatform\Models\Secret;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class SecretController extends Controller
{
    public function index(Environment $environment): AnonymousResourceCollection
    {
        return SecretResource::collection($environment->secrets()->latest()->get());
    }

    public function store(CreateSecretRequest $request, Environment $environment): SecretResource
    {
        $secret = (new CreateSecret)->execute(
            $environment,
            CreateSecretData::from($request->validated()),
        );

        return new SecretResource($secret);
    }

    public function update(UpdateSecretRequest $request, Environment $environment, Secret $secret): SecretResource
    {
        abort_if($secret->environment_id !== $environment->id, 404);

        $secret = (new UpdateSecret)->execute(
            $secret,
            UpdateSecretData::from($request->validated()),
        );

        return new SecretResource($secret);
    }

    public function destroy(Environment $environment, Secret $secret): Response
    {
        abort_if($secret->environment_id !== $environment->id, 404);

        (new DeleteSecret)->execute($secret);

        return response()->noContent();
    }

    public function reveal(Environment $environment, Secret $secret): JsonResponse
    {
        abort_if($secret->environment_id !== $environment->id, 404);

        return response()->json([
            'value' => (new RevealSecret)->execute($secret),
        ]);
    }
}
