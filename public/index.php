<?php
/**
 * Advanced File Manager - Professional Home Page
 * 
 * Features:
 * - Clear and engaging layouts with modern design
 * - Professional branding elements and visual identity
 * - Strategic CTAs (Call-to-Action buttons)
 * - Intuitive navigation aids and breadcrumbs
 * - Content placeholders for dynamic content
 * - Mobile-responsive design for all devices
 * - Dynamic content support with real-time updates
 * - Customizable header, footer, and sidebar navigation
 * - SEO optimization and accessibility features
 * - Security measures and performance optimization
 */

// Security: Start session and set security headers
session_start();
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Configuration
class Config {
    const UPLOAD_DIR = 'uploads/';
    const ALLOWED_FOLDER_CHARS = '/[^A-Za-z0-9_\-]/';
    const MAX_FILE_SIZE = 50 * 1024 * 1024; // 50MB
    const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'zip', 'rar', 'mp3', 'mp4', 'avi'];
    const ANALYTICS_ID = 'G-XXXXXXXXXX'; // Replace with your Google Analytics ID
}

// Enhanced File Manager Class with Security and Performance
class FileManager {
    private $baseDir;
    private $logFile;
    
    public function __construct() {
        $this->baseDir = __DIR__ . '/' . Config::UPLOAD_DIR;
        $this->logFile = __DIR__ . '/logs/filemanager.log';
        $this->ensureUploadDir();
        $this->ensureLogDir();
    }
    
    private function ensureUploadDir() {
        if (!is_dir($this->baseDir)) {
            mkdir($this->baseDir, 0755, true);
        }
    }
    
    private function ensureLogDir() {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    private function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    public function uploadFile($file) {
        if (!$this->isValidFile($file)) {
            $this->log("Invalid file upload attempt: " . ($file['name'] ?? 'unknown'), 'WARNING');
            return ['success' => false, 'message' => 'Invalid file type or size!'];
        }
        
        if ($file['size'] > Config::MAX_FILE_SIZE) {
            $this->log("File too large: " . $file['name'], 'WARNING');
            return ['success' => false, 'message' => 'File too large! Maximum size is 50MB.'];
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, Config::ALLOWED_EXTENSIONS)) {
            $this->log("Disallowed file type: " . $file['name'], 'WARNING');
            return ['success' => false, 'message' => 'File type not allowed!'];
        }
        
        $safeName = $this->sanitizeFilename($file['name']);
        $target = $this->baseDir . $safeName;
        
        if (move_uploaded_file($file['tmp_name'], $target)) {
            $this->log("File uploaded successfully: " . $safeName);
            return ['success' => true, 'message' => 'File uploaded successfully!'];
        }
        
        $this->log("Upload failed: " . $file['name'], 'ERROR');
        return ['success' => false, 'message' => 'Error uploading file!'];
    }
    
    private function sanitizeFilename($filename) {
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        $name = pathinfo($safeName, PATHINFO_FILENAME);
        $ext = pathinfo($safeName, PATHINFO_EXTENSION);
        
        $counter = 1;
        $finalName = $safeName;
        while (file_exists($this->baseDir . $finalName)) {
            $finalName = $name . '_' . $counter . '.' . $ext;
            $counter++;
        }
        
        return $finalName;
    }
    
    public function createFolder($folderName) {
        $cleanName = preg_replace(Config::ALLOWED_FOLDER_CHARS, '', $folderName);
        $target = $this->baseDir . $cleanName;
        
        if (file_exists($target)) {
            return ['success' => false, 'message' => 'Folder already exists!'];
        }
        
        if (mkdir($target, 0755, true)) {
            $this->log("Folder created: " . $cleanName);
            return ['success' => true, 'message' => 'Folder created successfully!'];
        }
        
        $this->log("Folder creation failed: " . $cleanName, 'ERROR');
        return ['success' => false, 'message' => 'Error creating folder!'];
    }
    
