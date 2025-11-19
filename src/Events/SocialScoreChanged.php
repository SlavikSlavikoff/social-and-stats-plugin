<?php

namespace Azuriom\Plugin\InspiratoStats\Events;

use Azuriom\Models\User;
use Azuriom\Plugin\InspiratoStats\Models\SocialScore;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SocialScoreChanged
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(public User $user, public SocialScore $score, public array $context = [])
    {
    }
}
