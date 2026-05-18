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

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$amount = floatval($input['amount'] ?? 0);

// Validate amount
if ($amount < 10) {
    echo json_encode(['success' => false, 'message' => 'Minimum credit amount is ₱10.00.']);
    exit;
}
if ($amount > 50000) {
    echo json_encode(['success' => false, 'message' => 'Maximum single top-up is ₱50,000.00.']);
    exit;
}

try {
    $user_id = intval($_SESSION['user_id']);

    // ── 1. Get client_id ─────────────────────────────────────────────────────
    $stmt = $pdo->prepare("SELECT id FROM clients WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $client = $stmt->fetch();

    if (!$client) {
        echo json_encode(['success' => false, 'message' => 'No client profile found. Please contact support.']);
        exit;
    }

    $client_id = $client['id'];

    // ── 2. Ensure a credits row exists ───────────────────────────────────────
    $stmt = $pdo->prepare("SELECT id FROM client_credits WHERE client_id = ?");
    $stmt->execute([$client_id]);
    if (!$stmt->fetch()) {
        $pdo->prepare("INSERT INTO client_credits (client_id, balance) VALUES (?, 0.00)")
            ->execute([$client_id]);
    }

    // ── 3. Add credits in a transaction ──────────────────────────────────────
    $pdo->beginTransaction();

    try {
        // Insert transaction record
        $stmt = $pdo->prepare("
            INSERT INTO credit_transactions
                (client_id, transaction_type, amount, description, created_at)
            VALUES
                (?, 'add', ?, 'Wallet top-up', NOW())
        ");
        $stmt->execute([$client_id, $amount]);

        // Update balance directly (safe even if trigger also fires — just double-check)
        $stmt = $pdo->prepare("
            UPDATE client_credits
            SET balance = balance + ?, updated_at = NOW()
            WHERE client_id = ?
        ");
        $stmt->execute([$amount, $client_id]);

        $pdo->commit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Transaction failed. Please try again.']);
        exit;
    }

    // ── 4. Return new balance ─────────────────────────────────────────────────
    $stmt = $pdo->prepare("SELECT balance FROM client_credits WHERE client_id = ?");
    $stmt->execute([$client_id]);
    $row = $stmt->fetch();
    $new_balance = $row ? floatval($row['balance']) : 0;

    echo json_encode([
        'success'     => true,
        'message'     => '₱' . number_format($amount, 2) . ' added successfully!',
        'new_balance' => number_format($new_balance, 2),
        'new_balance_raw' => $new_balance,
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
}
?>
