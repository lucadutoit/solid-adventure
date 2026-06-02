<?php
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed.'], 405);
}

$data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$username = trim($data['username'] ?? '');
$email    = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if (!$username || !$email || !$password) {
    jsonResponse(['error' => 'All fields are required.'], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['error' => 'Invalid email address.'], 422);
}

if (strlen($username) < 3 || strlen($username) > 50) {
    jsonResponse(['error' => 'Username must be 3–50 characters.'], 422);
}

if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    jsonResponse(['error' => 'Username may only contain letters, numbers, and underscores.'], 422);
}

if (strlen($password) < 8) {
    jsonResponse(['error' => 'Password must be at least 8 characters.'], 422);
}

$db = getDB();

$stmt = $db->prepare('SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1');
$stmt->execute([$email, $username]);
if ($stmt->fetch()) {
    jsonResponse(['error' => 'Email or username already in use.'], 409);
}

$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
$stmt = $db->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
$stmt->execute([$username, $email, $hash]);
$userId = (int)$db->lastInsertId();

$roleStmt = $db->prepare(
    'SELECT r.is_admin, r.can_buy, r.can_sell FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?'
);
$roleStmt->execute([$userId]);
$role = $roleStmt->fetch();

$_SESSION['user_id']  = $userId;
$_SESSION['username'] = $username;

jsonResponse(['success' => true, 'user' => [
    'id'       => $userId,
    'username' => $username,
    'is_admin' => (bool)($role['is_admin'] ?? false),
    'can_buy'  => (bool)($role['can_buy']  ?? true),
    'can_sell' => (bool)($role['can_sell'] ?? true),
]]);
