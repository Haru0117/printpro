<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// All specs endpoints (except GET for clients) require admin role
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Optional type filter
        $type = $_GET['type'] ?? null;
        if ($type) {
            $stmt = $pdo->prepare("SELECT * FROM print_specs WHERE spec_type = ? AND is_active = 1");
            $stmt->execute([$type]);
        } else {
            $stmt = $pdo->query("SELECT * FROM print_specs");
        }
        $specs = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $specs]);
        break;

    case 'POST':
        $type = $_POST['spec_type'] ?? '';
        $name = $_POST['name'] ?? '';
        $price = $_POST['price_modifier'] ?? 0;

        if (empty($type) || empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO print_specs (spec_type, name, price_modifier) VALUES (?, ?, ?)");
        if ($stmt->execute([$type, $name, $price])) {
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to insert spec']);
        }
        break;

    case 'PUT':
        parse_str(file_get_contents("php://input"), $put_vars);
        $id = $put_vars['id'] ?? '';
        $name = $put_vars['name'] ?? '';
        $price = $put_vars['price_modifier'] ?? '';
        $is_active = $put_vars['is_active'] ?? 1;

        if (empty($id) || empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE print_specs SET name = ?, price_modifier = ?, is_active = ? WHERE id = ?");
        if ($stmt->execute([$name, $price, $is_active, $id])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update spec']);
        }
        break;

    case 'DELETE':
        parse_str(file_get_contents("php://input"), $del_vars);
        $id = $del_vars['id'] ?? '';

        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'Missing ID']);
            exit;
        }

        // Soft delete
        $stmt = $pdo->prepare("UPDATE print_specs SET is_active = 0 WHERE id = ?");
        if ($stmt->execute([$id])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete spec']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid method']);
        break;
}
?>
