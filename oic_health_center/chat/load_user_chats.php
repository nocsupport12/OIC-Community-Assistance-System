<?php
//load_user_chats.php
session_start();
include("../components/db.php");

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$user_id = isset($input['user_id']) ? intval($input['user_id']) : 0;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit;
}

// Get user's recent messages from all sessions
$query = "SELECT m.*, s.session_id 
          FROM messages m 
          JOIN chat_sessions s ON m.session_id = s.session_id 
          WHERE s.user_id = $user_id 
          ORDER BY m.created_at DESC 
          LIMIT 50";
          
$result = mysqli_query($conn, $query);

$messages = [];
while ($row = mysqli_fetch_assoc($result)) {
    $messages[] = $row;
}

// Reverse to show in chronological order
$messages = array_reverse($messages);

echo json_encode([
    'success' => true,
    'messages' => $messages
]);

mysqli_close($conn);
?>