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
    $stmt = $pdo->query("SELECT u.id, u.name, u.email, u.role, u.status, u.created_at, COALESCE(c.business_name, '') as business_name FROM users u LEFT JOIN clients c ON u.id = c.user_id ORDER BY u.created_at DESC");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
