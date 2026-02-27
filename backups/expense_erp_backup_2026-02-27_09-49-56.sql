-- Database Backup
-- School: Loydence Academy British School
-- Date: 2026-02-27 09:49:56
-- 

SET FOREIGN_KEY_CHECKS=0;


-- Table: activity_logs
DROP TABLE IF EXISTS `activity_logs`;
;

INSERT INTO `activity_logs` VALUES ('1', '2', 'user_login', 'User logged in: hr', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-27 09:19:50');
INSERT INTO `activity_logs` VALUES ('2', '2', 'report_generated', 'Generated HR Expense Report report', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-27 09:21:10');
INSERT INTO `activity_logs` VALUES ('3', '2', 'user_logout', 'User logged out: hr', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-27 09:21:32');
INSERT INTO `activity_logs` VALUES ('4', '1', 'user_login', 'User logged in: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-27 09:21:44');
INSERT INTO `activity_logs` VALUES ('5', '1', 'opening_balance_updated', 'Updated opening balance to: 7500 QAR', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-27 09:24:41');
INSERT INTO `activity_logs` VALUES ('6', '1', 'expense_created', 'New expense created: EXP-20260227-0001 - Amount: 138.00', NULL, NULL, '2026-02-27 09:26:09');
INSERT INTO `activity_logs` VALUES ('7', '1', 'expense_created', 'Created expense voucher: EXP-20260227-0001, Amount: 138 QAR', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-27 09:26:09');
INSERT INTO `activity_logs` VALUES ('8', '2', 'user_login', 'User logged in: hr', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-27 09:27:11');
INSERT INTO `activity_logs` VALUES ('9', '2', 'expense_approved', 'Expense approved: EXP-20260227-0001 - Amount: 138.00', NULL, NULL, '2026-02-27 09:27:22');
INSERT INTO `activity_logs` VALUES ('10', '2', 'expense_approved', 'Approved expense: EXP-20260227-0001, Amount: 138.00 QAR', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-27 09:27:22');
INSERT INTO `activity_logs` VALUES ('11', '1', 'funds_released', 'Funds released: EXP-20260227-0001 - Amount: 138.00', NULL, NULL, '2026-02-27 09:28:29');
INSERT INTO `activity_logs` VALUES ('12', '1', 'funds_released', 'Released funds for voucher: EXP-20260227-0001, Amount: 138.00 QAR', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-27 09:28:29');
INSERT INTO `activity_logs` VALUES ('13', '1', 'report_generated', 'Generated Expense Report report', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-27 09:29:32');
INSERT INTO `activity_logs` VALUES ('14', '2', 'report_generated', 'Generated HR Expense Report report', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-27 09:33:48');
INSERT INTO `activity_logs` VALUES ('15', '2', 'report_generated', 'Generated HR Expense Report report', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-27 09:34:39');
INSERT INTO `activity_logs` VALUES ('16', '2', 'report_generated', 'Generated HR Expense Report report', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-27 09:35:57');
INSERT INTO `activity_logs` VALUES ('17', '1', 'report_generated', 'Generated Expense Report report', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-27 09:37:32');
INSERT INTO `activity_logs` VALUES ('18', '2', 'report_generated', 'Generated HR Expense Report report', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-27 09:37:40');
INSERT INTO `activity_logs` VALUES ('19', '2', 'report_generated', 'Generated HR Expense Report report', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-27 09:37:43');

-- Table: approvals
DROP TABLE IF EXISTS `approvals`;
;

INSERT INTO `approvals` VALUES ('1', '1', '2', 'approved', '2026-02-27 09:27:22', NULL, '2026-02-27 09:27:22');

-- Table: balance_archive
DROP TABLE IF EXISTS `balance_archive`;
;


-- Table: categories
DROP TABLE IF EXISTS `categories`;
;

INSERT INTO `categories` VALUES ('1', 'Salaries & Wages', 'SAL', 'Staff salaries and wages', '1', '2026-02-27 09:14:32');
INSERT INTO `categories` VALUES ('2', 'Teaching Materials', 'TM', 'Books, stationery, educational materials', '1', '2026-02-27 09:14:32');
INSERT INTO `categories` VALUES ('3', 'Infrastructure & Maintenance', 'INF', 'Building repairs, maintenance, utilities', '1', '2026-02-27 09:14:32');
INSERT INTO `categories` VALUES ('4', 'Technology & IT', 'TECH', 'Computer equipment, software, internet', '1', '2026-02-27 09:14:32');
INSERT INTO `categories` VALUES ('5', 'Transportation', 'TRANS', 'School buses, transport expenses', '1', '2026-02-27 09:14:32');
INSERT INTO `categories` VALUES ('6', 'Food & Catering', 'FOOD', 'Canteen, food services', '1', '2026-02-27 09:14:32');
INSERT INTO `categories` VALUES ('7', 'Events & Activities', 'EVT', 'School events, sports, activities', '1', '2026-02-27 09:14:32');
INSERT INTO `categories` VALUES ('8', 'Insurance', 'INS', 'Insurance premiums', '1', '2026-02-27 09:14:32');
INSERT INTO `categories` VALUES ('9', 'Administrative Expenses', 'ADMIN', 'Office supplies, printing, postage', '1', '2026-02-27 09:14:32');
INSERT INTO `categories` VALUES ('10', 'Miscellaneous', 'MISC', 'Other miscellaneous expenses', '1', '2026-02-27 09:14:32');

-- Table: expenses
DROP TABLE IF EXISTS `expenses`;
;

INSERT INTO `expenses` VALUES ('1', 'EXP-20260227-0001', '2026-02-27', '5', 'school bus fuel', '6383', '138.00', NULL, 'released', '2', '2026-02-27 09:27:22', '1', '2026-02-27 09:28:29', NULL, '1', '2026-02-27 09:26:09', '2026-02-27 09:28:29');

-- Table: notifications
DROP TABLE IF EXISTS `notifications`;
;

INSERT INTO `notifications` VALUES ('1', '2', 'New Expense Pending', 'New expense EXP-20260227-0001 of 138 QAR is pending your approval.', '0', '../hr/pending_expenses.php', '2026-02-27 09:26:09');
INSERT INTO `notifications` VALUES ('2', '1', 'Expense Approved', 'Expense EXP-20260227-0001 has been approved by HR. Ready for fund release.', '0', '../admin/release_funds.php', '2026-02-27 09:27:22');
INSERT INTO `notifications` VALUES ('3', '1', 'Funds Released', 'Funds of 138.00 QAR have been released for EXP-20260227-0001.', '0', 'expenses.php', '2026-02-27 09:28:29');

-- Table: opening_balance
DROP TABLE IF EXISTS `opening_balance`;
;

INSERT INTO `opening_balance` VALUES ('1', '7500.00', '2026-02-22', 'cash given by HR-Manger', '1', '2026-02-27 09:24:41');

-- Table: roles
DROP TABLE IF EXISTS `roles`;
;

INSERT INTO `roles` VALUES ('1', 'admin', 'Full system access - Add/Edit/Delete expenses, Release funds, Generate reports', '2026-02-27 09:14:32');
INSERT INTO `roles` VALUES ('2', 'hr_manager', 'HR Manager - Approve/Reject expenses, View reports only', '2026-02-27 09:14:32');

-- Table: settings
DROP TABLE IF EXISTS `settings`;
;

INSERT INTO `settings` VALUES ('1', 'school_name', 'Loydence Academy British School', '2026-02-27 09:14:33');
INSERT INTO `settings` VALUES ('2', 'school_location', 'Al Aziziyah, Qatar', '2026-02-27 09:14:33');
INSERT INTO `settings` VALUES ('3', 'admin_name', 'Mr. Mohammad Ilyas', '2026-02-27 09:14:33');
INSERT INTO `settings` VALUES ('4', 'hr_name', 'Sharifa Shaikh', '2026-02-27 09:14:33');
INSERT INTO `settings` VALUES ('5', 'currency', 'QAR', '2026-02-27 09:14:33');
INSERT INTO `settings` VALUES ('6', 'warning_balance', '1000', '2026-02-27 09:14:33');
INSERT INTO `settings` VALUES ('7', 'academic_year', '2024-2025', '2026-02-27 09:14:33');

-- Table: users
DROP TABLE IF EXISTS `users`;
;

INSERT INTO `users` VALUES ('1', 'admin', '$2y$10$Vw396PzaBq/nhLp3Gc2yJeAsdboVzPSvItiw8kW9XKk2P6cBJOWkS', 'admin@loydence.edu.qa', 'Mr. Mohammad Ilyas', '1', NULL, '1', '2026-02-27 09:14:32', '2026-02-27 09:19:09');
INSERT INTO `users` VALUES ('2', 'hr', '$2y$10$Vw396PzaBq/nhLp3Gc2yJeAsdboVzPSvItiw8kW9XKk2P6cBJOWkS', 'hr@loydence.edu.qa', 'Sharifa Shaikh', '2', NULL, '1', '2026-02-27 09:14:32', '2026-02-27 09:19:09');

-- Table: view_expenses_detail
DROP TABLE IF EXISTS `view_expenses_detail`;
;

INSERT INTO `view_expenses_detail` VALUES ('1', 'EXP-20260227-0001', '2026-02-27', 'Transportation', 'TRANS', 'school bus fuel', '6383', '138.00', 'released', '2', 'Sharifa Shaikh', '2026-02-27 09:27:22', '1', 'Mr. Mohammad Ilyas', '2026-02-27 09:28:29', NULL, '1', 'Mr. Mohammad Ilyas', '2026-02-27 09:26:09');

-- Table: view_financial_summary
DROP TABLE IF EXISTS `view_financial_summary`;
;

INSERT INTO `view_financial_summary` VALUES ('7500.00', '138.00', '0.00', '0.00', '138.00', '7362.00');

SET FOREIGN_KEY_CHECKS=1;
