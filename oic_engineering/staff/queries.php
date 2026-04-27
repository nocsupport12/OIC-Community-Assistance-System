<?php
session_start();
include("../components/db.php");

// Protect staff page
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'staff') {
    header("Location: ../login.php");
    exit;
}

// Handle reset auto-increment
if (isset($_GET['reset_auto_increment']) && $_GET['reset_auto_increment'] == '1') {
    header('Content-Type: application/json');
    
    // Check if table is empty
    $check = mysqli_query($conn, "SELECT COUNT(*) as count FROM call_requests");
    $count = mysqli_fetch_assoc($check)['count'];
    
    if ($count == 0) {
        // Reset auto-increment to 1
        $reset = mysqli_query($conn, "ALTER TABLE call_requests AUTO_INCREMENT = 1");
        
        if ($reset) {
            echo json_encode(['success' => true, 'message' => 'ID counter reset to 1 successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to reset ID counter: ' . mysqli_error($conn)]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Cannot reset ID counter - table is not empty!']);
    }
    exit;
}

// Handle archive (move to admin archive instead of delete) with auto-reset
if (isset($_GET['archive'])) {
    $id = intval($_GET['archive']);
    $staff_id = $_SESSION['user_id'];
    
    // Return JSON response for SweetAlert
    header('Content-Type: application/json');
    
    // First get the record
    $get_record = mysqli_query($conn, "SELECT * FROM call_requests WHERE id = $id");
    $record = mysqli_fetch_assoc($get_record);
    
    if ($record) {
        // Insert into archive table
        $insert_archive = "INSERT INTO archived_queries 
            (original_id, session_id, name, mobile_number, email, reason, contact_type, status, requested_at, archived_by) 
            VALUES (
                {$record['id']}, 
                '{$record['session_id']}', 
                '{$record['name']}', 
                '{$record['mobile_number']}', 
                '{$record['email']}', 
                '{$record['reason']}', 
                '{$record['contact_type']}', 
                '{$record['status']}', 
                '{$record['requested_at']}', 
                $staff_id
            )";
        $archive_result = mysqli_query($conn, $insert_archive);
        
        if ($archive_result) {
            // Delete from active table
            mysqli_query($conn, "DELETE FROM call_requests WHERE id = $id");
            
            // Check if table is now empty
            $check_empty = mysqli_query($conn, "SELECT COUNT(*) as count FROM call_requests");
            $count = mysqli_fetch_assoc($check_empty)['count'];
            
            // If table is empty, reset auto-increment
            if ($count == 0) {
                mysqli_query($conn, "ALTER TABLE call_requests AUTO_INCREMENT = 1");
                echo json_encode(['success' => true, 'message' => 'Query moved to archive successfully! ID counter has been reset to 1.']);
            } else {
                echo json_encode(['success' => true, 'message' => 'Query moved to archive successfully!']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Archive failed: ' . mysqli_error($conn)]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Record not found!']);
    }
    exit;
}

// Handle call (mark as in_progress)
if (isset($_GET['call'])) {
    $id = intval($_GET['call']);
    header('Content-Type: application/json');
    
    $result = mysqli_query($conn, "UPDATE call_requests SET status = 'in_progress' WHERE id = $id");
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Call started! Status updated to In Progress.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed: ' . mysqli_error($conn)]);
    }
    exit;
}

// Handle complete
if (isset($_GET['complete'])) {
    $id = intval($_GET['complete']);
    header('Content-Type: application/json');
    
    $result = mysqli_query($conn, "UPDATE call_requests SET status = 'completed' WHERE id = $id");
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Request marked as completed!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed: ' . mysqli_error($conn)]);
    }
    exit;
}

// Handle staff sending a message
if (isset($_POST['send_staff_message'])) {
    $session_id = mysqli_real_escape_string($conn, $_POST['session_id']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    $staff_id = $_SESSION['user_id'];
    
    // Get staff name from database
    $name_query = mysqli_query($conn, "SELECT fname, lname FROM usr_tbl WHERE id = $staff_id");
    $name_data = mysqli_fetch_assoc($name_query);
    $staff_name = ($name_data['fname'] ?? '') . ' ' . ($name_data['lname'] ?? '');
    $staff_name = trim($staff_name) ?: 'Staff';
    
    // Insert staff message
    $insert = "INSERT INTO messages (session_id, sender_type, message, created_at) 
               VALUES ('$session_id', 'staff', '$message', NOW())";
    
    if (mysqli_query($conn, $insert)) {
        // Check if staff session exists
        $check = mysqli_query($conn, "SELECT id FROM staff_chat_sessions WHERE session_id = '$session_id'");
        if (mysqli_num_rows($check) == 0) {
            // Create staff session
            mysqli_query($conn, "INSERT INTO staff_chat_sessions (session_id, staff_id, staff_name) 
                                 VALUES ('$session_id', $staff_id, '$staff_name')");
        }
        
        // Return the new message data for real-time update
        echo json_encode([
            'success' => true, 
            'message' => 'Message sent',
            'new_message' => [
                'id' => mysqli_insert_id($conn),
                'sender_type' => 'staff',
                'message' => $message,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($conn)]);
    }
    exit;
}

// Handle checking for new messages (AJAX endpoint for real-time updates)
if (isset($_GET['check_new_messages'])) {
    $session_id = mysqli_real_escape_string($conn, $_GET['session_id']);
    $last_message_id = isset($_GET['last_message_id']) ? intval($_GET['last_message_id']) : 0;
    
    header('Content-Type: application/json');
    
    // Get messages with ID greater than the last received message
    $query = "SELECT * FROM messages 
              WHERE session_id = '$session_id' 
              AND id > $last_message_id 
              ORDER BY created_at ASC";
    $result = mysqli_query($conn, $query);
    
    $messages = [];
    $new_last_id = $last_message_id;
    
    while ($row = mysqli_fetch_assoc($result)) {
        $messages[] = $row;
        if ($row['id'] > $new_last_id) {
            $new_last_id = $row['id'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'last_message_id' => $new_last_id
    ]);
    exit;
}

include("../components/staff_nav.php");

// Get filter from URL
$filter = isset($_GET['filter']) ? mysqli_real_escape_string($conn, $_GET['filter']) : 'all';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Build query based on filters
$where = "1=1";
if ($filter != 'all') {
    $where .= " AND status = '$filter'";
}
if (!empty($search)) {
    $where .= " AND (name LIKE '%$search%' OR mobile_number LIKE '%$search%' OR email LIKE '%$search%' OR reason LIKE '%$search%')";
}

// Fetch all callback requests with filters
$query = "SELECT * FROM call_requests WHERE $where ORDER BY 
    CASE status 
        WHEN 'pending' THEN 1 
        WHEN 'in_progress' THEN 2 
        WHEN 'completed' THEN 3 
        ELSE 4 
    END, requested_at DESC";
$requests = mysqli_query($conn, $query);

// Get counts for stats
$counts_query = "SELECT 
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
    FROM call_requests";
$counts_result = mysqli_query($conn, $counts_query);
$counts = mysqli_fetch_assoc($counts_result);

// Check if table is empty for reset button visibility
$check_empty_query = "SELECT COUNT(*) as total FROM call_requests";
$check_empty_result = mysqli_query($conn, $check_empty_query);
$total_records = mysqli_fetch_assoc($check_empty_result)['total'];
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Customer Queries - Power2Connect</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
:root {
  --tw-primary: #2563eb;
  --tw-accent1: #60a5fa;
  --tw-accent2: #43991f;
  --tw-accent3: #fdd400;
}
.status-pending {
  background-color: #fef3c7;
  color: #92400e;
  padding: 4px 8px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  display: inline-block;
}
.status-progress {
  background-color: #dbeafe;
  color: #1e40af;
  padding: 4px 8px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  display: inline-block;
}
.status-completed {
  background-color: #d1fae5;
  color: #065f46;
  padding: 4px 8px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  display: inline-block;
}
.concern-cell {
  max-width: 250px;
  cursor: pointer;
  position: relative;
}
.concern-text {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-size: 0.9rem;
  color: #374151;
}
.concern-text:hover {
  color: #2563eb;
  text-decoration: underline;
}
.modal-overlay {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 1000;
  justify-content: center;
  align-items: center;
}
.modal-overlay.active {
  display: flex;
}
.modal-container {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 800px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}
.modal-header {
  background: linear-gradient(to right, #22c55e, #eab308);
  color: white;
  padding: 16px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-radius: 12px 12px 0 0;
  position: sticky;
  top: 0;
  z-index: 10;
}
.modal-body {
  padding: 20px;
  max-height: calc(90vh - 180px);
  overflow-y: auto;
}
.modal-footer {
  padding: 16px 20px;
  border-top: 1px solid #e5e7eb;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  position: sticky;
  bottom: 0;
  background: white;
  border-radius: 0 0 12px 12px;
}
.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
  margin-bottom: 20px;
  background: #f9fafb;
  padding: 16px;
  border-radius: 8px;
}
.info-label {
  font-size: 0.75rem;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.info-value {
  font-weight: 600;
  color: #1f2937;
}
.reason-box {
  background: #f9fafb;
  padding: 16px;
  border-radius: 8px;
  margin-bottom: 20px;
  border-left: 4px solid #22c55e;
}
.reason-text {
  white-space: pre-wrap;
  word-wrap: break-word;
  line-height: 1.6;
  max-height: 150px;
  overflow-y: auto;
  padding-right: 10px;
}
.chat-history {
  background: #f9fafb;
  padding: 16px;
  border-radius: 8px;
  margin-bottom: 20px;
}
.chat-messages {
  max-height: 250px;
  overflow-y: auto;
  padding: 10px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.message-user {
  background: #3b82f6;
  color: white;
  padding: 10px 14px;
  border-radius: 18px 18px 4px 18px;
  max-width: 80%;
  align-self: flex-end;
  word-wrap: break-word;
}
.message-ai {
  background: #e5e7eb;
  color: #1f2937;
  padding: 10px 14px;
  border-radius: 18px 18px 18px 4px;
  max-width: 80%;
  align-self: flex-start;
  word-wrap: break-word;
}
.message-staff {
  background: #fef3c7;
  color: #92400e;
  padding: 10px 14px;
  border-radius: 18px 18px 18px 4px;
  max-width: 80%;
  align-self: flex-start;
  word-wrap: break-word;
  border-left: 4px solid #f97316;
}
.message-time {
  font-size: 0.7rem;
  opacity: 0.7;
  margin-top: 4px;
}
.close-btn {
  background: none;
  border: none;
  color: white;
  font-size: 1.5rem;
  cursor: pointer;
  padding: 0 5px;
}
.close-btn:hover {
  opacity: 0.8;
}
.action-btn {
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: all 0.2s;
  cursor: pointer;
  border: none;
}
.action-btn i {
  font-size: 0.875rem;
}
.refresh-btn {
  background-color: #22c55e;
  color: white;
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 0.875rem;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  border: none;
  transition: all 0.2s;
  margin-left: 10px;
}
.refresh-btn:hover {
  background-color: #16a34a;
}
.refresh-btn i {
  font-size: 0.875rem;
}
.reset-counter-btn {
  background-color: #f97316;
  color: white;
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 0.875rem;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  border: none;
  transition: all 0.2s;
  margin-left: 10px;
}
.reset-counter-btn:hover {
  background-color: #ea580c;
}
.reset-counter-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.chat-input-area {
  display: flex;
  gap: 10px;
  margin-top: 15px;
  padding: 10px;
  background: #f9fafb;
  border-radius: 8px;
}
.chat-input-field {
  flex: 1;
  padding: 10px 15px;
  border: 1px solid #e5e7eb;
  border-radius: 20px;
  outline: none;
  font-size: 14px;
}
.chat-input-field:focus {
  border-color: #22c55e;
  box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.2);
}
.send-chat-btn {
  background: #22c55e;
  color: white;
  border: none;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}
.send-chat-btn:hover {
  background: #16a34a;
  transform: scale(1.05);
}
.send-chat-btn i {
  font-size: 16px;
}
</style>
</head>
<body class="bg-gray-50 text-gray-800 font-sans">

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="text-xl font-bold" id="modalTitle">Conversation Details</h3>
            <button onclick="closeModal()" class="close-btn">&times;</button>
        </div>
        <div class="modal-body" id="modalBody">
            <!-- Content will be loaded dynamically -->
        </div>
        <div class="modal-footer" id="modalFooter">
            <!-- Footer will be loaded dynamically -->
        </div>
    </div>
</div>

<main id="dashboard" class="flex-1 p-6 lg:p-8">
  <div class="bg-white p-6 rounded-xl shadow-lg overflow-x-auto">
    <div class="flex justify-between items-center mb-6">
      <div class="flex items-center gap-4">
        <h1 class="text-2xl font-bold text-green-600">List Of Queries</h1>
      </div>
      
      <!-- Stats Summary with Reset Button -->
      <div class="flex gap-4 text-sm items-center">
        <!-- Manual Refresh Button -->
        <button onclick="location.reload()" class="refresh-btn">
          <i class="fas fa-sync-alt"></i>
          Refresh
        </button>
        
        <!-- Reset ID Counter Button (only enabled when table is empty) 
        <button onclick="confirmResetCounter()" class="reset-counter-btn" id="resetCounterBtn" <?php echo $total_records > 0 ? 'disabled' : ''; ?>>
          <i class="fas fa-sync"></i>
          Reset ID Counter
        </button> -->
        
        <span class="text-yellow-600 font-semibold bg-yellow-50 px-3 py-1 rounded-full flex items-center">
          <i class="fas fa-clock mr-1"></i>Pending: <?php echo $counts['pending'] ?? 0; ?>
        </span>
        <span class="text-blue-600 font-semibold bg-blue-50 px-3 py-1 rounded-full flex items-center">
          <i class="fas fa-spinner mr-1"></i>In Progress: <?php echo $counts['in_progress'] ?? 0; ?>
        </span>
        <span class="text-green-600 font-semibold bg-green-50 px-3 py-1 rounded-full flex items-center">
          <i class="fas fa-check-circle mr-1"></i>Completed: <?php echo $counts['completed'] ?? 0; ?>
        </span>
      </div>
    </div>
    
    <!-- Filter and Search Bar -->
    <div class="mb-6 flex flex-col md:flex-row gap-4 justify-between">
      <div class="flex gap-2">
        <a href="queries.php?filter=all" class="px-4 py-2 rounded-lg transition-colors <?php echo $filter == 'all' ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">All</a>
        <a href="queries.php?filter=pending" class="px-4 py-2 rounded-lg transition-colors <?php echo $filter == 'pending' ? 'bg-yellow-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">Pending</a>
        <a href="queries.php?filter=in_progress" class="px-4 py-2 rounded-lg transition-colors <?php echo $filter == 'in_progress' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">In Progress</a>
        <a href="queries.php?filter=completed" class="px-4 py-2 rounded-lg transition-colors <?php echo $filter == 'completed' ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">Completed</a>
      </div>
      
      <form method="GET" class="flex gap-2">
        <input type="hidden" name="filter" value="<?php echo $filter; ?>">
        <div class="relative">
          <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
          <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                 placeholder="Search by name, number, or concern..." 
                 class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent w-64">
        </div>
        
        <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-colors">
          <i class="fas fa-search mr-2"></i>Search
        </button>
       
        <?php if (!empty($search)): ?>
          <a href="queries.php?filter=<?php echo $filter; ?>" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors">
            Clear
          </a>
        <?php endif; ?>
      </form>
    </div>
    
    <!-- Queries Table -->
    <table class="w-full border-collapse text-sm">
      <thead>
        <tr class="bg-gradient-to-r from-green-500 to-yellow-400 text-white">
          <th class="p-3 text-center w-16">ID</th>
          <th class="p-3 text-center w-48">Name</th>
          <th class="p-3 text-center w-36">Number</th>
          <th class="p-3 text-center">Concern</th>
          <th class="p-3 text-center w-24">Status</th>
          <th class="p-3 text-center w-28">Date</th>
          <th class="p-3 text-center w-64">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (mysqli_num_rows($requests) > 0): ?>
          <?php while($row = mysqli_fetch_assoc($requests)): ?>
          <tr class="border-b hover:bg-gray-50" id="row-<?php echo $row['id']; ?>">
            <td class="p-3 text-center font-medium">#<?php echo str_pad($row['id'], 3, '0', STR_PAD_LEFT); ?></td>
            <td class="p-3">
              <div class="font-medium"><?php echo htmlspecialchars($row['name'] ?: 'Unknown'); ?></div>
              <?php if (!empty($row['email'])): ?>
                <div class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($row['email']); ?></div>
              <?php endif; ?>
            </td>
            <td class="p-3">
              <div class="font-mono"><?php echo htmlspecialchars($row['mobile_number']); ?></div>
            </td>
            <td class="p-3">
              <div class="concern-cell" onclick='openModal(<?php echo $row['id']; ?>, "<?php echo $row['session_id']; ?>")'>
                <div class="concern-text">
                  <?php echo htmlspecialchars(substr($row['reason'] ?: 'No reason provided', 0, 50)) . (strlen($row['reason'] ?: '') > 50 ? '...' : ''); ?>
                </div>
                <div class="text-xs text-blue-500 mt-1">
                  <i class="fas fa-expand-alt mr-1"></i>Click to view full conversation
                </div>
              </div>
            </td>
            <td class="p-3">
              <?php
              $status_class = '';
              $status_text = '';
              if ($row['status'] == 'pending') {
                $status_class = 'status-pending';
                $status_text = 'Pending';
              } elseif ($row['status'] == 'in_progress') {
                $status_class = 'status-progress';
                $status_text = 'In Progress';
              } elseif ($row['status'] == 'completed') {
                $status_class = 'status-completed';
                $status_text = 'Completed';
              }
              ?>
              <span class="<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
            </td>
            <td class="p-3 text-sm text-gray-500">
              <?php echo date('M d, Y', strtotime($row['requested_at'])); ?>
            </td>
            <td class="p-3">
              <div class="flex gap-2 justify-center flex-wrap">
                <?php if ($row['status'] == 'pending'): ?>
                  <button onclick="confirmAction('call', <?php echo $row['id']; ?>)" 
                          class="action-btn bg-green-600 text-white hover:bg-green-700">
                    <i class="fas fa-phone"></i>Call
                  </button>
                <?php elseif ($row['status'] == 'in_progress'): ?>
                  <button onclick="confirmAction('complete', <?php echo $row['id']; ?>)" 
                          class="action-btn bg-blue-600 text-white hover:bg-blue-700">
                    <i class="fas fa-check"></i>Done
                  </button>
                <?php else: ?>
                  <span class="action-btn bg-gray-100 text-gray-400 cursor-not-allowed">
                    <i class="fas fa-check-circle"></i>Done
                  </span>
                <?php endif; ?>
                
                <!-- Archive Button -->
                <button onclick="confirmAction('archive', <?php echo $row['id']; ?>)" 
                        class="action-btn bg-orange-500 text-white hover:bg-orange-600">
                  <i class="fas fa-archive"></i>Archive
                </button>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" class="p-8 text-center text-gray-500">
              <i class="fas fa-inbox text-4xl mb-3 text-gray-300"></i>
              <p class="text-lg">No queries found</p>
              <p class="text-sm">No callback requests match your criteria</p>
              <?php if ($total_records == 0): ?>
               <!-- <p class="text-sm text-orange-500 mt-2">The table is empty. You can reset the ID counter to start from 1.</p> -->
              <?php endif; ?>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
    
    <!-- Table Footer -->
    <div class="mt-4 text-sm text-gray-500 flex justify-between items-center">
      <span>Showing <?php echo mysqli_num_rows($requests); ?> entries</span>
      <span>Total Queries: <?php echo $total_records; ?></span>
    </div>
  </div>
</main>

<script>
let currentSessionId = null;
let currentRequestId = null;
let messageCheckInterval = null;
let lastMessageId = 0;
let displayedMessageIds = new Set(); // Track displayed message IDs to prevent duplicates

// Function to confirm actions with SweetAlert
function confirmAction(action, id) {
    let config = {
        title: '',
        text: '',
        icon: 'question',
        confirmButtonColor: '',
        actionUrl: ''
    };
    
    switch(action) {
        case 'call':
            config = {
                title: 'Start Call?',
                text: 'Mark this query as In Progress?',
                icon: 'question',
                confirmButtonColor: '#22c55e',
                actionUrl: `?call=${id}`
            };
            break;
        case 'complete':
            config = {
                title: 'Mark as Completed?',
                text: 'This query will be marked as completed.',
                icon: 'question',
                confirmButtonColor: '#22c55e',
                actionUrl: `?complete=${id}`
            };
            break;
        case 'archive':
            config = {
                title: 'Archive Query?',
                text: 'This query will be moved to admin archive. It can be restored later.',
                icon: 'warning',
                confirmButtonColor: '#f97316',
                actionUrl: `?archive=${id}`
            };
            break;
    }
    
    Swal.fire({
        title: config.title,
        text: config.text,
        icon: config.icon,
        showCancelButton: true,
        confirmButtonColor: config.confirmButtonColor,
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, proceed!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Processing...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Send AJAX request
            fetch(config.actionUrl)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: data.message,
                            confirmButtonColor: '#22c55e'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: data.message,
                            confirmButtonColor: '#ef4444'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'An error occurred. Please try again.',
                        confirmButtonColor: '#ef4444'
                    });
                });
        }
    });
}

// Function to reset ID counter
function confirmResetCounter() {
    // Check if table is empty
    const rowCount = <?php echo $total_records; ?>;
    
    if (rowCount > 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Cannot Reset Counter',
            text: 'The table must be empty before resetting the ID counter. Please archive all records first.',
            confirmButtonColor: '#f97316'
        });
        return;
    }
    
    Swal.fire({
        title: 'Reset ID Counter?',
        text: 'This will reset the auto-increment counter to 1 for new records. New queries will start from ID #001.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f97316',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, reset it!'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Processing...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch('?reset_auto_increment=1')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: data.message,
                            confirmButtonColor: '#22c55e'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: data.message,
                            confirmButtonColor: '#ef4444'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'An error occurred. Please try again.',
                        confirmButtonColor: '#ef4444'
                    });
                });
        }
    });
}

