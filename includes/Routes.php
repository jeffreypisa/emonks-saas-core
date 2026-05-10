<?php
/**
 * Class: Routes
 * Purpose: Frontend account/public routing via rewrite rules.
 * Responsibilities: Register routes, query vars, route dispatch.
 * Example: /account/workspaces/new, /g/{public_slug}.
 * Hooks: init, query_vars, template_redirect.
 * Architecture Role: Request router for SaaS frontend flows.
 */

declare(strict_types=1);

namespace Emonks\SaasCore;

final class Routes
{
    /** @return array<string,string> */
    public static function defaultRoutes(): array
    {
        $defaults = [
            'account' => 'account',
            'workspaces' => 'workspaces',
            'billing' => 'billing',
            'settings' => 'settings',
            'onboarding' => 'onboarding',
            'login' => 'login',
            'register' => 'register',
            'logout' => 'logout',
            'public' => 'g',
        ];

        return apply_filters('emonks_saas_routes', $defaults);
    }

    public function boot(): void
    {
        add_action('init', [self::class, 'registerRewriteRules']);
        add_filter('query_vars', [$this, 'queryVars']);
        add_action('template_redirect', [$this, 'disableAdminBarForSaasRoutes'], 1);
        add_action('template_redirect', [$this, 'dispatch']);
    }

    public function disableAdminBarForSaasRoutes(): void
    {
        $route = (string) get_query_var('emonks_route');
        if ($route === '') {
            return;
        }

        add_filter('show_admin_bar', '__return_false', 999);
        show_admin_bar(false);
        remove_action('wp_head', '_admin_bar_bump_cb');
        add_action('wp_head', static function (): void {
            echo '<style>html{margin-top:0!important;}body.admin-bar{margin-top:0!important;}</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }, 99);
    }

    public static function registerRewriteRules(): void
    {
        $r = self::defaultRoutes();

        add_rewrite_rule('^' . $r['account'] . '/?$', 'index.php?emonks_route=account_dashboard', 'top');
        add_rewrite_rule('^' . $r['account'] . '/' . $r['workspaces'] . '/?$', 'index.php?emonks_route=account_workspaces', 'top');
        add_rewrite_rule('^' . $r['account'] . '/' . $r['workspaces'] . '/new/?$', 'index.php?emonks_route=account_workspaces_new', 'top');
        add_rewrite_rule('^' . $r['account'] . '/' . $r['workspaces'] . '/([0-9]+)/edit/?$', 'index.php?emonks_route=account_workspace_edit&emonks_workspace_id=$matches[1]', 'top');
        add_rewrite_rule('^' . $r['account'] . '/' . $r['billing'] . '/?$', 'index.php?emonks_route=account_billing', 'top');
        add_rewrite_rule('^' . $r['account'] . '/' . $r['settings'] . '/?$', 'index.php?emonks_route=account_settings', 'top');
        add_rewrite_rule('^' . $r['account'] . '/' . $r['onboarding'] . '/?$', 'index.php?emonks_route=account_onboarding', 'top');
        add_rewrite_rule('^' . $r['login'] . '/?$', 'index.php?emonks_route=auth_login', 'top');
        add_rewrite_rule('^' . $r['register'] . '/?$', 'index.php?emonks_route=auth_register', 'top');
        add_rewrite_rule('^' . $r['logout'] . '/?$', 'index.php?emonks_route=auth_logout', 'top');
        add_rewrite_rule('^' . $r['public'] . '/([^/]+)/?$', 'index.php?emonks_route=public_workspace&emonks_public_slug=$matches[1]', 'top');
    }

    /** @param string[] $vars */
    public function queryVars(array $vars): array
    {
        $vars[] = 'emonks_route';
        $vars[] = 'emonks_workspace_id';
        $vars[] = 'emonks_public_slug';
        return $vars;
    }

    public function dispatch(): void
    {
        $route = get_query_var('emonks_route');
        if (! $route) {
            return;
        }

        $plugin = Plugin::instance();
        /** @var Dashboard $dashboard */
        $dashboard = $plugin->get('dashboard');
        /** @var Auth $auth */
        $auth = $plugin->get('auth');

        switch ($route) {
            case 'auth_login':
                $auth->handleLoginRoute();
                break;
            case 'auth_register':
                $auth->handleRegisterRoute();
                break;
            case 'auth_logout':
                $auth->handleLogoutRoute();
                break;
            case 'public_workspace':
                $dashboard->renderPublicWorkspace();
                break;
            default:
                $dashboard->handleAccountRoute((string) $route);
                break;
        }

        exit;
    }
}
