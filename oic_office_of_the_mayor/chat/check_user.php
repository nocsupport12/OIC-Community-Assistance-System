<?php
session_start();
include("../components/db.php");

$input = json_decode(file_get_contents('php://input'), true);
$mobile = mysqli_real_escape_string($conn, $input['mobile_number'] ?? '');

if (empty($mobile)) {
    echo json_encode(['exists' => false]);
    exit;
}

// Check if user exists
$user_query = "SELECT * FROM users WHERE mobile_number = '$mobile'";
$user_result = mysqli_query($conn, $user_query);

if (mysqli_num_rows($user_result) > 0) {
    $user = mysqli_fetch_assoc($user_result);
    
    // Get recent chat sessions
    $sessions_query = "SELECT * FROM chat_sessions WHERE user_id = {$user['id']} ORDER BY started_at DESC LIMIT 5";
    $sessions_result = mysqli_query($conn, $sessions_query);
    
    $chat_history = [];
    while ($session = mysqli_fetch_assoc($sessions_result)) {
        $chat_history[] = $session;
    }
    
    echo json_encode([
        'exists' => true,
        'user' => $user,
        'chat_history' => $chat_history
    ]);
} else {
    echo json_encode(['exists' => false]);
}

mysqli_close($conn);
?>