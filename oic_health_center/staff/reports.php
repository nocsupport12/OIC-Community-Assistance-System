<?php
session_start();
include("../components/db.php");
require '../vendor/autoload.php';
use Dompdf\Dompdf;

// Protect staff page
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'staff') {
    header("Location: ../login.php");
    exit;
}

$staff_id = $_SESSION['user_id']; // logged-in staff

// Get staff name
$staffQuery = $conn->query("SELECT fname, lname FROM usr_tbl WHERE id = $staff_id");
if (!$staffQuery) {
    die("Error fetching staff name: " . $conn->error);
}
$staffData = $staffQuery->fetch_assoc();
$staffName = $staffData['fname'] . ' ' . $staffData['lname'];
$firstName = explode(' ', $staffName)[0];

// ===== DATE RANGE FILTER =====
$start_date = isset($_GET['from']) ? $_GET['from'] : '';
$end_date = isset($_GET['to']) ? $_GET['to'] : '';

// Build the WHERE clause for ALL completed queries (not just assigned to this staff)
$date_query = "WHERE cr.status = 'completed'";

// Add date filters if they exist
if (!empty($start_date) && !empty($end_date)) {
    $date_query .= " AND DATE(cr.requested_at) BETWEEN '$start_date' AND '$end_date'";
} elseif (!empty($start_date)) {
    $date_query .= " AND DATE(cr.requested_at) >= '$start_date'";
} elseif (!empty($end_date)) {
    $date_query .= " AND DATE(cr.requested_at) <= '$end_date'";
}

// ===== FETCH ALL COMPLETED QUERIES =====
$sql = "
    SELECT cr.*, u.name as user_name,
           (SELECT fname FROM usr_tbl WHERE id = cr.assigned_staff_id) as staff_fname,
           (SELECT lname FROM usr_tbl WHERE id = cr.assigned_staff_id) as staff_lname
    FROM call_requests cr
    LEFT JOIN users u ON cr.user_id = u.id
    $date_query
    ORDER BY cr.requested_at DESC
";

$completed_queries = $conn->query($sql);

// Error checking
if (!$completed_queries) {
    die("Error in query: " . $conn->error . "<br>SQL: " . $sql);
}

// ===== TOTAL ROWS =====
$total_rows = $completed_queries->num_rows;

