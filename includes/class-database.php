<?php
class RES_Database {
    private static $db_version = '2.0.0';
    
    public static function activate() {
        self::create_tables();
        self::insert_default_data();
        self::update_existing_tables();
        update_option('res_db_version', self::$db_version);
    }
    
    public static function check_and_update() {
        $current_version = get_option('res_db_version', '1.0.0');
        if (version_compare($current_version, self::$db_version, '<')) {
            self::update_existing_tables();
            update_option('res_db_version', self::$db_version);
        }
    }
    
    private static function update_existing_tables() {
        global $wpdb;
        
        // Update res_sales_agents table
        $columns = $wpdb->get_col("DESCRIBE {$wpdb->prefix}res_sales_agents", 0);
        
        if (!in_array('wp_user_id', $columns)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}res_sales_agents ADD COLUMN wp_user_id INT DEFAULT NULL AFTER agent_id");
            $wpdb->query("ALTER TABLE {$wpdb->prefix}res_sales_agents ADD UNIQUE INDEX idx_wp_user_unique (wp_user_id)");
        }
        
        if (!in_array('bank_account', $columns)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}res_sales_agents ADD COLUMN bank_account VARCHAR(100) DEFAULT NULL");
        }
        
        // Update res_clients table
        $columns = $wpdb->get_col("DESCRIBE {$wpdb->prefix}res_clients", 0);
        
        if (!in_array('created_by_agent_id', $columns)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}res_clients ADD COLUMN created_by_agent_id INT DEFAULT NULL");
            $wpdb->query("ALTER TABLE {$wpdb->prefix}res_clients ADD INDEX idx_created_by_agent (created_by_agent_id)");
        }
        
        // Update res_account_collections table
        $columns = $wpdb->get_col("DESCRIBE {$wpdb->prefix}res_account_collections", 0);
        
        if (!in_array('commission_percentage', $columns)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}res_account_collections ADD COLUMN commission_percentage DECIMAL(5,2) DEFAULT NULL");
        }
        
        if (!in_array('is_released', $columns)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}res_account_collections ADD COLUMN is_released TINYINT(1) DEFAULT 0");
        }
        
        if (!in_array('voucher_id', $columns)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}res_account_collections ADD COLUMN voucher_id INT DEFAULT NULL");
            $wpdb->query("ALTER TABLE {$wpdb->prefix}res_account_collections ADD INDEX idx_voucher (voucher_id)");
        }
        
        // Create new tables
        self::create_voucher_tables();
    }
    
    private static function create_voucher_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $voucher_tables = array(
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}res_commission_vouchers (
                voucher_id INT AUTO_INCREMENT PRIMARY KEY,
                voucher_number VARCHAR(50) UNIQUE NOT NULL,
                payee_agent_id INT NOT NULL,
                voucher_date DATE NOT NULL,
                total_gross_amount DECIMAL(15,2) NOT NULL,
                total_deductions DECIMAL(15,2) DEFAULT 0,
                total_net_amount DECIMAL(15,2) NOT NULL,
                prepared_by VARCHAR(100) DEFAULT NULL,
                checked_by VARCHAR(100) DEFAULT NULL,
                approved_by VARCHAR(100) DEFAULT NULL,
                bizlink_ref VARCHAR(100) DEFAULT NULL,
                status ENUM('draft', 'approved', 'paid') DEFAULT 'draft',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_voucher_number (voucher_number),
                INDEX idx_payee (payee_agent_id),
                INDEX idx_date (voucher_date)
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}res_voucher_line_items (
                line_item_id INT AUTO_INCREMENT PRIMARY KEY,
                voucher_id INT NOT NULL,
                collection_id INT NOT NULL,
                commission_percentage DECIMAL(5,2) DEFAULT NULL,
                client_name VARCHAR(200) DEFAULT NULL,
                project_name VARCHAR(100) DEFAULT NULL,
                amount DECIMAL(15,2) DEFAULT NULL,
                incremental_cost DECIMAL(15,2) DEFAULT NULL,
                net_pay DECIMAL(15,2) DEFAULT NULL,
                particulars TEXT DEFAULT NULL,
                line_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_voucher (voucher_id),
                INDEX idx_collection (collection_id)
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}res_voucher_deductions (
                deduction_id INT AUTO_INCREMENT PRIMARY KEY,
                voucher_id INT NOT NULL,
                description VARCHAR(255) NOT NULL,
                amount DECIMAL(15,2) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_voucher (voucher_id)
            ) $charset_collate;"
        );
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        foreach ($voucher_tables as $sql) {
            dbDelta($sql);
        }
    }
    
    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $tables = array(
            // Reference tables
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}res_ref_gender (
                id INT AUTO_INCREMENT PRIMARY KEY,
                gender VARCHAR(20) NOT NULL
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}res_ref_civil_status (
                id INT AUTO_INCREMENT PRIMARY KEY,
                civil_status VARCHAR(50) NOT NULL
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}res_ref_employment_type (
                id INT AUTO_INCREMENT PRIMARY KEY,
                employment_type VARCHAR(50) NOT NULL
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}res_ref_status (
                id INT AUTO_INCREMENT PRIMARY KEY,
                status VARCHAR(50) NOT NULL
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}res_ref_payment_status (
                id INT AUTO_INCREMENT PRIMARY KEY,
                payment_status VARCHAR(50) NOT NULL
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}res_ref_document_status (
                id INT AUTO_INCREMENT PRIMARY KEY,
                document_status VARCHAR(50) NOT NULL
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}res_ref_source_of_sale (
                id INT AUTO_INCREMENT PRIMARY KEY,
                source VARCHAR(100) NOT NULL
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}res_ref_license_status (
                id INT AUTO_INCREMENT PRIMARY KEY,
                license_status VARCHAR(50) NOT NULL
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}res_ref_or_status (
                id INT AUTO_INCREMENT PRIMARY KEY,
                or_status VARCHAR(50) NOT NULL
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}res_ref_agent_status (
                id INT AUTO_INCREMENT PRIMARY KEY,
                agent_status VARCHAR(50) NOT NULL
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}res_ref_2307_status (
                id INT AUTO_INCREMENT PRIMARY KEY,
                status VARCHAR(50) NOT NULL
            ) $charset_collate;",
            
            // Main tables
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}res_clients (
                client_id INT AUTO_INCREMENT PRIMARY KEY,
                client_type VARCHAR(50) DEFAULT NULL,
                first_name VARCHAR(100) NOT NULL,
                middle_initial VARCHAR(10) DEFAULT NULL,
                surname VARCHAR(100) NOT NULL,
                date_of_birth DATE DEFAULT NULL,
                civil_status_id INT DEFAULT NULL,
                tin VARCHAR(50) DEFAULT NULL,
                gender_id INT DEFAULT NULL,
                citizenship VARCHAR(50) DEFAULT NULL,
                email VARCHAR(100) DEFAULT NULL,
                contact_no VARCHAR(50) DEFAULT NULL,
                employer_name VARCHAR(100) DEFAULT NULL,
                occupation VARCHAR(100) DEFAULT NULL,
                employment_type_id INT DEFAULT NULL,
                secondary_client_type VARCHAR(50) DEFAULT NULL,
                secondary_first_name VARCHAR(100) DEFAULT NULL,
                secondary_middle_initial VARCHAR(10) DEFAULT NULL,
                secondary_surname VARCHAR(100) DEFAULT NULL,
                secondary_date_of_birth DATE DEFAULT NULL,
                secondary_tin VARCHAR(50) DEFAULT NULL,
                secondary_gender_id INT DEFAULT NULL,
                secondary_citizenship VARCHAR(50) DEFAULT NULL,
                secondary_email VARCHAR(100) DEFAULT NULL,
                secondary_contact_no VARCHAR(50) DEFAULT NULL,
                secondary_employer_name VARCHAR(100) DEFAULT NULL,
                secondary_occupation VARCHAR(100) DEFAULT NULL,
                unit_street_village VARCHAR(255) DEFAULT NULL,
                barangay VARCHAR(100) DEFAULT NULL,
                city VARCHAR(100) DEFAULT NULL,
                province VARCHAR(100) DEFAULT NULL,
                zip_code VARCHAR(20) DEFAULT NULL,
                secondary_unit_street_village VARCHAR(255) DEFAULT NULL,
                secondary_barangay VARCHAR(100) DEFAULT NULL,
                secondary_city VARCHAR(100) DEFAULT NULL,
                secondary_province VARCHAR(100) DEFAULT NULL,
                secondary_zip_code VARCHAR(20) DEFAULT NULL,
                source_of_sale_id INT DEFAULT NULL,
                created_by_agent_id INT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_client_name (first_name, surname),
                INDEX idx_created_by_agent (created_by_agent_id)
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}res_developers (
                developer_id INT AUTO_INCREMENT PRIMARY KEY,
                developer_name VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}res_projects (
                project_id INT AUTO_INCREMENT PRIMARY KEY,
                project_name VARCHAR(255) NOT NULL,
                developer_id INT DEFAULT NULL,
                date_accredited DATE DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_developer (developer_id)
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}res_agent_teams (
                team_id INT AUTO_INCREMENT PRIMARY KEY,
                team_name VARCHAR(100) NOT NULL,
                team_leader INT DEFAULT NULL,
                date_created DATE DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}res_agent_positions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                position_no INT NOT NULL,
                position_code VARCHAR(50) UNIQUE NOT NULL,
                position VARCHAR(100) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}res_sales_agents (
                agent_id INT AUTO_INCREMENT PRIMARY KEY,
                wp_user_id INT DEFAULT NULL,
                agent_code VARCHAR(50) DEFAULT NULL,
                agent_name VARCHAR(200) NOT NULL,
                date_hired DATE DEFAULT NULL,
                status_id INT DEFAULT NULL,
                position_code VARCHAR(50) DEFAULT NULL,
                team_id INT DEFAULT NULL,
                commission_rate DECIMAL(5,2) DEFAULT NULL,
                bank_account VARCHAR(100) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_wp_user (wp_user_id),
                UNIQUE KEY unique_agent_code (agent_code),
                INDEX idx_agent_name (agent_name),
                INDEX idx_team (team_id)
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}res_residential_sales (
                sale_id INT AUTO_INCREMENT PRIMARY KEY,
                client_id INT DEFAULT NULL,
                reservation_date DATE DEFAULT NULL,
                agent_id INT DEFAULT NULL,
                status_id INT DEFAULT NULL,
                payment_status_id INT DEFAULT NULL,
                document_status_id INT DEFAULT NULL,
                project_id INT DEFAULT NULL,
                block VARCHAR(50) DEFAULT NULL,
                lot VARCHAR(50) DEFAULT NULL,
                unit VARCHAR(50) DEFAULT NULL,
                net_tcp DECIMAL(15,2) DEFAULT NULL,
                gross_tcp DECIMAL(15,2) DEFAULT NULL,
                downpayment_type VARCHAR(100) DEFAULT NULL,
                downpayment_terms VARCHAR(100) DEFAULT NULL,
                financing_type_id INT DEFAULT NULL,
                year_to_pay INT DEFAULT NULL,
                license_status_id INT DEFAULT NULL,
                remarks TEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_client (client_id),
                INDEX idx_agent (agent_id),
                INDEX idx_project (project_id),
                INDEX idx_status (status_id)
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}res_developer_collections (
                dev_collection_id INT AUTO_INCREMENT PRIMARY KEY,
                collection_date DATE DEFAULT NULL,
                or_number VARCHAR(50) DEFAULT NULL,
                payor VARCHAR(200) DEFAULT NULL,
                project_id INT DEFAULT NULL,
                payee VARCHAR(200) DEFAULT NULL,
                particulars TEXT DEFAULT NULL,
                gross_amount DECIMAL(15,2) DEFAULT NULL,
                vat DECIMAL(15,2) DEFAULT NULL,
                ewt DECIMAL(15,2) DEFAULT NULL,
                net_collected_amount DECIMAL(15,2) DEFAULT NULL,
                form_2307_status_id INT DEFAULT NULL,
                deposit_date DATE DEFAULT NULL,
                account_deposited VARCHAR(100) DEFAULT NULL,
                reference VARCHAR(100) DEFAULT NULL,
                remarks TEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_or_number (or_number),
                INDEX idx_project (project_id)
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}res_account_collections (
                acct_collection_id INT AUTO_INCREMENT PRIMARY KEY,
                or_number VARCHAR(50) DEFAULT NULL,
                date_collected DATE DEFAULT NULL,
                payor VARCHAR(200) DEFAULT NULL,
                payee VARCHAR(200) DEFAULT NULL,
                client_id INT DEFAULT NULL,
                agent_id INT DEFAULT NULL,
                developer_id INT DEFAULT NULL,
                project_id INT DEFAULT NULL,
                block VARCHAR(50) DEFAULT NULL,
                lot VARCHAR(50) DEFAULT NULL,
                gross_commission DECIMAL(15,2) DEFAULT NULL,
                vat DECIMAL(15,2) DEFAULT NULL,
                ewt DECIMAL(15,2) DEFAULT NULL,
                net_commission DECIMAL(15,2) DEFAULT NULL,
                particulars TEXT DEFAULT NULL,
                cname_particulars TEXT DEFAULT NULL,
                commission_percentage DECIMAL(5,2) DEFAULT NULL,
                is_released TINYINT(1) DEFAULT 0,
                voucher_id INT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_or_number (or_number),
                INDEX idx_client (client_id),
                INDEX idx_agent (agent_id),
                INDEX idx_voucher (voucher_id)
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}res_released_commissions (
                release_id INT AUTO_INCREMENT PRIMARY KEY,
                release_date DATE DEFAULT NULL,
                voucher_number VARCHAR(50) DEFAULT NULL,
                or_number VARCHAR(50) DEFAULT NULL,
                client_reference VARCHAR(200) DEFAULT NULL,
                amount_collected DECIMAL(15,2) DEFAULT NULL,
                payee_id INT DEFAULT NULL,
                agent_status_id INT DEFAULT NULL,
                commission_rate DECIMAL(5,2) DEFAULT NULL,
                client_id INT DEFAULT NULL,
                project_id INT DEFAULT NULL,
                or_status_id INT DEFAULT NULL,
                gross_amount DECIMAL(15,2) DEFAULT NULL,
                inc DECIMAL(15,2) DEFAULT NULL,
                ewt DECIMAL(15,2) DEFAULT NULL,
                net_pay DECIMAL(15,2) DEFAULT NULL,
                particulars TEXT DEFAULT NULL,
                bizlink VARCHAR(100) DEFAULT NULL,
                reference VARCHAR(100) DEFAULT NULL,
                remarks TEXT DEFAULT NULL,
                voucher_id INT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_payee (payee_id),
                INDEX idx_voucher_number (voucher_number),
                INDEX idx_voucher (voucher_id)
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}res_agent_cash_advances (
                advance_id INT AUTO_INCREMENT PRIMARY KEY,
                date DATE DEFAULT NULL,
                agent_id INT DEFAULT NULL,
                amount DECIMAL(15,2) DEFAULT NULL,
                reason TEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_agent (agent_id)
            ) $charset_collate;"
        );
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        foreach ($tables as $sql) {
            dbDelta($sql);
        }
        
        // Create voucher tables
        self::create_voucher_tables();
    }
    
    private static function insert_default_data() {
        global $wpdb;
        
        // Check if data already exists
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}res_ref_gender");
        if ($count > 0) return;
        
        // Gender
        $genders = array('Male', 'Female', 'Other');
        foreach ($genders as $gender) {
            $wpdb->insert($wpdb->prefix . 'res_ref_gender', array('gender' => $gender), array('%s'));
        }
        
        // Civil Status
        $civil_statuses = array('Single', 'Married', 'Widowed', 'Separated', 'Divorced');
        foreach ($civil_statuses as $status) {
            $wpdb->insert($wpdb->prefix . 'res_ref_civil_status', array('civil_status' => $status), array('%s'));
        }
        
        // Employment Type
        $employment_types = array('Regular', 'Contractual', 'Freelance', 'Self-Employed', 'Unemployed');
        foreach ($employment_types as $type) {
            $wpdb->insert($wpdb->prefix . 'res_ref_employment_type', array('employment_type' => $type), array('%s'));
        }
        
        // Status
        $statuses = array('Active', 'Cancelled', 'Pending', 'On Hold');
        foreach ($statuses as $status) {
            $wpdb->insert($wpdb->prefix . 'res_ref_status', array('status' => $status), array('%s'));
        }
        
        // Payment Status
        $payment_statuses = array('Paid', 'Partially Paid', 'Unpaid', 'Overdue');
        foreach ($payment_statuses as $status) {
            $wpdb->insert($wpdb->prefix . 'res_ref_payment_status', array('payment_status' => $status), array('%s'));
        }
        
        // Document Status
        $document_statuses = array('Complete', 'Incomplete', 'Processing', 'Verified');
        foreach ($document_statuses as $status) {
            $wpdb->insert($wpdb->prefix . 'res_ref_document_status', array('document_status' => $status), array('%s'));
        }
        
        // License Status
        $license_statuses = array('Valid', 'Expired', 'Pending', 'Suspended');
        foreach ($license_statuses as $status) {
            $wpdb->insert($wpdb->prefix . 'res_ref_license_status', array('license_status' => $status), array('%s'));
        }
        
        // Agent Status
        $agent_statuses = array('Active', 'Inactive', 'Terminated', 'On Leave');
        foreach ($agent_statuses as $status) {
            $wpdb->insert($wpdb->prefix . 'res_ref_agent_status', array('agent_status' => $status), array('%s'));
        }
        
        // OR Status
        $or_statuses = array('Pending', 'Issued', 'Cancelled');
        foreach ($or_statuses as $status) {
            $wpdb->insert($wpdb->prefix . 'res_ref_or_status', array('or_status' => $status), array('%s'));
        }
        
        // 2307 Status
        $form_2307_statuses = array('Pending', 'Submitted', 'Processed');
        foreach ($form_2307_statuses as $status) {
            $wpdb->insert($wpdb->prefix . 'res_ref_2307_status', array('status' => $status), array('%s'));
        }
        
        // Source of Sale
        $sources = array('Referral', 'Online', 'Walk-in', 'Social Media', 'Advertisement');
        foreach ($sources as $source) {
            $wpdb->insert($wpdb->prefix . 'res_ref_source_of_sale', array('source' => $source), array('%s'));
        }
    }
}