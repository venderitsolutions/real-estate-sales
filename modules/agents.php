<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

// Get reference data
$agent_statuses = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}res_ref_agent_status");
$positions = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}res_agent_positions ORDER BY position_no");
$teams = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}res_agent_teams");

// Get WordPress users that are not already linked to agents
$existing_user_ids = $wpdb->get_col("SELECT wp_user_id FROM {$wpdb->prefix}res_sales_agents WHERE wp_user_id IS NOT NULL");
$user_query_args = array(
    'exclude' => $existing_user_ids,
    'orderby' => 'display_name'
);
$wp_users = get_users($user_query_args);

// Get agents list with WordPress user info
$agents = $wpdb->get_results("
    SELECT a.*, p.position, t.team_name, s.agent_status, u.display_name as wp_user_name, u.user_email
    FROM {$wpdb->prefix}res_sales_agents a
    LEFT JOIN {$wpdb->prefix}res_agent_positions p ON a.position_code = p.position_code
    LEFT JOIN {$wpdb->prefix}res_agent_teams t ON a.team_id = t.team_id
    LEFT JOIN {$wpdb->prefix}res_ref_agent_status s ON a.status_id = s.id
    LEFT JOIN {$wpdb->prefix}users u ON a.wp_user_id = u.ID
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
            
            <div class="res-form-section">
                <h3>WordPress User Integration</h3>
                <div class="res-form-grid">
                    <div class="res-form-group">
                        <label>Link to WordPress User</label>
                        <select name="wp_user_id" id="wp_user_select">
                            <option value="">Select WordPress User (Optional)...</option>
                            <?php foreach ($wp_users as $user): ?>
                                <option value="<?php echo $user->ID; ?>" data-email="<?php echo esc_attr($user->user_email); ?>" data-name="<?php echo esc_attr($user->display_name); ?>">
                                    <?php echo esc_html($user->display_name . ' (' . $user->user_email . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Link this agent to a WordPress user account for frontend dashboard access</p>
                    </div>
                    <div class="res-form-group">
                        <label>Auto-fill from WordPress User</label>
                        <button type="button" class="button" id="autofill-from-user">Auto-fill Name & Email</button>
                        <p class="description">Automatically populate agent name and email from selected WordPress user</p>
                    </div>
                </div>
            </div>
            
            <div class="res-form-section">
                <h3>Agent Information</h3>
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
                    <div class="res-form-group">
                        <label>Bank Account</label>
                        <input type="text" name="bank_account" placeholder="e.g., BPI 0983231942">
                        <p class="description">Bank account for commission deposits</p>
                    </div>
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
                    <th>WordPress User</th>
                    <th>Position</th>
                    <th>Team</th>
                    <th>Status</th>
                    <th>Date Hired</th>
                    <th>Commission Rate</th>
                    <th>Bank Account</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($agents as $agent): ?>
                <tr>
                    <td><?php echo $agent->agent_id; ?></td>
                    <td><?php echo esc_html($agent->agent_code); ?></td>
                    <td><?php echo esc_html($agent->agent_name); ?></td>
                    <td>
                        <?php if ($agent->wp_user_id): ?>
                            <span class="dashicons dashicons-yes" style="color: green;"></span>
                            <?php echo esc_html($agent->wp_user_name); ?>
                            <br><small><?php echo esc_html($agent->user_email); ?></small>
                        <?php else: ?>
                            <span class="dashicons dashicons-no" style="color: red;"></span>
                            Not linked
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html($agent->position); ?></td>
                    <td><?php echo esc_html($agent->team_name); ?></td>
                    <td>
                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $agent->agent_status)); ?>">
                            <?php echo esc_html($agent->agent_status); ?>
                        </span>
                    </td>
                    <td><?php echo $agent->date_hired; ?></td>
                    <td><?php echo $agent->commission_rate; ?>%</td>
                    <td><?php echo esc_html($agent->bank_account); ?></td>
                    <td>
                        <a href="#" class="edit-agent" data-id="<?php echo $agent->agent_id; ?>">Edit</a> |
                        <a href="#" class="delete-agent" data-id="<?php echo $agent->agent_id; ?>">Delete</a>
                        <?php if ($agent->wp_user_id): ?>
                            | <a href="<?php echo home_url('/agent-dashboard/'); ?>" target="_blank">View Dashboard</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Dashboard Setup Instructions -->
    <div class="res-info-box" style="margin-top: 30px; padding: 20px; background: #f0f8ff; border: 1px solid #ccc;">
        <h3>Frontend Dashboard Setup</h3>
        <p><strong>To enable the agent frontend dashboard:</strong></p>
        <ol>
            <li>Create a new page in WordPress (e.g., "Agent Dashboard")</li>
            <li>Add the shortcode <code>[res_agent_dashboard]</code> to the page content</li>
            <li>Set the page slug to "agent-dashboard" or update the dashboard link above</li>
            <li>Ensure agents have WordPress user accounts linked to access the dashboard</li>
        </ol>
        <p><strong>Dashboard Features:</strong></p>
        <ul>
            <li>Dashboard overview with statistics</li>
            <li>Client management (add, edit, view)</li>
            <li>Sales entry and tracking</li>
            <li>Commission voucher download</li>
        </ul>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Auto-fill agent information from WordPress user
    $('#autofill-from-user').click(function() {
        var selectedUser = $('#wp_user_select option:selected');
        if (selectedUser.val()) {
            var userName = selectedUser.data('name');
            var userEmail = selectedUser.data('email');
            
            if (userName) {
                $('input[name="agent_name"]').val(userName);
            }
            
            // You can add email field if needed in the future
            alert('Agent name auto-filled from WordPress user');
        } else {
            alert('Please select a WordPress user first');
        }
    });
    
    // Handle user selection change
    $('#wp_user_select').change(function() {
        var selectedUser = $(this).find('option:selected');
        if (selectedUser.val()) {
            $('#autofill-from-user').prop('disabled', false);
        } else {
            $('#autofill-from-user').prop('disabled', true);
        }
    });
    
    $('#add-new-agent').click(function(e) {
        e.preventDefault();
        $('#form-title').text('Add New Agent');
        $('#res-agent-form')[0].reset();
        $('#agent_id').val('');
        $('#autofill-from-user').prop('disabled', true);
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
                
                // Handle WordPress user selection
                if (agent.wp_user_id) {
                    $('#autofill-from-user').prop('disabled', false);
                }
                
                $('#agent-form').slideDown();
                $('#agents-list').slideUp();
            }
        });
    });
    
    $('.delete-agent').click(function(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to delete this agent? This will not delete the linked WordPress user.')) return;
        
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