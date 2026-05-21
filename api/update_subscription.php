<?php
// ─────────────────────────────────────────────────────────────
//  PrintPro — Update Subscription Plan
//  Client: POST { plan: 'Pro'|'Premium'|'Premium+' }
//  Admin:  POST { plan: '...', user_id: 123 }
// ─────────────────────────────────────────────────────────────
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../includes/db.php';
require_once '../includes/auth.php';

$input   = json_decode(file_get_contents('php://input'), true);
$planMap = ['pro' => 'Pro', 'premium' => 'Premium', 'premium+' => 'Premium+'];
$plan    = $planMap[strtolower(trim($input['plan'] ?? ''))] ?? '';

$allowed = ['Pro', 'Premium', 'Premium+'];
if (!in_array($plan, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Invalid plan selected.']);
    exit;
}

// Admin can pass a target user_id; clients update their own
$targetUserId = $_SESSION['user_id'];
if (!empty($input['user_id']) && is_admin()) {
    $targetUserId = (int)$input['user_id'];
}

try {
    $stmt = $pdo->prepare("UPDATE users SET subscription_plan = ? WHERE id = ?");
    $stmt->execute([$plan, $targetUserId]);

    // Update session if updating own plan
    if ($targetUserId === (int)$_SESSION['user_id']) {
        $_SESSION['subscription_plan'] = $plan;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Plan updated to ' . $plan,
        'plan'    => $plan
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
