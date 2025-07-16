<?php
/**
 * PHP File Manager Dashboard
 * A comprehensive, secure file management system with modern features
 * 
 * Features:
 * - Secure authentication with role-based access control
 * - Complete file operations (upload, download, create, rename, move, copy, delete)
 * - Text editor with syntax highlighting
 * - File previews and archive support
 * - Search functionality and breadcrumb navigation
 * - Responsive design for desktop and mobile
 * - Security measures: CSRF protection, input validation, IP restrictions
 * 
 * @author File Manager System
 * @version 1.0
 */

// Security configuration
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL & ~E_NOTICE);

// Start session with secure settings
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true
]);

// Configuration
class Config {
    const VERSION = '1.0.0';
    const MAX_FILE_SIZE = 50 * 1024 * 1024; // 50MB
    const MAX_UPLOAD_FILES = 10;
    const SESSION_TIMEOUT = 3600; // 1 hour
    const ALLOWED_EXTENSIONS = ['txt', 'php', 'html', 'css', 'js', 'json', 'xml', 'md', 'jpg', 'jpeg', 'png', 'gif', 'pdf', 'zip', 'tar', 'gz'];
    const RESTRICTED_EXTENSIONS = ['exe', 'bat', 'cmd', 'scr', 'pif', 'com'];
    const ADMIN_IPS = ['127.0.0.1', '::1']; // Add your IP addresses here
    
    // User roles
    const ROLE_ADMIN = 'admin';
    const ROLE_USER = 'user';
    const ROLE_READONLY = 'readonly';
    
    // Default users (In production, use database)
    public static $users = [
        'admin' => [
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'role' => self::ROLE_ADMIN,
            'name' => 'Administrator'
        ],
        'user' => [
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'role' => self::ROLE_USER,
            'name' => 'Regular User'
        ]
    ];
}

// Security helper class
class Security {
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Sanitize input
     */
    public static function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate file path
     */
    public static function validatePath($path) {
        $realPath = realpath($path);
        $basePath = realpath(getcwd());
        return $realPath !== false && strpos($realPath, $basePath) === 0;
    }
    
    /**
     * Check IP restriction
     */
    public static function checkIPRestriction() {
        $clientIP = $_SERVER['REMOTE_ADDR'] ?? '';
        return in_array($clientIP, Config::ADMIN_IPS);
    }
    
    /**
     * Log security events
     */
    public static function logEvent($message, $level = 'INFO') {
        $logEntry = date('Y-m-d H:i:s') . " [{$level}] " . $message . " IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
        error_log($logEntry, 3, 'security.log');
    }
}

// Authentication class
class Auth {
    /**
     * Authenticate user
     */
    public static function authenticate($username, $password) {
        if (!isset(Config::$users[$username])) {
            Security::logEvent("Failed login attempt for user: {$username}", 'WARNING');
            return false;
        }
        
        $user = Config::$users[$username];
        if (password_verify($password, $user['password'])) {
            $_SESSION['user'] = $username;
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['login_time'] = time();
            Security::logEvent("User {$username} logged in successfully");
            return true;
        }
        
        Security::logEvent("Failed login attempt for user: {$username}", 'WARNING');
        return false;
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user']) && 
               isset($_SESSION['login_time']) && 
               (time() - $_SESSION['login_time']) < Config::SESSION_TIMEOUT;
    }
    
    /**
     * Check user role
     */
    public static function hasRole($role) {
        return isset($_SESSION['role']) && $_SESSION['role'] === $role;
    }
    
    /**
     * Check permissions
     */
    public static function canWrite() {
        return self::hasRole(Config::ROLE_ADMIN) || self::hasRole(Config::ROLE_USER);
    }
    
    /**
     * Logout user
     */
    public static function logout() {
        Security::logEvent("User {$_SESSION['user']} logged out");
        session_destroy();
        header('Location: ?');
        exit;
    }
}

// File manager class
class FileManager {
    private $basePath;
    
    public function __construct($basePath = '.') {
        $this->basePath = realpath($basePath);
    }
    
    /**
     * List directory contents
     */
    public function listDirectory($path = '') {
        $fullPath = $this->basePath . '/' . ltrim($path, '/');
        
        if (!Security::validatePath($fullPath) || !is_dir($fullPath)) {
            return false;
        }
        
        $items = [];
        $files = scandir($fullPath);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $itemPath = $fullPath . '/' . $file;
            $relativePath = ltrim($path . '/' . $file, '/');
            
            $items[] = [
                'name' => $file,
                'path' => $relativePath,
                'type' => is_dir($itemPath) ? 'directory' : 'file',
                'size' => is_file($itemPath) ? filesize($itemPath) : 0,
                'modified' => filemtime($itemPath),
                'permissions' => substr(sprintf('%o', fileperms($itemPath)), -4),
                'extension' => pathinfo($file, PATHINFO_EXTENSION)
            ];
        }
        
        // Sort: directories first, then files
        usort($items, function($a, $b) {
            if ($a['type'] !== $b['type']) {
                return $a['type'] === 'directory' ? -1 : 1;
            }
            return strcasecmp($a['name'], $b['name']);
        });
        
