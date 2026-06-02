<?php
require_once __DIR__ . '/../config/database.php';

requireLogin();
session_write_close();
$db     = getDB();
$userId = currentUserId();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $stmt = $db->prepare('
            SELECT l.id, l.title, l.price, l.condition, l.status, l.created_at,
                   u.username AS seller_name,
                   (SELECT filename FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) AS image
            FROM watchlist w
            JOIN listings l ON l.id = w.listing_id
            JOIN users u ON u.id = l.seller_id
            WHERE w.user_id = ?
            ORDER BY w.created_at DESC
        ');
        $stmt->execute([$userId]);
        jsonResponse(['watchlist' => $stmt->fetchAll()]);
        break;

    case 'POST':
        $data      = json_decode(file_get_contents('php://input'), true) ?? [];
        $listingId = (int)($data['listing_id'] ?? 0);
        if (!$listingId) { jsonResponse(['error' => 'listing_id required.'], 422); }

        $db->prepare('INSERT IGNORE INTO watchlist (user_id, listing_id) VALUES (?,?)')->execute([$userId, $listingId]);
        jsonResponse(['success' => true]);
        break;

    case 'DELETE':
        $listingId = (int)($_GET['listing_id'] ?? 0);
        if (!$listingId) { jsonResponse(['error' => 'listing_id required.'], 422); }

        $db->prepare('DELETE FROM watchlist WHERE user_id = ? AND listing_id = ?')->execute([$userId, $listingId]);
        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['error' => 'Method not allowed.'], 405);
}
