// Create this file as: assets/js/commissions.js

jQuery(document).ready(function($) {
    var deductionCounter = 1;
    var currentVoucherId = null;
    
    // Show voucher form
    $('#generate-new-voucher, #generate-first-voucher').click(function(e) {
        e.preventDefault();
        $('#form-title').text('Generate New Commission Voucher');
        $('#res-voucher-form')[0].reset();
        $('#voucher-form').slideDown();
        $('#vouchers-list').slideUp();
        resetDeductions();
        clearCollectionsList();
        updateVoucherSummary();
    });
    
    // Cancel voucher form
    $('#cancel-voucher-form').click(function() {
        $('#voucher-form').slideUp();
        $('#vouchers-list').slideDown();
    });
    
    // Load collections when agent is selected
    $('#voucher-agent-select').change(function() {
        var agentId = $(this).val();
        if (agentId) {
            loadUnreleasedCollections(agentId);
        } else {
            clearCollectionsList();
        }
    });
    
    function loadUnreleasedCollections(agentId) {
        $('#collections-list').html('<p>Loading collections...</p>');
        
        $.post(res_ajax.ajax_url, {
            action: 'res_get_unreleased_collections',
            agent_id: agentId,
            nonce: res_ajax.nonce
        }, function(response) {
            console.log('Collections response:', response);
            
            if (response.success && response.data && response.data.length > 0) {
                var html = '<div style="margin-bottom: 15px;"><label><input type="checkbox" id="select-all-collections"> Select All</label></div>';
                html += '<table class="widefat"><thead><tr><th>Select</th><th>Date</th><th>Client</th><th>Project</th><th>Gross Commission</th><th>Net Commission</th><th>Particulars</th></tr></thead><tbody>';
                
                response.data.forEach(function(collection) {
                    var grossCommission = parseFloat(collection.gross_commission) || 0;
                    var netCommission = parseFloat(collection.net_commission) || 0;
                    var clientName = collection.client_name || 'Unknown Client';
                    var projectName = collection.project_name || 'Unknown Project';
                    var particulars = collection.particulars || '';
                    
                    html += '<tr>';
                    html += '<td><input type="checkbox" class="collection-checkbox" value="' + collection.acct_collection_id + '" data-gross="' + grossCommission + '" data-net="' + netCommission + '"></td>';
                    html += '<td>' + (collection.date_collected || '') + '</td>';
                    html += '<td>' + clientName + '</td>';
                    html += '<td>' + projectName + '</td>';
                    html += '<td>₱' + grossCommission.toLocaleString('en-US', {minimumFractionDigits: 2}) + '</td>';
                    html += '<td>₱' + netCommission.toLocaleString('en-US', {minimumFractionDigits: 2}) + '</td>';
                    html += '<td>' + particulars + '</td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
                $('#collections-list').html(html);
                
                // Bind checkbox events
                bindCollectionCheckboxes();
            } else {
                $('#collections-list').html('<p>No unreleased collections found for this agent.</p>');
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX Error loading collections:', xhr.responseText);
            $('#collections-list').html('<p>Error loading collections. Please check console for details.</p>');
        });
    }
    
    function bindCollectionCheckboxes() {
        $('#select-all-collections').change(function() {
            $('.collection-checkbox').prop('checked', $(this).is(':checked')).trigger('change');
        });
        
        $('.collection-checkbox').change(function() {
            updateVoucherSummary();
        });
    }
    
    function clearCollectionsList() {
        $('#collections-list').html('<p>Please select an agent first to load available collections.</p>');
        updateVoucherSummary();
    }
    
    // Add deduction functionality
    $('#add-deduction').click(function() {
        var html = '<div class="deduction-row" style="display: flex; gap: 10px; margin-bottom: 10px;">';
        html += '<input type="text" name="deductions[' + deductionCounter + '][description]" placeholder="Deduction description" style="flex: 2;">';
        html += '<input type="number" name="deductions[' + deductionCounter + '][amount]" placeholder="Amount" step="0.01" style="flex: 1;" class="deduction-amount">';
        html += '<button type="button" class="button remove-deduction">Remove</button>';
        html += '</div>';
        
        $('#deductions-container').append(html);
        deductionCounter++;
        
        // Bind events for new deduction
        bindDeductionEvents();
    });
    
    function bindDeductionEvents() {
        $('.remove-deduction').off('click').on('click', function() {
            $(this).closest('.deduction-row').remove();
            updateVoucherSummary();
        });
        
        $('.deduction-amount').off('input').on('input', function() {
            updateVoucherSummary();
        });
    }
    
    function resetDeductions() {
        $('#deductions-container').html('<div class="deduction-row" style="display: flex; gap: 10px; margin-bottom: 10px;"><input type="text" name="deductions[0][description]" placeholder="Deduction description" style="flex: 2;"><input type="number" name="deductions[0][amount]" placeholder="Amount" step="0.01" style="flex: 1;" class="deduction-amount"><button type="button" class="button remove-deduction">Remove</button></div>');
        deductionCounter = 1;
        bindDeductionEvents();
    }
    
    function updateVoucherSummary() {
        var totalGross = 0;
        var totalDeductions = 0;
        
        // Calculate gross from selected collections
        $('.collection-checkbox:checked').each(function() {
            totalGross += parseFloat($(this).data('gross')) || 0;
        });
        
        // Calculate deductions
        $('.deduction-amount').each(function() {
            totalDeductions += parseFloat($(this).val()) || 0;
        });
        
        var totalNet = totalGross - totalDeductions;
        
        $('#summary-gross').text('₱' + totalGross.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        $('#summary-deductions').text('₱' + totalDeductions.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        $('#summary-net').text('₱' + totalNet.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    }
    
    // Initialize deduction events
    bindDeductionEvents();
    
    // Form submission - SIMPLIFIED VERSION
    $('#res-voucher-form').submit(function(e) {
        e.preventDefault();
        
        console.log('Form submitted');
        
        // Get selected collection IDs
        var selectedCollectionIds = [];
        $('.collection-checkbox:checked').each(function() {
            selectedCollectionIds.push($(this).val());
        });
        
        if (selectedCollectionIds.length === 0) {
            alert('Please select at least one collection');
            return;
        }
        
        // Get agent ID
        var agentId = $('#voucher-agent-select').val();
        if (!agentId) {
            alert('Please select an agent');
            return;
        }
        
        // Collect deductions in simplified format
        var deductions = [];
        $('.deduction-row').each(function() {
            var description = $(this).find('input[type="text"]').val();
            var amount = $(this).find('input[type="number"]').val();
            if (description && amount && parseFloat(amount) > 0) {
                deductions.push({
                    description: description,
                    amount: parseFloat(amount)
                });
            }
        });
        
        // Show loading state
        var submitBtn = $(this).find('button[type="submit"]');
        var originalText = submitBtn.text();
        submitBtn.text('Generating...').prop('disabled', true);
        
        // Prepare simplified form data
        var formData = {
            action: 'res_generate_voucher',
            nonce: res_ajax.nonce,
            agent_id: agentId,
            collection_ids: selectedCollectionIds.join(','), // Send as comma-separated string
            voucher_date: $('input[name="voucher_date"]').val() || '<?php echo date("Y-m-d"); ?>',
            prepared_by: $('input[name="prepared_by"]').val() || 'Meriam Dacara',
            checked_by: $('input[name="checked_by"]').val() || 'Laiza Sison',
            approved_by: $('input[name="approved_by"]').val() || 'Reymart Zuniega',
            bizlink_ref: $('input[name="bizlink_ref"]').val() || ''
        };
        
        // Add deductions if any
        if (deductions.length > 0) {
            formData.deductions = deductions;
        }
        
        console.log('Sending voucher data:', formData);
        
        // Send AJAX request
        $.ajax({
            url: res_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log('Voucher response:', response);
                
                if (response.success) {
                    alert('Voucher generated successfully: ' + response.data.voucher_number);
                    location.reload();
                } else {
                    alert('Error: ' + (response.data || 'Unknown error occurred'));
                    console.error('Voucher generation failed:', response);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                alert('Error generating voucher. Please check the console for details.');
            },
            complete: function() {
                // Restore button state
                submitBtn.text(originalText).prop('disabled', false);
            }
        });
    });
    
    // View voucher details
    $('.view-voucher-details').click(function(e) {
        e.preventDefault();
        var voucherId = $(this).data('id');
        currentVoucherId = voucherId;
        
        $.post(res_ajax.ajax_url, {
            action: 'res_get_voucher_details',
            voucher_id: voucherId,
            nonce: res_ajax.nonce
        }, function(response) {
            if (response.success) {
                $('#modal-voucher-title').text('Voucher Details: ' + response.data.voucher.voucher_number);
                
                var html = '<div class="voucher-details">';
                html += '<p><strong>Agent:</strong> ' + (response.data.voucher.agent_name || 'Unknown') + '</p>';
                html += '<p><strong>Date:</strong> ' + response.data.voucher.voucher_date + '</p>';
                html += '<p><strong>Status:</strong> ' + response.data.voucher.status + '</p>';
                html += '<p><strong>Gross Amount:</strong> ₱' + parseFloat(response.data.voucher.total_gross_amount).toLocaleString() + '</p>';
                html += '<p><strong>Deductions:</strong> ₱' + parseFloat(response.data.voucher.total_deductions).toLocaleString() + '</p>';
                html += '<p><strong>Net Amount:</strong> ₱' + parseFloat(response.data.voucher.total_net_amount).toLocaleString() + '</p>';
                
                if (response.data.line_items && response.data.line_items.length > 0) {
                    html += '<h4>Commission Line Items:</h4>';
                    html += '<table class="widefat"><thead><tr><th>Client</th><th>Project</th><th>Commission %</th><th>Amount</th><th>Net Pay</th><th>Particulars</th></tr></thead><tbody>';
                    response.data.line_items.forEach(function(item) {
                        html += '<tr>';
                        html += '<td>' + (item.client_name || '') + '</td>';
                        html += '<td>' + (item.project_name || '') + '</td>';
                        html += '<td>' + parseFloat(item.commission_percentage || 0).toFixed(1) + '%</td>';
                        html += '<td>₱' + parseFloat(item.amount || 0).toLocaleString() + '</td>';
                        html += '<td>₱' + parseFloat(item.net_pay || 0).toLocaleString() + '</td>';
                        html += '<td>' + (item.particulars || '') + '</td>';
                        html += '</tr>';
                    });
                    html += '</tbody></table>';
                }
                
                if (response.data.deductions && response.data.deductions.length > 0) {
                    html += '<h4>Deductions:</h4>';
                    html += '<table class="widefat"><thead><tr><th>Description</th><th>Amount</th></tr></thead><tbody>';
                    response.data.deductions.forEach(function(deduction) {
                        html += '<tr>';
                        html += '<td>' + deduction.description + '</td>';
                        html += '<td>₱' + parseFloat(deduction.amount).toLocaleString() + '</td>';
                        html += '</tr>';
                    });
                    html += '</tbody></table>';
                }
                
                html += '</div>';
                $('#voucher-details-content').html(html);
                
                $('#modal-overlay').show();
                $('#voucher-details-modal').show();
            }
        });
    });
    
    // Download current voucher from modal
    $('#download-current-voucher').click(function() {
        if (currentVoucherId) {
            window.open(res_ajax.ajax_url + '?action=res_download_voucher&voucher_id=' + currentVoucherId + '&nonce=' + res_ajax.nonce, '_blank');
        }
    });
    
    // Close modal when clicking overlay
    $('#modal-overlay').click(function() {
        $('#voucher-details-modal').hide();
        $('#modal-overlay').hide();
    });
    
    // Delete voucher
    $('.delete-voucher').click(function(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to delete this voucher? This will also unreleased the associated collections.')) return;
        
        var voucherId = $(this).data('id');
        $.post(res_ajax.ajax_url, {
            action: 'res_delete_voucher',
            voucher_id: voucherId,
            nonce: res_ajax.nonce
        }, function(response) {
            if (response.success) {
                alert('Voucher deleted successfully');
                location.reload();
            } else {
                alert('Error: ' + (response.data || 'Unknown error'));
            }
        });
    });
});