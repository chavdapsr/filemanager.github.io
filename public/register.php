<?php
// Register page
require_once __DIR__ . '/../config/database.php';

$db = new Database();
$pdo = $db->getConnection();
$message = '';

// Auto-register the provided user if not exists
$email = 'chavda096@gmail.com';
$username = 'chavda096@gmail.com';
$password = 'Tec@#903366';
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
if (!$stmt->fetch()) {
    $insert = $pdo->prepare('INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)');
    if ($insert->execute([$username, $email, $hashedPassword, 'user'])) {
        $message = 'User chavda096@gmail.com registered successfully!';
    } else {
        $message = 'Failed to register user.';
    }
} else {
    $message = 'User already exists.';
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - PHP File Manager</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <h1>Register</h1>
    <?php if (!empty($message)): ?>
        <div style="color:green; margin-bottom:10px;"> <?= htmlspecialchars($message) ?> </div>
    <?php endif; ?>
    <form method="post" action="">
        <input type="text" name="username" placeholder="Username" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <button type="submit">Register</button>
    </form>
</body>
</html>
