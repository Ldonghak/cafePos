<?php
// API: 결제 게이트웨이 설정 관리
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../lib/GatewayFactory.php';
requireLogin('admin');

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // 전체 게이트웨이 목록 (DB + 메타 병합)
    $rows = $pdo->query("SELECT * FROM payment_gateways ORDER BY id")->fetchAll();
    $meta = GatewayFactory::all();
    foreach ($rows as &$r) {
        $r['config']       = json_decode($r['config'] ?? '{}', true) ?? [];
        $r['config_fields'] = $meta[$r['code']]['configFields'] ?? [];
        $r['ui_type']      = $meta[$r['code']]['uiType'] ?? '';
        // 비밀번호 필드는 마스킹
        foreach ($r['config_fields'] as $f) {
            if ($f['type'] === 'password' && !empty($r['config'][$f['key']])) {
                $r['config'][$f['key']] = '••••••••';
            }
        }
    }
    echo json_encode(['success' => true, 'data' => $rows]);
    exit;
}

if ($method === 'POST') {
    $input  = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    if ($action === 'activate') {
        // 단일 활성화
        $code = $input['code'] ?? '';
        $pdo->exec("UPDATE payment_gateways SET is_active = 0");
        $pdo->prepare("UPDATE payment_gateways SET is_active = 1 WHERE code = ?")->execute([$code]);
        echo json_encode(['success' => true, 'message' => '게이트웨이가 변경되었습니다.']);
        exit;
    }

    if ($action === 'save_config') {
        $code   = $input['code'] ?? '';
        $config = $input['config'] ?? [];
        $mode   = in_array($input['mode'] ?? '', ['test','live']) ? $input['mode'] : 'test';

        // 기존 config 읽기 (비밀번호 마스킹 된 필드는 기존 값 유지)
        $existing = $pdo->prepare("SELECT config, mode FROM payment_gateways WHERE code = ?");
        $existing->execute([$code]);
        $row = $existing->fetch();
        $existingConfig = json_decode($row['config'] ?? '{}', true) ?? [];

        // 마스킹된 값(••••)은 기존 값으로 대체
        foreach ($config as $k => $v) {
            if (str_contains((string)$v, '••')) {
                $config[$k] = $existingConfig[$k] ?? '';
            }
        }

        $pdo->prepare("UPDATE payment_gateways SET config = ?, mode = ? WHERE code = ?")
            ->execute([json_encode($config, JSON_UNESCAPED_UNICODE), $mode, $code]);
        echo json_encode(['success' => true, 'message' => '설정이 저장되었습니다.']);
        exit;
    }
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => '잘못된 요청']);
