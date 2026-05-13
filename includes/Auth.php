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
    private const MAX_ATTEMPTS = 5;
    private const WINDOW_SECONDS = 900;

    public function boot(): void
    {
    }

    public function handleLoginRoute(): void
    {
        if (is_user_logged_in()) {
            wp_safe_redirect(emonks_get_account_url());
            exit;
        }

        $context = emonks_default_context();
        $formSchema = emonks_get_form_schema('auth_login');
        $context['form'] = $formSchema;
        $context['form_values'] = [];
        $context['form_errors'] = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_admin_referer('emonks_login_action', 'emonks_nonce');
            $validated = emonks_validate_form_payload('auth_login', is_array($_POST) ? $_POST : []);
            $values = is_array($validated['values'] ?? null) ? $validated['values'] : [];
            $errors = is_array($validated['errors'] ?? null) ? $validated['errors'] : [];
            $context['form_values'] = $values;
            $context['form_errors'] = $errors;

            $login = sanitize_text_field((string) ($values['user_login'] ?? $_POST['user_login'] ?? ''));
            if ($this->isRateLimited('login', $login)) {
                do_action('emonks_auth_login_blocked', $login);
                emonks_flash_add('auth_error', 'Too many login attempts. Please try again later.');
                emonks_render_template('account/login.twig', $context);
                return;
            }

            if (! empty($errors)) {
                emonks_render_template('account/login.twig', $context);
                return;
            }

            $creds = [
                'user_login' => $login,
                'user_password' => (string) ($values['user_password'] ?? $_POST['user_password'] ?? ''),
                'remember' => true,
            ];

            $user = wp_signon($creds, is_ssl());
            if (! is_wp_error($user)) {
                $this->clearRateLimit('login', $creds['user_login']);
                do_action('emonks_auth_login_success', $user->ID);
                wp_safe_redirect(emonks_get_account_url());
                exit;
            }

            $this->trackFailedAttempt('login', $creds['user_login']);
            emonks_flash_add('auth_error', 'Login failed. Check your credentials and try again.');
            do_action('emonks_auth_login_failed', $creds['user_login'], $user);
        }

        emonks_render_template('account/login.twig', $context);
    }

    public function handleRegisterRoute(): void
    {
        if (is_user_logged_in()) {
            wp_safe_redirect(emonks_get_account_url());
            exit;
        }

        $context = emonks_default_context();
        $formSchema = emonks_get_form_schema('auth_register');
        $context['form'] = $formSchema;
        $context['form_values'] = [];
        $context['form_errors'] = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_admin_referer('emonks_register_action', 'emonks_nonce');
            $validated = emonks_validate_form_payload('auth_register', is_array($_POST) ? $_POST : []);
            $values = is_array($validated['values'] ?? null) ? $validated['values'] : [];
            $errors = is_array($validated['errors'] ?? null) ? $validated['errors'] : [];
            $context['form_values'] = $values;
            $context['form_errors'] = $errors;

            $email = sanitize_email((string) ($values['email'] ?? $_POST['email'] ?? ''));
            $password = (string) ($values['password'] ?? $_POST['password'] ?? '');
            if ($this->isRateLimited('register', $email)) {
                do_action('emonks_auth_register_blocked', $email);
                emonks_flash_add('auth_error', 'Too many signup attempts. Please try again later.');
                emonks_render_template('account/register.twig', $context);
                return;
            }

            if (! empty($errors)) {
                emonks_render_template('account/register.twig', $context);
                return;
            }

            if (is_email($email) && strlen($password) >= 8) {
                $userId = wp_create_user($email, $password, $email);
                if (! is_wp_error($userId)) {
                    $user = get_user_by('id', $userId);
                    if ($user instanceof \WP_User) {
                        $this->clearRateLimit('register', $email);
                        $user->set_role('emonks_customer');
                        wp_set_current_user($userId);
                        wp_set_auth_cookie($userId);
                        do_action('emonks_account_registered', $userId);
                        wp_safe_redirect(emonks_get_account_url('onboarding'));
                        exit;
                    }
                }
            }

            $this->trackFailedAttempt('register', $email);
            emonks_flash_add('auth_error', 'Signup failed. Use a valid email and a password of at least 8 characters.');
        }

        emonks_render_template('account/register.twig', $context);
    }

    public function handleLogoutRoute(): void
    {
        wp_logout();
        wp_safe_redirect(home_url('/'));
        exit;
    }

    private function isRateLimited(string $action, string $identifier): bool
    {
        $attempts = (int) get_transient($this->rateLimitKey($action, $identifier));
        return $attempts >= self::MAX_ATTEMPTS;
    }

    private function trackFailedAttempt(string $action, string $identifier): void
    {
        $key = $this->rateLimitKey($action, $identifier);
        $attempts = (int) get_transient($key);
        set_transient($key, $attempts + 1, self::WINDOW_SECONDS);
    }

    private function clearRateLimit(string $action, string $identifier): void
    {
        delete_transient($this->rateLimitKey($action, $identifier));
    }

    private function rateLimitKey(string $action, string $identifier): string
    {
        $ip = sanitize_text_field((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        return 'emonks_rl_' . md5($action . '|' . sanitize_text_field($identifier) . '|' . $ip);
    }
}
