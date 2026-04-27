<?php
// staff/get_query_details.php
session_start();
include("../components/db.php");

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'staff') {
    http_response_code(401);
    exit('Unauthorized');
}

$request_id = intval($_GET['id'] ?? 0);

if (!$request_id) {
    http_response_code(400);
    exit('Invalid request ID');
}

// Get query details
$sql = "SELECT * FROM call_requests WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    exit('Request not found');
}

$row = $result->fetch_assoc();
$stmt->close();

// Get chat messages
$messages = [];
if (!empty($row['session_id'])) {
    $msg_query = "SELECT * FROM messages WHERE session_id = '{$row['session_id']}' ORDER BY created_at ASC";
    $msg_result = mysqli_query($conn, $msg_query);
    while ($msg = mysqli_fetch_assoc($msg_result)) {
        $messages[] = $msg;
    }
}
?>

<div class="space-y-4">
    <!-- Customer Information -->
    <div class="bg-gray-50 p-4 rounded-lg">
        <h4 class="font-semibold text-gray-700 mb-3">Customer Information</h4>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-500">Name</p>
                <p class="font-medium"><?php echo htmlspecialchars($row['name'] ?: 'Not provided'); ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Mobile Number</p>
                <p class="font-medium"><?php echo htmlspecialchars($row['mobile_number']); ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Email</p>
                <p class="font-medium"><?php echo htmlspecialchars($row['email'] ?: 'Not provided'); ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Contact Type</p>
                <p class="font-medium"><?php echo ucfirst($row['contact_type'] ?? 'Call'); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Request Details -->
    <div class="bg-gray-50 p-4 rounded-lg">
        <h4 class="font-semibold text-gray-700 mb-3">Request Details</h4>
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <p class="text-sm text-gray-500">Request ID</p>
                <p class="font-medium">#<?php echo str_pad($row['id'], 3, '0', STR_PAD_LEFT); ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Status</p>
                <p class="font-medium">
                    <span class="px-2 py-1 text-xs rounded-full 
                        <?php
                        if ($row['status'] == 'pending') echo 'bg-yellow-100 text-yellow-800';
                        elseif ($row['status'] == 'in_progress') echo 'bg-blue-100 text-blue-800';
                        elseif ($row['status'] == 'completed') echo 'bg-green-100 text-green-800';
                        else echo 'bg-gray-100 text-gray-800';
                        ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                    </span>
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Requested At</p>
                <p class="font-medium"><?php echo date('F d, Y h:i A', strtotime($row['requested_at'])); ?></p>
            </div>
            <?php if ($row['called_at']): ?>
            <div>
                <p class="text-sm text-gray-500">Called At</p>
                <p class="font-medium"><?php echo date('F d, Y h:i A', strtotime($row['called_at'])); ?></p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Reason -->
        <div class="mt-4">
            <p class="text-sm text-gray-500 mb-2">Reason for Callback</p>
            <div class="p-3 bg-white rounded border">
                <?php echo nl2br(htmlspecialchars($row['reason'] ?: 'No reason provided')); ?>
            </div>
        </div>
        
        <!-- Chat History -->
        <?php if (!empty($messages)): ?>
        <div class="mt-4">
            <p class="text-sm text-gray-500 mb-2">Chat History</p>
            <div class="bg-white rounded border p-3 max-h-60 overflow-y-auto">
                <?php foreach ($messages as $msg): ?>
                    <div class="mb-2 p-2 rounded <?php echo $msg['sender_type'] == 'user' ? 'bg-blue-100 text-right' : 'bg-gray-100'; ?>">
                        <div class="text-xs text-gray-500"><?php echo date('h:i A', strtotime($msg['created_at'])); ?></div>
                        <div><?php echo htmlspecialchars($msg['message']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Notes -->
        <?php if (!empty($row['notes'])): ?>
        <div class="mt-4">
            <p class="text-sm text-gray-500 mb-2">Staff Notes</p>
            <div class="p-3 bg-white rounded border">
                <?php echo nl2br(htmlspecialchars($row['notes'])); ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Actions -->
    <div class="flex justify-end space-x-3 pt-4 border-t">
        <button onclick="closeModal()" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
            Close
        </button>
        <?php if ($row['status'] == 'pending'): ?>
            <form method="POST" action="queries.php" class="inline">
                <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                <input type="hidden" name="status" value="in_progress">
                <input type="hidden" name="update_status" value="1">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    <i class="fas fa-phone mr-2"></i>Start Call
                </button>
            </form>
        <?php elseif ($row['status'] == 'in_progress'): ?>
            <form method="POST" action="queries.php" class="inline">
                <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                <input type="hidden" name="status" value="completed">
                <input type="hidden" name="update_status" value="1">
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    <i class="fas fa-check mr-2"></i>Mark Completed
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>