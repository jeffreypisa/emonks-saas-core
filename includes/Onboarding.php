<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

final class Onboarding
{
    public function boot(): void
    {
        add_action('emonks_account_registered', [$this, 'markStarted']);
        add_action('admin_post_emonks_onboarding_step', [$this, 'handleStepCompletion']);
    }

    public function markStarted(int $userId): void
    {
        update_user_meta($userId, 'emonks_onboarding_status', 'started');
        update_user_meta($userId, 'emonks_onboarding_steps', ['account_created' => true]);
        do_action('emonks_onboarding_started', $userId);
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

    /** @return array<string,mixed> */
    public function getProgress(int $userId): array
    {
        $stored = get_user_meta($userId, 'emonks_onboarding_steps', true);
        if (! is_array($stored)) {
            $stored = [];
        }

        $steps = [
            'account_created' => true,
            'first_workspace' => emonks_count_user_workspaces($userId) > 0 || ! empty($stored['first_workspace']),
            'billing_connected' => emonks_user_has_active_subscription($userId) || ! empty($stored['billing_connected']),
            'workspace_published' => emonks_user_has_published_workspace($userId) || ! empty($stored['workspace_published']),
        ];

        $done = count(array_filter($steps));
        $total = count($steps);

        return apply_filters('emonks_onboarding_progress', [
            'steps' => $steps,
            'checklist' => array_keys($steps),
            'percentage' => $total > 0 ? (int) floor(($done / $total) * 100) : 0,
            'completed' => $done === $total,
        ], $userId);
    }
}
