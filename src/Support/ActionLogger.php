<?php

namespace Azuriom\Plugin\InspiratoStats\Support;

use Azuriom\Models\ActionLog;

class ActionLogger
{
    public static function log(string $action, array $context = []): void
    {
        if (! class_exists(ActionLog::class)) {
            return;
        }

        ActionLog::log($action, null, $context);
    }
}
