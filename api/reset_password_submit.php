<?php
// ─────────────────────────────────────────────────────────────
//  reset_password_submit.php — Handles password update
// ─────────────────────────────────────────────────────────────
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm'] ?? '';

if (empty($token) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Token and password are required.']);
    exit;
}

if ($password !== $confirm) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
    exit;
}

// Password Strength Validation
if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long.']);
    exit;
}
if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
    echo json_encode(['success' => false, 'message' => 'Password must include uppercase, lowercase, and numbers.']);
    exit;
}

try {
    $tokenHash = hash('sha256', $token);

    // 1. Verify token one last time
    $stmt = $pdo->prepare("SELECT email FROM password_reset_tokens WHERE token_hash = ? AND expires_at > NOW() LIMIT 1");
    $stmt->execute([$tokenHash]);
    $resetData = $stmt->fetch();

    if (!$resetData) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired token.']);
        exit;
    }

    $email = $resetData['email'];

    // 2. Hash and update password
    $newHash = password_hash($password, PASSWORD_BCRYPT);
    
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE email = ?");
    $stmt->execute([$newHash, $email]);

    // 3. Delete the used token (Single-use security)
    $pdo->prepare("DELETE FROM password_reset_tokens WHERE email = ?")->execute([$email]);

    echo json_encode(['success' => true, 'message' => 'Password updated successfully! Redirecting...']);

} catch (\Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to reset password.']);
}
