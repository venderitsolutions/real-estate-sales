<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

// Get reference data
$agents = $wpdb->get_results("SELECT agent_id, agent_name FROM {$wpdb->prefix}res_sales_agents ORDER BY agent_name");
$clients = $wpdb->get_results("SELECT client_id, CONCAT(first_name, ' ', surname) as name FROM {$wpdb->prefix}res_clients ORDER BY first_name");
$projects = $wpdb->get_results("SELECT project_id, project_name FROM {$wpdb->prefix}res_projects ORDER BY project_name");
$agent_statuses = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}res_ref_agent_status");
$or_statuses = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}res_ref_or_status");

// Get released commissions
$commissions = $wpdb->get_results("
    SELECT rc.*, 
           a.agent_name,
           CONCAT(c.first_name, ' ', c.surname) as client_name,
           p.project_name,
           ast.agent_status,
           ors.or_status
    FROM {$wpdb->prefix}res_released_commissions rc
    LEFT JOIN {$wpdb->prefix}res_sales_agents a ON rc.payee_id = a.agent_id
    LEFT JOIN {$wpdb->prefix}res_clients c ON rc.client_id = c.client_id
    LEFT JOIN {$wpdb->prefix}res_projects p ON rc.project_id = p.project_id
    LEFT JOIN {$wpdb->prefix}res_ref_agent_status ast ON rc.agent_status_id = ast.id
    LEFT JOIN {$wpdb->prefix}res_ref_or_status ors ON rc.or_status_id = ors.id
    ORDER BY rc.release_date DESC
");

// Calculate totals
$total_gross = $wpdb->get_var("SELECT SUM(gross_amount) FROM {$wpdb->prefix}res_released_commissions");
$total_net = $wpdb->get_var("SELECT SUM(net_pay) FROM {$wpdb->prefix}res_released_commissions");
$total_ewt = $wpdb->get_var("SELECT SUM(ewt) FROM {$wpdb->prefix}res_released_commissions");
?>

<div class="wrap">
    <h1>Released Commissions <a href="#" class="page-title-action" id="add-new-commission">Add New</a></h1>
    
    <!-- Summary Cards -->
    <div class="res-summary-cards">
        <div class="summary-card">
            <h3>Total Gross Commissions</h3>
            <p class="amount">₱<?php echo number_format($total_gross ?: 0, 2); ?></p>
        </div>
        <div class="summary-card">
            <h3>Total Net Commissions</h3>
            <p class="amount">₱<?php echo number_format($total_net ?: 0, 2); ?></p>
        </div>
        <div class="summary-card">
            <h3>Total EWT</h3>
            <p class="amount">₱<?php echo number_format($total_ewt ?: 0, 2); ?></p>
        </div>
    </div>
    
    <!-- Commission Form -->
    <div id="commission-form" style="display:none;">
        <h2 id="form-title">Add New Commission</h2>
        <form id="res-commission-form">
            <input type="hidden" id="release_id" name="release_id" value="">
            
            <div class="res-form-section">
                <h3>Agent & Client Information</h3>
                <div class="res-form-grid">
                    <div class="res-form-group">
                        <label>Payee (Agent)*</label>
                        <select name="payee_id" required>
                            <option value="">Select Agent...</option>
                            <?php foreach ($agents as $agent): ?>
                                <option value="<?php echo $agent->agent_id; ?>"><?php echo esc_html($agent->agent_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="res-form-group">
                        <label>Agent Status</label>
                        <select name="agent_status_id">
                            <option value="">Select...</option>
                            <?php foreach ($agent_statuses as $status): ?>
                                <option value="<?php echo $status->id; ?>"><?php echo esc_html($status->agent_status); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="res-form-group">
                        <label>Client</label>
                        <select name="client_id">
                            <option value="">Select Client...</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?php echo $client->client_id; ?>"><?php echo esc_html($client->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="res-form-group">
                        <label>Project</label>
                        <select name="project_id">
                            <option value="">Select Project...</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?php echo $project->project_id; ?>"><?php echo esc_html($project->project_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="res-form-section">
                <h3>Commission Details</h3>
                <div class="res-form-grid">
                    <div class="res-form-group">
                        <label>Client Reference</label>
                        <input type="text" name="client_reference">
                    </div>
                    <div class="res-form-group">
                        <label>Commission Rate (%)</label>
                        <input type="number" name="commission_rate" step="0.01" min="0" max="100">
                    </div>
                    <div class="res-form-group">
                        <label>Amount Collected</label>
                        <input type="number" name="amount_collected" step="0.01">
                    </div>
                    <div class="res-form-group">
                        <label>Gross Amount*</label>
                        <input type="number" name="gross_amount" step="0.01" required>
                    </div>
                    <div class="res-form-group">
                        <label>INC</label>
                        <input type="number" name="inc" step="0.01">
                    </div>
                    <div class="res-form-group">
                        <label>EWT</label>
                        <input type="number" name="ewt" step="0.01">
                    </div>
                    <div class="res-form-group">
                        <label>Net Pay*</label>
                        <input type="number" name="net_pay" step="0.01" required>
                    </div>
                </div>
            </div>
            
            <div class="res-form-section">
                <h3>Additional Information</h3>
                <div class="res-form-grid">
                    <div class="res-form-group full-width">
                        <label>Particulars</label>
                        <textarea name="particulars" rows="3"></textarea>
                    </div>
                    <div class="res-form-group">
                        <label>Bizlink</label>
                        <input type="text" name="bizlink">
                    </div>
                    <div class="res-form-group">
                        <label>Reference</label>
                        <input type="text" name="reference">
                    </div>
                    <div class="res-form-group full-width">
                        <label>Remarks</label>
                        <textarea name="remarks" rows="3"></textarea>
                    </div>
                </div>
            </div>
            
            <div class="res-form-actions">
                <button type="submit" class="button button-primary">Save Commission</button>
                <button type="button" class="button" id="cancel-form">Cancel</button>
            </div>
        </form>
    </div>
    
    <!-- Commissions List -->
    <div id="commissions-list">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Voucher #</th>
                    <th>Agent</th>
                    <th>Client</th>
                    <th>Project</th>
                    <th>Gross</th>
                    <th>EWT</th>
                    <th>Net Pay</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($commissions as $commission): ?>
                <tr>
                    <td><?php echo $commission->release_date; ?></td>
                    <td><?php echo esc_html($commission->voucher_number); ?></td>
                    <td><?php echo esc_html($commission->agent_name); ?></td>
                    <td><?php echo esc_html($commission->client_name); ?></td>
                    <td><?php echo esc_html($commission->project_name); ?></td>
                    <td>₱<?php echo number_format($commission->gross_amount, 2); ?></td>
                    <td>₱<?php echo number_format($commission->ewt, 2); ?></td>
                    <td>₱<?php echo number_format($commission->net_pay, 2); ?></td>
                    <td><?php echo esc_html($commission->or_status); ?></td>
                    <td>
                        <a href="#" class="edit-commission" data-id="<?php echo $commission->release_id; ?>">Edit</a> |
                        <a href="#" class="delete-commission" data-id="<?php echo $commission->release_id; ?>">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#add-new-commission').click(function(e) {
        e.preventDefault();
        $('#form-title').text('Add New Commission');
        $('#res-commission-form')[0].reset();
        $('#release_id').val('');
        $('#commission-form').slideDown();
        $('#commissions-list').slideUp();
    });
    
    $('#cancel-form').click(function() {
        $('#commission-form').slideUp();
        $('#commissions-list').slideDown();
    });
    
    // Auto-calculate net pay
    $('input[name="gross_amount"], input[name="inc"], input[name="ewt"]').on('input', function() {
        var gross = parseFloat($('input[name="gross_amount"]').val()) || 0;
        var inc = parseFloat($('input[name="inc"]').val()) || 0;
        var ewt = parseFloat($('input[name="ewt"]').val()) || 0;
        var net = gross - inc - ewt;
        $('input[name="net_pay"]').val(net.toFixed(2));
    });
    
    $('#res-commission-form').submit(function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        formData += '&action=res_save_commission&nonce=' + res_ajax.nonce;
        
        $.post(res_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                alert(response.data);
                location.reload();
            }
        });
    });
    
    $('.edit-commission').click(function(e) {
        e.preventDefault();
        var releaseId = $(this).data('id');
        
        $.post(res_ajax.ajax_url, {
            action: 'res_get_commission',
            release_id: releaseId,
            nonce: res_ajax.nonce
        }, function(response) {
            if (response.success) {
                var commission = response.data;
                $('#form-title').text('Edit Commission');
                $('#release_id').val(commission.release_id);
                
                $.each(commission, function(key, value) {
                    $('[name="' + key + '"]').val(value);
                });
                
                $('#commission-form').slideDown();
                $('#commissions-list').slideUp();
            }
        });
    });
    
    $('.delete-commission').click(function(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to delete this commission record?')) return;
        
        var releaseId = $(this).data('id');
        $.post(res_ajax.ajax_url, {
            action: 'res_delete_commission',
            release_id: releaseId,
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
                <h3>Release Information</h3>
                <div class="res-form-grid">
                    <div class="res-form-group">
                        <label>Release Date*</label>
                        <input type="date" name="release_date" required>
                    </div>
                    <div class="res-form-group">
                        <label>Voucher Number*</label>
                        <input type="text" name="voucher_number" required>
                    </div>
                    <div class="res-form-group">
                        <label>OR Number</label>
                        <input type="text" name="or_number">
                    </div>
                    <div class="res-form-group">
                        <label>OR Status</label>
                        <select name="or_status_id">
                            <option value="">Select...</option>
                            <?php foreach ($or_statuses as $status): ?>
                                <option value="<?php echo $status->id; ?>"><?php echo esc_html($status->or_status); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="res-form-section">