// Function to open modal and fetch conversation details
function openModal(requestId, sessionId) {
    currentRequestId = requestId;
    currentSessionId = sessionId;
    lastMessageId = 0;
    displayedMessageIds.clear(); // Clear the set of displayed messages
    
    // Show loading in modal
    document.getElementById('modalBody').innerHTML = `
        <div class="flex justify-center items-center py-12">
            <i class="fas fa-spinner fa-spin text-3xl text-green-600 mr-3"></i>
            <p class="text-gray-600">Loading conversation...</p>
        </div>
    `;
    document.getElementById('modalTitle').textContent = 'Loading...';
    document.getElementById('modalOverlay').classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // Fetch conversation details
    fetch(`get_conversation.php?request_id=${requestId}&session_id=${sessionId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                displayConversation(data);
                // Start checking for new messages
                startMessageCheck();
            } else {
                document.getElementById('modalBody').innerHTML = `
                    <div class="text-center py-12 text-red-600">
                        <i class="fas fa-exclamation-circle text-4xl mb-3"></i>
                        <p>Error loading conversation: ${data.message}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = `
                <div class="text-center py-12 text-red-600">
                    <i class="fas fa-exclamation-circle text-4xl mb-3"></i>
                    <p>Error loading conversation. Please try again.</p>
                    <p class="text-sm mt-2">${error.message}</p>
                </div>
            `;
        });
}

