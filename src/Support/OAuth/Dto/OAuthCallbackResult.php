<?php

namespace Azuriom\Plugin\InspiratoStats\Support\OAuth\Dto;

class OAuthCallbackResult
{
    public function __construct(
        public readonly string $provider,
        public readonly string $flowType,
        public readonly array $context,
        public readonly AccessToken $accessToken,
        public readonly OAuthUser $user,
    ) {
    }
}
