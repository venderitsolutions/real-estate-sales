<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

// Get reference data
$clients = $wpdb->get_results("SELECT client_id, CONCAT(first_name, ' ', surname) as name FROM {$wpdb->prefix}res_clients ORDER BY first_name");
$agents = $wpdb->get_results("SELECT agent_id, agent_name FROM {$wpdb->prefix}res_sales_agents ORDER BY agent_name");
$developers = $wpdb->get_results("SELECT developer_id, developer_name FROM {$wpdb->prefix}res_developers ORDER BY developer_name");
$projects = $wpdb->get_results("SELECT project_id, project_name FROM {$wpdb->prefix}res_projects ORDER BY project_name");

// Get OR numbers from developer collections
$or_numbers = $wpdb->get_results("SELECT DISTINCT or_number FROM {$wpdb->prefix}res_developer_collections WHERE or_number IS NOT NULL AND or_number != '' ORDER BY or_number");

// Get default VAT and EWT rates
$default_vat_rate = get_option('res_vat_rate', 12);
$default_ewt_rate = get_option('res_ewt_rate', 10);

// Get collections
$collections = $wpdb->get_results("
    SELECT ac.*, 
           CONCAT(c.first_name, ' ', c.surname) as client_name,
           a.agent_name,
           d.developer_name,
           p.project_name,
           v.voucher_number
    FROM {$wpdb->prefix}res_account_collections ac
    LEFT JOIN {$wpdb->prefix}res_clients c ON ac.client_id = c.client_id
    LEFT JOIN {$wpdb->prefix}res_sales_agents a ON ac.agent_id = a.agent_id
    LEFT JOIN {$wpdb->prefix}res_developers d ON ac.developer_id = d.developer_id
    LEFT JOIN {$wpdb->prefix}res_projects p ON ac.project_id = p.project_id
    LEFT JOIN {$wpdb->prefix}res_commission_vouchers v ON ac.voucher_id = v.voucher_id
    ORDER BY ac.date_collected DESC
");

// Calculate totals
$total_gross = $wpdb->get_var("SELECT SUM(gross_commission) FROM {$wpdb->prefix}res_account_collections");
$total_net = $wpdb->get_var("SELECT SUM(net_commission) FROM {$wpdb->prefix}res_account_collections");
$total_vat = $wpdb->get_var("SELECT SUM(vat) FROM {$wpdb->prefix}res_account_collections");
$total_ewt = $wpdb->get_var("SELECT SUM(ewt) FROM {$wpdb->prefix}res_account_collections");
$total_released = $wpdb->get_var("SELECT SUM(gross_commission) FROM {$wpdb->prefix}res_account_collections WHERE is_released = 1");
$total_unreleased = $wpdb->get_var("SELECT SUM(gross_commission) FROM {$wpdb->prefix}res_account_collections WHERE is_released = 0");
?>

<div class="wrap">
    <h1>Accounts from Collections <a href="#" class="page-title-action" id="add-new-collection">Add New</a></h1>
    
    <!-- Summary Cards -->
    <div class="res-summary-cards">
        <div class="summary-card">
            <h3>Total Gross Commission</h3>
            <p class="amount">₱<?php echo number_format($total_gross ?: 0, 2); ?></p>
        </div>
        <div class="summary-card">
            <h3>Total Net Commission</h3>
            <p class="amount">₱<?php echo number_format($total_net ?: 0, 2); ?></p>
        </div>
        <div class="summary-card">
            <h3>Released Commissions</h3>
            <p class="amount">₱<?php echo number_format($total_released ?: 0, 2); ?></p>
        </div>
        <div class="summary-card">
            <h3>Unreleased Commissions</h3>
            <p class="amount">₱<?php echo number_format($total_unreleased ?: 0, 2); ?></p>
        </div>
    </div>
    
    <!-- Collection Form -->
    <div id="collection-form" style="display:none;">
        <h2 id="form-title">Add New Collection</h2>
        <form id="res-collection-form">
            <input type="hidden" id="acct_collection_id" name="acct_collection_id" value="">
            
            <div class="res-form-section">
                <h3>Collection Information</h3>
                <div class="res-form-grid">
                    <div class="res-form-group">
                        <label>OR Number* (from Developer Collections)</label>
                        <select name="or_number" id="or_number" required>
                            <option value="">Select OR Number...</option>
                            <?php foreach ($or_numbers as $or): ?>
                                <option value="<?php echo esc_attr($or->or_number); ?>"><?php echo esc_html($or->or_number); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="description">Select from existing Developer Collection OR numbers</small>
                    </div>
                    <div class="res-form-group">
                        <label>Date Collected*</label>
                        <input type="date" name="date_collected" required>
                    </div>
                    <div class="res-form-group">
                        <label>Payor (Developer)</label>
                        <select name="payor_developer" id="payor_developer">
                            <option value="">Select Developer...</option>
                            <?php foreach ($developers as $developer): ?>
                                <option value="<?php echo esc_attr($developer->developer_name); ?>"><?php echo esc_html($developer->developer_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="res-form-group">
                        <label>Payee (Your Company)</label>
                        <input type="text" name="payee" value="JCRZ Realty">
                    </div>
                </div>
            </div>
            
            <div class="res-form-section">
                <h3>Client & Agent Information</h3>
                <div class="res-form-grid">
                    <div class="res-form-group">
                        <label>Client Name*</label>
                        <select name="client_id" id="client_select" required>
                            <option value="">Select Client...</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?php echo $client->client_id; ?>" data-name="<?php echo esc_attr($client->name); ?>">
                                    <?php echo esc_html($client->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="res-form-group">
                        <label>Agent Name*</label>
                        <select name="agent_id" required>
                            <option value="">Select Agent...</option>
                            <?php foreach ($agents as $agent): ?>
                                <option value="<?php echo $agent->agent_id; ?>"><?php echo esc_html($agent->agent_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="res-form-section">
                <h3>Property Information</h3>
                <div class="res-form-grid">
                    <div class="res-form-group">
                        <label>Developer*</label>
                        <select name="developer_id" id="developer_select" required>
                            <option value="">Select Developer...</option>
                            <?php foreach ($developers as $developer): ?>
                                <option value="<?php echo $developer->developer_id; ?>"><?php echo esc_html($developer->developer_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="res-form-group">
                        <label>Project*</label>
                        <select name="project_id" id="project_select" required>
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
                </div>
            </div>
            
            <div class="res-form-section">
                <h3>Commission Details</h3>
                <div class="res-form-grid">
                    <div class="res-form-group">
                        <label>Commission Percentage*</label>
                        <input type="number" name="commission_percentage" id="commission_percentage" step="0.01" min="0" max="100" required>
                        <small class="description">Enter the commission percentage for this transaction</small>
                    </div>
                    <div class="res-form-group">
                        <label>Gross Commission*</label>
                        <input type="number" name="gross_commission" id="gross_commission" step="0.01" required>
                    </div>
                    <div class="res-form-group">
                        <label>VAT (<?php echo $default_vat_rate; ?>%)</label>
                        <input type="number" name="vat" id="vat" step="0.01" data-rate="<?php echo $default_vat_rate; ?>">
                        <small class="description">Formula: Gross - (Gross/(1+(<?php echo $default_vat_rate; ?>%/100)))</small>
                    </div>
                    <div class="res-form-group">
                        <label>EWT (<?php echo $default_ewt_rate; ?>%)</label>
                        <input type="number" name="ewt" id="ewt" step="0.01" data-rate="<?php echo $default_ewt_rate; ?>">
                        <small class="description">Formula: (Gross/(1+(<?php echo $default_vat_rate; ?>%/100))) × (<?php echo $default_ewt_rate; ?>%/100)</small>
                    </div>
                    <div class="res-form-group">
                        <label>Net Commission*</label>
                        <input type="number" name="net_commission" id="net_commission" step="0.01" readonly style="background-color: #f5f5f5;">
                        <small class="description">Formula: Gross - EWT</small>
                    </div>
                </div>
            </div>
            
            <div class="res-form-section">
                <h3>Additional Information</h3>
                <div class="res-form-grid">
                    <div class="res-form-group full-width">
                        <label>Particulars</label>
                        <textarea name="particulars" id="particulars" rows="3"></textarea>
                    </div>
                    <div class="res-form-group full-width">
                        <label>Client Name + Particulars</label>
                        <input type="text" name="cname_particulars" id="cname_particulars" readonly style="background-color: #f5f5f5;">
                        <small class="description">Auto-generated: Client Name [Particulars]</small>
                    </div>
                </div>
            </div>
            
            <!-- Hidden fields for payor -->
            <input type="hidden" name="payor" id="payor_hidden">
            
            <div class="res-form-actions">
                <button type="submit" class="button button-primary">Save Collection</button>
                <button type="button" class="button" id="cancel-form">Cancel</button>
            </div>
        </form>
    </div>
    
    <!-- Collections List -->
    <div id="collections-list">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>OR #</th>
                    <th>Date</th>
                    <th>Payor</th>
                    <th>Client</th>
                    <th>Agent</th>
                    <th>Project</th>
                    <th>Block/Lot</th>
                    <th>Comm %</th>
                    <th>Gross</th>
                    <th>VAT</th>
                    <th>EWT</th>
                    <th>Net</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($collections as $collection): ?>
                <tr>
                    <td><strong><?php echo esc_html($collection->or_number); ?></strong></td>
                    <td><?php echo date('D, M j, Y', strtotime($collection->date_collected)); ?></td>
                    <td><?php echo esc_html($collection->payor); ?></td>
                    <td><?php echo esc_html($collection->client_name); ?></td>
                    <td><?php echo esc_html($collection->agent_name); ?></td>
                    <td><?php echo esc_html($collection->project_name); ?></td>
                    <td>
                        <?php 
                        $location = array();
                        if ($collection->block) $location[] = 'B' . $collection->block;
                        if ($collection->lot) $location[] = 'L' . $collection->lot;
                        echo esc_html(implode(' ', $location));
                        ?>
                    </td>
                    <td><?php echo number_format($collection->commission_percentage, 1); ?>%</td>
                    <td>₱<?php echo number_format($collection->gross_commission, 2); ?></td>
                    <td>₱<?php echo number_format($collection->vat, 2); ?></td>
                    <td>₱<?php echo number_format($collection->ewt, 2); ?></td>
                    <td><strong>₱<?php echo number_format($collection->net_commission, 2); ?></strong></td>
                    <td>
                        <?php if ($collection->is_released): ?>
                            <span class="status-badge status-released">
                                Released
                                <?php if ($collection->voucher_number): ?>
                                    <br><small><?php echo esc_html($collection->voucher_number); ?></small>
                                <?php endif; ?>
                            </span>
                        <?php else: ?>
                            <span class="status-badge status-unreleased">Unreleased</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$collection->is_released): ?>
                            <a href="#" class="edit-collection" data-id="<?php echo $collection->acct_collection_id; ?>">Edit</a> |
                            <a href="#" class="delete-collection" data-id="<?php echo $collection->acct_collection_id; ?>">Delete</a>
                        <?php else: ?>
                            <span class="text-muted">Released</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.status-released {
    background-color: #d4edda;
    color: #155724;
}

.status-unreleased {
    background-color: #fff3cd;
    color: #856404;
}

.text-muted {
    color: #6c757d;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Load OR details when selected
    $('#or_number').change(function() {
        var orNumber = $(this).val();
        if (orNumber) {
            $.post(res_ajax.ajax_url, {
                action: 'res_get_developer_collection_by_or',
                or_number: orNumber,
                nonce: res_ajax.nonce
            }, function(response) {
                if (response.success && response.data) {
                    // Auto-fill payor from developer collection
                    $('#payor_developer').val(response.data.payor).trigger('change');
                    // Set date if needed
                    if (response.data.collection_date && !$('input[name="date_collected"]').val()) {
                        $('input[name="date_collected"]').val(response.data.collection_date);
                    }
                }
            });
        }
    });
    
    // Update hidden payor field when developer is selected
    $('#payor_developer').change(function() {
        $('#payor_hidden').val($(this).val());
    });
    
    // Auto-calculate VAT and EWT when gross commission is entered
    $('#gross_commission').on('input', function() {
        var gross = parseFloat($(this).val()) || 0;
        var vatRate = parseFloat($('#vat').data('rate')) || 12;
        var ewtRate = parseFloat($('#ewt').data('rate')) || 10;
        
        // Calculate VAT: Gross - (Gross/(1+(VAT%/100)))
        var vatBase = gross / (1 + (vatRate / 100));
        var vat = gross - vatBase;
        
        // Calculate EWT: (Gross/(1+(VAT%/100))) × (EWT%/100)
        var ewt = vatBase * (ewtRate / 100);
        
        // Set values but allow user to override
        if (!$('#vat').data('user-modified')) {
            $('#vat').val(vat.toFixed(2));
        }
        if (!$('#ewt').data('user-modified')) {
            $('#ewt').val(ewt.toFixed(2));
        }
        
        calculateNetCommission();
    });
    
    // Mark fields as user-modified when manually changed
    $('#vat, #ewt').on('input', function() {
        $(this).data('user-modified', true);
        calculateNetCommission();
    });
    
    // Calculate net commission
    function calculateNetCommission() {
        var gross = parseFloat($('#gross_commission').val()) || 0;
        var ewt = parseFloat($('#ewt').val()) || 0;
        // Net = Gross - EWT (VAT is not deducted from net)
        var net = gross - ewt;
        $('#net_commission').val(net.toFixed(2));
    }
    
    // Auto-generate Client Name + Particulars
    $('#client_select, #particulars').on('change input', function() {
        var clientName = $('#client_select option:selected').data('name') || '';
        var particulars = $('#particulars').val();
        var combined = clientName;
        if (particulars) {
            combined += ' [' + particulars + ']';
        }
        $('#cname_particulars').val(combined);
    });
    
    // Filter projects by developer
    $('#developer_select').change(function() {
        var developerId = $(this).val();
        if (developerId) {
            $.post(res_ajax.ajax_url, {
                action: 'res_get_developer_projects',
                developer_id: developerId,
                nonce: res_ajax.nonce
            }, function(response) {
                if (response.success) {
                    var options = '<option value="">Select Project...</option>';
                    $.each(response.data, function(i, project) {
                        options += '<option value="' + project.project_id + '">' + project.project_name + '</option>';
                    });
                    $('#project_select').html(options);
                }
            });
        }
    });
    
    $('#add-new-collection').click(function(e) {
        e.preventDefault();
        $('#form-title').text('Add New Collection');
        $('#res-collection-form')[0].reset();
        $('#acct_collection_id').val('');
        $('#vat, #ewt').data('user-modified', false);
        $('#collection-form').slideDown();
        $('#collections-list').slideUp();
    });
    
    $('#cancel-form').click(function() {
        $('#collection-form').slideUp();
        $('#collections-list').slideDown();
    });
    
    $('#res-collection-form').submit(function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        formData += '&action=res_save_collection&nonce=' + res_ajax.nonce;
        
        $.post(res_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                alert(response.data);
                location.reload();
            }
        });
    });
    
    $('.edit-collection').click(function(e) {
        e.preventDefault();
        var collectionId = $(this).data('id');
        
        $.post(res_ajax.ajax_url, {
            action: 'res_get_collection',
            collection_id: collectionId,
            nonce: res_ajax.nonce
        }, function(response) {
            if (response.success) {
                var collection = response.data;
                $('#form-title').text('Edit Collection');
                $('#acct_collection_id').val(collection.acct_collection_id);
                
                $.each(collection, function(key, value) {
                    $('[name="' + key + '"]').val(value);
                });
                
                // Mark VAT and EWT as user-modified to prevent auto-calculation override
                $('#vat, #ewt').data('user-modified', true);
                
                $('#collection-form').slideDown();
                $('#collections-list').slideUp();
            }
        });
    });
    
    $('.delete-collection').click(function(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to delete this collection record?')) return;
        
        var collectionId = $(this).data('id');
        $.post(res_ajax.ajax_url, {
            action: 'res_delete_collection',
            collection_id: collectionId,
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