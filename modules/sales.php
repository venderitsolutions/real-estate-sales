<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

// Get reference data
$clients = $wpdb->get_results("SELECT client_id, CONCAT(first_name, ' ', surname) as name FROM {$wpdb->prefix}res_clients ORDER BY first_name");
$agents = $wpdb->get_results("SELECT agent_id, agent_name FROM {$wpdb->prefix}res_sales_agents WHERE status_id = 1 ORDER BY agent_name");
$projects = $wpdb->get_results("SELECT project_id, project_name FROM {$wpdb->prefix}res_projects ORDER BY project_name");
$statuses = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}res_ref_status");
$payment_statuses = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}res_ref_payment_status");
$document_statuses = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}res_ref_document_status");
$financing_types = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}res_ref_employment_type"); // Using as financing type
$license_statuses = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}res_ref_license_status");

// Get sales list
$sales = $wpdb->get_results("
    SELECT s.*, 
           CONCAT(c.first_name, ' ', c.surname) as client_name,
           a.agent_name,
           p.project_name,
           st.status,
           ps.payment_status,
           ds.document_status
    FROM {$wpdb->prefix}res_residential_sales s
    LEFT JOIN {$wpdb->prefix}res_clients c ON s.client_id = c.client_id
    LEFT JOIN {$wpdb->prefix}res_sales_agents a ON s.agent_id = a.agent_id
    LEFT JOIN {$wpdb->prefix}res_projects p ON s.project_id = p.project_id
    LEFT JOIN {$wpdb->prefix}res_ref_status st ON s.status_id = st.id
    LEFT JOIN {$wpdb->prefix}res_ref_payment_status ps ON s.payment_status_id = ps.id
    LEFT JOIN {$wpdb->prefix}res_ref_document_status ds ON s.document_status_id = ds.id
    ORDER BY s.reservation_date DESC
");
?>

<div class="wrap">
    <h1>Residential Sales <a href="#" class="page-title-action" id="add-new-sale">Add New</a></h1>
    
    <!-- Sale Form -->
    <div id="sale-form" style="display:none;">
        <h2 id="form-title">Add New Sale</h2>
        <form id="res-sale-form">
            <input type="hidden" id="sale_id" name="sale_id" value="">
            
            <div class="res-form-section">
                <h3>Client & Agent Information</h3>
                <div class="res-form-grid">
                    <div class="res-form-group">
                        <label>Client*</label>
                        <select name="client_id" required>
                            <option value="">Select Client...</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?php echo $client->client_id; ?>"><?php echo esc_html($client->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="res-form-group">
                        <label>Sales Agent*</label>
                        <select name="agent_id" required>
                            <option value="">Select Agent...</option>
                            <?php foreach ($agents as $agent): ?>
                                <option value="<?php echo $agent->agent_id; ?>"><?php echo esc_html($agent->agent_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="res-form-group">
                        <label>Reservation Date*</label>
                        <input type="date" name="reservation_date" required>
                    </div>
                </div>
            </div>
            
            <div class="res-form-section">
                <h3>Property Details</h3>
                <div class="res-form-grid">
                    <div class="res-form-group">
                        <label>Project*</label>
                        <select name="project_id" required>
                            <option value="">Select Project...</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?php echo $project->project_id; ?>"><?php echo esc_html($project->project_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="res-form-group">
                        <label>Block</label>
                        <input type="text" name="block">
                    </div>
                    <div class="res-form-group">
                        <label>Lot</label>
                        <input type="text" name="lot">
                    </div>
                    <div class="res-form-group">
                        <label>Unit</label>
                        <input type="text" name="unit">
                    </div>
                </div>
            </div>
            
            <div class="res-form-section">
                <h3>Financial Details</h3>
                <div class="res-form-grid">
                    <div class="res-form-group">
                        <label>Net TCP*</label>
                        <input type="number" name="net_tcp" step="0.01" required>
                    </div>
                    <div class="res-form-group">
                        <label>Gross TCP*</label>
                        <input type="number" name="gross_tcp" step="0.01" required>
                    </div>
                    <div class="res-form-group">
                        <label>Downpayment Type</label>
                        <input type="text" name="downpayment_type">
                    </div>
                    <div class="res-form-group">
                        <label>Downpayment Terms</label>
                        <input type="text" name="downpayment_terms">
                    </div>
                    <div class="res-form-group">
                        <label>Financing Type</label>
                        <select name="financing_type_id">
                            <option value="">Select...</option>
                            <?php foreach ($financing_types as $ft): ?>
                                <option value="<?php echo $ft->id; ?>"><?php echo esc_html($ft->employment_type); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="res-form-group">
                        <label>Years to Pay</label>
                        <input type="number" name="year_to_pay" min="1">
                    </div>
                </div>
            </div>
            
            <div class="res-form-section">
                <h3>Status Information</h3>
                <div class="res-form-grid">
                    <div class="res-form-group">
                        <label>Sale Status</label>
                        <select name="status_id">
                            <option value="">Select...</option>
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?php echo $status->id; ?>"><?php echo esc_html($status->status); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="res-form-group">
                        <label>Payment Status</label>
                        <select name="payment_status_id">
                            <option value="">Select...</option>
                            <?php foreach ($payment_statuses as $ps): ?>
                                <option value="<?php echo $ps->id; ?>"><?php echo esc_html($ps->payment_status); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="res-form-group">
                        <label>Document Status</label>
                        <select name="document_status_id">
                            <option value="">Select...</option>
                            <?php foreach ($document_statuses as $ds): ?>
                                <option value="<?php echo $ds->id; ?>"><?php echo esc_html($ds->document_status); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="res-form-group">
                        <label>License Status</label>
                        <select name="license_status_id">
                            <option value="">Select...</option>
                            <?php foreach ($license_statuses as $ls): ?>
                                <option value="<?php echo $ls->id; ?>"><?php echo esc_html($ls->license_status); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="res-form-section">
                <h3>Remarks</h3>
                <div class="res-form-group full-width">
                    <textarea name="remarks" rows="4"></textarea>
                </div>
            </div>
            
            <div class="res-form-actions">
                <button type="submit" class="button button-primary">Save Sale</button>
                <button type="button" class="button" id="cancel-form">Cancel</button>
            </div>
        </form>
    </div>
    
    <!-- Sales List -->
    <div id="sales-list">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Agent</th>
                    <th>Project</th>
                    <th>Unit/Block/Lot</th>
                    <th>Net TCP</th>
                    <th>Gross TCP</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sales as $sale): ?>
                <tr>
                    <td><?php echo $sale->sale_id; ?></td>
                    <td><?php echo $sale->reservation_date; ?></td>
                    <td><?php echo esc_html($sale->client_name); ?></td>
                    <td><?php echo esc_html($sale->agent_name); ?></td>
                    <td><?php echo esc_html($sale->project_name); ?></td>
                    <td><?php echo esc_html(trim($sale->unit . ' ' . $sale->block . ' ' . $sale->lot)); ?></td>
                    <td>₱<?php echo number_format($sale->net_tcp, 2); ?></td>
                    <td>₱<?php echo number_format($sale->gross_tcp, 2); ?></td>
                    <td>
                        <span class="status-badge status-<?php echo strtolower($sale->status); ?>">
                            <?php echo esc_html($sale->status); ?>
                        </span>
                    </td>
                    <td>
                        <a href="#" class="edit-sale" data-id="<?php echo $sale->sale_id; ?>">Edit</a> |
                        <a href="#" class="delete-sale" data-id="<?php echo $sale->sale_id; ?>">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#add-new-sale').click(function(e) {
        e.preventDefault();
        $('#form-title').text('Add New Sale');
        $('#res-sale-form')[0].reset();
        $('#sale_id').val('');
        $('#sale-form').slideDown();
        $('#sales-list').slideUp();
    });
    
    $('#cancel-form').click(function() {
        $('#sale-form').slideUp();
        $('#sales-list').slideDown();
    });
    
    $('#res-sale-form').submit(function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        formData += '&action=res_save_sale&nonce=' + res_ajax.nonce;
        
        $.post(res_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                alert(response.data);
                location.reload();
            }
        });
    });
    
    $('.edit-sale').click(function(e) {
        e.preventDefault();
        var saleId = $(this).data('id');
        
        $.post(res_ajax.ajax_url, {
            action: 'res_get_sale',
            sale_id: saleId,
            nonce: res_ajax.nonce
        }, function(response) {
            if (response.success) {
                var sale = response.data;
                $('#form-title').text('Edit Sale');
                $('#sale_id').val(sale.sale_id);
                
                $.each(sale, function(key, value) {
                    $('[name="' + key + '"]').val(value);
                });
                
                $('#sale-form').slideDown();
                $('#sales-list').slideUp();
            }
        });
    });
    
    $('.delete-sale').click(function(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to delete this sale?')) return;
        
        var saleId = $(this).data('id');
        $.post(res_ajax.ajax_url, {
            action: 'res_delete_sale',
            sale_id: saleId,
            nonce: res_ajax.nonce
        }, function(response) {
            if (response.success) {
                alert(response.data);
                location.reload();
            }
        });
    });
});
</script>