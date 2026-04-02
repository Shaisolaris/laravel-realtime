<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Events\WebhookDispatched;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * Dispatch webhooks for an event to all subscribed endpoints.
     */
    public function dispatch(int $teamId, string $event, array $payload): void
    {
        $webhooks = Webhook::where('team_id', $teamId)
            ->active()
            ->get()
            ->filter(fn (Webhook $wh) => $wh->subscribesTo($event));

        foreach ($webhooks as $webhook) {
            $this->deliver($webhook, $event, $payload);
        }
    }

    private function deliver(Webhook $webhook, string $event, array $payload): void
    {
        $body = [
            'event' => $event,
            'payload' => $payload,
            'timestamp' => now()->toIso8601String(),
            'webhook_id' => $webhook->id,
        ];

        $signature = hash_hmac('sha256', json_encode($body), $webhook->secret ?? '');

        $startTime = microtime(true);
        $success = false;
        $statusCode = 0;
        $responseBody = '';

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event' => $event,
                    'User-Agent' => 'RealtimePlatform-Webhook/1.0',
                ])
                ->post($webhook->url, $body);

            $statusCode = $response->status();
            $responseBody = substr($response->body(), 0, 1000);
            $success = $response->successful();
        } catch (\Throwable $e) {
            $responseBody = $e->getMessage();
            Log::error("[Webhook] Delivery failed", [
                'webhook_id' => $webhook->id,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }

        $durationMs = round((microtime(true) - $startTime) * 1000);

        WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'event' => $event,
            'payload' => $body,
            'response_status' => $statusCode,
            'response_body' => $responseBody,
            'duration_ms' => (int) $durationMs,
            'success' => $success,
        ]);

        $webhook->update(['last_triggered_at' => now()]);

        WebhookDispatched::dispatch($webhook->id, $event, $payload, $statusCode, $success);
    }
}
