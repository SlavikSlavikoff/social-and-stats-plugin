<?php

namespace Azuriom\Plugin\InspiratoStats\Events;

use Azuriom\Models\User;
use Azuriom\Plugin\InspiratoStats\Models\TrustLevel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TrustLevelChanged
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        public User $user,
        public TrustLevel $trustLevel,
        public string $oldLevel,
        public string $newLevel,
        public ?User $actor = null,
        public array $context = []
    ) {
    }
}
