<?php
// knowledge_admin.php
session_start();
include("../components/db.php");

// Protect admin page
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Start output buffering to prevent header errors
ob_start();

include("../components/admin_nav.php");

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg = "";
    $error = "";
    
    // ============= DROPDOWN GROUP OPERATIONS =============
    
    // Add Dropdown Group
    if (isset($_POST['add_dropdown_group'])) {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $icon = mysqli_real_escape_string($conn, $_POST['icon']);
        $color = mysqli_real_escape_string($conn, $_POST['color']);
        $display_order = (int)$_POST['display_order'];
        $language = mysqli_real_escape_string($conn, $_POST['language']);
        
        $sql = "INSERT INTO dropdown_groups (name, icon, color, display_order, language) VALUES ('$name', '$icon', '$color', $display_order, '$language')";
        if (mysqli_query($conn, $sql)) {
            $msg = "Dropdown group added successfully";
        } else {
            $error = mysqli_error($conn);
        }
    }
    
    // Update Dropdown Group
    if (isset($_POST['update_dropdown_group'])) {
        $id = (int)$_POST['id'];
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $icon = mysqli_real_escape_string($conn, $_POST['icon']);
        $color = mysqli_real_escape_string($conn, $_POST['color']);
        $display_order = (int)$_POST['display_order'];
        $language = mysqli_real_escape_string($conn, $_POST['language']);
        
        $sql = "UPDATE dropdown_groups SET name='$name', icon='$icon', color='$color', display_order=$display_order, language='$language' WHERE id=$id";
        if (mysqli_query($conn, $sql)) {
            $msg = "Dropdown group updated successfully";
        } else {
            $error = mysqli_error($conn);
        }
    }
    
    // Delete Dropdown Group
    if (isset($_POST['delete_dropdown_group'])) {
        $id = (int)$_POST['id'];
        
        // First update categories to remove this group
        mysqli_query($conn, "UPDATE categories SET dropdown_group_id = NULL WHERE dropdown_group_id = $id");
        // Then delete group
        $sql = "DELETE FROM dropdown_groups WHERE id = $id";
        if (mysqli_query($conn, $sql)) {
            $msg = "Dropdown group deleted successfully";
        } else {
            $error = mysqli_error($conn);
        }
    }
    
    // ============= CATEGORY OPERATIONS =============
    
    // Add Category
    if (isset($_POST['add_category'])) {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $icon = mysqli_real_escape_string($conn, $_POST['icon']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $language = mysqli_real_escape_string($conn, $_POST['language']);
        $dropdown_group_id = !empty($_POST['dropdown_group_id']) ? (int)$_POST['dropdown_group_id'] : 'NULL';
        
        $sql = "INSERT INTO categories (name, icon, description, language, dropdown_group_id) VALUES ('$name', '$icon', '$description', '$language', $dropdown_group_id)";
        if (mysqli_query($conn, $sql)) {
            $category_id = mysqli_insert_id($conn);
            // Log to ai_training_log if table exists
            $checkTable = mysqli_query($conn, "SHOW TABLES LIKE 'ai_training_log'");
            if (mysqli_num_rows($checkTable) > 0) {
                mysqli_query($conn, "INSERT INTO ai_training_log (action, item_type, item_id, performed_by) VALUES ('add', 'category', $category_id, {$_SESSION['user_id']})");
            }
            $msg = "Category added successfully";
        } else {
            $error = mysqli_error($conn);
        }
    }
    
    // Update Category
    if (isset($_POST['update_category'])) {
        $id = (int)$_POST['id'];
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $icon = mysqli_real_escape_string($conn, $_POST['icon']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $language = mysqli_real_escape_string($conn, $_POST['language']);
        $dropdown_group_id = !empty($_POST['dropdown_group_id']) ? (int)$_POST['dropdown_group_id'] : 'NULL';
        
        $sql = "UPDATE categories SET name='$name', icon='$icon', description='$description', language='$language', dropdown_group_id=$dropdown_group_id WHERE id=$id";
        if (mysqli_query($conn, $sql)) {
            $checkTable = mysqli_query($conn, "SHOW TABLES LIKE 'ai_training_log'");
            if (mysqli_num_rows($checkTable) > 0) {
                mysqli_query($conn, "INSERT INTO ai_training_log (action, item_type, item_id, performed_by) VALUES ('edit', 'category', $id, {$_SESSION['user_id']})");
            }
            $msg = "Category updated successfully";
        } else {
            $error = mysqli_error($conn);
        }
    }
    
    // Delete Category
    if (isset($_POST['delete_category'])) {
        $id = (int)$_POST['id'];
        
        // First delete related knowledge items
        mysqli_query($conn, "DELETE FROM knowledge_base WHERE category_id = $id");
        // Then delete category
        $sql = "DELETE FROM categories WHERE id = $id";
        if (mysqli_query($conn, $sql)) {
            $checkTable = mysqli_query($conn, "SHOW TABLES LIKE 'ai_training_log'");
            if (mysqli_num_rows($checkTable) > 0) {
                mysqli_query($conn, "INSERT INTO ai_training_log (action, item_type, item_id, performed_by) VALUES ('delete', 'category', $id, {$_SESSION['user_id']})");
            }
            $msg = "Category deleted successfully";
        } else {
            $error = mysqli_error($conn);
        }
    }
    
    // ============= KNOWLEDGE OPERATIONS =============
    
    // Add Knowledge
    if (isset($_POST['add_knowledge'])) {
        $category_id = (int)$_POST['category_id'];
        $question = mysqli_real_escape_string($conn, $_POST['question']);
        $answer = mysqli_real_escape_string($conn, $_POST['answer']);
        $keywords = mysqli_real_escape_string($conn, $_POST['keywords']);
        $language = mysqli_real_escape_string($conn, $_POST['language']);
        
        $sql = "INSERT INTO knowledge_base (category_id, question, answer, keywords, language) 
                VALUES ($category_id, '$question', '$answer', '$keywords', '$language')";
        if (mysqli_query($conn, $sql)) {
            $knowledge_id = mysqli_insert_id($conn);
            $checkTable = mysqli_query($conn, "SHOW TABLES LIKE 'ai_training_log'");
            if (mysqli_num_rows($checkTable) > 0) {
                mysqli_query($conn, "INSERT INTO ai_training_log (action, item_type, item_id, performed_by) VALUES ('add', 'knowledge', $knowledge_id, {$_SESSION['user_id']})");
            }
            $msg = "Knowledge added successfully";
        } else {
            $error = mysqli_error($conn);
        }
    }
    
    // Update Knowledge
    if (isset($_POST['update_knowledge'])) {
        $id = (int)$_POST['id'];
        $category_id = (int)$_POST['category_id'];
        $question = mysqli_real_escape_string($conn, $_POST['question']);
        $answer = mysqli_real_escape_string($conn, $_POST['answer']);
        $keywords = mysqli_real_escape_string($conn, $_POST['keywords']);
        $language = mysqli_real_escape_string($conn, $_POST['language']);
        
        $sql = "UPDATE knowledge_base SET category_id=$category_id, question='$question', answer='$answer', keywords='$keywords', language='$language' WHERE id=$id";
        if (mysqli_query($conn, $sql)) {
            $checkTable = mysqli_query($conn, "SHOW TABLES LIKE 'ai_training_log'");
            if (mysqli_num_rows($checkTable) > 0) {
                mysqli_query($conn, "INSERT INTO ai_training_log (action, item_type, item_id, performed_by) VALUES ('edit', 'knowledge', $id, {$_SESSION['user_id']})");
            }
            $msg = "Knowledge updated successfully";
        } else {
            $error = mysqli_error($conn);
        }
    }
    
    // Delete Knowledge
    if (isset($_POST['delete_knowledge'])) {
        $id = (int)$_POST['id'];
        $sql = "DELETE FROM knowledge_base WHERE id = $id";
        if (mysqli_query($conn, $sql)) {
            $checkTable = mysqli_query($conn, "SHOW TABLES LIKE 'ai_training_log'");
            if (mysqli_num_rows($checkTable) > 0) {
                mysqli_query($conn, "INSERT INTO ai_training_log (action, item_type, item_id, performed_by) VALUES ('delete', 'knowledge', $id, {$_SESSION['user_id']})");
            }
            $msg = "Knowledge deleted successfully";
        } else {
            $error = mysqli_error($conn);
        }
    }
    
    // ============= GREETING OPERATIONS =============
    
    // Add Greeting
    if (isset($_POST['add_greeting'])) {
        $question = mysqli_real_escape_string($conn, $_POST['question']);
        $answer = mysqli_real_escape_string($conn, $_POST['answer']);
        $keywords = mysqli_real_escape_string($conn, $_POST['keywords']);
        $language = mysqli_real_escape_string($conn, $_POST['language']);
        
        $sql = "INSERT INTO knowledge_base (category_id, question, answer, keywords, language) 
                VALUES (NULL, '$question', '$answer', '$keywords', '$language')";
        if (mysqli_query($conn, $sql)) {
            $greeting_id = mysqli_insert_id($conn);
            $checkTable = mysqli_query($conn, "SHOW TABLES LIKE 'ai_training_log'");
            if (mysqli_num_rows($checkTable) > 0) {
                mysqli_query($conn, "INSERT INTO ai_training_log (action, item_type, item_id, performed_by) VALUES ('add', 'greeting', $greeting_id, {$_SESSION['user_id']})");
            }
            $msg = "Greeting added successfully";
        } else {
            $error = mysqli_error($conn);
        }
    }
    
    // Update Greeting
    if (isset($_POST['update_greeting'])) {
        $id = (int)$_POST['id'];
        $question = mysqli_real_escape_string($conn, $_POST['question']);
        $answer = mysqli_real_escape_string($conn, $_POST['answer']);
        $keywords = mysqli_real_escape_string($conn, $_POST['keywords']);
        $language = mysqli_real_escape_string($conn, $_POST['language']);
        
        $sql = "UPDATE knowledge_base SET question='$question', answer='$answer', keywords='$keywords', language='$language' WHERE id=$id";
        if (mysqli_query($conn, $sql)) {
            $checkTable = mysqli_query($conn, "SHOW TABLES LIKE 'ai_training_log'");
            if (mysqli_num_rows($checkTable) > 0) {
                mysqli_query($conn, "INSERT INTO ai_training_log (action, item_type, item_id, performed_by) VALUES ('edit', 'greeting', $id, {$_SESSION['user_id']})");
            }
            $msg = "Greeting updated successfully";
        } else {
            $error = mysqli_error($conn);
        }
    }
    
    // Delete Greeting
    if (isset($_POST['delete_greeting'])) {
        $id = (int)$_POST['id'];
        $sql = "DELETE FROM knowledge_base WHERE id = $id";
        if (mysqli_query($conn, $sql)) {
            $checkTable = mysqli_query($conn, "SHOW TABLES LIKE 'ai_training_log'");
            if (mysqli_num_rows($checkTable) > 0) {
                mysqli_query($conn, "INSERT INTO ai_training_log (action, item_type, item_id, performed_by) VALUES ('delete', 'greeting', $id, {$_SESSION['user_id']})");
            }
            $msg = "Greeting deleted successfully";
        } else {
            $error = mysqli_error($conn);
        }
    }
    
    // Use JavaScript redirect instead of header()
    if (!empty($msg) || !empty($error)) {
        echo "<script>";
        if (!empty($msg)) {
            echo "window.location.href = 'knowledge_admin.php?msg=" . urlencode($msg) . "';";
        } else {
            echo "window.location.href = 'knowledge_admin.php?error=" . urlencode($error) . "';";
        }
        echo "</script>";
        exit;
    }
}

// Get all dropdown groups
$dropdownGroups = mysqli_query($conn, "SELECT * FROM dropdown_groups ORDER BY display_order, name");

// Get all categories with dropdown group info
$categories = mysqli_query($conn, "
    SELECT c.*, dg.name as group_name, dg.icon as group_icon 
    FROM categories c 
    LEFT JOIN dropdown_groups dg ON c.dropdown_group_id = dg.id 
    ORDER BY 
        COALESCE(dg.display_order, 999), 
        dg.name, 
        c.name
");

// Get all knowledge with category names (excluding greetings)
$knowledge = mysqli_query($conn, "
    SELECT k.*, c.name as category_name, c.icon 
    FROM knowledge_base k 
    LEFT JOIN categories c ON k.category_id = c.id 
    WHERE k.category_id IS NOT NULL
    ORDER BY k.created_at DESC
");

// Get all greetings (knowledge with no category)
$greetings = mysqli_query($conn, "
    SELECT * FROM knowledge_base 
    WHERE category_id IS NULL 
    ORDER BY language, question
");

// Get stats
$total_categories = mysqli_query($conn, "SELECT COUNT(*) as count FROM categories")->fetch_assoc()['count'] ?? 0;
$total_knowledge = mysqli_query($conn, "SELECT COUNT(*) as count FROM knowledge_base WHERE category_id IS NOT NULL")->fetch_assoc()['count'] ?? 0;
$total_greetings = mysqli_query($conn, "SELECT COUNT(*) as count FROM knowledge_base WHERE category_id IS NULL")->fetch_assoc()['count'] ?? 0;
$total_dropdowns = mysqli_query($conn, "SELECT COUNT(*) as count FROM dropdown_groups")->fetch_assoc()['count'] ?? 0;
$en_categories = mysqli_query($conn, "SELECT COUNT(*) as count FROM categories WHERE language='en'")->fetch_assoc()['count'] ?? 0;
$tl_categories = mysqli_query($conn, "SELECT COUNT(*) as count FROM categories WHERE language='tl'")->fetch_assoc()['count'] ?? 0;
$en_knowledge = mysqli_query($conn, "SELECT COUNT(*) as count FROM knowledge_base WHERE language='en' AND category_id IS NOT NULL")->fetch_assoc()['count'] ?? 0;
$tl_knowledge = mysqli_query($conn, "SELECT COUNT(*) as count FROM knowledge_base WHERE language='tl' AND category_id IS NOT NULL")->fetch_assoc()['count'] ?? 0;
$en_greetings = mysqli_query($conn, "SELECT COUNT(*) as count FROM knowledge_base WHERE language='en' AND category_id IS NULL")->fetch_assoc()['count'] ?? 0;
$tl_greetings = mysqli_query($conn, "SELECT COUNT(*) as count FROM knowledge_base WHERE language='tl' AND category_id IS NULL")->fetch_assoc()['count'] ?? 0;

// Get recent logs if table exists
$logs = [];
$checkLogTable = mysqli_query($conn, "SHOW TABLES LIKE 'ai_training_log'");
if (mysqli_num_rows($checkLogTable) > 0) {
    $logsQuery = mysqli_query($conn, "
        SELECT l.*, u.fname, u.lname 
        FROM ai_training_log l 
        LEFT JOIN usr_tbl u ON l.performed_by = u.id 
        ORDER BY l.performed_at DESC 
        LIMIT 10
    ");
    if ($logsQuery) {
        while ($row = mysqli_fetch_assoc($logsQuery)) {
            $logs[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Knowledge Management - Power2Connect Admin</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        .stat-card {
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .knowledge-card {
            transition: all 0.3s ease;
        }
        .knowledge-card:hover {
            transform: translateX(5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        
        .modal-content.large {
            max-width: 700px;
        }
        
        .tab-button {
            transition: all 0.3s ease;
        }
        .tab-button.active {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: white;
        }
        
        .group-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            color: white;
            margin-left: 5px;
        }
    </style>
</head>
<body class="bg-[#9BB4C0]">
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        
        <!-- Header with Stats -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Knowledge Management</h1>
                    <p class="text-gray-600 mt-2">Manage your AI knowledge base, dropdown groups, categories, and greetings</p>
                </div>
                <div class="flex gap-3">
                    <button onclick="openDropdownGroupModal()" class="bg-purple-600 text-white px-6 py-3 rounded-xl hover:bg-purple-700 transition-all flex items-center gap-2 shadow-md">
                        <i class="fas fa-layer-group"></i> Add Dropdown
                    </button>
                    <button onclick="openCategoryModal()" class="bg-blue-600 text-white px-6 py-3 rounded-xl hover:bg-blue-700 transition-all flex items-center gap-2 shadow-md">
                        <i class="fas fa-plus"></i> Add Category
                    </button>
                    <button onclick="openKnowledgeModal()" class="bg-green-600 text-white px-6 py-3 rounded-xl hover:bg-green-700 transition-all flex items-center gap-2 shadow-md">
                        <i class="fas fa-book"></i> Add Knowledge
                    </button>
                    <button onclick="openGreetingModal()" class="bg-purple-600 text-white px-6 py-3 rounded-xl hover:bg-purple-700 transition-all flex items-center gap-2 shadow-md">
                        <i class="fas fa-hand-sparkles"></i> Add Greeting
                    </button>
                </div>
            </div>
            
            <!-- Success/Error Messages -->
            <?php if (isset($_GET['msg'])): ?>
            <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg flex items-center gap-2">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($_GET['msg']) ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
            <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-lg flex items-center gap-2">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($_GET['error']) ?>
            </div>
            <?php endif; ?>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="stat-card bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-4 text-white shadow-lg">
                    <p class="text-purple-100 text-xs">Dropdown Groups</p>
                    <p class="text-2xl font-bold"><?= $total_dropdowns ?></p>
                </div>
                <div class="stat-card bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white shadow-lg">
                    <p class="text-blue-100 text-xs">Categories</p>
                    <p class="text-2xl font-bold"><?= $total_categories ?></p>
                    <div class="flex gap-2 mt-2 text-xs">
                        <span>EN: <?= $en_categories ?></span>
                        <span>TL: <?= $tl_categories ?></span>
                    </div>
                </div>
                <div class="stat-card bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-4 text-white shadow-lg">
                    <p class="text-green-100 text-xs">Knowledge Items</p>
                    <p class="text-2xl font-bold"><?= $total_knowledge ?></p>
                    <div class="flex gap-2 mt-2 text-xs">
                        <span>EN: <?= $en_knowledge ?></span>
                        <span>TL: <?= $tl_knowledge ?></span>
                    </div>
                </div>
                <div class="stat-card bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-4 text-white shadow-lg">
                    <p class="text-purple-100 text-xs">Greetings</p>
                    <p class="text-2xl font-bold"><?= $total_greetings ?></p>
                    <div class="flex gap-2 mt-2 text-xs">
                        <span>EN: <?= $en_greetings ?></span>
                        <span>TL: <?= $tl_greetings ?></span>
                    </div>
                </div>
                <div class="stat-card bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl p-4 text-white shadow-lg">
                    <p class="text-amber-100 text-xs">Total Items</p>
                    <p class="text-2xl font-bold"><?= $total_categories + $total_knowledge + $total_greetings + $total_dropdowns ?></p>
                </div>
            </div>
        </div>

        <!-- Dropdown Groups Section -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-layer-group text-purple-600"></i>
                    Dropdown Groups (Create unlimited dropdowns!)
                </h2>
                <button onclick="openDropdownGroupModal()" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-all flex items-center gap-2 text-sm">
                    <i class="fas fa-plus"></i> New Dropdown Group
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <?php if (mysqli_num_rows($dropdownGroups) == 0): ?>
                <div class="col-span-full text-center py-8 text-gray-500">
                    <i class="fas fa-layer-group text-4xl mb-3 opacity-50"></i>
                    <p>No dropdown groups yet. Click "New Dropdown Group" to create your first dropdown!</p>
                </div>
                <?php else: ?>
                <?php while($group = mysqli_fetch_assoc($dropdownGroups)): ?>
                <div class="border border-gray-200 rounded-xl p-4 hover:shadow-lg transition-all bg-white">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <span class="text-3xl"><?= htmlspecialchars($group['icon']) ?></span>
                            <div>
                                <h3 class="font-bold text-gray-800"><?= htmlspecialchars($group['name']) ?></h3>
                                <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full <?= $group['language'] == 'en' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' ?> mt-1">
                                    <?= strtoupper($group['language']) ?>
                                </span>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="editDropdownGroup(<?= htmlspecialchars(json_encode($group)) ?>)" class="text-blue-600 hover:text-blue-800 bg-blue-50 p-2 rounded-lg">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteDropdownGroup(<?= $group['id'] ?>)" class="text-red-600 hover:text-red-800 bg-red-50 p-2 rounded-lg">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mt-2 text-xs text-gray-500 flex items-center gap-2">
                        <span class="inline-block w-3 h-3 rounded-full" style="background: <?= $group['color'] ?>"></span>
                        <span>Order: <?= $group['display_order'] ?></span>
                    </div>
                </div>
                <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="flex gap-2 mb-6 overflow-x-auto pb-2">
            <button onclick="showTab('categories')" id="tab-categories" class="tab-button active px-6 py-3 rounded-xl font-medium transition-all bg-white shadow-md whitespace-nowrap">
                <i class="fas fa-folder-tree mr-2"></i> Categories
            </button>
            <button onclick="showTab('knowledge')" id="tab-knowledge" class="tab-button px-6 py-3 rounded-xl font-medium transition-all bg-white shadow-md whitespace-nowrap">
                <i class="fas fa-database mr-2"></i> Knowledge Base
            </button>
            <button onclick="showTab('greetings')" id="tab-greetings" class="tab-button px-6 py-3 rounded-xl font-medium transition-all bg-white shadow-md whitespace-nowrap">
                <i class="fas fa-hand-sparkles mr-2"></i> Greetings
            </button>
        </div>

        <!-- Categories Tab -->
        <div id="categories-tab" class="tab-content">
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                    <i class="fas fa-folder-tree text-blue-600"></i>
                    Categories
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php if (mysqli_num_rows($categories) == 0): ?>
                    <div class="col-span-full text-center py-12 text-gray-500">
                        <i class="fas fa-folder-open text-4xl mb-3 opacity-50"></i>
                        <p>No categories yet. Click "Add Category" to create one.</p>
                    </div>
                    <?php else: ?>
                    <?php while($cat = mysqli_fetch_assoc($categories)): ?>
                    <div class="border border-gray-200 rounded-xl p-5 hover:shadow-lg transition-all bg-white">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <span class="text-3xl"><?= htmlspecialchars($cat['icon']) ?></span>
                                <div>
                                    <h3 class="font-bold text-gray-800"><?= htmlspecialchars($cat['name']) ?></h3>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full <?= $cat['language'] == 'en' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' ?>">
                                            <?= strtoupper($cat['language']) ?>
                                        </span>
                                        <?php if ($cat['group_name']): ?>
                                        <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-700">
                                            <?= htmlspecialchars($cat['group_icon']) ?> <?= htmlspecialchars($cat['group_name']) ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button onclick="editCategory(<?= $cat['id'] ?>, '<?= htmlspecialchars(addslashes($cat['name'])) ?>', '<?= htmlspecialchars(addslashes($cat['icon'])) ?>', '<?= htmlspecialchars(addslashes($cat['description'] ?? '')) ?>', '<?= $cat['language'] ?>', <?= $cat['dropdown_group_id'] ?? 'null' ?>)" class="text-blue-600 hover:text-blue-800 bg-blue-50 p-2 rounded-lg">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this category? All knowledge in this category will also be deleted.')">
                                    <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                    <button type="submit" name="delete_category" class="text-red-600 hover:text-red-800 bg-red-50 p-2 rounded-lg">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php if ($cat['description']): ?>
                        <p class="text-sm text-gray-600 mt-3"><?= htmlspecialchars($cat['description']) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Knowledge Tab -->
        <div id="knowledge-tab" class="tab-content hidden">
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                    <i class="fas fa-database text-green-600"></i>
                    Knowledge Base
                </h2>
                
                <div class="space-y-4">
                    <?php if (mysqli_num_rows($knowledge) == 0): ?>
                    <div class="text-center py-12 text-gray-500">
                        <i class="fas fa-database text-4xl mb-3 opacity-50"></i>
                        <p>No knowledge items yet. Click "Add Knowledge" to create one.</p>
                    </div>
                    <?php else: ?>
                    <?php while($item = mysqli_fetch_assoc($knowledge)): ?>
                    <div class="knowledge-card border border-gray-200 rounded-xl p-6 hover:shadow-lg transition-all bg-white">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-3">
                                    <span class="text-2xl"><?= htmlspecialchars($item['icon'] ?? '📌') ?></span>
                                    <span class="font-semibold text-gray-700"><?= htmlspecialchars($item['category_name'] ?? 'Uncategorized') ?></span>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $item['language'] == 'en' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' ?>">
                                        <?= strtoupper($item['language']) ?>
                                    </span>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 mb-2"><?= htmlspecialchars($item['question']) ?></h3>
                                <div class="text-gray-600 text-sm mb-3 line-clamp-2"><?= nl2br(htmlspecialchars(substr($item['answer'], 0, 200))) ?>...</div>
                                <?php if ($item['keywords']): ?>
                                <div class="flex items-center gap-2 text-xs text-gray-400">
                                    <i class="fas fa-tags"></i>
                                    <span><?= htmlspecialchars($item['keywords']) ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex gap-2 ml-4">
                                <button onclick="editKnowledge(<?= $item['id'] ?>, <?= $item['category_id'] ?>, '<?= htmlspecialchars(addslashes($item['question'])) ?>', '<?= htmlspecialchars(addslashes($item['answer'])) ?>', '<?= htmlspecialchars(addslashes($item['keywords'] ?? '')) ?>', '<?= $item['language'] ?>')" class="text-blue-600 hover:text-blue-800 bg-blue-50 p-2 rounded-lg">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this knowledge item?')">
                                    <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                    <button type="submit" name="delete_knowledge" class="text-red-600 hover:text-red-800 bg-red-50 p-2 rounded-lg">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Greetings Tab -->
        <div id="greetings-tab" class="tab-content hidden">
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                    <i class="fas fa-hand-sparkles text-purple-600"></i>
                    Greetings & Common Phrases
                </h2>
                
                <div class="space-y-4">
                    <?php if (mysqli_num_rows($greetings) == 0): ?>
                    <div class="text-center py-12 text-gray-500">
                        <i class="fas fa-smile text-4xl mb-3 opacity-50"></i>
                        <p>No greetings yet. Click "Add Greeting" to create one.</p>
                    </div>
                    <?php else: ?>
                    <?php while($greeting = mysqli_fetch_assoc($greetings)): ?>
                    <div class="border border-gray-200 rounded-xl p-6 hover:shadow-lg transition-all bg-white">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="text-2xl">💬</span>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $greeting['language'] == 'en' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' ?>">
                                        <?= strtoupper($greeting['language']) ?>
                                    </span>
                                </div>
                                <div class="mb-3">
                                    <span class="text-sm text-gray-500">When user says:</span>
                                    <p class="font-bold text-gray-900 text-lg"><?= htmlspecialchars($greeting['question']) ?></p>
                                </div>
                                <div class="mb-2">
                                    <span class="text-sm text-gray-500">Bot responds:</span>
                                    <p class="text-gray-700"><?= nl2br(htmlspecialchars($greeting['answer'])) ?></p>
                                </div>
                                <?php if ($greeting['keywords']): ?>
                                <div class="flex items-center gap-2 text-xs text-gray-400 mt-2">
                                    <i class="fas fa-tags"></i>
                                    <span><?= htmlspecialchars($greeting['keywords']) ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex gap-2 ml-4">
                                <button onclick="editGreeting(<?= $greeting['id'] ?>, '<?= htmlspecialchars(addslashes($greeting['question'])) ?>', '<?= htmlspecialchars(addslashes($greeting['answer'])) ?>', '<?= htmlspecialchars(addslashes($greeting['keywords'] ?? '')) ?>', '<?= $greeting['language'] ?>')" class="text-blue-600 hover:text-blue-800 bg-blue-50 p-2 rounded-lg">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this greeting?')">
                                    <input type="hidden" name="id" value="<?= $greeting['id'] ?>">
                                    <button type="submit" name="delete_greeting" class="text-red-600 hover:text-red-800 bg-red-50 p-2 rounded-lg">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Training Logs -->
        <?php if (!empty($logs)): ?>
        <div class="bg-white rounded-2xl shadow-lg p-8 mt-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                <i class="fas fa-history text-purple-600"></i>
                Recent Activity Log
            </h2>
            
            <div class="space-y-2">
                <?php foreach($logs as $log): ?>
                <div class="flex items-center gap-3 py-2 border-b border-gray-100 last:border-0">
                    <?php
                    $icon = match($log['action']) {
                        'add' => 'fa-plus-circle text-green-600',
                        'edit' => 'fa-edit text-blue-600',
                        'delete' => 'fa-trash text-red-600',
                        'train' => 'fa-robot text-purple-600',
                        default => 'fa-circle text-gray-400'
                    };
                    ?>
                    <i class="fas <?= $icon ?>"></i>
                    <span class="flex-1 text-sm">
                        <span class="font-semibold"><?= ucfirst($log['action']) ?></span>
                        <span class="text-gray-600"><?= $log['item_type'] ?></span>
                        <?php if ($log['fname']): ?>
                        <span class="text-gray-500">by <?= htmlspecialchars($log['fname'] . ' ' . $log['lname']) ?></span>
                        <?php endif; ?>
                    </span>
                    <span class="text-xs text-gray-400"><?= date('M d, Y h:i A', strtotime($log['performed_at'])) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Dropdown Group Modal -->
    <div id="dropdownGroupModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-6">
                <h2 id="dropdownGroupModalTitle" class="text-2xl font-bold text-gray-800">Add Dropdown Group</h2>
                <button type="button" onclick="closeModal('dropdownGroupModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST" id="dropdownGroupForm">
                <input type="hidden" name="id" id="dropdownGroupId">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Group Name</label>
                    <input type="text" name="name" id="dropdownGroupName" required 
                           placeholder="e.g., Promos, Support, Products"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Icon (emoji)</label>
                    <input type="text" name="icon" id="dropdownGroupIcon" required 
                           placeholder="🏷️"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Color Gradient</label>
                    <select name="color" id="dropdownGroupColor" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500">
                        <option value="linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)">Blue</option>
                        <option value="linear-gradient(135deg, #10b981 0%, #059669 100%)">Green</option>
                        <option value="linear-gradient(135deg, #f97316 0%, #ea580c 100%)">Orange</option>
                        <option value="linear-gradient(135deg, #ec4899 0%, #db2777 100%)">Pink</option>
                        <option value="linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%)">Purple</option>
                        <option value="linear-gradient(135deg, #ef4444 0%, #dc2626 100%)">Red</option>
                        <option value="linear-gradient(135deg, #64748b 0%, #475569 100%)">Gray</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Display Order</label>
                    <input type="number" name="display_order" id="dropdownGroupOrder" value="0" min="0"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Lower numbers appear first</p>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Language</label>
                    <select name="language" id="dropdownGroupLanguage" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500">
                        <option value="en">English</option>
                        <option value="tl">Tagalog</option>
                    </select>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeModal('dropdownGroupModal')" 
                            class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" name="add_dropdown_group" id="dropdownGroupSubmitBtn" class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors flex items-center gap-2">
                        <i class="fas fa-plus"></i> Add Group
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Category Modal -->
    <div id="categoryModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-6">
                <h2 id="categoryModalTitle" class="text-2xl font-bold text-gray-800">Add Category</h2>
                <button type="button" onclick="closeModal('categoryModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST" id="categoryForm">
                <input type="hidden" name="id" id="categoryId">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input type="text" name="name" id="categoryName" required 
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Icon (emoji)</label>
                    <input type="text" name="icon" id="categoryIcon" required 
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Use emoji like ☀️, 🌐, 📋, 🔧</p>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Language</label>
                    <select name="language" id="categoryLanguage" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="en">English</option>
                        <option value="tl">Tagalog</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dropdown Group</label>
                    <select name="dropdown_group_id" id="categoryDropdownGroup" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Auto-assign based on keywords --</option>
                        <?php 
                        $groups = mysqli_query($conn, "SELECT * FROM dropdown_groups ORDER BY display_order, name");
                        while($group = mysqli_fetch_assoc($groups)): 
                        ?>
                        <option value="<?= $group['id'] ?>">
                            <?= htmlspecialchars($group['icon']) ?> <?= htmlspecialchars($group['name']) ?> (<?= strtoupper($group['language']) ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Select a specific dropdown or leave empty for auto-assignment</p>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description (optional)</label>
                    <input type="text" name="description" id="categoryDescription" 
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeModal('categoryModal')" 
                            class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" name="add_category" id="categorySubmitBtn" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2">
                        <i class="fas fa-plus"></i> Add Category
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Knowledge Modal -->
    <div id="knowledgeModal" class="modal">
        <div class="modal-content large">
            <div class="flex justify-between items-center mb-6">
                <h2 id="knowledgeModalTitle" class="text-2xl font-bold text-gray-800">Add Knowledge</h2>
                <button type="button" onclick="closeModal('knowledgeModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST" id="knowledgeForm">
                <input type="hidden" name="id" id="knowledgeId">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select name="category_id" id="knowledgeCategory" required 
                                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500">
                            <option value="">Select Category</option>
                            <?php 
                            mysqli_data_seek($categories, 0);
                            while($cat = mysqli_fetch_assoc($categories)): 
                            ?>
                            <option value="<?= $cat['id'] ?>">
                                <?= htmlspecialchars($cat['icon']) ?> <?= htmlspecialchars($cat['name']) ?> <?= $cat['group_name'] ? '(' . $cat['group_name'] . ')' : '' ?> (<?= strtoupper($cat['language']) ?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Language</label>
                        <select name="language" id="knowledgeLanguage" required 
                                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500">
                            <option value="en">English</option>
                            <option value="tl">Tagalog</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Question</label>
                    <input type="text" name="question" id="knowledgeQuestion" required 
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Answer</label>
                    <textarea name="answer" id="knowledgeAnswer" rows="5" required 
                              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500"></textarea>
                    <p class="text-xs text-gray-500 mt-1">You can use **bold** text and bullet points with *</p>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Keywords (comma separated)</label>
                    <input type="text" name="keywords" id="knowledgeKeywords" 
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500">
                    <p class="text-xs text-gray-500 mt-1">Example: billing, payment, invoice, bayad</p>
                </div>
                
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" onclick="closeModal('knowledgeModal')" 
                            class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" name="add_knowledge" id="knowledgeSubmitBtn" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2">
                        <i class="fas fa-plus"></i> Add Knowledge
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Greeting Modal -->
    <div id="greetingModal" class="modal">
        <div class="modal-content large">
            <div class="flex justify-between items-center mb-6">
                <h2 id="greetingModalTitle" class="text-2xl font-bold text-gray-800">Add Greeting</h2>
                <button type="button" onclick="closeModal('greetingModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST" id="greetingForm">
                <input type="hidden" name="id" id="greetingId">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">When user says:</label>
                    <input type="text" name="question" id="greetingQuestion" required 
                           placeholder="e.g., hello, hi, good morning, kamusta"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bot response:</label>
                    <textarea name="answer" id="greetingAnswer" rows="3" required 
                              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent"></textarea>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Keywords (comma separated)</label>
                    <input type="text" name="keywords" id="greetingKeywords" 
                           placeholder="hello, hi, hey, kamusta"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Language</label>
                    <select name="language" id="greetingLanguage" required 
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500">
                        <option value="en">English</option>
                        <option value="tl">Tagalog</option>
                    </select>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeModal('greetingModal')" 
                            class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" name="add_greeting" id="greetingSubmitBtn" class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors flex items-center gap-2">
                        <i class="fas fa-plus"></i> Add Greeting
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Tab Functions
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.add('hidden');
            });
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            document.getElementById(tabName + '-tab').classList.remove('hidden');
            document.getElementById('tab-' + tabName).classList.add('active');
        }
        
        // Modal Functions
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
            document.body.style.overflow = 'auto';
            resetForms();
        }
        
        function resetForms() {
            document.getElementById('categoryForm')?.reset();
            document.getElementById('knowledgeForm')?.reset();
            document.getElementById('greetingForm')?.reset();
            document.getElementById('dropdownGroupForm')?.reset();
            
            document.getElementById('categoryId').value = '';
            document.getElementById('knowledgeId').value = '';
            document.getElementById('greetingId').value = '';
            document.getElementById('dropdownGroupId').value = '';
            
            document.getElementById('categoryModalTitle').textContent = 'Add Category';
            document.getElementById('categorySubmitBtn').innerHTML = '<i class="fas fa-plus"></i> Add Category';
            document.getElementById('categorySubmitBtn').name = 'add_category';
            
            document.getElementById('knowledgeModalTitle').textContent = 'Add Knowledge';
            document.getElementById('knowledgeSubmitBtn').innerHTML = '<i class="fas fa-plus"></i> Add Knowledge';
            document.getElementById('knowledgeSubmitBtn').name = 'add_knowledge';
            
            document.getElementById('greetingModalTitle').textContent = 'Add Greeting';
            document.getElementById('greetingSubmitBtn').innerHTML = '<i class="fas fa-plus"></i> Add Greeting';
            document.getElementById('greetingSubmitBtn').name = 'add_greeting';
            
            document.getElementById('dropdownGroupModalTitle').textContent = 'Add Dropdown Group';
            document.getElementById('dropdownGroupSubmitBtn').innerHTML = '<i class="fas fa-plus"></i> Add Group';
            document.getElementById('dropdownGroupSubmitBtn').name = 'add_dropdown_group';
        }
        
        // Dropdown Group Functions
        function openDropdownGroupModal() {
            resetForms();
            openModal('dropdownGroupModal');
        }
        
        function editDropdownGroup(data) {
            document.getElementById('dropdownGroupId').value = data.id;
            document.getElementById('dropdownGroupName').value = data.name;
            document.getElementById('dropdownGroupIcon').value = data.icon;
            document.getElementById('dropdownGroupColor').value = data.color;
            document.getElementById('dropdownGroupOrder').value = data.display_order;
            document.getElementById('dropdownGroupLanguage').value = data.language;
            document.getElementById('dropdownGroupModalTitle').textContent = 'Edit Dropdown Group';
            document.getElementById('dropdownGroupSubmitBtn').innerHTML = '<i class="fas fa-save"></i> Update Group';
            document.getElementById('dropdownGroupSubmitBtn').name = 'update_dropdown_group';
            openModal('dropdownGroupModal');
        }
        
        function deleteDropdownGroup(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "Categories in this group will be moved to auto-assignment",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `<input type="hidden" name="id" value="${id}"><input type="hidden" name="delete_dropdown_group" value="1">`;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
        
        // Category Functions
        function openCategoryModal() {
            resetForms();
            openModal('categoryModal');
        }
        
        function editCategory(id, name, icon, description, language, dropdown_group_id) {
            document.getElementById('categoryId').value = id;
            document.getElementById('categoryName').value = name;
            document.getElementById('categoryIcon').value = icon;
            document.getElementById('categoryDescription').value = description || '';
            document.getElementById('categoryLanguage').value = language;
            if (dropdown_group_id) {
                document.getElementById('categoryDropdownGroup').value = dropdown_group_id;
            }
            document.getElementById('categoryModalTitle').textContent = 'Edit Category';
            document.getElementById('categorySubmitBtn').innerHTML = '<i class="fas fa-save"></i> Update Category';
            document.getElementById('categorySubmitBtn').name = 'update_category';
            openModal('categoryModal');
        }
        
        // Knowledge Functions
        function openKnowledgeModal() {
            resetForms();
            openModal('knowledgeModal');
        }
        
        function editKnowledge(id, category_id, question, answer, keywords, language) {
            document.getElementById('knowledgeId').value = id;
            document.getElementById('knowledgeCategory').value = category_id;
            document.getElementById('knowledgeQuestion').value = question;
            document.getElementById('knowledgeAnswer').value = answer;
            document.getElementById('knowledgeKeywords').value = keywords || '';
            document.getElementById('knowledgeLanguage').value = language;
            document.getElementById('knowledgeModalTitle').textContent = 'Edit Knowledge';
            document.getElementById('knowledgeSubmitBtn').innerHTML = '<i class="fas fa-save"></i> Update Knowledge';
            document.getElementById('knowledgeSubmitBtn').name = 'update_knowledge';
            openModal('knowledgeModal');
        }
        
        // Greeting Functions
        function openGreetingModal() {
            resetForms();
            openModal('greetingModal');
        }
        
        function editGreeting(id, question, answer, keywords, language) {
            document.getElementById('greetingId').value = id;
            document.getElementById('greetingQuestion').value = question;
            document.getElementById('greetingAnswer').value = answer;
            document.getElementById('greetingKeywords').value = keywords || '';
            document.getElementById('greetingLanguage').value = language;
            document.getElementById('greetingModalTitle').textContent = 'Edit Greeting';
            document.getElementById('greetingSubmitBtn').innerHTML = '<i class="fas fa-save"></i> Update Greeting';
            document.getElementById('greetingSubmitBtn').name = 'update_greeting';
            openModal('greetingModal');
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                closeModal('categoryModal');
                closeModal('knowledgeModal');
                closeModal('greetingModal');
                closeModal('dropdownGroupModal');
            }
        }
        
        // Auto-hide messages after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.bg-green-100, .bg-red-100');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
        
        // Auto-set language based on category selection
        document.getElementById('knowledgeCategory')?.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            if (selected.value) {
                const lang = selected.text.match(/\((EN|TL)\)/i);
                if (lang) {
                    document.getElementById('knowledgeLanguage').value = lang[1].toLowerCase();
                }
            }
        });
    </script>
</body>
</html>
<?php 
ob_end_flush();
$conn->close(); 
?>