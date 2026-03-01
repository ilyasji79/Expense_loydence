# Employee Payroll and Attendance Module - Implementation Plan

## 1. Information Gathered

### Existing System Analysis:
- **Technology Stack**: PHP, MySQL, Bootstrap 5, JavaScript, Chart.js
- **Authentication**: Session-based with bcrypt password hashing
- **Roles**: admin, hr_manager
- **Database**: Uses views, stored procedures, triggers
- **Design System**: CSS variables, responsive design, gradient sidebar

### Key Files Examined:
- `database.sql` - Database schema with users, roles, expenses
- `includes/auth.php` - Authentication functions (login, logout, roles)
- `includes/functions.php` - Helper functions (formatCurrency, redirect, etc.)
- `config/config.php` - Database config, BASE_URL, timezone
- `admin/dashboard.php` - Admin dashboard structure
- `hr/dashboard.php` - HR dashboard structure
- `login.php` - Login page with form validation

### Current User Flow:
1. Login → Role-based redirect
2. Admin → admin/dashboard.php
3. HR → hr/dashboard.php
4. Employee → Not implemented (needs new role & dashboard)

---

## 2. Implementation Plan

### Phase 1: Database Changes (database.sql additions)

#### New Roles:
- Add 'employee' role to roles table

#### New Tables:
1. **employees** - Employee personal & job details
2. **employee_attendance** - QR-based attendance records
3. **employee_salary** - Salary records with components
4. **employee_leave_requests** - Leave applications
5. **employee_leave_types** - Leave type definitions
6. **employee_deductions** - Tax, fines, advances
7. **employee_overtime** - Overtime records

### Phase 2: New Core Files

#### Employee Login:
- `employee/login.php` - Dedicated employee login page

#### Employee Dashboard:
- `employee/dashboard.php` - Personal dashboard

#### Admin HR Module Pages:
- `hr/employees.php` - Manage employees
- `hr/attendance.php` - View/manage attendance
- `hr/payroll.php` - Payroll management
- `hr/salary_slips.php` - Generate salary slips
- `hr/leave_requests.php` - Approve/reject leaves
- `hr/employee_reports.php` - Reports dashboard

### Phase 3: Backend Functions (new includes)

#### New Functions Files:
- `includes/employee_auth.php` - Employee-specific auth
- `includes/employee_functions.php` - Employee business logic

### Phase 4: Updates to Existing Files

1. **database.sql** - Add new tables and role
2. **dashboard.php** - Add employee role redirect
3. **login.php** - Add employee login option
4. **admin/dashboard.php** - Add employee menu items
5. **hr/dashboard.php** - Add employee menu items

### Phase 5: PDF Generation
- `hr/generate_salary_slip.php` - TCPDF-based salary slip

---

## 3. Detailed File-Level Plan

### 3.1 Database Updates (database.sql)

```sql
-- Add employee role
INSERT INTO roles (role_name, role_description) VALUES 
('employee', 'Employee - View personal dashboard, attendance, salary, request leave');

-- New tables with full schema:
-- employees, employee_attendance, employee_salary, employee_leave_requests,
-- employee_leave_types, employee_deductions, employee_overtime
```

### 3.2 New Files to Create

| File | Purpose |
|------|---------|
| employee/login.php | Employee login page |
| employee/dashboard.php | Employee self-service dashboard |
| hr/employees.php | HR manage employees CRUD |
| hr/attendance.php | Attendance management |
| hr/payroll.php | Payroll calculation & management |
| hr/salary_slips.php | Salary slip generation |
| hr/leave_requests.php | Leave approval workflow |
| hr/employee_reports.php | Reports & exports |
| includes/employee_auth.php | Employee auth functions |
| includes/employee_functions.php | Employee business logic |

### 3.3 Files to Modify

| File | Changes |
|------|---------|
| database.sql | Add new tables, role |
| dashboard.php | Add employee redirect |
| login.php | Add employee login link |
| admin/dashboard.php | Add Payroll menu |
| hr/dashboard.php | Add Employees menu |
| config/config.php | Add employee BASE_URL |

---

## 4. Features by Module

### 4.1 Employee Login
- Secure login with username/password
- Session management
- Password change capability

### 4.2 Employee Dashboard
- Personal details display
- Attendance summary (Present/Absent/Late)
- Current month salary preview
- Leave balance display
- Leave request status
- Quick actions

### 4.3 Attendance Module
- QR code integration placeholder
- Daily attendance marking
- Monthly summary (Present/Absent/Late)
- Overtime tracking
- Leave auto-calculation

### 4.4 Payroll Module
- Base salary + allowances
- Deductions (tax, fines, advances)
- Overtime pay calculation
- Net salary formula: Base + Allowances + Overtime - Deductions
- Multiple payment types
- Monthly salary slip PDF

### 4.5 Leave Management
- Leave request form
- Notice period validation
- HR approval workflow
- Automatic attendance adjustment

### 4.6 Role-Based Access
- **Admin**: Full employee/payroll control
- **HR**: Manage employees, approve leaves, generate payroll
- **Employee**: View own data only

---

## 5. Security Requirements

- All inputs sanitized
- SQL injection prevention (parameterized queries)
- Session timeout
- CSRF tokens
- Role-based page access control

---

## 6. Design Requirements

- Bootstrap 5 responsive
- Mobile-compatible
- Consistent with existing ERP styling
- Fullscreen toggle on all pages
- Charts for attendance/salary visualization

---

## 7. Follow-up Steps

1. Update database.sql with new tables
2. Create employee login page
3. Create employee authentication functions
4. Create employee dashboard
5. Create HR employee management pages
6. Create attendance, payroll, leave pages
7. Update existing dashboards with new menus
8. Test all functionality
9. Export and verify database

---

## 8. Implementation Order

1. Database schema (tables + data)
2. Employee login page
3. Employee authentication functions
4. Employee dashboard
5. HR employee management (CRUD)
6. Attendance module
7. Payroll module
8. Leave management
9. Reports & exports
10. Integration & testing

