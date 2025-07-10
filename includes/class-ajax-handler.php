<?php
class RES_Ajax_Handler {
    public function __construct() {
        // Dashboard AJAX
        add_action('wp_ajax_res_get_dashboard_data', array($this, 'get_dashboard_data'));
        
        // CRUD operations for all modules
        $modules = array('client', 'agent', 'team', 'sale', 'position', 'commission', 'collection', 'developer', 'project');
        foreach ($modules as $module) {
            add_action('wp_ajax_res_save_' . $module, array($this, 'save_' . $module));
            add_action('wp_ajax_res_delete_' . $module, array($this, 'delete_' . $module));
            add_action('wp_ajax_res_get_' . $module, array($this, 'get_' . $module));
        }
        
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
    
    public function save_client() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $data = array(
            'first_name' => sanitize_text_field($_POST['first_name']),
            'surname' => sanitize_text_field($_POST['surname']),
            'middle_initial' => sanitize_text_field($_POST['middle_initial']),
            'email' => sanitize_email($_POST['email']),
            'contact_no' => sanitize_text_field($_POST['contact_no']),
            'civil_status_id' => intval($_POST['civil_status_id']),
            'gender_id' => intval($_POST['gender_id']),
            'date_of_birth' => sanitize_text_field($_POST['date_of_birth']),
            'tin' => sanitize_text_field($_POST['tin']),
            'citizenship' => sanitize_text_field($_POST['citizenship']),
            'employer_name' => sanitize_text_field($_POST['employer_name']),
            'occupation' => sanitize_text_field($_POST['occupation']),
            'employment_type_id' => intval($_POST['employment_type_id']),
            'unit_street_village' => sanitize_text_field($_POST['unit_street_village']),
            'barangay' => sanitize_text_field($_POST['barangay']),
            'city' => sanitize_text_field($_POST['city']),
            'province' => sanitize_text_field($_POST['province']),
            'zip_code' => sanitize_text_field($_POST['zip_code']),
            'source_of_sale_id' => intval($_POST['source_of_sale_id']),
            // Secondary client fields
            'secondary_client_type' => sanitize_text_field($_POST['secondary_client_type']),
            'secondary_first_name' => sanitize_text_field($_POST['secondary_first_name']),
            'secondary_middle_initial' => sanitize_text_field($_POST['secondary_middle_initial']),
            'secondary_surname' => sanitize_text_field($_POST['secondary_surname']),
            'secondary_date_of_birth' => sanitize_text_field($_POST['secondary_date_of_birth']),
            'secondary_tin' => sanitize_text_field($_POST['secondary_tin']),
            'secondary_gender_id' => intval($_POST['secondary_gender_id']),
            'secondary_citizenship' => sanitize_text_field($_POST['secondary_citizenship']),
            'secondary_email' => sanitize_email($_POST['secondary_email']),
            'secondary_contact_no' => sanitize_text_field($_POST['secondary_contact_no']),
            'secondary_employer_name' => sanitize_text_field($_POST['secondary_employer_name']),
            'secondary_occupation' => sanitize_text_field($_POST['secondary_occupation']),
            'secondary_unit_street_village' => sanitize_text_field($_POST['secondary_unit_street_village']),
            'secondary_barangay' => sanitize_text_field($_POST['secondary_barangay']),
            'secondary_city' => sanitize_text_field($_POST['secondary_city']),
            'secondary_province' => sanitize_text_field($_POST['secondary_province']),
            'secondary_zip_code' => sanitize_text_field($_POST['secondary_zip_code'])
        );
        
        if (isset($_POST['client_id']) && $_POST['client_id']) {
            $result = $wpdb->update($wpdb->prefix . 'res_clients', $data, array('client_id' => intval($_POST['client_id'])));
        } else {
            $result = $wpdb->insert($wpdb->prefix . 'res_clients', $data);
        }
        
        if ($result !== false) {
            wp_send_json_success('Client saved successfully');
        } else {
            wp_send_json_error('Failed to save client');
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
    
    // Similar methods for other modules (agents, teams, sales, etc.)
    public function save_agent() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $data = array(
            'agent_code' => sanitize_text_field($_POST['agent_code']),
            'agent_name' => sanitize_text_field($_POST['agent_name']),
            'date_hired' => sanitize_text_field($_POST['date_hired']),
            'status_id' => intval($_POST['status_id']),
            'position_code' => sanitize_text_field($_POST['position_code']),
            'team_id' => intval($_POST['team_id']),
            'commission_rate' => floatval($_POST['commission_rate'])
        );
        
        if (isset($_POST['agent_id']) && $_POST['agent_id']) {
            $result = $wpdb->update($wpdb->prefix . 'res_sales_agents', $data, array('agent_id' => intval($_POST['agent_id'])));
        } else {
            $result = $wpdb->insert($wpdb->prefix . 'res_sales_agents', $data);
        }
        
        wp_send_json_success($result !== false ? 'Agent saved successfully' : 'Failed to save agent');
    }
    
    public function delete_agent() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $agent_id = intval($_POST['agent_id']);
        $result = $wpdb->delete($wpdb->prefix . 'res_sales_agents', array('agent_id' => $agent_id));
        
        wp_send_json_success($result ? 'Agent deleted successfully' : 'Failed to delete agent');
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
    
    // Team methods
    public function save_team() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $data = array(
            'team_name' => sanitize_text_field($_POST['team_name']),
            'team_leader' => intval($_POST['team_leader']),
            'date_created' => sanitize_text_field($_POST['date_created'])
        );
        
        if (isset($_POST['team_id']) && $_POST['team_id']) {
            $result = $wpdb->update($wpdb->prefix . 'res_agent_teams', $data, array('team_id' => intval($_POST['team_id'])));
        } else {
            $result = $wpdb->insert($wpdb->prefix . 'res_agent_teams', $data);
        }
        
        wp_send_json_success($result !== false ? 'Team saved successfully' : 'Failed to save team');
    }
    
