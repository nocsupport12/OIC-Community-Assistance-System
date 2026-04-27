<?php
session_start();
include("../components/db.php");

// Protect admin page
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../login.php");
    exit;
}

include("../components/admin_nav.php");

// Handle permanent delete (admin only)
if (isset($_GET['permanent_delete'])) {
    $id = intval($_GET['permanent_delete']);
    $result = mysqli_query($conn, "DELETE FROM archived_queries WHERE id = $id");
    if ($result) {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Deleted!',
                text: 'Query permanently deleted.',
                confirmButtonColor: '#f97316'
            }).then(() => {
                window.location.href = 'archived.php';
            });
        </script>";
    } else {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Delete failed: " . mysqli_error($conn) . "',
                confirmButtonColor: '#f97316'
            }).then(() => {
                window.location.href = 'archived.php';
            });
        </script>";
    }
    exit;
}

// Handle restore to active
if (isset($_GET['restore'])) {
    $id = intval($_GET['restore']);
    
    // Get archived record
    $get_archive = mysqli_query($conn, "SELECT * FROM archived_queries WHERE id = $id");
    
    if (!$get_archive) {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Database error: " . mysqli_error($conn) . "',
                confirmButtonColor: '#f97316'
            }).then(() => {
                window.location.href = 'archived.php';
            });
        </script>";
        exit;
    }
    
    $archive = mysqli_fetch_assoc($get_archive);
    
    if ($archive) {
        // Check if original ID already exists
        $check = mysqli_query($conn, "SELECT id FROM call_requests WHERE id = {$archive['original_id']}");
        
        if (mysqli_num_rows($check) > 0) {
            // Original ID exists, create new ID
            $restore = "INSERT INTO call_requests 
                (session_id, name, mobile_number, email, reason, contact_type, status, requested_at) 
                VALUES (
                    '{$archive['session_id']}', 
                    '{$archive['name']}', 
                    '{$archive['mobile_number']}', 
                    '{$archive['email']}', 
                    '{$archive['reason']}', 
                    '{$archive['contact_type']}', 
                    '{$archive['status']}', 
                    '{$archive['requested_at']}'
                )";
        } else {
            // Restore with original ID
            $restore = "INSERT INTO call_requests 
                (id, session_id, name, mobile_number, email, reason, contact_type, status, requested_at) 
                VALUES (
                    {$archive['original_id']}, 
                    '{$archive['session_id']}', 
                    '{$archive['name']}', 
                    '{$archive['mobile_number']}', 
                    '{$archive['email']}', 
                    '{$archive['reason']}', 
                    '{$archive['contact_type']}', 
                    '{$archive['status']}', 
                    '{$archive['requested_at']}'
                )";
        }
        
        $restore_result = mysqli_query($conn, $restore);
        
        if ($restore_result) {
            // Delete from archive
            mysqli_query($conn, "DELETE FROM archived_queries WHERE id = $id");
            
            // Show success message with SweetAlert
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Restored!',
                    text: 'Query has been restored to active panel.',
                    confirmButtonColor: '#f97316'
                }).then(() => {
                    window.location.href = 'archived.php';
                });
            </script>";
        } else {
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Restore failed: " . mysqli_error($conn) . "',
                    confirmButtonColor: '#f97316'
                }).then(() => {
                    window.location.href = 'archived.php';
                });
            </script>";
        }
    }
    exit;
}

// Get search parameter
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Build query
$where = "1=1";
if (!empty($search)) {
    $where .= " AND (a.name LIKE '%$search%' OR a.mobile_number LIKE '%$search%' OR a.email LIKE '%$search%' OR a.reason LIKE '%$search%')";
}

// Fetch archived queries with staff info
$query = "SELECT a.*, u.fname, u.lname as staff_name 
    FROM archived_queries a 
    LEFT JOIN usr_tbl u ON a.archived_by = u.id 
    WHERE $where 
    ORDER BY a.archived_at DESC";

$archived = mysqli_query($conn, $query);

if (!$archived) {
    die("Query failed: " . mysqli_error($conn));
}

