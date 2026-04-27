<?php
session_start();
include("../components/db.php");

// Protect staff page
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'staff') {
    header("Location: ../login.php");
    exit;
}

$staff_id = $_SESSION['user_id'];

// Get staff name
$staffQuery = $conn->query("SELECT fname, lname FROM usr_tbl WHERE id = $staff_id");
$staffData = $staffQuery->fetch_assoc();
$staffName = $staffData['fname'] . ' ' . $staffData['lname'];

include("../components/staff_nav.php");

// ================= QUERIES STATISTICS =================

// Get total queries in the system
$totalQueriesQuery = $conn->query("SELECT COUNT(*) as total FROM call_requests");
$totalQueries = $totalQueriesQuery->fetch_assoc()['total'] ?? 0;

// Get queries by status
$pendingQuery = $conn->query("SELECT COUNT(*) as total FROM call_requests WHERE status = 'pending'");
$pendingCount = $pendingQuery->fetch_assoc()['total'] ?? 0;

$inProgressQuery = $conn->query("SELECT COUNT(*) as total FROM call_requests WHERE status = 'in_progress'");
$inProgressCount = $inProgressQuery->fetch_assoc()['total'] ?? 0;

$completedQuery = $conn->query("SELECT COUNT(*) as total FROM call_requests WHERE status = 'completed'");
$completedCount = $completedQuery->fetch_assoc()['total'] ?? 0;

