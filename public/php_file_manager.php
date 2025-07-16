<?php
// =============================================================================
// CONFIG FILES
// =============================================================================

// config/database.php
<?php
class DatabaseConfig {
    const HOST = 'localhost';
    const USERNAME = 'root';
    const PASSWORD = '';
    const DATABASE = 'file_manager';
    const CHARSET = 'utf8mb4';
    
    public static function getDSN() {
        return 'mysql:host=' . self::HOST . ';dbname=' . self::DATABASE . ';charset=' . self::CHARSET;
    }
}

// config/config.php
<?php
define('APP_NAME', 'File Manager');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/filemanager/public/');
define('UPLOAD_PATH', __DIR__ . '/../src/uploads/users/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'zip', 'rar']);
define('SESSION_TIMEOUT', 3600); // 1 hour
define('TIMEZONE', 'UTC');

date_default_timezone_set(TIMEZONE);

// config/.env
APP_ENV=development
DB_HOST=localhost
DB_NAME=file_manager
DB_USER=root
DB_PASS=
ENCRYPTION_KEY=your-secret-key-here
SESSION_SECRET=your-session-secret-here

// =============================================================================
// CLASSES
// =============================================================================

// src/classes/Database.php
<?php
require_once __DIR__ . '/../../config/database.php';

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $this->connection = new PDO(
                DatabaseConfig::getDSN(),
                DatabaseConfig::USERNAME,
                DatabaseConfig::PASSWORD,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            error_log("Database query error: " . $e->getMessage());
            return false;
        }
    }
    
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollback() {
        return $this->connection->rollback();
    }
}

// src/classes/User.php
<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Security.php';

class User {
    private $db;
    private $security;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->security = new Security();
    }
    
    public function register($username, $email, $password) {
        // Validate input
        if (!$this->validateUsername($username)) {
            return ['success' => false, 'message' => 'Invalid username'];
        }
        
        if (!$this->validateEmail($email)) {
            return ['success' => false, 'message' => 'Invalid email'];
        }
        
        if (!$this->validatePassword($password)) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters'];
        }
        
        // Check if user already exists
        if ($this->userExists($username, $email)) {
            return ['success' => false, 'message' => 'User already exists'];
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $sql = "INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())";
        $result = $this->db->query($sql, [$username, $email, $hashedPassword]);
        
        if ($result) {
            $userId = $this->db->getConnection()->lastInsertId();
            $this->createUserDirectory($userId);
            return ['success' => true, 'message' => 'User registered successfully', 'user_id' => $userId];
        }
        
        return ['success' => false, 'message' => 'Registration failed'];
    }
    
    public function login($username, $password) {
        $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
        $stmt = $this->db->query($sql, [$username, $username]);
        
        if ($stmt && $user = $stmt->fetch()) {
            if (password_verify($password, $user['password'])) {
                // Update last login
                $this->updateLastLogin($user['id']);
                
                // Start session
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['login_time'] = time();
                
                return ['success' => true, 'message' => 'Login successful', 'user' => $user];
            }
        }
        
        return ['success' => false, 'message' => 'Invalid credentials'];
    }
    
    public function logout() {
        session_start();
        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully'];
    }
    
    public function getCurrentUser() {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $this->db->query($sql, [$_SESSION['user_id']]);
        
        return $stmt ? $stmt->fetch() : null;
    }
    
    private function validateUsername($username) {
        return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username);
    }
    
    private function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    private function validatePassword($password) {
        return strlen($password) >= 6;
    }
    
    private function userExists($username, $email) {
        $sql = "SELECT id FROM users WHERE username = ? OR email = ?";
        $stmt = $this->db->query($sql, [$username, $email]);
        return $stmt && $stmt->fetch();
    }
    
    private function updateLastLogin($userId) {
        $sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
        $this->db->query($sql, [$userId]);
    }
    
    private function createUserDirectory($userId) {
        $userDir = UPLOAD_PATH . $userId;
        if (!is_dir($userDir)) {
            mkdir($userDir, 0755, true);
        }
    }
}

