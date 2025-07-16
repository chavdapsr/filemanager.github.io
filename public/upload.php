<?php
// Upload handler
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload - PHP File Manager</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <h1>Upload File</h1>
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="file" required><br>
        <button type="submit">Upload</button>
    </form>
</body>
</html>
