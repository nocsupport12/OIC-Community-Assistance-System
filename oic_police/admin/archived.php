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
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Archived Queries - Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
/*===========================================
   ARCHIVED QUERIES PAGE STYLES
   ===========================================*/

/*===== VARIABLES =====*/
:root {
  --primary: #2563eb;
  --primary-light: #60a5fa;
  --accent-green: #43991f;
  --accent-yellow: #fdd400;
  --orange-500: #f97316;
  --orange-600: #ea580c;
  --orange-700: #c2410c;
  --blue-500: #3b82f6;
  --blue-600: #2563eb;
  --blue-700: #1d4ed8;
  --green-600: #16a34a;
  --green-700: #15803d;
  --red-600: #dc2626;
  --red-700: #b91c1c;
  --gray-50: #f9fafb;
  --gray-100: #f3f4f6;
  --gray-200: #e5e7eb;
  --gray-300: #d1d5db;
  --gray-400: #9ca3af;
  --gray-500: #6b7280;
  --gray-600: #4b5563;
  --gray-700: #374151;
  --gray-800: #1f2937;
  --white: #ffffff;
  --bg-body: #9BB4C0;
  --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/*===== BASE STYLES =====*/
body {
  background-color: var(--bg-body);
  color: var(--gray-800);
  font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
  margin: 0;
  padding: 0;
}

/*===== LAYOUT =====*/
main {
  flex: 1;
  padding: 1.5rem;
}

@media (min-width: 1024px) {
  main {
    padding: 2rem;
  }
}

.content-wrapper {
  background-color: var(--white);
  padding: 1.5rem;
  border-radius: 0.75rem;
  box-shadow: var(--shadow-lg);
  overflow-x: auto;
}

/*===== NAVIGATION TABS =====*/
.nav-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  border-bottom: 1px solid var(--gray-200);
  padding-bottom: 0.5rem;
}

.nav-tabs {
  display: flex;
  gap: 0.5rem;
}

.nav-link {
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  transition: all 0.2s;
  text-decoration: none;
  color: var(--gray-700);
}

.nav-link:hover {
  background-color: var(--gray-100);
}

.nav-link.active {
  background-color: var(--blue-500);
  color: var(--white);
}

.nav-link i {
  margin-right: 0.5rem;
}

/*===== BUTTONS =====*/
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
  border: none;
  cursor: pointer;
}

.btn-green {
  background-color: var(--green-600);
  color: var(--white);
}

.btn-green:hover {
  background-color: var(--green-700);
}

.btn-red {
  background-color: var(--red-600);
  color: var(--white);
}

.btn-red:hover {
  background-color: var(--red-700);
}

.btn-blue {
  background-color: var(--blue-500);
  color: var(--white);
  padding: 0.5rem 1.5rem;
  border-radius: 0.5rem;
  border: none;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-blue:hover {
  background-color: var(--blue-700);
}

.btn-gray {
  background-color: var(--gray-500);
  color: var(--white);
  padding: 0.5rem 1.5rem;
  border-radius: 0.5rem;
  text-decoration: none;
  border: none;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-gray:hover {
  background-color: var(--gray-600);
}

.refresh-btn {
  background-color: var(--blue-500);
  color: var(--white);
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 0.875rem;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  cursor: pointer;
  border: none;
  transition: background-color 0.2s;
  margin-left: 10px;
}

.refresh-btn:hover {
  background-color: var(--blue-600);
}

.refresh-btn i {
  font-size: 0.875rem;
}

/*===== SEARCH BAR =====*/
.search-section {
  margin-bottom: 1.5rem;
}

.search-form {
  display: flex;
  gap: 0.5rem;
}

.search-wrapper {
  position: relative;
  flex: 1;
}

.search-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--gray-400);
}

.search-input {
  width: 100%;
  padding: 0.5rem 1rem 0.5rem 2.5rem;
  border: 1px solid var(--gray-300);
  border-radius: 0.5rem;
  font-size: 1rem;
  transition: all 0.2s;
  box-sizing: border-box;
}

