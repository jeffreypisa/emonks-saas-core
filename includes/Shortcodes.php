<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

final class Shortcodes
{
    public function boot(): void
    {
        add_shortcode('emonks_form', [$this, 'renderFormShortcode']);
        add_shortcode('emonks_workspace_list', [$this, 'renderWorkspaceListShortcode']);
        add_shortcode('emonks_account_link', [$this, 'renderAccountLinkShortcode']);
    }

    /** @return array<int,array<string,string>> */
    public static function catalog(): array
    {
        return [
            [
                'tag' => 'emonks_form',
                'example' => '[emonks_form key="auth_login"]',
                'description' => 'Rendert een formulier uit Forms op basis van form key.',
            ],
            [
                'tag' => 'emonks_workspace_list',
                'example' => '[emonks_workspace_list]',
                'description' => 'Toont workspaces van ingelogde gebruiker (account-scope).',
            ],
            [
                'tag' => 'emonks_account_link',
                'example' => '[emonks_account_link suffix="billing" label="Ga naar billing"]',
                'description' => 'Toont een link naar account of een account-subroute.',
            ],
        ];
    }

    /** @param array<string,mixed> $atts */
    public function renderFormShortcode(array $atts = []): string
    {
        $atts = shortcode_atts([
            'key' => '',
            'title' => '',
            'class' => '',
            'workspace_id' => '0',
            'account_id' => '0',
            'service_type' => '',
            'submit_label' => '',
        ], $atts, 'emonks_form');

        $formKey = sanitize_key((string) ($atts['key'] ?? ''));
        if ($formKey === '') {
            return '<div class="alert alert-warning">Form key ontbreekt. Gebruik bijvoorbeeld: [emonks_form key="auth_login"]</div>';
        }

        $schema = emonks_get_form_schema($formKey);
        $fields = is_array($schema['fields'] ?? null) ? $schema['fields'] : [];
        if (empty($schema) || empty($fields)) {
            return '<div class="alert alert-warning">Form schema niet gevonden voor key: <code>' . esc_html($formKey) . '</code></div>';
        }

        $submitLabel = sanitize_text_field((string) ($atts['submit_label'] ?? ''));
        if ($submitLabel === '') {
            $submitLabel = sanitize_text_field((string) ($schema['submit_label'] ?? 'Opslaan'));
        }

        $wrapperClass = trim('emonks-shortcode-form ' . sanitize_html_class((string) ($atts['class'] ?? '')));
        $title = sanitize_text_field((string) ($atts['title'] ?? ''));
        $action = $this->resolveActionUrl($formKey);
        $nonceAction = sanitize_key((string) ($schema['nonce_action'] ?? ''));
        $workspaceId = absint((string) ($atts['workspace_id'] ?? 0));
        $accountId = absint((string) ($atts['account_id'] ?? 0));
        $serviceType = sanitize_key((string) ($atts['service_type'] ?? ''));

        ob_start();
        echo '<section class="' . esc_attr($wrapperClass) . '">';
        if ($title !== '') {
            echo '<h3>' . esc_html($title) . '</h3>';
        }
        echo '<form method="post" action="' . esc_url($action) . '" class="vstack gap-3">';
        if ($nonceAction !== '') {
            wp_nonce_field($nonceAction, 'emonks_nonce');
        }

        if ($formKey === 'workspace_create' || $formKey === 'workspace_edit') {
            if ($workspaceId > 0) {
                echo '<input type="hidden" name="workspace_id" value="' . esc_attr((string) $workspaceId) . '" />';
            }
            if ($accountId > 0) {
                echo '<input type="hidden" name="account_id" value="' . esc_attr((string) $accountId) . '" />';
            }
            if ($serviceType !== '') {
                echo '<input type="hidden" name="service_type" value="' . esc_attr($serviceType) . '" />';
            }
        }

        foreach ($fields as $field) {
            if (! is_array($field)) {
                continue;
            }
            $this->renderField($field);
        }

        echo '<button type="submit" class="btn btn-primary">' . esc_html($submitLabel) . '</button>';
        echo '</form>';
        echo '</section>';

        return (string) ob_get_clean();
    }

    private function resolveActionUrl(string $formKey): string
    {
        return match ($formKey) {
            'auth_login' => emonks_get_login_url(),
            'auth_register' => home_url('/' . emonks_get_routes()['register'] . '/'),
            'workspace_create', 'workspace_edit' => emonks_admin_post_url('emonks_workspace_save'),
            default => emonks_admin_post_url('emonks_workspace_save'),
        };
    }

