<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

final class RestApi
{
    public function boot(): void
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void
    {
        register_rest_route('emonks/v1', '/health', ['methods' => 'GET', 'callback' => [$this, 'health'], 'permission_callback' => '__return_true']);
        register_rest_route('emonks/v1', '/me', ['methods' => 'GET', 'callback' => [$this, 'me'], 'permission_callback' => [$this, 'canReadCurrentUser']]);

        register_rest_route('emonks/v1', '/services', ['methods' => 'GET', 'callback' => [$this, 'services'], 'permission_callback' => [$this, 'canReadCurrentUser']]);
        register_rest_route('emonks/v1', '/workspaces', ['methods' => 'GET', 'callback' => [$this, 'workspacesIndex'], 'permission_callback' => [$this, 'canListWorkspaces']]);
        register_rest_route('emonks/v1', '/workspaces', [
            'methods' => 'POST',
            'callback' => [$this, 'workspacesCreate'],
            'permission_callback' => [$this, 'canCreateWorkspace'],
            'args' => [
                'title' => [
                    'type' => 'string',
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'service_type' => [
                    'type' => 'string',
                    'required' => false,
                    'sanitize_callback' => 'sanitize_key',
                    'validate_callback' => [$this, 'validateServiceType'],
                ],
            ],
        ]);
        register_rest_route('emonks/v1', '/workspaces/(?P<id>\d+)', ['methods' => 'GET', 'callback' => [$this, 'workspacesShow'], 'permission_callback' => [$this, 'canReadWorkspace']]);
        register_rest_route('emonks/v1', '/workspaces/(?P<id>\d+)', [
            'methods' => 'PUT,PATCH',
            'callback' => [$this, 'workspacesUpdate'],
            'permission_callback' => [$this, 'canUpdateWorkspace'],
            'args' => [
                'title' => [
                    'type' => 'string',
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'workspace_status' => [
                    'type' => 'string',
                    'required' => false,
                    'sanitize_callback' => 'sanitize_key',
                    'validate_callback' => [$this, 'validateWorkspaceStatus'],
                ],
                'public_slug' => [
                    'type' => 'string',
                    'required' => false,
                    'sanitize_callback' => 'sanitize_title',
                    'validate_callback' => [$this, 'validatePublicSlug'],
                ],
            ],
        ]);

        register_rest_route('emonks/v1', '/config/forms/(?P<key>[a-z0-9_\\-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'configFormsGet'],
            'permission_callback' => [$this, 'canManageConfig'],
        ]);
        register_rest_route('emonks/v1', '/config/forms/(?P<key>[a-z0-9_\\-]+)', [
            'methods' => 'PUT,PATCH',
            'callback' => [$this, 'configFormsUpdate'],
            'permission_callback' => [$this, 'canManageConfig'],
        ]);
        register_rest_route('emonks/v1', '/config/services/(?P<key>[a-z0-9_\\-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'configServicesGet'],
            'permission_callback' => [$this, 'canManageConfig'],
        ]);
        register_rest_route('emonks/v1', '/config/services/(?P<key>[a-z0-9_\\-]+)', [
            'methods' => 'PUT,PATCH',
            'callback' => [$this, 'configServicesUpdate'],
            'permission_callback' => [$this, 'canManageConfig'],
        ]);
    }

    public function health(): \WP_REST_Response
    {
        return new \WP_REST_Response(['ok' => true, 'service' => 'emonks-saas-core'], 200);
    }

    public function me(): \WP_REST_Response
    {
        $user = wp_get_current_user();
        return new \WP_REST_Response(['id' => $user->ID, 'email' => $user->user_email, 'plan' => emonks_get_current_user_plan()], 200);
    }

    public function services(): \WP_REST_Response
    {
        return new \WP_REST_Response(['services' => Services::all()], 200);
    }

