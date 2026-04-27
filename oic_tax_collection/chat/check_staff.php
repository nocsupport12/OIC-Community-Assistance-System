<?php
session_start();
include("../components/db.php");

$input = json_decode(file_get_contents('php://input'), true);
$session_id = mysqli_real_escape_string($conn, $input['session_id'] ?? '');

// Check if staff has joined this session
$query = "SELECT * FROM staff_chat_sessions WHERE session_id = '$session_id'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    $staff = mysqli_fetch_assoc($result);
    echo json_encode([
        'staff_joined' => true,
        'staff_name' => $staff['staff_name']
    ]);
} else {
    echo json_encode([
        'staff_joined' => false,
        'staff_name' => null
    ]);
}

mysqli_close($conn);
?>