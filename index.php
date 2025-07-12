<?php
// --- CONFIG ---
$root = realpath('../'); // Set to your project root
$startDir = isset($_GET['dir']) ? $_GET['dir'] : $root;
$dir = realpath($startDir);
if (!$dir || strpos($dir, $root) !== 0) $dir = $root; // Prevent directory traversal

// --- Handle AJAX Actions ---
header('Access-Control-Allow-Origin: *');
if (isset($_POST['action'])) {
    $response = ['success' => false];
    switch ($_POST['action']) {
        case 'list':
            $path = realpath($_POST['path'] ?? $root);
            if (!$path || strpos($path, $root) !== 0) $path = $root;
            $items = scandir($path);
            $result = [];
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                $itemPath = $path . DIRECTORY_SEPARATOR . $item;
                $result[] = [
                    'text' => $item,
                    'type' => is_dir($itemPath) ? 'folder' : 'file',
                    'children' => is_dir($itemPath),
                    'id' => $itemPath
                ];
            }
            $response = $result;
            break;
        case 'create_folder':
            $parent = realpath($_POST['parent'] ?? $root);
            $name = trim($_POST['name']);
            if ($parent && $name && strpos($parent, $root) === 0) {
                $newPath = $parent . DIRECTORY_SEPARATOR . $name;
                $response['success'] = mkdir($newPath);
            }
            break;
        case 'rename':
            $from = realpath($_POST['from']);
            $to = dirname($from) . DIRECTORY_SEPARATOR . $_POST['to'];
            if ($from && strpos($from, $root) === 0) {
                $response['success'] = rename($from, $to);
            }
            break;
        case 'delete':
            $target = realpath($_POST['target']);
            if ($target && strpos($target, $root) === 0) {
                if (is_dir($target)) {
                    $response['success'] = rmdir($target);
                } else {
                    $response['success'] = unlink($target);
                }
            }
            break;
        case 'upload':
            $parent = realpath($_POST['parent'] ?? $root);
            if ($parent && isset($_FILES['file']) && strpos($parent, $root) === 0) {
                $target = $parent . DIRECTORY_SEPARATOR . basename($_FILES['file']['name']);
                $response['success'] = move_uploaded_file($_FILES['file']['tmp_name'], $target);
            }
            break;
        case 'download':
            $file = realpath($_POST['file']);
            if ($file && is_file($file) && strpos($file, $root) === 0) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename=' . basename($file));
                readfile($file);
                exit;
            }
            break;
        case 'copy':
            $from = realpath($_POST['from']);
            $to = $_POST['to'];
            if ($from && strpos($from, $root) === 0 && $to && strpos(dirname($to), $root) === 0) {
                $response['success'] = copy($from, $to);
            }
            break;
        case 'move':
            $from = realpath($_POST['from']);
            $to = $_POST['to'];
            if ($from && strpos($from, $root) === 0 && $to && strpos(dirname($to), $root) === 0) {
                $response['success'] = rename($from, $to);
            }
            break;
    }
    echo json_encode($response);
    exit;
}
?>