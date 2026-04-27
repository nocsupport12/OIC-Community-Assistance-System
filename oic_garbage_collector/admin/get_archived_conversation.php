<?php
// admin/get_archived_conversation.php
session_start();
include("../components/db.php");

// Protect admin page
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    header('HTTP/1.0 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$archive_id = isset($_GET['archive_id']) ? intval($_GET['archive_id']) : 0;
$session_id = isset($_GET['session_id']) ? mysqli_real_escape_string($conn, $_GET['session_id']) : '';

if (!$archive_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid archive ID']);
    exit;
}

// Get archived record
$archive_query = "SELECT * FROM archived_queries WHERE id = $archive_id";
$archive_result = mysqli_query($conn, $archive_query);

if (!$archive_result) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    exit;
}

$archive = mysqli_fetch_assoc($archive_result);

if (!$archive) {
    echo json_encode(['success' => false, 'message' => 'Archived record not found']);
    exit;
}

// Get chat messages if session_id exists
$messages = [];
if (!empty($session_id) && $session_id !== 'null' && $session_id !== '') {
    $messages_query = "SELECT * FROM messages WHERE session_id = '$session_id' ORDER BY created_at ASC";
    $messages_result = mysqli_query($conn, $messages_query);
    
    if ($messages_result) {
        while ($msg = mysqli_fetch_assoc($messages_result)) {
            $messages[] = $msg;
        }
    }
}

echo json_encode([
    'success' => true,
    'archive' => $archive,
    'messages' => $messages
]);

mysqli_close($conn);
?>