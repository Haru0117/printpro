<?php
$user = 'root';
$pass = '';
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

try {
    $pdo = new PDO("mysql:host=localhost", $user, $pass, $options);

    // Copy tbl_* from printpro to printpro_db
    $tables = ['tbl_base_prices', 'tbl_finishes', 'tbl_materials', 'tbl_products', 'tbl_sizes'];
    foreach ($tables as $t) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS printpro_db.$t LIKE printpro.$t");
        $pdo->exec("INSERT IGNORE INTO printpro_db.$t SELECT * FROM printpro.$t");
    }

    // Alter orders table in printpro_db to add missing columns from place_order.php
    $pdo->exec("ALTER TABLE printpro_db.orders 
                ADD COLUMN job_name VARCHAR(255) DEFAULT 'Untitled',
                ADD COLUMN paper_id INT DEFAULT 0,
                ADD COLUMN size_id INT DEFAULT 0,
                ADD COLUMN finish_id INT DEFAULT 0,
                ADD COLUMN total_price DECIMAL(10,2) DEFAULT 0.00;");

    echo "Migration successful.";
} catch (\Exception $e) {
    // Ignore duplicate column errors
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Columns already exist. Tables copied successfully.";
    } else {
        echo "Migration error: " . $e->getMessage();
    }
}
?>