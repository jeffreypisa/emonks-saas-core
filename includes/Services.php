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
            'settings_schema' => [],
            'dashboard_cards' => [],
            'onboarding_steps' => [],
        ]);

        $module = new \Emonks\SaasCore\Services\GuestGuideService();
        if ($module instanceof ServiceModuleInterface) {
            self::registerServiceType($module->key(), $module->definition());
        } else {
            $module->register();
        }
    }

    /** @param array<string,mixed> $definition */
    public static function registerServiceType(string $key, array $definition): void
    {
        $normalizedKey = sanitize_key($key);
        self::$registry[$normalizedKey] = self::normalizeDefinition($normalizedKey, $definition);
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

    /** @param array<string,mixed> $definition */
    private static function normalizeDefinition(string $key, array $definition): array
    {
        $defaults = [
            'labels' => ['singular' => ucfirst($key), 'plural' => ucfirst($key) . 's'],
            'routes' => [],
            'templates' => [],
            'settings_schema' => [],
            'fields' => [],
            'dashboard_cards' => [],
            'capabilities' => [],
            'supported_features' => [],
            'onboarding_steps' => [],
            'policy' => '',
        ];

        $normalized = array_replace_recursive($defaults, $definition);
        $normalized['capabilities'] = array_values(array_filter(array_map(static fn($v) => sanitize_key((string) $v), (array) ($normalized['capabilities'] ?? []))));
        $normalized['supported_features'] = array_values(array_filter(array_map(static fn($v) => sanitize_key((string) $v), (array) ($normalized['supported_features'] ?? []))));
        $normalized['onboarding_steps'] = array_values(array_filter(array_map(static fn($v) => sanitize_key((string) $v), (array) ($normalized['onboarding_steps'] ?? []))));
        $normalized['policy'] = sanitize_text_field((string) ($normalized['policy'] ?? ''));

        return $normalized;
    }
}
