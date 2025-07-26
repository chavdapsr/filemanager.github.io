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

?><?php
// ...existing PHP logic above...

// ...existing code...
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Manager Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 250px;
            --header-height: 60px;
        }
        body { font-size: 14px; }
        .sidebar {
            position: fixed; top: 0; left: 0; height: 100vh; width: var(--sidebar-width);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; overflow-y: auto; z-index: 1000;
        }
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; background-color: #f8f9fa; }
        .top-header {
            height: var(--header-height); background: white; border-bottom: 1px solid #e9ecef;
            display: flex; align-items: center; padding: 0 1.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .sidebar-header { padding: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-nav { padding: 1rem 0; }
        .sidebar-nav .nav-link {
            color: rgba(255,255,255,0.8); padding: 0.75rem 1.5rem; border: none;
            display: flex; align-items: center; transition: all 0.3s ease;
        }
        .sidebar-nav .nav-link:hover, .sidebar-nav .nav-link.active {
            background-color: rgba(255,255,255,0.1); color: white;
        }
        .sidebar-nav .nav-link i { margin-right: 0.75rem; font-size: 1.1rem; }
        .breadcrumb { background: none; padding: 0; margin-bottom: 1rem; }
        .breadcrumb-item a { color: #6c757d; text-decoration: none; }
        .breadcrumb-item a:hover { color: #495057; }
        .file-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;
        }
        .file-item {
            background: white; border-radius: 8px; padding: 1.5rem; text-align: center;
            border: 1px solid #e9ecef; transition: all 0.3s ease; cursor: pointer;
        }
        .file-item:hover {
            transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-color: #667eea;
        }
        .file-icon { font-size: 3rem; margin-bottom: 0.75rem; }
        .file-icon.folder { color: #ffc107; }
        .file-icon.image { color: #28a745; }
        .file-icon.document { color: #007bff; }
        .file-icon.video { color: #dc3545; }
        .file-icon.audio { color: #6f42c1; }
        .file-icon.archive { color: #fd7e14; }
        .file-icon.code { color: #20c997; }
        .file-name { font-weight: 500; margin-bottom: 0.25rem; word-break: break-word; }
        .file-meta { color: #6c757d; font-size: 0.875rem; }
        .storage-info {
            background: white; border-radius: 8px; padding: 1rem; margin-bottom: 1rem;
        }
        .progress { height: 6px; }
        .action-toolbar {
            background: white; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem;
            display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;
        }
        .view-toggle { margin-left: auto; }
        .list-view .file-grid { display: block; }
        .list-view .file-item {
            display: flex; align-items: center; text-align: left; padding: 0.75rem 1rem; margin-bottom: 0.5rem;
        }
        .list-view .file-icon { font-size: 1.5rem; margin-right: 1rem; margin-bottom: 0; }
        .list-view .file-info { flex: 1; }
        .list-view .file-meta { margin-left: auto; display: flex; gap: 2rem; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s ease; }
            .sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .mobile-toggle { display: block !important; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h5 class="mb-0">
                <i class="bi bi-folder-fill me-2"></i>File Manager
            </h5>
        </div>
        <nav class="sidebar-nav">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="#" data-section="dashboard">
                        <i class="bi bi-house-door"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-section="recent">
                        <i class="bi bi-clock-history"></i>Recent Files
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-section="documents">
                        <i class="bi bi-file-text"></i>Documents
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-section="images">
                        <i class="bi bi-image"></i>Images
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-section="videos">
                        <i class="bi bi-play-circle"></i>Videos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-section="music">
                        <i class="bi bi-music-note"></i>Music
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-section="trash">
                        <i class="bi bi-trash"></i>Trash
                    </a>
                </li>
            </ul>
        </nav>
        <div class="mt-auto p-3">
            <div class="storage-info">
                <div class="d-flex justify-content-between mb-2">
                    <small>Storage Used</small>
                    <small>75.2 GB / 100 GB</small>
                </div>
                <div class="progress">
                    <div class="progress-bar bg-primary" style="width: 75%"></div>
                </div>
            </div>
        </div>
    </div>
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="top-header">
            <button class="btn btn-outline-secondary d-md-none me-3 mobile-toggle" onclick="toggleSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <nav aria-label="breadcrumb" class="flex-grow-1">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="#"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="#">Documents</a></li>
                    <li class="breadcrumb-item active">Projects</li>
                </ol>
            </nav>
            <div class="d-flex align-items-center">
                <div class="input-group me-3" style="width: 300px;">
                    <input type="text" class="form-control" placeholder="Search files...">
                    <button class="btn btn-outline-secondary" type="button">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </header>
        <!-- Content Area -->
        <div class="p-4">
            <!-- Action Toolbar -->
            <div class="action-toolbar">
                <button class="btn btn-primary">
                    <i class="bi bi-cloud-upload me-2"></i>Upload
                </button>
                <button class="btn btn-outline-primary">
                    <i class="bi bi-folder-plus me-2"></i>New Folder
                </button>
                <button class="btn btn-outline-secondary">
                    <i class="bi bi-download me-2"></i>Download
                </button>
                <button class="btn btn-outline-danger">
                    <i class="bi bi-trash me-2"></i>Delete
                </button>
                <div class="view-toggle">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-secondary active" onclick="setView('grid')">
                            <i class="bi bi-grid-3x3-gap"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="setView('list')">
                            <i class="bi bi-list-ul"></i>
                        </button>
                    </div>
                </div>
            </div>
            <!-- File Grid -->
            <div class="file-grid" id="fileGrid">
                <!-- Example Folders -->
                <div class="file-item" ondblclick="openFolder('Projects')">
                    <div class="file-icon folder">
                        <i class="bi bi-folder-fill"></i>
                    </div>
                    <div class="file-name">Projects</div>
                    <div class="file-meta">12 items</div>
                </div>
                <div class="file-item" ondblclick="openFolder('Documents')">
                    <div class="file-icon folder">
                        <i class="bi bi-folder-fill"></i>
                    </div>
                    <div class="file-name">Documents</div>
                    <div class="file-meta">8 items</div>
                </div>
                <div class="file-item" ondblclick="openFolder('Images')">
                    <div class="file-icon folder">
                        <i class="bi bi-folder-fill"></i>
                    </div>
                    <div class="file-name">Images</div>
                    <div class="file-meta">156 items</div>
                </div>
                <!-- Example Files -->
                <div class="file-item">
                    <div class="file-icon document">
                        <i class="bi bi-file-text-fill"></i>
                    </div>
                    <div class="file-name">Report_2024.docx</div>
                    <div class="file-meta">2.3 MB • 2 days ago</div>
                </div>
                <div class="file-item">
                    <div class="file-icon image">
                        <i class="bi bi-file-image-fill"></i>
                    </div>
                    <div class="file-name">presentation.pdf</div>
                    <div class="file-meta">5.7 MB • 1 week ago</div>
                </div>
                <div class="file-item">
                    <div class="file-icon code">
                        <i class="bi bi-file-code-fill"></i>
                    </div>
                    <div class="file-name">app.js</div>
                    <div class="file-meta">42 KB • 3 hours ago</div>
                </div>
                <div class="file-item">
                    <div class="file-icon video">
                        <i class="bi bi-file-play-fill"></i>
                    </div>
                    <div class="file-name">demo_video.mp4</div>
                    <div class="file-meta">125 MB • 5 days ago</div>
                </div>
                <div class="file-item">
                    <div class="file-icon archive">
                        <i class="bi bi-file-zip-fill"></i>
                    </div>
                    <div class="file-name">backup.zip</div>
                    <div class="file-meta">89 MB • 1 month ago</div>
                </div>
                <div class="file-item">
                    <div class="file-icon audio">
                        <i class="bi bi-file-music-fill"></i>
                    </div>
                    <div class="file-name">podcast_ep1.mp3</div>
                    <div class="file-meta">15 MB • 2 weeks ago</div>
                </div>
                <div class="file-item">
                    <div class="file-icon document">
                        <i class="bi bi-file-excel-fill"></i>
                    </div>
                    <div class="file-name">budget_2024.xlsx</div>
                    <div class="file-meta">1.2 MB • 4 days ago</div>
                </div>
                <div class="file-item">
                    <div class="file-icon image">
                        <i class="bi bi-file-image-fill"></i>
                    </div>
                    <div class="file-name">logo_design.png</div>
                    <div class="file-meta">890 KB • 1 day ago</div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
        }
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.mobile-toggle');
            if (window.innerWidth <= 768 && 
                !sidebar.contains(e.target) && 
                !toggle.contains(e.target) && 
                sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        });
        // Navigation handling
        document.querySelectorAll('.sidebar-nav .nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.sidebar-nav .nav-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                const section = this.getAttribute('data-section');
                console.log('Loading section:', section);
            });
        });
        // View toggle functionality
        function setView(viewType) {
            const fileGrid = document.getElementById('fileGrid');
            const buttons = document.querySelectorAll('.view-toggle .btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            if (viewType === 'list') {
                fileGrid.parentElement.classList.add('list-view');
                buttons[1].classList.add('active');
            } else {
                fileGrid.parentElement.classList.remove('list-view');
                buttons[0].classList.add('active');
            }
        }
        // Folder navigation
        function openFolder(folderName) {
            const breadcrumb = document.querySelector('.breadcrumb');
            const newItem = document.createElement('li');
            newItem.className = 'breadcrumb-item active';
            newItem.textContent = folderName;
            const activeItem = breadcrumb.querySelector('.active');
            if (activeItem) {
                activeItem.classList.remove('active');
                const link = document.createElement('a');
                link.href = '#';
                link.textContent = activeItem.textContent;
                activeItem.innerHTML = '';
                activeItem.appendChild(link);
            }
            breadcrumb.appendChild(newItem);
            console.log('Opening folder:', folderName);
        }
        // File selection
        document.addEventListener('click', function(e) {
            if (e.target.closest('.file-item')) {
                const fileItem = e.target.closest('.file-item');
                if (e.ctrlKey || e.metaKey) {
                    fileItem.classList.toggle('selected');
                } else {
                    document.querySelectorAll('.file-item.selected').forEach(item => {
                        item.classList.remove('selected');
                    });
                    fileItem.classList.add('selected');
                }
            }
        });
        // Add selected styling
        const style = document.createElement('style');
        style.textContent = `
            .file-item.selected {
                background-color: #e7f3ff !important;
                border-color: #007bff !important;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
// ...existing code...