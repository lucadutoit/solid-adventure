<?php
require_once __DIR__ . '/../config/database.php';

requireLogin();
session_write_close();
$db     = getDB();
$userId = currentUserId();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $type = $_GET['type'] ?? 'buying'; // buying | selling
        $field = $type === 'selling' ? 'seller_id' : 'buyer_id';

        $stmt = $db->prepare("
            SELECT o.id, o.amount, o.status, o.created_at, o.payment_method,
                   l.title AS listing_title, l.id AS listing_id,
                   IF(o.buyer_id = ?, s.username, b.username) AS other_party
            FROM orders o
            JOIN listings l ON l.id = o.listing_id
            JOIN users s ON s.id = o.seller_id
            JOIN users b ON b.id = o.buyer_id
            WHERE o.$field = ?
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([$userId, $userId]);
        jsonResponse(['orders' => $stmt->fetchAll()]);
        break;

    case 'POST':
        $data      = json_decode(file_get_contents('php://input'), true) ?? [];
        $listingId = (int)($data['listing_id'] ?? 0);
        $payment   = trim($data['payment_method'] ?? 'cash');
        $notes     = trim($data['notes'] ?? '');

        if (!$listingId) { jsonResponse(['error' => 'listing_id required.'], 422); }

        // Check if user can buy
        $stmt = $db->prepare('SELECT r.can_buy FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user || !$user['can_buy']) {
            jsonResponse(['error' => 'Your role does not have permission to make purchases.'], 403);
        }

        $stmt = $db->prepare("SELECT id, seller_id, price, status FROM listings WHERE id = ? AND status = 'active'");
        $stmt->execute([$listingId]);
        $listing = $stmt->fetch();
        if (!$listing) { jsonResponse(['error' => 'Listing not available.'], 422); }
        if ($listing['seller_id'] === $userId) { jsonResponse(['error' => 'Cannot buy your own listing.'], 422); }

        $db->beginTransaction();
        try {
            $db->prepare('INSERT INTO orders (listing_id, buyer_id, seller_id, amount, payment_method, notes) VALUES (?,?,?,?,?,?)')
               ->execute([$listingId, $userId, $listing['seller_id'], $listing['price'], $payment, $notes]);
            $orderId = (int)$db->lastInsertId();

            $db->prepare("UPDATE listings SET status = 'reserved' WHERE id = ?")->execute([$listingId]);
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            jsonResponse(['error' => 'Could not create order.'], 500);
        }

        jsonResponse(['success' => true, 'order_id' => $orderId], 201);
        break;

    case 'PUT':
        $orderId = (int)($_GET['id'] ?? 0);
        $data    = json_decode(file_get_contents('php://input'), true) ?? [];
        $status  = $data['status'] ?? '';

        $stmt = $db->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if (!$order) { jsonResponse(['error' => 'Order not found.'], 404); }
        if ($order['buyer_id'] !== $userId && $order['seller_id'] !== $userId) {
            jsonResponse(['error' => 'Forbidden.'], 403);
        }

        $allowed = ['confirmed', 'completed', 'cancelled'];
        if (!in_array($status, $allowed)) { jsonResponse(['error' => 'Invalid status.'], 422); }

        $db->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$status, $orderId]);

        if ($status === 'completed') {
            $db->prepare("UPDATE listings SET status = 'sold' WHERE id = ?")->execute([$order['listing_id']]);
        } elseif ($status === 'cancelled') {
            $db->prepare("UPDATE listings SET status = 'active' WHERE id = ?")->execute([$order['listing_id']]);
        }

        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['error' => 'Method not allowed.'], 405);
}
