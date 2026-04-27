<?php
// chat/get_staff_messages.php
session_start();
include("../components/db.php");

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$session_id = mysqli_real_escape_string($conn, $input['session_id'] ?? '');
$last_check = mysqli_real_escape_string($conn, $input['last_check'] ?? date('Y-m-d H:i:s', strtotime('-1 minute')));

if (empty($session_id)) {
    echo json_encode(['success' => false, 'message' => 'Session ID required']);
    exit;
}

// Get new messages from staff OR admin (BOTH TYPES) with sender names
// FIXED: Join with staff_chat_sessions directly without the limiting subquery
$query = "SELECT m.*, s.staff_name 
          FROM messages m
          LEFT JOIN staff_chat_sessions s ON m.session_id = s.session_id AND s.staff_id = (
              SELECT staff_id FROM staff_chat_sessions 
              WHERE session_id = m.session_id AND staff_id IS NOT NULL 
              AND staff_id = (
                  CASE 
                      WHEN m.sender_type = 'admin' THEN 
                          (SELECT staff_id FROM staff_chat_sessions WHERE session_id = m.session_id AND staff_id IS NOT NULL ORDER BY joined_at ASC LIMIT 1)
                      ELSE
                          (SELECT staff_id FROM staff_chat_sessions WHERE session_id = m.session_id AND staff_id IS NOT NULL ORDER BY joined_at DESC LIMIT 1)
                  END
              )
              LIMIT 1
          )
          WHERE m.session_id = '$session_id' 
          AND (m.sender_type = 'staff' OR m.sender_type = 'admin')
          AND m.created_at > '$last_check' 
          ORDER BY m.created_at ASC";

// SIMPLER APPROACH - Use this instead of the complex query above
$query = "SELECT m.*, s.staff_name 
          FROM messages m
          LEFT JOIN staff_chat_sessions s ON m.session_id = s.session_id 
          WHERE m.session_id = '$session_id' 
          AND (m.sender_type = 'staff' OR m.sender_type = 'admin')
          AND m.created_at > '$last_check' 
          ORDER BY m.created_at ASC";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    exit;
}

$messages = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Add display info for each message
    if ($row['sender_type'] === 'staff') {
        $row['display_name'] =  'Staff';
        $row['display_role'] = 'Staff';
        $row['display_icon'] = '👨‍💼';
    } else if ($row['sender_type'] === 'admin') {
        $row['display_name'] =  'Admin';
        $row['display_role'] = 'Admin';
        $row['display_icon'] = '👨‍💼';
    }
    $messages[] = $row;
}

// Check if any staff OR admin has joined
$staff_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM staff_chat_sessions WHERE session_id = '$session_id'");
$staff_count = mysqli_fetch_assoc($staff_query)['count'];
$staff_joined = $staff_count > 0;

echo json_encode([
    'success' => true,
    'messages' => $messages,
    'staff_joined' => $staff_joined,
    'last_check' => date('Y-m-d H:i:s')
]);

mysqli_close($conn);
?>