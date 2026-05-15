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
        $context['dashboard_cards'] = apply_filters('emonks_dashboard_cards', [], $userId);
        $context['service_types'] = emonks_get_available_services_for_plan((string) $context['current_plan']);
        $context['all_service_types'] = Services::all();
        $context['service_schemas'] = emonks_get_service_schemas();
        $context['field_library'] = emonks_get_field_library();
        $context['form_templates'] = emonks_get_form_templates();
        $context['acf_available'] = emonks_acf_available();
        $context['acf_groups'] = emonks_get_acf_field_groups();
        $context['workspace_create_form'] = emonks_get_form_schema('workspace_create');
        $context['workspace_create_service_forms'] = [];
        foreach (array_keys($context['all_service_types']) as $serviceKey) {
            $context['workspace_create_service_forms'][$serviceKey] = emonks_resolve_forms_for_context((string) $serviceKey, 'workspace_create');
        }
        $context['workspace_statuses'] = WorkspaceStatuses::all();
        $context['next_action'] = $this->resolveNextAction($userId, $context);
        $context['admin_post'] = [
            'workspace_save' => emonks_admin_post_url('emonks_workspace_save'),
            'billing_checkout' => emonks_admin_post_url('emonks_billing_checkout'),
            'billing_portal' => emonks_admin_post_url('emonks_billing_portal'),
            'billing_change_plan' => emonks_admin_post_url('emonks_billing_change_plan'),
            'account_delete_request' => emonks_admin_post_url('emonks_account_delete_request'),
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
            $serviceContext = emonks_get_workspace_service_context($workspaceId);
            $context['workspace_service_data'] = $serviceContext['service_data'];
            $context['workspace_acf_data'] = $serviceContext['acf_data'];
            $context['workspace_service_context'] = $serviceContext;
            $context['workspace_service_forms'] = $serviceContext['forms']['workspace_edit'] ?? [];
        }

        if ($route === 'account_billing') {
            $context['billing_preview'] = [
                'plan' => sanitize_key((string) ($_GET['preview_plan'] ?? '')),
                'cycle' => sanitize_key((string) ($_GET['preview_cycle'] ?? '')),
                'is_upgrade' => (string) ($_GET['preview_upgrade'] ?? '0') === '1',
            ];
        }

        emonks_render_template($template, $context);
    }

    /** @param array<string,mixed> $context */
    private function resolveNextAction(int $userId, array $context): array
    {
        if ((int) ($context['workspace_count'] ?? 0) === 0) {
            return ['label' => 'Maak je eerste workspace', 'url' => emonks_get_account_url('workspaces'), 'variant' => 'primary'];
        }

        if (! emonks_user_has_active_subscription($userId)) {
            return ['label' => 'Activeer billing', 'url' => emonks_get_account_url('billing'), 'variant' => 'warning'];
        }

        if (! emonks_user_completed_onboarding($userId)) {
            return ['label' => 'Rond onboarding af', 'url' => emonks_get_account_url('onboarding'), 'variant' => 'info'];
        }

        if (! emonks_user_has_published_workspace($userId)) {
            return ['label' => 'Publiceer een workspace', 'url' => emonks_get_account_url('workspaces'), 'variant' => 'success'];
        }

        return ['label' => 'Alles staat goed', 'url' => emonks_get_account_url(), 'variant' => 'neutral'];
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
        $serviceType = sanitize_key((string) emonks_get_workspace_meta($workspaceId, 'service_type', ''));
        $policy = emonks_get_service_policy($serviceType);
        $requiresBilling = (bool) ($policy['can_publish_requires_billing'] ?? true);
        $billingBlocked = $requiresBilling
            && ! emonks_is_billing_test_mode()
            && ! emonks_user_has_active_subscription($ownerId);

        if (
            ! emonks_workspace_has_status($workspaceId, 'published')
            || $billingBlocked
        ) {
            status_header(404);
            return;
        }

        $context = emonks_default_context();
        $context['workspace'] = $workspace;
        $context['owner'] = get_userdata($ownerId);
        $context['service_type'] = $serviceType;
        $context['settings'] = emonks_get_workspace_meta($workspaceId, 'settings', []);
        $serviceContext = emonks_get_workspace_service_context($workspaceId);
        $context['service_data'] = $serviceContext['service_data'];
        $context['acf_data'] = $serviceContext['acf_data'];
        $context['service_context'] = $serviceContext;
        $context['field_source'] = $serviceContext['field_source'];
        $serviceSchema = is_array($serviceContext['service'] ?? null) ? $serviceContext['service'] : emonks_get_service_schema((string) $context['service_type']);
        $context['service_schema'] = $serviceSchema;
        $preferredTemplate = sanitize_text_field((string) ($serviceSchema['render_hints']['template'] ?? ''));
        $template = $preferredTemplate !== '' ? $preferredTemplate : 'public/workspace.twig';
        emonks_render_template($template, $context);
    }
}
