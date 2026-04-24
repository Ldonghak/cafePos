<?php
// API: 메뉴 목록 조회 / 추가 / 삭제
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../db.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDB();

    if ($method === 'GET') {
        // 메뉴 전체 조회
        $stmt = $pdo->query("SELECT * FROM menus ORDER BY category, id");
        $menus = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $menus]);

    } elseif ($method === 'POST') {
        // 메뉴 추가
        $input = json_decode(file_get_contents('php://input'), true);
        $name        = trim($input['name']        ?? '');
        $price       = (int)($input['price']      ?? 0);
        $category    = trim($input['category']    ?? '기타');
        $description = trim($input['description'] ?? '');

        if (!$name || $price <= 0) {
            echo json_encode(['success' => false, 'message' => '이름과 가격을 올바르게 입력해주세요.']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO menus (name, price, category, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $price, $category, $description ?: null]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId(), 'message' => '메뉴가 등록되었습니다.']);

    } elseif ($method === 'DELETE') {
        // 메뉴 삭제
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => '유효하지 않은 ID입니다.']);
            exit;
        }
        $stmt = $pdo->prepare("DELETE FROM menus WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => '메뉴가 삭제되었습니다.']);

    } elseif ($method === 'PATCH') {
        // 메뉴 숨김/표시 토글
        $id = (int)($_GET['id'] ?? 0);
        $action = $_GET['action'] ?? '';
        if (!$id || $action !== 'toggle') {
            echo json_encode(['success' => false, 'message' => '유효하지 않은 요청입니다.']);
            exit;
        }
        $row = $pdo->prepare("SELECT is_available FROM menus WHERE id = ?")->execute([$id]);
        $current = $pdo->prepare("SELECT is_available FROM menus WHERE id = ?");
        $current->execute([$id]);
        $menu = $current->fetch();
        $newVal = $menu['is_available'] ? 0 : 1;
        $pdo->prepare("UPDATE menus SET is_available = ? WHERE id = ?")->execute([$newVal, $id]);
        echo json_encode(['success' => true, 'is_available' => $newVal]);

    } elseif ($method === 'PUT') {
        // 메뉴 수정 (이름/가격/카테고리/설명)
        $id    = (int)($_GET['id'] ?? 0);
        $input = json_decode(file_get_contents('php://input'), true);
        $name        = trim($input['name']        ?? '');
        $price       = (int)($input['price']      ?? 0);
        $category    = trim($input['category']    ?? '기타');
        $description = trim($input['description'] ?? '');

        if (!$id || !$name || $price <= 0) {
            echo json_encode(['success' => false, 'message' => '올바른 값을 입력해주세요.']);
            exit;
        }
        $pdo->prepare("UPDATE menus SET name=?, price=?, category=?, description=? WHERE id=?")
            ->execute([$name, $price, $category, $description ?: null, $id]);
        echo json_encode(['success' => true, 'message' => '메뉴가 수정되었습니다.']);

    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => '허용되지 않은 메서드입니다.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
