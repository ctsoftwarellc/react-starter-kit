<?php

namespace App\Http\Resources\Networking;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DomainResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'environment_id' => $this->environment_id,
            'hostname' => $this->hostname,
            'is_primary' => $this->is_primary,
            'is_verified' => $this->is_verified,
            'verification_token' => $this->verification_token,
            'certificate' => new CertificateResource($this->whenLoaded('certificate')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
