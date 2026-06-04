<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$ids = $input['ids'] ?? [];

if (empty($ids) || !is_array($ids)) {
    echo json_encode(['success' => false, 'message' => 'No user IDs provided']);
    exit;
}

// Sanitize — ensure all values are integers
$ids = array_map('intval', $ids);
$ids = array_filter($ids, fn($id) => $id > 0);

// Prevent deleting yourself
$ids = array_filter($ids, fn($id) => $id != $_SESSION['user_id']);

if (empty($ids)) {
    echo json_encode(['success' => false, 'message' => 'No valid users to delete (cannot delete yourself)']);
    exit;
}

try {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $pdo->beginTransaction();

    // Get client_ids for these users
    $stmt = $pdo->prepare("SELECT id, user_id FROM clients WHERE user_id IN ($placeholders)");
    $stmt->execute($ids);
    $client_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $client_ids = array_map('intval', array_column($client_rows, 'id'));

    if (!empty($client_ids)) {
        $c_placeholders = implode(',', array_fill(0, count($client_ids), '?'));

        // Delete order status history
        $stmt = $pdo->prepare("DELETE FROM order_status_history WHERE order_id IN (SELECT id FROM orders WHERE client_id IN ($c_placeholders))");
        $stmt->execute($client_ids);

        // Delete orders
        $stmt = $pdo->prepare("DELETE FROM orders WHERE client_id IN ($c_placeholders)");
        $stmt->execute($client_ids);

        // Delete credit transactions
        $stmt = $pdo->prepare("DELETE FROM credit_transactions WHERE client_id IN ($c_placeholders)");
        $stmt->execute($client_ids);

        // Delete client_credits
        $stmt = $pdo->prepare("DELETE FROM client_credits WHERE client_id IN ($c_placeholders)");
        $stmt->execute($client_ids);

        // Delete subscriptions
        $stmt = $pdo->prepare("DELETE FROM subscriptions WHERE client_id IN ($c_placeholders)");
        $stmt->execute($client_ids);

        // Delete clients
        $stmt = $pdo->prepare("DELETE FROM clients WHERE id IN ($c_placeholders)");
        $stmt->execute($client_ids);
    }

    // Delete users
    $stmt = $pdo->prepare("DELETE FROM users WHERE id IN ($placeholders)");
    $stmt->execute($ids);

    $pdo->commit();

    $deleted = $stmt->rowCount();
    echo json_encode([
        'success' => true,
        'message' => "$deleted user(s) deleted successfully",
        'deleted_count' => $deleted,
    ]);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
