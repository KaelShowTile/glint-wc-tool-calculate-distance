<?php
/**
 * Database setup class
 */
if (!defined('ABSPATH')) {
    exit;
}

class Glint_WC_Distance_Database
{

    public static function init()
    {
    // Init happens early, but DB creation is done on activation hook.
    }

    public static function create_tables()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // 1. glint_ai_tool_setting table
        $table_setting_name = $wpdb->prefix . 'glint_ai_tool_setting';
        $sql_setting = "CREATE TABLE $table_setting_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            setting_key varchar(255) NOT NULL,
            setting_value longtext NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY setting_key (setting_key)
        ) $charset_collate;";

        dbDelta($sql_setting);

        // 2. glint_ai_tool_order_distance table
        $table_distance_name = $wpdb->prefix . 'glint_ai_tool_order_distance';
        $sql_distance = "CREATE TABLE $table_distance_name (
            record_id int(11) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            distance varchar(255) NOT NULL,
            PRIMARY KEY  (record_id),
            UNIQUE KEY order_id (order_id)
        ) $charset_collate;";

        dbDelta($sql_distance);
    }

    /**
     * Get setting from custom table
     */
    public static function get_setting($key, $default = '')
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'glint_ai_tool_setting';

        // Suppress errors gracefully if table somehow doesn't exist
        $suppress = $wpdb->suppress_errors();
        $row = $wpdb->get_row($wpdb->prepare("SELECT setting_value FROM $table_name WHERE setting_key = %s", $key));
        $wpdb->suppress_errors($suppress);

        if ($row) {
            $value = json_decode($row->setting_value, true);
            return (json_last_error() === JSON_ERROR_NONE) ? $value : $row->setting_value;
        }

        return $default;
    }

    /**
     * Update setting in custom table
     */
    public static function update_setting($key, $value)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'glint_ai_tool_setting';

        $value_str = is_array($value) || is_object($value) ? wp_json_encode($value) : $value;

        // Check if exists
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE setting_key = %s", $key));

        if ($exists) {
            return $wpdb->update(
                $table_name,
                array('setting_value' => $value_str),
                array('setting_key' => $key),
                array('%s'),
                array('%s')
            );
        }
        else {
            return $wpdb->insert(
                $table_name,
                array(
                'setting_key' => $key,
                'setting_value' => $value_str
            ),
                array('%s', '%s')
            );
        }
    }

    /**
     * Get distance for an order
     */
    public static function get_order_distance($order_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'glint_ai_tool_order_distance';

        $suppress = $wpdb->suppress_errors();
        $distance = $wpdb->get_var($wpdb->prepare("SELECT distance FROM $table_name WHERE order_id = %d", $order_id));
        $wpdb->suppress_errors($suppress);

        return $distance;
    }

    /**
     * Update distance for an order
     */
    public static function update_order_distance($order_id, $distance)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'glint_ai_tool_order_distance';

        $exists = $wpdb->get_var($wpdb->prepare("SELECT record_id FROM $table_name WHERE order_id = %d", $order_id));

        if ($exists) {
            return $wpdb->update(
                $table_name,
                array('distance' => $distance),
                array('order_id' => $order_id),
                array('%s'),
                array('%d')
            );
        }
        else {
            return $wpdb->insert(
                $table_name,
                array(
                'order_id' => $order_id,
                'distance' => $distance
            ),
                array('%d', '%s')
            );
        }
    }
}