// ===== SUMMARY STATISTICS =====
$stats_query = $conn->query("
    SELECT 
        COUNT(*) as total_completed,
        SUM(CASE WHEN contact_type = 'call' THEN 1 ELSE 0 END) as call_requests
    FROM call_requests 
    WHERE status = 'completed'
");
if (!$stats_query) {
    die("Error in stats query: " . $conn->error);
}
$stats = $stats_query->fetch_assoc();
$email_requests = ($stats['total_completed'] ?? 0) - ($stats['call_requests'] ?? 0);

// ===== PDF GENERATION =====
if (isset($_GET['download_pdf'])) {
    $pdf_sql = "
        SELECT cr.*, u.name as user_name 
        FROM call_requests cr
        LEFT JOIN users u ON cr.user_id = u.id
        $date_query
        ORDER BY cr.requested_at DESC
    ";
    $pdf_queries = $conn->query($pdf_sql);
    
    if (!$pdf_queries) {
        die("Error in PDF query: " . $conn->error);
    }

    $total_completed = 0;
    $call_count = 0;
    $pdf_data = [];
    while($row = $pdf_queries->fetch_assoc()){
        $total_completed++;
        if ($row['contact_type'] == 'call') $call_count++;
        $pdf_data[] = $row;
    }

    $html = '<html><head>
    <meta charset="UTF-8">
    <style>
    body { font-family: DejaVu Sans, sans-serif; font-size:12px; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align:left; }
    th { background-color:#f0f0f0; }
    .header { text-align:center; margin-bottom:20px; }
    .summary { margin-top:20px; padding:10px; border:1px solid #ccc; }
    </style></head><body>';

    $html .= '<div class="header">';
    $html .= '<h2>Completed Queries Report</h2>';
    $html .= '<p>Staff: ' . htmlspecialchars($staffName) . '</p>';
    $html .= '<p>Generated: '.date('F d, Y h:i A').'</p>';
    if (!empty($start_date) && !empty($end_date)) {
        $html .= '<p>Period: ' . htmlspecialchars($start_date) . ' to ' . htmlspecialchars($end_date) . '</p>';
    } elseif (!empty($start_date)) {
        $html .= '<p>From: ' . htmlspecialchars($start_date) . '</p>';
    } elseif (!empty($end_date)) {
        $html .= '<p>Until: ' . htmlspecialchars($end_date) . '</p>';
    }
    $html .= '</div>';

    $html .= '<table>
        <thead>
        <tr>
        <th>ID</th>
        <th>Customer</th>
        <th>Mobile</th>
        <th>Reason</th>
        <th>Type</th>
        <th>Date & Time</th>
        </tr>
        </thead>
        <tbody>';

    if (!empty($pdf_data)) {
        foreach($pdf_data as $q){
            $html .= '<tr>
                <td>#'.str_pad($q['id'],3,'0',STR_PAD_LEFT).'</td>
                <td>'.htmlspecialchars($q['name']?:$q['user_name']?:'Anonymous').'</td>
                <td>'.htmlspecialchars($q['mobile_number']).'</td>
                <td>'.htmlspecialchars(substr($q['reason']?:'No reason',0,50)).'...</td>
                <td>'.ucfirst($q['contact_type']).'</td>
                <td>'.date('M d, Y h:i A', strtotime($q['requested_at'])).'</td>
            </tr>';
        }
    } else {
        $html .= '<tr><td colspan="6" style="text-align:center;">No completed queries found.</td></tr>';
    }

    $html .= '</tbody></table>';
    $html .= '<div class="summary">
        <p><strong>Total Completed:</strong> '.$total_completed.'</p>
        <p><strong>Call Requests:</strong> '.$call_count.'</p>
        <p><strong>Email Requests:</strong> '.($total_completed - $call_count).'</p>
    </div>';
    $html .= '</body></html>';

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $dompdf->stream('completed_queries_'.date('Ymd_His').'.pdf', ["Attachment" => true]);
    exit;
}

include("../components/staff_nav.php");
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Completed Queries Report - Staff</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    /* Light color palette */
    :root {
        --bg-light: #f8fafc;
        --card-white: #ffffff;
        --border-light: #e2e8f0;
        --text-dark: #334155;
        --text-light: #64748b;
        --primary-light: #e0f2fe;
        --secondary-light: #ede9fe;
        --hover-light: #f1f5f9;
    }
    
    body { 
        background-color: pink;
       /* background: var(--bg-light);*/
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        margin: 0;
        color: var(--text-dark);
    }
    
    /* Main content area - accounts for navbar */
    .main-content {
        margin-left: 135px;
        padding: 30px;
        transition: margin-left 0.3s;
        min-height: 100vh;
        width: calc(100% - 250px);
        box-sizing: border-box;
    }
    
    /* Dashboard header navigation */
    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }
    
    .nav-links {
        display: flex;
        gap: 30px;
    }
    
    .nav-links a {
        text-decoration: none;
        color: var(--text-dark);
        font-weight: 500;
        font-size: 16px;
    }
    
    .nav-links a:hover {
        color: #22c55e;
    }
    
    .user-name {
        font-weight: 600;
        color: var(--text-dark);
    }
    
    .container {
        max-width: 1400px;
        width: 100%;
        margin: 0 auto;
    }
    
    /* Header with title and buttons */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .page-title {
        font-size: 24px;
        font-weight: 600;
        color: var(--text-dark);
        margin: 0;
    }
    
    .header-buttons {
        display: flex;
        gap: 10px;
    }
    
    .back-btn, .print-btn {
        padding: 8px 16px;
        border: 1px solid var(--border-light);
        border-radius: 8px;
        background: white;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 500;
        color: var(--text-dark);
        transition: all 0.2s;
        text-decoration: none;
    }
    
    .back-btn:hover, .print-btn:hover {
        background: var(--hover-light);
        border-color: #cbd5e1;
    }
    
    /* Welcome message */
    .welcome-message {
        margin-bottom: 20px;
    }
    
    .welcome-message h2 {
        font-size: 20px;
        font-weight: 600;
        margin: 0 0 5px 0;
    }
    
    .welcome-message p {
        color: var(--text-light);
        margin: 0;
        font-size: 14px;
    }
    
    /* Info banner */
    .info-banner {
        background: var(--primary-light);
        border: 1px solid #bae6fd;
        border-radius: 8px;
        padding: 12px 20px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 12px;
        color: #0369a1;
        font-size: 14px;
    }
    
    .info-banner i {
        font-size: 18px;
    }
    
    /* Stats cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 25px;
    }
    
    .stat-card {
        background: var(--card-white);
        border: 1px solid var(--border-light);
        border-radius: 12px;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.02);
    }
    
    .stat-info h3 {
        font-size: 14px;
        font-weight: 500;
        color: var(--text-light);
        margin: 0 0 8px 0;
    }
    
    .stat-number {
        font-size: 32px;
        font-weight: 600;
        color: var(--text-dark);
        line-height: 1.2;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }
    
    .stat-icon.total { background: var(--primary-light); color: #0284c7; }
    .stat-icon.calls { background: var(--secondary-light); color: #7c3aed; }
    .stat-icon.emails { background: #fef9c3; color: #ca8a04; }
    
    /* Filter section */
    .filter-section {
        background: var(--card-white);
        border: 1px solid var(--border-light);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 25px;
    }
    
    .filter-form {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        align-items: flex-end;
    }
    
    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    
    .filter-group label {
        font-size: 12px;
        font-weight: 600;
        color: var(--text-light);
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    
    .filter-group input {
        padding: 10px 14px;
        border: 1px solid var(--border-light);
        border-radius: 8px;
        min-width: 200px;
        font-size: 14px;
    }
    
    .filter-group input:focus {
        outline: none;
        border-color: #94a3b8;
    }
    
    .btn {
        padding: 10px 20px;
        border: 1px solid var(--border-light);
        border-radius: 8px;
        background: white;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 500;
        color: var(--text-dark);
        transition: all 0.2s;
        text-decoration: none;
    }
    
    .btn:hover {
        background: var(--hover-light);
    }
    
    .btn-pdf {
        background: #fee2e2;
        color: #b91c1c;
        border-color: #fecaca;
    }
    
    .btn-pdf:hover {
        background: #fecaca;
    }
    
    /* Table */
    .table-container {
        background: var(--card-white);
        border: 1px solid var(--border-light);
        border-radius: 12px;
        overflow: hidden;
    }
    
    .table-header {
        padding: 16px 20px;
        border-bottom: 1px solid var(--border-light);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--bg-light);
    }
    
    .table-header h3 {
        font-size: 16px;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .table-container table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .table-container th {
        background: var(--bg-light);
        text-align: left;
        padding: 12px 20px;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-light);
        text-transform: uppercase;
        letter-spacing: 0.3px;
        border-bottom: 1px solid var(--border-light);
    }
    
    .table-container td {
        padding: 12px 20px;
        font-size: 14px;
        border-bottom: 1px solid var(--border-light);
        color: var(--text-dark);
    }
    
    .table-container tr:last-child td {
        border-bottom: none;
    }
    
    .table-container tr:hover td {
        background: var(--hover-light);
    }
    
    /* Badges */
    .badge {
        padding: 4px 12px;
        border-radius: 30px;
        font-size: 12px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    
    .badge-call { 
        background: var(--primary-light); 
        color: #0369a1; 
    }
    
    .badge-email { 
        background: var(--secondary-light); 
        color: #6d28d9; 
    }
    
    /* Staff badge */
    .staff-badge {
        font-size: 11px;
        color: var(--text-light);
        margin-top: 2px;
    }
    
    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 60px;
        color: var(--text-light);
    }
    
    .empty-state i {
        font-size: 48px;
        margin-bottom: 15px;
        opacity: 0.3;
    }
    
    .empty-state .main-message {
        font-size: 16px;
        margin-bottom: 5px;
    }
    
    .empty-state .sub-message {
        font-size: 13px;
    }
    
    /* Table footer */
    .table-footer {
        padding: 12px 20px;
        background: var(--bg-light);
        border-top: 1px solid var(--border-light);
        font-size: 13px;
        color: var(--text-light);
        text-align: right;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .main-content {
            margin-left: 0;
            padding: 20px;
            width: 100%;
        }
        .stats-grid {
            grid-template-columns: 1fr;
        }
        .filter-form {
            flex-direction: column;
            align-items: stretch;
        }
        .filter-group input {
            width: 100%;
        }
        .page-header {
            flex-direction: column;
            gap: 15px;
            align-items: flex-start;
        }
        .dashboard-header {
            flex-direction: column;
            gap: 15px;
            align-items: flex-start;
        }
    }
    
    @media print {
        .no-print {
            display: none !important;
        }
    }
</style>
</head>
<body>
    
    <div class="main-content">
        <div class="container">
            
                <div class="user-name">
                    <?= htmlspecialchars($staffName) ?>
                </div>
            </div>

            <!-- Header with title and buttons -->
            <div class="page-header no-print">
                <h1 class="page-title">Completed Queries Report</h1>
                <div class="header-buttons">
                    
                    <button onclick="window.print()" class="print-btn">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                </div>
            </div>

            <!-- Welcome message -->
            <div class="welcome-message no-print">
                <h2>Welcome back, <?= htmlspecialchars($firstName) ?>!</h2>
                <p>View all completed inquiries</p>
            </div>

            <!-- Info Banner -->
            <div class="info-banner no-print">
                <i class="fas fa-info-circle"></i>
                <span>Showing ALL completed queries from the system. Use the filters below to refine your results by date range.</span>
            </div>

            <!-- Stats cards -->
            <div class="stats-grid no-print">
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Total Completed</h3>
                        <div class="stat-number"><?= number_format($stats['total_completed'] ?? 0) ?></div>
                    </div>
                    <div class="stat-icon total">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Call Requests</h3>
                        <div class="stat-number"><?= number_format($stats['call_requests'] ?? 0) ?></div>
                    </div>
                    <div class="stat-icon calls">
                        <i class="fas fa-phone"></i>
                    </div>
                </div>
                <div >
                    <div>
                    </div>
                </div>
            </div>

            <!-- Filter section -->
            <div class="filter-section no-print">
                <form method="GET" class="filter-form">
                    <div class="filter-group">
                        <label>FROM DATE</label>
                        <input type="date" name="from" value="<?= htmlspecialchars($start_date) ?>" placeholder="mm/dd/yyyy">
                    </div>
                    <div class="filter-group">
                        <label>TO DATE</label>
                        <input type="date" name="to" value="<?= htmlspecialchars($end_date) ?>" placeholder="mm/dd/yyyy">
                    </div>
                    <button type="submit" class="btn">
                        <i class="fas fa-filter"></i> Apply
                    </button>
                    <a href="reports.php" class="btn">
                        <i class="fas fa-undo"></i> Reset
                    </a>
                    <button type="submit" name="download_pdf" value="1" class="btn btn-pdf">
                        <i class="fas fa-file-pdf"></i> PDF Report
                    </button>
                </form>
            </div>

            <!-- Table -->
            <div class="table-container">
                <div class="table-header no-print">
                    <h3>
                        <i class="fas fa-list"></i>
                        Completed Queries List
                    </h3>
                    <span>Total: <?= $total_rows ?> entries</span>
                </div>
                
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>CUSTOMER</th>
                                <th>MOBILE</th>
                                <th>REASON</th>
                                <th>TYPE</th>
                                <th>DATE & TIME</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($completed_queries && $completed_queries->num_rows > 0): ?>
                                <?php while($q = $completed_queries->fetch_assoc()): ?>
                                <tr>
                                    <td><span style="font-weight: 500;">#<?= str_pad($q['id'], 3, '0', STR_PAD_LEFT) ?></span></td>
                                    <td>
                                        <?= htmlspecialchars($q['name'] ?: $q['user_name'] ?: 'Anonymous') ?>
                                        <?php if ($q['assigned_staff_id']): ?>
                                            <div class="staff-badge">
                                                <i class="fas fa-user-check"></i> 
                                                Assigned to: <?= htmlspecialchars($q['staff_fname'] . ' ' . $q['staff_lname']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-family: monospace;"><?= htmlspecialchars($q['mobile_number']) ?></td>
                                    <td style="max-width: 300px;">
                                        <?= htmlspecialchars(substr($q['reason'] ?: 'No reason provided', 0, 60)) ?>
                                        <?= strlen($q['reason'] ?? '') > 60 ? '...' : '' ?>
                                    </td>
                                    <td>
                                        <?php if ($q['contact_type'] == 'call'): ?>
                                            <span class="badge badge-call">
                                                <i class="fas fa-phone"></i> Call
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-email">
                                                <i class="fas fa-envelope"></i> Email
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= date('M d, Y', strtotime($q['requested_at'])) ?><br>
                                        <span style="font-size: 11px; color: var(--text-light);"><?= date('h:i A', strtotime($q['requested_at'])) ?></span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="empty-state">
                                        <i class="fas fa-inbox"></i>
                                        <p class="main-message">No completed queries found</p>
                                        <p class="sub-message">No queries have been completed for the selected period</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Footer -->
                <div class="table-footer no-print">
                    <span>Total: <?= $total_rows ?> entries</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>