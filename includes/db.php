<?php
// ─────────────────────────────────────────────────────────────
//  PrintPro — Central Database Configuration
//  Environment: Auto-detect (Local XAMPP vs AwardSpace)
// ─────────────────────────────────────────────────────────────

$isLocal = ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_ADDR'] === '127.0.0.1');

if ($isLocal) {
    $host = 'localhost';
    $db   = 'printpro';
    $user = 'root';
    $pass = '';
} else {
    $host = 'fdb1034.awardspace.net';
    $db   = '4728062_printpro';
    $user = '4728062_printpro';
    $pass = 'iF8q#5:*9o/iqF!4';
}

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Fail gracefully with a JSON error for API calls
    if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    } else {
        die("Critical System Error: Unable to connect to database.");
    }
    exit;
}
?>