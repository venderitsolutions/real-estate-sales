<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

// Get developers for dropdown
$developers = $wpdb->get_results("SELECT developer_id, developer_name FROM {$wpdb->prefix}res_developers ORDER BY developer_name");

// Get projects list
$projects = $wpdb->get_results("
    SELECT p.*, d.developer_name,
           (SELECT COUNT(*) FROM {$wpdb->prefix}res_residential_sales WHERE project_id = p.project_id) as sales_count,
           (SELECT SUM(net_tcp) FROM {$wpdb->prefix}res_residential_sales WHERE project_id = p.project_id AND status_id = 1) as total_sales
    FROM {$wpdb->prefix}res_projects p
    LEFT JOIN {$wpdb->prefix}res_developers d ON p.developer_id = d.developer_id
    ORDER BY p.project_name
");
?>

<div class="wrap">
    <h1>Projects <a href="#" class="page-title-action" id="add-new-project">Add New</a></h1>
    
    <!-- Project Form -->
    <div id="project-form" style="display:none;">
        <h2 id="form-title">Add New Project</h2>
        <form id="res-project-form">
            <input type="hidden" id="project_id" name="project_id" value="">
            
            <div class="res-form-grid">
                <div class="res-form-group">
                    <label>Project Name*</label>
                    <input type="text" name="project_name" required>
                </div>
                <div class="res-form-group">
                    <label>Developer*</label>
                    <select name="developer_id" required>
                        <option value="">Select Developer...</option>
                        <?php foreach ($developers as $developer): ?>
                            <option value="<?php echo $developer->developer_id; ?>"><?php echo esc_html($developer->developer_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="res-form-group">
                    <label>Date Accredited</label>
                    <input type="date" name="date_accredited">
                </div>
            </div>
            
            <div class="res-form-actions">
                <button type="submit" class="button button-primary">Save Project</button>
                <button type="button" class="button" id="cancel-form">Cancel</button>
            </div>
        </form>
    </div>
    
    <!-- Projects List -->
    <div id="projects-list">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Project Name</th>
                    <th>Developer</th>
                    <th>Date Accredited</th>
                    <th>Sales Count</th>
                    <th>Total Sales</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($projects as $project): ?>
                <tr>
                    <td><?php echo $project->project_id; ?></td>
                    <td>
                        <strong><?php echo esc_html($project->project_name); ?></strong>
                    </td>
                    <td><?php echo esc_html($project->developer_name); ?></td>
                    <td><?php echo $project->date_accredited; ?></td>
                    <td><?php echo $project->sales_count; ?></td>
                    <td>₱<?php echo number_format($project->total_sales ?: 0, 2); ?></td>
                    <td>
                        <a href="#" class="edit-project" data-id="<?php echo $project->project_id; ?>">Edit</a> |
                        <a href="#" class="delete-project" data-id="<?php echo $project->project_id; ?>">Delete</a> |
                        <a href="#" class="view-sales" data-id="<?php echo $project->project_id; ?>" data-name="<?php echo esc_attr($project->project_name); ?>">View Sales</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Sales Modal -->
    <div id="project-sales-modal" style="display:none;">
        <div class="modal-content">
            <h3 id="modal-title"></h3>
            <div id="project-sales-list"></div>
            <button type="button" class="button" onclick="jQuery('#project-sales-modal').hide();">Close</button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#add-new-project').click(function(e) {
        e.preventDefault();
        $('#form-title').text('Add New Project');
        $('#res-project-form')[0].reset();
        $('#project_id').val('');
        $('#project-form').slideDown();
        $('#projects-list').slideUp();
    });
    
    $('#cancel-form').click(function() {
        $('#project-form').slideUp();
        $('#projects-list').slideDown();
    });
    
    $('#res-project-form').submit(function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        formData += '&action=res_save_project&nonce=' + res_ajax.nonce;
        
        $.post(res_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                alert(response.data);
                location.reload();
            }
        });
    });
    
    $('.edit-project').click(function(e) {
        e.preventDefault();
        var projectId = $(this).data('id');
        
        $.post(res_ajax.ajax_url, {
            action: 'res_get_project',
            project_id: projectId,
            nonce: res_ajax.nonce
        }, function(response) {
            if (response.success) {
                var project = response.data;
                $('#form-title').text('Edit Project');
                $('#project_id').val(project.project_id);
                $('[name="project_name"]').val(project.project_name);
                $('[name="developer_id"]').val(project.developer_id);
                $('[name="date_accredited"]').val(project.date_accredited);
                
                $('#project-form').slideDown();
                $('#projects-list').slideUp();
            }
        });
    });
    
    $('.delete-project').click(function(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to delete this project? This will not delete associated sales.')) return;
        
        var projectId = $(this).data('id');
        $.post(res_ajax.ajax_url, {
            action: 'res_delete_project',
            project_id: projectId,
            nonce: res_ajax.nonce
        }, function(response) {
            if (response.success) {
                alert(response.data);
                location.reload();
            }
        });
    });
    
    $('.view-sales').click(function(e) {
        e.preventDefault();
        var projectId = $(this).data('id');
        var projectName = $(this).data('name');
        
        $('#modal-title').text('Sales for ' + projectName);
        
        $.post(res_ajax.ajax_url, {
            action: 'res_get_project_sales',
            project_id: projectId,
            nonce: res_ajax.nonce
        }, function(response) {
            if (response.success && response.data.length > 0) {
                var html = '<table class="widefat"><thead><tr><th>Client</th><th>Agent</th><th>Unit/Block/Lot</th><th>Net TCP</th><th>Status</th></tr></thead><tbody>';
                $.each(response.data, function(i, sale) {
                    html += '<tr>';
                    html += '<td>' + sale.client_name + '</td>';
                    html += '<td>' + sale.agent_name + '</td>';
                    html += '<td>' + (sale.unit || '') + ' ' + (sale.block || '') + ' ' + (sale.lot || '') + '</td>';
                    html += '<td>₱' + parseFloat(sale.net_tcp).toLocaleString('en-US', {minimumFractionDigits: 2}) + '</td>';
                    html += '<td>' + sale.status + '</td>';
                    html += '</tr>';
                });
                html += '</tbody></table>';
                $('#project-sales-list').html(html);
            } else {
                $('#project-sales-list').html('<p>No sales found for this project.</p>');
            }
            $('#project-sales-modal').show();
        });
    });
});
</script>