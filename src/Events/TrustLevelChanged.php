<?php

namespace Azuriom\Plugin\SocialProfile\Events;

use Azuriom\Models\User;
use Azuriom\Plugin\SocialProfile\Models\TrustLevel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TrustLevelChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public User $user, public TrustLevel $trustLevel, public ?User $actor = null)
    {
    }
}
