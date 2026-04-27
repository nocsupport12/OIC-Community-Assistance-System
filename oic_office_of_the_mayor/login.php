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
      'admin' => 'admin/reports.php',
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
<title>Login - Power2Connect</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
/* Base Styles */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background-image: url('assets/bg_office.jpg');
    background-size: cover;
    background-position: center;
    position: relative;
}

/* Copyright text */
.copyright {
    position: fixed;
    bottom: 1rem;
    left: 1rem;
    font-size: 0.75rem;
    color: white;
}

/* Main container */
.login-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
}

/* Card wrapper */
.card-wrapper {
    display: flex;
    flex-direction: column;
    border-radius: 1.5rem;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    width: 100%;
    max-width: 48rem;
    overflow: hidden;
}

/* Left side - Logo with glass effect */
.logo-section {
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 1.5rem 1.5rem 0 0;
    padding: 2rem;
}

.logo-section img {
    height: 17.5rem;
    width: 40rem;
}

/* Right side - Form with glass effect */
.form-section {
    width: 100%;
    padding: 3.5rem;
    display: flex;
    flex-direction: column;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 0 0 1.5rem 1.5rem;
}

.form-title {
    text-align: left;
    color: #1f2937;
    font-weight: 700;
    margin-bottom: 1.25rem;
    font-size: 1.25rem;
}

/* Form styles */
.login-form {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

/* Error message */
.error-message {
    color: #b91c1c;
    text-align: center;
    background-color: #fee2e2;
    padding: 0.75rem;
    border-radius: 0.375rem;
    border: 1px solid #fecaca;
    font-weight: 500;
}

/* Form groups */
.form-group {
    margin-bottom: 0.25rem;
}

.form-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.25rem;
}

.form-input {
    width: 100%;
    background: rgba(255, 255, 255, 0.3);
    border: 1px solid #d1d5db;
    padding: 0.75rem;
    border-radius: 0.75rem;
    outline: none;
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    font-size: 1rem;
}

.form-input:focus {
    border-color: #1e3a8a;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5);
}

/* Login button */
.login-btn {
    width: 100%;
    padding: 0.75rem 1rem;
    background: linear-gradient(to right, #15803d, #15803d);
    color: white;
    border: none;
    border-radius: 0.75rem;
    font-weight: 600;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 1rem;
}

.login-btn:hover {
    box-shadow: none;
    transform: scale(1.05);
}

/* Forgot password link */
.forgot-link {
    text-align: center;
    margin-top: 0.5rem;
}

.forgot-link a {
    color: #1e3a8a;
    font-weight: 700;
    font-size: 0.875rem;
    text-decoration: none;
}

.forgot-link a:hover {
    color: #166534;
    text-decoration: underline;
}

/* Desktop styles */
@media (min-width: 768px) {
    .card-wrapper {
        flex-direction: row;
    }
    
    .logo-section {
        width: 50%;
        border-radius: 1.5rem 0 0 1.5rem;
    }
    
    .form-section {
        width: 50%;
        border-radius: 0 1.5rem 1.5rem 0;
    }
}

/* Mobile adjustments */
@media (max-width: 767px) {
    .form-section {
        padding: 2rem;
    }
    
    .logo-section img {
        height: 12rem;
        width: 12rem;
    }
}
</style>
</head>

<body>
<p class="copyright">© 2026 One Intranet Corporation. All Rights Reserved.</p>

<div class="login-container">
  <div class="card-wrapper">
    
    <!-- Left Image with Glass Effect -->
    <div class="logo-section">
      <img 
        src="assets/oic_logo.png" 
        alt="Power2Connect Logo" 
      />
    </div>

    <!-- Right Form with Glass Effect -->
    <div class="form-section">
      <p class="form-title">Login to your account</p>

      <form class="login-form" method="POST" action="login.php">
        <?php if(!empty($error_message)): ?>
          <p class="error-message"><?= htmlspecialchars($error_message) ?></p>
        <?php endif; ?>

        <div class="form-group">
          <label class="form-label" for="email">Email Address</label>
          <input 
            name="email"
            type="email" 
            id="email"
            placeholder="Enter your email address" 
            required autofocus autocomplete="username"
            class="form-input"
          />
        </div>

        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <input 
            name="password"
            id="password" 
            type="password" 
            placeholder="********" 
            required autocomplete="current-password"
            class="form-input"
          />
        </div>

        <button type="submit" class="login-btn">
          Login
        </button>

        <p class="forgot-link"><a href="forgot_password.php">Can't Login?</a></p>
      </form>
    </div>
  </div>
</div>
</body>
</html>