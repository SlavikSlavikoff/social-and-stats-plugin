<?php

namespace Azuriom\Plugin\SocialProfile\Support;

use Azuriom\Models\User;
use Azuriom\Plugin\SocialProfile\Models\ApiToken;

class ApiAccessContext
{
    public function __construct(
        public readonly ?ApiToken $token,
        public readonly bool $hasFullAccess,
        public readonly ?User $actor
    ) {
    }

    public function viaToken(): bool
    {
        return $this->token !== null;
    }
}
