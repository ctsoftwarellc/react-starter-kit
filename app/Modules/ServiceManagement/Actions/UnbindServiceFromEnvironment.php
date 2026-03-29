<?php

namespace App\Modules\ServiceManagement\Actions;

use App\Modules\AppPlatform\Events\EnvironmentConfigChanged;
use App\Modules\ServiceManagement\Models\ServiceBinding;
use App\Modules\ServiceManagement\Services\ServiceSecretManager;
use Illuminate\Support\Facades\DB;

class UnbindServiceFromEnvironment
{
    public function __construct(
        private readonly ServiceSecretManager $serviceSecretManager = new ServiceSecretManager,
    ) {}

    public function execute(ServiceBinding $serviceBinding): void
    {
        DB::transaction(function () use ($serviceBinding) {
            $serviceBinding->loadMissing(['environment', 'databaseInstance', 'cacheInstance', 'storageBucket']);

            $this->serviceSecretManager->removeBindingSecrets($serviceBinding->environment, $serviceBinding);

            event(new EnvironmentConfigChanged($serviceBinding->environment, 'service_unbound', [
                'binding_name' => $serviceBinding->binding_name,
                'service_type' => $serviceBinding->service_type->value,
            ]));

            $serviceBinding->delete();
        });
    }
}
