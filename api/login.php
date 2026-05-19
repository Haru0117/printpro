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
        SELECT u.*, c.id as client_id, c.business_name, c.industry 
        FROM users u 
        LEFT JOIN clients c ON u.id = c.user_id 
        WHERE u.email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && (password_verify($password, $user['password_hash']) || $password === $user['password_hash'])) {
        // Task: Check if account is suspended
        if (isset($user['status']) && strtolower($user['status']) === 'suspended') {
            echo json_encode(['success' => false, 'message' => 'Your account has been suspended. Please contact support.']);
            exit;
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name']    = $user['name'];
        $_SESSION['email']   = $user['email'];
        $_SESSION['role']    = $user['role'];

        // Store client_id for client-role users
        $client_id = null;
        if (strtolower($user['role']) === 'client') {
            $cstmt = $pdo->prepare('SELECT id FROM clients WHERE user_id = ?');
            $cstmt->execute([$user['id']]);
            $crow = $cstmt->fetch();
            $client_id = $crow ? (int)$crow['id'] : null;
        }
        $_SESSION['client_id'] = $client_id;

        $role = strtolower($user['role']);
        if ($role === 'admin' || $role === 'super_admin' || $role === 'manager' || $role === 'operator') {
            $redirect = 'admin/';
            $portal = 'admin';
        } else {
            $redirect = 'client/';
            $portal = 'client';
        }

        echo json_encode([
            'success'           => true,
            'id'                => $user['id'],
            'redirect'          => $redirect,
            'role'              => $user['role'],
            'portal'            => $portal,
            'name'              => $user['name'],
            'email'             => $user['email'],
            'client_id'         => $client_id,
            'business_name'     => $user['business_name'] ?? '',
            'industry'          => $user['industry'] ?? '',
            'subscription_plan' => $user['subscription_plan'] ?? 'pro'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>