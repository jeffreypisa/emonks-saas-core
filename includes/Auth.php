<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

/**
 * Class: Auth
 * Purpose: Account authentication workflows.
 * Responsibilities: Login/register/logout processing and route rendering.
 * Example: handleLoginRoute().
 * Hooks: none direct, invoked by Routes dispatcher.
 * Architecture Role: Authentication boundary for frontend flows.
 */
final class Auth
{
    public function boot(): void
    {
    }

    public function handleLoginRoute(): void
    {
        if (is_user_logged_in()) {
            wp_safe_redirect(emonks_get_account_url());
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_admin_referer('emonks_login_action', 'emonks_nonce');

            $creds = [
                'user_login' => sanitize_text_field((string) ($_POST['user_login'] ?? '')),
                'user_password' => (string) ($_POST['user_password'] ?? ''),
                'remember' => true,
            ];

            $user = wp_signon($creds, is_ssl());
            if (! is_wp_error($user)) {
                do_action('emonks_auth_login_success', $user->ID);
                wp_safe_redirect(emonks_get_account_url());
                return;
            }

            do_action('emonks_auth_login_failed', $creds['user_login'], $user);
        }

        emonks_render_template('account/login.twig', emonks_default_context());
    }

    public function handleRegisterRoute(): void
    {
        if (is_user_logged_in()) {
            wp_safe_redirect(emonks_get_account_url());
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_admin_referer('emonks_register_action', 'emonks_nonce');

            $email = sanitize_email((string) ($_POST['email'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');

            if (is_email($email) && strlen($password) >= 8) {
                $userId = wp_create_user($email, $password, $email);
                if (! is_wp_error($userId)) {
                    $user = get_user_by('id', $userId);
                    if ($user instanceof \WP_User) {
                        $user->set_role('emonks_customer');
                        wp_set_current_user($userId);
                        wp_set_auth_cookie($userId);
                        do_action('emonks_account_registered', $userId);
                        wp_safe_redirect(emonks_get_account_url('onboarding'));
                        return;
                    }
                }
            }
        }

        emonks_render_template('account/register.twig', emonks_default_context());
    }

    public function handleLogoutRoute(): void
    {
        wp_logout();
        wp_safe_redirect(home_url('/'));
    }
}
