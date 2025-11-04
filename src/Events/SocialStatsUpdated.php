<?php

namespace Azuriom\Plugin\SocialProfile\Events;

use Azuriom\Models\User;
use Azuriom\Plugin\SocialProfile\Models\GameStatistic;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SocialStatsUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public User $user, public GameStatistic $statistics)
    {
    }
}
