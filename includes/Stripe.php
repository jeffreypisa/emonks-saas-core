<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

final class Stripe
{
    private const API_BASE = 'https://api.stripe.com/v1';

    public function boot(): void
    {
    }

    /** @return array<string,string> */
    public function constants(): array
    {
        return [
            'secret_key' => defined('EMONKS_STRIPE_SECRET_KEY') ? (string) EMONKS_STRIPE_SECRET_KEY : '',
            'webhook_secret' => defined('EMONKS_STRIPE_WEBHOOK_SECRET') ? (string) EMONKS_STRIPE_WEBHOOK_SECRET : '',
            'starter' => defined('EMONKS_STRIPE_PRICE_STARTER') ? (string) EMONKS_STRIPE_PRICE_STARTER : '',
            'plus' => defined('EMONKS_STRIPE_PRICE_PLUS') ? (string) EMONKS_STRIPE_PRICE_PLUS : '',
            'pro' => defined('EMONKS_STRIPE_PRICE_PRO') ? (string) EMONKS_STRIPE_PRICE_PRO : '',
        ];
    }

    /** @return array<string,mixed> */
    public function createOrGetCustomer(int $userId): array
    {
        $existing = (string) get_user_meta($userId, 'emonks_stripe_customer_id', true);
        if ($existing !== '') {
            return ['id' => $existing, 'existing' => true];
        }

        $user = get_userdata($userId);
        if (! $user instanceof \WP_User) {
            return ['error' => 'invalid_user'];
        }

        $response = $this->request('POST', '/customers', [
            'email' => $user->user_email,
            'name' => $user->display_name,
            'metadata[user_id]' => (string) $userId,
        ]);

        if (isset($response['id'])) {
            update_user_meta($userId, 'emonks_stripe_customer_id', sanitize_text_field((string) $response['id']));
        }

        return $response;
    }

    /** @return array<string,mixed> */
    public function createCheckoutSession(int $userId, string $plan, string $successUrl, string $cancelUrl, string $cycle = 'monthly'): array
    {
        $planConfig = Plans::getPlans()[$plan] ?? null;
        if (! is_array($planConfig)) {
            return ['error' => 'invalid_plan'];
        }

        $cycle = in_array($cycle, ['monthly', 'yearly'], true) ? $cycle : 'monthly';
        $constant = (string) ($planConfig['stripe_price_constant'] ?? '');
        $priceId = sanitize_text_field((string) ($planConfig['stripe_price_id_' . $cycle] ?? ''));
        if ($priceId === '') {
            $priceId = sanitize_text_field((string) ($planConfig['stripe_price_id'] ?? ''));
        }
        if ($priceId === '' && $constant !== '' && defined($constant)) {
            $priceId = (string) constant($constant);
        }
        if ($priceId === '') {
            return ['error' => 'missing_price_id'];
        }

        $customer = $this->createOrGetCustomer($userId);
        if (! isset($customer['id'])) {
            return ['error' => 'missing_customer', 'customer' => $customer];
        }

        return $this->request('POST', '/checkout/sessions', [
            'customer' => (string) $customer['id'],
            'mode' => 'subscription',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'line_items[0][price]' => $priceId,
            'line_items[0][quantity]' => '1',
            'allow_promotion_codes' => 'true',
            'metadata[user_id]' => (string) $userId,
            'metadata[plan]' => $plan,
            'metadata[cycle]' => $cycle,
        ]);
    }

    /** @return array<string,mixed> */
    public function changeSubscriptionPlan(int $userId, string $targetPlan, bool $immediate = true, string $cycle = 'monthly'): array
    {
        $subscriptionId = (string) get_user_meta($userId, 'emonks_stripe_subscription_id', true);
        if ($subscriptionId === '') {
            return ['error' => 'missing_subscription'];
        }

        $planConfig = Plans::getPlans()[$targetPlan] ?? null;
        if (! is_array($planConfig)) {
            return ['error' => 'invalid_plan'];
        }

        $cycle = in_array($cycle, ['monthly', 'yearly'], true) ? $cycle : 'monthly';
        $priceId = sanitize_text_field((string) ($planConfig['stripe_price_id_' . $cycle] ?? ''));
        if ($priceId === '') {
            $priceId = sanitize_text_field((string) ($planConfig['stripe_price_id'] ?? ''));
        }
        $constant = sanitize_text_field((string) ($planConfig['stripe_price_constant'] ?? ''));
        if ($priceId === '' && $constant !== '' && defined($constant)) {
            $priceId = (string) constant($constant);
        }
        if ($priceId === '') {
            return ['error' => 'missing_price_id'];
        }

        $sub = $this->request('GET', '/subscriptions/' . rawurlencode($subscriptionId));
        if (isset($sub['error']) || empty($sub['items']['data'][0]['id'])) {
            return ['error' => 'subscription_fetch_failed', 'details' => $sub];
        }

        $itemId = sanitize_text_field((string) $sub['items']['data'][0]['id']);

        $payload = [
            'items[0][id]' => $itemId,
            'items[0][price]' => $priceId,
            'metadata[user_id]' => (string) $userId,
            'metadata[plan]' => sanitize_key($targetPlan),
            'metadata[cycle]' => $cycle,
            'proration_behavior' => $immediate ? 'create_prorations' : 'none',
        ];

        $response = $this->request('POST', '/subscriptions/' . rawurlencode($subscriptionId), $payload);
        if (! isset($response['id'])) {
            return ['error' => 'subscription_update_failed', 'details' => $response];
        }

        update_user_meta($userId, 'emonks_subscription_plan', sanitize_key($targetPlan));
        update_user_meta($userId, 'emonks_subscription_cycle', $cycle);
        return $response;
    }

