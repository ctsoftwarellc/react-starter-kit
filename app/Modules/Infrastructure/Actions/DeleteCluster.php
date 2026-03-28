<?php

namespace App\Modules\Infrastructure\Actions;

use App\Modules\Infrastructure\Models\Cluster;
use Illuminate\Support\Facades\DB;

class DeleteCluster
{
    public function execute(Cluster $cluster): void
    {
        DB::transaction(function () use ($cluster) {
            $cluster->servers()->detach();
            $cluster->delete();
        });
    }
}
