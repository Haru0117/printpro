<?php
// ─────────────────────────────────────────────────────────────
//  PrintPro — Client Cancel Order
//  POST: { order_id, reason }
//  Only cancellable if status is Prepress or earlier
// ─────────────────────────────────────────────────────────────
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in again.']);
    exit;
}

$role = strtolower($_SESSION['role'] ?? '');
if ($role !== 'client') {
    echo json_encode(['success' => false, 'message' => 'Only clients can cancel orders.']);
    exit;
}

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
    // Ensure status column accepts any string value (convert ENUM → VARCHAR)
    $pdo->exec("ALTER TABLE orders MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'Proof Pending'");

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

    // Don't cancel immediately — set status to "Cancellation Requested" for admin approval
    $stmt = $pdo->prepare("UPDATE orders SET status = 'Cancellation Requested', rejection_reason = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$reason, $order_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Cancellation request submitted. An admin will review and approve it.'
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