    public function delete_team() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $team_id = intval($_POST['team_id']);
        $result = $wpdb->delete($wpdb->prefix . 'res_agent_teams', array('team_id' => $team_id));
        
        wp_send_json_success($result ? 'Team deleted successfully' : 'Failed to delete team');
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
    
    // Sale methods
    public function save_sale() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $data = array(
            'client_id' => intval($_POST['client_id']),
            'reservation_date' => sanitize_text_field($_POST['reservation_date']),
            'agent_id' => intval($_POST['agent_id']),
            'status_id' => intval($_POST['status_id']),
            'payment_status_id' => intval($_POST['payment_status_id']),
            'document_status_id' => intval($_POST['document_status_id']),
            'project_id' => intval($_POST['project_id']),
            'block' => sanitize_text_field($_POST['block']),
            'lot' => sanitize_text_field($_POST['lot']),
            'unit' => sanitize_text_field($_POST['unit']),
            'net_tcp' => floatval($_POST['net_tcp']),
            'gross_tcp' => floatval($_POST['gross_tcp']),
            'downpayment_type' => sanitize_text_field($_POST['downpayment_type']),
            'downpayment_terms' => sanitize_text_field($_POST['downpayment_terms']),
            'financing_type_id' => intval($_POST['financing_type_id']),
            'year_to_pay' => intval($_POST['year_to_pay']),
            'license_status_id' => intval($_POST['license_status_id']),
            'remarks' => sanitize_textarea_field($_POST['remarks'])
        );
        
        if (isset($_POST['sale_id']) && $_POST['sale_id']) {
            $result = $wpdb->update($wpdb->prefix . 'res_residential_sales', $data, array('sale_id' => intval($_POST['sale_id'])));
        } else {
            $result = $wpdb->insert($wpdb->prefix . 'res_residential_sales', $data);
        }
        
