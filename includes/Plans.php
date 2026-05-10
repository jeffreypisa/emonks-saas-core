<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

/**
 * Class: Plans
 * Purpose: Generic subscription plan definitions.
 * Responsibilities: Provide defaults and plan lookups via filters.
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
        $defaults = [
            'starter' => [
                'label' => 'Starter',
                'max_workspaces' => 1,
                'enabled_features' => ['public_pages'],
                'stripe_price_constant' => 'EMONKS_STRIPE_PRICE_STARTER',
                'stripe_price_id_monthly' => '',
                'stripe_price_id_yearly' => '',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'currency' => 'EUR',
            ],
            'plus' => [
                'label' => 'Plus',
                'max_workspaces' => 5,
                'enabled_features' => ['public_pages', 'qr_codes'],
                'stripe_price_constant' => 'EMONKS_STRIPE_PRICE_PLUS',
                'stripe_price_id_monthly' => '',
                'stripe_price_id_yearly' => '',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'currency' => 'EUR',
            ],
            'pro' => [
                'label' => 'Pro',
                'max_workspaces' => 25,
                'enabled_features' => ['public_pages', 'qr_codes', 'custom_domains', 'translations'],
                'stripe_price_constant' => 'EMONKS_STRIPE_PRICE_PRO',
                'stripe_price_id_monthly' => '',
                'stripe_price_id_yearly' => '',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'currency' => 'EUR',
            ],
        ];

        $configured = emonks_get_setting('plans', []);
        if (is_array($configured) && ! empty($configured)) {
            foreach ($configured as $key => $plan) {
                $planKey = sanitize_key((string) $key);
                if (! is_array($plan)) {
                    continue;
                }

                $defaults[$planKey] = [
                    'label' => sanitize_text_field((string) ($plan['label'] ?? ucfirst($planKey))),
                    'max_workspaces' => (int) ($plan['max_workspaces'] ?? 0),
                    'enabled_features' => self::normalizeFeatures($plan['enabled_features'] ?? []),
                    'stripe_price_constant' => sanitize_text_field((string) ($plan['stripe_price_constant'] ?? '')),
                    'stripe_price_id' => sanitize_text_field((string) ($plan['stripe_price_id'] ?? '')),
                    'stripe_price_id_monthly' => sanitize_text_field((string) ($plan['stripe_price_id_monthly'] ?? '')),
                    'stripe_price_id_yearly' => sanitize_text_field((string) ($plan['stripe_price_id_yearly'] ?? '')),
                    'price_monthly' => (float) ($plan['price_monthly'] ?? 0),
                    'price_yearly' => (float) ($plan['price_yearly'] ?? 0),
                    'currency' => strtoupper(sanitize_text_field((string) ($plan['currency'] ?? 'EUR'))),
                ];
            }
        }

        $plans = $defaults;
        return apply_filters('emonks_saas_plans', $plans);
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
