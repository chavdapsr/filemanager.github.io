<?php
session_start();

// Configuration
$config = [
    'root_path' => __DIR__ . '/files', // Change this to your desired root directory
    'allowed_extensions' => ['txt', 'pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif', 'zip', 'rar', 'mp4', 'mp3'],
    'max_file_size' => 10 * 1024 * 1024, // 10MB
    'password' => 'admin123' // Change this password
];

// Create files directory if it doesn't exist
if (!is_dir($config['root_path'])) {
    mkdir($config['root_path'], 0755, true);
}

// Simple authentication
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    if (isset($_POST['password']) && $_POST['password'] === $config['password']) {
        $_SESSION['authenticated'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    if (isset($_POST['password']) && $_POST['password'] !== $config['password']) {
        $error = 'Invalid password';
    }
    
    // Login form
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>File Manager - Login</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
            .login-container { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); width: 100%; max-width: 400px; }
            .login-header { text-align: center; margin-bottom: 2rem; }
            .login-header h1 { color: #333; font-size: 1.8rem; margin-bottom: 0.5rem; }
            .login-header p { color: #666; }
            .form-group { margin-bottom: 1.5rem; }
            .form-group label { display: block; margin-bottom: 0.5rem; color: #333; font-weight: 500; }
            .form-group input { width: 100%; padding: 0.75rem; border: 2px solid #e1e1e1; border-radius: 8px; font-size: 1rem; transition: border-color 0.3s; }
            .form-group input:focus { outline: none; border-color: #667eea; }
            .btn { width: 100%; padding: 0.75rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: transform 0.2s; }
            .btn:hover { transform: translateY(-2px); }
            .error { color: #e74c3c; text-align: center; margin-bottom: 1rem; }
        </style>
    </head>
    <body>
        <div class="login-container">
            <div class="login-header">
                <h1>🗂️ File Manager</h1>
                <p>Enter password to access dashboard</p>
            </div>
            <?php if (isset($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn">Login</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Get current directory
$current_dir = isset($_GET['dir']) ? $_GET['dir'] : '';
$current_path = $config['root_path'] . '/' . $current_dir;
$current_path = realpath($current_path);

// Security check - ensure we're within the root path
if (!$current_path || strpos($current_path, realpath($config['root_path'])) !== 0) {
    $current_path = $config['root_path'];
    $current_dir = '';
}

// Handle file operations
$message = '';
$error = '';

// Handle file upload
if (isset($_POST['upload']) && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $filename = basename($file['name']);
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (in_array($extension, $config['allowed_extensions'])) {
        if ($file['size'] <= $config['max_file_size']) {
            $destination = $current_path . '/' . $filename;
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $message = 'File uploaded successfully!';
            } else {
                $error = 'Failed to upload file.';
            }
        } else {
            $error = 'File size exceeds maximum limit.';
        }
    } else {
        $error = 'File type not allowed.';
    }
}

// Handle file deletion
if (isset($_GET['delete'])) {
    $file_to_delete = $current_path . '/' . basename($_GET['delete']);
    if (file_exists($file_to_delete)) {
        if (is_file($file_to_delete)) {
            unlink($file_to_delete);
            $message = 'File deleted successfully!';
        } elseif (is_dir($file_to_delete)) {
            rmdir($file_to_delete);
            $message = 'Directory deleted successfully!';
        }
    }
}

// Handle directory creation
if (isset($_POST['create_dir']) && !empty($_POST['dir_name'])) {
    $dir_name = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['dir_name']);
    $new_dir = $current_path . '/' . $dir_name;
    if (!file_exists($new_dir)) {
        mkdir($new_dir, 0755);
        $message = 'Directory created successfully!';
    } else {
        $error = 'Directory already exists.';
    }
}

// Handle file download
if (isset($_GET['download'])) {
    $file_to_download = $current_path . '/' . basename($_GET['download']);
    if (file_exists($file_to_download) && is_file($file_to_download)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file_to_download) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_to_download));
        readfile($file_to_download);
        exit;
    }
}

// Get files and directories
$files = [];
$directories = [];

if ($handle = opendir($current_path)) {
    while (false !== ($entry = readdir($handle))) {
        if ($entry != "." && $entry != "..") {
            $full_path = $current_path . '/' . $entry;
            if (is_dir($full_path)) {
                $directories[] = $entry;
            } else {
                $files[] = $entry;
            }
        }
    }
    closedir($handle);
}

sort($directories);
sort($files);

// Helper functions
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

function getFileIcon($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    $icons = [
        'pdf' => '📄',
        'doc' => '📄', 'docx' => '📄',
        'txt' => '📝',
        'jpg' => '🖼️', 'jpeg' => '🖼️', 'png' => '🖼️', 'gif' => '🖼️',
        'zip' => '📦', 'rar' => '📦',
        'mp4' => '🎬', 'avi' => '🎬',
        'mp3' => '🎵', 'wav' => '🎵',
    ];
    
    return isset($icons[$extension]) ? $icons[$extension] : '📄';
}

function getBreadcrumbs($current_dir) {
    $breadcrumbs = [];
    $path_parts = explode('/', trim($current_dir, '/'));
    $current_path = '';
    
    $breadcrumbs[] = ['name' => 'Home', 'path' => ''];
    
    foreach ($path_parts as $part) {
        if (!empty($part)) {
            $current_path .= '/' . $part;
            $breadcrumbs[] = ['name' => $part, 'path' => ltrim($current_path, '/')];
        }
    }
    
    return $breadcrumbs;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Manager Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        .btn-primary:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-1px);
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
            transform: translateY(-1px);
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-success:hover {
            background: #229954;
            transform: translateY(-1px);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .breadcrumbs {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .breadcrumbs a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .breadcrumbs a:hover {
            text-decoration: underline;
        }
        
        .actions-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .upload-form, .create-dir-form {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            flex: 1;
            min-width: 300px;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e1e1;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .file-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .file-item {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .file-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .file-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .file-icon {
            font-size: 2rem;
        }
        
        .file-info h3 {
            margin: 0;
            font-size: 1.1rem;
            color: #333;
            word-break: break-word;
        }
        
        .file-info p {
            margin: 0.25rem 0 0 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .file-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .btn-small {
            padding: 0.25rem 0.75rem;
            font-size: 0.8rem;
        }
        
        .message {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .empty-state h3 {
            color: #666;
            margin-bottom: 1rem;
        }
        
        .empty-state p {
            color: #888;
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            .actions-bar {
                flex-direction: column;
            }
            
            .container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">🗂️ File Manager</div>
            <div class="header-actions">
                <span>Welcome, Admin</span>
                <a href="?logout" class="btn btn-primary">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="breadcrumbs">
            📁 
            <?php
            $breadcrumbs = getBreadcrumbs($current_dir);
            foreach ($breadcrumbs as $index => $crumb) {
                if ($index > 0) echo ' / ';
                if ($index === count($breadcrumbs) - 1) {
                    echo htmlspecialchars($crumb['name']);
                } else {
                    echo '<a href="?dir=' . urlencode($crumb['path']) . '">' . htmlspecialchars($crumb['name']) . '</a>';
                }
            }
            ?>
        </div>
        
        <div class="actions-bar">
            <div class="upload-form">
                <h3>📤 Upload File</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="file">Choose file:</label>
                        <input type="file" id="file" name="file" required>
                    </div>
                    <button type="submit" name="upload" class="btn btn-success">Upload</button>
                </form>
            </div>
            
            <div class="create-dir-form">
                <h3>📁 Create Directory</h3>
                <form method="POST">
                    <div class="form-group">
                        <label for="dir_name">Directory name:</label>
                        <input type="text" id="dir_name" name="dir_name" required>
                    </div>
                    <button type="submit" name="create_dir" class="btn btn-success">Create</button>
                </form>
            </div>
        </div>
        
        <?php if (empty($directories) && empty($files)): ?>
            <div class="empty-state">
                <h3>📂 This directory is empty</h3>
                <p>Upload files or create directories to get started</p>
            </div>
        <?php else: ?>
            <div class="file-grid">
                <?php foreach ($directories as $dir): ?>
                    <div class="file-item">
                        <div class="file-header">
                            <div class="file-icon">📁</div>
                            <div class="file-info">
                                <h3><?php echo htmlspecialchars($dir); ?></h3>
                                <p>Directory</p>
                            </div>
                        </div>
                        <div class="file-actions">
                            <a href="?dir=<?php echo urlencode($current_dir . '/' . $dir); ?>" class="btn btn-primary btn-small">Open</a>
                            <a href="?delete=<?php echo urlencode($dir); ?>&dir=<?php echo urlencode($current_dir); ?>" 
                               class="btn btn-danger btn-small" 
                               onclick="return confirm('Are you sure you want to delete this directory?')">Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php foreach ($files as $file): ?>
                    <?php
                    $file_path = $current_path . '/' . $file;
                    $file_size = filesize($file_path);
                    $file_modified = date('M j, Y H:i', filemtime($file_path));
                    ?>
                    <div class="file-item">
                        <div class="file-header">
                            <div class="file-icon"><?php echo getFileIcon($file); ?></div>
                            <div class="file-info">
                                <h3><?php echo htmlspecialchars($file); ?></h3>
                                <p><?php echo formatBytes($file_size); ?> • <?php echo $file_modified; ?></p>
                            </div>
                        </div>
                        <div class="file-actions">
                            <a href="?download=<?php echo urlencode($file); ?>&dir=<?php echo urlencode($current_dir); ?>" 
                               class="btn btn-primary btn-small">Download</a>
                            <a href="?delete=<?php echo urlencode($file); ?>&dir=<?php echo urlencode($current_dir); ?>" 
                               class="btn btn-danger btn-small" 
                               onclick="return confirm('Are you sure you want to delete this file?')">Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>