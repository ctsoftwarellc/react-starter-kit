<?php

namespace App\Http\Controllers\Api\ServiceManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceManagement\ProvisionCacheRequest;
use App\Http\Resources\ServiceManagement\CacheInstanceResource;
use App\Modules\ServiceManagement\Actions\DeleteCache;
use App\Modules\ServiceManagement\Actions\ProvisionCache;
use App\Modules\ServiceManagement\Actions\RotateCacheCredentials;
use App\Modules\ServiceManagement\DTOs\ProvisionCacheData;
use App\Modules\ServiceManagement\Models\CacheInstance;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use InvalidArgumentException;

class CacheInstanceController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return CacheInstanceResource::collection(CacheInstance::with('cluster')->latest()->paginate(15));
    }

    public function store(ProvisionCacheRequest $request): CacheInstanceResource
    {
        try {
            $cacheInstance = (new ProvisionCache)->execute(ProvisionCacheData::from($request->validated()));
        } catch (InvalidArgumentException $exception) {
            abort(422, $exception->getMessage());
        }

        return new CacheInstanceResource($cacheInstance->load('cluster'));
    }

    public function show(CacheInstance $cacheInstance): CacheInstanceResource
    {
        return new CacheInstanceResource($cacheInstance->load(['cluster', 'serviceBindings.environment']));
    }

    public function destroy(CacheInstance $cacheInstance): Response
    {
        try {
            (new DeleteCache)->execute($cacheInstance);
        } catch (InvalidArgumentException $exception) {
            abort(422, $exception->getMessage());
        }

        return response()->noContent();
    }

    public function rotateCredentials(CacheInstance $cacheInstance): CacheInstanceResource
    {
        $cacheInstance = (new RotateCacheCredentials)->execute($cacheInstance);

        return new CacheInstanceResource($cacheInstance->load('cluster'));
    }
}
