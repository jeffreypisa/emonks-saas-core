<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

/**
 * Class: Features
 * Purpose: Feature flag evaluation.
 * Responsibilities: Global and per-plan feature checks.
 * Example: isFeatureEnabled('public_pages').
 * Hooks: emonks_feature_flags.
 * Architecture Role: Capability toggle layer.
 */
final class Features
{
    public function boot(): void
    {
    }

    /** @return array<string,bool> */
    public static function globalFlags(): array
    {
        $defaults = [];
        if (taxonomy_exists('emonks_feature')) {
            $terms = get_terms([
                'taxonomy' => 'emonks_feature',
                'hide_empty' => false,
            ]);

            if (is_array($terms)) {
                foreach ($terms as $term) {
                    if (! $term instanceof \WP_Term) {
                        continue;
                    }
                    $defaults[sanitize_key($term->slug)] = true;
                }
            }
        }

        return apply_filters('emonks_feature_flags', $defaults);
    }
}