// src/classes/FileManager.php
<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Security.php';

class FileManager {
    private $db;
    private $security;
    private $userId;
    
    public function __construct($userId) {
        $this->db = Database::getInstance();
        $this->security = new Security();
        $this->userId = $userId;
    }
    
    public function uploadFile($file, $description = '') {
        // Validate file
        $validation = $this->validateFile($file);
        if (!$validation['valid']) {
            return ['success' => false, 'message' => $validation['message']];
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $userDir = UPLOAD_PATH . $this->userId . '/';
        $filePath = $userDir . $filename;
        
        // Create user directory if it doesn't exist
        if (!is_dir($userDir)) {
            mkdir($userDir, 0755, true);
        }
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Save file info to database
            $sql = "INSERT INTO files (user_id, original_name, filename, file_path, file_size, mime_type, description, uploaded_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            $result = $this->db->query($sql, [
                $this->userId,
                $file['name'],
                $filename,
                $filePath,
                $file['size'],
                $file['type'],
                $description
            ]);
            
            if ($result) {
                return ['success' => true, 'message' => 'File uploaded successfully'];
            } else {
                unlink($filePath); // Remove file if database insert fails
                return ['success' => false, 'message' => 'Database error occurred'];
            }
        }
        
        return ['success' => false, 'message' => 'File upload failed'];
    }
    
    public function getUserFiles() {
        $sql = "SELECT * FROM files WHERE user_id = ? ORDER BY uploaded_at DESC";
        $stmt = $this->db->query($sql, [$this->userId]);
        
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    public function getFile($fileId) {
        $sql = "SELECT * FROM files WHERE id = ? AND user_id = ?";
        $stmt = $this->db->query($sql, [$fileId, $this->userId]);
        
        return $stmt ? $stmt->fetch() : null;
    }
    
    public function deleteFile($fileId) {
        $file = $this->getFile($fileId);
        if (!$file) {
            return ['success' => false, 'message' => 'File not found'];
        }
        
        // Delete from database
        $sql = "DELETE FROM files WHERE id = ? AND user_id = ?";
        $result = $this->db->query($sql, [$fileId, $this->userId]);
        
        if ($result) {
            // Delete physical file
            if (file_exists($file['file_path'])) {
                unlink($file['file_path']);
            }
            return ['success' => true, 'message' => 'File deleted successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to delete file'];
    }
    
    public function downloadFile($fileId) {
        $file = $this->getFile($fileId);
        if (!$file || !file_exists($file['file_path'])) {
            return false;
        }
        
        // Update download count
        $sql = "UPDATE files SET download_count = download_count + 1 WHERE id = ?";
        $this->db->query($sql, [$fileId]);
        
        return $file;
    }
    
    private function validateFile($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'message' => 'Upload error occurred'];
        }
        
        // Check file size
        if ($file['size'] > MAX_FILE_SIZE) {
            return ['valid' => false, 'message' => 'File too large'];
        }
        
        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ALLOWED_EXTENSIONS)) {
            return ['valid' => false, 'message' => 'File type not allowed'];
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimes = [
            'image/jpeg', 'image/png', 'image/gif',
            'application/pdf', 'text/plain',
            'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip', 'application/x-rar-compressed'
        ];
        
        if (!in_array($mimeType, $allowedMimes)) {
            return ['valid' => false, 'message' => 'Invalid file type'];
        }
        
        return ['valid' => true];
    }
}

// src/classes/Security.php
<?php
class Security {
    
