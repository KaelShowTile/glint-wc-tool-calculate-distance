<?php
/**
 * Order Hooks functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class Glint_WC_Distance_Order_Hooks
{

    public static function init()
    {
        // Triggered exactly when order is created
        add_action('woocommerce_new_order', array(__CLASS__, 'handle_new_order'), 10, 2);
    }

    public static function handle_new_order($order_id, $order = null)
    {
        if (!$order_id) {
            return;
        }

        // Check if auto trigger is enabled
        $auto_trigger = Glint_WC_Distance_Database::get_setting('glint_wc_distance_auto_trigger', 'no');
        if ('yes' !== $auto_trigger) {
            return;
        }

        // Prevent infinite loops or redundant calls
        if (did_action('woocommerce_new_order') > 1) {
        // Usually this hooks only fires once per order, but double check
        }

        if (!$order instanceof WC_Order) {
            $order = wc_get_order($order_id);
        }

        if (!$order) {
            return;
        }

        $origin = Glint_WC_Distance_Database::get_setting('glint_wc_distance_shop_address');
        if (empty($origin)) {
            return;
        }

        // Use shipping address, or billing if shipping is empty? usually shipping.
        $address_lines = array(
            $order->get_shipping_address_1(),
            $order->get_shipping_address_2(),
            $order->get_shipping_city(),
            $order->get_shipping_state(),
            $order->get_shipping_postcode(),
            $order->get_shipping_country()
        );
        $address_lines = array_filter($address_lines);
        $destination = implode(', ', $address_lines);

        if (empty($destination)) {
            return;
        }

        $distance = Glint_WC_Distance_Google_Maps_API::calculate_distance($origin, $destination);

        if (!is_wp_error($distance)) {
            Glint_WC_Distance_Database::update_order_distance($order_id, $distance);
        }
    }
}
