<?php
// chat/check_usage.php
include("../components/db.php");

$today = date('Y-m-d');
$result = mysqli_query($conn, "
    SELECT COUNT(*) as count, SUM(tokens_estimated) as tokens 
    FROM ai_usage_log 
    WHERE source='gemini' AND DATE(created_at) = '$today'
");

$usage = mysqli_fetch_assoc($result);

if ($usage['count'] > 1000) { // Alert if over 1000 calls
    mail('admin@power2connect.com', 'AI Usage Alert', 
         "Today's Gemini usage: {$usage['count']} calls, {$usage['tokens']} tokens");
}
?>