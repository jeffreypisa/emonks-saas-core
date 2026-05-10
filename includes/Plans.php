<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

/**
 * Class: Plans
 * Purpose: Generic subscription plan definitions.
 * Responsibilities: Provide catalog-driven plan lookups via filters.
 * Example: getPlans()['starter'].
 * Hooks: emonks_saas_plans.
 * Architecture Role: Subscription plan catalog.
 */
final class Plans
{
    public function boot(): void
    {
    }

    /** @return array<string,array<string,mixed>> */
    public static function getPlans(): array
    {
        return apply_filters('emonks_saas_plans', self::getPlansFromCatalog());
    }

    /** @return array<string,array<string,mixed>> */
    private static function getPlansFromCatalog(): array
    {
        if (! post_type_exists('emonks_plan')) {
            return [];
        }

        $posts = get_posts([
            'post_type' => 'emonks_plan',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => ['menu_order' => 'ASC', 'title' => 'ASC'],
        ]);

        if (empty($posts)) {
            return [];
        }

        $plans = [];
        foreach ($posts as $post) {
            if (! $post instanceof \WP_Post) {
                continue;
            }

            $key = sanitize_key($post->post_name);
            if ($key === '') {
                continue;
            }

            $features = wp_get_post_terms($post->ID, 'emonks_feature', ['fields' => 'slugs']);
            $services = wp_get_post_terms($post->ID, 'emonks_service', ['fields' => 'slugs']);
            if (! is_array($features)) {
                $features = [];
            }
            if (! is_array($services)) {
                $services = [];
            }

            $plans[$key] = [
                'label' => sanitize_text_field($post->post_title !== '' ? $post->post_title : ucfirst($key)),
                'max_workspaces' => (int) get_post_meta($post->ID, 'max_workspaces', true),
                'enabled_features' => self::normalizeFeatures($features),
                'enabled_services' => array_values(array_filter(array_map(static fn($v) => sanitize_key((string) $v), $services))),
                'stripe_price_constant' => sanitize_text_field((string) get_post_meta($post->ID, 'stripe_price_constant', true)),
                'stripe_price_id' => sanitize_text_field((string) get_post_meta($post->ID, 'stripe_price_id_monthly', true)),
                'stripe_price_id_monthly' => sanitize_text_field((string) get_post_meta($post->ID, 'stripe_price_id_monthly', true)),
                'stripe_price_id_yearly' => sanitize_text_field((string) get_post_meta($post->ID, 'stripe_price_id_yearly', true)),
                'price_monthly' => (float) get_post_meta($post->ID, 'price_monthly', true),
                'price_yearly' => (float) get_post_meta($post->ID, 'price_yearly', true),
                'currency' => strtoupper(sanitize_text_field((string) get_post_meta($post->ID, 'currency', true) ?: 'EUR')),
            ];
        }

        return $plans;
    }

    /** @param mixed $raw */
    private static function normalizeFeatures($raw): array
    {
        if (is_array($raw)) {
            return array_values(array_filter(array_map(static fn($v) => sanitize_key((string) $v), $raw)));
        }

        $items = array_map('trim', explode(',', (string) $raw));
        return array_values(array_filter(array_map(static fn($v) => sanitize_key((string) $v), $items)));
    }
}
