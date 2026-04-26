<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$paper_id = $_POST['paper_id'] ?? 0;
$size_id = $_POST['size_id'] ?? 0;
$finish_id = $_POST['finish_id'] ?? 0;
$quantity = intval($_POST['quantity'] ?? 0);

if ($quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid quantity']);
    exit;
}

// Fetch modifiers
$ids = [$paper_id, $size_id, $finish_id];
$placeholders = str_repeat('?,', count($ids) - 1) . '?';
$stmt = $pdo->prepare("SELECT id, price_modifier FROM print_specs WHERE id IN ($placeholders) AND is_active = 1");
$stmt->execute($ids);
$specs = $stmt->fetchAll();

$unit_price = 0;
foreach ($specs as $spec) {
    $unit_price += floatval($spec['price_modifier']);
}

$subtotal = $unit_price * $quantity;

// Discount Logic (Tiered)
$discount_rate = 0;
if ($quantity >= 1000) {
    $discount_rate = 0.15; // 15%
} elseif ($quantity >= 500) {
    $discount_rate = 0.10; // 10%
} elseif ($quantity >= 100) {
    $discount_rate = 0.05; // 5%
}

$discount_amount = $subtotal * $discount_rate;
$total = $subtotal - $discount_amount;
$tax = $total * 0.12; // 12% VAT
$grand_total = $total + $tax;

echo json_encode([
    'success' => true,
    'data' => [
        'unit_price' => $unit_price,
        'subtotal' => $subtotal,
        'discount_rate' => $discount_rate * 100,
        'discount_amount' => $discount_amount,
        'total' => $total,
        'tax' => $tax,
        'grand_total' => $grand_total
    ]
]);
?>
