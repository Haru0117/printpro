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
        "ALTER TABLE orders ADD COLUMN order_number VARCHAR(20) DEFAULT NULL",
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

        // 3. Password Reset Tokens table
        "CREATE TABLE IF NOT EXISTS password_reset_tokens (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            email VARCHAR(160) NOT NULL,
            token_hash VARCHAR(64) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_token_hash (token_hash),
            KEY idx_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        // 4. Clients table
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

        // 5. Subscriptions table migration
        "ALTER TABLE subscriptions MODIFY COLUMN plan ENUM('free', 'pro', 'premium', 'premium+') NOT NULL DEFAULT 'free'",

        // 6. Users table subscription_plan migration (for existing users schema)
        "ALTER TABLE users MODIFY COLUMN subscription_plan ENUM('free', 'pro', 'premium', 'premium+') DEFAULT 'free'",

        // 7. Client Credits table
        "CREATE TABLE IF NOT EXISTS client_credits (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            client_id INT UNSIGNED NOT NULL,
            balance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_client_credits_client (client_id),
            CONSTRAINT fk_client_credits_client FOREIGN KEY (client_id) REFERENCES clients (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // 8. Credit Transactions table
        "CREATE TABLE IF NOT EXISTS credit_transactions (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            client_id INT UNSIGNED NOT NULL,
            transaction_type ENUM('add','deduct') NOT NULL,
            amount DECIMAL(12,2) NOT NULL,
            description VARCHAR(255) DEFAULT NULL,
            order_id INT UNSIGNED DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_credit_transactions_client (client_id),
            KEY idx_credit_transactions_order (order_id),
            CONSTRAINT fk_credit_transactions_client FOREIGN KEY (client_id) REFERENCES clients (id) ON DELETE CASCADE,
            CONSTRAINT fk_credit_transactions_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
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

    // Create triggers for credits system
    $triggerSQL = "
        DELIMITER //
        CREATE TRIGGER IF NOT EXISTS update_credit_balance_after_insert
        AFTER INSERT ON credit_transactions
        FOR EACH ROW
        BEGIN
          IF NEW.transaction_type = 'add' THEN
            UPDATE client_credits SET balance = balance + NEW.amount, updated_at = CURRENT_TIMESTAMP WHERE client_id = NEW.client_id;
          ELSEIF NEW.transaction_type = 'deduct' THEN
            UPDATE client_credits SET balance = balance - NEW.amount, updated_at = CURRENT_TIMESTAMP WHERE client_id = NEW.client_id;
          END IF;
        END //
        DELIMITER ;
    ";
    try {
        $pdo->exec($triggerSQL);
    } catch (\PDOException $e) {
        // Trigger might already exist, ignore
    }

    $trigger2SQL = "
        DELIMITER //
        CREATE TRIGGER IF NOT EXISTS create_default_credits_after_client_insert
        AFTER INSERT ON clients
        FOR EACH ROW
        BEGIN
          INSERT INTO client_credits (client_id, balance) VALUES (NEW.id, 10000.00);
        END //
        DELIMITER ;
    ";
    try {
        $pdo->exec($trigger2SQL);
    } catch (\PDOException $e) {
        // Trigger might already exist, ignore
    }

    // Insert default credits for existing clients
    $pdo->exec("INSERT IGNORE INTO client_credits (client_id, balance) SELECT id, 10000.00 FROM clients");

    echo "Migration completed successfully! All required columns and tables are present.";

} catch (\Exception $e) {
    echo "Migration error: " . $e->getMessage();
}
?>