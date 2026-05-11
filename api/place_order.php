<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');
ob_clean();

// ── Auth check ───────────────────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

try {
    $user_id = intval($_SESSION['user_id']);

    // ── 1. Get the client_id for this logged-in user ─────────────────────────
    $stmt = $pdo->prepare("SELECT id FROM clients WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $client = $stmt->fetch();

    if (!$client) {
        echo json_encode(['success' => false, 'message' => 'No client profile found for this account.']);
        exit;
    }

    $client_id = $client['id'];

    // ── 2. Check client credits ──────────────────────────────────────────────
    $stmt = $pdo->prepare("SELECT balance FROM client_credits WHERE client_id = ?");
    $stmt->execute([$client_id]);
    $credit_row = $stmt->fetch();

    if (!$credit_row) {
        echo json_encode(['success' => false, 'message' => 'No credit account found. Please contact support.']);
        exit;
    }

    $current_balance = floatval($credit_row['balance']);

    if ($current_balance < $total_amount) {
        echo json_encode(['success' => false, 'message' => 'Insufficient credits. Current balance: ₱' . number_format($current_balance, 2) . ', Required: ₱' . number_format($total_amount, 2)]);
        exit;
    }

    // ── 3. Read and sanitize form fields ─────────────────────────────────────
    $product_type = trim($_POST['product_type'] ?? 'Flyers');
    $paper_weight = trim($_POST['paper_weight'] ?? '');
    $finish = trim($_POST['finish'] ?? 'None');
    $quantity = max(1, intval($_POST['quantity'] ?? 1));
    $size_width = floatval($_POST['size_width'] ?? 4.0);
    $size_height = floatval($_POST['size_height'] ?? 6.0);
    $total_amount = floatval($_POST['total_price'] ?? 0.00);
    $notes = trim($_POST['notes'] ?? '');

    // Turnaround: form sends 'standard' | 'rush' | 'priority'
    $turnaround_map = ['standard' => 'Standard', 'rush' => 'Rush', 'priority' => 'Priority'];
    $turnaround_raw = strtolower($_POST['turnaround'] ?? 'standard');
    $turnaround = $turnaround_map[$turnaround_raw] ?? 'Standard';

    // Shipping: form sends 'free' | 'express' | 'overnight'
    $shipping_map = ['free' => 'Ground', 'express' => 'Express', 'overnight' => 'Overnight'];
    $shipping_raw = strtolower($_POST['shipping'] ?? 'free');
    $shipping_method = $shipping_map[$shipping_raw] ?? 'Ground';

    // Bleed: form sends 'With Bleed...' | 'No Bleed...' | 'Custom...'
    $bleed_str = $_POST['bleed'] ?? 'With Bleed';
    $bleed = (stripos($bleed_str, 'No Bleed') !== false) ? 0 : 1;

    // Calc unit price from total
    $tax_rate = 12.00;
    $unit_price = $quantity > 0 ? round($total_amount / $quantity, 4) : 0;

    // Due date: 3 business days for Standard, 1 for Rush, today for Priority
    $days_map = ['Standard' => 3, 'Rush' => 1, 'Priority' => 0];
    $add_days = $days_map[$turnaround] ?? 3;
    $due_date = date('Y-m-d', strtotime("+{$add_days} weekdays"));

    // ── 3. Auto-generate order number (PPR-XXX) ──────────────────────────────
    $stmt = $pdo->query("SELECT MAX(id) AS max_id FROM orders");
    $row = $stmt->fetch();
    $next_num = intval($row['max_id'] ?? 0) + 1;
    $order_number = 'PPR-' . str_pad($next_num, 3, '0', STR_PAD_LEFT);

    // ── 4. Insert the order ───────────────────────────────────────────────────
    $stmt = $pdo->prepare("
        INSERT INTO orders (
            order_number, client_id,
            product_type, quantity,
            size_width, size_height,
            paper_weight, finish, bleed,
            turnaround, shipping_method,
            unit_price, tax_rate, total_amount,
            status, progress_pct, due_date, notes
        ) VALUES (
            :order_number, :client_id,
            :product_type, :quantity,
            :size_width, :size_height,
            :paper_weight, :finish, :bleed,
            :turnaround, :shipping_method,
            :unit_price, :tax_rate, :total_amount,
            'Prepress', 0, :due_date, :notes
        )
    ");

    $stmt->execute([
        ':order_number' => $order_number,
        ':client_id' => $client_id,
        ':product_type' => $product_type,
        ':quantity' => $quantity,
        ':size_width' => $size_width,
        ':size_height' => $size_height,
        ':paper_weight' => $paper_weight,
        ':finish' => $finish,
        ':bleed' => $bleed,
        ':turnaround' => $turnaround,
        ':shipping_method' => $shipping_method,
        ':unit_price' => $unit_price,
        ':tax_rate' => $tax_rate,
        ':total_amount' => $total_amount,
        ':due_date' => $due_date,
        ':notes' => $notes,
    ]);

    $order_id = $pdo->lastInsertId();

    // ── 5. Deduct credits and record transaction ────────────────────────────
    $pdo->beginTransaction();

    try {
        // Insert credit transaction
        $stmt = $pdo->prepare("
            INSERT INTO credit_transactions (client_id, transaction_type, amount, description, order_id)
            VALUES (?, 'deduct', ?, ?, ?)
        ");
        $stmt->execute([$client_id, $total_amount, "Order #$order_number", $order_id]);

        // The trigger will automatically update the balance
        // But to be safe, we can manually update if trigger fails
        $stmt = $pdo->prepare("UPDATE client_credits SET balance = balance - ? WHERE client_id = ?");
        $stmt->execute([$total_amount, $client_id]);

        $pdo->commit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to process payment: ' . $e->getMessage()]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'order_number' => $order_number,
        'due_date' => $due_date,
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>