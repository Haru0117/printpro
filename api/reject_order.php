<?php
// ─────────────────────────────────────────────────────────────
//  PrintPro — Admin Reject / Cancel Order
//  Sets status = 'Cancelled', logs reason, refunds credits
// ─────────────────────────────────────────────────────────────
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Admin access required.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$order_id         = intval($_POST['order_id'] ?? 0);
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
    $stmt = $pdo->prepare("SELECT id, status, client_id, total_amount, order_number FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found.']);
        exit;
    }

    $orderNum = $order['order_number'] ?: ('PPR-' . str_pad($order_id, 3, '0', STR_PAD_LEFT));

    $pdo->beginTransaction();

    // 1. Set status to Cancelled and store reason
    $stmt = $pdo->prepare("
        UPDATE orders
        SET status = 'Cancelled',
            rejection_reason = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$rejection_reason, $order_id]);

    // 2. Refund credits — use 'add' so walletLoad() picks it up
    $refundAmt   = floatval($order['total_amount']);
    $client_id   = $order['client_id'];
    $description = "Refund for cancelled order #{$orderNum}: {$rejection_reason}";

    $stmt = $pdo->prepare("
        INSERT INTO credit_transactions (client_id, transaction_type, amount, description, order_id)
        VALUES (?, 'add', ?, ?, ?)
    ");
    $stmt->execute([$client_id, $refundAmt, $description, $order_id]);

    // 3. Update wallet balance
    $stmt = $pdo->prepare("
        UPDATE client_credits
        SET balance = balance + ?, updated_at = NOW()
        WHERE client_id = ?
    ");
    $stmt->execute([$refundAmt, $client_id]);

    $pdo->commit();

    echo json_encode([
        'success'        => true,
        'message'        => "Order #{$orderNum} cancelled. ₱" . number_format($refundAmt, 2) . " refunded to client.",
        'refund_amount'  => $refundAmt,
        'order_number'   => $orderNum
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
