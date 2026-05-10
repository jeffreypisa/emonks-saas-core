<?php
/**
 * Class: Settings
 * Purpose: Central settings API wrapper.
 * Responsibilities: Register option, expose safe get/update helpers.
 * Example: emonks_get_setting('stripe.secret_key').
 * Hooks: admin_init.
 * Architecture Role: Infrastructure settings store.
 */

declare(strict_types=1);

namespace Emonks\SaasCore;

final class Settings
{
    public const OPTION_KEY = 'emonks_saas_settings';

    public function boot(): void
    {
        add_action('admin_init', [$this, 'registerSettings']);
    }

    /**
     * Parameters: none.
     * Return: void.
     * Security: register_setting sanitizes stored payload.
     * Example: automatic in admin_init.
     */
    public function registerSettings(): void
    {
        register_setting('general', self::OPTION_KEY, [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitizeSettings'],
            'default' => [],
        ]);
    }

    /**
     * Parameters: mixed $raw.
     * Return: array<string,mixed>.
     * Security: sanitizes recursively to avoid unsafe input persistence.
     * Example: internal callback.
     */
    public function sanitizeSettings($raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        return $this->sanitizeRecursive($raw);
    }

    /** @param array<string,mixed> $data */
    private function sanitizeRecursive(array $data): array
    {
        $clean = [];

        foreach ($data as $key => $value) {
            $safeKey = sanitize_key((string) $key);
            if (is_array($value)) {
                $clean[$safeKey] = $this->sanitizeRecursive($value);
            } elseif (is_bool($value) || is_int($value) || is_float($value)) {
                $clean[$safeKey] = $value;
            } else {
                $clean[$safeKey] = sanitize_text_field((string) $value);
            }
        }

        return $clean;
    }
}
