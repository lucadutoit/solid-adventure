<?php
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed.'], 405);
}

$data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$email    = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if (!$email || !$password) {
    jsonResponse(['error' => 'Email and password are required.'], 422);
}

$db = getDB();
$stmt = $db->prepare(
    'SELECT u.id, u.username, u.password_hash, u.is_active,
            r.is_admin, r.can_buy, r.can_sell
     FROM users u JOIN roles r ON u.role_id = r.id
     WHERE u.email = ? LIMIT 1'
);
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    jsonResponse(['error' => 'Invalid email or password.'], 401);
}

if (!$user['is_active']) {
    jsonResponse(['error' => 'Account is disabled.'], 403);
}

session_regenerate_id(true);
$_SESSION['user_id']  = (int)$user['id'];
$_SESSION['username'] = $user['username'];

jsonResponse(['success' => true, 'user' => [
    'id'       => $user['id'],
    'username' => $user['username'],
    'is_admin' => (bool)$user['is_admin'],
    'can_buy'  => (bool)$user['can_buy'],
    'can_sell' => (bool)$user['can_sell'],
]]);
