<?php
/**
 * Run Database Updates
 * Execute this file once to update the database with new employee module tables
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';

echo "Starting database update...\n";

// Add employee role if not exists
$db->query("INSERT IGNORE INTO roles (role_name, role_description) VALUES ('employee', 'Employee - View personal dashboard, attendance, salary, request leave')");
echo "✓ Added 'employee' role\n";

// Create leave types table
$db->query("CREATE TABLE IF NOT EXISTS employee_leave_types (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    leave_type_name VARCHAR(50) NOT NULL,
    leave_code VARCHAR(20) NOT NULL UNIQUE,
    description TEXT,
    days_per_year INT(11) DEFAULT 0,
    is_paid TINYINT(1) DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB");
echo "✓ Created employee_leave_types table\n";

// Insert default leave types
$db->query("INSERT IGNORE INTO employee_leave_types (leave_type_name, leave_code, description, days_per_year, is_paid) VALUES 
('Annual Leave', 'ANNUAL', 'Annual vacation leave', 30, 1),
('Sick Leave', 'SICK', 'Medical sick leave', 15, 1),
('Casual Leave', 'CASUAL', 'Casual personal leave', 10, 1),
('Unpaid Leave', 'UNPAID', 'Leave without pay', 0, 0),
('Maternity Leave', 'MATERNITY', 'Maternity leave for female staff', 60, 1),
('Paternity Leave', 'PATERNITY', 'Paternity leave for male staff', 7, 1),
('Emergency Leave', 'EMERGENCY', 'Emergency family leave', 5, 1)");
echo "✓ Added default leave types\n";

// Create employees table
$db->query("CREATE TABLE IF NOT EXISTS employees (
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
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB");
echo "✓ Created employees table\n";

// Create attendance table
$db->query("CREATE TABLE IF NOT EXISTS employee_attendance (
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
    UNIQUE KEY idx_employee_date (employee_id, attendance_date)
) ENGINE=InnoDB");
echo "✓ Created employee_attendance table\n";

// Create salary table
$db->query("CREATE TABLE IF NOT EXISTS employee_salary (
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
    UNIQUE KEY idx_employee_month (employee_id, salary_month)
) ENGINE=InnoDB");
echo "✓ Created employee_salary table\n";

// Create deductions table
$db->query("CREATE TABLE IF NOT EXISTS employee_deductions (
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
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB");
echo "✓ Created employee_deductions table\n";

// Create overtime table
$db->query("CREATE TABLE IF NOT EXISTS employee_overtime (
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
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB");
echo "✓ Created employee_overtime table\n";

// Create leave requests table
$db->query("CREATE TABLE IF NOT EXISTS employee_leave_requests (
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
    FOREIGN KEY (leave_type_id) REFERENCES employee_leave_types(id) ON DELETE RESTRICT
) ENGINE=InnoDB");
echo "✓ Created employee_leave_requests table\n";

// Create leave balance table
$db->query("CREATE TABLE IF NOT EXISTS employee_leave_balance (
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
    UNIQUE KEY idx_employee_leave_year (employee_id, leave_type_id, year)
) ENGINE=InnoDB");
echo "✓ Created employee_leave_balance table\n";

echo "\n✅ Database update completed successfully!\n";
echo "\nYou can now:\n";
echo "1. Login as admin/HR and go to HR > Employees to add employees\n";
echo "2. Create user accounts for employees\n";
echo "3. Employees can login at employee/login.php\n";

