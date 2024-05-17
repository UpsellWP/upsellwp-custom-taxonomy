<?php
defined('ABSPATH') || exit;

if (!class_exists('CUW\App\Modules\Filters\Base')) {
    return;
}

class UWP_CT_Filter extends \CUW\App\Modules\Filters\Base {
    public $custom_taxonomy = '';
    public function __construct($custom_taxonomy) {
        $this->custom_taxonomy = $custom_taxonomy;
    }
    public function check($filter, $data)
    {
        if (!isset($filter['values']) || !isset($filter['method'])) {
            return false;
        }
        $cuw_product_taxonomy = [];
        if (function_exists('wp_get_post_terms')) {
            $ids = wp_get_post_terms($data['id'], $this->custom_taxonomy, ['fields' => 'ids']);
            if (!empty($ids) && is_array($ids)) {
                $cuw_product_taxonomy = $ids;
            }
        }
        return self::checkLists($filter['values'], $cuw_product_taxonomy, $filter['method']);
    }

    public function template($data = [], $print = false) {
        $key = isset($data['key']) ? (int) $data['key'] : '{key}';
        $filter = isset($data['filter']) ? $data['filter'] : [];
        $method = isset($filter['method']) && !empty($filter['method']) ? $filter['method'] : '';
        $values = isset($filter['values']) && !empty($filter['values']) ? array_flip($filter['values']) : [];
        foreach ($values as $id => $index) {
            $values[$id] = function_exists('get_the_category_by_ID') ? get_the_category_by_ID($id) : $id;
        }
        ob_start();
        ?>
        <div class="filter-method flex-fill">
            <select class="form-control" name="filters[<?php echo esc_attr($key); ?>][method]">
                <option value="in_list" <?php if ($method == 'in_list') echo "selected"; ?>><?php esc_html_e("In list", 'checkout-upsell-woocommerce'); ?></option>
                <option value="not_in_list" <?php if ($method == 'not_in_list') echo "selected"; ?>><?php esc_html_e("Not in list", 'checkout-upsell-woocommerce'); ?></option>
            </select>
        </div>

        <div class="filter-values">
            <select multiple class="select2-list" name="filters[<?php echo esc_attr($key); ?>][values][]" data-list="taxonomies"
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
