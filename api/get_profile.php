<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.email, u.username, u.subscription_plan, 
               c.business_name, c.industry 
        FROM users u 
        LEFT JOIN clients c ON u.id = c.user_id 
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Normalize subscription_plan to correct casing
        $planMap = [
            'pro'       => 'Pro',
            'premium'   => 'Premium',
            'premium+'  => 'Premium+',
            'premiumplus' => 'Premium+',
            'premium plus' => 'Premium+',
        ];
        $rawPlan = strtolower(trim($user['subscription_plan'] ?? ''));
        if (isset($planMap[$rawPlan])) {
            $user['subscription_plan'] = $planMap[$rawPlan];
        }

        echo json_encode([
            'success' => true,
            'data' => $user
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
