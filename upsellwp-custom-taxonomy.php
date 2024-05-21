<?php
/**
 * Plugin Name:          UpsellWP: Custom Taxonomy
 * Plugin URI:           https://upsellwp.com
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

include UWP_CT_PLUGIN_PATH . 'src/UWP_CT_Main.php';

add_filter('cuw_filters', [UWP_CT_Main::class, 'loadFilters']);
add_filter('cuw_conditions', [UWP_CT_Main::class, 'loadConditions']);
