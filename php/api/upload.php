<?php
require_once __DIR__ . '/../config/database.php';

requireLogin();
session_write_close();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed.'], 405);
}

$listingId = (int)($_POST['listing_id'] ?? 0);
if (!$listingId) { jsonResponse(['error' => 'listing_id is required.'], 422); }

// Verify ownership
$db   = getDB();
$stmt = $db->prepare('SELECT seller_id FROM listings WHERE id = ?');
$stmt->execute([$listingId]);
$listing = $stmt->fetch();
if (!$listing || $listing['seller_id'] !== currentUserId()) {
    jsonResponse(['error' => 'Forbidden.'], 403);
}

// Count existing images
$countStmt = $db->prepare('SELECT COUNT(*) FROM listing_images WHERE listing_id = ?');
$countStmt->execute([$listingId]);
$existing = (int)$countStmt->fetchColumn();

if (!isset($_FILES['images'])) {
    jsonResponse(['error' => 'No files uploaded.'], 422);
}

$files     = $_FILES['images'];
$uploaded  = [];
$allowed   = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

// Normalize single-file upload
if (!is_array($files['name'])) {
    $files = array_map(fn($v) => [$v], $files);
}

$count = count($files['name']);
if ($existing + $count > MAX_IMAGES_PER_LISTING) {
    jsonResponse(['error' => 'Maximum ' . MAX_IMAGES_PER_LISTING . ' images per listing.'], 422);
}

for ($i = 0; $i < $count; $i++) {
    if ($files['error'][$i] !== UPLOAD_ERR_OK) { continue; }
    if ($files['size'][$i] > MAX_FILE_SIZE) { continue; }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $files['tmp_name'][$i]);
    finfo_close($finfo);
    if (!in_array($mime, $allowed)) { continue; }

    $ext      = match($mime) { 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif', default => 'jpg' };
    $filename = uniqid('img_', true) . '.' . $ext;
    $dest     = UPLOAD_DIR . $filename;

    if (move_uploaded_file($files['tmp_name'][$i], $dest)) {
        $isPrimary = ($existing === 0 && $i === 0) ? 1 : 0;
        $db->prepare('INSERT INTO listing_images (listing_id, filename, is_primary, sort_order) VALUES (?,?,?,?)')
           ->execute([$listingId, $filename, $isPrimary, $existing + $i]);
        $uploaded[] = $filename;
    }
}

jsonResponse(['success' => true, 'uploaded' => $uploaded]);
