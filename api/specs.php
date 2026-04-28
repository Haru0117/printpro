<?php
session_start();
error_reporting(0);
require_once '../includes/db.php';

header('Content-Type: application/json');

try {
    // Check if tables exist
    $check = $pdo->query("SHOW TABLES LIKE 'tbl_materials'");
    if ($check->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'Migration required']);
        exit;
    }

    $specs = [];

    // Fetch Materials
    $stmt = $pdo->query("SELECT id, name, 'paper' as spec_type FROM tbl_materials");
    $specs = array_merge($specs, $stmt->fetchAll());

    // Fetch Sizes
    $stmt = $pdo->query("SELECT id, name, 'size' as spec_type FROM tbl_sizes");
    $specs = array_merge($specs, $stmt->fetchAll());

    // Fetch Finishes
    $stmt = $pdo->query("SELECT id, name, 'finish' as spec_type FROM tbl_finishes");
    $specs = array_merge($specs, $stmt->fetchAll());

    echo json_encode(['success' => true, 'data' => $specs]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>