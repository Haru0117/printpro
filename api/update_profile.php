<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$name = $_POST['name'] ?? '';
$business_name = $_POST['business_name'] ?? '';
$industry = $_POST['industry'] ?? '';

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Name cannot be empty']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Update users table (Name)
    $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
    $stmt->execute([$name, $user_id]);

    // 2. Update/Insert clients table (Business & Industry)
    // Check if client record exists
    $stmt = $pdo->prepare("SELECT id FROM clients WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $client = $stmt->fetch();

    if ($client) {
        $stmt = $pdo->prepare("UPDATE clients SET business_name = ?, industry = ? WHERE user_id = ?");
        $stmt->execute([$business_name, $industry, $user_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO clients (user_id, business_name, industry) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $business_name, $industry]);
    }

    $pdo->commit();

    // Update session name if it changed
    $_SESSION['name'] = $name;

    echo json_encode([
        'success' => true, 
        'message' => 'Profile updated successfully',
        'data' => [
            'name' => $name,
            'business_name' => $business_name,
            'industry' => $industry
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
