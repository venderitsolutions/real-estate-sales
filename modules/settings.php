<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

// Get all reference data
$tables = [
    'res_ref_gender' => ['name' => 'Gender', 'field' => 'gender'],
    'res_ref_civil_status' => ['name' => 'Civil Status', 'field' => 'civil_status'],
    'res_ref_employment_type' => ['name' => 'Employment Type', 'field' => 'employment_type'],
    'res_ref_status' => ['name' => 'Status', 'field' => 'status'],
    'res_ref_payment_status' => ['name' => 'Payment Status', 'field' => 'payment_status'],
    'res_ref_document_status' => ['name' => 'Document Status', 'field' => 'document_status'],
    'res_ref_source_of_sale' => ['name' => 'Source of Sale', 'field' => 'source'],
    'res_ref_license_status' => ['name' => 'License Status', 'field' => 'license_status'],
    'res_ref_or_status' => ['name' => 'OR Status', 'field' => 'or_status'],
    'res_ref_agent_status' => ['name' => 'Agent Status', 'field' => 'agent_status'],
    'res_ref_2307_status' => ['name' => 'Form 2307 Status', 'field' => 'status']
];
?>

<div class="wrap">
    <h1>Settings</h1>
    
    <div class="res-settings-container">
        <h2>Reference Data Management</h2>
        <p>Manage dropdown options and reference data used throughout the system.</p>
        
        <div id="settings-accordion">
            <?php foreach ($tables as $table => $info): ?>
                <?php 
                $items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}{$table} ORDER BY id");
                ?>
                <div class="settings-section">
                    <h3><?php echo esc_html($info['name']); ?> <span class="count">(<?php echo count($items); ?> items)</span></h3>
                    <div class="settings-content" data-table="<?php echo esc_attr($table); ?>" data-field="<?php echo esc_attr($info['field']); ?>">
                        <table class="widefat">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th><?php echo esc_html($info['name']); ?></th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?php echo $item->id; ?></td>
                                    <td>
                                        <span class="item-value"><?php echo esc_html($item->{$info['field']}); ?></span>
                                        <input type="text" class="edit-input" style="display:none;" value="<?php echo esc_attr($item->{$info['field']}); ?>">
                                    </td>
                                    <td>
                                        <a href="#" class="edit-item" data-id="<?php echo $item->id; ?>">Edit</a>
                                        <a href="#" class="save-item" data-id="<?php echo $item->id; ?>" style="display:none;">Save</a>
                                        <a href="#" class="cancel-edit" data-id="<?php echo $item->id; ?>" style="display:none;">Cancel</a>
                                        | <a href="#" class="delete-item" data-id="<?php echo $item->id; ?>">Delete</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div class="add-new-item">
                            <h4>Add New <?php echo esc_html($info['name']); ?></h4>
                            <input type="text" class="new-item-input" placeholder="Enter <?php echo esc_attr(strtolower($info['name'])); ?>">
                            <button class="button add-item-btn">Add</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Cash Advance Settings -->
        <div class="settings-section">
            <h3>Cash Advance Settings</h3>
            <div class="settings-content">
                <table class="form-table">
                    <tr>
                        <th><label for="max_advance_amount">Maximum Advance Amount</label></th>
                        <td>
                            <input type="number" id="max_advance_amount" value="<?php echo get_option('res_max_advance_amount', 50000); ?>" step="0.01">
                            <p class="description">Maximum amount that can be advanced to agents</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="advance_approval_required">Approval Required</label></th>
                        <td>
                            <input type="checkbox" id="advance_approval_required" <?php checked(get_option('res_advance_approval_required', 1)); ?>>
                            <label for="advance_approval_required">Require approval for cash advances</label>
                        </td>
                    </tr>
                </table>
                <button class="button button-primary" id="save-advance-settings">Save Settings</button>
            </div>
        </div>
        
        <!-- Commission Settings -->
        <div class="settings-section">
            <h3>Commission Settings</h3>
            <div class="settings-content">
                <table class="form-table">
                    <tr>
                        <th><label for="default_commission_rate">Default Commission Rate (%)</label></th>
                        <td>
                            <input type="number" id="default_commission_rate" value="<?php echo get_option('res_default_commission_rate', 5); ?>" step="0.01" min="0" max="100">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="vat_rate">VAT Rate (%)</label></th>
                        <td>
                            <input type="number" id="vat_rate" value="<?php echo get_option('res_vat_rate', 12); ?>" step="0.01" min="0" max="100">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="ewt_rate">EWT Rate (%)</label></th>
                        <td>
                            <input type="number" id="ewt_rate" value="<?php echo get_option('res_ewt_rate', 10); ?>" step="0.01" min="0" max="100">
                        </td>
                    </tr>
                </table>
                <button class="button button-primary" id="save-commission-settings">Save Settings</button>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Edit reference item
    $('.edit-item').click(function(e) {
        e.preventDefault();
        var row = $(this).closest('tr');
        row.find('.item-value').hide();
        row.find('.edit-input').show();
        $(this).hide();
        row.find('.save-item, .cancel-edit').show();
    });
    
    // Cancel edit
    $('.cancel-edit').click(function(e) {
        e.preventDefault();
        var row = $(this).closest('tr');
        row.find('.item-value').show();
        row.find('.edit-input').hide();
        row.find('.edit-item').show();
        row.find('.save-item, .cancel-edit').hide();
    });
    
    // Save edited item
    $('.save-item').click(function(e) {
        e.preventDefault();
        var row = $(this).closest('tr');
        var content = row.closest('.settings-content');
        var table = content.data('table');
        var field = content.data('field');
        var id = $(this).data('id');
        var value = row.find('.edit-input').val();
        
        $.post(res_ajax.ajax_url, {
            action: 'res_update_reference',
            table: table,
            field: field,
            id: id,
            value: value,
            nonce: res_ajax.nonce
        }, function(response) {
            if (response.success) {
                row.find('.item-value').text(value);
                row.find('.item-value').show();
                row.find('.edit-input').hide();
                row.find('.edit-item').show();
                row.find('.save-item, .cancel-edit').hide();
            }
        });
    });
    
    // Delete item
    $('.delete-item').click(function(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to delete this item?')) return;
        
        var row = $(this).closest('tr');
        var content = row.closest('.settings-content');
        var table = content.data('table');
        var id = $(this).data('id');
        
        $.post(res_ajax.ajax_url, {
            action: 'res_delete_reference',
            table: table,
            id: id,
            nonce: res_ajax.nonce
        }, function(response) {
            if (response.success) {
                row.fadeOut(function() {
                    $(this).remove();
                });
            }
        });
    });
    
    // Add new item
    $('.add-item-btn').click(function() {
        var container = $(this).closest('.settings-content');
        var table = container.data('table');
        var field = container.data('field');
        var value = container.find('.new-item-input').val();
        
        if (!value) {
            alert('Please enter a value');
            return;
        }
        
        $.post(res_ajax.ajax_url, {
            action: 'res_add_reference',
            table: table,
            field: field,
            value: value,
            nonce: res_ajax.nonce
        }, function(response) {
            if (response.success) {
                location.reload();
            }
        });
    });
    
    // Save advance settings
    $('#save-advance-settings').click(function() {
        var maxAmount = $('#max_advance_amount').val();
        var approvalRequired = $('#advance_approval_required').is(':checked') ? 1 : 0;
        
        $.post(res_ajax.ajax_url, {
            action: 'res_save_advance_settings',
            max_amount: maxAmount,
            approval_required: approvalRequired,
            nonce: res_ajax.nonce
        }, function(response) {
            if (response.success) {
                alert('Settings saved successfully');
            }
        });
    });
    
    // Save commission settings
    $('#save-commission-settings').click(function() {
        var defaultRate = $('#default_commission_rate').val();
        var vatRate = $('#vat_rate').val();
        var ewtRate = $('#ewt_rate').val();
        
        $.post(res_ajax.ajax_url, {
            action: 'res_save_commission_settings',
            default_rate: defaultRate,
            vat_rate: vatRate,
            ewt_rate: ewtRate,
            nonce: res_ajax.nonce
        }, function(response) {
            if (response.success) {
                alert('Settings saved successfully');
            }
        });
    });
    
    // Accordion functionality
    $('.settings-section h3').click(function() {
        $(this).next('.settings-content').slideToggle();
        $(this).toggleClass('active');
    });
});
</script>