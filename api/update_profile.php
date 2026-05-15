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
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $business_name = $_POST['business_name'] ?? '';
    $industry = $_POST['industry'] ?? '';
    $user_id = $_SESSION['user_id'];

    if (empty($name) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Name and email are required']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Update users table
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->execute([$name, $email, $user_id]);

        // Update clients table
        $stmt_client = $pdo->prepare("UPDATE clients SET business_name = ?, industry = ? WHERE user_id = ?");
        $stmt_client->execute([$business_name, $industry, $user_id]);

        $pdo->commit();

        // Update session variables
        $_SESSION['name'] = $name;
        $_SESSION['email'] = $email;
        $_SESSION['business_name'] = $business_name;
        $_SESSION['industry'] = $industry;

        echo json_encode([
            'success' => true, 
            'message' => 'Profile updated successfully',
            'data' => [
                'name' => $name, 
                'email' => $email,
                'business_name' => $business_name,
                'industry' => $industry
            ]
        ]);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if ($e->getCode() == 23000) {
            echo json_encode(['success' => false, 'message' => 'Email address is already in use']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