    /** @return array<string,mixed> */
    public function createCustomerPortalSession(int $userId, string $returnUrl): array
    {
        $customerId = (string) get_user_meta($userId, 'emonks_stripe_customer_id', true);
        if ($customerId === '') {
            return ['error' => 'missing_customer'];
        }

        return $this->request('POST', '/billing_portal/sessions', [
            'customer' => $customerId,
            'return_url' => $returnUrl,
        ]);
    }

    /** @param array<string,mixed> $event */
    public function syncSubscriptionFromWebhook(array $event): void
    {
        $type = (string) ($event['type'] ?? '');
        $object = $event['data']['object'] ?? [];
        if (! is_array($object)) {
            return;
        }

        $userId = $this->resolveUserIdFromStripeObject($object);
        if ($userId <= 0) {
            Logger::log('webhook', 'Unable to resolve user from webhook object', ['type' => $type]);
            return;
        }

        switch ($type) {
            case 'checkout.session.completed':
                $this->applyCheckoutCompleted($userId, $object);
                break;
            case 'customer.subscription.created':
            case 'customer.subscription.updated':
                $this->applySubscriptionState($userId, $object);
                break;
            case 'customer.subscription.deleted':
                $this->applySubscriptionDeleted($userId, $object);
                break;
            case 'invoice.payment_succeeded':
                $this->applyInvoiceSucceeded($userId, $object);
                break;
            case 'invoice.payment_failed':
                $this->applyInvoiceFailed($userId, $object);
                break;
        }

        do_action('emonks_stripe_synced_subscription', $event, $userId);
    }

    /** @return array<string,mixed> */
    private function request(string $method, string $path, array $body = []): array
    {
        $secret = $this->constants()['secret_key'];
        if ($secret === '') {
            return ['error' => 'missing_secret'];
        }

        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $secret,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'timeout' => 20,
            'body' => http_build_query($body, '', '&'),
        ];

        $response = wp_remote_request(self::API_BASE . $path, $args);
        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $raw = (string) wp_remote_retrieve_body($response);
        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return ['error' => 'invalid_json', 'status_code' => $code, 'raw' => $raw];
        }

        if ($code >= 400) {
            $decoded['error_http_status'] = $code;
        }

        return $decoded;
    }

    /** @param array<string,mixed> $object */
    private function resolveUserIdFromStripeObject(array $object): int
    {
        $metaUserId = absint((string) ($object['metadata']['user_id'] ?? 0));
        if ($metaUserId > 0) {
            return $metaUserId;
        }

        $customerId = sanitize_text_field((string) ($object['customer'] ?? ''));
        if ($customerId === '') {
            return 0;
        }

        $users = get_users([
            'meta_key' => 'emonks_stripe_customer_id',
            'meta_value' => $customerId,
            'number' => 1,
            'fields' => 'ids',
        ]);

        return ! empty($users) ? (int) $users[0] : 0;
    }

    /** @param array<string,mixed> $object */
    private function applyCheckoutCompleted(int $userId, array $object): void
    {
        update_user_meta($userId, 'emonks_stripe_customer_id', sanitize_text_field((string) ($object['customer'] ?? '')));
        update_user_meta($userId, 'emonks_subscription_status', 'active');
        $plan = sanitize_key((string) ($object['metadata']['plan'] ?? 'starter'));
        $cycle = sanitize_key((string) ($object['metadata']['cycle'] ?? 'monthly'));
        update_user_meta($userId, 'emonks_subscription_plan', $plan);
        update_user_meta($userId, 'emonks_subscription_cycle', in_array($cycle, ['monthly', 'yearly'], true) ? $cycle : 'monthly');
    }

    /** @param array<string,mixed> $object */
    private function applySubscriptionState(int $userId, array $object): void
    {
        $status = sanitize_key((string) ($object['status'] ?? 'inactive'));
        $subId = sanitize_text_field((string) ($object['id'] ?? ''));
        $plan = sanitize_key((string) ($object['metadata']['plan'] ?? get_user_meta($userId, 'emonks_subscription_plan', true)));
        $cycle = sanitize_key((string) ($object['metadata']['cycle'] ?? get_user_meta($userId, 'emonks_subscription_cycle', true)));
        $periodEnd = absint((string) ($object['current_period_end'] ?? 0));

        update_user_meta($userId, 'emonks_stripe_subscription_id', $subId);
        update_user_meta($userId, 'emonks_subscription_status', $status);
        if ($plan !== '') {
            update_user_meta($userId, 'emonks_subscription_plan', $plan);
        }
        if ($cycle !== '') {
            update_user_meta($userId, 'emonks_subscription_cycle', in_array($cycle, ['monthly', 'yearly'], true) ? $cycle : 'monthly');
        }
        if ($periodEnd > 0) {
            update_user_meta($userId, 'emonks_subscription_current_period_end', gmdate('c', $periodEnd));
        }
    }

    /** @param array<string,mixed> $object */
    private function applySubscriptionDeleted(int $userId, array $object): void
    {
        update_user_meta($userId, 'emonks_stripe_subscription_id', sanitize_text_field((string) ($object['id'] ?? '')));
        update_user_meta($userId, 'emonks_subscription_status', 'canceled');
    }

    /** @param array<string,mixed> $object */
    private function applyInvoiceSucceeded(int $userId, array $object): void
    {
        update_user_meta($userId, 'emonks_subscription_status', 'active');
        do_action('emonks_invoice_payment_succeeded', $userId, $object);
    }

    /** @param array<string,mixed> $object */
    private function applyInvoiceFailed(int $userId, array $object): void
    {
        update_user_meta($userId, 'emonks_subscription_status', 'past_due');
        do_action('emonks_invoice_payment_failed', $userId, $object);
    }
}
