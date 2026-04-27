<?php
// chat/get_ticket_status.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database configuration - UPDATED with correct database name
$db_host = 'localhost';
$db_user = 'root'; // Keep as is if using XAMPP default
$db_pass = ''; // Keep as is if using XAMPP default
$db_name = 'oic_health_center'; // CHANGED from 'power2connect' to 'oic_health_center'

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid input data'
    ]);
    exit;
}

$ticketNumber = isset($input['ticket_number']) ? trim($input['ticket_number']) : '';
$phone = isset($input['phone']) ? trim($input['phone']) : '';

// Validate input
if (empty($ticketNumber) || empty($phone)) {
    echo json_encode([
        'success' => false,
        'message' => 'Ticket number and phone number are required'
    ]);
    exit;
}

// Remove # if present for database query
$ticketId = ltrim($ticketNumber, '#');

// Ensure ticket ID is numeric
if (!is_numeric($ticketId)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid ticket number format'
    ]);
    exit;
}

// Convert to integer
$ticketId = intval($ticketId);

// Prepare SQL to fetch ticket data from call_requests table
$sql = "SELECT 
            cr.id,
            cr.session_id,
            cr.mobile_number,
            cr.name,
            cr.reason,
            cr.contact_type,
            cr.status,
            cr.requested_at,
            cr.called_at,
            cr.notes,
            cr.assigned_staff_id
        FROM call_requests cr
        WHERE cr.id = ? AND cr.mobile_number = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $ticketId, $phone);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $ticket = $result->fetch_assoc();
    
    // Format status for display
    $statusDisplay = ucwords(str_replace('_', ' ', $ticket['status']));
    
    // Determine status color and emoji
    $statusEmoji = match($ticket['status']) {
        'pending' => '🟡',
        'in_progress' => '🔵',
        'completed' => '🟢',
        'cancelled' => '🔴',
        default => '⚪'
    };
    
    // Get assigned staff name if available
    $assignedTo = 'Not Assigned';
    if ($ticket['assigned_staff_id']) {
        $staffSql = "SELECT fname, lname FROM usr_tbl WHERE id = ?";
        $staffStmt = $conn->prepare($staffSql);
        $staffStmt->bind_param("i", $ticket['assigned_staff_id']);
        $staffStmt->execute();
        $staffResult = $staffStmt->get_result();
        if ($staffResult->num_rows > 0) {
            $staff = $staffResult->fetch_assoc();
            $assignedTo = trim($staff['fname'] . ' ' . $staff['lname']);
        }
        $staffStmt->close();
    }
    
    // Format the response
    $response = [
        'success' => true,
        'ticket' => [
            'ticket' => '#' . str_pad($ticket['id'], 3, '0', STR_PAD_LEFT),
            'name' => $ticket['name'] ?? 'Not provided',
            'phone' => $ticket['mobile_number'],
            'status' => $statusEmoji . ' ' . $statusDisplay,
            'status_raw' => $ticket['status'],
            'date' => date('M d, Y', strtotime($ticket['requested_at'])),
            'time' => date('h:i A', strtotime($ticket['requested_at'])),
            'updated' => $ticket['called_at'] ? date('M d, Y h:i A', strtotime($ticket['called_at'])) : 'Not updated',
            'dept' => $ticket['contact_type'] == 'call' ? 'Call Center' : 'Email Support',
            'assigned' => $assignedTo,
            'concern' => $ticket['reason'] ?? 'No concern specified',
            'message' => $ticket['notes'] ?? 'No remarks available'
        ]
    ];
    
    echo json_encode($response);
    
} else {
    // Check if ticket exists but phone doesn't match
    $checkSql = "SELECT id, mobile_number FROM call_requests WHERE id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $ticketId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $existing = $checkResult->fetch_assoc();
        echo json_encode([
            'success' => false,
            'message' => '❌ Phone number does not match our records for ticket #' . str_pad($ticketId, 3, '0', STR_PAD_LEFT),
            'ticket_exists' => true
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '❌ Ticket #' . str_pad($ticketId, 3, '0', STR_PAD_LEFT) . ' not found in our system.',
            'ticket_exists' => false
        ]);
    }
    $checkStmt->close();
}

$stmt->close();
$conn->close();
?>