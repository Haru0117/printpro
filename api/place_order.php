<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$user_id = $_SESSION['user_id'];
$job_name = $_POST['job_name'] ?? 'Untitled Job';
$paper_id = $_POST['paper_id'] ?? 0;
$size_id = $_POST['size_id'] ?? 0;
$finish_id = $_POST['finish_id'] ?? 0;
$quantity = intval($_POST['quantity'] ?? 0);
$total_price = floatval($_POST['total_price'] ?? 0);

try {
    $stmt = $pdo->prepare("INSERT INTO orders (user_id, job_name, paper_id, size_id, finish_id, quantity, total_price, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Prepress')");
    $stmt->execute([$user_id, $job_name, $paper_id, $size_id, $finish_id, $quantity, $total_price]);
    
    $order_id = $pdo->lastInsertId();
    echo json_encode(['success' => true, 'order_id' => $order_id]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
