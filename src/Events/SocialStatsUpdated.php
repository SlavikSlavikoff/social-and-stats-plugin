<?php

namespace Azuriom\Plugin\InspiratoStats\Events;

use Azuriom\Models\User;
use Azuriom\Plugin\InspiratoStats\Models\GameStatistic;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SocialStatsUpdated
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(public User $user, public GameStatistic $statistics, public array $context = [])
    {
    }
}
