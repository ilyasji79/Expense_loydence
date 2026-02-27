# School Expense Management ERP System - Plan

## Project: Loydence Academy British School - Expense Management System

---

## 📋 INFORMATION GATHERED

### Project Overview
- **School Name**: Loydence Academy British School – Al Aziziyah, Qatar
- **Admin**: Mr. Mohammad Ilyas (Full access, fund release)
- **HR Manager**: Sharifa Shaikh (Approval/Rejection only)
- **Technology**: PHP + MySQL (XAMPP)

### Core Workflow
1. **Admin** adds expense → Status: "Pending HR Approval"
2. **HR Manager** approves/rejects → Status: "Approved by HR" or "Rejected"
3. **Admin** releases funds → Status: "Funds Released" → Deduct from Opening Balance

### Financial Cards Required
- Opening Balance
- Pending HR Approval Amount
- Approved But Not Released
- Total Released Funds
- Remaining Balance (Red alert if < 1000 QAR)

### Dashboard Analytics
- Pie Chart: Expense Category Distribution
- Bar Chart: Monthly Released Funds
- Line Graph: Expense Trend
- Approval Status Counter
- Use Chart.js

### PDF Report Requirements
- Header with School Name, Logo, HR/Admin names, Date Range
- Financial Summary Section
- Expense Table with all columns
- Signature Section at bottom

### Security Requirements
- Role-based access control
- Session validation
- Password hashing (bcrypt)
- CSRF protection
- Prepared statements (PDO)
- Audit log tracking

---

## 🗂️ PROJECT STRUCTURE

```
/Expense_loydence/
├── config/
│   ├── config.php          # Database configuration
│   └── constants.php       # System constants
├── includes/
│   ├── db.php              # Database connection (PDO)
│   ├── functions.php       # Helper functions
│   ├── auth.php            # Authentication functions
│   └── logger.php          # Activity log functions
├── assets/
│   ├── css/
│   │   ├── style.css       # Main styles
│   │   ├── sidebar.css     # Sidebar styles
│   │   └── responsive.css  # Responsive styles
│   ├── js/
│   │   ├── main.js         # Main JavaScript
│   │   ├── dashboard.js    # Dashboard charts
│   │   └── ajax.js         # AJAX functions
│   └── images/
│       └── logo.png        # School logo
├── admin/
│   ├── dashboard.php       # Admin dashboard
│   ├── expenses.php        # Manage expenses
│   ├── add_expense.php     # Add new expense
│   ├── edit_expense.php    # Edit expense
│   ├── delete_expense.php  # Delete expense
│   ├── release_funds.php  # Release funds
│   ├── users.php           # Manage users
│   ├── reports.php         # Generate reports
│   ├── backup.php         # Database backup
│   └── opening_balance.php # Manage opening balance
├── hr/
│   ├── dashboard.php       # HR dashboard
│   ├── pending_expenses.php # Pending approvals
│   ├── approve_expense.php # Approve expense
│   ├── reject_expense.php  # Reject expense
│   └── reports.php         # HR reports
├── reports/
│   ├── pdf_report.php      # PDF generation
│   └── export_excel.php    # Excel export
├── uploads/
│   └── invoices/           # Invoice uploads
├── login.php               # Login page
├── logout.php              # Logout script
├── index.php               # Redirect to login/dashboard
└── database.sql            # Database schema

```

---

## 📦 DATABASE STRUCTURE

### Tables Required

1. **users**
   - id, username, password, email, role_id, created_at

2. **roles**
   - id, role_name (admin, hr_manager)

3. **categories**
   - id, category_name, description

4. **opening_balance**
   - id, amount, date, added_by, created_at

5. **expenses**
   - id, voucher_no, date, category_id, description, invoice_no, amount
   - status (pending, approved, rejected, released)
   - hr_approved_by, hr_approval_date
   - admin_released_by, release_date
   - rejection_reason, created_at

6. **approvals**
   - id, expense_id_by, approval, approved_date, status, notes

7. **activity_logs**
   - id, user_id, action, details, created_at

---

## 📝 EDIT PLAN

### Phase 1: Configuration & Database
1. Create config/config.php includes/db.php (PDO connection)
3. Create database.sql with
2. Create all tables

### Phase 2: Core Functions
4. Create includes/functions.php
5. Create includes/auth.php
6. Create includes/logger.php

### Phase 3: Authentication
7. Create login.php
8. Create logout.php
9. Create session management

### Phase 4: Admin Module
10. Create admin/dashboard.php
11. Create admin/opening_balance.php
12. Create admin/expenses.php
13. Create admin/add_expense.php
14. Create admin/edit_expense.php
15. Create admin/release_funds.php
16. Create admin/users.php

### Phase 5: HR Module
17. Create hr/dashboard.php
18. Create hr/pending_expenses.php
19. Create hr/approve_expense.php

### Phase 6: Reports
20. Create reports/pdf_report.php

### Phase 7: Assets & UI
21. Create assets/css/style.css
22. Create assets/js/main.js
23. Create assets/js/dashboard.js

### Phase 8: Testing & Validation
24. Test workflow
25. Verify financial calculations

---

## 🔄 DEPENDENT FILES TO BE EDITED

All files need to be created from scratch since the directory is empty.

---

## ✅ FOLLOWUP STEPS

1. Create database.sql file with complete schema
2. Create all PHP files with proper functionality
3. Create CSS/JS assets
4. Test the complete workflow
5. Verify PDF report generation with TCPDF library

