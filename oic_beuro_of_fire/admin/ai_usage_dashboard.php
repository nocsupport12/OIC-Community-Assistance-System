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
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold">AI Usage Dashboard</h1>
            <a href="knowledge_admin.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                ← Back to Knowledge Admin
            </a>
        </div>
        
        <?php if (isset($tableError)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?= $tableError ?>
        </div>
        <?php endif; ?>
        
        <?php if (!$tableExists && !isset($tableCreated)): ?>
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
            <strong>Notice:</strong> No usage data yet. The table will be created automatically when AI is first used.
        </div>
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-gray-500 text-sm">Total Queries</h3>
                <p class="text-3xl font-bold"><?= number_format($totalQueries) ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-gray-500 text-sm">Gemini AI Calls</h3>
                <p class="text-3xl font-bold text-blue-600"><?= number_format($geminiQueries) ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-gray-500 text-sm">Database Answers</h3>
                <p class="text-3xl font-bold text-green-600"><?= number_format($dbQueries) ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-gray-500 text-sm">Local Fallback</h3>
                <p class="text-3xl font-bold text-gray-600"><?= number_format($localQueries) ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-gray-500 text-sm">Est. Tokens</h3>
                <p class="text-3xl font-bold text-purple-600"><?= number_format($totalTokens) ?></p>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-bold mb-4">Quick Actions</h2>
            <div class="flex gap-4">
                <button onclick="refreshData()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    <i class="fas fa-sync-alt mr-2"></i>Refresh Data
                </button>
                <button onclick="exportData()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    <i class="fas fa-download mr-2"></i>Export CSV
                </button>
                <button onclick="clearOldData()" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                    <i class="fas fa-trash mr-2"></i>Clear Old Data
                </button>
            </div>
        </div>
        
        <!-- Usage Chart -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-bold mb-4">Daily Usage (Last 30 Days)</h2>
            <?php if ($dailyUsage && mysqli_num_rows($dailyUsage) > 0): ?>
            <canvas id="usageChart" height="100"></canvas>
            <?php else: ?>
            <div class="text-center py-8 text-gray-500">
                No usage data available yet. Start using the chatbot to see statistics.
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Recent Logs -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold mb-4">Recent AI Interactions</h2>
            <?php if ($recentLogs && mysqli_num_rows($recentLogs) > 0): ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="p-3 text-left">Time</th>
                            <th class="p-3 text-left">Source</th>
                            <th class="p-3 text-left">User Message</th>
                            <th class="p-3 text-left">Response Preview</th>
                            <th class="p-3 text-left">Tokens</th>
                            <th class="p-3 text-left">Time (ms)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($log = mysqli_fetch_assoc($recentLogs)): ?>
                        <tr class="border-t hover:bg-gray-50">
                            <td class="p-3"><?= date('M d, H:i', strtotime($log['created_at'])) ?></td>
                            <td class="p-3">
                                <span class="px-2 py-1 rounded text-xs font-semibold 
                                    <?= $log['source'] == 'gemini' ? 'bg-blue-100 text-blue-700' : 
                                       ($log['source'] == 'database' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700') ?>">
                                    <?= ucfirst($log['source']) ?>
                                </span>
                            </td>
                            <td class="p-3 max-w-xs truncate"><?= htmlspecialchars($log['user_message']) ?></td>
                            <td class="p-3 max-w-xs truncate"><?= htmlspecialchars(substr($log['ai_response'], 0, 50)) ?>...</td>
                            <td class="p-3"><?= $log['tokens_estimated'] ?></td>
                            <td class="p-3"><?= round($log['response_time']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-8 text-gray-500">
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