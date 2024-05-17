<?php
defined('ABSPATH') || exit;

if (!class_exists('CUW\App\Modules\Conditions\Base')) {
    return;
}

class UWP_CT_Condition extends \CUW\App\Modules\Conditions\Base {
    public $custom_taxonomy = '';
    public function __construct($custom_taxonomy) {
        $this->custom_taxonomy = $custom_taxonomy;
    }
    public function check($condition, $data)
    {
        if (!isset($condition['values']) || !isset($condition['method'])) {
            return false;
        }
        global $cuw_product_taxonomies_in_the_cart;
        if (!isset($cuw_product_taxonomies_in_the_cart)) {
            $cuw_product_taxonomies_in_the_cart = [];
            foreach ($data['products'] as $product) {
                if (isset($product['id']) && function_exists('wp_get_post_terms')) {
                    $ids = wp_get_post_terms($product['id'], $this->custom_taxonomy, ['fields' => 'ids']);
                    if (!empty($ids) && is_array($ids)) {
                        $cuw_product_taxonomies_in_the_cart = array_merge($cuw_product_taxonomies_in_the_cart, $ids);
                    }
                }
            }
        }
        return self::checkLists($condition['values'], $cuw_product_taxonomies_in_the_cart, $condition['method']);
    }

    public function template($data = [], $print = false) {
        $key = isset($data['key']) ? (int) $data['key'] : '{key}';
        $condition = isset($data['condition']) ? $data['condition'] : [];
        $method = isset($condition['method']) && !empty($condition['method']) ? $condition['method'] : '';
        $values = isset($condition['values']) && !empty($condition['values']) ? array_flip($condition['values']) : [];
        foreach ($values as $id => $index) {
            $values[$id] = function_exists('get_the_category_by_ID') ? get_the_category_by_ID($id) : $id;
        }
        ob_start();
        ?>
        <div class="condition-method flex-fill">
            <select class="form-control" name="conditions[<?php echo esc_attr($key); ?>][method]">
                <option value="in_list" <?php if ($method == 'in_list') echo "selected"; ?>><?php esc_html_e("In list", 'checkout-upsell-woocommerce'); ?></option>
                <option value="not_in_list" <?php if ($method == 'not_in_list') echo "selected"; ?>><?php esc_html_e("Not in list", 'checkout-upsell-woocommerce'); ?></option>
            </select>
        </div>

        <div class="condition-values">
            <select multiple class="select2-list" name="conditions[<?php echo esc_attr($key); ?>][values][]" data-list="taxonomies"
                    data-taxonomy="<?php echo esc_attr($this->custom_taxonomy); ?>"
                    data-placeholder=" <?php esc_html_e("Choose taxonomies", 'checkout-upsell-woocommerce'); ?>">
                <?php foreach ($values as $id => $name) { ?>
                    <option value="<?php echo esc_attr($id); ?>" selected><?php echo esc_html($name); ?></option>
                <?php } ?>
            </select>
        </div>
        <?php
        $html = ob_get_clean();
        if ($print) echo $html;
        return $html;
    }
}