    public function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    public function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public function checkAuthentication() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }
        
        // Check session timeout
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > SESSION_TIMEOUT) {
            session_destroy();
            header('Location: login.php?timeout=1');
            exit;
        }
    }
    
    public function preventDirectAccess() {
        if (!defined('SECURE_ACCESS')) {
            die('Direct access not allowed');
        }
    }
    
    public function logActivity($action, $details = '') {
        $logFile = __DIR__ . '/../../logs/activity.log';
        $timestamp = date('Y-m-d H:i:s');
        $userId = $_SESSION['user_id'] ?? 'anonymous';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $logEntry = "[$timestamp] User: $userId, IP: $ip, Action: $action, Details: $details" . PHP_EOL;
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}

// =============================================================================
// INCLUDES
// =============================================================================

// src/includes/functions.php
<?php
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

function getFileIcon($mimeType) {
    $icons = [
        'image/jpeg' => 'image.png',
        'image/png' => 'image.png',
        'image/gif' => 'image.png',
        'application/pdf' => 'pdf.png',
        'text/plain' => 'text.png',
        'application/msword' => 'word.png',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'word.png',
        'application/zip' => 'archive.png',
        'application/x-rar-compressed' => 'archive.png'
    ];
    
    return $icons[$mimeType] ?? 'file.png';
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    
    return floor($time/31536000) . ' years ago';
}

function generateBreadcrumb($current = '') {
    $breadcrumb = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';
    $breadcrumb .= '<li class="breadcrumb-item"><a href="index.php">Home</a></li>';
    
    if ($current) {
        $breadcrumb .= '<li class="breadcrumb-item active">' . htmlspecialchars($current) . '</li>';
    }
    
    $breadcrumb .= '</ol></nav>';
    return $breadcrumb;
}

// src/includes/session.php
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../classes/Security.php';

$security = new Security();

// Check if user is logged in for protected pages
if (basename($_SERVER['PHP_SELF']) !== 'login.php' && 
    basename($_SERVER['PHP_SELF']) !== 'register.php') {
    $security->checkAuthentication();
}

// =============================================================================
// PUBLIC PAGES
// =============================================================================

// public/index.php
<?php
define('SECURE_ACCESS', true);
require_once __DIR__ . '/../src/includes/session.php';
require_once __DIR__ . '/../src/includes/functions.php';
require_once __DIR__ . '/../src/classes/FileManager.php';
require_once __DIR__ . '/../src/classes/User.php';
require_once __DIR__ . '/../config/config.php';

$user = new User();
$currentUser = $user->getCurrentUser();
$fileManager = new FileManager($currentUser['id']);
$files = $fileManager->getUserFiles();

$pageTitle = 'Dashboard';
include __DIR__ . '/../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <?php include __DIR__ . '/../templates/navigation.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>My Files</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                    <i class="fas fa-upload"></i> Upload File
                </button>
            </div>
            
            <!-- Upload Modal -->
            <div class="modal fade" id="uploadModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Upload File</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="upload.php" method="post" enctype="multipart/form-data">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="file" class="form-label">Choose File</label>
                                    <input type="file" class="form-control" id="file" name="file" required>
                                    <div class="form-text">Max file size: <?php echo formatFileSize(MAX_FILE_SIZE); ?></div>
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description (optional)</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                </div>
                                <input type="hidden" name="csrf_token" value="<?php echo $security->generateCSRFToken(); ?>">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Upload</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <?php include __DIR__ . '/../templates/file-list.php'; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>

// public/login.php
<?php
define('SECURE_ACCESS', true);
require_once __DIR__ . '/../src/classes/User.php';
require_once __DIR__ . '/../src/classes/Security.php';
require_once __DIR__ . '/../config/config.php';

session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = new User();
    $security = new Security();
    
    $username = $security->sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    
    $result = $user->login($username, $password);
    
    if ($result['success']) {
        header('Location: index.php');
        exit;
    } else {
        $error = $result['message'];
    }
}

$pageTitle = 'Login';
include __DIR__ . '/../templates/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header">
                    <h3 class="text-center">Login</h3>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['timeout'])): ?>
                        <div class="alert alert-warning">Session expired. Please login again.</div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username or Email</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <p>Don't have an account? <a href="register.php">Register here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>

