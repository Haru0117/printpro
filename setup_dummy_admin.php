<?php
/**
 * PrintPro — Setup Dummy Admin
 * Run this file once in your browser to create a test admin account.
 * URL: http://localhost/printpro/setup_dummy_admin.php
 */

require_once __DIR__ . '/includes/db.php';

header('Content-Type: text/html; charset=utf-8');

try {
    // 1. Configuration for the dummy admin
    $adminName = 'System Administrator';
    $adminEmail = 'admin@example.com';
    $adminPassword = 'password123';
    $adminRole = 'admin'; // Other valid roles: 'manager', 'operator'

    // 2. Hash the password using bcrypt (matching api/login.php logic)
    $hashedPassword = password_hash($adminPassword, PASSWORD_BCRYPT, ['cost' => 12]);

    // 3. Check if the admin already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$adminEmail]);
    $existingUser = $stmt->fetch();

    echo "<!DOCTYPE html><html><head><title>Setup Dummy Admin</title>";
    echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
    echo "<style>body { background: #f4f6fb; padding: 50px; font-family: sans-serif; } .card { max-width: 500px; margin: auto; border-radius: 15px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }</style></head><body>";

    echo "<div class='card p-4'>";
    
    if ($existingUser) {
        echo "<h2 class='text-warning'>Admin Already Exists!</h2>";
        echo "<p class='lead'>An account with this email is already in the database.</p>";
        echo "<div class='alert alert-secondary'>";
        echo "<strong>Email:</strong> " . htmlspecialchars($adminEmail) . "<br>";
        echo "<strong>Password:</strong> " . htmlspecialchars($adminPassword);
        echo "</div>";
    } else {
        // 4. Insert into `users` table
        // We use 'password_hash' column as seen in setup_dummy_user.php and api/login.php
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$adminName, $adminEmail, $hashedPassword, $adminRole]);

        echo "<h2 class='text-success'>Success! Admin Created.</h2>";
        echo "<p class='lead'>A dummy administrator account has been added to the system.</p>";
        echo "<div class='alert alert-primary'>";
        echo "<strong>Email:</strong> " . htmlspecialchars($adminEmail) . "<br>";
        echo "<strong>Password:</strong> " . htmlspecialchars($adminPassword);
        echo "</div>";
    }

    echo "<hr>";
    echo "<p class='text-muted small'>You can now use these credentials to log in via the main landing page.</p>";
    echo "<div class='d-grid gap-2'>";
    echo "<a href='index.php' class='btn btn-outline-primary'>Go to Home</a>";
    echo "<a href='Admin Dashboard.html' class='btn btn-primary'>Go to Admin Dashboard</a>";
    echo "</div>";
    echo "<p class='mt-3 text-center'><small class='text-danger'>IMPORTANT: Delete this file after use for security.</small></p>";
    echo "</div>";
    echo "</body></html>";

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'><h1>Database Error</h1><p>" . htmlspecialchars($e->getMessage()) . "</p></div>";
}
?>