$total_archived = mysqli_num_rows($archived);
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Archived Queries - Admin</title>
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
.action-btn {
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  transition: all 0.2s;
}
.status-badge {
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
  color: #f97316;
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
  background: linear-gradient(to right, #f97316, #eab308);
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
  border-left: 4px solid #f97316;
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
.nav-link {
  padding: 8px 16px;
  border-radius: 8px;
  transition: all 0.2s;
}
.nav-link:hover {
  background-color: #f3f4f6;
}
.nav-link.active {
  background-color: #f97316;
  color: white;
}
.refresh-btn {
  background-color: #f97316;
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
  margin-left: 10px;
}
.refresh-btn:hover {
  background-color: #ea580c;
}
.refresh-btn i {
  font-size: 0.875rem;
}
</style>
</head>
<body class="bg-[#DDA15E] text-gray-800 font-sans">

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="text-xl font-bold" id="modalTitle">Archived Conversation Details</h3>
            <button onclick="closeModal()" class="close-btn">&times;</button>
        </div>
        <div class="modal-body" id="modalBody">
            <!-- Content will be loaded dynamically -->
        </div>
    </div>
</div>

<main id="dashboard" class="flex-1 p-6 lg:p-8">
  <div class="bg-white p-6 rounded-xl shadow-lg overflow-x-auto">
    <!-- Navigation Tabs with Refresh Button -->
    <div class="flex justify-between items-center mb-6 border-b pb-2">
      <div class="flex gap-2">
        <p class="nav-link active">
          <i class="fas fa-archive mr-2"></i>Archived Queries
        </p>
      </div>
      
      

    <!-- Search Bar -->
    <div class="mb-6">
      <form method="GET" class="flex gap-2">
        <div class="relative flex-1">
          <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
          <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                 placeholder="Search archived queries..." 
                 class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 w-full">
        </div>
        
        <button type="submit" class="bg-orange-600 text-white px-6 py-2 rounded-lg hover:bg-orange-700">
          Search
        </button>
       
    </div>
        <?php if (!empty($search)): ?>
          <a href="archived.php" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600">
            Clear
          </a>
        <?php endif; ?>
      </form>
    </div>
    
    <!-- Stats Summary -->
    <div class="mb-4 text-sm text-gray-600">
       <!-- Manual Refresh Button -->
      <button onclick="location.reload()" class="refresh-btn">
        <i class="fas fa-sync-alt"></i>
        Refresh
      </button>
      Total Archived: <span class="font-bold text-orange-600"><?php echo $total_archived; ?></span> queries
    </div>
    
    <!-- Archived Queries Table -->
    <table class="w-full border-collapse text-sm">
      <thead>
        <tr class="bg-gradient-to-r from-orange-500 to-yellow-400 text-white">
          <th class="p-3 text-center">ID</th>
          <th class="p-3 text-center">Name</th>
          <th class="p-3 text-center">Number</th>
          <th class="p-3 text-center">Concern</th>
          <th class="p-3 text-center">Original Status</th>
          <th class="p-3 text-center">Archived Date</th>
          <th class="p-3 text-center">Archived By</th>
          <th class="p-3 text-center">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (mysqli_num_rows($archived) > 0): ?>
          <?php while($row = mysqli_fetch_assoc($archived)): ?>
          <tr class="border-b hover:bg-gray-50">
            <td class="p-3 text-center font-medium">#<?php echo str_pad($row['original_id'], 3, '0', STR_PAD_LEFT); ?></td>
            <td class="p-3">
              <div class="font-medium"><?php echo htmlspecialchars($row['name'] ?: 'Unknown'); ?></div>
              <?php if (!empty($row['email'])): ?>
                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($row['email']); ?></div>
              <?php endif; ?>
            </td>
            <td class="p-3 text-center font-mono">
              <?php echo htmlspecialchars($row['mobile_number']); ?>
            </td>
            <td class="p-3">
              <div class="concern-cell" onclick='openModal(<?php echo $row['id']; ?>, <?php echo json_encode($row['session_id']); ?>)'>
                <div class="concern-text">
                  <?php echo htmlspecialchars(substr($row['reason'] ?: 'No reason provided', 0, 50)) . (strlen($row['reason'] ?: '') > 50 ? '...' : ''); ?>
                </div>
                <div class="text-xs text-orange-500 mt-1">
                  <i class="fas fa-expand-alt mr-1"></i>Click to view full conversation
                </div>
              </div>
            </td>
            <td class="p-3 text-center">
              <?php
              $status_colors = [
                'pending' => 'bg-yellow-100 text-yellow-800',
                'in_progress' => 'bg-blue-100 text-blue-800',
                'completed' => 'bg-green-100 text-green-800'
              ];
              $color = $status_colors[$row['status']] ?? 'bg-gray-100 text-gray-800';
              ?>
              <span class="status-badge <?php echo $color; ?>">
                <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
              </span>
            </td>
            <td class="p-3 text-center text-sm text-gray-500">
              <?php echo date('M d, Y h:i A', strtotime($row['archived_at'])); ?>
            </td>
            <td class="p-3 text-center">
              <?php 
              $staff_name = trim(($row['fname'] ?? '') . ' ' . ($row['lname'] ?? ''));
              echo htmlspecialchars($staff_name ?: 'Unknown Staff'); 
              ?>
            </td>
            <td class="p-3 text-center">
              <div class="flex gap-2 justify-center">
                <!-- Restore Button with SweetAlert confirmation -->
                <a href="javascript:void(0)" 
                   onclick="confirmRestore(<?php echo $row['id']; ?>)"
                   class="action-btn bg-green-600 text-white hover:bg-green-700">
                  <i class="fas fa-undo"></i> Restore
                </a>
                
                <!-- Permanent Delete Button with SweetAlert confirmation -->
                <a href="javascript:void(0)" 
                   onclick="confirmDelete(<?php echo $row['id']; ?>)"
                   class="action-btn bg-red-600 text-white hover:bg-red-700">
                  <i class="fas fa-trash"></i> Delete
                </a>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="8" class="p-8 text-center text-gray-500">
              <i class="fas fa-archive text-4xl mb-3 text-gray-300"></i>
              <p class="text-lg">No archived queries found</p>
              <p class="text-sm">Archived queries from staff will appear here</p>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<script>
// Function to open modal and fetch conversation details
function openModal(archiveId, sessionId) {
    // Show loading in modal
    document.getElementById('modalBody').innerHTML = `
        <div class="flex justify-center items-center py-12">
            <i class="fas fa-spinner fa-spin text-3xl text-orange-600 mr-3"></i>
            <p class="text-gray-600">Loading archived conversation...</p>
        </div>
    `;
    document.getElementById('modalTitle').textContent = 'Loading...';
    document.getElementById('modalOverlay').classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // Fetch archived conversation details
    fetch(`get_archived_conversation.php?archive_id=${archiveId}&session_id=${sessionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayConversation(data);
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

// Function to display conversation in modal
function displayConversation(data) {
    const archive = data.archive;
    const messages = data.messages || [];
    
    // Build customer info HTML
    let infoHtml = `
        <div class="info-grid">
            <div>
                <div class="info-label">Name</div>
                <div class="info-value">${escapeHtml(archive.name || 'Unknown')}</div>
            </div>
            <div>
                <div class="info-label">Mobile Number</div>
                <div class="info-value">${escapeHtml(archive.mobile_number || 'N/A')}</div>
            </div>
            <div>
                <div class="info-label">Email</div>
                <div class="info-value">${escapeHtml(archive.email || 'Not provided')}</div>
            </div>
            <div>
                <div class="info-label">Original Status</div>
                <div class="info-value">
                    <span class="status-badge ${archive.status === 'pending' ? 'bg-yellow-100 text-yellow-800' : archive.status === 'in_progress' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'}">
                        ${archive.status.replace('_', ' ').toUpperCase()}
                    </span>
                </div>
            </div>
            <div>
                <div class="info-label">Archived Date</div>
                <div class="info-value">${new Date(archive.archived_at).toLocaleString()}</div>
            </div>
            <div>
                <div class="info-label">Contact Type</div>
                <div class="info-value">${archive.contact_type === 'call' ? '📞 Phone Call' : '📧 Email'}</div>
            </div>
        </div>
    `;
    
    // Build reason HTML
    let reasonHtml = `
        <div class="reason-box">
            <h4 class="font-semibold text-gray-700 mb-2 flex items-center">
                <i class="fas fa-comment-dots text-orange-500 mr-2"></i>
                Concern / Reason
            </h4>
            <div class="reason-text">${escapeHtml(archive.reason || 'No reason provided')}</div>
        </div>
    `;
    
    // Build chat history HTML
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
            } else {
                chatHtml += `
                    <div class="message-ai">
                        <div>${escapeHtml(msg.message)}</div>
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
    
    // Build footer with restore button
    let footerHtml = `
        <div class="modal-footer">
            <button onclick="closeModal()" class="action-btn bg-gray-500 text-white hover:bg-gray-600">
                Close
            </button>
            <a href="?restore=${archive.id}" 
               onclick="return confirmRestoreFromModal(${archive.id}); return false;"
               class="action-btn bg-green-600 text-white hover:bg-green-700">
                <i class="fas fa-undo mr-2"></i>Restore
            </a>
            <a href="?permanent_delete=${archive.id}" 
               onclick="return confirmDeleteFromModal(${archive.id}); return false;"
               class="action-btn bg-red-600 text-white hover:bg-red-700">
                <i class="fas fa-trash mr-2"></i>Delete
            </a>
        </div>
    `;
    
    // Combine all HTML
    const modalContent = infoHtml + reasonHtml + chatHtml + footerHtml;
    
    document.getElementById('modalTitle').textContent = `Archived Conversation #${String(archive.original_id).padStart(3, '0')}`;
    document.getElementById('modalBody').innerHTML = modalContent;
}

// Helper function to escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close modal function
function closeModal() {
    document.getElementById('modalOverlay').classList.remove('active');
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
document.getElementById('modalOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

// SweetAlert confirm restore
function confirmRestore(id) {
    Swal.fire({
        title: 'Restore Query?',
        text: 'This query will be moved back to active panel.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#22c55e',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, restore it!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `?restore=${id}`;
        }
    });
}

// SweetAlert confirm delete
function confirmDelete(id) {
    Swal.fire({
        title: 'Permanently Delete?',
        text: 'This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `?permanent_delete=${id}`;
        }
    });
}

// AUTO-REFRESH REMOVED - Manual refresh only
</script>

</body>
</html>
<?php mysqli_close($conn); ?>