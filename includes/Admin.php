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
        add_submenu_page('emonks-saas-core', 'Setup', 'Setup', 'manage_options', 'emonks-saas-setup', [$this, 'renderSetupPage']);
        add_submenu_page('emonks-saas-core', 'Services', 'Services', 'manage_options', 'emonks-saas-services', [$this, 'renderServicesPage']);
        add_submenu_page('emonks-saas-core', 'Fields', 'Fields', 'manage_options', 'emonks-saas-fields', [$this, 'renderFieldsPage']);
        add_submenu_page('emonks-saas-core', 'Forms', 'Forms', 'manage_options', 'emonks-saas-forms', [$this, 'renderFormsPage']);
        add_submenu_page('emonks-saas-core', 'Shortcodes', 'Shortcodes', 'manage_options', 'emonks-saas-shortcodes', [$this, 'renderShortcodesPage']);
        add_submenu_page('emonks-saas-core', 'Features', 'Features', 'manage_options', 'emonks-saas-features', [$this, 'renderFeaturesPage']);
        add_submenu_page('emonks-saas-core', 'Plans', 'Plans', 'manage_options', 'emonks-saas-plans', [$this, 'renderPlansPage']);
        add_submenu_page('emonks-saas-core', 'Billing', 'Billing', 'manage_options', 'emonks-saas-billing', [$this, 'renderBillingPage']);
        add_submenu_page('emonks-saas-core', 'Workspaces', 'Workspaces', 'manage_options', 'edit.php?post_type=emonks_workspace');
        add_submenu_page('emonks-saas-core', 'SaaS Health', 'SaaS Health', 'manage_options', 'emonks-saas-health', [$this, 'renderSaasHealthPage']);
        add_submenu_page('emonks-saas-core', 'Logs', 'Logs', 'manage_options', 'emonks-saas-logs', [$this, 'renderLogsPage']);
        add_submenu_page('emonks-saas-core', 'Settings', 'Settings', 'manage_options', 'emonks-saas-settings', [$this, 'renderSettingsPage']);
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
            '.emonks-inline-remove{border:0;background:transparent;color:#b32d2e;padding:0;margin:0;line-height:1.4;font:inherit;cursor:pointer;text-decoration:underline;text-underline-offset:2px}' .
            '.emonks-inline-remove:hover,.emonks-inline-remove:focus{color:#8a2424;border:0;background:transparent;outline:none;box-shadow:none}' .
            '.emonks-settings-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}' .
            '.emonks-settings-grid .full{grid-column:1/-1}' .
            '.emonks-services-shell{display:grid;grid-template-columns:340px minmax(0,1fr);gap:18px;align-items:start}' .
            '.emonks-services-sidebar{position:sticky;top:46px}' .
            '.emonks-services-sidebar .regular-text,.emonks-services-sidebar select,.emonks-services-sidebar input[type="text"],.emonks-services-sidebar input[type="number"]{width:100%;max-width:100%;box-sizing:border-box}' .
            '.emonks-service-item{display:flex;justify-content:space-between;align-items:center;gap:8px;width:100%;max-width:100%;box-sizing:border-box;padding:9px 10px;border:1px solid #dcdcde;border-radius:8px;background:#fff;color:#1d2327;text-decoration:none;margin-bottom:8px;overflow:hidden}' .
            '.emonks-service-item > span:first-child{min-width:0}' .
            '.emonks-service-item .emonks-subtle{display:block;word-break:break-word}' .
            '.emonks-service-item:hover{border-color:#2271b1}.emonks-service-item.is-active{border-color:#2271b1;background:#eef6ff}' .
            '.emonks-service-meta{display:flex;gap:6px;align-items:center}.emonks-service-form{display:none}.emonks-service-form.is-open{display:block}' .
            '.emonks-danger-zone{margin-top:24px;padding-top:14px;border-top:1px solid #dcdcde}' .
            '.emonks-tab-panel{display:none}.emonks-tab-panel.is-active{display:block}' .
            '.emonks-editor-section{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px;margin-bottom:12px}' .
            '.emonks-editor-title{margin:0 0 10px;font-size:13px;line-height:1.3;letter-spacing:.02em;text-transform:uppercase;color:#334155}' .
            '.emonks-editor-form p{margin:0}' .
            '.emonks-editor-form label{display:block;font-weight:600;color:#1e293b}' .
            '.emonks-editor-form .regular-text,.emonks-editor-form select,.emonks-editor-form input[type="text"],.emonks-editor-form input[type="number"]{width:100%;max-width:100%;margin-top:6px}' .
            '.emonks-editor-form .checkbox-row{display:flex;align-items:center;gap:8px;padding-top:10px}' .
            '.emonks-editor-form .checkbox-row label{display:flex;align-items:center;gap:8px;font-weight:500;margin:0}' .
            '@media(max-width:1120px){.emonks-builder-shell{grid-template-columns:1fr}.emonks-builder-inspector{position:static}.emonks-field-type-grid{grid-template-columns:1fr}.emonks-services-shell{grid-template-columns:1fr}.emonks-services-sidebar{position:static}}'
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

    public function renderSettingsPage(): void
    {
        $settings = get_option(Settings::OPTION_KEY, []);
        $templateBackgroundClass = sanitize_html_class((string) ($settings['templates']['background_class'] ?? 'bg-light'));
        $templateBackgroundOptions = [
            'bg-light' => 'bg-light',
            'bg-dark' => 'bg-dark',
            'bg-gradient-dark' => 'bg-gradient-dark',
            'bg-gradient-light' => 'bg-gradient-light',
            'bg-primary' => 'bg-primary',
            'bg-greylight' => 'bg-greylight',
            'bg-transparent' => 'bg-transparent',
        ];
        if (! array_key_exists($templateBackgroundClass, $templateBackgroundOptions)) {
            $templateBackgroundClass = 'bg-light';
        }
        echo '<div class="wrap">';
        $this->renderPageHeader('Emonks SaaS - Settings', 'Algemene instellingen voor branding, debug en technische defaults.', 'Services en formulieren beheer je niet hier, maar in Services, Fields en Forms.');

        $this->renderSettingsFormStart('settings');
        echo '<table class="form-table emonks-form-table">';
        echo '<tr><th scope="row"><label for="branding_name">Branding Name</label></th><td><input name="settings[branding][name]" id="branding_name" class="regular-text" value="' . esc_attr((string) ($settings['branding']['name'] ?? 'Emonks')) . '" /><span class="description">Wordt gebruikt als standaard productnaam in plugin UI context.</span></td></tr>';
        echo '<tr><th scope="row"><label for="debug_enabled">Debug Logging</label></th><td><label><input type="checkbox" id="debug_enabled" name="settings[debug][enabled]" value="1" ' . checked((bool) ($settings['debug']['enabled'] ?? false), true, false) . ' /> Enable debug logging to PHP error log when WP_DEBUG=true</label><span class="description">Gebruik in combinatie met het Logs-tabblad voor snellere troubleshooting.</span></td></tr>';
        echo '<tr><th scope="row"><label for="templates_background_class">Template achtergrond</label></th><td><select name="settings[templates][background_class]" id="templates_background_class">';
        foreach ($templateBackgroundOptions as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($templateBackgroundClass, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select><span class="description">Wordt toegepast op alle account/public templates via de section-class.</span></td></tr>';
        echo '</table>';
        submit_button('Save Settings');
        $this->renderSettingsFormEnd();
        echo '</div>';
    }

    public function renderGeneralPage(): void
    {
        $this->renderSettingsPage();
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

    public function renderShortcodesPage(): void
    {
        $catalog = class_exists(Shortcodes::class) ? Shortcodes::catalog() : [];

        echo '<div class="wrap">';
        $this->renderPageHeader('Emonks SaaS - Shortcodes', 'Gebruik plugin-formulieren en accountblokken direct in pagina’s of Twig content.', 'Beheer formulieren in Forms en velden in Fields. Shortcodes lezen die configuratie automatisch uit.');

        echo '<div class="emonks-card">';
        echo '<h2 style="margin-top:0;">Beschikbare shortcodes</h2>';
        echo '<table class="widefat striped"><thead><tr><th>Shortcode</th><th>Voorbeeld</th><th>Beschrijving</th></tr></thead><tbody>';
        if (empty($catalog)) {
            echo '<tr><td colspan="3">Geen shortcodes gevonden.</td></tr>';
        } else {
            foreach ($catalog as $row) {
                if (! is_array($row)) {
                    continue;
                }
                echo '<tr>';
                echo '<td><code>[' . esc_html((string) ($row['tag'] ?? '')) . ']</code></td>';
                echo '<td><code>' . esc_html((string) ($row['example'] ?? '')) . '</code></td>';
                echo '<td>' . esc_html((string) ($row['description'] ?? '')) . '</td>';
                echo '</tr>';
            }
        }
        echo '</tbody></table>';
        echo '</div>';

        echo '<div class="emonks-grid" style="margin-top:16px;">';
        echo '<div class="emonks-card"><h3 style="margin-top:0;">Waar beheer ik dit?</h3><ul><li><strong>Fields:</strong> velddefinities en type/source.</li><li><strong>Forms:</strong> welke velden in welk formulier.</li><li><strong>Services:</strong> welke forms in welke context actief zijn.</li></ul></div>';
        echo '<div class="emonks-card"><h3 style="margin-top:0;">Gebruik in theme</h3><p>Plaats shortcode in pagina-content of render via WordPress:</p><p><code>&lt;?php echo do_shortcode(\'[emonks_form key="workspace_create"]\'); ?&gt;</code></p><p class="emonks-muted">Tip: houd presentatie in je Twig/theme en gebruik shortcodes voor snelle validatie/MVP-flow.</p></div>';
        echo '</div>';

        echo '</div>';
    }

    public function renderSetupPage(): void
    {
        $services = emonks_get_service_schemas()['services'] ?? [];
        $fields = emonks_get_field_library()['fields'] ?? [];
        $forms = emonks_get_form_templates()['forms'] ?? [];
        $defaultContextForms = $this->defaultServiceContextForms($forms);
        $plans = Plans::getPlans();

        echo '<div class="wrap">';
        $this->renderPageHeader('Emonks SaaS - Setup', 'Richt je SaaS fundament stap voor stap in.', 'Begin met een service, koppel velden via formulieren en maak daarna plannen/features commercieel.');
        echo '<div class="emonks-grid">';
        $this->renderKpiCard('Services', (string) count((array) $services));
        $this->renderKpiCard('Velden', (string) count((array) $fields));
        $this->renderKpiCard('Formulieren', (string) count((array) $forms));
        $this->renderKpiCard('Plannen', (string) count((array) $plans));
        echo '</div>';
        echo '<div class="emonks-card" style="margin-top:18px;"><h2 style="margin-top:0;">Aanbevolen volgorde</h2>';
        echo '<ol><li>Maak je eerste service aan.</li><li>Controleer of de standaard core velden genoeg zijn of voeg velden toe.</li><li>Stel formulieren samen en koppel ze aan service-contexten.</li><li>Maak features en plannen aan zodra de service logisch werkt.</li><li>Controleer SaaS Health voor ontbrekende koppelingen.</li></ol>';
        echo '<p><a class="button button-primary" href="' . esc_url(admin_url('admin.php?page=emonks-saas-services')) . '">Nieuwe service starten</a></p></div>';
        echo '</div>';
    }

    public function renderFieldsPage(): void
    {
        $library = emonks_get_field_library();
        $fields = is_array($library['fields'] ?? null) ? $library['fields'] : [];
        $acfGroups = emonks_get_acf_field_group_options();
        $activeFieldKey = sanitize_key((string) ($_GET['field'] ?? ''));
        if ($activeFieldKey === '' && ! empty($fields)) {
            $activeFieldKey = (string) array_key_first($fields);
        }
        $activeField = is_array($fields[$activeFieldKey] ?? null) ? $fields[$activeFieldKey] : [];

        echo '<div class="wrap">';
        $this->renderPageHeader('Emonks SaaS - Fields', 'Centrale veldbibliotheek voor alle services en formulieren.', 'Definieer velden eenmalig en gebruik ze daarna in meerdere formulieren.');
        echo '<div class="emonks-services-shell">';

        echo '<div class="emonks-card emonks-services-sidebar">';
        echo '<h2 style="margin-top:0;">Fields</h2>';
        echo '<p class="emonks-subtle">Kies een veld om rechts de instellingen te beheren.</p>';
        echo '<p><button type="button" class="button button-primary" data-toggle-field-create>Nieuw veld</button></p>';
        echo '<div class="emonks-service-form" data-field-create-form>';
        $this->renderFieldCreateForm();
        echo '</div>';
        echo '<div class="emonks-builder-list">';
        foreach ($fields as $fieldKey => $field) {
            if (! is_array($field)) {
                continue;
            }
            $safeKey = sanitize_key((string) $fieldKey);
            $label = sanitize_text_field((string) ($field['label'] ?? $safeKey));
            $type = sanitize_key((string) ($field['type'] ?? 'text'));
            $source = sanitize_key((string) ($field['source'] ?? 'plugin'));
            $url = admin_url('admin.php?page=emonks-saas-fields&field=' . rawurlencode($safeKey));
            echo '<a class="emonks-service-item ' . ($safeKey === $activeFieldKey ? 'is-active' : '') . '" href="' . esc_url($url) . '"><span><strong>' . esc_html($label) . '</strong><br><span class="emonks-subtle"><code>' . esc_html($safeKey) . '</code> - ' . esc_html($type) . ' - ' . esc_html($source) . '</span></span></a>';
        }
        echo '</div>';
        echo '</div>';

        echo '<div class="emonks-card">';
        if ($activeFieldKey === '' || empty($activeField)) {
            echo '<p>Selecteer links een veld of maak een nieuw veld aan.</p>';
            echo '</div></div></div>';
            echo '<script>document.addEventListener("DOMContentLoaded",function(){document.querySelectorAll("[data-toggle-field-create]").forEach(function(btn){btn.addEventListener("click",function(){document.querySelectorAll("[data-field-create-form]").forEach(function(p){p.classList.toggle("is-open")})})});});</script>';
            return;
        }

        $source = sanitize_key((string) ($activeField['source'] ?? 'plugin'));
        $type = sanitize_key((string) ($activeField['type'] ?? 'text'));
        $minLength = $this->fieldRuleValue($activeField, 'min_length');
        $maxLength = $this->fieldRuleValue($activeField, 'max_length');

        echo '<h2 style="margin-top:0;">' . esc_html((string) ($activeField['label'] ?? $activeFieldKey)) . ' <code>' . esc_html($activeFieldKey) . '</code></h2>';
        $this->renderSettingsFormStart('fields_builder');
        echo '<input type="hidden" name="fields_builder[action]" value="update" />';
        echo '<input type="hidden" name="fields_builder[field_key]" value="' . esc_attr($activeFieldKey) . '" />';
        echo '<div class="emonks-editor-form">';
        echo '<div class="emonks-editor-section"><h3 class="emonks-editor-title">Basis</h3><div class="emonks-settings-grid">';
        echo '<p><label>Label<input type="text" name="fields_builder[field][label]" class="regular-text" value="' . esc_attr((string) ($activeField['label'] ?? $activeFieldKey)) . '" /></label></p>';
        echo '<p><label>Type<select name="fields_builder[field][type]">';
        foreach ($this->allowedFieldTypes() as $allowedType) {
            echo '<option value="' . esc_attr($allowedType) . '" ' . selected($type, $allowedType, false) . '>' . esc_html($allowedType) . '</option>';
        }
        echo '</select></label></p>';
        echo '<p><label>Source<select name="fields_builder[field][source]"><option value="plugin" ' . selected($source, 'plugin', false) . '>plugin</option><option value="acf" ' . selected($source, 'acf', false) . '>acf</option><option value="computed" ' . selected($source, 'computed', false) . '>computed</option></select></label></p>';
        echo '<p class="checkbox-row"><label><input type="checkbox" name="fields_builder[field][required]" value="1" ' . checked((bool) ($activeField['required'] ?? false), true, false) . ' /> Required</label></p>';
        echo '</div></div>';

        echo '<div class="emonks-editor-section"><h3 class="emonks-editor-title">Input Gedrag</h3><div class="emonks-settings-grid">';
        echo '<p><label>Placeholder<input type="text" name="fields_builder[field][placeholder]" class="regular-text" value="' . esc_attr((string) ($activeField['placeholder'] ?? '')) . '" /></label></p>';
        echo '<p><label>Help text<input type="text" name="fields_builder[field][help]" class="regular-text" value="' . esc_attr((string) ($activeField['help'] ?? '')) . '" /></label></p>';
        echo '<p><label>Default value<input type="text" name="fields_builder[field][default]" class="regular-text" value="' . esc_attr((string) ($activeField['default'] ?? '')) . '" /></label></p>';
        echo '<p><label>Min length<input type="number" min="0" name="fields_builder[field][min_length]" value="' . esc_attr($minLength) . '" /></label></p>';
        echo '<p><label>Max length<input type="number" min="0" name="fields_builder[field][max_length]" value="' . esc_attr($maxLength) . '" /></label></p>';
        echo '</div></div>';

        echo '<div class="emonks-editor-section"><h3 class="emonks-editor-title">ACF Koppeling</h3><div class="emonks-settings-grid">';
        echo '<p><label>ACF field group<select name="fields_builder[field][acf_group_key]"><option value="">Geen</option>';
        foreach ($acfGroups as $groupKey => $groupLabel) {
            echo '<option value="' . esc_attr($groupKey) . '" ' . selected((string) ($activeField['acf_group_key'] ?? ''), (string) $groupKey, false) . '>' . esc_html($groupLabel) . '</option>';
        }
        echo '</select></label></p>';
        echo '<p><label>ACF field key<input type="text" name="fields_builder[field][acf_field_key]" class="regular-text" value="' . esc_attr((string) ($activeField['acf_field_key'] ?? '')) . '" /></label></p>';
        echo '</div></div>';
        echo '</div>';
        submit_button('Veld opslaan');
        $this->renderSettingsFormEnd();

        echo '<div class="emonks-danger-zone"><h3>Veld verwijderen</h3><p class="description">Verwijdert dit veld uit de centrale veldbibliotheek.</p>';
        $this->renderSettingsFormStart('fields_builder');
        echo '<input type="hidden" name="fields_builder[action]" value="delete" />';
        echo '<input type="hidden" name="fields_builder[field_key]" value="' . esc_attr($activeFieldKey) . '" />';
        echo '<button type="submit" class="button button-link-delete" onclick="return confirm(\'Weet je zeker dat je dit veld wilt verwijderen?\')">Veld verwijderen</button>';
        $this->renderSettingsFormEnd();
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '<script>document.addEventListener("DOMContentLoaded",function(){document.querySelectorAll("[data-toggle-field-create]").forEach(function(btn){btn.addEventListener("click",function(){document.querySelectorAll("[data-field-create-form]").forEach(function(p){p.classList.toggle("is-open")})})});});</script>';
    }

    private function renderFieldCreateForm(): void
    {
        $this->renderSettingsFormStart('fields_builder');
        echo '<input type="hidden" name="fields_builder[action]" value="create" />';
        echo '<p><label>Key<br><input type="text" name="fields_builder[new][key]" class="regular-text" placeholder="bijv contact_email" /></label></p>';
        echo '<p><label>Label<br><input type="text" name="fields_builder[new][label]" class="regular-text" /></label></p>';
        echo '<p><label>Type<br><select name="fields_builder[new][type]">';
        foreach ($this->allowedFieldTypes() as $type) {
            echo '<option value="' . esc_attr($type) . '">' . esc_html($type) . '</option>';
        }
        echo '</select></label></p>';
        submit_button('Veld aanmaken', 'secondary', 'submit', false);
        $this->renderSettingsFormEnd();
    }

    public function renderFormsPage(): void
    {
        $forms = emonks_get_form_templates()['forms'] ?? [];
        $fields = emonks_get_field_library()['fields'] ?? [];
        $activeFormKey = sanitize_key((string) ($_GET['form'] ?? ''));
        if ($activeFormKey === '' && ! empty($forms)) {
            $activeFormKey = (string) array_key_first($forms);
        }
        $activeForm = is_array($forms[$activeFormKey] ?? null) ? $forms[$activeFormKey] : [];

        echo '<div class="wrap">';
        $this->renderPageHeader('Emonks SaaS - Forms', 'Stel formulieren samen met velden uit de centrale bibliotheek.', 'Formulieren zijn herbruikbare templates; services bepalen waar ze gebruikt worden.');
        echo '<div class="emonks-services-shell">';
        echo '<div class="emonks-card emonks-services-sidebar">';
        echo '<h2 style="margin-top:0;">Forms</h2>';
        echo '<p class="emonks-subtle">Kies een formulier om rechts de velden te beheren.</p>';
        echo '<p><button type="button" class="button button-primary" data-toggle-form-create>Nieuw formulier</button></p>';
        echo '<div class="emonks-service-form" data-form-create-form>';
        $this->renderFormCreateForm();
        echo '</div>';
        echo '<div class="emonks-builder-list">';
        foreach ($forms as $formKey => $form) {
            if (! is_array($form)) {
                continue;
            }
            $safeKey = sanitize_key((string) $formKey);
            $label = sanitize_text_field((string) ($form['label'] ?? $safeKey));
            $fieldCount = is_array($form['field_refs'] ?? null) ? count($form['field_refs']) : 0;
            $scope = sanitize_key((string) ($form['scope'] ?? 'service'));
            $url = admin_url('admin.php?page=emonks-saas-forms&form=' . rawurlencode($safeKey));
            echo '<a class="emonks-service-item ' . ($safeKey === $activeFormKey ? 'is-active' : '') . '" href="' . esc_url($url) . '"><span><strong>' . esc_html($label) . '</strong><br><span class="emonks-subtle"><code>' . esc_html($safeKey) . '</code> - ' . esc_html((string) $fieldCount) . ' velden - ' . esc_html($scope) . '</span></span></a>';
        }
        echo '</div>';
        echo '</div>';

        echo '<div class="emonks-card">';
        if ($activeFormKey === '' || empty($activeForm)) {
            echo '<p>Selecteer links een formulier of maak een nieuw formulier aan.</p>';
            echo '</div></div></div>';
            echo '<script>document.addEventListener("DOMContentLoaded",function(){document.querySelectorAll("[data-toggle-form-create]").forEach(function(btn){btn.addEventListener("click",function(){document.querySelectorAll("[data-form-create-form]").forEach(function(p){p.classList.toggle("is-open")})})});});</script>';
            return;
        }

        $refs = is_array($activeForm['field_refs'] ?? null) ? $activeForm['field_refs'] : [];
        $selected = [];
        $selectedOrders = [];
        foreach ($refs as $ref) {
            if (is_array($ref)) {
                $fieldKey = sanitize_key((string) ($ref['field_key'] ?? ''));
                if ($fieldKey === '') {
                    continue;
                }
                $selected[] = $fieldKey;
                $selectedOrders[$fieldKey] = (int) ($ref['order'] ?? 10);
            }
        }

        echo '<h2 style="margin-top:0;">' . esc_html((string) ($activeForm['label'] ?? $activeFormKey)) . ' <code>' . esc_html($activeFormKey) . '</code></h2>';
        $this->renderSettingsFormStart('forms_builder');
        echo '<input type="hidden" name="forms_builder[action]" value="update" />';
        echo '<input type="hidden" name="forms_builder[form_key]" value="' . esc_attr($activeFormKey) . '" />';
        echo '<p><label>Label<br><input type="text" name="forms_builder[form][label]" class="regular-text" value="' . esc_attr((string) ($activeForm['label'] ?? $activeFormKey)) . '" /></label></p>';
        echo '<p><label>Scope<br><select name="forms_builder[form][scope]">';
        foreach (['core', 'service', 'account', 'public'] as $scope) {
            echo '<option value="' . esc_attr($scope) . '" ' . selected((string) ($activeForm['scope'] ?? 'service'), $scope, false) . '>' . esc_html($scope) . '</option>';
        }
        echo '</select></label></p>';
        echo '<p><label>Submit label<br><input type="text" name="forms_builder[form][submit_label]" value="' . esc_attr((string) ($activeForm['submit_label'] ?? 'Opslaan')) . '" /></label></p>';
        $availableFieldOptions = '';
        foreach ($fields as $fieldKey => $field) {
            $safeFieldKey = sanitize_key((string) $fieldKey);
            if ($safeFieldKey === '' || in_array($safeFieldKey, $selected, true)) {
                continue;
            }
            $fieldLabel = sanitize_text_field((string) ($field['label'] ?? $safeFieldKey));
            $availableFieldOptions .= '<option value="' . esc_attr($safeFieldKey) . '" data-label="' . esc_attr($fieldLabel) . '">' . esc_html($fieldLabel) . ' (' . esc_html($safeFieldKey) . ')</option>';
        }
        echo '<fieldset><legend>Velden</legend>';
        echo '<p><label>Veld toevoegen<br><select data-add-field-select><option value="">Kies veld</option>' . $availableFieldOptions . '</select></label> <button type="button" class="button" data-add-field-button>Toevoegen</button></p>';
        echo '<table class="widefat striped"><thead><tr><th style="width:44px;"></th><th>Veld</th><th style="width:130px;">Acties</th></tr></thead><tbody data-form-fields-sortable>';
        foreach ($selected as $fieldKey) {
            $fieldKey = sanitize_key((string) $fieldKey);
            if ($fieldKey === '') {
                continue;
            }
            $field = is_array($fields[$fieldKey] ?? null) ? $fields[$fieldKey] : [];
            $orderValue = (int) ($selectedOrders[$fieldKey] ?? 9990);
            $fieldLabel = sanitize_text_field((string) ($field['label'] ?? $fieldKey));
            echo '<tr draggable="true" data-field-key="' . esc_attr($fieldKey) . '" data-field-label="' . esc_attr($fieldLabel) . '">';
            echo '<td><button type="button" class="button-link" data-drag-handle title="Sleep om te sorteren">::</button></td>';
            echo '<td>' . esc_html($fieldLabel) . ' <code>' . esc_html($fieldKey) . '</code><input type="hidden" name="forms_builder[form][field_refs][' . esc_attr($fieldKey) . '][enabled]" value="1" /><input type="hidden" data-order-input name="forms_builder[form][field_refs][' . esc_attr($fieldKey) . '][order]" value="' . esc_attr((string) $orderValue) . '" /></td>';
            echo '<td><button type="button" class="emonks-inline-remove" data-remove-field>Verwijderen</button></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '</fieldset>';
        submit_button('Form opslaan');
        $this->renderSettingsFormEnd();

        echo '<div class="emonks-danger-zone"><h3>Formulier verwijderen</h3><p class="description">Verwijdert dit formulier uit de template-catalogus.</p>';
        $this->renderSettingsFormStart('forms_builder');
        echo '<input type="hidden" name="forms_builder[action]" value="delete" />';
        echo '<input type="hidden" name="forms_builder[form_key]" value="' . esc_attr($activeFormKey) . '" />';
        echo '<button type="submit" class="button button-link-delete" onclick="return confirm(\'Weet je zeker dat je dit formulier wilt verwijderen?\')">Formulier verwijderen</button>';
        $this->renderSettingsFormEnd();
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo <<<'HTML'
<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll("[data-toggle-form-create]").forEach(function (btn) {
        btn.addEventListener("click", function () {
            document.querySelectorAll("[data-form-create-form]").forEach(function (panel) {
                panel.classList.toggle("is-open");
            });
        });
    });

    var tbody = document.querySelector("[data-form-fields-sortable]");
    if (!tbody) {
        return;
    }

    var addSelect = document.querySelector("[data-add-field-select]");
    var addButton = document.querySelector("[data-add-field-button]");
    var dragging = null;

    function renumber() {
        var i = 10;
        tbody.querySelectorAll("tr").forEach(function (row) {
            var input = row.querySelector("[data-order-input]");
            if (input) {
                input.value = String(i);
                i += 10;
            }
        });
    }

    function escHtml(value) {
        return String(value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function bindRow(row) {
        row.addEventListener("dragstart", function () {
            dragging = row;
            row.style.opacity = ".55";
        });

        row.addEventListener("dragend", function () {
            row.style.opacity = "";
            dragging = null;
            renumber();
        });

        row.addEventListener("dragover", function (e) {
            e.preventDefault();
        });

        row.addEventListener("drop", function (e) {
            e.preventDefault();
            if (!dragging || dragging === row) {
                return;
            }

            var rect = row.getBoundingClientRect();
            var after = (e.clientY - rect.top) > (rect.height / 2);
            if (after) {
                row.parentNode.insertBefore(dragging, row.nextSibling);
            } else {
                row.parentNode.insertBefore(dragging, row);
            }
            renumber();
        });

        var removeBtn = row.querySelector("[data-remove-field]");
        if (removeBtn) {
            removeBtn.addEventListener("click", function () {
                if (addSelect) {
                    var key = row.getAttribute("data-field-key") || "";
                    var label = row.getAttribute("data-field-label") || key;
                    if (key !== "") {
                        var option = document.createElement("option");
                        option.value = key;
                        option.textContent = label + " (" + key + ")";
                        option.setAttribute("data-label", label);
                        addSelect.appendChild(option);
                    }
                }
                row.remove();
                renumber();
            });
        }
    }

    function buildRow(key, label) {
        var tr = document.createElement("tr");
        tr.setAttribute("draggable", "true");
        tr.setAttribute("data-field-key", key);
        tr.setAttribute("data-field-label", label);
        tr.innerHTML = '<td><button type="button" class="button-link" data-drag-handle title="Sleep om te sorteren">::</button></td>'
            + '<td>' + escHtml(label) + ' <code>' + escHtml(key) + '</code>'
            + '<input type="hidden" name="forms_builder[form][field_refs][' + escHtml(key) + '][enabled]" value="1" />'
            + '<input type="hidden" data-order-input name="forms_builder[form][field_refs][' + escHtml(key) + '][order]" value="9990" /></td>'
            + '<td><button type="button" class="emonks-inline-remove" data-remove-field>Verwijderen</button></td>';
        return tr;
    }

    tbody.querySelectorAll("tr").forEach(function (row) {
        bindRow(row);
    });

    if (addButton && addSelect) {
        addButton.addEventListener("click", function () {
            var key = addSelect.value;
            if (!key) {
                return;
            }
            var selectedOption = addSelect.options[addSelect.selectedIndex];
            var label = selectedOption.getAttribute("data-label") || key;
            var row = buildRow(key, label);
            tbody.appendChild(row);
            bindRow(row);
            addSelect.remove(addSelect.selectedIndex);
            addSelect.value = "";
            renumber();
        });
    }

    renumber();
});
</script>
HTML;
        echo '</div>';
    }

    private function renderFormCreateForm(): void
    {
        $this->renderSettingsFormStart('forms_builder');
        echo '<input type="hidden" name="forms_builder[action]" value="create" />';
        echo '<p><label>Key<br><input type="text" name="forms_builder[new][key]" class="regular-text" placeholder="bijv intake" /></label></p>';
        echo '<p><label>Label<br><input type="text" name="forms_builder[new][label]" class="regular-text" /></label></p>';
        echo '<p><label>Scope<br><select name="forms_builder[new][scope]">';
        foreach (['service', 'core', 'account', 'public'] as $scope) {
            echo '<option value="' . esc_attr($scope) . '">' . esc_html($scope) . '</option>';
        }
        echo '</select></label></p>';
        submit_button('Formulier aanmaken', 'secondary', 'submit', false);
        $this->renderSettingsFormEnd();
    }

    public function renderServicesPage(): void
    {
        $schemas = emonks_get_service_schemas();
        $services = is_array($schemas['services'] ?? null) ? $schemas['services'] : [];
        $forms = emonks_get_form_templates()['forms'] ?? [];
        $featuresTerms = get_terms(['taxonomy' => 'emonks_feature', 'hide_empty' => false]);
        $acfGroups = emonks_get_acf_field_group_options();
        $serviceKey = sanitize_key((string) ($_GET['service'] ?? ''));
        $activeService = is_array($services[$serviceKey] ?? null) ? $services[$serviceKey] : [];

        echo '<div class="wrap">';
        $this->renderPageHeader('Emonks SaaS - Services', 'Maak en beheer dynamische services zonder hardcoded modules.', 'Een service bepaalt welke formulieren, features, publicatie-instellingen en datadefaults worden gebruikt.');

        if (empty($services)) {
            echo '<div class="emonks-card"><h2 style="margin-top:0;">Je hebt nog geen services aangemaakt.</h2><p>Maak je eerste service om workspaces, formulieren en publicatie te configureren.</p>';
            echo '<button type="button" class="button button-primary" data-toggle-service-create>Nieuwe service</button>';
            echo '<div class="emonks-service-form" data-service-create-form style="margin-top:14px;">';
            $this->renderServiceCreateForm();
            echo '</div></div>';
            echo '<script>document.addEventListener("DOMContentLoaded",function(){document.querySelectorAll("[data-toggle-service-create]").forEach(function(btn){btn.addEventListener("click",function(){document.querySelectorAll("[data-service-create-form]").forEach(function(p){p.classList.toggle("is-open")})})});});</script>';
            echo '</div>';
            return;
        }

        if ($serviceKey === '' || empty($activeService)) {
            $serviceKey = (string) array_key_first($services);
            $activeService = is_array($services[$serviceKey] ?? null) ? $services[$serviceKey] : [];
        }

        echo '<div class="emonks-services-shell">';
        echo '<div class="emonks-card emonks-services-sidebar"><h2 style="margin-top:0;">Services</h2><p><button type="button" class="button button-primary" data-toggle-service-create>Nieuwe service</button></p><div class="emonks-service-form" data-service-create-form>';
        $this->renderServiceCreateForm();
        echo '</div><div class="emonks-builder-list">';
        foreach ($services as $key => $schema) {
            if (! is_array($schema)) {
                continue;
            }
            $label = sanitize_text_field((string) ($schema['label'] ?? $key));
            $status = sanitize_key((string) ($schema['status'] ?? 'draft'));
            $usages = is_array($schema['form_usages'] ?? null) ? count($schema['form_usages']) : 0;
            $featureCount = is_array($schema['features'] ?? null) ? count($schema['features']) : 0;
            $url = admin_url('admin.php?page=emonks-saas-services&service=' . rawurlencode((string) $key));
            echo '<a class="emonks-service-item ' . ($key === $serviceKey ? 'is-active' : '') . '" href="' . esc_url($url) . '"><span><strong>' . esc_html($label) . '</strong><br><span class="emonks-subtle">' . esc_html((string) $key) . ' - ' . esc_html((string) $usages) . ' forms - ' . esc_html((string) $featureCount) . ' features</span></span><span class="emonks-service-meta"><span class="emonks-pill">' . esc_html($status) . '</span></span></a>';
        }
        echo '</div></div>';

        $featureKeys = is_array($activeService['features'] ?? null) ? $activeService['features'] : [];
        $status = sanitize_key((string) ($activeService['status'] ?? 'draft'));
        $fieldSource = sanitize_key((string) ($activeService['field_source_default'] ?? 'plugin'));
        $renderHints = is_array($activeService['render_hints'] ?? null) ? $activeService['render_hints'] : [];
        $formUsages = is_array($activeService['form_usages'] ?? null) ? $activeService['form_usages'] : [];

        echo '<div class="emonks-card">';
        echo '<h2 style="margin-top:0;">' . esc_html((string) ($activeService['label'] ?? $serviceKey)) . ' <code>' . esc_html($serviceKey) . '</code></h2>';
        echo '<h2 class="nav-tab-wrapper" style="margin-bottom:14px;">';
        foreach (['overview' => 'Overzicht', 'forms' => 'Formulieren', 'data' => 'Velden & Data', 'features' => 'Features', 'publish' => 'Publicatie', 'plans' => 'Plannen', 'advanced' => 'Advanced'] as $tab => $label) {
            echo '<button type="button" class="nav-tab ' . ($tab === 'overview' ? 'nav-tab-active' : '') . '" data-service-tab="' . esc_attr($tab) . '">' . esc_html($label) . '</button>';
        }
        echo '</h2>';

        $this->renderSettingsFormStart('services_builder');
        echo '<input type="hidden" name="services_builder[action]" value="update" /><input type="hidden" name="services_builder[service_key]" value="' . esc_attr($serviceKey) . '" />';

        echo '<div class="emonks-tab-panel is-active" data-service-panel="overview">';
        echo '<p><label>Naam<br><input type="text" name="services_builder[label]" class="regular-text" value="' . esc_attr((string) ($activeService['label'] ?? $serviceKey)) . '" /></label></p>';
        echo '<p><label>Beschrijving<br><input type="text" name="services_builder[description]" class="regular-text" value="' . esc_attr((string) ($activeService['description'] ?? '')) . '" /></label></p>';
        echo '<p><label>Status<br><select name="services_builder[status]"><option value="draft" ' . selected($status, 'draft', false) . '>draft</option><option value="active" ' . selected($status, 'active', false) . '>active</option><option value="archived" ' . selected($status, 'archived', false) . '>archived</option></select></label></p>';
        echo '</div>';

        echo '<div class="emonks-tab-panel" data-service-panel="forms"><p class="description">Koppel formulieren aan gebruiksmomenten binnen deze service.</p><table class="widefat striped"><thead><tr><th>Context</th><th>Formulier</th><th>Actief</th></tr></thead><tbody>';
        $contexts = ['workspace_create' => 'Workspace create', 'workspace_edit' => 'Workspace edit', 'public_contact' => 'Public contact', 'intake' => 'Intake', 'feedback' => 'Feedback'];
        $usageByContext = [];
        foreach ($formUsages as $usage) {
            if (is_array($usage)) {
                $usageByContext[sanitize_key((string) ($usage['context'] ?? ''))] = $usage;
            }
        }
        foreach ($contexts as $context => $contextLabel) {
            $usage = is_array($usageByContext[$context] ?? null) ? $usageByContext[$context] : [];
            if (empty($usage) && isset($defaultContextForms[$context])) {
                $usage = ['context' => $context, 'form_key' => $defaultContextForms[$context], 'enabled' => true];
            }
            echo '<tr><td>' . esc_html($contextLabel) . '<input type="hidden" name="services_builder[form_usages][' . esc_attr($context) . '][context]" value="' . esc_attr($context) . '" /></td><td><select name="services_builder[form_usages][' . esc_attr($context) . '][form_key]"><option value="">Geen formulier</option>';
            foreach ($forms as $formKey => $form) {
                echo '<option value="' . esc_attr((string) $formKey) . '" ' . selected((string) ($usage['form_key'] ?? ''), (string) $formKey, false) . '>' . esc_html((string) ($form['label'] ?? $formKey)) . ' (' . esc_html((string) $formKey) . ')</option>';
            }
            echo '</select></td><td><label><input type="checkbox" name="services_builder[form_usages][' . esc_attr($context) . '][enabled]" value="1" ' . checked(! empty($usage['enabled']), true, false) . ' /> actief</label></td></tr>';
        }
        echo '</tbody></table></div>';

        echo '<div class="emonks-tab-panel" data-service-panel="data">';
        echo '<p><label>Standaard veldbron<br><select name="services_builder[field_source_default]"><option value="plugin" ' . selected($fieldSource, 'plugin', false) . '>plugin</option><option value="acf" ' . selected($fieldSource, 'acf', false) . '>acf</option><option value="hybrid" ' . selected($fieldSource, 'hybrid', false) . '>hybrid</option></select></label></p>';
        echo '<p><label>Standaard ACF field group<br><select name="services_builder[acf_group_key_default]"><option value="">Geen</option>';
        foreach ($acfGroups as $groupKey => $groupLabel) {
            echo '<option value="' . esc_attr($groupKey) . '" ' . selected((string) ($activeService['acf_group_key_default'] ?? ''), (string) $groupKey, false) . '>' . esc_html($groupLabel) . '</option>';
        }
        echo '</select></label></p><p class="description">Velden zelf beheer je onder Fields; formulieren onder Forms.</p></div>';

        echo '<div class="emonks-tab-panel" data-service-panel="features">';
        if (is_array($featuresTerms) && ! empty($featuresTerms)) {
            foreach ($featuresTerms as $feature) {
                if ($feature instanceof \WP_Term) {
                    $slug = sanitize_key((string) $feature->slug);
                    echo '<label style="display:block;margin:6px 0;"><input type="checkbox" name="services_builder[features][]" value="' . esc_attr($slug) . '" ' . checked(in_array($slug, $featureKeys, true), true, false) . ' /> ' . esc_html($feature->name) . ' <code>' . esc_html($slug) . '</code></label>';
                }
            }
        } else {
            echo '<p class="description">Nog geen features aangemaakt.</p>';
        }
        echo '</div>';

        echo '<div class="emonks-tab-panel" data-service-panel="publish"><p><label>Public route actief<br><input type="checkbox" name="services_builder[public_enabled]" value="1" ' . checked(! empty($renderHints['public_enabled']), true, false) . ' /></label></p><p><label>Template target<br><input type="text" name="services_builder[template]" class="regular-text" value="' . esc_attr((string) ($renderHints['template'] ?? '')) . '" placeholder="services/{service_key}/public.twig" /></label></p><p><label>Public route base<br><input type="text" name="services_builder[public_route]" class="regular-text" value="' . esc_attr((string) ($renderHints['public_route'] ?? 'g')) . '" /></label></p><p><label>Dashboard label<br><input type="text" name="services_builder[dashboard_label]" class="regular-text" value="' . esc_attr((string) ($renderHints['dashboard_label'] ?? '')) . '" /></label></p></div>';
        echo '<div class="emonks-tab-panel" data-service-panel="plans"><p class="description">Plan-koppelingen beheer je in de plan-editor via de Services taxonomie. Dit tabblad toont straks een matrix; de bron blijft de planconfiguratie.</p></div>';
        echo '<div class="emonks-tab-panel" data-service-panel="advanced"><p><label>Service schema JSON<br><textarea name="services_builder[schema_json]" rows="14" class="large-text code">' . esc_textarea(wp_json_encode($activeService, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</textarea></label></p></div>';

        submit_button('Service opslaan');
        $this->renderSettingsFormEnd();

        echo '<div class="emonks-danger-zone"><h3>Service verwijderen</h3><p class="description">Verwijdert de serviceconfiguratie. Workspaces met deze service blijven bestaan, maar de service is niet meer selecteerbaar.</p>';
        $this->renderSettingsFormStart('services_builder');
        echo '<input type="hidden" name="services_builder[action]" value="delete" /><input type="hidden" name="services_builder[service_key]" value="' . esc_attr($serviceKey) . '" /><button type="submit" class="button button-link-delete" onclick="return confirm(\'Weet je zeker dat je deze service wilt verwijderen?\')">Service verwijderen</button>';
        $this->renderSettingsFormEnd();
        echo '</div></div></div>';
        echo '<script>document.addEventListener("DOMContentLoaded",function(){document.querySelectorAll("[data-service-tab]").forEach(function(tab){tab.addEventListener("click",function(){var t=tab.getAttribute("data-service-tab");document.querySelectorAll("[data-service-tab]").forEach(function(i){i.classList.remove("nav-tab-active")});tab.classList.add("nav-tab-active");document.querySelectorAll("[data-service-panel]").forEach(function(p){p.classList.toggle("is-active",p.getAttribute("data-service-panel")===t)})})});document.querySelectorAll("[data-toggle-service-create]").forEach(function(btn){btn.addEventListener("click",function(){document.querySelectorAll("[data-service-create-form]").forEach(function(p){p.classList.toggle("is-open")})})});});</script>';
        echo '</div>';
    }

    private function renderServiceCreateForm(): void
    {
        $this->renderSettingsFormStart('services_builder');
        echo '<input type="hidden" name="services_builder[action]" value="create" />';
        echo '<p><label>Key<br><input type="text" name="services_builder[new_key]" class="regular-text" placeholder="bijv intake_portal" /></label></p>';
        echo '<p><label>Naam<br><input type="text" name="services_builder[new_label]" class="regular-text" placeholder="Intake portal" /></label></p>';
        submit_button('Service aanmaken', 'secondary', 'submit', false);
        $this->renderSettingsFormEnd();
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

    public function renderSaasHealthPage(): void
    {
        $health = Plugin::instance()->get('saas_health');
        $report = $health instanceof SaasHealth ? $health->report() : ['core' => [], 'config' => [], 'services' => []];

        echo '<div class="wrap">';
        $this->renderPageHeader('Emonks SaaS - SaaS Health', 'Controleer core, configuratie en dynamische services.', 'Een lege serviceconfiguratie is normaal bij een verse installatie; alleen core checks horen dan groen te zijn.');
        foreach ($report as $group => $rows) {
            echo '<h2>' . esc_html(ucfirst((string) $group)) . '</h2><table class="widefat striped"><thead><tr><th>Check</th><th>Status</th><th>Details</th></tr></thead><tbody>';
            if (empty($rows)) {
                echo '<tr><td colspan="3" class="emonks-muted">Geen checks in deze groep.</td></tr>';
            }
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $status = sanitize_key((string) ($row['status'] ?? 'warning'));
                $label = sanitize_text_field((string) ($row['label'] ?? ''));
                $details = sanitize_text_field((string) ($row['details'] ?? ''));
                $badgeClass = $status === 'ok' ? 'updated' : ($status === 'info' ? 'notice-info' : 'notice-warning');
                echo '<tr><td><strong>' . esc_html($label) . '</strong></td><td><span class="notice ' . esc_attr($badgeClass) . '" style="display:inline-block;margin:0;padding:2px 8px;">' . esc_html($status) . '</span></td><td>' . esc_html($details) . '</td></tr>';
            }
            echo '</tbody></table>';
        }
        echo '</div>';
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
        $redirectUrl = wp_get_referer() ?: admin_url('admin.php?page=emonks-saas-core');
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
        } elseif ($tab === 'settings') {
            $current = array_replace_recursive($current, $sanitized);
            unset($current['modules'], $current['form_schemas']);
        } elseif ($tab === 'fields_builder') {
            $builder = isset($_POST['fields_builder']) ? wp_unslash($_POST['fields_builder']) : [];
            $builder = is_array($builder) ? $builder : [];
            $existing = emonks_get_field_library();
            $fields = is_array($existing['fields'] ?? null) ? $existing['fields'] : [];
            $action = sanitize_key((string) ($builder['action'] ?? 'update'));

            if ($action === 'create') {
                $new = is_array($builder['new'] ?? null) ? $builder['new'] : [];
                $newKey = sanitize_key((string) ($new['key'] ?? ''));
                if ($newKey === '') {
                    emonks_flash_add('settings_warning', 'Veld key is verplicht.');
                } elseif (isset($fields[$newKey])) {
                    emonks_flash_add('settings_warning', 'Veld key bestaat al.');
                } else {
                    $fields[$newKey] = $this->sanitizeFieldDefinition($newKey, $new);
                    $redirectUrl = admin_url('admin.php?page=emonks-saas-fields&field=' . rawurlencode($newKey));
                }
            } elseif ($action === 'delete') {
                $fieldKey = sanitize_key((string) ($builder['field_key'] ?? ''));
                if ($fieldKey === '' || ! isset($fields[$fieldKey])) {
                    emonks_flash_add('settings_warning', 'Onbekend veld voor verwijderen.');
                } else {
                    unset($fields[$fieldKey]);
                    emonks_flash_add('settings_success', 'Veld verwijderd.');
                    $redirectUrl = admin_url('admin.php?page=emonks-saas-fields');
                }
            } else {
                $fieldKey = sanitize_key((string) ($builder['field_key'] ?? ''));
                $fieldData = is_array($builder['field'] ?? null) ? $builder['field'] : [];
                if ($fieldKey === '' || ! isset($fields[$fieldKey])) {
                    emonks_flash_add('settings_warning', 'Onbekend veld voor update.');
                } else {
                    $fields[$fieldKey] = $this->sanitizeFieldDefinition($fieldKey, $fieldData);
                    $redirectUrl = admin_url('admin.php?page=emonks-saas-fields&field=' . rawurlencode($fieldKey));
                }
            }

            $current['field_library'] = ['schema_version' => 1, 'fields' => $fields];
        } elseif ($tab === 'forms_builder') {
            $builder = isset($_POST['forms_builder']) ? wp_unslash($_POST['forms_builder']) : [];
            $builder = is_array($builder) ? $builder : [];
            $existing = emonks_get_form_templates();
            $forms = is_array($existing['forms'] ?? null) ? $existing['forms'] : [];
            $action = sanitize_key((string) ($builder['action'] ?? 'update'));

            if ($action === 'create') {
                $new = is_array($builder['new'] ?? null) ? $builder['new'] : [];
                $newKey = sanitize_key((string) ($new['key'] ?? ''));
                if ($newKey === '') {
                    emonks_flash_add('settings_warning', 'Form key is verplicht.');
                } elseif (isset($forms[$newKey])) {
                    emonks_flash_add('settings_warning', 'Form key bestaat al.');
                } else {
                    $forms[$newKey] = $this->sanitizeFormTemplate($newKey, $new);
                    $redirectUrl = admin_url('admin.php?page=emonks-saas-forms&form=' . rawurlencode($newKey));
                }
            } elseif ($action === 'delete') {
                $formKey = sanitize_key((string) ($builder['form_key'] ?? ''));
                if ($formKey === '' || ! isset($forms[$formKey])) {
                    emonks_flash_add('settings_warning', 'Onbekend formulier voor verwijderen.');
                } else {
                    unset($forms[$formKey]);
                    emonks_flash_add('settings_success', 'Formulier verwijderd.');
                    $redirectUrl = admin_url('admin.php?page=emonks-saas-forms');
                }
            } else {
                $formKey = sanitize_key((string) ($builder['form_key'] ?? ''));
                $formData = is_array($builder['form'] ?? null) ? $builder['form'] : [];
                if ($formKey === '' || ! isset($forms[$formKey])) {
                    emonks_flash_add('settings_warning', 'Onbekend formulier voor update.');
                } else {
                    $currentForm = is_array($forms[$formKey]) ? $forms[$formKey] : [];
                    $formData['action'] = (string) ($currentForm['action'] ?? '');
                    $formData['nonce_action'] = (string) ($currentForm['nonce_action'] ?? '');
                    $forms[$formKey] = $this->sanitizeFormTemplate($formKey, $formData);
                    $redirectUrl = admin_url('admin.php?page=emonks-saas-forms&form=' . rawurlencode($formKey));
                }
            }

            $current['form_templates'] = ['schema_version' => 1, 'forms' => $forms];
        } elseif ($tab === 'services_builder') {
            $builder = isset($_POST['services_builder']) ? wp_unslash($_POST['services_builder']) : [];
            $builder = is_array($builder) ? $builder : [];
            $all = emonks_get_service_schemas();
            $services = is_array($all['services'] ?? null) ? $all['services'] : [];
            $action = sanitize_key((string) ($builder['action'] ?? 'update'));

            if ($action === 'create') {
                $newKey = sanitize_key((string) ($builder['new_key'] ?? ''));
                $newLabel = sanitize_text_field((string) ($builder['new_label'] ?? ''));
                if ($newKey === '') {
                    emonks_flash_add('settings_warning', 'Service key is verplicht.');
                } elseif (isset($services[$newKey])) {
                    emonks_flash_add('settings_warning', 'Service key bestaat al.');
                } else {
                    $defaultFormUsages = [];
                    if (isset($defaultContextForms['workspace_create'])) {
                        $defaultFormUsages[] = ['context' => 'workspace_create', 'form_key' => $defaultContextForms['workspace_create'], 'enabled' => true];
                    }
                    if (isset($defaultContextForms['workspace_edit'])) {
                        $defaultFormUsages[] = ['context' => 'workspace_edit', 'form_key' => $defaultContextForms['workspace_edit'], 'enabled' => true];
                    }

                    $services[$newKey] = [
                        'key' => $newKey,
                        'label' => $newLabel !== '' ? $newLabel : ucfirst(str_replace('_', ' ', $newKey)),
                        'description' => '',
                        'status' => 'draft',
                        'features' => [],
                        'field_source_default' => 'plugin',
                        'acf_group_key_default' => '',
                        'form_usages' => $defaultFormUsages,
                        'render_hints' => [
                            'public_enabled' => false,
                            'template' => '',
                            'public_route' => 'g',
                            'dashboard_label' => '',
                        ],
                    ];
                    $this->syncServiceTermFromSchema($newKey, $services[$newKey]);
                    $redirectUrl = admin_url('admin.php?page=emonks-saas-services&service=' . rawurlencode($newKey));
                }
            } elseif ($action === 'delete') {
                $deleteKey = sanitize_key((string) ($builder['service_key'] ?? ''));
                if ($deleteKey === '') {
                    emonks_flash_add('settings_warning', 'Geen service key opgegeven voor verwijderen.');
                } elseif (! isset($services[$deleteKey])) {
                    emonks_flash_add('settings_warning', 'Service niet gevonden.');
                } else {
                    unset($services[$deleteKey]);
                    $this->deleteServiceTerm($deleteKey);
                    emonks_flash_add('settings_success', 'Service verwijderd.');
                    $redirectUrl = admin_url('admin.php?page=emonks-saas-services');
                }
            } else {
                $serviceKey = sanitize_key((string) ($builder['service_key'] ?? ''));
                if ($serviceKey === '' || ! isset($services[$serviceKey]) || ! is_array($services[$serviceKey])) {
                    emonks_flash_add('settings_warning', 'Onbekende service voor update.');
                } else {
                    $existing = $services[$serviceKey];
                    $schemaJson = trim((string) ($builder['schema_json'] ?? ''));
                    if ($schemaJson !== '') {
                        $decodedSchema = json_decode($schemaJson, true);
                        if (is_array($decodedSchema)) {
                            $existing = array_replace_recursive($existing, $decodedSchema);
                        } else {
                            emonks_flash_add('settings_warning', 'Service schema JSON is ongeldig; advanced merge overgeslagen.');
                        }
                    }

                    $features = [];
                    if (isset($builder['features'])) {
                        $rawFeatures = is_array($builder['features']) ? $builder['features'] : [];
                        $features = array_values(array_filter(array_map(static fn($item) => sanitize_key((string) $item), $rawFeatures)));
                    }

                    $formUsages = [];
                    $rawUsages = is_array($builder['form_usages'] ?? null) ? $builder['form_usages'] : [];
                    foreach ($rawUsages as $usage) {
                        if (! is_array($usage)) {
                            continue;
                        }
                        $context = sanitize_key((string) ($usage['context'] ?? ''));
                        $formKey = sanitize_key((string) ($usage['form_key'] ?? ''));
                        if ($context === '' || $formKey === '') {
                            continue;
                        }
                        $formUsages[] = ['context' => $context, 'form_key' => $formKey, 'enabled' => ! empty($usage['enabled'])];
                    }

                    if (empty($formUsages)) {
                        $defaultContextForms = $this->defaultServiceContextForms(emonks_get_form_templates()['forms'] ?? []);
                        if (isset($defaultContextForms['workspace_create'])) {
                            $formUsages[] = ['context' => 'workspace_create', 'form_key' => $defaultContextForms['workspace_create'], 'enabled' => true];
                        }
                        if (isset($defaultContextForms['workspace_edit'])) {
                            $formUsages[] = ['context' => 'workspace_edit', 'form_key' => $defaultContextForms['workspace_edit'], 'enabled' => true];
                        }
                    }

                    $status = $this->sanitizeServiceStatus((string) ($builder['status'] ?? ($existing['status'] ?? 'draft')));
                    $fieldSource = $this->sanitizeFieldSource((string) ($builder['field_source_default'] ?? ($existing['field_source_default'] ?? 'plugin')));
                    $template = sanitize_text_field((string) ($builder['template'] ?? ($existing['render_hints']['template'] ?? '')));
                    $publicRoute = sanitize_key((string) ($builder['public_route'] ?? ($existing['render_hints']['public_route'] ?? 'g')));
                    $dashboardLabel = sanitize_text_field((string) ($builder['dashboard_label'] ?? ($existing['render_hints']['dashboard_label'] ?? '')));

                    $existing['key'] = $serviceKey;
                    $existing['label'] = sanitize_text_field((string) ($builder['label'] ?? ($existing['label'] ?? $serviceKey)));
                    $existing['description'] = sanitize_text_field((string) ($builder['description'] ?? ($existing['description'] ?? '')));
                    $existing['status'] = $status;
                    $existing['field_source_default'] = $fieldSource;
                    $existing['acf_group_key_default'] = sanitize_text_field((string) ($builder['acf_group_key_default'] ?? ($existing['acf_group_key_default'] ?? '')));
                    $existing['features'] = $features;
                    $existing['form_usages'] = $formUsages;
                    $existing['render_hints'] = [
                        'public_enabled' => ! empty($builder['public_enabled']),
                        'template' => $template,
                        'public_route' => $publicRoute !== '' ? $publicRoute : 'g',
                        'dashboard_label' => $dashboardLabel,
                    ];
                    unset($existing['fields'], $existing['forms'], $existing['capabilities'], $existing['field_source'], $existing['acf_field_group_key']);
                    $services[$serviceKey] = $existing;
                    $this->syncServiceTermFromSchema($serviceKey, $existing);
                    $redirectUrl = admin_url('admin.php?page=emonks-saas-services&service=' . rawurlencode($serviceKey));
                }
            }

            $current['service_schemas'] = ['schema_version' => 1, 'config_model' => 'dynamic_services_v1', 'services' => $services];
        } else {
            $current = array_replace_recursive($current, $sanitized);
        }

        unset($current['modules'], $current['form_schemas']);

        update_option(Settings::OPTION_KEY, $current);

        wp_safe_redirect($redirectUrl);
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

    /** @param array<string,mixed> $forms @return array<string,string> */
    private function defaultServiceContextForms(array $forms): array
    {
        $defaults = [];
        $workspaceCreate = 'workspace_create';
        $workspaceEdit = 'workspace_edit';

        if (isset($forms[$workspaceCreate]) && is_array($forms[$workspaceCreate])) {
            $defaults['workspace_create'] = $workspaceCreate;
        }
        if (isset($forms[$workspaceEdit]) && is_array($forms[$workspaceEdit])) {
            $defaults['workspace_edit'] = $workspaceEdit;
        }

        foreach ($forms as $key => $form) {
            if (! is_array($form)) {
                continue;
            }
            $scope = sanitize_key((string) ($form['scope'] ?? 'service'));
            if ($scope !== 'core' && $scope !== 'service') {
                continue;
            }
            $formKey = sanitize_key((string) $key);
            if (! isset($defaults['workspace_create']) && str_contains($formKey, 'workspace') && str_contains($formKey, 'create')) {
                $defaults['workspace_create'] = $formKey;
            }
            if (! isset($defaults['workspace_edit']) && str_contains($formKey, 'workspace') && str_contains($formKey, 'edit')) {
                $defaults['workspace_edit'] = $formKey;
            }
        }

        return $defaults;
    }

    /** @param array<string,mixed> $raw */
    private function buildFieldLibraryFromPost(array $raw): array
    {
        $existing = emonks_get_field_library();
        $fields = [];
        $submitted = is_array($raw['fields'] ?? null) ? $raw['fields'] : [];
        foreach ($submitted as $key => $field) {
            if (! is_array($field)) {
                continue;
            }
            $fieldKey = sanitize_key((string) ($field['key'] ?? $key));
            if ($fieldKey === '') {
                continue;
            }
            $fields[$fieldKey] = $this->sanitizeFieldDefinition($fieldKey, $field);
        }

        $new = is_array($raw['new'] ?? null) ? $raw['new'] : [];
        $newKey = sanitize_key((string) ($new['key'] ?? ''));
        if ($newKey !== '') {
            $fields[$newKey] = $this->sanitizeFieldDefinition($newKey, $new);
        }

        if (empty($fields)) {
            $fields = is_array($existing['fields'] ?? null) ? $existing['fields'] : [];
        }

        return ['schema_version' => 1, 'fields' => $fields];
    }

    /** @param array<string,mixed> $field */
    private function sanitizeFieldDefinition(string $fieldKey, array $field): array
    {
        $type = sanitize_key((string) ($field['type'] ?? 'text'));
        if (! in_array($type, $this->allowedFieldTypes(), true)) {
            $type = 'text';
        }
        $source = sanitize_key((string) ($field['source'] ?? 'plugin'));
        if (! in_array($source, ['plugin', 'acf', 'computed'], true)) {
            $source = 'plugin';
        }

        return [
            'key' => $fieldKey,
            'label' => sanitize_text_field((string) ($field['label'] ?? $fieldKey)),
            'type' => $type,
            'source' => $source,
            'required' => ! empty($field['required']),
            'placeholder' => sanitize_text_field((string) ($field['placeholder'] ?? '')),
            'help' => sanitize_text_field((string) ($field['help'] ?? '')),
            'default' => sanitize_text_field((string) ($field['default'] ?? '')),
            'acf_group_key' => sanitize_text_field((string) ($field['acf_group_key'] ?? '')),
            'acf_field_key' => sanitize_text_field((string) ($field['acf_field_key'] ?? '')),
            'rules' => $this->sanitizeFieldRules($field),
        ];
    }

    /** @param array<string,mixed> $raw */
    private function buildFormTemplatesFromPost(array $raw): array
    {
        $existing = emonks_get_form_templates();
        $forms = [];
        $submitted = is_array($raw['forms'] ?? null) ? $raw['forms'] : [];
        foreach ($submitted as $key => $form) {
            if (! is_array($form)) {
                continue;
            }
            $formKey = sanitize_key((string) ($form['key'] ?? $key));
            if ($formKey === '') {
                continue;
            }
            $forms[$formKey] = $this->sanitizeFormTemplate($formKey, $form);
        }

        $new = is_array($raw['new'] ?? null) ? $raw['new'] : [];
        $newKey = sanitize_key((string) ($new['key'] ?? ''));
        if ($newKey !== '') {
            $forms[$newKey] = $this->sanitizeFormTemplate($newKey, $new);
        }

        if (empty($forms)) {
            $forms = is_array($existing['forms'] ?? null) ? $existing['forms'] : [];
        }

        return ['schema_version' => 1, 'forms' => $forms];
    }

    /** @param array<string,mixed> $form */
    private function sanitizeFormTemplate(string $formKey, array $form): array
    {
        $refs = [];
        $submittedRefs = is_array($form['field_refs'] ?? null) ? $form['field_refs'] : [];
        foreach ($submittedRefs as $fieldKey => $ref) {
            $safeFieldKey = sanitize_key((string) $fieldKey);
            if ($safeFieldKey === '') {
                continue;
            }

            $enabled = false;
            $order = 9990;
            if (is_array($ref)) {
                $enabled = ! empty($ref['enabled']);
                $order = isset($ref['order']) && is_numeric($ref['order']) ? (int) $ref['order'] : 9990;
            } else {
                $enabled = ! empty($ref);
            }

            if (! $enabled) {
                continue;
            }

            $refs[] = ['field_key' => $safeFieldKey, 'order' => $order, 'required' => false];
        }

        usort($refs, static fn($a, $b) => (int) ($a['order'] ?? 0) <=> (int) ($b['order'] ?? 0));

        return [
            'key' => $formKey,
            'label' => sanitize_text_field((string) ($form['label'] ?? $formKey)),
            'scope' => sanitize_key((string) ($form['scope'] ?? 'service')),
            'action' => sanitize_text_field((string) ($form['action'] ?? '')),
            'nonce_action' => sanitize_key((string) ($form['nonce_action'] ?? '')),
            'submit_label' => sanitize_text_field((string) ($form['submit_label'] ?? 'Opslaan')),
            'field_refs' => $refs,
        ];
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
    private function allowedFieldTypes(): array
    {
        return ['text', 'email', 'password', 'url', 'textarea', 'select', 'checkbox', 'hidden'];
    }

    /** @param array<string,mixed> $field */
    private function fieldRuleValue(array $field, string $ruleType): string
    {
        $rules = is_array($field['rules'] ?? null) ? $field['rules'] : [];
        foreach ($rules as $rule) {
            if (! is_array($rule)) {
                continue;
            }
            $type = sanitize_key((string) ($rule['type'] ?? ''));
            if ($type !== sanitize_key($ruleType)) {
                continue;
            }
            $value = $rule['value'] ?? '';
            if (is_numeric($value)) {
                return (string) (int) $value;
            }
            return sanitize_text_field((string) $value);
        }

        return '';
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

    /** @param array<string,mixed> $schema */
    private function syncServiceTermFromSchema(string $serviceKey, array $schema): void
    {
        if (! taxonomy_exists('emonks_service')) {
            return;
        }

        $label = sanitize_text_field((string) ($schema['label'] ?? $serviceKey));
        $term = get_term_by('slug', $serviceKey, 'emonks_service');
        if (! $term instanceof \WP_Term) {
            $created = wp_insert_term($label !== '' ? $label : ucfirst($serviceKey), 'emonks_service', ['slug' => $serviceKey]);
            if (is_wp_error($created)) {
                return;
            }
            $termId = (int) ($created['term_id'] ?? 0);
        } else {
            $termId = (int) $term->term_id;
            if ($label !== '' && $term->name !== $label) {
                wp_update_term($termId, 'emonks_service', ['name' => $label]);
            }
        }

        if ($termId <= 0) {
            return;
        }

        $features = is_array($schema['features'] ?? null) ? $schema['features'] : (is_array($schema['capabilities'] ?? null) ? $schema['capabilities'] : []);
        $features = array_values(array_filter(array_map(static fn($item) => sanitize_key((string) $item), $features)));
        update_term_meta($termId, 'supported_features', $features);
    }

    private function deleteServiceTerm(string $serviceKey): void
    {
        if (! taxonomy_exists('emonks_service')) {
            return;
        }
        $term = get_term_by('slug', $serviceKey, 'emonks_service');
        if (! $term instanceof \WP_Term) {
            return;
        }
        wp_delete_term((int) $term->term_id, 'emonks_service');
    }

    private function sanitizeServiceStatus(string $status): string
    {
        $normalized = sanitize_key($status);
        return in_array($normalized, ['active', 'draft', 'archived'], true) ? $normalized : 'draft';
    }

    private function sanitizeFieldSource(string $source): string
    {
        $normalized = sanitize_key($source);
        return in_array($normalized, ['plugin', 'acf', 'hybrid'], true) ? $normalized : 'plugin';
    }

    /** @param array<string,mixed> $fallback @return array<string,mixed> */
    private function decodeFormsJson(string $json, array $fallback): array
    {
        $json = trim($json);
        if ($json === '') {
            return $fallback;
        }
        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            emonks_flash_add('settings_warning', 'Forms JSON in service schema is ongeldig; bestaande forms blijven behouden.');
            return $fallback;
        }

        return $decoded;
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
