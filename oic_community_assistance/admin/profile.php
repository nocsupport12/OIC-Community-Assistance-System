<?php
session_start();
include("../components/db.php");

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// ✅ Fetch user data
$query = $conn->prepare("SELECT * FROM usr_tbl WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$user = $query->get_result()->fetch_assoc();

// ✅ Update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = $_POST['fname'];
    $mnane = $_POST['mnane'];
    $lname = $_POST['lname'];
    $email = $_POST['email'];
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : $user['password'];
    $profilePath = $user['profile']; // keep current image if no new upload

    // ✅ Handle profile upload
    if (!empty($_FILES['profile']['name'])) {
        $uploadDir = "../uploads/profile/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileName = time() . "_" . basename($_FILES["profile"]["name"]);
        $targetFile = $uploadDir . $fileName;

        // If upload succeeds
        if (move_uploaded_file($_FILES["profile"]["tmp_name"], $targetFile)) {
            // ✅ Delete old image if not default
            if (!empty($user['profile']) && file_exists("../" . $user['profile']) && $user['profile'] !== "assets/default_profile.png") {
                unlink("../" . $user['profile']);
            }

            // ✅ Update new path
            $profilePath = "uploads/profile/" . $fileName;
        }
    }

    // ✅ Update all fields
    $update = $conn->prepare("UPDATE usr_tbl SET fname=?, mnane=?, lname=?, email=?, password=?, profile=? WHERE id=?");
    $update->bind_param("ssssssi", $fname, $mnane, $lname, $email, $password, $profilePath, $user_id);

    if ($update->execute()) {
        $_SESSION['success'] = "Profile updated successfully!";
        header("Location: profile.php");
        exit;
    }
}

include("../components/admin_nav.php");
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MLRS</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  .fade-out { animation: fadeOut 1s ease-in-out forwards; }
  @keyframes fadeOut { from {opacity:1;} to {opacity:0; visibility:hidden;} }
</style>
</head>
<body class="bg-gradient-to-br from-gray-100 via-white to-gray-100 text-gray-800 font-sans min-h-screen">

<main class="flex justify-center items-start p-6 lg:p-10">
  <div class="bg-white/80 backdrop-blur-lg shadow-2xl rounded-3xl w-full max-w-4xl p-8 md:p-10">

    <!-- HEADER -->
    <div class="flex flex-col items-center mb-8 text-center">
      <h2 class="text-3xl font-extrabold text-green-700 tracking-wide">My Profile</h2>
      <p class="text-gray-500 text-sm mt-1">Manage your personal information and account settings</p>
    </div>

    <!-- SUCCESS MESSAGE -->
    <?php if (isset($_SESSION['success'])): ?>
      <div id="successMsg" class="bg-green-100 border border-green-300 text-green-700 text-center p-3 rounded-lg mb-6">
        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
      </div>
      <script>
        setTimeout(() => document.getElementById('successMsg').classList.add('fade-out'), 2500);
      </script>
    <?php endif; ?>

    <!-- PROFILE FORM -->
    <form method="POST" enctype="multipart/form-data" class="flex flex-col gap-8">
      
      <!-- IMAGE SECTION -->
      <div class="flex flex-col items-center">
        <div class="relative">
          <img id="profilePreview"
            src="<?= !empty($user['profile']) ? '../' . htmlspecialchars($user['profile']) : '../assets/default_profile.png' ?>"
            alt="Profile Picture"
            class="w-36 h-36 rounded-full object-cover border-4 border-green-500 shadow-md transition-all duration-200 hover:scale-105"
          />
          <label for="profile" class="absolute bottom-2 right-2 bg-green-600 text-white p-2 rounded-full cursor-pointer hover:bg-green-700 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15.232 5.232l3.536 3.536m-2.036-1.5A2.5 2.5 0 0015.5 7h-7A2.5 2.5 0 006 9.5v7A2.5 2.5 0 008.5 19h7a2.5 2.5 0 002.5-2.5v-7a2.5 2.5 0 00-.768-1.768z" />
            </svg>
          </label>
          <input type="file" name="profile" id="profile" class="hidden" accept="image/*"
                 onchange="previewProfile(event)" />
        </div>
      </div>

      <!-- INPUT FIELDS -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
          <label class="text-sm text-gray-600">First Name</label>
          <input type="text" name="fname" value="<?= htmlspecialchars($user['fname']) ?>" 
            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-green-500 focus:outline-none" required />
        </div>

        <div>
          <label class="text-sm text-gray-600">Middle Name</label>
          <input type="text" name="mnane" value="<?= htmlspecialchars($user['mnane']) ?>" 
            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-green-500 focus:outline-none" />
        </div>

        <div>
          <label class="text-sm text-gray-600">Last Name</label>
          <input type="text" name="lname" value="<?= htmlspecialchars($user['lname']) ?>" 
            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-green-500 focus:outline-none" required />
        </div>

        <div>
          <label class="text-sm text-gray-600">Email</label>
          <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" 
            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-green-500 focus:outline-none" required />
        </div>

        <div>
          <label class="text-sm text-gray-600">New Password</label>
          <input type="password" name="password" placeholder="Leave blank to keep current password"
            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-green-500 focus:outline-none" />
        </div>

        <div>
          <label class="text-sm text-gray-600">Role</label>
          <input type="text" value="<?= htmlspecialchars($user['role']) ?>" 
            class="w-full border border-gray-200 rounded-lg px-4 py-2.5 bg-gray-100 text-gray-500 cursor-not-allowed" disabled />
        </div>
      </div>

      <!-- ACTION -->
      <div class="flex justify-center mt-6">
        <button id="saveBtn" type="submit"
          class="bg-green-600 text-white px-6 py-2.5 rounded-lg hover:bg-green-700 transition flex items-center gap-2">
          <span id="saveText">Save Changes</span>
          <svg id="spinner" class="hidden w-5 h-5 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
          </svg>
        </button>
      </div>
    </form>
  </div>
</main>

<script>
function previewProfile(event) {
  const img = document.getElementById('profilePreview');
  img.src = URL.createObjectURL(event.target.files[0]);
}

const saveBtn = document.getElementById('saveBtn');
saveBtn.addEventListener('click', () => {
  document.getElementById('saveText').textContent = "Saving...";
  document.getElementById('spinner').classList.remove('hidden');
});
</script>

</body>
</html>
