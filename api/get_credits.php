<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'balance' => 0, 'transactions' => []]);
    exit;
}

require_once '../includes/db.php';

try {
    // Get current balance
    $stmt = $pdo->prepare("
        SELECT cc.balance 
        FROM client_credits cc 
        JOIN clients c ON cc.client_id = c.id 
        WHERE c.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();
    $balance = $row ? floatval($row['balance']) : 0;

    // Get recent transactions
    $stmt = $pdo->prepare("
        SELECT ct.transaction_type, ct.amount, ct.description, ct.created_at, 
               o.id as order_id
        FROM credit_transactions ct
        JOIN clients c ON ct.client_id = c.id
        LEFT JOIN orders o ON ct.order_id = o.id
        WHERE c.user_id = ?
        ORDER BY ct.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format transactions for frontend
    $formatted_trans = array_map(function($t) {
        $t['order_number'] = $t['order_id'] ? 'PPR-' . str_pad($t['order_id'], 3, '0', STR_PAD_LEFT) : null;
        unset($t['order_id']);
        return $t;
    }, $transactions);

    echo json_encode([
        'success' => true,
        'balance' => number_format($balance, 2),
        'balance_raw' => $balance,
        'transactions' => $formatted_trans,
        'used' => number_format(10000 - $balance, 2),
        'used_raw' => 10000 - $balance
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'balance' => '0.00',
        'balance_raw' => 0,
        'transactions' => [],
        'error' => 'Unable to fetch credits'
    ]);
}
?>
