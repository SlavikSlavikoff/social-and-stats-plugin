<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GameStatisticResource extends JsonResource
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
        $data = [
            'played_minutes' => (int) $this->played_minutes,
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];

        if ($this->fullAccess) {
            $data['kills'] = (int) $this->kills;
            $data['deaths'] = (int) $this->deaths;
            $data['extra_metrics'] = $this->extra_metrics;
        }

        return $data;
    }
}