// public/register.php
<?php
define('SECURE_ACCESS', true);
require_once __DIR__ . '/../src/classes/User.php';
require_once __DIR__ . '/../src/classes/Security.php';
require_once __DIR__ . '/../config/config.php';

session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = new User();
    $security = new Security();
    
    $username = $security->sanitizeInput($_POST['username']);
    $email = $security->sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        $result = $user->register($username, $email, $password);
        
        if ($result['success']) {
            $success = 'Registration successful! You can now login.';
        } else {
            $error = $result['message'];
        }
    }
}

$pageTitle = 'Register';
include __DIR__ . '/../templates/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header">
                    <h3 class="text-center">Register</h3>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Register</button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <p>Already have an account? <a href="login.php">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>

// public/logout.php
<?php
define('SECURE_ACCESS', true);
require_once __DIR__ . '/../src/classes/User.php';

$user = new User();
$user->logout();

header('Location: login.php');
exit;

// public/upload.php
<?php
define('SECURE_ACCESS', true);
require_once __DIR__ . '/../src/includes/session.php';
require_once __DIR__ . '/../src/classes/FileManager.php';
require_once __DIR__ . '/../src/classes/Security.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $security = new Security();
    
    // Validate CSRF token
    if (!$security->validateCSRFToken($_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }
    
    $fileManager = new FileManager($_SESSION['user_id']);
    $description = $security->sanitizeInput($_POST['description'] ?? '');
    
    $result = $fileManager->uploadFile($_FILES['file'], $description);
    
    if ($result['success']) {
        $security->logActivity('File Upload', $_FILES['file']['name']);
        header('Location: index.php?success=' . urlencode($result['message']));
    } else {
        header('Location: index.php?error=' . urlencode($result['message']));
    }
} else {
    header('Location: index.php');
}
exit;

// public/download.php
<?php
define('SECURE_ACCESS', true);
require_once __DIR__ . '/../src/includes/session.php';
require_once __DIR__ . '/../src/classes/FileManager.php';
require_once __DIR__ . '/../src/classes/Security.php';

if (!isset($_GET['id'])) {
    die('File ID required');
}

$fileManager = new FileManager($_SESSION['user_id']);
$security = new Security();
$fileId = (int)$_GET['id'];

$file = $fileManager->downloadFile($fileId);

if (!$file) {
    die('File not found');
}

// Log download activity
$security->logActivity('File Download', $file['original_name']);

// Set headers for download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $file['original_name'] . '"');
header('Content-Length: ' . $file['file_size']);
header('Cache-Control: private');

// Output file
readfile($file['file_path']);
exit;

// public/delete.php
<?php
define('SECURE_ACCESS', true);
require_once __DIR__ . '/../src/includes/session.php';
require_once __DIR__ . '/../src/classes/FileManager.php';
require_once __DIR__ . '/../src/classes/Security.php';

if (!isset($_GET['id'])) {
    die('File ID required');
}

$fileManager = new FileManager($_SESSION['user_id']);
$security = new Security();
$fileId = (int)$_GET['id'];

$result = $fileManager->deleteFile($fileId);

if ($result['success']) {
    $security->logActivity('File Delete', "File ID: $fileId");
    header('Location: index.php?success=' . urlencode($result['message']));
} else {
    header('Location: index.php?error=' . urlencode($result['message']));
}
exit;

// =============================================================================
// TEMPLATES
// =============================================================================

