<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

/**
 * Class: Emails
 * Purpose: Generic email workflow dispatcher.
 * Responsibilities: Build mail content, trigger templates/filters, send notifications.
 * Example: send('welcome_email', $userId, []).
 * Hooks: emonks_email_subject, emonks_email_message.
 * Architecture Role: Notification subsystem.
 */
final class Emails
{
    public function boot(): void
    {
    }

    /** @param array<string,mixed> $context */
    public function send(string $template, int $userId, array $context = []): bool
    {
        $user = get_userdata($userId);
        if (! $user instanceof \WP_User) {
            return false;
        }

        $subject = (string) apply_filters('emonks_email_subject', 'Notification', $template, $context, $userId);
        $message = (string) apply_filters('emonks_email_message', 'No message.', $template, $context, $userId);

        return wp_mail($user->user_email, $subject, $message);
    }
}
