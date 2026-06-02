<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
session_write_close();
$db     = getDB();

switch ($method) {

    case 'GET':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id) {
            // Single listing
            $stmt = $db->prepare('
                SELECT l.*, u.username AS seller_name, u.avatar AS seller_avatar,
                       u.rating AS seller_rating, u.review_count AS seller_reviews,
                       c.name AS category_name
                FROM listings l
                JOIN users u ON u.id = l.seller_id
                JOIN categories c ON c.id = l.category_id
                WHERE l.id = ? AND l.status != "removed"
            ');
            $stmt->execute([$id]);
            $listing = $stmt->fetch();
            if (!$listing) { jsonResponse(['error' => 'Listing not found.'], 404); }

            // Increment view count
            $db->prepare('UPDATE listings SET view_count = view_count + 1 WHERE id = ?')->execute([$id]);

            // Fetch images
            $imgStmt = $db->prepare('SELECT filename, is_primary FROM listing_images WHERE listing_id = ? ORDER BY is_primary DESC, sort_order');
            $imgStmt->execute([$id]);
            $listing['images'] = $imgStmt->fetchAll();

            jsonResponse($listing);
        } else {
            // Listing feed / search
            $page     = max(1, (int)($_GET['page'] ?? 1));
            $limit    = 20;
            $offset   = ($page - 1) * $limit;
            $category = (int)($_GET['category'] ?? 0);
            $q        = trim($_GET['q'] ?? '');
            $minRaw   = $_GET['min_price'] ?? '';
            $maxRaw   = $_GET['max_price'] ?? '';
            $minPrice = ($minRaw !== '') ? (float)$minRaw : null;
            $maxPrice = ($maxRaw !== '') ? (float)$maxRaw : null;
            $sort     = in_array($_GET['sort'] ?? '', ['price_asc','price_desc','newest']) ? $_GET['sort'] : 'newest';

            $where  = ["l.status = 'active'"];
            $params = [];

            if ($category) { $where[] = 'l.category_id = ?'; $params[] = $category; }
            if ($q) {
                $where[] = '(l.title LIKE ? OR l.description LIKE ?)';
                $params[] = '%' . $q . '%';
                $params[] = '%' . $q . '%';
            }
            if ($minPrice !== null) { $where[] = 'l.price >= ?'; $params[] = $minPrice; }
            if ($maxPrice !== null) { $where[] = 'l.price <= ?'; $params[] = $maxPrice; }

            $orderBy = match($sort) {
                'price_asc'  => 'l.price ASC',
                'price_desc' => 'l.price DESC',
                default      => 'l.created_at DESC',
            };

            $sql = 'SELECT l.id, l.title, l.price, l.condition, l.location, l.created_at,
                           u.username AS seller_name,
                           (SELECT filename FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) AS image
                    FROM listings l
                    JOIN users u ON u.id = l.seller_id
                    WHERE ' . implode(' AND ', $where) .
                   " ORDER BY $orderBy LIMIT $limit OFFSET $offset";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $listings = $stmt->fetchAll();

            jsonResponse(['listings' => $listings, 'page' => $page]);
        }
        break;

    case 'POST':
        requireLogin();
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $title      = trim($data['title'] ?? '');
        $desc       = trim($data['description'] ?? '');
        $price      = (float)($data['price'] ?? 0);
        $categoryId = (int)($data['category_id'] ?? 0);
        $condition  = $data['condition'] ?? 'good';
        $location   = trim($data['location'] ?? '');

        if (!$title || !$desc || $price <= 0 || !$categoryId) {
            jsonResponse(['error' => 'Title, description, price, and category are required.'], 422);
        }

        // Check if user can sell
        $stmt = $db->prepare('SELECT r.can_sell FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?');
        $stmt->execute([currentUserId()]);
        $user = $stmt->fetch();
        if (!$user || !$user['can_sell']) {
            jsonResponse(['error' => 'Your role does not have permission to create listings.'], 403);
        }

        $validConditions = ['new', 'like_new', 'good', 'fair', 'poor'];
        if (!in_array($condition, $validConditions)) { $condition = 'good'; }

        $stmt = $db->prepare('INSERT INTO listings (seller_id, category_id, title, description, price, `condition`, location) VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([currentUserId(), $categoryId, $title, $desc, $price, $condition, $location]);
        $listingId = (int)$db->lastInsertId();

        jsonResponse(['success' => true, 'id' => $listingId], 201);
        break;

    case 'PUT':
        requireLogin();
        $id   = (int)($_GET['id'] ?? 0);
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $stmt = $db->prepare('SELECT seller_id FROM listings WHERE id = ?');
        $stmt->execute([$id]);
        $listing = $stmt->fetch();
        if (!$listing || $listing['seller_id'] !== currentUserId()) {
            jsonResponse(['error' => 'Forbidden.'], 403);
        }

        $fields = [];
        $params = [];
        foreach (['title','description','price','category_id','condition','location','status'] as $field) {
            if (isset($data[$field])) {
                $col      = $field === 'condition' ? '`condition`' : $field;
                $fields[] = "$col = ?";
                $params[] = $data[$field];
            }
        }
        if (!$fields) { jsonResponse(['error' => 'No fields to update.'], 422); }

        $params[] = $id;
        $db->prepare('UPDATE listings SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($params);
        jsonResponse(['success' => true]);
        break;

    case 'DELETE':
        requireLogin();
        $id = (int)($_GET['id'] ?? 0);

        $stmt = $db->prepare('SELECT seller_id FROM listings WHERE id = ?');
        $stmt->execute([$id]);
        $listing = $stmt->fetch();
        if (!$listing || $listing['seller_id'] !== currentUserId()) {
            jsonResponse(['error' => 'Forbidden.'], 403);
        }

        $db->prepare("UPDATE listings SET status = 'removed' WHERE id = ?")->execute([$id]);
        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['error' => 'Method not allowed.'], 405);
}
