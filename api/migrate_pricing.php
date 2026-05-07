<?php
// We connect to MySQL without a database first to create it if it doesn't exist
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS printpro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE printpro");

    // Re-create tables if they don't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS tbl_products (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, slug VARCHAR(50) NOT NULL UNIQUE)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS tbl_base_prices (id INT AUTO_INCREMENT PRIMARY KEY, product_id INT NOT NULL, min_qty INT NOT NULL, base_price DECIMAL(10,2) NOT NULL)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS tbl_materials (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, multiplier DECIMAL(5,2) NOT NULL DEFAULT 1.00)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS tbl_sizes (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, multiplier DECIMAL(5,2) NOT NULL DEFAULT 1.00)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS tbl_finishes (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, setup_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00, per_unit_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00)");

    // Clear and Seed Products
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0; TRUNCATE tbl_products; SET FOREIGN_KEY_CHECKS = 1;");
    $products = [['Flyers', 'flyers'], ['Brochures', 'brochures'], ['Booklets', 'booklets'], ['Cards', 'cards'], ['Posters', 'posters'], ['Mailers', 'mailers']];
    $stmt = $pdo->prepare("INSERT INTO tbl_products (name, slug) VALUES (?, ?)");
    foreach ($products as $p)
        $stmt->execute($p);

    // Seed Base Prices (Tiered)
    $pdo->exec("TRUNCATE tbl_base_prices");
    $tiers = [50, 250, 1000, 5000, 10000];
    $priceMap = [
        'flyers' => [1200, 2500, 4500, 12500, 20000],
        'brochures' => [2500, 4000, 8500, 22000, 35000],
        'booklets' => [4500, 12000, 35000, 110000, 180000],
        'cards' => [800, 1500, 2800, 8000, 14000],
        'posters' => [8500, 35000, 110000, 450000, 800000],
        'mailers' => [1500, 3000, 6500, 18000, 30000]
    ];
    foreach ($priceMap as $slug => $prices) {
        $pid = $pdo->query("SELECT id FROM tbl_products WHERE slug='$slug'")->fetchColumn();
        for ($i = 0; $i < count($tiers); $i++) {
            $pdo->prepare("INSERT INTO tbl_base_prices (product_id, min_qty, base_price) VALUES (?, ?, ?)")->execute([$pid, $tiers[$i], $prices[$i]]);
        }
    }

    // Seed Materials (MATCHING UI EXACTLY)
    $pdo->exec("TRUNCATE tbl_materials");
    $mats = [
        ['80lb Text', 0.90],
        ['100lb Text', 1.00],
        ['100lb Text (Standard)', 1.00],
        ['100lb Gloss Text', 1.10],
        ['70lb Text', 0.85],
        ['100lb Cover', 1.35],
        ['80lb Cover', 1.25],
        ['Self-Cover', 1.00],
        ['14pt Cardstock', 1.50],
        ['16pt Cardstock', 1.70],
        ['18pt Premium', 2.00],
        ['32pt Extra Thick', 3.50],
        ['10mil Premium Photo Paper', 4.00],
        ['15oz Vinyl', 5.00]
    ];
    foreach ($mats as $m)
        $pdo->prepare("INSERT INTO tbl_materials (name, multiplier) VALUES (?, ?)")->execute($m);

    // Seed Sizes (MATCHING UI EXACTLY)
    $pdo->exec("TRUNCATE tbl_sizes");
    $sizes = [
        ['4" × 6"', 1.00],
        ['5" × 7"', 1.20],
        ['5.5" × 8.5"', 1.40],
        ['5.5" × 8.5" (Half-Letter)', 1.40],
        ['8.5" × 11" (Letter)', 2.00],
        ['8.5" × 11"', 2.00],
        ['A4 (8.27" × 11.69")', 2.10],
        ['8.5" × 11" (Standard Tri-fold)', 2.20],
        ['8.5" × 14" (Legal)', 2.50],
        ['11" × 17"', 3.50],
        ['11" × 17" (Tabloid / Bi-fold)', 3.50],
        ['6" × 9"', 1.80],
        ['18" × 24"', 6.00],
        ['24" × 36" (Architectural/Standard)', 10.00],
        ['27" × 39" (Movie Poster)', 14.00],
        ['2" × 3.5" (Standard US)', 1.00],
        ['2.12" × 3.37" (Euro/Credit Card)', 1.10]
    ];
    foreach ($sizes as $s)
        $pdo->prepare("INSERT INTO tbl_sizes (name, multiplier) VALUES (?, ?)")->execute($s);

    // Seed Finishes (MATCHING UI EXACTLY)
    $pdo->exec("TRUNCATE tbl_finishes");
    $finishes = [
        ['Uncoated', 0, 0],
        ['None', 0, 0],
        ['Matte AQ', 500, 0.05],
        ['Gloss AQ', 500, 0.05],
        ['UV Coating', 1200, 0.10],
        ['Soft Touch (Aqueous)', 1500, 0.15],
        ['Spot UV (Cover only)', 2500, 0.20],
        ['Gloss Lamination', 1800, 0.30],
        ['Matte Lamination', 1800, 0.30],
        ['Matte', 400, 0.02],
        ['Gloss UV', 1000, 0.08],
        ['Soft Touch', 1800, 0.12],
        ['Foil Stamping', 5000, 0.50]
    ];
    foreach ($finishes as $f)
        $pdo->prepare("INSERT INTO tbl_finishes (name, setup_fee, per_unit_fee) VALUES (?, ?, ?)")->execute($f);

    echo "<h2 style='color:green'>Success! Pricing Data Synchronized.</h2>";
    echo "<p>All products, sizes, and materials now match your UI exactly.</p>";
    echo "<a href='../Client Dashboard.html'>Return to Dashboard</a>";

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}