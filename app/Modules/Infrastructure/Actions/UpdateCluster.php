<?php

namespace App\Modules\Infrastructure\Actions;

use App\Modules\Infrastructure\Models\Cluster;
use Illuminate\Support\Facades\DB;

class UpdateCluster
{
    public function execute(Cluster $cluster, array $data): Cluster
    {
        return DB::transaction(function () use ($cluster, $data) {
            $attributes = array_filter([
                'name' => $data['name'] ?? null,
                'settings' => $data['settings'] ?? null,
            ], fn ($value) => $value !== null);

            $cluster->update($attributes);

            return $cluster;
        });
    }
}
