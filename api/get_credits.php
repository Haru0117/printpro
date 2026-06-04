<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'balance' => 0, 'transactions' => []]);
    exit;
}

require_once '../includes/db.php';

try {
    $user_id = $_SESSION['user_id'];

    // Get current balance
    $stmt = $pdo->prepare("
        SELECT cc.balance 
        FROM client_credits cc 
        JOIN clients c ON cc.client_id = c.id 
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();
    $balance = $row ? floatval($row['balance']) : 0;

    // Get subscription plan & monthly limit
    $plan_limits = [
        'free'     => 0,
        'pro'      => 25000,
        'premium'  => 75000,
        'premium+' => PHP_INT_MAX,
    ];
    $stmt = $pdo->prepare("SELECT subscription_plan FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_row = $stmt->fetch();
    $plan = strtolower($user_row['subscription_plan'] ?? 'free');
    $plan_label = $user_row['subscription_plan'] ?? 'Free';
    $monthly_limit = $plan_limits[$plan] ?? 0;

    // Get monthly usage
    $monthly_used = 0;
    if ($monthly_limit < PHP_INT_MAX) {
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) 
            FROM credit_transactions ct
            JOIN clients c ON ct.client_id = c.id
            WHERE c.user_id = ? 
              AND ct.transaction_type = 'deduct'
              AND DATE_FORMAT(ct.created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
        ");
        $stmt->execute([$user_id]);
        $monthly_used = floatval($stmt->fetchColumn());
    }

    // Get recent transactions
    $stmt = $pdo->prepare("
        SELECT ct.transaction_type, ct.amount, ct.description, ct.created_at, 
               o.id as order_id
        FROM credit_transactions ct
        JOIN clients c ON ct.client_id = c.id
        LEFT JOIN orders o ON ct.order_id = o.id
        WHERE c.user_id = ?
        ORDER BY ct.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$user_id]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format transactions for frontend
    $formatted_trans = array_map(function($t) {
        $t['order_number'] = $t['order_id'] ? 'PPR-' . str_pad($t['order_id'], 3, '0', STR_PAD_LEFT) : null;
        unset($t['order_id']);
        return $t;
    }, $transactions);

    echo json_encode([
        'success'        => true,
        'balance'        => number_format($balance, 2),
        'balance_raw'    => $balance,
        'plan'           => $plan_label,
        'monthly_limit'  => $monthly_limit >= PHP_INT_MAX ? null : $monthly_limit,
        'monthly_used'   => $monthly_used,
        'monthly_remaining' => $monthly_limit >= PHP_INT_MAX ? null : max(0, $monthly_limit - $monthly_used),
        'transactions'   => $formatted_trans,
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
