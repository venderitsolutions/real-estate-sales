<?php
class RES_Frontend_Dashboard {
    
    public function __construct() {
        add_shortcode('res_agent_dashboard', array($this, 'render_dashboard'));
        add_action('wp_ajax_res_frontend_save_client', array($this, 'save_client'));
        add_action('wp_ajax_res_frontend_save_sale', array($this, 'save_sale'));
        add_action('wp_ajax_res_frontend_get_client', array($this, 'get_client'));
        add_action('wp_ajax_res_frontend_get_sale', array($this, 'get_sale'));
        add_action('wp_ajax_res_frontend_delete_client', array($this, 'delete_client'));
        add_action('wp_ajax_res_frontend_delete_sale', array($this, 'delete_sale'));
        add_action('wp_ajax_res_frontend_dashboard_data', array($this, 'get_dashboard_data'));
    }
    
    public function render_dashboard($atts) {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<p>Please log in to access your agent dashboard.</p>';
        }
        
        // Get current agent
        $agent = $this->get_current_agent();
        if (!$agent) {
            return '<p>You are not registered as an agent. Please contact the administrator.</p>';
        }
        
        ob_start();
        ?>
        <div id="res-agent-dashboard" class="res-frontend-dashboard">
            <!-- Dashboard Navigation -->
            <nav class="res-dashboard-nav">
                <ul>
                    <li><a href="#dashboard" class="nav-link active" data-tab="dashboard">Dashboard</a></li>
                    <li><a href="#clients" class="nav-link" data-tab="clients">My Clients</a></li>
                    <li><a href="#sales" class="nav-link" data-tab="sales">My Sales</a></li>
                    <li><a href="#commissions" class="nav-link" data-tab="commissions">Commissions</a></li>
                </ul>
            </nav>
            
            <!-- Dashboard Content -->
            <div class="res-dashboard-content">
                <!-- Dashboard Overview -->
                <div id="dashboard-tab" class="tab-content active">
                    <h2>Welcome, <?php echo esc_html($agent->agent_name); ?>!</h2>
                    
                    <div class="dashboard-stats">
                        <div class="stat-card">
                            <h3>Total Clients</h3>
                            <span class="stat-number" id="total-clients">0</span>
                        </div>
                        <div class="stat-card">
                            <h3>Total Sales</h3>
                            <span class="stat-number" id="total-sales">0</span>
                        </div>
                        <div class="stat-card">
                            <h3>Active Sales</h3>
                            <span class="stat-number" id="active-sales">0</span>
                        </div>
                        <div class="stat-card">
                            <h3>Total Commissions</h3>
                            <span class="stat-amount" id="total-commissions">₱0.00</span>
                        </div>
                    </div>
                    
                    <div class="recent-activities">
                        <h3>Recent Activities</h3>
                        <div id="recent-sales-list"></div>
                    </div>
                </div>
                
                <!-- Clients Tab -->
                <div id="clients-tab" class="tab-content">
                    <div class="tab-header">
                        <h2>My Clients</h2>
                        <button class="btn btn-primary" id="add-client-btn">Add New Client</button>
                    </div>
                    
                    <!-- Client Form -->
                    <div id="client-form" class="form-section" style="display:none;">
                        <h3 id="client-form-title">Add New Client</h3>
                        <form id="frontend-client-form">
                            <input type="hidden" id="client_id" name="client_id" value="">
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>First Name*</label>
                                    <input type="text" name="first_name" required>
                                </div>
                                <div class="form-group">
                                    <label>Middle Initial</label>
                                    <input type="text" name="middle_initial" maxlength="10">
                                </div>
                                <div class="form-group">
                                    <label>Surname*</label>
                                    <input type="text" name="surname" required>
                                </div>
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email">
                                </div>
                                <div class="form-group">
                                    <label>Contact Number</label>
                                    <input type="text" name="contact_no">
                                </div>
                                <div class="form-group">
                                    <label>Date of Birth</label>
                                    <input type="date" name="date_of_birth">
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Save Client</button>
                                <button type="button" class="btn btn-secondary" id="cancel-client-form">Cancel</button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Clients List -->
                    <div id="clients-list">
                        <table class="res-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Contact</th>
                                    <th>Date Added</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="clients-tbody">
                                <!-- Populated via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Sales Tab -->
                <div id="sales-tab" class="tab-content">
                    <div class="tab-header">
                        <h2>My Sales</h2>
                        <button class="btn btn-primary" id="add-sale-btn">Add New Sale</button>
                    </div>
                    
