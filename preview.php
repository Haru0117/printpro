<?php
$file = __DIR__ . '/assets/img/logo.png';
if (file_exists($file)) {
    $info = getimagesize($file);
    header('Content-Type: ' . $info['mime']);
    header('Cache-Control: public, max-age=31536000');
    readfile($file);
    exit;
}
header('HTTP/1.0 404 Not Found');
