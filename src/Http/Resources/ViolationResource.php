<?php

namespace Azuriom\Plugin\SocialProfile\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ViolationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'reason' => $this->reason,
            'points' => (int) $this->points,
            'issued_by' => $this->issuer?->name,
            'evidence_url' => $this->evidence_url,
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
