<?php
// admin/ai_usage_dashboard.php
session_start();
include("../components/db.php");

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../login.php");
    exit;
}

include("../components/admin_nav.php");

// Check if table exists
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'ai_usage_log'");
$tableExists = mysqli_num_rows($tableCheck) > 0;

if (!$tableExists) {
    // Create the table if it doesn't exist
    $createTable = "CREATE TABLE IF NOT EXISTS ai_usage_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_id VARCHAR(50),
        user_message TEXT,
        ai_response TEXT,
        tokens_estimated INT,
        response_time FLOAT,
        source VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if (mysqli_query($conn, $createTable)) {
        $tableCreated = true;
    } else {
        $tableError = "Failed to create table: " . mysqli_error($conn);
    }
}

// Get usage statistics with error checking
$totalQueries = 0;
$geminiQueries = 0;
$dbQueries = 0;
$localQueries = 0;
$totalTokens = 0;
$avgResponseTime = 0;

if ($tableExists || isset($tableCreated)) {
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM ai_usage_log");
    if ($result) $totalQueries = $result->fetch_assoc()['count'] ?? 0;
    
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM ai_usage_log WHERE source='gemini'");
    if ($result) $geminiQueries = $result->fetch_assoc()['count'] ?? 0;
    
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM ai_usage_log WHERE source='database'");
    if ($result) $dbQueries = $result->fetch_assoc()['count'] ?? 0;
    
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM ai_usage_log WHERE source='local'");
    if ($result) $localQueries = $result->fetch_assoc()['count'] ?? 0;
    
    $result = mysqli_query($conn, "SELECT SUM(tokens_estimated) as total FROM ai_usage_log WHERE source='gemini'");
    if ($result) $totalTokens = $result->fetch_assoc()['total'] ?? 0;
    
    $result = mysqli_query($conn, "SELECT AVG(response_time) as avg FROM ai_usage_log");
    if ($result) $avgResponseTime = $result->fetch_assoc()['avg'] ?? 0;
}