                    <!-- Sale Form -->
                    <div id="sale-form" class="form-section" style="display:none;">
                        <h3 id="sale-form-title">Add New Sale</h3>
                        <form id="frontend-sale-form">
                            <input type="hidden" id="sale_id" name="sale_id" value="">
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Client*</label>
                                    <select name="client_id" id="sale-client-select" required>
                                        <option value="">Select Client...</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Project*</label>
                                    <select name="project_id" required>
                                        <option value="">Select Project...</option>
                                        <?php echo $this->get_projects_options(); ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Reservation Date*</label>
                                    <input type="date" name="reservation_date" required>
                                </div>
                                <div class="form-group">
                                    <label>Block</label>
                                    <input type="text" name="block">
                                </div>
                                <div class="form-group">
                                    <label>Lot</label>
                                    <input type="text" name="lot">
                                </div>
                                <div class="form-group">
                                    <label>Unit</label>
                                    <input type="text" name="unit">
                                </div>
                                <div class="form-group">
                                    <label>Net TCP*</label>
                                    <input type="number" name="net_tcp" step="0.01" required>
                                </div>
                                <div class="form-group">
                                    <label>Gross TCP*</label>
                                    <input type="number" name="gross_tcp" step="0.01" required>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Save Sale</button>
                                <button type="button" class="btn btn-secondary" id="cancel-sale-form">Cancel</button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Sales List -->
                    <div id="sales-list">
                        <table class="res-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Client</th>
                                    <th>Project</th>
                                    <th>Unit/Block/Lot</th>
                                    <th>Net TCP</th>
                                    <th>Gross TCP</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="sales-tbody">
                                <!-- Populated via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Commissions Tab -->
                <div id="commissions-tab" class="tab-content">
                    <h2>My Commissions</h2>
                    <div id="commissions-list">
                        <table class="res-table">
                            <thead>
                                <tr>
                                    <th>Voucher #</th>
                                    <th>Date</th>
                                    <th>Gross Amount</th>
                                    <th>Net Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="commissions-tbody">
                                <!-- Populated via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Load dashboard data
            loadDashboardData();
            loadClients();
            loadSales();
            loadCommissions();
            
            // Tab navigation
            $('.nav-link').click(function(e) {
                e.preventDefault();
                var tab = $(this).data('tab');
                
                $('.nav-link').removeClass('active');
                $(this).addClass('active');
                
                $('.tab-content').removeClass('active');
                $('#' + tab + '-tab').addClass('active');
            });
            
            // Client form handling
            $('#add-client-btn').click(function() {
                $('#client-form-title').text('Add New Client');
                $('#frontend-client-form')[0].reset();
                $('#client_id').val('');
                $('#client-form').slideDown();
            });
            
            $('#cancel-client-form').click(function() {
                $('#client-form').slideUp();
            });
            
            $('#frontend-client-form').submit(function(e) {
                e.preventDefault();
                saveClient();
            });
            
            // Sale form handling
            $('#add-sale-btn').click(function() {
                loadClientsForSale();
                $('#sale-form-title').text('Add New Sale');
                $('#frontend-sale-form')[0].reset();
                $('#sale_id').val('');
                $('#sale-form').slideDown();
            });
            
            $('#cancel-sale-form').click(function() {
                $('#sale-form').slideUp();
            });
            
            $('#frontend-sale-form').submit(function(e) {
                e.preventDefault();
                saveSale();
            });
            
