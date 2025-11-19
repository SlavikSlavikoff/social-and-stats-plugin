<?php

namespace Azuriom\Plugin\InspiratoStats\Support\OAuth\Dto;

class OAuthUser
{
    public function __construct(
        public readonly string $provider,
        public readonly string $providerUserId,
        public readonly ?string $email = null,
        public readonly ?string $name = null,
        public readonly ?string $avatarUrl = null,
        public readonly array $raw = [],
    ) {
    }
}