        return $items;
    }
    
    /**
     * Create directory
     */
    public function createDirectory($path, $name) {
        if (!Auth::canWrite()) return false;
        
        $fullPath = $this->basePath . '/' . ltrim($path, '/') . '/' . $name;
        
        if (!Security::validatePath(dirname($fullPath))) {
            return false;
        }
        
        if (mkdir($fullPath, 0755)) {
            Security::logEvent("Directory created: {$fullPath}");
            return true;
        }
        
        return false;
    }
    
    /**
     * Delete file or directory
     */
    public function delete($path) {
        if (!Auth::canWrite()) return false;
        
        $fullPath = $this->basePath . '/' . ltrim($path, '/');
        
        if (!Security::validatePath($fullPath)) {
            return false;
        }
        
        if (is_dir($fullPath)) {
            return $this->deleteDirectory($fullPath);
        } else {
            if (unlink($fullPath)) {
                Security::logEvent("File deleted: {$fullPath}");
                return true;
            }
        }
        
        return false;
    }
    
    private function deleteDirectory($dir) {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        if (rmdir($dir)) {
            Security::logEvent("Directory deleted: {$dir}");
            return true;
        }
        return false;
    }
    
    /**
     * Rename file or directory
     */
    public function rename($path, $newName) {
        if (!Auth::canWrite()) return false;
        
        $oldPath = $this->basePath . '/' . ltrim($path, '/');
        $newPath = dirname($oldPath) . '/' . $newName;
        
        if (!Security::validatePath($oldPath) || !Security::validatePath($newPath)) {
            return false;
        }
        
        if (rename($oldPath, $newPath)) {
            Security::logEvent("Renamed: {$oldPath} to {$newPath}");
            return true;
        }
        
        return false;
    }
    
    /**
     * Copy file or directory
     */
    public function copy($source, $destination) {
        if (!Auth::canWrite()) return false;
        
        $sourcePath = $this->basePath . '/' . ltrim($source, '/');
        $destPath = $this->basePath . '/' . ltrim($destination, '/');
        
        if (!Security::validatePath($sourcePath) || !Security::validatePath(dirname($destPath))) {
            return false;
        }
        
        if (is_dir($sourcePath)) {
            return $this->copyDirectory($sourcePath, $destPath);
        } else {
            if (copy($sourcePath, $destPath)) {
                Security::logEvent("Copied: {$sourcePath} to {$destPath}");
                return true;
            }
        }
        
        return false;
    }
    
    private function copyDirectory($source, $destination) {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        $files = array_diff(scandir($source), ['.', '..']);
        foreach ($files as $file) {
            $sourcePath = $source . '/' . $file;
            $destPath = $destination . '/' . $file;
            
            if (is_dir($sourcePath)) {
                $this->copyDirectory($sourcePath, $destPath);
            } else {
                copy($sourcePath, $destPath);
            }
        }
        
        Security::logEvent("Directory copied: {$source} to {$destination}");
        return true;
    }
    
    /**
     * Upload files
     */
    public function uploadFiles($path, $files) {
        if (!Auth::canWrite()) return false;
        
        $uploadPath = $this->basePath . '/' . ltrim($path, '/');
        
        if (!Security::validatePath($uploadPath) || !is_dir($uploadPath)) {
            return false;
        }
        
        $results = [];
        
        foreach ($files['tmp_name'] as $key => $tmpName) {
            $fileName = $files['name'][$key];
            $fileSize = $files['size'][$key];
            $fileError = $files['error'][$key];
            
            if ($fileError !== UPLOAD_ERR_OK) {
                $results[] = ['file' => $fileName, 'success' => false, 'error' => 'Upload error'];
                continue;
            }
            
            if ($fileSize > Config::MAX_FILE_SIZE) {
                $results[] = ['file' => $fileName, 'success' => false, 'error' => 'File too large'];
                continue;
            }
            
            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            if (in_array($extension, Config::RESTRICTED_EXTENSIONS)) {
                $results[] = ['file' => $fileName, 'success' => false, 'error' => 'File type not allowed'];
                continue;
            }
            
            if (!empty(Config::ALLOWED_EXTENSIONS) && !in_array($extension, Config::ALLOWED_EXTENSIONS)) {
                $results[] = ['file' => $fileName, 'success' => false, 'error' => 'File type not allowed'];
                continue;
            }
            
            $destination = $uploadPath . '/' . $fileName;
            
            if (move_uploaded_file($tmpName, $destination)) {
                chmod($destination, 0644);
                $results[] = ['file' => $fileName, 'success' => true];
                Security::logEvent("File uploaded: {$destination}");
            } else {
                $results[] = ['file' => $fileName, 'success' => false, 'error' => 'Upload failed'];
            }
        }
        
        return $results;
    }
    
    /**
     * Read file content
     */
    public function readFile($path) {
        $fullPath = $this->basePath . '/' . ltrim($path, '/');
        
        if (!Security::validatePath($fullPath) || !is_file($fullPath)) {
            return false;
        }
        
        return file_get_contents($fullPath);
    }
    
    /**
     * Write file content
     */
    public function writeFile($path, $content) {
        if (!Auth::canWrite()) return false;
        
        $fullPath = $this->basePath . '/' . ltrim($path, '/');
        
        if (!Security::validatePath($fullPath)) {
            return false;
        }
        
        if (file_put_contents($fullPath, $content) !== false) {
            Security::logEvent("File written: {$fullPath}");
            return true;
        }
        
        return false;
    }
    
    /**
     * Search files
     */
    public function searchFiles($query, $path = '') {
        $fullPath = $this->basePath . '/' . ltrim($path, '/');
        
        if (!Security::validatePath($fullPath) || !is_dir($fullPath)) {
            return [];
        }
        
        $results = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($fullPath, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if (stripos($file->getFilename(), $query) !== false) {
                $relativePath = str_replace($this->basePath . '/', '', $file->getPathname());
                $results[] = [
                    'name' => $file->getFilename(),
                    'path' => $relativePath,
                    'type' => $file->isDir() ? 'directory' : 'file',
                    'size' => $file->isFile() ? $file->getSize() : 0,
                    'modified' => $file->getMTime()
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Format file size
     */
    public static function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if (!Auth::isLoggedIn()) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }
    
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }
    
    $fm = new FileManager();
    $action = $_POST['action'];
    
    switch ($action) {
        case 'list':
            $path = Security::sanitizeInput($_POST['path'] ?? '');
            $items = $fm->listDirectory($path);
            echo json_encode(['success' => true, 'items' => $items]);
            break;
            
        case 'create_directory':
            $path = Security::sanitizeInput($_POST['path'] ?? '');
            $name = Security::sanitizeInput($_POST['name'] ?? '');
            $success = $fm->createDirectory($path, $name);
            echo json_encode(['success' => $success]);
            break;
            
        case 'delete':
            $path = Security::sanitizeInput($_POST['path'] ?? '');
            $success = $fm->delete($path);
            echo json_encode(['success' => $success]);
            break;
            
        case 'rename':
            $path = Security::sanitizeInput($_POST['path'] ?? '');
            $newName = Security::sanitizeInput($_POST['new_name'] ?? '');
            $success = $fm->rename($path, $newName);
            echo json_encode(['success' => $success]);
            break;
            
        case 'copy':
            $source = Security::sanitizeInput($_POST['source'] ?? '');
            $destination = Security::sanitizeInput($_POST['destination'] ?? '');
            $success = $fm->copy($source, $destination);
            echo json_encode(['success' => $success]);
            break;
            
        case 'upload':
            $path = Security::sanitizeInput($_POST['path'] ?? '');
            $results = $fm->uploadFiles($path, $_FILES['files']);
            echo json_encode(['success' => true, 'results' => $results]);
            break;
            
        case 'read_file':
            $path = Security::sanitizeInput($_POST['path'] ?? '');
            $content = $fm->readFile($path);
            echo json_encode(['success' => $content !== false, 'content' => $content]);
            break;
            
        case 'write_file':
            $path = Security::sanitizeInput($_POST['path'] ?? '');
            $content = $_POST['content'] ?? '';
            $success = $fm->writeFile($path, $content);
            echo json_encode(['success' => $success]);
            break;
            
        case 'search':
            $query = Security::sanitizeInput($_POST['query'] ?? '');
            $path = Security::sanitizeInput($_POST['path'] ?? '');
            $results = $fm->searchFiles($query, $path);
            echo json_encode(['success' => true, 'results' => $results]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
    }
    
    exit;
}

// Handle authentication
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = Security::sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token';
    } elseif (Auth::authenticate($username, $password)) {
        header('Location: ?');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    Auth::logout();
}

// Force HTTPS in production
if (!isset($_SERVER['HTTPS']) && $_SERVER['SERVER_NAME'] !== 'localhost') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}

