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
    $primaryAccountId = $userId > 0 ? emonks_get_primary_account_id($userId) : 0;
    $allowedTemplateBackgrounds = ['bg-light', 'bg-dark', 'bg-gradient-dark', 'bg-gradient-light', 'bg-primary', 'bg-greylight', 'bg-transparent'];
    $templateBackgroundClass = sanitize_html_class((string) emonks_get_setting('templates.background_class', 'bg-light'));
    if (! in_array($templateBackgroundClass, $allowedTemplateBackgrounds, true)) {
        $templateBackgroundClass = 'bg-light';
    }

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
        'template_background_class' => $templateBackgroundClass,
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

function emonks_render_user_menu(): string
{
    $service = Plugin::instance()->get('user_menu');
    if (! $service instanceof Emonks\SaasCore\UserMenu) {
        return '';
    }

    return $service->renderShortcode();
}

/** @return array<string,mixed> */
function emonks_default_field_library(): array
{
    return [
        'schema_version' => 1,
        'fields' => [
            'user_login' => ['key' => 'user_login', 'label' => 'E-mail of gebruikersnaam', 'type' => 'text', 'source' => 'plugin', 'required' => true, 'placeholder' => '', 'help' => '', 'default' => '', 'rules' => []],
            'user_password' => ['key' => 'user_password', 'label' => 'Wachtwoord', 'type' => 'password', 'source' => 'plugin', 'required' => true, 'placeholder' => '', 'help' => '', 'default' => '', 'rules' => []],
            'email' => ['key' => 'email', 'label' => 'E-mailadres', 'type' => 'email', 'source' => 'plugin', 'required' => true, 'placeholder' => '', 'help' => '', 'default' => '', 'rules' => []],
            'password' => ['key' => 'password', 'label' => 'Wachtwoord', 'type' => 'password', 'source' => 'plugin', 'required' => true, 'placeholder' => '', 'help' => '', 'default' => '', 'rules' => [['type' => 'min_length', 'value' => 8]]],
            'title' => ['key' => 'title', 'label' => 'Naam', 'type' => 'text', 'source' => 'plugin', 'required' => true, 'placeholder' => 'Bijv. Main workspace', 'help' => '', 'default' => '', 'rules' => []],
            'service_type' => ['key' => 'service_type', 'label' => 'Service type', 'type' => 'select', 'source' => 'plugin', 'required' => true, 'placeholder' => '', 'help' => '', 'default' => '', 'rules' => []],
            'public_slug' => ['key' => 'public_slug', 'label' => 'Public slug', 'type' => 'text', 'source' => 'plugin', 'required' => false, 'placeholder' => 'mijn-workspace', 'help' => 'We checken automatisch of de slug beschikbaar is.', 'default' => '', 'rules' => []],
            'workspace_status' => ['key' => 'workspace_status', 'label' => 'Status', 'type' => 'select', 'source' => 'plugin', 'required' => true, 'placeholder' => '', 'help' => 'Published vereist afgeronde onboarding en actieve subscription.', 'default' => 'draft', 'rules' => []],
        ],
    ];
}

