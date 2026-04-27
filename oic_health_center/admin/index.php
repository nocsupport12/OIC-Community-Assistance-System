<?php
session_start();
include("../components/db.php");

// Protect admin page
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../login.php");
    exit;
}

include("../components/admin_nav.php");

// ================= DATABASE QUERIES =================

// Get total queries count
$totalQueriesQuery = $conn->query("SELECT COUNT(*) AS total_queries FROM call_requests");
$totalQueries = $totalQueriesQuery && $totalQueriesQuery->num_rows > 0 ? $totalQueriesQuery->fetch_assoc()['total_queries'] : 0;

// Get pending queries count
$pendingQueriesQuery = $conn->query("SELECT COUNT(*) AS pending_queries FROM call_requests WHERE status = 'pending'");
$pendingQueries = $pendingQueriesQuery && $pendingQueriesQuery->num_rows > 0 ? $pendingQueriesQuery->fetch_assoc()['pending_queries'] : 0;

// Get in_progress queries count
$inProgressQueriesQuery = $conn->query("SELECT COUNT(*) AS in_progress_queries FROM call_requests WHERE status = 'in_progress'");
$inProgressQueries = $inProgressQueriesQuery && $inProgressQueriesQuery->num_rows > 0 ? $inProgressQueriesQuery->fetch_assoc()['in_progress_queries'] : 0;

// Get completed queries count
$completedQueriesQuery = $conn->query("SELECT COUNT(*) AS completed_queries FROM call_requests WHERE status = 'completed'");
$completedQueries = $completedQueriesQuery && $completedQueriesQuery->num_rows > 0 ? $completedQueriesQuery->fetch_assoc()['completed_queries'] : 0;

