<?php

namespace Azuriom\Plugin\SocialProfile\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CoinResource extends JsonResource
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
            'balance' => (float) $this->balance,
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];

        if ($this->fullAccess) {
            $data['hold'] = $this->hold !== null ? (float) $this->hold : null;
        }

        return $data;
    }
}
