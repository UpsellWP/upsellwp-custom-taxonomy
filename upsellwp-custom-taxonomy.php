<?php
/**
 * Plugin Name:          UpsellWP: Custom Taxonomy
 * Plugin URI:           https://upsellwp.com
 * Description:          Custom taxonomy addon.
 * Version:              0.0.2
 * Requires at least:    5.3
 * Requires PHP:         7.0
 * Author:               UpsellWP
 * Author URI:           https://upsellwp.com
 * License:              GPL v3 or later
 * License URI:          https://www.gnu.org/licenses/gpl-3.0.html
 */

defined('ABSPATH') || exit;

defined('UWP_CT_PLUGIN_PATH') || define('UWP_CT_PLUGIN_PATH', plugin_dir_path(__FILE__));

// load plugin
add_action('plugins_loaded', function () {
    $load_addon = false;
    $error_message = '';
    $dependencies = [
        'php' => '7.0',
        'wordpress' => '5.3',
        'woocommerce' => '4.4',
        'upsellwp' => '2.1',
    ];
    $plugin_name = 'UpsellWP: Custom Taxonomy';

    if (class_exists('\CUW\App\Core') && method_exists('\CUW\App\Helpers\Plugin', 'getDependenciesError')) {
        $error_message = \CUW\App\Helpers\Plugin::getDependenciesError($dependencies, $plugin_name);
        $load_addon = empty($error_message);
    } else {
        if (!empty($dependencies['upsellwp-pro'])) {
            $upsellwp_name = 'UpsellWP PRO';
            $upsellwp_url = 'https://upsellwp.com?utm_campaign=upsellwp_plugin&utm_source=upsellwp_free&utm_medium=upgrade';
            $upsellwp_version = $dependencies['upsellwp-pro'];
        } else {
            $upsellwp_name = 'UpsellWP';
            $upsellwp_url = 'https://wordpress.org/plugins/checkout-upsell-and-order-bumps';
            $upsellwp_version = !empty($dependencies['upsellwp']) ? $dependencies['upsellwp'] : '2.1';
        }
        $upsellwp = '<a href="' . esc_url($upsellwp_url) . '" target="_blank">' . esc_html($upsellwp_name) . '</a>';
        if (defined('CUW_VERSION') && version_compare(CUW_VERSION, $upsellwp_version, '>=')) {
            $error_message = sprintf('%1$s requires %2$s version %3$s or above', $plugin_name, $upsellwp, $upsellwp_version);
        } else {
            $error_message = sprintf('%1$s requires %2$s plugin to be installed and active', $plugin_name, $upsellwp);
        }
    }

    if ($load_addon) {
        include UWP_CT_PLUGIN_PATH . 'src/UWP_CT_Main.php';
        add_filter('cuw_filters', [UWP_CT_Main::class, 'loadFilters']);
        add_filter('cuw_conditions', [UWP_CT_Main::class, 'loadConditions']);
    } elseif (!empty($error_message)) {
        add_action('admin_notices', function () use ($error_message) { ?>
            <div class="notice notice-error"><p><?php echo wp_kses_post($error_message); ?></p></div>
            <?php
        }, 1);
    }
});

// run updater
include UWP_CT_PLUGIN_PATH . 'src/UWP_GH_Updater.php';
if (class_exists('UWP_GH_Updater')) {
    new UWP_GH_Updater(__FILE__, 'upsellwp-custom-taxonomy');
}
