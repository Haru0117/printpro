<?php
session_start();
// Suppress warnings to prevent corrupting JSON output
error_reporting(0);
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$product_name = trim($_POST['product_name'] ?? 'Flyers');
$quantity = intval($_POST['quantity'] ?? 0);
$material_id = intval($_POST['paper_id'] ?? 0);
$size_id = intval($_POST['size_id'] ?? 0);
$finish_id = intval($_POST['finish_id'] ?? 0);
$bleed_option = $_POST['bleed_option'] ?? 'No Bleed';

if ($quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid quantity']);
    exit;
}

try {
    // Check if tables exist
    $check = $pdo->query("SHOW TABLES LIKE 'tbl_products'");
    if ($check->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'Database tables missing. Please run api/migrate_pricing.php']);
        exit;
    }

    // 1. Get Base Rate for the product and quantity tier
    $stmt = $pdo->prepare("
        SELECT bp.base_price 
        FROM tbl_base_prices bp
        JOIN tbl_products p ON p.id = bp.product_id
        WHERE LOWER(p.name) = LOWER(?) AND bp.min_qty <= ?
        ORDER BY bp.min_qty DESC LIMIT 1
    ");
    $stmt->execute([$product_name, $quantity]);
    $base_rate = $stmt->fetchColumn();
    
    // Fallback if no exact tier found (use lowest tier)
    if ($base_rate === false) {
        $stmt = $pdo->prepare("SELECT base_price FROM tbl_base_prices bp JOIN tbl_products p ON p.id = bp.product_id WHERE LOWER(p.name) = LOWER(?) ORDER BY min_qty ASC LIMIT 1");
        $stmt->execute([$product_name]);
        $base_rate = $stmt->fetchColumn() ?: 0;
    }

    // 2. Get Material Multiplier
    $stmt = $pdo->prepare("SELECT multiplier FROM tbl_materials WHERE id = ?");
    $stmt->execute([$material_id]);
    $mat_mult = $stmt->fetchColumn() ?: 1.0;

    // 3. Get Size Multiplier
    $stmt = $pdo->prepare("SELECT multiplier FROM tbl_sizes WHERE id = ?");
    $stmt->execute([$size_id]);
    $size_mult = $stmt->fetchColumn() ?: 1.0;

    // 4. Get Finishing Fees
    $stmt = $pdo->prepare("SELECT setup_fee, per_unit_fee FROM tbl_finishes WHERE id = ?");
    $stmt->execute([$finish_id]);
    $finish = $stmt->fetch() ?: ['setup_fee' => 0, 'per_unit_fee' => 0];

    // 5. Bleed Fee
    $bleed_fee = (strpos($bleed_option, 'Custom') !== false) ? 250.00 : 0.00;

    // --- FORMULA ---
    // Subtotal = (Base Rate * Mat * Size) + Setup + (Unit * Qty) + Bleed
    $subtotal = ($base_rate * $mat_mult * $size_mult) + $finish['setup_fee'] + ($finish['per_unit_fee'] * $quantity) + $bleed_fee;

    $tax = $subtotal * 0.12; 
    $grand_total = $subtotal + $tax;

    echo json_encode([
        'success' => true,
        'data' => [
            'base_rate' => floatval($base_rate),
            'subtotal' => round($subtotal, 2),
            'tax' => round($tax, 2),
            'grand_total' => round($grand_total, 2),
            'discount_rate' => 0
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'System error: ' . $e->getMessage()]);
}
?>