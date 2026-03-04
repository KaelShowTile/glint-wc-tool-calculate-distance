<?php
/**
 * Settings functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class Glint_WC_Distance_Settings
{

    public static function init()
    {
        add_action('admin_menu', array(__CLASS__, 'add_settings_page'));
        add_action('admin_init', array(__CLASS__, 'handle_save_settings'));
        add_action('admin_post_glint_wc_distance_export_csv', array(__CLASS__, 'handle_export_csv'));
    }

    public static function add_settings_page()
    {
        if (empty($GLOBALS['admin_page_hooks']['glint-ai-tools'])) {
            add_menu_page(
                'ST AI Tools',
                'ST AI Tools',
                'manage_options',
                'glint-ai-tools',
                array(__CLASS__, 'render_main_dashboard'),
                'dashicons-admin-tools',
                58
            );
        }

        add_submenu_page(
            'glint-ai-tools',
            'Distance Calculator',
            'Distance Calculator',
            'manage_options',
            'glint-wc-distance-settings',
            array(__CLASS__, 'render_settings_page')
        );
    }

    public static function render_main_dashboard()
    {
?>
        <div class="wrap">
            <h1>ST AI Tools</h1>
            <p>Placeholder for Manual/p>
        </div>
        <?php
    }

    public static function handle_save_settings()
    {
        if (!isset($_POST['glint_wc_distance_save'])) {
            return;
        }

        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['_wpnonce'], 'glint_wc_distance_settings')) {
            wp_die('Unauthorized action.');
        }

        // Save fields
        $fields = array(
            'glint_wc_distance_google_api_key',
            'glint_wc_distance_shop_address',
        );

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                Glint_WC_Distance_Database::update_setting($field, sanitize_text_field($_POST[$field]));
            }
        }

        // Checkbox field
        $auto_trigger = isset($_POST['glint_wc_distance_auto_trigger']) ? 'yes' : 'no';
        Glint_WC_Distance_Database::update_setting('glint_wc_distance_auto_trigger', $auto_trigger);

        add_action('admin_notices', function () {
            echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully.</p></div>';
        });
    }

    public static function render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $api_key = Glint_WC_Distance_Database::get_setting('glint_wc_distance_google_api_key');
        $shop_address = Glint_WC_Distance_Database::get_setting('glint_wc_distance_shop_address');
        $auto_trigger = Glint_WC_Distance_Database::get_setting('glint_wc_distance_auto_trigger', 'no');

?>
        <div class="wrap">
            <h1>ST WooCommerce Distance Calculator Settings</h1>

            <form method="post" action="">
                <?php wp_nonce_field('glint_wc_distance_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="glint_wc_distance_google_api_key">Google Maps API Key</label></th>
                        <td>
                            <input type="text" id="glint_wc_distance_google_api_key" name="glint_wc_distance_google_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="glint_wc_distance_shop_address">Shop / Warehouse Address</label></th>
                        <td>
                            <input type="text" id="glint_wc_distance_shop_address" name="glint_wc_distance_shop_address" value="<?php echo esc_attr($shop_address); ?>" class="regular-text" />
                            <p class="description">This will be used as the origin when calculating the distance.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="glint_wc_distance_auto_trigger">Auto Trigger on New Order</label></th>
                        <td>
                            <input type="checkbox" id="glint_wc_distance_auto_trigger" name="glint_wc_distance_auto_trigger" value="1" <?php checked($auto_trigger, 'yes'); ?> />
                            <label for="glint_wc_distance_auto_trigger">Enable automatic calculation when an order is created.</label>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" name="glint_wc_distance_save" id="submit" class="button button-primary" value="Save Settings">
                </p>
            </form>

            <hr>

            <h2>Export Distance Data</h2>
            <p>Export all calculated distances for orders.</p>
            <a href="<?php echo esc_url(admin_url('admin-post.php?action=glint_wc_distance_export_csv')); ?>" class="button">Export to CSV</a>
        </div>
        <?php
    }

    public static function handle_export_csv()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized action.');
        }

        global $wpdb;
        $table_distance_name = $wpdb->prefix . 'glint_ai_tool_order_distance';

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=glint_distance_export_' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        // CSV headers
        fputcsv($output, array('Order ID', 'Customer Full Name', 'Shipping Address', 'Distance'));

        // Fetch distances
        $distances = $wpdb->get_results("SELECT order_id, distance FROM $table_distance_name");

        if ($distances) {
            foreach ($distances as $record) {
                $order = wc_get_order($record->order_id);
                if (!$order) {
                    continue;
                }

                $customer_name = $order->get_formatted_shipping_full_name();
                // Get shipping address cleanly formatted (1 line if possible)
                $address_lines = array(
                    $order->get_shipping_address_1(),
                    $order->get_shipping_address_2(),
                    $order->get_shipping_city(),
                    $order->get_shipping_state(),
                    $order->get_shipping_postcode(),
                    $order->get_shipping_country()
                );
                // Remove empty lines
                $address_lines = array_filter($address_lines);
                $address_str = implode(', ', $address_lines);

                fputcsv($output, array(
                    $record->order_id,
                    $customer_name,
                    $address_str,
                    $record->distance
                ));
            }
        }

        fclose($output);
        exit;
    }
}
