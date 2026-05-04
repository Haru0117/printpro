<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $company_name = $_POST['business_name'] ?? '';
    $password = $_POST['password'] ?? '';
    $terms = $_POST['terms'] ?? '';

    if (empty($name) || empty($email) || empty($password) || empty($terms)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
        exit;
    }

    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email already registered.']);
        exit;
    }

    // We are storing plain text passwords as requested
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'client')");
    
    try {
        $stmt->execute([$name, $email, $password]);
        $user_id = $pdo->lastInsertId();
        
        // Insert into clients table
        $stmt_client = $pdo->prepare("INSERT INTO clients (user_id, business_name) VALUES (?, ?)");
        $stmt_client->execute([$user_id, $company_name]);
        
        // Log them in immediately
        $_SESSION['user_id'] = $user_id;
        $_SESSION['role'] = 'client';
        $_SESSION['name'] = $name;

        echo json_encode(['success' => true, 'redirect' => 'Client Dashboard.html']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>