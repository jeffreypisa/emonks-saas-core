<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

/**
 * Class: Services
 * Purpose: Service type registry.
 * Responsibilities: Register and query service definitions and schemas.
 * Example: registerServiceType('concierge', [...]).
 * Hooks: emonks_registered_service_type.
 * Architecture Role: Extensible service/capability catalog.
 */
final class Services
{
    /** @var array<string,array<string,mixed>> */
    private static array $registry = [];

    public function boot(): void
    {
        $this->registerServicesFromSchemas();
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
            'description' => '',
            'status' => 'active',
            'field_source_default' => 'plugin',
            'acf_group_key_default' => '',
            'form_usages' => [],
            'routes' => [],
            'templates' => [],
            'settings_schema' => [],
            'dashboard_cards' => [],
            'capabilities' => [],
            'supported_features' => [],
            'onboarding_steps' => [],
            'policy' => '',
            'render_hints' => [
                'template' => '',
                'public_route' => 'g',
                'dashboard_label' => '',
            ],
        ];

        $normalized = array_replace_recursive($defaults, $definition);
        $normalized['capabilities'] = array_values(array_filter(array_map(static fn($v) => sanitize_key((string) $v), (array) ($normalized['capabilities'] ?? []))));
        $normalized['supported_features'] = array_values(array_filter(array_map(static fn($v) => sanitize_key((string) $v), (array) ($normalized['supported_features'] ?? []))));
        $normalized['onboarding_steps'] = array_values(array_filter(array_map(static fn($v) => sanitize_key((string) $v), (array) ($normalized['onboarding_steps'] ?? []))));
        $normalized['policy'] = sanitize_text_field((string) ($normalized['policy'] ?? ''));
        $normalized['description'] = sanitize_text_field((string) ($normalized['description'] ?? ''));
        $status = sanitize_key((string) ($normalized['status'] ?? 'active'));
        $normalized['status'] = in_array($status, ['active', 'draft', 'disabled'], true) ? $status : 'active';
        $source = sanitize_key((string) ($normalized['field_source_default'] ?? 'plugin'));
        $normalized['field_source_default'] = in_array($source, ['plugin', 'acf', 'hybrid'], true) ? $source : 'plugin';
        $normalized['acf_group_key_default'] = sanitize_text_field((string) ($normalized['acf_group_key_default'] ?? ''));
        $normalized['form_usages'] = is_array($normalized['form_usages'] ?? null) ? $normalized['form_usages'] : [];
        $normalized['render_hints']['template'] = sanitize_text_field((string) ($normalized['render_hints']['template'] ?? ''));
        $normalized['render_hints']['public_route'] = sanitize_key((string) ($normalized['render_hints']['public_route'] ?? 'g'));
        $normalized['render_hints']['dashboard_label'] = sanitize_text_field((string) ($normalized['render_hints']['dashboard_label'] ?? ''));

        return $normalized;
    }

    private function registerServicesFromTerms(): void
    {
        if (! taxonomy_exists('emonks_service')) {
            return;
        }

        $terms = get_terms([
            'taxonomy' => 'emonks_service',
            'hide_empty' => false,
        ]);

        if (! is_array($terms)) {
            return;
        }

        foreach ($terms as $term) {
            if (! $term instanceof \WP_Term) {
                continue;
            }

            $key = sanitize_key($term->slug);
            if ($key === '' || isset(self::$registry[$key])) {
                continue;
            }

            $supported = get_term_meta($term->term_id, 'supported_features', true);
            if (! is_array($supported)) {
                $supported = [];
            }

            self::registerServiceType($key, [
                'labels' => [
                    'singular' => $term->name,
                    'plural' => $term->name,
                ],
                'status' => 'active',
                'supported_features' => $supported,
            ]);
        }
    }

    private function registerServicesFromSchemas(): void
    {
        $schemas = emonks_get_service_schemas();
        $services = is_array($schemas['services'] ?? null) ? $schemas['services'] : [];
        foreach ($services as $key => $schema) {
            if (! is_array($schema)) {
                continue;
            }

            $serviceKey = sanitize_key((string) $key);
            if ($serviceKey === '' || isset(self::$registry[$serviceKey])) {
                continue;
            }

            self::registerServiceType($serviceKey, [
                'labels' => [
                    'singular' => sanitize_text_field((string) ($schema['label'] ?? ucfirst($serviceKey))),
                    'plural' => sanitize_text_field((string) ($schema['label'] ?? ucfirst($serviceKey) . 's')),
                ],
                'description' => sanitize_text_field((string) ($schema['description'] ?? '')),
                'status' => sanitize_key((string) ($schema['status'] ?? 'active')),
                'field_source_default' => sanitize_key((string) ($schema['field_source_default'] ?? 'plugin')),
                'acf_group_key_default' => sanitize_text_field((string) ($schema['acf_group_key_default'] ?? '')),
                'form_usages' => is_array($schema['form_usages'] ?? null) ? $schema['form_usages'] : [],
                'render_hints' => is_array($schema['render_hints'] ?? null) ? $schema['render_hints'] : [],
                'supported_features' => is_array($schema['features'] ?? null) ? $schema['features'] : (is_array($schema['capabilities'] ?? null) ? $schema['capabilities'] : []),
            ]);
        }
    }
}