// templates/header.php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php if (isset($_SESSION['user_id'])): ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-folder"></i> <?php echo APP_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <main class="py-4">
        <?php if (isset($_GET['success'])): ?>
            <div class="container">
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_GET['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="container">
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        <?php endif; ?>

// templates/footer.php
    </main>
    
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><?php echo APP_NAME; ?></h5>
                    <p>Secure file management system</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>&copy; <?php echo date('Y'); ?> File Manager. All rights reserved.</p>
                    <p>Version <?php echo APP_VERSION; ?></p>
                </div>
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>

// templates/navigation.php
<div class="list-group">
    <a href="index.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
        <i class="fas fa-home"></i> Dashboard
    </a>
    <a href="#" class="list-group-item list-group-item-action" data-bs-toggle="modal" data-bs-target="#uploadModal">
        <i class="fas fa-upload"></i> Upload Files
    </a>
    <div class="list-group-item">
        <h6 class="mb-1">Storage Usage</h6>
        <div class="progress">
            <div class="progress-bar" role="progressbar" style="width: <?php echo rand(20, 80); ?>%"></div>
        </div>
        <small class="text-muted"><?php echo formatFileSize(rand(1000000, 10000000)); ?> used</small>
    </div>
</div>

// templates/file-list.php
<div class="row">
    <?php if (empty($files)): ?>
        <div class="col-12">
            <div class="text-center py-5">
                <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No files uploaded yet</h4>
                <p class="text-muted">Upload your first file to get started</p>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                    <i class="fas fa-upload"></i> Upload File
                </button>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($files as $file): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-file fa-2x text-primary me-3"></i>
                            <div class="flex-grow-1">
                                <h6 class="card-title mb-1"><?php echo htmlspecialchars($file['original_name']); ?></h6>
                                <small class="text-muted"><?php echo formatFileSize($file['file_size']); ?></small>
                            </div>
                        </div>
                        
                        <?php if ($file['description']): ?>
                            <p class="card-text text-muted small"><?php echo htmlspecialchars($file['description']); ?></p>
                        <?php endif; ?>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="fas fa-clock"></i> <?php echo timeAgo($file['uploaded_at']); ?>
                            </small>
                            <div class="btn-group">
                                <a href="download.php?id=<?php echo $file['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-download"></i>
                                </a>
                                <a href="delete.php?id=<?php echo $file['id']; ?>" class="btn btn-sm btn-outline-danger" 
                                   onclick="return confirm('Are you sure you want to delete this file?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                        
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="fas fa-download"></i> Downloaded <?php echo $file['download_count']; ?> times
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

// =============================================================================
// ASSETS
// =============================================================================

// public/assets/css/style.css
:root {
    --primary-color: #007bff;
    --secondary-color: #6c757d;
    --success-color: #28a745;
    --danger-color: #dc3545;
    --warning-color: #ffc107;
    --info-color: #17a2b8;
    --light-color: #f8f9fa;
    --dark-color: #343a40;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background-color: #f8f9fa;
}

.navbar-brand {
    font-weight: bold;
}

.card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.list-group-item {
    border: none;
    border-radius: 0.375rem;
    margin-bottom: 0.5rem;
}

.list-group-item:hover {
    background-color: #f8f9fa;
}

.list-group-item.active {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

.btn {
    border-radius: 0.375rem;
    font-weight: 500;
}

.btn-group .btn {
    border-radius: 0.375rem;
    margin-left: 0.25rem;
}

.progress {
    height: 6px;
    border-radius: 3px;
}

.alert {
    border: none;
    border-radius: 0.5rem;
}

.modal-content {
    border: none;
    border-radius: 0.5rem;
}

.form-control {
    border-radius: 0.375rem;
}

.table {
    background-color: white;
}

.file-icon {
    width: 32px;
    height: 32px;
}

.upload-area {
    border: 2px dashed #dee2e6;
    border-radius: 0.5rem;
    padding: 2rem;
    text-align: center;
    transition: border-color 0.3s;
}

.upload-area:hover {
    border-color: var(--primary-color);
}

.upload-area.dragover {
    border-color: var(--primary-color);
    background-color: rgba(0, 123, 255, 0.1);
}

footer {
    margin-top: auto;
}

// public/assets/js/script.js
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
    
    // File upload drag and drop
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('file');
    
    if (uploadArea && fileInput) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight(e) {
            uploadArea.classList.add('dragover');
        }
        
        function unhighlight(e) {
            uploadArea.classList.remove('dragover');
        }
        
        uploadArea.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length > 0) {
                fileInput.files = files;
                updateFileLabel(files[0].name);
            }
        }
    }
    
    // Update file input label
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                updateFileLabel(this.files[0].name);
            }
        });
    }
    
    function updateFileLabel(fileName) {
        const label = document.querySelector('label[for="file"]');
        if (label) {
            label.textContent = fileName;
        }
    }
    
    // Confirm delete
    const deleteLinks = document.querySelectorAll('a[href*="delete.php"]');
    deleteLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this file?')) {
                e.preventDefault();
            }
        });
    });
    
    // File size validation
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const maxSize = 10 * 1024 * 1024; // 10MB
                if (file.size > maxSize) {
                    alert('File size must be less than 10MB');
                    this.value = '';
                }
            }
        });
    }
    
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const fileCards = document.querySelectorAll('.file-card');
            
            fileCards.forEach(card => {
                const fileName = card.querySelector('.card-title').textContent.toLowerCase();
                const fileDescription = card.querySelector('.card-text')?.textContent.toLowerCase() || '';
                
                if (fileName.includes(searchTerm) || fileDescription.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }
});

