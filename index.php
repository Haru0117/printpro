<?php
// Fallback redirect — normally index.html is served directly
$file = __DIR__ . '/index.html';
if (file_exists($file)) {
    echo file_get_contents($file);
    exit;
}
header('Location: index.html');
