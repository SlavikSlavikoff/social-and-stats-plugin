<?php

namespace Azuriom\Plugin\InspiratoStats\Support\Court;

use Azuriom\Plugin\InspiratoStats\Models\CourtWebhookDelivery;
use Illuminate\Support\Facades\Http;

class WebhookDispatcher
{
    public function dispatchPending(int $limit = 25): void
    {
        CourtWebhookDelivery::where('status', 'pending')
            ->where(function ($query) {
                $query->whereNull('next_attempt_at')
                    ->orWhere('next_attempt_at', '<=', now());
            })
            ->with('webhook')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->each(function (CourtWebhookDelivery $delivery) {
                $this->send($delivery);
            });
    }

    protected function send(CourtWebhookDelivery $delivery): void
    {
        $webhook = $delivery->webhook;

        if (! $webhook) {
            $delivery->status = 'failed';
            $delivery->error = 'Webhook removed';
            $delivery->save();

            return;
        }

        $payload = $delivery->payload ?? [];
        $attempts = $delivery->attempts + 1;
        $delivery->attempts = $attempts;
        $delivery->last_attempt_at = now();

        try {
            $request = Http::timeout(10);

            if ($webhook->secret) {
                $signature = hash_hmac('sha256', json_encode($payload), $webhook->secret);
                $request = $request->withHeaders(['X-Court-Signature' => $signature]);
            }

            $response = $request->post($webhook->url, $payload);
            $delivery->response_code = $response->status();
            $delivery->response_body = substr($response->body(), 0, 4096);

            if ($response->successful()) {
                $delivery->status = 'sent';
                $delivery->save();

                return;
            }

            $delivery->status = $attempts >= config('socialprofile.court.webhook.max_attempts', 5) ? 'failed' : 'pending';
            $delivery->next_attempt_at = now()->addSeconds(config('socialprofile.court.webhook.retry_after_seconds', 120));
            $delivery->error = $response->body();
            $delivery->save();
        } catch (\Throwable $exception) {
            $delivery->error = $exception->getMessage();
            $delivery->status = $attempts >= config('socialprofile.court.webhook.max_attempts', 5) ? 'failed' : 'pending';
            $delivery->next_attempt_at = now()->addSeconds(config('socialprofile.court.webhook.retry_after_seconds', 120));
            $delivery->save();
        }
    }
}
