<?php
session_start();
include("../components/db.php");

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$session_id = mysqli_real_escape_string($conn, $input['session_id'] ?? '');
$last_check = mysqli_real_escape_string($conn, $input['last_check'] ?? date('Y-m-d H:i:s', strtotime('-1 minute')));

// Get new staff messages
$query = "SELECT * FROM messages 
          WHERE session_id = '$session_id' 
          AND sender_type = 'staff' 
          AND created_at > '$last_check' 
          ORDER BY created_at ASC";
$result = mysqli_query($conn, $query);

$messages = [];
while ($row = mysqli_fetch_assoc($result)) {
    $messages[] = $row;
}

echo json_encode([
    'messages' => $messages,
    'last_check' => date('Y-m-d H:i:s')
]);

mysqli_close($conn);
?>