<?php
include 'conn.php';
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT filepath FROM files WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $file = $stmt->fetch();
    if ($file && file_exists($file['filepath'])) {
        unlink($file['filepath']);
    }
    $pdo->prepare("DELETE FROM files WHERE id = ?")->execute([$_GET['id']]);
    header("Location: view_files.php");
}
?>
