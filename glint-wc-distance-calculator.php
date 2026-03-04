<?php
/**
 * Plugin Name: ST WooCommerce Distance Calculator
 * Description: Calculates driving distance between shop address and customer shipping address using Google Maps API.
 * Version: 1.0.0
 * Author: Kael
 * Text Domain: glint-wc-distance
 */

if (!defined('ABSPATH')) {
    exit;
}

define('GLINT_WC_DISTANCE_VERSION', '1.0.0');
define('GLINT_WC_DISTANCE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GLINT_WC_DISTANCE_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load core classes
require_once GLINT_WC_DISTANCE_PLUGIN_DIR . 'includes/class-database.php';
require_once GLINT_WC_DISTANCE_PLUGIN_DIR . 'includes/class-settings.php';
require_once GLINT_WC_DISTANCE_PLUGIN_DIR . 'includes/class-google-maps-api.php';
require_once GLINT_WC_DISTANCE_PLUGIN_DIR . 'includes/class-order-meta-box.php';
require_once GLINT_WC_DISTANCE_PLUGIN_DIR . 'includes/class-order-hooks.php';

/**
 * Initialize the plugin
 */
function glint_wc_distance_init()
{
    // Check if WooCommerce is active
    if (class_exists('WooCommerce')) {
        Glint_WC_Distance_Database::init();
        Glint_WC_Distance_Settings::init();
        Glint_WC_Distance_Order_Meta_Box::init();
        Glint_WC_Distance_Order_Hooks::init();
    }
}
add_action('plugins_loaded', 'glint_wc_distance_init');

/**
 * Plugin activation hook
 */
function glint_wc_distance_activate()
{
    require_once GLINT_WC_DISTANCE_PLUGIN_DIR . 'includes/class-database.php';
    Glint_WC_Distance_Database::create_tables();
}
register_activation_hook(__FILE__, 'glint_wc_distance_activate');
