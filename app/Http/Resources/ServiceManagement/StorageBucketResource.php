<?php

namespace App\Http\Resources\ServiceManagement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StorageBucketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'provider' => $this->provider,
            'region' => $this->region,
            'bucket_name' => $this->bucket_name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
