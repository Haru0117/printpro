<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$period = $_GET['period'] ?? '6months';
$interval = 'INTERVAL 6 MONTH';
$dateFormat = '%b'; // Default: Month name (Jan, Feb...)

switch ($period) {
    case 'week':
        $interval = 'INTERVAL 7 DAY';
        $dateFormat = '%a'; // Day name (Mon, Tue...)
        break;
    case 'month':
        $interval = 'INTERVAL 1 MONTH';
        $dateFormat = '%d %b'; // Day and Month (15 May)
        break;
    case 'year':
        $interval = 'INTERVAL 1 YEAR';
        $dateFormat = '%b %y'; // Month and Year (May 24)
        break;
    case 'all':
        $interval = 'INTERVAL 100 YEAR';
        $dateFormat = '%Y'; // Year only for readability if it's long, or %b %Y
        break;
}

try {
    // 1. KPI Stats
    // Task 2: Specifically target orders where status = 'Delivered' for Total Revenue
    $stmt = $pdo->query("SELECT 
                            (SELECT COUNT(*) FROM orders) as total_orders, 
                            (SELECT SUM(total_amount) FROM orders WHERE status = 'Delivered') as total_revenue");
    $kpis = $stmt->fetch();

    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
    $status_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // 2. Revenue over time (Filtered by Period)
    $query = "SELECT DATE_FORMAT(created_at, '$dateFormat') as label, SUM(total_amount) as value 
              FROM orders 
              WHERE created_at >= DATE_SUB(NOW(), $interval) AND status = 'Delivered'
              GROUP BY label 
              ORDER BY MIN(created_at) ASC";
    
    // Special handling for All Time to ensure readability
    if ($period === 'all') {
        $query = "SELECT YEAR(created_at) as label, SUM(total_amount) as value 
                  FROM orders 
                  WHERE status = 'Delivered'
                  GROUP BY label 
                  ORDER BY label ASC";
    }

    $stmt = $pdo->query($query);
    $revenue_chart = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. New Users (Last 7 days)
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $new_users = $stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'data' => [
            'total_orders' => $kpis['total_orders'] ?? 0,
            'total_revenue' => $kpis['total_revenue'] ?? 0,
            'status_counts' => $status_counts,
            'revenue_chart' => $revenue_chart,
            'new_users' => $new_users
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>