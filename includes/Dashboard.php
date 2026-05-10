<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

/**
 * Class: Dashboard
 * Purpose: Account/public page orchestration.
 * Responsibilities: Route-to-template mapping, context building, access checks.
 * Example: handleAccountRoute('account_workspaces').
 * Hooks: none direct.
 * Architecture Role: Presentation coordinator between routing and template loader.
 */
final class Dashboard
{
    public function boot(): void
    {
    }

    public function handleAccountRoute(string $route): void
    {
        if (! is_user_logged_in()) {
            wp_safe_redirect(emonks_get_login_url());
            return;
        }

        $context = emonks_default_context();
        $userId = get_current_user_id();
        $context['workspaces'] = emonks_get_user_workspaces($userId);
        $context['workspace_count'] = count($context['workspaces']);
        $context['workspace_limit'] = emonks_get_plan_limit((string) $context['current_plan'], 'max_workspaces', 0);
        $context['can_create_workspace'] = emonks_user_can_create_workspace($userId);
        $context['billing_active'] = emonks_user_has_active_subscription($userId);
        $context['workspace_status_counts'] = emonks_get_workspace_status_counts($userId);
        $context['admin_post'] = [
            'workspace_save' => emonks_admin_post_url('emonks_workspace_save'),
            'billing_checkout' => emonks_admin_post_url('emonks_billing_checkout'),
            'billing_portal' => emonks_admin_post_url('emonks_billing_portal'),
            'billing_change_plan' => emonks_admin_post_url('emonks_billing_change_plan'),
            'onboarding_step' => emonks_admin_post_url('emonks_onboarding_step'),
        ];

        $template = match ($route) {
            'account_dashboard' => 'account/dashboard.twig',
            'account_workspaces', 'account_workspaces_new' => 'account/workspaces.twig',
            'account_workspace_edit' => 'account/workspace-edit.twig',
            'account_billing' => 'account/billing.twig',
            'account_settings' => 'account/settings.twig',
            'account_onboarding' => 'account/onboarding.twig',
            default => 'account/dashboard.twig',
        };

        if ($route === 'account_workspace_edit') {
            $workspaceId = absint((string) get_query_var('emonks_workspace_id'));
            if (! emonks_user_can_access_workspace(get_current_user_id(), $workspaceId)) {
                wp_die(esc_html__('You cannot access this workspace.', 'emonks-saas-core'), 403);
            }
            $context['workspace'] = get_post($workspaceId);
        }

        emonks_render_template($template, $context);
    }

    public function renderPublicWorkspace(): void
    {
        $slug = sanitize_title((string) get_query_var('emonks_public_slug'));
        $workspaceId = emonks_get_workspace_by_slug($slug);
        $host = sanitize_text_field((string) ($_SERVER['HTTP_HOST'] ?? ''));
        if ($workspaceId <= 0 && $host !== '') {
            $domains = Plugin::instance()->get('custom_domains');
            if ($domains instanceof CustomDomains) {
                $mapping = $domains->resolveByHost($host);
                $workspaceId = absint((string) ($mapping['workspace_id'] ?? 0));
            }
        }

        if (! $workspaceId) {
            status_header(404);
            return;
        }

        $workspace = get_post($workspaceId);
        if (! $workspace instanceof \WP_Post) {
            status_header(404);
            return;
        }

        $ownerId = (int) $workspace->post_author;

        if (
            ! emonks_workspace_has_status($workspaceId, 'published')
            || ! emonks_user_has_active_subscription($ownerId)
        ) {
            status_header(404);
            return;
        }

        $context = emonks_default_context();
        $context['workspace'] = $workspace;
        $context['owner'] = get_userdata($ownerId);
        $context['service_type'] = emonks_get_workspace_meta($workspaceId, 'service_type', '');
        $context['settings'] = emonks_get_workspace_meta($workspaceId, 'settings', []);

        emonks_render_template('public/workspace.twig', $context);
    }
}
