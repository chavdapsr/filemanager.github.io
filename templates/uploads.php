<?php
include 'conn.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $target_dir = "uploads/";
    $filename = basename($_FILES["file"]["name"]);
    $target_file = $target_dir . $filename;
    if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
        $stmt = $pdo->prepare("INSERT INTO files (filename, filepath) VALUES (?, ?)");
        $stmt->execute([$filename, $target_file]);
        echo "File uploaded successfully.";
    } else {
        echo "Error uploading file.";
    }
}
?>
