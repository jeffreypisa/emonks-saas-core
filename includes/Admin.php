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
        add_submenu_page('emonks-saas-core', 'Form Builder', 'Form Builder', 'manage_options', 'emonks-saas-form-builder', [$this, 'renderFormBuilderPage']);
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
            '.emonks-form-table td .description{margin-top:6px;display:block}' .
            '.emonks-builder-shell{display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:18px;align-items:start}' .
            '.emonks-builder-main{min-width:0}.emonks-builder-inspector{position:sticky;top:46px}' .
            '.emonks-builder-schemas{display:flex;gap:8px;flex-wrap:wrap;margin:0 0 14px}' .
            '.emonks-builder-preview{background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:14px}' .
            '.emonks-builder-field{display:flex;justify-content:space-between;gap:10px;align-items:center;border:1px solid #dcdcde;border-left:3px solid transparent;background:#fff;border-radius:10px;padding:10px 12px;margin:0 0 10px;cursor:pointer}' .
            '.emonks-builder-field:hover{border-color:#bfc3c9}.emonks-builder-field.is-selected{border-color:#2271b1;border-left-color:#2271b1;background:#eef6ff;box-shadow:0 0 0 1px rgba(34,113,177,.18)}' .
            '.emonks-builder-field-meta{display:flex;gap:8px;flex-wrap:wrap;align-items:center;color:#646970;font-size:12px}' .
            '.emonks-pill{display:inline-flex;align-items:center;gap:4px;border:1px solid #dcdcde;border-radius:999px;padding:2px 8px;background:#fff;font-size:11px}' .
            '.emonks-field-settings{display:none}' .
            '[data-settings-container] > .emonks-field-settings{display:block}' .
            '.emonks-field-type-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}' .
            '.emonks-field-type-grid .button{justify-content:flex-start;text-align:left;min-height:34px}' .
            '.emonks-inspector-tabs{display:flex;gap:6px;margin-bottom:10px}' .
            '.emonks-inspector-tab-panel{display:none}.emonks-inspector-tab-panel.is-active{display:block}' .
            '.emonks-actions{display:flex;gap:6px;flex-wrap:wrap}.emonks-actions .button-link{font-size:12px}' .
            '.emonks-settings-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}' .
            '.emonks-settings-grid .full{grid-column:1/-1}' .
            '@media(max-width:1120px){.emonks-builder-shell{grid-template-columns:1fr}.emonks-builder-inspector{position:static}.emonks-field-type-grid{grid-template-columns:1fr}}'
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
        echo '<tr><th scope="row"><label for="module_guestbook">Module: Guestbook</label></th><td><label><input type="checkbox" id="module_guestbook" name="settings[modules][enabled][guestbook]" value="1" ' . checked((bool) ($settings['modules']['enabled']['guestbook'] ?? true), true, false) . ' /> Active</label><span class="description">Schakelt guestbook rendering en dashboard cards aan/uit.</span></td></tr>';
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

    public function renderFormBuilderPage(): void
    {
        echo '<div class="wrap">';
        $this->renderPageHeader('Emonks SaaS - Form Builder', 'Configureer dynamische formulieren en service-velden zonder JSON te schrijven.', 'De editor beheert dezelfde schema`s die runtime rendering, validatie en service data aansturen.');
        $this->renderSettingsFormStart('form_builder');
        $formSchemas = emonks_get_form_schemas();
        $serviceSchemas = emonks_get_service_schemas();

        $forms = is_array($formSchemas['forms'] ?? null) ? $formSchemas['forms'] : [];
        $services = is_array($serviceSchemas['services'] ?? null) ? $serviceSchemas['services'] : [];

        echo '<h2 class="nav-tab-wrapper" style="margin-bottom:14px;">';
        echo '<button type="button" class="nav-tab nav-tab-active" data-builder-mode="forms">Forms</button>';
        echo '<button type="button" class="nav-tab" data-builder-mode="services">Services</button>';
        echo '</h2>';

        echo '<div class="emonks-builder-shell">';
        echo '<div class="emonks-builder-main">';
        echo '<div class="emonks-builder-schemas" data-schema-switcher="forms">';
        $isFirst = true;
        foreach ($forms as $formKey => $form) {
            $safeKey = sanitize_key((string) $formKey);
            echo '<button type="button" class="button ' . ($isFirst ? 'button-primary' : '') . '" data-schema-target="form-' . esc_attr($safeKey) . '">' . esc_html($safeKey) . '</button>';
            $isFirst = false;
        }
        echo '</div>';
        echo '<div class="emonks-builder-schemas" data-schema-switcher="services" style="display:none;">';
        $isFirst = true;
        foreach ($services as $serviceKey => $service) {
            $safeKey = sanitize_key((string) $serviceKey);
            echo '<button type="button" class="button ' . ($isFirst ? 'button-primary' : '') . '" data-schema-target="service-' . esc_attr($safeKey) . '">' . esc_html($safeKey) . '</button>';
            $isFirst = false;
        }
        echo '</div>';

        $isFirst = true;
        foreach ($forms as $formKey => $form) {
            $this->renderBuilderSchemaPanel('form', sanitize_key((string) $formKey), is_array($form) ? $form : [], $isFirst);
            $isFirst = false;
        }
        $isFirst = true;
        foreach ($services as $serviceKey => $service) {
            $this->renderBuilderSchemaPanel('service', sanitize_key((string) $serviceKey), is_array($service) ? $service : [], $isFirst);
            $isFirst = false;
        }
        echo '</div>';

        echo '<div class="emonks-card emonks-builder-inspector">';
        echo '<div class="emonks-inspector-tabs">';
        echo '<button type="button" class="button button-primary" data-inspector-tab="fields">Velden</button>';
        echo '<button type="button" class="button" data-inspector-tab="settings">Instellingen</button>';
        echo '<button type="button" class="button" data-inspector-tab="advanced">Advanced JSON</button>';
        echo '</div>';

        echo '<div class="emonks-inspector-tab-panel is-active" data-inspector-panel="fields">';
        echo '<p class="emonks-subtle">Klik op een type om direct een veld toe te voegen aan het actieve schema.</p>';
        echo '<div class="emonks-field-type-grid">';
        foreach ($this->allowedFieldTypes() as $type) {
            echo '<button type="button" class="button" data-add-field-type="' . esc_attr($type) . '">' . esc_html(ucfirst($type)) . '</button>';
        }
        echo '</div></div>';

        echo '<div class="emonks-inspector-tab-panel" data-inspector-panel="settings">';
        echo '<div data-settings-empty><p class="description">Selecteer links een veld om instellingen te bewerken.</p></div>';
        echo '<div data-settings-container></div>';
        echo '</div>';

        echo '<div class="emonks-inspector-tab-panel" data-inspector-panel="advanced">';
        echo '<p><label><input type="checkbox" name="settings[use_advanced_json]" value="1" /> Advanced JSON gebruiken bij opslaan</label></p>';
        echo '<h3 style="margin-top:10px;">Form schemas JSON</h3>';
        echo '<textarea name="settings[form_schemas_json]" rows="10" style="width:100%;font-family:monospace;">' . esc_textarea(wp_json_encode($formSchemas, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</textarea>';
        echo '<h3>Service schemas JSON</h3>';
        echo '<textarea name="settings[service_schemas_json]" rows="10" style="width:100%;font-family:monospace;">' . esc_textarea(wp_json_encode($serviceSchemas, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</textarea>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        submit_button('Save Form Builder');
        $this->renderSettingsFormEnd();
        $this->renderFormBuilderScript();
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
            $current['modules']['enabled']['guestbook'] = isset($_POST['settings']['modules']['enabled']['guestbook']);
        } elseif ($tab === 'form_builder') {
            if (! empty($_POST['settings']['use_advanced_json'])) {
                $rawFormJson = (string) wp_unslash($_POST['settings']['form_schemas_json'] ?? '');
                $rawServiceJson = (string) wp_unslash($_POST['settings']['service_schemas_json'] ?? '');
                $decodedForms = json_decode($rawFormJson, true);
                $decodedServices = json_decode($rawServiceJson, true);
                if (is_array($decodedForms)) {
                    $current['form_schemas'] = $decodedForms;
                } else {
                    emonks_flash_add('settings_warning', 'Form schema JSON was ongeldig; bestaande waarden behouden.');
                }
                if (is_array($decodedServices)) {
                    $current['service_schemas'] = $decodedServices;
                } else {
                    emonks_flash_add('settings_warning', 'Service schema JSON was ongeldig; bestaande waarden behouden.');
                }
            } else {
                $formBuilderRaw = isset($_POST['form_builder']) ? wp_unslash($_POST['form_builder']) : [];
                $serviceBuilderRaw = isset($_POST['service_builder']) ? wp_unslash($_POST['service_builder']) : [];
                $current['form_schemas'] = $this->buildFormSchemasFromPost(is_array($formBuilderRaw) ? $formBuilderRaw : []);
                $current['service_schemas'] = $this->buildServiceSchemasFromPost(is_array($serviceBuilderRaw) ? $serviceBuilderRaw : []);
            }
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

    /** @param array<string,mixed> $schema */
    private function renderBuilderSchemaPanel(string $mode, string $schemaKey, array $schema, bool $active): void
    {
        $id = $mode . '-' . $schemaKey;
        $prefix = $mode === 'form' ? 'form_builder[forms][' . $schemaKey . ']' : 'service_builder[services][' . $schemaKey . ']';
        $fields = $this->schemaFields($schema);
        $meta = $this->schemaMetaSummary($mode, $schema);

        echo '<div class="emonks-builder-preview ' . ($active ? '' : 'is-hidden') . '" data-mode="' . esc_attr($mode === 'form' ? 'forms' : 'services') . '" data-schema-panel="' . esc_attr($id) . '" data-active-schema="' . ($active ? '1' : '0') . '"' . ($active ? '' : ' style="display:none;"') . '>';
        echo '<h3 style="margin-top:0;">' . esc_html($schemaKey) . '</h3>';
        echo '<p class="emonks-subtle" style="margin-top:4px;">' . esc_html($meta) . '</p>';
        echo '<input type="hidden" name="' . esc_attr($prefix . '[key]') . '" value="' . esc_attr($schemaKey) . '" />';
        if ($mode === 'form') {
            echo '<input type="hidden" name="' . esc_attr($prefix . '[submit_label]') . '" value="' . esc_attr((string) ($schema['submit_label'] ?? 'Opslaan')) . '" />';
            echo '<input type="hidden" name="' . esc_attr($prefix . '[action]') . '" value="' . esc_attr((string) ($schema['action'] ?? '')) . '" />';
            echo '<input type="hidden" name="' . esc_attr($prefix . '[nonce_action]') . '" value="' . esc_attr((string) ($schema['nonce_action'] ?? '')) . '" />';
        } else {
            $caps = is_array($schema['capabilities'] ?? null) ? implode(', ', array_map('strval', $schema['capabilities'])) : '';
            $renderHints = is_array($schema['render_hints'] ?? null) ? $schema['render_hints'] : [];
            echo '<input type="hidden" name="' . esc_attr($prefix . '[label]') . '" value="' . esc_attr((string) ($schema['label'] ?? $schemaKey)) . '" />';
            echo '<input type="hidden" name="' . esc_attr($prefix . '[capabilities]') . '" value="' . esc_attr($caps) . '" />';
            echo '<input type="hidden" name="' . esc_attr($prefix . '[template]') . '" value="' . esc_attr((string) ($renderHints['template'] ?? '')) . '" />';
        }

        echo '<div data-field-list="' . esc_attr($id) . '">';
        foreach ($fields as $index => $field) {
            $this->renderBuilderFieldRow($prefix . '[fields]', (string) $index, $field, $id, $index === 0);
        }
        echo '</div>';
        echo '<template data-field-template="' . esc_attr($id) . '">';
        $this->renderBuilderFieldRow($prefix . '[fields]', '__INDEX__', [], $id, false);
        echo '</template>';
        echo '</div>';
    }

    /** @param array<string,mixed> $schema */
    private function schemaMetaSummary(string $mode, array $schema): string
    {
        $fields = is_array($schema['fields'] ?? null) ? count($schema['fields']) : 0;
        if ($mode === 'form') {
            return sprintf('%d velden', $fields);
        }

        $template = '';
        if (is_array($schema['render_hints'] ?? null)) {
            $template = sanitize_text_field((string) ($schema['render_hints']['template'] ?? ''));
        }
        return $template !== '' ? sprintf('%d velden - %s', $fields, $template) : sprintf('%d velden', $fields);
    }

    /** @param array<string,mixed> $schema @return array<int,array<string,mixed>> */
    private function schemaFields(array $schema): array
    {
        $fields = is_array($schema['fields'] ?? null) ? $schema['fields'] : [];
        usort($fields, static fn($a, $b) => (int) ($a['order'] ?? 0) <=> (int) ($b['order'] ?? 0));
        return array_values(array_filter($fields, 'is_array'));
    }

    /** @param array<string,mixed> $field */
    private function renderBuilderFieldRow(string $namePrefix, string $index, array $field, string $schemaId, bool $selected): void
    {
        $key = (string) ($field['key'] ?? '');
        $label = (string) ($field['label'] ?? 'Nieuw veld');
        $type = sanitize_key((string) ($field['type'] ?? 'text'));
        $type = in_array($type, $this->allowedFieldTypes(), true) ? $type : 'text';
        $required = (bool) ($field['required'] ?? false);
        $order = (int) ($field['order'] ?? 10);
        $settingsId = $schemaId . '-field-' . preg_replace('/[^a-z0-9_\-]/i', '_', $index);
        $minLength = $this->fieldRuleValue($field, 'min_length');
        $maxLength = $this->fieldRuleValue($field, 'max_length');

        echo '<div class="emonks-builder-field' . ($selected ? ' is-selected' : '') . '" data-field-row data-settings-id="' . esc_attr($settingsId) . '">';
        echo '<div><strong>' . esc_html($label !== '' ? $label : 'Nieuw veld') . '</strong><div class="emonks-builder-field-meta"><span class="emonks-pill" data-meta-type>' . esc_html($type) . '</span><span data-meta-key>' . esc_html($key !== '' ? $key : 'zonder key') . '</span><span class="emonks-pill" data-meta-required' . ($required ? '' : ' style="display:none;"') . '>required</span></div></div>';
        echo '<div class="emonks-actions"><button type="button" class="button-link" data-move-field="up">Omhoog</button><button type="button" class="button-link" data-move-field="down">Omlaag</button><button type="button" class="button-link-delete" data-remove-field>Verwijder</button></div>';
        echo '<div class="emonks-field-settings' . ($selected ? ' is-active' : '') . '" data-settings-panel="' . esc_attr($settingsId) . '">';
        echo '<div class="emonks-settings-grid">';
        echo '<label class="full">Label<br><input type="text" class="regular-text" data-bind-label name="' . esc_attr($namePrefix . '[' . $index . '][label]') . '" value="' . esc_attr((string) ($field['label'] ?? '')) . '" /></label>';
        echo '<label>Key<br><input type="text" data-bind-key name="' . esc_attr($namePrefix . '[' . $index . '][key]') . '" value="' . esc_attr($key) . '" /></label>';
        echo '<label>Type<br><select data-field-type-select name="' . esc_attr($namePrefix . '[' . $index . '][type]') . '">';
        foreach ($this->allowedFieldTypes() as $fieldType) {
            echo '<option value="' . esc_attr($fieldType) . '" ' . selected($type, $fieldType, false) . '>' . esc_html($fieldType) . '</option>';
        }
        echo '</select></label>';
        echo '<label>Order<br><input data-order-input type="number" name="' . esc_attr($namePrefix . '[' . $index . '][order]') . '" value="' . esc_attr((string) $order) . '" /></label>';
        echo '<label><input type="checkbox" data-bind-required name="' . esc_attr($namePrefix . '[' . $index . '][required]') . '" value="1" ' . checked($required, true, false) . ' /> Required</label>';
        echo '<label class="full">Placeholder<br><input type="text" class="regular-text" name="' . esc_attr($namePrefix . '[' . $index . '][placeholder]') . '" value="' . esc_attr((string) ($field['placeholder'] ?? '')) . '" /></label>';
        echo '<label class="full">Help text<br><input type="text" class="regular-text" name="' . esc_attr($namePrefix . '[' . $index . '][help]') . '" value="' . esc_attr((string) ($field['help'] ?? '')) . '" /></label>';
        echo '<label class="full">Default value<br><input type="text" class="regular-text" name="' . esc_attr($namePrefix . '[' . $index . '][default]') . '" value="' . esc_attr((string) ($field['default'] ?? '')) . '" /></label>';
        echo '<label>Min length<br><input type="number" min="0" name="' . esc_attr($namePrefix . '[' . $index . '][min_length]') . '" value="' . esc_attr($minLength) . '" /></label>';
        echo '<label>Max length<br><input type="number" min="0" name="' . esc_attr($namePrefix . '[' . $index . '][max_length]') . '" value="' . esc_attr($maxLength) . '" /></label>';
        echo '<label class="full" data-options-field>Options<br><textarea rows="3" class="large-text" name="' . esc_attr($namePrefix . '[' . $index . '][options]') . '" placeholder="value|Label">' . esc_textarea($this->formatOptions($field)) . '</textarea><span class="description">Alleen relevant voor select. Gebruik een optie per regel: <code>waarde|Label</code>.</span></label>';
        echo '</div></div></div>';
    }

    /** @return array<int,string> */
    private function allowedFieldTypes(): array
    {
        return ['text', 'email', 'password', 'url', 'textarea', 'select', 'checkbox', 'hidden'];
    }

    /** @param array<string,mixed> $field */
    private function fieldRuleValue(array $field, string $ruleType): string
    {
        $rules = is_array($field['rules'] ?? null) ? $field['rules'] : [];
        foreach ($rules as $rule) {
            if (is_array($rule) && sanitize_key((string) ($rule['type'] ?? '')) === $ruleType && is_numeric($rule['value'] ?? null)) {
                return (string) (int) $rule['value'];
            }
        }

        return '';
    }

    /** @param array<string,mixed> $field */
    private function formatOptions(array $field): string
    {
        $options = is_array($field['options'] ?? null) ? $field['options'] : [];
        $lines = [];
        foreach ($options as $value => $label) {
            $lines[] = (string) $value . '|' . (string) $label;
        }

        return implode("\n", $lines);
    }

    private function renderFormBuilderScript(): void
    {
        echo '<script>
document.addEventListener("DOMContentLoaded", function () {
  function syncSettingsTarget(row) {
    var container = document.querySelector("[data-settings-container]");
    var emptyState = document.querySelector("[data-settings-empty]");
    if (!container) {
      return;
    }
    var activePanel = container.querySelector("[data-settings-panel]");
    if (activePanel) {
      var ownerId = activePanel.getAttribute("data-settings-panel");
      var ownerRow = ownerId ? document.querySelector("[data-field-row][data-settings-id=\"" + ownerId + "\"]") : null;
      if (ownerRow) {
        ownerRow.appendChild(activePanel);
      }
    }
    container.innerHTML = "";
    if (!row) {
      if (emptyState) {
        emptyState.style.display = "";
      }
      return;
    }
    var panel = row.querySelector("[data-settings-panel]");
    if (!panel) {
      return;
    }
    if (emptyState) {
      emptyState.style.display = "none";
    }
    panel.classList.remove("is-active");
    container.appendChild(panel);
  }

  function setSelectedRow(row) {
    document.querySelectorAll("[data-field-row]").forEach(function (item) { item.classList.remove("is-selected"); });
    if (row) {
      row.classList.add("is-selected");
      syncSettingsTarget(row);
    } else {
      syncSettingsTarget(null);
    }
  }

  function updateOptionsVisibility(context) {
    (context || document).querySelectorAll("[data-settings-panel], .emonks-field-settings").forEach(function (panel) {
      var typeSelect = panel.querySelector("[data-field-type-select]");
      var optionsField = panel.querySelector("[data-options-field]");
      if (typeSelect && optionsField) {
        optionsField.style.display = typeSelect.value === "select" ? "" : "none";
      }
    });
  }

  function refreshFieldOrders(list) {
    if (!list) {
      return;
    }
    list.querySelectorAll("[data-field-row]").forEach(function (row, index) {
      var orderInput = row.querySelector("[data-order-input]");
      if (orderInput) {
        orderInput.value = String((index + 1) * 10);
      }
    });
  }

  function firstVisibleRow() {
    var panel = document.querySelector("[data-schema-panel]:not([style*=\"display:none\"])");
    return panel ? panel.querySelector("[data-field-row]") : null;
  }

  function activateSchema(schemaId) {
    document.querySelectorAll("[data-schema-panel]").forEach(function (panel) {
      var isActive = panel.getAttribute("data-schema-panel") === schemaId;
      panel.style.display = isActive ? "" : "none";
      panel.setAttribute("data-active-schema", isActive ? "1" : "0");
    });
    document.querySelectorAll("[data-schema-target]").forEach(function (button) {
      button.classList.toggle("button-primary", button.getAttribute("data-schema-target") === schemaId);
    });
    setSelectedRow(firstVisibleRow());
  }

  function refreshRowMeta(row) {
    if (!row) {
      return;
    }
    var labelInput = row.querySelector("[data-bind-label]");
    var keyInput = row.querySelector("[data-bind-key]");
    var requiredInput = row.querySelector("[data-bind-required]");
    var typeInput = row.querySelector("[data-field-type-select]");
    var labelNode = row.querySelector("strong");
    var keyNode = row.querySelector("[data-meta-key]");
    var typeNode = row.querySelector("[data-meta-type]");
    var requiredNode = row.querySelector("[data-meta-required]");
    if (labelNode && labelInput) {
      labelNode.textContent = labelInput.value || "Nieuw veld";
    }
    if (keyNode && keyInput) {
      keyNode.textContent = keyInput.value || "zonder key";
    }
    if (typeNode && typeInput) {
      typeNode.textContent = typeInput.value || "text";
    }
    if (requiredNode && requiredInput) {
      requiredNode.style.display = requiredInput.checked ? "" : "none";
    }
  }

  function setInspectorTab(tabName) {
    document.querySelectorAll("[data-inspector-tab]").forEach(function (button) {
      button.classList.toggle("button-primary", button.getAttribute("data-inspector-tab") === tabName);
    });
    document.querySelectorAll("[data-inspector-panel]").forEach(function (panel) {
      panel.classList.toggle("is-active", panel.getAttribute("data-inspector-panel") === tabName);
    });
  }

  updateOptionsVisibility(document);
  document.querySelectorAll("[data-builder-mode]").forEach(function (tab) {
    tab.addEventListener("click", function () {
      var target = tab.getAttribute("data-builder-mode");
      document.querySelectorAll("[data-builder-mode]").forEach(function (item) { item.classList.remove("nav-tab-active"); });
      tab.classList.add("nav-tab-active");
      document.querySelectorAll("[data-schema-switcher]").forEach(function (switcher) {
        switcher.style.display = switcher.getAttribute("data-schema-switcher") === target ? "" : "none";
      });
      var activeButton = document.querySelector("[data-schema-switcher=\"" + target + "\"] [data-schema-target].button-primary") || document.querySelector("[data-schema-switcher=\"" + target + "\"] [data-schema-target]");
      if (activeButton) {
        activateSchema(activeButton.getAttribute("data-schema-target"));
      } else {
        setSelectedRow(null);
      }
    });
  });

  document.querySelectorAll("[data-schema-target]").forEach(function (button) {
    button.addEventListener("click", function () {
      activateSchema(button.getAttribute("data-schema-target"));
    });
  });

  document.querySelectorAll("[data-inspector-tab]").forEach(function (button) {
    button.addEventListener("click", function () {
      setInspectorTab(button.getAttribute("data-inspector-tab"));
    });
  });

  document.addEventListener("click", function (event) {
    var targetButton = event.target.closest("[data-schema-target]");
    if (targetButton) {
      activateSchema(targetButton.getAttribute("data-schema-target"));
      return;
    }
    var fieldRow = event.target.closest("[data-field-row]");
    if (fieldRow && !event.target.closest("[data-move-field]") && !event.target.closest("[data-remove-field]")) {
      setSelectedRow(fieldRow);
      setInspectorTab("settings");
    }
    var addTypeButton = event.target.closest("[data-add-field-type]");
    if (addTypeButton) {
      var activePanel = document.querySelector("[data-schema-panel][data-active-schema=\"1\"]");
      var fieldList = activePanel ? activePanel.querySelector("[data-field-list]") : null;
      var template = activePanel ? activePanel.querySelector("template[data-field-template]") : null;
      var fieldType = addTypeButton.getAttribute("data-add-field-type");
      if (fieldList && template) {
        fieldList.insertAdjacentHTML("beforeend", template.innerHTML.replaceAll("__INDEX__", "new_" + Date.now()));
        var row = fieldList.lastElementChild;
        if (row) {
          var typeSelect = row.querySelector("[data-field-type-select]");
          if (typeSelect && fieldType) {
            typeSelect.value = fieldType;
          }
          var keyInput = row.querySelector("[data-bind-key]");
          if (keyInput && fieldType) {
            keyInput.value = fieldType + "_" + (fieldList.children.length * 10);
          }
          var labelInput = row.querySelector("[data-bind-label]");
          if (labelInput && fieldType) {
            labelInput.value = fieldType.charAt(0).toUpperCase() + fieldType.slice(1);
          }
          var requiredInput = row.querySelector("[data-bind-required]");
          if (requiredInput) {
            requiredInput.checked = false;
          }
          refreshRowMeta(row);
          updateOptionsVisibility(row);
          refreshFieldOrders(fieldList);
          setSelectedRow(row);
          setInspectorTab("fields");
        }
      }
      return;
    }
    var removeButton = event.target.closest("[data-remove-field]");
    if (removeButton) {
      var card = removeButton.closest("[data-field-row]");
      if (card) {
        var parentList = card.parentElement;
        card.remove();
        refreshFieldOrders(parentList);
        setSelectedRow(firstVisibleRow());
      }
      return;
    }
    var moveButton = event.target.closest("[data-move-field]");
    if (moveButton) {
      var moveRow = moveButton.closest("[data-field-row]");
      var moveList = moveRow ? moveRow.parentElement : null;
      if (moveRow && moveList && moveButton.getAttribute("data-move-field") === "up" && moveRow.previousElementSibling) {
        moveList.insertBefore(moveRow, moveRow.previousElementSibling);
        refreshFieldOrders(moveList);
        setSelectedRow(moveRow);
      }
      if (moveRow && moveList && moveButton.getAttribute("data-move-field") === "down" && moveRow.nextElementSibling) {
        moveList.insertBefore(moveRow.nextElementSibling, moveRow);
        refreshFieldOrders(moveList);
        setSelectedRow(moveRow);
      }
      return;
    }
    var typeSelect = event.target.closest("[data-field-type-select]");
    if (typeSelect) {
      var typeRow = typeSelect.closest("[data-field-row]");
      refreshRowMeta(typeRow);
      updateOptionsVisibility(typeRow);
    }
  });

  document.addEventListener("input", function (event) {
    var labelInput = event.target.closest("[data-bind-label]");
    if (labelInput) {
      refreshRowMeta(labelInput.closest("[data-field-row]"));
    }
    var keyInput = event.target.closest("[data-bind-key]");
    if (keyInput) {
      refreshRowMeta(keyInput.closest("[data-field-row]"));
    }
    var requiredInput = event.target.closest("[data-bind-required]");
    if (requiredInput) {
      refreshRowMeta(requiredInput.closest("[data-field-row]"));
    }
  });

  var defaultSchemaButton = document.querySelector("[data-schema-switcher=\"forms\"] [data-schema-target].button-primary") || document.querySelector("[data-schema-switcher=\"forms\"] [data-schema-target]");
  if (defaultSchemaButton) {
    activateSchema(defaultSchemaButton.getAttribute("data-schema-target"));
  } else {
    var fallbackSchemaButton = document.querySelector("[data-schema-target]");
    if (fallbackSchemaButton) {
      activateSchema(fallbackSchemaButton.getAttribute("data-schema-target"));
    } else {
      setSelectedRow(null);
    }
  }
});
</script>';
    }

    /** @param array<string,mixed> $raw */
    private function buildFormSchemasFromPost(array $raw): array
    {
        $forms = [];
        $submittedForms = is_array($raw['forms'] ?? null) ? $raw['forms'] : [];
        foreach ($submittedForms as $key => $form) {
            if (! is_array($form)) {
                continue;
            }
            $formKey = sanitize_key((string) $key);
            if ($formKey === '') {
                continue;
            }
            $forms[$formKey] = [
                'key' => $formKey,
                'action' => sanitize_text_field((string) ($form['action'] ?? '')),
                'nonce_action' => sanitize_key((string) ($form['nonce_action'] ?? '')),
                'submit_label' => sanitize_text_field((string) ($form['submit_label'] ?? 'Opslaan')),
                'fields' => $this->sanitizeBuilderFields(is_array($form['fields'] ?? null) ? $form['fields'] : []),
            ];
        }

        return ['schema_version' => 1, 'forms' => $forms];
    }

    /** @param array<string,mixed> $raw */
    private function buildServiceSchemasFromPost(array $raw): array
    {
        $services = [];
        $submittedServices = is_array($raw['services'] ?? null) ? $raw['services'] : [];
        foreach ($submittedServices as $key => $service) {
            if (! is_array($service)) {
                continue;
            }
            $serviceKey = sanitize_key((string) $key);
            if ($serviceKey === '') {
                continue;
            }
            $services[$serviceKey] = [
                'key' => $serviceKey,
                'label' => sanitize_text_field((string) ($service['label'] ?? $serviceKey)),
                'capabilities' => $this->sanitizeCapabilities((string) ($service['capabilities'] ?? '')),
                'render_hints' => ['template' => sanitize_text_field((string) ($service['template'] ?? ''))],
                'fields' => $this->sanitizeBuilderFields(is_array($service['fields'] ?? null) ? $service['fields'] : []),
            ];
        }

        return ['schema_version' => 1, 'services' => $services];
    }

    /** @param array<string,mixed> $fields @return array<int,array<string,mixed>> */
    private function sanitizeBuilderFields(array $fields): array
    {
        $sanitized = [];
        foreach ($fields as $field) {
            if (! is_array($field)) {
                continue;
            }

            $key = sanitize_key((string) ($field['key'] ?? ''));
            if ($key === '') {
                emonks_flash_add('settings_warning', 'Een veld zonder geldige key is overgeslagen.');
                continue;
            }

            $type = sanitize_key((string) ($field['type'] ?? 'text'));
            if (! in_array($type, $this->allowedFieldTypes(), true)) {
                emonks_flash_add('settings_warning', sprintf('Veldtype "%s" is ongeldig; "%s" gebruikt nu text.', $type, $key));
                $type = 'text';
            }

            $next = [
                'key' => $key,
                'type' => $type,
                'label' => sanitize_text_field((string) ($field['label'] ?? $key)),
                'required' => ! empty($field['required']),
                'placeholder' => sanitize_text_field((string) ($field['placeholder'] ?? '')),
                'help' => sanitize_text_field((string) ($field['help'] ?? '')),
                'default' => sanitize_text_field((string) ($field['default'] ?? '')),
                'order' => (int) ($field['order'] ?? 10),
                'rules' => $this->sanitizeFieldRules($field),
            ];

            $options = $this->parseOptions((string) ($field['options'] ?? ''));
            if (! empty($options)) {
                $next['options'] = $options;
            }

            $sanitized[] = $next;
        }

        usort($sanitized, static fn($a, $b) => (int) ($a['order'] ?? 0) <=> (int) ($b['order'] ?? 0));
        return array_values($sanitized);
    }

    /** @param array<string,mixed> $field @return array<int,array{type:string,value:int}> */
    private function sanitizeFieldRules(array $field): array
    {
        $rules = [];
        if (isset($field['min_length']) && is_numeric($field['min_length']) && (int) $field['min_length'] > 0) {
            $rules[] = ['type' => 'min_length', 'value' => (int) $field['min_length']];
        }
        if (isset($field['max_length']) && is_numeric($field['max_length']) && (int) $field['max_length'] > 0) {
            $rules[] = ['type' => 'max_length', 'value' => (int) $field['max_length']];
        }

        return $rules;
    }

    /** @return array<string,string> */
    private function parseOptions(string $rawOptions): array
    {
        $options = [];
        foreach (preg_split('/\R/', $rawOptions) ?: [] as $line) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }
            [$value, $label] = array_pad(explode('|', $line, 2), 2, $line);
            $value = sanitize_key($value);
            $label = sanitize_text_field($label);
            if ($value !== '') {
                $options[$value] = $label !== '' ? $label : $value;
            }
        }

        return $options;
    }

    /** @return array<int,string> */
    private function sanitizeCapabilities(string $rawCapabilities): array
    {
        $capabilities = [];
        foreach (explode(',', $rawCapabilities) as $capability) {
            $capability = sanitize_key(trim($capability));
            if ($capability !== '') {
                $capabilities[] = $capability;
            }
        }

        return array_values(array_unique($capabilities));
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
