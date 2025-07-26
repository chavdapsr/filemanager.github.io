<?php
if ($_FILES['file']['name']) {
    $uploadDir = 'uploads/';
    $filename = basename($_FILES['file']['name']);
    $target = $uploadDir . $filename;

    if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
        echo "File uploaded successfully.";
    } else {
        echo "Upload failed.";
    }
}
header('Location: index.php');
