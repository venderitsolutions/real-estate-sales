<?php
class RES_Admin_Menu {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            'Real Estate Sales',
            'Real Estate Sales',
            'manage_options',
            'real-estate-sales',
            array($this, 'load_module'),
            'dashicons-building',
            30
        );
        
        // Submenu items
        $submenus = array(
            array('Dashboard', 'Dashboard', 'dashboard'),
            array('Clients', 'Clients', 'clients'),
            array('Agents', 'Agents', 'agents'),
            array('Teams', 'Teams', 'teams'),
            array('Sales', 'Sales', 'sales'),
            array('Positions', 'Positions', 'positions'),
            array('Commissions', 'Commissions', 'commissions'),
            array('Collections', 'Collections', 'collections'),
            array('Developer Collections', 'Dev Collections', 'developer-collections'),
            array('Reports', 'Reports', 'reports'),
            array('Developers', 'Developers', 'developers'),
            array('Projects', 'Projects', 'projects'),
            array('Settings', 'Settings', 'settings')
        );
        
        foreach ($submenus as $submenu) {
            add_submenu_page(
                'real-estate-sales',
                $submenu[0],
                $submenu[1],
                'manage_options',
                'real-estate-sales&module=' . $submenu[2],
                array($this, 'load_module')
            );
        }
        
        // Remove duplicate main menu item
        remove_submenu_page('real-estate-sales', 'real-estate-sales');
    }
    
    public function load_module() {
        $module = isset($_GET['module']) ? sanitize_text_field($_GET['module']) : 'dashboard';
        $module_file = RES_PLUGIN_DIR . 'modules/' . $module . '.php';
        
        if (file_exists($module_file)) {
            require_once $module_file;
        } else {
            require_once RES_PLUGIN_DIR . 'modules/dashboard.php';
        }
    }
}