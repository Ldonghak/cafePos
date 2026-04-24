<?php
// API: 결제 처리 (게이트웨이 공통)
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/GatewayFactory.php';

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ─── 결제 파라미터 조회 (POS 화면 → 어떤 SDK 사용할지 알아야 함) ───
if ($method === 'GET' && $action === 'params') {
    $orderId   = (int)($_GET['order_id'] ?? 0);
    $amount    = (int)($_GET['amount'] ?? 0);
    $orderName = urldecode($_GET['order_name'] ?? '주문');

    $gw = GatewayFactory::active($pdo);
    if (!$gw) { echo json_encode(['success' => false, 'message' => '활성 게이트웨이 없음']); exit; }

    $params = $gw->getClientParams($orderId, $amount, $orderName);
    echo json_encode(['success' => true, 'data' => $params]);
    exit;
}

// ─── 결제 승인 처리 ───
if ($method === 'POST' && $action === 'confirm') {
    $input   = json_decode(file_get_contents('php://input'), true) ?? [];
    $items   = $input['items']     ?? [];
    $payload = $input['payload']   ?? []; // 게이트웨이별 승인 데이터

    if (empty($items)) { echo json_encode(['success' => false, 'message' => '주문 항목 없음']); exit; }

    $gw = GatewayFactory::active($pdo);
    if (!$gw) { echo json_encode(['success' => false, 'message' => '활성 게이트웨이 없음']); exit; }

    // 게이트웨이 승인
    $result = $gw->confirm($payload);
    if (!$result['success']) { echo json_encode($result); exit; }

    // DB 저장
    $total = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $items));
    $pdo->beginTransaction();
    $pdo->prepare("INSERT INTO orders (total_price, status, card_last4) VALUES (?, '완료', ?)")
        ->execute([$total, $result['card_last4']]);
    $orderId = $pdo->lastInsertId();

    $st = $pdo->prepare("INSERT INTO order_items (order_id, menu_id, menu_name, price, quantity) VALUES (?,?,?,?,?)");
    foreach ($items as $item) {
        $st->execute([$orderId, $item['id'], $item['name'], $item['price'], $item['quantity']]);
    }
    $pdo->commit();

    echo json_encode(['success' => true, 'order_id' => $orderId, 'total_price' => $total,
        'card_last4' => $result['card_last4'], 'message' => '결제 완료']);
    exit;
}

// ─── 활성 게이트웨이 정보 조회 ───
if ($method === 'GET' && $action === 'active') {
    $row = $pdo->query("SELECT code, name, mode FROM payment_gateways WHERE is_active = 1 LIMIT 1")->fetch();
    echo json_encode(['success' => true, 'data' => $row ?: null]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => '잘못된 요청']);
