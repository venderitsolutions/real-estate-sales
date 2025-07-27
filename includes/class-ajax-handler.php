<?php
class RES_Ajax_Handler {
    public function __construct() {
        // Dashboard AJAX
        add_action('wp_ajax_res_get_dashboard_data', array($this, 'get_dashboard_data'));
        
        // Frontend Dashboard AJAX
        add_action('wp_ajax_res_frontend_dashboard_data', array($this, 'frontend_dashboard_data'));
        add_action('wp_ajax_res_frontend_save_client', array($this, 'frontend_save_client'));
        add_action('wp_ajax_res_frontend_save_sale', array($this, 'frontend_save_sale'));
        add_action('wp_ajax_res_frontend_get_client', array($this, 'frontend_get_client'));
        add_action('wp_ajax_res_frontend_get_sale', array($this, 'frontend_get_sale'));
        add_action('wp_ajax_res_frontend_delete_client', array($this, 'frontend_delete_client'));
        add_action('wp_ajax_res_frontend_delete_sale', array($this, 'frontend_delete_sale'));
        add_action('wp_ajax_res_frontend_get_clients', array($this, 'frontend_get_clients'));
        add_action('wp_ajax_res_frontend_get_sales', array($this, 'frontend_get_sales'));
        add_action('wp_ajax_res_frontend_get_commissions', array($this, 'frontend_get_commissions'));
        
        // Voucher AJAX
        add_action('wp_ajax_res_generate_voucher', array($this, 'generate_voucher'));
        add_action('wp_ajax_res_get_unreleased_collections', array($this, 'get_unreleased_collections'));
        add_action('wp_ajax_res_get_voucher_details', array($this, 'get_voucher_details'));
        add_action('wp_ajax_res_delete_voucher', array($this, 'delete_voucher'));
        
        // CRUD operations for all modules
        add_action('wp_ajax_res_save_client', array($this, 'save_client'));
        add_action('wp_ajax_res_save_agent', array($this, 'save_agent'));
        add_action('wp_ajax_res_save_team', array($this, 'save_team'));
        add_action('wp_ajax_res_save_sale', array($this, 'save_sale'));
        add_action('wp_ajax_res_save_position', array($this, 'save_position'));
        add_action('wp_ajax_res_save_commission', array($this, 'save_commission'));
        add_action('wp_ajax_res_save_collection', array($this, 'save_collection'));
        add_action('wp_ajax_res_save_developer', array($this, 'save_developer'));
        add_action('wp_ajax_res_save_project', array($this, 'save_project'));
        
        add_action('wp_ajax_res_delete_client', array($this, 'delete_client'));
        add_action('wp_ajax_res_delete_agent', array($this, 'delete_agent'));
        add_action('wp_ajax_res_delete_team', array($this, 'delete_team'));
        add_action('wp_ajax_res_delete_sale', array($this, 'delete_sale'));
        add_action('wp_ajax_res_delete_position', array($this, 'delete_position'));
        add_action('wp_ajax_res_delete_commission', array($this, 'delete_commission'));
        add_action('wp_ajax_res_delete_collection', array($this, 'delete_collection'));
        add_action('wp_ajax_res_delete_developer', array($this, 'delete_developer'));
        add_action('wp_ajax_res_delete_project', array($this, 'delete_project'));
        
        add_action('wp_ajax_res_get_client', array($this, 'get_client'));
        add_action('wp_ajax_res_get_agent', array($this, 'get_agent'));
        add_action('wp_ajax_res_get_team', array($this, 'get_team'));
        add_action('wp_ajax_res_get_sale', array($this, 'get_sale'));
        add_action('wp_ajax_res_get_position', array($this, 'get_position'));
        add_action('wp_ajax_res_get_commission', array($this, 'get_commission'));
        add_action('wp_ajax_res_get_collection', array($this, 'get_collection'));
        add_action('wp_ajax_res_get_developer', array($this, 'get_developer'));
        add_action('wp_ajax_res_get_project', array($this, 'get_project'));
        
        // Additional specific actions
        add_action('wp_ajax_res_get_team_members', array($this, 'get_team_members'));
        add_action('wp_ajax_res_get_developer_projects', array($this, 'get_developer_projects'));
        add_action('wp_ajax_res_get_project_sales', array($this, 'get_project_sales'));
        add_action('wp_ajax_res_generate_report', array($this, 'generate_report'));
        add_action('wp_ajax_res_update_reference', array($this, 'update_reference'));
        add_action('wp_ajax_res_delete_reference', array($this, 'delete_reference'));
        add_action('wp_ajax_res_add_reference', array($this, 'add_reference'));
        add_action('wp_ajax_res_save_advance_settings', array($this, 'save_advance_settings'));
        add_action('wp_ajax_res_save_commission_settings', array($this, 'save_commission_settings'));
        add_action('wp_ajax_res_save_developer_collection', array($this, 'save_developer_collection'));
        add_action('wp_ajax_res_delete_developer_collection', array($this, 'delete_developer_collection'));
        add_action('wp_ajax_res_get_developer_collection', array($this, 'get_developer_collection'));
        add_action('wp_ajax_res_get_developer_collection_by_or', array($this, 'get_developer_collection_by_or'));
    }
    
