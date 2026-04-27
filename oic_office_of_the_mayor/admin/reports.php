<?php
session_start();

// Main database connection (Office of the Mayor)
include("../components/db.php");

// Define all sector database connections
$sector_databases = [
    'police' => [
        'name' => 'Police Station',
        'icon' => 'fa-shield-alt',
        'gradient' => 'linear-gradient(135deg, #2563eb, #1e40af)',
        'light' => 'rgba(37, 99, 235, 0.1)',
        'host' => 'localhost',
        'user' => 'root',
        'pass' => '',
        'db' => 'oic_police'
    ],
    'fire' => [
        'name' => 'Bureau of Fire',
        'icon' => 'fa-fire-extinguisher',
        'gradient' => 'linear-gradient(135deg, #dc2626, #991b1b)',
        'light' => 'rgba(220, 38, 38, 0.1)',
        'host' => 'localhost',
        'user' => 'root',
        'pass' => '',
        'db' => 'oic_beuro_of_fire'
    ],
    'health' => [
        'name' => 'Health Center',
        'icon' => 'fa-heartbeat',
        'gradient' => 'linear-gradient(135deg, #16a34a, #166534)',
        'light' => 'rgba(22, 163, 74, 0.1)',
        'host' => 'localhost',
        'user' => 'root',
        'pass' => '',
        'db' => 'oic_health_center'
    ],
    'engineering' => [
        'name' => 'Engineering',
        'icon' => 'fa-hard-hat',
        'gradient' => 'linear-gradient(135deg, #9333ea, #6b21a8)',
        'light' => 'rgba(147, 51, 234, 0.1)',
        'host' => 'localhost',
        'user' => 'root',
        'pass' => '',
        'db' => 'oic_engineering'
    ],
    'accounting' => [
        'name' => 'Accounting',
        'icon' => 'fa-calculator',
        'gradient' => 'linear-gradient(135deg, #f59e0b, #b45309)',
        'light' => 'rgba(245, 158, 11, 0.1)',
        'host' => 'localhost',
        'user' => 'root',
        'pass' => '',
        'db' => 'oic_accounting'
    ],
    'tax' => [
        'name' => 'Tax Collection',
        'icon' => 'fa-coins',
        'gradient' => 'linear-gradient(135deg, #059669, #065f46)',
        'light' => 'rgba(5, 150, 105, 0.1)',
        'host' => 'localhost',
        'user' => 'root',
        'pass' => '',
        'db' => 'oic_tax_collection'
    ],
    'garbage' => [
        'name' => 'Garbage Collector',
        'icon' => 'fa-trash-alt',
        'gradient' => 'linear-gradient(135deg, #6b7280, #374151)',
        'light' => 'rgba(107, 114, 128, 0.1)',
        'host' => 'localhost',
        'user' => 'root',
        'pass' => '',
        'db' => 'oic_garbage_collector'
    ],
    'community' => [
        'name' => 'Community Assistance',
        'icon' => 'fa-hands-helping',
        'gradient' => 'linear-gradient(135deg, #db2777, #9d174d)',
        'light' => 'rgba(219, 39, 119, 0.1)',
        'host' => 'localhost',
        'user' => 'root',
        'pass' => '',
        'db' => 'oic_community_assistance'
    ]
];

require '../vendor/autoload.php';
use Dompdf\Dompdf;

// Protect admin page
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Get admin name for welcome message
$admin_id = $_SESSION['user_id'];
$adminQuery = $conn->query("SELECT fname, lname, profile FROM usr_tbl WHERE id = $admin_id");
$adminData = $adminQuery->fetch_assoc();
$adminName = $adminData['fname'] . ' ' . $adminData['lname'];

// ===== DATE RANGE FILTER =====
$start_date = isset($_GET['from']) ? $_GET['from'] : '';
$end_date = isset($_GET['to']) ? $_GET['to'] : '';
$selected_sector = isset($_GET['sector']) ? $_GET['sector'] : 'all';

