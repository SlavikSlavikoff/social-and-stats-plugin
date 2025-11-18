<?php

namespace Azuriom\Plugin\InspiratoStats\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BundleResource extends JsonResource
{
    public function __construct($resource, protected bool $fullAccess = false)
    {
        parent::__construct($resource);
    }

    public static function makeWithAccess(array $data, bool $fullAccess): self
    {
        return new self($data, $fullAccess);
    }

    public function toArray($request): array
    {
        $user = $this->resource['user'];

        $profile = [
            'nickname' => $user->name,
            'uuid' => $user->uuid ?? null,
            'skin_url' => method_exists($user, 'getAvatar') ? $user->getAvatar() : null,
        ];

        $showCoinsPublic = (bool) setting('socialprofile_show_coins_public', true);

        $data = [
            'profile' => $profile,
            'social_score' => $this->resource['social_score']?->score ?? 0,
            'activity' => $this->resource['activity']?->points ?? 0,
            'trust' => [
                'level' => $this->resource['trust']?->level ?? 'newbie',
                'label' => __('socialprofile::messages.trust.levels.'.($this->resource['trust']?->level ?? 'newbie')),
            ],
            'statistics' => [
                'played_minutes' => (int) ($this->resource['stats']?->played_minutes ?? 0),
            ],
        ];

        if ($this->fullAccess || $showCoinsPublic) {
            $data['coins'] = (float) ($this->resource['coins']?->balance ?? 0);
        }

        if ($this->fullAccess) {
            $data['coins_hold'] = $this->resource['coins']?->hold !== null ? (float) $this->resource['coins']?->hold : null;
            $data['statistics']['kills'] = (int) ($this->resource['stats']?->kills ?? 0);
            $data['statistics']['deaths'] = (int) ($this->resource['stats']?->deaths ?? 0);
            $data['statistics']['extra_metrics'] = $this->resource['stats']?->extra_metrics;
        }

        return $data;
    }
}
