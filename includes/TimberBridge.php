<?php
declare(strict_types=1);

namespace Emonks\SaasCore;

/**
 * Class: TimberBridge
 * Purpose: Load Timber 2 via Composer without requiring WordPress Timber plugin.
 * Responsibilities: Bootstrap Timber classes from plugin vendor, with theme vendor fallback.
 * Architecture Role: Rendering dependency bootstrap for Twig-based SaaS templates.
 */
final class TimberBridge
{
    public static function bootstrap(): void
    {
        if (class_exists('Timber\\Timber')) {
            return;
        }

        $pluginAutoload = EMONKS_SAAS_CORE_PATH . 'vendor/autoload.php';
        if (file_exists($pluginAutoload)) {
            require_once $pluginAutoload;
        }

        if (! class_exists('Timber\\Timber')) {
            $themeAutoload = get_stylesheet_directory() . '/vendor/autoload.php';
            if (file_exists($themeAutoload)) {
                require_once $themeAutoload;
            }
        }

        if (class_exists('Timber\\Timber')) {
            \Timber\Timber::$dirname = [
                'templates',
                'views',
                EMONKS_SAAS_CORE_PATH . 'templates',
            ];
        }
    }
}