/** @return array<string,mixed> */
function emonks_default_form_templates(): array
{
    return [
        'schema_version' => 1,
        'forms' => [
            'auth_login' => [
                'key' => 'auth_login',
                'label' => 'Login',
                'scope' => 'core',
                'action' => '',
                'nonce_action' => 'emonks_login_action',
                'submit_label' => 'Inloggen',
                'field_refs' => [
                    ['field_key' => 'user_login', 'order' => 10, 'required' => true],
                    ['field_key' => 'user_password', 'order' => 20, 'required' => true],
                ],
            ],
            'auth_register' => [
                'key' => 'auth_register',
                'label' => 'Registreren',
                'scope' => 'core',
                'action' => '',
                'nonce_action' => 'emonks_register_action',
                'submit_label' => 'Registreren',
                'field_refs' => [
                    ['field_key' => 'email', 'order' => 10, 'required' => true],
                    ['field_key' => 'password', 'order' => 20, 'required' => true],
                ],
            ],
            'workspace_create' => [
                'key' => 'workspace_create',
                'label' => 'Workspace aanmaken',
                'scope' => 'core',
                'action' => '',
                'nonce_action' => 'emonks_workspace_save',
                'submit_label' => 'Workspace opslaan',
                'field_refs' => [
                    ['field_key' => 'title', 'order' => 10, 'required' => true],
                    ['field_key' => 'public_slug', 'order' => 20, 'required' => false],
                    ['field_key' => 'workspace_status', 'order' => 30, 'required' => true],
                ],
            ],
            'workspace_edit' => [
                'key' => 'workspace_edit',
                'label' => 'Workspace bewerken',
                'scope' => 'core',
                'action' => '',
                'nonce_action' => 'emonks_workspace_save',
                'submit_label' => 'Opslaan',
                'field_refs' => [
                    ['field_key' => 'title', 'order' => 10, 'required' => true],
                    ['field_key' => 'public_slug', 'order' => 20, 'required' => false],
                    ['field_key' => 'workspace_status', 'order' => 30, 'required' => true],
                ],
            ],
        ],
    ];
}

/** @return array<string,mixed> */
function emonks_default_service_schemas(): array
{
    return ['schema_version' => 1, 'config_model' => 'dynamic_services_v1', 'services' => []];
}

/** @return array<string,mixed> */
function emonks_default_email_templates(): array
{
    $today = wp_date('Y-m-d');
    return [
        'schema_version' => 1,
        'templates' => [
            'account_registered' => [
                'key' => 'account_registered',
                'label' => 'Account geregistreerd',
                'enabled' => true,
                'trigger' => 'emonks_account_registered',
                'recipients' => ['targets' => ['current_user'], 'extra' => []],
                'subject' => 'Welkom bij {{ system.site_name }}',
                'body_html' => '<p>Hi {{ user.display_name }},</p><p>Welkom bij {{ system.site_name }}.</p>',
                'body_text' => "Hi {{ user.display_name }},\n\nWelkom bij {{ system.site_name }}.",
                'from_name' => '',
                'from_email' => '',
                'reply_to' => '',
                'conditions' => [],
                'updated_at' => $today,
            ],
            'workspace_created' => [
                'key' => 'workspace_created',
                'label' => 'Workspace aangemaakt',
                'enabled' => true,
                'trigger' => 'emonks_workspace_created',
                'recipients' => ['targets' => ['current_user'], 'extra' => []],
                'subject' => 'Workspace aangemaakt: {{ workspace.title }}',
                'body_html' => '<p>Je workspace <strong>{{ workspace.title }}</strong> is aangemaakt.</p>',
                'body_text' => "Je workspace {{ workspace.title }} is aangemaakt.",
                'from_name' => '',
                'from_email' => '',
                'reply_to' => '',
                'conditions' => [],
                'updated_at' => $today,
            ],
            'workspace_updated' => [
                'key' => 'workspace_updated',
                'label' => 'Workspace bijgewerkt',
                'enabled' => true,
                'trigger' => 'emonks_workspace_updated',
                'recipients' => ['targets' => ['current_user'], 'extra' => []],
                'subject' => 'Workspace bijgewerkt: {{ workspace.title }}',
                'body_html' => '<p>Je workspace <strong>{{ workspace.title }}</strong> is bijgewerkt.</p>',
                'body_text' => "Je workspace {{ workspace.title }} is bijgewerkt.",
                'from_name' => '',
                'from_email' => '',
                'reply_to' => '',
                'conditions' => [],
                'updated_at' => $today,
            ],
            'billing_plan_changed' => [
                'key' => 'billing_plan_changed',
                'label' => 'Plan wijziging',
                'enabled' => true,
                'trigger' => 'emonks_billing_plan_changed',
                'recipients' => ['targets' => ['current_user'], 'extra' => []],
                'subject' => 'Plan gewijzigd naar {{ billing.target_plan }}',
                'body_html' => '<p>Je plan is gewijzigd van {{ billing.current_plan }} naar {{ billing.target_plan }}.</p>',
                'body_text' => "Je plan is gewijzigd van {{ billing.current_plan }} naar {{ billing.target_plan }}.",
                'from_name' => '',
                'from_email' => '',
                'reply_to' => '',
                'conditions' => [],
                'updated_at' => $today,
            ],
            'auth_login_failed' => [
                'key' => 'auth_login_failed',
                'label' => 'Login mislukt',
                'enabled' => false,
                'trigger' => 'emonks_auth_login_failed',
                'recipients' => ['targets' => ['site_admin'], 'extra' => []],
                'subject' => 'Mislukte login poging',
                'body_html' => '<p>Login mislukt voor: {{ user.email }}</p>',
                'body_text' => 'Login mislukt voor: {{ user.email }}',
                'from_name' => '',
                'from_email' => '',
                'reply_to' => '',
                'conditions' => [],
                'updated_at' => $today,
            ],
        ],
    ];
}

