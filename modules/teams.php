<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

// Get agents for team leader selection
$agents = $wpdb->get_results("SELECT agent_id, agent_name FROM {$wpdb->prefix}res_sales_agents WHERE status_id = 1 ORDER BY agent_name");

// Get teams list
$teams = $wpdb->get_results("
    SELECT t.*, a.agent_name as leader_name,
           (SELECT COUNT(*) FROM {$wpdb->prefix}res_sales_agents WHERE team_id = t.team_id) as member_count
    FROM {$wpdb->prefix}res_agent_teams t
    LEFT JOIN {$wpdb->prefix}res_sales_agents a ON t.team_leader = a.agent_id
    ORDER BY t.team_name
");
?>

<div class="wrap">
    <h1>Teams <a href="#" class="page-title-action" id="add-new-team">Add New</a></h1>
    
    <!-- Team Form -->
    <div id="team-form" style="display:none;">
        <h2 id="form-title">Add New Team</h2>
        <form id="res-team-form">
            <input type="hidden" id="team_id" name="team_id" value="">
            
            <div class="res-form-grid">
                <div class="res-form-group">
                    <label>Team Name*</label>
                    <input type="text" name="team_name" required>
                </div>
                <div class="res-form-group">
                    <label>Team Leader</label>
                    <select name="team_leader">
                        <option value="">Select...</option>
                        <?php foreach ($agents as $agent): ?>
                            <option value="<?php echo $agent->agent_id; ?>"><?php echo esc_html($agent->agent_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="res-form-group">
                    <label>Date Created</label>
                    <input type="date" name="date_created" value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            
            <div class="res-form-actions">
                <button type="submit" class="button button-primary">Save Team</button>
                <button type="button" class="button" id="cancel-form">Cancel</button>
            </div>
        </form>
    </div>
    
    <!-- Teams List -->
    <div id="teams-list">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Team Name</th>
                    <th>Team Leader</th>
                    <th>Members</th>
                    <th>Date Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($teams as $team): ?>
                <tr>
                    <td><?php echo $team->team_id; ?></td>
                    <td><?php echo esc_html($team->team_name); ?></td>
                    <td><?php echo esc_html($team->leader_name); ?></td>
                    <td><?php echo $team->member_count; ?></td>
                    <td><?php echo $team->date_created; ?></td>
                    <td>
                        <a href="#" class="edit-team" data-id="<?php echo $team->team_id; ?>">Edit</a> |
                        <a href="#" class="delete-team" data-id="<?php echo $team->team_id; ?>">Delete</a> |
                        <a href="#" class="view-members" data-id="<?php echo $team->team_id; ?>" data-name="<?php echo esc_attr($team->team_name); ?>">View Members</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Team Members Modal -->
    <div id="team-members-modal" style="display:none;">
        <div class="modal-content">
            <h3 id="modal-title"></h3>
            <div id="team-members-list"></div>
            <button type="button" class="button" onclick="jQuery('#team-members-modal').hide();">Close</button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#add-new-team').click(function(e) {
        e.preventDefault();
        $('#form-title').text('Add New Team');
        $('#res-team-form')[0].reset();
        $('#team_id').val('');
        $('#team-form').slideDown();
        $('#teams-list').slideUp();
    });
    
    $('#cancel-form').click(function() {
        $('#team-form').slideUp();
        $('#teams-list').slideDown();
    });
    
    $('#res-team-form').submit(function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        formData += '&action=res_save_team&nonce=' + res_ajax.nonce;
        
        $.post(res_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                alert(response.data);
                location.reload();
            }
        });
    });
    
    $('.edit-team').click(function(e) {
        e.preventDefault();
        var teamId = $(this).data('id');
        
        $.post(res_ajax.ajax_url, {
            action: 'res_get_team',
            team_id: teamId,
            nonce: res_ajax.nonce
        }, function(response) {
            if (response.success) {
                var team = response.data;
                $('#form-title').text('Edit Team');
                $('#team_id').val(team.team_id);
                $('[name="team_name"]').val(team.team_name);
                $('[name="team_leader"]').val(team.team_leader);
                $('[name="date_created"]').val(team.date_created);
                
                $('#team-form').slideDown();
                $('#teams-list').slideUp();
            }
        });
    });
    
    $('.delete-team').click(function(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to delete this team?')) return;
        
        var teamId = $(this).data('id');
        $.post(res_ajax.ajax_url, {
            action: 'res_delete_team',
            team_id: teamId,
            nonce: res_ajax.nonce
        }, function(response) {
            if (response.success) {
                alert(response.data);
                location.reload();
            }
        });
    });
    
    $('.view-members').click(function(e) {
        e.preventDefault();
        var teamId = $(this).data('id');
        var teamName = $(this).data('name');
        
        $('#modal-title').text('Members of ' + teamName);
        
        $.post(res_ajax.ajax_url, {
            action: 'res_get_team_members',
            team_id: teamId,
            nonce: res_ajax.nonce
        }, function(response) {
            if (response.success && response.data.length > 0) {
                var html = '<ul>';
                $.each(response.data, function(i, member) {
                    html += '<li>' + member.agent_name + ' - ' + member.position + '</li>';
                });
                html += '</ul>';
                $('#team-members-list').html(html);
            } else {
                $('#team-members-list').html('<p>No members found.</p>');
            }
            $('#team-members-modal').show();
        });
    });
});
</script>