<?php
/**
 * Spec Management API — PrintPro
 * Actual schema:
 *   tbl_materials: id, name, multiplier
 *   tbl_finishes:  id, name, setup_fee, per_unit_fee
 *   tbl_sizes:     id, name, multiplier
 */
session_start();
error_reporting(0);
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// For GET (used by client loadOrderSpecs), allow any logged-in user
// For write ops, require admin
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    if (!is_admin()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
}

function getTableName($type)
{
    if ($type === 'paper')
        return 'tbl_materials';
    if ($type === 'finish')
        return 'tbl_finishes';
    if ($type === 'size')
        return 'tbl_sizes';
    return null;
}

try {
    if ($method === 'GET') {
        // Returns all specs — used by both Client Dashboard (loadOrderSpecs)
        // and Admin Dashboard (Spec Management page)
        $specs = [];

        $stmt = $pdo->query("SELECT id, name, multiplier AS price_modifier, NULL AS setup_fee, NULL AS per_unit_fee, 1 AS is_active, 'paper' AS spec_type FROM tbl_materials ORDER BY name");
        $specs = array_merge($specs, $stmt->fetchAll());

        $stmt = $pdo->query("SELECT id, name, multiplier AS price_modifier, NULL AS setup_fee, NULL AS per_unit_fee, 1 AS is_active, 'size' AS spec_type FROM tbl_sizes ORDER BY name");
        $specs = array_merge($specs, $stmt->fetchAll());

        $stmt = $pdo->query("SELECT id, name, setup_fee AS price_modifier, setup_fee, per_unit_fee, 1 AS is_active, 'finish' AS spec_type FROM tbl_finishes ORDER BY name");
        $specs = array_merge($specs, $stmt->fetchAll());

        echo json_encode(['success' => true, 'data' => $specs]);

    } elseif ($method === 'POST') {
        // Add new spec
        $type = $_POST['spec_type'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $price = floatval($_POST['price_modifier'] ?? 0);
        $table = getTableName($type);

        if (!$table || !$name)
            throw new Exception('Invalid spec type or name');

        if ($type === 'finish') {
            $per_unit = floatval($_POST['per_unit_fee'] ?? 0);
            $stmt = $pdo->prepare("INSERT INTO $table (name, setup_fee, per_unit_fee) VALUES (?, ?, ?)");
            $stmt->execute([$name, $price, $per_unit]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO $table (name, multiplier) VALUES (?, ?)");
            $stmt->execute([$name, $price]);
        }
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);

    } elseif ($method === 'PUT') {
        parse_str(file_get_contents("php://input"), $put);
        $id = intval($put['id'] ?? 0);
        $type = $put['spec_type'] ?? '';
        $name = trim($put['name'] ?? '');
        $price = floatval($put['price_modifier'] ?? 0);
        $table = getTableName($type);

        if (!$table || !$id)
            throw new Exception('Invalid data');

        if ($type === 'finish') {
            $per_unit = floatval($put['per_unit_fee'] ?? 0);
            $stmt = $pdo->prepare("UPDATE $table SET name=?, setup_fee=?, per_unit_fee=? WHERE id=?");
            $stmt->execute([$name, $price, $per_unit, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE $table SET name=?, multiplier=? WHERE id=?");
            $stmt->execute([$name, $price, $id]);
        }
        echo json_encode(['success' => true]);

    } elseif ($method === 'DELETE') {
        parse_str(file_get_contents("php://input"), $del);
        $id = intval($del['id'] ?? 0);
        $type = $del['spec_type'] ?? '';
        $table = getTableName($type);

        if (!$table || !$id)
            throw new Exception('Invalid data');

        $stmt = $pdo->prepare("DELETE FROM $table WHERE id=?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>