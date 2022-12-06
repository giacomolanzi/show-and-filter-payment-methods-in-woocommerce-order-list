<?php

/**
 * Plugin Name: Show and filter payment methods in WooCommerce Order list
 * Plugin URI: https://github.com/giacomolanzi/show-and-filter-payment-methods-in-woocommerce-order-list
 * Description: Show payment methods and allow filtering option in the orde list page
 * Author: Giacomo Lanzi
 * Author URI: https://planbproject.it/giacomo-lanzi/
 * Version: 1.0.0
 * Text Domain: show-and-filter-payment-methods-in-woocommerce-order-list
 *
 * Copyright: (c) 2022 Plan B Project
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   show-and-filter-payment-methods-in-woocommerce-order-list
 * @author    Plan B Project di Giacomo Lanzi
 * @category  Admin
 * @copyright Copyright (c) 2022
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined('ABSPATH') or exit;

// fire it up!
add_action('plugins_loaded', 'wc_filter_orders_by_payment');


/** 
 * Main plugin class
 *
 * @since 1.0.0
 */
class WC_Filter_Orders_By_Payment
{


    const VERSION = '1.0.0';

    /** @var WC_Filter_Orders_By_Payment single instance of this plugin */
    protected static $instance;

    /**
     * Main plugin class constructor
     *
     * @since 1.0.0
     */
    public function __construct()
    {

        if (is_admin()) {

            // add bulk order filter for exported / non-exported orders
            add_action('restrict_manage_posts', array($this, 'filter_orders_by_payment_method'), 20);
            add_filter('request',               array($this, 'filter_orders_by_payment_method_query'));
        }
    }


    /** Plugin methods ***************************************/


    /**
     * Add bulk filter for orders by payment method
     *
     * @since 1.0.0
     */
    public function filter_orders_by_payment_method()
    {
        global $typenow;

        if ('shop_order' === $typenow) {

            // get all payment methods, even inactive ones
            $gateways = WC()->payment_gateways->payment_gateways();

?>
            <select name="_shop_order_payment_method" id="dropdown_shop_order_payment_method">
                <option value="">
                    <?php esc_html_e('All Payment Methods', 'wc-filter-orders-by-payment'); ?>
                </option>

                <?php foreach ($gateways as $id => $gateway) : ?>
                    <option value="<?php echo esc_attr($id); ?>" <?php echo esc_attr(isset($_GET['_shop_order_payment_method']) ? selected($id, $_GET['_shop_order_payment_method'], false) : ''); ?>>
                        <?php echo esc_html($gateway->get_method_title()); ?>
                    </option>
                <?php endforeach; ?>
            </select>
<?php
        }
    }


    /**
     * Process bulk filter order payment method
     *
     * @since 1.0.0
     *
     * @param array $vars query vars without filtering
     * @return array $vars query vars with (maybe) filtering
     */
    public function filter_orders_by_payment_method_query($vars)
    {
        global $typenow;

        if ('shop_order' === $typenow && isset($_GET['_shop_order_payment_method']) && !empty($_GET['_shop_order_payment_method'])) {

            $vars['meta_key']   = '_payment_method';
            $vars['meta_value'] = wc_clean($_GET['_shop_order_payment_method']);
        }

        return $vars;
    }


    /** Helper methods ***************************************/


    /**
     * Main WC_Filter_Orders_By_Payment Instance, ensures only one instance is/can be loaded
     *
     * @since 1.0.0
     * @see wc_filter_orders_by_payment()
     * @return WC_Filter_Orders_By_Payment
     */
    public static function instance()
    {

        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}


/**
 * Returns the One True Instance of WC_Filter_Orders_By_Payment
 *
 * @since 1.0.0
 * @return WC_Filter_Orders_By_Payment
 */
function wc_filter_orders_by_payment()
{
    return WC_Filter_Orders_By_Payment::instance();
}


add_filter('manage_edit-shop_order_columns', 'add_payment_method_custom_column', 20);
function add_payment_method_custom_column($columns)
{
    $new_columns = array();
    foreach ($columns as $column_name => $column_info) {
        $new_columns[$column_name] = $column_info;
        if ('order_status' === $column_name) {
            $new_columns['order_payment'] = __('Payment Method', 'my-textdomain');
        }
    }
    return $new_columns;
}
add_action('manage_shop_order_posts_custom_column', 'add_payment_method_custom_column_content');
function add_payment_method_custom_column_content($column)
{
    global $post;
    if ('order_payment' === $column) {
        $order = wc_get_order($post->ID);
        echo $order->payment_method_title;
    }
}
