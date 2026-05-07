<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // 1. KPI Stats
    $stmt = $pdo->query("SELECT COUNT(*) as total_orders, SUM(total_price) as total_revenue FROM orders");
    $kpis = $stmt->fetch();

    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
    $status_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // 2. Revenue over time (Last 6 months)
    $stmt = $pdo->query("SELECT DATE_FORMAT(created_at, '%b') as month, SUM(total_price) as revenue 
                         FROM orders 
                         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                         GROUP BY month 
                         ORDER BY created_at ASC");
    $revenue_chart = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => [
            'total_orders' => $kpis['total_orders'] ?? 0,
            'total_revenue' => $kpis['total_revenue'] ?? 0,
            'status_counts' => $status_counts,
            'revenue_chart' => $revenue_chart
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>