<?php
//save_message.php
session_start();
include("../components/db.php");

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$session_id = mysqli_real_escape_string($conn, $input['session_id'] ?? '');
$sender_type = mysqli_real_escape_string($conn, $input['sender_type'] ?? '');
$message = mysqli_real_escape_string($conn, $input['message'] ?? '');

if (empty($session_id) || empty($sender_type) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Insert message
$sql = "INSERT INTO messages (session_id, sender_type, message, created_at) 
        VALUES ('$session_id', '$sender_type', '$message', NOW())";

if (mysqli_query($conn, $sql)) {
    echo json_encode(['success' => true, 'message' => 'Message saved']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>