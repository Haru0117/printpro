<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Require admin session
if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Admin access required.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$order_id = intval($_POST['order_id'] ?? 0);
$rejection_reason = trim($_POST['rejection_reason'] ?? '');

if (!$order_id) {
    echo json_encode(['success' => false, 'message' => 'Order ID is required.']);
    exit;
}

if (empty($rejection_reason)) {
    echo json_encode(['success' => false, 'message' => 'Rejection reason is required.']);
    exit;
}

try {
    // Check if order exists
    $stmt = $pdo->prepare("SELECT id, status FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found.']);
        exit;
    }

    // Update order status to Rejected and save rejection reason
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET status = 'Reprint', 
            rejection_reason = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$rejection_reason, $order_id]);

    // Refund credits to client
    $stmt = $pdo->prepare("
        SELECT client_id, total_amount 
        FROM orders 
        WHERE id = ?
    ");
    $stmt->execute([$order_id]);
    $order_data = $stmt->fetch();

    if ($order_data) {
        $client_id = $order_data['client_id'];
        $refund_amount = $order_data['total_amount'];

        // Add refund transaction
        $pdo->beginTransaction();

        try {
            // Insert credit transaction
            $stmt = $pdo->prepare("
                INSERT INTO credit_transactions (client_id, transaction_type, amount, description, order_id)
                VALUES (?, 'refund', ?, ?, ?)
            ");
            $stmt->execute([$client_id, $refund_amount, "Order #$order_id rejected - Refunded", $order_id]);

            // Update client balance
            $stmt = $pdo->prepare("
                UPDATE client_credits 
                SET balance = balance + ?, updated_at = NOW() 
                WHERE client_id = ?
            ");
            $stmt->execute([$refund_amount, $client_id]);

            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Failed to process refund: ' . $e->getMessage()]);
            exit;
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Order rejected successfully. Credits refunded to client.'
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
