<?php

namespace Azuriom\Plugin\InspiratoStats\Events;

use Azuriom\Models\User;
use Azuriom\Plugin\InspiratoStats\Models\ActivityPoint;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ActivityChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public User $user, public ActivityPoint $activity)
    {
    }
}
