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
        $defaults = [
            'public_pages' => true,
            'qr_codes' => true,
            'custom_domains' => false,
            'translations' => false,
        ];

        return apply_filters('emonks_feature_flags', $defaults);
    }
}