/** @return array<string,mixed> */
function emonks_get_email_templates(): array
{
    $defaults = emonks_default_email_templates();
    $raw = emonks_get_setting('email_templates', []);
    if (! is_array($raw) || ! is_array($raw['templates'] ?? null) || empty($raw['templates'])) {
        return $defaults;
    }

    return ['schema_version' => (int) ($raw['schema_version'] ?? 1), 'templates' => $raw['templates']];
}

/** @return array<string,mixed> */
function emonks_get_email_template(string $templateKey): array
{
    $templateKey = sanitize_key($templateKey);
    $templates = emonks_get_email_templates()['templates'] ?? [];
    $template = is_array($templates[$templateKey] ?? null) ? $templates[$templateKey] : [];
    if (empty($template)) {
        return [];
    }

    $template['key'] = $templateKey;
    return $template;
}

/** @param array<string,mixed> $context @return array<string,mixed> */
function emonks_resolve_email_template(string $templateKey, array $context = []): array
{
    $template = emonks_get_email_template($templateKey);
    if (empty($template)) {
        return [];
    }

    $template['enabled'] = ! empty($template['enabled']);
    $template['label'] = sanitize_text_field((string) ($template['label'] ?? $templateKey));
    $template['trigger'] = sanitize_key((string) ($template['trigger'] ?? ''));
    $template['subject'] = (string) ($template['subject'] ?? '');
    $template['body_html'] = (string) ($template['body_html'] ?? '');
    $template['body_text'] = (string) ($template['body_text'] ?? '');
    $template['from_name'] = sanitize_text_field((string) ($template['from_name'] ?? ''));
    $template['from_email'] = sanitize_email((string) ($template['from_email'] ?? ''));
    $template['reply_to'] = sanitize_email((string) ($template['reply_to'] ?? ''));
    $template['conditions'] = is_array($template['conditions'] ?? null) ? $template['conditions'] : [];
    $template['recipients'] = is_array($template['recipients'] ?? null) ? $template['recipients'] : ['targets' => ['current_user'], 'extra' => []];
    $template['recipients']['targets'] = is_array($template['recipients']['targets'] ?? null) ? array_values(array_map('sanitize_key', $template['recipients']['targets'])) : ['current_user'];
    $template['recipients']['extra'] = is_array($template['recipients']['extra'] ?? null) ? array_values(array_map('sanitize_email', $template['recipients']['extra'])) : [];

    return apply_filters('emonks_resolve_email_template', $template, $templateKey, $context);
}

