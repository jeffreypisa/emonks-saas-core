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
        add_action('admin_init', [$this, 'maybeBlockCustomerAdminAccess']);
        add_action('emonks_account_delete_requested', [$this, 'handleAccountDeleteRequest'], 10, 2);
        add_action('admin_post_emonks_account_delete_request', [$this, 'handleAccountDeleteRequestAction']);
        add_action('admin_post_nopriv_emonks_account_delete_request', [$this, 'rejectGuest']);
        add_action('admin_post_emonks_auth_resend_verification', [$this, 'handleResendVerificationAction']);
        add_action('admin_post_nopriv_emonks_auth_resend_verification', [$this, 'handleResendVerificationAction']);
    }

    public function handleLoginRoute(): void
    {
        if (is_user_logged_in()) {
            wp_safe_redirect(emonks_get_redirect_url('after_login', get_current_user_id()));
            exit;
        }

        $authSettings = emonks_get_auth_settings();
        $context = emonks_default_context();
        $formSchema = emonks_get_form_schema('auth_login');
        $context['form'] = $formSchema;
        $context['form_values'] = [];
        $context['form_errors'] = [];
        $notice = sanitize_key((string) ($_GET['emonks_notice'] ?? ''));
        if ($notice === 'registration_pending') {
            $context['flash_messages']['auth_info'] = 'Je account is aangemaakt en wacht op activatie. Je ontvangt bericht zodra je kunt inloggen.';
        } elseif ($notice === 'registration_verify_email') {
            $context['flash_messages']['auth_info'] = 'Controleer je e-mail om je account te verifieren.';
        } elseif ($notice === 'email_verified') {
            $context['flash_messages']['auth_success'] = 'Je e-mail is bevestigd. Je kunt nu inloggen.';
        } elseif ($notice === 'email_verify_invalid') {
            $context['flash_messages']['auth_error'] = 'De verificatielink is ongeldig of verlopen.';
        } elseif ($notice === 'resend_sent') {
            $context['flash_messages']['auth_info'] = 'Als het account bestaat en nog verificatie nodig heeft, is er een nieuwe e-mail verzonden.';
        } elseif ($notice === 'resend_rate_limited') {
            $context['flash_messages']['auth_warning'] = 'Wacht even voordat je opnieuw een verificatiemail aanvraagt.';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_admin_referer('emonks_login_action', 'emonks_nonce');
            $validated = emonks_validate_form_payload('auth_login', is_array($_POST) ? $_POST : []);
            $values = is_array($validated['values'] ?? null) ? $validated['values'] : [];
            $errors = is_array($validated['errors'] ?? null) ? $validated['errors'] : [];
            $context['form_values'] = $values;
            $context['form_errors'] = $errors;

            $login = sanitize_text_field((string) ($values['user_login'] ?? $_POST['user_login'] ?? ''));
            if ($this->isRateLimited('login', $login, (int) $authSettings['login']['rate_limit']['max_attempts'])) {
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
                $registrationStatus = sanitize_key((string) get_user_meta((int) $user->ID, 'emonks_registration_status', true));
                if (in_array($registrationStatus, ['pending', 'pending_delete', 'rejected'], true)) {
                    wp_logout();
                    $warning = 'Je account wacht nog op activatie.';
                    if ($registrationStatus === 'pending_delete') {
                        $warning = 'Je account staat gemarkeerd voor verwijdering.';
                    } elseif ($registrationStatus === 'rejected') {
                        $warning = 'Je accountaanvraag is afgewezen.';
                    }
                    emonks_flash_add('auth_warning', $warning);
                    emonks_render_template('account/login.twig', $context);
                    return;
                }
                $this->clearRateLimit('login', $creds['user_login']);
                do_action('emonks_auth_login_success', $user->ID);
                wp_safe_redirect(emonks_get_redirect_url('after_login', (int) $user->ID));
                exit;
            }

            $this->trackFailedAttempt('login', $creds['user_login'], (int) $authSettings['login']['rate_limit']['lockout_minutes']);
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

        $authSettings = emonks_get_auth_settings();
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
            if ($this->isRateLimited('register', $email, (int) $authSettings['login']['rate_limit']['max_attempts'])) {
                do_action('emonks_auth_register_blocked', $email);
                emonks_flash_add('auth_error', 'Too many signup attempts. Please try again later.');
                emonks_render_template('account/register.twig', $context);
                return;
            }

            $missingRequired = $this->collectMissingRequiredRegisterFields(
                (array) $authSettings['register']['required_fields'],
                $values,
                is_array($_POST) ? $_POST : []
            );
            foreach ($missingRequired as $fieldKey) {
                $context['form_errors'][$fieldKey] = 'Required field ontbreekt: ' . $fieldKey;
            }

            $passwordErrors = $this->validatePasswordPolicy($password, (array) $authSettings['password']);
            foreach ($passwordErrors as $idx => $message) {
                $context['form_errors']['password_policy_' . $idx] = $message;
            }

            if (! empty($errors)) {
                emonks_render_template('account/register.twig', $context);
                return;
            }

            if (! empty($context['form_errors'])) {
                emonks_render_template('account/register.twig', $context);
                return;
            }

            if (is_email($email)) {
                $userId = wp_create_user($email, $password, $email);
                if (! is_wp_error($userId)) {
                    $user = get_user_by('id', $userId);
                    if ($user instanceof \WP_User) {
                        $this->clearRateLimit('register', $email);
                        $user->set_role('emonks_customer');

                        $registrationStatus = sanitize_key((string) $authSettings['registration']['status']);
                        if ($registrationStatus === 'auto_approve') {
                            wp_set_current_user($userId);
                            wp_set_auth_cookie($userId);
                        } else {
                            update_user_meta($userId, 'emonks_registration_status', 'pending');
                            update_user_meta($userId, 'emonks_registration_mode', $registrationStatus);
                        }

                        do_action('emonks_account_registered', $userId);
                        if ($registrationStatus === 'email_verification') {
                            $token = $this->issueEmailVerificationToken($userId);
                            if ($token !== '') {
                                do_action('emonks_auth_registration_verify_email_required', $userId, $token);
                            }
                            wp_safe_redirect(add_query_arg('emonks_notice', 'registration_verify_email', emonks_get_login_url()));
                            exit;
                        }
                        if ($registrationStatus === 'admin_approval') {
                            do_action('emonks_auth_registration_admin_approval_required', $userId);
                            wp_safe_redirect(add_query_arg('emonks_notice', 'registration_pending', emonks_get_login_url()));
                            exit;
                        }

                        wp_safe_redirect(emonks_get_redirect_url('after_register', $userId));
                        exit;
                    }
                }
            }

            $this->trackFailedAttempt('register', $email, (int) $authSettings['login']['rate_limit']['lockout_minutes']);
            emonks_flash_add('auth_error', 'Signup failed. Use a valid email and password that matches the policy.');
        }

        emonks_render_template('account/register.twig', $context);
    }

    public function handleLogoutRoute(): void
    {
        wp_logout();
        wp_safe_redirect(emonks_get_redirect_url('after_logout', 0));
        exit;
    }

    public function handleVerifyEmailRoute(): void
    {
        $token = sanitize_text_field((string) ($_GET['token'] ?? ''));
        $verifiedUserId = $this->consumeEmailVerificationToken($token);
        if ($verifiedUserId <= 0) {
            wp_safe_redirect(add_query_arg('emonks_notice', 'email_verify_invalid', emonks_get_login_url()));
            exit;
        }

        update_user_meta($verifiedUserId, 'emonks_registration_status', 'active');
        update_user_meta($verifiedUserId, 'emonks_registration_verified_at', wp_date('c'));
        do_action('emonks_auth_email_verified', $verifiedUserId);
        wp_safe_redirect(add_query_arg('emonks_notice', 'email_verified', emonks_get_login_url()));
        exit;
    }

    public function rejectGuest(): void
    {
        wp_safe_redirect(emonks_get_login_url());
        exit;
    }

    public function maybeBlockCustomerAdminAccess(): void
    {
        if (! is_user_logged_in() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }

        $authSettings = emonks_get_auth_settings();
        if (empty($authSettings['login']['wp_admin_block_customers'])) {
            return;
        }

        if (! is_admin() || user_can(get_current_user_id(), 'manage_options')) {
            return;
        }
        global $pagenow;
        if ($pagenow === 'admin-post.php') {
            return;
        }

        $user = wp_get_current_user();
        $roles = is_array($user->roles ?? null) ? $user->roles : [];
        if (! in_array('emonks_customer', $roles, true)) {
            return;
        }

        wp_safe_redirect(emonks_get_account_url());
        exit;
    }

    public function handleAccountDeleteRequest(int $userId, int $accountId = 0): void
    {
        $userId = absint((string) $userId);
        if ($userId <= 0) {
            return;
        }

        $policy = emonks_get_lifecycle_settings();
        $delete = (array) ($policy['account_delete'] ?? []);
        $action = sanitize_key((string) ($delete['action'] ?? 'soft_delete'));
        if ($action === 'hard_delete') {
            wp_delete_user($userId);
            return;
        }

        update_user_meta($userId, 'emonks_account_delete_requested_at', wp_date('c'));
        update_user_meta($userId, 'emonks_account_delete_grace_days', (int) ($delete['grace_days'] ?? 14));
        update_user_meta($userId, 'emonks_account_delete_retention_days', (int) ($delete['retention_days'] ?? 30));
        update_user_meta($userId, 'emonks_registration_status', 'pending_delete');
        do_action('emonks_account_soft_delete_marked', $userId, $accountId);
    }

    public function handleAccountDeleteRequestAction(): void
    {
        if (! is_user_logged_in()) {
            wp_safe_redirect(emonks_get_login_url());
            exit;
        }
        check_admin_referer('emonks_account_delete_request', 'emonks_nonce');

        $userId = get_current_user_id();
        $accountId = emonks_get_primary_account_id($userId);
        do_action('emonks_account_delete_requested', $userId, $accountId);
        wp_logout();
        wp_safe_redirect(emonks_get_redirect_url('after_logout', 0));
        exit;
    }

    public function handleResendVerificationAction(): void
    {
        check_admin_referer('emonks_auth_resend_verification', 'emonks_nonce');
        $email = sanitize_email((string) ($_POST['email'] ?? ''));
        $redirect = add_query_arg('emonks_notice', 'resend_sent', emonks_get_login_url());

        if ($email === '') {
            wp_safe_redirect($redirect);
            exit;
        }

        if ($this->isRateLimited('verify_resend', $email, 3)) {
            wp_safe_redirect(add_query_arg('emonks_notice', 'resend_rate_limited', emonks_get_login_url()));
            exit;
        }

        $user = get_user_by('email', $email);
        if ($user instanceof \WP_User) {
            $userId = (int) $user->ID;
            $registrationStatus = sanitize_key((string) get_user_meta($userId, 'emonks_registration_status', true));
            $registrationMode = sanitize_key((string) get_user_meta($userId, 'emonks_registration_mode', true));
            if ($registrationStatus === 'pending' && $registrationMode === 'email_verification') {
                $token = $this->issueEmailVerificationToken($userId);
                if ($token !== '') {
                    do_action('emonks_auth_registration_verify_email_required', $userId, $token);
                }
            }
        }

        $this->trackFailedAttempt('verify_resend', $email, 5);
        wp_safe_redirect($redirect);
        exit;
    }

    private function isRateLimited(string $action, string $identifier, int $maxAttempts): bool
    {
        $attempts = (int) get_transient($this->rateLimitKey($action, $identifier));
        return $attempts >= max(1, $maxAttempts);
    }

    private function trackFailedAttempt(string $action, string $identifier, int $lockoutMinutes): void
    {
        $key = $this->rateLimitKey($action, $identifier);
        $attempts = (int) get_transient($key);
        set_transient($key, $attempts + 1, max(60, $lockoutMinutes * 60));
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

    private function issueEmailVerificationToken(int $userId): string
    {
        $userId = absint((string) $userId);
        if ($userId <= 0) {
            return '';
        }

        $token = wp_generate_password(48, false, false);
        $hash = wp_hash_password($token);
        update_user_meta($userId, 'emonks_verify_token_hash', $hash);
        update_user_meta($userId, 'emonks_verify_token_expires_at', (string) (time() + DAY_IN_SECONDS));
        delete_user_meta($userId, 'emonks_verify_token_used_at');
        return $token;
    }

    private function consumeEmailVerificationToken(string $token): int
    {
        $token = trim($token);
        if ($token === '') {
            return 0;
        }

        global $wpdb;
        $userIds = $wpdb->get_col($wpdb->prepare(
            'SELECT user_id FROM ' . $wpdb->usermeta . ' WHERE meta_key = %s',
            'emonks_verify_token_hash'
        ));
        if (! is_array($userIds) || empty($userIds)) {
            return 0;
        }

        foreach ($userIds as $candidate) {
            $userId = absint((string) $candidate);
            if ($userId <= 0) {
                continue;
            }
            $hash = (string) get_user_meta($userId, 'emonks_verify_token_hash', true);
            $expiresAt = (int) get_user_meta($userId, 'emonks_verify_token_expires_at', true);
            $usedAt = (string) get_user_meta($userId, 'emonks_verify_token_used_at', true);
            if ($hash === '' || $expiresAt <= 0 || $expiresAt < time() || $usedAt !== '') {
                continue;
            }
            if (! wp_check_password($token, $hash)) {
                continue;
            }

            update_user_meta($userId, 'emonks_verify_token_used_at', wp_date('c'));
            delete_user_meta($userId, 'emonks_verify_token_hash');
            delete_user_meta($userId, 'emonks_verify_token_expires_at');
            return $userId;
        }

        return 0;
    }

    /** @param array<int,string> $requiredFields @param array<string,mixed> $values @param array<string,mixed> $posted */
    private function collectMissingRequiredRegisterFields(array $requiredFields, array $values, array $posted): array
    {
        $missing = [];
        foreach ($requiredFields as $fieldKey) {
            $key = sanitize_key((string) $fieldKey);
            if ($key === '') {
                continue;
            }
            $value = $values[$key] ?? ($posted[$key] ?? '');
            if (trim((string) $value) === '') {
                $missing[] = $key;
            }
        }
        return $missing;
    }

    /** @param array<string,mixed> $policy @return array<int,string> */
    private function validatePasswordPolicy(string $password, array $policy): array
    {
        $errors = [];
        $minLength = max(6, (int) ($policy['min_length'] ?? 8));
        if (strlen($password) < $minLength) {
            $errors[] = 'Wachtwoord moet minimaal ' . $minLength . ' tekens bevatten.';
        }
        if (! empty($policy['require_uppercase']) && ! preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Wachtwoord moet minimaal 1 hoofdletter bevatten.';
        }
        if (! empty($policy['require_number']) && ! preg_match('/[0-9]/', $password)) {
            $errors[] = 'Wachtwoord moet minimaal 1 cijfer bevatten.';
        }
        if (! empty($policy['require_symbol']) && ! preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'Wachtwoord moet minimaal 1 speciaal teken bevatten.';
        }
        return $errors;
    }
}