// Get monthly user growth (users who submitted queries per month)
$monthlyUsersQuery = $conn->query("
    SELECT 
        DATE_FORMAT(requested_at, '%b') AS month,
        COUNT(DISTINCT user_id) as user_count,
        COUNT(*) as query_count
    FROM call_requests 
    WHERE requested_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY YEAR(requested_at), MONTH(requested_at)
    ORDER BY requested_at ASC
");

$months = [];
$userGrowth = [];
$queryVolume = [];

if ($monthlyUsersQuery && $monthlyUsersQuery->num_rows > 0) {
    while ($row = $monthlyUsersQuery->fetch_assoc()) {
        $months[] = $row['month'];
        $userGrowth[] = $row['user_count'];
        $queryVolume[] = $row['query_count'];
    }
}

// Calculate average queries per user
$avgQueriesPerUser = $totalQueries > 0 ? round($totalQueries / max(1, $totalQueries), 1) : 0;

// Get recent queries (last 5)
$recentQueriesQuery = $conn->query("
    SELECT id, name, mobile_number, reason, status, requested_at 
    FROM call_requests 
    ORDER BY requested_at DESC 
    LIMIT 5
");
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Staff Dashboard - Power2Connect</title>

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
    
    body {
      background: linear-gradient(135deg, #C5E6C0 0%, #C5E6C0 100%);
      background-attachment: fixed;
      min-height: 100vh;
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
    }
    
    /* Stat card */
    .stat-card {
      background: white;
      border-radius: 16px;
      padding: 1.5rem;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease;
      border-left: 4px solid var(--primary);
    }
    
    .stat-card:hover {
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
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
      background: #fef3c7;
      color: #92400e;
    }
    
    .badge-progress {
      background: #dbeafe;
      color: #1e40af;
    }
    
    .badge-completed {
      background: #d1fae5;
      color: #065f46;
    }
    
    /* Gradient headers */
    .gradient-header {
      background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
      color: white;
      padding: 1rem 1.5rem;
      border-radius: 16px 16px 0 0;
    }
    
    /* Chart container */
    .chart-container {
      position: relative;
      width: 100%;
      height: 250px;
      padding: 1rem;
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
    
    /* Welcome banner */
    .welcome-banner {
      background: linear-gradient(135deg, #74af77 0%, #b9c275 100%);
      border-radius: 16px;
      padding: 1.5rem 2rem;
      color: white;
      margin-bottom: 2rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    
    .date-badge {
      background: rgba(255,255,255,0.2);
      padding: 0.5rem 1rem;
      border-radius: 30px;
      font-size: 0.8rem;
      backdrop-filter: blur(5px);
    }
    
    @media (max-width: 768px) {
      .chart-container {
        height: 200px;
      }
      .main-content {
        padding: 1rem;
      }
    }
  </style>
</head>
<body>
  <div class="main-content">
    <!-- Welcome Banner -->
    <div class="welcome-banner">
      <div>
        <h1 class="text-2xl md:text-3xl font-bold mb-1 ">📊 Query Analytics Dashboard</h1>
        <p class="text-white/80 text-sm">Welcome, <?= htmlspecialchars(explode(' ', $staffName)[0]) ?>! Track all customer inquiries</p>
      </div>
      <div class="date-badge">
        <i class="far fa-calendar-alt mr-2"></i> <?= date('F j, Y') ?>
      </div>
    </div>

    <!-- Stats Cards - All Queries Overview -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <!-- Total Queries Card -->
      <div class="bg-white/50 rounded-lg border p-4 h-44 flex flex-col shadow-lg transition-transform hover:-translate-y-1">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-medium text-gray-500">Total Queries</span>
          <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
            <i class="fas fa-phone-alt text-blue-600"></i>
          </div>
        </div>
        <div class="text-3xl font-bold text-gray-800 mb-1"><?= number_format($totalQueries) ?></div>
        <div class="text-xs text-gray-400">All time inquiries</div>
      </div>
      
      <!-- Pending Queries Card -->
      <div class="bg-white/50 rounded-lg border p-4 h-44 flex flex-col shadow-lg transition-transform hover:-translate-y-1">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-medium text-gray-500">Pending</span>
          <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center">
            <i class="fas fa-clock text-amber-600"></i>
          </div>
        </div>
        <div class="text-3xl font-bold text-gray-800 mb-1"><?= number_format($pendingCount) ?></div>
        <div class="text-xs text-gray-400">Awaiting response</div>
      </div>
      
      <!-- In Progress Card -->
      <div class="bg-white/50 rounded-lg border p-4 h-44 flex flex-col shadow-lg transition-transform hover:-translate-y-1">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-medium text-gray-500">In Progress</span>
          <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
            <i class="fas fa-spinner text-blue-600"></i>
          </div>
        </div>
        <div class="text-3xl font-bold text-gray-800 mb-1"><?= number_format($inProgressCount) ?></div>
        <div class="text-xs text-gray-400">Being handled</div>
      </div>
      
      <!-- Completed Card -->
      <div class="bg-white/50 rounded-lg border p-4 h-44 flex flex-col shadow-lg transition-transform hover:-translate-y-1">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-medium text-gray-500">Completed</span>
          <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
            <i class="fas fa-check-circle text-green-600"></i>
          </div>
        </div>
        <div class="text-3xl font-bold text-gray-800 mb-1"><?= number_format($completedCount) ?></div>
        <div class="text-xs text-gray-400">Resolved</div>
      </div>
    </div>
    <!-- Monthly User Growth Chart -->
    <div class="p-6 mb-8 bg-white/50 rounded-lg border p-4 h-50 flex flex-col shadow-lg transition-transform hover:-translate-y-1">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-800">📈 Monthly User Growth & Query Volume</h3>
        <div class="flex gap-4">
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 bg-blue-600 rounded-full"></div>
            <span class="text-sm text-gray-600">New Users</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 bg-purple-500 rounded-full"></div>
            <span class="text-sm text-gray-600">Query Volume</span>
          </div>
        </div>
      </div>
      <div class="chart-container">
        <canvas id="growthChart"></canvas>
      </div>
      <div class="flex justify-between mt-4 text-sm text-gray-500">
        <span>📊 Avg Queries/User: <?= $avgQueriesPerUser ?></span>
        <span>📈 Growth Rate: +<?= count($userGrowth) > 1 ? round((end($userGrowth) - $userGrowth[0]) / max(1, $userGrowth[0]) * 100) : 0 ?>%</span>
      </div>
    </div>

    <!-- Recent Queries Table -->
    <div class="overflow-hidden bg-white/50 rounded-lg border p-4 h-50 flex flex-col shadow-lg transition-transform hover:-translate-y-1">
      
        <h3 class="font-semibold text-lg">📋 Recent Customer Queries</h3>
        <p class="text-black/80 text-sm mt-1">Latest inquiries from customers</p>
      <!-- </div> baka maging error babalikan mamaya☠️--> 
      <div class="p-6">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left border-b">
                <th class="pb-3 font-semibold text-gray-600">ID</th>
                <th class="pb-3 font-semibold text-gray-600">Customer</th>
                <th class="pb-3 font-semibold text-gray-600">Contact</th>
                <th class="pb-3 font-semibold text-gray-600">Concern</th>
                <th class="pb-3 font-semibold text-gray-600">Status</th>
                <th class="pb-3 font-semibold text-gray-600">Date</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              <?php if ($recentQueriesQuery && $recentQueriesQuery->num_rows > 0): ?>
                <?php while ($query = $recentQueriesQuery->fetch_assoc()): ?>
                <tr class="hover:bg-gray-50">
                  <td class="py-3 font-medium">#<?= str_pad($query['id'], 3, '0', STR_PAD_LEFT) ?></td>
                  <td class="py-3"><?= htmlspecialchars($query['name'] ?: 'Anonymous') ?></td>
                  <td class="py-3 font-mono"><?= htmlspecialchars($query['mobile_number']) ?></td>
                  <td class="py-3 max-w-xs truncate"><?= htmlspecialchars(substr($query['reason'] ?: '', 0, 40)) ?>...</td>
                  <td class="py-3">
                    <?php
                    $badge_class = $query['status'] == 'pending' ? 'badge-pending' : 
                                   ($query['status'] == 'in_progress' ? 'badge-progress' : 'badge-completed');
                    ?>
                    <span class="badge <?= $badge_class ?>">
                      <?= ucfirst(str_replace('_', ' ', $query['status'])) ?>
                    </span>
                  </td>
                  <td class="py-3 text-gray-500"><?= date('M d, h:i A', strtotime($query['requested_at'])) ?></td>
                </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" class="py-8 text-center text-gray-500">
                    <i class="fas fa-inbox text-3xl mb-2 text-gray-300"></i>
                    <p>No recent queries</p>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <footer class="mt-8 text-center">
      <p class="text-sm text-black/60">© <span id="year"></span> Power2Connect — Staff Analytics Dashboard</p>
    </footer>
  </div>

  <script>
    document.getElementById('year').textContent = new Date().getFullYear();

    // Monthly Growth Chart
    const ctx = document.getElementById('growthChart').getContext('2d');
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: <?= json_encode($months) ?>,
        datasets: [
          {
            label: 'New Users',
            data: <?= json_encode($userGrowth) ?>,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            borderWidth: 3,
            tension: 0.4,
            fill: true,
            pointRadius: 4,
            pointBackgroundColor: '#3b82f6'
          },
          {
            label: 'Query Volume',
            data: <?= json_encode($queryVolume) ?>,
            borderColor: '#8b5cf6',
            backgroundColor: 'rgba(139, 92, 246, 0.1)',
            borderWidth: 3,
            tension: 0.4,
            fill: true,
            pointRadius: 4,
            pointBackgroundColor: '#8b5cf6'
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              stepSize: 1,
              callback: function(value) {
                return value + ' users'
              }
            }
          }
        }
      }
    });
  </script>
</body>
</html>
<?php $conn->close(); ?>