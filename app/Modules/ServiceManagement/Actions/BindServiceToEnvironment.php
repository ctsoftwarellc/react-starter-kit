<?php

namespace App\Modules\ServiceManagement\Actions;

use App\Modules\AppPlatform\Events\EnvironmentConfigChanged;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\ServiceManagement\DTOs\BindServiceData;
use App\Modules\ServiceManagement\Enums\ServiceType;
use App\Modules\ServiceManagement\Models\ServiceBinding;
use App\Modules\ServiceManagement\Services\ServiceSecretManager;
use Illuminate\Support\Facades\DB;

class BindServiceToEnvironment
{
    public function __construct(
        private readonly ServiceSecretManager $serviceSecretManager = new ServiceSecretManager,
    ) {}

    public function execute(Environment $environment, BindServiceData $data): ServiceBinding
    {
        return DB::transaction(function () use ($environment, $data) {
            $binding = ServiceBinding::create([
                'environment_id' => $environment->id,
                'service_type' => $data->serviceType,
                'database_instance_id' => $data->serviceType === ServiceType::Database ? $data->databaseInstanceId : null,
                'cache_instance_id' => $data->serviceType === ServiceType::Cache ? $data->cacheInstanceId : null,
                'storage_bucket_id' => $data->serviceType === ServiceType::Storage ? $data->storageBucketId : null,
                'binding_name' => $data->bindingName,
                'config' => $data->config,
            ]);

            $binding->load(['databaseInstance', 'cacheInstance', 'storageBucket']);

            $this->serviceSecretManager->syncBindingSecrets($environment, $binding);

            event(new EnvironmentConfigChanged($environment, 'service_bound', [
                'binding_name' => $binding->binding_name,
                'service_type' => $binding->service_type->value,
            ]));

            return $binding;
        });
    }
}
