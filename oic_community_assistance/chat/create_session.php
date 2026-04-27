<?php
session_start();
include("../components/db.php");

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$session_id = mysqli_real_escape_string($conn, $input['session_id'] ?? '');
$language = mysqli_real_escape_string($conn, $input['language'] ?? 'en');

if (empty($session_id)) {
    echo json_encode(['success' => false, 'message' => 'Session ID required']);
    exit;
}

// Check if session exists
$check = mysqli_query($conn, "SELECT id FROM chat_sessions WHERE session_id = '$session_id'");

if (mysqli_num_rows($check) == 0) {
    // Create new session
    $sql = "INSERT INTO chat_sessions (session_id, language, status) VALUES ('$session_id', '$language', 'active')";
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['success' => true, 'session_id' => $session_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => true, 'session_id' => $session_id]);
}

mysqli_close($conn);
?>