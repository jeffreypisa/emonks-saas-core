<?php
/**
 * Class: Activation
 * Purpose: Activation/deactivation lifecycle tasks.
 * Responsibilities: Create role, register rewrites, flush rewrites only on lifecycle events.
 * Example: register_activation_hook(...).
 * Hooks: None.
 * Architecture Role: Installer/upgrader entry point.
 */

declare(strict_types=1);

namespace Emonks\SaasCore;

final class Activation
{
    /**
     * Parameters: none.
     * Return: void.
     * Security: WordPress capability system controls plugin activation.
     * Example: Activation::activate();
     */
    public static function activate(): void
    {
        add_role('emonks_customer', 'Emonks Customer', ['read' => true]);
        Accounts::registerPostType();
        Workspaces::registerPostType();
        Catalog::registerPlanPostType();
        Catalog::registerTaxonomies();
        Memberships::install();
        Routes::registerRewriteRules();
        flush_rewrite_rules();
    }

    /**
     * Parameters: none.
     * Return: void.
     * Security: WordPress capability system controls plugin deactivation.
     * Example: Activation::deactivate();
     */
    public static function deactivate(): void
    {
        flush_rewrite_rules();
    }
}
