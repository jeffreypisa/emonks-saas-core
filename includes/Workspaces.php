<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

/**
 * Class: Workspaces
 * Purpose: Workspace post type and CRUD handling.
 * Responsibilities: Register CPT, handle create/update actions, persist core meta.
 * Example: Workspaces::registerPostType().
 * Hooks: init, admin_post_*.
 * Architecture Role: Domain model manager for workspaces.
 */
final class Workspaces
{
    public function boot(): void
    {
        add_action('init', [self::class, 'registerPostType']);
        add_action('admin_post_emonks_workspace_save', [$this, 'handleSave']);
        add_action('admin_post_nopriv_emonks_workspace_save', [$this, 'rejectGuest']);
    }

    public static function registerPostType(): void
    {
        register_post_type('emonks_workspace', [
            'label' => 'Workspaces',
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => ['title', 'author'],
            'has_archive' => false,
            'rewrite' => false,
            'map_meta_cap' => true,
        ]);
    }

    public function rejectGuest(): void
    {
        wp_safe_redirect(emonks_get_login_url());
        exit;
    }

    public function handleSave(): void
    {
        if (! is_user_logged_in()) {
            wp_safe_redirect(emonks_get_login_url());
            exit;
        }

        check_admin_referer('emonks_workspace_save', 'emonks_nonce');

        $userId = get_current_user_id();
        $workspaceId = absint((string) ($_POST['workspace_id'] ?? 0));
        $title = sanitize_text_field((string) ($_POST['title'] ?? 'Untitled workspace'));
        $serviceType = sanitize_key((string) ($_POST['service_type'] ?? 'generic'));

        $isNew = $workspaceId === 0;

        if ($isNew && ! emonks_user_can_create_workspace($userId)) {
            wp_die(esc_html__('Workspace limit reached for current plan.', 'emonks-saas-core'), 403);
        }

        if (! $isNew && ! emonks_user_can_access_workspace($userId, $workspaceId)) {
            wp_die(esc_html__('Unauthorized workspace edit.', 'emonks-saas-core'), 403);
        }

        if ($isNew) {
            $workspaceId = wp_insert_post([
                'post_type' => 'emonks_workspace',
                'post_title' => $title,
                'post_status' => 'publish',
                'post_author' => $userId,
            ]);
        } else {
            wp_update_post([
                'ID' => $workspaceId,
                'post_title' => $title,
            ]);
        }

        if (! is_numeric($workspaceId) || (int) $workspaceId <= 0) {
            wp_die(esc_html__('Workspace save failed.', 'emonks-saas-core'), 500);
        }

        $workspaceId = (int) $workspaceId;

        update_post_meta($workspaceId, 'service_type', $serviceType);
        update_post_meta($workspaceId, 'workspace_status', sanitize_key((string) ($_POST['workspace_status'] ?? 'draft')));
        update_post_meta($workspaceId, 'public_slug', sanitize_title((string) ($_POST['public_slug'] ?? $title)));
        update_post_meta($workspaceId, 'settings', wp_kses_post((string) ($_POST['settings_json'] ?? '{}')));
        update_post_meta($workspaceId, 'created_by', $isNew ? $userId : (int) get_post_meta($workspaceId, 'created_by', true));
        update_post_meta($workspaceId, 'updated_at', current_time('mysql'));

        do_action($isNew ? 'emonks_workspace_created' : 'emonks_workspace_updated', $workspaceId, $userId);

        wp_safe_redirect(emonks_get_account_url('workspaces'));
        exit;
    }
}
