<?php

namespace Azuriom\Plugin\InspiratoStats\Events;

use Azuriom\Models\User;
use Azuriom\Plugin\InspiratoStats\Models\CoinBalance;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CoinsChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public User $user, public CoinBalance $coins, public ?string $context = null)
    {
    }
}
