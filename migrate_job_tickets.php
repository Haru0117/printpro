<?php
// ─────────────────────────────────────────────────────────────
//  PrintPro — Database Migration Script
//  This script creates the job_tickets_log table if it doesn't exist
// ─────────────────────────────────────────────────────────────

require_once 'includes/db.php';

try {
    // Create job_tickets_log table
    $sql = "
        CREATE TABLE IF NOT EXISTS `job_tickets_log` (
            `id` int NOT NULL AUTO_INCREMENT,
            `order_id` int NOT NULL,
            `generated_by_user_id` int NOT NULL,
            `generated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `fk_job_tickets_log_orders` (`order_id`),
            KEY `fk_job_tickets_log_users` (`generated_by_user_id`),
            CONSTRAINT `fk_job_tickets_log_orders` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_job_tickets_log_users` FOREIGN KEY (`generated_by_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($sql);
    
    // Add indices
    try {
        $pdo->exec("CREATE INDEX idx_job_tickets_log_order_id ON `job_tickets_log`(`order_id`)");
    } catch (Exception $e) {
        // Index might already exist
    }
    
    try {
        $pdo->exec("CREATE INDEX idx_job_tickets_log_generated_at ON `job_tickets_log`(`generated_at`)");
    } catch (Exception $e) {
        // Index might already exist
    }
    
    echo "✓ Database migration completed successfully.\n";
    echo "✓ Table 'job_tickets_log' is ready.\n";
    
} catch (Exception $e) {
    echo "✗ Migration error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
