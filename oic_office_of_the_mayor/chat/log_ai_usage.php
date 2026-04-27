<?php
// chat/log_ai_usage.php
include("../components/db.php");

// Create usage table if not exists
$createTable = "CREATE TABLE IF NOT EXISTS ai_usage_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(50),
    user_message TEXT,
    ai_response TEXT,
    tokens_estimated INT,
    response_time FLOAT,
    source VARCHAR(20), -- 'gemini' or 'database' or 'local'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

mysqli_query($conn, $createTable);

function logAIUsage($session_id, $user_message, $ai_response, $source, $response_time) {
    global $conn;
    
    // Estimate tokens (rough estimate: 1 token ≈ 4 characters)
    $tokens = ceil(strlen($user_message . $ai_response) / 4);
    
    $sql = "INSERT INTO ai_usage_log (session_id, user_message, ai_response, tokens_estimated, response_time, source) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssdds", $session_id, $user_message, $ai_response, $tokens, $response_time, $source);
    mysqli_stmt_execute($stmt);
}
?>