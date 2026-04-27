<?php
// chat/knowledge_api.php
include("../components/db.php");

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$action = $_GET['action'] ?? '';

// Search knowledge base
if ($action === 'search') {
    $query = mysqli_real_escape_string($conn, $_GET['q'] ?? '');
    $lang = mysqli_real_escape_string($conn, $_GET['lang'] ?? 'en');
    
    if (empty($query)) {
        echo json_encode(['success' => false, 'error' => 'No query provided']);
        exit;
    }
    
    // Search knowledge base
    $sql = "SELECT k.*, c.name as category_name, c.icon 
            FROM knowledge_base k 
            LEFT JOIN categories c ON k.category_id = c.id 
            WHERE k.language = '$lang' 
            AND (k.question LIKE '%$query%' 
            OR k.answer LIKE '%$query%'
            OR k.keywords LIKE '%$query%')
            ORDER BY 
                CASE 
                    WHEN k.question LIKE '%$query%' THEN 1
                    WHEN k.keywords LIKE '%$query%' THEN 2
                    ELSE 3
                END,
                k.created_at DESC 
            LIMIT 5";
    
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
        exit;
    }
    
    $knowledge = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $knowledge[] = [
            'id' => $row['id'],
            'question' => $row['question'],
            'answer' => $row['answer'],
            'category' => $row['category_name'],
            'icon' => $row['icon'],
            'keywords' => $row['keywords']
        ];
    }
    
    echo json_encode(['success' => true, 'data' => $knowledge]);
    exit;
}

// Get categories with dropdown group info - FIXED VERSION
if ($action === 'categories') {
    $lang = mysqli_real_escape_string($conn, $_GET['lang'] ?? 'en');
    
    // UPDATED QUERY: Include dropdown group information
    $sql = "SELECT 
                c.*, 
                dg.id as dropdown_group_id,
                dg.name as group_name, 
                dg.icon as group_icon, 
                dg.color as group_color,
                dg.display_order as group_order
            FROM categories c 
            LEFT JOIN dropdown_groups dg ON c.dropdown_group_id = dg.id 
            WHERE c.language = '$lang' 
            ORDER BY 
                COALESCE(dg.display_order, 999), 
                dg.name, 
                c.name";
    
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
        exit;
    }
    
    $categories = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'icon' => $row['icon'],
            'description' => $row['description'],
            'language' => $row['language'],
            'dropdown_group_id' => $row['dropdown_group_id'],
            'group_name' => $row['group_name'],
            'group_icon' => $row['group_icon'],
            'group_color' => $row['group_color'],
            'group_order' => $row['group_order']
        ];
    }
    
    echo json_encode(['success' => true, 'data' => $categories]);
    exit;
}

// NEW: Get dropdown groups only
if ($action === 'dropdown_groups') {
    $lang = mysqli_real_escape_string($conn, $_GET['lang'] ?? 'en');
    
    $sql = "SELECT * FROM dropdown_groups WHERE language = '$lang' AND is_active = 1 ORDER BY display_order, name";
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
        exit;
    }
    
    $groups = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $groups[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'icon' => $row['icon'],
            'color' => $row['color'],
            'display_order' => $row['display_order']
        ];
    }
    
    echo json_encode(['success' => true, 'data' => $groups]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
?>