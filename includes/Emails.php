<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

final class Emails
{
    public function boot(): void
    {
        add_action('emonks_account_registered', [$this, 'onAccountRegistered'], 10, 1);
        add_action('emonks_workspace_created', [$this, 'onWorkspaceCreated'], 10, 2);
        add_action('emonks_workspace_updated', [$this, 'onWorkspaceUpdated'], 10, 2);
        add_action('emonks_billing_plan_changed', [$this, 'onBillingPlanChanged'], 10, 5);
        add_action('emonks_auth_login_failed', [$this, 'onLoginFailed'], 10, 2);
    }

    public function onAccountRegistered(int $userId): void
    {
        $this->sendTemplate('account_registered', ['user_id' => $userId]);
    }

    public function onWorkspaceCreated(int $workspaceId, int $userId): void
    {
        $this->sendTemplate('workspace_created', ['workspace_id' => $workspaceId, 'user_id' => $userId]);
    }

    public function onWorkspaceUpdated(int $workspaceId, int $userId): void
    {
        $this->sendTemplate('workspace_updated', ['workspace_id' => $workspaceId, 'user_id' => $userId]);
    }

    /** @param array<string,mixed> $result */
    public function onBillingPlanChanged(int $userId, string $currentPlan, string $targetPlan, bool $isUpgrade, array $result): void
    {
        $cycle = sanitize_key((string) ($result['cycle'] ?? emonks_get_current_user_billing_cycle($userId)));
        $this->sendTemplate('billing_plan_changed', [
            'user_id' => $userId,
            'current_plan' => sanitize_key($currentPlan),
            'target_plan' => sanitize_key($targetPlan),
            'cycle' => $cycle,
            'is_upgrade' => $isUpgrade,
            'billing_result' => $result,
        ]);
    }

    public function onLoginFailed(string $login, $error): void
    {
        $userId = 0;
        if (is_email($login)) {
            $user = get_user_by('email', $login);
            if ($user instanceof \WP_User) {
                $userId = (int) $user->ID;
            }
        }

        $this->sendTemplate('auth_login_failed', [
            'user_id' => $userId,
            'login' => sanitize_text_field($login),
            'error' => is_wp_error($error) ? sanitize_text_field($error->get_error_message()) : '',
        ]);
    }

    /** @param array<string,mixed> $eventContext */
    private function sendTemplate(string $templateKey, array $eventContext = []): bool
    {
        $template = emonks_resolve_email_template($templateKey, $eventContext);
        if (empty($template) || empty($template['enabled'])) {
            return false;
        }

        $context = emonks_build_email_token_context($eventContext);
        $resolved = emonks_render_email_template_strings($template, $context);

        $subject = (string) ($resolved['subject'] ?? 'Notification');
        $bodyHtml = (string) ($resolved['body_html'] ?? '');
        $bodyText = (string) ($resolved['body_text'] ?? '');

        $subject = (string) apply_filters('emonks_email_subject', $subject, $templateKey, $eventContext, (int) ($eventContext['user_id'] ?? 0));
        $bodyHtml = (string) apply_filters('emonks_email_message_html', $bodyHtml, $templateKey, $eventContext, (int) ($eventContext['user_id'] ?? 0));
        $bodyText = (string) apply_filters('emonks_email_message', $bodyText, $templateKey, $eventContext, (int) ($eventContext['user_id'] ?? 0));

        $recipients = $this->resolveRecipients($resolved, $eventContext, $context);
        if (empty($recipients)) {
            return false;
        }

        $headers = $this->buildHeaders($resolved);
        $message = $bodyHtml !== '' ? $bodyHtml : $bodyText;
        if ($message === '') {
            $message = ' '; // wp_mail guard
        }

        return wp_mail($recipients, $subject !== '' ? $subject : 'Notification', $message, $headers);
    }

    /** @param array<string,mixed> $template @param array<string,mixed> $eventContext @param array<string,mixed> $tokenContext @return array<int,string> */
    private function resolveRecipients(array $template, array $eventContext, array $tokenContext): array
    {
        $recipients = [];
        $targets = is_array($template['recipients']['targets'] ?? null) ? $template['recipients']['targets'] : [];

        foreach ($targets as $target) {
            $target = sanitize_key((string) $target);
            if ($target === 'current_user') {
                $email = sanitize_email((string) ($tokenContext['user']['email'] ?? ''));
                if ($email !== '') {
                    $recipients[] = $email;
                }
            }
            if ($target === 'account_owner') {
                $workspaceId = absint((string) ($eventContext['workspace_id'] ?? 0));
                $accountId = absint((string) ($eventContext['account_id'] ?? 0));
                if ($accountId <= 0 && $workspaceId > 0) {
                    $accountId = absint((string) emonks_get_workspace_meta($workspaceId, 'account_id', 0));
                }
                if ($accountId > 0) {
                    $ownerId = absint((string) get_post_meta($accountId, 'owner_user_id', true));
                    if ($ownerId > 0) {
                        $owner = get_userdata($ownerId);
                        if ($owner instanceof \WP_User) {
                            $ownerEmail = sanitize_email((string) $owner->user_email);
                            if ($ownerEmail !== '') {
                                $recipients[] = $ownerEmail;
                            }
                        }
                    }
                }
            }
            if ($target === 'site_admin') {
                $adminEmail = sanitize_email((string) get_option('admin_email', ''));
                if ($adminEmail !== '') {
                    $recipients[] = $adminEmail;
                }
            }
        }

        $extras = is_array($template['recipients']['extra'] ?? null) ? $template['recipients']['extra'] : [];
        foreach ($extras as $email) {
            $email = sanitize_email((string) $email);
            if ($email !== '') {
                $recipients[] = $email;
            }
        }

        return array_values(array_unique(array_filter($recipients)));
    }

    /** @param array<string,mixed> $template @return array<int,string> */
    private function buildHeaders(array $template): array
    {
        $headers = [];
        $bodyHtml = trim((string) ($template['body_html'] ?? ''));
        $replyTo = sanitize_email((string) ($template['reply_to'] ?? ''));
        $fromEmail = sanitize_email((string) ($template['from_email'] ?? ''));
        $fromName = sanitize_text_field((string) ($template['from_name'] ?? ''));

        if ($bodyHtml !== '') {
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
        }

        if ($replyTo !== '') {
            $headers[] = 'Reply-To: ' . $replyTo;
        }

        if ($fromEmail !== '') {
            $from = $fromEmail;
            if ($fromName !== '') {
                $from = sprintf('%s <%s>', $fromName, $fromEmail);
            }
            $headers[] = 'From: ' . $from;
        }

        return $headers;
    }

    /** @param array<string,mixed> $context */
    public function send(string $template, int $userId, array $context = []): bool
    {
        $context['user_id'] = $userId;
        return $this->sendTemplate($template, $context);
    }
}
