<?php
/**
 * Main entry point for File Manager
 */

// Include bootstrap file
require_once __DIR__ . '/bootstrap.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: /filemanager/myfilemanager/public/login.php');
    exit();
}

// Redirect to dashboard if already logged in
header('Location: /filemanager/myfilemanager/public/dashboard.php');
exit();
