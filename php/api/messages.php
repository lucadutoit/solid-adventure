<?php
require_once __DIR__ . '/../config/database.php';

requireLogin();
session_write_close();
$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();
$userId = currentUserId();

switch ($method) {

    case 'GET':
        $listingId = isset($_GET['listing_id']) ? (int)$_GET['listing_id'] : 0;

        if ($listingId) {
            // Conversation thread for a listing
            $stmt = $db->prepare('
                SELECT m.id, m.body, m.is_read, m.created_at,
                       m.sender_id, u.username AS sender_name, u.avatar AS sender_avatar
                FROM messages m
                JOIN users u ON u.id = m.sender_id
                WHERE m.listing_id = ?
                  AND (m.sender_id = ? OR m.receiver_id = ?)
                ORDER BY m.created_at ASC
            ');
            $stmt->execute([$listingId, $userId, $userId]);
            $messages = $stmt->fetchAll();

            // Mark as read
            $db->prepare('UPDATE messages SET is_read = 1 WHERE listing_id = ? AND receiver_id = ? AND is_read = 0')
               ->execute([$listingId, $userId]);

            jsonResponse(['messages' => $messages]);
        } else {
            // Inbox: list all conversations
            $stmt = $db->prepare('
                SELECT m.listing_id, l.title AS listing_title,
                       IF(m.sender_id = ?, m.receiver_id, m.sender_id) AS other_user_id,
                       u.username AS other_username,
                       MAX(m.created_at) AS last_message_at,
                       SUM(m.is_read = 0 AND m.receiver_id = ?) AS unread_count
                FROM messages m
                JOIN listings l ON l.id = m.listing_id
                JOIN users u ON u.id = IF(m.sender_id = ?, m.receiver_id, m.sender_id)
                WHERE m.sender_id = ? OR m.receiver_id = ?
                GROUP BY m.listing_id, other_user_id
                ORDER BY last_message_at DESC
            ');
            $stmt->execute([$userId, $userId, $userId, $userId, $userId]);
            jsonResponse(['conversations' => $stmt->fetchAll()]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $listingId  = (int)($data['listing_id'] ?? 0);
        $receiverId = (int)($data['receiver_id'] ?? 0);
        $body       = trim($data['body'] ?? '');

        if (!$listingId || !$receiverId || !$body) {
            jsonResponse(['error' => 'listing_id, receiver_id, and body are required.'], 422);
        }

        if ($receiverId === $userId) {
            jsonResponse(['error' => 'Cannot message yourself.'], 422);
        }

        $stmt = $db->prepare('INSERT INTO messages (listing_id, sender_id, receiver_id, body) VALUES (?,?,?,?)');
        $stmt->execute([$listingId, $userId, $receiverId, $body]);

        jsonResponse(['success' => true, 'id' => (int)$db->lastInsertId()], 201);
        break;

    default:
        jsonResponse(['error' => 'Method not allowed.'], 405);
}
