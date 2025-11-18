<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SocialScoreResource extends JsonResource
{
    public function __construct($resource)
    {
        parent::__construct($resource);
    }

    public static function makeResource($resource): self
    {
        return new self($resource);
    }

    public function toArray($request): array
    {
        return [
            'score' => (int) $this->score,
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
