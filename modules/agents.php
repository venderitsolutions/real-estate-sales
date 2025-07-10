<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

// Get reference data
$agent_statuses = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}res_ref_agent_status");
$positions = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}res_agent_positions ORDER BY position_no");
$teams = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}res_agent_teams");

// Get agents list
$agents = $wpdb->get_results("
    SELECT a.*, p.position, t.team_name, s.agent_status
    FROM {$wpdb->prefix}res_sales_agents a
    LEFT JOIN {$wpdb->prefix}res_agent_positions p ON a.position_code = p.position_code
    LEFT JOIN {$wpdb->prefix}res_agent_teams t ON a.team_id = t.team_id
    LEFT JOIN {$wpdb->prefix}res_ref_agent_status s ON a.status_id = s.id
    ORDER BY a.agent_name
");
?>

<div class="wrap">
    <h1>Sales Agents <a href="#" class="page-title-action" id="add-new-agent">Add New</a></h1>
    
    <!-- Agent Form -->
    <div id="agent-form" style="display:none;">
        <h2 id="form-title">Add New Agent</h2>
        <form id="res-agent-form">
            <input type="hidden" id="agent_id" name="agent_id" value="">
            
            <div class="res-form-grid">
                <div class="res-form-group">
                    <label>Agent Code</label>
                    <input type="text" name="agent_code">
                </div>
                <div class="res-form-group">
                    <label>Agent Name*</label>
                    <input type="text" name="agent_name" required>
                </div>
                <div class="res-form-group">
                    <label>Date Hired</label>
                    <input type="date" name="date_hired">
                </div>
                <div class="res-form-group">
                    <label>Status</label>
                    <select name="status_id">
                        <option value="">Select...</option>
                        <?php foreach ($agent_statuses as $status): ?>
                            <option value="<?php echo $status->id; ?>"><?php echo esc_html($status->agent_status); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="res-form-group">
                    <label>Position</label>
                    <select name="position_code">
                        <option value="">Select...</option>
                        <?php foreach ($positions as $position): ?>
                            <option value="<?php echo esc_attr($position->position_code); ?>">
                                <?php echo esc_html($position->position); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="res-form-group">
                    <label>Team</label>
                    <select name="team_id">
                        <option value="">Select...</option>
                        <?php foreach ($teams as $team): ?>
                            <option value="<?php echo $team->team_id; ?>"><?php echo esc_html($team->team_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="res-form-group">
                    <label>Commission Rate (%)</label>
                    <input type="number" name="commission_rate" step="0.01" min="0" max="100">
                </div>
            </div>
            
            <div class="res-form-actions">
                <button type="submit" class="button button-primary">Save Agent</button>
                <button type="button" class="button" id="cancel-form">Cancel</button>
            </div>
        </form>
    </div>
    
    <!-- Agents List -->
    <div id="agents-list">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Position</th>
                    <th>Team</th>
                    <th>Status</th>
                    <th>Date Hired</th>
                    <th>Commission Rate</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($agents as $agent): ?>
                <tr>
                    <td><?php echo $agent->agent_id; ?></td>
                    <td><?php echo esc_html($agent->agent_code); ?></td>
                    <td><?php echo esc_html($agent->agent_name); ?></td>
                    <td><?php echo esc_html($agent->position); ?></td>
                    <td><?php echo esc_html($agent->team_name); ?></td>
                    <td><?php echo esc_html($agent->agent_status); ?></td>
                    <td><?php echo $agent->date_hired; ?></td>
                    <td><?php echo $agent->commission_rate; ?>%</td>
                    <td>
                        <a href="#" class="edit-agent" data-id="<?php echo $agent->agent_id; ?>">Edit</a> |
                        <a href="#" class="delete-agent" data-id="<?php echo $agent->agent_id; ?>">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#add-new-agent').click(function(e) {
        e.preventDefault();
        $('#form-title').text('Add New Agent');
        $('#res-agent-form')[0].reset();
        $('#agent_id').val('');
        $('#agent-form').slideDown();
        $('#agents-list').slideUp();
    });
    
    $('#cancel-form').click(function() {
        $('#agent-form').slideUp();
        $('#agents-list').slideDown();
    });
    
    $('#res-agent-form').submit(function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        formData += '&action=res_save_agent&nonce=' + res_ajax.nonce;
        
        $.post(res_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                alert(response.data);
                location.reload();
            } else {
                alert('Error: ' + response.data);
            }
        });
    });
    
    $('.edit-agent').click(function(e) {
        e.preventDefault();
        var agentId = $(this).data('id');
        
        $.post(res_ajax.ajax_url, {
            action: 'res_get_agent',
            agent_id: agentId,
            nonce: res_ajax.nonce
        }, function(response) {
            if (response.success) {
                var agent = response.data;
                $('#form-title').text('Edit Agent');
                $('#agent_id').val(agent.agent_id);
                
                $.each(agent, function(key, value) {
                    $('[name="' + key + '"]').val(value);
                });
                
                $('#agent-form').slideDown();
                $('#agents-list').slideUp();
            }
        });
    });
    
    $('.delete-agent').click(function(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to delete this agent?')) return;
        
        var agentId = $(this).data('id');
        $.post(res_ajax.ajax_url, {
            action: 'res_delete_agent',
            agent_id: agentId,
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