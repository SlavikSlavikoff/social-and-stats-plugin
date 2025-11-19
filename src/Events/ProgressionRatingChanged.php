<?php

namespace Azuriom\Plugin\InspiratoStats\Events;

use Azuriom\Models\User;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionEvent;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionRating;
use Azuriom\Plugin\InspiratoStats\Models\ProgressionRule;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProgressionRatingChanged
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public User $user,
        public ProgressionRating $rating,
        public int $valueBefore,
        public int $valueAfter,
        public ?ProgressionRule $rule = null,
        public ?ProgressionEvent $event = null,
        public array $payload = []
    ) {
    }
}
