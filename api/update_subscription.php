<?php
// ─────────────────────────────────────────────────────────────
//  PrintPro — Update Subscription Plan
//  Client: POST { plan: 'Pro'|'Premium'|'Premium+' }
//  Admin:  POST { plan: '...', user_id: 123 }
// ─────────────────────────────────────────────────────────────
session_start();
header('Content-Type: application/json');

require_once '../includes/db.php';
require_once '../includes/auth.php';

$input = json_decode(file_get_contents('php://input'), true) ?? [];

// Normalize plan
$planMap = ['pro' => 'Pro', 'premium' => 'Premium', 'premium+' => 'Premium+'];
$plan    = $planMap[strtolower(trim($input['plan'] ?? ''))] ?? '';

$allowed = ['Pro', 'Premium', 'Premium+'];
if (!in_array($plan, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Invalid plan: ' . ($input['plan'] ?? 'none')]);
    exit;
}

// Determine target user
// Admin path: must have valid session + is_admin() + explicit user_id in body
// Client path: valid PHP session OR valid user_id passed in body (for sessionStorage-based auth)
$targetUserId = null;

if (isset($_SESSION['user_id'])) {
    $targetUserId = (int)$_SESSION['user_id'];
    // Admin override
    if (!empty($input['user_id']) && is_admin()) {
        $targetUserId = (int)$input['user_id'];
    }
} elseif (!empty($input['user_id'])) {
    // Client dashboard uses sessionStorage — pass user_id in body
    $targetUserId = (int)$input['user_id'];
} else {
    echo json_encode(['success' => false, 'message' => 'Unauthorized — no session or user_id provided.']);
    exit;
}

if (!$targetUserId) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE users SET subscription_plan = ? WHERE id = ?");
    $stmt->execute([$plan, $targetUserId]);

    if (isset($_SESSION['user_id']) && $targetUserId === (int)$_SESSION['user_id']) {
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
