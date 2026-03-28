<?php

namespace App\Http\Resources\Infrastructure;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClusterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => $this->status->value,
            'settings' => $this->settings,
            'servers' => ServerResource::collection($this->whenLoaded('servers')),
            'node_counts' => $this->when($this->relationLoaded('servers'), function () {
                return $this->servers->groupBy('pivot.role')->map->count();
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
