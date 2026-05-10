<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

/**
 * Class: WorkspaceStatuses
 * Purpose: Central status vocabulary for workspaces.
 * Responsibilities: Expose canonical statuses and filters.
 * Example: WorkspaceStatuses::all().
 * Hooks: emonks_workspace_statuses filter.
 * Architecture Role: Domain consistency for workspace lifecycle.
 */
final class WorkspaceStatuses
{
    /** @return array<string,string> */
    public static function all(): array
    {
        $statuses = [
            'draft' => 'Draft',
            'active' => 'Active',
            'suspended' => 'Suspended',
            'archived' => 'Archived',
            'published' => 'Published',
        ];

        return apply_filters('emonks_workspace_statuses', $statuses);
    }

    public function boot(): void
    {
    }
}
