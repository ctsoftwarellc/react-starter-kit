<?php

namespace App\Http\Controllers\Api\ServiceManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceManagement\ProvisionStorageBucketRequest;
use App\Http\Resources\ServiceManagement\StorageBucketResource;
use App\Modules\ServiceManagement\Actions\DeleteStorageBucket;
use App\Modules\ServiceManagement\Actions\ProvisionStorageBucket;
use App\Modules\ServiceManagement\Actions\RotateStorageCredentials;
use App\Modules\ServiceManagement\DTOs\ProvisionStorageBucketData;
use App\Modules\ServiceManagement\Models\StorageBucket;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use InvalidArgumentException;

class StorageBucketController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return StorageBucketResource::collection(StorageBucket::latest()->paginate(15));
    }

    public function store(ProvisionStorageBucketRequest $request): StorageBucketResource
    {
        $storageBucket = (new ProvisionStorageBucket)->execute(ProvisionStorageBucketData::from($request->validated()));

        return new StorageBucketResource($storageBucket);
    }

    public function show(StorageBucket $storageBucket): StorageBucketResource
    {
        return new StorageBucketResource($storageBucket->load('serviceBindings.environment'));
    }

    public function destroy(StorageBucket $storageBucket): Response
    {
        try {
            (new DeleteStorageBucket)->execute($storageBucket);
        } catch (InvalidArgumentException $exception) {
            abort(422, $exception->getMessage());
        }

        return response()->noContent();
    }

    public function rotateCredentials(StorageBucket $storageBucket): StorageBucketResource
    {
        $storageBucket = (new RotateStorageCredentials)->execute($storageBucket);

        return new StorageBucketResource($storageBucket);
    }
}
