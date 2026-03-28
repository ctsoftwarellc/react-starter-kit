<?php

namespace App\Modules\Infrastructure\Actions;

use App\Modules\Infrastructure\DTOs\CreateClusterData;
use App\Modules\Infrastructure\Enums\ClusterStatus;
use App\Modules\Infrastructure\Models\Cluster;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateCluster
{
    public function execute(CreateClusterData $data): Cluster
    {
        return DB::transaction(function () use ($data) {
            $slug = $this->generateUniqueSlug($data->name);

            return Cluster::create([
                'name' => $data->name,
                'slug' => $slug,
                'status' => ClusterStatus::Pending,
                'settings' => $data->settings ?? [],
            ]);
        });
    }

    private function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $counter = 2;

        while (Cluster::where('slug', $slug)->exists()) {
            $slug = $original.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
