<?php
// admin/queries.php
session_start();
include("../components/db.php");

// Protect admin page
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// ============ ALL HANDLERS ============

// Handle delete request (admin can permanently delete)
if (isset($_GET['permanent_delete'])) {
    $id = intval($_GET['permanent_delete']);
    $result = mysqli_query($conn, "DELETE FROM call_requests WHERE id = $id");
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Query permanently deleted!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Delete failed: ' . mysqli_error($conn)]);
    }
    exit;
}

// Handle archive (move to archive)
if (isset($_GET['archive'])) {
    $id = intval($_GET['archive']);
    $admin_id = $_SESSION['user_id'];
    
    $get_record = mysqli_query($conn, "SELECT * FROM call_requests WHERE id = $id");
    $record = mysqli_fetch_assoc($get_record);
    
    if ($record) {
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
                $admin_id
            )";
        $archive_result = mysqli_query($conn, $insert_archive);
        
        if ($archive_result) {
            mysqli_query($conn, "DELETE FROM call_requests WHERE id = $id");
            echo json_encode(['success' => true, 'message' => 'Query moved to archive successfully!']);
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
    $result = mysqli_query($conn, "UPDATE call_requests SET status = 'completed' WHERE id = $id");
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Request marked as completed!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed: ' . mysqli_error($conn)]);
    }
    exit;
}

// ============ ADMIN MESSAGE HANDLER (ADMIN SPECIFIC - SENDS AS 'admin') ============
if (isset($_POST['send_admin_message'])) {
    $session_id = mysqli_real_escape_string($conn, $_POST['session_id']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    $admin_id = $_SESSION['user_id'];
    
    // Get admin name
    $name_query = mysqli_query($conn, "SELECT fname, lname FROM usr_tbl WHERE id = $admin_id");
    $name_data = mysqli_fetch_assoc($name_query);
    $admin_name = trim(($name_data['fname'] ?? '') . ' ' . ($name_data['lname'] ?? '')) ?: 'Admin';
    
    // Insert as 'admin' sender_type WITH staff_id
    $insert = "INSERT INTO messages (session_id, sender_type, staff_id, message, created_at) 
               VALUES ('$session_id', 'admin', $admin_id, '$message', NOW())";
    
    if (mysqli_query($conn, $insert)) {
        // Track admin session
        $check = mysqli_query($conn, "SELECT id FROM staff_chat_sessions WHERE session_id = '$session_id' AND staff_id = $admin_id");
        if (mysqli_num_rows($check) == 0) {
            mysqli_query($conn, "INSERT INTO staff_chat_sessions (session_id, staff_id, staff_name) 
                                 VALUES ('$session_id', $admin_id, '$admin_name')");
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Message sent',
            'new_message' => [
                'id' => mysqli_insert_id($conn),
                'sender_type' => 'admin',
                'sender_name' => $admin_name,
                'staff_id' => $admin_id,
                'message' => $message,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($conn)]);
    }
    exit;
}

// ============ CHECK NEW MESSAGES (UPDATED WITH ERROR HANDLING) ============
if (isset($_GET['check_new_messages'])) {
    $session_id = mysqli_real_escape_string($conn, $_GET['session_id']);
    $last_message_id = isset($_GET['last_message_id']) ? intval($_GET['last_message_id']) : 0;
    
    header('Content-Type: application/json');
    
    // Validate session_id
    if (empty($session_id)) {
        echo json_encode([
            'success' => false,
            'message' => 'Session ID required'
        ]);
        exit;
    }
    
    // Get messages with sender info using staff_id
    $query = "SELECT m.*, s.staff_name 
              FROM messages m
              LEFT JOIN staff_chat_sessions s ON m.staff_id = s.staff_id AND m.session_id = s.session_id
              WHERE m.session_id = '$session_id' 
              AND m.id > $last_message_id 
              ORDER BY m.created_at ASC";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . mysqli_error($conn)
        ]);
        exit;
    }
    
    $messages = [];
    $new_last_id = $last_message_id;
    
    while ($row = mysqli_fetch_assoc($result)) {
        // Add sender name for staff/admin
        if ($row['sender_type'] === 'staff' || $row['sender_type'] === 'admin') {
            $row['sender_name'] = $row['staff_name'] ?? ucfirst($row['sender_type']);
        }
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

// ============ FILTERS AND QUERIES ============
$filter = isset($_GET['filter']) ? mysqli_real_escape_string($conn, $_GET['filter']) : 'all';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$where = "1=1";
if ($filter != 'all') {
    $where .= " AND status = '$filter'";
}
if (!empty($search)) {
    $where .= " AND (name LIKE '%$search%' OR mobile_number LIKE '%$search%' OR email LIKE '%$search%' OR reason LIKE '%$search%')";
}

$query = "SELECT * FROM call_requests WHERE $where ORDER BY 
    CASE status 
        WHEN 'pending' THEN 1 
        WHEN 'in_progress' THEN 2 
        WHEN 'completed' THEN 3 
        ELSE 4 
    END, requested_at DESC";
$requests = mysqli_query($conn, $query);

$counts_query = "SELECT 
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
    FROM call_requests";
$counts_result = mysqli_query($conn, $counts_query);
$counts = mysqli_fetch_assoc($counts_result);

include("../components/admin_nav.php");
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Admin Queries - Power2Connect</title>
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
  max-width: 700px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}
