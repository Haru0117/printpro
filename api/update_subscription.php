<?php
// ─────────────────────────────────────────────────────────────
//  PrintPro — Update Subscription Plan
//  POST: { plan: 'Pro' | 'Premium' | 'Premium+' | 'Free Plan' }
// ─────────────────────────────────────────────────────────────
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../includes/db.php';

$input = json_decode(file_get_contents('php://input'), true);
$planMap  = ['pro' => 'Pro', 'premium' => 'Premium', 'premium+' => 'Premium+'];
$plan     = $planMap[strtolower(trim($input['plan'] ?? ''))] ?? '';

$allowed = ['Pro', 'Premium', 'Premium+'];
if (!in_array($plan, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Invalid plan selected.']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE users SET subscription_plan = ? WHERE id = ?");
    $stmt->execute([$plan, $_SESSION['user_id']]);

    // Update session so hero banner reflects immediately
    $_SESSION['subscription_plan'] = $plan;

    echo json_encode([
        'success' => true,
        'message' => 'Plan updated to ' . $plan,
        'plan'    => $plan
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
