<?php
//load_session.php
session_start();
include("../components/db.php");

$input = json_decode(file_get_contents('php://input'), true);
$session_id = mysqli_real_escape_string($conn, $input['session_id'] ?? '');

if (empty($session_id)) {
    echo json_encode(['success' => false, 'message' => 'Session ID required']);
    exit;
}

// Get session messages
$messages_query = "SELECT * FROM messages WHERE session_id = '$session_id' ORDER BY created_at ASC";
$messages_result = mysqli_query($conn, $messages_query);

$messages = [];
while ($msg = mysqli_fetch_assoc($messages_result)) {
    $messages[] = $msg;
}

// Check if staff has joined
$staff_query = "SELECT * FROM staff_chat_sessions WHERE session_id = '$session_id'";
$staff_result = mysqli_query($conn, $staff_query);
$staff_joined = mysqli_num_rows($staff_result) > 0;
$staff_name = null;

if ($staff_joined) {
    $staff = mysqli_fetch_assoc($staff_result);
    $staff_name = $staff['staff_name'];
}

echo json_encode([
    'success' => true,
    'messages' => $messages,
    'staff_joined' => $staff_joined,
    'staff_name' => $staff_name
]);

mysqli_close($conn);
?>