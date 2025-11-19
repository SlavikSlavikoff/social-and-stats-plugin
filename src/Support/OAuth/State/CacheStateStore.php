<?php

namespace Azuriom\Plugin\InspiratoStats\Support\OAuth\State;

use Azuriom\Plugin\InspiratoStats\Support\OAuth\Dto\StoredState;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Str;

class CacheStateStore implements StateStoreInterface
{
    public function __construct(
        private readonly Repository $cache,
        private readonly int $ttlSeconds = 300,
    ) {
    }

    public function generateAndStore(string $provider, string $flowType, array $context = []): string
    {
        $state = Str::random(64);
        $payload = [
            'provider' => $provider,
            'flow_type' => $flowType,
            'context' => $context,
        ];

        $this->cache->put($this->key($state), $payload, $this->ttlSeconds);

        return $state;
    }

    public function get(string $state): ?StoredState
    {
        $payload = $this->cache->pull($this->key($state));

        if ($payload === null) {
            return null;
        }

        return new StoredState(
            $payload['provider'] ?? '',
            $payload['flow_type'] ?? '',
            $payload['context'] ?? []
        );
    }

    private function key(string $state): string
    {
        return 'socialprofile:oauth:state:'.$state;
    }
}
