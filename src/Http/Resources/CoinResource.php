<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CoinResource extends JsonResource
{
    public function __construct(
        $resource,
        protected bool $fullAccess = false,
        protected bool $canViewBalance = false
    ) {
        parent::__construct($resource);
    }

    public static function makeWithAccess($resource, bool $fullAccess, bool $canViewBalance = false): self
    {
        return new self($resource, $fullAccess, $canViewBalance);
    }

    public function toArray($request): array
    {
        // Public callers must respect the visibility flag and verification status.
        $canViewBalance = $this->fullAccess || $this->canViewBalance;

        $data = [
            'balance' => $canViewBalance ? (float) $this->balance : null,
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];

        if ($this->fullAccess) {
            $data['hold'] = $this->hold !== null ? (float) $this->hold : null;
        }

        return $data;
    }
}
