<?php

namespace App\Http\Controllers\Api\ServiceManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceManagement\BindServiceRequest;
use App\Http\Resources\ServiceManagement\ServiceBindingResource;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\ServiceManagement\Actions\BindServiceToEnvironment;
use App\Modules\ServiceManagement\Actions\UnbindServiceFromEnvironment;
use App\Modules\ServiceManagement\DTOs\BindServiceData;
use App\Modules\ServiceManagement\Models\ServiceBinding;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ServiceBindingController extends Controller
{
    public function index(Environment $environment): AnonymousResourceCollection
    {
        return ServiceBindingResource::collection(
            $environment->serviceBindings()->with(['databaseInstance', 'cacheInstance', 'storageBucket'])->latest()->get(),
        );
    }

    public function store(BindServiceRequest $request, Environment $environment): ServiceBindingResource
    {
        $binding = (new BindServiceToEnvironment)->execute($environment, BindServiceData::from($request->validated()));

        return new ServiceBindingResource($binding->load(['environment', 'databaseInstance', 'cacheInstance', 'storageBucket']));
    }

    public function destroy(Environment $environment, ServiceBinding $serviceBinding): Response
    {
        (new UnbindServiceFromEnvironment)->execute($serviceBinding);

        return response()->noContent();
    }
}
