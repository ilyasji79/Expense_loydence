<?php
/**
 * Generate PDF Report (HTML-based)
 * Expense Management ERP - Loydence Academy
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(BASE_URL . '/login.php');
}

// Get filters
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');
$categoryFilter = $_GET['category'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// Build query
$sql = "SELECT * FROM view_expenses_detail WHERE date BETWEEN ? AND ?";
$params = [$dateFrom, $dateTo];

if ($categoryFilter) {
    $sql .= " AND category_id = ?";
    $params[] = $categoryFilter;
}
if ($statusFilter) {
    $sql .= " AND status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY date DESC";
$expenses = $db->fetchAll($sql, $params);

// Calculate totals
$totalAmount = array_sum(array_column($expenses, 'amount'));
$approvedAmount = array_sum(array_filter($expenses, function($e) { return in_array($e['status'], ['approved', 'released']); }));
$rejectedAmount = array_sum(array_filter($expenses, function($e) { return $e['status'] === 'rejected'; }));
$releasedAmount = array_sum(array_filter($expenses, function($e) { return $e['status'] === 'released'; }));

$financialSummary = getFinancialSummary($db);

// Get settings
$siteName = getSetting($db, 'school_name', SITE_NAME);
$adminName = getSetting($db, 'admin_name', ADMIN_NAME);
$hrName = getSetting($db, 'hr_name', HR_NAME);

// Output as HTML (can be printed to PDF via browser)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Report - <?php echo $siteName; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; padding: 20px; color: #333; }
        
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #1e3c72; padding-bottom: 20px; }
        .header h1 { font-size: 24px; color: #1e3c72; margin-bottom: 5px; }
        .header p { font-size: 14px; color: #666; }
        
        .report-info { margin-bottom: 20px; }
        .report-info p { font-size: 12px; margin-bottom: 5px; }
        
        .financial-summary { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 25px; }
        .financial-summary h3 { font-size: 14px; color: #1e3c72; margin-bottom: 10px; }
        .summary-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
        .summary-item { text-align: center; }
        .summary-item label { display: block; font-size: 11px; color: #666; }
        .summary-item .value { font-size: 16px; font-weight: bold; color: #333; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; font-size: 11px; }
        th { background: #1e3c72; color: white; padding: 10px; text-align: left; font-weight: 600; }
        td { padding: 8px 10px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) { background: #f8f9fa; }
        
        .status-pending { background: #fff3cd; padding: 3px 8px; border-radius: 10px; font-size: 10px; }
        .status-approved { background: #d4edda; padding: 3px 8px; border-radius: 10px; font-size: 10px; }
        .status-rejected { background: #f8d7da; padding: 3px 8px; border-radius: 10px; font-size: 10px; }
        .status-released { background: #cce5ff; padding: 3px 8px; border-radius: 10px; font-size: 10px; }
        
        .signatures { margin-top: 50px; display: flex; justify-content: space-between; }
        .signature-box { width: 45%; }
        .signature-box .label { font-size: 12px; margin-bottom: 30px; }
        .signature-box .name { font-size: 12px; color: #666; }
        
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #999; }
        
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #1e3c72; color: white; border: none; border-radius: 5px; cursor: pointer;">
            <i class="fas fa-print"></i> Print / Save as PDF
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
            Close
        </button>
    </div>

    <div class="header">
        <h1><?php echo $siteName; ?></h1>
        <p>Al Aziziyah, Qatar</p>
        <h2 style="font-size: 18px; color: #333; margin-top: 10px;">Expense Management Report</h2>
    </div>

    <div class="report-info">
        <p><strong>Report Period:</strong> <?php echo formatDate($dateFrom); ?> to <?php echo formatDate($dateTo); ?></p>
        <p><strong>Admin:</strong> <?php echo $adminName; ?> | <strong>HR Manager:</strong> <?php echo $hrName; ?></p>
        <p><strong>Generated Date:</strong> <?php echo date('d M Y h:i A'); ?></p>
    </div>

    <div class="financial-summary">
        <h3>Financial Summary</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <label>Opening Balance</label>
                <div class="value"><?php echo number_format($financialSummary['total_opening_balance'], 2); ?> QAR</div>
            </div>
            <div class="summary-item">
                <label>Total Expenses</label>
                <div class="value"><?php echo number_format($totalAmount, 2); ?> QAR</div>
            </div>
            <div class="summary-item">
                <label>Approved Amount</label>
                <div class="value"><?php echo number_format($approvedAmount, 2); ?> QAR</div>
            </div>
            <div class="summary-item">
                <label>Rejected Amount</label>
                <div class="value"><?php echo number_format($rejectedAmount, 2); ?> QAR</div>
            </div>
            <div class="summary-item">
                <label>Released Amount</label>
                <div class="value"><?php echo number_format($releasedAmount, 2); ?> QAR</div>
            </div>
            <div class="summary-item">
                <label>Remaining Balance</label>
                <div class="value"><?php echo number_format($financialSummary['remaining_balance'], 2); ?> QAR</div>
            </div>
        </div>
    </div>

    <h3 style="margin-bottom: 15px; color: #1e3c72;">Expense Details</h3>
    <table>
        <thead>
            <tr>
                <th>Voucher No</th>
                <th>Date</th>
                <th>Category</th>
                <th>Description</th>
                <th>Invoice No</th>
                <th>Amount</th>
                <th>Status</th>
                <th>HR Approved By</th>
                <th>Approval Date</th>
                <th>Released</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($expenses) > 0): ?>
                <?php foreach ($expenses as $expense): ?>
                <tr>
                    <td><?php echo htmlspecialchars($expense['voucher_no']); ?></td>
                    <td><?php echo formatDate($expense['date']); ?></td>
                    <td><?php echo htmlspecialchars($expense['category_name']); ?></td>
                    <td><?php echo htmlspecialchars(substr($expense['description'], 0, 40)); ?></td>
                    <td><?php echo htmlspecialchars($expense['invoice_no'] ?? '-'); ?></td>
                    <td><?php echo number_format($expense['amount'], 2); ?></td>
                    <td><span class="status-<?php echo $expense['status']; ?>"><?php echo ucfirst($expense['status']); ?></span></td>
                    <td><?php echo $expense['hr_approver_name'] ? htmlspecialchars($expense['hr_approver_name']) : '-'; ?></td>
                    <td><?php echo $expense['hr_approval_date'] ? formatDate($expense['hr_approval_date']) : '-'; ?></td>
                    <td><?php echo $expense['status'] === 'released' ? 'Yes' : '-'; ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="10" style="text-align: center;">No expenses found</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="signatures">
        <div class="signature-box">
            <div class="label">HR Manager Signature:</div>
            <div class="name"><?php echo $hrName; ?></div>
        </div>
        <div class="signature-box">
            <div class="label">Admin Signature:</div>
            <div class="name"><?php echo $adminName; ?></div>
        </div>
    </div>

    <div class="footer">
        <p>This is a computer-generated document. No signature required.</p>
        <p><?php echo $siteName; ?> - Expense Management System</p>
    </div>
</body>
</html>

