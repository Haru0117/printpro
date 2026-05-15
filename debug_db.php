<?php
// ─────────────────────────────────────────────────────────────
//  PrintPro — Advanced Diagnostic Tool
// ─────────────────────────────────────────────────────────────
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/db.php';

echo "<h2>PrintPro Database Diagnostic</h2>";
echo "<pre>";
echo "Environment Check:\n";
echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'N/A') . "\n";
echo "SERVER_ADDR: " . ($_SERVER['SERVER_ADDR'] ?? 'N/A') . "\n";
echo "Detected Local: " . ($isLocal ? "YES" : "NO") . "\n";
echo "Connecting to Host: $host\n";
echo "Database Name: $db\n";

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "\nConnection Status: SUCCESS\n";

    // Check Tables
    $tables = ['users', 'clients', 'orders', 'tbl_materials', 'plans'];
    foreach ($tables as $t) {
        try {
            $count = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
            echo "Table [$t]: $count rows found.\n";
        } catch (Exception $e) {
            echo "Table [$t]: ERROR - " . $e->getMessage() . "\n";
        }
    }

    // Sample Order Check
    echo "\nRecent Orders Sample:\n";
    $stmt = $pdo->query("SELECT id, client_id, status, total_amount FROM orders ORDER BY id DESC LIMIT 5");
    $orders = $stmt->fetchAll();
    if ($orders) {
        print_r($orders);
    } else {
        echo "No orders found in table.\n";
    }

    // Role Check for current session
    session_start();
    echo "\nSession Status:\n";
    echo "User ID: " . ($_SESSION['user_id'] ?? 'NONE') . "\n";
    echo "Role: " . ($_SESSION['role'] ?? 'NONE') . "\n";

} catch (Exception $e) {
    echo "\nConnection Status: FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
}
echo "</pre>";
?>
