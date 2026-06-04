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
    // Try with credit tables first, fallback to simple query if they don't exist
    try {
        $stmt = $pdo->query("SELECT 
                                u.id as user_id, u.name, u.email, u.subscription_plan as active_plan,
                                u.created_at, u.status as sub_status,
                                COALESCE(c.business_name, '') as business_name
                             FROM users u
                             LEFT JOIN clients c ON u.id = c.user_id
                             WHERE u.role = 'client'
                             ORDER BY u.created_at DESC");
    } catch (PDOException $e) {
        $stmt = $pdo->query("SELECT 
                                id as user_id, name, email, subscription_plan as active_plan,
                                created_at, status as sub_status,
                                name as business_name
                             FROM users 
                             WHERE role = 'client'
                             ORDER BY created_at DESC");
    }
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $plan_limits = [
        'free'     => 0,
        'pro'      => 25000,
        'premium'  => 75000,
        'premium+' => PHP_INT_MAX,
    ];

    foreach ($results as &$row) {
        $created = new DateTime($row['created_at']);
        $created->modify('+30 days');
        $row['renews_on'] = $created->format('Y-m-d');

        $plan = strtolower($row['active_plan'] ?? 'free');
        $limit = $plan_limits[$plan] ?? 0;
        $row['monthly_limit'] = $limit >= PHP_INT_MAX ? null : $limit;

        $monthly_used = 0;
        if ($limit < PHP_INT_MAX && $limit > 0) {
            try {
                $stmt2 = $pdo->prepare("
                    SELECT COALESCE(SUM(ct.amount), 0)
                    FROM credit_transactions ct
                    JOIN clients c ON ct.client_id = c.id
                    WHERE c.user_id = ?
                      AND ct.transaction_type = 'deduct'
                      AND DATE_FORMAT(ct.created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
                ");
                $stmt2->execute([$row['user_id']]);
                $monthly_used = floatval($stmt2->fetchColumn());
            } catch (Exception $e) {}
        }
        $row['monthly_used'] = $monthly_used;
    }

    if (empty($results)) {
        echo json_encode(['success' => true, 'data' => [], 'message' => 'No Data Found']);
    } else {
        echo json_encode(['success' => true, 'data' => $results]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
