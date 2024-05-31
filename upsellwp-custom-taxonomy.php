<?php
/**
 * Plugin Name:          UpsellWP: Custom Taxonomy
 * Plugin URI:           https://upsellwp.com/add-ons/custom-taxonomy
 * Description:          Custom taxonomy addon.
 * Version:              1.0.0
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
    $requires = [
        'php' => '7.0',
        'wordpress' => '5.3',
        'woocommerce' => '4.4',
        'upsellwp-pro' => '2.1',
    ];
    $addon_name = 'UpsellWP: Custom Taxonomy';
    include UWP_CT_PLUGIN_PATH . 'src/UWP_AO_Helper.php';
    if (class_exists('UWP_AO_Helper') && UWP_AO_Helper::checkDependencies($requires, $addon_name)) {
        include UWP_CT_PLUGIN_PATH . 'src/UWP_CT_Main.php';
        add_filter('cuw_filters', [UWP_CT_Main::class, 'loadFilters']);
        add_filter('cuw_conditions', [UWP_CT_Main::class, 'loadConditions']);
    }
});

// run updater
include UWP_CT_PLUGIN_PATH . 'src/UWP_AO_Updater.php';
if (class_exists('UWP_AO_Updater')) {
    new UWP_AO_Updater(__FILE__, 'upsellwp-custom-taxonomy');
}
