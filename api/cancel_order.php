<?php
// ─────────────────────────────────────────────────────────────
//  PrintPro — Client Cancel Order
//  POST: { order_id, reason }
//  Only cancellable if status is Prepress or earlier
// ─────────────────────────────────────────────────────────────
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../includes/db.php';

$order_id = intval($_POST['order_id'] ?? 0);
$reason   = trim($_POST['reason'] ?? '');

if (!$order_id) {
    echo json_encode(['success' => false, 'message' => 'Order ID required.']);
    exit;
}
if (empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'Please provide a cancellation reason.']);
    exit;
}

try {
    // Verify order belongs to this client and is cancellable
    $stmt = $pdo->prepare("
        SELECT o.id, o.status, o.client_id, o.total_amount
        FROM orders o
        JOIN clients c ON o.client_id = c.id
        WHERE o.id = ? AND c.user_id = ?
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found.']);
        exit;
    }

    $cancellable = ['Proof Pending', 'Proof Pending Review', 'Prepress'];
    if (!in_array($order['status'], $cancellable)) {
        echo json_encode(['success' => false, 'message' => 'This order cannot be cancelled — it is already in ' . $order['status'] . '.']);
        exit;
    }

    $pdo->beginTransaction();

    // Update order status
    $stmt = $pdo->prepare("UPDATE orders SET status = 'Cancelled', rejection_reason = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$reason, $order_id]);

    // Refund credits — use 'add' so walletLoad() picks it up
    $orderNum    = 'PPR-' . str_pad($order_id, 3, '0', STR_PAD_LEFT);
    $description = "Refund for cancelled order #{$orderNum}: {$reason}";

    $stmt = $pdo->prepare("
        INSERT INTO credit_transactions (client_id, transaction_type, amount, description, order_id)
        VALUES (?, 'add', ?, ?, ?)
    ");
    $stmt->execute([$order['client_id'], $order['total_amount'], $description, $order_id]);

    $stmt = $pdo->prepare("UPDATE client_credits SET balance = balance + ?, updated_at = NOW() WHERE client_id = ?");
    $stmt->execute([$order['total_amount'], $order['client_id']]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Order cancelled. ₱' . number_format($order['total_amount'], 2) . ' refunded to your wallet.'
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
