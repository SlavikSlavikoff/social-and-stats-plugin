<?php

namespace Azuriom\Plugin\InspiratoStats\Events;

use Azuriom\Models\User;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionThreshold;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProgressionThresholdChanged
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        public User $user,
        public ProgressionThreshold $threshold,
        public string $direction,
        public string $state,
        public array $context = []
    ) {
    }
}
