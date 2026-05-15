<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

final class Admin
{
    public function boot(): void
    {
        add_action('admin_menu', [$this, 'registerMenu']);
        add_action('admin_post_emonks_saas_save_settings', [$this, 'saveSettings']);
        add_action('admin_post_emonks_saas_approval_action', [$this, 'handleApprovalAction']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }

    public function registerMenu(): void
    {
        add_menu_page('Emonks SaaS', 'Emonks SaaS', 'manage_options', 'emonks-saas-core', [$this, 'renderDashboardPage'], 'dashicons-chart-area', 58);
        add_submenu_page('emonks-saas-core', 'Dashboard', 'Dashboard', 'manage_options', 'emonks-saas-core', [$this, 'renderDashboardPage']);
        add_submenu_page('emonks-saas-core', 'Services', 'Services', 'manage_options', 'emonks-saas-services', [$this, 'renderServicesPage']);
        add_submenu_page('emonks-saas-core', 'Fields', 'Fields', 'manage_options', 'emonks-saas-fields', [$this, 'renderFieldsPage']);
        add_submenu_page('emonks-saas-core', 'Forms', 'Forms', 'manage_options', 'emonks-saas-forms', [$this, 'renderFormsPage']);
        add_submenu_page('emonks-saas-core', 'Emails', 'Emails', 'manage_options', 'emonks-saas-emails', [$this, 'renderEmailsPage']);
        add_submenu_page('emonks-saas-core', 'Features', 'Features', 'manage_options', 'emonks-saas-features', [$this, 'renderFeaturesPage']);
        add_submenu_page('emonks-saas-core', 'Plans', 'Plans', 'manage_options', 'emonks-saas-plans', [$this, 'renderPlansPage']);
        add_submenu_page('emonks-saas-core', 'Billing', 'Billing', 'manage_options', 'emonks-saas-billing', [$this, 'renderBillingPage']);
        add_submenu_page('emonks-saas-core', 'Workspaces', 'Workspaces', 'manage_options', 'edit.php?post_type=emonks_workspace');
        add_submenu_page('emonks-saas-core', 'SaaS Health', 'SaaS Health', 'manage_options', 'emonks-saas-health', [$this, 'renderSaasHealthPage']);
        add_submenu_page('emonks-saas-core', 'Logs', 'Logs', 'manage_options', 'emonks-saas-logs', [$this, 'renderLogsPage']);
        add_submenu_page('emonks-saas-core', 'Pending Users', 'Pending Users', 'manage_options', 'emonks-saas-pending-users', [$this, 'renderPendingUsersPage']);
        add_submenu_page('emonks-saas-core', 'Docs & Support', 'Docs & Support', 'manage_options', 'emonks-saas-docs', [$this, 'renderDocsPage']);
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
            '.emonks-card{background:#fff;border:1px solid #dcdcde;padding:16px}' .
            '.emonks-kpi{font-size:28px;font-weight:700;margin-top:8px}' .
            '.emonks-muted{color:#646970}' .
            '.emonks-dashboard-hero{background:#fff;border:1px solid #dcdcde;padding:20px;margin:0 0 18px;display:grid;grid-template-columns:minmax(0,1fr) auto;gap:20px;align-items:center}' .
            '.emonks-dashboard-hero h1{margin:0 0 8px;font-size:26px;line-height:1.2}' .
            '.emonks-dashboard-hero p{margin:0;max-width:720px;font-size:14px;line-height:1.55}' .
            '.emonks-dashboard-actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}' .
            '.emonks-dashboard-layout{display:grid;grid-template-columns:minmax(0,1.45fr) minmax(320px,.75fr);gap:18px;align-items:start}' .
            '.emonks-dashboard-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:0 0 18px}' .
            '.emonks-kpi-card{background:#fff;border:1px solid #dcdcde;padding:14px;min-height:92px;display:flex;flex-direction:column;justify-content:space-between}' .
            '.emonks-kpi-label{color:#646970;font-size:12px;font-weight:600;text-transform:uppercase}' .
            '.emonks-kpi-value{font-size:30px;line-height:1;font-weight:700;color:#1d2327}' .
            '.emonks-dashboard-section{background:#fff;border:1px solid #dcdcde;padding:16px;margin-bottom:18px}' .
            '.emonks-dashboard-section h2{margin:0 0 12px;font-size:16px}' .
            '.emonks-setup-metrics{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}' .
            '.emonks-setup-metric{border:1px solid #e0e0e0;background:#f8fafc;padding:12px}' .
            '.emonks-setup-metric strong{display:block;font-size:22px;line-height:1;margin-bottom:6px}' .
            '.emonks-plan-row{display:grid;grid-template-columns:96px minmax(0,1fr) 44px;gap:10px;align-items:center;margin:10px 0}' .
            '.emonks-plan-bar{height:8px;background:#f0f0f1;border-radius:999px;overflow:hidden}' .
            '.emonks-plan-fill{display:block;height:100%;background:#2271b1;border-radius:999px}' .
            '.emonks-flow-list{margin:0;display:grid;gap:10px}' .
            '.emonks-flow-list li{margin:0;padding:12px;border:1px solid #e0e0e0;background:#f8fafc;display:grid;grid-template-columns:28px minmax(0,1fr);gap:10px;align-items:start}' .
            '.emonks-step-index{width:24px;height:24px;border-radius:50%;background:#2271b1;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:700}' .
            '.emonks-quicklinks{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}' .
            '.emonks-quicklinks .button{display:flex;justify-content:center;text-align:center}' .
            '.emonks-support-shell{display:grid;grid-template-columns:260px minmax(0,1fr);gap:18px;align-items:start}' .
            '.emonks-support-nav{position:sticky;top:46px}' .
            '.emonks-support-nav a{display:block;padding:9px 10px;border:1px solid #dcdcde;background:#fff;color:#1d2327;text-decoration:none;margin-bottom:8px}' .
            '.emonks-support-nav a:hover{border-color:#2271b1;color:#1d2327}' .
            '.emonks-support-nav a.is-active{border-color:#2271b1;background:#eef6ff;box-shadow:0 0 0 1px rgba(34,113,177,.12)}' .
            '.emonks-support-hero{background:#fff;border:1px solid #dcdcde;padding:20px;margin:0 0 18px}' .
            '.emonks-support-hero h1{margin:0 0 8px;font-size:26px;line-height:1.2}' .
            '.emonks-support-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px}' .
            '.emonks-support-checklist{margin:0;display:grid;gap:10px}' .
            '.emonks-support-checklist li{margin:0;padding:12px;border:1px solid #e0e0e0;background:#f8fafc}' .
            '.emonks-support-callout{border-left:4px solid #2271b1;background:#f6f7f7;padding:12px 14px;margin:12px 0}' .
            '.emonks-header{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin:12px 0 20px}' .
            '.emonks-tip{background:#f6f7f7;border-left:4px solid #2271b1;padding:12px 14px;max-width:540px}' .
            '.emonks-subtle{font-size:12px;color:#646970;line-height:1.45}' .
            '.emonks-form-table td .description{margin-top:6px;display:block}' .
            '.emonks-builder-shell{display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:18px;align-items:start}' .
            '.emonks-builder-main{min-width:0}.emonks-builder-inspector{position:sticky;top:46px}' .
            '.emonks-builder-schemas{display:flex;gap:8px;flex-wrap:wrap;margin:0 0 14px}' .
            '.emonks-builder-preview{background:#fff;border:1px solid #dcdcde;padding:14px}' .
            '.emonks-builder-field{display:flex;justify-content:space-between;gap:10px;align-items:center;border:1px solid #dcdcde;border-left:3px solid transparent;background:#fff;padding:10px 12px;margin:0 0 10px;cursor:pointer}' .
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
            '.emonks-service-item{display:flex;justify-content:space-between;align-items:center;gap:8px;width:100%;max-width:100%;box-sizing:border-box;padding:9px 10px;border:1px solid #dcdcde;background:#fff;color:#1d2327;text-decoration:none;margin-bottom:8px;overflow:hidden}' .
            '.emonks-service-item > span:first-child{min-width:0}' .
            '.emonks-service-item .emonks-subtle{display:block;word-break:break-word}' .
            '.emonks-service-item:hover{border-color:#2271b1}.emonks-service-item.is-active{border-color:#2271b1;background:#eef6ff}' .
            '.emonks-service-meta{display:flex;gap:6px;align-items:center}.emonks-service-form{display:none}.emonks-service-form.is-open{display:block}' .
            '.emonks-danger-zone{margin-top:24px;padding-top:14px;border-top:1px solid #dcdcde}' .
            '.emonks-tab-panel{display:none}.emonks-tab-panel.is-active{display:block}' .
            '.emonks-editor-section{background:#f8fafc;border:1px solid #e2e8f0;padding:14px;margin-bottom:12px}' .
            '.emonks-editor-title{margin:0 0 10px;font-size:13px;line-height:1.3;letter-spacing:.02em;text-transform:uppercase;color:#334155}' .
            '.emonks-editor-form p{margin:0}' .
            '.emonks-editor-form label{display:block;font-weight:600;color:#1e293b}' .
            '.emonks-editor-form .regular-text,.emonks-editor-form select,.emonks-editor-form input[type="text"],.emonks-editor-form input[type="number"]{width:100%;max-width:100%;margin-top:6px}' .
            '.emonks-editor-form .checkbox-row{display:flex;align-items:center;gap:8px;padding-top:10px}' .
            '.emonks-editor-form .checkbox-row label{display:flex;align-items:center;gap:8px;font-weight:500;margin:0}' .
            '@media(max-width:1120px){.emonks-builder-shell{grid-template-columns:1fr}.emonks-builder-inspector{position:static}.emonks-field-type-grid{grid-template-columns:1fr}.emonks-services-shell{grid-template-columns:1fr}.emonks-services-sidebar{position:static}.emonks-dashboard-layout{grid-template-columns:1fr}.emonks-dashboard-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}.emonks-dashboard-hero{grid-template-columns:1fr}.emonks-dashboard-actions{justify-content:flex-start}.emonks-support-shell{grid-template-columns:1fr}.emonks-support-nav{position:static}}' .
            '@media(max-width:640px){.emonks-dashboard-kpis{grid-template-columns:1fr}.emonks-quicklinks{grid-template-columns:1fr}.emonks-setup-metrics{grid-template-columns:1fr}.emonks-plan-row{grid-template-columns:78px minmax(0,1fr) 36px}}'
        );
    }

