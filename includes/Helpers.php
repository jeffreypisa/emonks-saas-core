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

function emonks_get_client_portal_url(string $suffix = ''): string
{
    $base = emonks_get_account_url('services/client-portal');
    if ($suffix === '') {
        return trailingslashit($base);
    }

    return trailingslashit($base) . ltrim($suffix, '/');
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
    $primaryAccountId = $userId > 0 ? emonks_get_primary_account_id($userId) : 0;
    return [
        'labels' => emonks_get_labels(),
        'routes' => emonks_get_routes(),
        'current_user' => wp_get_current_user(),
        'current_plan' => $plan,
        'plans' => Plans::getPlans(),
        'current_workspace_count' => $workspaceCount,
        'current_billing_cycle' => $billingCycle,
        'current_account_id' => $primaryAccountId,
        'current_account_ids' => $userId > 0 ? emonks_get_user_account_ids($userId) : [],
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

function emonks_template_exists(string $template): bool
{
    $loader = Plugin::instance()->get('template_loader');
    if (! $loader instanceof Emonks\SaasCore\TemplateLoader) {
        return false;
    }

    return $loader->locate($template) !== null;
}

function emonks_get_user_workspaces(int $userId): array
{
    $baseArgs = [
        'post_type' => 'emonks_workspace',
        'post_status' => 'publish',
        'posts_per_page' => -1,
    ];

    if (user_can($userId, 'manage_options')) {
        return get_posts($baseArgs);
    }

    $accountIds = emonks_get_user_account_ids($userId);
    if (empty($accountIds)) {
        return [];
    }

    return get_posts(array_merge($baseArgs, [
        'meta_query' => [
            [
                'key' => 'account_id',
                'value' => $accountIds,
                'compare' => 'IN',
                'type' => 'NUMERIC',
            ],
        ],
    ]));
}

function emonks_get_primary_account_id(?int $userId = null): int
{
    $userId = $userId ?: get_current_user_id();
    if ($userId <= 0) {
        return 0;
    }

    $accountId = absint((string) get_user_meta($userId, 'emonks_primary_account_id', true));
    if ($accountId > 0 && get_post_type($accountId) === 'emonks_account') {
        return $accountId;
    }

    $accounts = Plugin::instance()->get('accounts');
    if ($accounts instanceof Emonks\SaasCore\Accounts) {
        return $accounts->ensurePersonalAccount($userId);
    }

    return 0;
}

/** @return array<int,int> */
function emonks_get_user_account_ids(?int $userId = null): array
{
    $userId = $userId ?: get_current_user_id();
    if ($userId <= 0) {
        return [];
    }

    if (user_can($userId, 'manage_options')) {
        $accountPosts = get_posts([
            'post_type' => 'emonks_account',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ]);
        if (! is_array($accountPosts)) {
            return [];
        }

        return array_values(array_map(static fn($id) => absint((string) $id), $accountPosts));
    }

    $memberships = Plugin::instance()->get('memberships');
    if ($memberships instanceof Emonks\SaasCore\Memberships) {
        $ids = $memberships->getUserAccountIds($userId);
        if (! empty($ids)) {
            return $ids;
        }
    }

    $primary = emonks_get_primary_account_id($userId);
    return $primary > 0 ? [$primary] : [];
}

function emonks_user_can_access_account(int $userId, int $accountId): bool
{
    if (user_can($userId, 'manage_options')) {
        return true;
    }

    if ($userId <= 0 || $accountId <= 0) {
        return false;
    }

    $primaryAccountId = absint((string) get_user_meta($userId, 'emonks_primary_account_id', true));
    if ($primaryAccountId > 0 && $primaryAccountId === $accountId) {
        $memberships = Plugin::instance()->get('memberships');
        if ($memberships instanceof Emonks\SaasCore\Memberships) {
            $memberships->addMembership($accountId, $userId, 'account_owner');
        }
        return true;
    }

    $ownerUserId = absint((string) get_post_meta($accountId, 'owner_user_id', true));
    if ($ownerUserId > 0 && $ownerUserId === $userId) {
        $memberships = Plugin::instance()->get('memberships');
        if ($memberships instanceof Emonks\SaasCore\Memberships) {
            $memberships->addMembership($accountId, $userId, 'account_owner');
        }
        update_user_meta($userId, 'emonks_primary_account_id', $accountId);
        return true;
    }

    $memberships = Plugin::instance()->get('memberships');
    if ($memberships instanceof Emonks\SaasCore\Memberships) {
        return $memberships->userHasAccount($userId, $accountId);
    }

    return false;
}

function emonks_get_account_role(int $userId, int $accountId): string
{
    if (user_can($userId, 'manage_options')) {
        return 'account_owner';
    }

    global $wpdb;
    $table = \Emonks\SaasCore\Memberships::tableName();
    $role = $wpdb->get_var($wpdb->prepare(
        "SELECT role FROM {$table} WHERE account_id = %d AND user_id = %d LIMIT 1",
        $accountId,
        $userId
    ));

    $role = sanitize_key((string) $role);
    return $role !== '' ? $role : 'account_member';
}

/** @return array<int,\WP_Post> */
function emonks_get_account_service_items(int $accountId, string $moduleKey = ''): array
{
    $service = Plugin::instance()->get('service_items');
    if (! $service instanceof Emonks\SaasCore\ServiceItems) {
        return [];
    }

    return $service->listByAccount($accountId, $moduleKey);
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

/** @return array<string,array<string,string>> */
function emonks_status_vocabulary(): array
{
    return [
        'draft' => ['label' => 'Draft', 'badge' => 'text-bg-secondary'],
        'open' => ['label' => 'Open', 'badge' => 'text-bg-secondary'],
        'in_progress' => ['label' => 'In progress', 'badge' => 'text-bg-primary'],
        'blocked' => ['label' => 'Blocked', 'badge' => 'text-bg-warning'],
        'done' => ['label' => 'Done', 'badge' => 'text-bg-success'],
        'active' => ['label' => 'Active', 'badge' => 'text-bg-primary'],
        'published' => ['label' => 'Published', 'badge' => 'text-bg-success'],
        'suspended' => ['label' => 'Suspended', 'badge' => 'text-bg-warning'],
        'archived' => ['label' => 'Archived', 'badge' => 'text-bg-dark'],
    ];
}

function emonks_normalize_status(string $status, string $fallback = 'open'): string
{
    $status = sanitize_key($status);
    $fallback = sanitize_key($fallback);
    $vocabulary = emonks_status_vocabulary();

    if (isset($vocabulary[$status])) {
        return $status;
    }

    return isset($vocabulary[$fallback]) ? $fallback : 'open';
}

function emonks_get_status_label(string $status): string
{
    $status = emonks_normalize_status($status, 'open');
    $vocabulary = emonks_status_vocabulary();
    return (string) ($vocabulary[$status]['label'] ?? ucfirst(str_replace('_', ' ', $status)));
}

function emonks_get_status_badge_class(string $status): string
{
    $status = emonks_normalize_status($status, 'open');
    $vocabulary = emonks_status_vocabulary();
    return (string) ($vocabulary[$status]['badge'] ?? 'text-bg-secondary');
}

function emonks_user_can_access_workspace(int $userId, int $workspaceId): bool
{
    return emonks_can_access_entity_account('workspace', $workspaceId, $userId);
}

function emonks_can_access_entity_account(string $entityType, int $entityId, ?int $userId = null): bool
{
    $entityType = sanitize_key($entityType);
    $entityId = absint((string) $entityId);
    $userId = $userId ?: get_current_user_id();

    if ($userId <= 0 || $entityId <= 0) {
        return false;
    }

    if (user_can($userId, 'manage_options')) {
        return true;
    }

    $accountId = 0;

    if ($entityType === 'account') {
        $accountId = $entityId;
    } elseif ($entityType === 'workspace') {
        if (get_post_type($entityId) !== 'emonks_workspace') {
            return false;
        }
        $accountId = absint((string) get_post_meta($entityId, 'account_id', true));
    } elseif ($entityType === 'service_item') {
        if (get_post_type($entityId) !== 'emonks_service_item') {
            return false;
        }
        $accountId = absint((string) get_post_meta($entityId, 'account_id', true));
    } else {
        return false;
    }

    if ($accountId <= 0) {
        return false;
    }

    return emonks_user_can_access_account($userId, $accountId);
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
        'published' => 'text-bg-success',
        'active' => 'text-bg-primary',
        'suspended' => 'text-bg-warning',
        'archived' => 'text-bg-dark',
        default => 'text-bg-secondary',
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

function emonks_user_ready_to_publish_workspace(?int $userId = null): bool
{
    $userId = $userId ?: get_current_user_id();
    $onboarding = Plugin::instance()->get('onboarding');
    if (! $onboarding instanceof Emonks\SaasCore\Onboarding) {
        return false;
    }

    $progress = $onboarding->getProgress($userId);
    $steps = is_array($progress['steps'] ?? null) ? $progress['steps'] : [];
    if (empty($steps)) {
        return false;
    }

    foreach ($steps as $step => $done) {
        if (sanitize_key((string) $step) === 'workspace_published') {
            continue;
        }

        if (! (bool) $done) {
            return false;
        }
    }

    return true;
}

function emonks_user_has_published_workspace(int $userId): bool
{
    foreach (emonks_get_user_workspaces($userId) as $workspace) {
        if (! $workspace instanceof WP_Post) {
            continue;
        }

        if (emonks_workspace_has_status($workspace->ID, 'published')) {
            return true;
        }
    }

    return false;
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

function emonks_module_enabled(string $key): bool
{
    $registry = Plugin::instance()->get('module_registry');
    if (! $registry instanceof Emonks\SaasCore\ModuleRegistry) {
        return false;
    }

    return $registry->isEnabled($key);
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

function emonks_alert_variant(string $flashKey): string
{
    $key = sanitize_key($flashKey);
    if ($key === '') {
        return 'alert-info';
    }

    if (str_ends_with($key, '_error')) {
        return 'alert-danger';
    }

    if (str_ends_with($key, '_warning')) {
        return 'alert-warning';
    }

    if (str_ends_with($key, '_success')) {
        return 'alert-success';
    }

    return 'alert-info';
}

/** @param array<string,mixed> $details */
function emonks_rest_error(string $code, string $message, array $details = [], int $status = 400): WP_REST_Response
{
    $payload = [
        'code' => sanitize_key($code),
        'message' => sanitize_text_field($message),
        'details' => $details,
    ];

    return new WP_REST_Response($payload, $status);
}
