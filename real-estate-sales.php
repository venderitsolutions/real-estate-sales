<?php
/**
 * Plugin Name: Real Estate Sales
 * Plugin URI: https://yourcompany.com/
 * Description: Comprehensive real estate sales management system with agent frontend dashboard
 * Version: 2.0.0
 * Author: Your Company
 * License: GPL v2 or later
 * Text Domain: real-estate-sales
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('RES_VERSION', '2.0.0');
define('RES_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RES_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RES_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include required files
require_once RES_PLUGIN_DIR . 'includes/class-database.php';
require_once RES_PLUGIN_DIR . 'includes/class-admin-menu.php';
require_once RES_PLUGIN_DIR . 'includes/class-ajax-handler.php';
require_once RES_PLUGIN_DIR . 'includes/class-frontend-dashboard.php';
require_once RES_PLUGIN_DIR . 'includes/class-voucher-generator.php';

// Activation hook
register_activation_hook(__FILE__, array('RES_Database', 'activate'));

// Initialize plugin
add_action('plugins_loaded', 'res_init_plugin');
function res_init_plugin() {
    // Check and update database if needed
    RES_Database::check_and_update();
    
    // Initialize admin menu
    if (is_admin()) {
        new RES_Admin_Menu();
        new RES_Ajax_Handler();
    }
    
    // Initialize frontend dashboard
    new RES_Frontend_Dashboard();
    
    // Initialize voucher generator
    new RES_Voucher_Generator();
}

// Enqueue admin styles and scripts
add_action('admin_enqueue_scripts', 'res_enqueue_admin_assets');
function res_enqueue_admin_assets($hook) {
    if (strpos($hook, 'real-estate-sales') !== false) {
        wp_enqueue_style('res-admin-style', RES_PLUGIN_URL . 'assets/css/admin-style.css', array(), RES_VERSION);
        wp_enqueue_script('res-admin-script', RES_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery'), RES_VERSION, true);
        wp_localize_script('res-admin-script', 'res_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('res_ajax_nonce'),
            'plugin_url' => RES_PLUGIN_URL
        ));
    }
}

// Enqueue frontend styles and scripts
add_action('wp_enqueue_scripts', 'res_enqueue_frontend_assets');
function res_enqueue_frontend_assets() {
    wp_enqueue_style('res-frontend-style', RES_PLUGIN_URL . 'assets/css/frontend-style.css', array(), RES_VERSION);
    wp_enqueue_script('res-frontend-script', RES_PLUGIN_URL . 'assets/js/frontend-script.js', array('jquery'), RES_VERSION, true);
    wp_localize_script('res-frontend-script', 'res_frontend_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('res_frontend_nonce'),
        'plugin_url' => RES_PLUGIN_URL
    ));
}

// Add admin notice for database update
add_action('admin_notices', 'res_database_update_notice');
function res_database_update_notice() {
    $current_version = get_option('res_db_version', '1.0.0');
    if (version_compare($current_version, '2.0.0', '<') && current_user_can('manage_options')) {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>Real Estate Sales Plugin:</strong> Database needs to be updated to version 2.0.0. ';
        echo '<a href="' . admin_url('admin.php?page=real-estate-sales&update_db=1') . '" class="button button-primary">Update Database Now</a></p>';
        echo '</div>';
    }
}

// Handle manual database update
add_action('admin_init', 'res_handle_manual_db_update');
function res_handle_manual_db_update() {
    if (isset($_GET['update_db']) && $_GET['update_db'] == '1' && current_user_can('manage_options')) {
        RES_Database::activate(); // This will run the full update
        wp_redirect(admin_url('admin.php?page=real-estate-sales&db_updated=1'));
        exit;
    }
    
    if (isset($_GET['db_updated']) && $_GET['db_updated'] == '1') {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>Real Estate Sales Plugin:</strong> Database has been successfully updated to version 2.0.0!</p>';
            echo '</div>';
        });
    }
}