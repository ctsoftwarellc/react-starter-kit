<?php

namespace App\Http\Resources\ServiceManagement;

use App\Http\Resources\AppPlatform\EnvironmentResource;
use App\Modules\ServiceManagement\Enums\ServiceType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceBindingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'environment_id' => $this->environment_id,
            'service_type' => $this->service_type->value,
            'binding_name' => $this->binding_name,
            'config' => $this->config,
            'environment' => new EnvironmentResource($this->whenLoaded('environment')),
            'database_instance' => new DatabaseInstanceResource($this->when($this->service_type === ServiceType::Database, $this->databaseInstance)),
            'cache_instance' => new CacheInstanceResource($this->when($this->service_type === ServiceType::Cache, $this->cacheInstance)),
            'storage_bucket' => new StorageBucketResource($this->when($this->service_type === ServiceType::Storage, $this->storageBucket)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