// Get daily usage for chart - FIXED: renamed 'database' alias to 'db_count'
$dailyUsage = false;
if ($tableExists || isset($tableCreated)) {
    $dailyUsage = mysqli_query($conn, "
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as total,
            SUM(CASE WHEN source='gemini' THEN 1 ELSE 0 END) as gemini,
            SUM(CASE WHEN source='database' THEN 1 ELSE 0 END) as db_count
        FROM ai_usage_log 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ");
}

// Get recent logs
$recentLogs = false;
if ($tableExists || isset($tableCreated)) {
    $recentLogs = mysqli_query($conn, "
        SELECT * FROM ai_usage_log 
        ORDER BY created_at DESC 
        LIMIT 50
    ");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Usage Dashboard - Power2Connect</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Reset & Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f3f4f6;
            line-height: 1.5;
        }

        /* Container */
        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .header h1 {
            font-size: 1.875rem;
            font-weight: 700;
            color: #111827;
        }

        .back-btn {
            background-color: #2563eb;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            text-decoration: none;
            transition: background-color 0.2s;
        }

        .back-btn:hover {
            background-color: #1d4ed8;
        }

        /* Alert Messages */
        .alert {
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
            border-width: 1px;
            border-style: solid;
        }

        .alert-error {
            background-color: #fee2e2;
            border-color: #f87171;
            color: #b91c1c;
        }

        .alert-warning {
            background-color: #fef3c7;
            border-color: #fbbf24;
            color: #b45309;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        @media (min-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(5, 1fr);
            }
        }

        .stat-card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 1.5rem;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
        }

        .stat-value {
            font-size: 1.875rem;
            font-weight: 700;
            margin-top: 0.25rem;
        }

        .stat-value.blue { color: #2563eb; }
        .stat-value.green { color: #059669; }
        .stat-value.gray { color: #4b5563; }
        .stat-value.purple { color: #7c3aed; }

        /* Quick Actions */
        .quick-actions {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .quick-actions h2 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.375rem;
            color: white;
            cursor: pointer;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            transition: background-color 0.2s;
        }

        .btn i {
            margin-right: 0.5rem;
        }

        .btn-blue {
            background-color: #2563eb;
        }
        .btn-blue:hover {
            background-color: #1d4ed8;
        }

        .btn-green {
            background-color: #059669;
        }
        .btn-green:hover {
            background-color: #047857;
        }

        .btn-red {
            background-color: #dc2626;
        }
        .btn-red:hover {
            background-color: #b91c1c;
        }

        /* Chart Section */
        .chart-section {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .chart-section h2 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .chart-container {
            height: 100px;
        }

        .no-data {
            text-align: center;
            padding: 2rem;
            color: #6b7280;
        }

        /* Table Section */
        .table-section {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 1.5rem;
        }

        .table-section h2 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 0.75rem;
            background-color: #f9fafb;
            font-weight: 600;
            color: #374151;
        }

        td {
            padding: 0.75rem;
            border-top: 1px solid #e5e7eb;
        }

        tr:hover {
            background-color: #f9fafb;
        }

        /* Source Badges */
        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }

        .badge-blue {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .badge-green {
            background-color: #d1fae5;
            color: #065f46;
        }

        .badge-gray {
            background-color: #f3f4f6;
            color: #374151;
        }

        /* Text utilities */
        .truncate {
            max-width: 20rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .text-gray-500 {
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>AI Usage Dashboard</h1>
            <a href="knowledge_admin.php" class="back-btn">← Back to Knowledge Admin</a>
        </div>
        
        <?php if (isset($tableError)): ?>
        <div class="alert alert-error">
            <?= $tableError ?>
        </div>
        <?php endif; ?>
        
        <?php if (!$tableExists && !isset($tableCreated)): ?>
        <div class="alert alert-warning">
            <strong>Notice:</strong> No usage data yet. The table will be created automatically when AI is first used.
        </div>
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3 class="stat-label">Total Queries</h3>
                <p class="stat-value"><?= number_format($totalQueries) ?></p>
            </div>
            <div class="stat-card">
                <h3 class="stat-label">Gemini AI Calls</h3>
                <p class="stat-value blue"><?= number_format($geminiQueries) ?></p>
            </div>
            <div class="stat-card">
                <h3 class="stat-label">Database Answers</h3>
                <p class="stat-value green"><?= number_format($dbQueries) ?></p>
            </div>
            <div class="stat-card">
                <h3 class="stat-label">Local Fallback</h3>
                <p class="stat-value gray"><?= number_format($localQueries) ?></p>
            </div>
            <div class="stat-card">
                <h3 class="stat-label">Est. Tokens</h3>
                <p class="stat-value purple"><?= number_format($totalTokens) ?></p>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2>Quick Actions</h2>
            <div class="btn-group">
                <button onclick="refreshData()" class="btn btn-blue">
                    <i class="fas fa-sync-alt"></i>Refresh Data
                </button>
                <button onclick="exportData()" class="btn btn-green">
                    <i class="fas fa-download"></i>Export CSV
                </button>
                <button onclick="clearOldData()" class="btn btn-red">
                    <i class="fas fa-trash"></i>Clear Old Data
                </button>
            </div>
        </div>
        
        <!-- Usage Chart -->
        <div class="chart-section">
            <h2>Daily Usage (Last 30 Days)</h2>
            <?php if ($dailyUsage && mysqli_num_rows($dailyUsage) > 0): ?>
            <div class="chart-container">
                <canvas id="usageChart"></canvas>
            </div>
            <?php else: ?>
            <div class="no-data">
                No usage data available yet. Start using the chatbot to see statistics.
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Recent Logs -->
        <div class="table-section">
            <h2>Recent AI Interactions</h2>
            <?php if ($recentLogs && mysqli_num_rows($recentLogs) > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Source</th>
                            <th>User Message</th>
                            <th>Response Preview</th>
                            <th>Tokens</th>
                            <th>Time (ms)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($log = mysqli_fetch_assoc($recentLogs)): ?>
                        <tr>
                            <td><?= date('M d, H:i', strtotime($log['created_at'])) ?></td>
                            <td>
                                <span class="badge <?= $log['source'] == 'gemini' ? 'badge-blue' : ($log['source'] == 'database' ? 'badge-green' : 'badge-gray') ?>">
                                    <?= ucfirst($log['source']) ?>
                                </span>
                            </td>
                            <td class="truncate"><?= htmlspecialchars($log['user_message']) ?></td>
                            <td class="truncate"><?= htmlspecialchars(substr($log['ai_response'], 0, 50)) ?>...</td>
                            <td><?= $log['tokens_estimated'] ?></td>
                            <td><?= round($log['response_time']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="no-data">
                No recent activity logs.
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Chart data from PHP
        <?php if ($dailyUsage && mysqli_num_rows($dailyUsage) > 0): ?>
        const dates = [<?php 
            mysqli_data_seek($dailyUsage, 0);
            $dates = [];
            $gemini = [];
            $database = [];
            while($row = mysqli_fetch_assoc($dailyUsage)) {
                $dates[] = "'" . $row['date'] . "'";
                $gemini[] = $row['gemini'];
                $database[] = $row['db_count']; // FIXED: using db_count instead of database
            }
            echo implode(',', array_reverse($dates));
        ?>];
        
        const geminiData = [<?php echo implode(',', array_reverse($gemini)); ?>];
        const databaseData = [<?php echo implode(',', array_reverse($database)); ?>];
        
        new Chart(document.getElementById('usageChart'), {
            type: 'line',
            data: {
                labels: dates,
                datasets: [
                    {
                        label: 'Gemini AI Calls',
                        data: geminiData,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Database Answers',
                        data: databaseData,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
        <?php endif; ?>
        
        // Refresh data
        function refreshData() {
            window.location.reload();
        }
        
        // Export data as CSV
        function exportData() {
            window.location.href = 'export_usage.php';
        }
        
        // Clear old data (with confirmation)
        function clearOldData() {
            if (confirm('Are you sure you want to clear all old data? This action cannot be undone.')) {
                window.location.href = 'clear_usage.php';
            }
        }
    </script>
</body>
</html>