.modal-header {
  background: linear-gradient(to right, #2563eb, #60a5fa);
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
  border-left: 4px solid #2563eb;
}
.reason-text {
  white-space: pre-wrap;
  word-wrap: break-word;
  line-height: 1.6;
  max-height: 200px;
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
  max-height: 300px;
  overflow-y: auto;
  padding: 10px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

/* Message Styles - Distinct for each type */
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
.message-admin {
  background: #fee2e2;
  color: #991b1b;
  padding: 10px 14px;
  border-radius: 18px 18px 18px 4px;
  max-width: 80%;
  align-self: flex-start;
  word-wrap: break-word;
  border-left: 4px solid #dc2626;
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
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
  text-decoration: none;
  display: inline-block;
  width: 70px;
  text-align: center;
  transition: all 0.2s;
  cursor: pointer;
  border: none;
}
.action-buttons {
  display: flex;
  flex-direction: column;
  gap: 4px;
  align-items: center;
}
.refresh-btn {
  background-color: #3b82f6;
  color: white;
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 0.875rem;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  cursor: pointer;
  border: none;
  transition: all 0.2s;
}
.refresh-btn:hover {
  background-color: #2563eb;
}
.refresh-btn i {
  font-size: 0.875rem;
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
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}
.send-chat-btn {
  background: #3b82f6;
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
  background: #2563eb;
  transform: scale(1.05);
}
.send-chat-btn i {
  font-size: 16px;
}
.admin-badge {
  background: #dc2626;
  color: white;
  font-size: 0.7rem;
  padding: 2px 6px;
  border-radius: 10px;
  margin-left: 5px;
  display: inline-block;
}
.staff-badge {
  background: #f97316;
  color: white;
  font-size: 0.7rem;
  padding: 2px 6px;
  border-radius: 10px;
  margin-left: 5px;
  display: inline-block;
}
body {
  margin: 0;
  height: 100vh;
  background: linear-gradient(to right, #b3cde0, #e0f7fa);
  background-attachment: fixed;
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
    </div>
</div>

<main id="dashboard" class="flex-1 p-6 lg:p-8">
  <div class="bg-white p-6 rounded-xl shadow-lg overflow-x-auto">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-blue-600">Active Queries</h1>
      
      <div class="flex items-center gap-4">
        <!-- Manual Refresh Button -->
        <button onclick="location.reload()" class="refresh-btn">
          <i class="fas fa-sync-alt"></i>
          Refresh
        </button>
        
        <!-- Stats Summary -->
        <div class="flex gap-4 text-sm">
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
    </div>
    
    <!-- Filter and Search Bar -->
    <div class="mb-6 flex flex-col md:flex-row gap-4 justify-between">
      <div class="flex gap-2">
        <a href="queries.php?filter=all" class="px-4 py-2 rounded-lg transition-colors <?php echo $filter == 'all' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">All</a>
        <a href="queries.php?filter=pending" class="px-4 py-2 rounded-lg transition-colors <?php echo $filter == 'pending' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">Pending</a>
        <a href="queries.php?filter=in_progress" class="px-4 py-2 rounded-lg transition-colors <?php echo $filter == 'in_progress' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">In Progress</a>
        <a href="queries.php?filter=completed" class="px-4 py-2 rounded-lg transition-colors <?php echo $filter == 'completed' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">Completed</a>
      </div>
      
      <form method="GET" class="flex gap-2">
        <input type="hidden" name="filter" value="<?php echo $filter; ?>">
        <div class="relative">
          <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
          <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                 placeholder="Search by name, number, or concern..." 
                 class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent w-64">
        </div>
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
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
        <tr class="bg-gradient-to-r from-blue-500 to-blue-400 text-white">
          <th class="p-3 text-center w-16">ID</th>
          <th class="p-3 text-center w-48">Name</th>
          <th class="p-3 text-center w-36">Number</th>
          <th class="p-3 text-center">Concern</th>
          <th class="p-3 text-center w-24">Status</th>
          <th class="p-3 text-center w-28">Date</th>
          <th class="p-3 text-center w-24">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (mysqli_num_rows($requests) > 0): ?>
          <?php while($row = mysqli_fetch_assoc($requests)): ?>
          <tr class="border-b hover:bg-gray-50">
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
              <div class="concern-cell" onclick='openModal(<?php echo $row['id']; ?>, <?php echo json_encode($row['session_id']); ?>)'>
                <div class="concern-text">
                  <?php echo htmlspecialchars(substr($row['reason'] ?: 'No reason provided', 0, 40)) . (strlen($row['reason'] ?: '') > 40 ? '...' : ''); ?>
                </div>
                <div class="text-xs text-blue-500 mt-1">
                  <i class="fas fa-expand-alt mr-1"></i>Click to expand
                </div>
              </div>
            </td>
            <td class="p-3 text-center">
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
              <div class="action-buttons">
                <?php if ($row['status'] == 'pending'): ?>
                  <button onclick="confirmAction('call', <?php echo $row['id']; ?>)" 
                          class="action-btn bg-green-600 text-white hover:bg-green-700">
                    Call
                  </button>
                <?php elseif ($row['status'] == 'in_progress'): ?>
                  <button onclick="confirmAction('complete', <?php echo $row['id']; ?>)" 
                          class="action-btn bg-green-600 text-white hover:bg-green-700">
                    Complete
                  </button>
                <?php endif; ?>
                
                <button onclick="confirmAction('archive', <?php echo $row['id']; ?>)" 
                        class="action-btn bg-orange-500 text-white hover:bg-orange-600">
                  Archive
                </button>
                
                <button onclick="confirmAction('delete', <?php echo $row['id']; ?>)" 
                        class="action-btn bg-blue-600 text-white hover:bg-blue-700">
                  Delete
                </button>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" class="p-8 text-center text-gray-500">
              <i class="fas fa-inbox text-4xl mb-3 text-gray-300"></i>
              <p class="text-lg">No active queries found</p>
              <p class="text-sm">All queries have been archived or completed</p>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
    
    <!-- Table Footer -->
    <div class="mt-4 text-sm text-gray-500 flex justify-between items-center">
      <span>Showing <?php echo mysqli_num_rows($requests); ?> entries</span>
      <span>Total Active: <?php 
        $total = mysqli_query($conn, "SELECT COUNT(*) as count FROM call_requests");
        $total_row = mysqli_fetch_assoc($total);
        echo $total_row['count']; 
      ?></span>
    </div>
  </div>
</main>

<script>
let currentSessionId = null;
let currentRequestId = null;
let messageCheckInterval = null;
let lastMessageId = 0;
let displayedMessageIds = new Set();
let currentAdminName = '<?php 
    $admin_query = mysqli_query($conn, "SELECT fname, lname FROM usr_tbl WHERE id = " . $_SESSION['user_id']);
    $admin_data = mysqli_fetch_assoc($admin_query);
    echo trim(($admin_data['fname'] ?? '') . ' ' . ($admin_data['lname'] ?? '')) ?: 'Admin';
?>';

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
                text: 'This query will be moved to archive.',
                icon: 'warning',
                confirmButtonColor: '#f97316',
                actionUrl: `?archive=${id}`
            };
            break;
        case 'delete':
            config = {
                title: 'Permanently Delete?',
                text: 'This action cannot be undone!',
                icon: 'error',
                confirmButtonColor: '#ef4444',
                actionUrl: `?permanent_delete=${id}`
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
            Swal.fire({
                title: 'Processing...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
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

// Function to open modal and fetch conversation details
function openModal(requestId, sessionId) {
    currentRequestId = requestId;
    currentSessionId = sessionId;
    lastMessageId = 0;
    displayedMessageIds.clear();
    
    document.getElementById('modalBody').innerHTML = `
        <div class="flex justify-center items-center py-12">
            <i class="fas fa-spinner fa-spin text-3xl text-blue-600 mr-3"></i>
            <p class="text-gray-600">Loading conversation...</p>
        </div>
    `;
    document.getElementById('modalTitle').textContent = 'Loading...';
    document.getElementById('modalOverlay').classList.add('active');
    document.body.style.overflow = 'hidden';
    
    fetch(`get_conversation.php?request_id=${requestId}&session_id=${sessionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayConversation(data);
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
                </div>
            `;
        });
}

// Start checking for new messages - FIXED with delay
function startMessageCheck() {
    if (messageCheckInterval) {
        clearInterval(messageCheckInterval);
    }
    
    // Wait 1 second before first check to let modal fully load
    setTimeout(() => {
        checkForNewMessages();
    }, 1000);
    
    // Then check every 3 seconds
    messageCheckInterval = setInterval(checkForNewMessages, 3000);
}

// Check for new messages with SILENT error handling - FIXED to prevent duplicates
function checkForNewMessages() {
    if (!currentSessionId) return;
    
    fetch(`queries.php?check_new_messages=1&session_id=${currentSessionId}&last_message_id=${lastMessageId}`)
        .then(response => {
            if (!response.ok) {
                console.log('Server returned status:', response.status);
                return null;
            }
            return response.json();
        })
        .then(data => {
            if (data && data.success) {
                if (data.messages && data.messages.length > 0) {
                    lastMessageId = data.last_message_id;
                    
                    const chatMessages = document.querySelector('.chat-messages');
                    if (chatMessages) {
                        data.messages.forEach(msg => {
                            // Skip if already displayed (including temp messages)
                            if (!displayedMessageIds.has(msg.id)) {
                                const time = new Date(msg.created_at).toLocaleString();
                                let msgDiv = document.createElement('div');
                                
                                if (msg.sender_type === 'user') {
                                    msgDiv.className = 'message-user';
                                    msgDiv.innerHTML = `
                                        <div>🙍🏻‍♂️ ${escapeHtml(msg.message)}</div>
                                        <div class="message-time">${time}</div>
                                    `;
                                } else if (msg.sender_type === 'ai') {
                                    msgDiv.className = 'message-ai';
                                    msgDiv.innerHTML = `
                                        <div>🤖 ${escapeHtml(msg.message)}</div>
                                        <div class="message-time">${time}</div>
                                    `;
                                } else if (msg.sender_type === 'admin') {
                                    msgDiv.className = 'message-admin';
                                    msgDiv.innerHTML = `
                                        <div><span class="admin-badge">Admin</span><br>${escapeHtml(msg.message)}</div>
                                        <div class="message-time">${time}</div>
                                    `;
                                } else if (msg.sender_type === 'staff') {
                                    msgDiv.className = 'message-staff';
                                    msgDiv.innerHTML = `
                                        <div><span class="staff-badge">Staff</span><br>${escapeHtml(msg.message)}</div>
                                        <div class="message-time">${time}</div>
                                    `;
                                }
                                
                                chatMessages.appendChild(msgDiv);
                                displayedMessageIds.add(msg.id);
                                chatMessages.scrollTop = chatMessages.scrollHeight;
                            }
                        });
                    }
                }
            }
        })
        .catch(error => {
            console.log('Background check error (safe to ignore):', error.message);
        });
}

function displayConversation(data) {
    const request = data.request;
    const messages = data.messages || [];
    
    displayedMessageIds.clear();
    messages.forEach(msg => displayedMessageIds.add(msg.id));
    
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
                <i class="fas fa-comment-dots text-blue-500 mr-2"></i>
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
            <div class="chat-messages">
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
            } else if (msg.sender_type === 'admin') {
                const senderName = msg.sender_name || 'Admin';
                chatHtml += `
                    <div class="message-admin">
                        <div><strong>${escapeHtml(senderName)}</strong> <span class="admin-badge">Admin</span><br>${escapeHtml(msg.message)}</div>
                        <div class="message-time">${time}</div>
                    </div>
                `;
            } else if (msg.sender_type === 'staff') {
                const senderName = msg.sender_name || 'Staff';
                chatHtml += `
                    <div class="message-staff">
                        <div><strong>${escapeHtml(senderName)}</strong> <span class="staff-badge">Staff</span><br>${escapeHtml(msg.message)}</div>
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
    
    // ADMIN CHAT INPUT - Uses send_admin_message
    let chatInputHtml = `
        <div class="chat-input-area">
            <input type="text" id="adminMessageInput" class="chat-input-field" placeholder="Type your message as Admin..." onkeypress="handleChatKeyPress(event)">
            <button onclick="sendAdminMessage()" class="send-chat-btn">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
        <div class="text-xs text-gray-500 mt-1 ml-2">
            <span class="admin-badge">Admin</span> You are messaging as <strong>${escapeHtml(currentAdminName)}</strong> (Administrator)
        </div>
    `;
    
    let footerHtml = `
        <div class="modal-footer">
            <button onclick="closeModal()" class="action-btn bg-gray-500 text-white hover:bg-gray-600">
                Close
            </button>
            <a href="tel:${escapeHtml(request.mobile_number)}" class="action-btn bg-green-600 text-white hover:bg-green-700">
                <i class="fas fa-phone mr-2"></i>Call Customer
            </a>
        </div>
    `;
    
    const modalContent = infoHtml + reasonHtml + chatHtml + chatInputHtml + footerHtml;
    
    document.getElementById('modalTitle').textContent = `Conversation #${String(request.id).padStart(3, '0')}`;
    document.getElementById('modalBody').innerHTML = modalContent;
}

function handleChatKeyPress(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        sendAdminMessage();
    }
}

// ADMIN MESSAGE SENDING FUNCTION - Fixed to prevent duplicates
function sendAdminMessage() {
    const messageInput = document.getElementById('adminMessageInput');
    if (!messageInput) return;
    
    const message = messageInput.value.trim();
    if (!message) return;
    
    if (!currentSessionId) {
        return;
    }
    
    messageInput.value = '';
    
    // Create a temporary unique ID for this message
    const tempId = 'temp_' + Date.now();
    
    // Add to displayed messages set to prevent duplicate
    displayedMessageIds.add(tempId);
    
    // Show message immediately with temp ID
    const chatMessages = document.querySelector('.chat-messages');
    const time = new Date().toLocaleString();
    
    const msgDiv = document.createElement('div');
    msgDiv.className = 'message-admin';
    msgDiv.setAttribute('data-temp-id', tempId); // Mark with temp ID
    msgDiv.innerHTML = `
        <div><span class="admin-badge">Admin</span><br>${escapeHtml(message)}</div>
        <div class="message-time">${time}</div>
    `;
    chatMessages.appendChild(msgDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
    
    const formData = new FormData();
    formData.append('send_admin_message', '1');
    formData.append('session_id', currentSessionId);
    formData.append('message', message);
    
    fetch('queries.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Replace temp ID with real ID when response comes back
            const tempMsg = document.querySelector(`[data-temp-id="${tempId}"]`);
            if (tempMsg) {
                tempMsg.removeAttribute('data-temp-id');
                // Replace temp ID in the set with real ID
                displayedMessageIds.delete(tempId);
                displayedMessageIds.add(data.new_message.id);
            }
        } else {
            // Remove the message if it failed to send
            const tempMsg = document.querySelector(`[data-temp-id="${tempId}"]`);
            if (tempMsg) {
                tempMsg.remove();
                displayedMessageIds.delete(tempId);
            }
        }
    })
    .catch(error => {
        // Remove message on error
        const tempMsg = document.querySelector(`[data-temp-id="${tempId}"]`);
        if (tempMsg) {
            tempMsg.remove();
            displayedMessageIds.delete(tempId);
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