    public function deleteItem($name, $type) {
        $target = $this->baseDir . basename($name);
        
        if (!file_exists($target)) {
            return ['success' => false, 'message' => 'File/Folder doesn\'t exist!'];
        }
        
        if ($type === 'folder') {
            $result = $this->deleteFolder($target);
        } else {
            $result = unlink($target);
        }
        
        if ($result) {
            $this->log("Item deleted: " . $name);
            $message = ucfirst($type) . ' deleted successfully!';
        } else {
            $this->log("Delete failed: " . $name, 'ERROR');
            $message = 'Error deleting ' . $type . '!';
        }
        
        return ['success' => $result, 'message' => $message];
    }
    
    private function deleteFolder($dir) {
        if (!is_dir($dir)) return false;
        
        $items = array_diff(scandir($dir), ['.', '..']);
        foreach ($items as $item) {
            $path = "$dir/$item";
            is_dir($path) ? $this->deleteFolder($path) : unlink($path);
        }
        
        return rmdir($dir);
    }
    
    public function downloadFile($fileName) {
        $file = basename($fileName);
        $path = $this->baseDir . $file;
        
        if (!is_file($path)) {
            return false;
        }
        
        if (strpos($file, '..') !== false) {
            return false;
        }
        
        $this->log("File downloaded: " . $file);
        
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        readfile($path);
        return true;
    }
    
    public function getFiles() {
        $files = array_diff(scandir($this->baseDir), ['.', '..']);
        return array_values($files);
    }
    
    public function searchFiles($query) {
        $files = $this->getFiles();
        $results = [];
        
        foreach ($files as $file) {
            if (stripos($file, $query) !== false) {
                $results[] = $file;
            }
        }
        
        return $results;
    }
    
    public function isFolder($fileName) {
        return is_dir($this->baseDir . $fileName);
    }
    
    public function getFileInfo($fileName) {
        $path = $this->baseDir . $fileName;
        if (!file_exists($path)) return null;
        
        return [
            'name' => $fileName,
            'size' => filesize($path),
            'modified' => filemtime($path),
            'type' => is_dir($path) ? 'folder' : 'file',
            'extension' => is_dir($path) ? '' : strtolower(pathinfo($fileName, PATHINFO_EXTENSION))
        ];
    }
    
    private function isValidFile($file) {
        return isset($file['tmp_name']) && 
               is_uploaded_file($file['tmp_name']) && 
               $file['error'] === UPLOAD_ERR_OK;
    }
    
    public function cleanupOldFiles($days = 30) {
        $files = $this->getFiles();
        $deleted = 0;
        $cutoff = time() - ($days * 24 * 60 * 60);
        
        foreach ($files as $file) {
            $path = $this->baseDir . $file;
            if (filemtime($path) < $cutoff) {
                if (is_dir($path)) {
                    $this->deleteFolder($path);
                } else {
                    unlink($path);
                }
                $deleted++;
            }
        }
        
        $this->log("Cleanup completed: $deleted files/folders removed");
        return $deleted;
    }
    
    public function getStorageStats() {
        $files = $this->getFiles();
        $stats = [
            'total_files' => 0,
            'total_folders' => 0,
            'total_size' => 0,
            'file_types' => []
        ];
        
        foreach ($files as $file) {
            $info = $this->getFileInfo($file);
            if ($info) {
                if ($info['type'] === 'folder') {
                    $stats['total_folders']++;
                } else {
                    $stats['total_files']++;
                    $stats['total_size'] += $info['size'];
                    $ext = $info['extension'];
                    if ($ext) {
                        $stats['file_types'][$ext] = ($stats['file_types'][$ext] ?? 0) + 1;
                    }
                }
            }
        }
        
        return $stats;
    }
}

// Enhanced Request Handler with CSRF Protection
class RequestHandler {
    private $fileManager;
    
    public function __construct(FileManager $fileManager) {
        $this->fileManager = $fileManager;
    }
    