/** @param array<string,mixed> $eventContext @return array<string,mixed> */
function emonks_build_email_token_context(array $eventContext = []): array
{
    $userId = absint((string) ($eventContext['user_id'] ?? 0));
    $workspaceId = absint((string) ($eventContext['workspace_id'] ?? 0));
    $currentPlan = sanitize_key((string) ($eventContext['current_plan'] ?? ''));
    $targetPlan = sanitize_key((string) ($eventContext['target_plan'] ?? ''));
    $cycle = sanitize_key((string) ($eventContext['cycle'] ?? ($userId > 0 ? emonks_get_current_user_billing_cycle($userId) : 'monthly')));
    $user = $userId > 0 ? get_userdata($userId) : null;
    $workspace = $workspaceId > 0 ? get_post($workspaceId) : null;
    $accountId = absint((string) ($eventContext['account_id'] ?? ($workspaceId > 0 ? emonks_get_workspace_meta($workspaceId, 'account_id', 0) : 0)));
    $account = $accountId > 0 ? get_post($accountId) : null;

    return [
        'user' => [
            'id' => $userId,
            'email' => $user instanceof \WP_User ? (string) $user->user_email : '',
            'display_name' => $user instanceof \WP_User ? (string) $user->display_name : '',
            'first_name' => $user instanceof \WP_User ? (string) get_user_meta($userId, 'first_name', true) : '',
            'last_name' => $user instanceof \WP_User ? (string) get_user_meta($userId, 'last_name', true) : '',
        ],
        'workspace' => [
            'id' => $workspaceId,
            'title' => $workspace instanceof \WP_Post ? (string) $workspace->post_title : '',
            'status' => $workspaceId > 0 ? emonks_get_workspace_status($workspaceId) : '',
            'public_slug' => $workspaceId > 0 ? (string) emonks_get_workspace_meta($workspaceId, 'public_slug', '') : '',
            'url' => $workspaceId > 0 ? home_url('/' . sanitize_title((string) emonks_get_workspace_meta($workspaceId, 'public_slug', '')) . '/') : '',
        ],
        'account' => [
            'id' => $accountId,
            'name' => $account instanceof \WP_Post ? (string) $account->post_title : '',
        ],
        'billing' => [
            'current_plan' => $currentPlan !== '' ? $currentPlan : ($userId > 0 ? emonks_get_current_user_plan($userId) : ''),
            'target_plan' => $targetPlan,
            'cycle' => $cycle,
            'is_upgrade' => ! empty($eventContext['is_upgrade']) ? '1' : '0',
        ],
        'system' => [
            'site_name' => (string) get_bloginfo('name'),
            'site_url' => (string) home_url('/'),
            'today' => (string) wp_date('Y-m-d'),
        ],
    ];
}

/** @param array<string,mixed> $template @param array<string,mixed> $context @return array<string,mixed> */
function emonks_render_email_template_strings(array $template, array $context): array
{
    foreach (['subject', 'body_html', 'body_text', 'from_name', 'from_email', 'reply_to'] as $fieldKey) {
        $template[$fieldKey] = emonks_replace_email_tokens((string) ($template[$fieldKey] ?? ''), $context);
    }
    return $template;
}

/** @param array<string,mixed> $context */
function emonks_replace_email_tokens(string $content, array $context): string
{
    return (string) preg_replace_callback('/\{\{\s*([a-z0-9_\\.]+)\s*\}\}/i', static function ($matches) use ($context): string {
        $path = explode('.', sanitize_text_field((string) ($matches[1] ?? '')));
        $value = $context;
        foreach ($path as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return '';
            }
            $value = $value[$segment];
        }
        if (is_scalar($value)) {
            return (string) $value;
        }
        return '';
    }, $content);
}

/** @return array<string,string> */
function emonks_email_token_catalog(): array
{
    return [
        'user.id' => 'Gebruiker ID',
        'user.email' => 'Gebruiker e-mail',
        'user.display_name' => 'Gebruiker display name',
        'user.first_name' => 'Gebruiker voornaam',
        'user.last_name' => 'Gebruiker achternaam',
        'workspace.id' => 'Workspace ID',
        'workspace.title' => 'Workspace titel',
        'workspace.status' => 'Workspace status',
        'workspace.public_slug' => 'Workspace public slug',
        'workspace.url' => 'Workspace URL',
        'account.id' => 'Account ID',
        'account.name' => 'Account naam',
        'billing.current_plan' => 'Huidig plan',
        'billing.target_plan' => 'Doelplan',
        'billing.cycle' => 'Billing cycle',
        'billing.is_upgrade' => 'Is upgrade (1/0)',
        'system.site_name' => 'Site naam',
        'system.site_url' => 'Site URL',
        'system.today' => 'Datum (Y-m-d)',
    ];
}

