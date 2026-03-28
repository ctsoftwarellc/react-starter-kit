<?php

namespace App\Http\Resources\Infrastructure;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider_id' => $this->provider_id,
            'name' => $this->name,
            'hostname' => $this->hostname,
            'public_ip' => $this->public_ip,
            'private_ip' => $this->private_ip,
            'ssh_port' => $this->ssh_port,
            'ssh_user' => $this->ssh_user,
            'os' => $this->os,
            'cpu_cores' => $this->cpu_cores,
            'memory_mb' => $this->memory_mb,
            'disk_gb' => $this->disk_gb,
            'region' => $this->region,
            'status' => $this->status->value,
            'last_heartbeat_at' => $this->last_heartbeat_at,
            'metadata' => $this->metadata,
            'provider' => new ProviderResource($this->whenLoaded('provider')),
            'clusters' => ClusterResource::collection($this->whenLoaded('clusters')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
