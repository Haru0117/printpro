<?php
session_start();
echo "<h1>PrintPro Credits System - Diagnostic Check</h1>";
echo "<style>body { font-family: Arial; margin: 20px; } .ok { color: green; } .error { color: red; } .warning { color: orange; } .info { background: #f0f0f0; padding: 10px; margin: 10px 0; }</style>";

require_once 'includes/db.php';

echo "<h2>1. Database Tables Check</h2>";
$tables_ok = true;
foreach (['client_credits', 'credit_transactions'] as $table) {
    $result = $pdo->query("SHOW TABLES LIKE '$table'");
    if ($result->rowCount() > 0) {
        echo "<div class='ok'>✓ Table '$table' exists</div>";
    } else {
        echo "<div class='error'>✗ Table '$table' does NOT exist</div>";
        $tables_ok = false;
    }
}

if (!$tables_ok) {
    echo "<div class='info'><strong>⚠ Action Required:</strong> Run <a href='migrate.php' style='color: blue; text-decoration: underline;'>migrate.php</a> to create the tables.</div>";
}

echo "<h2>2. User Session Check</h2>";
if (isset($_SESSION['user_id'])) {
    echo "<div class='ok'>✓ User ID: " . $_SESSION['user_id'] . "</div>";
} else {
    echo "<div class='error'>✗ No user logged in. <a href='index.html#login' style='color: blue; text-decoration: underline;'>Login here</a></div>";
}

if ($tables_ok && isset($_SESSION['user_id'])) {
    echo "<h2>3. Client Profile Check</h2>";
    try {
        $stmt = $pdo->prepare("SELECT id FROM clients WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $client = $stmt->fetch();
        if ($client) {
            echo "<div class='ok'>✓ Client profile found. Client ID: " . $client['id'] . "</div>";
        } else {
            echo "<div class='error'>✗ No client profile for this user</div>";
            echo "<div class='info'>This usually happens after user registration. The client profile should be auto-created.</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>✗ Error checking client: " . htmlspecialchars($e->getMessage()) . "</div>";
    }

    echo "<h2>4. Credit Balance Check</h2>";
    try {
        $stmt = $pdo->prepare("SELECT cc.balance FROM client_credits cc JOIN clients c ON cc.client_id = c.id WHERE c.user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $row = $stmt->fetch();
        if ($row) {
            $balance = floatval($row['balance']);
            echo "<div class='ok'>✓ Credit balance found: ₱" . number_format($balance, 2) . "</div>";
        } else {
            echo "<div class='warning'>⚠ No credit record for this user. Default ₱10,000 should be created when registering.</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>✗ Error fetching credits: " . htmlspecialchars($e->getMessage()) . "</div>";
    }

    echo "<h2>5. API Endpoint Test</h2>";
    echo "<div class='info'>Check your browser console (F12) for API response. Look for 'Credits API response' message.</div>";
    echo "<button onclick='testAPI()'>Test API Now</button>";
}

echo "<h2>6. Recommended Steps if Credits Don't Show:</h2>";
echo "<div class='info'>";
echo "<ol>";
echo "<li>Run <a href='migrate.php' style='color: blue;'>migrate.php</a> to create tables</li>";
echo "<li>Log out and log back in to refresh session</li>";
echo "<li>Open browser developer tools (F12)</li>";
echo "<li>Go to Console tab and look for 'Credits API response'</li>";
echo "<li>Check if response shows success: true or error message</li>";
echo "<li>Check the Network tab - is get_credits.php being called?</li>";
echo "</ol>";
echo "</div>";

echo "<script>";
echo "function testAPI() {";
echo "  fetch('api/get_credits.php').then(r => r.json()).then(d => {";
echo "    console.log('Credits API response:', d);";
echo "    alert('Check browser console (F12) for response details');";
echo "  }).catch(e => alert('API Error: ' + e));";
echo "}";
echo "</script>";
?>
