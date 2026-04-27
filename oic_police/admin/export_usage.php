<?php
session_start();
include("../components/db.php");

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../login.php");
    exit;
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="ai_usage_export.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Session ID', 'User Message', 'AI Response', 'Source', 'Tokens', 'Response Time', 'Created At']);

$result = mysqli_query($conn, "SELECT * FROM ai_usage_log ORDER BY created_at DESC");
while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [
        $row['id'],
        $row['session_id'],
        $row['user_message'],
        $row['ai_response'],
        $row['source'],
        $row['tokens_estimated'],
        $row['response_time'],
        $row['created_at']
    ]);
}
fclose($output);
?>