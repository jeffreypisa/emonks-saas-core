<?php
declare(strict_types=1);

use Emonks\SaasCore\Features;
use Emonks\SaasCore\Permissions;
use Emonks\SaasCore\Plans;
use Emonks\SaasCore\Plugin;
use Emonks\SaasCore\Services;
use Emonks\SaasCore\WorkspaceStatuses;

if (! defined('ABSPATH')) {
    exit;
}

function emonks_get_setting(string $key, $default = null)
{
    $settings = get_option(Emonks\SaasCore\Settings::OPTION_KEY, []);
    if (! is_array($settings)) {
        return $default;
    }

    $parts = explode('.', $key);
    $value = $settings;
    foreach ($parts as $part) {
        if (! is_array($value) || ! array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }

    return $value;
}

function emonks_get_setting_with_overrides(string $key, $default = null, string $serviceType = '', int $workspaceId = 0)
{
    $serviceType = sanitize_key($serviceType);
    $workspaceId = absint((string) $workspaceId);

    if ($workspaceId > 0) {
        $workspaceSettingsRaw = emonks_get_workspace_meta($workspaceId, 'settings', '{}');
        $workspaceSettings = json_decode((string) $workspaceSettingsRaw, true);
        if (is_array($workspaceSettings) && array_key_exists($key, $workspaceSettings)) {
            return $workspaceSettings[$key];
        }
    }

    if ($serviceType !== '') {
        $serviceSettings = emonks_get_setting('services.' . $serviceType, []);
        if (is_array($serviceSettings) && array_key_exists($key, $serviceSettings)) {
            return $serviceSettings[$key];
        }
    }

    return emonks_get_setting($key, $default);
}

function emonks_update_setting(string $key, $value): bool
{
    $settings = get_option(Emonks\SaasCore\Settings::OPTION_KEY, []);
    if (! is_array($settings)) {
        $settings = [];
    }

    $parts = explode('.', $key);
    $target =& $settings;
    foreach ($parts as $part) {
        if (! isset($target[$part]) || ! is_array($target[$part])) {
            $target[$part] = [];
        }
        $target =& $target[$part];
    }
    $target = $value;

    return update_option(Emonks\SaasCore\Settings::OPTION_KEY, $settings);
}

function emonks_get_labels(): array
{
    $defaults = ['singular' => 'Item', 'plural' => 'Items', 'new' => 'New item', 'edit' => 'Edit item'];
    return apply_filters('emonks_saas_labels', $defaults);
}

function emonks_get_routes(): array
{
    return Emonks\SaasCore\Routes::defaultRoutes();
}

function emonks_get_account_url(string $suffix = ''): string
{
    $routes = emonks_get_routes();
    $path = '/' . $routes['account'];

    if ($suffix !== '') {
        if ($suffix === 'workspaces') {
            $path .= '/' . $routes['workspaces'];
        } elseif ($suffix === 'billing') {
            $path .= '/' . $routes['billing'];
        } elseif ($suffix === 'settings') {
            $path .= '/' . $routes['settings'];
        } elseif ($suffix === 'onboarding') {
            $path .= '/' . $routes['onboarding'];
        } else {
            $path .= '/' . ltrim($suffix, '/');
        }
    }

    return home_url($path . '/');
}

function emonks_get_login_url(): string
{
    $routes = emonks_get_routes();
    return home_url('/' . $routes['login'] . '/');
}

function emonks_default_context(): array
{
    $userId = get_current_user_id();
    $plan = $userId > 0 ? emonks_get_current_user_plan() : 'starter';
    $workspaceCount = $userId > 0 ? emonks_count_user_workspaces($userId) : 0;
    $billingCycle = $userId > 0 ? emonks_get_current_user_billing_cycle($userId) : 'monthly';
    return [
        'labels' => emonks_get_labels(),
        'routes' => emonks_get_routes(),
        'current_user' => wp_get_current_user(),
        'current_plan' => $plan,
        'plans' => Plans::getPlans(),
        'current_workspace_count' => $workspaceCount,
        'current_billing_cycle' => $billingCycle,
        'billing_test_mode' => emonks_is_billing_test_mode(),
        'feature_flags' => Features::globalFlags(),
        'onboarding_progress' => $userId > 0 ? emonks_get_onboarding_progress($userId) : ['steps' => [], 'percentage' => 0, 'completed' => false],
        'nonce_workspace' => wp_create_nonce('emonks_workspace_save'),
        'nonce_login' => wp_create_nonce('emonks_login_action'),
        'nonce_register' => wp_create_nonce('emonks_register_action'),
        'flash_messages' => emonks_flash_pull(),
    ];
}

function emonks_admin_post_url(string $action): string
{
    return add_query_arg(['action' => sanitize_key($action)], admin_url('admin-post.php'));
}

function emonks_render_template(string $template, array $context = []): void
{
    $loader = Plugin::instance()->get('template_loader');
    if ($loader instanceof Emonks\SaasCore\TemplateLoader) {
        $loader->render($template, apply_filters('emonks_template_context', $context, $template));
    }
}

function emonks_get_user_workspaces(int $userId): array
{
    $args = [
        'post_type' => 'emonks_workspace',
        'post_status' => 'publish',
        'posts_per_page' => -1,
    ];

    if (! user_can($userId, 'manage_options')) {
        $args['author'] = $userId;
    }

    return get_posts($args);
}

function emonks_count_user_workspaces(int $userId): int
{
    return count(emonks_get_user_workspaces($userId));
}

function emonks_get_workspace_meta(int $workspaceId, string $metaKey, $default = null)
{
    $value = get_post_meta($workspaceId, $metaKey, true);
    return $value === '' ? $default : $value;
}

function emonks_get_workspace_status(int $workspaceId): string
{
    $status = sanitize_key((string) get_post_meta($workspaceId, 'workspace_status', true));
    $valid = WorkspaceStatuses::all();
    return array_key_exists($status, $valid) ? $status : 'draft';
}

function emonks_workspace_has_status(int $workspaceId, string $status): bool
{
    return emonks_get_workspace_status($workspaceId) === sanitize_key($status);
}

function emonks_user_can_access_workspace(int $userId, int $workspaceId): bool
{
    $permissions = Plugin::instance()->get('permissions');
    if ($permissions instanceof Permissions) {
        return $permissions->userCanAccessWorkspace($userId, $workspaceId);
    }

    return false;
}

function emonks_get_current_user_plan(): string
{
    $plan = sanitize_key((string) get_user_meta(get_current_user_id(), 'emonks_subscription_plan', true));
    return $plan !== '' ? $plan : 'starter';
}

function emonks_user_has_active_subscription(?int $userId = null): bool
{
    $userId = $userId ?: get_current_user_id();
    $status = sanitize_key((string) get_user_meta($userId, 'emonks_subscription_status', true));
    return in_array($status, ['active', 'trialing'], true);
}

function emonks_get_plan_limit(string $plan, string $limitKey, int $default = 0): int
{
    $plans = Plans::getPlans();
    if (! isset($plans[$plan][$limitKey])) {
        return $default;
    }

    return (int) $plans[$plan][$limitKey];
}

function emonks_get_plans(): array
{
    return Plans::getPlans();
}

function emonks_get_plan(string $plan): array
{
    $plans = Plans::getPlans();
    return $plans[sanitize_key($plan)] ?? [];
}

function emonks_get_current_user_workspace_count(?int $userId = null): int
{
    $userId = $userId ?: get_current_user_id();
    return emonks_count_user_workspaces($userId);
}

function emonks_is_billing_test_mode(): bool
{
    return (bool) emonks_get_setting('billing.test_mode', false);
}

function emonks_get_current_user_billing_cycle(?int $userId = null): string
{
    $userId = $userId ?: get_current_user_id();
    $cycle = sanitize_key((string) get_user_meta($userId, 'emonks_subscription_cycle', true));
    return in_array($cycle, ['monthly', 'yearly'], true) ? $cycle : 'monthly';
}

function emonks_user_can_create_workspace(?int $userId = null): bool
{
    $userId = $userId ?: get_current_user_id();
    if (user_can($userId, 'manage_options')) {
        return true;
    }

    if (! emonks_user_has_active_subscription($userId)) {
        return false;
    }

    $plan = sanitize_key((string) get_user_meta($userId, 'emonks_subscription_plan', true));
    if ($plan === '') {
        $plan = 'starter';
    }

    $limit = emonks_get_plan_limit($plan, 'max_workspaces', 0);
    return emonks_count_user_workspaces($userId) < $limit;
}

function emonks_feature_enabled(string $feature): bool
{
    $flags = Features::globalFlags();
    $overrides = emonks_get_setting('feature_flags', []);
    if (is_array($overrides)) {
        foreach ($overrides as $key => $value) {
            $flags[sanitize_key((string) $key)] = (bool) $value;
        }
    }

    return (bool) ($flags[sanitize_key($feature)] ?? false);
}

function emonks_plan_has_feature(string $plan, string $feature): bool
{
    $plans = Plans::getPlans();
    $features = $plans[$plan]['enabled_features'] ?? [];
    return in_array(sanitize_key($feature), $features, true);
}

function emonks_service_supports(string $serviceType, string $capability): bool
{
    $service = Services::get($serviceType);
    $caps = $service['supported_features'] ?? [];
    return in_array(sanitize_key($capability), $caps, true);
}

function emonks_feature_available_for_service_and_plan(string $serviceType, string $plan, string $feature): bool
{
    $featureKey = sanitize_key($feature);
    return emonks_service_supports($serviceType, $featureKey)
        && emonks_plan_has_feature($plan, $featureKey)
        && emonks_feature_enabled($featureKey);
}

function emonks_get_workspace_by_slug(string $slug): int
{
    $query = new WP_Query([
        'post_type' => 'emonks_workspace',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_query' => [
            [
                'key' => 'public_slug',
                'value' => sanitize_title($slug),
                'compare' => '=',
            ],
        ],
    ]);

    return ! empty($query->posts) ? (int) $query->posts[0] : 0;
}

