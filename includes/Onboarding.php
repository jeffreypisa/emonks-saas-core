<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

final class Onboarding
{
    public function boot(): void
    {
        add_action('emonks_account_registered', [$this, 'markStarted']);
        add_action('emonks_workspace_created', [$this, 'markFirstWorkspace'], 10, 2);
        add_action('emonks_workspace_updated', [$this, 'markWorkspacePublishedFromWorkspace'], 10, 2);
        add_action('emonks_billing_plan_changed', [$this, 'markBillingConnected'], 10, 1);
        add_action('emonks_stripe_synced_subscription', [$this, 'markBillingConnectedFromWebhook'], 10, 2);
    }

    public function markStarted(int $userId): void
    {
        update_user_meta($userId, 'emonks_onboarding_status', 'started');
        update_user_meta($userId, 'emonks_onboarding_steps', ['account_created' => true]);
        do_action('emonks_onboarding_started', $userId);
    }

    public function markFirstWorkspace(int $workspaceId, int $userId): void
    {
        if ($workspaceId <= 0 || $userId <= 0) {
            return;
        }

        $this->markStep($userId, 'first_workspace');
    }

    public function markWorkspacePublishedFromWorkspace(int $workspaceId, int $userId): void
    {
        if ($workspaceId <= 0 || $userId <= 0) {
            return;
        }

        if (emonks_workspace_has_status($workspaceId, 'published')) {
            $this->markStep($userId, 'workspace_published');
        }
    }

    public function markBillingConnected(int $userId): void
    {
        if ($userId <= 0 || ! emonks_user_has_active_subscription($userId)) {
            return;
        }

        $this->markStep($userId, 'billing_connected');
    }

    /** @param array<string,mixed> $event */
    public function markBillingConnectedFromWebhook(array $event, int $userId): void
    {
        unset($event);
        $this->markBillingConnected($userId);
    }

    public function handleStepCompletion(): void
    {
        if (! is_user_logged_in()) {
            wp_safe_redirect(emonks_get_login_url());
            exit;
        }

        check_admin_referer('emonks_onboarding_step', 'emonks_nonce');
        $userId = get_current_user_id();
        $step = sanitize_key((string) ($_POST['step'] ?? ''));

        $steps = get_user_meta($userId, 'emonks_onboarding_steps', true);
        if (! is_array($steps)) {
            $steps = [];
        }

        if ($step !== '') {
            $steps[$step] = true;
            update_user_meta($userId, 'emonks_onboarding_steps', $steps);
        }

        $progress = $this->getProgress($userId);
        if ((bool) ($progress['completed'] ?? false)) {
            update_user_meta($userId, 'emonks_onboarding_status', 'completed');
            do_action('emonks_onboarding_completed', $userId);
        }

        wp_safe_redirect(emonks_get_account_url('onboarding'));
        exit;
    }

    private function markStep(int $userId, string $step): void
    {
        $steps = get_user_meta($userId, 'emonks_onboarding_steps', true);
        if (! is_array($steps)) {
            $steps = [];
        }

        $steps[sanitize_key($step)] = true;
        update_user_meta($userId, 'emonks_onboarding_steps', $steps);

        $progress = $this->getProgress($userId);
        if ((bool) ($progress['completed'] ?? false)) {
            update_user_meta($userId, 'emonks_onboarding_status', 'completed');
            do_action('emonks_onboarding_completed', $userId);
        } else {
            update_user_meta($userId, 'emonks_onboarding_status', 'started');
        }
    }

    /** @return array<string,mixed> */
    public function getProgress(int $userId): array
    {
        $stored = get_user_meta($userId, 'emonks_onboarding_steps', true);
        if (! is_array($stored)) {
            $stored = [];
        }

        $evaluators = [
            'account_created' => static fn(int $id, array $done): bool => true || ! empty($done['account_created']),
            'first_workspace' => static fn(int $id, array $done): bool => emonks_count_user_workspaces($id) > 0 || ! empty($done['first_workspace']),
            'billing_connected' => static fn(int $id, array $done): bool => emonks_user_has_active_subscription($id) || ! empty($done['billing_connected']),
            'workspace_published' => static fn(int $id, array $done): bool => emonks_user_has_published_workspace($id) || ! empty($done['workspace_published']),
        ];
        $evaluators = apply_filters('emonks_onboarding_step_evaluators', $evaluators, $userId, $stored);

        $checklist = array_keys($evaluators);
        $checklist = apply_filters('emonks_onboarding_checklist', $checklist, $userId);

        $steps = [];
        foreach ($checklist as $step) {
            $key = sanitize_key((string) $step);
            $evaluator = $evaluators[$key] ?? null;
            if (is_callable($evaluator)) {
                $steps[$key] = (bool) call_user_func($evaluator, $userId, $stored);
            } else {
                $steps[$key] = ! empty($stored[$key]);
            }
        }

        $done = count(array_filter($steps, static fn($v) => (bool) $v));
        $total = max(1, count($steps));

        return apply_filters('emonks_onboarding_progress', [
            'steps' => $steps,
            'checklist' => array_keys($steps),
            'percentage' => $total > 0 ? (int) floor(($done / $total) * 100) : 0,
            'completed' => $done === $total,
        ], $userId);
    }
}
