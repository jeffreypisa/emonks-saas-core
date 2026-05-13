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
        add_submenu_page('emonks-saas-core', 'Services', 'Services', 'manage_options', 'emonks-saas-services', [$this, 'renderServicesPage']);
        add_submenu_page('emonks-saas-core', 'Module Health', 'Module Health', 'manage_options', 'emonks-saas-module-health', [$this, 'renderModuleHealthPage']);
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
        wp_add_inline_style(
            'emonks-saas-admin',
            '.emonks-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px}' .
            '.emonks-card{background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:16px}' .
            '.emonks-kpi{font-size:28px;font-weight:700;margin-top:8px}' .
            '.emonks-muted{color:#646970}' .
            '.emonks-header{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin:12px 0 20px}' .
            '.emonks-tip{background:#f6f7f7;border-left:4px solid #2271b1;padding:12px 14px;border-radius:8px;max-width:540px}' .
            '.emonks-subtle{font-size:12px;color:#646970;line-height:1.45}' .
            '.emonks-form-table td .description{margin-top:6px;display:block}'
        );
    }

    public function renderDashboardPage(): void
    {
        $stats = $this->collectStats();

        echo '<div class="wrap">';
        $this->renderPageHeader('Emonks SaaS Dashboard', 'Overzicht van adoptie, workspaces en subscriptions.', 'Gebruik dit dashboard om bottlenecks in onboarding, billing activatie en publicatie direct te signaleren.');

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

        echo '<h2 style="margin-top:24px;">Aanbevolen beheerflow</h2>';
        echo '<ol><li>Controleer eerst Billing configuratie en test mode.</li><li>Valideer daarna planlimieten en features per service.</li><li>Rond af met onboarding- en publish-checks.</li></ol>';
        echo '</div>';
    }

    public function renderGeneralPage(): void
    {
        $settings = get_option(Settings::OPTION_KEY, []);
        echo '<div class="wrap">';
        $this->renderPageHeader('Emonks SaaS - General', 'Algemene instellingen voor branding en debug gedrag.', 'Houd branding neutraal en configureer debug alleen in test/staging om noise op productie te beperken.');

        $this->renderSettingsFormStart('general');
        echo '<table class="form-table emonks-form-table">';
        echo '<tr><th scope="row"><label for="branding_name">Branding Name</label></th><td><input name="settings[branding][name]" id="branding_name" class="regular-text" value="' . esc_attr((string) ($settings['branding']['name'] ?? 'Emonks')) . '" /><span class="description">Wordt gebruikt als standaard productnaam in plugin UI context.</span></td></tr>';
        echo '<tr><th scope="row"><label for="debug_enabled">Debug Logging</label></th><td><label><input type="checkbox" id="debug_enabled" name="settings[debug][enabled]" value="1" ' . checked((bool) ($settings['debug']['enabled'] ?? false), true, false) . ' /> Enable debug logging to PHP error log when WP_DEBUG=true</label><span class="description">Gebruik in combinatie met het Logs-tabblad voor snellere troubleshooting.</span></td></tr>';
        echo '<tr><th scope="row"><label for="module_client_portal">Module: Client Portal</label></th><td><label><input type="checkbox" id="module_client_portal" name="settings[modules][enabled][client_portal]" value="1" ' . checked((bool) ($settings['modules']['enabled']['client_portal'] ?? true), true, false) . ' /> Active</label><span class="description">Schakelt client portal routes, REST endpoints en dashboard cards aan/uit.</span></td></tr>';
        echo '</table>';
        submit_button('Save General Settings');
        $this->renderSettingsFormEnd();
        echo '</div>';
    }

    public function renderBillingPage(): void
    {
        $settings = get_option(Settings::OPTION_KEY, []);
        $constants = (new Stripe())->constants();

        echo '<div class="wrap">';
        $this->renderPageHeader('Emonks SaaS - Billing', 'Checkout/portal instellingen en provider readiness.', 'Billing gebruikt een provider interface. Standaard is Stripe actief, maar je kunt via filters een andere provider injecteren.');

        echo '<div class="emonks-grid">';
        $this->renderKpiCard('Secret key', $constants['secret_key'] !== '' ? 'Configured' : 'Missing');
        $this->renderKpiCard('Webhook secret', $constants['webhook_secret'] !== '' ? 'Configured' : 'Missing');
        $this->renderKpiCard('Price IDs', ($constants['starter'] !== '' && $constants['plus'] !== '' && $constants['pro'] !== '') ? 'Configured' : 'Incomplete');
        echo '</div>';

        $this->renderSettingsFormStart('billing');
        echo '<table class="form-table emonks-form-table">';
        echo '<tr><th scope="row">Test mode</th><td><label><input type="checkbox" name="settings[billing][test_mode]" value="1" ' . checked((bool) ($settings['billing']['test_mode'] ?? false), true, false) . ' /> Simuleer checkout/upgrade/downgrade zonder live provider mutaties</label><span class="description">Aanbevolen voor QA van planwissels en onboarding-flow.</span></td></tr>';
        echo '<tr><th scope="row">Success URL</th><td><input name="settings[billing][success_url]" class="regular-text" value="' . esc_attr((string) ($settings['billing']['success_url'] ?? home_url('/account/billing/'))) . '" /><span class="description">Gebruiker landt hier na succesvolle checkout.</span></td></tr>';
        echo '<tr><th scope="row">Cancel URL</th><td><input name="settings[billing][cancel_url]" class="regular-text" value="' . esc_attr((string) ($settings['billing']['cancel_url'] ?? home_url('/account/billing/'))) . '" /><span class="description">Gebruiker landt hier na afgebroken checkout.</span></td></tr>';
        echo '</table>';
        submit_button('Save Billing Settings');
        $this->renderSettingsFormEnd();
        echo '</div>';
    }

    public function renderPlansPage(): void
    {
        $plans = Plans::getPlans();
        echo '<div class="wrap">';
        $this->renderPageHeader('Emonks SaaS - Plans', 'Beheer plannen als losse entiteiten en koppel features/services via taxonomieen.', 'Gebruik de native editor om plannen aan te maken. Koppel daarna Features en Services zoals tags/categorieen.');

        echo '<p><a class="button button-primary" href="' . esc_url(admin_url('post-new.php?post_type=emonks_plan')) . '">Nieuw plan aanmaken</a> ';
        echo '<a class="button" href="' . esc_url(admin_url('edit.php?post_type=emonks_plan')) . '">Planoverzicht openen</a></p>';
        echo '<p class="emonks-muted">Relaties: Plan <-> Features en Plan <-> Services. Deze koppelingen worden direct gebruikt door billing, workspace limits en feature checks.</p>';

        echo '<h2 style="margin-top:20px;">Actieve Plan Matrix</h2>';
        echo '<table class="widefat striped"><thead><tr><th>Plan key</th><th>Label</th><th>Max workspaces</th><th>Prijs p/m</th><th>Prijs p/j</th><th>Valuta</th><th>Features</th><th>Services</th><th>Stripe Monthly ID</th><th>Stripe Yearly ID</th></tr></thead><tbody>';
        foreach ($plans as $key => $plan) {
            $features = is_array($plan['enabled_features'] ?? null) ? implode(',', $plan['enabled_features']) : '';
            $services = is_array($plan['enabled_services'] ?? null) ? implode(',', $plan['enabled_services']) : '';
            echo '<tr>';
            echo '<td><strong>' . esc_html((string) $key) . '</strong></td>';
            echo '<td>' . esc_html((string) ($plan['label'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($plan['max_workspaces'] ?? 0)) . '</td>';
            echo '<td>' . esc_html((string) ($plan['price_monthly'] ?? 0)) . '</td>';
            echo '<td>' . esc_html((string) ($plan['price_yearly'] ?? 0)) . '</td>';
            echo '<td>' . esc_html((string) ($plan['currency'] ?? 'EUR')) . '</td>';
            echo '<td>' . esc_html($features !== '' ? $features : '-') . '</td>';
            echo '<td>' . esc_html($services !== '' ? $services : '-') . '</td>';
            echo '<td>' . esc_html((string) ($plan['stripe_price_id_monthly'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($plan['stripe_price_id_yearly'] ?? '')) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
    }

    public function renderFeaturesPage(): void
    {
        $flags = Features::globalFlags();
        $terms = get_terms(['taxonomy' => 'emonks_feature', 'hide_empty' => false]);

        echo '<div class="wrap">';
        $this->renderPageHeader('Emonks SaaS - Features', 'Features zijn losse termen die je aan plannen koppelt.', 'Maak eerst features aan, koppel ze daarna in de plan editor. Global flags hieronder blijven platform-brede kill-switches.');

        echo '<p><a class="button button-primary" href="' . esc_url(admin_url('edit-tags.php?taxonomy=emonks_feature&post_type=emonks_plan')) . '">Features beheren</a></p>';
        echo '<h2>Beschikbare Feature Termen</h2>';
        echo '<table class="widefat striped"><thead><tr><th>Slug</th><th>Naam</th></tr></thead><tbody>';
        if (is_array($terms) && ! empty($terms)) {
            foreach ($terms as $term) {
                if (! $term instanceof \WP_Term) {
                    continue;
                }
                echo '<tr><td>' . esc_html($term->slug) . '</td><td>' . esc_html($term->name) . '</td></tr>';
            }
        } else {
            echo '<tr><td colspan="2">Nog geen feature-termen.</td></tr>';
        }
        echo '</table>';

        echo '<h2 style="margin-top:24px;">Global Feature Flags</h2>';
        $this->renderSettingsFormStart('features');
        echo '<table class="form-table emonks-form-table">';
        foreach ($flags as $flag => $enabled) {
            $checked = (bool) (emonks_get_setting('feature_flags.' . $flag, $enabled));
            echo '<tr><th scope="row">' . esc_html($flag) . '</th><td><label><input type="checkbox" name="settings[feature_flags][' . esc_attr($flag) . ']" value="1" ' . checked($checked, true, false) . ' /> Enabled</label><span class="description">Uitzetten forceert deze capability platformbreed naar false.</span></td></tr>';
        }
        echo '</table>';
        submit_button('Save Global Feature Flags');
        $this->renderSettingsFormEnd();
        echo '</div>';
    }

    public function renderServicesPage(): void
    {
        $services = Services::all();
        $terms = get_terms(['taxonomy' => 'emonks_service', 'hide_empty' => false]);
        echo '<div class="wrap">';
        $this->renderPageHeader('Emonks SaaS - Services', 'Services zijn losse termen die je aan plannen koppelt.', 'Gebruik services als taxonomie voor plan-koppeling. De plugin registreert deze termen automatisch als service types in de runtime.');

        echo '<p><a class="button button-primary" href="' . esc_url(admin_url('edit-tags.php?taxonomy=emonks_service&post_type=emonks_plan')) . '">Services beheren</a></p>';
        echo '<h2>Beschikbare Service Termen</h2>';
        echo '<table class="widefat striped"><thead><tr><th>Slug</th><th>Naam</th></tr></thead><tbody>';
        if (is_array($terms) && ! empty($terms)) {
            foreach ($terms as $term) {
                if (! $term instanceof \WP_Term) {
                    continue;
                }
                echo '<tr><td>' . esc_html($term->slug) . '</td><td>' . esc_html($term->name) . '</td></tr>';
            }
        } else {
            echo '<tr><td colspan="2">Nog geen service-termen.</td></tr>';
        }
        echo '</tbody></table>';

        echo '<h2 style="margin-top:24px;">Runtime Service Registry</h2>';
        echo '<table class="widefat striped"><thead><tr><th>Service</th><th>Capabilities</th><th>Supported Features</th><th>Onboarding Steps</th><th>Policy</th></tr></thead><tbody>';
        foreach ($services as $key => $service) {
            $caps = implode(', ', (array) ($service['capabilities'] ?? []));
            $features = implode(', ', (array) ($service['supported_features'] ?? []));
            $steps = implode(', ', (array) ($service['onboarding_steps'] ?? []));
            $policy = (string) ($service['policy'] ?? '');
            echo '<tr>';
            echo '<td><strong>' . esc_html((string) $key) . '</strong><div class="emonks-subtle">' . esc_html((string) (($service['labels']['singular'] ?? '') . ' / ' . ($service['labels']['plural'] ?? ''))) . '</div></td>';
            echo '<td>' . esc_html($caps !== '' ? $caps : '-') . '</td>';
            echo '<td>' . esc_html($features !== '' ? $features : '-') . '</td>';
            echo '<td>' . esc_html($steps !== '' ? $steps : '-') . '</td>';
            echo '<td>' . esc_html($policy !== '' ? $policy : '-') . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';

        echo '<h2 style="margin-top:24px;">Implementatie tips</h2>';
        echo '<ol><li>Maak per dienst een class die ServiceModuleInterface implementeert.</li><li>Gebruik settings_schema voor form rendering en validatie.</li><li>Definieer policy/onboarding_steps per dienst in de service definitie.</li></ol>';
        echo '</div>';
    }

    public function renderOnboardingPage(): void
    {
        echo '<div class="wrap">';
        $this->renderPageHeader('Emonks SaaS - Onboarding UX', 'Configureer first-value flow voor verschillende services.', 'Onboarding ondersteunt nu dynamische step evaluators en checklists via filters. Koppel stappen aan domeinevents voor automatische progressie.');

        echo '<h2>Standaard flow</h2>';
        echo '<ol><li>Account aangemaakt</li><li>Eerste workspace</li><li>Billing verbonden</li><li>Workspace gepubliceerd</li></ol>';
        echo '<h2>Customizable hooks</h2>';
        echo '<ul><li><code>emonks_onboarding_checklist</code></li><li><code>emonks_onboarding_step_evaluators</code></li><li><code>emonks_service_onboarding_steps</code></li></ul>';
        echo '</div>';
    }

    public function renderModuleHealthPage(): void
    {
        $health = Plugin::instance()->get('module_health');
        $rows = [];
        if ($health instanceof ModuleHealth) {
            $rows = $health->report();
        }

        echo '<div class="wrap">';
        $this->renderPageHeader('Emonks SaaS - Module Health', 'Snelle controle op module-status, lifecycle en template-resolutie.', 'Gebruik dit scherm om snel te zien of modules enabled zijn en of hun belangrijkste templates geladen kunnen worden.');
        echo '<table class="widefat striped"><thead><tr><th>Module</th><th>Enabled</th><th>Routes actief</th><th>REST actief</th><th>Templates gevonden</th><th>Lifecycle</th><th>Details</th></tr></thead><tbody>';
        if (empty($rows)) {
            echo '<tr><td colspan="7">No modules registered.</td></tr>';
        } else {
            foreach ($rows as $row) {
                $detailBits = [];
                $routes = is_array($row['routes'] ?? null) ? $row['routes'] : [];
                $rest = is_array($row['rest'] ?? null) ? $row['rest'] : [];
                $templateBits = [];
                $templates = is_array($row['templates'] ?? null) ? $row['templates'] : [];
                foreach ($routes as $route => $ok) {
                    $detailBits[] = 'route ' . esc_html((string) $route) . ': ' . ($ok ? 'ok' : 'missing');
                }
                foreach ($rest as $endpoint => $ok) {
                    $detailBits[] = 'rest ' . esc_html((string) $endpoint) . ': ' . ($ok ? 'ok' : 'missing');
                }
                foreach ($templates as $template => $ok) {
                    $templateBits[] = esc_html((string) $template) . ': ' . ($ok ? 'ok' : 'missing');
                }
                $detailBits = array_merge($detailBits, $templateBits);
                $detailSummary = empty($detailBits) ? '-' : implode('<br>', $detailBits);

                echo '<tr>';
                echo '<td><strong>' . esc_html((string) ($row['key'] ?? '')) . '</strong></td>';
                echo '<td>' . (! empty($row['enabled']) ? 'yes' : 'no') . '</td>';
                echo '<td>' . (! empty($row['routes_active']) ? 'yes' : 'no') . '</td>';
                echo '<td>' . (! empty($row['rest_active']) ? 'yes' : 'no') . '</td>';
                echo '<td>' . (! empty($row['templates_found']) ? 'yes' : 'no') . '</td>';
                echo '<td>' . (! empty($row['lifecycle']) ? 'yes' : 'no') . '</td>';
                echo '<td>' . $detailSummary . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo '</tr>';
            }
        }
        echo '</tbody></table></div>';
    }

    public function renderLogsPage(): void
    {
        $logs = get_option('emonks_saas_logs', []);
        echo '<div class="wrap">';
        $this->renderPageHeader('Emonks SaaS - Logs', 'Snelle operationele feedback vanuit plugin events.', 'Gebruik logs voor korte termijn troubleshooting. Voor hoge volumes is externe logging of custom table aanbevolen.');

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
        } elseif ($tab === 'general') {
            $current = array_replace_recursive($current, $sanitized);
            $current['modules']['enabled']['client_portal'] = isset($_POST['settings']['modules']['enabled']['client_portal']);
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

    private function renderPageHeader(string $title, string $subtitle, string $tip): void
    {
        echo '<div class="emonks-header">';
        echo '<div><h1>' . esc_html($title) . '</h1><p class="emonks-muted">' . esc_html($subtitle) . '</p></div>';
        echo '<div class="emonks-tip"><strong>Tip</strong><br>' . esc_html($tip) . '</div>';
        echo '</div>';
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
