<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get POST parameters
$order_id = intval($_POST['order_id'] ?? 0);
$action = trim($_POST['action'] ?? '');

if (!$order_id || !in_array($action, ['approve', 'revise'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
    exit;
}

try {
    // 1. Verify that this order belongs to the logged-in client
    $stmt = $pdo->prepare("SELECT o.* FROM orders o JOIN clients c ON o.client_id = c.id WHERE o.id = ? AND c.user_id = ?");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found or access denied.']);
        exit;
    }

    if ($order['status'] !== 'Proof Pending Review') {
        echo json_encode(['success' => false, 'message' => 'This order is not currently in Proof Pending Review status.']);
        exit;
    }

    if ($action === 'approve') {
        // Approve proof -> sets status='Prepress'
        $stmt = $pdo->prepare("UPDATE orders SET status = 'Prepress', proof_reviewed_at = NOW() WHERE id = ?");
        $stmt->execute([$order_id]);
        echo json_encode(['success' => true, 'message' => 'Proof approved successfully.']);
    } else {
        // Request revision -> sets status='Proof Pending', clears proof_file
        $stmt = $pdo->prepare("UPDATE orders SET status = 'Proof Pending', proof_file = NULL, proof_reviewed_at = NOW() WHERE id = ?");
        $stmt->execute([$order_id]);
        echo json_encode(['success' => true, 'message' => 'Proof revision requested successfully.']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