        wp_send_json_success($result !== false ? 'Sale saved successfully' : 'Failed to save sale');
    }
    
    public function delete_sale() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $sale_id = intval($_POST['sale_id']);
        $result = $wpdb->delete($wpdb->prefix . 'res_residential_sales', array('sale_id' => $sale_id));
        
        wp_send_json_success($result ? 'Sale deleted successfully' : 'Failed to delete sale');
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
    
    // Additional AJAX handlers
    public function get_team_members() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $team_id = intval($_POST['team_id']);
        $members = $wpdb->get_results($wpdb->prepare("
            SELECT a.*, p.position 
            FROM {$wpdb->prefix}res_sales_agents a
            LEFT JOIN {$wpdb->prefix}res_agent_positions p ON a.position_code = p.position_code
            WHERE a.team_id = %d
        ", $team_id));
        
        wp_send_json_success($members);
    }
    
    // Report generation
    public function generate_report() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $report_type = sanitize_text_field($_POST['report_type']);
        $date_from = sanitize_text_field($_POST['date_from']);
        $date_to = sanitize_text_field($_POST['date_to']);
        
        $html = '';
        
        switch ($report_type) {
            case 'agent_sales':
                $results = $wpdb->get_results("
                    SELECT a.agent_name, 
                           SUM(s.net_tcp) as net_sales,
                           SUM(s.gross_tcp) as gross_sales,
                           COUNT(s.sale_id) as sale_count
                    FROM {$wpdb->prefix}res_sales_agents a
                    LEFT JOIN {$wpdb->prefix}res_residential_sales s ON a.agent_id = s.agent_id
                    WHERE s.status_id = 1
                    " . ($date_from ? " AND s.reservation_date >= '$date_from'" : "") . "
                    " . ($date_to ? " AND s.reservation_date <= '$date_to'" : "") . "
                    GROUP BY a.agent_id
                    ORDER BY net_sales DESC
                ");
                
                $html = '<table class="widefat"><thead><tr><th>Agent Name</th><th>Net Sales</th><th>Gross Sales</th><th>Sale Count</th></tr></thead><tbody>';
                foreach ($results as $row) {
                    $html .= '<tr>';
                    $html .= '<td>' . esc_html($row->agent_name) . '</td>';
                    $html .= '<td>₱' . number_format($row->net_sales, 2) . '</td>';
                    $html .= '<td>₱' . number_format($row->gross_sales, 2) . '</td>';
                    $html .= '<td>' . $row->sale_count . '</td>';
                    $html .= '</tr>';
                }
                $html .= '</tbody></table>';
                break;
                
            // Add more report types as needed
        }
        
        wp_send_json_success($html);
    }
    
    // Settings handlers
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
    
    // Position methods
    public function save_position() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $data = array(
            'position_no' => intval($_POST['position_no']),
            'position_code' => sanitize_text_field($_POST['position_code']),
            'position' => sanitize_text_field($_POST['position'])
        );
        
        if (isset($_POST['id']) && $_POST['id']) {
            $result = $wpdb->update($wpdb->prefix . 'res_agent_positions', $data, array('id' => intval($_POST['id'])));
        } else {
            $result = $wpdb->insert($wpdb->prefix . 'res_agent_positions', $data);
        }
        
        wp_send_json_success($result !== false ? 'Position saved successfully' : 'Failed to save position');
    }
    
    public function delete_position() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $id = intval($_POST['position_id']);
        $result = $wpdb->delete($wpdb->prefix . 'res_agent_positions', array('id' => $id));
        
        wp_send_json_success($result ? 'Position deleted successfully' : 'Failed to delete position');
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
    
    // Commission methods
    public function save_commission() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $data = array(
            'release_date' => sanitize_text_field($_POST['release_date']),
            'voucher_number' => sanitize_text_field($_POST['voucher_number']),
            'or_number' => sanitize_text_field($_POST['or_number']),
            'client_reference' => sanitize_text_field($_POST['client_reference']),
            'amount_collected' => floatval($_POST['amount_collected']),
            'payee_id' => intval($_POST['payee_id']),
            'agent_status_id' => intval($_POST['agent_status_id']),
            'commission_rate' => floatval($_POST['commission_rate']),
            'client_id' => intval($_POST['client_id']),
            'project_id' => intval($_POST['project_id']),
            'or_status_id' => intval($_POST['or_status_id']),
            'gross_amount' => floatval($_POST['gross_amount']),
            'inc' => floatval($_POST['inc']),
            'ewt' => floatval($_POST['ewt']),
            'net_pay' => floatval($_POST['net_pay']),
            'particulars' => sanitize_textarea_field($_POST['particulars']),
            'bizlink' => sanitize_text_field($_POST['bizlink']),
            'reference' => sanitize_text_field($_POST['reference']),
            'remarks' => sanitize_textarea_field($_POST['remarks'])
        );
        
        if (isset($_POST['release_id']) && $_POST['release_id']) {
            $result = $wpdb->update($wpdb->prefix . 'res_released_commissions', $data, array('release_id' => intval($_POST['release_id'])));
        } else {
            $result = $wpdb->insert($wpdb->prefix . 'res_released_commissions', $data);
        }
        
        wp_send_json_success($result !== false ? 'Commission saved successfully' : 'Failed to save commission');
    }
    
    public function delete_commission() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $release_id = intval($_POST['release_id']);
        $result = $wpdb->delete($wpdb->prefix . 'res_released_commissions', array('release_id' => $release_id));
        
        wp_send_json_success($result ? 'Commission deleted successfully' : 'Failed to delete commission');
    }
    
    public function get_commission() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $release_id = intval($_POST['release_id']);
        $commission = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}res_released_commissions WHERE release_id = %d", $release_id), ARRAY_A);
        
        if ($commission) {
            wp_send_json_success($commission);
        } else {
            wp_send_json_error('Commission not found');
        }
    }
    
    // Collection methods
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
            'block' => sanitize_text_field($_POST['block']),
            'lot' => sanitize_text_field($_POST['lot']),
            'gross_commission' => floatval($_POST['gross_commission']),
            'vat' => floatval($_POST['vat']),
            'ewt' => floatval($_POST['ewt']),
            'net_commission' => floatval($_POST['net_commission']),
            'particulars' => sanitize_textarea_field($_POST['particulars']),
            'cname_particulars' => sanitize_textarea_field($_POST['cname_particulars'])
        );
        
        if (isset($_POST['acct_collection_id']) && $_POST['acct_collection_id']) {
            $result = $wpdb->update($wpdb->prefix . 'res_account_collections', $data, array('acct_collection_id' => intval($_POST['acct_collection_id'])));
        } else {
            $result = $wpdb->insert($wpdb->prefix . 'res_account_collections', $data);
        }
        
        wp_send_json_success($result !== false ? 'Collection saved successfully' : 'Failed to save collection');
    }
    
    public function delete_collection() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $collection_id = intval($_POST['collection_id']);
        $result = $wpdb->delete($wpdb->prefix . 'res_account_collections', array('acct_collection_id' => $collection_id));
        
        wp_send_json_success($result ? 'Collection deleted successfully' : 'Failed to delete collection');
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
    
    // Developer methods
    public function save_developer() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $data = array(
            'developer_name' => sanitize_text_field($_POST['developer_name'])
        );
        
        if (isset($_POST['developer_id']) && $_POST['developer_id']) {
            $result = $wpdb->update($wpdb->prefix . 'res_developers', $data, array('developer_id' => intval($_POST['developer_id'])));
        } else {
            $result = $wpdb->insert($wpdb->prefix . 'res_developers', $data);
        }
        
        wp_send_json_success($result !== false ? 'Developer saved successfully' : 'Failed to save developer');
    }
    
    public function delete_developer() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $developer_id = intval($_POST['developer_id']);
        $result = $wpdb->delete($wpdb->prefix . 'res_developers', array('developer_id' => $developer_id));
        
        wp_send_json_success($result ? 'Developer deleted successfully' : 'Failed to delete developer');
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
    
    // Project methods
    public function save_project() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $data = array(
            'project_name' => sanitize_text_field($_POST['project_name']),
            'developer_id' => intval($_POST['developer_id']),
            'date_accredited' => sanitize_text_field($_POST['date_accredited'])
        );
        
        if (isset($_POST['project_id']) && $_POST['project_id']) {
            $result = $wpdb->update($wpdb->prefix . 'res_projects', $data, array('project_id' => intval($_POST['project_id'])));
        } else {
            $result = $wpdb->insert($wpdb->prefix . 'res_projects', $data);
        }
        
        wp_send_json_success($result !== false ? 'Project saved successfully' : 'Failed to save project');
    }
    
    public function delete_project() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $project_id = intval($_POST['project_id']);
        $result = $wpdb->delete($wpdb->prefix . 'res_projects', array('project_id' => $project_id));
        
        wp_send_json_success($result ? 'Project deleted successfully' : 'Failed to delete project');
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
    
    // Developer Collection methods
    public function save_developer_collection() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $data = array(
            'collection_date' => sanitize_text_field($_POST['collection_date']),
            'or_number' => sanitize_text_field($_POST['or_number']),
            'payor' => sanitize_text_field($_POST['payor']),
            'project_id' => intval($_POST['project_id']),
            'payee' => sanitize_text_field($_POST['payee']),
            'particulars' => sanitize_textarea_field($_POST['particulars']),
            'gross_amount' => floatval($_POST['gross_amount']),
            'vat' => floatval($_POST['vat']),
            'ewt' => floatval($_POST['ewt']),
            'net_collected_amount' => floatval($_POST['net_collected_amount']),
            'form_2307_status_id' => intval($_POST['form_2307_status_id']),
            'deposit_date' => sanitize_text_field($_POST['deposit_date']),
            'account_deposited' => sanitize_text_field($_POST['account_deposited']),
            'reference' => sanitize_text_field($_POST['reference']),
            'remarks' => sanitize_textarea_field($_POST['remarks'])
        );
        
        if (isset($_POST['dev_collection_id']) && $_POST['dev_collection_id']) {
            $result = $wpdb->update($wpdb->prefix . 'res_developer_collections', $data, array('dev_collection_id' => intval($_POST['dev_collection_id'])));
        } else {
            $result = $wpdb->insert($wpdb->prefix . 'res_developer_collections', $data);
        }
        
        wp_send_json_success($result !== false ? 'Developer collection saved successfully' : 'Failed to save developer collection');
    }
    
    public function delete_developer_collection() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $collection_id = intval($_POST['collection_id']);
        $result = $wpdb->delete($wpdb->prefix . 'res_developer_collections', array('dev_collection_id' => $collection_id));
        
        wp_send_json_success($result ? 'Developer collection deleted successfully' : 'Failed to delete developer collection');
    }
    
    public function get_developer_collection() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $collection_id = intval($_POST['collection_id']);
        $collection = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}res_developer_collections WHERE dev_collection_id = %d", $collection_id), ARRAY_A);
        
        if ($collection) {
            wp_send_json_success($collection);
        } else {
            wp_send_json_error('Developer collection not found');
        }
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
    
    public function get_project_sales() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        global $wpdb;
        
        $project_id = intval($_POST['project_id']);
        $sales = $wpdb->get_results($wpdb->prepare("
            SELECT s.*, 
                   CONCAT(c.first_name, ' ', c.surname) as client_name,
                   a.agent_name,
                   st.status
            FROM {$wpdb->prefix}res_residential_sales s
            LEFT JOIN {$wpdb->prefix}res_clients c ON s.client_id = c.client_id
            LEFT JOIN {$wpdb->prefix}res_sales_agents a ON s.agent_id = a.agent_id
            LEFT JOIN {$wpdb->prefix}res_ref_status st ON s.status_id = st.id
            WHERE s.project_id = %d
            ORDER BY s.reservation_date DESC
        ", $project_id));
        
        wp_send_json_success($sales);
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