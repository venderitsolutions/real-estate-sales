<?php
/**
 * Uninstall Real Estate Sales Plugin
 * 
 * This file is executed when the plugin is deleted
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Check if we should delete data (you might want to add an option for this)
$delete_data = get_option('res_delete_data_on_uninstall', false);

if ($delete_data) {
    // Drop all plugin tables
    $tables = array(
        $wpdb->prefix . 'res_clients',
        $wpdb->prefix . 'res_residential_sales',
        $wpdb->prefix . 'res_sales_agents',
        $wpdb->prefix . 'res_agent_teams',
        $wpdb->prefix . 'res_agent_positions',
        $wpdb->prefix . 'res_projects',
        $wpdb->prefix . 'res_developers',
        $wpdb->prefix . 'res_developer_collections',
        $wpdb->prefix . 'res_account_collections',
        $wpdb->prefix . 'res_released_commissions',
        $wpdb->prefix . 'res_agent_cash_advances',
        $wpdb->prefix . 'res_ref_gender',
        $wpdb->prefix . 'res_ref_civil_status',
        $wpdb->prefix . 'res_ref_employment_type',
        $wpdb->prefix . 'res_ref_status',
        $wpdb->prefix . 'res_ref_payment_status',
        $wpdb->prefix . 'res_ref_document_status',
        $wpdb->prefix . 'res_ref_source_of_sale',
        $wpdb->prefix . 'res_ref_license_status',
        $wpdb->prefix . 'res_ref_or_status',
        $wpdb->prefix . 'res_ref_agent_status',
        $wpdb->prefix . 'res_ref_2307_status'
    );
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }
    
    // Delete all plugin options
    $options = array(
        'res_db_version',
        'res_delete_data_on_uninstall',
        'res_max_advance_amount',
        'res_advance_approval_required',
        'res_default_commission_rate',
        'res_vat_rate',
        'res_ewt_rate'
    );
    
    foreach ($options as $option) {
        delete_option($option);
    }
    
    // Clear any cached data
    wp_cache_flush();
}