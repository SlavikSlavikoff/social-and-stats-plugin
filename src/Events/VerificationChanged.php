<?php

namespace Azuriom\Plugin\InspiratoStats\Events;

use Azuriom\Models\User;
use Azuriom\Plugin\InspiratoStats\Models\Verification;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VerificationChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public User $user, public Verification $verification)
    {
    }
}