/** @return array<string,mixed> */
function emonks_get_field_library(): array
{
    $defaults = emonks_default_field_library();
    $raw = emonks_get_setting('field_library', []);
    if (! is_array($raw) || ! is_array($raw['fields'] ?? null) || empty($raw['fields'])) {
        return $defaults;
    }

    return ['schema_version' => (int) ($raw['schema_version'] ?? 1), 'fields' => $raw['fields']];
}

/** @return array<string,mixed> */
function emonks_get_field_definition(string $fieldKey): array
{
    $fieldKey = sanitize_key($fieldKey);
    $fields = emonks_get_field_library()['fields'] ?? [];
    $field = is_array($fields[$fieldKey] ?? null) ? $fields[$fieldKey] : [];
    if (empty($field)) {
        return [];
    }
    $field['key'] = $fieldKey;
    $field['type'] = sanitize_key((string) ($field['type'] ?? 'text'));
    $field['source'] = sanitize_key((string) ($field['source'] ?? 'plugin'));
    if (! in_array($field['source'], ['plugin', 'acf', 'computed'], true)) {
        $field['source'] = 'plugin';
    }
    return $field;
}

/** @return array<string,mixed> */
function emonks_get_form_templates(): array
{
    $defaults = emonks_default_form_templates();
    $raw = emonks_get_setting('form_templates', []);
    if (! is_array($raw) || ! is_array($raw['forms'] ?? null) || empty($raw['forms'])) {
        return $defaults;
    }

    $forms = $raw['forms'];
    foreach (['workspace_create', 'workspace_edit'] as $workspaceFormKey) {
        if (! is_array($forms[$workspaceFormKey] ?? null)) {
            continue;
        }
        $fieldRefs = is_array($forms[$workspaceFormKey]['field_refs'] ?? null) ? $forms[$workspaceFormKey]['field_refs'] : [];
        $fieldRefs = array_values(array_filter($fieldRefs, static function ($ref): bool {
            if (! is_array($ref)) {
                return true;
            }
            return sanitize_key((string) ($ref['field_key'] ?? '')) !== 'service_type';
        }));
        $forms[$workspaceFormKey]['field_refs'] = $fieldRefs;
    }

    return ['schema_version' => (int) ($raw['schema_version'] ?? 1), 'forms' => $forms];
}

/** @return array<string,mixed> */
function emonks_get_form_template(string $formKey): array
{
    $formKey = sanitize_key($formKey);
    $forms = emonks_get_form_templates()['forms'] ?? [];
    $form = is_array($forms[$formKey] ?? null) ? $forms[$formKey] : [];
    if (empty($form)) {
        return [];
    }
    $form['key'] = $formKey;
    $refs = is_array($form['field_refs'] ?? null) ? $form['field_refs'] : [];
    usort($refs, static fn($a, $b) => (int) ($a['order'] ?? 0) <=> (int) ($b['order'] ?? 0));
    $form['field_refs'] = $refs;
    return $form;
}

/** @return array<string,mixed> */
function emonks_resolve_form_template(string $formKey, array $context = []): array
{
    $form = emonks_get_form_template($formKey);
    if (empty($form)) {
        return [];
    }

    $fields = [];
    foreach ((array) ($form['field_refs'] ?? []) as $ref) {
        if (! is_array($ref)) {
            continue;
        }
        $fieldKey = sanitize_key((string) ($ref['field_key'] ?? ''));
        $field = emonks_get_field_definition($fieldKey);
        if (empty($field)) {
            continue;
        }
        foreach (['label', 'placeholder', 'help', 'default', 'required'] as $overrideKey) {
            if (array_key_exists($overrideKey, $ref)) {
                $field[$overrideKey] = $ref[$overrideKey];
            }
        }
        $field['order'] = (int) ($ref['order'] ?? ($field['order'] ?? 10));
        if ($fieldKey === 'service_type') {
            $options = [];
            foreach (Services::all() as $key => $service) {
                $options[$key] = (string) ($service['labels']['singular'] ?? $key);
            }
            $field['options'] = $options;
        }
        if ($fieldKey === 'workspace_status') {
            $field['options'] = WorkspaceStatuses::all();
        }
        $fields[] = $field;
    }
    usort($fields, static fn($a, $b) => (int) ($a['order'] ?? 0) <=> (int) ($b['order'] ?? 0));
    $form['fields'] = $fields;
    return $form;
}

