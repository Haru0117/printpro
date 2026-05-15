<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$order_id = $_GET['id'] ?? '';

if (empty($order_id)) {
    echo json_encode(['success' => false, 'message' => 'Missing Order ID']);
    exit;
}

try {
    // Task 3: Fetch specific order data with client details and specs
    $stmt = $pdo->prepare("SELECT 
                                o.*, 
                                COALESCE(c.business_name, u.name) as client_name,
                                u.email as client_email,
                                m.name as paper_name,
                                s.name as size_name,
                                f.name as finish_name
                             FROM orders o 
                             LEFT JOIN clients c ON o.client_id = c.id 
                             LEFT JOIN users u ON c.user_id = u.id
                             LEFT JOIN tbl_materials m ON o.paper_id = m.id
                             LEFT JOIN tbl_sizes s ON o.size_id = s.id
                             LEFT JOIN tbl_finishes f ON o.finish_id = f.id
                             WHERE o.id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($order) {
        // Build Size Label
        $sizeLabel = $order['size_name'] ?: 'Custom';
        if (!empty($order['size_width']) && !empty($order['size_height'])) {
            $sizeLabel .= " (" . $order['size_width'] . "\" x " . $order['size_height'] . "\")";
        }

        // Add specs array for JS compatibility
        $order['specs'] = [
            ['spec_type' => 'Material', 'name' => $order['paper_name'] ?: ($order['paper_weight'] ?: 'Standard')],
            ['spec_type' => 'Size', 'name' => $sizeLabel],
            ['spec_type' => 'Finish', 'name' => $order['finish_name'] ?: ($order['finish'] ?: 'None')],
            ['spec_type' => 'Sides', 'name' => $order['print_sides'] ?: 'Double Sided']
        ];
    }

    if (empty($order)) {
        echo json_encode(['success' => false, 'message' => 'No Data Found']);
    } else {
        echo json_encode(['success' => true, 'data' => $order]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
