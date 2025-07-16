<?php
// CRUD operations handler for records table
require_once __DIR__ . '/../config/database.php';

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$response = ['success' => false, 'message' => '', 'data' => null];

if ($action === 'create' && isset($_POST['name'], $_POST['email'])) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    if ($name && $email) {
        $stmt = $pdo->prepare('INSERT INTO records (name, email) VALUES (?, ?)');
        $response['success'] = $stmt->execute([$name, $email]);
        $response['message'] = $response['success'] ? 'Record added.' : 'Insert failed.';
    } else {
        $response['message'] = 'Invalid input.';
    }
} elseif ($action === 'read') {
    $stmt = $pdo->query('SELECT * FROM records ORDER BY id DESC');
    $response['success'] = true;
    $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($action === 'update' && isset($_POST['id'], $_POST['name'], $_POST['email'])) {
    $id = (int)$_POST['id'];
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $stmt = $pdo->prepare('UPDATE records SET name=?, email=? WHERE id=?');
    $response['success'] = $stmt->execute([$name, $email, $id]);
    $response['message'] = $response['success'] ? 'Record updated.' : 'Update failed.';
} elseif ($action === 'delete' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $stmt = $pdo->prepare('DELETE FROM records WHERE id=?');
    $response['success'] = $stmt->execute([$id]);
    $response['message'] = $response['success'] ? 'Record deleted.' : 'Delete failed.';
} else {
    $response['message'] = 'Invalid request.';
}

header('Content-Type: application/json');
echo json_encode($response);