/** @return array<int,array<string,mixed>> */
function emonks_resolve_forms_for_context(string $serviceKey, string $context, int $workspaceId = 0): array
{
    $service = emonks_get_service_schema($serviceKey);
    $usages = is_array($service['form_usages'] ?? null) ? $service['form_usages'] : [];
    $forms = [];
    foreach ($usages as $usage) {
        if (! is_array($usage) || empty($usage['enabled'])) {
            continue;
        }
        if (sanitize_key((string) ($usage['context'] ?? '')) !== sanitize_key($context)) {
            continue;
        }
        $form = emonks_resolve_form_template((string) ($usage['form_key'] ?? ''), ['service_key' => $serviceKey, 'workspace_id' => $workspaceId]);
        if (! empty($form)) {
            $forms[] = $form;
        }
    }
    return $forms;
}

/** @return array<string,mixed> */
function emonks_get_form_schema(string $formKey): array
{
    return emonks_resolve_form_template($formKey);
}

/** @return array<string,mixed> */
function emonks_get_service_schemas(): array
{
    $raw = emonks_get_setting('service_schemas', []);
    if (! is_array($raw) || ($raw['config_model'] ?? '') !== 'dynamic_services_v1' || ! is_array($raw['services'] ?? null)) {
        return emonks_default_service_schemas();
    }

    return ['schema_version' => (int) ($raw['schema_version'] ?? 1), 'config_model' => 'dynamic_services_v1', 'services' => $raw['services']];
}

/** @return array<string,mixed> */
function emonks_get_service_schema(string $serviceKey): array
{
    $all = emonks_get_service_schemas();
    $serviceKey = sanitize_key($serviceKey);
    $schema = $all['services'][$serviceKey] ?? [];
    if (! is_array($schema)) {
        return [];
    }

    $schema['key'] = $serviceKey;
    $schema['status'] = sanitize_key((string) ($schema['status'] ?? 'draft'));
    if (! in_array($schema['status'], ['active', 'draft', 'archived'], true)) {
        $schema['status'] = 'draft';
    }
    $schema['field_source_default'] = sanitize_key((string) ($schema['field_source_default'] ?? 'plugin'));
    if (! in_array($schema['field_source_default'], ['plugin', 'acf', 'hybrid'], true)) {
        $schema['field_source_default'] = 'plugin';
    }
    $schema['acf_group_key_default'] = sanitize_text_field((string) ($schema['acf_group_key_default'] ?? ''));
    $schema['features'] = is_array($schema['features'] ?? null) ? array_values(array_filter(array_map(static fn($v) => sanitize_key((string) $v), $schema['features']))) : [];
    $schema['form_usages'] = is_array($schema['form_usages'] ?? null) ? array_values(array_filter($schema['form_usages'], 'is_array')) : [];
    if (! isset($schema['render_hints']) || ! is_array($schema['render_hints'])) {
        $schema['render_hints'] = [];
    }
    $schema['render_hints']['template'] = sanitize_text_field((string) ($schema['render_hints']['template'] ?? ''));
    $schema['render_hints']['public_route'] = sanitize_key((string) ($schema['render_hints']['public_route'] ?? 'g'));
    $schema['render_hints']['dashboard_label'] = sanitize_text_field((string) ($schema['render_hints']['dashboard_label'] ?? ''));
    return $schema;
}

