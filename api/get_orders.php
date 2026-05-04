<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'client';

try {
    if ($role === 'admin') {
        // Admins see all orders
        $stmt = $pdo->query("
            SELECT o.*, o.product_type as job_name, o.total_amount as total_price, c.business_name as client_name 
            FROM orders o 
            JOIN clients c ON o.client_id = c.id 
            ORDER BY o.created_at DESC
        ");
        $orders = $stmt->fetchAll();
    } else {
        // Clients see only their own orders
        // First get the client_id
        $stmt = $pdo->prepare("SELECT id FROM clients WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $client = $stmt->fetch();
        
        if ($client) {
            $client_id = $client['id'];
            $stmt = $pdo->prepare("
                SELECT *, product_type as job_name, total_amount as total_price 
                FROM orders 
                WHERE client_id = ? 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$client_id]);
            $orders = $stmt->fetchAll();
        } else {
            $orders = [];
        }
    }
    
    echo json_encode(['success' => true, 'data' => $orders]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
