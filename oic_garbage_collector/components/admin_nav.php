<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("../components/db.php");

$user_fullname = "Guest";

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT fname, mnane, lname FROM usr_tbl WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $fname = ucfirst($row['fname']);
        $mname = $row['mnane'] ? strtoupper(substr($row['mnane'], 0, 1)) . '.' : '';
        $lname = ucfirst($row['lname']);
        $user_fullname = "$fname $mname $lname";
    }
    $stmt->close();
}
?>
<!-- Tailwind CDN -->
<script src="https://cdn.tailwindcss.com"></script>
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
  :root {
    --tw-primary: #2563eb;
    --tw-accent1: #60a5fa;
    --tw-accent2: #43991f;
    --tw-accent3: #fdd400;
  }

  #preloader img {
    transition: transform 0.3s ease;
  }
  #preloader img:hover {
    transform: scale(1.05);
  }
</style>

<!-- Preloader -->
<div id="preloader" class="fixed inset-0 bg-white flex items-center justify-center z-50">
  <img src="../assets/P2C_logo1.png" alt="Loading..." class="h-24 w-24">
</div>

<nav class="bg-white border-b border-gray-200">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between h-16 items-center">
      
      <!-- Left Section -->
      <div class="flex items-center">
        <a href="index.php" class="flex-shrink-0 flex items-center">
          <img src="../assets/P2C_logo1.png" class="block h-9 w-auto" alt="Logo">
        </a>

        <!-- Desktop Links -->
        <div class="hidden sm:flex sm:space-x-8 sm:ml-10">
          <a href="index.php" class="text-gray-700 hover:text-green-600 font-medium">Dashboard</a>
          <a href="queries.php" class="text-gray-700 hover:text-green-600 font-medium">Queries</a>
          <a href="reports.php" class="text-gray-700 hover:text-green-600 font-medium">Reports</a>
          <a href="archived.php" class="text-gray-700 hover:text-green-600 font-medium">Archive</a>
          <a href="knowledge_admin.php" class="text-gray-700 hover:text-green-600 font-medium">Edit Ai</a>
          <a href="ai_usage_dashboard.php" class="text-gray-700 hover:text-green-600 font-medium">AI_USAGE</a>
          
        </div>
      </div>

      <!-- Right Section -->
      <div class="hidden sm:flex sm:items-center relative">
        <button id="userMenuButton"
          class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md 
                 text-gray-700 bg-white hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition"
          onclick="toggleUserMenu()">
          <span id="userName" class="truncate max-w-[120px] sm:max-w-[200px]"><?= htmlspecialchars($user_fullname) ?></span>
          <svg id="userMenuArrow" class="ml-2 h-4 w-4 fill-current text-gray-600 transition-transform duration-200"
               xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
            <path fill-rule="evenodd" 
                  d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.27a.75.75 0 01.02-1.06z" 
                  clip-rule="evenodd" />
          </svg>
        </button>

        <!-- Dropdown -->
        <div id="userMenu" 
             class="hidden absolute right-0 top-full mt-2 w-48 bg-white border border-gray-200 rounded-md shadow-lg py-2 transform origin-top scale-95 opacity-0 transition-all duration-200 ease-out z-50">
          <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition">Profile</a>
          <a href="users.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition">Add Users</a>
          <a href="staff.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition">Staff</a>
          <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition">Log out</a>
        </div>
      </div>

      <!-- Mobile Menu Button -->
      <div class="sm:hidden flex items-center">
        <button id="mobileMenuButton" class="p-2 rounded-md text-gray-600 hover:text-green-600 focus:outline-none focus:ring-2 focus:ring-green-500">
          <svg id="mobileMenuIconOpen" class="h-6 w-6 block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
          <svg id="mobileMenuIconClose" class="h-6 w-6 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>
  </div>

  <!-- Mobile Menu -->
  <div id="mobileMenu" class="hidden sm:hidden border-t border-gray-200 bg-white">
    <div class="px-4 pt-4 pb-3 space-y-3">
      <a href="index.php" class="block text-gray-700 hover:text-green-600 font-medium">Dashboard</a>
      <a href="queries.php" class="block text-gray-700 hover:text-green-600 font-medium">Queries</a>
      <a href="reports.php" class="block text-gray-700 hover:text-green-600 font-medium">Reports</a>
      <a href="archived.php" class="block text-gray-700 hover:text-green-600 font-medium">Archive</a>
      <hr>
      <a href="profile.php" class="block text-gray-700 hover:text-green-600 font-medium">Profile</a>
      <a href="staff.php" class="block text-gray-700 hover:text-green-600 font-medium">Staff</a>
      <a href="users.php" class="block text-gray-700 hover:text-green-600 font-medium">Users</a>
      <a href="logout.php" class="block text-gray-700 hover:text-green-600 font-medium">Log Out</a>
    </div>
  </div>
</nav>

<!-- Scripts -->
<script>
  // User dropdown
  function toggleUserMenu() {
    const menu = document.getElementById('userMenu');
    const arrow = document.getElementById('userMenuArrow');
    const isHidden = menu.classList.contains('hidden');

    if (isHidden) {
      menu.classList.remove('hidden');
      setTimeout(() => {
        menu.classList.remove('opacity-0', 'scale-95');
        menu.classList.add('opacity-100', 'scale-100');
        arrow.classList.add('rotate-180');
      }, 10);
    } else {
      menu.classList.add('opacity-0', 'scale-95');
      arrow.classList.remove('rotate-180');
      setTimeout(() => menu.classList.add('hidden'), 150);
    }
  }

  // Close user menu when clicking outside
  window.addEventListener('click', function (e) {
    const menu = document.getElementById('userMenu');
    const button = document.getElementById('userMenuButton');
    const arrow = document.getElementById('userMenuArrow');
    if (!button.contains(e.target) && !menu.contains(e.target)) {
      menu.classList.add('opacity-0', 'scale-95');
      arrow.classList.remove('rotate-180');
      setTimeout(() => menu.classList.add('hidden'), 150);
    }
  });

  // Mobile menu toggle
  const mobileMenuButton = document.getElementById('mobileMenuButton');
  const mobileMenu = document.getElementById('mobileMenu');
  const iconOpen = document.getElementById('mobileMenuIconOpen');
  const iconClose = document.getElementById('mobileMenuIconClose');

  mobileMenuButton.addEventListener('click', () => {
    const isOpen = !mobileMenu.classList.contains('hidden');
    mobileMenu.classList.toggle('hidden');
    iconOpen.classList.toggle('hidden', !mobileMenu.classList.contains('hidden'));
    iconClose.classList.toggle('hidden', mobileMenu.classList.contains('hidden'));
  });

  // Preloader fade out
  window.addEventListener('load', () => {
    const preloader = document.getElementById('preloader');
    const dashboard = document.getElementById('dashboard');
    preloader.classList.add('opacity-0');
    setTimeout(() => {
      preloader.style.display = 'none';
      if (dashboard) dashboard.classList.remove('hidden');
    }, 500);
  });
</script>
