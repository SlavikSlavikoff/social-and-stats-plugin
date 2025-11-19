<?php

namespace Azuriom\Plugin\InspiratoStats\Support\OAuth\State;

use Azuriom\Plugin\InspiratoStats\Support\OAuth\Dto\StoredState;

interface StateStoreInterface
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function generateAndStore(string $provider, string $flowType, array $context = []): string;

    public function get(string $state): ?StoredState;
}
