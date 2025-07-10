<?php
if (!defined('ABSPATH')) exit;
global $wpdb;
?>

<div class="wrap">
    <h1>Reports</h1>
    
    <div class="res-reports-container">
        <!-- Report Filters -->
        <div class="res-report-filters">
            <h3>Report Filters</h3>
            <form id="report-filters">
                <div class="filter-row">
                    <label>Date Range:</label>
                    <input type="date" id="date-from" name="date_from">
                    <span> to </span>
                    <input type="date" id="date-to" name="date_to">
                </div>
            </form>
        </div>
        
        <!-- Report Types -->
        <div class="res-report-types">
            <h2>Available Reports</h2>
            
            <div class="report-cards">
                <!-- Agent Sales Report -->
                <div class="report-card">
                    <h3>Agent Sales Report</h3>
                    <p>View sales performance by agent including net and gross sales</p>
                    <button class="button button-primary" onclick="generateReport('agent_sales')">Generate Report</button>
                </div>
                
                <!-- Developer Sales Report -->
                <div class="report-card">
                    <h3>Developer Sales Report</h3>
                    <p>Sales summary grouped by developer</p>
                    <button class="button button-primary" onclick="generateReport('developer_sales')">Generate Report</button>
                </div>
                
                <!-- Team Sales Report -->
                <div class="report-card">
                    <h3>Team Sales Report</h3>
                    <p>Performance analysis by sales teams</p>
                    <button class="button button-primary" onclick="generateReport('team_sales')">Generate Report</button>
                </div>
                
                <!-- Source of Sales Report -->
                <div class="report-card">
                    <h3>Source of Sales Report</h3>
                    <p>Analysis of sales sources and their effectiveness</p>
                    <button class="button button-primary" onclick="generateReport('source_sales')">Generate Report</button>
                </div>
                
                <!-- Commission Summary Report -->
                <div class="report-card">
                    <h3>Commission Summary Report</h3>
                    <p>Total commissions released by agent and period</p>
                    <button class="button button-primary" onclick="generateReport('commission_summary')">Generate Report</button>
                </div>
                
                <!-- Collection Report -->
                <div class="report-card">
                    <h3>Collection Report</h3>
                    <p>Summary of all collections from developers</p>
                    <button class="button button-primary" onclick="generateReport('collection_summary')">Generate Report</button>
                </div>
                
                <!-- Document Status Report -->
                <div class="report-card">
                    <h3>Document Status Report</h3>
                    <p>Overview of complete vs incomplete documentation</p>
                    <button class="button button-primary" onclick="generateReport('document_status')">Generate Report</button>
                </div>
                
                <!-- Cash Advance Report -->
                <div class="report-card">
                    <h3>Cash Advance Report</h3>
                    <p>Summary of agent cash advances</p>
                    <button class="button button-primary" onclick="generateReport('cash_advance')">Generate Report</button>
                </div>
            </div>
        </div>
        
        <!-- Report Results -->
        <div id="report-results" style="display:none;">
            <h2 id="report-title"></h2>
            <div class="report-actions">
                <button class="button" onclick="printReport()">Print</button>
                <button class="button" onclick="exportReport()">Export to CSV</button>
            </div>
            <div id="report-content"></div>
        </div>
    </div>
</div>

<script>
function generateReport(reportType) {
    var dateFrom = jQuery('#date-from').val();
    var dateTo = jQuery('#date-to').val();
    
    jQuery('#report-results').hide();
    
    jQuery.post(res_ajax.ajax_url, {
        action: 'res_generate_report',
        report_type: reportType,
        date_from: dateFrom,
        date_to: dateTo,
        nonce: res_ajax.nonce
    }, function(response) {
        if (response.success) {
            displayReport(reportType, response.data);
        } else {
            alert('Error generating report');
        }
    });
}

function displayReport(reportType, data) {
    var titles = {
        'agent_sales': 'Agent Sales Report',
        'developer_sales': 'Developer Sales Report',
        'team_sales': 'Team Sales Report',
        'source_sales': 'Source of Sales Report',
        'commission_summary': 'Commission Summary Report',
        'collection_summary': 'Collection Report',
        'document_status': 'Document Status Report',
        'cash_advance': 'Cash Advance Report'
    };
    
    jQuery('#report-title').text(titles[reportType]);
    jQuery('#report-content').html(data);
    jQuery('#report-results').show();
}

function printReport() {
    var content = document.getElementById('report-content').innerHTML;
    var printWindow = window.open('', '', 'height=600,width=800');
    printWindow.document.write('<html><head><title>Report</title>');
    printWindow.document.write('<style>table {border-collapse: collapse; width: 100%;} th, td {border: 1px solid #ddd; padding: 8px; text-align: left;}</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(content);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}

function exportReport() {
    // Implementation for CSV export
    var table = jQuery('#report-content table');
    if (table.length) {
        var csv = [];
        var rows = table.find('tr');
        
        rows.each(function() {
            var row = [];
            jQuery(this).find('th, td').each(function() {
                row.push('"' + jQuery(this).text().replace(/"/g, '""') + '"');
            });
            csv.push(row.join(','));
        });
        
        var csvContent = csv.join('\n');
        var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'report.csv';
        link.click();
    }
}
</script>