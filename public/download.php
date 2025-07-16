<?php
// Download handler
// Usage: download.php?file=filename
if (isset($_GET['file'])) {
    $file = basename($_GET['file']);
    $filepath = __DIR__ . '/' . $file;
    if (file_exists($filepath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . basename($filepath));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }
}
header('Location: index.php');
exit;