    public function renderDashboardPage(): void
    {
        $stats = $this->collectStats();
        $services = emonks_get_service_schemas()['services'] ?? [];
        $fields = emonks_get_field_library()['fields'] ?? [];
        $forms = emonks_get_form_templates()['forms'] ?? [];
        $plans = Plans::getPlans();

        echo '<div class="wrap">';
        echo '<div class="emonks-dashboard-hero">';
        echo '<div><h1>Emonks SaaS Dashboard</h1><p class="emonks-muted">Een strak overzicht van adoptie, setup, subscriptions en publicatie. Begin hier voor dagelijkse checks, nieuwe inrichting en snelle troubleshooting.</p></div>';
        echo '<div class="emonks-dashboard-actions">';
        echo '<a class="button button-primary" href="' . esc_url(admin_url('admin.php?page=emonks-saas-services')) . '">Service beheren</a>';
        echo '<a class="button" href="' . esc_url(admin_url('admin.php?page=emonks-saas-health')) . '">Health check</a>';
        echo '<a class="button" href="' . esc_url(admin_url('admin.php?page=emonks-saas-docs')) . '">Docs</a>';
        echo '</div>';
        echo '</div>';

        echo '<div class="emonks-dashboard-kpis">';
        $this->renderDashboardKpi('Accounts', (string) $stats['accounts']);
        $this->renderDashboardKpi('Workspaces', (string) $stats['workspaces']);
        $this->renderDashboardKpi('Actieve subscriptions', (string) $stats['active_subscriptions']);
        $this->renderDashboardKpi('Published workspaces', (string) $stats['published_workspaces']);
        echo '</div>';

        echo '<div class="emonks-dashboard-layout">';
        echo '<div>';
        echo '<div class="emonks-dashboard-section"><h2>Plan verdeling</h2>';
        $this->renderPlanDistribution($stats['plan_distribution']);
        echo '</div>';

        echo '<div class="emonks-dashboard-section"><h2>Aanbevolen beheerflow</h2>';
        echo '<ol class="emonks-flow-list">';
        echo '<li><span class="emonks-step-index">1</span><span><strong>Controleer Billing</strong><br><span class="emonks-muted">Check test mode, Stripe constants, success/cancel URLs en webhook readiness voordat je live gebruikers doorstuurt.</span></span></li>';
        echo '<li><span class="emonks-step-index">2</span><span><strong>Valideer planlimieten</strong><br><span class="emonks-muted">Controleer features, services, max workspaces en prijsinformatie per plan.</span></span></li>';
        echo '<li><span class="emonks-step-index">3</span><span><strong>Test onboarding en publicatie</strong><br><span class="emonks-muted">Maak een testgebruiker, start een workspace en controleer de account- en public routes.</span></span></li>';
        echo '</ol></div>';
        echo '</div>';

        echo '<div>';
        echo '<div class="emonks-dashboard-section"><h2>Setup status</h2>';
        echo '<div class="emonks-setup-metrics">';
        $this->renderSetupMetric('Services', (string) count((array) $services));
        $this->renderSetupMetric('Velden', (string) count((array) $fields));
        $this->renderSetupMetric('Formulieren', (string) count((array) $forms));
        $this->renderSetupMetric('Plannen', (string) count((array) $plans));
        echo '</div></div>';

        echo '<div class="emonks-dashboard-section"><h2>Snelle acties</h2><div class="emonks-quicklinks">';
        echo '<a class="button" href="' . esc_url(admin_url('admin.php?page=emonks-saas-services')) . '">Services</a>';
        echo '<a class="button" href="' . esc_url(admin_url('admin.php?page=emonks-saas-fields')) . '">Fields</a>';
        echo '<a class="button" href="' . esc_url(admin_url('admin.php?page=emonks-saas-forms')) . '">Forms</a>';
        echo '<a class="button" href="' . esc_url(admin_url('admin.php?page=emonks-saas-plans')) . '">Plans</a>';
        echo '<a class="button" href="' . esc_url(admin_url('admin.php?page=emonks-saas-billing')) . '">Billing</a>';
        echo '<a class="button" href="' . esc_url(admin_url('admin.php?page=emonks-saas-logs')) . '">Logs</a>';
        echo '</div></div>';

        echo '<div class="emonks-dashboard-section"><h2>Volgende stap</h2>';
        echo '<p class="emonks-muted">Werk van service naar formulier naar plan. Gebruik daarna SaaS Health om ontbrekende koppelingen te vinden.</p>';
        echo '<p><a class="button button-primary" href="' . esc_url(admin_url('admin.php?page=emonks-saas-services')) . '">Nieuwe service starten</a></p>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    public function renderSettingsPage(): void
    {
        $tab = sanitize_key((string) ($_GET['settings_tab'] ?? 'general'));
        if ($tab === 'docs') {
            wp_safe_redirect(admin_url('admin.php?page=emonks-saas-docs'));
            exit;
        }

        if (! in_array($tab, ['general', 'pages', 'auth', 'redirects', 'lifecycle', 'shortcodes', 'user_menu'], true)) {
            $tab = 'general';
        }

        echo '<div class="wrap">';
        $this->renderPageHeader('Emonks SaaS - Settings', 'Algemene instellingen en compacte beheerpagina\'s.', 'Grotere operationele schermen blijven apart; compacte pagina\'s staan hier als tab.');
        echo '<h2 class="nav-tab-wrapper" style="margin-bottom:14px;">';
        foreach ([
            'general' => 'Algemeen',
            'pages' => 'Pages',
            'auth' => 'Auth',
            'redirects' => 'Redirects',
            'lifecycle' => 'Lifecycle',
            'shortcodes' => 'Shortcodes',
            'user_menu' => 'Profielmenu',
        ] as $key => $label) {
            $url = admin_url('admin.php?page=emonks-saas-settings&settings_tab=' . rawurlencode($key));
            echo '<a class="nav-tab ' . ($tab === $key ? 'nav-tab-active' : '') . '" href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
        }
        echo '</h2>';

        if ($tab === 'shortcodes') {
            $this->renderShortcodesPanel();
            echo '</div>';
            return;
        }

        if ($tab === 'pages') {
            $this->renderPagesPanel();
            echo '</div>';
            return;
        }

        if ($tab === 'auth') {
            $this->renderAuthPanel();
            echo '</div>';
            return;
        }

        if ($tab === 'redirects') {
            $this->renderRedirectsPanel();
            echo '</div>';
            return;
        }

        if ($tab === 'lifecycle') {
            $this->renderLifecyclePanel();
            echo '</div>';
            return;
        }

        if ($tab === 'user_menu') {
            $this->renderUserMenuPanel();
            echo '</div>';
            return;
        }

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

    public function renderUserMenuPage(): void
    {
        wp_safe_redirect(admin_url('admin.php?page=emonks-saas-settings&settings_tab=user_menu'));
        exit;
    }

    private function renderUserMenuPanel(): void
    {
        $service = Plugin::instance()->get('user_menu');
        if (! $service instanceof UserMenu) {
            echo '<p>User menu service niet beschikbaar.</p>';
            return;
        }

        $settings = $service->getSettings();
        $this->renderSettingsFormStart('user_menu');
        echo '<table class="form-table emonks-form-table">';
        echo '<tr><th scope="row">Inschakelen</th><td><label><input type="checkbox" name="settings[user_menu][enabled]" value="1" ' . checked(! empty($settings['enabled']), true, false) . ' /> Activeer profielmenu</label></td></tr>';
        echo '<tr><th scope="row">Profielfoto</th><td><label><input type="checkbox" name="settings[user_menu][show_avatar]" value="1" ' . checked(! empty($settings['show_avatar']), true, false) . ' /> Toon avatar/profielfoto</label></td></tr>';
        echo '<tr><th scope="row">Naamweergave</th><td><select name="settings[user_menu][name_mode]">';
        echo '<option value="full" ' . selected((string) ($settings['name_mode'] ?? 'full'), 'full', false) . '>Volledige naam</option>';
        echo '<option value="first" ' . selected((string) ($settings['name_mode'] ?? 'full'), 'first', false) . '>Alleen voornaam</option>';
        echo '<option value="display" ' . selected((string) ($settings['name_mode'] ?? 'full'), 'display', false) . '>Display name fallback</option>';
        echo '</select></td></tr>';
        echo '<tr><th scope="row">Type link</th><td><select name="settings[user_menu][link_type]">';
        echo '<option value="link" ' . selected((string) ($settings['link_type'] ?? 'link'), 'link', false) . '>Normale link</option>';
        echo '<option value="button" ' . selected((string) ($settings['link_type'] ?? 'link'), 'button', false) . '>Knop (btn-sm)</option>';
        echo '</select></td></tr>';
        echo '<tr><th scope="row">Knop stijl</th><td><select name="settings[user_menu][button_style]">';
        foreach ([
            'btn-light' => 'btn-light',
            'btn-outline-contrast' => 'btn-outline-contrast',
            'btn-dark' => 'btn-dark',
            'btn-primary' => 'btn-primary',
            'btn-gradient-light' => 'btn-gradient-light',
            'btn-gradient-dark' => 'btn-gradient-dark',
        ] as $styleValue => $styleLabel) {
            echo '<option value="' . esc_attr($styleValue) . '" ' . selected((string) ($settings['button_style'] ?? 'btn-primary'), $styleValue, false) . '>' . esc_html($styleLabel) . '</option>';
        }
        echo '</select><span class="description">In Skeletor menu-rendering zonder theme-aanpassing worden stijlen gemapt naar link, btn-primary of btn-secondary.</span></td></tr>';
        echo '<tr><th scope="row">Dropdown links</th><td>';
        foreach ([
            'account' => 'Account',
            'workspaces' => 'Workspaces',
            'settings' => 'Profiel',
            'billing' => 'Billing',
            'logout' => 'Uitloggen',
        ] as $key => $label) {
            echo '<label style="display:block;margin:4px 0;"><input type="checkbox" name="settings[user_menu][links][' . esc_attr($key) . ']" value="1" ' . checked(! empty($settings['links'][$key]), true, false) . ' /> ' . esc_html($label) . '</label>';
        }
        echo '</td></tr>';
        echo '</table>';
        submit_button('Profielmenu opslaan');
        $this->renderSettingsFormEnd();

        $menus = wp_get_nav_menus();
        $selectedMenuId = absint((string) ($_GET['menu_id'] ?? 0));
        echo '<hr style="margin:24px 0;" />';
        echo '<h2>Direct toevoegen aan bestaand menu</h2>';
        echo '<p class="description">Fallback als Weergave > Menu\'s geen item toevoegt. Dit voegt het profielmenu server-side toe.</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('emonks_user_menu_add_to_menu', 'emonks_user_menu_add_nonce');
        echo '<input type="hidden" name="action" value="emonks_user_menu_add_to_menu" />';
        echo '<p><label for="emonks_user_menu_target">Menu</label><br>';
        echo '<select id="emonks_user_menu_target" name="menu_id">';
        echo '<option value="">Selecteer menu...</option>';
        foreach ($menus as $menu) {
            if (! $menu instanceof \WP_Term) {
                continue;
            }
            echo '<option value="' . esc_attr((string) $menu->term_id) . '" ' . selected($selectedMenuId, (int) $menu->term_id, false) . '>' . esc_html((string) $menu->name) . '</option>';
        }
        echo '</select></p>';
        submit_button('Voeg profielmenu toe');
        echo '</form>';
    }

    private function renderPagesPanel(): void
    {
        $routes = [
            'account' => 'Account page',
            'workspaces' => 'Workspaces page',
            'billing' => 'Billing page',
            'settings' => 'Settings page',
            'onboarding' => 'Onboarding page',
            'login' => 'Login page',
            'register' => 'Register page',
            'logout' => 'Logout page',
        ];

        echo '<p class="description">Koppel specifieke WordPress-pagina\'s aan core routes. Bij een koppeling gebruikt de plugin de slug van die pagina als route. Leeg laten = standaard route.</p>';

        if (! empty($_GET['pages_updated'])) {
            echo '<div class="notice notice-warning inline"><p><strong>Let op:</strong> page routes zijn bijgewerkt. Als URL\'s nog niet direct werken, sla dan één keer permalinks opnieuw op via <em>Instellingen > Permalinks</em>.</p></div>';
        }
        if ((bool) emonks_get_setting('pages.conflict_guard.enabled', true)) {
            $conflicts = $this->detectPageAssignmentConflicts();
            if (! empty($conflicts)) {
                echo '<div class="notice notice-error inline"><p><strong>Route conflict guard:</strong> ' . esc_html(implode(' | ', $conflicts)) . '</p></div>';
            }
        }

        $this->renderSettingsFormStart('pages');
        echo '<table class="form-table emonks-form-table">';
        foreach ($routes as $routeKey => $label) {
            $selected = absint((string) emonks_get_setting('pages.' . $routeKey . '.page_id', 0));
            echo '<tr><th scope="row"><label for="emonks_pages_' . esc_attr($routeKey) . '">' . esc_html($label) . '</label></th><td>';
            wp_dropdown_pages([
                'name' => 'settings[pages][' . $routeKey . '][page_id]',
                'id' => 'emonks_pages_' . $routeKey,
                'show_option_none' => 'Standaard route gebruiken',
                'option_none_value' => '0',
                'selected' => $selected,
                'post_status' => ['publish'],
            ]);
            echo '<span class="description">Route key: <code>' . esc_html($routeKey) . '</code></span>';
            echo '</td></tr>';
        }
        echo '</table>';
        submit_button('Pages opslaan');
        $this->renderSettingsFormEnd();
    }

    private function renderAuthPanel(): void
    {
        $auth = emonks_get_auth_settings();
        $requiredFields = (array) ($auth['register']['required_fields'] ?? ['email', 'password']);

        echo '<p class="description">Beheer registratiegedrag, login-hardening en wachtwoordbeleid.</p>';
        $this->renderSettingsFormStart('auth');
        echo '<table class="form-table emonks-form-table">';
        echo '<tr><th scope="row">Registration status</th><td><select name="settings[auth][registration][status]">';
        echo '<option value="auto_approve" ' . selected((string) ($auth['registration']['status'] ?? 'auto_approve'), 'auto_approve', false) . '>Auto approve</option>';
        echo '<option value="email_verification" ' . selected((string) ($auth['registration']['status'] ?? 'auto_approve'), 'email_verification', false) . '>Email verification</option>';
        echo '<option value="admin_approval" ' . selected((string) ($auth['registration']['status'] ?? 'auto_approve'), 'admin_approval', false) . '>Admin approval</option>';
        echo '</select><span class="description">Bij email/admin approval wordt gebruiker niet automatisch ingelogd.</span></td></tr>';
        echo '<tr><th scope="row">Blokkeer wp-admin voor klanten</th><td><label><input type="checkbox" name="settings[auth][login][wp_admin_block_customers]" value="1" ' . checked(! empty($auth['login']['wp_admin_block_customers']), true, false) . ' /> Ja, stuur emonks_customer gebruikers naar account.</label></td></tr>';
        echo '<tr><th scope="row">Rate limit: max attempts</th><td><input type="number" min="1" max="20" name="settings[auth][login][rate_limit][max_attempts]" value="' . esc_attr((string) ($auth['login']['rate_limit']['max_attempts'] ?? 5)) . '" /></td></tr>';
        echo '<tr><th scope="row">Rate limit: lockout (minuten)</th><td><input type="number" min="1" max="240" name="settings[auth][login][rate_limit][lockout_minutes]" value="' . esc_attr((string) ($auth['login']['rate_limit']['lockout_minutes'] ?? 15)) . '" /></td></tr>';
        echo '<tr><th scope="row">Wachtwoord minimum lengte</th><td><input type="number" min="6" max="64" name="settings[auth][password][min_length]" value="' . esc_attr((string) ($auth['password']['min_length'] ?? 8)) . '" /></td></tr>';
        echo '<tr><th scope="row">Wachtwoord regels</th><td>';
        echo '<label style="display:block;margin:3px 0;"><input type="checkbox" name="settings[auth][password][require_uppercase]" value="1" ' . checked(! empty($auth['password']['require_uppercase']), true, false) . ' /> Vereis hoofdletter</label>';
        echo '<label style="display:block;margin:3px 0;"><input type="checkbox" name="settings[auth][password][require_number]" value="1" ' . checked(! empty($auth['password']['require_number']), true, false) . ' /> Vereis cijfer</label>';
        echo '<label style="display:block;margin:3px 0;"><input type="checkbox" name="settings[auth][password][require_symbol]" value="1" ' . checked(! empty($auth['password']['require_symbol']), true, false) . ' /> Vereis speciaal teken</label>';
        echo '</td></tr>';
        echo '<tr><th scope="row">Verplichte registervelden</th><td>';
        foreach (['email', 'password', 'first_name', 'company', 'phone'] as $fieldKey) {
            echo '<label style="display:block;margin:3px 0;"><input type="checkbox" name="settings[auth][register][required_fields][]" value="' . esc_attr($fieldKey) . '" ' . checked(in_array($fieldKey, $requiredFields, true), true, false) . ' /> ' . esc_html($fieldKey) . '</label>';
        }
        echo '</td></tr>';
        echo '</table>';
        submit_button('Auth instellingen opslaan');
        $this->renderSettingsFormEnd();
    }

    private function renderRedirectsPanel(): void
    {
        $settings = get_option(Settings::OPTION_KEY, []);
        $redirects = is_array($settings['redirects'] ?? null) ? $settings['redirects'] : [];

        echo '<p class="description">Bepaal bestemming na register, login en logout.</p>';
        $this->renderSettingsFormStart('redirects');
        echo '<table class="form-table emonks-form-table">';
        foreach ([
            'after_register' => 'Na registratie',
            'after_login' => 'Na login',
            'after_logout' => 'Na logout',
        ] as $eventKey => $label) {
            $type = sanitize_key((string) ($redirects[$eventKey]['type'] ?? 'default'));
            $target = (string) ($redirects[$eventKey]['target'] ?? '');
            echo '<tr><th scope="row">' . esc_html($label) . ' type</th><td><select name="settings[redirects][' . esc_attr($eventKey) . '][type]">';
            foreach (['default' => 'Default', 'route' => 'Route path', 'page' => 'WordPress page', 'custom_url' => 'Custom URL'] as $value => $text) {
                echo '<option value="' . esc_attr($value) . '" ' . selected($type, $value, false) . '>' . esc_html($text) . '</option>';
            }
            echo '</select></td></tr>';
            echo '<tr><th scope="row">' . esc_html($label) . ' target</th><td><input class="regular-text" name="settings[redirects][' . esc_attr($eventKey) . '][target]" value="' . esc_attr($target) . '" /><span class="description">Voor route: bijv. account/onboarding, voor page: page ID, voor custom URL: volledig URL of /pad.</span></td></tr>';
        }
        $roleTarget = sanitize_text_field((string) ($redirects['after_login']['by_role']['emonks_customer'] ?? ''));
        echo '<tr><th scope="row">Login redirect per rol</th><td><label>emonks_customer <input class="regular-text" name="settings[redirects][after_login][by_role][emonks_customer]" value="' . esc_attr($roleTarget) . '" /></label><span class="description">Overschrijft algemene login redirect als gevuld.</span></td></tr>';
        echo '</table>';
        submit_button('Redirect instellingen opslaan');
        $this->renderSettingsFormEnd();
    }

    private function renderLifecyclePanel(): void
    {
        $lifecycle = emonks_get_lifecycle_settings();
        $action = (string) ($lifecycle['account_delete']['action'] ?? 'soft_delete');
        $graceDays = (int) ($lifecycle['account_delete']['grace_days'] ?? 14);
        $retentionDays = (int) ($lifecycle['account_delete']['retention_days'] ?? 30);
        $guardEnabled = (bool) emonks_get_setting('pages.conflict_guard.enabled', true);

        echo '<p class="description">Lifecycle policies voor account verwijdering en routing safety.</p>';
        $this->renderSettingsFormStart('lifecycle');
        echo '<table class="form-table emonks-form-table">';
        echo '<tr><th scope="row">Account delete action</th><td><select name="settings[lifecycle][account_delete][action]">';
        echo '<option value="soft_delete" ' . selected($action, 'soft_delete', false) . '>Soft delete</option>';
        echo '<option value="hard_delete" ' . selected($action, 'hard_delete', false) . '>Hard delete</option>';
        echo '</select><span class="description">Hard delete verwijdert data direct en is risicovol.</span></td></tr>';
        echo '<tr><th scope="row">Grace period (dagen)</th><td><input type="number" min="0" max="365" name="settings[lifecycle][account_delete][grace_days]" value="' . esc_attr((string) $graceDays) . '" /></td></tr>';
        echo '<tr><th scope="row">Retentie (dagen)</th><td><input type="number" min="0" max="3650" name="settings[lifecycle][account_delete][retention_days]" value="' . esc_attr((string) $retentionDays) . '" /></td></tr>';
        echo '<tr><th scope="row">Route conflict guard</th><td><label><input type="checkbox" name="settings[pages][conflict_guard][enabled]" value="1" ' . checked($guardEnabled, true, false) . ' /> Waarschuw bij dubbele page/slug mappings.</label></td></tr>';
        echo '</table>';
        submit_button('Lifecycle instellingen opslaan');
        $this->renderSettingsFormEnd();
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
        wp_safe_redirect(admin_url('admin.php?page=emonks-saas-settings&settings_tab=shortcodes'));
        exit;
    }

    private function renderShortcodesPanel(): void
    {
        $catalog = class_exists(Shortcodes::class) ? Shortcodes::catalog() : [];

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
    }

    public function renderDocsPage(): void
    {
        $sections = $this->docsSections();
        $active = sanitize_key((string) ($_GET['docs_section'] ?? 'overview'));
        if (! isset($sections[$active])) {
            $active = 'overview';
        }

        echo '<div class="wrap">';
        echo '<div class="emonks-support-hero">';
        echo '<h1>Emonks SaaS Docs & Support</h1>';
        echo '<p class="emonks-muted">Handleiding, support-checklists en technische referentie voor beheer, Skeletor-integratie en troubleshooting. Gebruik dit als startpunt voordat je instellingen wijzigt of bugs onderzoekt.</p>';
        echo '<div class="emonks-support-actions">';
        echo '<a class="button button-primary" href="' . esc_url(admin_url('admin.php?page=emonks-saas-health')) . '">Open SaaS Health</a>';
        echo '<a class="button" href="' . esc_url(admin_url('admin.php?page=emonks-saas-logs')) . '">Bekijk Logs</a>';
        echo '<a class="button" href="' . esc_url(admin_url('admin.php?page=emonks-saas-settings&settings_tab=shortcodes')) . '">Shortcodes tab</a>';
        echo '</div></div>';

        echo '<div class="emonks-support-shell">';
        $this->renderDocsNavigation($sections, $active);
        echo '<div class="emonks-card">';
        echo '<h2 style="margin-top:0;">' . esc_html($sections[$active]) . '</h2>';
        $this->renderDocsSection($active);
        echo '</div></div></div>';
    }

    /** @return array<string,string> */
    private function docsSections(): array
    {
        return [
            'overview' => 'Support overzicht',
            'setup' => 'Startgids & setup',
            'beheer' => 'Beheerhandleiding',
            'skeletor' => 'Skeletor theme gebruik',
            'shortcodes' => 'Shortcodes',
            'developer' => 'Developer referentie',
            'troubleshooting' => 'Troubleshooting',
            'ideas' => 'Gebruiksideeen',
            'files' => 'Markdown docs',
        ];
    }

    /** @param array<string,string> $sections */
    private function renderDocsNavigation(array $sections, string $active): void
    {
        echo '<div class="emonks-support-nav">';
        foreach ($sections as $key => $label) {
            $url = admin_url('admin.php?page=emonks-saas-docs&docs_section=' . rawurlencode((string) $key));
            echo '<a class="' . ($active === $key ? 'is-active' : '') . '" href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
        }
        echo '</div>';
    }

    private function renderDocsSection(string $section): void
    {
        if ($section === 'overview') {
            echo '<div class="emonks-support-callout"><strong>Support workflow</strong><br>Begin bij SaaS Health, controleer daarna Logs, reproduceer de flow met een testgebruiker en pas dan pas instellingen of templates aan.</div>';
            echo '<div class="emonks-grid">';
            echo '<div><h3>Voor beheerders</h3><ul><li>Dashboard voor adoptie en setup-status.</li><li>SaaS Health voor ontbrekende routes, settings en koppelingen.</li><li>Billing voor test mode, checkout en provider-readiness.</li><li>Logs voor recente plugin-events.</li></ul></div>';
            echo '<div><h3>Voor developers</h3><ul><li>Theme overrides blijven in Skeletor.</li><li>Plugin bewaakt routes, permissions, billing en opslag.</li><li>Gebruik helpers, filters en shortcodes voor snelle integratie.</li><li>Raadpleeg Markdown docs voor verdieping.</li></ul></div>';
            echo '</div>';
            return;
        }

        if ($section === 'setup') {
            echo '<ol class="emonks-support-checklist"><li><strong>Installatie</strong><br>Plaats de plugin in <code>/wp-content/plugins/emonks-saas-core/</code>, activeer hem en draai <code>composer install</code> in de pluginmap voor Timber 2.</li><li><strong>Permalinks</strong><br>Sla WordPress permalinks opnieuw op zodat account-, auth- en public routes geregistreerd zijn.</li><li><strong>Eerste inrichting</strong><br>Maak eerst een service, daarna velden, formulieren, features en plannen.</li><li><strong>Controle</strong><br>Open SaaS Health en los waarschuwingen op voordat je met echte gebruikers test.</li></ol>';
            return;
        }

        if ($section === 'beheer') {
            echo '<table class="widefat striped"><thead><tr><th>Onderdeel</th><th>Gebruik</th><th>Supportvraag</th></tr></thead><tbody>';
            foreach ([
                ['Dashboard', 'Adoptie, setup, subscriptions en planverdeling.', 'Klopt de basisstatus van de SaaS?'],
                ['Services', 'Product- of dienstdefinities met form usages en render hints.', 'Is de juiste service actief en gekoppeld?'],
                ['Fields', 'Centrale veldbibliotheek.', 'Bestaat het veld en heeft het de juiste source?'],
                ['Forms', 'Formuliersamenstellingen per context.', 'Gebruikt de flow het juiste formulier?'],
                ['Features', 'Feature flags en feature-termen.', 'Blokkeert een feature gate de gebruiker?'],
                ['Plans', 'Limieten, prijzen, services en Stripe IDs.', 'Heeft het plan toegang tot deze service?'],
                ['Billing', 'Test mode, checkout, portal en provider instellingen.', 'Gaat de gebruiker door checkout of simulatie?'],
                ['Workspaces', 'Workspace posts en metadata.', 'Bestaat de workspace en is ownership correct?'],
                ['SaaS Health', 'Automatische statuschecks.', 'Welke dependency of koppeling ontbreekt?'],
                ['Logs', 'Recente plugin-events.', 'Welke actie ging net mis?'],
            ] as $row) {
                echo '<tr><td><strong>' . esc_html($row[0]) . '</strong></td><td>' . esc_html($row[1]) . '</td><td>' . esc_html($row[2]) . '</td></tr>';
            }
            echo '</tbody></table>';
            return;
        }

        if ($section === 'skeletor') {
            echo '<div class="emonks-grid">';
            echo '<div><h3>Template overrides</h3><p>De loader zoekt eerst in het actieve theme en daarna in de plugin fallback. Plaats overrides in <code>/templates/...</code>, bijvoorbeeld <code>/templates/account/dashboard.twig</code> of <code>/templates/account/login.twig</code>.</p></div>';
            echo '<div><h3>Twig context</h3><p>Templates krijgen service schema\'s, field library, form templates, resolved forms, workspace data en ACF-data. Houd layout en Bootstrap markup in Skeletor; houd rechten, opslag en routing in de plugin.</p></div>';
            echo '<div><h3>Filters</h3><p>Gebruik <code>emonks_saas_labels</code> voor labels en <code>emonks_saas_routes</code> voor URL-segmenten.</p></div>';
            echo '<div><h3>Styling</h3><p>Fallback templates gebruiken classes zoals <code>btn</code>, <code>form-control</code>, <code>alert</code>, <code>badge</code> en <code>list-group</code>.</p></div>';
            echo '</div>';
            return;
        }

        if ($section === 'shortcodes') {
            echo '<table class="widefat striped"><thead><tr><th>Voorbeeld</th><th>Doel</th><th>Typisch gebruik</th></tr></thead><tbody>';
            foreach ([
                ['[emonks_form key="auth_login"]', 'Rendert loginformulier uit Forms.', 'Loginpagina of modal-content.'],
                ['[emonks_form key="workspace_create"]', 'Rendert workspace-create formulier.', 'Nieuwe workspace of intake.'],
                ['[emonks_workspace_list]', 'Toont workspaces van de ingelogde gebruiker.', 'Accountdashboard of klantportaal.'],
                ['[emonks_account_link suffix="billing" label="Ga naar billing"]', 'Maakt link naar account-subroute.', 'CTA naar billing, settings of workspaces.'],
                ['[emonks_user_menu]', 'Toont profielmenu.', 'Header, accountnavigatie of menu fallback.'],
            ] as $row) {
                echo '<tr><td><code>' . esc_html($row[0]) . '</code></td><td>' . esc_html($row[1]) . '</td><td>' . esc_html($row[2]) . '</td></tr>';
            }
            echo '</tbody></table>';
            echo '<p class="emonks-muted">Tip: gebruik shortcodes voor MVP-validatie en verplaats presentatie later naar Twig templates.</p>';
            return;
        }

        if ($section === 'developer') {
            echo '<div class="emonks-grid">';
            echo '<div><h3>Routes & REST</h3><p>Frontend routes: <code>/account</code>, <code>/account/workspaces</code>, <code>/account/billing</code>, <code>/login</code>, <code>/register</code> en <code>/g/{public_slug}</code>. REST namespace: <code>/wp-json/emonks/v1/</code>.</p></div>';
            echo '<div><h3>Helpers</h3><p><code>emonks_get_plans()</code>, <code>emonks_get_plan()</code>, <code>emonks_get_current_user_plan()</code>, <code>emonks_get_plan_limit()</code>, <code>emonks_plan_has_feature()</code>, <code>emonks_feature_enabled()</code>, <code>emonks_get_account_url()</code>.</p></div>';
            echo '<div><h3>Billing provider</h3><p>Billing loopt via een provider-interface. Stripe is standaard, maar een andere provider kan dezelfde checkout-, portal- en plan-change verantwoordelijkheden overnemen.</p></div>';
            echo '<div><h3>Security</h3><p>Nonce checks, sanitization, ownership checks, capabilities, safe redirects, webhook signature verificatie en idempotency zijn onderdeel van de pluginlaag.</p></div>';
            echo '</div>';
            return;
        }

        if ($section === 'troubleshooting') {
            echo '<ol class="emonks-support-checklist"><li><strong>Route werkt niet</strong><br>Sla permalinks opnieuw op en controleer <code>Emonks SaaS > SaaS Health</code>.</li><li><strong>Formulier toont niet</strong><br>Controleer of de form key bestaat in Forms en of velden in Fields nog geldig zijn.</li><li><strong>Gebruiker ziet geen workspace</strong><br>Controleer loginstatus, account membership, ownership en workspace status.</li><li><strong>Billing werkt niet</strong><br>Controleer test mode, Stripe constants, price IDs, webhook secret en Logs.</li><li><strong>Theme ziet er vreemd uit</strong><br>Controleer template override pad, Bootstrap classes en of Skeletor styles de fallback markup ondersteunen.</li></ol>';
            return;
        }

        if ($section === 'ideas') {
            echo '<ul><li>Gated dashboards per plan of feature.</li><li>Klantportalen met workspace-status, documenten en service-specifieke acties.</li><li>Intake-flows waarbij een formulier direct een workspace aanmaakt.</li><li>Publieke workspace pagina\'s voor portfolio, lead capture of deelbare klantresultaten.</li><li>Service-specifieke Twig templates voor verschillende verticals binnen dezelfde SaaS-core.</li><li>Onboarding-checklists langs profiel, billing en eerste workspace.</li><li>Profielmenu in de Skeletor header via menu-item of shortcode.</li><li>MVP-validatie met shortcodes voordat een volledige custom UI wordt gebouwd.</li></ul>';
            return;
        }

        echo '<table class="widefat striped"><thead><tr><th>Bestand</th><th>Onderwerp</th></tr></thead><tbody>';
        foreach ([
            ['docs/architecture.md', 'Architectuur en lagen.'],
            ['docs/services.md', 'Dynamic services, form usages en publicatie.'],
            ['docs/fields.md', 'Veldbibliotheek en sources.'],
            ['docs/forms.md', 'Form templates en rendercontext.'],
            ['docs/billing.md', 'Billingflows, provider model, test mode en webhooks.'],
            ['docs/routes.md', 'Account, auth en public routes.'],
            ['docs/page-assignments.md', 'Page-to-route koppelingen voor core pagina\'s.'],
            ['docs/auth-redirects-lifecycle-settings.md', 'Auth, redirects en lifecycle settings.'],
            ['docs/rest-api.md', 'REST API endpoints.'],
            ['docs/theme-overrides.md', 'Labels, routes en template overrides in het theme.'],
            ['docs/template-overrides.md', 'Template resolution en Twig context.'],
            ['docs/user-menu.md', 'Profielmenu instellingen, menu-integratie en shortcode.'],
            ['docs/security.md', 'Security basisregels.'],
            ['docs/security-avg-checklist.md', 'Security en AVG checklist.'],
            ['docs/qa-smoke.md', 'Smoke tests voor installatie en beheerflows.'],
        ] as $row) {
            echo '<tr><td><code>' . esc_html($row[0]) . '</code></td><td>' . esc_html($row[1]) . '</td></tr>';
        }
        echo '</tbody></table>';
    }

    public function renderSetupPage(): void
    {
        wp_safe_redirect(admin_url('admin.php?page=emonks-saas-core'));
        exit;
    }

    private function renderSetupPanel(): void
    {
        $services = emonks_get_service_schemas()['services'] ?? [];
        $fields = emonks_get_field_library()['fields'] ?? [];
        $forms = emonks_get_form_templates()['forms'] ?? [];
        $defaultContextForms = $this->defaultServiceContextForms($forms);
        $plans = Plans::getPlans();
        echo '<div class="emonks-grid">';
        $this->renderKpiCard('Services', (string) count((array) $services));
        $this->renderKpiCard('Velden', (string) count((array) $fields));
        $this->renderKpiCard('Formulieren', (string) count((array) $forms));
        $this->renderKpiCard('Plannen', (string) count((array) $plans));
        echo '</div>';
        echo '<div class="emonks-card" style="margin-top:18px;"><h2 style="margin-top:0;">Aanbevolen volgorde</h2>';
        echo '<ol><li>Maak je eerste service aan.</li><li>Controleer of de standaard core velden genoeg zijn of voeg velden toe.</li><li>Stel formulieren samen en koppel ze aan service-contexten.</li><li>Maak features en plannen aan zodra de service logisch werkt.</li><li>Controleer SaaS Health voor ontbrekende koppelingen.</li></ol>';
        echo '<p><a class="button button-primary" href="' . esc_url(admin_url('admin.php?page=emonks-saas-services')) . '">Nieuwe service starten</a></p></div>';
        echo '<p class="emonks-subtle">Default context forms: ' . esc_html(implode(', ', array_keys($defaultContextForms))) . '.</p>';
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

    public function renderEmailsPage(): void
    {
        $emailsTab = sanitize_key((string) ($_GET['emails_tab'] ?? 'emails'));
        if (! in_array($emailsTab, ['emails', 'template'], true)) {
            $emailsTab = 'emails';
        }

        $templates = emonks_get_email_templates()['templates'] ?? [];
        $activeKey = sanitize_key((string) ($_GET['email'] ?? ''));
        if ($activeKey === '' && ! empty($templates)) {
            $activeKey = (string) array_key_first($templates);
        }
        $activeTemplate = is_array($templates[$activeKey] ?? null) ? $templates[$activeKey] : [];

        echo '<div class="wrap">';
        $this->renderPageHeader('Emonks SaaS - Emails', 'Dynamische mailflows per actie met slimme velden.', 'Koppel e-mails aan events en gebruik tokens zoals {{ user.email }} in onderwerp en inhoud.');
        echo '<h2 class="nav-tab-wrapper" style="margin-bottom:14px;">';
        echo '<a class="nav-tab ' . ($emailsTab === 'emails' ? 'nav-tab-active' : '') . '" href="' . esc_url(admin_url('admin.php?page=emonks-saas-emails&emails_tab=emails')) . '">Emails</a>';
        echo '<a class="nav-tab ' . ($emailsTab === 'template' ? 'nav-tab-active' : '') . '" href="' . esc_url(admin_url('admin.php?page=emonks-saas-emails&emails_tab=template')) . '">Template</a>';
        echo '</h2>';

        if ($emailsTab === 'template') {
            $this->renderEmailLayoutPanel();
            echo '</div>';
            return;
        }

        echo '<div class="emonks-services-shell">';

        echo '<div class="emonks-card emonks-services-sidebar">';
        echo '<h2 style="margin-top:0;">Templates</h2>';
        echo '<p><button type="button" class="button button-primary" data-toggle-email-create>Nieuwe template</button></p>';
        echo '<div class="emonks-service-form" data-email-create-form>';
        $this->renderEmailCreateForm();
        echo '</div>';
        echo '<div class="emonks-builder-list">';
        foreach ($templates as $templateKey => $template) {
            if (! is_array($template)) {
                continue;
            }
            $safeKey = sanitize_key((string) $templateKey);
            $label = sanitize_text_field((string) ($template['label'] ?? $safeKey));
            $enabled = ! empty($template['enabled']);
            $url = admin_url('admin.php?page=emonks-saas-emails&email=' . rawurlencode($safeKey));
            echo '<a class="emonks-service-item ' . ($safeKey === $activeKey ? 'is-active' : '') . '" href="' . esc_url($url) . '"><span><strong>' . esc_html($label) . '</strong><br><span class="emonks-subtle"><code>' . esc_html($safeKey) . '</code> - ' . esc_html((string) ($template['trigger'] ?? '')) . '</span></span><span class="emonks-pill">' . esc_html($enabled ? 'on' : 'off') . '</span></a>';
        }
        echo '</div>';
        echo '</div>';

        echo '<div class="emonks-card">';
        if ($activeKey === '' || empty($activeTemplate)) {
            echo '<p>Selecteer links een template of maak een nieuwe aan.</p>';
            echo '</div></div></div>';
            echo '<script>document.addEventListener("DOMContentLoaded",function(){document.querySelectorAll("[data-toggle-email-create]").forEach(function(btn){btn.addEventListener("click",function(){document.querySelectorAll("[data-email-create-form]").forEach(function(p){p.classList.toggle("is-open")})})});});</script>';
            return;
        }

        $targets = is_array($activeTemplate['recipients']['targets'] ?? null) ? $activeTemplate['recipients']['targets'] : ['current_user'];
        $extras = is_array($activeTemplate['recipients']['extra'] ?? null) ? $activeTemplate['recipients']['extra'] : [];
        $tokenContext = emonks_build_email_token_context([]);
        $preview = emonks_render_email_template_strings($activeTemplate, $tokenContext);

        echo '<h2 style="margin-top:0;">' . esc_html((string) ($activeTemplate['label'] ?? $activeKey)) . ' <code>' . esc_html($activeKey) . '</code></h2>';
        $this->renderSettingsFormStart('emails_builder');
        echo '<input type="hidden" name="emails_builder[action]" value="update" />';
        echo '<input type="hidden" name="emails_builder[email_key]" value="' . esc_attr($activeKey) . '" />';

        echo '<div class="emonks-editor-section"><h3 class="emonks-editor-title">Basis</h3><div class="emonks-settings-grid">';
        echo '<p><label>Label<input type="text" name="emails_builder[email][label]" class="regular-text" value="' . esc_attr((string) ($activeTemplate['label'] ?? $activeKey)) . '" /></label></p>';
        echo '<p><label>Trigger<select name="emails_builder[email][trigger]">';
        foreach ($this->emailTriggerOptions() as $trigger => $label) {
            echo '<option value="' . esc_attr($trigger) . '" ' . selected((string) ($activeTemplate['trigger'] ?? ''), $trigger, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select></label></p>';
        echo '<p class="checkbox-row"><label><input type="checkbox" name="emails_builder[email][enabled]" value="1" ' . checked(! empty($activeTemplate['enabled']), true, false) . ' /> Ingeschakeld</label></p>';
        echo '</div></div>';

        echo '<div class="emonks-editor-section"><h3 class="emonks-editor-title">Ontvangers</h3><div class="emonks-settings-grid">';
        foreach (['current_user' => 'Current user', 'account_owner' => 'Account owner', 'site_admin' => 'Site admin'] as $target => $label) {
            echo '<p class="checkbox-row"><label><input type="checkbox" name="emails_builder[email][recipients][targets][]" value="' . esc_attr($target) . '" ' . checked(in_array($target, $targets, true), true, false) . ' /> ' . esc_html($label) . '</label></p>';
        }
        echo '<p class="full"><label>Extra e-mails (komma gescheiden)<input type="text" name="emails_builder[email][recipients][extra_csv]" class="regular-text" value="' . esc_attr(implode(', ', array_map('strval', $extras))) . '" /></label></p>';
        echo '</div></div>';

        echo '<div class="emonks-editor-section"><h3 class="emonks-editor-title">Onderwerp</h3>';
        echo '<p><input type="text" name="emails_builder[email][subject]" class="large-text" value="' . esc_attr((string) ($activeTemplate['subject'] ?? '')) . '" /></p>';
        echo '</div>';

        echo '<div class="emonks-editor-section"><h3 class="emonks-editor-title">Visual (HTML)</h3>';
        wp_editor((string) ($activeTemplate['body_html'] ?? ''), 'emails_builder_body_html', [
            'textarea_name' => 'emails_builder[email][body_html]',
            'textarea_rows' => 12,
            'media_buttons' => false,
        ]);
        echo '</div>';
        echo '<div class="emonks-editor-section"><h3 class="emonks-editor-title">Text</h3>';
        echo '<p><textarea name="emails_builder[email][body_text]" rows="12" class="large-text code">' . esc_textarea((string) ($activeTemplate['body_text'] ?? '')) . '</textarea></p>';
        echo '</div>';

        echo '<div class="emonks-editor-section"><h3 class="emonks-editor-title">Geavanceerd</h3><div class="emonks-settings-grid">';
        echo '<p><label>From name<input type="text" name="emails_builder[email][from_name]" class="regular-text" value="' . esc_attr((string) ($activeTemplate['from_name'] ?? '')) . '" /></label></p>';
        echo '<p><label>From e-mail<input type="email" name="emails_builder[email][from_email]" class="regular-text" value="' . esc_attr((string) ($activeTemplate['from_email'] ?? '')) . '" /></label></p>';
        echo '<p><label>Reply-to<input type="email" name="emails_builder[email][reply_to]" class="regular-text" value="' . esc_attr((string) ($activeTemplate['reply_to'] ?? '')) . '" /></label></p>';
        echo '<p><label>Conditions (JSON)<input type="text" name="emails_builder[email][conditions_json]" class="regular-text" value="' . esc_attr(wp_json_encode($activeTemplate['conditions'] ?? [])) . '" /></label></p>';
        echo '</div></div>';

        echo '<div class="emonks-editor-section"><h3 class="emonks-editor-title">Slimme velden</h3><ul>';
        foreach (emonks_email_token_catalog() as $token => $description) {
            echo '<li><code>{{ ' . esc_html($token) . ' }}</code> - ' . esc_html($description) . '</li>';
        }
        echo '</ul></div>';

        echo '<div class="emonks-editor-section"><h3 class="emonks-editor-title">Preview</h3>';
        echo '<p><strong>Onderwerp:</strong> ' . esc_html((string) ($preview['subject'] ?? '')) . '</p>';
        echo '<p><strong>HTML:</strong></p><div style="border:1px solid #dcdcde;padding:10px;background:#fff;">' . wp_kses_post((string) ($preview['body_html'] ?? '')) . '</div>';
        echo '<p><strong>Text:</strong></p><pre style="white-space:pre-wrap;">' . esc_html((string) ($preview['body_text'] ?? '')) . '</pre>';
        echo '</div>';

        submit_button('Email template opslaan');
        $this->renderSettingsFormEnd();

        echo '<div class="emonks-danger-zone"><h3>Template verwijderen</h3>';
        $this->renderSettingsFormStart('emails_builder');
        echo '<input type="hidden" name="emails_builder[action]" value="delete" />';
        echo '<input type="hidden" name="emails_builder[email_key]" value="' . esc_attr($activeKey) . '" />';
        echo '<button type="submit" class="button button-link-delete" onclick="return confirm(\'Weet je zeker dat je deze template wilt verwijderen?\')">Template verwijderen</button>';
        $this->renderSettingsFormEnd();
        echo '</div>';

        echo '</div></div></div>';
        echo '<script>document.addEventListener("DOMContentLoaded",function(){document.querySelectorAll("[data-toggle-email-create]").forEach(function(btn){btn.addEventListener("click",function(){document.querySelectorAll("[data-email-create-form]").forEach(function(p){p.classList.toggle("is-open")})})});});</script>';
    }

    private function renderEmailLayoutPanel(): void
    {
        $layout = emonks_get_email_layout_settings();
        $htmlTemplate = (string) ($layout['html_template'] ?? '');

        echo '<div class="emonks-card">';
        echo '<h2 style="margin-top:0;">Globale E-mail Template</h2>';
        echo '<p class="description">Deze wrapper wordt op alle e-mails toegepast. Gebruik <code>[email_content]</code> of <code>{{ content_html }}</code> / <code>{{ content_text }}</code> als placeholder voor template-inhoud.</p>';

        $this->renderSettingsFormStart('emails_template');
        echo '<div class="emonks-editor-section"><h3 class="emonks-editor-title">Layout</h3>';
        echo '<p class="description">Gebruik Visueel voor eenvoudige opmaak en Tekst voor exacte HTML. De inhoud-placeholder blijft verplicht: <code>[email_content]</code>.</p>';
        wp_editor($htmlTemplate, 'emonkslayouttemplate', [
            'textarea_name' => 'email_layout_html_raw',
            'textarea_rows' => 18,
            'media_buttons' => true,
            'quicktags' => true,
            'tinymce' => true,
        ]);
        echo '<script>document.addEventListener("DOMContentLoaded",function(){var editor=document.getElementById("emonkslayouttemplate");if(!editor){return;}var form=editor.closest("form");if(!form){return;}form.addEventListener("submit",function(){if(window.tinyMCE&&typeof window.tinyMCE.triggerSave==="function"){window.tinyMCE.triggerSave();}});});</script>';
        echo '</div>';

        submit_button('E-mail template opslaan');
        $this->renderSettingsFormEnd();
        echo '</div>';
    }

    private function renderEmailCreateForm(): void
    {
        $this->renderSettingsFormStart('emails_builder');
        echo '<input type="hidden" name="emails_builder[action]" value="create" />';
        echo '<p><label>Key<br><input type="text" name="emails_builder[new][key]" class="regular-text" placeholder="bijv custom_notification" /></label></p>';
        echo '<p><label>Label<br><input type="text" name="emails_builder[new][label]" class="regular-text" /></label></p>';
        echo '<p><label>Trigger<br><select name="emails_builder[new][trigger]">';
        foreach ($this->emailTriggerOptions() as $trigger => $label) {
            echo '<option value="' . esc_attr($trigger) . '">' . esc_html($label) . '</option>';
        }
        echo '</select></label></p>';
        submit_button('Template aanmaken', 'secondary', 'submit', false);
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

    public function renderPendingUsersPage(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die('Unauthorized', 403);
        }

        $statusNotice = sanitize_key((string) ($_GET['approval_status'] ?? ''));
        $message = match ($statusNotice) {
            'approved' => 'Gebruiker is goedgekeurd.',
            'rejected' => 'Gebruiker is afgewezen.',
            'noop' => 'Geen wijziging nodig.',
            'error' => 'Actie kon niet worden uitgevoerd.',
            default => '',
        };

        $pendingUsers = get_users([
            'meta_key' => 'emonks_registration_status',
            'meta_value' => 'pending',
            'orderby' => 'registered',
            'order' => 'ASC',
        ]);

        echo '<div class="wrap">';
        $this->renderPageHeader('Emonks SaaS - Pending Users', 'Beheer gebruikers die wachten op activatie.', 'Approve activeert de gebruiker. Reject markeert als rejected en blokkeert login.');
        if ($message !== '') {
            echo '<div class="notice notice-info inline"><p>' . esc_html($message) . '</p></div>';
        }

        echo '<table class="widefat striped"><thead><tr><th>User</th><th>Email</th><th>Registered</th><th>Mode</th><th>Verified</th><th>Acties</th></tr></thead><tbody>';
        if (empty($pendingUsers)) {
            echo '<tr><td colspan="6">Geen pending users gevonden.</td></tr>';
        } else {
            foreach ($pendingUsers as $user) {
                if (! $user instanceof \WP_User) {
                    continue;
                }
                $userId = (int) $user->ID;
                $mode = sanitize_key((string) get_user_meta($userId, 'emonks_registration_mode', true));
                $verifiedAt = (string) get_user_meta($userId, 'emonks_registration_verified_at', true);
                $approveUrl = wp_nonce_url(add_query_arg([
                    'action' => 'emonks_saas_approval_action',
                    'decision' => 'approve',
                    'user_id' => $userId,
                ], admin_url('admin-post.php')), 'emonks_saas_approval_action_' . $userId, 'emonks_nonce');
                $rejectUrl = wp_nonce_url(add_query_arg([
                    'action' => 'emonks_saas_approval_action',
                    'decision' => 'reject',
                    'user_id' => $userId,
                ], admin_url('admin-post.php')), 'emonks_saas_approval_action_' . $userId, 'emonks_nonce');

                echo '<tr>';
                echo '<td>' . esc_html((string) $user->display_name) . '</td>';
                echo '<td>' . esc_html((string) $user->user_email) . '</td>';
                echo '<td>' . esc_html((string) $user->user_registered) . '</td>';
                echo '<td><code>' . esc_html($mode !== '' ? $mode : 'unknown') . '</code></td>';
                echo '<td>' . esc_html($verifiedAt !== '' ? $verifiedAt : 'no') . '</td>';
                echo '<td><a class="button button-primary" href="' . esc_url($approveUrl) . '">Approve</a> ';
                echo '<a class="button" href="' . esc_url($rejectUrl) . '">Reject</a></td>';
                echo '</tr>';
            }
        }
        echo '</tbody></table>';
        echo '</div>';
    }

    public function handleApprovalAction(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die('Unauthorized', 403);
        }

        $userId = absint((string) ($_GET['user_id'] ?? 0));
        $decision = sanitize_key((string) ($_GET['decision'] ?? ''));
        check_admin_referer('emonks_saas_approval_action_' . $userId, 'emonks_nonce');

        $status = 'error';
        if ($userId > 0 && in_array($decision, ['approve', 'reject'], true)) {
            $currentStatus = sanitize_key((string) get_user_meta($userId, 'emonks_registration_status', true));
            if ($currentStatus !== 'pending') {
                $status = 'noop';
            } else {
                if ($decision === 'approve') {
                    update_user_meta($userId, 'emonks_registration_status', 'active');
                    update_user_meta($userId, 'emonks_registration_approved_at', wp_date('c'));
                    $status = 'approved';
                } else {
                    update_user_meta($userId, 'emonks_registration_status', 'rejected');
                    update_user_meta($userId, 'emonks_registration_rejected_at', wp_date('c'));
                    $status = 'rejected';
                }
                do_action('emonks_auth_admin_approval_result', $userId, $decision);
            }
        }

        wp_safe_redirect(add_query_arg([
            'page' => 'emonks-saas-pending-users',
            'approval_status' => $status,
        ], admin_url('admin.php')));
        exit;
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
        } elseif ($tab === 'pages') {
            $incomingPages = is_array($sanitized['pages'] ?? null) ? $sanitized['pages'] : [];
            $routeKeys = ['account', 'workspaces', 'billing', 'settings', 'onboarding', 'login', 'register', 'logout'];
            $existingConflictGuard = (bool) emonks_get_setting('pages.conflict_guard.enabled', true);
            $current['pages'] = [];
            foreach ($routeKeys as $routeKey) {
                $pageId = absint((string) ($incomingPages[$routeKey]['page_id'] ?? 0));
                if ($pageId > 0) {
                    $page = get_post($pageId);
                    if (! $page instanceof \WP_Post || $page->post_type !== 'page' || $page->post_status !== 'publish') {
                        $pageId = 0;
                    }
                }
                $current['pages'][$routeKey] = ['page_id' => $pageId];
            }
            $current['pages']['conflict_guard'] = ['enabled' => $existingConflictGuard];

            $redirectUrl = add_query_arg([
                'page' => 'emonks-saas-settings',
                'settings_tab' => 'pages',
                'pages_updated' => '1',
            ], admin_url('admin.php'));
        } elseif ($tab === 'auth') {
            $incoming = is_array($sanitized['auth'] ?? null) ? $sanitized['auth'] : [];
            $status = sanitize_key((string) ($incoming['registration']['status'] ?? 'auto_approve'));
            if (! in_array($status, ['auto_approve', 'email_verification', 'admin_approval'], true)) {
                $status = 'auto_approve';
            }
            $requiredFields = is_array($incoming['register']['required_fields'] ?? null) ? $incoming['register']['required_fields'] : [];
            $requiredFields = array_values(array_filter(array_map(static fn($item) => sanitize_key((string) $item), $requiredFields)));
            if (empty($requiredFields)) {
                $requiredFields = ['email', 'password'];
            }
            $current['auth'] = [
                'registration' => ['status' => $status],
                'login' => [
                    'wp_admin_block_customers' => ! empty($incoming['login']['wp_admin_block_customers']),
                    'rate_limit' => [
                        'max_attempts' => max(1, min(20, absint((string) ($incoming['login']['rate_limit']['max_attempts'] ?? 5)))),
                        'lockout_minutes' => max(1, min(240, absint((string) ($incoming['login']['rate_limit']['lockout_minutes'] ?? 15)))),
                    ],
                ],
                'password' => [
                    'min_length' => max(6, min(64, absint((string) ($incoming['password']['min_length'] ?? 8)))),
                    'require_uppercase' => ! empty($incoming['password']['require_uppercase']),
                    'require_number' => ! empty($incoming['password']['require_number']),
                    'require_symbol' => ! empty($incoming['password']['require_symbol']),
                ],
                'register' => [
                    'required_fields' => $requiredFields,
                ],
            ];
        } elseif ($tab === 'redirects') {
            $incoming = is_array($sanitized['redirects'] ?? null) ? $sanitized['redirects'] : [];
            $current['redirects'] = [];
            foreach (['after_register', 'after_login', 'after_logout'] as $eventKey) {
                $type = sanitize_key((string) ($incoming[$eventKey]['type'] ?? 'default'));
                if (! in_array($type, ['default', 'route', 'page', 'custom_url'], true)) {
                    $type = 'default';
                }
                $target = sanitize_text_field((string) ($incoming[$eventKey]['target'] ?? ''));
                $current['redirects'][$eventKey] = ['type' => $type, 'target' => $target];
            }
            $roleTarget = sanitize_text_field((string) ($incoming['after_login']['by_role']['emonks_customer'] ?? ''));
            $current['redirects']['after_login']['by_role'] = ['emonks_customer' => $roleTarget];
        } elseif ($tab === 'lifecycle') {
            $incomingLifecycle = is_array($sanitized['lifecycle'] ?? null) ? $sanitized['lifecycle'] : [];
            $incomingPages = is_array($sanitized['pages'] ?? null) ? $sanitized['pages'] : [];
            $action = sanitize_key((string) ($incomingLifecycle['account_delete']['action'] ?? 'soft_delete'));
            if (! in_array($action, ['soft_delete', 'hard_delete'], true)) {
                $action = 'soft_delete';
            }
            $current['lifecycle'] = [
                'account_delete' => [
                    'action' => $action,
                    'grace_days' => max(0, min(365, absint((string) ($incomingLifecycle['account_delete']['grace_days'] ?? 14)))),
                    'retention_days' => max(0, min(3650, absint((string) ($incomingLifecycle['account_delete']['retention_days'] ?? 30)))),
                ],
            ];
            if (! is_array($current['pages'] ?? null)) {
                $current['pages'] = [];
            }
            $current['pages']['conflict_guard'] = ['enabled' => ! empty($incomingPages['conflict_guard']['enabled'])];
        } elseif ($tab === 'user_menu') {
            $userMenuService = Plugin::instance()->get('user_menu');
            $defaults = $userMenuService instanceof UserMenu ? $userMenuService->defaults() : [
                'enabled' => false,
                'show_avatar' => true,
                'name_mode' => 'full',
                'link_type' => 'link',
                'button_style' => 'btn-primary',
                'links' => ['account' => true, 'workspaces' => true, 'settings' => true, 'billing' => true, 'logout' => true],
            ];
            $incoming = is_array($sanitized['user_menu'] ?? null) ? $sanitized['user_menu'] : [];
            $next = $defaults;
            $next['enabled'] = ! empty($incoming['enabled']);
            $next['show_avatar'] = ! empty($incoming['show_avatar']);
            $nameMode = (string) ($incoming['name_mode'] ?? 'full');
            $next['name_mode'] = in_array($nameMode, ['full', 'first', 'display'], true) ? $nameMode : 'full';
            $linkType = (string) ($incoming['link_type'] ?? 'link');
            $next['link_type'] = in_array($linkType, ['link', 'button'], true) ? $linkType : 'link';
            $buttonStyle = (string) ($incoming['button_style'] ?? 'btn-primary');
            $allowedStyles = ['btn-light', 'btn-outline-contrast', 'btn-dark', 'btn-primary', 'btn-gradient-light', 'btn-gradient-dark'];
            $next['button_style'] = in_array($buttonStyle, $allowedStyles, true) ? $buttonStyle : 'btn-primary';
            $next['links'] = [];
            foreach (['account', 'workspaces', 'settings', 'billing', 'logout'] as $linkKey) {
                $next['links'][$linkKey] = ! empty($incoming['links'][$linkKey]);
            }
            $current['user_menu'] = $next;
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
        } elseif ($tab === 'emails_builder') {
            $builder = isset($_POST['emails_builder']) ? wp_unslash($_POST['emails_builder']) : [];
            $builder = is_array($builder) ? $builder : [];
            $existing = emonks_get_email_templates();
            $templates = is_array($existing['templates'] ?? null) ? $existing['templates'] : [];
            $action = sanitize_key((string) ($builder['action'] ?? 'update'));

            if ($action === 'create') {
                $new = is_array($builder['new'] ?? null) ? $builder['new'] : [];
                $newKey = sanitize_key((string) ($new['key'] ?? ''));
                if ($newKey === '') {
                    emonks_flash_add('settings_warning', 'Email template key is verplicht.');
                } elseif (isset($templates[$newKey])) {
                    emonks_flash_add('settings_warning', 'Email template key bestaat al.');
                } else {
                    $templates[$newKey] = $this->sanitizeEmailTemplate($newKey, $new, []);
                    $redirectUrl = admin_url('admin.php?page=emonks-saas-emails&email=' . rawurlencode($newKey));
                }
            } elseif ($action === 'delete') {
                $emailKey = sanitize_key((string) ($builder['email_key'] ?? ''));
                if ($emailKey === '' || ! isset($templates[$emailKey])) {
                    emonks_flash_add('settings_warning', 'Onbekende email template voor verwijderen.');
                } else {
                    unset($templates[$emailKey]);
                    emonks_flash_add('settings_success', 'Email template verwijderd.');
                    $redirectUrl = admin_url('admin.php?page=emonks-saas-emails');
                }
            } else {
                $emailKey = sanitize_key((string) ($builder['email_key'] ?? ''));
                $emailData = is_array($builder['email'] ?? null) ? $builder['email'] : [];
                if ($emailKey === '' || ! isset($templates[$emailKey])) {
                    emonks_flash_add('settings_warning', 'Onbekende email template voor update.');
                } else {
                    $templates[$emailKey] = $this->sanitizeEmailTemplate($emailKey, $emailData, is_array($templates[$emailKey]) ? $templates[$emailKey] : []);
                    $redirectUrl = admin_url('admin.php?page=emonks-saas-emails&email=' . rawurlencode($emailKey));
                }
            }

            $current['email_templates'] = ['schema_version' => 1, 'templates' => $templates];
        } elseif ($tab === 'emails_template') {
            $htmlTemplateRaw = (string) wp_unslash($_POST['email_layout_html_raw_payload'] ?? '');
            if ($htmlTemplateRaw === '') {
                $htmlTemplateRaw = (string) wp_unslash($_POST['email_layout_html_raw'] ?? '');
            }
            if ($htmlTemplateRaw === '') {
                $htmlTemplateRaw = (string) emonks_get_setting('email_layout.html_template', '');
            }
            $htmlTemplate = $this->sanitizeEmailLayoutTemplate($htmlTemplateRaw);

            if (trim($htmlTemplate) === '' || (! str_contains($htmlTemplate, '[email_content]') && ! str_contains($htmlTemplate, '{{ content_html }}') && ! str_contains($htmlTemplate, '{{ content_text }}'))) {
                $htmlTemplate = '<div style="font-family:Arial,sans-serif;color:#1d2327;line-height:1.6;">[email_content]</div>';
                emonks_flash_add('settings_warning', 'HTML template had geen geldige content-placeholder, fallback is toegepast.');
            }
            $existingTextTemplate = (string) emonks_get_setting('email_layout.text_template', "[email_content]\n");

            $current['email_layout'] = [
                'html_template' => $htmlTemplate,
                'text_template' => $existingTextTemplate,
            ];
            $redirectUrl = admin_url('admin.php?page=emonks-saas-emails&emails_tab=template');
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

    private function renderDashboardKpi(string $label, string $value): void
    {
        echo '<div class="emonks-kpi-card">';
        echo '<div class="emonks-kpi-label">' . esc_html($label) . '</div>';
        echo '<div class="emonks-kpi-value">' . esc_html($value) . '</div>';
        echo '</div>';
    }

    private function renderSetupMetric(string $label, string $value): void
    {
        echo '<div class="emonks-setup-metric">';
        echo '<strong>' . esc_html($value) . '</strong>';
        echo '<span class="emonks-muted">' . esc_html($label) . '</span>';
        echo '</div>';
    }

    /** @param array<string,int> $distribution */
    private function renderPlanDistribution(array $distribution): void
    {
        $total = array_sum(array_map('intval', $distribution));
        if ($total <= 0) {
            echo '<p class="emonks-muted">Nog geen gebruikers met planinformatie.</p>';
            return;
        }

        foreach ($distribution as $plan => $count) {
            $safeCount = max(0, (int) $count);
            $percentage = $safeCount > 0 ? max(4, (int) round(($safeCount / $total) * 100)) : 0;
            echo '<div class="emonks-plan-row">';
            echo '<strong>' . esc_html((string) $plan) . '</strong>';
            echo '<div class="emonks-plan-bar"><span class="emonks-plan-fill" style="width:' . esc_attr((string) $percentage) . '%"></span></div>';
            echo '<span>' . esc_html((string) $safeCount) . '</span>';
            echo '</div>';
        }
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

    /** @return array<string,string> */
    private function emailTriggerOptions(): array
    {
        return [
            'emonks_account_registered' => 'Account registered',
            'emonks_workspace_created' => 'Workspace created',
            'emonks_workspace_updated' => 'Workspace updated',
            'emonks_billing_plan_changed' => 'Billing plan changed',
            'emonks_auth_login_failed' => 'Auth login failed',
            'emonks_auth_registration_verify_email_required' => 'Verify email required',
            'emonks_auth_registration_admin_approval_required' => 'Admin approval required',
            'emonks_auth_admin_approval_result' => 'Admin approval result',
        ];
    }

    /** @param array<string,mixed> $incoming @param array<string,mixed> $existing @return array<string,mixed> */
    private function sanitizeEmailTemplate(string $templateKey, array $incoming, array $existing): array
    {
        $key = sanitize_key($templateKey);
        $trigger = sanitize_key((string) ($incoming['trigger'] ?? ($existing['trigger'] ?? 'emonks_account_registered')));
        if (! array_key_exists($trigger, $this->emailTriggerOptions())) {
            $trigger = 'emonks_account_registered';
        }

        $targets = is_array($incoming['recipients']['targets'] ?? null) ? $incoming['recipients']['targets'] : ($existing['recipients']['targets'] ?? ['current_user']);
        $targets = array_values(array_unique(array_filter(array_map(static fn($item) => sanitize_key((string) $item), is_array($targets) ? $targets : []))));
        $allowedTargets = ['current_user', 'account_owner', 'site_admin'];
        $targets = array_values(array_filter($targets, static fn($target) => in_array($target, $allowedTargets, true)));
        if (empty($targets)) {
            $targets = ['current_user'];
        }

        $extraCsv = sanitize_text_field((string) ($incoming['recipients']['extra_csv'] ?? ''));
        $extra = [];
        foreach (explode(',', $extraCsv) as $email) {
            $email = sanitize_email(trim($email));
            if ($email !== '') {
                $extra[] = $email;
            }
        }
        $extra = array_values(array_unique($extra));

        $conditions = [];
        $conditionsJson = trim((string) ($incoming['conditions_json'] ?? ''));
        if ($conditionsJson !== '') {
            $decoded = json_decode($conditionsJson, true);
            if (is_array($decoded)) {
                $conditions = $decoded;
            }
        }

        return [
            'key' => $key,
            'label' => sanitize_text_field((string) ($incoming['label'] ?? ($existing['label'] ?? $key))),
            'enabled' => ! empty($incoming['enabled']),
            'trigger' => $trigger,
            'recipients' => [
                'targets' => $targets,
                'extra' => $extra,
            ],
            'subject' => sanitize_text_field((string) ($incoming['subject'] ?? ($existing['subject'] ?? 'Notification'))),
            'body_html' => wp_kses_post((string) ($incoming['body_html'] ?? ($existing['body_html'] ?? ''))),
            'body_text' => sanitize_textarea_field((string) ($incoming['body_text'] ?? ($existing['body_text'] ?? ''))),
            'from_name' => sanitize_text_field((string) ($incoming['from_name'] ?? ($existing['from_name'] ?? ''))),
            'from_email' => sanitize_email((string) ($incoming['from_email'] ?? ($existing['from_email'] ?? ''))),
            'reply_to' => sanitize_email((string) ($incoming['reply_to'] ?? ($existing['reply_to'] ?? ''))),
            'conditions' => $conditions,
            'updated_at' => wp_date('Y-m-d H:i:s'),
        ];
    }

    private function sanitizeEmailLayoutTemplate(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        if (! function_exists('wp_kses_allowed_html')) {
            return $html;
        }

        $allowed = wp_kses_allowed_html('post');
        foreach (['html', 'head', 'body', 'meta', 'title', 'style'] as $tag) {
            $allowed[$tag] = [
                'class' => true,
                'dir' => true,
                'id' => true,
                'lang' => true,
                'style' => true,
            ];
        }
        $allowed['meta'] = [
            'charset' => true,
            'content' => true,
            'http-equiv' => true,
            'name' => true,
            'viewport' => true,
        ];
        $allowed['table'] = array_merge($allowed['table'] ?? [], [
            'align' => true,
            'bgcolor' => true,
            'border' => true,
            'cellpadding' => true,
            'cellspacing' => true,
            'role' => true,
            'width' => true,
        ]);
        foreach (['td', 'th'] as $tag) {
            $allowed[$tag] = array_merge($allowed[$tag] ?? [], [
                'align' => true,
                'bgcolor' => true,
                'colspan' => true,
                'height' => true,
                'rowspan' => true,
                'valign' => true,
                'width' => true,
            ]);
        }

        return trim(wp_kses($html, $allowed));
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

    /** @return array<int,string> */
    private function detectPageAssignmentConflicts(): array
    {
        $routeKeys = ['account', 'workspaces', 'billing', 'settings', 'onboarding', 'login', 'register', 'logout'];
        $pageIds = [];
        $slugs = [];
        foreach ($routeKeys as $routeKey) {
            $pageId = absint((string) emonks_get_setting('pages.' . $routeKey . '.page_id', 0));
            if ($pageId <= 0) {
                continue;
            }
            $pageIds[$routeKey] = $pageId;
            $slug = sanitize_title((string) get_post_field('post_name', $pageId));
            if ($slug !== '') {
                $slugs[$routeKey] = $slug;
            }
        }

        $messages = [];
        $usedPageIds = [];
        foreach ($pageIds as $routeKey => $pageId) {
            if (isset($usedPageIds[$pageId])) {
                $messages[] = $usedPageIds[$pageId] . ' en ' . $routeKey . ' gebruiken dezelfde pagina-ID (' . $pageId . ').';
            } else {
                $usedPageIds[$pageId] = $routeKey;
            }
        }

        $usedSlugs = [];
        foreach ($slugs as $routeKey => $slug) {
            if (isset($usedSlugs[$slug])) {
                $messages[] = $usedSlugs[$slug] . ' en ' . $routeKey . ' gebruiken dezelfde slug (' . $slug . ').';
            } else {
                $usedSlugs[$slug] = $routeKey;
            }
        }

        return $messages;
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
