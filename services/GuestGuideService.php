<?php
declare(strict_types=1);

namespace Emonks\SaasCore\Services;

/**
 * Class: GuestGuideService
 * Purpose: Example service registration implementation.
 * Responsibilities: Demonstrate service registry usage with schema/capabilities.
 * Example: auto-registered in Services::boot().
 * Hooks: none.
 * Architecture Role: Reference implementation for future service modules.
 */
final class GuestGuideService
{
    public function register(): void
    {
        emonks_register_service_type('guest_guide', [
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
        ]);
    }
}