// Start checking for new messages
function startMessageCheck() {
    if (messageCheckInterval) {
        clearInterval(messageCheckInterval);
    }
    
    // Check for new messages every 2 seconds
    messageCheckInterval = setInterval(checkForNewMessages, 2000);
}

// Check for new messages
function checkForNewMessages() {
    if (!currentSessionId) return;
    
    fetch(`queries.php?check_new_messages=1&session_id=${currentSessionId}&last_message_id=${lastMessageId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.messages.length > 0) {
                // Update last message ID
                lastMessageId = data.last_message_id;
                
                // Add new messages to chat (only if not already displayed)
                const chatMessages = document.getElementById('chatMessages');
                data.messages.forEach(msg => {
                    // Check if message already displayed using ID
                    if (!displayedMessageIds.has(msg.id)) {
                        const time = new Date(msg.created_at).toLocaleString();
                        let msgDiv = document.createElement('div');
                        
                        if (msg.sender_type === 'user') {
                            msgDiv.className = 'message-user';
                            msgDiv.innerHTML = `
                                <div>${escapeHtml(msg.message)}</div>
                                <div class="message-time">${time}</div>
                            `;
                        } else if (msg.sender_type === 'ai') {
                            msgDiv.className = 'message-ai';
                            msgDiv.innerHTML = `
                                <div>${escapeHtml(msg.message)}</div>
                                <div class="message-time">${time}</div>
                            `;
                        } else if (msg.sender_type === 'staff') {
                            msgDiv.className = 'message-staff';
                            msgDiv.innerHTML = `
                                <div><strong>Staff:</strong> ${escapeHtml(msg.message)}</div>
                                <div class="message-time">${time}</div>
                            `;
                        }
                        
                        chatMessages.appendChild(msgDiv);
                        displayedMessageIds.add(msg.id); // Mark as displayed
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    }
                });
            }
        })
        .catch(error => console.error('Error checking messages:', error));
}

function displayConversation(data) {
    const request = data.request;
    const messages = data.messages || [];
    
    // Clear displayed message IDs and add existing messages
    displayedMessageIds.clear();
    messages.forEach(msg => displayedMessageIds.add(msg.id));
    
    // Set last message ID
    if (messages.length > 0) {
        lastMessageId = messages[messages.length - 1].id;
    }
    
    let infoHtml = `
        <div class="info-grid">
            <div>
                <div class="info-label">Name</div>
                <div class="info-value">${escapeHtml(request.name || 'Unknown')}</div>
            </div>
            <div>
                <div class="info-label">Mobile Number</div>
                <div class="info-value">${escapeHtml(request.mobile_number || 'N/A')}</div>
            </div>
            <div>
                <div class="info-label">Email</div>
                <div class="info-value">${escapeHtml(request.email || 'Not provided')}</div>
            </div>
            <div>
                <div class="info-label">Status</div>
                <div class="info-value">
                    <span class="${request.status === 'pending' ? 'status-pending' : request.status === 'in_progress' ? 'status-progress' : 'status-completed'}">
                        ${request.status.replace('_', ' ').toUpperCase()}
                    </span>
                </div>
            </div>
            <div>
                <div class="info-label">Requested Date</div>
                <div class="info-value">${new Date(request.requested_at).toLocaleString()}</div>
            </div>
            <div>
                <div class="info-label">Contact Type</div>
                <div class="info-value">${request.contact_type === 'call' ? '📞 Phone Call' : '📧 Email'}</div>
            </div>
        </div>
    `;
    
    let reasonHtml = `
        <div class="reason-box">
            <h4 class="font-semibold text-gray-700 mb-2 flex items-center">
                <i class="fas fa-comment-dots text-green-500 mr-2"></i>
                Concern / Reason
            </h4>
            <div class="reason-text">${escapeHtml(request.reason || 'No reason provided')}</div>
        </div>
    `;
    
    let chatHtml = `
        <div class="chat-history">
            <h4 class="font-semibold text-gray-700 mb-3 flex items-center">
                <i class="fas fa-history text-purple-500 mr-2"></i>
                Chat History (${messages.length} messages)
            </h4>
            <div class="chat-messages" id="chatMessages">
    `;
    
    if (messages.length > 0) {
        messages.forEach(msg => {
            const time = new Date(msg.created_at).toLocaleString();
            if (msg.sender_type === 'user') {
                chatHtml += `
                    <div class="message-user">
                        <div>${escapeHtml(msg.message)}</div>
                        <div class="message-time">${time}</div>
                    </div>
                `;
            } else if (msg.sender_type === 'ai') {
                chatHtml += `
                    <div class="message-ai">
                        <div>${escapeHtml(msg.message)}</div>
                        <div class="message-time">${time}</div>
                    </div>
                `;
            } else if (msg.sender_type === 'staff') {
                chatHtml += `
                    <div class="message-staff">
                        <div><strong>Staff:</strong> ${escapeHtml(msg.message)}</div>
                        <div class="message-time">${time}</div>
                    </div>
                `;
            }
        });
    } else {
        chatHtml += `
            <div class="text-center text-gray-500 py-4">
                <i class="fas fa-comment-slash text-3xl mb-2 text-gray-300"></i>
                <p>No chat history available for this session</p>
            </div>
        `;
    }
    
    chatHtml += `
            </div>
        </div>
    `;
    
    // Add chat input for staff to reply
    let chatInputHtml = `
       
    `;
    
    let footerHtml = `
        <div class="modal-footer" style="justify-content: space-between;">
            <div>
                <button onclick="closeModal()" class="action-btn bg-gray-500 text-white hover:bg-gray-600">
                    Close
                </button>
            </div>
            <div class="flex gap-2">
                <a href="tel:${escapeHtml(request.mobile_number)}" class="action-btn bg-green-600 text-white hover:bg-green-700">
                    <i class="fas fa-phone mr-2"></i>Call
                </a>
                ${request.status !== 'completed' ? 
                    `<button onclick="markAsCompleted(${request.id})" class="action-btn bg-blue-600 text-white hover:bg-blue-700">
                        <i class="fas fa-check mr-2"></i>Mark Complete
                    </button>` : ''}
            </div>
        </div>
    `;
    
    const modalContent = infoHtml + reasonHtml + chatHtml + chatInputHtml;
    
    document.getElementById('modalTitle').textContent = `Conversation #${String(request.id).padStart(3, '0')} - ${escapeHtml(request.name || 'Unknown')}`;
    document.getElementById('modalBody').innerHTML = modalContent;
    document.getElementById('modalFooter').innerHTML = footerHtml;
}

function handleChatKeyPress(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        sendStaffMessage();
    }
}

