# Fullscreen Toggle Implementation - TODO

## Task
Add a fullscreen toggle icon to the header of authenticated pages (admin, HR dashboards and related internal pages) that uses JavaScript Fullscreen API to enter and exit fullscreen mode, works on all modern browsers, is fully responsive, and does not affect existing layout or backend functionality.

## Implementation Plan

### 1. Update responsive.js
- [x] Improve initFullscreenToggle() to handle pages without .top-header
- [x] Add check to skip creating floating button on login page
- [x] Create floating fullscreen button only for authenticated pages with .top-header

### 2. Login Page
- [x] Removed responsive.js from login.php (fullscreen toggle only for authenticated pages)

### 3. Update Admin Pages
- [x] admin/dashboard.php (already had responsive.js)
- [x] admin/expenses.php (already had responsive.js)
- [x] admin/add_expense.php
- [x] admin/edit_expense.php
- [x] admin/release_funds.php
- [x] admin/reports.php
- [x] admin/users.php
- [x] admin/backup.php (already had responsive.js)
- [x] admin/activity_logs.php
- [x] admin/opening_balance.php

### 4. Update HR Pages
- [x] hr/dashboard.php (already had responsive.js)
- [x] hr/pending_expenses.php
- [x] hr/approved_expenses.php
- [x] hr/reports.php

## Status: COMPLETED
Fullscreen toggle is now implemented only on authenticated pages (admin and HR dashboards and related internal pages).

