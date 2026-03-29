<?php

namespace App\Http\Controllers\Api\ServiceManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceManagement\ProvisionDatabaseRequest;
use App\Http\Resources\ServiceManagement\DatabaseInstanceResource;
use App\Modules\ServiceManagement\Actions\DeleteDatabase;
use App\Modules\ServiceManagement\Actions\ProvisionDatabase;
use App\Modules\ServiceManagement\Actions\RotateDatabaseCredentials;
use App\Modules\ServiceManagement\DTOs\ProvisionDatabaseData;
use App\Modules\ServiceManagement\Models\DatabaseInstance;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use InvalidArgumentException;

class DatabaseInstanceController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return DatabaseInstanceResource::collection(DatabaseInstance::with('cluster')->latest()->paginate(15));
    }

    public function store(ProvisionDatabaseRequest $request): DatabaseInstanceResource
    {
        try {
            $databaseInstance = (new ProvisionDatabase)->execute(ProvisionDatabaseData::from($request->validated()));
        } catch (InvalidArgumentException $exception) {
            abort(422, $exception->getMessage());
        }

        return new DatabaseInstanceResource($databaseInstance->load('cluster'));
    }

    public function show(DatabaseInstance $databaseInstance): DatabaseInstanceResource
    {
        return new DatabaseInstanceResource($databaseInstance->load(['cluster', 'serviceBindings.environment']));
    }

    public function destroy(DatabaseInstance $databaseInstance): Response
    {
        try {
            (new DeleteDatabase)->execute($databaseInstance);
        } catch (InvalidArgumentException $exception) {
            abort(422, $exception->getMessage());
        }

        return response()->noContent();
    }

    public function rotateCredentials(DatabaseInstance $databaseInstance): DatabaseInstanceResource
    {
        $databaseInstance = (new RotateDatabaseCredentials)->execute($databaseInstance);

        return new DatabaseInstanceResource($databaseInstance->load('cluster'));
    }
}
