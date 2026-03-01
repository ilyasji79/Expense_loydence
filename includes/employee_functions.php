<?php
/**
 * Employee Business Functions
 * Expense Management ERP - Loydence Academy
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/employee_auth.php';

// ============================================================
// ATTENDANCE FUNCTIONS
// ============================================================

// Mark attendance (for QR scan)
function markAttendance($db, $employeeId, $date, $checkInTime, $checkOutTime = null, $qrCode = '', $location = '') {
    // Check if already marked
    $existing = $db->fetch("
        SELECT id FROM employee_attendance 
        WHERE employee_id = ? AND attendance_date = ?
    ", [$employeeId, $date]);
    
    if ($existing) {
        return ['success' => false, 'message' => 'Attendance already marked for this date'];
    }
    
    // Calculate late minutes (assuming work starts at 8:00 AM)
    $lateMinutes = 0;
    $status = 'present';
    $workStartTime = '08:00:00';
    
    if ($checkInTime > $workStartTime) {
        $checkIn = strtotime($checkInTime);
        $workStart = strtotime($workStartTime);
        $lateMinutes = round(($checkIn - $workStart) / 60);
        $status = 'late';
    }
    
    // Calculate overtime if check out time is provided
    $overtimeHours = 0;
    if ($checkOutTime) {
        $workEndTime = '16:00:00';
        if ($checkOutTime > $workEndTime) {
            $checkOut = strtotime($checkOutTime);
            $workEnd = strtotime($workEndTime);
            $overtimeHours = round(($checkOut - $workEnd) / 3600, 2);
        }
    }
    
    $db->query("
        INSERT INTO employee_attendance (
            employee_id, attendance_date, check_in_time, check_out_time,
            late_minutes, overtime_hours, status, qr_code, location
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ", [
        $employeeId, $date, $checkInTime, $checkOutTime,
        $lateMinutes, $overtimeHours, $status, $qrCode, $location
    ]);
    
    return ['success' => true, 'message' => 'Attendance marked successfully'];
}

// Get attendance for employee
function getEmployeeAttendance($db, $employeeId, $year = null, $month = null) {
    $year = $year ?? date('Y');
    $month = $month ?? date('m');
    
    return $db->fetchAll("
        SELECT * FROM employee_attendance
        WHERE employee_id = ? 
            AND YEAR(attendance_date) = ? 
            AND MONTH(attendance_date) = ?
        ORDER BY attendance_date DESC
    ", [$employeeId, $year, $month]);
}

// Get attendance summary for a month
function getAttendanceSummary($db, $employeeId, $year = null, $month = null) {
    $year = $year ?? date('Y');
    $month = $month ?? date('m');
    
    return $db->fetch("
        SELECT 
            COUNT(CASE WHEN status = 'present' THEN 1 END) as present_days,
            COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_days,
            COUNT(CASE WHEN status = 'late' THEN 1 END) as late_days,
            COUNT(CASE WHEN status = 'leave' THEN 1 END) as leave_days,
            COUNT(CASE WHEN status = 'holiday' THEN 1 END) as holiday_days,
            COALESCE(SUM(late_minutes), 0) as total_late_minutes,
            COALESCE(SUM(overtime_hours), 0) as total_overtime_hours,
            COUNT(*) as total_days
        FROM employee_attendance
        WHERE employee_id = ? 
            AND YEAR(attendance_date) = ? 
            AND MONTH(attendance_date) = ?
    ", [$employeeId, $year, $month]);
}

// Record manual attendance (admin/HR)
function recordManualAttendance($db, $employeeId, $date, $checkIn, $checkOut, $status, $notes = '') {
    // Calculate late minutes
    $lateMinutes = 0;
    if ($status !== 'absent' && $checkIn > '08:00:00') {
        $checkInTs = strtotime($checkIn);
        $workStartTs = strtotime('08:00:00');
        $lateMinutes = round(($checkInTs - $workStartTs) / 60);
    }
    
    // Calculate overtime
    $overtimeHours = 0;
    if ($checkOut && $checkOut > '16:00:00') {
        $checkOutTs = strtotime($checkOut);
        $workEndTs = strtotime('16:00:00');
        $overtimeHours = round(($checkOutTs - $workEndTs) / 3600, 2);
    }
    
    $db->query("
        INSERT INTO employee_attendance (
            employee_id, attendance_date, check_in_time, check_out_time,
            late_minutes, overtime_hours, status, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            check_in_time = VALUES(check_in_time),
            check_out_time = VALUES(check_out_time),
            late_minutes = VALUES(late_minutes),
            overtime_hours = VALUES(overtime_hours),
            status = VALUES(status),
            notes = VALUES(notes)
    ", [$employeeId, $date, $checkIn, $checkOut, $lateMinutes, $overtimeHours, $status, $notes]);
    
    return ['success' => true, 'message' => 'Attendance recorded successfully'];
}

// Get all attendance records for a date range
function getAttendanceRange($db, $employeeId, $startDate, $endDate) {
    return $db->fetchAll("
        SELECT * FROM employee_attendance
        WHERE employee_id = ? 
            AND attendance_date BETWEEN ? AND ?
        ORDER BY attendance_date DESC
    ", [$employeeId, $startDate, $endDate]);
}

// ============================================================
// SALARY FUNCTIONS
// ============================================================

// Get employee salary for a month
function getEmployeeSalary($db, $employeeId, $year = null, $month = null) {
    $year = $year ?? date('Y');
    $month = $month ?? date('m');
    $salaryMonth = "$year-" . str_pad($month, 2, '0') . "-01";
    
    return $db->fetch("
        SELECT * FROM employee_salary
        WHERE employee_id = ? AND salary_month = ?
    ", [$employeeId, $salaryMonth]);
}

// Get all salary records for employee
function getEmployeeSalaries($db, $employeeId, $limit = 12) {
    return $db->fetchAll("
        SELECT * FROM employee_salary
        WHERE employee_id = ?
        ORDER BY salary_month DESC
        LIMIT ?
    ", [$employeeId, $limit]);
}

// Calculate and generate salary
function generateSalary($db, $employeeId, $year, $month) {
    $employee = getEmployeeById($db, $employeeId);
    if (!$employee) {
        return ['success' => false, 'message' => 'Employee not found'];
    }
    
    $salaryMonth = "$year-" . str_pad($month, 2, '0') . "-01";
    
    // Get base salary
    $baseSalary = $employee['base_salary'];
    
    // Get attendance summary
    $attendance = getAttendanceSummary($db, $employeeId, $year, $month);
    $presentDays = $attendance['present_days'] + $attendance['late_days'];
    $workingDays = 30; // Assuming 30 days working month
    
    // Calculate daily rate
    $dailyRate = $baseSalary / $workingDays;
    
    // Calculate absent days deduction
    $absentDays = $attendance['absent_days'];
    $absentDeduction = $dailyRate * $absentDays;
    
    // Get overtime amount
    $overtimeAmount = getApprovedOvertimeAmount($db, $employeeId, $year, $month);
    
    // Get deductions
    $deductions = getActiveDeductions($db, $employeeId, $year . '-' . str_pad($month, 2, '0'));
    $totalDeductions = 0;
    foreach ($deductions as $d) {
        $totalDeductions += $d['amount'];
    }
    
    // Calculate tax (simplified - 5% of gross)
    $allowances = 0;
    $bonus = 0;
    $grossSalary = $baseSalary + $allowances + $overtimeAmount + $bonus;
    $taxDeduction = $grossSalary * 0.05;
    
    $totalDeductions += $taxDeduction + $absentDeduction;
    $netSalary = $grossSalary - $totalDeductions;
    
    // Insert or update salary record
    $db->query("
        INSERT INTO employee_salary (
            employee_id, salary_month, base_salary, allowances, overtime_amount,
            bonus, gross_salary, tax_deduction, other_deductions, total_deductions,
            net_salary, generated_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            base_salary = VALUES(base_salary),
            allowances = VALUES(allowances),
            overtime_amount = VALUES(overtime_amount),
            bonus = VALUES(bonus),
            gross_salary = VALUES(gross_salary),
            tax_deduction = VALUES(tax_deduction),
            other_deductions = VALUES(other_deductions),
            total_deductions = VALUES(total_deductions),
            net_salary = VALUES(net_salary),
            generated_by = VALUES(generated_by)
    ", [
        $employeeId, $salaryMonth, $baseSalary, $allowances, $overtimeAmount,
        $bonus, $grossSalary, $taxDeduction, $absentDeduction, $totalDeductions,
        $netSalary, $_SESSION['user_id'] ?? null
    ]);
    
    return [
        'success' => true,
        'message' => 'Salary generated successfully',
        'salary' => [
            'base_salary' => $baseSalary,
            'allowances' => $allowances,
            'overtime' => $overtimeAmount,
            'bonus' => $bonus,
            'gross_salary' => $grossSalary,
            'tax_deduction' => $taxDeduction,
            'absent_deduction' => $absentDeduction,
            'other_deductions' => $totalDeductions - $taxDeduction - $absentDeduction,
            'total_deductions' => $totalDeductions,
            'net_salary' => $netSalary
        ]
    ];
}

// Update salary payment status
function updateSalaryPayment($db, $salaryId, $paymentType, $paymentStatus, $paymentDate, $reference = '') {
    $db->query("
        UPDATE employee_salary SET
            payment_type = ?,
            payment_status = ?,
            payment_date = ?,
            payment_reference = ?
        WHERE id = ?
    ", [$paymentType, $paymentStatus, $paymentDate, $reference, $salaryId]);
    
    return ['success' => true, 'message' => 'Payment status updated'];
}

// ============================================================
// DEDUCTIONS FUNCTIONS
// ============================================================

// Add deduction
function addDeduction($db, $employeeId, $type, $name, $amount, $startDate, $endDate = null, $isRecurring = false, $notes = '') {
    $db->query("
        INSERT INTO employee_deductions (
            employee_id, deduction_type, deduction_name, amount,
            start_date, end_date, is_recurring, notes, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ", [
        $employeeId, $type, $name, $amount,
        $startDate, $endDate, $isRecurring ? 1 : 0, $notes,
        $_SESSION['user_id'] ?? null
    ]);
    
    return ['success' => true, 'message' => 'Deduction added successfully'];
}

// Get active deductions
function getActiveDeductions($db, $employeeId, $month = null) {
    $month = $month ?? date('Y-m');
    
    return $db->fetchAll("
        SELECT * FROM employee_deductions
        WHERE employee_id = ? 
            AND status = 'active'
            AND (
                is_recurring = 1 
                OR (start_date <= LAST_DAY(?) AND (end_date IS NULL OR end_date >= ?))
            )
    ", [$employeeId, $month . '-01', $month . '-01']);
}

// Stop deduction
function stopDeduction($db, $deductionId) {
    $db->query("UPDATE employee_deductions SET status = 'stopped' WHERE id = ?", [$deductionId]);
    return ['success' => true, 'message' => 'Deduction stopped'];
}

// ============================================================
// OVERTIME FUNCTIONS
// ============================================================

// Request overtime
function requestOvertime($db, $employeeId, $date, $hours, $hourlyRate, $reason = '') {
    $totalAmount = $hours * $hourlyRate;
    
    $db->query("
        INSERT INTO employee_overtime (
            employee_id, overtime_date, hours, hourly_rate, total_amount, reason
        ) VALUES (?, ?, ?, ?, ?, ?)
    ", [$employeeId, $date, $hours, $hourlyRate, $totalAmount, $reason]);
    
    return ['success' => true, 'message' => 'Overtime request submitted'];
}

// Approve/reject overtime
function approveOvertime($db, $overtimeId, $status) {
    $db->query("
        UPDATE employee_overtime SET
            status = ?,
            approved_by = ?,
            approved_at = NOW()
        WHERE id = ?
    ", [$status, $_SESSION['user_id'] ?? null, $overtimeId]);
    
    return ['success' => true, 'message' => 'Overtime ' . $status];
}

// Get approved overtime amount
function getApprovedOvertimeAmount($db, $employeeId, $year, $month) {
    $result = $db->fetch("
        SELECT COALESCE(SUM(total_amount), 0) as total
        FROM employee_overtime
        WHERE employee_id = ?
            AND YEAR(overtime_date) = ?
            AND MONTH(overtime_date) = ?
            AND status = 'approved'
    ", [$employeeId, $year, $month]);
    
    return $result['total'];
}

// Get overtime requests
function getOvertimeRequests($db, $employeeId = null, $status = null) {
    $sql = "SELECT eo.*, e.full_name as employee_name, e.employee_code 
            FROM employee_overtime eo 
            JOIN employees e ON eo.employee_id = e.id 
            WHERE 1=1";
    $params = [];
    
    if ($employeeId) {
        $sql .= " AND eo.employee_id = ?";
        $params[] = $employeeId;
    }
    
    if ($status) {
        $sql .= " AND eo.status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY eo.overtime_date DESC";
    
    return $db->fetchAll($sql, $params);
}

// ============================================================
// LEAVE FUNCTIONS
// ============================================================

// Get leave types
function getLeaveTypes($db, $activeOnly = true) {
    $sql = "SELECT * FROM employee_leave_types";
    if ($activeOnly) {
        $sql .= " WHERE is_active = 1";
    }
    return $db->fetchAll($sql);
}

// Get leave type by ID
function getLeaveTypeById($db, $id) {
    return $db->fetch("SELECT * FROM employee_leave_types WHERE id = ?", [$id]);
}

// Request leave
function requestLeave($db, $employeeId, $leaveTypeId, $startDate, $endDate, $reason = '') {
    // Validate dates
    if (strtotime($startDate) > strtotime($endDate)) {
        return ['success' => false, 'message' => 'End date must be after start date'];
    }
    
    // Check if dates are in the past
    if (strtotime($startDate) < strtotime(date('Y-m-d'))) {
        return ['success' => false, 'message' => 'Cannot request leave for past dates'];
    }
    
    // Calculate total days
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $totalDays = $end->diff($start)->days + 1;
    
    // Get leave type
    $leaveType = getLeaveTypeById($db, $leaveTypeId);
    if (!$leaveType) {
        return ['success' => false, 'message' => 'Invalid leave type'];
    }
    
    // Check leave balance (for paid leave)
    if ($leaveType['is_paid']) {
        $year = date('Y', strtotime($startDate));
        $balance = getLeaveBalance($db, $employeeId, $leaveTypeId, $year);
        
        if ($balance['remaining_days'] < $totalDays) {
            return ['success' => false, 'message' => 'Insufficient leave balance. Available: ' . $balance['remaining_days'] . ' days'];
        }
    }
    
    // Check minimum notice period (3 days)
    $daysUntilStart = (strtotime($startDate) - strtotime(date('Y-m-d'))) / (60 * 60 * 24);
    if ($totalDays > 1 && $daysUntilStart < 3) {
        return ['success' => false, 'message' => 'Minimum 3 days notice required for this leave'];
    }
    
    // Check for overlapping leave requests
    $overlap = $db->fetch("
        SELECT id FROM employee_leave_requests
        WHERE employee_id = ?
            AND status != 'rejected'
            AND (
                (start_date <= ? AND end_date >= ?)
                OR (start_date <= ? AND end_date >= ?)
                OR (start_date >= ? AND end_date <= ?)
            )
    ", [$employeeId, $endDate, $startDate, $startDate, $startDate, $startDate, $endDate]);
    
    if ($overlap) {
        return ['success' => false, 'message' => 'You already have a leave request for these dates'];
    }
    
    $db->query("
        INSERT INTO employee_leave_requests (
            employee_id, leave_type_id, start_date, end_date, total_days, reason
        ) VALUES (?, ?, ?, ?, ?, ?)
    ", [$employeeId, $leaveTypeId, $startDate, $endDate, $totalDays, $reason]);
    
    return ['success' => true, 'message' => 'Leave request submitted successfully'];
}

// Get leave balance
function getLeaveBalance($db, $employeeId, $leaveTypeId, $year) {
    $result = $db->fetch("
        SELECT * FROM employee_leave_balance
        WHERE employee_id = ? AND leave_type_id = ? AND year = ?
    ", [$employeeId, $leaveTypeId, $year]);
    
    if (!$result) {
        // Create balance for the year if not exists
        $leaveType = getLeaveTypeById($db, $leaveTypeId);
        $db->query("
            INSERT INTO employee_leave_balance (employee_id, leave_type_id, year, total_days, used_days, remaining_days)
            VALUES (?, ?, ?, ?, 0, ?)
        ", [$employeeId, $leaveTypeId, $year, $leaveType['days_per_year'], $leaveType['days_per_year']]);
        
        return [
            'total_days' => $leaveType['days_per_year'],
            'used_days' => 0,
            'remaining_days' => $leaveType['days_per_year']
        ];
    }
    
    return $result;
}

// Get all leave balances for employee
function getAllLeaveBalances($db, $employeeId, $year = null) {
    $year = $year ?? date('Y');
    
    return $db->fetchAll("
        SELECT lb.*, lt.leave_type_name, lt.leave_code, lt.is_paid
        FROM employee_leave_balance lb
        JOIN employee_leave_types lt ON lb.leave_type_id = lt.id
        WHERE lb.employee_id = ? AND lb.year = ?
        ORDER BY lt.leave_type_name
    ", [$employeeId, $year]);
}

// Get leave requests for employee
function getEmployeeLeaveRequests($db, $employeeId, $status = null) {
    $sql = "SELECT lr.*, lt.leave_type_name, lt.leave_code 
            FROM employee_leave_requests lr
            JOIN employee_leave_types lt ON lr.leave_type_id = lt.id
            WHERE lr.employee_id = ?";
    $params = [$employeeId];
    
    if ($status) {
        $sql .= " AND lr.status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY lr.created_at DESC";
    
    return $db->fetchAll($sql, $params);
}

// Get all leave requests (for HR)
function getAllLeaveRequests($db, $status = null, $employeeId = null) {
    $sql = "SELECT lr.*, e.full_name as employee_name, e.employee_code, e.department,
                   lt.leave_type_name, lt.leave_code, u.full_name as approver_name
            FROM employee_leave_requests lr
            JOIN employees e ON lr.employee_id = e.id
            JOIN employee_leave_types lt ON lr.leave_type_id = lt.id
            LEFT JOIN users u ON lr.approved_by = u.id
            WHERE 1=1";
    $params = [];
    
    if ($status) {
        $sql .= " AND lr.status = ?";
        $params[] = $status;
    }
    
    if ($employeeId) {
        $sql .= " AND lr.employee_id = ?";
        $params[] = $employeeId;
    }
    
    $sql .= " ORDER BY lr.created_at DESC";
    
    return $db->fetchAll($sql, $params);
}

// Approve/reject leave
function processLeaveRequest($db, $leaveId, $status, $rejectionReason = '') {
    $leave = $db->fetch("SELECT * FROM employee_leave_requests WHERE id = ?", [$leaveId]);
    if (!$leave) {
        return ['success' => false, 'message' => 'Leave request not found'];
    }
    
    if ($leave['status'] !== 'pending') {
        return ['success' => false, 'message' => 'Leave request already processed'];
    }
    
    $db->query("
        UPDATE employee_leave_requests SET
            status = ?,
            approved_by = ?,
            approval_date = NOW(),
            rejection_reason = ?
        WHERE id = ?
    ", [$status, $_SESSION['user_id'] ?? null, $rejectionReason, $leaveId]);
    
    // If approved, update leave balance and attendance
    if ($status === 'approved') {
        $year = date('Y', strtotime($leave['start_date']));
        
        // Update leave balance
        $db->query("
            UPDATE employee_leave_balance SET
                used_days = used_days + ?,
                remaining_days = remaining_days - ?
            WHERE employee_id = ? AND leave_type_id = ? AND year = ?
        ", [$leave['total_days'], $leave['total_days'], $leave['employee_id'], $leave['leave_type_id'], $year]);
        
        // Update attendance for leave period
        $db->query("
            INSERT INTO employee_attendance (
                employee_id, attendance_date, status
            ) SELECT employee_id, attendance_date, 'leave'
            FROM (
                SELECT employee_id, DATE_ADD(?, INTERVAL n DAY) as attendance_date
                FROM employee_leave_requests
                CROSS JOIN (
                    SELECT 0 as n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL
                    SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL
                    SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL
                    SELECT 12 UNION ALL SELECT 13 UNION ALL SELECT 14 UNION ALL SELECT 15
                ) numbers
                WHERE n <= DATEDIFF(?, ?)
            ) dates
            WHERE employee_id = ? AND attendance_date NOT IN (
                SELECT attendance_date FROM employee_attendance WHERE employee_id = ?
            )
        ", [
            $leave['start_date'],
            $leave['end_date'],
            $leave['start_date'],
            $leave['employee_id'],
            $leave['employee_id']
        ]);
    }
    
    // Add notification
    $employee = getEmployeeById($db, $leave['employee_id']);
    $statusText = $status === 'approved' ? 'approved' : 'rejected';
    addNotification($db, $employee['user_id'], "Leave $statusText", "Your leave request for " . formatDate($leave['start_date']) . " to " . formatDate($leave['end_date']) . " has been $statusText.");
    
    return ['success' => true, 'message' => 'Leave request ' . $statusText];
}

// Get pending leave requests count
function getPendingLeaveCount($db) {
    $result = $db->fetch("SELECT COUNT(*) as count FROM employee_leave_requests WHERE status = 'pending'");
    return $result['count'] ?? 0;
}

// ============================================================
// REPORTING FUNCTIONS
// ============================================================

// Get employee payroll report
function getPayrollReport($db, $year, $month) {
    return $db->fetchAll("
        SELECT es.*, e.full_name, e.employee_code, e.department, e.designation
        FROM employee_salary es
        JOIN employees e ON es.employee_id = e.id
        WHERE YEAR(es.salary_month) = ? AND MONTH(es.salary_month) = ?
        ORDER BY e.department, e.full_name
    ", [$year, $month]);
}

// Get attendance report
function getAttendanceReport($db, $year, $month, $department = null) {
    $sql = "
        SELECT e.id, e.employee_code, e.full_name, e.department,
               COALESCE(a.present_days, 0) as present_days,
               COALESCE(a.absent_days, 0) as absent_days,
               COALESCE(a.late_days, 0) as late_days,
               COALESCE(a.leave_days, 0) as leave_days,
               COALESCE(a.total_late_minutes, 0) as total_late_minutes,
               COALESCE(a.total_overtime_hours, 0) as total_overtime_hours
        FROM employees e
        LEFT JOIN (
            SELECT employee_id,
                   COUNT(CASE WHEN status = 'present' THEN 1 END) as present_days,
                   COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_days,
                   COUNT(CASE WHEN status = 'late' THEN 1 END) as late_days,
                   COUNT(CASE WHEN status = 'leave' THEN 1 END) as leave_days,
                   SUM(late_minutes) as total_late_minutes,
                   SUM(overtime_hours) as total_overtime_hours
            FROM employee_attendance
            WHERE YEAR(attendance_date) = ? AND MONTH(attendance_date) = ?
            GROUP BY employee_id
        ) a ON e.id = a.employee_id
        WHERE e.is_active = 1
    ";
    $params = [$year, $month];
    
    if ($department) {
        $sql .= " AND e.department = ?";
        $params[] = $department;
    }
    
    $sql .= " ORDER BY e.department, e.full_name";
    
    return $db->fetchAll($sql, $params);
}

// Get leave report
function getLeaveReport($db, $year, $status = null) {
    $sql = "
        SELECT lr.*, e.full_name, e.employee_code, e.department, lt.leave_type_name
        FROM employee_leave_requests lr
        JOIN employees e ON lr.employee_id = e.id
        JOIN employee_leave_types lt ON lr.leave_type_id = lt.id
        WHERE YEAR(lr.start_date) = ?
    ";
    $params = [$year];
    
    if ($status) {
        $sql .= " AND lr.status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY lr.start_date DESC";
    
    return $db->fetchAll($sql, $params);
}

// ============================================================
// UTILITY FUNCTIONS
// ============================================================

// Get departments
function getDepartments($db) {
    return $db->fetchAll("SELECT DISTINCT department FROM employees WHERE department != '' AND department IS NOT NULL ORDER BY department");
}

// Get employees by department
function getEmployeesByDepartment($db, $department) {
    return $db->fetchAll("
        SELECT * FROM employees 
        WHERE department = ? AND is_active = 1 
        ORDER BY full_name
    ", [$department]);
}

// Format currency helper
function formatEmployeeCurrency($amount) {
    return number_format($amount, 2) . ' QAR';
}