// Get monthly queries data for chart
$monthlyQueriesQuery = $conn->query("
    SELECT 
        DATE_FORMAT(requested_at, '%b %Y') AS month_label,
        COUNT(*) AS total_queries,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_queries
    FROM call_requests
    GROUP BY YEAR(requested_at), MONTH(requested_at)
    ORDER BY YEAR(requested_at), MONTH(requested_at)
    LIMIT 6
");

$months = [];
$totalQueriesData = [];
$completedQueriesData = [];

if ($monthlyQueriesQuery && $monthlyQueriesQuery->num_rows > 0) {
    while ($row = $monthlyQueriesQuery->fetch_assoc()) {
        $months[] = $row['month_label'];
        $totalQueriesData[] = $row['total_queries'];
        $completedQueriesData[] = $row['completed_queries'];
    }
}

// Calculate monthly averages
$avgTotalQueries = count($totalQueriesData) > 0 ? round(array_sum($totalQueriesData) / count($totalQueriesData)) : 0;
$avgCompletedQueries = count($completedQueriesData) > 0 ? round(array_sum($completedQueriesData) / count($completedQueriesData)) : 0;

// Get recent queries
$recentQueriesQuery = $conn->query("
    SELECT id, name, mobile_number, reason, status, requested_at 
    FROM call_requests 
    ORDER BY requested_at DESC 
    LIMIT 5
");

// Get completed queries
$completedQueriesList = $conn->query("
    SELECT id, name, mobile_number, reason, requested_at 
    FROM call_requests 
    WHERE status = 'completed' 
    ORDER BY requested_at DESC 
    LIMIT 5
");

// Get staff performance data
$staffPerformanceQuery = $conn->query("
    SELECT 
        u.fname,
        u.lname,
        COUNT(DISTINCT cs.session_id) as chats_handled
    FROM usr_tbl u
    LEFT JOIN staff_chat_sessions cs ON u.id = cs.staff_id
    WHERE u.role = 'staff'
    GROUP BY u.id
    ORDER BY chats_handled DESC
    LIMIT 5
");

// Get active staff count
$activeStaffQuery = $conn->query("SELECT COUNT(*) as count FROM usr_tbl WHERE role = 'staff'");
$activeStaff = $activeStaffQuery->fetch_assoc()['count'];

// Get archive count
$archiveCountQuery = $conn->query("SELECT COUNT(*) as count FROM archived_queries");
$archiveCount = $archiveCountQuery->fetch_assoc()['count'];

// Calculate response rate
$responseRate = $totalQueries > 0 ? round(($completedQueries / $totalQueries) * 100) : 0;
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Admin Dashboard - Power2Connect</title>

  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Chart.js CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    
    * {
      font-family: 'Inter', sans-serif;
    }
    
    :root {
      --primary: #2563eb;
      --primary-light: #3b82f6;
      --primary-soft: #dbeafe;
      --secondary: #7c3aed;
      --success: #059669;
      --warning: #d97706;
      --danger: #dc2626;
      --gray-50: #f9fafb;
      --gray-100: #f3f4f6;
      --gray-200: #e5e7eb;
      --gray-600: #4b5563;
      --gray-700: #374151;
      --gray-800: #1f2937;
      --gray-900: #111827;
    }
    
  body {
    margin: 0; /* remove default body margin */
    height: 100vh; /* make body full height */
    background: linear-gradient(to right, #b3cde0, #e0f7fa);
    background-attachment: fixed; /* keeps gradient fixed on scroll */
}
    
    /* Glass morphism effect */
    .glass-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 20px;
      box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.2);
      transition: all 0.3s ease;
    }
    
    .glass-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
      background: rgba(255, 255, 255, 1);
    }
    
    /* Modern stat card */
    .stat-card {
      background: white;
      border-radius: 16px;
      padding: 1.5rem;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
      transition: all 0.3s ease;
      border-left: 4px solid var(--primary);
      position: relative;
      overflow: hidden;
    }
    
    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      right: 0;
      width: 100px;
      height: 100px;
      background: linear-gradient(135deg, rgba(37, 99, 235, 0.1) 0%, rgba(124, 58, 237, 0.1) 100%);
      border-radius: 50%;
      transform: translate(30px, -30px);
    }
    
    .stat-card:hover {
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    
    /* Modern table styling */
    .modern-table {
      border-collapse: separate;
      border-spacing: 0 8px;
    }
    
    .modern-table thead th {
      color: var(--gray-600);
      font-weight: 600;
      font-size: 0.75rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      padding: 0.75rem 1rem;
    }
    
    .modern-table tbody tr {
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
      transition: all 0.2s;
    }
    
    .modern-table tbody tr:hover {
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
      transform: scale(1.01);
    }
    
    .modern-table tbody td {
      padding: 1rem;
      color: var(--gray-700);
    }
    
    /* Status badges */
    .badge {
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 600;
      display: inline-block;
    }
    
    .badge-pending {
      background: linear-gradient(135deg, #fef3c7, #fde68a);
      color: #92400e;
    }
    
    .badge-progress {
      background: linear-gradient(135deg, #dbeafe, #bfdbfe);
      color: #1e40af;
    }
    
    .badge-completed {
      background: linear-gradient(135deg, #d1fae5, #a7f3d0);
      color: #065f46;
    }
    
    /* Gradient headers */
    .gradient-header {
      background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
      color: white;
      padding: 1rem 1.5rem;
      border-radius: 16px 16px 0 0;
    }
    
    /* Modern buttons */
    .btn-primary {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      padding: 0.5rem 1rem;
      border-radius: 12px;
      font-size: 0.875rem;
      font-weight: 500;
      transition: all 0.2s;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
    }
    
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3);
    }
    
    .btn-secondary {
      background: white;
      color: var(--gray-700);
      padding: 0.5rem 1rem;
      border-radius: 12px;
      font-size: 0.875rem;
      font-weight: 500;
      transition: all 0.2s;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      border: 1px solid var(--gray-200);
    }
    
    .btn-secondary:hover {
      background: var(--gray-50);
      border-color: var(--gray-300);
    }
    
    /* Chart containers */
    .chart-container {
      position: relative;
      width: 100%;
      height: 200px;
      padding: 1rem;
    }
    
    .pie-chart-container {
      position: relative;
      width: 100%;
      height: 180px;
      max-width: 180px;
      margin: 0 auto;
    }
    
    /* Main content area */
    .main-content {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border-radius: 30px;
      padding: 2rem;
      margin: 1rem;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }
    
    @media (max-width: 768px) {
      .chart-container {
        height: 160px;
      }
      .pie-chart-container {
        height: 140px;
        max-width: 140px;
      }
      .main-content {
        padding: 1rem;
        margin: 0.5rem;
      }
    }
  </style>
</head>
<body>
  <div class="main-content">
    <!-- Header -->
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-8">
      
      <!-- Quick Actions -->
      <div class="flex gap-3 mt-4 md:mt-0">
        <a href="queries.php" class="btn-primary">
          <i class="fas fa-list"></i> All Queries
        </a>
        <a href="staff.php" class="btn-secondary">
          <i class="fas fa-users"></i> Staff
        </a>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <!-- Total Queries -->
      <div class="stat-card">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-medium text-gray-500">Total Queries</span>
          <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
            <i class="fas fa-phone-alt text-blue-600"></i>
          </div>
        </div>
        <div class="text-3xl font-bold text-gray-800 mb-1"><?= number_format($totalQueries) ?></div>
        <div class="text-xs text-gray-400">All time requests</div>
      </div>
      
      <!-- Pending -->
      <div class="stat-card" style="border-left-color: var(--warning);">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-medium text-gray-500">Pending</span>
          <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center">
            <i class="fas fa-clock text-amber-600"></i>
          </div>
        </div>
        <div class="text-3xl font-bold text-gray-800 mb-1"><?= number_format($pendingQueries) ?></div>
        <div class="text-xs text-gray-400">Awaiting response</div>
      </div>
      
      <!-- In Progress -->
      <div class="stat-card" style="border-left-color: var(--primary);">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-medium text-gray-500">In Progress</span>
          <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center">
            <i class="fas fa-spinner text-indigo-600"></i>
          </div>
        </div>
        <div class="text-3xl font-bold text-gray-800 mb-1"><?= number_format($inProgressQueries) ?></div>
        <div class="text-xs text-gray-400">Being handled</div>
      </div>
      
      <!-- Completed -->
      <div class="stat-card" style="border-left-color: var(--success);">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-medium text-gray-500">Completed</span>
          <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
            <i class="fas fa-check-circle text-green-600"></i>
          </div>
        </div>
        <div class="text-3xl font-bold text-gray-800 mb-1"><?= number_format($completedQueries) ?></div>
        <div class="text-xs text-gray-400">Resolved calls</div>
      </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
      <!-- Monthly Trend -->
      <div class="lg:col-span-2 glass-card p-6">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-semibold text-gray-800">Monthly Performance</h3>
          <div class="flex gap-4">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 bg-blue-600 rounded-full"></div>
              <span class="text-sm text-gray-600">Total</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 bg-green-500 rounded-full"></div>
              <span class="text-sm text-gray-600">Completed</span>
            </div>
          </div>
        </div>
        <div class="chart-container">
          <canvas id="trendChart"></canvas>
        </div>
        <div class="flex justify-between mt-4 text-sm text-gray-500">
          <span>📊 Avg Total: <?= $avgTotalQueries ?>/mo</span>
          <span>✅ Avg Completed: <?= $avgCompletedQueries ?>/mo</span>
        </div>
      </div>

      <!-- Status Distribution -->
      <div class="glass-card p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Status Distribution</h3>
        <div class="pie-chart-container">
          <canvas id="statusChart"></canvas>
        </div>
        <div class="grid grid-cols-3 gap-3 mt-6">
          <div class="text-center">
            <div class="text-lg font-bold text-amber-600"><?= $pendingQueries ?></div>
            <div class="text-xs text-gray-500">Pending</div>
          </div>
          <div class="text-center">
            <div class="text-lg font-bold text-blue-600"><?= $inProgressQueries ?></div>
            <div class="text-xs text-gray-500">In Progress</div>
          </div>
          <div class="text-center">
            <div class="text-lg font-bold text-green-600"><?= $completedQueries ?></div>
            <div class="text-xs text-gray-500">Completed</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tables Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
      <!-- Recent Queries -->
      <div class="glass-card overflow-hidden">
        <div class="gradient-header flex justify-between items-center">
          <h3 class="font-semibold flex items-center gap-2">
            <i class="fas fa-history"></i> Recent Queries
          </h3>
          <a href="queries.php" class="text-white/80 hover:text-white text-sm flex items-center gap-1">
            View all <i class="fas fa-arrow-right text-xs"></i>
          </a>
        </div>
        <div class="p-4">
          <table class="w-full modern-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Number</th>
                <th>Status</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($recentQueriesQuery && $recentQueriesQuery->num_rows > 0): ?>
                <?php while ($query = $recentQueriesQuery->fetch_assoc()): ?>
                <tr>
                  <td class="font-medium text-gray-800">#<?= str_pad($query['id'], 3, '0', STR_PAD_LEFT) ?></td>
                  <td><?= htmlspecialchars($query['name'] ?: 'Unknown') ?></td>
                  <td class="font-mono"><?= htmlspecialchars($query['mobile_number']) ?></td>
                  <td>
                    <?php
                    $badge_class = $query['status'] == 'pending' ? 'badge-pending' : 
                                   ($query['status'] == 'in_progress' ? 'badge-progress' : 'badge-completed');
                    ?>
                    <span class="badge <?= $badge_class ?>">
                      <?= ucfirst(str_replace('_', ' ', $query['status'])) ?>
                    </span>
                  </td>
                  <td class="text-gray-500"><?= date('M d', strtotime($query['requested_at'])) ?></td>
                </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="5" class="text-center py-8 text-gray-500">No recent queries</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Completed Queries -->
      <div class="glass-card overflow-hidden">
        <div class="gradient-header flex justify-between items-center">
          <h3 class="font-semibold flex items-center gap-2">
            <i class="fas fa-check-circle"></i> Recently Completed
          </h3>
          <a href="queries.php?filter=completed" class="text-white/80 hover:text-white text-sm flex items-center gap-1">
            View all <i class="fas fa-arrow-right text-xs"></i>
          </a>
        </div>
        <div class="p-4">
          <table class="w-full modern-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Number</th>
                <th>Concern</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($completedQueriesList && $completedQueriesList->num_rows > 0): ?>
                <?php while ($query = $completedQueriesList->fetch_assoc()): ?>
                <tr>
                  <td class="font-medium text-gray-800">#<?= str_pad($query['id'], 3, '0', STR_PAD_LEFT) ?></td>
                  <td><?= htmlspecialchars($query['name'] ?: 'Unknown') ?></td>
                  <td class="font-mono"><?= htmlspecialchars($query['mobile_number']) ?></td>
                  <td class="max-w-[150px] truncate"><?= htmlspecialchars(substr($query['reason'] ?: '', 0, 20)) ?>...</td>
                  <td class="text-gray-500"><?= date('M d', strtotime($query['requested_at'])) ?></td>
                </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="5" class="text-center py-8 text-gray-500">No completed queries</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Bottom Stats -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Staff Performance -->
      <div class="glass-card overflow-hidden">
        <div class="gradient-header">
          <h3 class="font-semibold flex items-center gap-2">
            <i class="fas fa-users"></i> Top Staff
          </h3>
        </div>
        <div class="p-4">
          <?php if ($staffPerformanceQuery && $staffPerformanceQuery->num_rows > 0): ?>
            <div class="space-y-3">
              <?php while ($staff = $staffPerformanceQuery->fetch_assoc()): ?>
              <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                <span class="font-medium text-gray-700"><?= htmlspecialchars($staff['fname'] . ' ' . $staff['lname']) ?></span>
                <span class="text-sm bg-blue-100 text-blue-600 px-3 py-1 rounded-full"><?= $staff['chats_handled'] ?> chats</span>
              </div>
              <?php endwhile; ?>
            </div>
          <?php else: ?>
            <p class="text-center text-gray-500 py-4">No staff data</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Quick Stats -->
      <div class="glass-card overflow-hidden">
        <div class="gradient-header">
          <h3 class="font-semibold"><i class="fas fa-chart-line text-2xl"></i> Quick Stats</h3>
        </div>
        <div class="p-4 space-y-4">
          <div>
            <div class="flex justify-between text-sm mb-2">
              <span class="text-gray-600">Response Rate</span>
              <span class="font-bold text-green-600"><?= $responseRate ?>%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div class="bg-gradient-to-r from-blue-500 to-green-500 h-2 rounded-full" style="width: <?= $responseRate ?>%"></div>
            </div>
          </div>
          <div class="flex justify-between items-center py-2 border-b border-gray-100">
            <span class="text-gray-600">Active Staff</span>
            <span class="font-bold text-blue-600"><?= $activeStaff ?></span>
          </div>
          <div class="flex justify-between items-center py-2">
            <span class="text-gray-600">Archive Size</span>
            <span class="font-bold text-purple-600"><?= $archiveCount ?> items</span>
          </div>
        </div>
      </div>

      <!-- Archive Card -->
      <div class="glass-card overflow-hidden">
        <div class="gradient-header">
          <h3 class="font-semibold flex items-center gap-2">
            <i class="fas fa-archive"></i> Archive
          </h3>
        </div>
        <div class="p-4">
          <div class="flex items-center justify-between mb-4">
            <span class="text-gray-600">Archived queries</span>
            <span class="text-2xl font-bold text-purple-600"><?= $archiveCount ?></span>
          </div>
          <a href="archived.php" class="w-full btn-primary justify-center">
            <i class="fas fa-archive"></i> Browse Archive
          </a>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <footer class="mt-8 text-center">
      <p class="text-sm text-white/60">© <span id="year"></span> Power2Connect — Admin Dashboard</p>
    </footer>
  </div>

  <!-- Charts Script -->
  <script>
    document.getElementById('year').textContent = new Date().getFullYear();

    // Monthly Trend Chart
    const trendCtx = document.getElementById('trendChart').getContext('2d');
    new Chart(trendCtx, {
      type: 'line',
      data: {
        labels: <?= json_encode($months) ?>,
        datasets: [
          {
            label: 'Total Queries',
            data: <?= json_encode($totalQueriesData) ?>,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            borderWidth: 3,
            tension: 0.4,
            pointRadius: 4,
            pointBackgroundColor: '#3b82f6',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointHoverRadius: 6
          },
          {
            label: 'Completed',
            data: <?= json_encode($completedQueriesData) ?>,
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            borderWidth: 3,
            tension: 0.4,
            pointRadius: 4,
            pointBackgroundColor: '#10b981',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointHoverRadius: 6
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { 
            grid: { display: false },
            ticks: { font: { size: 11 } }
          },
          y: { 
            beginAtZero: true,
            ticks: { 
              stepSize: 1,
              font: { size: 11 }
            }
          }
        }
      }
    });

    // Status Pie Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
      type: 'doughnut',
      data: {
        labels: ['Pending', 'In Progress', 'Completed'],
        datasets: [{
          data: [<?= $pendingQueries ?>, <?= $inProgressQueries ?>, <?= $completedQueries ?>],
          backgroundColor: ['#f59e0b', '#3b82f6', '#10b981'],
          borderWidth: 0,
          hoverOffset: 8
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        cutout: '70%'
      }
    });
  </script>
</body>
</html>
<?php $conn->close(); ?>