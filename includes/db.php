<?php
// ─────────────────────────────────────────────────────────────
//  PrintPro — Central Database Configuration
//  Connected to: AwardSpace Production Database
// ─────────────────────────────────────────────────────────────

$host = 'fdb1034.awardspace.net';
$db   = '4728062_printpro';
$user = '4728062_printpro';
$pass = 'iF8q#5:*9o/iqF!4';

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}
?>