<?php
declare(strict_types=1);

namespace Emonks\SaasCore\Modules\ClientPortal;

use Emonks\SaasCore\ModuleInterface;
use Emonks\SaasCore\ModuleLifecycleInterface;

final class ClientPortalModule implements ModuleInterface, ModuleLifecycleInterface
{
    public function key(): string
    {
        return 'client_portal';
    }

    public function boot(): void
    {
        emonks_register_service_type('client_portal', [
            'labels' => [
                'singular' => 'Client Portal',
                'plural' => 'Client Portals',
            ],
            'supported_features' => ['cp_view', 'cp_manage', 'cp_create', 'cp_update'],
        ]);
    }

    public function isEnabledByDefault(): bool
    {
        return true;
    }

    public function registerDashboardCard(array $cards, int $userId): array
    {
        unset($userId);
        $cards[] = [
            'title' => 'Client Portal',
            'description' => 'Bekijk client portal service items.',
            'url' => emonks_get_account_url('services/client-portal'),
            'variant' => 'primary',
        ];

        return $cards;
    }

    public function registerRoutes(): void
    {
    }

    public function registerRest(): void
    {
    }

    public function registerCapabilities(): void
    {
    }

    public function registerDashboardCards(): void
    {
        add_filter('emonks_dashboard_cards', [$this, 'registerDashboardCard'], 10, 2);
    }
}
