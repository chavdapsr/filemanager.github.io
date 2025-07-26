<?php
$dir = 'uploads/';
$base_dir = __DIR__ . '/uploads/';
if (!is_dir($base_dir)) mkdir($base_dir, 0777, true);
$message = '';

// Helper: Recursively delete a folder
function deleteFolder($dir) {
    if (!is_dir($dir)) return false;
    $items = array_diff(scandir($dir), ['.', '..']);
    foreach ($items as $item) {
        $path = "$dir/$item";
        is_dir($path) ? deleteFolder($path) : unlink($path);
    }
    return rmdir($dir);
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Upload
    if (isset($_FILES['file'])) {
        $file = $_FILES['file'];
        $target = $base_dir . basename($file['name']);
        $message = move_uploaded_file($file['tmp_name'], $target)
            ? "File uploaded successfully!"
            : "Error uploading file!";
    }
    // Create folder
    elseif (isset($_POST['folder_name'])) {
        $folder_name = preg_replace('/[^A-Za-z0-9_\-]/', '', $_POST['folder_name']);
        $target = $base_dir . $folder_name;
        if (!file_exists($target)) {
            mkdir($target, 0777, true);
            $message = "Folder created successfully!";
        } else {
            $message = "Folder already exists!";
        }
    }
    // Create file
    elseif (isset($_POST['new_file_name'])) {
        $file_name = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $_POST['new_file_name']);
        $target = $base_dir . $file_name;
        if (!file_exists($target)) {
            file_put_contents($target, '');
            $message = "File created successfully!";
        } else {
            $message = "File already exists!";
        }
    }
    // Delete file/folder
    elseif (isset($_POST['delete_name'], $_POST['delete_type'])) {
        $name = basename($_POST['delete_name']);
        $type = $_POST['delete_type'];
        $target = $base_dir . $name;
        if (file_exists($target)) {
            if ($type === 'folder') {
                $message = deleteFolder($target) ? "Folder deleted!" : "Error deleting folder!";
            } else {
                $message = unlink($target) ? "File deleted!" : "Error deleting file!";
            }
        } else {
            $message = "File/Folder doesn't exist!";
        }
    }
}

// Download handler
if (isset($_GET['download'])) {
    $file = basename($_GET['download']);
    $path = $base_dir . $file;
    if (is_file($path)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }
}

