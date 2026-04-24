<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';
requireLogin('admin');

header('Content-Type: application/json; charset=utf-8');
$pdo = getDB();

// Ensure categories table exists with is_hidden column
$pdo->exec("
    CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        sort_order INT NOT NULL DEFAULT 0,
        is_hidden TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
// Add is_hidden if older table exists without it
try { $pdo->exec("ALTER TABLE categories ADD COLUMN is_hidden TINYINT(1) NOT NULL DEFAULT 0"); } catch(Exception $e) {}

// Seed from existing menus if table is empty
$catCount = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
if ($catCount == 0) {
    $existing = $pdo->query("SELECT DISTINCT category FROM menus ORDER BY FIELD(category,'커피','라떼','에이드','스무디','차','기타')")->fetchAll(PDO::FETCH_COLUMN);
    $sort = 1;
    $ins = $pdo->prepare("INSERT IGNORE INTO categories (name, sort_order) VALUES (?, ?)");
    foreach ($existing as $cat) {
        $ins->execute([$cat, $sort++]);
    }
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    // GET: list all categories
    if ($method === 'GET') {
        $cats = $pdo->query("SELECT * FROM categories ORDER BY sort_order, id")->fetchAll();
        echo json_encode(['success' => true, 'data' => $cats]);
        exit;
    }

    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    // POST: add category
    if ($method === 'POST' && $action === 'add') {
        $name = trim($body['name'] ?? '');
        if (!$name) { echo json_encode(['success'=>false,'message'=>'카테고리명을 입력해주세요']); exit; }
        $maxOrder = $pdo->query("SELECT COALESCE(MAX(sort_order),0) FROM categories")->fetchColumn();
        $stmt = $pdo->prepare("INSERT INTO categories (name, sort_order) VALUES (?, ?)");
        $stmt->execute([$name, $maxOrder + 1]);
        $id = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'id' => $id, 'message' => '카테고리가 추가되었습니다']);
        exit;
    }

    // POST: delete category
    if ($method === 'POST' && $action === 'delete') {
        $id = intval($body['id'] ?? 0);
        if (!$id) { echo json_encode(['success'=>false,'message'=>'잘못된 요청']); exit; }
        $cat = $pdo->prepare("SELECT name FROM categories WHERE id=?");
        $cat->execute([$id]);
        $row = $cat->fetch();
        if (!$row) { echo json_encode(['success'=>false,'message'=>'카테고리를 찾을 수 없습니다']); exit; }
        // Check if any menus use this category
        $inUse = $pdo->prepare("SELECT COUNT(*) FROM menus WHERE category=?");
        $inUse->execute([$row['name']]);
        if ($inUse->fetchColumn() > 0) {
            echo json_encode(['success'=>false,'message'=>"해당 카테고리에 메뉴가 있어 삭제할 수 없습니다. 먼저 메뉴를 다른 카테고리로 이동하거나 삭제해주세요."]);
            exit;
        }
        $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([$id]);
        echo json_encode(['success' => true, 'message' => '카테고리가 삭제되었습니다']);
        exit;
    }

    // POST: reorder categories
    if ($method === 'POST' && $action === 'reorder') {
        $ids = $body['ids'] ?? [];
        if (!is_array($ids) || empty($ids)) { echo json_encode(['success'=>false,'message'=>'잘못된 요청']); exit; }
        $stmt = $pdo->prepare("UPDATE categories SET sort_order=? WHERE id=?");
        foreach ($ids as $order => $id) {
            $stmt->execute([$order + 1, intval($id)]);
        }
        echo json_encode(['success' => true, 'message' => '순서가 저장되었습니다']);
        exit;
    }

    // POST: toggle visibility
    if ($method === 'POST' && $action === 'toggle') {
        $id = intval($body['id'] ?? 0);
        if (!$id) { echo json_encode(['success'=>false,'message'=>'잘못된 요청']); exit; }
        $cur = $pdo->prepare("SELECT is_hidden FROM categories WHERE id=?");
        $cur->execute([$id]);
        $row = $cur->fetch();
        if (!$row) { echo json_encode(['success'=>false,'message'=>'카테고리를 찾을 수 없습니다']); exit; }
        $newVal = $row['is_hidden'] ? 0 : 1;
        $pdo->prepare("UPDATE categories SET is_hidden=? WHERE id=?")->execute([$newVal, $id]);
        $msg = $newVal ? '카테고리가 숨곊졌습니다' : '카테고리가 표시됩니다';
        echo json_encode(['success' => true, 'is_hidden' => $newVal, 'message' => $msg]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => '알 수 없는 요청']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
