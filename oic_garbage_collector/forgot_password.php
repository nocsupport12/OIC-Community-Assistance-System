<?php
session_start();
include('components/db.php');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
    $email = $conn->real_escape_string(trim($_POST['email']));

    $sql = "SELECT id, fname FROM usr_tbl WHERE email = '$email' LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $token = bin2hex(random_bytes(16));
        $expiry = date('Y-m-d H:i:s', time() + 300); // 5 minutes

        $update_sql = "UPDATE usr_tbl SET reset_token = '$token', reset_expiry = '$expiry' WHERE id = " . $user['id'];
        if ($conn->query($update_sql) === TRUE) {
            $reset_link = "http://localhost/mhelandlinda/reset_password.php?token=$token&email=" . urlencode($email);

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'ylsc04binangonan@gmail.com';
                $mail->Password   = 'rpbh gzen crvn yqqx';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('ylsc04binangonan@gmail.com', 'MLRS Admin');
                $mail->addAddress($email);

                $mail->isHTML(false);
                $mail->Subject = 'Password Reset Request';
                $mail->Body = "Hi " . htmlspecialchars($user['fname']) . ",\n\n"
                    . "Click the link below to reset your password. This link expires in 5 minutes.\n\n"
                    . "$reset_link\n\nThanks,\nMLRS Team";

                $mail->send();
                $success = "Password reset link has been sent to your email.";
            } catch (Exception $e) {
                $error = "Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            $error = "Database error.";
        }
    } else {
        $error = "Email address not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Forgot Password</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body class="bg-gradient-to-tr from-[#e6f4ea] to-[#d0f0d6] font-sans min-h-screen flex items-center justify-center p-4">

<div class="min-h-screen flex items-center justify-center w-90">
  <div class="flex flex-col md:flex-row bg-white rounded-3xl shadow-2xl w-full max-w-5xl overflow-hidden">

    <!-- Left Form -->
    <div class="p-7 flex flex-col justify-center relative">
      <i class="fas fa-lock-open text-green-700 text-center flex items-center justify-center"></i> <br>
      <h2 class="text-xl font-bold mb-6 text-green-700 text-center flex items-center justify-center">
      Forgot Password?
      </h2>

      <?php if (!empty($success)): ?>
        <p class="mb-6 text-green-800 bg-green-100 p-3 rounded-md border border-green-200 text-center font-medium"><?php echo htmlspecialchars($success); ?></p>
      <?php elseif (!empty($error)): ?>
        <p class="mb-6 text-red-700 bg-red-100 p-3 rounded-md border border-red-200 text-center font-medium"><?php echo htmlspecialchars($error); ?></p>
      <?php endif; ?>

      <form method="POST" action="forgot_password.php" class="space-y-6">
        <div class="relative">
          <label for="email" class="block mb-2 text-sm font-medium text-gray-700"><br>Email Address</label>
          <input
            type="email"
            id="email"
            name="email"
            required
            class=" mt-1 w-80 bg-gray-50 border border-gray-300 focus:border-green-400 focus:ring focus:ring-green-600 p-3 rounded-xl outline-none"
            placeholder="myemail@gmail.com"
          />
        </div>

        <button
          type="submit"
          class="w-full py-3 bg-gradient-to-r from-green-900 to-green-500 text-white rounded-xl font-semibold shadow-lg hover:from-green-700 hover:to-green-600 transition-all"
        >
          Send Reset Link
        </button>
      </form>

      <div class="mt-6 text-center">
        <a href="login.php" class="inline-block text-green-700 hover:text-green-900 hover:underline font-small transition-colors duration-300">
           Back to Login
        </a>
      </div>
    </div>
  </div>
</div>

</body>
</html>
