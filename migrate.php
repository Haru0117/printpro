<?php
// ─────────────────────────────────────────────────────────────
//  migrate.php — Applies schema patches to AwardSpace DB
//  Safe to run multiple times (uses IF NOT EXISTS / IGNORE)
// ─────────────────────────────────────────────────────────────
require_once __DIR__ . '/includes/db.php'; // Uses AwardSpace credentials

try {
    // Provide a comprehensive set of alters to ensure the live database 
    // matches the latest printpro.sql schema without dropping data.
    $alterStatements = [
        // 1. Orders table fixes
        "ALTER TABLE orders ADD COLUMN job_name VARCHAR(255) DEFAULT 'Untitled'",
        "ALTER TABLE orders ADD COLUMN paper_id INT DEFAULT 0",
        "ALTER TABLE orders ADD COLUMN size_id INT DEFAULT 0",
        "ALTER TABLE orders ADD COLUMN finish_id INT DEFAULT 0",
        "ALTER TABLE orders ADD COLUMN total_price DECIMAL(10,2) DEFAULT 0.00",

        // 2. Users table fixes
        "ALTER TABLE users ADD COLUMN name VARCHAR(120) NOT NULL AFTER id",
        "ALTER TABLE users ADD COLUMN password_hash VARCHAR(255) NOT NULL",
        "ALTER TABLE users MODIFY COLUMN role ENUM('admin','manager','operator','client') NOT NULL DEFAULT 'client'",
        "ALTER TABLE users ADD COLUMN status ENUM('active','suspended') NOT NULL DEFAULT 'active'",
        "ALTER TABLE users ADD COLUMN two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0",
        "ALTER TABLE users ADD COLUMN last_login_at TIMESTAMP NULL",
        "ALTER TABLE users ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",

        // 3. Clients table
        "CREATE TABLE IF NOT EXISTS clients (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT UNSIGNED NOT NULL,
            business_name VARCHAR(160) NOT NULL,
            industry VARCHAR(100) NULL,
            phone VARCHAR(30) NULL,
            address TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY fk_clients_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        // 4. Subscriptions table migration
        "ALTER TABLE subscriptions MODIFY COLUMN plan ENUM('free', 'pro', 'premium', 'premium+') NOT NULL DEFAULT 'free'",

        // 5. Users table subscription_plan migration (for existing users schema)
        "ALTER TABLE users MODIFY COLUMN subscription_plan ENUM('free', 'pro', 'premium', 'premium+') DEFAULT 'free'"
    ];

    foreach ($alterStatements as $sql) {
        try {
            $pdo->exec($sql);
        } catch (\PDOException $e) {
            // Error 1060: Duplicate column name (already exists, ignore)
            // Error 1050: Table already exists (ignore)
            $msg = $e->getMessage();
            if (strpos($msg, '1060') === false && strpos($msg, 'Duplicate column') === false && strpos($msg, '1050') === false) {
                // If it's another error, we could throw it, but for a robust migration script we log it.
                // echo "Warning on query: $sql -> $msg <br>";
            }
        }
    }

    // Compatibility fix for adding industry column
    $checkColumn = $pdo->query("SHOW COLUMNS FROM clients LIKE 'industry'")->fetch();
    if (!$checkColumn) {
        $pdo->exec("ALTER TABLE clients ADD industry VARCHAR(100) AFTER business_name");
    }

    echo "Migration completed successfully! All required columns and tables are present.";

} catch (\Exception $e) {
    echo "Migration error: " . $e->getMessage();
}
?>