.search-input:focus {
  outline: none;
  box-shadow: 0 0 0 2px var(--orange-500);
}

/*===== STATUS BADGES =====*/
.status-badge {
  padding: 4px 8px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  display: inline-block;
}

.status-pending {
  background-color: #fef3c7;
  color: #92400e;
}

.status-in-progress {
  background-color: #dbeafe;
  color: #1e40af;
}

.status-completed {
  background-color: #dcfce7;
  color: #166534;
}

/*===== TABLE STYLES =====*/
.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.data-table thead tr {
  background-color: var(--bg-body);
  color: var(--white);
}

.data-table th {
  padding: 0.75rem;
  text-align: center;
}

.data-table tbody tr {
  border-bottom: 1px solid var(--gray-200);
}

.data-table tbody tr:hover {
  background-color: var(--gray-50);
}

.data-table td {
  padding: 0.75rem;
}

.text-center {
  text-align: center;
}

.font-mono {
  font-family: monospace;
}

.id-cell {
  font-weight: 500;
}

.name-cell .main-name {
  font-weight: 500;
}

.name-cell .email-sub {
  font-size: 0.75rem;
  color: var(--gray-500);
}

/*===== CONCERN CELL WITH MODAL TRIGGER =====*/
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
  color: var(--gray-700);
}

.concern-text:hover {
  color: var(--orange-500);
  text-decoration: underline;
}

.expand-hint {
  font-size: 0.75rem;
  color: var(--blue-500);
  margin-top: 0.25rem;
}

.expand-hint i {
  margin-right: 0.25rem;
}

/*===== EMPTY STATE =====*/
.empty-state {
  padding: 2rem;
  text-align: center;
  color: var(--gray-500);
}

.empty-state i {
  font-size: 2.5rem;
  margin-bottom: 0.75rem;
  color: var(--gray-300);
}

.empty-state p:first-of-type {
  font-size: 1.125rem;
}

.empty-state p:last-of-type {
  font-size: 0.875rem;
}

/*===== STATS SUMMARY =====*/
.stats-summary {
  margin-bottom: 1rem;
  font-size: 0.875rem;
  color: var(--gray-600);
  display: flex;
  align-items: center;
  gap: 1rem;
}

.stats-summary .total {
  font-weight: bold;
  color: var(--orange-600);
}

/*===== MODAL STYLES =====*/
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
  background: var(--white);
  border-radius: 12px;
  width: 90%;
  max-width: 700px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: var(--shadow-lg);
}

.modal-header {
  background-color: var(--blue-500);
  color: var(--white);
  padding: 16px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-radius: 12px 12px 0 0;
  position: sticky;
  top: 0;
  z-index: 10;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: bold;
}

.close-btn {
  background: none;
  border: none;
  color: var(--white);
  font-size: 1.5rem;
  cursor: pointer;
  padding: 0 5px;
}

.close-btn:hover {
  opacity: 0.8;
}

.modal-body {
  padding: 20px;
}

.modal-footer {
  padding: 16px 20px;
  border-top: 1px solid var(--gray-200);
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  position: sticky;
  bottom: 0;
  background: var(--white);
  border-radius: 0 0 12px 12px;
}

/*===== MODAL CONTENT SECTIONS =====*/
.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
  margin-bottom: 20px;
  background: var(--gray-50);
  padding: 16px;
  border-radius: 8px;
}

.info-label {
  font-size: 0.75rem;
  color: var(--gray-500);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 4px;
}

.info-value {
  font-weight: 600;
  color: var(--gray-800);
}

.reason-box {
  background: var(--gray-50);
  padding: 16px;
  border-radius: 8px;
  margin-bottom: 20px;
  border-left: 4px solid var(--orange-500);
}