    /** @param array<string,mixed> $atts */
    public function renderWorkspaceListShortcode(array $atts = []): string
    {
        if (! is_user_logged_in()) {
            return '<div class="alert alert-warning">Je moet ingelogd zijn om workspaces te zien.</div>';
        }

        $atts = shortcode_atts([
            'empty' => 'Nog geen workspaces.',
            'class' => '',
        ], $atts, 'emonks_workspace_list');

        $userId = get_current_user_id();
        $workspaces = emonks_get_user_workspaces($userId);
        $class = trim('emonks-shortcode-workspaces list-group ' . sanitize_html_class((string) ($atts['class'] ?? '')));

        ob_start();
        echo '<section class="' . esc_attr($class) . '">';
        if (empty($workspaces)) {
            echo '<div class="list-group-item">' . esc_html((string) ($atts['empty'] ?? 'Nog geen workspaces.')) . '</div>';
        } else {
            foreach ($workspaces as $workspace) {
                if (! $workspace instanceof \WP_Post) {
                    continue;
                }
                $workspaceId = (int) $workspace->ID;
                $title = get_the_title($workspaceId);
                $status = sanitize_key((string) emonks_get_workspace_meta($workspaceId, 'workspace_status', 'draft'));
                $editUrl = emonks_get_account_url('workspaces/' . $workspaceId . '/edit');
                echo '<a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="' . esc_url($editUrl) . '">';
                echo '<span>' . esc_html($title !== '' ? $title : ('Workspace #' . $workspaceId)) . '</span>';
                echo '<span class="badge text-bg-secondary">' . esc_html($status) . '</span>';
                echo '</a>';
            }
        }
        echo '</section>';

        return (string) ob_get_clean();
    }

    /** @param array<string,mixed> $atts */
    public function renderAccountLinkShortcode(array $atts = []): string
    {
        $atts = shortcode_atts([
            'suffix' => '',
            'label' => 'Open account',
            'class' => 'btn btn-outline-primary',
        ], $atts, 'emonks_account_link');

        $suffix = sanitize_key((string) ($atts['suffix'] ?? ''));
        $label = sanitize_text_field((string) ($atts['label'] ?? 'Open account'));
        $class = trim((string) ($atts['class'] ?? 'btn btn-outline-primary'));
        $url = emonks_get_account_url($suffix);

        return '<a class="' . esc_attr($class) . '" href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
    }

    /** @param array<string,mixed> $field */
    private function renderField(array $field): void
    {
        $key = sanitize_key((string) ($field['key'] ?? ''));
        if ($key === '') {
            return;
        }

        $type = sanitize_key((string) ($field['type'] ?? 'text'));
        $label = sanitize_text_field((string) ($field['label'] ?? $key));
        $required = ! empty($field['required']);
        $placeholder = sanitize_text_field((string) ($field['placeholder'] ?? ''));
        $help = sanitize_text_field((string) ($field['help'] ?? ''));
        $default = sanitize_text_field((string) ($field['default'] ?? ''));
        $requiredAttr = $required ? ' required' : '';
        $requiredMark = $required ? ' <span class="text-danger">*</span>' : '';

        echo '<div class="mb-3">';
        if ($type !== 'hidden') {
            echo '<label class="form-label" for="emonks_form_' . esc_attr($key) . '">' . esc_html($label) . $requiredMark . '</label>';
        }

        if ($type === 'textarea') {
            echo '<textarea class="form-control" id="emonks_form_' . esc_attr($key) . '" name="' . esc_attr($key) . '" placeholder="' . esc_attr($placeholder) . '"' . $requiredAttr . '>' . esc_textarea($default) . '</textarea>';
        } elseif ($type === 'select') {
            $options = is_array($field['options'] ?? null) ? $field['options'] : [];
            echo '<select class="form-select" id="emonks_form_' . esc_attr($key) . '" name="' . esc_attr($key) . '"' . $requiredAttr . '>';
            echo '<option value="">Maak een keuze</option>';
            foreach ($options as $value => $optionLabel) {
                echo '<option value="' . esc_attr((string) $value) . '">' . esc_html((string) $optionLabel) . '</option>';
            }
            echo '</select>';
        } elseif ($type === 'checkbox') {
            echo '<div class="form-check"><input class="form-check-input" type="checkbox" id="emonks_form_' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="1"' . $requiredAttr . ' />';
            echo '<label class="form-check-label" for="emonks_form_' . esc_attr($key) . '">' . esc_html($label) . '</label></div>';
        } else {
            $inputType = in_array($type, ['text', 'email', 'password', 'url', 'hidden'], true) ? $type : 'text';
            $class = $inputType === 'hidden' ? '' : 'form-control';
            echo '<input type="' . esc_attr($inputType) . '" class="' . esc_attr($class) . '" id="emonks_form_' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr($default) . '" placeholder="' . esc_attr($placeholder) . '"' . $requiredAttr . ' />';
        }

        if ($help !== '' && $type !== 'hidden') {
            echo '<div class="form-text">' . esc_html($help) . '</div>';
        }
        echo '</div>';
    }
}
