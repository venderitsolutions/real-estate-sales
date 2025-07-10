<?php
/**
 * Plugin Name: Real Estate Sales
 * Plugin URI: https://yourcompany.com/
 * Description: Comprehensive real estate sales management system
 * Version: 1.0.0
 * Author: Your Company
 * License: GPL v2 or later
 * Text Domain: real-estate-sales
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('RES_VERSION', '1.0.0');
define('RES_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RES_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RES_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include required files
require_once RES_PLUGIN_DIR . 'includes/class-database.php';
require_once RES_PLUGIN_DIR . 'includes/class-admin-menu.php';
require_once RES_PLUGIN_DIR . 'includes/class-ajax-handler.php';

// Activation hook
register_activation_hook(__FILE__, array('RES_Database', 'activate'));

// Initialize plugin
add_action('plugins_loaded', 'res_init_plugin');
function res_init_plugin() {
    // Initialize database
    new RES_Database();
    
    // Initialize admin menu
    if (is_admin()) {
        new RES_Admin_Menu();
        new RES_Ajax_Handler();
    }
}

// Enqueue admin styles and scripts
add_action('admin_enqueue_scripts', 'res_enqueue_admin_assets');
function res_enqueue_admin_assets($hook) {
    if (strpos($hook, 'real-estate-sales') !== false) {
        wp_enqueue_style('res-admin-style', RES_PLUGIN_URL . 'assets/css/admin-style.css', array(), RES_VERSION);
        wp_enqueue_script('res-admin-script', RES_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery'), RES_VERSION, true);
        wp_localize_script('res-admin-script', 'res_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('res_ajax_nonce')
        ));
    }
}