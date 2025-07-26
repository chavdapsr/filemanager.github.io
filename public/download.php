<?php
$file = $_GET['file'];
$path = "uploads/$file";

if (file_exists($path) && is_file($path)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($path) . '"');
    readfile($path);
    exit;
} else {
    echo "File not found.";
}

