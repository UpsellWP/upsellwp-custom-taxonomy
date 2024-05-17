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

if (!defined('ABSPATH')) {
    exit;
}

defined('UWP_CT_PLUGIN_PATH') || define('UWP_CT_PLUGIN_PATH', plugin_dir_path(__FILE__));

add_filter('cuw_filters', function ($filters) {
    if (!class_exists('UWP_CT_Filter')) {
        include UWP_CT_PLUGIN_PATH . 'Classes/UWP_CT_Filter.php';
    }
    if (!class_exists('UWP_CT_Filter')) {
        return $filters;
    }

    global $cuw_custom_product_taxonomies;
    if (!isset($cuw_custom_product_taxonomies)) {
        if (function_exists('get_taxonomies')) {
            $cuw_custom_product_taxonomies = array_filter(get_taxonomies(array(
                'show_ui' => true,
                'show_in_menu' => true,
                'object_type' => array('product'),
            ), 'objects'), function ($tax) {
                return !in_array($tax->name, array('product_cat', 'product_tag'));
            });
        }
    }
    if (is_array($cuw_custom_product_taxonomies)) {
        foreach($cuw_custom_product_taxonomies as $slug => $taxonomy) {
            $filters[$slug] = [
                'name' => isset($taxonomy->label) ? $taxonomy->label : '',
                'group' => __("Custom taxonomy", 'checkout-upsell-woocommerce'),
                'handler' => new UWP_CT_Filter($slug),
                'campaigns' => ['fbt', 'product_addons', 'cart_addons'],
            ];
        }
    }
    return $filters;
});

add_filter('cuw_conditions', function ($conditions) {
    if (!class_exists('UWP_CT_Condition')) {
        include UWP_CT_PLUGIN_PATH . 'Classes/UWP_CT_Condition.php';
    }

    if (!class_exists('UWP_CT_Condition')) {
        return $conditions;
    }

    global $cuw_custom_product_taxonomies;
    if (!isset($cuw_custom_product_taxonomies)) {
        if (function_exists('get_taxonomies')) {
            $cuw_custom_product_taxonomies = array_filter(get_taxonomies(array(
                'show_ui' => true,
                'show_in_menu' => true,
                'object_type' => array('product'),
            ), 'objects'), function ($tax) {
                return !in_array($tax->name, array('product_cat', 'product_tag'));
            });
        }
    }
    if (is_array($cuw_custom_product_taxonomies)) {
        foreach($cuw_custom_product_taxonomies as $slug => $taxonomy) {
            $conditions[$slug] = [
                'name' => isset($taxonomy->label) ? $taxonomy->label : '',
                'group' => __("Custom taxonomy", 'checkout-upsell-woocommerce'),
                'handler' => new UWP_CT_Condition($slug),
                'campaigns' => ['checkout_upsells', 'post_purchase', 'cart_upsells', 'double_order', 'noc', 'thankyou_upsells', 'upsell_popups', 'cart_addons'],
            ];
        }
    }
    return $conditions;
});
