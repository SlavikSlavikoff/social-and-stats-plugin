<?php

namespace Azuriom\Plugin\InspiratoStats\Listeners;

use Azuriom\Plugin\InspiratoStats\Events\CourtDecisionChanged;
use Illuminate\Support\Facades\Log;

class ForwardCourtDecisionToIntegrations
{
    public function handle(CourtDecisionChanged $event): void
    {
        // TODO(see docs/TODO.md#listeners): wire up Minecraft and social bots integrations.
        Log::info('[court] listener placeholder triggered', [
            'case' => $event->case->case_number,
            'action' => $event->action,
        ]);
    }
}
