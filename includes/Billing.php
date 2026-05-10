<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

final class Billing
{
    public function boot(): void
    {
        add_action('admin_post_emonks_billing_checkout', [$this, 'handleCheckout']);
        add_action('admin_post_emonks_billing_portal', [$this, 'handlePortal']);
        add_action('admin_post_emonks_billing_change_plan', [$this, 'handleChangePlan']);
    }

    /** @return array<string,mixed> */
    public function startCheckout(string $plan): array
    {
        $cycle = sanitize_key((string) ($_POST['cycle'] ?? 'monthly'));
        $cycle = in_array($cycle, ['monthly', 'yearly'], true) ? $cycle : 'monthly';

        if (emonks_is_billing_test_mode()) {
            $userId = get_current_user_id();
            update_user_meta($userId, 'emonks_subscription_plan', sanitize_key($plan));
            update_user_meta($userId, 'emonks_subscription_status', 'active');
            update_user_meta($userId, 'emonks_subscription_cycle', $cycle);
            return ['ok' => true, 'test_mode' => true];
        }

        $provider = $this->resolveProvider();
        if (! $provider instanceof BillingProviderInterface) {
            return ['error' => 'stripe_unavailable'];
        }

        $success = (string) emonks_get_setting('billing.success_url', emonks_get_account_url('billing'));
        $cancel = (string) emonks_get_setting('billing.cancel_url', emonks_get_account_url('billing'));

        return $provider->createCheckoutSession(get_current_user_id(), $plan, $success, $cancel, $cycle);
    }

    public function handleCheckout(): void
    {
        if (! is_user_logged_in()) {
            wp_safe_redirect(emonks_get_login_url());
            exit;
        }

        check_admin_referer('emonks_billing_checkout', 'emonks_nonce');
        $plan = sanitize_key((string) ($_POST['plan'] ?? 'starter'));

        $result = $this->startCheckout($plan);
        if (! empty($result['url'])) {
            wp_safe_redirect((string) $result['url']);
            exit;
        }

        if (! empty($result['ok']) && ! empty($result['test_mode'])) {
            emonks_flash_add('billing_success', 'Checkout simulated in test mode.');
            wp_safe_redirect(emonks_get_account_url('billing'));
            exit;
        }

        emonks_flash_add('billing_error', 'Unable to create checkout session.');
        wp_safe_redirect(emonks_get_account_url('billing'));
        exit;
    }

    public function handlePortal(): void
    {
        if (! is_user_logged_in()) {
            wp_safe_redirect(emonks_get_login_url());
            exit;
        }

        check_admin_referer('emonks_billing_portal', 'emonks_nonce');

        $provider = $this->resolveProvider();
        if (! $provider instanceof BillingProviderInterface) {
            emonks_flash_add('billing_error', 'Stripe unavailable.');
            wp_safe_redirect(emonks_get_account_url('billing'));
            exit;
        }

        $result = $provider->createCustomerPortalSession(get_current_user_id(), emonks_get_account_url('billing'));
        if (! empty($result['url'])) {
            wp_safe_redirect((string) $result['url']);
            exit;
        }

        emonks_flash_add('billing_error', 'Unable to open customer portal.');
        wp_safe_redirect(emonks_get_account_url('billing'));
        exit;
    }

    public function handleChangePlan(): void
    {
        if (! is_user_logged_in()) {
            wp_safe_redirect(emonks_get_login_url());
            exit;
        }

        check_admin_referer('emonks_billing_change_plan', 'emonks_nonce');
        $userId = get_current_user_id();
        $targetPlan = sanitize_key((string) ($_POST['plan'] ?? 'starter'));
        $targetCycle = sanitize_key((string) ($_POST['cycle'] ?? emonks_get_current_user_billing_cycle($userId)));
        $targetCycle = in_array($targetCycle, ['monthly', 'yearly'], true) ? $targetCycle : 'monthly';
        $currentPlan = emonks_get_current_user_plan();
        $plans = Plans::getPlans();

        if (! isset($plans[$targetPlan])) {
            emonks_flash_add('billing_error', 'Unknown target plan.');
            wp_safe_redirect(emonks_get_account_url('billing'));
            exit;
        }

        if ($targetPlan === $currentPlan) {
            emonks_flash_add('billing_info', 'You are already on this plan.');
            wp_safe_redirect(emonks_get_account_url('billing'));
            exit;
        }

        $isUpgrade = $this->planRank($targetPlan) > $this->planRank($currentPlan);
        $confirmed = isset($_POST['confirm_change']) && (string) $_POST['confirm_change'] === '1';
        if (! $confirmed) {
            $previewUrl = add_query_arg([
                'preview_plan' => $targetPlan,
                'preview_cycle' => $targetCycle,
                'preview_upgrade' => $isUpgrade ? '1' : '0',
            ], emonks_get_account_url('billing'));
            emonks_flash_add('billing_info', 'Review plan change details and confirm to continue.');
            wp_safe_redirect($previewUrl);
            exit;
        }

        if (emonks_is_billing_test_mode()) {
            update_user_meta($userId, 'emonks_subscription_plan', $targetPlan);
            update_user_meta($userId, 'emonks_subscription_status', 'active');
            update_user_meta($userId, 'emonks_subscription_cycle', $targetCycle);
            emonks_flash_add('billing_success', $isUpgrade ? 'Plan upgraded (test mode).' : 'Plan downgraded (test mode).');
            wp_safe_redirect(emonks_get_account_url('billing'));
            exit;
        }

        $provider = $this->resolveProvider();
        if (! $provider instanceof BillingProviderInterface) {
            emonks_flash_add('billing_error', 'Stripe unavailable.');
            wp_safe_redirect(emonks_get_account_url('billing'));
            exit;
        }

        $result = $provider->changeSubscriptionPlan($userId, $targetPlan, $isUpgrade, $targetCycle);
        if (! empty($result['error'])) {
            emonks_flash_add('billing_error', 'Unable to change plan in Stripe.');
            wp_safe_redirect(emonks_get_account_url('billing'));
            exit;
        }

        emonks_flash_add('billing_success', $isUpgrade ? 'Plan upgraded successfully.' : 'Plan downgraded successfully.');
        do_action('emonks_billing_plan_changed', $userId, $currentPlan, $targetPlan, $isUpgrade, $result);
        wp_safe_redirect(emonks_get_account_url('billing'));
        exit;
    }

    private function planRank(string $plan): int
    {
        $order = array_keys(Plans::getPlans());
        $index = array_search($plan, $order, true);
        return is_int($index) ? $index : 0;
    }

    private function resolveProvider(): ?BillingProviderInterface
    {
        $stripe = Plugin::instance()->get('stripe');
        if (! $stripe instanceof Stripe) {
            return null;
        }

        $provider = new StripeBillingProvider($stripe);
        $custom = apply_filters('emonks_billing_provider', $provider);
        return $custom instanceof BillingProviderInterface ? $custom : $provider;
    }
}
