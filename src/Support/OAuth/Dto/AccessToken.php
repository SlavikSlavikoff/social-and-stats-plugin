<?php

namespace Azuriom\Plugin\InspiratoStats\Support\OAuth\Dto;

use DateTimeImmutable;

class AccessToken
{
    public function __construct(
        public readonly string $accessToken,
        public readonly ?string $refreshToken = null,
        public readonly ?DateTimeImmutable $expiresAt = null,
        public readonly ?string $idToken = null,
        public readonly array $raw = [],
    ) {
    }

    public function hasExpired(): bool
    {
        return $this->expiresAt !== null && $this->expiresAt <= new DateTimeImmutable();
    }
}
