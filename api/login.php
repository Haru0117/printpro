<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');
ob_clean();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT u.*, c.business_name, c.industry 
        FROM users u 
        LEFT JOIN clients c ON u.id = c.user_id 
        WHERE u.email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && (password_verify($password, $user['password_hash']) || $password === $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];

        if ($user['role'] === 'admin' || $user['role'] === 'manager' || $user['role'] === 'operator') {
            $redirect = 'admin_dashboard.html';
            $portal = 'admin';
        } else {
            $redirect = 'client_dashboard.html';
            $portal = 'client';
        }

        echo json_encode([
            'success' => true, 
            'redirect' => $redirect, 
            'role' => $user['role'], 
            'portal' => $portal, 
            'username' => $user['username'], 
            'name' => $user['name'],
            'email' => $user['email'],
            'business_name' => $user['business_name'] ?? '',
            'industry' => $user['industry'] ?? ''
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>