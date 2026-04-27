<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('components/db.php');

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $email = trim($_POST['email']);
  $password = trim($_POST['password']);

  $stmt = $conn->prepare("SELECT * FROM usr_tbl WHERE email = ?");
  if (!$stmt) die("DB error: " . $conn->error);

  $stmt->bind_param("s", $email);
  $stmt->execute();
  $user = $stmt->get_result()->fetch_assoc();

  if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];

    $role = strtolower($user['role']);
    $redirect = [
      'admin' => 'admin/index.php',
      'staff' => 'staff/index.php',
    ][$role] ?? false;

    if ($redirect) {
      header("Location: $redirect");
      exit;
    } else {
      $error_message = "There is a conflict on your account.";
    }
  } else {
    $error_message = $user ? "Invalid password." : "No user found with this email.";
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>OIC Login - Engineering Office</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>

<body class="font-sans min-h-screen flex items-center justify-center bg-cover " style="background-image: url('assets/gc5.png');">
<p class="fixed bottom-4 left-4 text-xs text-[#212529]"> © 2026 One Intranet Corporation. All Rights Reserved.</p>


<div class="min-h-screen flex items-center justify-center w-cover h-90">
  <div class="flex flex-col md:flex-row bg-white rounded-3xl shadow-2xl w-full max-w-5xl overflow-hidden">
    <!-- Left Image -->
<div class="md:w-1/2 md:flex items-center ">
  <img 
    src="assets/logo.png" 
    alt="oic logo" 
    class="h-50 w-65 ml-7"
  />
</div>


    <!-- Right Form -->
    <div class="md:w-2/3 w-full p-14 flex flex-col">
    <div class="flex items-center gap-3 justify-center mb-4">
  </div>
  <p class="text-left text-gray-600 font-bold mb-5 text-xl">Login to One Intranet Community <br>ACCOUNTING OFFICE</p>

  <form class="space-y-5" method="POST" action="login.php">
    <?php if(!empty($error_message)): ?>
      <p class="text-black-500 text-center bg-[#DDA15E] p-3 rounded-md border border-[#DDA15E] font-medium"><?= htmlspecialchars($error_message) ?></p>
    <?php endif; ?>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1" for="email">Email Address</label>
      <input 
        name="email"
        type="email" 
        id="email"
        placeholder="Enter your email address" 
        required autofocus autocomplete="username"
        class="mt-1 w-full bg-gray-50 border border-[#7CB518] focus:border-[#7CB518] focus:ring focus:ring-[#7CB518] p-3 rounded-xl outline-none"
      />
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1" for="password">Password</label>
      <input 
        name="password"
        id="password" 
        type="password" 
        placeholder="********" 
        required autocomplete="current-password"
        class="mt-1 w-full bg-gray-50 border border-[#7CB518] focus:border-[#7CB518] focus:ring focus:ring-[#7CB518] p-3 rounded-xl outline-none"
      />
    </div>

    <button type="submit" class="w-full py-3 bg-gradient-to-r from-[#007200] to-[#7CB518] text-white rounded-xl font-semibold shadow-lg hover:shadow-none hover:scale-105 transform transition duration-300">
      Login
    </button>

    <p class="text-center"><a href="forgot_password.php" class=" text-s text-gray-800 hover:text-red-900 hover:underline">Can't Login?</a></p>
  </form>
</div>

</body>
</html>








