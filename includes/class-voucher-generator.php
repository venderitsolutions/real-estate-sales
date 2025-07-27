<?php
class RES_Voucher_Generator {
    
    public function __construct() {
        add_action('wp_ajax_res_generate_voucher', array($this, 'generate_voucher'));
        add_action('wp_ajax_res_download_voucher', array($this, 'download_voucher'));
        add_action('wp_ajax_nopriv_res_download_voucher', array($this, 'download_voucher'));
        add_action('wp_ajax_res_get_unreleased_collections', array($this, 'get_unreleased_collections'));
    }
    
    public function generate_voucher() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        
        global $wpdb;
        
        $agent_id = intval($_POST['agent_id']);
        $collection_ids = array_map('intval', $_POST['collection_ids']);
        $deductions = isset($_POST['deductions']) ? $_POST['deductions'] : array();
        $voucher_date = sanitize_text_field($_POST['voucher_date']);
        $prepared_by = sanitize_text_field($_POST['prepared_by']);
        $checked_by = sanitize_text_field($_POST['checked_by']);
        $approved_by = sanitize_text_field($_POST['approved_by']);
        $bizlink_ref = sanitize_text_field($_POST['bizlink_ref']);
        
        if (empty($collection_ids)) {
            wp_send_json_error('No collections selected');
        }
        
        // Generate voucher number
        $voucher_number = $this->generate_voucher_number();
        
        // Calculate totals
        $total_gross = 0;
        $total_deductions = 0;
        $collections = array();
        
        foreach ($collection_ids as $collection_id) {
            $collection = $wpdb->get_row($wpdb->prepare(
                "SELECT ac.*, CONCAT(c.first_name, ' ', c.surname) as client_name, p.project_name
                 FROM {$wpdb->prefix}res_account_collections ac
                 LEFT JOIN {$wpdb->prefix}res_clients c ON ac.client_id = c.client_id
                 LEFT JOIN {$wpdb->prefix}res_projects p ON ac.project_id = p.project_id
                 WHERE ac.acct_collection_id = %d AND ac.agent_id = %d AND ac.is_released = 0",
                $collection_id, $agent_id
            ));
            
            if ($collection) {
                $collections[] = $collection;
                $total_gross += $collection->gross_commission;
            }
        }
        
        // Calculate deductions total
        foreach ($deductions as $deduction) {
            $total_deductions += floatval($deduction['amount']);
        }
        
        $total_net = $total_gross - $total_deductions;
        
        // Create voucher record
        $voucher_data = array(
            'voucher_number' => $voucher_number,
            'payee_agent_id' => $agent_id,
            'voucher_date' => $voucher_date,
            'total_gross_amount' => $total_gross,
            'total_deductions' => $total_deductions,
            'total_net_amount' => $total_net,
            'prepared_by' => $prepared_by,
            'checked_by' => $checked_by,
            'approved_by' => $approved_by,
            'bizlink_ref' => $bizlink_ref,
            'status' => 'approved'
        );
        
        $voucher_result = $wpdb->insert($wpdb->prefix . 'res_commission_vouchers', $voucher_data);
        
        if ($voucher_result === false) {
            wp_send_json_error('Failed to create voucher');
        }
        
        $voucher_id = $wpdb->insert_id;
        
        // Create line items
        $line_order = 1;
        foreach ($collections as $collection) {
            $line_item_data = array(
                'voucher_id' => $voucher_id,
                'collection_id' => $collection->acct_collection_id,
                'commission_percentage' => $collection->commission_percentage,
                'client_name' => $collection->client_name,
                'project_name' => $collection->project_name,
                'amount' => $collection->gross_commission,
                'incremental_cost' => $collection->gross_commission * ($collection->commission_percentage / 100),
                'net_pay' => $collection->net_commission,
                'particulars' => $collection->particulars,
                'line_order' => $line_order++
            );
            
            $wpdb->insert($wpdb->prefix . 'res_voucher_line_items', $line_item_data);
            
            // Mark collection as released
            $wpdb->update(
                $wpdb->prefix . 'res_account_collections',
                array('is_released' => 1, 'voucher_id' => $voucher_id),
                array('acct_collection_id' => $collection->acct_collection_id)
            );
        }
        
        // Create deductions
        foreach ($deductions as $deduction) {
            if (!empty($deduction['description']) && floatval($deduction['amount']) > 0) {
                $deduction_data = array(
                    'voucher_id' => $voucher_id,
                    'description' => sanitize_text_field($deduction['description']),
                    'amount' => floatval($deduction['amount'])
                );
                
                $wpdb->insert($wpdb->prefix . 'res_voucher_deductions', $deduction_data);
            }
        }
        
