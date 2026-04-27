<?php
session_start();
include('components/db.php');

$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';
$error = '';
$success = '';

if (!$token || !$email) {
    $error = "Invalid password reset link.";
} else {
    $email = $conn->real_escape_string(trim($email));
    $sql = "SELECT id, reset_expiry FROM usr_tbl WHERE email = '$email' AND reset_token = '$token' LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows == 1) {
        $user = $result->fetch_assoc();

        if (strtotime($user['reset_expiry']) < time()) {
            $error = "Reset link has expired. Please request a new one.";
        } else {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $password = $_POST['password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';

                if (empty($password) || empty($confirm_password)) {
                    $error = "Please fill out all fields.";
                } elseif ($password !== $confirm_password) {
                    $error = "Passwords do not match.";
                } elseif (strlen($password) < 6) {
                    $error = "Password must be at least 6 characters.";
                } else {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $update_sql = "UPDATE usr_tbl SET password = '$password_hash', reset_token = NULL, reset_expiry = NULL WHERE id = " . $user['id'];
                    if ($conn->query($update_sql) === TRUE) {
                        $success = "Password has been reset successfully. You can now login.";          
                    } else {
                        $error = "Database error. Please try again.";
                    }
                }
            }
        }
    } else {
        $error = "Invalid password reset link.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Reset Password</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body class="bg-gradient-to-tr from-[#e6f4ea] to-[#d0f0d6] font-sans min-h-screen flex items-center justify-center p-4">

<div class="min-h-screen flex items-center justify-center w-full">
  <div class="flex flex-col md:flex-row bg-white rounded-3xl shadow-2xl w-full max-w-5xl overflow-hidden">

    <!-- Left Form -->
    <div class="md:w-1/2 p-10 flex flex-col justify-center relative">
      <h2 class="text-3xl font-bold mb-6 text-green-700 text-center flex items-center justify-center gap-2">
        <i class="fas fa-key"></i> Reset Password
      </h2>

      <?php if (!empty($success)): ?>
        <p class="mb-6 text-green-800 bg-green-100 p-3 rounded-md border border-green-200 text-center font-medium"><?= htmlspecialchars($success) ?></p>
      <?php elseif (!empty($error)): ?>
        <p class="mb-6 text-red-700 bg-red-100 p-3 rounded-md border border-red-200 text-center font-medium"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <?php if (empty($success)): ?>
      <form method="POST" action="" class="space-y-6">
        <div class="relative">
          <label for="password" class="block mb-2 text-sm font-medium text-gray-700">New Password</label>
          <input
            type="password"
            name="password"
            id="password"
            required
            minlength="6"
            class="mt-1 w-full bg-gray-50 border border-gray-300 focus:border-green-600 focus:ring focus:ring-green-300 transition p-3 rounded-xl outline-none"
            placeholder="Enter new password"
          />
        </div>

        <div class="relative">
          <label for="confirm_password" class="block mb-2 text-sm font-medium text-gray-700">Confirm New Password</label>
          <input
            type="password"
            name="confirm_password"
            id="confirm_password"
            required
            minlength="6"
            class="mt-1 w-full bg-gray-50 border border-gray-300 focus:border-green-600 focus:ring focus:ring-green-300 transition p-3 rounded-xl outline-none"
            placeholder="Confirm new password"
          />
        </div>

        <button
          type="submit"
          class="w-full py-3 bg-gradient-to-r from-green-600 to-green-500 text-white rounded-xl font-semibold shadow-lg hover:shadow-none hover:scale-105 transform transition duration-300"
        >
          Reset Password
        </button>
      </form>
      <?php endif; ?>

      <div class="mt-6 text-center">
        <a href="login.php" class="inline-block text-green-700 hover:text-green-900 hover:underline font-medium transition-colors duration-300">
          &larr; Back to Login
        </a>
      </div>
    </div>

    <!-- Right Image -->
    <div class="md:w-1/2 hidden md:block">
      <img 
        src="assets/hero_bg.jpg" 
        alt="Reset Password Illustration" 
        class="h-full w-full object-cover"
      />
    </div>
  </div>
</div>

</body>
</html>