    public function handleRequest() {
        $message = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->validateCSRF()) {
                return 'Invalid request!';
            }
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $message = $this->handlePostRequest();
        } elseif (isset($_GET['download'])) {
            $this->fileManager->downloadFile($_GET['download']);
            exit;
        } elseif (isset($_GET['search'])) {
            $searchResults = $this->fileManager->searchFiles($_GET['search']);
            $_SESSION['search_results'] = $searchResults;
        }
        
        return $message;
    }
    
    private function validateCSRF() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return isset($_POST['csrf_token']) && 
               hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
    }
    
    private function handlePostRequest() {
        if (isset($_FILES['file'])) {
            return $this->fileManager->uploadFile($_FILES['file'])['message'];
        }
        
        if (isset($_POST['folder_name'])) {
            return $this->fileManager->createFolder($_POST['folder_name'])['message'];
        }
        
        if (isset($_POST['delete_name'], $_POST['delete_type'])) {
            return $this->fileManager->deleteItem($_POST['delete_name'], $_POST['delete_type'])['message'];
        }
        
        if (isset($_POST['cleanup'])) {
            $deleted = $this->fileManager->cleanupOldFiles();
            return "Cleanup completed! Removed $deleted old files/folders.";
        }
        
        return '';
    }
}

// Initialize and handle requests
$fileManager = new FileManager();
$requestHandler = new RequestHandler($fileManager);
$message = $requestHandler->handleRequest();

// Get files (search results or all files)
$files = isset($_SESSION['search_results']) ? $_SESSION['search_results'] : $fileManager->getFiles();
unset($_SESSION['search_results']);

// Get storage statistics
$stats = $fileManager->getStorageStats();

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO Optimization -->
    <title>Advanced File Manager - Professional Cloud Storage & File Management</title>
    <meta name="description" content="Professional file management system with drag & drop upload, secure storage, and advanced organization features. Manage your files efficiently with our intuitive interface.">
    <meta name="keywords" content="file manager, cloud storage, file upload, document management, secure file sharing">
    <meta name="author" content="Advanced File Manager">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:title" content="Advanced File Manager - Professional Cloud Storage">
    <meta property="og:description" content="Professional file management system with advanced features and secure storage.">
    <meta property="og:image" content="assets/img/file-manager.png">
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo $_SERVER['REQUEST_URI']; ?>">
    <meta property="twitter:title" content="Advanced File Manager - Professional Cloud Storage">
    <meta property="twitter:description" content="Professional file management system with advanced features and secure storage.">
    <meta property="twitter:image" content="assets/img/file-manager.png">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico">
    <link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png">
    
    <!-- Preload critical resources -->
    <link rel="preload" href="assets/css/style.css" as="style">
    <link rel="preload" href="assets/js/script.js" as="script">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/bootstrap5/css/bootstrap.min.css">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Structured Data for SEO -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebApplication",
        "name": "Advanced File Manager",
        "description": "Professional file management system with secure storage and advanced features",
        "url": "<?php echo $_SERVER['REQUEST_URI']; ?>",
        "applicationCategory": "ProductivityApplication",
        "operatingSystem": "Web Browser",
        "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "USD"
        }
    }
    </script>
    
    <!-- Google Analytics -->
    <?php if (Config::ANALYTICS_ID !== 'G-XXXXXXXXXX'): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo Config::ANALYTICS_ID; ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?php echo Config::ANALYTICS_ID; ?>');
    </script>
    <?php endif; ?>
