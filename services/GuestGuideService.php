<?php
declare(strict_types=1);

namespace Emonks\SaasCore\Services;

use Emonks\SaasCore\ServiceModuleInterface;

/**
 * Class: GuestGuideService
 * Purpose: Example service registration implementation.
 * Responsibilities: Demonstrate service registry usage with schema/capabilities.
 * Example: auto-registered in Services::boot().
 * Hooks: none.
 * Architecture Role: Reference implementation for future service modules.
 */
final class GuestGuideService implements ServiceModuleInterface
{
    public function key(): string
    {
        return 'guest_guide';
    }

    public function definition(): array
    {
        return [
            'labels' => ['singular' => 'Item', 'plural' => 'Items'],
            'routes' => ['index' => 'workspaces'],
            'templates' => ['dashboard_card' => 'services/guest-guide-card.twig'],
            'settings_schema' => [
                'wifi_name' => ['type' => 'text', 'label' => 'Wifi name'],
            ],
            'fields' => [
                'wifi_name' => ['type' => 'text', 'label' => 'Wifi name'],
            ],
            'dashboard_cards' => [],
            'capabilities' => ['read', 'edit'],
            'supported_features' => ['public_pages', 'qr_codes', 'translations'],
        ];
    }

    public function register(): void
    {
        emonks_register_service_type($this->key(), $this->definition());
    }
}
