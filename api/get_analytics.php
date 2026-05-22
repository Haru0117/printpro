<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$range = $_GET['range'] ?? 'month';

$interval = match($range) {
    '3months' => 'INTERVAL 3 MONTH',
    '6months' => 'INTERVAL 6 MONTH',
    'year'    => 'INTERVAL 1 YEAR',
    default   => 'INTERVAL 1 MONTH'
};

try {
    // 1. Orders by product_type (for pie chart)
    $stmt = $pdo->prepare("SELECT product_type, COUNT(*) as count FROM orders WHERE created_at >= DATE_SUB(NOW(), $interval) GROUP BY product_type ORDER BY count DESC");
    $stmt->execute();
    $byProduct = $stmt->fetchAll();

    // 2. Monthly revenue (for bar chart) — last 6 data points regardless of range
    $stmt = $pdo->prepare("SELECT DATE_FORMAT(created_at, '%b %Y') as label, SUM(total_amount) as value, MIN(created_at) as sort_date FROM orders WHERE created_at >= DATE_SUB(NOW(), $interval) GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY MIN(created_at) ASC");
    $stmt->execute();
    $monthly = $stmt->fetchAll();

    // 3. KPIs
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE created_at >= DATE_SUB(NOW(), $interval)");
    $stmt->execute();
    $totalOrders = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE created_at >= DATE_SUB(NOW(), $interval)");
    $stmt->execute();
    $totalRevenue = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE status IN ('Proof Pending','Proof Pending Review')");
    $stmt->execute();
    $pendingProofs = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT client_id) FROM orders WHERE created_at >= DATE_SUB(NOW(), $interval)");
    $stmt->execute();
    $activeClients = $stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'data' => [
            'by_product' => $byProduct,
            'monthly_revenue' => $monthly,
            'kpis' => [
                'total_orders' => $totalOrders,
                'total_revenue' => floatval($totalRevenue),
                'pending_proofs' => $pendingProofs,
                'active_clients' => $activeClients
            ]
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>