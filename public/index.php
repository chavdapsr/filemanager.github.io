<?php
define('SECURE_ACCESS', true);
require_once __DIR__ . '/../src/includes/session.php';
require_once __DIR__ . '/../src/includes/functions.php';
require_once __DIR__ . '/../src/classes/FileManager.php';
require_once __DIR__ . '/../src/classes/User.php';
require_once __DIR__ . '/../config/config.php';



$user = new User();
$currentUser = $user->getCurrentUser();
if (is_array($currentUser) && isset($currentUser['id'])) {
    $fileManager = new FileManager($currentUser['id']);
    $files = $fileManager->getUserFiles();
} else {
    // Handle not logged in or invalid user
    $fileManager = null;
    $files = [];
}

// Define formatFileSize if not already defined
if (!function_exists('formatFileSize')) {
    function formatFileSize($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            return $bytes . ' bytes';
        } elseif ($bytes == 1) {
            return '1 byte';
        } else {
            return '0 bytes';
        }
    }
}

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
                                    <div class="form-text">Max file size: <?php echo formatFileSize(isset($config['max_file_size']) ? $config['max_file_size'] : 10485760); ?></div>
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
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-cloud"></i>
                    <span>Filemanager</span>
                </div>
            </div>
            <nav class="nav-menu">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="#" class="nav-item active">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="#" class="nav-item">
                        <i class="fas fa-folder"></i>
                        <span>Files</span>
                    </a>
                    <a href="#" class="nav-item">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span>Upload</span>
                    </a>
                    <a href="#" class="nav-item">
                        <i class="fas fa-share-alt"></i>
                        <span>Shared</span>
                    </a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Storage</div>
                    <a href="#" class="nav-item">
                        <i class="fas fa-heart"></i>
                        <span>Favorites</span>
                    </a>
                    <a href="#" class="nav-item">
                        <i class="fas fa-clock"></i>
                        <span>Recent</span>
                    </a>
                    <a href="#" class="nav-item">
                        <i class="fas fa-trash"></i>
                        <span>Trash</span>
                    </a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Account</div>
                    <a href="#" class="nav-item">
                        <i class="fas fa-user"></i>
                        <span>Profile</span>
                    </a>
                    <a href="#" class="nav-item">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                    <a href="#" class="nav-item">
                        <i class="fas fa-chart-bar"></i>
                        <span>Analytics</span>
                    </a>
                </div>
            </nav>
        </aside>
        <!-- Main Content -->
        <main class="main-content" id="mainContent">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <button class="menu-toggle" id="menuToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="search-bar">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="search-input" placeholder="Search files and folders...">
                    </div>
                </div>
                <div class="header-right">
                    <button class="header-icon" id="notificationBtn">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">3</span>
                    </button>
                    <button class="header-icon" id="settingsBtn">
                        <i class="fas fa-cog"></i>
                    </button>
                    <div class="user-profile" id="userProfile">
                        <div class="user-avatar">psr</div>
                        <div class="user-info">
                            <div class="user-name">Chavad Psr</div>
                            <div class="user-role">Administrator</div>
                        </div>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
            </header>
            <!-- Content Area -->
            <div class="content-area">
                <!-- Dashboard Widgets -->
                <div class="dashboard-widgets">
                    <div class="widget">
                        <div class="icon"><i class="fas fa-folder"></i></div>
                        <div class="info">
                            <div class="value"><?php echo isset($stats['size']) ? number_format($stats['size']/1024, 2) . ' KB' : '--'; ?></div>
                            <div class="label">Root Folder Size</div>
                        </div>
                    </div>
                    <div class="widget">
                        <div class="icon"><i class="fas fa-file-alt"></i></div>
                        <div class="info">
                            <div class="value"><?php echo isset($stats['modified']) ? date('M d, Y', $stats['modified']) : '--'; ?></div>
                            <div class="label">Last Modified</div>
                        </div>
                    </div>
                    <div class="widget">
                        <div class="icon"><i class="fas fa-users"></i></div>
                        <div class="info">
                            <div class="value">Admin</div>
                            <div class="label">User Role</div>
                        </div>
                    </div>
                    <div class="widget">
                        <div class="icon"><i class="fas fa-database"></i></div>
                        <div class="info">
                            <div class="value">Connected</div>
                            <div class="label">Database</div>
                        </div>
                    </div>
                </div>
                <!-- File Manager -->
                <div class="file-manager">
                    <div class="file-manager-header">
                        <div class="file-manager-title">
                            <i class="fas fa-folder-open"></i>
                            <span>File Manager</span>
                        </div>
                        <div class="file-actions">
                            <button class="btn btn-primary" id="uploadBtn">
                                <i class="fas fa-upload"></i>
                                Upload Files
                            </button>
                            <button class="btn btn-secondary" id="newFolderBtn">
                                <i class="fas fa-folder-plus"></i>
                                New Folder
                            </button>
                            <button class="btn btn-secondary" id="viewToggle">
                                <i class="fas fa-th"></i>
                                Grid View
                            </button>
                        </div>
                    </div>
                    <div class="breadcrumb">
                        <div class="breadcrumb-list">
                            <a href="#" class="breadcrumb-item">
                                <i class="fas fa-home"></i>
                                Home
                            </a>
                            <i class="fas fa-chevron-right"></i>
                            <a href="#" class="breadcrumb-item">Documents</a>
                            <i class="fas fa-chevron-right"></i>
                            <span class="breadcrumb-item active">Projects</span>
                        </div>
                    </div>
                    <div class="file-content">
                        <!-- Upload Area -->
                        <div class="upload-area" id="uploadArea">
                            <div class="upload-icon">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <div class="upload-text">Drag and drop files here</div>
                            <div class="upload-subtext">or click to browse files</div>
                            <input type="file" id="fileInput" multiple hidden>
                        </div>
                        <!-- File Grid -->
                        <div class="file-grid" id="fileGrid">
                            <?php
                            $currentDir = $fileManager->root ?? __DIR__;
                            $items = scandir($currentDir);
                            foreach ($items as $item) {
                                if ($item === '.' || $item === '..') continue;
                                $itemPath = $currentDir . DIRECTORY_SEPARATOR . $item;
                                $isDir = is_dir($itemPath);
                                $icon = $isDir ? 'fa-folder' : 'fa-file';
                                $type = $isDir ? 'folder' : 'file';
                                $meta = $isDir ? (count(scandir($itemPath)) - 2) . ' items' : number_format(filesize($itemPath)/1024, 2) . ' KB';
                                $modified = date('M d, Y', filemtime($itemPath));
                            ?>
                            <div class="file-item" data-type="<?= $type ?>">
                                <div class="file-header">
                                    <div class="file-icon <?= $type ?>">
                                        <i class="fas <?= $icon ?>"></i>
                                    </div>
                                    <div class="file-info">
                                        <h3><?= htmlspecialchars($item) ?></h3>
                                        <div class="file-meta">
                                            <span><?= $meta ?></span>
                                            <span><?= $modified ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="file-item-actions">
                                    <button class="action-btn" title="Open">
                                        <i class="fas fa-folder-open"></i>
                                    </button>
                                    <button class="action-btn" title="Share">
                                        <i class="fas fa-share"></i>
                                    </button>
                                    <button class="action-btn" title="More">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js" integrity="sha384-7qAoOXltbVP82dhxHAUje59V5r2YsVfBafyUDxEdApLPmcdhBPg1DKg1ERo0BZlK" crossorigin="anonymous"></script>
    <script src="public/assets/script.js"></script>
</body>
</html>
