<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

// Get reference data
$civil_statuses = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}res_ref_civil_status");
$genders = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}res_ref_gender");
$employment_types = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}res_ref_employment_type");
$sources = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}res_ref_source_of_sale");

// Get clients list
$clients = $wpdb->get_results("
    SELECT c.*, cs.civil_status, g.gender, et.employment_type, s.source
    FROM {$wpdb->prefix}res_clients c
    LEFT JOIN {$wpdb->prefix}res_ref_civil_status cs ON c.civil_status_id = cs.id
    LEFT JOIN {$wpdb->prefix}res_ref_gender g ON c.gender_id = g.id
    LEFT JOIN {$wpdb->prefix}res_ref_employment_type et ON c.employment_type_id = et.id
    LEFT JOIN {$wpdb->prefix}res_ref_source_of_sale s ON c.source_of_sale_id = s.id
    ORDER BY c.created_at DESC
");
?>

<div class="wrap">
    <h1>Clients <a href="#" class="page-title-action" id="add-new-client">Add New</a></h1>
    
    <!-- Client Form (Hidden by default) -->
    <div id="client-form" style="display:none;">
        <h2 id="form-title">Add New Client</h2>
        <form id="res-client-form">
            <input type="hidden" id="client_id" name="client_id" value="">
            
            <div class="res-form-section">
                <h3>Primary Client Information</h3>
                <div class="res-form-grid">
                    <div class="res-form-group">
                        <label>First Name*</label>
                        <input type="text" name="first_name" required>
                    </div>
                    <div class="res-form-group">
                        <label>Middle Initial</label>
                        <input type="text" name="middle_initial" maxlength="10">
                    </div>
                    <div class="res-form-group">
                        <label>Surname*</label>
                        <input type="text" name="surname" required>
                    </div>
                    <div class="res-form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="date_of_birth">
                    </div>
                    <div class="res-form-group">
                        <label>Civil Status</label>
                        <select name="civil_status_id">
                            <option value="">Select...</option>
                            <?php foreach ($civil_statuses as $cs): ?>
                                <option value="<?php echo $cs->id; ?>"><?php echo esc_html($cs->civil_status); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="res-form-group">
                        <label>Gender</label>
                        <select name="gender_id">
                            <option value="">Select...</option>
                            <?php foreach ($genders as $g): ?>
                                <option value="<?php echo $g->id; ?>"><?php echo esc_html($g->gender); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="res-form-group">
                        <label>TIN</label>
                        <input type="text" name="tin">
                    </div>
                    <div class="res-form-group">
                        <label>Citizenship</label>
                        <input type="text" name="citizenship">
                    </div>
                </div>
            </div>
            
            <div class="res-form-section">
                <h3>Contact Information</h3>
                <div class="res-form-grid">
                    <div class="res-form-group">
                        <label>Email</label>
                        <input type="email" name="email">
                    </div>
                    <div class="res-form-group">
                        <label>Contact Number</label>
                        <input type="text" name="contact_no">
                    </div>
                </div>
            </div>
            
            <div class="res-form-section">
                <h3>Address</h3>
                <div class="res-form-grid">
                    <div class="res-form-group full-width">
                        <label>Unit/House/Street/Village</label>
                        <input type="text" name="unit_street_village">
                    </div>
                    <div class="res-form-group">
                        <label>Barangay</label>
                        <input type="text" name="barangay">
                    </div>
                    <div class="res-form-group">
                        <label>City/Municipality</label>
                        <input type="text" name="city">
                    </div>
                    <div class="res-form-group">
                        <label>Province</label>
                        <input type="text" name="province">
                    </div>
                    <div class="res-form-group">
                        <label>ZIP Code</label>
                        <input type="text" name="zip_code">
                    </div>
                </div>
            </div>
            
            <div class="res-form-section">
                <h3>Employment Information</h3>
                <div class="res-form-grid">
                    <div class="res-form-group">
                        <label>Employer Name</label>
                        <input type="text" name="employer_name">
                    </div>
                    <div class="res-form-group">
                        <label>Occupation</label>
                        <input type="text" name="occupation">
                    </div>
                    <div class="res-form-group">
                        <label>Employment Type</label>
                        <select name="employment_type_id">
                            <option value="">Select...</option>
                            <?php foreach ($employment_types as $et): ?>
                                <option value="<?php echo $et->id; ?>"><?php echo esc_html($et->employment_type); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="res-form-group">
                        <label>Source of Sale</label>
                        <select name="source_of_sale_id">
                            <option value="">Select...</option>
                            <?php foreach ($sources as $s): ?>
                                <option value="<?php echo $s->id; ?>"><?php echo esc_html($s->source); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Secondary Client Information -->
            <div class="res-form-section" style="background-color: #f8f9fa;">
                <h3>Secondary Client Information (Spouse/Co-buyer)</h3>
                
                <div class="res-form-subsection">
                    <h4>Personal Information</h4>
                    <div class="res-form-grid">
                        <div class="res-form-group">
                            <label>Client Type</label>
                            <input type="text" name="secondary_client_type" placeholder="e.g., Spouse, Co-buyer">
                        </div>
                        <div class="res-form-group">
                            <label>First Name</label>
                            <input type="text" name="secondary_first_name">
                        </div>
                        <div class="res-form-group">
                            <label>Middle Initial</label>
                            <input type="text" name="secondary_middle_initial" maxlength="10">
                        </div>
                        <div class="res-form-group">
                            <label>Surname</label>
                            <input type="text" name="secondary_surname">
                        </div>
                        <div class="res-form-group">
                            <label>Date of Birth</label>
                            <input type="date" name="secondary_date_of_birth">
                        </div>
                        <div class="res-form-group">
                            <label>TIN</label>
                            <input type="text" name="secondary_tin">
                        </div>
                        <div class="res-form-group">
                            <label>Gender</label>
                            <select name="secondary_gender_id">
                                <option value="">Select...</option>
                                <?php foreach ($genders as $g): ?>
                                    <option value="<?php echo $g->id; ?>"><?php echo esc_html($g->gender); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="res-form-group">
                            <label>Citizenship</label>
                            <input type="text" name="secondary_citizenship">
                        </div>
                    </div>
                </div>
                
                <div class="res-form-subsection">
                    <h4>Contact Information</h4>
                    <div class="res-form-grid">
                        <div class="res-form-group">
                            <label>Email</label>
                            <input type="email" name="secondary_email">
                        </div>
                        <div class="res-form-group">
                            <label>Contact Number</label>
                            <input type="text" name="secondary_contact_no">
                        </div>
                    </div>
                </div>
                
                <div class="res-form-subsection">
                    <h4>Address</h4>
                    <div class="res-form-grid">
                        <div class="res-form-group full-width">
                            <label>Unit/House/Street/Village</label>
                            <input type="text" name="secondary_unit_street_village">
                        </div>
                        <div class="res-form-group">
                            <label>Barangay</label>
                            <input type="text" name="secondary_barangay">
                        </div>
                        <div class="res-form-group">
                            <label>City/Municipality</label>
                            <input type="text" name="secondary_city">
                        </div>
                        <div class="res-form-group">
                            <label>Province</label>
                            <input type="text" name="secondary_province">
                        </div>
                        <div class="res-form-group">
                            <label>ZIP Code</label>
                            <input type="text" name="secondary_zip_code">
                        </div>
                    </div>
                </div>
                
                <div class="res-form-subsection">
                    <h4>Employment Information</h4>
                    <div class="res-form-grid">
                        <div class="res-form-group">
                            <label>Employer Name</label>
                            <input type="text" name="secondary_employer_name">
                        </div>
                        <div class="res-form-group">
                            <label>Occupation</label>
                            <input type="text" name="secondary_occupation">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="res-form-actions">
                <button type="submit" class="button button-primary">Save Client</button>
                <button type="button" class="button" id="cancel-form">Cancel</button>
            </div>
        </form>
    </div>
    
    <!-- Clients List -->
    <div id="clients-list">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Primary Name</th>
                    <th>Secondary Name</th>
                    <th>Email</th>
                    <th>Contact No</th>
                    <th>Civil Status</th>
                    <th>Employment</th>
                    <th>Source</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clients as $client): ?>
                <tr>
                    <td><?php echo $client->client_id; ?></td>
                    <td><?php echo esc_html($client->first_name . ' ' . $client->middle_initial . ' ' . $client->surname); ?></td>
                    <td>
                        <?php 
                        if ($client->secondary_first_name || $client->secondary_surname) {
                            echo esc_html($client->secondary_first_name . ' ' . $client->secondary_middle_initial . ' ' . $client->secondary_surname);
                            if ($client->secondary_client_type) {
                                echo ' <small>(' . esc_html($client->secondary_client_type) . ')</small>';
                            }
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                    <td><?php echo esc_html($client->email); ?></td>
                    <td><?php echo esc_html($client->contact_no); ?></td>
                    <td><?php echo esc_html($client->civil_status); ?></td>
                    <td><?php echo esc_html($client->employment_type); ?></td>
                    <td><?php echo esc_html($client->source); ?></td>
                    <td>
                        <a href="#" class="edit-client" data-id="<?php echo $client->client_id; ?>">Edit</a> |
                        <a href="#" class="delete-client" data-id="<?php echo $client->client_id; ?>">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#add-new-client').click(function(e) {
        e.preventDefault();
        $('#form-title').text('Add New Client');
        $('#res-client-form')[0].reset();
        $('#client_id').val('');
        $('#client-form').slideDown();
        $('#clients-list').slideUp();
    });
    
    $('#cancel-form').click(function() {
        $('#client-form').slideUp();
        $('#clients-list').slideDown();
    });
    
    $('#res-client-form').submit(function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        formData += '&action=res_save_client&nonce=' + res_ajax.nonce;
        
        $.post(res_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                alert(response.data);
                location.reload();
            } else {
                alert('Error: ' + response.data);
            }
        });
    });
    
    $('.edit-client').click(function(e) {
        e.preventDefault();
        var clientId = $(this).data('id');
        
        $.post(res_ajax.ajax_url, {
            action: 'res_get_client',
            client_id: clientId,
            nonce: res_ajax.nonce
        }, function(response) {
            if (response.success) {
                var client = response.data;
                $('#form-title').text('Edit Client');
                $('#client_id').val(client.client_id);
                
                // Populate form fields
                $.each(client, function(key, value) {
                    $('[name="' + key + '"]').val(value);
                });
                
                $('#client-form').slideDown();
                $('#clients-list').slideUp();
            }
        });
    });
    
    $('.delete-client').click(function(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to delete this client?')) return;
        
        var clientId = $(this).data('id');
        $.post(res_ajax.ajax_url, {
            action: 'res_delete_client',
            client_id: clientId,
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