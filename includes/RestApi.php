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
            return ['id' => $post->ID, 'title' => $post->post_title, 'service_type' => emonks_get_workspace_meta($post->ID, 'service_type', 'generic'), 'workspace_status' => emonks_get_workspace_status($post->ID)];
        }, emonks_get_user_workspaces(get_current_user_id()));

        return new \WP_REST_Response(['items' => $items], 200);
    }

    public function workspacesShow(\WP_REST_Request $request): \WP_REST_Response
    {
        $id = absint((string) $request['id']);
        if (! emonks_user_can_access_workspace(get_current_user_id(), $id)) {
            return new \WP_REST_Response(['message' => 'Forbidden'], 403);
        }

        $post = get_post($id);
        if (! $post instanceof \WP_Post) {
            return new \WP_REST_Response(['message' => 'Not found'], 404);
        }

        return new \WP_REST_Response([
            'id' => $post->ID,
            'title' => $post->post_title,
            'service_type' => emonks_get_workspace_meta($id, 'service_type', 'generic'),
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
            return new \WP_REST_Response(['message' => 'Create failed'], 500);
        }

        $id = (int) $id;
        update_post_meta($id, 'service_type', $serviceType !== '' ? $serviceType : 'generic');
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
        $serviceType = emonks_get_workspace_meta($id, 'service_type', 'generic');

        if ($title !== '') {
            wp_update_post(['ID' => $id, 'post_title' => $title]);
        }

        if ($status !== '') {
            $policy = emonks_get_service_policy((string) $serviceType);
            $requiresBilling = (bool) ($policy['can_publish_requires_billing'] ?? true);
            $requiresOnboarding = (bool) ($policy['can_publish_requires_onboarding'] ?? true);
            $canPublish = user_can(get_current_user_id(), 'manage_options')
                || ((! $requiresBilling || emonks_user_has_active_subscription(get_current_user_id())) && (! $requiresOnboarding || emonks_user_completed_onboarding(get_current_user_id())));
            if ($status === 'published' && ! $canPublish) {
                return new \WP_REST_Response(['message' => 'Publishing requires active subscription and completed onboarding'], 422);
            }
            update_post_meta($id, 'workspace_status', $status);
        }

        if ($slug !== '') {
            if (! emonks_is_workspace_slug_available($slug, $id)) {
                return new \WP_REST_Response([
                    'message' => 'Public slug already exists',
                    'suggestions' => emonks_suggest_workspace_slugs($slug, $id, 3),
                ], 422);
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
}
