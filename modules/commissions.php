<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

// Get reference data
$agents = $wpdb->get_results("SELECT agent_id, agent_name FROM {$wpdb->prefix}res_sales_agents ORDER BY agent_name");

// Get commission vouchers
$vouchers = $wpdb->get_results("
    SELECT v.*, a.agent_name
    FROM {$wpdb->prefix}res_commission_vouchers v
    LEFT JOIN {$wpdb->prefix}res_sales_agents a ON v.payee_agent_id = a.agent_id
    ORDER BY v.voucher_date DESC
");

// Calculate totals
$total_gross = $wpdb->get_var("SELECT SUM(total_gross_amount) FROM {$wpdb->prefix}res_commission_vouchers");
$total_net = $wpdb->get_var("SELECT SUM(total_net_amount) FROM {$wpdb->prefix}res_commission_vouchers");
$total_deductions = $wpdb->get_var("SELECT SUM(total_deductions) FROM {$wpdb->prefix}res_commission_vouchers");
?>

<div class="wrap">
    <h1>Commission Vouchers <a href="#" class="page-title-action" id="generate-new-voucher">Generate New Voucher</a></h1>
    
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
            <h3>Total Deductions</h3>
            <p class="amount">₱<?php echo number_format($total_deductions ?: 0, 2); ?></p>
        </div>
        <div class="summary-card">
            <h3>Total Vouchers</h3>
            <p class="amount"><?php echo count($vouchers); ?></p>
        </div>
    </div>
    
    <!-- Voucher Generation Form -->
    <div id="voucher-form" style="display:none;">
        <h2 id="form-title">Generate New Commission Voucher</h2>
        <form id="res-voucher-form">
            <div class="res-form-section">
                <h3>Voucher Information</h3>
                <div class="res-form-grid">
                    <div class="res-form-group">
                        <label>Agent*</label>
                        <select name="agent_id" id="voucher-agent-select" required>
                            <option value="">Select Agent...</option>
                            <?php foreach ($agents as $agent): ?>
                                <option value="<?php echo $agent->agent_id; ?>"><?php echo esc_html($agent->agent_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="res-form-group">
                        <label>Voucher Date*</label>
                        <input type="date" name="voucher_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="res-form-group">
                        <label>Prepared By*</label>
                        <input type="text" name="prepared_by" value="Meriam Dacara" required>
                    </div>
                    <div class="res-form-group">
                        <label>Checked By*</label>
                        <input type="text" name="checked_by" value="Laiza Sison" required>
                    </div>
                    <div class="res-form-group">
                        <label>Approved By*</label>
                        <input type="text" name="approved_by" value="Reymart Zuniega" required>
                    </div>
                    <div class="res-form-group">
                        <label>Bizlink Reference</label>
                        <input type="text" name="bizlink_ref" placeholder="e.g., ACA-072424-163315-12682423">
                    </div>
                </div>
            </div>
            
            <div class="res-form-section">
                <h3>Commission Collections</h3>
                <p>Select the collections to include in this voucher:</p>
                <div id="collections-list" style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 15px;">
                    <p>Please select an agent first to load available collections.</p>
                </div>
            </div>
            
            <div class="res-form-section">
                <h3>Deductions</h3>
                <div id="deductions-container">
                    <div class="deduction-row" style="display: flex; gap: 10px; margin-bottom: 10px;">
                        <input type="text" name="deductions[0][description]" placeholder="Deduction description" style="flex: 2;">
                        <input type="number" name="deductions[0][amount]" placeholder="Amount" step="0.01" style="flex: 1;">
                        <button type="button" class="button remove-deduction">Remove</button>
                    </div>
                </div>
                <button type="button" class="button" id="add-deduction">Add Deduction</button>
            </div>
            
            <div class="res-form-section">
                <h3>Voucher Summary</h3>
                <div class="voucher-summary" style="background: #f9f9f9; padding: 15px; border: 1px solid #ddd;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>Total Gross Amount:</span>
                        <span id="summary-gross">₱0.00</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>Total Deductions:</span>
                        <span id="summary-deductions">₱0.00</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 16px; border-top: 1px solid #ccc; padding-top: 10px;">
                        <span>Net Amount:</span>
                        <span id="summary-net">₱0.00</span>
                    </div>
                </div>
            </div>
            
            <div class="res-form-actions">
                <button type="submit" class="button button-primary">Generate Voucher</button>
                <button type="button" class="button" id="cancel-voucher-form">Cancel</button>
            </div>
        </form>
    </div>
    
    <!-- Vouchers List -->
    <div id="vouchers-list">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Voucher #</th>
                    <th>Date</th>
                    <th>Agent</th>
                    <th>Gross Amount</th>
                    <th>Deductions</th>
                    <th>Net Amount</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vouchers as $voucher): ?>
                <tr>
                    <td><strong><?php echo esc_html($voucher->voucher_number); ?></strong></td>
                    <td><?php echo date('M j, Y', strtotime($voucher->voucher_date)); ?></td>
                    <td><?php echo esc_html($voucher->agent_name); ?></td>
                    <td>₱<?php echo number_format($voucher->total_gross_amount, 2); ?></td>
                    <td>₱<?php echo number_format($voucher->total_deductions, 2); ?></td>
                    <td><strong>₱<?php echo number_format($voucher->total_net_amount, 2); ?></strong></td>
                    <td>
                        <span class="status-badge status-<?php echo $voucher->status; ?>">
                            <?php echo ucfirst($voucher->status); ?>
                        </span>
                    </td>
                    <td>
                        <a href="#" class="view-voucher-details" data-id="<?php echo $voucher->voucher_id; ?>">View</a> |
                        <a href="<?php echo admin_url('admin-ajax.php?action=res_download_voucher&voucher_id=' . $voucher->voucher_id . '&nonce=' . wp_create_nonce('res_ajax_nonce')); ?>" target="_blank" class="download-voucher">Download</a> |
                        <a href="#" class="delete-voucher" data-id="<?php echo $voucher->voucher_id; ?>">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Voucher Details Modal -->
    <div id="voucher-details-modal" style="display:none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border: 2px solid #ccc; max-width: 80%; max-height: 80%; overflow-y: auto; z-index: 10000;">
        <div class="modal-content">
            <h3 id="modal-voucher-title"></h3>
            <div id="voucher-details-content"></div>
            <div style="text-align: right; margin-top: 20px;">
                <button type="button" class="button button-primary" id="download-current-voucher">Download Voucher</button>
                <button type="button" class="button" onclick="jQuery('#voucher-details-modal').hide();">Close</button>
            </div>
        </div>
    </div>
    
    <!-- Modal Overlay -->
    <div id="modal-overlay" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;"></div>
</div>

<script>
jQuery(document).ready(function($) {
    var deductionCounter = 1;
    var selectedCollections = [];
    var currentVoucherId = null;
    
    $('#generate-new-voucher').click(function(e) {
        e.preventDefault();
        $('#form-title').text('Generate New Commission Voucher');
        $('#res-voucher-form')[0].reset();
        $('#voucher-form').slideDown();
        $('#vouchers-list').slideUp();
        resetDeductions();
        clearCollectionsList();
        updateVoucherSummary();
    });
    
    $('#cancel-voucher-form').click(function() {
        $('#voucher-form').slideUp();
        $('#vouchers-list').slideDown();
    });
    
    // Load collections when agent is selected
    $('#voucher-agent-select').change(function() {
        var agentId = $(this).val();
        if (agentId) {
            loadUnreleasedCollections(agentId);
        } else {
            clearCollectionsList();
        }
    });
    
    function loadUnreleasedCollections(agentId) {
        $.post(res_ajax.ajax_url, {
            action: 'res_get_unreleased_collections',
            agent_id: agentId,
            nonce: res_ajax.nonce
        }, function(response) {
            if (response.success && response.data.length > 0) {
                var html = '<div style="margin-bottom: 15px;"><label><input type="checkbox" id="select-all-collections"> Select All</label></div>';
                html += '<table class="widefat"><thead><tr><th>Select</th><th>Date</th><th>Client</th><th>Project</th><th>Gross Commission</th><th>Net Commission</th><th>Particulars</th></tr></thead><tbody>';
                
                response.data.forEach(function(collection) {
                    html += '<tr>';
                    html += '<td><input type="checkbox" class="collection-checkbox" value="' + collection.acct_collection_id + '" data-gross="' + collection.gross_commission + '" data-net="' + collection.net_commission + '"></td>';
                    html += '<td>' + collection.date_collected + '</td>';
                    html += '<td>' + collection.client_name + '</td>';
                    html += '<td>' + collection.project_name + '</td>';
                    html += '<td>₱' + parseFloat(collection.gross_commission).toLocaleString() + '</td>';
                    html += '<td>₱' + parseFloat(collection.net_commission).toLocaleString() + '</td>';
                    html += '<td>' + (collection.particulars || '') + '</td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
                $('#collections-list').html(html);
                
                // Bind checkbox events
                bindCollectionCheckboxes();
            } else {
                $('#collections-list').html('<p>No unreleased collections found for this agent.</p>');
            }
        });
    }
    
    function bindCollectionCheckboxes() {
        $('#select-all-collections').change(function() {
            $('.collection-checkbox').prop('checked', $(this).is(':checked')).trigger('change');
        });
        
        $('.collection-checkbox').change(function() {
            updateVoucherSummary();
        });
    }
    
    function clearCollectionsList() {
        $('#collections-list').html('<p>Please select an agent first to load available collections.</p>');
        updateVoucherSummary();
    }
    
    // Add deduction functionality
    $('#add-deduction').click(function() {
        var html = '<div class="deduction-row" style="display: flex; gap: 10px; margin-bottom: 10px;">';
        html += '<input type="text" name="deductions[' + deductionCounter + '][description]" placeholder="Deduction description" style="flex: 2;">';
        html += '<input type="number" name="deductions[' + deductionCounter + '][amount]" placeholder="Amount" step="0.01" style="flex: 1;" class="deduction-amount">';
        html += '<button type="button" class="button remove-deduction">Remove</button>';
        html += '</div>';
        
        $('#deductions-container').append(html);
        deductionCounter++;
        
        // Bind events for new deduction
        bindDeductionEvents();
    });
    
    function bindDeductionEvents() {
        $('.remove-deduction').off('click').on('click', function() {
            $(this).closest('.deduction-row').remove();
            updateVoucherSummary();
        });
        
        $('.deduction-amount').off('input').on('input', function() {
            updateVoucherSummary();
        });
    }
    
    function resetDeductions() {
        $('#deductions-container').html('<div class="deduction-row" style="display: flex; gap: 10px; margin-bottom: 10px;"><input type="text" name="deductions[0][description]" placeholder="Deduction description" style="flex: 2;"><input type="number" name="deductions[0][amount]" placeholder="Amount" step="0.01" style="flex: 1;" class="deduction-amount"><button type="button" class="button remove-deduction">Remove</button></div>');
        deductionCounter = 1;
        bindDeductionEvents();
    }
    
    function updateVoucherSummary() {
        var totalGross = 0;
        var totalDeductions = 0;
        
        // Calculate gross from selected collections
        $('.collection-checkbox:checked').each(function() {
            totalGross += parseFloat($(this).data('gross')) || 0;
        });
        
        // Calculate deductions
        $('.deduction-amount').each(function() {
            totalDeductions += parseFloat($(this).val()) || 0;
        });
        
        var totalNet = totalGross - totalDeductions;
        
        $('#summary-gross').text('₱' + totalGross.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        $('#summary-deductions').text('₱' + totalDeductions.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        $('#summary-net').text('₱' + totalNet.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    }
    
    // Initialize deduction events
    bindDeductionEvents();
    
    // Form submission
    $('#res-voucher-form').submit(function(e) {
        e.preventDefault();
        
        var selectedCollectionIds = [];
        $('.collection-checkbox:checked').each(function() {
            selectedCollectionIds.push($(this).val());
        });
        
        if (selectedCollectionIds.length === 0) {
            alert('Please select at least one collection');
            return;
        }
        
        var formData = $(this).serializeArray();
        formData.push({name: 'action', value: 'res_generate_voucher'});
        formData.push({name: 'nonce', value: res_ajax.nonce});
        formData.push({name: 'collection_ids', value: selectedCollectionIds});
        
        // Add deductions as separate array
        var deductions = [];
        $('.deduction-row').each(function() {
            var description = $(this).find('input[type="text"]').val();
            var amount = $(this).find('input[type="number"]').val();
            if (description && amount) {
                deductions.push({description: description, amount: amount});
            }
        });
        
        $.post(res_ajax.ajax_url, $.param(formData) + '&' + $.param({deductions: deductions}), function(response) {
            if (response.success) {
                alert('Voucher generated successfully: ' + response.data.voucher_number);
                location.reload();
            } else {
                alert('Error: ' + response.data);
            }
        });
    });
    
    // View voucher details
    $('.view-voucher-details').click(function(e) {
        e.preventDefault();
        var voucherId = $(this).data('id');
        currentVoucherId = voucherId;
        
        $.post(res_ajax.ajax_url, {
            action: 'res_get_voucher_details',
            voucher_id: voucherId,
            nonce: res_ajax.nonce
        }, function(response) {
            if (response.success) {
                $('#modal-voucher-title').text('Voucher Details: ' + response.data.voucher.voucher_number);
                
                var html = '<div class="voucher-details">';
                html += '<p><strong>Agent:</strong> ' + response.data.voucher.agent_name + '</p>';
                html += '<p><strong>Date:</strong> ' + response.data.voucher.voucher_date + '</p>';
                html += '<p><strong>Status:</strong> ' + response.data.voucher.status + '</p>';
                html += '<p><strong>Gross Amount:</strong> ₱' + parseFloat(response.data.voucher.total_gross_amount).toLocaleString() + '</p>';
                html += '<p><strong>Deductions:</strong> ₱' + parseFloat(response.data.voucher.total_deductions).toLocaleString() + '</p>';
                html += '<p><strong>Net Amount:</strong> ₱' + parseFloat(response.data.voucher.total_net_amount).toLocaleString() + '</p>';
                
                if (response.data.line_items.length > 0) {
                    html += '<h4>Commission Line Items:</h4>';
                    html += '<table class="widefat"><thead><tr><th>Client</th><th>Project</th><th>Commission %</th><th>Amount</th><th>Net Pay</th><th>Particulars</th></tr></thead><tbody>';
                    response.data.line_items.forEach(function(item) {
                        html += '<tr>';
                        html += '<td>' + item.client_name + '</td>';
                        html += '<td>' + item.project_name + '</td>';
                        html += '<td>' + parseFloat(item.commission_percentage).toFixed(1) + '%</td>';
                        html += '<td>₱' + parseFloat(item.amount).toLocaleString() + '</td>';
                        html += '<td>₱' + parseFloat(item.net_pay).toLocaleString() + '</td>';
                        html += '<td>' + (item.particulars || '') + '</td>';
                        html += '</tr>';
                    });
                    html += '</tbody></table>';
                }
                
                if (response.data.deductions.length > 0) {
                    html += '<h4>Deductions:</h4>';
                    html += '<table class="widefat"><thead><tr><th>Description</th><th>Amount</th></tr></thead><tbody>';
                    response.data.deductions.forEach(function(deduction) {
                        html += '<tr>';
                        html += '<td>' + deduction.description + '</td>';
                        html += '<td>₱' + parseFloat(deduction.amount).toLocaleString() + '</td>';
                        html += '</tr>';
                    });
                    html += '</tbody></table>';
                }
                
                html += '</div>';
                $('#voucher-details-content').html(html);
                
                $('#modal-overlay').show();
                $('#voucher-details-modal').show();
            }
        });
    });
    
    // Download current voucher from modal
    $('#download-current-voucher').click(function() {
        if (currentVoucherId) {
            window.open(res_ajax.ajax_url + '?action=res_download_voucher&voucher_id=' + currentVoucherId + '&nonce=' + res_ajax.nonce, '_blank');
        }
    });
    
    // Close modal when clicking overlay
    $('#modal-overlay').click(function() {
        $('#voucher-details-modal').hide();
        $('#modal-overlay').hide();
    });
    
    // Delete voucher
    $('.delete-voucher').click(function(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to delete this voucher? This will also unreleased the associated collections.')) return;
        
        var voucherId = $(this).data('id');
        $.post(res_ajax.ajax_url, {
            action: 'res_delete_voucher',
            voucher_id: voucherId,
            nonce: res_ajax.nonce
        }, function(response) {
            if (response.success) {
                alert('Voucher deleted successfully');
                location.reload();
            }
        });
    });
});
</script>