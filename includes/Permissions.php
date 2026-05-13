<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

/**
 * Class: Permissions
 * Purpose: Ownership and access policy checks.
 * Responsibilities: Determine owner/admin access and subscription gate checks.
 * Example: userCanAccessWorkspace(12, 100).
 * Hooks: emonks_user_can_access_workspace filter.
 * Architecture Role: Authorization layer.
 */
final class Permissions
{
    public function boot(): void
    {
    }

    public function userCanAccessWorkspace(int $userId, int $workspaceId): bool
    {
        $allowed = emonks_can_access_entity_account('workspace', $workspaceId, $userId);
        return (bool) apply_filters('emonks_user_can_access_workspace', $allowed, $userId, $workspaceId);
    }
}
