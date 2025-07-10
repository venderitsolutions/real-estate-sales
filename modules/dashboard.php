<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap">
    <h1>Real Estate Sales Dashboard</h1>
    
    <div id="res-dashboard-loading" class="notice notice-info">
        <p>Loading dashboard data...</p>
    </div>
    
    <div id="res-dashboard-content" style="display:none;">
        <!-- KPI Widgets -->
        <div class="res-widgets">
            <div class="res-widget">
                <h3>Active Net Sales</h3>
                <p class="res-amount" id="active-net">₱0.00</p>
            </div>
            <div class="res-widget">
                <h3>Active Gross Sales</h3>
                <p class="res-amount" id="active-gross">₱0.00</p>
            </div>
            <div class="res-widget">
                <h3>Cancelled Net Sales</h3>
                <p class="res-amount" id="cancelled-net">₱0.00</p>
            </div>
            <div class="res-widget">
                <h3>Cancelled Gross Sales</h3>
                <p class="res-amount" id="cancelled-gross">₱0.00</p>
            </div>
            <div class="res-widget">
                <h3>Active Accounts</h3>
                <p class="res-count" id="active-accounts">0</p>
            </div>
            <div class="res-widget">
                <h3>Cancelled Accounts</h3>
                <p class="res-count" id="cancelled-accounts">0</p>
            </div>
            <div class="res-widget">
                <h3>Complete Documents</h3>
                <p class="res-count" id="complete-docs">0</p>
            </div>
            <div class="res-widget">
                <h3>Incomplete Documents</h3>
                <p class="res-count" id="incomplete-docs">0</p>
            </div>
        </div>
        
        <!-- Charts -->
        <div class="res-charts">
            <div class="res-chart-container">
                <h3>Monthly Sales Trend</h3>
                <canvas id="monthly-trend-chart"></canvas>
            </div>
        </div>
        
        <!-- Quick Reports -->
        <div class="res-reports">
            <h2>Quick Reports</h2>
            <div class="res-report-grid">
                <div class="res-report-card">
                    <h3>Top Agents by Sales</h3>
                    <div id="top-agents-report"></div>
                </div>
                <div class="res-report-card">
                    <h3>Top Developers by Sales</h3>
                    <div id="top-developers-report"></div>
                </div>
                <div class="res-report-card">
                    <h3>Sales by Source</h3>
                    <div id="sales-source-report"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
jQuery(document).ready(function($) {
    // Load dashboard data
    $.ajax({
        url: res_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'res_get_dashboard_data',
            nonce: res_ajax.nonce
        },
        success: function(response) {
            if (response.success) {
                // Update widgets
                $('#active-net').text('₱' + response.data.widgets.active_net);
                $('#active-gross').text('₱' + response.data.widgets.active_gross);
                $('#cancelled-net').text('₱' + response.data.widgets.cancelled_net);
                $('#cancelled-gross').text('₱' + response.data.widgets.cancelled_gross);
                $('#active-accounts').text(response.data.widgets.active_accounts);
                $('#cancelled-accounts').text(response.data.widgets.cancelled_accounts);
                $('#complete-docs').text(response.data.widgets.complete_docs);
                $('#incomplete-docs').text(response.data.widgets.incomplete_docs);
                
                // Create monthly trend chart
                if (response.data.monthly_trend) {
                    createMonthlyTrendChart(response.data.monthly_trend);
                }
                
                $('#res-dashboard-loading').hide();
                $('#res-dashboard-content').fadeIn();
            }
        }
    });
    
    function createMonthlyTrendChart(data) {
        const ctx = document.getElementById('monthly-trend-chart').getContext('2d');
        const labels = data.map(item => item.month);
        const netData = data.map(item => parseFloat(item.net_sales));
        const grossData = data.map(item => parseFloat(item.gross_sales));
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Net Sales',
                    data: netData,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }, {
                    label: 'Gross Sales',
                    data: grossData,
                    borderColor: 'rgb(255, 99, 132)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>