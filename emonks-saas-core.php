<?php
/**
 * Plugin Name: Emonks SaaS Core
 * Description: Generic, extensible SaaS core framework plugin for WordPress themes.
 * Version: 0.1.0
 * Requires PHP: 8.0
 * Author: Emonks
 * Text Domain: emonks-saas-core
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('EMONKS_SAAS_CORE_VERSION', '0.1.0');
define('EMONKS_SAAS_CORE_FILE', __FILE__);
define('EMONKS_SAAS_CORE_PATH', plugin_dir_path(__FILE__));
define('EMONKS_SAAS_CORE_URL', plugin_dir_url(__FILE__));

require_once EMONKS_SAAS_CORE_PATH . 'includes/Plugin.php';
require_once EMONKS_SAAS_CORE_PATH . 'includes/Activation.php';
require_once EMONKS_SAAS_CORE_PATH . 'includes/Helpers.php';

register_activation_hook(EMONKS_SAAS_CORE_FILE, ['Emonks\\SaasCore\\Activation', 'activate']);
register_deactivation_hook(EMONKS_SAAS_CORE_FILE, ['Emonks\\SaasCore\\Activation', 'deactivate']);

Emonks\SaasCore\Plugin::instance()->boot();
