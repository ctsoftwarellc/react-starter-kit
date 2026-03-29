<?php

namespace App\Modules\ServiceManagement\Actions;

use App\Modules\ServiceManagement\DTOs\ProvisionStorageBucketData;
use App\Modules\ServiceManagement\Models\StorageBucket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProvisionStorageBucket
{
    public function execute(ProvisionStorageBucketData $data): StorageBucket
    {
        return DB::transaction(function () use ($data) {
            return StorageBucket::create([
                'name' => $data->name,
                'provider' => $data->provider,
                'region' => $data->region,
                'access_key' => Str::upper(Str::random(20)),
                'secret_key' => Str::random(40),
                'bucket_name' => $data->bucketName,
            ]);
        });
    }
}