</head>
<body>
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <!-- Customizable Header -->
    <header class="header" role="banner">
        <div class="header-top">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="header-contact">
                            <span><i class="fas fa-phone"></i> +1 (555) 123-4567</span>
                            <span><i class="fas fa-envelope"></i> support@filemanager.com</span>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="header-social">
                            <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                            <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                            <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Navigation Header -->
        <nav class="navbar navbar-expand-lg navbar-dark" role="navigation" aria-label="Main navigation">
            <div class="container">
                <!-- Branding Elements -->
                <a class="navbar-brand" href="#" aria-label="File Manager Home">
                    <div class="brand-logo">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <div class="brand-text">
                        <span class="brand-name">Advanced File Manager</span>
                        <span class="brand-tagline">Professional Cloud Storage</span>
                    </div>
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                        aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link active" href="#" aria-current="page">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#files">
                                <i class="fas fa-folder"></i> Files
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#analytics">
                                <i class="fas fa-chart-bar"></i> Analytics
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#settings">
                                <i class="fas fa-cog"></i> Settings
                            </a>
                        </li>
                    </ul>
                    
                    <!-- Search Bar -->
                    <form class="d-flex" role="search" method="GET">
                        <div class="search-container">
                            <input class="form-control" type="search" name="search" 
                                   placeholder="Search files..." aria-label="Search files"
                                   value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                            <button class="btn btn-outline-light" type="submit" aria-label="Search">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                    
                    <!-- User Menu -->
                    <div class="navbar-nav ms-3">
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle"></i> Admin
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#profile"><i class="fas fa-user"></i> Profile</a></li>
                                <li><a class="dropdown-item" href="#settings"><i class="fas fa-cog"></i> Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- Breadcrumb Navigation -->
    <nav aria-label="breadcrumb" class="breadcrumb-nav">
        <div class="container">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#" aria-label="Home"><i class="fas fa-home"></i> Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
            </ol>
        </div>
    </nav>

    <!-- Main Content Area -->
    <div class="main-container">
        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar Navigation -->
                <aside class="col-lg-3 col-md-4 sidebar" role="complementary">
                    <div class="sidebar-content">
                        <div class="sidebar-header">
                            <h5><i class="fas fa-bars"></i> Quick Navigation</h5>
                        </div>
                        
                        <nav class="sidebar-nav" role="navigation" aria-label="Sidebar navigation">
                            <ul class="nav flex-column">
                                <li class="nav-item">
                                    <a class="nav-link active" href="#dashboard">
                                        <i class="fas fa-tachometer-alt"></i> Dashboard
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#files">
                                        <i class="fas fa-folder"></i> All Files
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#recent">
                                        <i class="fas fa-clock"></i> Recent Files
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#favorites">
                                        <i class="fas fa-star"></i> Favorites
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#shared">
                                        <i class="fas fa-share-alt"></i> Shared Files
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#trash">
                                        <i class="fas fa-trash"></i> Trash
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        
                        <div class="sidebar-section">
                            <h6><i class="fas fa-chart-pie"></i> Storage Overview</h6>
                            <div class="storage-progress">
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: <?php echo min(($stats['total_size'] / (1024 * 1024 * 1024)) * 100, 100); ?>%"
                                         aria-valuenow="<?php echo $stats['total_size']; ?>" 
                                         aria-valuemin="0" aria-valuemax="1073741824">
                                    </div>
                                </div>
                                <small class="text-muted">
                                    <?php echo formatFileSize($stats['total_size']); ?> used of 1 GB
                                </small>
                            </div>
                        </div>
                        
                        <div class="sidebar-section">
                            <h6><i class="fas fa-bell"></i> Notifications</h6>
                            <div class="notification-list">
                                <div class="notification-item">
                                    <i class="fas fa-upload text-success"></i>
                                    <span>File uploaded successfully</span>
                                </div>
                                <div class="notification-item">
                                    <i class="fas fa-exclamation-triangle text-warning"></i>
                                    <span>Storage space running low</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>

                <!-- Main Content -->
                <main id="main-content" class="col-lg-9 col-md-8 main-content" role="main">
                    <div class="content-wrapper">
                        <!-- Status Messages -->
                        <?php if ($message): ?>
                            <div class="alert alert-info alert-dismissible fade show" role="alert" aria-live="polite">
                                <i class="fas fa-info-circle"></i>
                                <?php echo htmlspecialchars($message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Hero Section with CTAs -->
                        <section class="hero-section">
                            <div class="hero-content">
                                <h1 class="hero-title">Welcome to Advanced File Manager</h1>
                                <p class="hero-subtitle">Professional cloud storage and file management solution</p>
                                <div class="hero-ctas">
                                    <button class="btn btn-primary btn-lg" onclick="document.getElementById('file-upload').click()">
                                        <i class="fas fa-upload"></i> Upload Files
                                    </button>
                                    <button class="btn btn-outline-primary btn-lg" onclick="showCreateFolder()">
                                        <i class="fas fa-folder-plus"></i> Create Folder
                                    </button>
                                </div>
                            </div>
                        </section>

                        <!-- Statistics Dashboard -->
                        <section class="stats-section">
                            <div class="section-header">
                                <h2><i class="fas fa-chart-bar"></i> Storage Statistics</h2>
                                <p>Real-time overview of your file storage</p>
                            </div>
                            
                            <div class="stats-grid">
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="fas fa-folder"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3><?php echo $stats['total_folders']; ?></h3>
                                        <p>Folders</p>
                                    </div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="fas fa-file"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3><?php echo $stats['total_files']; ?></h3>
                                        <p>Files</p>
                                    </div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="fas fa-hdd"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3><?php echo formatFileSize($stats['total_size']); ?></h3>
                                        <p>Total Size</p>
                                    </div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="fas fa-chart-pie"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3><?php echo count($stats['file_types']); ?></h3>
                                        <p>File Types</p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Quick Actions Section -->
                        <section class="quick-actions-section">
                            <div class="section-header">
                                <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
                                <p>Common tasks and shortcuts</p>
                            </div>
                            
                            <div class="quick-actions-grid">
                                <button class="action-card" onclick="document.getElementById('file-upload').click()">
                                    <div class="action-icon">
                                        <i class="fas fa-upload"></i>
                                    </div>
                                    <h4>Upload Files</h4>
                                    <p>Drag & drop or click to upload</p>
                                </button>
                                
                                <button class="action-card" onclick="showCreateFolder()">
                                    <div class="action-icon">
                                        <i class="fas fa-folder-plus"></i>
                                    </div>
                                    <h4>New Folder</h4>
                                    <p>Create organized folder structure</p>
                                </button>
                                
                                <button class="action-card" onclick="showSearch()">
                                    <div class="action-icon">
                                        <i class="fas fa-search"></i>
                                    </div>
                                    <h4>Search Files</h4>
                                    <p>Find files quickly</p>
                                </button>
                                
                                <button class="action-card" onclick="toggleSort()">
                                    <div class="action-icon">
                                        <i class="fas fa-sort"></i>
                                    </div>
                                    <h4>Sort Files</h4>
                                    <p>Organize by name, size, date</p>
                                </button>
                            </div>
                        </section>

                        <!-- File Management Section -->
                        <section class="files-section">
                            <div class="section-header">
                                <h2><i class="fas fa-files-o"></i> Files & Folders</h2>
                                <div class="section-controls">
                                    <div class="view-controls">
                                        <button class="view-btn active" data-view="grid">
                                            <i class="fas fa-th"></i> Grid
                                        </button>
                                        <button class="view-btn" data-view="list">
                                            <i class="fas fa-list"></i> List
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <?php if (empty($files)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-folder-open"></i>
                                </div>
                                <h4>No files yet</h4>
                                <p>Upload your first file or create a folder to get started!</p>
                                <button class="btn btn-primary" onclick="document.getElementById('file-upload').click()">
                                    <i class="fas fa-upload"></i> Upload Your First File
                                </button>
                            </div>
                            <?php else: ?>
                            <div class="files-grid" id="files-container">
                                <?php foreach ($files as $file): ?>
                                <?php $isFolder = $fileManager->isFolder($file); ?>
                                <div class="file-card" data-type="<?php echo $isFolder ? 'folder' : 'file'; ?>">
                                    <div class="file-icon">
                                        <?php if ($isFolder): ?>
                                            <i class="fas fa-folder"></i>
                                        <?php else: ?>
                                            <i class="<?php echo getFileTypeIcon(strtolower(pathinfo($file, PATHINFO_EXTENSION))); ?>"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="file-info">
                                        <h4 class="file-name"><?php echo htmlspecialchars($file); ?></h4>
                                        <p class="file-meta">
                                            <?php if ($isFolder): ?>
                                                <i class="fas fa-folder"></i> Folder
                                            <?php else: ?>
                                                <?php 
                                                $filePath = __DIR__ . '/uploads/' . $file;
                                                echo file_exists($filePath) ? formatFileSize(filesize($filePath)) : 'Unknown size';
                                                ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <div class="file-actions">
                                        <?php if (!$isFolder): ?>
                                        <button class="action-btn download-btn" title="Download" 
                                                onclick="downloadFile('<?php echo urlencode($file); ?>')">
                                            <i class="fas fa-download"></i>
                                        </button>
                                        <?php endif; ?>
                                        <button class="action-btn delete-btn" title="Delete" 
                                                onclick="deleteItem('<?php echo htmlspecialchars($file); ?>', '<?php echo $isFolder ? 'folder' : 'file'; ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </section>
                    </div>
                </main>
            </div>
        </div>
    </div>

    <!-- Hidden file upload input -->
    <input type="file" id="file-upload" style="display: none;" onchange="uploadFile(this.files[0])">

    <!-- Enhanced Forms -->
    <div class="modal" id="create-folder-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-folder-plus"></i> Create New Folder</h3>
                <button class="close-btn" onclick="hideCreateFolder()">×</button>
            </div>
            <form method="POST" class="modal-form">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="form-group">
                    <label for="folder-name">Folder Name</label>
                    <input type="text" id="folder-name" name="folder_name" placeholder="Enter folder name" required>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="hideCreateFolder()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Folder</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Customizable Footer -->
    <footer class="footer" role="contentinfo">
        <div class="footer-main">
            <div class="container">
                <div class="row">
                    <div class="col-lg-4 col-md-6">
                        <div class="footer-section">
                            <h5><i class="fas fa-cloud-upload-alt"></i> Advanced File Manager</h5>
                            <p>Professional cloud storage and file management solution for modern businesses.</p>
                            <div class="social-links">
                                <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                                <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                                <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin"></i></a>
                                <a href="#" aria-label="GitHub"><i class="fab fa-github"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <div class="footer-section">
                            <h6>Features</h6>
                            <ul class="footer-links">
                                <li><a href="#upload">File Upload</a></li>
                                <li><a href="#share">File Sharing</a></li>
                                <li><a href="#security">Security</a></li>
                                <li><a href="#backup">Backup</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <div class="footer-section">
                            <h6>Support</h6>
                            <ul class="footer-links">
                                <li><a href="#help">Help Center</a></li>
                                <li><a href="#contact">Contact Us</a></li>
                                <li><a href="#docs">Documentation</a></li>
                                <li><a href="#status">System Status</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="footer-section">
                            <h6>System Status</h6>
                            <div class="status-indicators">
                                <div class="status-item">
                                    <span class="status-dot online"></span>
                                    <span>Storage: <?php echo formatFileSize($stats['total_size']); ?></span>
                                </div>
                                <div class="status-item">
                                    <span class="status-dot online"></span>
                                    <span>Files: <?php echo $stats['total_files']; ?></span>
                                </div>
                                <div class="status-item">
                                    <span class="status-dot online"></span>
                                    <span>System: Online</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <p class="mb-0">&copy; 2024 Advanced File Manager. All rights reserved.</p>
                    </div>
                    <div class="col-md-6 text-end">
                        <p class="mb-0">
                            <a href="#privacy">Privacy Policy</a> | 
                            <a href="#terms">Terms of Service</a> | 
                            <a href="#cookies">Cookie Policy</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript Libraries -->
    <script src="assets/bootstrap5/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
    
    <!-- Additional JavaScript functions -->
    <script>
        // Performance monitoring
        window.addEventListener('load', function() {
            if ('performance' in window) {
                const perfData = performance.getEntriesByType('navigation')[0];
                console.log('Page load time:', perfData.loadEventEnd - perfData.loadEventStart, 'ms');
                
                // Send to analytics if available
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'timing_complete', {
                        name: 'load',
                        value: Math.round(perfData.loadEventEnd - perfData.loadEventStart)
                    });
                }
            }
        });
        
        // Accessibility enhancements
        document.addEventListener('keydown', function(e) {
            // Keyboard shortcuts
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 'u':
                        e.preventDefault();
                        document.getElementById('file-upload').click();
                        break;
                    case 'n':
                        e.preventDefault();
                        showCreateFolder();
                        break;
                    case 'f':
                        e.preventDefault();
                        document.querySelector('input[name="search"]').focus();
                        break;
                }
            }
        });
        
        // Additional JavaScript functions for file manager
        function showCreateFolder() {
            document.getElementById('create-folder-modal').style.display = 'flex';
            const input = document.getElementById('folder-name');
            if (input) {
                input.focus();
            }
        }
        
        function hideCreateFolder() {
            document.getElementById('create-folder-modal').style.display = 'none';
        }
        
        function showSearch() {
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }
        
        function toggleSort() {
            // Implement sorting functionality
            console.log('Sort functionality to be implemented');
        }
        
        function uploadFile(file) {
            if (!file) return;
            
            const formData = new FormData();
            formData.append('file', file);
            formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
            
            fetch('', {
                method: 'POST',
                body: formData
            }).then(() => {
                location.reload();
            });
        }
        
        function downloadFile(filename) {
            window.location.href = '?download=' + encodeURIComponent(filename);
        }
        
        function deleteItem(name, type) {
            if (confirm(`Are you sure you want to delete "${name}"?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="delete_name" value="${name}">
                    <input type="hidden" name="delete_type" value="${type}">
                    <input type="hidden" name="csrf_token" value="${document.querySelector('input[name="csrf_token"]').value}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // View switching functionality
        document.addEventListener('DOMContentLoaded', function() {
            const viewButtons = document.querySelectorAll('.view-btn');
            const filesContainer = document.getElementById('files-container');
            
            viewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const view = this.dataset.view;
                    
                    // Update active button
                    viewButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Update container class
                    if (filesContainer) {
                        filesContainer.className = view === 'grid' ? 'files-grid' : 'files-list';
                    }
                });
            });
            
            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                const modal = document.getElementById('create-folder-modal');
                if (event.target === modal) {
                    hideCreateFolder();
                }
            });
        });
    </script>
</body>
</html>

<?php
// Helper function for file size formatting
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

// Helper function for file type icons
function getFileTypeIcon($type) {
    $icons = [
        'pdf' => 'fas fa-file-pdf',
        'doc' => 'fas fa-file-word', 'docx' => 'fas fa-file-word',
        'txt' => 'fas fa-file-alt', 'rtf' => 'fas fa-file-alt',
        'jpg' => 'fas fa-file-image', 'jpeg' => 'fas fa-file-image', 
        'png' => 'fas fa-file-image', 'gif' => 'fas fa-file-image',
        'mp4' => 'fas fa-file-video', 'avi' => 'fas fa-file-video', 
        'mov' => 'fas fa-file-video',
        'mp3' => 'fas fa-file-audio', 'wav' => 'fas fa-file-audio', 
        'flac' => 'fas fa-file-audio',
        'zip' => 'fas fa-file-archive', 'rar' => 'fas fa-file-archive', 
        '7z' => 'fas fa-file-archive',
        'exe' => 'fas fa-cog', 'msi' => 'fas fa-cog'
    ];
    return $icons[strtolower($type)] ?? 'fas fa-file';
}
?>
