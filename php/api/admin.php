<?php
require_once __DIR__ . '/../config/database.php';

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Authentication required.'], 401);
}
session_write_close();
$db = getDB();

$stmt = $db->prepare('SELECT r.is_admin FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ? AND u.is_active = 1');
$stmt->execute([currentUserId()]);
$row = $stmt->fetch();
if (!$row || !$row['is_admin']) {
    jsonResponse(['error' => 'Forbidden.'], 403);
}

$method  = $_SERVER['REQUEST_METHOD'];
$section = $_GET['section'] ?? '';
$id      = isset($_GET['id']) ? (int)$_GET['id'] : 0;

switch ($method) {

    case 'GET':
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 30;
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? '');

        if ($section === 'roles') {
            $stmt = $db->prepare(
                "SELECT id, name, description, is_admin, can_buy, can_sell, created_at
                 FROM roles ORDER BY name"
            );
            $stmt->execute();
            jsonResponse(['roles' => $stmt->fetchAll()]);

        } elseif ($section === 'users') {
            $where  = ['1=1'];
            $params = [];
            if ($search) {
                $where[]  = '(u.username LIKE ? OR u.email LIKE ?)';
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            $whereStr = implode(' AND ', $where);
            $stmt = $db->prepare(
                "SELECT u.id, u.username, u.email, u.is_active, u.is_verified, u.rating, u.review_count, u.created_at,
                        r.id as role_id, r.name as role_name, r.is_admin
                 FROM users u
                 JOIN roles r ON u.role_id = r.id
                 WHERE $whereStr ORDER BY u.created_at DESC LIMIT $limit OFFSET $offset"
            );
            $stmt->execute($params);
            $users = $stmt->fetchAll();

            $countStmt = $db->prepare("SELECT COUNT(*) FROM users u WHERE $whereStr");
            $countStmt->execute($params);
            jsonResponse(['users' => $users, 'total' => (int)$countStmt->fetchColumn(), 'page' => $page]);

        } elseif ($section === 'listings') {
            $where  = ['1=1'];
            $params = [];
            if ($search) {
                $where[]  = 'l.title LIKE ?';
                $params[] = "%$search%";
            }
            $statusFilter = $_GET['status'] ?? '';
            if ($statusFilter) {
                $where[]  = 'l.status = ?';
                $params[] = $statusFilter;
            }
            $whereStr = implode(' AND ', $where);
            $stmt = $db->prepare(
                "SELECT l.id, l.title, l.price, l.status, l.condition, l.view_count, l.created_at,
                        u.username AS seller_name, u.id AS seller_id
                 FROM listings l
                 JOIN users u ON u.id = l.seller_id
                 WHERE $whereStr ORDER BY l.created_at DESC LIMIT $limit OFFSET $offset"
            );
            $stmt->execute($params);
            $listings = $stmt->fetchAll();

            $countStmt = $db->prepare(
                "SELECT COUNT(*) FROM listings l JOIN users u ON u.id = l.seller_id WHERE $whereStr"
            );
            $countStmt->execute($params);
            jsonResponse(['listings' => $listings, 'total' => (int)$countStmt->fetchColumn(), 'page' => $page]);

        } else {
            jsonResponse([
                'total_users'     => (int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn(),
                'active_users'    => (int)$db->query('SELECT COUNT(*) FROM users WHERE is_active = 1')->fetchColumn(),
                'total_listings'  => (int)$db->query('SELECT COUNT(*) FROM listings')->fetchColumn(),
                'active_listings' => (int)$db->query("SELECT COUNT(*) FROM listings WHERE status = 'active'")->fetchColumn(),
                'total_orders'    => (int)$db->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
            ]);
        }
        break;

    case 'PATCH':
        if (!$id) { jsonResponse(['error' => 'ID required.'], 422); }
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        if ($section === 'roles') {
            $name        = trim($data['name'] ?? '');
            $description = trim($data['description'] ?? '');
            $is_admin    = (int)($data['is_admin'] ?? 0);
            $can_buy     = (int)($data['can_buy'] ?? 1);
            $can_sell    = (int)($data['can_sell'] ?? 1);

            if (!$name) { jsonResponse(['error' => 'Name is required.'], 422); }

            $stmt = $db->prepare('SELECT name FROM roles WHERE id = ?');
            $stmt->execute([$id]);
            $role = $stmt->fetch();
            if (!$role) { jsonResponse(['error' => 'Role not found.'], 404); }
            if (in_array($role['name'], ['user', 'admin'])) {
                jsonResponse(['error' => 'Cannot modify built-in roles.'], 403);
            }

            $db->prepare('UPDATE roles SET name = ?, description = ?, is_admin = ?, can_buy = ?, can_sell = ? WHERE id = ?')
                ->execute([$name, $description, $is_admin, $can_buy, $can_sell, $id]);
            jsonResponse(['success' => true]);

        } elseif ($section === 'users') {
            if ($id === currentUserId()) {
                jsonResponse(['error' => 'Cannot modify your own account.'], 403);
            }
            $action = $data['action'] ?? '';
            $action_map = [
                'ban'      => ['UPDATE users SET is_active = 0 WHERE id = ?', [$id]],
                'unban'    => ['UPDATE users SET is_active = 1 WHERE id = ?', [$id]],
                'verify'   => ['UPDATE users SET is_verified = 1 WHERE id = ?', [$id]],
                'unverify' => ['UPDATE users SET is_verified = 0 WHERE id = ?', [$id]],
                'set_role' => ['UPDATE users SET role_id = ? WHERE id = ?', [(int)($data['role_id'] ?? 1), $id]],
            ];

            if (!isset($action_map[$action])) { jsonResponse(['error' => 'Unknown action.'], 422); }

            [$sql, $params] = $action_map[$action];
            $db->prepare($sql)->execute($params);
            jsonResponse(['success' => true]);

        } elseif ($section === 'listings') {
            $status  = $data['status'] ?? '';
            $allowed = ['active', 'sold', 'reserved', 'removed'];
            if (!in_array($status, $allowed)) { jsonResponse(['error' => 'Invalid status.'], 422); }
            $db->prepare('UPDATE listings SET status = ? WHERE id = ?')->execute([$status, $id]);
            jsonResponse(['success' => true]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        if ($section === 'roles') {
            $name        = trim($data['name'] ?? '');
            $description = trim($data['description'] ?? '');
            $is_admin    = (int)($data['is_admin'] ?? 0);
            $can_buy     = (int)($data['can_buy'] ?? 1);
            $can_sell    = (int)($data['can_sell'] ?? 1);

            if (!$name) { jsonResponse(['error' => 'Name is required.'], 422); }

            $stmt = $db->prepare('SELECT id FROM roles WHERE name = ?');
            $stmt->execute([$name]);
            if ($stmt->fetch()) { jsonResponse(['error' => 'Role already exists.'], 422); }

            $db->prepare('INSERT INTO roles (name, description, is_admin, can_buy, can_sell) VALUES (?,?,?,?,?)')
                ->execute([$name, $description, $is_admin, $can_buy, $can_sell]);
            $roleId = (int)$db->lastInsertId();
            jsonResponse(['success' => true, 'id' => $roleId], 201);
        }
        break;

    case 'DELETE':
        if (!$id) { jsonResponse(['error' => 'ID required.'], 422); }

        if ($section === 'roles') {
            $stmt = $db->prepare('SELECT name FROM roles WHERE id = ?');
            $stmt->execute([$id]);
            $role = $stmt->fetch();
            if (!$role) { jsonResponse(['error' => 'Role not found.'], 404); }
            if (in_array($role['name'], ['user', 'admin'])) {
                jsonResponse(['error' => 'Cannot delete built-in roles.'], 403);
            }

            $stmt = $db->prepare('SELECT COUNT(*) FROM users WHERE role_id = ?');
            $stmt->execute([$id]);
            if ((int)$stmt->fetchColumn() > 0) {
                jsonResponse(['error' => 'Role is in use by users. Reassign users before deletion.'], 422);
            }

            $db->prepare('DELETE FROM roles WHERE id = ?')->execute([$id]);
            jsonResponse(['success' => true]);
        }
        break;

    default:
        jsonResponse(['error' => 'Method not allowed.'], 405);
}
