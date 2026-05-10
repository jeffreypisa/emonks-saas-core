<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

final class Logger
{
    public function boot(): void
    {
    }

    /** @param array<string,mixed> $context */
    public static function log(string $channel, string $message, array $context = []): void
    {
        do_action('emonks_log_entry', $channel, $message, $context);

        $logs = get_option('emonks_saas_logs', []);
        if (! is_array($logs)) {
            $logs = [];
        }

        $logs[] = [
            'time' => current_time('mysql'),
            'channel' => sanitize_key($channel),
            'message' => sanitize_text_field($message),
            'context' => $context,
        ];

        if (count($logs) > 1000) {
            $logs = array_slice($logs, -1000);
        }

        update_option('emonks_saas_logs', $logs, false);

        $debugEnabled = (bool) emonks_get_setting('general.debug.enabled', false);
        if (defined('WP_DEBUG') && WP_DEBUG && $debugEnabled) {
            error_log(sprintf('[emonks:%s] %s %s', sanitize_key($channel), $message, wp_json_encode($context)));
        }
    }
}
