<?php
/**
 * UpsellWP: Custom taxonomy
 *
 * @package   upsellwp-custom-taxonomy
 * @author    Team UpsellWP <team@upsellwp.com>
 * @license   GPL-3.0-or-later
 * @link      https://upsellwp.com
 */

defined('ABSPATH') || exit;

if (class_exists('UWP_CT_Helper')) {
    return;
}

class UWP_CT_Helper
{
    /**
     * Check depentencies.
     *
     * @param array $requires
     * @param string $addon_name
     * @return bool
     */
    public static function checkDependencies($requires, $addon_name)
    {
        if (class_exists('\CUW\App\Core') && method_exists('\CUW\App\Helpers\Plugin', 'getDependenciesError')) {
            $error_message = \CUW\App\Helpers\Plugin::getDependenciesError($requires, $addon_name);
        } else {
            if (!empty($requires['upsellwp_pro'])) {
                $upsellwp_name = 'UpsellWP PRO';
                $upsellwp_url = 'https://upsellwp.com?utm_campaign=upsellwp_plugin&utm_source=upsellwp_free&utm_medium=upgrade';
                $upsellwp_version = $requires['upsellwp_pro'];
            } else {
                $upsellwp_name = 'UpsellWP';
                $upsellwp_url = 'https://wordpress.org/plugins/checkout-upsell-and-order-bumps';
                $upsellwp_version = !empty($requires['upsellwp']) ? $requires['upsellwp'] : '2.1';
            }
            $upsellwp = '<a href="' . esc_url($upsellwp_url) . '" target="_blank">' . esc_html($upsellwp_name) . '</a>';
            if (defined('CUW_VERSION') && version_compare(CUW_VERSION, $upsellwp_version, '>=')) {
                $error_message = sprintf('%1$s requires %2$s version %3$s or above.', $addon_name, $upsellwp, $upsellwp_version);
            } else {
                $error_message = sprintf('%1$s requires %2$s plugin to be installed and active.', $addon_name, $upsellwp);
            }
        }

        if (!empty($error_message)) {
            add_action('admin_notices', function () use ($error_message) { ?>
                <div class="notice notice-error"><p><?php echo wp_kses_post($error_message); ?></p></div>
                <?php
            }, 1);
            return false;
        }
        return true;
    }
}