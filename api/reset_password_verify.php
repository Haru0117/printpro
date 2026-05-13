<?php
// ─────────────────────────────────────────────────────────────
//  reset_password_verify.php — Verifies token validity
// ─────────────────────────────────────────────────────────────
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$token = $_GET['token'] ?? '';

if (empty($token)) {
    echo json_encode(['success' => false, 'message' => 'Missing token.']);
    exit;
}

try {
    $tokenHash = hash('sha256', $token);

    $stmt = $pdo->prepare("SELECT email FROM password_reset_tokens WHERE token_hash = ? AND expires_at > NOW() LIMIT 1");
    $stmt->execute([$tokenHash]);
    $result = $stmt->fetch();

    if ($result) {
        echo json_encode(['success' => true, 'email' => $result['email']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Your password reset link is invalid or has expired.']);
    }
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Verification failed.']);
}
