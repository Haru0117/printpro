<?php
require 'includes/db.php';
$stmt = $pdo->query("DESCRIBE users");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
$stmt = $pdo->query("DESCRIBE clients");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