// List files/folders
$files = array_diff(scandir($base_dir), ['.', '..']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Manager Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; }
        .file-card { cursor: pointer; transition: all 0.3s; }
        .file-card:hover { box-shadow: 0 .5rem 1rem rgba(0,0,0,.15); background: #f8f9fa; }
        .centered-form { max-width: 600px; margin: 0 auto; }
        .progress { height: 20px; }
        .table td, .table th { vertical-align: middle; }
    </style>
</head>
<body>
    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand text-dark" href="#">File Manager</a>
            <div class="d-flex align-items-center">
                <input class="form-control me-2" type="search" placeholder="Search files...">
                <button class="btn btn-outline-dark me-2"><i class="bi bi-bell"></i></button>
                <div class="dropdown">
                    <button class="btn btn-outline-dark dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> User
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#">Profile</a></li>
                        <li><a class="dropdown-item" href="#">Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item"><a class="nav-link active" href="#"><i class="bi bi-folder"></i> My Files</a></li>
                        <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-clock-history"></i> Recent</a></li>
                        <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-star"></i> Starred</a></li>
                        <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-trash"></i> Trash</a></li>
                        <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-google"></i> Google Drive</a></li>
                        <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-dropbox"></i> Dropbox</a></li>
                        <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-share"></i> Shared</a></li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Documents</a></li>
                            <li class="breadcrumb-item active">Projects</li>
                        </ol>
                    </nav>
                    <div>
                        <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#newFolderModal"><i class="bi bi-plus-lg"></i> New Folder</button>
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#uploadModal"><i class="bi bi-upload"></i> Upload</button>
                    </div>
                </div>

                <!-- Storage Usage -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Storage</h5>
                        <div class="progress mb-2">
                            <div class="progress-bar" role="progressbar" style="width: 75%" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small class="text-muted">75% of 100 GB used</small>
                    </div>
                </div>

                <!-- Alert message -->
                <?php if ($message): ?>
                    <div class="alert alert-info text-center centered-form"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>

                <!-- Modals for Upload and New Folder -->
                <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <form method="POST" enctype="multipart/form-data" class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="uploadModalLabel">Upload File</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="file" name="file" required class="form-control">
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-success">Upload</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="modal fade" id="newFolderModal" tabindex="-1" aria-labelledby="newFolderModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <form method="POST" class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="newFolderModalLabel">Create New Folder</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="text" name="folder_name" placeholder="New Folder Name" required class="form-control">
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary">Create Folder</button>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- Create File Form -->
                <form method="POST" class="mb-4 row g-2 justify-content-center align-items-center centered-form">
                    <div class="col-8 col-md-7">
                        <input type="text" name="new_file_name" placeholder="New File Name" required class="form-control">
                    </div>
                    <div class="col-4 col-md-3">
                        <button type="submit" class="btn btn-secondary w-100">Create File</button>
                    </div>
                </form>

                <!-- Files Grid -->
                <div class="row">
                    <?php foreach ($files as $file): ?>
                        <?php $isFolder = is_dir($base_dir . $file); ?>
                        <div class="col-sm-6 col-md-4 col-lg-3 mb-4">
                            <div class="card file-card">
                                <div class="card-body text-center">
                                    <i class="bi <?php echo $isFolder ? 'bi-folder-fill text-warning' : 'bi-file-earmark-text text-primary'; ?> fs-1 mb-3"></i>
                                    <h6 class="card-title"><?php echo htmlspecialchars($file); ?></h6>
                                    <p class="card-text text-muted">
                                        <?php echo $isFolder ? 'Folder' : (is_file($base_dir . $file) ? round(filesize($base_dir . $file)/1024, 2).' KB' : ''); ?>
                                    </p>
                                    <div class="d-flex justify-content-center gap-2">
                                        <?php if (!$isFolder): ?>
                                            <a href="?download=<?php echo urlencode($file); ?>" class="btn btn-sm btn-outline-primary" title="Download"><i class="bi bi-download"></i></a>
                                        <?php endif; ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($file); ?>?');">
                                            <input type="hidden" name="delete_name" value="<?php echo htmlspecialchars($file); ?>">
                                            <input type="hidden" name="delete_type" value="<?php echo $isFolder ? 'folder' : 'file'; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Files Table (for large screens) -->
                <div class="card mt-4 d-none d-lg-block">
                    <div class="card-body">
                        <h5 class="card-title">Recent Files</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Date Modified</th>
                                        <th>Size</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($files as $file): ?>
                                        <?php
                                          $isFolder = is_dir($base_dir . $file);
                                          $filePath = $base_dir . $file;
                                        ?>
                                        <tr>
                                          <td>
                                            <i class="bi <?php echo $isFolder ? 'bi-folder-fill text-warning' : 'bi-file-earmark-text text-primary'; ?>"></i>
                                            <?php echo htmlspecialchars($file); ?>
                                          </td>
                                          <td>
                                            <?php echo date("Y-m-d H:i", filemtime($filePath)); ?>
                                          </td>
                                          <td>
                                            <?php echo $isFolder ? 'Folder' : (is_file($filePath) ? round(filesize($filePath)/1024, 2).' KB' : ''); ?>
                                          </td>
                                          <td>
                                            <div class="d-flex gap-2">
                                              <?php if (!$isFolder): ?>
                                                <a href="?download=<?php echo urlencode($file); ?>" class="btn btn-sm btn-outline-primary" title="Download"><i class="bi bi-download"></i></a>
                                              <?php endif; ?>
                                              <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($file); ?>?');">
                                                <input type="hidden" name="delete_name" value="<?php echo htmlspecialchars($file); ?>">
                                                <input type="hidden" name="delete_type" value="<?php echo $isFolder ? 'folder' : 'file'; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                              </form>
                                            </div>
                                          </td>
                                        </tr>
                                      <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
