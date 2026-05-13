<?php
declare(strict_types=1);

namespace Emonks\SaasCore\Modules\Guestbook;

use Emonks\SaasCore\ModuleInterface;
use Emonks\SaasCore\ModuleLifecycleInterface;

final class GuestbookModule implements ModuleInterface, ModuleLifecycleInterface
{
    public function key(): string
    {
        return 'guestbook';
    }

    public function boot(): void
    {
        emonks_register_service_type('guestbook', [
            'labels' => [
                'singular' => 'Guestbook',
                'plural' => 'Guestbooks',
            ],
            'supported_features' => ['gb_view', 'gb_manage'],
        ]);
    }

    public function isEnabledByDefault(): bool
    {
        return true;
    }

    public function registerDashboardCards(): void
    {
        add_filter('emonks_dashboard_cards', [$this, 'registerDashboardCard'], 10, 2);
    }

    public function registerDashboardCard(array $cards, int $userId): array
    {
        unset($userId);
        $cards[] = [
            'title' => 'Guestbook',
            'description' => 'Publiceer een digitale gastenmap op je workspace.',
            'url' => emonks_get_account_url('workspaces'),
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
}
