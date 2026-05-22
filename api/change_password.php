<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $user_id = $_SESSION['user_id'];

    if (empty($current_password) || empty($new_password)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all fields']);
        exit;
    }

    try {
        // Fetch current password hash
        $stmt = $pdo->prepare("SELECT password_hash, password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }

        $storedHash = $user['password_hash'] ?? '';
        $storedPlain = $user['password'] ?? '';
        $is_correct = false;

        if (!empty($storedHash) && password_verify($current_password, $storedHash)) {
            $is_correct = true;
        } elseif ($current_password === $storedHash || $current_password === $storedPlain) {
            $is_correct = true;
        }

        if (!$is_correct) {
            echo json_encode(['success' => false, 'message' => 'Incorrect current password']);
            exit;
        }

        // Update password (hash it!)
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, password = ? WHERE id = ?");
        $stmt->execute([$new_hash, $new_hash, $user_id]);

        echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
