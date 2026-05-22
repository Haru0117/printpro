<?php
// ─────────────────────────────────────────────────────────────
//  PrintPro — Add Cancelled Status Migration
//  Run this once to add 'Cancelled' status to orders table
// ─────────────────────────────────────────────────────────────

require_once 'includes/db.php';

echo "<h2>PrintPro - Add Cancelled Status Migration</h2>";

try {
    // Add Cancelled status to ENUM
    $sql1 = "ALTER TABLE orders 
             MODIFY COLUMN status ENUM(
                'Proof Pending',
                'Proof Pending Review', 
                'Prepress',
                'Printing',
                'Finishing',
                'Shipping',
                'Delivered',
                'Reprint',
                'Rejected',
                'Cancelled'
             ) NOT NULL DEFAULT 'Proof Pending'";

    $pdo->exec($sql1);
    echo "<p>✅ Added 'Cancelled' and 'Rejected' statuses to orders table</p>";

    echo "<p><strong>✅ Migration completed successfully!</strong></p>";
    echo "<p>Rejected orders will now appear in the 'Rejected' tab.</p>";
} catch (PDOException $e) {
    if (
        strpos($e->getMessage(), 'Duplicate column name') !== false ||
        strpos($e->getMessage(), 'already exists') !== false
    ) {
        echo "<p>ℹ️ Status ENUM already includes 'Cancelled'</p>";
        echo "<p><strong>✅ Migration completed successfully!</strong></p>";
    } else {
        echo "<p><strong>❌ Migration failed:</strong> " . $e->getMessage() . "</p>";
    }
}
