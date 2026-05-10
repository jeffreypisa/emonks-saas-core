<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

final class Webhooks
{
    public function boot(): void
    {
        add_action('rest_api_init', [$this, 'registerRoute']);
    }

    public function registerRoute(): void
    {
        register_rest_route('emonks/v1', '/webhooks/stripe', [
            'methods' => 'POST',
            'callback' => [$this, 'handleStripeWebhook'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function handleStripeWebhook(\WP_REST_Request $request): \WP_REST_Response
    {
        $payload = $request->get_body();
        $signature = (string) $request->get_header('stripe-signature');

        if (! $this->verifySignature($payload, $signature)) {
            Logger::log('webhook', 'Invalid Stripe signature', []);
            return new \WP_REST_Response(['ok' => false, 'message' => 'Invalid signature'], 400);
        }

        $event = json_decode($payload, true);
        if (! is_array($event)) {
            return new \WP_REST_Response(['ok' => false, 'message' => 'Invalid payload'], 400);
        }

        $eventId = sanitize_text_field((string) ($event['id'] ?? ''));
        if ($eventId !== '' && get_transient('emonks_wh_' . $eventId)) {
            return new \WP_REST_Response(['ok' => true, 'idempotent' => true], 200);
        }

        if ($eventId !== '') {
            set_transient('emonks_wh_' . $eventId, 1, DAY_IN_SECONDS);
        }

        do_action('emonks_stripe_webhook_received', $event);

        $stripe = Plugin::instance()->get('stripe');
        if ($stripe instanceof Stripe) {
            $stripe->syncSubscriptionFromWebhook($event);
        }

        Logger::log('webhook', 'Stripe webhook processed', ['event_id' => $eventId, 'type' => $event['type'] ?? '']);
        return new \WP_REST_Response(['ok' => true], 200);
    }

    private function verifySignature(string $payload, string $signature): bool
    {
        $secret = defined('EMONKS_STRIPE_WEBHOOK_SECRET') ? (string) EMONKS_STRIPE_WEBHOOK_SECRET : '';
        if ($secret === '' || $signature === '') {
            return false;
        }

        $parts = [];
        foreach (explode(',', $signature) as $segment) {
            [$k, $v] = array_pad(explode('=', trim($segment), 2), 2, '');
            if ($k !== '' && $v !== '') {
                $parts[$k] = $v;
            }
        }

        $timestamp = $parts['t'] ?? '';
        $v1 = $parts['v1'] ?? '';
        if ($timestamp === '' || $v1 === '') {
            return false;
        }

        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $signedPayload = $timestamp . '.' . $payload;
        $expected = hash_hmac('sha256', $signedPayload, $secret);

        return hash_equals($expected, $v1);
    }
}
