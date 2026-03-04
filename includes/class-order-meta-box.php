<?php
/**
 * Order Meta Box functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class Glint_WC_Distance_Order_Meta_Box
{

    public static function init()
    {
        add_action('add_meta_boxes', array(__CLASS__, 'add_meta_box'), 10, 2);
        add_action('wp_ajax_glint_wc_get_distance', array(__CLASS__, 'ajax_get_distance'));
    }

    public static function add_meta_box($post_type, $post)
    {
        // Handle both older post_type based orders and new HPOS order screens
        $screen = wc_get_page_screen_id('shop-order');
        if (in_array($post_type, array('shop_order', $screen))) {
            add_meta_box(
                'glint_wc_distance_meta_box',
                'Driving Distance (Shop to Customer)',
                array(__CLASS__, 'render_meta_box'),
                $post_type,
                'side',
                'core'
            );
        }
    }

    public static function render_meta_box($post_or_order_object)
    {
        // If it's HPOS, $post_or_order_object is a WC_Order. Otherwise, it's a WP_Post.
        $order_id = $post_or_order_object instanceof WC_Order ? $post_or_order_object->get_id() : $post_or_order_object->ID;

        $distance = Glint_WC_Distance_Database::get_order_distance($order_id);

?>
        <div id="glint-distance-wrapper">
            <p>
                <strong>Current Distance: </strong> 
                <span id="glint-distance-value">
                    <?php echo $distance ? esc_html($distance) : 'Not calculated yet'; ?>
                </span>
            </p>
            <p>
                <button type="button" class="button" id="glint-btn-get-distance">Get Distance</button>
                <span class="spinner" id="glint-distance-spinner"></span>
            </p>
            <p id="glint-distance-msg" style="color:red; display:none;"></p>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#glint-btn-get-distance').on('click', function(e) {
                e.preventDefault();
                $('#glint-distance-spinner').addClass('is-active');
                $('#glint-distance-msg').hide();

                $.post(ajaxurl, {
                    action: 'glint_wc_get_distance',
                    order_id: <?php echo intval($order_id); ?>,
                    security: '<?php echo wp_create_nonce('glint_distance_ajax_nonce'); ?>'
                }, function(response) {
                    $('#glint-distance-spinner').removeClass('is-active');
                    if (response.success) {
                        $('#glint-distance-value').text(response.data.distance);
                        $('#glint-distance-msg').hide();
                    } else {
                        $('#glint-distance-msg').text(response.data.message).show();
                    }
                });
            });
        });
        </script>
        <?php
    }

    public static function ajax_get_distance()
    {
        check_ajax_referer('glint_distance_ajax_nonce', 'security');

        if (!current_user_can('edit_shop_orders')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        if (!$order_id) {
            wp_send_json_error(array('message' => 'Invalid order ID'));
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(array('message' => 'Order not found'));
        }

        $origin = Glint_WC_Distance_Database::get_setting('glint_wc_distance_shop_address');
        if (empty($origin)) {
            wp_send_json_error(array('message' => 'Shop address not configured in settings.'));
        }

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
            wp_send_json_error(array('message' => 'No shipping address provided in this order.'));
        }

        $distance = Glint_WC_Distance_Google_Maps_API::calculate_distance($origin, $destination);

        if (is_wp_error($distance)) {
            wp_send_json_error(array('message' => $distance->get_error_message()));
        }

        // Save and return
        Glint_WC_Distance_Database::update_order_distance($order_id, $distance);
        wp_send_json_success(array('distance' => $distance));
    }
}
