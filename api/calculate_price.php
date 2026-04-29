<?php
/**
 * Robust Price Calculation API
 * Handles both ID-based and name-based lookups for print specifications.
 */
session_start();

// We want to catch everything and return it as JSON
header('Content-Type: application/json');

function returnError($msg) {
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

// Handle non-POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    returnError('Invalid request method');
}

try {
    // 1. Database Connection (Wrapped in try/catch)
    $dbPath = '../includes/db.php';
    if (!file_exists($dbPath)) returnError('Database configuration missing');
    require_once $dbPath;

    // 2. Input Sanitation
    $product_name = trim($_POST['product_name'] ?? 'Flyers');
    $quantity     = intval($_POST['quantity'] ?? 0);
    $paper_id     = $_POST['paper_id'] ?? '';
    $size_id      = $_POST['size_id'] ?? '';
    $finish_id    = $_POST['finish_id'] ?? '';
    $turnaround   = $_POST['turnaround'] ?? 'standard';
    $shipping     = $_POST['shipping'] ?? 'free';

    if ($quantity <= 0) returnError('Quantity must be greater than 0');

    // 3. Helper for flexible lookup (ID or Name)
    function getSpecMultiplier($pdo, $table, $val, $col = 'multiplier') {
        if (empty($val)) return 1.0;
        
        // Try ID first
        if (is_numeric($val)) {
            $stmt = $pdo->prepare("SELECT $col FROM $table WHERE id = ?");
            $stmt->execute([$val]);
            $res = $stmt->fetch();
            if ($res) return floatval($res[$col]);
        }
        
        // Try Name lookup
        $stmt = $pdo->prepare("SELECT $col FROM $table WHERE name LIKE ?");
        $stmt->execute(["%$val%"]);
        $res = $stmt->fetch();
        return $res ? floatval($res[$col]) : 1.0;
    }

    // 4. Get Product ID
    $stmt = $pdo->prepare("SELECT id FROM tbl_products WHERE name = ? OR slug = ?");
    $stmt->execute([$product_name, strtolower($product_name)]);
    $product_id = $stmt->fetchColumn();
    if (!$product_id) $product_id = 1; // Default to first product

    // 5. Get Base Price (Tiered)
    $stmt = $pdo->prepare("SELECT base_price, min_qty FROM tbl_base_prices WHERE product_id = ? AND min_qty <= ? ORDER BY min_qty DESC LIMIT 1");
    $stmt->execute([$product_id, $quantity]);
    $tier = $stmt->fetch();

    if (!$tier) {
        // Fallback to lowest tier
        $stmt = $pdo->prepare("SELECT base_price, min_qty FROM tbl_base_prices WHERE product_id = ? ORDER BY min_qty ASC LIMIT 1");
        $stmt->execute([$product_id]);
        $tier = $stmt->fetch();
    }

    $base_total = $tier ? floatval($tier['base_price']) : 1000.00;
    $tier_qty   = $tier ? intval($tier['min_qty']) : 50;
    
    // Calculate unit price at this tier and apply to actual quantity
    $unit_base = $base_total / $tier_qty;
    $subtotal  = $unit_base * $quantity;

    // 6. Apply Multipliers
    $mat_mult  = getSpecMultiplier($pdo, 'tbl_materials', $paper_id);
    $size_mult = getSpecMultiplier($pdo, 'tbl_sizes', $size_id);
    $subtotal *= ($mat_mult * $size_mult);

    // 7. Apply Finishing Fees
    $finish_setup = getSpecMultiplier($pdo, 'tbl_finishes', $finish_id, 'setup_fee');
    $finish_unit  = getSpecMultiplier($pdo, 'tbl_finishes', $finish_id, 'per_unit_fee');
    $subtotal += $finish_setup + ($finish_unit * $quantity);

    // 8. Turnaround Surcharge
    $turnaround_mult = 1.0;
    if ($turnaround === 'rush') $turnaround_mult = 1.25;
    if ($turnaround === 'priority') $turnaround_mult = 1.50;
    $subtotal *= $turnaround_mult;

    // 9. Discount Logic
    $discount_rate = 0;
    if ($quantity >= 5000) $discount_rate = 0.20;
    elseif ($quantity >= 1000) $discount_rate = 0.15;
    elseif ($quantity >= 500) $discount_rate = 0.10;

    $discount_amt   = $subtotal * $discount_rate;
    $after_discount = $subtotal - $discount_amt;

    // 10. Shipping
    $shipping_cost = 0;
    if ($shipping === 'express') $shipping_cost = 250.00;
    if ($shipping === 'overnight') $shipping_cost = 600.00;

    $final_total = $after_discount + $shipping_cost;
    $tax         = $final_total * 0.12;
    $grand_total = $final_total + $tax;

    // 11. Success Response
    echo json_encode([
        'success' => true,
        'data' => [
            'subtotal'        => round($subtotal, 2),
            'discount_rate'   => $discount_rate * 100,
            'discount_amount' => round($discount_amt, 2),
            'shipping'        => $shipping_cost,
            'tax'             => round($tax, 2),
            'grand_total'     => round($grand_total, 2)
        ]
    ]);

} catch (PDOException $e) {
    returnError('Database Error: ' . $e->getMessage());
} catch (Exception $e) {
    returnError('System Error: ' . $e->getMessage());
}
?>