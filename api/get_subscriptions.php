<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
header('Content-Type: application/json');
if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
try {
    // Task 1: Simplified query to ensure it works even if clients/subscriptions tables are missing
    $stmt = $pdo->query("SELECT 
                            id as user_id, name, email, subscription_plan as active_plan,
                            created_at, status as sub_status,
                            name as business_name
                         FROM users 
                         WHERE role = 'client'
                         ORDER BY created_at DESC");
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate 'Renews On' (30 days from created_at)
    foreach ($results as &$row) {
        $created = new DateTime($row['created_at']);
        $created->modify('+30 days');
        $row['renews_on'] = $created->format('Y-m-d');
    }

    if (empty($results)) {
        echo json_encode(['success' => true, 'data' => [], 'message' => 'No Data Found']);
    } else {
        echo json_encode(['success' => true, 'data' => $results]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
