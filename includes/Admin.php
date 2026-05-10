<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

final class Admin
{
    public function boot(): void
    {
        add_action('admin_menu', [$this, 'registerMenu']);
        add_action('admin_post_emonks_saas_save_settings', [$this, 'saveSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }

    public function registerMenu(): void
    {
        add_menu_page('Emonks SaaS', 'Emonks SaaS', 'manage_options', 'emonks-saas-core', [$this, 'renderDashboardPage'], 'dashicons-chart-area', 58);
        add_submenu_page('emonks-saas-core', 'Dashboard', 'Dashboard', 'manage_options', 'emonks-saas-core', [$this, 'renderDashboardPage']);
        add_submenu_page('emonks-saas-core', 'General', 'General', 'manage_options', 'emonks-saas-general', [$this, 'renderGeneralPage']);
        add_submenu_page('emonks-saas-core', 'Billing', 'Billing', 'manage_options', 'emonks-saas-billing', [$this, 'renderBillingPage']);
        add_submenu_page('emonks-saas-core', 'Plans', 'Plans', 'manage_options', 'emonks-saas-plans', [$this, 'renderPlansPage']);
        add_submenu_page('emonks-saas-core', 'Features', 'Features', 'manage_options', 'emonks-saas-features', [$this, 'renderFeaturesPage']);
        add_submenu_page('emonks-saas-core', 'Onboarding', 'Onboarding', 'manage_options', 'emonks-saas-onboarding', [$this, 'renderOnboardingPage']);
        add_submenu_page('emonks-saas-core', 'Logs', 'Logs', 'manage_options', 'emonks-saas-logs', [$this, 'renderLogsPage']);
    }

    public function enqueueAdminAssets(string $hook): void
    {
        if (! str_contains($hook, 'emonks-saas')) {
            return;
        }

        wp_register_style('emonks-saas-admin', false, [], EMONKS_SAAS_CORE_VERSION);
        wp_enqueue_style('emonks-saas-admin');
        wp_add_inline_style('emonks-saas-admin', '.emonks-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px}.emonks-card{background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:16px}.emonks-kpi{font-size:28px;font-weight:700;margin-top:8px}.emonks-muted{color:#646970}');
    }

    public function renderDashboardPage(): void
    {
        $stats = $this->collectStats();
        echo '<div class="wrap"><h1>Emonks SaaS Dashboard</h1>';
        echo '<p class="emonks-muted">Overzicht van adoptie, workspaces en subscriptions.</p>';
        echo '<div class="emonks-grid">';
        $this->renderKpiCard('Accounts', (string) $stats['accounts']);
        $this->renderKpiCard('Workspaces', (string) $stats['workspaces']);
        $this->renderKpiCard('Actieve subscriptions', (string) $stats['active_subscriptions']);
        $this->renderKpiCard('Published workspaces', (string) $stats['published_workspaces']);
        echo '</div>';

        echo '<h2 style="margin-top:24px;">Plan verdeling</h2><table class="widefat striped"><thead><tr><th>Plan</th><th>Aantal</th></tr></thead><tbody>';
        foreach ($stats['plan_distribution'] as $plan => $count) {
            echo '<tr><td>' . esc_html((string) $plan) . '</td><td>' . esc_html((string) $count) . '</td></tr>';
        }
        echo '</tbody></table>';

        echo '<h2 style="margin-top:24px;">Beheer UX tips</h2>';
        echo '<ul><li>Configureer Stripe constants en test checkout flow.</li><li>Controleer feature flags per omgeving.</li><li>Gebruik routes/labels filters in je theme voor service-specifieke terminologie.</li></ul>';
        echo '</div>';
    }

    public function renderGeneralPage(): void
    {
        $settings = get_option(Settings::OPTION_KEY, []);
        echo '<div class="wrap"><h1>Emonks SaaS - General</h1>';
        echo '<p class="emonks-muted">Algemene instellingen voor branding en debug gedrag.</p>';
        $this->renderSettingsFormStart('general');
        echo '<table class="form-table">';
        echo '<tr><th scope="row"><label for="branding_name">Branding Name</label></th><td><input name="settings[branding][name]" id="branding_name" class="regular-text" value="' . esc_attr((string) ($settings['branding']['name'] ?? 'Emonks')) . '" /></td></tr>';
        echo '<tr><th scope="row"><label for="debug_enabled">Debug Logging</label></th><td><label><input type="checkbox" name="settings[debug][enabled]" value="1" ' . checked((bool) ($settings['debug']['enabled'] ?? false), true, false) . ' /> Enable debug logging to PHP error log when WP_DEBUG=true</label></td></tr>';
        echo '</table>';
        submit_button('Save General Settings');
        $this->renderSettingsFormEnd();
        echo '</div>';
    }

    public function renderBillingPage(): void
    {
        $settings = get_option(Settings::OPTION_KEY, []);
        $constants = (new Stripe())->constants();

        echo '<div class="wrap"><h1>Emonks SaaS - Billing</h1>';
        echo '<p class="emonks-muted">Checkout en portal URL instellingen, plus status van Stripe constants.</p>';
        echo '<div class="emonks-grid">';
        $this->renderKpiCard('Secret key', $constants['secret_key'] !== '' ? 'Configured' : 'Missing');
        $this->renderKpiCard('Webhook secret', $constants['webhook_secret'] !== '' ? 'Configured' : 'Missing');
        $this->renderKpiCard('Price IDs', ($constants['starter'] !== '' && $constants['plus'] !== '' && $constants['pro'] !== '') ? 'Configured' : 'Incomplete');
        echo '</div>';

        $this->renderSettingsFormStart('billing');
        echo '<table class="form-table">';
        echo '<tr><th scope="row">Test mode</th><td><label><input type="checkbox" name="settings[billing][test_mode]" value="1" ' . checked((bool) ($settings['billing']['test_mode'] ?? false), true, false) . ' /> Simuleer checkout/upgrade/downgrade zonder live Stripe mutaties</label></td></tr>';
        echo '<tr><th scope="row">Success URL</th><td><input name="settings[billing][success_url]" class="regular-text" value="' . esc_attr((string) ($settings['billing']['success_url'] ?? home_url('/account/billing/'))) . '" /></td></tr>';
        echo '<tr><th scope="row">Cancel URL</th><td><input name="settings[billing][cancel_url]" class="regular-text" value="' . esc_attr((string) ($settings['billing']['cancel_url'] ?? home_url('/account/billing/'))) . '" /></td></tr>';
        echo '</table>';
        submit_button('Save Billing Settings');
        $this->renderSettingsFormEnd();
        echo '</div>';
    }

    public function renderPlansPage(): void
    {
        $plans = Plans::getPlans();
        echo '<div class="wrap"><h1>Emonks SaaS - Plans</h1>';
        echo '<p class="emonks-muted">Beheer labels, workspace limieten, features en Stripe prijskoppelingen.</p>';
        $this->renderSettingsFormStart('plans');
        echo '<table class="widefat striped"><thead><tr><th>Plan key</th><th>Label</th><th>Max workspaces</th><th>Prijs p/m</th><th>Prijs p/j</th><th>Valuta</th><th>Features (comma separated)</th><th>Stripe Price Constant</th><th>Stripe Price ID Monthly</th><th>Stripe Price ID Yearly</th></tr></thead><tbody>';
        foreach ($plans as $key => $plan) {
            $features = is_array($plan['enabled_features'] ?? null) ? implode(',', $plan['enabled_features']) : '';
            echo '<tr>';
            echo '<td><strong>' . esc_html((string) $key) . '</strong></td>';
            echo '<td><input class="regular-text" name="settings[plans][' . esc_attr((string) $key) . '][label]" value="' . esc_attr((string) ($plan['label'] ?? '')) . '" /></td>';
            echo '<td><input type="number" min="0" class="small-text" name="settings[plans][' . esc_attr((string) $key) . '][max_workspaces]" value="' . esc_attr((string) ($plan['max_workspaces'] ?? 0)) . '" /></td>';
            echo '<td><input type="number" step="0.01" min="0" class="small-text" name="settings[plans][' . esc_attr((string) $key) . '][price_monthly]" value="' . esc_attr((string) ($plan['price_monthly'] ?? 0)) . '" /></td>';
            echo '<td><input type="number" step="0.01" min="0" class="small-text" name="settings[plans][' . esc_attr((string) $key) . '][price_yearly]" value="' . esc_attr((string) ($plan['price_yearly'] ?? 0)) . '" /></td>';
            echo '<td><input class="small-text" name="settings[plans][' . esc_attr((string) $key) . '][currency]" value="' . esc_attr((string) ($plan['currency'] ?? 'EUR')) . '" /></td>';
            echo '<td><input class="regular-text" name="settings[plans][' . esc_attr((string) $key) . '][enabled_features]" value="' . esc_attr($features) . '" /></td>';
            echo '<td><input class="regular-text" name="settings[plans][' . esc_attr((string) $key) . '][stripe_price_constant]" value="' . esc_attr((string) ($plan['stripe_price_constant'] ?? '')) . '" /></td>';
            echo '<td><input class="regular-text" name="settings[plans][' . esc_attr((string) $key) . '][stripe_price_id_monthly]" value="' . esc_attr((string) ($plan['stripe_price_id_monthly'] ?? '')) . '" /></td>';
            echo '<td><input class="regular-text" name="settings[plans][' . esc_attr((string) $key) . '][stripe_price_id_yearly]" value="' . esc_attr((string) ($plan['stripe_price_id_yearly'] ?? '')) . '" /></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        submit_button('Save Plans');
        $this->renderSettingsFormEnd();
        echo '</div>';
    }

    public function renderFeaturesPage(): void
    {
        $settings = get_option(Settings::OPTION_KEY, []);
        $flags = Features::globalFlags();

        echo '<div class="wrap"><h1>Emonks SaaS - Features</h1>';
        echo '<p class="emonks-muted">Activeer/deactiveer generieke capabilities platformbreed.</p>';
        $this->renderSettingsFormStart('features');
        echo '<table class="form-table">';
        foreach ($flags as $flag => $enabled) {
            $checked = (bool) ($settings['feature_flags'][$flag] ?? $enabled);
            echo '<tr><th scope="row">' . esc_html($flag) . '</th><td><label><input type="checkbox" name="settings[feature_flags][' . esc_attr($flag) . ']" value="1" ' . checked($checked, true, false) . ' /> Enabled</label></td></tr>';
        }
        echo '</table>';
        submit_button('Save Feature Flags');
        $this->renderSettingsFormEnd();
        echo '</div>';
    }

    public function renderOnboardingPage(): void
    {
        echo '<div class="wrap"><h1>Emonks SaaS - Onboarding UX</h1>';
        echo '<p class="emonks-muted">Onboarding flows zijn ontworpen voor snelle first value: account > first workspace > billing > publish.</p>';
        echo '<ol><li>Maak route templates thematisch service-proof.</li><li>Gebruik checklist-status in dashboard template context.</li><li>Toon helper content, voorbeelden en CTA’s op elk onboarding scherm.</li></ol>';
        echo '</div>';
    }

    public function renderLogsPage(): void
    {
        $logs = get_option('emonks_saas_logs', []);
        echo '<div class="wrap"><h1>Emonks SaaS - Logs</h1>';
        if (! is_array($logs) || empty($logs)) {
            echo '<p>No logs available.</p></div>';
            return;
        }

        echo '<table class="widefat striped"><thead><tr><th>Time</th><th>Channel</th><th>Message</th></tr></thead><tbody>';
        foreach (array_reverse(array_slice($logs, -200)) as $entry) {
            echo '<tr><td>' . esc_html((string) ($entry['time'] ?? '')) . '</td><td>' . esc_html((string) ($entry['channel'] ?? '')) . '</td><td>' . esc_html((string) ($entry['message'] ?? '')) . '</td></tr>';
        }
        echo '</tbody></table></div>';
    }

    public function saveSettings(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die('Unauthorized', 403);
        }

        check_admin_referer('emonks_saas_save_settings', 'emonks_nonce');
        $raw = $_POST['settings'] ?? [];
        $settingsService = Plugin::instance()->get('settings');
        if (! $settingsService instanceof Settings) {
            wp_safe_redirect(admin_url('admin.php?page=emonks-saas-core'));
            exit;
        }

        $sanitized = $settingsService->sanitizeSettings($raw);
        $current = get_option(Settings::OPTION_KEY, []);
        if (! is_array($current)) {
            $current = [];
        }

        $tab = sanitize_key((string) ($_POST['tab'] ?? ''));
        if ($tab === 'features') {
            $allFlags = array_keys(Features::globalFlags());
            $nextFlags = [];
            foreach ($allFlags as $flag) {
                $nextFlags[sanitize_key((string) $flag)] = false;
            }

            $submitted = $sanitized['feature_flags'] ?? [];
            if (is_array($submitted)) {
                foreach ($submitted as $flag => $value) {
                    $nextFlags[sanitize_key((string) $flag)] = (bool) $value;
                }
            }

            $current['feature_flags'] = $nextFlags;
        } else {
            $current = array_replace_recursive($current, $sanitized);
        }

        update_option(Settings::OPTION_KEY, $current);

        wp_safe_redirect(wp_get_referer() ?: admin_url('admin.php?page=emonks-saas-core'));
        exit;
    }

    private function renderSettingsFormStart(string $tab): void
    {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('emonks_saas_save_settings', 'emonks_nonce');
        echo '<input type="hidden" name="action" value="emonks_saas_save_settings" />';
        echo '<input type="hidden" name="tab" value="' . esc_attr($tab) . '" />';
    }

    private function renderSettingsFormEnd(): void
    {
        echo '</form>';
    }

    private function renderKpiCard(string $label, string $value): void
    {
        echo '<div class="emonks-card"><div>' . esc_html($label) . '</div><div class="emonks-kpi">' . esc_html($value) . '</div></div>';
    }

    /** @return array<string,mixed> */
    private function collectStats(): array
    {
        $accounts = count_users();
        $workspaceCount = wp_count_posts('emonks_workspace');
        $workspaceTotal = (int) ($workspaceCount->publish ?? 0);

        $activeSubscriptions = count(get_users(['meta_key' => 'emonks_subscription_status', 'meta_value' => 'active', 'fields' => 'ids']));
        $publishedWorkspaces = count(get_posts([
            'post_type' => 'emonks_workspace',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [[
                'key' => 'workspace_status',
                'value' => 'published',
                'compare' => '=',
            ]],
        ]));

        $planDistribution = ['starter' => 0, 'plus' => 0, 'pro' => 0, 'other' => 0];
        foreach (get_users(['fields' => 'ids']) as $userId) {
            $plan = sanitize_key((string) get_user_meta((int) $userId, 'emonks_subscription_plan', true));
            if ($plan === '') {
                $plan = 'starter';
            }

            if (! isset($planDistribution[$plan])) {
                $planDistribution['other']++;
            } else {
                $planDistribution[$plan]++;
            }
        }

        return [
            'accounts' => (int) ($accounts['total_users'] ?? 0),
            'workspaces' => $workspaceTotal,
            'active_subscriptions' => $activeSubscriptions,
            'published_workspaces' => $publishedWorkspaces,
            'plan_distribution' => $planDistribution,
        ];
    }
}
