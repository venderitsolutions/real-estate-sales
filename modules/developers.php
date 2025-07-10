<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

// Get developers list with project count
$developers = $wpdb->get_results("
    SELECT d.*, 
           (SELECT COUNT(*) FROM {$wpdb->prefix}res_projects WHERE developer_id = d.developer_id) as project_count,
           (SELECT COUNT(*) FROM {$wpdb->prefix}res_developer_collections dc 
            JOIN {$wpdb->prefix}res_projects p ON dc.project_id = p.project_id 
            WHERE p.developer_id = d.developer_id) as collection_count
    FROM {$wpdb->prefix}res_developers d
    ORDER BY d.developer_name
");
?>

<div class="wrap">
    <h1>Developers <a href="#" class="page-title-action" id="add-new-developer">Add New</a></h1>
    
    <!-- Developer Form -->
    <div id="developer-form" style="display:none;">
        <h2 id="form-title">Add New Developer</h2>
        <form id="res-developer-form">
            <input type="hidden" id="developer_id" name="developer_id" value="">
            
            <div class="res-form-grid">
                <div class="res-form-group full-width">
                    <label>Developer Name*</label>
                    <input type="text" name="developer_name" required>
                </div>
            </div>
            
            <div class="res-form-actions">
                <button type="submit" class="button button-primary">Save Developer</button>
                <button type="button" class="button" id="cancel-form">Cancel</button>
            </div>
        </form>
    </div>
    
    <!-- Developers List -->
    <div id="developers-list">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Developer Name</th>
                    <th>Projects</th>
                    <th>Collections</th>
                    <th>Date Added</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($developers as $developer): ?>
                <tr>
                    <td><?php echo $developer->developer_id; ?></td>
                    <td>
                        <strong><?php echo esc_html($developer->developer_name); ?></strong>
                    </td>
                    <td><?php echo $developer->project_count; ?></td>
                    <td><?php echo $developer->collection_count; ?></td>
                    <td><?php echo date('Y-m-d', strtotime($developer->created_at)); ?></td>
                    <td>
                        <a href="#" class="edit-developer" data-id="<?php echo $developer->developer_id; ?>">Edit</a> |
                        <a href="#" class="delete-developer" data-id="<?php echo $developer->developer_id; ?>">Delete</a> |
                        <a href="#" class="view-projects" data-id="<?php echo $developer->developer_id; ?>" data-name="<?php echo esc_attr($developer->developer_name); ?>">View Projects</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Projects Modal -->
    <div id="developer-projects-modal" style="display:none;">
        <div class="modal-content">
            <h3 id="modal-title"></h3>
            <div id="developer-projects-list"></div>
            <button type="button" class="button" onclick="jQuery('#developer-projects-modal').hide();">Close</button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#add-new-developer').click(function(e) {
        e.preventDefault();
        $('#form-title').text('Add New Developer');
        $('#res-developer-form')[0].reset();
        $('#developer_id').val('');
        $('#developer-form').slideDown();
        $('#developers-list').slideUp();
    });
    
    $('#cancel-form').click(function() {
        $('#developer-form').slideUp();
        $('#developers-list').slideDown();
    });
    
    $('#res-developer-form').submit(function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        formData += '&action=res_save_developer&nonce=' + res_ajax.nonce;
        
        $.post(res_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                alert(response.data);
                location.reload();
            }
        });
    });
    
    $('.edit-developer').click(function(e) {
        e.preventDefault();
        var developerId = $(this).data('id');
        
        $.post(res_ajax.ajax_url, {
            action: 'res_get_developer',
            developer_id: developerId,
            nonce: res_ajax.nonce
        }, function(response) {
            if (response.success) {
                var developer = response.data;
                $('#form-title').text('Edit Developer');
                $('#developer_id').val(developer.developer_id);
                $('[name="developer_name"]').val(developer.developer_name);
                
                $('#developer-form').slideDown();
                $('#developers-list').slideUp();
            }
        });
    });
    
    $('.delete-developer').click(function(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to delete this developer? This will not delete associated projects.')) return;
        
        var developerId = $(this).data('id');
        $.post(res_ajax.ajax_url, {
            action: 'res_delete_developer',
            developer_id: developerId,
            nonce: res_ajax.nonce
        }, function(response) {
            if (response.success) {
                alert(response.data);
                location.reload();
            }
        });
    });
    
    $('.view-projects').click(function(e) {
        e.preventDefault();
        var developerId = $(this).data('id');
        var developerName = $(this).data('name');
        
        $('#modal-title').text('Projects by ' + developerName);
        
        $.post(res_ajax.ajax_url, {
            action: 'res_get_developer_projects',
            developer_id: developerId,
            nonce: res_ajax.nonce
        }, function(response) {
            if (response.success && response.data.length > 0) {
                var html = '<table class="widefat"><thead><tr><th>Project Name</th><th>Date Accredited</th></tr></thead><tbody>';
                $.each(response.data, function(i, project) {
                    html += '<tr><td>' + project.project_name + '</td><td>' + project.date_accredited + '</td></tr>';
                });
                html += '</tbody></table>';
                $('#developer-projects-list').html(html);
            } else {
                $('#developer-projects-list').html('<p>No projects found for this developer.</p>');
            }
            $('#developer-projects-modal').show();
        });
    });
});
</script>