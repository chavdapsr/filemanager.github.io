<?php
$folderName = basename($_POST['folder_name']);
$path = "uploads/$folderName";

if (!is_dir($path)) {
    mkdir($path, 0777, true);
    echo "Folder created.";
} else {
    echo "Folder already exists.";
}
header('Location: index.php');
