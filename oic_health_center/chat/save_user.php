<?php
//save_user.php
session_start();
include("../components/db.php");

$input = json_decode(file_get_contents('php://input'), true);
$mobile = mysqli_real_escape_string($conn, $input['mobile_number'] ?? '');
$name = mysqli_real_escape_string($conn, $input['name'] ?? '');

if (empty($mobile)) {
    echo json_encode(['success' => false, 'message' => 'Mobile number required']);
    exit;
}

// Check if user exists
$check = mysqli_query($conn, "SELECT id FROM users WHERE mobile_number = '$mobile'");
if (mysqli_num_rows($check) > 0) {
    $user = mysqli_fetch_assoc($check);
    echo json_encode([
        'success' => true,
        'user_id' => $user['id'],
        'message' => 'User already exists'
    ]);
    exit;
}

// Insert new user
$insert = "INSERT INTO users (mobile_number, name) VALUES ('$mobile', '$name')";
if (mysqli_query($conn, $insert)) {
    $user_id = mysqli_insert_id($conn);
    echo json_encode([
        'success' => true,
        'user_id' => $user_id,
        'message' => 'User created successfully'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

mysqli_close($conn);
?>