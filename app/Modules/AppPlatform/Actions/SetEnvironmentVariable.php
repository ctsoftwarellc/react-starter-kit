<?php

namespace App\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\DTOs\SetEnvironmentVariableData;
use App\Modules\AppPlatform\Events\EnvironmentConfigChanged;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\AppPlatform\Models\EnvironmentVariable;
use Illuminate\Support\Facades\DB;

class SetEnvironmentVariable
{
    public function execute(
        Environment $environment,
        SetEnvironmentVariableData $data,
        ?EnvironmentVariable $variable = null,
    ): EnvironmentVariable {
        $variable = DB::transaction(function () use ($environment, $data, $variable) {
            if ($variable !== null) {
                $variable->update([
                    'key' => $data->key,
                    'value' => $data->value,
                    'is_build_arg' => $data->isBuildArg,
                ]);

                return $variable->fresh();
            }

            return EnvironmentVariable::updateOrCreate(
                [
                    'environment_id' => $environment->id,
                    'key' => $data->key,
                ],
                [
                    'value' => $data->value,
                    'is_build_arg' => $data->isBuildArg,
                ],
            );
        });

        event(new EnvironmentConfigChanged($environment, 'variable_set', [
            'key' => $variable->key,
            'is_build_arg' => $variable->is_build_arg,
        ]));

        return $variable;
    }
}
