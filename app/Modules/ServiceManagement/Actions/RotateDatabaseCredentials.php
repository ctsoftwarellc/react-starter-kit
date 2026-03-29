<?php

namespace App\Modules\ServiceManagement\Actions;

use App\Modules\AppPlatform\Events\EnvironmentConfigChanged;
use App\Modules\ServiceManagement\Enums\DatabaseEngine;
use App\Modules\ServiceManagement\Models\DatabaseInstance;
use App\Modules\ServiceManagement\Services\DatabaseProvisioner;
use App\Modules\ServiceManagement\Services\MysqlProvisioner;
use App\Modules\ServiceManagement\Services\PostgresProvisioner;
use App\Modules\ServiceManagement\Services\ServiceSecretManager;
use Illuminate\Support\Facades\DB;

class RotateDatabaseCredentials
{
    public function __construct(
        private readonly ServiceSecretManager $serviceSecretManager = new ServiceSecretManager,
    ) {}

    public function execute(DatabaseInstance $databaseInstance): DatabaseInstance
    {
        $provisioner = $this->resolveProvisioner($databaseInstance->engine);
        $credentials = $provisioner->rotateCredentials($databaseInstance->database_name);

        return DB::transaction(function () use ($databaseInstance, $credentials) {
            $databaseInstance->update([
                'username' => $credentials['username'],
                'password' => $credentials['password'],
            ]);

            $databaseInstance = $databaseInstance->fresh();

            $databaseInstance->load('serviceBindings.environment');

            foreach ($databaseInstance->serviceBindings as $binding) {
                $this->serviceSecretManager->syncBindingSecrets($binding->environment, $binding);

                event(new EnvironmentConfigChanged($binding->environment, 'database_credentials_rotated', [
                    'binding_name' => $binding->binding_name,
                ]));
            }

            return $databaseInstance;
        });
    }

    private function resolveProvisioner(DatabaseEngine $engine): DatabaseProvisioner
    {
        return match ($engine) {
            DatabaseEngine::Postgres => new PostgresProvisioner,
            DatabaseEngine::Mysql => new MysqlProvisioner,
        };
    }
}
