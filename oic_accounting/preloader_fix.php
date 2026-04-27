<?php
session_start();
include("../components/admin_nav.php");
// Protect admin page
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../login.php");
    exit;
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    // Get the user's profile image path
    $imgQuery = $conn->query("SELECT profile FROM usr_tbl WHERE id = $id");
    if ($imgQuery && $imgQuery->num_rows > 0) {
        $row = $imgQuery->fetch_assoc();
        $imagePath = "../" . $row['profile'];

        // Delete image file if it exists
        if (!empty($row['profile']) && file_exists($imagePath)) {
            unlink($imagePath);
        }
    }

    // Delete user record
    $conn->query("DELETE FROM usr_tbl WHERE id = $id");

    // Redirect with success message
    echo "<script>
        alert('Account and profile image deleted successfully!');
        window.location.href = '" . $_SERVER['PHP_SELF'] . "';
    </script>";
    exit;
}

// ================= FETCH EMPLOYEES =================
$sql = "SELECT id, fname, mnane, lname, email, profile FROM usr_tbl WHERE role = 'staff'";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>MLRS</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
  <div class="min-h-screen flex flex-col items-center py-10 px-4">

    <div class="bg-white shadow-md rounded-2xl p-6 w-full max-w-5xl">
      <h2 class="text-2xl font-semibold text-gray-700 mb-6 text-center">List of Users</h2>

      <?php if ($result && $result->num_rows > 0): ?>
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm text-left text-gray-700 border rounded-lg overflow-hidden">
            <thead>
              <tr class="bg-gradient-to-r from-green-500 to-yellow-400 text-white">
                <th class="py-3 px-4">Profile</th>
                <th class="py-3 px-4">Full Name</th>
                <th class="py-3 px-4">Email</th>
                <th class="py-3 px-4">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
              <?php while ($row = $result->fetch_assoc()): ?>
                <tr class="hover:bg-gray-50 transition">
                  <td class="py-3 px-4">
                    <?php if (!empty($row['profile'])): ?>
                      <img src="../<?= htmlspecialchars($row['profile']) ?>" 
                           alt="Profile"
                           class="w-12 h-12 rounded-full object-cover border border-gray-300"
                           onerror="this.src='../assets/default_profile.png'">
                    <?php else: ?>
                      <img src="../assets/default_profile.png" 
                           alt="Default Profile"
                           class="w-12 h-12 rounded-full object-cover border border-gray-300">
                    <?php endif; ?>
                  </td>
                  <td class="py-3 px-4">
                    <?= htmlspecialchars($row['fname'] . ' ' . $row['mnane'] . ' ' . $row['lname']) ?>
                  </td>
                  <td class="py-3 px-4"><?= htmlspecialchars($row['email']) ?></td>   
                  <td>
                        <button class="delete-btn" onclick="confirmDelete(<?= $row['id'] ?>)">Delete</button>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="text-center text-gray-500">No employee accounts found.</p>
      <?php endif; ?>

    </div>
  </div>
<script>
    function confirmDelete(id) {
      if (confirm("Are you sure you want to delete this account?")) {
        window.location.href = "?delete=" + id;
      }
    } 
      // Hide preloader when DOM is ready
  window.addEventListener('DOMContentLoaded', () => {
      const preloader = document.getElementById('preloader');
      if(preloader) preloader.style.display = 'none';
  });
</script>
</body>
</html>