<?php
session_start();
require_once 'includes/db.php';

echo "<h1>PrintPro Credits System Test</h1>";
echo "<hr>";

try {
    // Check if tables exist
    $tables = ['client_credits', 'credit_transactions'];
    foreach ($tables as $table) {
        $result = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($result->rowCount() > 0) {
            echo "✓ Table '$table' exists<br>";
        } else {
            echo "✗ Table '$table' does NOT exist<br>";
        }
    }

    echo "<hr>";
    echo "<h2>Sample Data</h2>";

    // Count records
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM client_credits");
    $row = $stmt->fetch();
    echo "Client Credits Records: " . $row['cnt'] . "<br>";

    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM credit_transactions");
    $row = $stmt->fetch();
    echo "Credit Transactions Records: " . $row['cnt'] . "<br>";

    echo "<hr>";
    echo "<h2>Sample Client Credits</h2>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Client ID</th><th>Balance</th><th>Updated</th></tr>";
    $stmt = $pdo->query("SELECT * FROM client_credits LIMIT 5");
    while ($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td>" . $row['client_id'] . "</td>";
        echo "<td>₱" . number_format($row['balance'], 2) . "</td>";
        echo "<td>" . $row['updated_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<hr>";
    echo "<h2>Recent Transactions</h2>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Client</th><th>Type</th><th>Amount</th><th>Description</th><th>Date</th></tr>";
    $stmt = $pdo->query("
        SELECT ct.*, c.id as client_id 
        FROM credit_transactions ct
        JOIN clients c ON ct.client_id = c.id
        ORDER BY ct.created_at DESC 
        LIMIT 10
    ");
    while ($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td>" . $row['client_id'] . "</td>";
        echo "<td>" . ucfirst($row['transaction_type']) . "</td>";
        echo "<td>₱" . number_format($row['amount'], 2) . "</td>";
        echo "<td>" . htmlspecialchars($row['description']) . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<hr>";
    echo "<p><a href='index.php'>Back to Dashboard</a></p>";

} catch (Exception $e) {
    echo "<h2 style='color:red;'>Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please run <strong>migrate.php</strong> to create the credits tables.</p>";
    echo "<p><a href='migrate.php'>Run Migration</a></p>";
}
?>