/** @return array<string,mixed> */
function emonks_get_workspace_service_data(int $workspaceId): array
{
    $raw = emonks_get_workspace_meta($workspaceId, 'service_data_json', '{}');
    $decoded = json_decode((string) $raw, true);
    return is_array($decoded) ? $decoded : [];
}

function emonks_acf_available(): bool
{
    return function_exists('acf_get_field_groups') && function_exists('get_field_objects');
}

/** @return array<int,array<string,mixed>> */
function emonks_get_acf_field_groups(): array
{
    if (! emonks_acf_available()) {
        return [];
    }

    $groups = acf_get_field_groups();
    return is_array($groups) ? $groups : [];
}

/** @return array<string,string> */
function emonks_get_acf_field_group_options(): array
{
    $options = [];
    foreach (emonks_get_acf_field_groups() as $group) {
        if (! is_array($group)) {
            continue;
        }
        $key = sanitize_text_field((string) ($group['key'] ?? ''));
        if ($key === '') {
            continue;
        }
        $title = sanitize_text_field((string) ($group['title'] ?? $key));
        $options[$key] = $title . ' (' . $key . ')';
    }
    return $options;
}

/** @return array<string,string> */
function emonks_get_acf_field_options(string $groupKey): array
{
    if (! emonks_acf_available() || $groupKey === '') {
        return [];
    }
    $fields = acf_get_fields($groupKey);
    if (! is_array($fields)) {
        return [];
    }
    $options = [];
    foreach ($fields as $field) {
        if (! is_array($field)) {
            continue;
        }
        $key = sanitize_text_field((string) ($field['key'] ?? ''));
        $name = sanitize_key((string) ($field['name'] ?? ''));
        if ($key === '' || $name === '') {
            continue;
        }
        $label = sanitize_text_field((string) ($field['label'] ?? $name));
        $options[$key] = $label . ' (' . $name . ')';
    }
    return $options;
}

function emonks_get_service_acf_group(string $serviceKey): array
{
    $service = emonks_get_service_schema($serviceKey);
    $groupKey = sanitize_text_field((string) ($service['acf_group_key_default'] ?? ''));
    if ($groupKey === '' || ! emonks_acf_available()) {
        return [];
    }

    $group = acf_get_field_group($groupKey);
    return is_array($group) ? $group : [];
}

/** @return array<string,mixed> */
function emonks_get_workspace_acf_data(int $workspaceId, string $serviceKey = ''): array
{
    if (! emonks_acf_available() || $workspaceId <= 0) {
        return [];
    }

    $service = $serviceKey !== '' ? emonks_get_service_schema($serviceKey) : [];
    $groupKey = sanitize_text_field((string) ($service['acf_group_key_default'] ?? ''));
    if ($groupKey === '') {
        return [];
    }

    $fields = acf_get_fields($groupKey);
    if (! is_array($fields) || empty($fields)) {
        return [];
    }

    $data = [];
    foreach ($fields as $field) {
        if (! is_array($field)) {
            continue;
        }
        $name = sanitize_key((string) ($field['name'] ?? ''));
        if ($name === '') {
            continue;
        }
        $data[$name] = get_field($name, $workspaceId);
    }

    return $data;
}

/** @return array<string,mixed> */
function emonks_get_service_config(string $serviceKey): array
{
    return emonks_get_service_schema($serviceKey);
}

/** @return array<string,mixed> */
function emonks_get_workspace_service_context(int $workspaceId): array
{
    $serviceType = sanitize_key((string) emonks_get_workspace_meta($workspaceId, 'service_type', ''));
    $service = emonks_get_service_config($serviceType);
    $serviceData = emonks_get_workspace_service_data($workspaceId);
    $acfData = emonks_get_workspace_acf_data($workspaceId, $serviceType);
    $fieldSource = sanitize_key((string) ($service['field_source_default'] ?? 'plugin'));

    return [
        'service_type' => $serviceType,
        'service' => $service,
        'field_source' => $fieldSource,
        'forms' => [
            'workspace_edit' => emonks_resolve_forms_for_context($serviceType, 'workspace_edit', $workspaceId),
            'workspace_create' => emonks_resolve_forms_for_context($serviceType, 'workspace_create', $workspaceId),
        ],
        'service_data' => $serviceData,
        'acf_data' => $acfData,
        'content' => $fieldSource === 'acf' ? $acfData : ($fieldSource === 'hybrid' ? array_replace($serviceData, $acfData) : $serviceData),
    ];
}

