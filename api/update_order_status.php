<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Only Admins can update status
if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$order_id = intval($_POST['order_id'] ?? 0);
$status   = trim($_POST['status'] ?? '');

if (!$order_id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

try {
    // ── REJECTED LOCK: fetch current status before any mutation ──
    $chk = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
    $chk->execute([$order_id]);
    $current = $chk->fetch();

    if (!$current) {
        echo json_encode(['success' => false, 'message' => 'Order not found.']);
        exit;
    }

    if ($current['status'] === 'Rejected') {
        echo json_encode([
            'success' => false,
            'message' => 'This order can no longer be modified because it has been rejected.'
        ]);
        exit;
    }
    // ────────────────────────────────────────────────────────────

    $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $order_id]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>