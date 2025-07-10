<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

// Get reference data
$developers = $wpdb->get_results("SELECT developer_id, developer_name FROM {$wpdb->prefix}res_developers ORDER BY developer_name");
$projects = $wpdb->get_results("
    SELECT p.project_id, p.project_name, p.developer_id, d.developer_name 
    FROM {$wpdb->prefix}res_projects p
    LEFT JOIN {$wpdb->prefix}res_developers d ON p.developer_id = d.developer_id
    ORDER BY d.developer_name, p.project_name
");
$form_2307_statuses = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}res_ref_2307_status");

// Get default VAT and EWT rates
$default_vat_rate = get_option('res_vat_rate', 12);
$default_ewt_rate = get_option('res_ewt_rate', 10);

// Get developer collections
$collections = $wpdb->get_results("
    SELECT dc.*, p.project_name, d.developer_name, f.status as form_2307_status
    FROM {$wpdb->prefix}res_developer_collections dc
    LEFT JOIN {$wpdb->prefix}res_projects p ON dc.project_id = p.project_id
    LEFT JOIN {$wpdb->prefix}res_developers d ON p.developer_id = d.developer_id
    LEFT JOIN {$wpdb->prefix}res_ref_2307_status f ON dc.form_2307_status_id = f.id
    ORDER BY dc.collection_date DESC
");

// Calculate totals
$total_gross = $wpdb->get_var("SELECT SUM(gross_amount) FROM {$wpdb->prefix}res_developer_collections");
$total_net = $wpdb->get_var("SELECT SUM(net_collected_amount) FROM {$wpdb->prefix}res_developer_collections");
$total_vat = $wpdb->get_var("SELECT SUM(vat) FROM {$wpdb->prefix}res_developer_collections");
$total_ewt = $wpdb->get_var("SELECT SUM(ewt) FROM {$wpdb->prefix}res_developer_collections");
?>

<div class="wrap">
    <h1>Developer Collections <a href="#" class="page-title-action" id="add-new-dev-collection">Add New</a></h1>
    
    <!-- Summary Cards -->
    <div class="res-summary-cards">
        <div class="summary-card">
            <h3>Total Gross Collections</h3>
            <p class="amount">₱<?php echo number_format($total_gross ?: 0, 2); ?></p>
        </div>
        <div class="summary-card">
            <h3>Total Net Collections</h3>
            <p class="amount">₱<?php echo number_format($total_net ?: 0, 2); ?></p>
        </div>
        <div class="summary-card">
            <h3>Total VAT</h3>
            <p class="amount">₱<?php echo number_format($total_vat ?: 0, 2); ?></p>
        </div>
        <div class="summary-card">
            <h3>Total EWT</h3>
            <p class="amount">₱<?php echo number_format($total_ewt ?: 0, 2); ?></p>
        </div>
    </div>
    
    <!-- Developer Collection Form -->
    <div id="dev-collection-form" style="display:none;">
        <h2 id="form-title">Add New Developer Collection</h2>
        <form id="res-dev-collection-form">
            <input type="hidden" id="dev_collection_id" name="dev_collection_id" value="">
            <input type="hidden" id="default_vat_rate" value="<?php echo $default_vat_rate; ?>">
            <input type="hidden" id="default_ewt_rate" value="<?php echo $default_ewt_rate; ?>">
            
            <div class="res-form-section">
                <h3>Collection Information</h3>
                <div class="res-form-grid">
                    <div class="res-form-group">
                        <label>Date of Collection*</label>
                        <input type="date" name="collection_date" required>
                    </div>
                    <div class="res-form-group">
                        <label>OR Number*</label>
                        <input type="text" name="or_number" required>
                    </div>
                    <div class="res-form-group">
                        <label>Payor (Developer)*</label>
                        <select name="payor_developer_id" id="payor_developer_id" required>
                            <option value="">Select Developer...</option>
                            <?php foreach ($developers as $developer): ?>
                                <option value="<?php echo $developer->developer_id; ?>" data-name="<?php echo esc_attr($developer->developer_name); ?>">
                                    <?php echo esc_html($developer->developer_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="res-form-group">
                        <label>Project*</label>
                        <select name="project_id" id="project_select" required>
                            <option value="">Select Project...</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?php echo $project->project_id; ?>" data-developer="<?php echo $project->developer_id; ?>">
                                    <?php echo esc_html($project->project_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="res-form-group">
                        <label>Payee*</label>
                        <input type="text" name="payee" required value="JCRZ Realty">
                    </div>
                </div>
            </div>
            
            <div class="res-form-section">
                <h3>Particulars</h3>
                <div class="res-form-group full-width">
                    <label>Particulars</label>
                    <textarea name="particulars" rows="3"></textarea>
                </div>
            </div>
            
            <div class="res-form-section">
                <h3>Financial Details</h3>
                <div class="res-form-grid">
                    <div class="res-form-group">
                        <label>Gross Amount*</label>
                        <input type="number" name="gross_amount" id="gross_amount" step="0.01" required>
                    </div>
                    <div class="res-form-group">
                        <label>VAT</label>
                        <input type="number" name="vat" id="vat" step="0.01">
                        <small class="description">Formula: Gross - (Gross/(1+(<?php echo $default_vat_rate; ?>%/100)))</small>
                    </div>
                    <div class="res-form-group">
                        <label>EWT</label>
                        <input type="number" name="ewt" id="ewt" step="0.01">
                        <small class="description">Formula: (Gross/(1+(<?php echo $default_vat_rate; ?>%/100))) × (<?php echo $default_ewt_rate; ?>%/100)</small>
                    </div>
                    <div class="res-form-group">
                        <label>Net Collected Amount*</label>
                        <input type="number" name="net_collected_amount" id="net_collected_amount" step="0.01" readonly style="background-color: #f5f5f5;">
                        <small class="description">Formula: Gross - EWT</small>
                    </div>
                </div>
            </div>
            
            <div class="res-form-section">
                <h3>Deposit Information</h3>
                <div class="res-form-grid">
                    <div class="res-form-group">
                        <label>2307 Status</label>
                        <select name="form_2307_status_id">
                            <option value="">Select...</option>
                            <?php foreach ($form_2307_statuses as $status): ?>
                                <option value="<?php echo $status->id; ?>"><?php echo esc_html($status->status); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="res-form-group">
                        <label>Deposit Date</label>
                        <input type="date" name="deposit_date">
                    </div>
                    <div class="res-form-group">
                        <label>Account Deposited</label>
                        <input type="text" name="account_deposited">
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
            
            <!-- Hidden field for payor name -->
            <input type="hidden" name="payor" id="payor_hidden">
            
            <div class="res-form-actions">
                <button type="submit" class="button button-primary">Save Collection</button>
                <button type="button" class="button" id="cancel-form">Cancel</button>
            </div>
        </form>
    </div>
    
    <!-- Developer Collections List -->
    <div id="dev-collections-list">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>OR #</th>
                    <th>Payor</th>
                    <th>Project</th>
                    <th>Gross Amount</th>
                    <th>VAT</th>
                    <th>EWT</th>
                    <th>Net Collected</th>
                    <th>2307 Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($collections as $collection): ?>
                <tr>
                    <td><?php echo date('M j, Y', strtotime($collection->collection_date)); ?></td>
                    <td><strong><?php echo esc_html($collection->or_number); ?></strong></td>
                    <td><?php echo esc_html($collection->payor); ?></td>
                    <td><?php echo esc_html($collection->project_name); ?></td>
                    <td>₱<?php echo number_format($collection->gross_amount, 2); ?></td>
                    <td>₱<?php echo number_format($collection->vat, 2); ?></td>
                    <td>₱<?php echo number_format($collection->ewt, 2); ?></td>
                    <td><strong>₱<?php echo number_format($collection->net_collected_amount, 2); ?></strong></td>
                    <td><?php echo esc_html($collection->form_2307_status); ?></td>
                    <td>
                        <a href="#" class="edit-dev-collection" data-id="<?php echo $collection->dev_collection_id; ?>">Edit</a> |
                        <a href="#" class="delete-dev-collection" data-id="<?php echo $collection->dev_collection_id; ?>">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Set payor name when developer is selected
    $('#payor_developer_id').change(function() {
        var selectedOption = $(this).find('option:selected');
        var developerName = selectedOption.data('name');
        $('#payor_hidden').val(developerName);
        
        // Filter projects by developer
        var developerId = $(this).val();
        $('#project_select option').each(function() {
            if ($(this).val() === '') {
                $(this).show();
            } else if ($(this).data('developer') == developerId) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
        
        // Reset project selection if current selection doesn't match
        var currentProject = $('#project_select').val();
        if (currentProject && $('#project_select option:selected').data('developer') != developerId) {
            $('#project_select').val('');
        }
    });
    
    // Auto-calculate VAT, EWT and Net when gross amount is entered
    $('#gross_amount').on('input', function() {
        var gross = parseFloat($(this).val()) || 0;
        var vatRate = parseFloat($('#default_vat_rate').val()) || 12;
        var ewtRate = parseFloat($('#default_ewt_rate').val()) || 10;
        
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
        
        calculateNetCollected();
    });
    
    // Mark fields as user-modified when manually changed
    $('#vat, #ewt').on('input', function() {
        $(this).data('user-modified', true);
        calculateNetCollected();
    });
    
    // Calculate net collected amount
    function calculateNetCollected() {
        var gross = parseFloat($('#gross_amount').val()) || 0;
        var ewt = parseFloat($('#ewt').val()) || 0;
        // Net = Gross - EWT (VAT is not deducted from net)
        var net = gross - ewt;
        $('#net_collected_amount').val(net.toFixed(2));
    }
    
    $('#add-new-dev-collection').click(function(e) {
        e.preventDefault();
        $('#form-title').text('Add New Developer Collection');
        $('#res-dev-collection-form')[0].reset();
        $('#dev_collection_id').val('');
        $('#vat, #ewt').data('user-modified', false);
        $('#dev-collection-form').slideDown();
        $('#dev-collections-list').slideUp();
    });
    
    $('#cancel-form').click(function() {
        $('#dev-collection-form').slideUp();
        $('#dev-collections-list').slideDown();
    });
    
    $('#res-dev-collection-form').submit(function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        formData += '&action=res_save_developer_collection&nonce=' + res_ajax.nonce;
        
        $.post(res_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                alert(response.data);
                location.reload();
            }
        });
    });
    
    $('.edit-dev-collection').click(function(e) {
        e.preventDefault();
        var collectionId = $(this).data('id');
        
        $.post(res_ajax.ajax_url, {
            action: 'res_get_developer_collection',
            collection_id: collectionId,
            nonce: res_ajax.nonce
        }, function(response) {
            if (response.success) {
                var collection = response.data;
                $('#form-title').text('Edit Developer Collection');
                $('#dev_collection_id').val(collection.dev_collection_id);
                
                // Find developer ID from project
                if (collection.project_id) {
                    var projectOption = $('#project_select option[value="' + collection.project_id + '"]');
                    var developerId = projectOption.data('developer');
                    if (developerId) {
                        $('#payor_developer_id').val(developerId).trigger('change');
                    }
                }
                
                $.each(collection, function(key, value) {
                    $('[name="' + key + '"]').val(value);
                });
                
                // Mark VAT and EWT as user-modified to prevent auto-calculation override
                $('#vat, #ewt').data('user-modified', true);
                
                $('#dev-collection-form').slideDown();
                $('#dev-collections-list').slideUp();
            }
        });
    });
    
    $('.delete-dev-collection').click(function(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to delete this collection record?')) return;
        
        var collectionId = $(this).data('id');
        $.post(res_ajax.ajax_url, {
            action: 'res_delete_developer_collection',
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