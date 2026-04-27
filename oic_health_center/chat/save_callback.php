<?php
//save_callback.php
session_start();
include("../components/db.php");

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$session_id = mysqli_real_escape_string($conn, $input['session_id'] ?? '');
$phone = mysqli_real_escape_string($conn, $input['phone'] ?? '');
$reason = mysqli_real_escape_string($conn, $input['reason'] ?? 'Customer requested staff assistance');
$name = mysqli_real_escape_string($conn, $input['name'] ?? '');

if (empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Phone number required']);
    exit;
}

// Insert into call_requests
$sql = "INSERT INTO call_requests (session_id, name, mobile_number, reason, status, requested_at) 
        VALUES ('$session_id', '$name', '$phone', '$reason', 'pending', NOW())";

if (mysqli_query($conn, $sql)) {
    echo json_encode([
        'success' => true,
        'message' => 'Callback request saved',
        'request_id' => mysqli_insert_id($conn)
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>