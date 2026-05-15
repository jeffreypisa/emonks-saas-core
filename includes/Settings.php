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
    private function sanitizeRecursive(array $data, string $path = ''): array
    {
        $clean = [];

        foreach ($data as $key => $value) {
            $safeKey = sanitize_key((string) $key);
            $currentPath = $path === '' ? $safeKey : $path . '.' . $safeKey;
            if (is_array($value)) {
                $clean[$safeKey] = $this->sanitizeRecursive($value, $currentPath);
            } elseif (is_bool($value) || is_int($value) || is_float($value)) {
                $clean[$safeKey] = $value;
            } else {
                $clean[$safeKey] = $this->sanitizeScalar((string) $value, $currentPath);
            }
        }

        return $clean;
    }

    private function sanitizeScalar(string $value, string $path): string
    {
        if ($path === 'email_layout.html_template' || str_ends_with($path, '.body_html')) {
            return $this->sanitizeEmailHtml($value);
        }

        if ($path === 'email_layout.text_template' || str_ends_with($path, '.body_text')) {
            return sanitize_textarea_field($value);
        }

        return sanitize_text_field($value);
    }

    private function sanitizeEmailHtml(string $html): string
    {
        $html = trim($html);
        if ($html === '' || ! function_exists('wp_kses_allowed_html')) {
            return $html;
        }

        $allowed = wp_kses_allowed_html('post');
        foreach (['html', 'head', 'body', 'meta', 'title', 'style'] as $tag) {
            $allowed[$tag] = [
                'class' => true,
                'dir' => true,
                'id' => true,
                'lang' => true,
                'style' => true,
            ];
        }
        $allowed['meta'] = [
            'charset' => true,
            'content' => true,
            'http-equiv' => true,
            'name' => true,
            'viewport' => true,
        ];
        $allowed['table'] = array_merge($allowed['table'] ?? [], [
            'align' => true,
            'bgcolor' => true,
            'border' => true,
            'cellpadding' => true,
            'cellspacing' => true,
            'role' => true,
            'width' => true,
        ]);
        foreach (['td', 'th'] as $tag) {
            $allowed[$tag] = array_merge($allowed[$tag] ?? [], [
                'align' => true,
                'bgcolor' => true,
                'colspan' => true,
                'height' => true,
                'rowspan' => true,
                'valign' => true,
                'width' => true,
            ]);
        }

        return trim(wp_kses($html, $allowed));
    }
}
