<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

/**
 * Class: Services
 * Purpose: Service type registry.
 * Responsibilities: Register and query service definitions and schemas.
 * Example: registerServiceType('guest_guide', [...]).
 * Hooks: emonks_registered_service_type.
 * Architecture Role: Extensible service/capability catalog.
 */
final class Services
{
    /** @var array<string,array<string,mixed>> */
    private static array $registry = [];

    public function boot(): void
    {
        self::registerServiceType('generic', [
            'labels' => ['singular' => 'Item', 'plural' => 'Items'],
            'routes' => [],
            'templates' => [],
            'fields' => [],
            'capabilities' => [],
            'supported_features' => [],
        ]);

        (new \Emonks\SaasCore\Services\GuestGuideService())->register();
    }

    /** @param array<string,mixed> $definition */
    public static function registerServiceType(string $key, array $definition): void
    {
        self::$registry[sanitize_key($key)] = $definition;
        do_action('emonks_registered_service_type', $key, $definition);
    }

    /** @return array<string,array<string,mixed>> */
    public static function all(): array
    {
        return self::$registry;
    }

    /** @return array<string,mixed> */
    public static function get(string $key): array
    {
        return self::$registry[sanitize_key($key)] ?? [];
    }
}
