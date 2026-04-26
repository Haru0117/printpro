<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

try {
    if ($role === 'admin') {
        // Admins see all orders with user info
        $stmt = $pdo->query("SELECT o.*, u.name as client_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC");
    } else {
        // Clients see only their own orders
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
    }
    
    $orders = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $orders]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