function emonks_is_workspace_slug_available(string $slug, int $excludeWorkspaceId = 0): bool
{
    $slug = sanitize_title($slug);
    if ($slug === '') {
        return false;
    }

    $query = new WP_Query([
        'post_type' => 'emonks_workspace',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'post__not_in' => $excludeWorkspaceId > 0 ? [$excludeWorkspaceId] : [],
        'meta_query' => [
            [
                'key' => 'public_slug',
                'value' => $slug,
                'compare' => '=',
            ],
        ],
    ]);

    return empty($query->posts);
}

/** @return array<int,string> */
function emonks_suggest_workspace_slugs(string $seed, int $excludeWorkspaceId = 0, int $limit = 3): array
{
    $base = sanitize_title($seed);
    if ($base === '') {
        $base = 'workspace';
    }

    $suggestions = [];
    $counter = 0;
    while (count($suggestions) < $limit && $counter < 20) {
        $candidate = $counter === 0 ? $base : $base . '-' . ($counter + 1);
        if (emonks_is_workspace_slug_available($candidate, $excludeWorkspaceId)) {
            $suggestions[] = $candidate;
        }
        $counter++;
    }

    return $suggestions;
}

function emonks_user_can_publish_workspace(int $userId): bool
{
    if (user_can($userId, 'manage_options')) {
        return true;
    }

    return emonks_user_has_active_subscription($userId) && emonks_user_completed_onboarding($userId);
}

