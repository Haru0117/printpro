<?php
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $company_name = $_POST['business_name'] ?? '';
    $password = $_POST['password'] ?? '';
    $terms = $_POST['terms'] ?? '';
    $plan = strtolower($_POST['plan'] ?? 'pro');

    if (empty($name) || empty($email) || empty($password) || empty($terms)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
        exit;
    }

    try {
        // Derive username from email
        $username = explode('@', $email)[0];

        // Check if email OR username already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'This email or username is already registered. Please use a different one.']);
            exit;
        }

        // We are storing passwords in both columns as requested
        $stmt = $pdo->prepare("INSERT INTO users (name, username, email, password, password_hash, role, subscription_plan) VALUES (?, ?, ?, ?, ?, 'client', ?)");

        $stmt->execute([$name, $username, $email, $password, $password, $plan]);
        $user_id = $pdo->lastInsertId();

        // Insert into clients table
        $stmt_client = $pdo->prepare("INSERT INTO clients (user_id, business_name) VALUES (?, ?)");
        $stmt_client->execute([$user_id, $company_name]);
        $client_id = $pdo->lastInsertId();



        // Log them in immediately
        $_SESSION['user_id'] = $user_id;
        $_SESSION['role'] = 'client';
        $_SESSION['name'] = $name;

        echo json_encode([
            'success' => true, 
            'id' => $user_id,
            'role' => 'client',
            'name' => $name,
            'email' => $email,
            'redirect' => 'client_dashboard.html'
        ]);
    } catch (PDOException $e) {
        // Identify missing columns
        if (strpos($e->getMessage(), "Unknown column 'name'") !== false) {
            // Attempt fallback to full_name or username if necessary, or just report
            echo json_encode(['success' => false, 'message' => 'Database schema error: name column missing.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
        }
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>