<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

// Get positions list ordered by position number
$positions = $wpdb->get_results("
    SELECT p.*, 
           (SELECT COUNT(*) FROM {$wpdb->prefix}res_sales_agents WHERE position_code = p.position_code) as agent_count
    FROM {$wpdb->prefix}res_agent_positions p
    ORDER BY p.position_no
");
?>

<div class="wrap">
    <h1>Agent Positions <a href="#" class="page-title-action" id="add-new-position">Add New</a></h1>
    
    <p class="description">Positions are hierarchical based on position number. Position 1 is the highest, position 2 reports to position 1, and so on.</p>
    
    <!-- Position Form -->
    <div id="position-form" style="display:none;">
        <h2 id="form-title">Add New Position</h2>
        <form id="res-position-form">
            <input type="hidden" id="position_id" name="id" value="">
            
            <div class="res-form-grid">
                <div class="res-form-group">
                    <label>Position Number*</label>
                    <input type="number" name="position_no" min="1" required>
                    <p class="description">Lower numbers are higher in hierarchy</p>
                </div>
                <div class="res-form-group">
                    <label>Position Code*</label>
                    <input type="text" name="position_code" required>
                    <p class="description">Unique identifier (e.g., CEO, MGR, AGT)</p>
                </div>
                <div class="res-form-group">
                    <label>Position Title*</label>
                    <input type="text" name="position" required>
                    <p class="description">Display name of the position</p>
                </div>
            </div>
            
            <div class="res-form-actions">
                <button type="submit" class="button button-primary">Save Position</button>
                <button type="button" class="button" id="cancel-form">Cancel</button>
            </div>
        </form>
    </div>
    
    <!-- Positions List -->
    <div id="positions-list">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 80px;">Hierarchy</th>
                    <th>Position Code</th>
                    <th>Position Title</th>
                    <th>Number of Agents</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($positions as $position): ?>
                <tr>
                    <td style="text-align: center;">
                        <strong><?php echo $position->position_no; ?></strong>
                    </td>
                    <td><?php echo esc_html($position->position_code); ?></td>
                    <td>
                        <?php 
                        // Add indentation based on position number
                        echo str_repeat('— ', $position->position_no - 1) . esc_html($position->position); 
                        ?>
                    </td>
                    <td><?php echo $position->agent_count; ?></td>
                    <td>
                        <a href="#" class="edit-position" data-id="<?php echo $position->id; ?>">Edit</a> |
                        <a href="#" class="delete-position" data-id="<?php echo $position->id; ?>">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Visual Hierarchy -->
    <div class="res-hierarchy-view">
        <h2>Position Hierarchy</h2>
        <div class="hierarchy-tree">
            <?php
            foreach ($positions as $position) {
                echo '<div class="hierarchy-level" style="margin-left: ' . (($position->position_no - 1) * 30) . 'px;">';
                echo '<div class="position-box">';
                echo '<strong>' . esc_html($position->position) . '</strong><br>';
                echo '<small>Level ' . $position->position_no . ' - ' . $position->agent_count . ' agents</small>';
                echo '</div>';
                echo '</div>';
            }
            ?>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#add-new-position').click(function(e) {
        e.preventDefault();
        $('#form-title').text('Add New Position');
        $('#res-position-form')[0].reset();
        $('#position_id').val('');
        $('#position-form').slideDown();
        $('#positions-list').slideUp();
    });
    
    $('#cancel-form').click(function() {
        $('#position-form').slideUp();
        $('#positions-list').slideDown();
    });
    
    $('#res-position-form').submit(function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        formData += '&action=res_save_position&nonce=' + res_ajax.nonce;
        
        $.post(res_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                alert(response.data);
                location.reload();
            } else {
                alert('Error: ' + response.data);
            }
        });
    });
    
    $('.edit-position').click(function(e) {
        e.preventDefault();
        var positionId = $(this).data('id');
        
        $.post(res_ajax.ajax_url, {
            action: 'res_get_position',
            position_id: positionId,
            nonce: res_ajax.nonce
        }, function(response) {
            if (response.success) {
                var position = response.data;
                $('#form-title').text('Edit Position');
                $('#position_id').val(position.id);
                $('[name="position_no"]').val(position.position_no);
                $('[name="position_code"]').val(position.position_code);
                $('[name="position"]').val(position.position);
                
                $('#position-form').slideDown();
                $('#positions-list').slideUp();
            }
        });
    });
    
    $('.delete-position').click(function(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to delete this position? This will not delete agents assigned to this position.')) return;
        
        var positionId = $(this).data('id');
        $.post(res_ajax.ajax_url, {
            action: 'res_delete_position',
            position_id: positionId,
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