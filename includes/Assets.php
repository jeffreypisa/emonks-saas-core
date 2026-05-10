<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

/**
 * Class: Assets
 * Purpose: Central asset registration/enqueue.
 * Responsibilities: Conditional account/public asset loading with versioning.
 * Example: enqueueFrontend().
 * Hooks: wp_enqueue_scripts.
 * Architecture Role: Asset gateway for plugin UX scripts only.
 */
final class Assets
{
    public function boot(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueueFrontend']);
    }

    public function enqueueFrontend(): void
    {
        $route = (string) get_query_var('emonks_route');

        if (str_starts_with($route, 'account_') || str_starts_with($route, 'auth_')) {
            wp_enqueue_script('emonks-saas-account', EMONKS_SAAS_CORE_URL . 'assets/js/account.js', [], EMONKS_SAAS_CORE_VERSION, true);
            wp_enqueue_style('emonks-saas-account', EMONKS_SAAS_CORE_URL . 'assets/css/account.css', [], EMONKS_SAAS_CORE_VERSION);
        }

        if ($route === 'public_workspace') {
            wp_enqueue_script('emonks-saas-public', EMONKS_SAAS_CORE_URL . 'assets/js/public.js', [], EMONKS_SAAS_CORE_VERSION, true);
        }
    }
}
