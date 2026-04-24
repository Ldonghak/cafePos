<?php
// API: 주문 생성
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDB();

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $items     = $input['items'] ?? [];       // [{id, name, price, quantity}]
        $cardLast4 = $input['card_last4'] ?? null; // 카드번호 마지막 4자리
        $totalPrice = 0;

        if (empty($items)) {
            echo json_encode(['success' => false, 'message' => '주문 항목이 없습니다.']);
            exit;
        }

        foreach ($items as $item) {
            $totalPrice += (int)($item['price']) * (int)($item['quantity']);
        }

        $pdo->beginTransaction();

        // 주문 생성
        $stmt = $pdo->prepare("INSERT INTO orders (total_price, status, card_last4) VALUES (?, '완료', ?)");
        $stmt->execute([$totalPrice, $cardLast4]);
        $orderId = $pdo->lastInsertId();

        // 주문 상세 삽입
        $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, menu_id, menu_name, price, quantity) VALUES (?, ?, ?, ?, ?)");
        foreach ($items as $item) {
            $stmtItem->execute([
                $orderId,
                (int)$item['id'],
                $item['name'],
                (int)$item['price'],
                (int)$item['quantity'],
            ]);
        }

        $pdo->commit();

        echo json_encode([
            'success'     => true,
            'order_id'    => $orderId,
            'total_price' => $totalPrice,
            'message'     => '결제가 완료되었습니다.',
        ]);

    } elseif ($method === 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true);
        $orderId = $input['id'] ?? null;
        if (!$orderId) { echo json_encode(['success'=>false, 'message'=>'주문 ID가 필요합니다.']); exit; }

        $pdo->beginTransaction();
        
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $user = currentUser();
        $username = $user ? $user['username'] : 'system';
        
        if (isset($input['status'])) {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$input['status'], $orderId]);
            
            $logStmt = $pdo->prepare("INSERT INTO order_logs (order_id, action, details, ip_address, username) VALUES (?, '상태변경', ?, ?, ?)");
            $logStmt->execute([$orderId, "상태: " . $input['status'], $ip, $username]);
        }
        
        if (isset($input['items'])) {
            $totalPrice = 0;
            $pdo->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$orderId]);
            $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, menu_id, menu_name, price, quantity) VALUES (?, ?, ?, ?, ?)");
            foreach ($input['items'] as $item) {
                if ((int)$item['quantity'] <= 0) continue;
                $totalPrice += (int)$item['price'] * (int)$item['quantity'];
                $stmtItem->execute([
                    $orderId,
                    (int)($item['menu_id'] ?? $item['id']),
                    $item['menu_name'] ?? $item['name'],
                    (int)$item['price'],
                    (int)$item['quantity'],
                ]);
            }
            $pdo->prepare("UPDATE orders SET total_price = ? WHERE id = ?")->execute([$totalPrice, $orderId]);
            
            $logStmt = $pdo->prepare("INSERT INTO order_logs (order_id, action, details, ip_address, username) VALUES (?, '내역수정', ?, ?, ?)");
            $logStmt->execute([$orderId, "총 결제액: " . $totalPrice . "원", $ip, $username]);
        }
        $pdo->commit();
        echo json_encode(['success' => true]);

    } elseif ($method === 'GET') {
        if (isset($_GET['action']) && $_GET['action'] === 'logs' && isset($_GET['id'])) {
            $orderId = (int)$_GET['id'];
            $logs = $pdo->prepare("SELECT * FROM order_logs WHERE order_id=? ORDER BY created_at DESC");
            $logs->execute([$orderId]);
            echo json_encode(['success'=>true, 'data'=>$logs->fetchAll(PDO::FETCH_ASSOC)]);
            exit;
        }
        
        if (isset($_GET['id'])) {
            $orderId = (int)$_GET['id'];
            $order = $pdo->prepare("SELECT * FROM orders WHERE id=?");
            $order->execute([$orderId]);
            $o = $order->fetch(PDO::FETCH_ASSOC);
            if ($o) {
                $items = $pdo->prepare("SELECT * FROM order_items WHERE order_id=?");
                $items->execute([$orderId]);
                $o['items'] = $items->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success'=>true, 'data'=>$o]);
            } else {
                echo json_encode(['success'=>false, 'message'=>'주문을 찾을 수 없습니다.']);
            }
        } else {
            $orders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $orders]);
        }
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => '허용되지 않은 메서드입니다.']);
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
