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

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(public User $user, public ActivityPoint $activity, public array $context = [])
    {
    }
}
