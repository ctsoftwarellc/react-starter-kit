<?php

namespace App\Http\Resources\ServiceManagement;

use App\Http\Resources\Infrastructure\ClusterResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CacheInstanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cluster_id' => $this->cluster_id,
            'name' => $this->name,
            'engine' => $this->engine->value,
            'version' => $this->version,
            'host' => $this->host,
            'port' => $this->port,
            'cluster' => new ClusterResource($this->whenLoaded('cluster')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
