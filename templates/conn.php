<?php
$pdo = new PDO("mysql:host=localhost;dbname=file_manager", "username", "password");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
?>
