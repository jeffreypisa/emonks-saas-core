<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

final class Policy
{
    public function boot(): void
    {
    }

    public function can(int $userId, string $capability, int $accountId): bool
    {
        if (user_can($userId, 'manage_options')) {
            return true;
        }

        if ($accountId <= 0 || ! emonks_user_can_access_account($userId, $accountId)) {
            return false;
        }

        $role = emonks_get_account_role($userId, $accountId);
        $capability = sanitize_key($capability);

        $matrix = [
            'account_owner' => ['cp_view', 'cp_manage', 'cp_create', 'cp_update', 'gb_view', 'gb_manage'],
            'account_manager' => ['cp_view', 'cp_create', 'cp_update', 'gb_view', 'gb_manage'],
            'account_member' => ['cp_view', 'gb_view'],
        ];

        $allowed = in_array($capability, $matrix[$role] ?? [], true);
        return (bool) apply_filters('emonks_policy_can', $allowed, $userId, $capability, $accountId, $role);
    }
}
