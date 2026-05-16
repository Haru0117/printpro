<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';
    $role = $_POST['role'] ?? '';
    $status = $_POST['status'] ?? '';

    if (empty($id) || empty($name) || empty($role) || empty($status)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    try {
        ob_start();
        
        // 1. Fetch current user data
        $currStmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $currStmt->execute([$id]);
        $currentRole = strtolower($currStmt->fetchColumn() ?: '');

        $roleLower = strtolower($role);
        $isPromotingToAdmin = ($currentRole === 'client' && $roleLower === 'admin');

        $pdo->beginTransaction();

        // 2. If promoting from client to admin, purge client-specific data
        if ($isPromotingToAdmin) {
            // Find client_id
            $cStmt = $pdo->prepare("SELECT id FROM clients WHERE user_id = ?");
            $cStmt->execute([$id]);
            $client_id = $cStmt->fetchColumn();

            if ($client_id) {
                // Individual try-catches for optional tables to prevent failure if tables are missing
                try {
                    $pdo->prepare("DELETE FROM invoices WHERE client_id = ?")->execute([$client_id]);
                } catch (Exception $e) {}

                try {
                    $pdo->prepare("DELETE FROM credit_transactions WHERE client_id = ?")->execute([$client_id]);
                } catch (Exception $e) {}

                try {
                    $pdo->prepare("DELETE FROM client_credits WHERE client_id = ?")->execute([$client_id]);
                } catch (Exception $e) {}
                
                try {
                    $pdo->prepare("DELETE FROM orders WHERE client_id = ?")->execute([$client_id]);
                } catch (Exception $e) {}

                try {
                    $pdo->prepare("DELETE FROM clients WHERE id = ?")->execute([$client_id]);
                } catch (Exception $e) {}
            }
            
            try {
                $pdo->prepare("DELETE FROM notifications WHERE user_id = ?")->execute([$id]);
            } catch (Exception $e) {}
        }

        // 3. Update primary user record
        $stmt = $pdo->prepare("UPDATE users SET name = ?, role = ?, status = ? WHERE id = ?");
        $stmt->execute([$name, $role, $status, $id]);

        $pdo->commit();
        ob_clean();

        $msg = 'User updated successfully';
        if ($isPromotingToAdmin) $msg .= ' and previous client data has been purged.';
        
        echo json_encode(['success' => true, 'message' => $msg]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
