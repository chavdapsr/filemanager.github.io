<?php
// Login page

session_start();
require_once __DIR__ . '/../config/database.php';

// Create Database instance and get PDO connection
$db = new Database();
$pdo = $db->getConnection();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if ($username && $password) {
        // Example: Replace with your real user table and password check
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role'] ?? 'user'
            ];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please enter both username and password.';
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - PHP File Manager</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <h1>Login</h1>
    <?php if (!empty($error)): ?>
        <div style="color:red; margin-bottom:10px;"> <?= htmlspecialchars($error) ?> </div>
    <?php endif; ?>
    <form method="post" action="">
        <input type="text" name="username" placeholder="Username" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <button type="submit">Login</button>
    </form>
</body>
</html>
