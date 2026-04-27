<?php
session_start();
include("../components/db.php");
require '../vendor/autoload.php';
use Dompdf\Dompdf;

// Protect admin page
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Get admin name for welcome message
$admin_id = $_SESSION['user_id'];
$adminQuery = $conn->query("SELECT fname, lname FROM usr_tbl WHERE id = $admin_id");
$adminData = $adminQuery->fetch_assoc();
$adminName = $adminData['fname'] . ' ' . $adminData['lname'];

// ===== DATE RANGE FILTER =====
$start_date = isset($_GET['from']) ? $_GET['from'] : '';
$end_date = isset($_GET['to']) ? $_GET['to'] : '';

$date_query = "";
if (!empty($start_date) && !empty($end_date)) {
    $date_query = "WHERE DATE(requested_at) BETWEEN '$start_date' AND '$end_date'";
} elseif (!empty($start_date)) {
    $date_query = "WHERE DATE(requested_at) >= '$start_date'";
} elseif (!empty($end_date)) {
    $date_query = "WHERE DATE(requested_at) <= '$end_date'";
}

// ===== CSV EXPORT =====
if (isset($_GET['download_csv'])) {
    // Fetch all data for CSV
    $csv_queries = $conn->query("
        SELECT cr.*, u.name as user_name 
        FROM call_requests cr
        LEFT JOIN users u ON cr.user_id = u.id
        WHERE cr.status = 'completed' $date_query
        ORDER BY cr.requested_at DESC
    ");

    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=completed_queries_' . date('Ymd_His') . '.csv');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // CSV Headers - removed created_at since it doesn't exist
    fputcsv($output, [
        'ID',
        'Customer Name',
        'Mobile Number',
        'Email',
        'Contact Type',
        'Reason',
        'Requested Date',
        'Requested Time',
        'Status',
        'User ID'
    ]);
    
    // Add data rows
    if ($csv_queries && $csv_queries->num_rows > 0) {
        while ($row = $csv_queries->fetch_assoc()) {
            fputcsv($output, [
                '#' . str_pad($row['id'], 3, '0', STR_PAD_LEFT),
                $row['name'] ?: $row['user_name'] ?: 'Anonymous',
                $row['mobile_number'],
                $row['email'] ?: 'N/A',
                ucfirst($row['contact_type']),
                $row['reason'] ?: 'No reason provided',
                date('Y-m-d', strtotime($row['requested_at'])),
                date('H:i:s', strtotime($row['requested_at'])),
                ucfirst($row['status']),
                $row['user_id'] ?: 'N/A'
            ]);
        }
    } else {
        // If no data, add a row indicating no records
        fputcsv($output, ['No completed queries found for the selected period']);
    }
    
    fclose($output);
    exit;
}

// ===== PAGINATION =====
$limit = 100;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// ===== FETCH COMPLETED QUERIES =====
$completed_queries = $conn->query("
    SELECT cr.*, u.name as user_name 
    FROM call_requests cr
    LEFT JOIN users u ON cr.user_id = u.id
    WHERE cr.status = 'completed' $date_query
    ORDER BY cr.requested_at DESC
    LIMIT $limit OFFSET $offset
");

// ===== TOTAL ROWS =====
$count_sql = "SELECT COUNT(*) as total FROM call_requests WHERE status = 'completed' $date_query";
$total_result = $conn->query($count_sql);
$total_rows = ($total_result && $total_result->num_rows > 0) ? $total_result->fetch_assoc()['total'] : 0;
$total_pages = ceil($total_rows / $limit);

// ===== SUMMARY STATISTICS =====
$stats_query = $conn->query("
    SELECT 
        COUNT(*) as total_completed,
        SUM(CASE WHEN contact_type = 'call' THEN 1 ELSE 0 END) as call_requests
    FROM call_requests 
    WHERE status = 'completed' $date_query
");
$stats = $stats_query->fetch_assoc();
$email_requests = ($stats['total_completed'] ?? 0) - ($stats['call_requests'] ?? 0);

// ===== DELETE HANDLER =====
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $result = $conn->query("DELETE FROM call_requests WHERE id = $id");
    
    header('Content-Type: application/json');
    echo json_encode(['success' => $result ? true : false]);
    exit;
}

// ===== PDF GENERATION =====
if (isset($_GET['download_pdf'])) {
    $pdf_queries = $conn->query("
        SELECT cr.*, u.name as user_name 
        FROM call_requests cr
        LEFT JOIN users u ON cr.user_id = u.id
        WHERE cr.status = 'completed' $date_query
        ORDER BY cr.requested_at DESC
    ");

    $total = 0;
    $calls = 0;
    $data = [];
    while($row = $pdf_queries->fetch_assoc()){
        $total++;
        if ($row['contact_type'] == 'call') $calls++;
        $data[] = $row;
    }

    $html = '<html><head><meta charset="UTF-8"><style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f0f0f0; }
        .header { text-align: center; margin-bottom: 20px; }
        .summary { margin-top: 20px; padding: 10px; border: 1px solid #ccc; }
    </style></head><body>';
    
    $html .= '<div class="header"><h2>Completed Queries Report</h2>';
    $html .= '<p>Generated: '.date('F d, Y h:i A').'</p>';
    if (!empty($start_date) && !empty($end_date)) {
        $html .= '<p>Period: ' . htmlspecialchars($start_date) . ' to ' . htmlspecialchars($end_date) . '</p>';
    } elseif (!empty($start_date)) {
        $html .= '<p>From: ' . htmlspecialchars($start_date) . '</p>';
    } elseif (!empty($end_date)) {
        $html .= '<p>Until: ' . htmlspecialchars($end_date) . '</p>';
    }
    $html .= '</div>';
    
    $html .= '<table><thead><tr><th>ID</th><th>Customer</th><th>Mobile</th><th>Reason</th><th>Type</th><th>Date & Time</th></tr></thead><tbody>';
    
    if (!empty($data)) {
        foreach($data as $q) {
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
        <p><strong>Total Completed:</strong> '.$total.'</p>
        <p><strong>Call Requests:</strong> '.$calls.'</p>
        <p><strong>Email Requests:</strong> '.($total-$calls).'</p>
    </div>';
    $html .= '</body></html>';

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $dompdf->stream('completed_queries_'.date('Ymd_His').'.pdf', ["Attachment" => true]);
    exit;
}

include("../components/admin_nav.php");
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Completed Queries - Power2Connect</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    /* Light color palette - matching staff page */
    :root {
        --bg-light: #f8fafc;
        --card-white: #ffffff;
        --border-light: #e2e8f0;
        --text-dark: #334155;
        --text-light: #64748b;
        --primary-light: #e0f2fe;
        --secondary-light: #ede9fe;
        --hover-light: #f1f5f9;
        --danger-light: #fee2e2;
        --danger-dark: #b91c1c;
        --success-light: #dcfce7;
        --success-dark: #166534;
    }
    
    body { 
        background: #ADB5BD;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        margin: 0;
        color: var(--text-dark);
    }
    
    /* Main content area - accounts for navbar and centers content */
    .main-content {
        margin-left: 134px; /* Keep this for navbar */
        padding: 30px;
        transition: margin-left 0.3s;
        display: flex;
        justify-content: center; /* Center the container horizontally */
        min-height: 100vh;
        width: calc(100% - 250px); /* Take full width minus navbar */
        box-sizing: border-box;
    }
    
    .container {
        max-width: 1400px;
        width: 100%; /* Take full width up to max-width */
        margin: 0 auto; /* Auto margins for centering */
    }
    
    /* Header */
    .page-header {
        margin-bottom: 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .page-title {
        font-size: 28px;
        font-weight: 600;
        color: var(--text-dark);
        margin: 0 0 5px 0;
    }
    
    .page-subtitle {
        color: var(--text-light);
        margin: 0;
        font-size: 15px;
    }
    
    /* Print button */
    .print-btn {
        padding: 8px 16px;
        border: 1px solid var(--border-light);
        border-radius: 10px;
        background: white;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 500;
        color: var(--text-dark);
        transition: all 0.2s;
    }
    
    .print-btn:hover {
        background: var(--hover-light);
        border-color: #cbd5e1;
    }
    
    /* Stats cards - light and airy */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 25px;
        width: 100%;
    }
    
    .stat-card {
        background: var(--card-white);
        border: 1px solid var(--border-light);
        border-radius: 16px;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        transition: all 0.2s;
    }
    
    .stat-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        border-color: #cbd5e1;
    }
    
    .stat-info h3 {
        font-size: 14px;
        font-weight: 500;
        color: var(--text-light);
        margin: 0 0 8px 0;
        letter-spacing: 0.3px;
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
    
    /* Filter section - light */
    .filter-section {
        background: var(--card-white);
        border: 1px solid var(--border-light);
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 25px;
        width: 100%;
        box-sizing: border-box;
    }
    
    .filter-form {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        align-items: flex-end;
        justify-content: flex-start;
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
        border-radius: 10px;
        min-width: 200px;
        font-size: 14px;
        transition: all 0.2s;
    }
    
    .filter-group input:focus {
        outline: none;
        border-color: #94a3b8;
        box-shadow: 0 0 0 3px rgba(148, 163, 184, 0.1);
    }
    
    .btn {
        padding: 10px 20px;
        border: 1px solid var(--border-light);
        border-radius: 10px;
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
        border-color: #cbd5e1;
    }
    
    .btn i {
        font-size: 14px;
        opacity: 0.7;
    }
    
    .btn-pdf {
        background: #fee2e2;
        color: #b91c1c;
        border-color: #fecaca;
    }
    
    .btn-pdf:hover {
        background: #fecaca;
    }
    
    .btn-csv {
        background: #dcfce7;
        color: #166534;
        border-color: #bbf7d0;
    }
    
    .btn-csv:hover {
        background: #bbf7d0;
    }
    
    .btn-danger {
        color: var(--danger-dark);
    }
    
    .btn-danger:hover {
        background: var(--danger-light);
    }
    
    /* Table - clean and light */
    .table-container {
        background: var(--card-white);
        border: 1px solid var(--border-light);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        width: 100%;
        box-sizing: border-box;
    }
    
    .table-header {
        padding: 16px 20px;
        border-bottom: 1px solid var(--border-light);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--bg-light);
        width: 100%;
        box-sizing: border-box;
    }
    
    .table-header h3 {
        font-size: 16px;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .table-header h3 i {
        color: var(--text-light);
    }
    
    .table-container table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .table-container th {
        background: var(--bg-light);
        text-align: left;
        padding: 16px 20px;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-light);
        text-transform: uppercase;
        letter-spacing: 0.3px;
        border-bottom: 1px solid var(--border-light);
    }
    
    .table-container td {
        padding: 16px 20px;
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
    
    /* Light badges */
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
    
    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        gap: 8px;
        padding: 20px;
        border-top: 1px solid var(--border-light);
    }
    
    .pagination a, .pagination span {
        min-width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--border-light);
        border-radius: 8px;
        text-decoration: none;
        color: var(--text-dark);
        font-size: 14px;
        transition: all 0.2s;
    }
    
    .pagination a:hover {
        background: var(--hover-light);
        border-color: #cbd5e1;
    }
    
    .pagination .active {
        background: var(--text-dark);
        color: white;
        border-color: var(--text-dark);
    }
    
    /* Footer */
    .table-footer {
        padding: 16px 20px;
        background: var(--bg-light);
        border-top: 1px solid var(--border-light);
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 13px;
        color: var(--text-light);
    }
    
    /* Empty state styling */
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
    
    .empty-state p {
        margin: 5px 0;
    }
    
    .empty-state .main-message {
        font-size: 16px;
    }
    
    .empty-state .sub-message {
        font-size: 13px;
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
    }

    /* Ensure all content stays within bounds */
    * {
        box-sizing: border-box;
    }
    
    /* Print styles */
    @media print {
        .no-print {
            display: none !important;
        }
        .main-content {
            margin-left: 0;
            width: 100%;
        }
        body {
            background: white;
        }
    }
</style>
</head>
<body>
    <!-- admin_nav.php is included above - navbar preserved -->
    
    <div class="main-content">
        <div class="container">
            <!-- Header with welcome message and print button -->
            <div class="page-header">
                <div>
                    <h1 class="page-title">Completed Queries</h1>
                    <p class="page-subtitle">Welcome back, <?= htmlspecialchars(explode(' ', $adminName)[0]) ?>! View all completed inquiries</p>
                </div>
                <button onclick="window.print()" class="print-btn no-print">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>

            <!-- Stats cards - light colors -->
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
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Email Requests</h3>
                        <div class="stat-number"><?= number_format($email_requests) ?></div>
                    </div>
                    <div class="stat-icon emails">
                        <i class="fas fa-envelope"></i>
                    </div>
                </div>
            </div>

            <!-- Filter section - light -->
            <div class="filter-section no-print">
                <form method="GET" class="filter-form">
                    <div class="filter-group">
                        <label>From Date</label>
                        <input type="date" name="from" value="<?= htmlspecialchars($start_date) ?>">
                    </div>
                    <div class="filter-group">
                        <label>To Date</label>
                        <input type="date" name="to" value="<?= htmlspecialchars($end_date) ?>">
                    </div>
                    <button type="submit" class="btn">
                        <i class="fas fa-filter"></i> Apply
                    </button>
                    <a href="?" class="btn">
                        <i class="fas fa-undo"></i> Reset
                    </a>
                    <button type="submit" name="download_pdf" value="1" class="btn btn-pdf">
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>
                    <button type="submit" name="download_csv" value="1" class="btn btn-csv">
                        <i class="fas fa-file-csv"></i> CSV
                    </button>
                </form>
            </div>

            <!-- Table - clean and readable -->
            <div class="table-container">
                <div class="table-header no-print">
                    <h3>
                        <i class="fas fa-list"></i>
                        Completed Queries List
                    </h3>
                    <span style="color: var(--text-light); font-size: 13px;">Total: <?= $total_rows ?> entries</span>
                </div>
                
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Mobile</th>
                                <th>Email</th>
                                <th>Reason</th>
                                <th>Type</th>
                                <th>Date & Time</th>
                                <th class="no-print">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($completed_queries && $completed_queries->num_rows > 0): ?>
                                <?php while($q = $completed_queries->fetch_assoc()): ?>
                                <tr>
                                    <td><span style="font-weight: 500;">#<?= str_pad($q['id'], 3, '0', STR_PAD_LEFT) ?></span></td>
                                    <td><?= htmlspecialchars($q['name'] ?: $q['user_name'] ?: 'Anonymous') ?></td>
                                    <td style="font-family: monospace;"><?= htmlspecialchars($q['mobile_number']) ?></td>
                                    <td><?= htmlspecialchars($q['email'] ?: 'N/A') ?></td>
                                    <td style="max-width: 250px;">
                                        <?= htmlspecialchars(substr($q['reason'] ?: 'No reason provided', 0, 50)) ?>
                                        <?= strlen($q['reason'] ?? '') > 50 ? '...' : '' ?>
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
                                    <td class="no-print">
                                        <button onclick="confirmDelete(<?= $q['id'] ?>)" class="btn btn-danger" style="padding: 6px 12px;" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="empty-state">
                                        <i class="fas fa-inbox"></i>
                                        <p class="main-message">No completed queries found</p>
                                        <p class="sub-message">No queries have been completed for the selected period</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if($total_pages > 1): ?>
                <div class="pagination no-print">
                    <?php 
                    $adjacents = 2;
                    $start = max(1, $page - $adjacents);
                    $end = min($total_pages, $page + $adjacents);
                    
                    if($page > 1) echo '<a href="?page='.($page-1).'&from='.$start_date.'&to='.$end_date.'"><i class="fas fa-chevron-left"></i></a>';
                    if($start > 1) { 
                        echo '<a href="?page=1&from='.$start_date.'&to='.$end_date.'">1</a>'; 
                        if($start > 2) echo '<span>...</span>'; 
                    }
                    for($i=$start; $i<=$end; $i++) {
                        $activeClass = ($i == $page) ? 'active' : '';
                        echo '<a href="?page='.$i.'&from='.$start_date.'&to='.$end_date.'" class="'.$activeClass.'">'.$i.'</a>';
                    }
                    if($end < $total_pages) { 
                        if($end < $total_pages - 1) echo '<span>...</span>'; 
                        echo '<a href="?page='.$total_pages.'&from='.$start_date.'&to='.$end_date.'">'.$total_pages.'</a>'; 
                    }
                    if($page < $total_pages) echo '<a href="?page='.($page+1).'&from='.$start_date.'&to='.$end_date.'"><i class="fas fa-chevron-right"></i></a>';
                    ?>
                </div>
                <?php endif; ?>

                <!-- Footer -->
                <div class="table-footer no-print">
                    <span>Showing <?= $completed_queries ? $completed_queries->num_rows : 0 ?> of <?= $total_rows ?></span>
                    <span>Page <?= $page ?> of <?= $total_pages ?></span>
                </div>
            </div>
        </div>
    </div>

    <script>
    function confirmDelete(id) {
        Swal.fire({
            title: 'Delete Query?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#b91c1c',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Deleting...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                fetch(window.location.pathname + '?delete=' + id + '&from=<?= urlencode($start_date) ?>&to=<?= urlencode($end_date) ?>')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: 'The query has been deleted.',
                                confirmButtonColor: '#334155'
                            }).then(() => location.reload());
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Failed to delete the query.',
                                confirmButtonColor: '#334155'
                            });
                        }
                    })
                    .catch(() => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'An error occurred while deleting.',
                            confirmButtonColor: '#334155'
                        });
                    });
            }
        });
    }
    </script>
</body>
</html>
<?php $conn->close(); ?>