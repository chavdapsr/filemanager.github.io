<?php
// Delete handler
// Usage: delete.php?file=filename
if (isset($_GET['file'])) {
    $file = basename($_GET['file']);
    $filepath = __DIR__ . '/' . $file;
    if (file_exists($filepath)) {
        unlink($filepath);
    }
}
header('Location: index.php');
exit;
