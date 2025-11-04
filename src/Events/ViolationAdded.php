<?php

namespace Azuriom\Plugin\SocialProfile\Events;

use Azuriom\Models\User;
use Azuriom\Plugin\SocialProfile\Models\Violation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ViolationAdded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public User $user, public Violation $violation)
    {
    }
}
