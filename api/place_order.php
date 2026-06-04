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

    // ── 3. Read and sanitize form fields FIRST ───────────────────────────
    $product_type = trim($_POST['product_type'] ?? 'Flyers');
    $job_name     = trim($_POST['job_name'] ?? '');
    if (empty($job_name)) {
        $job_name = $product_type . ' Project';
    }
    $paper_weight = trim($_POST['paper_weight'] ?? '');
    $finish       = trim($_POST['finish'] ?? 'None');
    $quantity     = max(1, intval($_POST['quantity'] ?? 1));
    $size_width   = floatval($_POST['size_width'] ?? 4.0);
    $size_height  = floatval($_POST['size_height'] ?? 6.0);
    $total_amount = floatval($_POST['total_price'] ?? 0.00);
    $notes        = trim($_POST['notes'] ?? '');

    // ── Artwork File Handling ──
    $artwork_path = null;

    // Check for saved_file_id (pre-uploaded file from tbl_user_files)
    if (empty($artwork_path) && !empty($_POST['saved_file_id'])) {
        $sfid = intval($_POST['saved_file_id']);
        $stmt = $pdo->prepare("SELECT file_path FROM tbl_user_files WHERE id = ? AND user_id = ?");
        $stmt->execute([$sfid, $user_id]);
        $sf = $stmt->fetch();
        if ($sf && !empty($sf['file_path'])) {
            $artwork_path = $sf['file_path'];
        }
    }

    if (isset($_FILES['artwork_file']) && $_FILES['artwork_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['artwork_file'];
        $fileName = basename($file['name']);
        $fileSize = $file['size'];
        $tmpName = $file['tmp_name'];
        
        // Check size (500MB limit)
        if ($fileSize > 500 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Artwork file exceeds the 500MB size limit.']);
            exit;
        }
        
        // Check extension
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['pdf', 'ai', 'psd', 'png', 'jpg', 'jpeg'];
        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file format. Supported: PDF, AI, PSD, PNG, JPG.']);
            exit;
        }
        
        // Define directory paths
        $uploadDir = '../uploads/artwork/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Make the file name unique
        $safeFileName = preg_replace('/[^a-zA-Z0-9_.-]/', '_', pathinfo($fileName, PATHINFO_FILENAME));
        $newFileName = 'artwork_' . time() . '_' . $safeFileName . '.' . $ext;
        $destPath = $uploadDir . $newFileName;
        
        if (move_uploaded_file($tmpName, $destPath)) {
            $artwork_path = 'uploads/artwork/' . $newFileName;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save uploaded artwork file.']);
            exit;
        }
    }

    // ── 4. Validate specs are still active ────────────────────────────────
    if (!empty($paper_weight)) {
        $stmt = $pdo->prepare("SELECT id FROM tbl_materials WHERE name = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$paper_weight]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => "The selected paper/material '$paper_weight' is no longer available. Please choose another."]);
            exit;
        }
    }
    if (!in_array($finish, ['None', 'Uncoated', ''])) {
        $stmt = $pdo->prepare("SELECT id FROM tbl_finishes WHERE name = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$finish]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => "The selected finish '$finish' is no longer available. Please choose another."]);
            exit;
        }
    }

    // ── 5. Check client credits (BEFORE creating the order) ──────────────
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

    // ── 6. Check monthly credit limit based on subscription plan ────────
    $stmt = $pdo->prepare("SELECT subscription_plan FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_row = $stmt->fetch();
    $plan = strtolower($user_row['subscription_plan'] ?? 'free');

    $plan_limits = [
        'free'     => 0,
        'pro'      => 25000,
        'premium'  => 75000,
        'premium+' => PHP_INT_MAX,
    ];
    $monthly_limit = $plan_limits[$plan] ?? 0;

    if ($monthly_limit < PHP_INT_MAX) {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM credit_transactions WHERE client_id = ? AND transaction_type = 'deduct' AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')");
        $stmt->execute([$client_id]);
        $monthly_used = floatval($stmt->fetchColumn());

        if (($monthly_used + $total_amount) > $monthly_limit) {
            echo json_encode(['success' => false, 'message' => 'Monthly credit limit reached. Your ' . ucfirst($plan) . ' plan is capped at ₱' . number_format($monthly_limit, 0) . ' in credits per month (₱' . number_format($monthly_used, 0) . ' used). Please upgrade your plan or wait until next month.']);
            exit;
        }
    }

    // ── 7. Parse turnaround & shipping ──────────────────────────────────────
    $turnaround_map = ['standard' => 'Standard', 'rush' => 'Rush', 'priority' => 'Priority'];
    $turnaround_raw = strtolower($_POST['turnaround'] ?? 'standard');
    $turnaround = $turnaround_map[$turnaround_raw] ?? 'Standard';

    $shipping_map = ['free' => 'Ground', 'express' => 'Express', 'overnight' => 'Overnight'];
    $shipping_raw = strtolower($_POST['shipping'] ?? 'free');
    $shipping_method = $shipping_map[$shipping_raw] ?? 'Ground';

    $bleed_str = $_POST['bleed'] ?? 'With Bleed';
    $bleed = (stripos($bleed_str, 'No Bleed') !== false) ? 0 : 1;

    $tax_rate = 12.00;
    $unit_price = $quantity > 0 ? round($total_amount / $quantity, 4) : 0;

    $days_map = ['Standard' => 3, 'Rush' => 1, 'Priority' => 0];
    $add_days = $days_map[$turnaround] ?? 3;
    $due_date = date('Y-m-d', strtotime("+{$add_days} weekdays"));

    // ── 8. Auto-generate order number (PPR-XXX) ──────────────────────────────
    $stmt = $pdo->query("SELECT MAX(id) AS max_id FROM orders");
    $row = $stmt->fetch();
    $next_num = intval($row['max_id'] ?? 0) + 1;
    $order_number = 'PPR-' . str_pad($next_num, 3, '0', STR_PAD_LEFT);

    // ── 9. Insert the order ───────────────────────────────────────────────────
    $stmt = $pdo->prepare("
        INSERT INTO orders (
            order_number, client_id, job_name,
            product_type, quantity,
            size_width, size_height,
            paper_weight, finish, bleed,
            turnaround, shipping_method,
            unit_price, tax_rate, total_amount,
            status, progress_pct, due_date, notes,
            artwork_file
        ) VALUES (
            :order_number, :client_id, :job_name,
            :product_type, :quantity,
            :size_width, :size_height,
            :paper_weight, :finish, :bleed,
            :turnaround, :shipping_method,
            :unit_price, :tax_rate, :total_amount,
            'Proof Pending', 0, :due_date, :notes,
            :artwork_file
        )
    ");

    $stmt->execute([
        ':order_number' => $order_number,
        ':client_id' => $client_id,
        ':job_name' => $job_name,
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
        ':artwork_file' => $artwork_path,
    ]);

    $order_id = $pdo->lastInsertId();

    // ── 10. Deduct credits and record transaction ───────────────────────────
    $pdo->beginTransaction();

    try {
        $balance_after = $current_balance - $total_amount;
        $stmt = $pdo->prepare("
            INSERT INTO credit_transactions (client_id, transaction_type, amount, description, order_id)
            VALUES (?, 'deduct', ?, ?, ?)
        ");
        $stmt->execute([$client_id, $total_amount, "Order #$order_number | Bal after: ₱" . number_format($balance_after, 2), $order_id]);

        $stmt = $pdo->prepare("UPDATE client_credits SET balance = GREATEST(0, balance - ?), updated_at = NOW() WHERE client_id = ?");
        $stmt->execute([$total_amount, $client_id]);

        $pdo->commit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to process payment: ' . $e->getMessage()]);
        exit;
    }

    $response = [
        'success' => true,
        'order_id' => $order_id,
        'order_number' => $order_number,
        'due_date' => $due_date,
    ];

} catch (PDOException $e) {
    $response = ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
}

// CLOSE CONNECTION IMMEDIATELY after fetching data (Fetch-Close-Render pattern)
$pdo = null;

// Now perform rendering
echo json_encode($response);
?>