// Function to connect to sector database
function connectToSectorDB($sector_config) {
    $sector_conn = new mysqli(
        $sector_config['host'],
        $sector_config['user'],
        $sector_config['pass'],
        $sector_config['db']
    );
    
    if ($sector_conn->connect_error) {
        return null;
    }
    
    return $sector_conn;
}

// Function to fetch data from a sector database
function fetchSectorData($sector_key, $sector_config, $start_date, $end_date) {
    $sector_conn = connectToSectorDB($sector_config);
    if (!$sector_conn) {
        return ['data' => [], 'stats' => ['total' => 0, 'calls' => 0, 'emails' => 0]];
    }
    
    $date_condition = "";
    if (!empty($start_date) && !empty($end_date)) {
        $start = $sector_conn->real_escape_string($start_date);
        $end = $sector_conn->real_escape_string($end_date);
        $date_condition = "AND DATE(requested_at) BETWEEN '$start' AND '$end'";
    } elseif (!empty($start_date)) {
        $start = $sector_conn->real_escape_string($start_date);
        $date_condition = "AND DATE(requested_at) >= '$start'";
    } elseif (!empty($end_date)) {
        $end = $sector_conn->real_escape_string($end_date);
        $date_condition = "AND DATE(requested_at) <= '$end'";
    }
    
    // Fetch completed queries
    $query = "SELECT 
                cr.*, 
                u.name as user_name,
                '{$sector_config['name']}' as sector_name,
                '$sector_key' as sector_key
              FROM call_requests cr
              LEFT JOIN users u ON cr.user_id = u.id
              WHERE cr.status = 'completed' $date_condition
              ORDER BY cr.requested_at DESC";
    
    $result = $sector_conn->query($query);
    $data = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['sector_display_name'] = $sector_config['name'];
            $row['sector_icon'] = $sector_config['icon'];
            $row['sector_gradient'] = $sector_config['gradient'];
            $row['sector_light'] = $sector_config['light'];
            $data[] = $row;
        }
    }
    
    // Get stats
    $stats_query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN contact_type = 'call' THEN 1 ELSE 0 END) as calls
                    FROM call_requests 
                    WHERE status = 'completed' $date_condition";
    
    $stats_result = $sector_conn->query($stats_query);
    $stats = $stats_result->fetch_assoc();
    
    $sector_conn->close();
    
    return [
        'data' => $data,
        'stats' => [
            'total' => $stats['total'] ?? 0,
            'calls' => $stats['calls'] ?? 0,
            'emails' => ($stats['total'] ?? 0) - ($stats['calls'] ?? 0)
        ]
    ];
}

// Fetch data from all sectors
$all_sector_data = [];
$sector_stats = [];

foreach ($sector_databases as $key => $config) {
    $sector_result = fetchSectorData($key, $config, $start_date, $end_date);
    $all_sector_data = array_merge($all_sector_data, $sector_result['data']);
    $sector_stats[$config['name']] = $sector_result['stats'];
}

// Sort by date (most recent first)
usort($all_sector_data, function($a, $b) {
    return strtotime($b['requested_at']) - strtotime($a['requested_at']);
});

// ===== PAGINATION =====
$limit = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filter by sector if selected
$filtered_data = $all_sector_data;
if ($selected_sector != 'all' && isset($sector_databases[$selected_sector])) {
    $filtered_data = array_filter($all_sector_data, function($item) use ($selected_sector) {
        return $item['sector_key'] == $selected_sector;
    });
}

$total_rows = count($filtered_data);
$paginated_data = array_slice($filtered_data, $offset, $limit);
$total_pages = ceil($total_rows / $limit);

// ===== SUMMARY STATISTICS =====
$total_completed = array_sum(array_column($sector_stats, 'total'));
$total_calls = array_sum(array_column($sector_stats, 'calls'));
$total_emails = $total_completed - $total_calls;

