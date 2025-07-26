<?php
$file = $_GET['file'];
$path = "uploads/$file";

if (is_file($path)) {
    unlink($path);
} elseif (is_dir($path)) {
    rmdir($path); // Only deletes empty folders
}
header('Location: index.php');