    public function workspacesIndex(): \WP_REST_Response
    {
        $items = array_map(static function ($post) {
            return ['id' => $post->ID, 'title' => $post->post_title, 'service_type' => emonks_get_workspace_meta($post->ID, 'service_type', ''), 'workspace_status' => emonks_get_workspace_status($post->ID)];
        }, emonks_get_user_workspaces(get_current_user_id()));

        return new \WP_REST_Response(['items' => $items], 200);
    }

    public function workspacesShow(\WP_REST_Request $request): \WP_REST_Response
    {
        $id = absint((string) $request['id']);
        if (! emonks_user_can_access_workspace(get_current_user_id(), $id)) {
            return emonks_rest_error('forbidden', 'Forbidden', [], 403);
        }

        $post = get_post($id);
        if (! $post instanceof \WP_Post) {
            return emonks_rest_error('not_found', 'Not found', [], 404);
        }

        return new \WP_REST_Response([
            'id' => $post->ID,
            'title' => $post->post_title,
            'service_type' => emonks_get_workspace_meta($id, 'service_type', ''),
            'workspace_status' => emonks_get_workspace_status($id),
            'public_slug' => emonks_get_workspace_meta($id, 'public_slug', ''),
            'settings' => emonks_get_workspace_meta($id, 'settings', '{}'),
        ], 200);
    }

    public function workspacesCreate(\WP_REST_Request $request): \WP_REST_Response
    {
        $userId = get_current_user_id();
        $title = sanitize_text_field((string) $request->get_param('title'));
        $serviceType = sanitize_key((string) $request->get_param('service_type'));

        $id = wp_insert_post(['post_type' => 'emonks_workspace', 'post_title' => $title !== '' ? $title : 'Untitled workspace', 'post_status' => 'publish', 'post_author' => $userId]);
        if (! is_numeric($id) || (int) $id <= 0) {
            return emonks_rest_error('create_failed', 'Create failed', [], 500);
        }

        $id = (int) $id;
        $accountId = emonks_get_primary_account_id($userId);
        if ($accountId <= 0) {
            return emonks_rest_error('no_account', 'No account available', [], 422);
        }

        update_post_meta($id, 'account_id', $accountId);
        update_post_meta($id, 'service_type', $serviceType !== '' ? $serviceType : '');
        update_post_meta($id, 'workspace_status', 'draft');
        $slug = sanitize_title($title);
        if (! emonks_is_workspace_slug_available($slug, $id)) {
            $suggestions = emonks_suggest_workspace_slugs($slug, $id, 1);
            $slug = $suggestions[0] ?? ($slug . '-' . $id);
        }
        update_post_meta($id, 'public_slug', $slug);
        update_post_meta($id, 'created_by', $userId);
        update_post_meta($id, 'updated_at', current_time('mysql'));

        return new \WP_REST_Response(['id' => $id], 201);
    }

    public function workspacesUpdate(\WP_REST_Request $request): \WP_REST_Response
    {
        $id = absint((string) $request['id']);
        $title = sanitize_text_field((string) $request->get_param('title'));
        $status = sanitize_key((string) $request->get_param('workspace_status'));
        $slug = sanitize_title((string) $request->get_param('public_slug'));
        $serviceType = emonks_get_workspace_meta($id, 'service_type', '');

        if ($title !== '') {
            wp_update_post(['ID' => $id, 'post_title' => $title]);
        }

        if ($status !== '') {
            $policy = emonks_get_service_policy((string) $serviceType);
            $requiresBilling = (bool) ($policy['can_publish_requires_billing'] ?? true);
            $requiresOnboarding = (bool) ($policy['can_publish_requires_onboarding'] ?? true);
            $canPublish = user_can(get_current_user_id(), 'manage_options')
                || ((! $requiresBilling || emonks_user_has_active_subscription(get_current_user_id())) && (! $requiresOnboarding || emonks_user_ready_to_publish_workspace(get_current_user_id())));
            if ($status === 'published' && ! $canPublish) {
                return emonks_rest_error('publish_requirements_not_met', 'Publishing requires active subscription and completed onboarding', [], 422);
            }
            update_post_meta($id, 'workspace_status', $status);
        }

        if ($slug !== '') {
            if (! emonks_is_workspace_slug_available($slug, $id)) {
                return emonks_rest_error(
                    'slug_conflict',
                    'Public slug already exists',
                    ['suggestions' => emonks_suggest_workspace_slugs($slug, $id, 3)],
                    422
                );
            }
            update_post_meta($id, 'public_slug', $slug);
        }

        update_post_meta($id, 'updated_at', current_time('mysql'));
        return new \WP_REST_Response(['ok' => true], 200);
    }

