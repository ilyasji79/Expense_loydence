<?php
/**
 * Secure Database Migration - Employee Module
 * 
 * This script creates employee management tables for the Expense ERP system.
 * It includes proper error handling, migration tracking, and security features.
 * 
 * IMPORTANT: Delete this file after successful execution for security!
 * 
 * @author Expense ERP System
 * @version 1.0
 */

echo "============================================================\n";
echo "  EMPLOYEE MODULE DATABASE MIGRATION\n";
echo "  Expense Management ERP - Loydence Academy\n";
echo "============================================================\n\n";

// Prevent direct access via web
if (php_sapi_name() !== 'cli') {
    die("This script should be run from command line or deleted after use.\n");
}

// Include required files
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting migration...\n\n";

/**
 * Migration class to handle all database operations
 */
class EmployeeMigration {
    private $db;
    private $migration_name = 'employee_module';
    private $migration_version = '1.0';
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Check if migration already executed
     */
    public function isMigrationExecuted() {
        try {
            $result = $this->db->fetch(
                "SELECT id FROM migration_logs WHERE migration_name = ? AND status = 'completed'",
                [$this->migration_name]
            );
            return $result !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Create migration_logs table if not exists
     */
    public function createMigrationLogsTable() {
        echo "  → Creating migration_logs table (if not exists)...\n";
        try {
            $sql = "CREATE TABLE IF NOT EXISTS migration_logs (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                migration_name VARCHAR(100) NOT NULL,
                migration_version VARCHAR(20) NOT NULL,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                status ENUM('running', 'completed', 'failed') DEFAULT 'running',
                error_message TEXT,
                UNIQUE KEY idx_migration_name (migration_name)
            ) ENGINE=InnoDB";
            
            $this->db->query($sql);
            echo "    ✓ Migration logs table ready\n";
            return true;
        } catch (Exception $e) {
            echo "    ✗ Error creating migration_logs table: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Start migration tracking
     */
    public function startMigration() {
        try {
            // Remove any existing running migration
            $this->db->query(
                "DELETE FROM migration_logs WHERE migration_name = ? AND status = 'running'",
                [$this->migration_name]
            );
            
            // Insert new migration record
            $this->db->query(
                "INSERT INTO migration_logs (migration_name, migration_version, status) VALUES (?, ?, 'running')",
                [$this->migration_name, $this->migration_version]
            );
            
            echo "  ✓ Migration tracking started\n";
            return true;
        } catch (Exception $e) {
            echo "  ✗ Error starting migration: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Complete migration successfully
     */
    public function completeMigration($error_message = null) {
        try {
            if ($error_message) {
                $this->db->query(
                    "UPDATE migration_logs SET status = 'failed', error_message = ? WHERE migration_name = ? AND status = 'running'",
                    [$error_message, $this->migration_name]
                );
            } else {
                $this->db->query(
                    "UPDATE migration_logs SET status = 'completed' WHERE migration_name = ? AND status = 'running'",
                    [$this->migration_name]
                );
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Check if table exists
     */
    public function tableExists($table_name) {
        try {
            $result = $this->db->fetch(
                "SELECT COUNT(*) as count FROM information_schema.tables 
                WHERE table_schema = ? AND table_name = ?",
                [DB_NAME, $table_name]
            );
            return $result['count'] > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Check if column exists in table
     */
    public function columnExists($table_name, $column_name) {
        try {
            $result = $this->db->fetch(
                "SELECT COUNT(*) as count FROM information_schema.columns 
                WHERE table_schema = ? AND table_name = ? AND column_name = ?",
                [DB_NAME, $table_name, $column_name]
            );
            return $result['count'] > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Check if role exists
     */
    public function roleExists($role_name) {
        try {
            $result = $this->db->fetch(
                "SELECT id FROM roles WHERE role_name = ?",
                [$role_name]
            );
            return $result !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Add employee role if not exists
     */
    public function addEmployeeRole() {
        echo "\n[1/8] Adding 'employee' role...\n";
        
        if ($this->roleExists('employee')) {
            echo "  ✓ 'employee' role already exists - skipping\n";
            return true;
        }
        
        try {
            $this->db->query(
                "INSERT INTO roles (role_name, role_description) VALUES ('employee', 'Employee - View personal dashboard, attendance, salary, request leave')"
            );
            echo "  ✓ 'employee' role added successfully\n";
            return true;
        } catch (Exception $e) {
            echo "  ✗ Error adding role: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Create employee_leave_types table
     */
    public function createLeaveTypesTable() {
        echo "\n[2/8] Creating employee_leave_types table...\n";
        
        if ($this->tableExists('employee_leave_types')) {
            echo "  ✓ Table already exists - skipping\n";
            
            // Check and insert default leave types
            $this->addDefaultLeaveTypes();
            return true;
        }
        
        try {
            $sql = "CREATE TABLE employee_leave_types (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                leave_type_name VARCHAR(50) NOT NULL,
                leave_code VARCHAR(20) NOT NULL UNIQUE,
                description TEXT,
                days_per_year INT(11) DEFAULT 0,
                is_paid TINYINT(1) DEFAULT 1,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $this->db->query($sql);
            echo "  ✓ Table created successfully\n";
            
            // Add default leave types
            $this->addDefaultLeaveTypes();
            
            return true;
        } catch (Exception $e) {
            echo "  ✗ Error creating table: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Add default leave types
     */
    private function addDefaultLeaveTypes() {
        echo "  → Adding default leave types...\n";
        
        $leave_types = [
            ['Annual Leave', 'ANNUAL', 'Annual vacation leave', 30, 1],
            ['Sick Leave', 'SICK', 'Medical sick leave', 15, 1],
            ['Casual Leave', 'CASUAL', 'Casual personal leave', 10, 1],
            ['Unpaid Leave', 'UNPAID', 'Leave without pay', 0, 0],
            ['Maternity Leave', 'MATERNITY', 'Maternity leave for female staff', 60, 1],
            ['Paternity Leave', 'PATERNITY', 'Paternity leave for male staff', 7, 1],
            ['Emergency Leave', 'EMERGENCY', 'Emergency family leave', 5, 1]
        ];
        
        try {
            foreach ($leave_types as $leave) {
                // Check if leave type already exists
                $exists = $this->db->fetch(
                    "SELECT id FROM employee_leave_types WHERE leave_code = ?",
                    [$leave[1]]
                );
                
                if (!$exists) {
                    $this->db->query(
                        "INSERT INTO employee_leave_types (leave_type_name, leave_code, description, days_per_year, is_paid) VALUES (?, ?, ?, ?, ?)",
                        $leave
                    );
                }
            }
            echo "  ✓ Default leave types processed\n";
        } catch (Exception $e) {
            echo "  ✗ Error adding leave types: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Create employees table
     */
    public function createEmployeesTable() {
        echo "\n[3/8] Creating employees table...\n";
        
        if ($this->tableExists('employees')) {
            echo "  ✓ Table already exists - skipping\n";
            return true;
        }
        
        try {
            $sql = "CREATE TABLE employees (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                user_id INT(11) NULL,
                employee_code VARCHAR(50) NOT NULL UNIQUE,
                first_name VARCHAR(50) NOT NULL,
                last_name VARCHAR(50) NOT NULL,
                full_name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                phone VARCHAR(20),
                date_of_birth DATE,
                gender ENUM('male', 'female', 'other') DEFAULT 'male',
                nationality VARCHAR(50),
                marital_status ENUM('single', 'married', 'divorced', 'widowed') DEFAULT 'single',
                address TEXT,
                department VARCHAR(100),
                designation VARCHAR(100),
                join_date DATE NOT NULL,
                employment_type ENUM('full_time', 'part_time', 'contract', 'temporary') DEFAULT 'full_time',
                base_salary DECIMAL(12,2) DEFAULT 0,
                hourly_rate DECIMAL(10,2) DEFAULT 0,
                bank_name VARCHAR(100),
                bank_account_number VARCHAR(50),
                emirates_id VARCHAR(50),
                passport_number VARCHAR(50),
                passport_expiry DATE,
                visa_status VARCHAR(50),
                emergency_contact_name VARCHAR(100),
                emergency_contact_phone VARCHAR(20),
                photo VARCHAR(255),
                is_active TINYINT(1) DEFAULT 1,
                created_by INT(11),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_employee_code (employee_code),
                INDEX idx_email (email),
                INDEX idx_department (department),
                INDEX idx_is_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $this->db->query($sql);
            echo "  ✓ Table created successfully\n";
            return true;
        } catch (Exception $e) {
            echo "  ✗ Error creating table: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Create employee_attendance table
     */
    public function createAttendanceTable() {
        echo "\n[4/8] Creating employee_attendance table...\n";
        
        if ($this->tableExists('employee_attendance')) {
            echo "  ✓ Table already exists - skipping\n";
            return true;
        }
        
        try {
            $sql = "CREATE TABLE employee_attendance (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                employee_id INT(11) NOT NULL,
                attendance_date DATE NOT NULL,
                check_in_time TIME,
                check_out_time TIME,
                late_minutes INT(11) DEFAULT 0,
                overtime_hours DECIMAL(5,2) DEFAULT 0,
                status ENUM('present', 'absent', 'late', 'leave', 'holiday') DEFAULT 'present',
                qr_code VARCHAR(100),
                location VARCHAR(255),
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
                UNIQUE KEY idx_employee_date (employee_id, attendance_date),
                INDEX idx_date (attendance_date),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $this->db->query($sql);
            echo "  ✓ Table created successfully\n";
            return true;
        } catch (Exception $e) {
            echo "  ✗ Error creating table: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Create employee_salary table
     */
    public function createSalaryTable() {
        echo "\n[5/8] Creating employee_salary table...\n";
        
        if ($this->tableExists('employee_salary')) {
            echo "  ✓ Table already exists - skipping\n";
            return true;
        }
        
        try {
            $sql = "CREATE TABLE employee_salary (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                employee_id INT(11) NOT NULL,
                salary_month DATE NOT NULL,
                base_salary DECIMAL(12,2) NOT NULL,
                allowances DECIMAL(12,2) DEFAULT 0,
                overtime_amount DECIMAL(12,2) DEFAULT 0,
                bonus DECIMAL(12,2) DEFAULT 0,
                gross_salary DECIMAL(12,2) NOT NULL,
                tax_deduction DECIMAL(12,2) DEFAULT 0,
                other_deductions DECIMAL(12,2) DEFAULT 0,
                total_deductions DECIMAL(12,2) DEFAULT 0,
                net_salary DECIMAL(12,2) NOT NULL,
                payment_type ENUM('cash', 'card', 'bank_transfer') DEFAULT 'bank_transfer',
                payment_status ENUM('pending', 'processed', 'paid') DEFAULT 'pending',
                payment_date DATE,
                payment_reference VARCHAR(100),
                generated_by INT(11),
                generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
                FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL,
                UNIQUE KEY idx_employee_month (employee_id, salary_month),
                INDEX idx_month (salary_month),
                INDEX idx_payment_status (payment_status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $this->db->query($sql);
            echo "  ✓ Table created successfully\n";
            return true;
        } catch (Exception $e) {
            echo "  ✗ Error creating table: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Create employee_deductions table
     */
    public function createDeductionsTable() {
        echo "\n[6/8] Creating employee_deductions table...\n";
        
        if ($this->tableExists('employee_deductions')) {
            echo "  ✓ Table already exists - skipping\n";
            return true;
        }
        
        try {
            $sql = "CREATE TABLE employee_deductions (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                employee_id INT(11) NOT NULL,
                deduction_type ENUM('tax', 'fine', 'advance', 'insurance', 'other') NOT NULL,
                deduction_name VARCHAR(100) NOT NULL,
                amount DECIMAL(12,2) NOT NULL,
                start_date DATE,
                end_date DATE,
                is_recurring TINYINT(1) DEFAULT 0,
                status ENUM('active', 'stopped') DEFAULT 'active',
                notes TEXT,
                created_by INT(11),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_employee (employee_id),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $this->db->query($sql);
            echo "  ✓ Table created successfully\n";
            return true;
        } catch (Exception $e) {
            echo "  ✗ Error creating table: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Create employee_overtime table
     */
    public function createOvertimeTable() {
        echo "\n[7/8] Creating employee_overtime table...\n";
        
        if ($this->tableExists('employee_overtime')) {
            echo "  ✓ Table already exists - skipping\n";
            return true;
        }
        
        try {
            $sql = "CREATE TABLE employee_overtime (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                employee_id INT(11) NOT NULL,
                overtime_date DATE NOT NULL,
                hours DECIMAL(5,2) NOT NULL,
                hourly_rate DECIMAL(10,2) NOT NULL,
                total_amount DECIMAL(12,2) NOT NULL,
                reason TEXT,
                status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                approved_by INT(11),
                approved_at DATETIME,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
                FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_employee (employee_id),
                INDEX idx_date (overtime_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $this->db->query($sql);
            echo "  ✓ Table created successfully\n";
            return true;
        } catch (Exception $e) {
            echo "  ✗ Error creating table: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Create employee_leave_requests and employee_leave_balance tables
     */
    public function createLeaveTables() {
        echo "\n[8/8] Creating leave management tables...\n";
        
        // Create leave_requests table
        if (!$this->tableExists('employee_leave_requests')) {
            try {
                $sql = "CREATE TABLE employee_leave_requests (
                    id INT(11) AUTO_INCREMENT PRIMARY KEY,
                    employee_id INT(11) NOT NULL,
                    leave_type_id INT(11) NOT NULL,
                    start_date DATE NOT NULL,
                    end_date DATE NOT NULL,
                    total_days DECIMAL(5,1) NOT NULL,
                    reason TEXT,
                    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                    approved_by INT(11),
                    approval_date DATETIME,
                    rejection_reason TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
                    FOREIGN KEY (leave_type_id) REFERENCES employee_leave_types(id) ON DELETE RESTRICT,
                    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
                    INDEX idx_employee (employee_id),
                    INDEX idx_status (status),
                    INDEX idx_dates (start_date, end_date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                $this->db->query($sql);
                echo "  ✓ employee_leave_requests table created\n";
            } catch (Exception $e) {
                echo "  ✗ Error creating employee_leave_requests: " . $e->getMessage() . "\n";
                return false;
            }
        } else {
            echo "  ✓ employee_leave_requests table already exists\n";
        }
        
        // Create leave_balance table
        if (!$this->tableExists('employee_leave_balance')) {
            try {
                $sql = "CREATE TABLE employee_leave_balance (
                    id INT(11) AUTO_INCREMENT PRIMARY KEY,
                    employee_id INT(11) NOT NULL,
                    leave_type_id INT(11) NOT NULL,
                    year INT(11) NOT NULL,
                    total_days DECIMAL(5,1) DEFAULT 0,
                    used_days DECIMAL(5,1) DEFAULT 0,
                    remaining_days DECIMAL(5,1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
                    FOREIGN KEY (leave_type_id) REFERENCES employee_leave_types(id) ON DELETE CASCADE,
                    UNIQUE KEY idx_employee_leave_year (employee_id, leave_type_id, year),
                    INDEX idx_year (year)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                $this->db->query($sql);
                echo "  ✓ employee_leave_balance table created\n";
            } catch (Exception $e) {
                echo "  ✗ Error creating employee_leave_balance: " . $e->getMessage() . "\n";
                return false;
            }
        } else {
            echo "  ✓ employee_leave_balance table already exists\n";
        }
        
        return true;
    }
    
    /**
     * Run complete migration
     */
    public function run() {
        try {
            // Create migration logs table (no transaction - DDL auto-commits)
            $this->createMigrationLogsTable();
            
            // Start migration tracking
            $this->startMigration();
            
            // Execute all migration steps
            $steps = [
                $this->addEmployeeRole(),
                $this->createLeaveTypesTable(),
                $this->createEmployeesTable(),
                $this->createAttendanceTable(),
                $this->createSalaryTable(),
                $this->createDeductionsTable(),
                $this->createOvertimeTable(),
                $this->createLeaveTables()
            ];
            
            // Check if all steps succeeded
            if (in_array(false, $steps)) {
                throw new Exception("One or more migration steps failed");
            }
            
            // Mark migration as completed
            $this->completeMigration();
            
            return true;
        } catch (Exception $e) {
            // Mark migration as failed
            $this->completeMigration($e->getMessage());
            
            throw $e;
        }
    }
}

// Main execution
try {
    // Initialize database connection
    $database = new Database();
    
    // Create migration instance
    $migration = new EmployeeMigration($database);
    
    // Check if already executed
    if ($migration->isMigrationExecuted()) {
        echo "\n⚠️  MIGRATION ALREADY EXECUTED\n";
        echo "============================================================\n";
        echo "This migration has already been completed.\n";
        echo "To re-run, please clear the migration log first:\n";
        echo "  DELETE FROM migration_logs WHERE migration_name = 'employee_module';\n";
        echo "============================================================\n";
        exit(0);
    }
    
    // Run migration
    $migration->run();
    
    // Success output
    echo "\n";
    echo "============================================================\n";
    echo "  ✅ MIGRATION COMPLETED SUCCESSFULLY!\n";
    echo "============================================================\n";
    echo "\n";
    echo "The following tables have been created/verified:\n";
    echo "  • employee_leave_types\n";
    echo "  • employees\n";
    echo "  • employee_attendance\n";
    echo "  • employee_salary\n";
    echo "  • employee_deductions\n";
    echo "  • employee_overtime\n";
    echo "  • employee_leave_requests\n";
    echo "  • employee_leave_balance\n";
    echo "\n";
    echo "The 'employee' role has been added to the roles table.\n";
    echo "\n";
    echo "============================================================\n";
    echo "  🔒 SECURITY WARNING - ACTION REQUIRED!\n";
    echo "============================================================\n";
    echo "\n";
    echo "⚠️  DELETE THIS FILE IMMEDIATELY FOR SECURITY! ⚠️\n";
    echo "\n";
    echo "This migration file should be deleted after successful\n";
    echo "execution to prevent unauthorized database changes.\n";
    echo "\n";
    echo "Run: rm update_employee_module.php (Linux/Mac)\n";
    echo "or:  del update_employee_module.php (Windows)\n";
    echo "\n";
    echo "============================================================\n";
    
} catch (Exception $e) {
    echo "\n";
    echo "============================================================\n";
    echo "  ❌ MIGRATION FAILED!\n";
    echo "============================================================\n";
    echo "\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "\n";
    echo "Please check the error and try again.\n";
    echo "If the issue persists, check the migration_logs table\n";
    echo "for more details.\n";
    echo "============================================================\n";
    exit(1);
}

