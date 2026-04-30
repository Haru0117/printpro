<?php
/**
 * Dynamic Pricing Engine — PrintPro
 * Matches actual DB schema: tbl_base_prices, tbl_materials, tbl_finishes, tbl_sizes
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../includes/db.php';

function calculateOrderTotal($pdo, $params)
{
    $product_id = intval($params['product_id'] ?? 1);
    $quantity = intval($params['quantity'] ?? 100);
    $custom_width = floatval($params['custom_width'] ?? 4.0);
    $custom_height = floatval($params['custom_height'] ?? 6.0);
    $bleed = floatval($params['bleed'] ?? 0.125);
    $material = trim($params['material'] ?? '100lb Text (Standard)');
    $finish = trim($params['finish'] ?? 'None');

    // ── Step 1: Size Multiplier ──────────────────────────────────────────────
    // Area ratio vs. base 4"×6" (24 sq in) including bleed on all sides
    $total_width = $custom_width + ($bleed * 2);
    $total_height = $custom_height + ($bleed * 2);
    $area_sq_in = $total_width * $total_height;
    $base_area = 24.0;  // 4×6 standard reference
    $size_multiplier = $area_sq_in / $base_area;

    // ── Step 2: Base Price from tier table ───────────────────────────────────
    // tbl_base_prices stores TOTAL job price (not unit price) per min_qty tier
    $base_price = 0.0;
    try {
        $stmt = $pdo->prepare("
            SELECT base_price 
            FROM tbl_base_prices 
            WHERE product_id = :pid AND min_qty <= :qty 
            ORDER BY min_qty DESC 
            LIMIT 1
        ");
        $stmt->execute(['pid' => $product_id, 'qty' => $quantity]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $base_price = floatval($row['base_price']);
        } else {
            // Quantity below lowest tier — use lowest tier price
            $stmt = $pdo->prepare("
                SELECT base_price 
                FROM tbl_base_prices 
                WHERE product_id = :pid 
                ORDER BY min_qty ASC 
                LIMIT 1
            ");
            $stmt->execute(['pid' => $product_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row)
                $base_price = floatval($row['base_price']);
        }
    } catch (PDOException $e) {
        $base_price = 1200.0; // fallback
    }

    // ── Step 3: Material Multiplier ──────────────────────────────────────────
    $material_multiplier = 1.0;
    try {
        $stmt = $pdo->prepare("SELECT multiplier FROM tbl_materials WHERE name = :name LIMIT 1");
        $stmt->execute(['name' => $material]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row)
            $material_multiplier = floatval($row['multiplier']);
    } catch (PDOException $e) {
        $material_multiplier = 1.0;
    }

    // ── Step 4: Finish Add-ons ───────────────────────────────────────────────
    $setup_fee = 0.0;
    $per_unit_fee = 0.0;
    if (!in_array($finish, ['None', 'Uncoated', ''])) {
        try {
            $stmt = $pdo->prepare("SELECT setup_fee, per_unit_fee FROM tbl_finishes WHERE name = :name LIMIT 1");
            $stmt->execute(['name' => $finish]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $setup_fee = floatval($row['setup_fee']);
                $per_unit_fee = floatval($row['per_unit_fee']);
            }
        } catch (PDOException $e) {
            $setup_fee = 0.0;
        }
    }

    // ── Step 5: Scale base price by size and material ────────────────────────
    // base_price is the job total for the standard 4×6 size
    $scaled_price = $base_price * $size_multiplier * $material_multiplier;

    // Add finishing: setup once + per-unit × quantity
    $subtotal_with_addons = $scaled_price + $setup_fee + ($per_unit_fee * $quantity);

    // ── Step 6: Turnaround Multiplier ───────────────────────────────────────
    $turnaround = $params['turnaround'] ?? 'standard';
    $turnaround_multiplier = 1.0;
    if ($turnaround === 'rush') $turnaround_multiplier = 1.25;
    if ($turnaround === 'priority') $turnaround_multiplier = 1.50;

    // ── Step 7: 10% Safety Markup ────────────────────────────────────────────
    $subtotal_final = ($subtotal_with_addons * $turnaround_multiplier) * 1.10;

    // ── Step 8: 12% VAT ──────────────────────────────────────────────────────
    $vat_tax = $subtotal_final * 0.12;
    
    // ── Step 9: Shipping Fee ─────────────────────────────────────────────────
    $shipping_method = $params['shipping'] ?? 'free';
    $shipping_cost = 0.0;
    if ($shipping_method === 'express') $shipping_cost = 250.0;
    if ($shipping_method === 'overnight') $shipping_cost = 600.0;

    $grand_total = $subtotal_final + $vat_tax + $shipping_cost;

    return [
        'success' => true,
        'breakdown' => [
            'area_sq_in' => round($area_sq_in, 4),
            'size_multiplier' => round($size_multiplier, 4),
            'base_price_tier' => round($base_price, 2),
            'material_multiplier' => round($material_multiplier, 4),
            'turnaround_multiplier' => round($turnaround_multiplier, 2),
            'scaled_price' => round($scaled_price, 2),
            'setup_fee' => round($setup_fee, 2),
            'finishing_unit_fee' => round($per_unit_fee, 4),
            'subtotal_raw' => round($subtotal_with_addons, 2),
            'subtotal_final' => round($subtotal_final, 2),
            'shipping_cost' => round($shipping_cost, 2),
            'vat_tax' => round($vat_tax, 2),
            'grand_total' => round($grand_total, 2)
        ]
    ];
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'CLI';

if ($method === 'POST' || $method === 'GET') {
    $params = $method === 'POST' ? $_POST : $_GET;
    echo json_encode(calculateOrderTotal($pdo, $params));
} elseif ($method === 'CLI') {
    // Quick CLI test
    $params = ['product_id' => 1, 'quantity' => 250, 'custom_width' => 4, 'custom_height' => 6, 'bleed' => 0.125, 'material' => '100lb Text (Standard)', 'finish' => 'UV Coating'];
    echo json_encode(calculateOrderTotal($pdo, $params), JSON_PRETTY_PRINT);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
