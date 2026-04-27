<?php
// admin/get_conversation.php
session_start();
include("../components/db.php");

// Protect admin page
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    header('HTTP/1.0 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$request_id = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;
$session_id = isset($_GET['session_id']) ? mysqli_real_escape_string($conn, $_GET['session_id']) : '';

if (!$request_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
    exit;
}

// Get request details
$request_query = "SELECT * FROM call_requests WHERE id = $request_id";
$request_result = mysqli_query($conn, $request_query);
$request = mysqli_fetch_assoc($request_result);

if (!$request) {
    echo json_encode(['success' => false, 'message' => 'Request not found']);
    exit;
}

// Get chat messages if session_id exists
$messages = [];
if (!empty($session_id) && $session_id !== 'null' && $session_id !== '') {
    $messages_query = "SELECT * FROM messages WHERE session_id = '$session_id' ORDER BY created_at ASC";
    $messages_result = mysqli_query($conn, $messages_query);
    while ($msg = mysqli_fetch_assoc($messages_result)) {
        $messages[] = $msg;
    }
}

echo json_encode([
    'success' => true,
    'request' => $request,
    'messages' => $messages
]);

mysqli_close($conn);
?>