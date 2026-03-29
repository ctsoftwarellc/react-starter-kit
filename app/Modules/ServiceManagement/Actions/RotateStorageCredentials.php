<?php

namespace App\Modules\ServiceManagement\Actions;

use App\Modules\AppPlatform\Events\EnvironmentConfigChanged;
use App\Modules\ServiceManagement\Models\StorageBucket;
use App\Modules\ServiceManagement\Services\ServiceSecretManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RotateStorageCredentials
{
    public function __construct(
        private readonly ServiceSecretManager $serviceSecretManager = new ServiceSecretManager,
    ) {}

    public function execute(StorageBucket $storageBucket): StorageBucket
    {
        return DB::transaction(function () use ($storageBucket) {
            $storageBucket->update([
                'access_key' => Str::upper(Str::random(20)),
                'secret_key' => Str::random(40),
            ]);

            $storageBucket = $storageBucket->fresh();
            $storageBucket->load('serviceBindings.environment');

            foreach ($storageBucket->serviceBindings as $binding) {
                $this->serviceSecretManager->syncBindingSecrets($binding->environment, $binding);

                event(new EnvironmentConfigChanged($binding->environment, 'storage_credentials_rotated', [
                    'binding_name' => $binding->binding_name,
                ]));
            }

            return $storageBucket;
        });
    }
}
