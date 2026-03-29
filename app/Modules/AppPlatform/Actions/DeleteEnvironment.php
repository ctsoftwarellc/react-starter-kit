<?php

namespace App\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\Events\EnvironmentConfigChanged;
use App\Modules\AppPlatform\Models\Environment;
use Illuminate\Support\Facades\DB;

class DeleteEnvironment
{
    public function execute(Environment $environment): void
    {
        DB::transaction(function () use ($environment) {
            $changes = [
                'name' => $environment->name,
                'type' => $environment->type->value,
            ];

            $environment->delete();

            event(new EnvironmentConfigChanged($environment, 'deleted', $changes));
        });
    }
}
