<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['id'] ?? '';
    $status = $_POST['status'] ?? '';

    if (empty($user_id) || empty($status)) {
        echo json_encode(['success' => false, 'message' => 'User ID and Status are required']);
        exit;
    }

    // Sanitize status
    $status = (strtolower($status) === 'active') ? 'active' : 'suspended';

    try {
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$status, $user_id]);

        echo json_encode([
            'success' => true, 
            'message' => 'Account status updated to ' . $status,
            'new_status' => $status
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
