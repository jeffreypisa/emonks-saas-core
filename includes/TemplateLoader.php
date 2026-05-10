<?php
/**
 * Class: TemplateLoader
 * Purpose: Locate and render templates from theme first, then plugin fallback.
 * Responsibilities: Template path resolution, Timber integration, graceful fallback.
 * Example: render('account/dashboard.twig', $context).
 * Hooks: admin_notices (for Timber missing notice).
 * Architecture Role: Bridge between plugin logic and theme presentation.
 */

declare(strict_types=1);

namespace Emonks\SaasCore;

final class TemplateLoader
{
    public function boot(): void
    {
        if (! class_exists('Timber\\Timber')) {
            add_action('admin_notices', [$this, 'timberNotice']);
        }
    }

    /**
     * Parameters: string $template, array<string,mixed> $context.
     * Return: void.
     * Security: only loads internal template paths.
     * Example: $loader->render('account/login.twig', []);
     */
    public function render(string $template, array $context = []): void
    {
        $resolved = $this->locate($template);
        if ($resolved === null) {
            status_header(404);
            echo esc_html__('Template not found.', 'emonks-saas-core');
            return;
        }

        if (class_exists('Timber\\Timber')) {
            $context = array_merge(\Timber\Timber::context(), $context);
            $wrapWithTheme = (bool) apply_filters('emonks_saas_wrap_with_theme', false, $template, $context);

            if ($wrapWithTheme) {
                get_header();
                echo \Timber\Timber::compile($resolved, $context); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                get_footer();
                return;
            }

            \Timber\Timber::render($resolved, $context);
            return;
        }

        // Fallback output stays minimal and unstyled.
        echo esc_html__('Timber is required for frontend rendering.', 'emonks-saas-core');
    }

    /**
     * Parameters: string $template.
     * Return: string|null.
     * Security: path constrained to known directories.
     * Example: $loader->locate('account/dashboard.twig');
     */
    public function locate(string $template): ?string
    {
        $template = ltrim($template, '/');

        $themePath = locate_template('templates/' . $template);
        if (! empty($themePath)) {
            return $themePath;
        }

        $pluginPath = EMONKS_SAAS_CORE_PATH . 'templates/' . $template;
        if (file_exists($pluginPath)) {
            return $pluginPath;
        }

        return null;
    }

    public function timberNotice(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        echo '<div class="notice notice-warning"><p>';
        echo esc_html__('Emonks SaaS Core: Timber 2 is not loaded. Run composer install in the plugin (or provide Timber via theme vendor).', 'emonks-saas-core');
        echo '</p></div>';
    }
}