// ===== DELETE HANDLER =====
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $sector_key = isset($_GET['sector_key']) ? $_GET['sector_key'] : '';
    
    if ($sector_key && isset($sector_databases[$sector_key])) {
        $sector_conn = connectToSectorDB($sector_databases[$sector_key]);
        if ($sector_conn) {
            $result = $sector_conn->query("DELETE FROM call_requests WHERE id = $id");
            $sector_conn->close();
            
            header('Content-Type: application/json');
            echo json_encode(['success' => $result ? true : false]);
            exit;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false]);
    exit;
}

// ===== CSV EXPORT =====
if (isset($_GET['download_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=mayor_report_' . date('Ymd_His') . '.csv');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // CSV Headers
    fputcsv($output, [
        'ID',
        'Sector',
        'Customer Name',
        'Mobile Number',
        'Email',
        'Contact Type',
        'Reason',
        'Requested Date',
        'Requested Time',
        'Status'
    ]);
    
    if (!empty($filtered_data)) {
        foreach ($filtered_data as $row) {
            fputcsv($output, [
                '#' . str_pad($row['id'], 3, '0', STR_PAD_LEFT),
                $row['sector_display_name'],
                $row['name'] ?: $row['user_name'] ?: 'Anonymous',
                $row['mobile_number'],
                $row['email'] ?: 'N/A',
                ucfirst($row['contact_type']),
                $row['reason'] ?: 'No reason provided',
                date('Y-m-d', strtotime($row['requested_at'])),
                date('H:i:s', strtotime($row['requested_at'])),
                ucfirst($row['status'])
            ]);
        }
    } else {
        fputcsv($output, ['No completed queries found for the selected period']);
    }
    
    fclose($output);
    exit;
}

// ===== PDF GENERATION =====
if (isset($_GET['download_pdf'])) {
    $html = '<html><head><meta charset="UTF-8"><style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; background: #f8fafc; }
        .report-container { padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .sector-card { 
            background: black;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
        }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #f1f5f9; padding: 12px; text-align: left; font-size: 11px; }
        td { padding: 10px; border-bottom: 1px solid #e2e8f0; }
    </style></head><body>
    <div class="report-container">
        <div class="header">
            <h2>Office of the Mayor - Consolidated Report</h2>
            <p>' . date('F d, Y h:i A') . '</p>
        </div>';
    
    foreach ($sector_stats as $sector_name => $stats) {
        $html .= '<div class="sector-card">
            <h3>' . $sector_name . '</h3>
            <p>Total: ' . $stats['total'] . ' | Calls: ' . $stats['calls'] . ' | Emails: ' . $stats['emails'] . '</p>
        </div>';
    }
    
    $html .= '<table>
        <thead><tr><th>ID</th><th>Sector</th><th>Customer</th><th>Type</th><th>Date</th></tr></thead>
        <tbody>';
    
    foreach (array_slice($filtered_data, 0, 200) as $q) {
        $html .= '<tr>
            <td>#' . str_pad($q['id'], 3, '0', STR_PAD_LEFT) . '</td>
            <td>' . $q['sector_display_name'] . '</td>
            <td>' . ($q['name'] ?: $q['user_name'] ?: 'Anonymous') . '</td>
            <td>' . ucfirst($q['contact_type']) . '</td>
            <td>' . date('M d, Y', strtotime($q['requested_at'])) . '</td>
        </tr>';
    }
    
    $html .= '</tbody></table></div></body></html>';

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $dompdf->stream('mayor_report_' . date('Ymd_His') . '.pdf', ["Attachment" => true]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mayor's Office - Glass Dashboard</title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /*===========================================
        =            GLASS DASHBOARD STYLES          =
        ===========================================*/

        /* Reset & Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
           background: linear-gradient(135deg, #dddfdd 0%, #6e9fe9 100%);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        
        }

        /* Animated Background Bubbles */
        .bg-bubbles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
        }

        .bg-bubbles span {
            position: absolute;
            display: block;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            bottom: -40px;
            border-radius: 50%;
            animation: bubble 25s infinite;
        }

        @keyframes bubble {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 1;
                border-radius: 50%;
            }
            100% {
                transform: translateY(-1200px) rotate(720deg);
                opacity: 0;
                border-radius: 50%;
            }
        }

        /* Main Glass Container */
        .glass-container {
            position: relative;
            z-index: 1;
            display: flex;
            min-height: 100vh;
            backdrop-filter: blur(20px);
            background: rgba(255, 255, 255, 0.1);
        }

        /* Glass Sidebar */
        .glass-sidebar {
            width: 300px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            flex-direction: column;
            height: 100vh;
            position: sticky;
            top: 0;
            box-shadow: 20px 0 30px -15px rgba(0, 0, 0, 0.2);
        }

        .sidebar-header {
            padding: 30px 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-icon {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: black;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .logo-text h2 {
            font-size: 20px;
            font-weight: 700;
            color: black;
            margin-bottom: 4px;
            text-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .logo-text p {
            font-size: 12px;
            color: rgba(0, 0, 0, 0.7);
        }

        .admin-profile {
            margin-top: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .admin-avatar {
            width: 55px;
            height: 55px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: 700;
            color: black;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .admin-info h4 {
            font-size: 16px;
            font-weight: 600;
            color: black;
            margin-bottom: 4px;
        }

        .admin-info p {
            font-size: 12px;
            color: rgba(0, 0, 0, 0.7);
        }

        .sidebar-nav {
            flex: 1;
            padding: 24px;
            overflow-y: auto;
        }

        .nav-section {
            margin-bottom: 30px;
            
        }

        .nav-section-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgb(255, 246, 246);
            margin-bottom: 15px;
            padding-left: 12px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 16px;
            color: rgba(8, 8, 8, 0.8);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 4px;
            
            transition: all 0.3s ease;
            background: transparent;
            border: 1px solid transparent;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-color: rgba(255, 255, 255, 0.2);
            color: black;
            transform: translateX(5px);
        }

        .nav-item.active {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-color: rgba(255, 255, 255, 0.3);
            color: black;
            box-shadow: 0 10px 20px -10px rgba(0, 0, 0, 0.3);
        }

        .nav-item i {
            width: 22px;
            font-size: 18px;
        }

        .sector-badge {
            margin-left: auto;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Glass Main Content */
        .glass-main {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }

        /* Glass Top Bar */
        .glass-topbar {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 20px 30px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.2);
        }

        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: black;
            text-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .date-display {
            color: rgba(7, 6, 6, 0.8);
            font-size: 14px;
            margin-top: 5px;
        }

        .action-buttons {
            display: flex;
            gap: 12px;
        }

        .glass-btn {
            padding: 10px 20px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: black;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .glass-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -8px rgba(0, 0, 0, 0.3);
        }

        .glass-btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            box-shadow: 0 10px 20px -8px rgba(102, 126, 234, 0.5);
        }

        .glass-btn-primary:hover {
            background: linear-gradient(135deg, #764ba2, #667eea);
        }

        /* Glass Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            margin-bottom: 30px;
        }

        .glass-stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .glass-stat-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 30px 50px -20px rgba(0, 0, 0, 0.3);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .stat-title {
            font-size: 14px;
            font-weight: 600;
            color: rgba(0, 0, 0, 0.8);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: black;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .stat-value {
            font-size: 36px;
            font-weight: 800;
            color: black;
            margin-bottom: 5px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .stat-trend {
            font-size: 12px;
            color: rgba(10, 8, 8, 0.7);
        }

        /* Glass Filter Bar */
        .glass-filter {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .filter-form {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
            min-width: 180px;
        }

        .filter-group label {
            font-size: 11px;
            font-weight: 700;
            color: rgba(0, 0, 0, 0.7);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .glass-input {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 12px 18px;
            color: black;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .glass-input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.4);
        }

        .glass-input option {
            background: #2d3748;
            color: black;
        }

        .glass-input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        /* Glass Table */
        .glass-table-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .table-header {
            padding: 20px 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-header h3 {
            font-size: 16px;
            font-weight: 600;
            color: black;
        }

        .table-wrapper {
            overflow-x: auto;
            color:black;
        }

        .glass-table {
            width: 100%;
            border-collapse: collapse;
        }

        .glass-table th {
            text-align: left;
            padding: 18px 25px;
            font-size: 12px;
            font-weight: 700;
            color: rgba(0, 0, 0, 0.7);
            text-transform: uppercase;
            letter-spacing: 1px;
            background: rgba(255, 255, 255, 0.05);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .glass-table td {
            padding: 18px 25px;
            font-size: 14px;
            color: rgba(0, 0, 0, 0.9);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .glass-table tr:hover td {
            background: rgba(255, 255, 255, 0.1);
        }

        /* Sector Tags */
        .sector-tag {
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: black;
        }

        /* Type Badges */
        .type-badge {
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(5px);
        }

        .type-badge.call {
            background: rgba(37, 99, 235, 0.3);
            border: 1px solid rgba(37, 99, 235, 0.5);
            color: black;
        }

        .type-badge.email {
            background: rgba(147, 51, 234, 0.3);
            border: 1px solid rgba(147, 51, 234, 0.5);
            color: black;
        }

        /* Glass Delete Button */
        .glass-delete-btn {
            padding: 8px 12px;
            border-radius: 16px;
            background: rgba(239, 68, 68, 0.2);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #000000;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }

        .glass-delete-btn:hover {
            background: rgba(239, 68, 68, 0.4);
            transform: scale(1.05);
        }

        /* Glass Pagination */
        .glass-pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            padding: 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }

        .glass-page-link {
            min-width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            color: black;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .glass-page-link:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }

        .glass-page-link.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            box-shadow: 0 10px 20px -8px rgba(102, 126, 234, 0.5);
        }

        /* Empty State */
        .glass-empty-state {
            text-align: center;
            padding: 60px;
            color: rgba(0, 0, 0, 0.7);
        }

        .glass-empty-state i {
            font-size: 60px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.4);
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-glass {
            animation: fadeInUp 0.6s ease forwards;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .glass-container {
                flex-direction: column;
            }
            
            .glass-sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-form {
                flex-direction: column;
            }
            
            .glass-topbar {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }

        /* Print Styles */
        @media print {
            .glass-sidebar,
            .glass-filter,
            .action-buttons,
            .glass-delete-btn,
            .glass-pagination {
                display: none !important;
            }
            
            .glass-main {
                padding: 0;
            }
            
            .glass-table-container {
                background: black;
                color: black;
            }
            
            .glass-table th,
            .glass-table td {
                color: black;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background Bubbles -->
    <div class="bg-bubbles">
        <?php for ($i = 1; $i <= 15; $i++): ?>
        <span style="left: <?= rand(0, 100) ?>%; width: <?= rand(20, 80) ?>px; height: <?= rand(20, 80) ?>px; animation-duration: <?= rand(15, 30) ?>s; animation-delay: <?= rand(0, 10) ?>s;"></span>
        <?php endfor; ?>
    </div>

    <!-- Main Glass Container -->
    <div class="glass-container">
        <!-- Glass Sidebar -->
        <div class="glass-sidebar">
            <div class="sidebar-header">
                <div class="logo-area">
                    <div class="logo-icon">
                        <i class="fas fa-landmark"></i>
                    </div>
                    <div class="logo-text">
                        <h2>City Hall</h2>
                        <p>Office of the Mayor</p>
                    </div>
                </div>
                <div class="admin-profile">
                    <div class="admin-avatar">
                        <?= strtoupper(substr($adminData['fname'], 0, 1) . substr($adminData['lname'], 0, 1)) ?>
                    </div>
                    <div class="admin-info">
                        <h4><?= htmlspecialchars($adminName) ?></h4>
                        <p><i class="fas fa-crown" style="margin-right: 4px;"></i> City Mayor</p>
                    </div>
                </div>
            </div>

            <div class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Dashboard</div>
                    <a href="?" class="nav-item <?= $selected_sector == 'all' ? 'active' : '' ?>">
                        <i class="fas fa-chart-pie"></i>
                        <span>Overview</span>
                        <span class="sector-badge"><?= $total_completed ?></span>
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">Sectors</div>
                    <?php foreach ($sector_databases as $key => $config): 
                        $stats = $sector_stats[$config['name']] ?? ['total' => 0];
                    ?>
                    <a href="?sector=<?= $key ?>&from=<?= urlencode($start_date) ?>&to=<?= urlencode($end_date) ?>" 
                       class="nav-item <?= $selected_sector == $key ? 'active' : '' ?>">
                        <i class="fas <?= $config['icon'] ?>"></i>
                        <span><?= $config['name'] ?></span>
                        <span class="sector-badge"><?= $stats['total'] ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">Reports</div>
                    <a href="#" class="nav-item" onclick="window.print()">
                        <i class="fas fa-print"></i>
                        <span>Print Report</span>
                    </a>
                    <a  href="?download_pdf=1&from=<?= urlencode($start_date) ?>&to=<?= urlencode($end_date) ?>&sector=<?= $selected_sector ?>" class="nav-item">
                        <i class="fas fa-file-pdf"></i>
                        <span>Export PDF</span>
                    </a>
                    <a href="?download_csv=1&from=<?= urlencode($start_date) ?>&to=<?= urlencode($end_date) ?>&sector=<?= $selected_sector ?>" class="nav-item">
                        <i class="fas fa-file-csv"></i>
                        <span>Export CSV</span>
                    </a>
                    <a href="logout.php" class="nav-item">
                        <i class="fas fa-arrow-right-from-bracket"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Glass Main Content -->
        <div class="glass-main">
            <!-- Glass Top Bar -->
            <div class="glass-topbar animate-glass">
                <div>
                    <h1 class="page-title">
                        <?= $selected_sector != 'all' ? $sector_databases[$selected_sector]['name'] : 'City Overview Dashboard' ?>
                    </h1>
                    <div class="date-display">
                        <i class="far fa-calendar-alt" style="margin-right: 6px;"></i>
                        <?= !empty($start_date) ? date('M d, Y', strtotime($start_date)) : 'All time' ?>
                        <?= !empty($end_date) ? ' - ' . date('M d, Y', strtotime($end_date)) : '' ?>
                    </div>
                </div>
                <div class="action-buttons">
                    <button class="glass-btn" onclick="window.print()">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <a href="?download_pdf=1&from=<?= urlencode($start_date) ?>&to=<?= urlencode($end_date) ?>&sector=<?= $selected_sector ?>" class="glass-btn">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                    <a href="?download_csv=1&from=<?= urlencode($start_date) ?>&to=<?= urlencode($end_date) ?>&sector=<?= $selected_sector ?>" class="glass-btn glass-btn-primary text-white">
                        <i class="fas fa-download"></i> Export Data
                    </a>
                </div>
            </div>

            <!-- Glass Stats Cards -->
            <div class="stats-grid">
                <div class="glass-stat-card animate-glass" style="animation-delay: 0.1s;">
                    <div class="stat-header">
                        <span class="stat-title">Total Requests</span>
                        <div class="stat-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= number_format($total_completed) ?></div>
                    <div class="stat-trend">
                        <i class="fas fa-chart-line" style="margin-right: 4px;"></i> All sectors combined
                    </div>
                </div>
                
                <div class="glass-stat-card animate-glass" style="animation-delay: 0.2s;">
                    <div class="stat-header">
                        <span class="stat-title">Call Requests</span>
                        <div class="stat-icon">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= number_format($total_calls) ?></div>
                    <div class="stat-trend">
                        <?= $total_completed > 0 ? round(($total_calls/$total_completed)*100) : 0 ?>% of total
                    </div>
                </div>
                
                <div class="glass-stat-card animate-glass" style="animation-delay: 0.3s;">
                    <div class="stat-header">
                        <span class="stat-title">Email Requests</span>
                        <div class="stat-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= number_format($total_emails) ?></div>
                    <div class="stat-trend">
                        <?= $total_completed > 0 ? round(($total_emails/$total_completed)*100) : 0 ?>% of total
                    </div>
                </div>
                
                <div class="glass-stat-card animate-glass" style="animation-delay: 0.4s;">
                    <div class="stat-header">
                        <span class="stat-title">Active Sectors</span>
                        <div class="stat-icon">
                            <i class="fas fa-building"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= count(array_filter($sector_stats, fn($s) => $s['total'] > 0)) ?>/8</div>
                    <div class="stat-trend">
                        <i class="fas fa-check-circle" style="margin-right: 4px; color: #10b981;"></i> Sectors with activity
                    </div>
                </div>
            </div>

            <!-- Glass Filter Bar -->
            <div class="glass-filter animate-glass" style="animation-delay: 0.5s;">
                <form method="GET" class="filter-form">
                    <div class="filter-group">
                        <label><i class="far fa-calendar" style="margin-right: 4px;"></i> From Date</label>
                        <input type="date" name="from" value="<?= htmlspecialchars($start_date) ?>" class="glass-input">
                    </div>
                    <div class="filter-group">
                        <label><i class="far fa-calendar-check" style="margin-right: 4px;"></i> To Date</label>
                        <input type="date" name="to" value="<?= htmlspecialchars($end_date) ?>" class="glass-input">
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-sitemap" style="margin-right: 4px;"></i> Sector</label>
                        <select name="sector" class="glass-input">
                            <option value="all">All Sectors</option>
                            <?php foreach ($sector_databases as $key => $config): ?>
                            <option value="<?= $key ?>" <?= $selected_sector == $key ? 'selected' : '' ?>>
                                <?= htmlspecialchars($config['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="glass-btn glass-btn-success">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <a href="?" class="glass-btn">
                        <i class="fas fa-undo-alt"></i> Reset
                    </a>
                </form>
            </div>

            <!-- Glass Table -->
            <div class="glass-table-container animate-glass" style="animation-delay: 0.6s;">
                <div class="table-header">
                    <h3><i class="fas fa-list-ul" style="margin-right: 8px;"></i> Completed Service Requests</h3>
                    <span style="color: rgba(0, 0, 0, 0.7); font-size: 13px;"><?= $total_rows ?> records found</span>
                </div>

                <div class="table-wrapper">
                    <table class="glass-table text-black">
                        <thead>
                            <tr>
                                <th class="text-black-300">ID</th>
                                <th>Sector</th>
                                <th>Customer</th>
                                <th>Mobile</th>
                                <th>Reason</th>
                                <th>Type</th>
                                <th>Date & Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($paginated_data)): ?>
                                <?php foreach ($paginated_data as $q): ?>
                                <tr>
                                    <td><span style="font-weight: 600; color: black;">#<?= str_pad($q['id'], 3, '0', STR_PAD_LEFT) ?></span></td>
                                    <td>
                                        <span class="sector-tag">
                                            <i class="fas <?= $q['sector_icon'] ?>"></i>
                                            <?= htmlspecialchars($q['sector_display_name']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($q['name'] ?: $q['user_name'] ?: 'Anonymous') ?></td>
                                    <td style="font-family: monospace;"><?= htmlspecialchars($q['mobile_number']) ?></td>
                                    <td style="max-width: 250px;">
                                        <?= htmlspecialchars(substr($q['reason'] ?: 'No reason provided', 0, 40)) ?>
                                        <?= strlen($q['reason'] ?? '') > 40 ? '...' : '' ?>
                                    </td>
                                    <td>
                                        <?php if ($q['contact_type'] == 'call'): ?>
                                            <span class="type-badge call">
                                                <i class="fas fa-phone-alt"></i> Call
                                            </span>
                                        <?php else: ?>
                                            <span class="type-badge email">
                                                <i class="fas fa-envelope"></i> Email
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><?= date('M d, Y', strtotime($q['requested_at'])) ?></div>
                                        <span style="font-size: 11px; color: rgba(255,255,255,0.5);"><?= date('h:i A', strtotime($q['requested_at'])) ?></span>
                                    </td>
                                    <td>
                                        <button onclick="confirmDelete(<?= $q['id'] ?>, '<?= $q['sector_key'] ?>')" class="glass-delete-btn" title="Delete Record">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="glass-empty-state">
                                        <i class="fas fa-inbox"></i>
                                        <p style="font-size: 16px; margin-bottom: 4px;">No completed requests found</p>
                                        <p style="font-size: 13px;">Try adjusting your filters</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Glass Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="glass-pagination">
                    <?php
                    $adjacents = 2;
                    $start = max(1, $page - $adjacents);
                    $end = min($total_pages, $page + $adjacents);
                    
                    $query_params = http_build_query([
                        'from' => $start_date,
                        'to' => $end_date,
                        'sector' => $selected_sector
                    ]);
                    
                    if ($page > 1) echo '<a href="?page='.($page-1).'&'.$query_params.'" class="glass-page-link"><i class="fas fa-chevron-left"></i></a>';
                    if ($start > 1) { 
                        echo '<a href="?page=1&'.$query_params.'" class="glass-page-link">1</a>'; 
                        if ($start > 2) echo '<span class="glass-page-link" style="background: transparent; border: none;">...</span>'; 
                    }
                    for ($i = $start; $i <= $end; $i++) {
                        $activeClass = ($i == $page) ? 'active' : '';
                        echo '<a href="?page='.$i.'&'.$query_params.'" class="glass-page-link '.$activeClass.'">'.$i.'</a>';
                    }
                    if ($end < $total_pages) { 
                        if ($end < $total_pages - 1) echo '<span class="glass-page-link" style="background: transparent; border: none;">...</span>'; 
                        echo '<a href="?page='.$total_pages.'&'.$query_params.'" class="glass-page-link">'.$total_pages.'</a>'; 
                    }
                    if ($page < $total_pages) echo '<a href="?page='.($page+1).'&'.$query_params.'" class="glass-page-link"><i class="fas fa-chevron-right"></i></a>';
                    ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    function confirmDelete(id, sectorKey) {
        Swal.fire({
            title: 'Delete Record?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel',
            background: 'rgba(255, 255, 255, 0.1)',
            backdrop: 'rgba(0,0,0,0.5)',
            allowOutsideClick: false,
            customClass: {
                popup: 'animate__animated animate__fadeInDown'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Deleting...',
                    allowOutsideClick: false,
                    background: 'rgba(255, 255, 255, 0.1)',
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                fetch(window.location.pathname + '?delete=' + id + '&sector_key=' + sectorKey + '&from=<?= urlencode($start_date) ?>&to=<?= urlencode($end_date) ?>')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: 'The record has been deleted.',
                                confirmButtonColor: '#667eea',
                                background: 'rgba(255, 255, 255, 0.1)'
                            }).then(() => location.reload());
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Failed to delete the record.',
                                confirmButtonColor: '#667eea',
                                background: 'rgba(255, 255, 255, 0.1)'
                            });
                        }
                    })
                    .catch(() => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'An error occurred while deleting.',
                            confirmButtonColor: '#667eea',
                            background: 'rgba(255, 255, 255, 0.1)'
                        });
                    });
            }
        });
    }
    </script>
</body>
</html>
<?php 
$conn->close(); 
?>