.reason-box h4 {
  margin: 0 0 0.5rem 0;
  font-weight: 600;
  color: var(--gray-700);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.reason-box h4 i {
  color: var(--orange-500);
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
  background: var(--gray-50);
  padding: 16px;
  border-radius: 8px;
  margin-bottom: 20px;
}

.chat-history h4 {
  margin: 0 0 0.75rem 0;
  font-weight: 600;
  color: var(--gray-700);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.chat-history h4 i {
  color: #a855f7;
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
  background: var(--blue-500);
  color: var(--white);
  padding: 10px 14px;
  border-radius: 18px 18px 4px 18px;
  max-width: 80%;
  align-self: flex-end;
  word-wrap: break-word;
}

.message-ai {
  background: var(--gray-200);
  color: var(--gray-800);
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

.loading-spinner {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 3rem 0;
  gap: 0.75rem;
}

.loading-spinner i {
  font-size: 1.875rem;
  color: var(--orange-600);
  animation: spin 1s linear infinite;
}

.loading-spinner p {
  color: var(--gray-600);
}

.error-message {
  text-align: center;
  padding: 3rem 0;
  color: var(--red-600);
}

.error-message i {
  font-size: 2.5rem;
  margin-bottom: 0.75rem;
}

/*===== ANIMATIONS =====*/
@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/*===== UTILITY CLASSES =====*/
.flex {
  display: flex;
}

.items-center {
  align-items: center;
}

.justify-between {
  justify-content: space-between;
}

.gap-2 {
  gap: 0.5rem;
}

.mb-6 {
  margin-bottom: 1.5rem;
}

.mr-2 {
  margin-right: 0.5rem;
}

.text-sm {
  font-size: 0.875rem;
}

.font-bold {
  font-weight: 700;
}

.w-full {
  width: 100%;
}

/*===== RESPONSIVE ADJUSTMENTS =====*/
@media (max-width: 768px) {
  .info-grid {
    grid-template-columns: 1fr;
  }
  
  .modal-footer {
    flex-direction: column;
  }
  
  .modal-footer .action-btn {
    width: 100%;
    justify-content: center;
  }
  
  .nav-container {
    flex-direction: column;
    gap: 1rem;
  }
  
  .search-form {
    flex-direction: column;
  }
  
  .data-table {
    font-size: 0.75rem;
  }
  
  .data-table th,
  .data-table td {
    padding: 0.5rem;
  }
}
</style>
</head>
<body>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h3 id="modalTitle">Archived Conversation Details</h3>
            <button onclick="closeModal()" class="close-btn">&times;</button>
        </div>
        <div class="modal-body" id="modalBody">
            <!-- Content will be loaded dynamically -->
        </div>
    </div>
</div>

<main>
  <div class="content-wrapper">
    <!-- Navigation Tabs with Refresh Button -->
    <div class="nav-container">
      <div class="nav-tabs">
        <p class="nav-link active">
          <i class="fas fa-archive"></i>Archived Queries
        </p>
      </div>
    </div>

    <!-- Search Bar -->
    <div class="search-section">
      <form method="GET" class="search-form">
        <div class="search-wrapper">
          <i class="fas fa-search search-icon"></i>
          <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                 placeholder="Search archived queries..." 
                 class="search-input">
        </div>
        
        <button type="submit" class="btn-blue">
          Search
        </button>
        
        <?php if (!empty($search)): ?>
          <a href="archived.php" class="btn-gray">
            Clear
          </a>
        <?php endif; ?>
      </form>
    </div>
    
    <!-- Stats Summary -->
    <div class="stats-summary">
      <button onclick="location.reload()" class="refresh-btn">
        <i class="fas fa-sync-alt"></i>
        Refresh
      </button>
      Total Archived: <span class="total"><?php echo $total_archived; ?></span> queries
    </div>
    
    <!-- Archived Queries Table -->
    <table class="data-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Number</th>
          <th>Concern</th>
          <th>Original Status</th>
          <th>Archived Date</th>
          <th>Archived By</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (mysqli_num_rows($archived) > 0): ?>
          <?php while($row = mysqli_fetch_assoc($archived)): ?>
          <tr>
            <td class="text-center id-cell">#<?php echo str_pad($row['original_id'], 3, '0', STR_PAD_LEFT); ?></td>
            <td class="name-cell">
              <div class="main-name"><?php echo htmlspecialchars($row['name'] ?: 'Unknown'); ?></div>
              <?php if (!empty($row['email'])): ?>
                <div class="email-sub"><?php echo htmlspecialchars($row['email']); ?></div>
              <?php endif; ?>
            </td>
            <td class="text-center font-mono">
              <?php echo htmlspecialchars($row['mobile_number']); ?>
            </td>
            <td>
              <div class="concern-cell" onclick='openModal(<?php echo $row['id']; ?>, <?php echo json_encode($row['session_id']); ?>)'>
                <div class="concern-text">
                  <?php echo htmlspecialchars(substr($row['reason'] ?: 'No reason provided', 0, 50)) . (strlen($row['reason'] ?: '') > 50 ? '...' : ''); ?>
                </div>
                <div class="expand-hint">
                  <i class="fas fa-expand-alt"></i>Click to view full conversation
                </div>
              </div>
            </td>
            <td class="text-center">
              <?php
              $status_class = '';
              if ($row['status'] == 'pending') $status_class = 'status-pending';
              elseif ($row['status'] == 'in_progress') $status_class = 'status-in-progress';
              elseif ($row['status'] == 'completed') $status_class = 'status-completed';
              ?>
              <span class="status-badge <?php echo $status_class; ?>">
                <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
              </span>
            </td>
            <td class="text-center text-sm">
              <?php echo date('M d, Y h:i A', strtotime($row['archived_at'])); ?>
            </td>
            <td class="text-center">
              <?php 
              $staff_name = trim(($row['fname'] ?? '') . ' ' . ($row['lname'] ?? ''));
              echo htmlspecialchars($staff_name ?: 'Unknown Staff'); 
              ?>
            </td>
            <td class="text-center">
              <div class="flex gap-2 justify-center">
                <!-- Restore Button with SweetAlert confirmation -->
                <a href="javascript:void(0)" 
                   onclick="confirmRestore(<?php echo $row['id']; ?>)"
                   class="action-btn btn-green">
                  <i class="fas fa-undo"></i> Restore
                </a>
                
                <!-- Permanent Delete Button with SweetAlert confirmation -->
                <a href="javascript:void(0)" 
                   onclick="confirmDelete(<?php echo $row['id']; ?>)"
                   class="action-btn btn-red">
                  <i class="fas fa-trash"></i> Delete
                </a>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="8" class="empty-state">
              <i class="fas fa-archive"></i>
              <p>No archived queries found</p>
              <p>Archived queries from staff will appear here</p>
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
        <div class="loading-spinner">
            <i class="fas fa-spinner"></i>
            <p>Loading archived conversation...</p>
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
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>Error loading conversation: ${data.message}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = `
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
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
                    <span class="status-badge ${archive.status === 'pending' ? 'status-pending' : archive.status === 'in_progress' ? 'status-in-progress' : 'status-completed'}">
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
            <h4>
                <i class="fas fa-comment-dots"></i>
                Concern / Reason
            </h4>
            <div class="reason-text">${escapeHtml(archive.reason || 'No reason provided')}</div>
        </div>
    `;
    
    // Build chat history HTML
    let chatHtml = `
        <div class="chat-history">
            <h4>
                <i class="fas fa-history"></i>
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
            <div class="text-center" style="color: var(--gray-500); padding: 1rem 0;">
                <i class="fas fa-comment-slash" style="font-size: 1.875rem; margin-bottom: 0.5rem; color: var(--gray-300);"></i>
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
            <button onclick="closeModal()" class="action-btn btn-gray">
                Close
            </button>
            <a href="?restore=${archive.id}" 
               onclick="return confirmRestoreFromModal(${archive.id}); return false;"
               class="action-btn btn-green">
                <i class="fas fa-undo mr-2"></i>Restore
            </a>
            <a href="?permanent_delete=${archive.id}" 
               onclick="return confirmDeleteFromModal(${archive.id}); return false;"
               class="action-btn btn-red">
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

// Modal restore confirmation
function confirmRestoreFromModal(id) {
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
    return false;
}

// Modal delete confirmation
function confirmDeleteFromModal(id) {
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
    return false;
}
</script>

</body>
</html>
<?php mysqli_close($conn); ?>