// =============================================================================
// CONFIGURATION FILES
// =============================================================================

// .htaccess
RewriteEngine On

# Redirect to public directory
RewriteCond %{REQUEST_URI} !^/public/
RewriteRule ^(.*)$ /public/$1 [L,QSA]

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"

# Prevent access to sensitive files
<Files ~ "^\.">
    Order allow,deny
    Deny from all
</Files>

<Files ~ "\.php$">
    Order allow,deny
    Allow from all
</Files>

# Prevent access to config and src directories
<DirectoryMatch "^.*(config|src).*$">
    Order allow,deny
    Deny from all
</DirectoryMatch>

# composer.json
{
    "name": "filemanager/php-file-manager",
    "description": "A secure PHP file manager with user authentication",
    "type": "project",
    "require": {
        "php": ">=7.4"
    },
    "autoload": {
        "psr-4": {
            "FileManager\\": "src/"
        }
    },
    "scripts": {
        "post-install-cmd": [
            "php -r \"copy('.env.example', '.env');\""
        ]
    }
}

// =============================================================================
// DATABASE SCHEMA (SQL)
// =============================================================================

-- Create database and tables
CREATE DATABASE IF NOT EXISTS file_manager;
USE file_manager;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_username (username),
    INDEX idx_email (email)
);

-- Files table
CREATE TABLE files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size BIGINT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    description TEXT,
    download_count INT DEFAULT 0,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_uploaded_at (uploaded_at)
);

-- User sessions table (optional)
CREATE TABLE user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(128) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_session_id (session_id),
    INDEX idx_user_id (user_id)
);

-- README.md
# PHP File Manager

A secure, modern PHP file manager with user authentication and file management capabilities.

## Features

- User registration and authentication
- Secure file upload and download
- File management (view, delete)
- User-specific file storage
- Session management
- CSRF protection
- File type validation
- Responsive design with Bootstrap

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server

## Installation

1. Clone or download the project
2. Create a MySQL database named `file_manager`
3. Import the database schema (see database schema in the code)
4. Configure database settings in `config/database.php`
5. Set up the web server to point to the `public` directory
6. Ensure the `src/uploads` directory has write permissions

## Configuration

Edit the following files:
- `config/config.php` - Application settings
- `config/database.php` - Database connection
- `config/.env` - Environment variables

## Security Features

- Password hashing
- SQL injection prevention
- XSS protection
- CSRF tokens
- File type validation
- User session management
- Activity logging

## Usage

1. Register a new account or login
2. Upload files through the web interface
3. View, download, or delete your files
4. All files are stored in user-specific directories

## License

This project is open source and available under the MIT License.