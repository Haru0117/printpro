<?php
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

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Default role is client
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, company_name, role) VALUES (?, ?, ?, ?, 'client')");
    
    try {
        $stmt->execute([$name, $email, $hashed_password, $company_name]);
        $user_id = $pdo->lastInsertId();
        
        // Log them in immediately
        $_SESSION['user_id'] = $user_id;
        $_SESSION['role'] = 'client';
        $_SESSION['name'] = $name;

        echo json_encode(['success' => true, 'redirect' => 'Client Dashboard.html']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again later.']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>