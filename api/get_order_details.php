<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$order_id = $_GET['id'] ?? '';

if (empty($order_id)) {
    echo json_encode(['success' => false, 'message' => 'Missing Order ID']);
    exit;
}

try {
    // Task 3: Fetch specific order data with client details
    $stmt = $pdo->prepare("SELECT 
                                o.*, 
                                COALESCE(c.business_name, u.name) as client_name,
                                u.email as client_email,
                                COALESCE(f.filename, 'Not Uploaded') as printed_file
                             FROM orders o 
                             LEFT JOIN clients c ON o.client_id = c.id 
                             LEFT JOIN users u ON c.user_id = u.id
                             LEFT JOIN files f ON o.id = f.order_id
                             WHERE o.id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (empty($order)) {
        echo json_encode(['success' => false, 'message' => 'No Data Found']);
    } else {
        echo json_encode(['success' => true, 'data' => $order]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