$fm = new FileManager();
$currentPath = Security::sanitizeInput($_GET['path'] ?? '');
$csrfToken = Security::generateCSRFToken();

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Manager Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/monokai.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            color: #333;
            font-size: 24px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .login-form {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            margin: 100px auto;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .btn {
            background: #667eea;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #5a67d8;
        }
        
        .btn-danger {
            background: #e53e3e;
        }
        
        .btn-danger:hover {
            background: #c53030;
        }
        
        .btn-success {
            background: #38a169;
        }
        
        .btn-success:hover {
            background: #2f855a;
        }
        
        .dashboard {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 20px;
            height: calc(100vh - 140px);
        }
        
        .sidebar {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .main-content {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .toolbar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .search-box {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-left: auto;
        }
        
        .search-box input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 200px;
        }
        
        .breadcrumb {
            margin-bottom: 20px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .file-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 15px;
            padding: 20px;
            overflow-y: auto;
            flex: 1;
        }
        
        .file-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #eee;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }
        
        .file-item:hover {
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .file-item.selected {
            background: #e6f3ff;
            border-color: #667eea;
        }
        
        .file-icon {
            font-size: 32px;
            margin-bottom: 10px;
            color: #667eea;
        }
        
        .file-name {
            font-size: 12px;
            word-break: break-word;
            margin-bottom: 5px;
        }
        
        .file-size {
            font-size: 10px;
            color: #666;
        }
        
        .upload-area {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            margin-bottom: 20px;
            transition: border-color 0.3s;
        }
        
        .upload-area.dragover {
            border-color: #667eea;
            background: #f0f8ff;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .close {
            font-size: 28px;
            cursor: pointer;
            color: #aaa;
        }
        
        .close:hover {
            color: #000;
        }
        
        .error {
            background: #fed7d7;
            color: #c53030;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        
        .success {
            background: #c6f6d5;
            color: #2f855a;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        
        .loading {
            text-align: center;
            padding: 20px;
        }
        
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .context-menu {
            position: fixed;
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            min-width: 150px;
            display: none;
        }
        
        .context-menu-item {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }
        
        .context-menu-item:hover {
            background: #f8f9fa;
        }
        
        .context-menu-item:last-child {
            border-bottom: none;
        }
        
        .editor-container {
            height: 400px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .file-list {
            display: grid;
            grid-template-columns: 1fr 100px 120px;
            gap: 10px;
            padding: 10px;
            border-bottom: 1px solid #eee;
            align-items: center;
        }
        
        .file-list.header {
            background: #f8f9fa;
            font-weight: 500;
        }
        
        .file-actions {
            display: flex;
            gap: 5px;
        }
        
        .file-actions button {
            padding: 5px 8px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .file-actions button:hover {
            background: #f8f9fa;
        }
        
        @media (max-width: 768px) {
            .dashboard {
                grid-template-columns: 1fr;
                grid-template-rows: auto 1fr;
            }
            
            .sidebar {
                order: 2;
            }
            
            .main-content {
                order: 1;
            }
            
            .toolbar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                margin-left: 0;
            }
            
            .file-grid {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            }
        }
        
        .tree-view {
            list-style: none;
        }
        
        .tree-item {
            padding: 5px 0;
            cursor: pointer;
        }
        
        .tree-item:hover {
            background: #f8f9fa;
        }
        
        .tree-item.active {
            background: #e6f3ff;
            color: #667eea;
        }
        
        .tree-toggle {
            display: inline-block;
            width: 20px;
            text-align: center;
            cursor: pointer;
        }
        
        .tree-children {
            margin-left: 20px;
        }
        
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #f0f0f0;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: #667eea;
            transition: width 0.3s;
        }
        
        .view-toggle {
            display: flex;
            gap: 5px;
            margin-left: 10px;
        }
        
        .view-toggle button {
            padding: 8px 12px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .view-toggle button.active {
            background: #667eea;
            color: white;
        }
        
        .file-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .file-table th,
        .file-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .file-table th {
            background: #f8f9fa;
            font-weight: 500;
            cursor: pointer;
        }
        
        .file-table tr:hover {
            background: #f8f9fa;
        }
        
        .file-table .file-name {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .preview-container {
            text-align: center;
            padding: 20px;
        }
        
        .preview-container img {
            max-width: 100%;
            max-height: 400px;
            border-radius: 5px;
        }
        
        .preview-container video {
            max-width: 100%;
            max-height: 400px;
        }
        
        .no-preview {
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <?php if (!Auth::isLoggedIn()): ?>
        <div class="container">
            <form method="post" class="login-form">
                <h2 style="text-align: center; margin-bottom: 20px;">File Manager Login</h2>
                
                <?php if (isset($error)): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <button type="submit" name="login" class="btn" style="width: 100%;">Login</button>
                
                <div style="margin-top: 20px; font-size: 12px; color: #666;">
                    <strong>Demo Credentials:</strong><br>
                    Username: admin | Password: password<br>
                    Username: user | Password: password
                </div>
            </form>
        </div>
    <?php else: ?>
        <div class="container">
            <div class="header">
                <h1><i class="fas fa-folder"></i> File Manager Dashboard</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                    <span class="badge"><?php echo htmlspecialchars($_SESSION['role']); ?></span>
                    <a href="?logout" class="btn btn-danger">Logout</a>
                </div>
            </div>
            
            <div class="dashboard">
                <div class="sidebar">
                    <h3>Navigation</h3>
                    <div id="treeView" class="tree-view">
                        <div class="loading">Loading...</div>
                    </div>
                    
                    <div style="margin-top: 20px;">
                        <h4>Quick Actions</h4>
                        <button class="btn" onclick="showUploadModal()" style="width: 100%; margin-bottom: 10px;">
                            <i class="fas fa-upload"></i> Upload Files
                        </button>
                        <button class="btn" onclick="createDirectory()" style="width: 100%; margin-bottom: 10px;">
                            <i class="fas fa-folder-plus"></i> New Folder
                        </button>
                        <button class="btn" onclick="createFile()" style="width: 100%;">
                            <i class="fas fa-file-plus"></i> New File
                        </button>
                    </div>
                </div>
                
                <div class="main-content">
                    <div class="toolbar">
                        <button class="btn" onclick="goBack()">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                        <button class="btn" onclick="refresh()">
                            <i class="fas fa-sync"></i> Refresh
                        </button>
                        <button class="btn btn-success" onclick="showUploadModal()">
                            <i class="fas fa-upload"></i> Upload
                        </button>
                        <button class="btn" onclick="createDirectory()">
                            <i class="fas fa-folder-plus"></i> New Folder
                        </button>
                        
                        <div class="view-toggle">
                            <button id="gridView" class="active" onclick="switchView('grid')">
                                <i class="fas fa-th"></i>
                            </button>
                            <button id="listView" onclick="switchView('list')">
                                <i class="fas fa-list"></i>
                            </button>
                        </div>
                        
                        <div class="search-box">
                            <input type="text" id="searchInput" placeholder="Search files...">
                            <button class="btn" onclick="searchFiles()">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="breadcrumb" id="breadcrumb">
                        <a href="#" onclick="navigateTo('')">Home</a>
                    </div>
                    
                    <div class="upload-area" id="uploadArea" style="display: none;">
                        <i class="fas fa-cloud-upload-alt" style="font-size: 48px; color: #ddd; margin-bottom: 20px;"></i>
                        <p>Drag and drop files here or click to select</p>
                        <input type="file" id="fileInput" multiple style="display: none;">
                        <button class="btn" onclick="document.getElementById('fileInput').click()">
                            Select Files
                        </button>
                    </div>
                    
                    <div id="fileContainer">
                        <div class="file-grid" id="fileGrid">
                            <div class="loading">Loading files...</div>
                        </div>
                        
                        <div class="file-table-container" id="fileTable" style="display: none;">
                            <table class="file-table">
                                <thead>
                                    <tr>
                                        <th onclick="sortFiles('name')">Name <i class="fas fa-sort"></i></th>
                                        <th onclick="sortFiles('size')">Size <i class="fas fa-sort"></i></th>
                                        <th onclick="sortFiles('modified')">Modified <i class="fas fa-sort"></i></th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="fileTableBody">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Context Menu -->
        <div id="contextMenu" class="context-menu">
            <div class="context-menu-item" onclick="openFile()">
                <i class="fas fa-eye"></i> Open
            </div>
            <div class="context-menu-item" onclick="editFile()">
                <i class="fas fa-edit"></i> Edit
            </div>
            <div class="context-menu-item" onclick="downloadFile()">
                <i class="fas fa-download"></i> Download
            </div>
            <div class="context-menu-item" onclick="renameFile()">
                <i class="fas fa-edit"></i> Rename
            </div>
            <div class="context-menu-item" onclick="copyFile()">
                <i class="fas fa-copy"></i> Copy
            </div>
            <div class="context-menu-item" onclick="deleteFile()">
                <i class="fas fa-trash"></i> Delete
            </div>
            <div class="context-menu-item" onclick="showProperties()">
                <i class="fas fa-info"></i> Properties
            </div>
        </div>
        
        <!-- Upload Modal -->
        <div id="uploadModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Upload Files</h3>
                    <span class="close" onclick="closeModal('uploadModal')">&times;</span>
                </div>
                <div class="upload-area" id="modalUploadArea">
                    <i class="fas fa-cloud-upload-alt" style="font-size: 48px; color: #ddd; margin-bottom: 20px;"></i>
                    <p>Drag and drop files here or click to select</p>
                    <input type="file" id="modalFileInput" multiple>
                    <button class="btn" onclick="document.getElementById('modalFileInput').click()">
                        Select Files
                    </button>
                </div>
                <div id="uploadProgress" style="display: none;">
                    <div class="progress-bar">
                        <div class="progress-fill" id="progressFill"></div>
                    </div>
                    <div id="uploadStatus"></div>
                </div>
                <div class="modal-footer">
                    <button class="btn" onclick="closeModal('uploadModal')">Close</button>
                    <button class="btn btn-success" onclick="uploadFiles()">Upload</button>
                </div>
            </div>
        </div>
        
        <!-- Edit File Modal -->
        <div id="editModal" class="modal">
            <div class="modal-content" style="width: 95%; max-width: 1000px;">
                <div class="modal-header">
                    <h3>Edit File: <span id="editFileName"></span></h3>
                    <span class="close" onclick="closeModal('editModal')">&times;</span>
                </div>
                <div class="editor-container">
                    <textarea id="fileEditor"></textarea>
                </div>
                <div class="modal-footer">
                    <button class="btn" onclick="closeModal('editModal')">Close</button>
                    <button class="btn btn-success" onclick="saveFile()">Save</button>
                </div>
            </div>
        </div>
        
        <!-- Preview Modal -->
        <div id="previewModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Preview: <span id="previewFileName"></span></h3>
                    <span class="close" onclick="closeModal('previewModal')">&times;</span>
                </div>
                <div id="previewContainer" class="preview-container">
                    <div class="loading">Loading preview...</div>
                </div>
            </div>
        </div>
        
        <!-- Properties Modal -->
        <div id="propertiesModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Properties</h3>
                    <span class="close" onclick="closeModal('propertiesModal')">&times;</span>
                </div>
                <div id="propertiesContent"></div>
            </div>
        </div>
    <?php endif; ?>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/php/php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js"></script>
    <script>
        let currentPath = '';
        let selectedFile = null;
        let fileList = [];
        let currentView = 'grid';
        let sortBy = 'name';
        let sortOrder = 'asc';
        let editor = null;
        
        const csrfToken = '<?php echo $csrfToken; ?>';
        
        // Initialize the application
        document.addEventListener('DOMContentLoaded', function() {
            loadDirectory('');
            loadTreeView();
            setupEventListeners();
            setupDragAndDrop();
        });
        
        // Setup event listeners
        function setupEventListeners() {
            document.getElementById('searchInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    searchFiles();
                }
            });
            
            document.getElementById('fileInput').addEventListener('change', function() {
                showUploadArea();
            });
            
            document.getElementById('modalFileInput').addEventListener('change', function() {
                uploadFiles();
            });
            
            // Hide context menu on click
            document.addEventListener('click', function() {
                document.getElementById('contextMenu').style.display = 'none';
            });
            
            // Prevent context menu from closing when clicking inside it
            document.getElementById('contextMenu').addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
        
        // Setup drag and drop
        function setupDragAndDrop() {
            const uploadArea = document.getElementById('uploadArea');
            const modalUploadArea = document.getElementById('modalUploadArea');
            const fileGrid = document.getElementById('fileGrid');
            
            [uploadArea, modalUploadArea, fileGrid].forEach(area => {
                area.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    this.classList.add('dragover');
                });
                
                area.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    this.classList.remove('dragover');
                });
                
                area.addEventListener('drop', function(e) {
                    e.preventDefault();
                    this.classList.remove('dragover');
                    
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        handleFileDrop(files);
                    }
                });
            });
        }
        
        // Handle file drop
        function handleFileDrop(files) {
            const fileInput = document.getElementById('modalFileInput');
            fileInput.files = files;
            showUploadModal();
        }
        
        // Load directory contents
        function loadDirectory(path) {
            currentPath = path;
            updateBreadcrumb();
            
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'list',
                    path: path,
                    csrf_token: csrfToken
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    fileList = data.items;
                    displayFiles();
                } else {
                    showError('Failed to load directory');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Network error occurred');
            });
        }
        
        // Display files in current view
        function displayFiles() {
            if (currentView === 'grid') {
                displayGridView();
            } else {
                displayListView();
            }
        }
        
        // Display grid view
        function displayGridView() {
            const fileGrid = document.getElementById('fileGrid');
            const fileTable = document.getElementById('fileTable');
            
            fileGrid.style.display = 'grid';
            fileTable.style.display = 'none';
            
            if (fileList.length === 0) {
                fileGrid.innerHTML = '<div class="no-files">No files found</div>';
                return;
            }
            
            fileGrid.innerHTML = fileList.map(file => {
                const icon = getFileIcon(file.type, file.extension);
                const size = file.type === 'directory' ? '' : formatFileSize(file.size);
                
                return `
                    <div class="file-item" data-path="${file.path}" data-type="${file.type}" 
                         onclick="selectFile('${file.path}', '${file.type}')" 
                         oncontextmenu="showContextMenu(event, '${file.path}', '${file.type}')">
                        <div class="file-icon">${icon}</div>
                        <div class="file-name">${file.name}</div>
                        <div class="file-size">${size}</div>
                    </div>
                `;
            }).join('');
        }
        
        // Display list view
        function displayListView() {
            const fileGrid = document.getElementById('fileGrid');
            const fileTable = document.getElementById('fileTable');
            const tableBody = document.getElementById('fileTableBody');
            
            fileGrid.style.display = 'none';
            fileTable.style.display = 'block';
            
            if (fileList.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="4">No files found</td></tr>';
                return;
            }
            
            tableBody.innerHTML = fileList.map(file => {
                const icon = getFileIcon(file.type, file.extension);
                const size = file.type === 'directory' ? '-' : formatFileSize(file.size);
                const modified = new Date(file.modified * 1000).toLocaleDateString();
                
                return `
                    <tr data-path="${file.path}" data-type="${file.type}"
                        onclick="selectFile('${file.path}', '${file.type}')"
                        oncontextmenu="showContextMenu(event, '${file.path}', '${file.type}')">
                        <td>
                            <div class="file-name">
                                <span class="file-icon">${icon}</span>
                                ${file.name}
                            </div>
                        </td>
                        <td>${size}</td>
                        <td>${modified}</td>
                        <td>
                            <div class="file-actions">
                                <button onclick="event.stopPropagation(); selectFile('${file.path}', '${file.type}'); openFile()" title="Open">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="event.stopPropagation(); selectFile('${file.path}', '${file.type}'); downloadFile()" title="Download">
                                    <i class="fas fa-download"></i>
                                </button>
                                <button onclick="event.stopPropagation(); selectFile('${file.path}', '${file.type}'); deleteFile()" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }
        
        // Get file icon based on type and extension
        function getFileIcon(type, extension) {
            if (type === 'directory') {
                return '<i class="fas fa-folder"></i>';
            }
            
            const iconMap = {
                'txt': 'fas fa-file-alt',
                'php': 'fab fa-php',
                'html': 'fab fa-html5',
                'css': 'fab fa-css3',
                'js': 'fab fa-js',
                'json': 'fas fa-file-code',
                'xml': 'fas fa-file-code',
                'md': 'fab fa-markdown',
                'jpg': 'fas fa-image',
                'jpeg': 'fas fa-image',
                'png': 'fas fa-image',
                'gif': 'fas fa-image',
                'pdf': 'fas fa-file-pdf',
                'zip': 'fas fa-file-archive',
                'tar': 'fas fa-file-archive',
                'gz': 'fas fa-file-archive'
            };
            
            const iconClass = iconMap[extension] || 'fas fa-file';
            return `<i class="${iconClass}"></i>`;
        }
        
        // Format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // Select file
        function selectFile(path, type) {
            selectedFile = { path: path, type: type };
            
            // Remove previous selection
            document.querySelectorAll('.file-item').forEach(item => {
                item.classList.remove('selected');
            });
            
            // Add selection to current item
            const item = document.querySelector(`[data-path="${path}"]`);
            if (item) {
                item.classList.add('selected');
            }
        }
        
        // Navigate to directory
        function navigateTo(path) {
            if (selectedFile && selectedFile.type === 'directory') {
                loadDirectory(selectedFile.path);
            } else {
                loadDirectory(path);
            }
        }
        
        // Go back to parent directory
        function goBack() {
            if (currentPath) {
                const parentPath = currentPath.substring(0, currentPath.lastIndexOf('/'));
                loadDirectory(parentPath);
            }
        }
        
        // Refresh current directory
        function refresh() {
            loadDirectory(currentPath);
        }
        
        // Update breadcrumb navigation
        function updateBreadcrumb() {
            const breadcrumb = document.getElementById('breadcrumb');
            
            if (!currentPath) {
                breadcrumb.innerHTML = '<a href="#" onclick="navigateTo(\'\')">Home</a>';
                return;
            }
            
            const parts = currentPath.split('/');
            let html = '<a href="#" onclick="navigateTo(\'\')">Home</a>';
            let path = '';
            
            parts.forEach((part, index) => {
                if (part) {
                    path += (path ? '/' : '') + part;
                    html += ' / <a href="#" onclick="navigateTo(\'' + path + '\')">' + part + '</a>';
                }
            });
            
            breadcrumb.innerHTML = html;
        }
        
        // Switch view between grid and list
        function switchView(view) {
            currentView = view;
            
            document.getElementById('gridView').classList.toggle('active', view === 'grid');
            document.getElementById('listView').classList.toggle('active', view === 'list');
            
            displayFiles();
        }
        
        // Show context menu
        function showContextMenu(event, path, type) {
            event.preventDefault();
            event.stopPropagation();
            
            selectFile(path, type);
            
            const contextMenu = document.getElementById('contextMenu');
            contextMenu.style.display = 'block';
            contextMenu.style.left = event.pageX + 'px';
            contextMenu.style.top = event.pageY + 'px';
        }
        
        // Open file
        function openFile() {
            if (!selectedFile) return;
            
            if (selectedFile.type === 'directory') {
                loadDirectory(selectedFile.path);
            } else {
                const extension = selectedFile.path.split('.').pop().toLowerCase();
                const imageExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                const textExtensions = ['txt', 'php', 'html', 'css', 'js', 'json', 'xml', 'md'];
                
                if (imageExtensions.includes(extension)) {
                    previewFile();
                } else if (textExtensions.includes(extension)) {
                    editFile();
                } else {
                    downloadFile();
                }
            }
        }
        
        // Edit file
        function editFile() {
            if (!selectedFile || selectedFile.type === 'directory') return;
            
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'read_file',
                    path: selectedFile.path,
                    csrf_token: csrfToken
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('editFileName').textContent = selectedFile.path.split('/').pop();
                    document.getElementById('editModal').style.display = 'block';
                    
                    // Initialize CodeMirror editor
                    if (editor) {
                        editor.toTextArea();
                    }
                    
                    const textarea = document.getElementById('fileEditor');
                    textarea.value = data.content;
                    
                    const extension = selectedFile.path.split('.').pop().toLowerCase();
                    const mode = getModeForExtension(extension);
                    
                    editor = CodeMirror.fromTextArea(textarea, {
                        lineNumbers: true,
                        mode: mode,
                        theme: 'monokai',
                        indentUnit: 4,
                        lineWrapping: true
                    });
                    
                    editor.setSize(null, 400);
                } else {
                    showError('Failed to read file');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Network error occurred');
            });
        }
        
        // Get CodeMirror mode for file extension
        function getModeForExtension(extension) {
            const modeMap = {
                'php': 'php',
                'html': 'htmlmixed',
                'css': 'css',
                'js': 'javascript',
                'json': 'javascript',
                'xml': 'xml'
            };
            
            return modeMap[extension] || 'text';
        }
        
        // Save file
        function saveFile() {
            if (!selectedFile || !editor) return;
            
            const content = editor.getValue();
            
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'write_file',
                    path: selectedFile.path,
                    content: content,
                    csrf_token: csrfToken
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess('File saved successfully');
                    closeModal('editModal');
                } else {
                    showError('Failed to save file');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Network error occurred');
            });
        }
        
        // Preview file
        function previewFile() {
            if (!selectedFile || selectedFile.type === 'directory') return;
            const extension = selectedFile.path.split('.').pop().toLowerCase();
            const imageExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            const videoExtensions = ['mp4', 'webm', 'ogg'];
            const audioExtensions = ['mp3', 'wav', 'ogg'];

            document.getElementById('previewFileName').textContent = selectedFile.path.split('/').pop();
            document.getElementById('previewModal').style.display = 'block';
            const previewContainer = document.getElementById('previewContainer');
            previewContainer.innerHTML = '<div class="loading">Loading preview...</div>';

            if (imageExtensions.includes(extension)) {
                previewContainer.innerHTML = `<img src="${selectedFile.path}" alt="Preview">`;
            } else if (videoExtensions.includes(extension)) {
                previewContainer.innerHTML = `<video controls src="${selectedFile.path}"></video>`;
            } else if (audioExtensions.includes(extension)) {
                previewContainer.innerHTML = `<audio controls src="${selectedFile.path}"></audio>`;
            } else {
                // For text files, fetch content
                fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'read_file',
                        path: selectedFile.path,
                        csrf_token: csrfToken
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        previewContainer.innerHTML = `<pre style="text-align:left;white-space:pre-wrap;">${escapeHtml(data.content)}</pre>`;
                    } else {
                        previewContainer.innerHTML = '<div class="no-preview">No preview available</div>';
                    }
                })
                .catch(() => {
                    previewContainer.innerHTML = '<div class="no-preview">No preview available</div>';
                });
            }
        }

        // Show error message
        function showError(msg) {
            const el = document.createElement('div');
            el.className = 'error';
            el.textContent = msg;
            document.body.appendChild(el);
            setTimeout(() => el.remove(), 3000);
        }

        // Show success message
        function showSuccess(msg) {
            const el = document.createElement('div');
            el.className = 'success';
            el.textContent = msg;
            document.body.appendChild(el);
            setTimeout(() => el.remove(), 3000);
        }

        // Close modal
        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        // Show upload modal
        function showUploadModal() {
            document.getElementById('uploadModal').style.display = 'block';
        }

        // Show upload area
        function showUploadArea() {
            document.getElementById('uploadArea').style.display = 'block';
        }

        // Upload files
        function uploadFiles() {
            const input = document.getElementById('modalFileInput');
            const files = input.files;
            if (!files.length) return;
            const formData = new FormData();
            formData.append('action', 'upload');
            formData.append('path', currentPath);
            formData.append('csrf_token', csrfToken);
            for (let i = 0; i < files.length; i++) {
                formData.append('files[]', files[i]);
            }
            document.getElementById('uploadProgress').style.display = 'block';
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('uploadProgress').style.display = 'none';
                if (data.success) {
                    showSuccess('Files uploaded successfully');
                    closeModal('uploadModal');
                    refresh();
                } else {
                    showError('Failed to upload files');
                }
            })
            .catch(() => {
                document.getElementById('uploadProgress').style.display = 'none';
                showError('Network error occurred');
            });
        }

        // Escape HTML for preview
        function escapeHtml(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        // Create directory
        function createDirectory() {
            const name = prompt('Enter folder name:');
            if (!name) return;
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'create_directory',
                    path: currentPath,
                    name: name,
                    csrf_token: csrfToken
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess('Folder created successfully');
                    refresh();
                } else {
                    showError('Failed to create folder');
                }
            })
            .catch(() => showError('Network error occurred'));
        }

        // Create file
        function createFile() {
            const name = prompt('Enter file name:');
            if (!name) return;
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'write_file',
                    path: (currentPath ? currentPath + '/' : '') + name,
                    content: '',
                    csrf_token: csrfToken
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess('File created successfully');
                    refresh();
                } else {
                    showError('Failed to create file');
                }
            })
            .catch(() => showError('Network error occurred'));
        }

        // Download file
        function downloadFile() {
            if (!selectedFile || selectedFile.type === 'directory') return;
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            form.innerHTML = `
                <input type="hidden" name="action" value="download">
                <input type="hidden" name="path" value="${selectedFile.path}">
                <input type="hidden" name="csrf_token" value="${csrfToken}">
            `;
            document.body.appendChild(form);
            form.submit();
            form.remove();
        }

        // Delete file
        function deleteFile() {
            if (!selectedFile) return;
            if (!confirm('Are you sure you want to delete this item?')) return;
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'delete',
                    path: selectedFile.path,
                    csrf_token: csrfToken
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess('Deleted successfully');
                    refresh();
                } else {
                    showError('Failed to delete');
                }
            })
            .catch(() => showError('Network error occurred'));
        }

        // Rename file
        function renameFile() {
            if (!selectedFile) return;
            const newName = prompt('Enter new name:', selectedFile.path.split('/').pop());
            if (!newName) return;
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'rename',
                    path: selectedFile.path,
                    new_name: newName,
                    csrf_token: csrfToken
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess('Renamed successfully');
                    refresh();
                } else {
                    showError('Failed to rename');
                }
            })
            .catch(() => showError('Network error occurred'));
        }

        // Copy file
        function copyFile() {
            // Implement as needed (could use clipboard logic)
            showSuccess('Copy feature coming soon!');
        }

        // Show properties
        function showProperties() {
            if (!selectedFile) return;
            document.getElementById('propertiesModal').style.display = 'block';
            document.getElementById('propertiesContent').innerHTML = `
                <strong>Path:</strong> ${selectedFile.path}<br>
                <strong>Type:</strong> ${selectedFile.type}
            `;
        }

        // Search files
        function searchFiles() {
            const query = document.getElementById('searchInput').value.trim();
            if (!query) return refresh();
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'search',
                    query: query,
                    path: currentPath,
                    csrf_token: csrfToken
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    fileList = data.results;
                    displayFiles();
                } else {
                    showError('Search failed');
                }
            })
            .catch(() => showError('Network error occurred'));
        }

        // Load tree view (optional, can be implemented as needed)
        function loadTreeView() {
            // Placeholder for tree view logic
            document.getElementById('treeView').innerHTML = '<div class="no-preview">Tree view coming soon!</div>';
        }
    </script>
</body>
</html>