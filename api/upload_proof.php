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

if (!isset($_SESSION['user_id']) || !in_array(strtolower($_SESSION['role'] ?? ''), ['admin', 'super_admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get POST parameters
$order_id = intval($_POST['order_id'] ?? 0);

if (!$order_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID.']);
    exit;
}

if (!isset($_FILES['proof_file']) || $_FILES['proof_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No proof file uploaded.']);
    exit;
}

try {
    // 1. Fetch order details to verify existence
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found.']);
        exit;
    }

    $file = $_FILES['proof_file'];
    $fileName = basename($file['name']);
    $fileSize = $file['size'];
    $tmpName = $file['tmp_name'];

    // Check size limit (500MB)
    if ($fileSize > 500 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Proof file exceeds the 500MB size limit.']);
        exit;
    }

    // Check extension
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = ['pdf', 'ai', 'psd', 'png', 'jpg', 'jpeg'];
    if (!in_array($ext, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file format. Supported: PDF, AI, PSD, PNG, JPG.']);
        exit;
    }

    // Define directory paths
    $uploadDir = '../uploads/proofs/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Make the file name unique
    $safeFileName = preg_replace('/[^a-zA-Z0-9_.-]/', '_', pathinfo($fileName, PATHINFO_FILENAME));
    $newFileName = 'proof_' . time() . '_' . $safeFileName . '.' . $ext;
    $destPath = $uploadDir . $newFileName;

    if (move_uploaded_file($tmpName, $destPath)) {
        $proof_path = 'uploads/proofs/' . $newFileName;

        // Update database: status = 'Proof Pending Review', proof_file = path
        $stmt = $pdo->prepare("UPDATE orders SET status = 'Proof Pending Review', proof_file = ?, proof_requested_at = NOW() WHERE id = ?");
        $stmt->execute([$proof_path, $order_id]);

        echo json_encode(['success' => true, 'message' => 'Print proof uploaded successfully.', 'proof_file' => $proof_path]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save uploaded print proof file.']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
