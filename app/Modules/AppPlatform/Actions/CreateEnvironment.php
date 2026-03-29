<?php

namespace App\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\DTOs\CreateEnvironmentData;
use App\Modules\AppPlatform\Events\EnvironmentConfigChanged;
use App\Modules\AppPlatform\Models\Application;
use App\Modules\AppPlatform\Models\Environment;
use Illuminate\Support\Facades\DB;

class CreateEnvironment
{
    public function execute(Application $application, CreateEnvironmentData $data): Environment
    {
        return DB::transaction(function () use ($application, $data) {
            $environment = Environment::create([
                'application_id' => $application->id,
                'cluster_id' => $data->clusterId,
                'name' => $data->name,
                'type' => $data->type,
                'is_auto_deploy' => $data->isAutoDeploy,
                'branch' => $data->branch,
            ]);

            event(new EnvironmentConfigChanged($environment, 'created', [
                'name' => $environment->name,
                'type' => $environment->type->value,
            ]));

            return $environment;
        });
    }
}
