<?php
session_start();
include("../components/db.php");

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Keep only last 30 days of data
mysqli_query($conn, "DELETE FROM ai_usage_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");

header("Location: ai_usage_dashboard.php?msg=Old data cleared successfully");
?>