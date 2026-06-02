<?php
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
session_write_close();
$db     = getDB();

switch ($method) {

    case 'GET':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : (isLoggedIn() ? currentUserId() : 0);
        if (!$id) { jsonResponse(['error' => 'User ID required.'], 422); }

        $stmt = $db->prepare('SELECT id, username, avatar, bio, location, rating, review_count, is_verified, created_at FROM users WHERE id = ? AND is_active = 1');
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if (!$user) { jsonResponse(['error' => 'User not found.'], 404); }

        // Public listings
        $listStmt = $db->prepare('
            SELECT l.id, l.title, l.price, l.condition, l.created_at, l.status,
                   (SELECT filename FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) AS image
            FROM listings l WHERE l.seller_id = ? AND l.status != "removed"
            ORDER BY l.created_at DESC LIMIT 20
        ');
        $listStmt->execute([$id]);
        $user['listings'] = $listStmt->fetchAll();

        jsonResponse($user);
        break;

    case 'PUT':
        requireLogin();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $userId = currentUserId();

        $fields = [];
        $params = [];
        foreach (['bio', 'location', 'phone'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = ?";
                $params[] = trim($data[$field]);
            }
        }

        if (isset($data['password'])) {
            if (strlen($data['password']) < 8) {
                jsonResponse(['error' => 'Password must be at least 8 characters.'], 422);
            }
            $fields[] = 'password_hash = ?';
            $params[] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }

        if (!$fields) { jsonResponse(['error' => 'Nothing to update.'], 422); }

        $params[] = $userId;
        $db->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($params);
        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['error' => 'Method not allowed.'], 405);
}
