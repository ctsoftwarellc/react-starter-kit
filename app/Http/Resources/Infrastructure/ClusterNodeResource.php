<?php

namespace App\Http\Resources\Infrastructure;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClusterNodeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->pivot->id,
            'server_id' => $this->id,
            'cluster_id' => $this->pivot->cluster_id,
            'role' => $this->pivot->role,
            'is_active' => $this->pivot->is_active,
            'sort_order' => $this->pivot->sort_order,
            'server' => new ServerResource($this->whenLoaded('provider', fn () => $this->resource)),
        ];
    }
}