        wp_send_json_success(array(
            'voucher_id' => $voucher_id,
            'voucher_number' => $voucher_number,
            'message' => 'Voucher generated successfully'
        ));
    }
    
    public function download_voucher() {
        if (!isset($_GET['voucher_id']) || !isset($_GET['nonce'])) {
            wp_die('Invalid request');
        }
        
        if (!wp_verify_nonce($_GET['nonce'], 'res_ajax_nonce') && !wp_verify_nonce($_GET['nonce'], 'res_frontend_nonce')) {
            wp_die('Security check failed');
        }
        
        $voucher_id = intval($_GET['voucher_id']);
        $voucher_html = $this->generate_voucher_html($voucher_id);
        
        if (!$voucher_html) {
            wp_die('Voucher not found');
        }
        
        // Set headers for PDF download
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: inline; filename="commission-voucher-' . $voucher_id . '.html"');
        
        echo $voucher_html;
        exit;
    }
    
    private function generate_voucher_html($voucher_id) {
        global $wpdb;
        
        // Get voucher data
        $voucher = $wpdb->get_row($wpdb->prepare(
            "SELECT v.*, a.agent_name, a.bank_account
             FROM {$wpdb->prefix}res_commission_vouchers v
             LEFT JOIN {$wpdb->prefix}res_sales_agents a ON v.payee_agent_id = a.agent_id
             WHERE v.voucher_id = %d",
            $voucher_id
        ));
        
        if (!$voucher) {
            return false;
        }
        
        // Get line items
        $line_items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}res_voucher_line_items 
             WHERE voucher_id = %d 
             ORDER BY line_order",
            $voucher_id
        ));
        
        // Get deductions
        $deductions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}res_voucher_deductions 
             WHERE voucher_id = %d",
            $voucher_id
        ));
        
        // Generate HTML
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Commission Voucher - <?php echo esc_html($voucher->voucher_number); ?></title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                    font-size: 12px;
                }
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                }
                .company-info {
                    text-align: left;
                    margin-bottom: 20px;
                }
                .voucher-info {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 20px;
                }
                .voucher-title {
                    text-align: center;
                    font-size: 18px;
                    font-weight: bold;
                    margin: 20px 0;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                }
                th, td {
                    border: 1px solid #000;
                    padding: 8px;
                    text-align: left;
                    font-size: 11px;
                }
                th {
                    background-color: #f0f0f0;
                    font-weight: bold;
                }
                .amount {
                    text-align: right;
                }
                .total-row {
                    font-weight: bold;
                    background-color: #f0f0f0;
                }
                .signatures {
                    display: flex;
                    justify-content: space-between;
                    margin-top: 40px;
                }
                .signature-block {
                    text-align: center;
                    width: 30%;
                }
                .signature-line {
                    border-bottom: 1px solid #000;
                    margin: 20px 0 5px 0;
                    height: 30px;
                }
                .bank-info {
                    margin-top: 20px;
                    text-align: center;
                    font-weight: bold;
                }
                .deductions-section {
                    margin-top: 20px;
                }
                .deductions-table {
                    background-color: #ffeaa7;
                }
                .grand-total {
                    font-size: 14px;
                    font-weight: bold;
                    text-align: right;
                    margin-top: 10px;
                }
            </style>
        </head>
        <body>
            <div class="company-info">
                <strong>JCRZ REALTY</strong><br>
                Manabat St. Brgy San Antonio City of Biñan Laguna 4024<br>
                TIN: 438-822-930-000<br>
                Business Style: Real Estate
            </div>
            
            <div class="voucher-info">
                <div>
                    <strong>VOUCHER NO:</strong> <?php echo esc_html($voucher->voucher_number); ?><br>
                    <strong>DATE:</strong> <?php echo date('F j, Y', strtotime($voucher->voucher_date)); ?><br>
                    <strong>BPI BANK ACCT#:</strong> <?php echo esc_html($voucher->bank_account ?: '0983231942'); ?>
                </div>
                <div>
                    <strong>Bizlink Ref#:</strong> <?php echo esc_html($voucher->bizlink_ref); ?>
                </div>
            </div>
            
            <div class="voucher-title">COMMISSION VOUCHER</div>
            
            <div style="margin-bottom: 20px;">
                <strong>Payee:</strong> <?php echo esc_html($voucher->agent_name); ?><br>
                <strong>PRIMEMITIVE</strong><br>
                <strong>Particulars:</strong> COMMISSION / CASH GIFT
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>COMM %</th>
                        <th>CLIENT NAME</th>
                        <th>PROJECT</th>
                        <th>AMOUNT</th>
                        <th>INCREMENTAL COST</th>
                        <th>NET PAY</th>
                        <th>PARTICULARS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($line_items as $item): ?>
                    <tr>
                        <td><?php echo number_format($item->commission_percentage, 1); ?>%</td>
                        <td><?php echo esc_html($item->client_name); ?></td>
                        <td><?php echo esc_html($item->project_name); ?></td>
                        <td class="amount"><?php echo number_format($item->amount, 2); ?></td>
                        <td class="amount"><?php echo number_format($item->incremental_cost, 2); ?></td>
                        <td class="amount"><?php echo number_format($item->net_pay, 2); ?></td>
                        <td><?php echo esc_html($item->particulars); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <tr class="total-row">
                        <td colspan="3"><strong>TOTAL:</strong></td>
                        <td class="amount"><strong><?php echo number_format($voucher->total_gross_amount, 2); ?></strong></td>
                        <td class="amount"><strong><?php echo number_format(array_sum(array_column($line_items, 'incremental_cost')), 2); ?></strong></td>
                        <td class="amount"><strong><?php echo number_format(array_sum(array_column($line_items, 'net_pay')), 2); ?></strong></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
            
            <?php if (!empty($deductions)): ?>
            <div class="deductions-section">
                <table class="deductions-table">
                    <thead>
                        <tr>
                            <th colspan="2" style="text-align: center; background-color: #fdcb6e;">DEDUCTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deductions as $deduction): ?>
                        <tr>
                            <td><?php echo esc_html($deduction->description); ?></td>
                            <td class="amount"><?php echo number_format($deduction->amount, 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <div class="grand-total">
                <strong>GRAND TOTAL: <?php echo number_format($voucher->total_net_amount, 2); ?></strong>
            </div>
            
            <div class="bank-info">
                DIRECT DEPOSIT TO BPI ACCOUNT OF PAYEE
            </div>
            
            <div class="signatures">
                <div class="signature-block">
                    <div class="signature-line"></div>
                    <div>Prepared by:</div>
                    <div><strong><?php echo esc_html($voucher->prepared_by); ?></strong></div>
                </div>
                <div class="signature-block">
                    <div class="signature-line"></div>
                    <div>Checked by:</div>
                    <div><strong><?php echo esc_html($voucher->checked_by); ?></strong></div>
                </div>
                <div class="signature-block">
                    <div class="signature-line"></div>
                    <div>Approved by:</div>
                    <div><strong><?php echo esc_html($voucher->approved_by); ?></strong></div>
                    <div>Signature over Printed Name</div>
                    <div>Date: _______________</div>
                </div>
            </div>
            
            <div style="margin-top: 30px; font-style: italic; text-align: center;">
                **Non-issuance of Official Receipt, No release of commission
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    public function get_unreleased_collections() {
        check_ajax_referer('res_ajax_nonce', 'nonce');
        
        global $wpdb;
        $agent_id = intval($_POST['agent_id']);
        
        $collections = $wpdb->get_results($wpdb->prepare(
            "SELECT ac.*, 
                    CONCAT(c.first_name, ' ', c.surname) as client_name,
                    p.project_name
             FROM {$wpdb->prefix}res_account_collections ac
             LEFT JOIN {$wpdb->prefix}res_clients c ON ac.client_id = c.client_id
             LEFT JOIN {$wpdb->prefix}res_projects p ON ac.project_id = p.project_id
             WHERE ac.agent_id = %d AND ac.is_released = 0
             ORDER BY ac.date_collected DESC",
            $agent_id
        ));
        
        wp_send_json_success($collections);
    }
    
    private function generate_voucher_number() {
        global $wpdb;
        
        $date_prefix = 'VR' . date('ymd');
        
        $last_voucher = $wpdb->get_var($wpdb->prepare(
            "SELECT voucher_number FROM {$wpdb->prefix}res_commission_vouchers 
             WHERE voucher_number LIKE %s 
             ORDER BY voucher_id DESC 
             LIMIT 1",
            $date_prefix . '%'
        ));
        
        if ($last_voucher) {
            $last_number = intval(substr($last_voucher, -3));
            $new_number = str_pad($last_number + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $new_number = '001';
        }
        
        return $date_prefix . '-' . $new_number;
    }
}