/** @return array<string,array<string,mixed>> */
function emonks_get_available_services_for_plan(string $plan): array
{
    $services = Services::all();
    $plans = Plans::getPlans();
    $allowed = is_array($plans[$plan]['enabled_services'] ?? null) ? $plans[$plan]['enabled_services'] : [];
    $allowed = array_values(array_filter(array_map(static fn($item) => sanitize_key((string) $item), $allowed)));

    if (empty($allowed)) {
        $allowed = array_keys($services);
    }

    $out = [];
    foreach ($services as $key => $service) {
        $serviceKey = sanitize_key((string) $key);
        if (! in_array($serviceKey, $allowed, true)) {
            continue;
        }
        $config = emonks_get_service_config($serviceKey);
        if (sanitize_key((string) ($config['status'] ?? 'draft')) !== 'active') {
            continue;
        }
        $out[$serviceKey] = $service;
    }

    return $out;
}

/** @return array{values:array<string,mixed>,errors:array<string,string>} */
function emonks_validate_form_payload(string $formKey, array $payload, array $context = []): array
{
    $schema = emonks_resolve_form_template($formKey, $context);
    $fields = is_array($schema['fields'] ?? null) ? $schema['fields'] : [];
    $values = [];
    $errors = [];

    foreach ($fields as $field) {
        if (! is_array($field)) {
            continue;
        }

        $key = sanitize_key((string) ($field['key'] ?? ''));
        if ($key === '') {
            continue;
        }

        $type = sanitize_key((string) ($field['type'] ?? 'text'));
        $label = sanitize_text_field((string) ($field['label'] ?? $key));
        $required = (bool) ($field['required'] ?? false);
        $raw = $payload[$key] ?? '';
        $raw = is_scalar($raw) ? (string) $raw : '';

        $value = match ($type) {
            'email' => sanitize_email($raw),
            'url' => esc_url_raw($raw),
            'textarea' => sanitize_textarea_field($raw),
            default => sanitize_text_field($raw),
        };

        if ($required && $value === '') {
            $errors[$key] = sprintf('%s is verplicht.', $label);
        }

        $rules = is_array($field['rules'] ?? null) ? $field['rules'] : [];
        foreach ($rules as $rule) {
            if (! is_array($rule)) {
                continue;
            }

            $ruleType = sanitize_key((string) ($rule['type'] ?? ''));
            $ruleValue = $rule['value'] ?? null;
            if ($ruleType === 'min_length' && is_numeric($ruleValue) && strlen($value) < (int) $ruleValue && $value !== '') {
                $errors[$key] = sprintf('%s moet minimaal %d tekens bevatten.', $label, (int) $ruleValue);
            }
            if ($ruleType === 'max_length' && is_numeric($ruleValue) && strlen($value) > (int) $ruleValue && $value !== '') {
                $errors[$key] = sprintf('%s mag maximaal %d tekens bevatten.', $label, (int) $ruleValue);
            }
        }

        if ($type === 'email' && $value !== '' && ! is_email($value)) {
            $errors[$key] = sprintf('%s is ongeldig.', $label);
        }

        if ($type === 'url' && $value !== '' && ! filter_var($value, FILTER_VALIDATE_URL)) {
            $errors[$key] = sprintf('%s is ongeldig.', $label);
        }

        if ($type === 'select') {
            $options = is_array($field['options'] ?? null) ? $field['options'] : [];
            if (! empty($options) && $value !== '' && ! array_key_exists($value, $options)) {
                $errors[$key] = sprintf('%s heeft een ongeldige waarde.', $label);
            }
        }

        $values[$key] = $value;
    }

    return ['values' => $values, 'errors' => $errors];
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