/** @return array<int,string> */
function emonks_get_service_onboarding_steps(string $serviceType): array
{
    $service = Services::get($serviceType);
    $steps = $service['onboarding_steps'] ?? [];
    if (! is_array($steps)) {
        $steps = [];
    }

    $normalized = array_values(array_filter(array_map(static fn($s) => sanitize_key((string) $s), $steps)));
    return apply_filters('emonks_service_onboarding_steps', $normalized, $serviceType);
}

/** @return array<string,mixed> */
function emonks_get_service_policy(string $serviceType): array
{
    $service = Services::get($serviceType);
    $policy = [
        'class' => sanitize_text_field((string) ($service['policy'] ?? '')),
        'can_publish_requires_billing' => true,
        'can_publish_requires_onboarding' => true,
    ];

    return apply_filters('emonks_service_policy', $policy, $serviceType, $service);
}

function emonks_get_workspace_status_label(string $status): string
{
    $all = WorkspaceStatuses::all();
    $status = sanitize_key($status);
    return (string) ($all[$status] ?? ucfirst($status));
}

function emonks_get_workspace_status_badge_class(string $status): string
{
    return match (sanitize_key($status)) {
        'published' => 'emonks-badge-published',
        'active' => 'emonks-badge-active',
        'suspended' => 'emonks-badge-suspended',
        'archived' => 'emonks-badge-archived',
        default => 'emonks-badge-draft',
    };
}

