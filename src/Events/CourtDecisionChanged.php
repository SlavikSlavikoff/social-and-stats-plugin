<?php

namespace Azuriom\Plugin\InspiratoStats\Events;

use Azuriom\Plugin\InspiratoStats\Models\CourtCase;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CourtDecisionChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public CourtCase $case,
        public string $action = 'issued'
    ) {
    }
}
