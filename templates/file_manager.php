<?php
// --- CONFIG ---
$root = realpath('.'); // Set to your root directory
$startDir = isset($_GET['dir']) ? $_GET['dir'] : $root;
$dir = realpath($startDir);
if (!$dir || strpos($dir, $root) !== 0) $dir = $root; // Prevent directory traversal

// --- Handle Actions ---
// Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $target = $dir . DIRECTORY_SEPARATOR . basename($_FILES['file']['name']);
    move_uploaded_file($_FILES['file']['tmp_name'], $target);
    header('Location: ?dir=' . urlencode($dir));
    exit;
}
// Create Folder
if (isset($_POST['newfolder'])) {
    $folder = trim($_POST['newfolder']);
    if ($folder) {
        mkdir($dir . DIRECTORY_SEPARATOR . $folder);
    }
    header('Location: ?dir=' . urlencode($dir));
    exit;
}
// Rename
if (isset($_POST['rename_from'], $_POST['rename_to'])) {
    $from = $dir . DIRECTORY_SEPARATOR . basename($_POST['rename_from']);
    $to = $dir . DIRECTORY_SEPARATOR . basename($_POST['rename_to']);
    if (file_exists($from)) {
        rename($from, $to);
    }
    header('Location: ?dir=' . urlencode($dir));
    exit;
}
// Delete
if (isset($_GET['del'])) {
    $target = $dir . DIRECTORY_SEPARATOR . basename($_GET['del']);
    if (is_dir($target)) {
        rmdir($target);
    } else {
        unlink($target);
    }
    header('Location: ?dir=' . urlencode($dir));
    exit;
}
// Download
if (isset($_GET['download'])) {
    $file = $dir . DIRECTORY_SEPARATOR . basename($_GET['download']);
    if (file_exists($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . basename($file));
        readfile($file);
        exit;
    }
}
// Preview
function preview_file($file) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg','jpeg','png','gif','bmp','webp'])) {
        echo "<img src='?dir=".urlencode(dirname($file))."&preview=".urlencode(basename($file))."' style='max-width:300px;'>";
    } elseif (in_array($ext, ['txt','md','log','php','html','css','js','json','xml','csv'])) {
        echo '<pre>' . htmlspecialchars(file_get_contents($file)) . '</pre>';
    } else {
        echo 'Preview not available.';
    }
}
if (isset($_GET['preview'])) {
    $file = $dir . DIRECTORY_SEPARATOR . basename($_GET['preview']);
    if (file_exists($file)) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','bmp','webp'])) {
            header('Content-Type: image/' . ($ext === 'jpg' ? 'jpeg' : $ext));
            readfile($file);
            exit;
        }
    }
}
// --- HTML ---
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PHP File Manager</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>body{padding:2em;} .table td, .table th{vertical-align:middle;}</style>
</head>
<body>
<div class="container">
    <h2>PHP File Manager</h2>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <?php
        $parts = explode(DIRECTORY_SEPARATOR, str_replace($root, '', $dir));
        $path = $root;
        echo '<li class="breadcrumb-item"><a href="?dir='.urlencode($root).'">root</a></li>';
        foreach ($parts as $part) {
            if ($part === '') continue;
            $path .= DIRECTORY_SEPARATOR . $part;
            echo '<li class="breadcrumb-item"><a href="?dir='.urlencode($path).'">'.htmlspecialchars($part).'</a></li>';
        }
        ?>
      </ol>
    </nav>
    <form method="post" enctype="multipart/form-data" class="mb-3 d-flex gap-2">
        <input type="file" name="file" class="form-control" required>
        <button type="submit" class="btn btn-primary">Upload</button>
    </form>
    <form method="post" class="mb-3 d-flex gap-2">
        <input type="text" name="newfolder" class="form-control" placeholder="New folder name" required>
        <button type="submit" class="btn btn-success">Create Folder</button>
    </form>
    <table class="table table-striped" id="files">
        <thead><tr><th>Name</th><th>Type</th><th>Size</th><th>Modified</th><th>Actions</th></tr></thead>
        <tbody>
        <?php
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            $isDir = is_dir($path);
            echo '<tr>';
            echo '<td>';
            if ($isDir) {
                echo '<a href="?dir='.urlencode($path).'">📁 '.htmlspecialchars($file).'</a>';
            } else {
                echo '<a href="?dir='.urlencode($dir).'&preview='.urlencode($file).'">'.htmlspecialchars($file).'</a>';
            }
            echo '</td>';
            echo '<td>'.($isDir ? 'Folder' : 'File').'</td>';
            echo '<td>'.($isDir ? '-' : filesize($path)).'</td>';
            echo '<td>'.date('Y-m-d H:i', filemtime($path)).'</td>';
            echo '<td>';
            if (!$isDir) {
                echo '<a class="btn btn-sm btn-outline-primary" href="?dir='.urlencode($dir).'&download='.urlencode($file).'">Download</a> ';
                echo '<a class="btn btn-sm btn-outline-secondary" href="?dir='.urlencode($dir).'&preview='.urlencode($file).'">Preview</a> ';
            }
            echo '<form method="post" style="display:inline-block;" class="d-inline-block me-1">
                    <input type="hidden" name="rename_from" value="'.htmlspecialchars($file).'">
                    <input type="text" name="rename_to" value="'.htmlspecialchars($file).'" style="width:90px;">
                    <button class="btn btn-sm btn-outline-warning" type="submit">Rename</button>
                  </form>';
            echo '<a class="btn btn-sm btn-outline-danger" href="?dir='.urlencode($dir).'&del='.urlencode($file).'" onclick="return confirm(\'Delete '.htmlspecialchars($file).' ?\')">Delete</a>';
            echo '</td>';
            echo '</tr>';
        }
        ?>
        </tbody>
    </table>
    <?php
    // File preview
    if (isset($_GET['preview'])) {
        $file = $dir . DIRECTORY_SEPARATOR . basename($_GET['preview']);
        if (file_exists($file) && is_file($file)) {
            echo '<h5>Preview: '.htmlspecialchars(basename($file)).'</h5>';
            preview_file($file);
        }
    }
    ?>
</div>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css"/>
<script>$(function(){$('#files').DataTable();});</script>
</body>
</html>

