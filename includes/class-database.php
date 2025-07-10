<?php
class RES_Database {
    private static $db_version = '1.0.0';
    
    public static function activate() {
        self::create_tables();
        self::insert_default_data();
        update_option('res_db_version', self::$db_version);
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
                client_type VARCHAR(50),
                first_name VARCHAR(100) NOT NULL,
                middle_initial VARCHAR(10),
                surname VARCHAR(100) NOT NULL,
                date_of_birth DATE,
                civil_status_id INT,
                tin VARCHAR(50),
                gender_id INT,
                citizenship VARCHAR(50),
                email VARCHAR(100),
                contact_no VARCHAR(50),
                employer_name VARCHAR(100),
                occupation VARCHAR(100),
                employment_type_id INT,
                secondary_client_type VARCHAR(50),
                secondary_first_name VARCHAR(100),
                secondary_middle_initial VARCHAR(10),
                secondary_surname VARCHAR(100),
                secondary_date_of_birth DATE,
                secondary_tin VARCHAR(50),
                secondary_gender_id INT,
                secondary_citizenship VARCHAR(50),
                secondary_email VARCHAR(100),
                secondary_contact_no VARCHAR(50),
                secondary_employer_name VARCHAR(100),
                secondary_occupation VARCHAR(100),
                unit_street_village VARCHAR(255),
                barangay VARCHAR(100),
                city VARCHAR(100),
                province VARCHAR(100),
                zip_code VARCHAR(20),
                secondary_unit_street_village VARCHAR(255),
                secondary_barangay VARCHAR(100),
                secondary_city VARCHAR(100),
                secondary_province VARCHAR(100),
                secondary_zip_code VARCHAR(20),
                source_of_sale_id INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_client_name (first_name, surname)
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}res_developers (
                developer_id INT AUTO_INCREMENT PRIMARY KEY,
                developer_name VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}res_projects (
                project_id INT AUTO_INCREMENT PRIMARY KEY,
                project_name VARCHAR(255) NOT NULL,
                developer_id INT,
                date_accredited DATE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_developer (developer_id)
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}res_agent_teams (
                team_id INT AUTO_INCREMENT PRIMARY KEY,
                team_name VARCHAR(100) NOT NULL,
                team_leader INT,
                date_created DATE,
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
                agent_code VARCHAR(50) UNIQUE,
                agent_name VARCHAR(200) NOT NULL,
                date_hired DATE,
                status_id INT,
                position_code VARCHAR(50),
                team_id INT,
                commission_rate DECIMAL(5,2),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_agent_name (agent_name),
                INDEX idx_team (team_id)
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}res_residential_sales (
                sale_id INT AUTO_INCREMENT PRIMARY KEY,
                client_id INT,
                reservation_date DATE,
                agent_id INT,
                status_id INT,
                payment_status_id INT,
                document_status_id INT,
                project_id INT,
                block VARCHAR(50),
                lot VARCHAR(50),
                unit VARCHAR(50),
                net_tcp DECIMAL(15,2),
                gross_tcp DECIMAL(15,2),
                downpayment_type VARCHAR(100),
                downpayment_terms VARCHAR(100),
                financing_type_id INT,
                year_to_pay INT,
                license_status_id INT,
                remarks TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_client (client_id),
                INDEX idx_agent (agent_id),
                INDEX idx_project (project_id),
                INDEX idx_status (status_id)
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}res_developer_collections (
                dev_collection_id INT AUTO_INCREMENT PRIMARY KEY,
                collection_date DATE,
                or_number VARCHAR(50),
                payor VARCHAR(200),
                project_id INT,
                payee VARCHAR(200),
                particulars TEXT,
                gross_amount DECIMAL(15,2),
                vat DECIMAL(15,2),
                ewt DECIMAL(15,2),
                net_collected_amount DECIMAL(15,2),
                form_2307_status_id INT,
                deposit_date DATE,
                account_deposited VARCHAR(100),
                reference VARCHAR(100),
                remarks TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_or_number (or_number),
                INDEX idx_project (project_id)
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}res_account_collections (
                acct_collection_id INT AUTO_INCREMENT PRIMARY KEY,
                or_number VARCHAR(50),
                date_collected DATE,
                payor VARCHAR(200),
                payee VARCHAR(200),
                client_id INT,
                agent_id INT,
                developer_id INT,
                project_id INT,
                block VARCHAR(50),
                lot VARCHAR(50),
                gross_commission DECIMAL(15,2),
                vat DECIMAL(15,2),
                ewt DECIMAL(15,2),
                net_commission DECIMAL(15,2),
                particulars TEXT,
                cname_particulars TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_or_number (or_number),
                INDEX idx_client (client_id),
                INDEX idx_agent (agent_id)
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}res_released_commissions (
                release_id INT AUTO_INCREMENT PRIMARY KEY,
                release_date DATE,
                voucher_number VARCHAR(50),
                or_number VARCHAR(50),
                client_reference VARCHAR(200),
                amount_collected DECIMAL(15,2),
                payee_id INT,
                agent_status_id INT,
                commission_rate DECIMAL(5,2),
                client_id INT,
                project_id INT,
                or_status_id INT,
                gross_amount DECIMAL(15,2),
                inc DECIMAL(15,2),
                ewt DECIMAL(15,2),
                net_pay DECIMAL(15,2),
                particulars TEXT,
                bizlink VARCHAR(100),
                reference VARCHAR(100),
                remarks TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_payee (payee_id),
                INDEX idx_voucher (voucher_number)
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}res_agent_cash_advances (
                advance_id INT AUTO_INCREMENT PRIMARY KEY,
                date DATE,
                agent_id INT,
                amount DECIMAL(15,2),
                reason TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_agent (agent_id)
            ) $charset_collate;"
        );
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        foreach ($tables as $sql) {
            dbDelta($sql);
        }
    }
    
    private static function insert_default_data() {
        global $wpdb;
        
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
    }
}