    public function canReadCurrentUser(): bool
    {
        return is_user_logged_in();
    }

    public function canListWorkspaces(): bool
    {
        return is_user_logged_in();
    }

    public function canCreateWorkspace(): bool
    {
        if (! is_user_logged_in()) {
            return false;
        }

        return emonks_user_can_create_workspace(get_current_user_id());
    }

    public function canReadWorkspace(\WP_REST_Request $request): bool
    {
        if (! is_user_logged_in()) {
            return false;
        }

        return emonks_user_can_access_workspace(get_current_user_id(), absint((string) $request['id']));
    }

    public function canUpdateWorkspace(\WP_REST_Request $request): bool
    {
        return $this->canReadWorkspace($request);
    }

    public function validateServiceType($value): bool
    {
        $serviceType = sanitize_key((string) $value);
        if ($serviceType === '') {
            return true;
        }

        return array_key_exists($serviceType, Services::all());
    }

    public function validateWorkspaceStatus($value): bool
    {
        $status = sanitize_key((string) $value);
        if ($status === '') {
            return true;
        }

        return array_key_exists($status, WorkspaceStatuses::all());
    }

    public function validatePublicSlug($value): bool
    {
        $slug = sanitize_title((string) $value);
        return $slug === '' || strlen($slug) <= 120;
    }

    public function validateGenericStatus($value): bool
    {
        $status = sanitize_key((string) $value);
        if ($status === '') {
            return true;
        }

        $vocabulary = emonks_status_vocabulary();
        return isset($vocabulary[$status]);
    }

    public function canManageConfig(): bool
    {
        return current_user_can('manage_options');
    }

    public function configFormsGet(\WP_REST_Request $request): \WP_REST_Response
    {
        $key = sanitize_key((string) $request['key']);
        return new \WP_REST_Response(['key' => $key, 'schema' => emonks_get_form_template($key)], 200);
    }

    public function configFormsUpdate(\WP_REST_Request $request): \WP_REST_Response
    {
        $key = sanitize_key((string) $request['key']);
        $schema = $request->get_json_params();
        if (! is_array($schema)) {
            return emonks_rest_error('invalid_payload', 'Invalid schema payload', [], 422);
        }

        $all = emonks_get_form_templates();
        $forms = is_array($all['forms'] ?? null) ? $all['forms'] : [];
        $forms[$key] = $schema;
        emonks_update_setting('form_templates', ['schema_version' => 1, 'forms' => $forms]);
        return new \WP_REST_Response(['ok' => true], 200);
    }

    public function configServicesGet(\WP_REST_Request $request): \WP_REST_Response
    {
        $key = sanitize_key((string) $request['key']);
        return new \WP_REST_Response(['key' => $key, 'schema' => emonks_get_service_schema($key)], 200);
    }

    public function configServicesUpdate(\WP_REST_Request $request): \WP_REST_Response
    {
        $key = sanitize_key((string) $request['key']);
        $schema = $request->get_json_params();
        if (! is_array($schema)) {
            return emonks_rest_error('invalid_payload', 'Invalid schema payload', [], 422);
        }

        $all = emonks_get_service_schemas();
        $services = is_array($all['services'] ?? null) ? $all['services'] : [];
        $services[$key] = $schema;
        emonks_update_setting('service_schemas', ['schema_version' => 1, 'config_model' => 'dynamic_services_v1', 'services' => $services]);
        return new \WP_REST_Response(['ok' => true], 200);
    }
}
