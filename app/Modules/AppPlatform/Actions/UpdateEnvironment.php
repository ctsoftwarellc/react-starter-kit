<?php

namespace App\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\DTOs\UpdateEnvironmentData;
use App\Modules\AppPlatform\Events\EnvironmentConfigChanged;
use App\Modules\AppPlatform\Models\Environment;
use Illuminate\Support\Facades\DB;

class UpdateEnvironment
{
    public function execute(Environment $environment, UpdateEnvironmentData $data): Environment
    {
        return DB::transaction(function () use ($environment, $data) {
            $attributes = [];

            if ($data->hasClusterId) {
                $attributes['cluster_id'] = $data->clusterId;
            }

            if ($data->hasName) {
                $attributes['name'] = $data->name;
            }

            if ($data->hasType) {
                $attributes['type'] = $data->type;
            }

            if ($data->hasIsAutoDeploy) {
                $attributes['is_auto_deploy'] = $data->isAutoDeploy;
            }

            if ($data->hasBranch) {
                $attributes['branch'] = $data->branch;
            }

            $environment->update($attributes);

            $environment = $environment->fresh();

            event(new EnvironmentConfigChanged($environment, 'updated', $attributes));

            return $environment;
        });
    }
}
