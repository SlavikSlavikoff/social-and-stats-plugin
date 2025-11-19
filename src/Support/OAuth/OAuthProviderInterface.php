<?php

namespace Azuriom\Plugin\InspiratoStats\Support\OAuth;

use Azuriom\Plugin\InspiratoStats\Support\OAuth\Dto\AccessToken;
use Azuriom\Plugin\InspiratoStats\Support\OAuth\Dto\OAuthUser;

interface OAuthProviderInterface
{
    public function getName(): string;

    /**
     * Build the provider authorization URL.
     *
     * @param  string  $state
     * @param  array<string, mixed>  $context
     */
    public function getAuthorizationUrl(string $state, array $context = []): string;

    public function getToken(string $code): AccessToken;

    public function getUserInfo(AccessToken $token): OAuthUser;
}
