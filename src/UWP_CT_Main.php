<?php
defined('ABSPATH') || exit;

if (class_exists('UWP_CT_Main')) {
    return;
}

class UWP_CT_Main
{
    /**
     * Hold custom taxonomies.
     *
     * @var array
     */
    private static $custom_taxonomies;

    /**
     * Load custom taxonomy filters.
     *
     * @param array $filters
     * @return array
     */
    public static function loadFilters($filters)
    {
        include UWP_CT_PLUGIN_PATH . 'src/UWP_CT_Filter.php';
        if (class_exists('UWP_CT_Filter')) {
            foreach (self::getCustomTaxonomies() as $slug => $taxonomy) {
                $filters['tax_' . $slug] = [
                    'name' => isset($taxonomy->label) ? $taxonomy->label : '',
                    'group' => __("Custom taxonomy", 'checkout-upsell-woocommerce'),
                    'handler' => new UWP_CT_Filter($slug),
                    'campaigns' => ['fbt', 'product_addons', 'cart_addons'],
                ];
            }
        }
        return $filters;
    }

    /**
     * Load custom taxonomy conditions.
     *
     * @param array $conditions
     * @return array
     */
    public static function loadConditions($conditions)
    {
        include UWP_CT_PLUGIN_PATH . 'src/UWP_CT_Condition.php';
        if (class_exists('UWP_CT_Condition')) {
            foreach (self::getCustomTaxonomies() as $slug => $taxonomy) {
                $conditions['tax_' . $slug] = [
                    'name' => isset($taxonomy->label) ? $taxonomy->label : '',
                    'group' => __("Custom taxonomy", 'checkout-upsell-woocommerce'),
                    'handler' => new UWP_CT_Condition($slug),
                    'campaigns' => ['checkout_upsells', 'post_purchase', 'cart_upsells', 'double_order', 'noc', 'thankyou_upsells', 'upsell_popups', 'cart_addons'],
                ];
            }
        }
        return $conditions;
    }

    /**
     * Returns custom taxonomies array.
     *
     * @return array
     */
    public static function getCustomTaxonomies()
    {
        if (isset(self::$custom_taxonomies)) {
            return self::$custom_taxonomies;
        }
        $custom_taxonomies = [];
        if (function_exists('get_taxonomies')) {
            $custom_taxonomies = apply_filters('uwp_ct_custom_taxonomies', array_filter(get_taxonomies(array(
                'show_ui' => true,
                'show_in_menu' => true,
                'object_type' => array('product'),
            ), 'objects'), function ($tax) {
                return !in_array($tax->name, array('product_cat', 'product_tag'));
            }));
        }
        return self::$custom_taxonomies = is_array($custom_taxonomies) ? $custom_taxonomies : [];
    }
}
