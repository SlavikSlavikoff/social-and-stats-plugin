<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    public function __construct($resource, protected bool $fullAccess = false)
    {
        parent::__construct($resource);
    }

    public static function makeWithAccess($resource, bool $fullAccess): self
    {
        return new self($resource, $fullAccess);
    }

    public function toArray($request): array
    {
        return [
            'points' => (int) $this->points,
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
