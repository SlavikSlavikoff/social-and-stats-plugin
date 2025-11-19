<?php

namespace Azuriom\Plugin\InspiratoStats\Support\OAuth\Dto;

class StoredState
{
    public function __construct(
        public readonly string $provider,
        public readonly string $flowType,
        public readonly array $context = [],
    ) {
    }
}
