-- ============================================================
-- Expense Management ERP Database
-- School: Loydence Academy British School
-- Location: Al Aziziyah, Qatar
-- ============================================================

-- Create database
CREATE DATABASE IF NOT EXISTS expense_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE expense_erp;

-- ============================================================
-- ROLES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS roles (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    role_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert default roles
INSERT INTO roles (role_name, role_description) VALUES 
('admin', 'Full system access - Add/Edit/Delete expenses, Release funds, Generate reports'),
('hr_manager', 'HR Manager - Approve/Reject expenses, View reports only'),
('employee', 'Employee - View personal dashboard, attendance, salary, request leave');

-- ============================================================
-- USERS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    role_id INT(11) NOT NULL,
    phone VARCHAR(20),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role_id)
) ENGINE=InnoDB;

-- Default admin user (password: admin123 - bcrypt hash)
-- Username: admin, Password: admin123
INSERT INTO users (username, password, email, full_name, role_id) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@loydence.edu.qa', 'Mr. Mohammad Ilyas', 1),
('hr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'hr@loydence.edu.qa', 'Sharifa Shaikh', 2);

-- ============================================================
-- CATEGORIES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS categories (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    category_code VARCHAR(20),
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert default expense categories
INSERT INTO categories (category_name, category_code, description) VALUES 
('Salaries & Wages', 'SAL', 'Staff salaries and wages'),
('Teaching Materials', 'TM', 'Books, stationery, educational materials'),
('Infrastructure & Maintenance', 'INF', 'Building repairs, maintenance, utilities'),
('Technology & IT', 'TECH', 'Computer equipment, software, internet'),
('Transportation', 'TRANS', 'School buses, transport expenses'),
('Food & Catering', 'FOOD', 'Canteen, food services'),
('Events & Activities', 'EVT', 'School events, sports, activities'),
('Insurance', 'INS', 'Insurance premiums'),
('Administrative Expenses', 'ADMIN', 'Office supplies, printing, postage'),
('Miscellaneous', 'MISC', 'Other miscellaneous expenses');

-- ============================================================
-- OPENING BALANCE TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS opening_balance (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    amount DECIMAL(15,2) NOT NULL,
    balance_date DATE NOT NULL,
    description TEXT,
    added_by INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (added_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_balance_date (balance_date)
) ENGINE=InnoDB;

-- ============================================================
-- EXPENSES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS expenses (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    voucher_no VARCHAR(50) NOT NULL UNIQUE,
    date DATE NOT NULL,
    category_id INT(11) NOT NULL,
    description TEXT NOT NULL,
    invoice_no VARCHAR(100),
    amount DECIMAL(15,2) NOT NULL,
    attachment VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected', 'released') DEFAULT 'pending',
    hr_approved_by INT(11),
    hr_approval_date DATETIME,
    admin_released_by INT(11),
    release_date DATETIME,
    rejection_reason TEXT,
    created_by INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (hr_approved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (admin_released_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_voucher (voucher_no),
    INDEX idx_date (date),
    INDEX idx_status (status),
    INDEX idx_category (category_id),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB;

-- ============================================================
-- APPROVALS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS approvals (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    expense_id INT(11) NOT NULL,
    approved_by INT(11) NOT NULL,
    approval_status ENUM('approved', 'rejected') NOT NULL,
    approval_date DATETIME NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (expense_id) REFERENCES expenses(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_expense (expense_id),
    INDEX idx_approved_by (approved_by)
) ENGINE=InnoDB;

-- ============================================================
-- ACTIVITY LOGS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11),
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- ============================================================
-- BALANCE ARCHIVE TABLE (Monthly closing)
-- ============================================================
CREATE TABLE IF NOT EXISTS balance_archive (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    archive_month DATE NOT NULL,
    opening_balance DECIMAL(15,2) NOT NULL,
    total_expenses DECIMAL(15,2) NOT NULL,
    total_released DECIMAL(15,2) NOT NULL,
    closing_balance DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_month (archive_month)
) ENGINE=InnoDB;

-- ============================================================
-- NOTIFICATIONS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    link VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read)
) ENGINE=InnoDB;

-- ============================================================
-- SETTINGS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS settings (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert default settings
INSERT INTO settings (setting_key, setting_value) VALUES 
('school_name', 'Loydence Academy British School'),
('school_location', 'Al Aziziyah, Qatar'),
('admin_name', 'Mr. Mohammad Ilyas'),
('hr_name', 'Sharifa Shaikh'),
('currency', 'QAR'),
('warning_balance', '1000'),
('academic_year', '2024-2025');

-- ============================================================
-- PROCEDURES FOR FINANCIAL CALCULATIONS
-- ============================================================

DELIMITER //

-- Procedure to get financial summary
CREATE PROCEDURE IF NOT EXISTS get_financial_summary()
BEGIN
    SELECT 
        (SELECT COALESCE(SUM(amount), 0) FROM opening_balance) AS opening_balance,
        (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE status = 'pending') AS pending_approval,
        (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE status = 'approved') AS approved_not_released,
        (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE status = 'released') AS total_released,
        (SELECT COALESCE(SUM(amount), 0) FROM opening_balance) - 
         (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE status = 'released') AS remaining_balance;
END //

-- Procedure to generate voucher number
CREATE PROCEDURE IF NOT EXISTS generate_voucher_no(OUT voucher_no VARCHAR(50))
BEGIN
    SET voucher_no = CONCAT('EXP-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', LPAD(FLOOR(RAND() * 10000), 4, '0'));
END //

DELIMITER ;

-- ============================================================
-- VIEWS FOR REPORTS
-- ============================================================

-- View: Expense with category details
CREATE OR REPLACE VIEW view_expenses_detail AS
SELECT 
    e.id,
    e.voucher_no,
    e.date,
    c.category_name,
    c.category_code,
    e.description,
    e.invoice_no,
    e.amount,
    e.status,
    e.hr_approved_by,
    u_hr.full_name AS hr_approver_name,
    e.hr_approval_date,
    e.admin_released_by,
    u_admin.full_name AS admin_releaser_name,
    e.release_date,
    e.rejection_reason,
    e.created_by,
    u_creator.full_name AS creator_name,
    e.created_at
FROM expenses e
LEFT JOIN categories c ON e.category_id = c.id
LEFT JOIN users u_hr ON e.hr_approved_by = u_hr.id
LEFT JOIN users u_admin ON e.admin_released_by = u_admin.id
LEFT JOIN users u_creator ON e.created_by = u_creator.id;

-- View: Financial summary
CREATE OR REPLACE VIEW view_financial_summary AS
SELECT 
    (SELECT COALESCE(SUM(amount), 0) FROM opening_balance) AS total_opening_balance,
    (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE status IN ('pending', 'approved', 'rejected', 'released')) AS total_expenses,
    (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE status = 'pending') AS pending_approval_amount,
    (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE status = 'approved') AS approved_not_released_amount,
    (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE status = 'released') AS total_released_amount,
    (SELECT COALESCE(SUM(amount), 0) FROM opening_balance) - 
     (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE status = 'released') AS remaining_balance;

-- ============================================================
-- TRIGGERS FOR AUTO LOGGING
-- ============================================================

DELIMITER //

-- Trigger: Log expense creation
CREATE TRIGGER IF NOT EXISTS trg_expense_created
AFTER INSERT ON expenses
FOR EACH ROW
BEGIN
    INSERT INTO activity_logs (user_id, action, details, ip_address)
    VALUES (NEW.created_by, 'expense_created', 
            CONCAT('New expense created: ', NEW.voucher_no, ' - Amount: ', NEW.amount),
            NULL);
END //

-- Trigger: Log expense approval
CREATE TRIGGER IF NOT EXISTS trg_expense_approved
AFTER UPDATE ON expenses
FOR EACH ROW
BEGIN
    IF NEW.status = 'approved' AND OLD.status != 'approved' THEN
        INSERT INTO activity_logs (user_id, action, details, ip_address)
        VALUES (NEW.hr_approved_by, 'expense_approved', 
                CONCAT('Expense approved: ', NEW.voucher_no, ' - Amount: ', NEW.amount),
                NULL);
    END IF;
    
    IF NEW.status = 'released' AND OLD.status != 'released' THEN
        INSERT INTO activity_logs (user_id, action, details, ip_address)
        VALUES (NEW.admin_released_by, 'funds_released', 
                CONCAT('Funds released: ', NEW.voucher_no, ' - Amount: ', NEW.amount),
                NULL);
    END IF;
END //

DELIMITER ;

-- ============================================================
-- EMPLOYEE MODULE TABLES
-- ============================================================

-- Employee Leave Types
CREATE TABLE IF NOT EXISTS employee_leave_types (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    leave_type_name VARCHAR(50) NOT NULL,
    leave_code VARCHAR(20) NOT NULL UNIQUE,
    description TEXT,
    days_per_year INT(11) DEFAULT 0,
    is_paid TINYINT(1) DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert default leave types
INSERT INTO employee_leave_types (leave_type_name, leave_code, description, days_per_year, is_paid) VALUES 
('Annual Leave', 'ANNUAL', 'Annual vacation leave', 30, 1),
('Sick Leave', 'SICK', 'Medical sick leave', 15, 1),
('Casual Leave', 'CASUAL', 'Casual personal leave', 10, 1),
('Unpaid Leave', 'UNPAID', 'Leave without pay', 0, 0),
('Maternity Leave', 'MATERNITY', 'Maternity leave for female staff', 60, 1),
('Paternity Leave', 'PATERNITY', 'Paternity leave for male staff', 7, 1),
('Emergency Leave', 'EMERGENCY', 'Emergency family leave', 5, 1);

-- Employees Table
CREATE TABLE IF NOT EXISTS employees (
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
) ENGINE=InnoDB;

-- Employee Attendance Table (QR-based)
CREATE TABLE IF NOT EXISTS employee_attendance (
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
) ENGINE=InnoDB;

-- Employee Salary Table
CREATE TABLE IF NOT EXISTS employee_salary (
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
) ENGINE=InnoDB;

-- Employee Deductions Table
CREATE TABLE IF NOT EXISTS employee_deductions (
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
) ENGINE=InnoDB;

-- Employee Overtime Table
CREATE TABLE IF NOT EXISTS employee_overtime (
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
) ENGINE=InnoDB;

-- Employee Leave Requests Table
CREATE TABLE IF NOT EXISTS employee_leave_requests (
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
) ENGINE=InnoDB;

-- Employee Leave Balance Table
CREATE TABLE IF NOT EXISTS employee_leave_balance (
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
) ENGINE=InnoDB;

-- ============================================================
-- VIEWS FOR EMPLOYEE MODULE
-- ============================================================

-- View: Employee Details with User
CREATE OR REPLACE VIEW view_employees AS
SELECT 
    e.*,
    u.username,
    u.email as user_email,
    u.is_active as user_is_active
FROM employees e
LEFT JOIN users u ON e.user_id = u.id;

-- View: Employee Attendance Summary
CREATE OR REPLACE VIEW view_employee_attendance_summary AS
SELECT 
    employee_id,
    YEAR(attendance_date) as year,
    MONTH(attendance_date) as month,
    COUNT(CASE WHEN status = 'present' THEN 1 END) as present_days,
    COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_days,
    COUNT(CASE WHEN status = 'late' THEN 1 END) as late_days,
    COUNT(CASE WHEN status = 'leave' THEN 1 END) as leave_days,
    SUM(late_minutes) as total_late_minutes,
    SUM(overtime_hours) as total_overtime_hours
FROM employee_attendance
GROUP BY employee_id, YEAR(attendance_date), MONTH(attendance_date);

-- View: Leave Requests with Details
CREATE OR REPLACE VIEW view_leave_requests AS
SELECT 
    lr.*,
    e.full_name as employee_name,
    e.employee_code,
    e.department,
    lt.leave_type_name,
    lt.leave_code,
    lt.is_paid,
    u.full_name as approver_name
FROM employee_leave_requests lr
JOIN employees e ON lr.employee_id = e.id
JOIN employee_leave_types lt ON lr.leave_type_id = lt.id
LEFT JOIN users u ON lr.approved_by = u.id;

-- View: Salary Details
CREATE OR REPLACE VIEW view_employee_salary AS
SELECT 
    es.*,
    e.full_name as employee_name,
    e.employee_code,
    e.department,
    e.designation,
    u.full_name as generated_by_name
FROM employee_salary es
JOIN employees e ON es.employee_id = e.id
LEFT JOIN users u ON es.generated_by = u.id;

-- ============================================================
-- PROCEDURES FOR EMPLOYEE MODULE
-- ============================================================

DELIMITER //

-- Procedure to calculate employee attendance for a month
CREATE PROCEDURE IF NOT EXISTS calculate_monthly_attendance(
    IN p_employee_id INT(11),
    IN p_year INT(11),
    IN p_month INT(11)
)
BEGIN
    SELECT 
        COUNT(CASE WHEN status = 'present' THEN 1 END) as present_days,
        COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_days,
        COUNT(CASE WHEN status = 'late' THEN 1 END) as late_days,
        COUNT(CASE WHEN status = 'leave' THEN 1 END) as leave_days,
        SUM(late_minutes) as total_late_minutes,
        SUM(overtime_hours) as total_overtime_hours
    FROM employee_attendance
    WHERE employee_id = p_employee_id 
        AND YEAR(attendance_date) = p_year 
        AND MONTH(attendance_date) = p_month;
END //

-- Procedure to generate monthly salary
CREATE PROCEDURE IF NOT EXISTS generate_monthly_salary(
    IN p_employee_id INT(11),
    IN p_year INT(11),
    IN p_month INT(11),
    OUT p_net_salary DECIMAL(12,2)
)
BEGIN
    DECLARE v_base_salary DECIMAL(12,2);
    DECLARE v_allowances DECIMAL(12,2) DEFAULT 0;
    DECLARE v_overtime_amount DECIMAL(12,2) DEFAULT 0;
    DECLARE v_bonus DECIMAL(12,2) DEFAULT 0;
    DECLARE v_tax_deduction DECIMAL(12,2) DEFAULT 0;
    DECLARE v_other_deductions DECIMAL(12,2) DEFAULT 0;
    DECLARE v_total_deductions DECIMAL(12,2) DEFAULT 0;
    DECLARE v_gross_salary DECIMAL(12,2);
    DECLARE v_present_days INT(11);
    DECLARE v_working_days INT(11) DEFAULT 30;
    
    -- Get base salary
    SELECT base_salary INTO v_base_salary FROM employees WHERE id = p_employee_id;
    
    -- Get attendance for the month
    SELECT 
        COALESCE(COUNT(CASE WHEN status IN ('present', 'late') THEN 1 END), 0),
        COALESCE(SUM(overtime_amount), 0)
    INTO v_present_days, v_overtime_amount
    FROM employee_overtime
    WHERE employee_id = p_employee_id 
        AND YEAR(overtime_date) = p_year 
        AND MONTH(overtime_date) = p_month
        AND status = 'approved';
    
    -- Calculate deductions based on absent days
    SET v_other_deductions = (v_base_salary / v_working_days) * (v_working_days - v_present_days);
    
    -- Calculate tax (simplified - 5% of gross)
    SET v_gross_salary = v_base_salary + v_allowances + v_overtime_amount + v_bonus;
    SET v_tax_deduction = v_gross_salary * 0.05;
    
    SET v_total_deductions = v_tax_deduction + v_other_deductions;
    SET p_net_salary = v_gross_salary - v_total_deductions;
    
    -- Insert or update salary record
    INSERT INTO employee_salary (employee_id, salary_month, base_salary, allowances, overtime_amount, bonus, gross_salary, tax_deduction, other_deductions, total_deductions, net_salary)
    VALUES (p_employee_id, CONCAT(p_year, '-', LPAD(p_month, 2, '0'), '-01'), v_base_salary, v_allowances, v_overtime_amount, v_bonus, v_gross_salary, v_tax_deduction, v_other_deductions, v_total_deductions, p_net_salary)
    ON DUPLICATE KEY UPDATE 
        base_salary = VALUES(base_salary),
        allowances = VALUES(allowances),
        overtime_amount = VALUES(overtime_amount),
        bonus = VALUES(bonus),
        gross_salary = VALUES(gross_salary),
        tax_deduction = VALUES(tax_deduction),
        other_deductions = VALUES(other_deductions),
        total_deductions = VALUES(total_deductions),
        net_salary = VALUES(net_salary);
END //

DELIMITER ;

-- ============================================================
-- TRIGGERS FOR EMPLOYEE MODULE
-- ============================================================

DELIMITER //

-- Trigger: Update leave balance when leave is approved
CREATE TRIGGER IF NOT EXISTS trg_leave_approved
AFTER UPDATE ON employee_leave_requests
FOR EACH ROW
BEGIN
    IF NEW.status = 'approved' AND OLD.status != 'approved' THEN
        UPDATE employee_leave_balance 
        SET used_days = used_days + NEW.total_days,
            remaining_days = total_days - (used_days + NEW.total_days)
        WHERE employee_id = NEW.employee_id 
            AND leave_type_id = NEW.leave_type_id 
            AND year = YEAR(NEW.start_date);
        
        -- Mark attendance as leave for the leave period
        UPDATE employee_attendance 
        SET status = 'leave'
        WHERE employee_id = NEW.employee_id 
            AND attendance_date BETWEEN NEW.start_date AND NEW.end_date;
    END IF;
END //

-- Trigger: Auto-create leave balance for new employees
CREATE TRIGGER IF NOT EXISTS trg_employee_created
AFTER INSERT ON employees
FOR EACH ROW
BEGIN
    INSERT INTO employee_leave_balance (employee_id, leave_type_id, year, total_days, used_days, remaining_days)
    SELECT NEW.id, id, YEAR(NOW()), days_per_year, 0, days_per_year
    FROM employee_leave_types 
    WHERE is_active = 1 AND days_per_year > 0;
END //

DELIMITER ;

-- ============================================================
-- END OF DATABASE SCHEMA
-- ============================================================

