<?php

namespace App\Modules\ServiceManagement\Actions;

use App\Modules\AppPlatform\Events\EnvironmentConfigChanged;
use App\Modules\ServiceManagement\Models\CacheInstance;
use App\Modules\ServiceManagement\Services\RedisProvisioner;
use App\Modules\ServiceManagement\Services\ServiceSecretManager;
use Illuminate\Support\Facades\DB;

class RotateCacheCredentials
{
    public function __construct(
        private readonly ServiceSecretManager $serviceSecretManager = new ServiceSecretManager,
    ) {}

    public function execute(CacheInstance $cacheInstance): CacheInstance
    {
        $password = (new RedisProvisioner)->rotateCredentials();

        return DB::transaction(function () use ($cacheInstance, $password) {
            $cacheInstance->update([
                'password' => $password,
            ]);

            $cacheInstance = $cacheInstance->fresh();
            $cacheInstance->load('serviceBindings.environment');

            foreach ($cacheInstance->serviceBindings as $binding) {
                $this->serviceSecretManager->syncBindingSecrets($binding->environment, $binding);

                event(new EnvironmentConfigChanged($binding->environment, 'cache_credentials_rotated', [
                    'binding_name' => $binding->binding_name,
                ]));
            }

            return $cacheInstance;
        });
    }
}
