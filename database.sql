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
('hr_manager', 'HR Manager - Approve/Reject expenses, View reports only');

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
-- END OF DATABASE SCHEMA
-- ============================================================