function sendStaffMessage() {
    const messageInput = document.getElementById('staffMessageInput');
    const message = messageInput.value.trim();
    
    if (!message) return;
    
    if (!currentSessionId) {
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'No active session found'
        });
        return;
    }
    
    // Clear input
    messageInput.value = '';
    
    // Create form data
    const formData = new FormData();
    formData.append('send_staff_message', '1');
    formData.append('session_id', currentSessionId);
    formData.append('message', message);
    
    // Send message
    fetch('queries.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Add message to chat immediately with a temporary ID
            const chatMessages = document.getElementById('chatMessages');
            const time = new Date().toLocaleString();
            const tempId = 'temp_' + Date.now();
            
            // Add to displayed messages set
            displayedMessageIds.add(tempId);
            
            const msgDiv = document.createElement('div');
            msgDiv.className = 'message-staff';
            msgDiv.setAttribute('data-message-id', tempId);
            msgDiv.innerHTML = `
                <div><strong>Staff:</strong> ${escapeHtml(message)}</div>
                <div class="message-time">${time}</div>
            `;
            chatMessages.appendChild(msgDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: data.message || 'Failed to send message'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'An error occurred. Please try again.'
        });
    });
}

function markAsCompleted(requestId) {
    Swal.fire({
        title: 'Mark as Completed?',
        text: 'This query will be marked as completed.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#22c55e',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, complete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`?complete=${requestId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: data.message
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: data.message
                        });
                    }
                });
        }
    });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function closeModal() {
    document.getElementById('modalOverlay').classList.remove('active');
    document.body.style.overflow = 'auto';
    currentSessionId = null;
    currentRequestId = null;
    displayedMessageIds.clear();
    
    // Stop checking for messages
    if (messageCheckInterval) {
        clearInterval(messageCheckInterval);
        messageCheckInterval = null;
    }
}

document.getElementById('modalOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

</body>
</html>
<?php mysqli_close($conn); ?>