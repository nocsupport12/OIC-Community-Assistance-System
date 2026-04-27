<?php
session_start();
include("../components/db.php");

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $fname = trim($_POST['fname']);
    $mnane = trim($_POST['mnane']);
    $lname = trim($_POST['lname']);
    $role = trim($_POST['role']);
    $profilePath = "";

    // upload profile
    if (!empty($_FILES['profile']['name'])) {
        $uploadDir = "../uploads/profile/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = time() . "_" . basename($_FILES["profile"]["name"]);
        $targetFile = $uploadDir . $fileName;
        move_uploaded_file($_FILES["profile"]["tmp_name"], $targetFile);
        $profilePath = "uploads/profile/" . $fileName;
    }

    // check if email already exists
    $check = $conn->prepare("SELECT id FROM usr_tbl WHERE email=?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error'] = "❌ Email already exists!";
    } else {
        $insert = $conn->prepare("INSERT INTO usr_tbl (email, password, fname, mnane, lname, profile, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $insert->bind_param("sssssss", $email, $password, $fname, $mnane, $lname, $profilePath, $role);
        if ($insert->execute()) {
            $_SESSION['success'] = "✅ Account created successfully!";
        } else {
            $_SESSION['error'] = "⚠️ Something went wrong. Try again.";
        }
    }

    header("Location: users.php");
    exit;
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
    <div class="flex flex-col items-center mb-8">
      <h2 class="text-3xl font-extrabold text-gray-700 tracking-wide">Add New Account</h2>
      <p class="text-gray-500 text-sm mt-1">Create a new user or admin account</p>
    </div>

    <!-- SUCCESS / ERROR MESSAGE -->
    <?php if (isset($_SESSION['success'])): ?>
      <div id="alertBox" class="bg-red-100 border border-red-300 text-red-700 text-center p-3 rounded-lg mb-6">
        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
      </div>
      <script>
        setTimeout(() => document.getElementById('alertBox').classList.add('fade-out'), 2500);
      </script>
    <?php elseif (isset($_SESSION['error'])): ?>
      <div id="alertBox" class="bg-red-100 border border-red-300 text-red-700 text-center p-3 rounded-lg mb-6">
        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
      </div>
      <script>
        setTimeout(() => document.getElementById('alertBox').classList.add('fade-out'), 2500);
      </script>
    <?php endif; ?>

    <!-- FORM -->
    <form method="POST" enctype="multipart/form-data" class="flex flex-col gap-8">
      
      <!-- IMAGE SECTION -->
      <div class="flex flex-col items-center">
        <div class="relative">
          <img id="profilePreview"
            src="../assets/default_profile.png"
            class="w-36 h-36 rounded-full object-cover border-4 border-red-500 shadow-md transition-all duration-200 hover:scale-105"
          />
          <label for="profile" class="absolute bottom-2 right-2 bg-red-600 text-white p-2 rounded-full cursor-pointer hover:bg-red-700 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15.232 5.232l3.536 3.536m-2.036-1.5A2.5 2.5 0 0015.5 7h-7A2.5 2.5 0 006 9.5v7A2.5 2.5 0 008.5 19h7a2.5 2.5 0 002.5-2.5v-7a2.5 2.5 0 00-.768-1.768z" />
            </svg>
          </label>
          <input type="file" name="profile" id="profile" class="hidden" accept="image/*"
                 onchange="previewProfile(event)" />
        </div>
      </div>

      <!-- INPUTS -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
          <label class="text-sm text-gray-600">First Name</label>
          <input type="text" name="fname"
            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-red-500 focus:outline-none" required />
        </div>

        <div>
          <label class="text-sm text-gray-600">Middle Name</label>
          <input type="text" name="mnane"
            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-red-500 focus:outline-none" />
        </div>

        <div>
          <label class="text-sm text-gray-600">Last Name</label>
          <input type="text" name="lname"
            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-red-500 focus:outline-none" required />
        </div>

        <div>
          <label class="text-sm text-gray-600">Email</label>
          <input type="email" name="email"
            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-red-500 focus:outline-none" required />
        </div>

        <div>
          <label class="text-sm text-gray-600">Password</label>
          <input type="password" name="password"
            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-red-500 focus:outline-none" required />
        </div>

        <div>
          <label class="text-sm text-gray-600">Role</label>
          <select name="role"
            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 bg-white focus:ring-2 focus:ring-red-500 focus:outline-none" required>
            <option value="">Select Role</option>
            <option value="Staff">Staff</option>
          </select>
        </div>
      </div>

      <!-- ACTION -->
      <div class="flex justify-center mt-6">
        <button id="saveBtn" type="submit"
          class="bg-red-600 text-white px-6 py-2.5 rounded-lg hover:bg-red-700 transition flex items-center gap-2">
          <span id="saveText">Create Account</span>
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
  document.getElementById('saveText').textContent = "Creating...";
  document.getElementById('spinner').classList.remove('hidden');
});
</script>

</body>
</html>
