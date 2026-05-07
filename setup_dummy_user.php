<?php
require_once __DIR__ . '/includes/db.php';

try {
    // The password we want to use
    $plainPassword = 'password123';

    // Generate a real bcrypt hash!
    $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 12]);

    // Check if the user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = 'client@example.com'");
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        echo "<h1>Account already exists!</h1>";
        echo "<p>Email: <b>client@example.com</b><br>Password: <b>password123</b></p>";
        exit;
    }

    // Insert into `users` table
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'client')");
    $stmt->execute(['Test Client', 'client@example.com', $hashedPassword]);

    // Get the new user ID
    $userId = $pdo->lastInsertId();

    // Insert into `clients` table so their client profile is complete
    $stmt = $pdo->prepare("INSERT INTO clients (user_id, business_name, industry) VALUES (?, ?, ?)");
    $stmt->execute([$userId, 'Test Business LLC', 'Retail']);

    echo "<h1>Success! Dummy account created.</h1>";
    echo "<p>You can now log in to the client portal using:</p>";
    echo "<ul>";
    echo "<li><b>Email:</b> client@example.com</li>";
    echo "<li><b>Password:</b> password123</li>";
    echo "</ul>";
    echo "<p><i>You can delete this setup_dummy_user.php file now.</i></p>";

} catch (PDOException $e) {
    echo "<h1>Database Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>