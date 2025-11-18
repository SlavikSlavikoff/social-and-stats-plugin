<?php

namespace Azuriom\Plugin\SocialProfile\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TrustLevelResource extends JsonResource
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
            'level' => $this->level,
            'label' => __('socialprofile::messages.trust.levels.'.$this->level),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];

        if ($this->fullAccess) {
            $data['note'] = $this->note;
            $data['granted_by'] = $this->granter?->name;
        }

        return $data;
    }
}