    // Dashboard Methods
    public function get_dashboard_data() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        // Get sales data
        $active_net = $wpdb->get_var("SELECT SUM(net_tcp) FROM {$wpdb->prefix}res_residential_sales WHERE status_id = 1");
        $active_gross = $wpdb->get_var("SELECT SUM(gross_tcp) FROM {$wpdb->prefix}res_residential_sales WHERE status_id = 1");
        $cancelled_net = $wpdb->get_var("SELECT SUM(net_tcp) FROM {$wpdb->prefix}res_residential_sales WHERE status_id = 2");
        $cancelled_gross = $wpdb->get_var("SELECT SUM(gross_tcp) FROM {$wpdb->prefix}res_residential_sales WHERE status_id = 2");
        
        // Get counts
        $active_accounts = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}res_residential_sales WHERE status_id = 1");
        $cancelled_accounts = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}res_residential_sales WHERE status_id = 2");
        $complete_docs = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}res_residential_sales WHERE document_status_id = 1");
        $incomplete_docs = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}res_residential_sales WHERE document_status_id = 2");
        
        // Get monthly sales trend
        $monthly_trend = $wpdb->get_results("
            SELECT DATE_FORMAT(reservation_date, '%Y-%m') as month, 
                   SUM(net_tcp) as net_sales, 
                   SUM(gross_tcp) as gross_sales
            FROM {$wpdb->prefix}res_residential_sales
            WHERE status_id = 1 AND reservation_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY month
            ORDER BY month
        ");
        
        wp_send_json_success(array(
            'widgets' => array(
                'active_net' => number_format($active_net ?: 0, 2),
                'active_gross' => number_format($active_gross ?: 0, 2),
                'cancelled_net' => number_format($cancelled_net ?: 0, 2),
                'cancelled_gross' => number_format($cancelled_gross ?: 0, 2),
                'active_accounts' => $active_accounts ?: 0,
                'cancelled_accounts' => $cancelled_accounts ?: 0,
                'complete_docs' => $complete_docs ?: 0,
                'incomplete_docs' => $incomplete_docs ?: 0
            ),
            'monthly_trend' => $monthly_trend
        ));
    }
    
    // Client Methods
    public function save_client() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $data = array(
            'first_name' => sanitize_text_field($_POST['first_name']),
            'surname' => sanitize_text_field($_POST['surname']),
            'middle_initial' => sanitize_text_field($_POST['middle_initial'] ?? ''),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'contact_no' => sanitize_text_field($_POST['contact_no'] ?? ''),
            'civil_status_id' => !empty($_POST['civil_status_id']) ? intval($_POST['civil_status_id']) : null,
            'gender_id' => !empty($_POST['gender_id']) ? intval($_POST['gender_id']) : null,
            'date_of_birth' => !empty($_POST['date_of_birth']) ? sanitize_text_field($_POST['date_of_birth']) : null,
            'tin' => sanitize_text_field($_POST['tin'] ?? ''),
            'citizenship' => sanitize_text_field($_POST['citizenship'] ?? ''),
            'employer_name' => sanitize_text_field($_POST['employer_name'] ?? ''),
            'occupation' => sanitize_text_field($_POST['occupation'] ?? ''),
            'employment_type_id' => !empty($_POST['employment_type_id']) ? intval($_POST['employment_type_id']) : null,
            'unit_street_village' => sanitize_text_field($_POST['unit_street_village'] ?? ''),
            'barangay' => sanitize_text_field($_POST['barangay'] ?? ''),
            'city' => sanitize_text_field($_POST['city'] ?? ''),
            'province' => sanitize_text_field($_POST['province'] ?? ''),
            'zip_code' => sanitize_text_field($_POST['zip_code'] ?? ''),
            'source_of_sale_id' => !empty($_POST['source_of_sale_id']) ? intval($_POST['source_of_sale_id']) : null,
            // Secondary client fields
            'secondary_client_type' => sanitize_text_field($_POST['secondary_client_type'] ?? ''),
            'secondary_first_name' => sanitize_text_field($_POST['secondary_first_name'] ?? ''),
            'secondary_middle_initial' => sanitize_text_field($_POST['secondary_middle_initial'] ?? ''),
            'secondary_surname' => sanitize_text_field($_POST['secondary_surname'] ?? ''),
            'secondary_date_of_birth' => !empty($_POST['secondary_date_of_birth']) ? sanitize_text_field($_POST['secondary_date_of_birth']) : null,
            'secondary_tin' => sanitize_text_field($_POST['secondary_tin'] ?? ''),
            'secondary_gender_id' => !empty($_POST['secondary_gender_id']) ? intval($_POST['secondary_gender_id']) : null,
            'secondary_citizenship' => sanitize_text_field($_POST['secondary_citizenship'] ?? ''),
            'secondary_email' => sanitize_email($_POST['secondary_email'] ?? ''),
            'secondary_contact_no' => sanitize_text_field($_POST['secondary_contact_no'] ?? ''),
            'secondary_employer_name' => sanitize_text_field($_POST['secondary_employer_name'] ?? ''),
            'secondary_occupation' => sanitize_text_field($_POST['secondary_occupation'] ?? ''),
            'secondary_unit_street_village' => sanitize_text_field($_POST['secondary_unit_street_village'] ?? ''),
            'secondary_barangay' => sanitize_text_field($_POST['secondary_barangay'] ?? ''),
            'secondary_city' => sanitize_text_field($_POST['secondary_city'] ?? ''),
            'secondary_province' => sanitize_text_field($_POST['secondary_province'] ?? ''),
            'secondary_zip_code' => sanitize_text_field($_POST['secondary_zip_code'] ?? '')
        );
        
        // Remove null values to avoid database issues
        $data = array_filter($data, function($value) {
            return $value !== null && $value !== '';
        });
        
        if (isset($_POST['client_id']) && !empty($_POST['client_id'])) {
            $result = $wpdb->update($wpdb->prefix . 'res_clients', $data, array('client_id' => intval($_POST['client_id'])));
        } else {
            $result = $wpdb->insert($wpdb->prefix . 'res_clients', $data);
        }
        
        if ($result !== false) {
            wp_send_json_success('Client saved successfully');
        } else {
            wp_send_json_error('Failed to save client: ' . $wpdb->last_error);
        }
    }
    
    public function delete_client() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $client_id = intval($_POST['client_id']);
        $result = $wpdb->delete($wpdb->prefix . 'res_clients', array('client_id' => $client_id));
        
        if ($result) {
            wp_send_json_success('Client deleted successfully');
        } else {
            wp_send_json_error('Failed to delete client');
        }
    }
    
    public function get_client() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $client_id = intval($_POST['client_id']);
        $client = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}res_clients WHERE client_id = %d", $client_id), ARRAY_A);
        
        if ($client) {
            wp_send_json_success($client);
        } else {
            wp_send_json_error('Client not found');
        }
    }
    
    // Agent Methods
    public function save_agent() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $data = array(
            'wp_user_id' => !empty($_POST['wp_user_id']) ? intval($_POST['wp_user_id']) : null,
            'agent_code' => sanitize_text_field($_POST['agent_code'] ?? ''),
            'agent_name' => sanitize_text_field($_POST['agent_name']),
            'date_hired' => !empty($_POST['date_hired']) ? sanitize_text_field($_POST['date_hired']) : null,
            'status_id' => !empty($_POST['status_id']) ? intval($_POST['status_id']) : null,
            'position_code' => sanitize_text_field($_POST['position_code'] ?? ''),
            'team_id' => !empty($_POST['team_id']) ? intval($_POST['team_id']) : null,
            'commission_rate' => !empty($_POST['commission_rate']) ? floatval($_POST['commission_rate']) : null,
            'bank_account' => sanitize_text_field($_POST['bank_account'] ?? '')
        );
        
        // Remove null/empty values
        $data = array_filter($data, function($value) {
            return $value !== null && $value !== '';
        });
        
        if (isset($_POST['agent_id']) && !empty($_POST['agent_id'])) {
            $result = $wpdb->update($wpdb->prefix . 'res_sales_agents', $data, array('agent_id' => intval($_POST['agent_id'])));
        } else {
            $result = $wpdb->insert($wpdb->prefix . 'res_sales_agents', $data);
        }
        
        if ($result !== false) {
            wp_send_json_success('Agent saved successfully');
        } else {
            wp_send_json_error('Failed to save agent: ' . $wpdb->last_error);
        }
    }
    
    public function delete_agent() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $agent_id = intval($_POST['agent_id']);
        $result = $wpdb->delete($wpdb->prefix . 'res_sales_agents', array('agent_id' => $agent_id));
        
        if ($result) {
            wp_send_json_success('Agent deleted successfully');
        } else {
            wp_send_json_error('Failed to delete agent');
        }
    }
    
    public function get_agent() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $agent_id = intval($_POST['agent_id']);
        $agent = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}res_sales_agents WHERE agent_id = %d", $agent_id), ARRAY_A);
        
        if ($agent) {
            wp_send_json_success($agent);
        } else {
            wp_send_json_error('Agent not found');
        }
    }
    
    // Sale Methods
    public function save_sale() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $data = array(
            'client_id' => intval($_POST['client_id']),
            'reservation_date' => sanitize_text_field($_POST['reservation_date']),
            'agent_id' => intval($_POST['agent_id']),
            'status_id' => !empty($_POST['status_id']) ? intval($_POST['status_id']) : null,
            'payment_status_id' => !empty($_POST['payment_status_id']) ? intval($_POST['payment_status_id']) : null,
            'document_status_id' => !empty($_POST['document_status_id']) ? intval($_POST['document_status_id']) : null,
            'project_id' => intval($_POST['project_id']),
            'block' => sanitize_text_field($_POST['block'] ?? ''),
            'lot' => sanitize_text_field($_POST['lot'] ?? ''),
            'unit' => sanitize_text_field($_POST['unit'] ?? ''),
            'net_tcp' => floatval($_POST['net_tcp']),
            'gross_tcp' => floatval($_POST['gross_tcp']),
            'downpayment_type' => sanitize_text_field($_POST['downpayment_type'] ?? ''),
            'downpayment_terms' => sanitize_text_field($_POST['downpayment_terms'] ?? ''),
            'financing_type_id' => !empty($_POST['financing_type_id']) ? intval($_POST['financing_type_id']) : null,
            'year_to_pay' => !empty($_POST['year_to_pay']) ? intval($_POST['year_to_pay']) : null,
            'license_status_id' => !empty($_POST['license_status_id']) ? intval($_POST['license_status_id']) : null,
            'remarks' => sanitize_textarea_field($_POST['remarks'] ?? '')
        );
        
        if (isset($_POST['sale_id']) && !empty($_POST['sale_id'])) {
            $result = $wpdb->update($wpdb->prefix . 'res_residential_sales', $data, array('sale_id' => intval($_POST['sale_id'])));
        } else {
            $result = $wpdb->insert($wpdb->prefix . 'res_residential_sales', $data);
        }
        
        if ($result !== false) {
            wp_send_json_success('Sale saved successfully');
        } else {
            wp_send_json_error('Failed to save sale: ' . $wpdb->last_error);
        }
    }
    
    public function delete_sale() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $sale_id = intval($_POST['sale_id']);
        $result = $wpdb->delete($wpdb->prefix . 'res_residential_sales', array('sale_id' => $sale_id));
        
        if ($result) {
            wp_send_json_success('Sale deleted successfully');
        } else {
            wp_send_json_error('Failed to delete sale');
        }
    }
    
    public function get_sale() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $sale_id = intval($_POST['sale_id']);
        $sale = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}res_residential_sales WHERE sale_id = %d", $sale_id), ARRAY_A);
        
        if ($sale) {
            wp_send_json_success($sale);
        } else {
            wp_send_json_error('Sale not found');
        }
    }
    
    // Collection Methods
    public function save_collection() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $data = array(
            'or_number' => sanitize_text_field($_POST['or_number']),
            'date_collected' => sanitize_text_field($_POST['date_collected']),
            'payor' => sanitize_text_field($_POST['payor']),
            'payee' => sanitize_text_field($_POST['payee']),
            'client_id' => intval($_POST['client_id']),
            'agent_id' => intval($_POST['agent_id']),
            'developer_id' => intval($_POST['developer_id']),
            'project_id' => intval($_POST['project_id']),
            'block' => sanitize_text_field($_POST['block'] ?? ''),
            'lot' => sanitize_text_field($_POST['lot'] ?? ''),
            'commission_percentage' => floatval($_POST['commission_percentage'] ?? 0),
            'gross_commission' => floatval($_POST['gross_commission']),
            'vat' => floatval($_POST['vat'] ?? 0),
            'ewt' => floatval($_POST['ewt'] ?? 0),
            'net_commission' => floatval($_POST['net_commission']),
            'particulars' => sanitize_textarea_field($_POST['particulars'] ?? ''),
            'cname_particulars' => sanitize_textarea_field($_POST['cname_particulars'] ?? '')
        );
        
        if (isset($_POST['acct_collection_id']) && !empty($_POST['acct_collection_id'])) {
            $result = $wpdb->update($wpdb->prefix . 'res_account_collections', $data, array('acct_collection_id' => intval($_POST['acct_collection_id'])));
        } else {
            $result = $wpdb->insert($wpdb->prefix . 'res_account_collections', $data);
        }
        
        if ($result !== false) {
            wp_send_json_success('Collection saved successfully');
        } else {
            wp_send_json_error('Failed to save collection: ' . $wpdb->last_error);
        }
    }
    
    public function delete_collection() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $collection_id = intval($_POST['collection_id']);
        $result = $wpdb->delete($wpdb->prefix . 'res_account_collections', array('acct_collection_id' => $collection_id));
        
        if ($result) {
            wp_send_json_success('Collection deleted successfully');
        } else {
            wp_send_json_error('Failed to delete collection');
        }
    }
    
    public function get_collection() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $collection_id = intval($_POST['collection_id']);
        $collection = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}res_account_collections WHERE acct_collection_id = %d", $collection_id), ARRAY_A);
        
        if ($collection) {
            wp_send_json_success($collection);
        } else {
            wp_send_json_error('Collection not found');
        }
    }
    
    // Frontend Dashboard Methods
    private function get_current_agent() {
        global $wpdb;
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return false;
        }
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}res_sales_agents WHERE wp_user_id = %d",
            $user_id
        ));
    }
    
    public function frontend_dashboard_data() {
        check_ajax_referer('res_frontend_nonce', 'nonce');
        
        $agent = $this->get_current_agent();
        if (!$agent) {
            wp_send_json_error('Agent not found');
        }
        
        global $wpdb;
        
        // Get statistics
        $total_clients = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}res_clients WHERE created_by_agent_id = %d",
            $agent->agent_id
        ));
        
        $total_sales = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}res_residential_sales WHERE agent_id = %d",
            $agent->agent_id
        ));
        
        $active_sales = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}res_residential_sales WHERE agent_id = %d AND status_id = 1",
            $agent->agent_id
        ));
        
        $total_commissions = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(total_net_amount), 0) FROM {$wpdb->prefix}res_commission_vouchers WHERE payee_agent_id = %d",
            $agent->agent_id
        ));
        
        // Get recent sales
        $recent_sales = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, CONCAT(c.first_name, ' ', c.surname) as client_name, p.project_name
             FROM {$wpdb->prefix}res_residential_sales s
             LEFT JOIN {$wpdb->prefix}res_clients c ON s.client_id = c.client_id
             LEFT JOIN {$wpdb->prefix}res_projects p ON s.project_id = p.project_id
             WHERE s.agent_id = %d
             ORDER BY s.reservation_date DESC
             LIMIT 5",
            $agent->agent_id
        ));
        
        wp_send_json_success(array(
            'total_clients' => intval($total_clients),
            'total_sales' => intval($total_sales),
            'active_sales' => intval($active_sales),
            'total_commissions' => number_format($total_commissions, 2),
            'recent_sales' => $recent_sales
        ));
    }
    
    public function frontend_save_client() {
        check_ajax_referer('res_frontend_nonce', 'nonce');
        
        $agent = $this->get_current_agent();
        if (!$agent) {
            wp_send_json_error('Agent not found');
        }
        
        global $wpdb;
        
        $data = array(
            'first_name' => sanitize_text_field($_POST['first_name']),
            'middle_initial' => sanitize_text_field($_POST['middle_initial'] ?? ''),
            'surname' => sanitize_text_field($_POST['surname']),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'contact_no' => sanitize_text_field($_POST['contact_no'] ?? ''),
            'date_of_birth' => !empty($_POST['date_of_birth']) ? sanitize_text_field($_POST['date_of_birth']) : null,
            'created_by_agent_id' => $agent->agent_id
        );
        
        if (isset($_POST['client_id']) && !empty($_POST['client_id'])) {
            // Update existing client (only if created by this agent)
            $result = $wpdb->update(
                $wpdb->prefix . 'res_clients',
                $data,
                array(
                    'client_id' => intval($_POST['client_id']),
                    'created_by_agent_id' => $agent->agent_id
                )
            );
        } else {
            // Create new client
            $result = $wpdb->insert($wpdb->prefix . 'res_clients', $data);
        }
        
        if ($result !== false) {
            wp_send_json_success('Client saved successfully');
        } else {
            wp_send_json_error('Failed to save client: ' . $wpdb->last_error);
        }
    }
    
    public function frontend_save_sale() {
        check_ajax_referer('res_frontend_nonce', 'nonce');
        
        $agent = $this->get_current_agent();
        if (!$agent) {
            wp_send_json_error('Agent not found');
        }
        
        global $wpdb;
        
        $data = array(
            'client_id' => intval($_POST['client_id']),
            'project_id' => intval($_POST['project_id']),
            'reservation_date' => sanitize_text_field($_POST['reservation_date']),
            'agent_id' => $agent->agent_id,
            'block' => sanitize_text_field($_POST['block'] ?? ''),
            'lot' => sanitize_text_field($_POST['lot'] ?? ''),
            'unit' => sanitize_text_field($_POST['unit'] ?? ''),
            'net_tcp' => floatval($_POST['net_tcp']),
            'gross_tcp' => floatval($_POST['gross_tcp']),
            'status_id' => 1 // Default to Active
        );
        
        if (isset($_POST['sale_id']) && !empty($_POST['sale_id'])) {
            // Update existing sale (only if agent's sale)
            $result = $wpdb->update(
                $wpdb->prefix . 'res_residential_sales',
                $data,
                array(
                    'sale_id' => intval($_POST['sale_id']),
                    'agent_id' => $agent->agent_id
                )
            );
        } else {
            // Create new sale
            $result = $wpdb->insert($wpdb->prefix . 'res_residential_sales', $data);
        }
        
        if ($result !== false) {
            wp_send_json_success('Sale saved successfully');
        } else {
            wp_send_json_error('Failed to save sale: ' . $wpdb->last_error);
        }
    }
    
    public function frontend_get_clients() {
        check_ajax_referer('res_frontend_nonce', 'nonce');
        
        $agent = $this->get_current_agent();
        if (!$agent) {
            wp_send_json_error('Agent not found');
        }
        
        global $wpdb;
        
        $clients = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}res_clients WHERE created_by_agent_id = %d ORDER BY created_at DESC",
            $agent->agent_id
        ));
        
        wp_send_json_success($clients);
    }
    
    public function frontend_get_sales() {
        check_ajax_referer('res_frontend_nonce', 'nonce');
        
        $agent = $this->get_current_agent();
        if (!$agent) {
            wp_send_json_error('Agent not found');
        }
        
        global $wpdb;
        
        $sales = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, CONCAT(c.first_name, ' ', c.surname) as client_name, p.project_name
             FROM {$wpdb->prefix}res_residential_sales s
             LEFT JOIN {$wpdb->prefix}res_clients c ON s.client_id = c.client_id
             LEFT JOIN {$wpdb->prefix}res_projects p ON s.project_id = p.project_id
             WHERE s.agent_id = %d
             ORDER BY s.reservation_date DESC",
            $agent->agent_id
        ));
        
        wp_send_json_success($sales);
    }
    
    public function frontend_get_commissions() {
        check_ajax_referer('res_frontend_nonce', 'nonce');
        
        $agent = $this->get_current_agent();
        if (!$agent) {
            wp_send_json_error('Agent not found');
        }
        
        global $wpdb;
        
        $commissions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}res_commission_vouchers WHERE payee_agent_id = %d ORDER BY voucher_date DESC",
            $agent->agent_id
        ));
        
        wp_send_json_success($commissions);
    }
    
    public function frontend_get_client() {
        check_ajax_referer('res_frontend_nonce', 'nonce');
        
        $agent = $this->get_current_agent();
        if (!$agent) {
            wp_send_json_error('Agent not found');
        }
        
        global $wpdb;
        $client_id = intval($_POST['client_id']);
        
        $client = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}res_clients WHERE client_id = %d AND created_by_agent_id = %d",
            $client_id, $agent->agent_id
        ), ARRAY_A);
        
        if ($client) {
            wp_send_json_success($client);
        } else {
            wp_send_json_error('Client not found');
        }
    }
    
    public function frontend_get_sale() {
        check_ajax_referer('res_frontend_nonce', 'nonce');
        
        $agent = $this->get_current_agent();
        if (!$agent) {
            wp_send_json_error('Agent not found');
        }
        
        global $wpdb;
        $sale_id = intval($_POST['sale_id']);
        
        $sale = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}res_residential_sales WHERE sale_id = %d AND agent_id = %d",
            $sale_id, $agent->agent_id
        ), ARRAY_A);
        
        if ($sale) {
            wp_send_json_success($sale);
        } else {
            wp_send_json_error('Sale not found');
        }
    }
    
    public function frontend_delete_client() {
        check_ajax_referer('res_frontend_nonce', 'nonce');
        
        $agent = $this->get_current_agent();
        if (!$agent) {
            wp_send_json_error('Agent not found');
        }
        
        global $wpdb;
        $client_id = intval($_POST['client_id']);
        
        $result = $wpdb->delete(
            $wpdb->prefix . 'res_clients',
            array(
                'client_id' => $client_id,
                'created_by_agent_id' => $agent->agent_id
            )
        );
        
        if ($result) {
            wp_send_json_success('Client deleted successfully');
        } else {
            wp_send_json_error('Failed to delete client');
        }
    }
    
    public function frontend_delete_sale() {
        check_ajax_referer('res_frontend_nonce', 'nonce');
        
        $agent = $this->get_current_agent();
        if (!$agent) {
            wp_send_json_error('Agent not found');
        }
        
        global $wpdb;
        $sale_id = intval($_POST['sale_id']);
        
        $result = $wpdb->delete(
            $wpdb->prefix . 'res_residential_sales',
            array(
                'sale_id' => $sale_id,
                'agent_id' => $agent->agent_id
            )
        );
        
        if ($result) {
            wp_send_json_success('Sale deleted successfully');
        } else {
            wp_send_json_error('Failed to delete sale');
        }
    }
    
    // Additional Methods for other modules
    public function save_team() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $data = array(
            'team_name' => sanitize_text_field($_POST['team_name']),
            'team_leader' => !empty($_POST['team_leader']) ? intval($_POST['team_leader']) : null,
            'date_created' => !empty($_POST['date_created']) ? sanitize_text_field($_POST['date_created']) : null
        );
        
        if (isset($_POST['team_id']) && !empty($_POST['team_id'])) {
            $result = $wpdb->update($wpdb->prefix . 'res_agent_teams', $data, array('team_id' => intval($_POST['team_id'])));
        } else {
            $result = $wpdb->insert($wpdb->prefix . 'res_agent_teams', $data);
        }
        
        if ($result !== false) {
            wp_send_json_success('Team saved successfully');
        } else {
            wp_send_json_error('Failed to save team: ' . $wpdb->last_error);
        }
    }
    
    public function delete_team() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $team_id = intval($_POST['team_id']);
        $result = $wpdb->delete($wpdb->prefix . 'res_agent_teams', array('team_id' => $team_id));
        
        if ($result) {
            wp_send_json_success('Team deleted successfully');
        } else {
            wp_send_json_error('Failed to delete team');
        }
    }
    
    public function get_team() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $team_id = intval($_POST['team_id']);
        $team = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}res_agent_teams WHERE team_id = %d", $team_id), ARRAY_A);
        
        if ($team) {
            wp_send_json_success($team);
        } else {
            wp_send_json_error('Team not found');
        }
    }
    
    public function save_position() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $data = array(
            'position_no' => intval($_POST['position_no']),
            'position_code' => sanitize_text_field($_POST['position_code']),
            'position' => sanitize_text_field($_POST['position'])
        );
        
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            $result = $wpdb->update($wpdb->prefix . 'res_agent_positions', $data, array('id' => intval($_POST['id'])));
        } else {
            $result = $wpdb->insert($wpdb->prefix . 'res_agent_positions', $data);
        }
        
        if ($result !== false) {
            wp_send_json_success('Position saved successfully');
        } else {
            wp_send_json_error('Failed to save position: ' . $wpdb->last_error);
        }
    }
    
    public function delete_position() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $id = intval($_POST['position_id']);
        $result = $wpdb->delete($wpdb->prefix . 'res_agent_positions', array('id' => $id));
        
        if ($result) {
            wp_send_json_success('Position deleted successfully');
        } else {
            wp_send_json_error('Failed to delete position');
        }
    }
    
    public function get_position() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $id = intval($_POST['position_id']);
        $position = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}res_agent_positions WHERE id = %d", $id), ARRAY_A);
        
        if ($position) {
            wp_send_json_success($position);
        } else {
            wp_send_json_error('Position not found');
        }
    }
    
    public function save_developer() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $data = array(
            'developer_name' => sanitize_text_field($_POST['developer_name'])
        );
        
        if (isset($_POST['developer_id']) && !empty($_POST['developer_id'])) {
            $result = $wpdb->update($wpdb->prefix . 'res_developers', $data, array('developer_id' => intval($_POST['developer_id'])));
        } else {
            $result = $wpdb->insert($wpdb->prefix . 'res_developers', $data);
        }
        
        if ($result !== false) {
            wp_send_json_success('Developer saved successfully');
        } else {
            wp_send_json_error('Failed to save developer: ' . $wpdb->last_error);
        }
    }
    
    public function delete_developer() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $developer_id = intval($_POST['developer_id']);
        $result = $wpdb->delete($wpdb->prefix . 'res_developers', array('developer_id' => $developer_id));
        
        if ($result) {
            wp_send_json_success('Developer deleted successfully');
        } else {
            wp_send_json_error('Failed to delete developer');
        }
    }
    
    public function get_developer() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $developer_id = intval($_POST['developer_id']);
        $developer = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}res_developers WHERE developer_id = %d", $developer_id), ARRAY_A);
        
        if ($developer) {
            wp_send_json_success($developer);
        } else {
            wp_send_json_error('Developer not found');
        }
    }
    
    public function save_project() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $data = array(
            'project_name' => sanitize_text_field($_POST['project_name']),
            'developer_id' => intval($_POST['developer_id']),
            'date_accredited' => !empty($_POST['date_accredited']) ? sanitize_text_field($_POST['date_accredited']) : null
        );
        
        if (isset($_POST['project_id']) && !empty($_POST['project_id'])) {
            $result = $wpdb->update($wpdb->prefix . 'res_projects', $data, array('project_id' => intval($_POST['project_id'])));
        } else {
            $result = $wpdb->insert($wpdb->prefix . 'res_projects', $data);
        }
        
        if ($result !== false) {
            wp_send_json_success('Project saved successfully');
        } else {
            wp_send_json_error('Failed to save project: ' . $wpdb->last_error);
        }
    }
    
    public function delete_project() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $project_id = intval($_POST['project_id']);
        $result = $wpdb->delete($wpdb->prefix . 'res_projects', array('project_id' => $project_id));
        
        if ($result) {
            wp_send_json_success('Project deleted successfully');
        } else {
            wp_send_json_error('Failed to delete project');
        }
    }
    
    public function get_project() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $project_id = intval($_POST['project_id']);
        $project = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}res_projects WHERE project_id = %d", $project_id), ARRAY_A);
        
        if ($project) {
            wp_send_json_success($project);
        } else {
            wp_send_json_error('Project not found');
        }
    }
    
    // Voucher Methods
    public function generate_voucher() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        
        global $wpdb;
        
        $agent_id = intval($_POST['agent_id']);
        $collection_ids = array_map('intval', $_POST['collection_ids']);
        $deductions = isset($_POST['deductions']) ? $_POST['deductions'] : array();
        $voucher_date = sanitize_text_field($_POST['voucher_date']);
        $prepared_by = sanitize_text_field($_POST['prepared_by']);
        $checked_by = sanitize_text_field($_POST['checked_by']);
        $approved_by = sanitize_text_field($_POST['approved_by']);
        $bizlink_ref = sanitize_text_field($_POST['bizlink_ref']);
        
        if (empty($collection_ids)) {
            wp_send_json_error('No collections selected');
        }
        
        // Generate voucher number
        $voucher_number = $this->generate_voucher_number();
        
        // Calculate totals
        $total_gross = 0;
        $total_deductions = 0;
        $collections = array();
        
        foreach ($collection_ids as $collection_id) {
            $collection = $wpdb->get_row($wpdb->prepare(
                "SELECT ac.*, CONCAT(c.first_name, ' ', c.surname) as client_name, p.project_name
                 FROM {$wpdb->prefix}res_account_collections ac
                 LEFT JOIN {$wpdb->prefix}res_clients c ON ac.client_id = c.client_id
                 LEFT JOIN {$wpdb->prefix}res_projects p ON ac.project_id = p.project_id
                 WHERE ac.acct_collection_id = %d AND ac.agent_id = %d AND ac.is_released = 0",
                $collection_id, $agent_id
            ));
            
            if ($collection) {
                $collections[] = $collection;
                $total_gross += $collection->gross_commission;
            }
        }
        
        // Calculate deductions total
        foreach ($deductions as $deduction) {
            if (isset($deduction['amount'])) {
                $total_deductions += floatval($deduction['amount']);
            }
        }
        
        $total_net = $total_gross - $total_deductions;
        
        // Create voucher record
        $voucher_data = array(
            'voucher_number' => $voucher_number,
            'payee_agent_id' => $agent_id,
            'voucher_date' => $voucher_date,
            'total_gross_amount' => $total_gross,
            'total_deductions' => $total_deductions,
            'total_net_amount' => $total_net,
            'prepared_by' => $prepared_by,
            'checked_by' => $checked_by,
            'approved_by' => $approved_by,
            'bizlink_ref' => $bizlink_ref,
            'status' => 'approved'
        );
        
        $voucher_result = $wpdb->insert($wpdb->prefix . 'res_commission_vouchers', $voucher_data);
        
        if ($voucher_result === false) {
            wp_send_json_error('Failed to create voucher: ' . $wpdb->last_error);
        }
        
        $voucher_id = $wpdb->insert_id;
        
        // Create line items
        $line_order = 1;
        foreach ($collections as $collection) {
            $line_item_data = array(
                'voucher_id' => $voucher_id,
                'collection_id' => $collection->acct_collection_id,
                'commission_percentage' => $collection->commission_percentage,
                'client_name' => $collection->client_name,
                'project_name' => $collection->project_name,
                'amount' => $collection->gross_commission,
                'incremental_cost' => $collection->gross_commission * ($collection->commission_percentage / 100),
                'net_pay' => $collection->net_commission,
                'particulars' => $collection->particulars,
                'line_order' => $line_order++
            );
            
            $wpdb->insert($wpdb->prefix . 'res_voucher_line_items', $line_item_data);
            
            // Mark collection as released
            $wpdb->update(
                $wpdb->prefix . 'res_account_collections',
                array('is_released' => 1, 'voucher_id' => $voucher_id),
                array('acct_collection_id' => $collection->acct_collection_id)
            );
        }
        
        // Create deductions
        foreach ($deductions as $deduction) {
            if (!empty($deduction['description']) && floatval($deduction['amount']) > 0) {
                $deduction_data = array(
                    'voucher_id' => $voucher_id,
                    'description' => sanitize_text_field($deduction['description']),
                    'amount' => floatval($deduction['amount'])
                );
                
                $wpdb->insert($wpdb->prefix . 'res_voucher_deductions', $deduction_data);
            }
        }
        
        wp_send_json_success(array(
            'voucher_id' => $voucher_id,
            'voucher_number' => $voucher_number,
            'message' => 'Voucher generated successfully'
        ));
    }
    
    public function get_unreleased_collections() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        
        global $wpdb;
        $agent_id = intval($_POST['agent_id']);
        
        $collections = $wpdb->get_results($wpdb->prepare(
            "SELECT ac.*, 
                    CONCAT(c.first_name, ' ', c.surname) as client_name,
                    p.project_name
             FROM {$wpdb->prefix}res_account_collections ac
             LEFT JOIN {$wpdb->prefix}res_clients c ON ac.client_id = c.client_id
             LEFT JOIN {$wpdb->prefix}res_projects p ON ac.project_id = p.project_id
             WHERE ac.agent_id = %d AND ac.is_released = 0
             ORDER BY ac.date_collected DESC",
            $agent_id
        ));
        
        wp_send_json_success($collections);
    }
    
    private function generate_voucher_number() {
        global $wpdb;
        
        $date_prefix = 'VR' . date('ymd');
        
        $last_voucher = $wpdb->get_var($wpdb->prepare(
            "SELECT voucher_number FROM {$wpdb->prefix}res_commission_vouchers 
             WHERE voucher_number LIKE %s 
             ORDER BY voucher_id DESC 
             LIMIT 1",
            $date_prefix . '%'
        ));
        
        if ($last_voucher) {
            $last_number = intval(substr($last_voucher, -3));
            $new_number = str_pad($last_number + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $new_number = '001';
        }
        
        return $date_prefix . '-' . $new_number;
    }
    
    // Additional helper methods
    public function get_developer_projects() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $developer_id = intval($_POST['developer_id']);
        $projects = $wpdb->get_results($wpdb->prepare("
            SELECT project_id, project_name, date_accredited 
            FROM {$wpdb->prefix}res_projects 
            WHERE developer_id = %d 
            ORDER BY project_name
        ", $developer_id));
        
        wp_send_json_success($projects);
    }
    
    public function get_developer_collection_by_or() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $or_number = sanitize_text_field($_POST['or_number']);
        $collection = $wpdb->get_row($wpdb->prepare("
            SELECT dc.*, p.project_name, d.developer_name 
            FROM {$wpdb->prefix}res_developer_collections dc
            LEFT JOIN {$wpdb->prefix}res_projects p ON dc.project_id = p.project_id
            LEFT JOIN {$wpdb->prefix}res_developers d ON p.developer_id = d.developer_id
            WHERE dc.or_number = %s
        ", $or_number), ARRAY_A);
        
        if ($collection) {
            wp_send_json_success($collection);
        } else {
            wp_send_json_error('Developer collection not found');
        }
    }
    
    // Settings methods
    public function save_advance_settings() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        
        update_option('res_max_advance_amount', floatval($_POST['max_amount']));
        update_option('res_advance_approval_required', intval($_POST['approval_required']));
        
        wp_send_json_success('Settings saved successfully');
    }
    
    public function save_commission_settings() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        
        update_option('res_default_commission_rate', floatval($_POST['default_rate']));
        update_option('res_vat_rate', floatval($_POST['vat_rate']));
        update_option('res_ewt_rate', floatval($_POST['ewt_rate']));
        
        wp_send_json_success('Settings saved successfully');
    }
    
    public function update_reference() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $table = sanitize_text_field($_POST['table']);
        $field = sanitize_text_field($_POST['field']);
        $id = intval($_POST['id']);
        $value = sanitize_text_field($_POST['value']);
        
        $result = $wpdb->update(
            $wpdb->prefix . $table,
            array($field => $value),
            array('id' => $id)
        );
        
        wp_send_json_success($result !== false);
    }
    
    public function delete_reference() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $table = sanitize_text_field($_POST['table']);
        $id = intval($_POST['id']);
        
        $result = $wpdb->delete($wpdb->prefix . $table, array('id' => $id));
        
        wp_send_json_success($result !== false);
    }
    
    public function add_reference() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $table = sanitize_text_field($_POST['table']);
        $field = sanitize_text_field($_POST['field']);
        $value = sanitize_text_field($_POST['value']);
        
        $result = $wpdb->insert(
            $wpdb->prefix . $table,
            array($field => $value)
        );
        
        wp_send_json_success($result !== false);
    }
}