function emonks_user_completed_onboarding(?int $userId = null): bool
{
    $userId = $userId ?: get_current_user_id();
    $onboarding = Plugin::instance()->get('onboarding');
    if (! $onboarding instanceof Emonks\SaasCore\Onboarding) {
        return false;
    }

    $progress = $onboarding->getProgress($userId);
    return (bool) ($progress['completed'] ?? false);
}

function emonks_user_has_published_workspace(int $userId): bool
{
    $query = new WP_Query([
        'post_type' => 'emonks_workspace',
        'post_status' => 'publish',
        'author' => $userId,
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_query' => [
            [
                'key' => 'workspace_status',
                'value' => 'published',
                'compare' => '=',
            ],
        ],
    ]);

    return ! empty($query->posts);
}

function emonks_get_onboarding_progress(?int $userId = null): array
{
    $userId = $userId ?: get_current_user_id();
    $onboarding = Plugin::instance()->get('onboarding');
    if (! $onboarding instanceof Emonks\SaasCore\Onboarding) {
        return ['steps' => [], 'percentage' => 0, 'completed' => false];
    }

    return $onboarding->getProgress($userId);
}

function emonks_register_service_type(string $key, array $definition): void
{
    Services::registerServiceType($key, $definition);
}

function emonks_get_workspace_status_counts(int $userId): array
{
    $counts = [];
    foreach (array_keys(WorkspaceStatuses::all()) as $status) {
        $counts[$status] = 0;
    }

    foreach (emonks_get_user_workspaces($userId) as $workspace) {
        if (! $workspace instanceof WP_Post) {
            continue;
        }

        $status = emonks_get_workspace_status($workspace->ID);
        if (! isset($counts[$status])) {
            $counts[$status] = 0;
        }
        $counts[$status]++;
    }

    return $counts;
}

function emonks_flash_add(string $key, string $message): void
{
    if (! is_user_logged_in()) {
        return;
    }

    $userId = get_current_user_id();
    $messages = get_user_meta($userId, 'emonks_flash_messages', true);
    if (! is_array($messages)) {
        $messages = [];
    }

    $messages[sanitize_key($key)] = sanitize_text_field($message);
    update_user_meta($userId, 'emonks_flash_messages', $messages);
}

function emonks_flash_pull(): array
{
    if (! is_user_logged_in()) {
        return [];
    }

    $userId = get_current_user_id();
    $messages = get_user_meta($userId, 'emonks_flash_messages', true);
    if (! is_array($messages)) {
        $messages = [];
    }

    delete_user_meta($userId, 'emonks_flash_messages');
    return $messages;
}
