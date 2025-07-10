// Real Estate Sales Admin JavaScript

jQuery(document).ready(function($) {
    // Global functions
    window.resAjax = function(action, data, callback) {
        data.action = action;
        data.nonce = res_ajax.nonce;
        
        $.post(res_ajax.ajax_url, data, function(response) {
            if (callback) callback(response);
        });
    };
    
    // Form validation
    $('form').on('submit', function(e) {
        var valid = true;
        $(this).find('[required]').each(function() {
            if (!$(this).val()) {
                $(this).css('border-color', '#dc3545');
                valid = false;
            } else {
                $(this).css('border-color', '#ddd');
            }
        });
        
        if (!valid) {
            e.preventDefault();
            alert('Please fill in all required fields');
        }
    });
    
    // Auto-save functionality
    var autoSaveTimer;
    $('input, select, textarea').on('change', function() {
        var $form = $(this).closest('form');
        if ($form.data('autosave')) {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(function() {
                $form.trigger('autosave');
            }, 2000);
        }
    });
    
    // Number formatting
    $('.res-amount, .amount').each(function() {
        var text = $(this).text();
        if (text.indexOf('₱') === -1 && !isNaN(parseFloat(text))) {
            $(this).text('₱' + parseFloat(text).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }));
        }
    });
    
    // Date picker enhancement
    $('input[type="date"]').each(function() {
        if (!$(this).val()) {
            var today = new Date().toISOString().split('T')[0];
            $(this).attr('max', today);
        }
    });
    
    // Sortable tables
    $('.wp-list-table th').click(function() {
        var table = $(this).closest('table');
        var index = $(this).index();
        var rows = table.find('tbody tr').get();
        
        rows.sort(function(a, b) {
            var A = $(a).children('td').eq(index).text().toUpperCase();
            var B = $(b).children('td').eq(index).text().toUpperCase();
            
            if (A < B) return -1;
            if (A > B) return 1;
            return 0;
        });
        
        $.each(rows, function(index, row) {
            table.children('tbody').append(row);
        });
    });
    
    // Search functionality
    $('#search-input').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('.wp-list-table tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
    
    // Bulk actions
    $('#bulk-action-selector').on('change', function() {
        var action = $(this).val();
        if (action && confirm('Are you sure you want to perform this bulk action?')) {
            var ids = [];
            $('input[name="bulk-select[]"]:checked').each(function() {
                ids.push($(this).val());
            });
            
            if (ids.length > 0) {
                resAjax('res_bulk_action', {
                    action_type: action,
                    ids: ids
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    }
                });
            }
        }
    });
    
    // Export functionality
    window.exportTableToCSV = function(filename) {
        var csv = [];
        var rows = document.querySelectorAll('.wp-list-table tr');
        
        for (var i = 0; i < rows.length; i++) {
            var row = [], cols = rows[i].querySelectorAll('td, th');
            
            for (var j = 0; j < cols.length; j++) {
                row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
            }
            
            csv.push(row.join(','));
        }
        
        downloadCSV(csv.join('\n'), filename);
    };
    
    function downloadCSV(csv, filename) {
        var csvFile = new Blob([csv], {type: 'text/csv'});
        var downloadLink = document.createElement('a');
        downloadLink.download = filename;
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = 'none';
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    }
    
    // Print functionality
    window.printContent = function(divId) {
        var content = document.getElementById(divId).innerHTML;
        var printWindow = window.open('', '', 'height=600,width=800');
        printWindow.document.write('<html><head><title>Print</title>');
        printWindow.document.write('<link rel="stylesheet" href="' + res_ajax.plugin_url + 'assets/css/admin-style.css">');
        printWindow.document.write('</head><body>');
        printWindow.document.write(content);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.print();
    };
    
    // Notification system
    window.showNotification = function(message, type) {
        var notification = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('.wrap').prepend(notification);
        
        setTimeout(function() {
            notification.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    };
    
    // Tab navigation
    $('.nav-tab').click(function(e) {
        e.preventDefault();
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        var target = $(this).data('tab');
        $('.tab-content').hide();
        $('#' + target).show();
    });
    
    // Form reset confirmation
    $('button[type="reset"]').click(function(e) {
        if (!confirm('Are you sure you want to reset this form?')) {
            e.preventDefault();
        }
    });
    
    // Tooltip initialization
    $('[title]').each(function() {
        $(this).tooltip({
            position: {
                my: 'center bottom-10',
                at: 'center top'
            }
        });
    });
});