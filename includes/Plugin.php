<?php
/**
 * Class: Plugin
 * Purpose: Main plugin bootstrap and service container entry.
 * Responsibilities: Load classes, wire hooks, initialize all SaaS core modules.
 * Example: Plugin::instance()->boot();
 * Hooks: Registers all module hooks via module boot() methods.
 * Architecture Role: Composition root of the SaaS core plugin.
 */

declare(strict_types=1);

namespace Emonks\SaasCore;

final class Plugin
{
    private static ?self $instance = null;

    /** @var array<string,object> */
    private array $services = [];

    /**
     * Parameters: none.
     * Return: Plugin instance.
     * Security: no direct input processed.
     * Example: Plugin::instance();
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
    }

    /**
     * Parameters: none.
     * Return: void.
     * Security: only runs in WP runtime.
     * Example: Plugin::instance()->boot();
     */
    public function boot(): void
    {
        $this->loadDependencies();
        add_action('init', [$this, 'loadTextDomain']);
        add_action('emonks_register_modules', [$this, 'registerCoreModules']);
        TimberBridge::bootstrap();
        $this->registerServices();

        foreach ($this->services as $service) {
            if (method_exists($service, 'boot')) {
                $service->boot();
            }
        }

        do_action('emonks_saas_core_booted', $this);
    }

    public function registerCoreModules(object $registry): void
    {
        if (! $registry instanceof ModuleRegistry) {
            return;
        }

        $registry->register(new Modules\ClientPortal\ClientPortalModule());
    }

    public function loadTextDomain(): void
    {
        load_plugin_textdomain('emonks-saas-core', false, dirname(plugin_basename(EMONKS_SAAS_CORE_FILE)) . '/languages');
    }

    /**
     * Parameters: none.
     * Return: object|null
     * Security: no unsafe operations.
     * Example: Plugin::instance()->get('routes');
     */
    public function get(string $key): ?object
    {
        return $this->services[$key] ?? null;
    }

    private function loadDependencies(): void
    {
        $files = [
            'TemplateLoader.php',
            'TimberBridge.php',
            'Routes.php',
            'Auth.php',
            'Dashboard.php',
            'Workspaces.php',
            'WorkspaceStatuses.php',
            'Permissions.php',
            'Policy.php',
            'Accounts.php',
            'MigrationRunner.php',
            'Memberships.php',
            'ServiceItems.php',
            'Plans.php',
            'Billing.php',
            'Stripe.php',
            'Webhooks.php',
            'Catalog.php',
            'Services.php',
            'ServiceModuleInterface.php',
            'ModuleInterface.php',
            'ModuleLifecycleInterface.php',
            'ModuleRegistry.php',
            'ModuleHealth.php',
            'Modules/ClientPortal/ClientPortalModule.php',
            'BillingProviderInterface.php',
            'StripeBillingProvider.php',
            'Features.php',
            'Settings.php',
            'Branding.php',
            'Admin.php',
            'Assets.php',
            'Emails.php',
            'Logger.php',
            'Onboarding.php',
            'RestApi.php',
            'CustomDomains.php',
        ];

        foreach ($files as $file) {
            require_once EMONKS_SAAS_CORE_PATH . 'includes/' . $file;
        }
    }

    private function registerServices(): void
    {
        $this->services = [
            'settings' => new Settings(),
            'branding' => new Branding(),
            'template_loader' => new TemplateLoader(),
            'statuses' => new WorkspaceStatuses(),
            'plans' => new Plans(),
            'features' => new Features(),
            'services_registry' => new Services(),
            'permissions' => new Permissions(),
            'policy' => new Policy(),
            'accounts' => new Accounts(),
            'migration_runner' => new MigrationRunner(),
            'memberships' => new Memberships(),
            'service_items' => new ServiceItems(),
            'module_registry' => new ModuleRegistry(),
            'module_health' => new ModuleHealth(),
            'admin' => new Admin(),
            'auth' => new Auth(),
            'workspaces' => new Workspaces(),
            'dashboard' => new Dashboard(),
            'routes' => new Routes(),
            'assets' => new Assets(),
            'billing' => new Billing(),
            'stripe' => new Stripe(),
            'webhooks' => new Webhooks(),
            'catalog' => new Catalog(),
            'emails' => new Emails(),
            'logger' => new Logger(),
            'onboarding' => new Onboarding(),
            'rest_api' => new RestApi(),
            'custom_domains' => new CustomDomains(),
        ];
    }
}
