<?php
// config.php - Use your existing power2connect database
session_start();

$host = 'localhost';
$username = 'root';      // Your MySQL username
$password = '';          // Your MySQL password
$database = 'power2connect';  // Your existing database name

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Function to sanitize input
function sanitize($input) {
    global $conn;
    return $conn->real_escape_string(trim($input));
}
?>