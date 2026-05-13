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
        $requestedAccountId = absint((string) ($_POST['account_id'] ?? 0));
        $title = sanitize_text_field((string) ($_POST['title'] ?? 'Untitled workspace'));
        $serviceType = sanitize_key((string) ($_POST['service_type'] ?? 'generic'));
        $formValidation = emonks_validate_form_payload('workspace_create', is_array($_POST) ? $_POST : []);
        $formErrors = is_array($formValidation['errors'] ?? null) ? $formValidation['errors'] : [];
        if (! empty($formErrors)) {
            emonks_flash_add('workspace_error', implode(' ', array_values($formErrors)));
            wp_safe_redirect($workspaceId > 0 ? emonks_get_account_url('workspaces/' . $workspaceId . '/edit') : emonks_get_account_url('workspaces'));
            exit;
        }

        $isNew = $workspaceId === 0;

        if ($isNew && ! emonks_user_can_create_workspace($userId)) {
            wp_die(esc_html__('Workspace limit reached for current plan.', 'emonks-saas-core'), 403);
        }

        if (! $isNew && ! emonks_user_can_access_workspace($userId, $workspaceId)) {
            wp_die(esc_html__('Unauthorized workspace edit.', 'emonks-saas-core'), 403);
        }

        $accountId = $requestedAccountId > 0 ? $requestedAccountId : emonks_get_primary_account_id($userId);
        if ($accountId <= 0) {
            wp_die(esc_html__('No account available for this workspace.', 'emonks-saas-core'), 422);
        }

        if (! user_can($userId, 'manage_options') && ! emonks_user_can_access_account($userId, $accountId)) {
            wp_die(esc_html__('You cannot assign this account.', 'emonks-saas-core'), 403);
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
        $workspaceStatus = sanitize_key((string) ($_POST['workspace_status'] ?? 'draft'));
        if (! array_key_exists($workspaceStatus, WorkspaceStatuses::all())) {
            $workspaceStatus = 'draft';
        }

        if ($serviceType === '' || ! array_key_exists($serviceType, Services::all())) {
            $serviceType = 'generic';
        }
        $moduleKey = in_array($serviceType, ['client_portal', 'guestbook'], true) ? $serviceType : 'generic';

        $settingsRaw = (string) ($_POST['settings_json'] ?? '{}');
        $settings = json_decode($settingsRaw, true);
        if (! is_array($settings)) {
            $settings = [];
            emonks_flash_add('workspace_warning', 'Settings JSON was invalid and has been reset to an empty object.');
        }

        $rawSlug = sanitize_title((string) ($_POST['public_slug'] ?? $title));
        if ($rawSlug === '') {
            $rawSlug = sanitize_title($title !== '' ? $title : 'workspace');
        }
        if (! emonks_is_workspace_slug_available($rawSlug, $workspaceId)) {
            $suggestions = emonks_suggest_workspace_slugs($rawSlug, $workspaceId, 3);
            $message = 'Public slug already exists.';
            if (! empty($suggestions)) {
                $message .= ' Suggestions: ' . implode(', ', $suggestions);
            }
            emonks_flash_add('workspace_error', $message);
            wp_safe_redirect($isNew ? emonks_get_account_url('workspaces') : emonks_get_account_url('workspaces/' . $workspaceId . '/edit'));
            exit;
        }

        $policy = emonks_get_service_policy($serviceType);
        $requiresBilling = (bool) ($policy['can_publish_requires_billing'] ?? true);
        $requiresOnboarding = (bool) ($policy['can_publish_requires_onboarding'] ?? true);
        $canPublish = user_can($userId, 'manage_options')
            || ((! $requiresBilling || emonks_user_has_active_subscription($userId)) && (! $requiresOnboarding || emonks_user_ready_to_publish_workspace($userId)));

        if ($workspaceStatus === 'published' && ! $canPublish) {
            $workspaceStatus = 'draft';
            emonks_flash_add('workspace_warning', 'Publishing requires completed onboarding and an active subscription. Saved as draft.');
        }

        update_post_meta($workspaceId, 'service_type', $serviceType);
        update_post_meta($workspaceId, 'module_key', $moduleKey);
        update_post_meta($workspaceId, 'account_id', $accountId);
        update_post_meta($workspaceId, 'workspace_status', $workspaceStatus);
        update_post_meta($workspaceId, 'public_slug', $rawSlug);
        update_post_meta($workspaceId, 'settings', wp_json_encode($settings));
        update_post_meta($workspaceId, 'service_data_json', wp_json_encode($this->collectServiceData($serviceType)));
        update_post_meta($workspaceId, 'created_by', $isNew ? $userId : (int) get_post_meta($workspaceId, 'created_by', true));
        update_post_meta($workspaceId, 'updated_at', current_time('mysql'));

        do_action($isNew ? 'emonks_workspace_created' : 'emonks_workspace_updated', $workspaceId, $userId);

        wp_safe_redirect(emonks_get_account_url('workspaces'));
        exit;
    }

    /** @return array<string,mixed> */
    private function collectServiceData(string $serviceType): array
    {
        $schema = emonks_get_service_schema($serviceType);
        $fields = is_array($schema['fields'] ?? null) ? $schema['fields'] : [];
        $data = [];

        foreach ($fields as $field) {
            if (! is_array($field)) {
                continue;
            }

            $key = sanitize_key((string) ($field['key'] ?? ''));
            if ($key === '') {
                continue;
            }

            $type = sanitize_key((string) ($field['type'] ?? 'text'));
            $raw = (string) ($_POST['service_data_' . $key] ?? '');
            $data[$key] = match ($type) {
                'textarea' => sanitize_textarea_field($raw),
                'email' => sanitize_email($raw),
                'url' => esc_url_raw($raw),
                default => sanitize_text_field($raw),
            };
        }

        return $data;
    }
}
