<?php

namespace Azuriom\Plugin\InspiratoStats\Listeners;

use Azuriom\Plugin\InspiratoStats\Events\CourtDecisionChanged;
use Azuriom\Plugin\InspiratoStats\Models\CourtWebhook;
use Azuriom\Plugin\InspiratoStats\Models\CourtWebhookDelivery;

class DispatchCourtWebhooks
{
    public function handle(CourtDecisionChanged $event): void
    {
        $case = $event->case->fresh(['judge', 'subject', 'actions']);

        $payload = [
            'case_number' => $case->case_number,
            'subject' => [
                'id' => $case->subject->id,
                'name' => $case->subject->name,
            ],
            'judge' => [
                'id' => $case->judge->id,
                'name' => $case->judge->name,
            ],
            'status' => $case->status,
            'mode' => $case->mode,
            'executor' => $case->executor,
            'comment' => $case->comment,
            'actions' => $case->actions->map(fn ($action) => [
                'type' => $action->type,
                'metric' => $action->metric_key,
                'delta' => $action->delta,
                'duration_minutes' => $action->duration_minutes,
                'status' => $action->status,
            ]),
            'event' => $event->action,
        ];

        CourtWebhook::where('is_active', true)
            ->get()
            ->each(function (CourtWebhook $webhook) use ($payload, $case, $event) {
                $events = $webhook->events ?? [];
                if (! empty($events) && ! in_array($event->action, $events, true)) {
                    return;
                }

                CourtWebhookDelivery::create([
                    'webhook_id' => $webhook->id,
                    'case_id' => $case->id,
                    'event' => $event->action,
                    'status' => 'pending',
                    'payload' => $payload,
                    'next_attempt_at' => now(),
                ]);
            });
    }
}