            function loadDashboardData() {
                $.post(res_frontend_ajax.ajax_url, {
                    action: 'res_frontend_dashboard_data',
                    nonce: res_frontend_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        $('#total-clients').text(response.data.total_clients);
                        $('#total-sales').text(response.data.total_sales);
                        $('#active-sales').text(response.data.active_sales);
                        $('#total-commissions').text('₱' + response.data.total_commissions);
                        
                        // Load recent sales
                        var recentHtml = '';
                        if (response.data.recent_sales.length > 0) {
                            recentHtml = '<ul>';
                            response.data.recent_sales.forEach(function(sale) {
                                recentHtml += '<li>' + sale.client_name + ' - ' + sale.project_name + ' (₱' + parseFloat(sale.net_tcp).toLocaleString() + ')</li>';
                            });
                            recentHtml += '</ul>';
                        } else {
                            recentHtml = '<p>No recent sales found.</p>';
                        }
                        $('#recent-sales-list').html(recentHtml);
                    }
                });
            }
            
            function loadClients() {
                $.post(res_frontend_ajax.ajax_url, {
                    action: 'res_frontend_get_clients',
                    nonce: res_frontend_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        var html = '';
                        response.data.forEach(function(client) {
                            html += '<tr>';
                            html += '<td>' + client.first_name + ' ' + (client.middle_initial || '') + ' ' + client.surname + '</td>';
                            html += '<td>' + (client.email || '') + '</td>';
                            html += '<td>' + (client.contact_no || '') + '</td>';
                            html += '<td>' + new Date(client.created_at).toLocaleDateString() + '</td>';
                            html += '<td>';
                            html += '<button class="btn btn-sm btn-secondary edit-client" data-id="' + client.client_id + '">Edit</button> ';
                            html += '<button class="btn btn-sm btn-danger delete-client" data-id="' + client.client_id + '">Delete</button>';
                            html += '</td>';
                            html += '</tr>';
                        });
                        $('#clients-tbody').html(html);
                    }
                });
            }
            
            function loadSales() {
                $.post(res_frontend_ajax.ajax_url, {
                    action: 'res_frontend_get_sales',
                    nonce: res_frontend_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        var html = '';
                        response.data.forEach(function(sale) {
                            html += '<tr>';
                            html += '<td>' + sale.reservation_date + '</td>';
                            html += '<td>' + sale.client_name + '</td>';
                            html += '<td>' + sale.project_name + '</td>';
                            html += '<td>' + (sale.unit || '') + ' ' + (sale.block || '') + ' ' + (sale.lot || '') + '</td>';
                            html += '<td>₱' + parseFloat(sale.net_tcp).toLocaleString() + '</td>';
                            html += '<td>₱' + parseFloat(sale.gross_tcp).toLocaleString() + '</td>';
                            html += '<td>';
                            html += '<button class="btn btn-sm btn-secondary edit-sale" data-id="' + sale.sale_id + '">Edit</button> ';
                            html += '<button class="btn btn-sm btn-danger delete-sale" data-id="' + sale.sale_id + '">Delete</button>';
                            html += '</td>';
                            html += '</tr>';
                        });
                        $('#sales-tbody').html(html);
                    }
                });
            }
            
            function loadCommissions() {
                $.post(res_frontend_ajax.ajax_url, {
                    action: 'res_frontend_get_commissions',
                    nonce: res_frontend_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        var html = '';
                        response.data.forEach(function(commission) {
                            html += '<tr>';
                            html += '<td>' + commission.voucher_number + '</td>';
                            html += '<td>' + commission.voucher_date + '</td>';
                            html += '<td>₱' + parseFloat(commission.total_gross_amount).toLocaleString() + '</td>';
                            html += '<td>₱' + parseFloat(commission.total_net_amount).toLocaleString() + '</td>';
                            html += '<td>' + commission.status + '</td>';
                            html += '<td>';
                            html += '<button class="btn btn-sm btn-primary download-voucher" data-id="' + commission.voucher_id + '">Download</button>';
                            html += '</td>';
                            html += '</tr>';
                        });
                        $('#commissions-tbody').html(html);
                    }
                });
            }
            
            function loadClientsForSale() {
                $.post(res_frontend_ajax.ajax_url, {
                    action: 'res_frontend_get_clients',
                    nonce: res_frontend_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        var options = '<option value="">Select Client...</option>';
                        response.data.forEach(function(client) {
                            options += '<option value="' + client.client_id + '">' + client.first_name + ' ' + client.surname + '</option>';
                        });
                        $('#sale-client-select').html(options);
                    }
                });
            }
            
            function saveClient() {
                var formData = $('#frontend-client-form').serialize();
                formData += '&action=res_frontend_save_client&nonce=' + res_frontend_ajax.nonce;
                
                $.post(res_frontend_ajax.ajax_url, formData, function(response) {
                    if (response.success) {
                        alert('Client saved successfully');
                        $('#client-form').slideUp();
                        loadClients();
                        loadDashboardData();
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            }
            
            function saveSale() {
                var formData = $('#frontend-sale-form').serialize();
                formData += '&action=res_frontend_save_sale&nonce=' + res_frontend_ajax.nonce;
                
                $.post(res_frontend_ajax.ajax_url, formData, function(response) {
                    if (response.success) {
                        alert('Sale saved successfully');
                        $('#sale-form').slideUp();
                        loadSales();
                        loadDashboardData();
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            }
            
            // Edit client
            $(document).on('click', '.edit-client', function() {
                var clientId = $(this).data('id');
                $.post(res_frontend_ajax.ajax_url, {
                    action: 'res_frontend_get_client',
                    client_id: clientId,
                    nonce: res_frontend_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        var client = response.data;
                        $('#client-form-title').text('Edit Client');
                        $('#client_id').val(client.client_id);
                        
                        $.each(client, function(key, value) {
                            $('[name="' + key + '"]').val(value);
                        });
                        
                        $('#client-form').slideDown();
                    }
                });
            });
            
            // Delete client
            $(document).on('click', '.delete-client', function() {
                if (!confirm('Are you sure you want to delete this client?')) return;
                
                var clientId = $(this).data('id');
                $.post(res_frontend_ajax.ajax_url, {
                    action: 'res_frontend_delete_client',
                    client_id: clientId,
                    nonce: res_frontend_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        alert('Client deleted successfully');
                        loadClients();
                        loadDashboardData();
                    }
                });
            });
            
            // Edit sale
            $(document).on('click', '.edit-sale', function() {
                var saleId = $(this).data('id');
                loadClientsForSale();
                
                $.post(res_frontend_ajax.ajax_url, {
                    action: 'res_frontend_get_sale',
                    sale_id: saleId,
                    nonce: res_frontend_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        var sale = response.data;
                        $('#sale-form-title').text('Edit Sale');
                        $('#sale_id').val(sale.sale_id);
                        
                        $.each(sale, function(key, value) {
                            $('[name="' + key + '"]').val(value);
                        });
                        
                        $('#sale-form').slideDown();
                    }
                });
            });
            
            // Delete sale
            $(document).on('click', '.delete-sale', function() {
                if (!confirm('Are you sure you want to delete this sale?')) return;
                
                var saleId = $(this).data('id');
                $.post(res_frontend_ajax.ajax_url, {
                    action: 'res_frontend_delete_sale',
                    sale_id: saleId,
                    nonce: res_frontend_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        alert('Sale deleted successfully');
                        loadSales();
                        loadDashboardData();
                    }
                });
            });
            
            // Download voucher
            $(document).on('click', '.download-voucher', function() {
                var voucherId = $(this).data('id');
                window.open(res_frontend_ajax.ajax_url + '?action=res_download_voucher&voucher_id=' + voucherId + '&nonce=' + res_frontend_ajax.nonce, '_blank');
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    private function get_current_agent() {
        global $wpdb;
        $user_id = get_current_user_id();
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}res_sales_agents WHERE wp_user_id = %d",
            $user_id
        ));
    }
    
    private function get_projects_options() {
        global $wpdb;
        $projects = $wpdb->get_results("SELECT project_id, project_name FROM {$wpdb->prefix}res_projects ORDER BY project_name");
        
        $options = '';
        foreach ($projects as $project) {
            $options .= '<option value="' . $project->project_id . '">' . esc_html($project->project_name) . '</option>';
        }
        
        return $options;
    }
    
    // AJAX Handlers
    public function get_dashboard_data() {
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
    
    public function save_client() {
        check_ajax_referer('res_frontend_nonce', 'nonce');
        
        $agent = $this->get_current_agent();
        if (!$agent) {
            wp_send_json_error('Agent not found');
        }
        
        global $wpdb;
        
        $data = array(
            'first_name' => sanitize_text_field($_POST['first_name']),
            'middle_initial' => sanitize_text_field($_POST['middle_initial']),
            'surname' => sanitize_text_field($_POST['surname']),
            'email' => sanitize_email($_POST['email']),
            'contact_no' => sanitize_text_field($_POST['contact_no']),
            'date_of_birth' => sanitize_text_field($_POST['date_of_birth']),
            'created_by_agent_id' => $agent->agent_id
        );
        
        if (isset($_POST['client_id']) && $_POST['client_id']) {
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
            wp_send_json_error('Failed to save client');
        }
    }
    
    public function save_sale() {
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
            'block' => sanitize_text_field($_POST['block']),
            'lot' => sanitize_text_field($_POST['lot']),
            'unit' => sanitize_text_field($_POST['unit']),
            'net_tcp' => floatval($_POST['net_tcp']),
            'gross_tcp' => floatval($_POST['gross_tcp']),
            'status_id' => 1 // Default to Active
        );
        
        if (isset($_POST['sale_id']) && $_POST['sale_id']) {
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
            wp_send_json_error('Failed to save sale');
        }
    }
    
    // Additional AJAX methods for get, delete operations...
    public function get_client() {
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
    
    public function delete_client() {
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
}