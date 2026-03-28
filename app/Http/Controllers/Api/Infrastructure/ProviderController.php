<?php

namespace App\Http\Controllers\Api\Infrastructure;

use App\Http\Controllers\Controller;
use App\Http\Requests\Infrastructure\CreateProviderRequest;
use App\Http\Requests\Infrastructure\UpdateProviderRequest;
use App\Http\Resources\Infrastructure\ProviderResource;
use App\Modules\Infrastructure\Actions\CreateProvider;
use App\Modules\Infrastructure\Actions\DeleteProvider;
use App\Modules\Infrastructure\Actions\TestProviderConnection;
use App\Modules\Infrastructure\Actions\UpdateProvider;
use App\Modules\Infrastructure\DTOs\CreateProviderData;
use App\Modules\Infrastructure\DTOs\UpdateProviderData;
use App\Modules\Infrastructure\Models\Provider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ProviderController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $providers = Provider::latest()->paginate(15);

        return ProviderResource::collection($providers);
    }

    public function store(CreateProviderRequest $request): ProviderResource
    {
        $provider = (new CreateProvider)->execute(
            CreateProviderData::from($request->validated()),
        );

        return new ProviderResource($provider);
    }

    public function show(Provider $provider): ProviderResource
    {
        return new ProviderResource($provider);
    }

    public function update(UpdateProviderRequest $request, Provider $provider): ProviderResource
    {
        $provider = (new UpdateProvider)->execute(
            $provider,
            UpdateProviderData::from($request->validated()),
        );

        return new ProviderResource($provider);
    }

    public function destroy(Provider $provider): Response
    {
        (new DeleteProvider)->execute($provider);

        return response()->noContent();
    }

    public function test(Provider $provider): JsonResponse
    {
        $success = (new TestProviderConnection)->execute($provider);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Connection successful.' : 'Connection failed.',
        ]);
    }
}
