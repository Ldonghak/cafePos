<?php
// API: 사용자 목록 조회 / 추가 / 삭제 / 상태 변경
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

// 반드시 admin 이상 권한 필요
requireLogin('admin');

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDB();

    if ($method === 'GET') {
        // 전체 사용자 목록 (비밀번호 제외)
        $users = $pdo->query("SELECT id, username, name, role, is_active, created_at FROM users ORDER BY id")->fetchAll();
        echo json_encode(['success' => true, 'data' => $users]);

    } elseif ($method === 'POST') {
        // 사용자 추가 — super_admin만 super_admin 등록 가능
        $input    = json_decode(file_get_contents('php://input'), true);
        $username = trim($input['username'] ?? '');
        $password = $input['password'] ?? '';
        $name     = trim($input['name'] ?? '');
        $role     = $input['role'] ?? 'user';

        $allowedRoles = ['user', 'admin', 'super_admin'];
        if (!in_array($role, $allowedRoles)) $role = 'user';

        // 일반 admin은 super_admin 생성 불가
        if ($role === 'super_admin' && !isSuperAdmin()) {
            echo json_encode(['success' => false, 'message' => '슈퍼관리자 계정은 메인관리자만 생성할 수 있습니다.']);
            exit;
        }

        if (!$username || strlen($password) < 4) {
            echo json_encode(['success' => false, 'message' => '아이디와 비밀번호(4자 이상)를 입력해주세요.']);
            exit;
        }

        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt   = $pdo->prepare("INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $hashed, $name, $role]);

        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId(), 'message' => '사용자가 등록되었습니다.']);

    } elseif ($method === 'DELETE') {
        // 사용자 삭제
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'message' => '유효하지 않은 ID입니다.']); exit; }

        // 자기 자신 삭제 불가
        if ($id === (int)(currentUser()['id'] ?? 0)) {
            echo json_encode(['success' => false, 'message' => '본인 계정은 삭제할 수 없습니다.']);
            exit;
        }

        // super_admin 삭제는 super_admin만 가능
        $target = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $target->execute([$id]);
        $t = $target->fetch();
        if (!$t) { echo json_encode(['success' => false, 'message' => '사용자를 찾을 수 없습니다.']); exit; }
        if ($t['role'] === 'super_admin' && !isSuperAdmin()) {
            echo json_encode(['success' => false, 'message' => '슈퍼관리자 계정은 메인관리자만 삭제할 수 있습니다.']);
            exit;
        }

        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true, 'message' => '사용자가 삭제되었습니다.']);

    } elseif ($method === 'PATCH') {
        // 계정 활성/비활성 토글 또는 비밀번호 변경
        $id     = (int)($_GET['id'] ?? 0);
        $action = $_GET['action'] ?? '';

        if (!$id) { echo json_encode(['success' => false, 'message' => '유효하지 않은 ID']); exit; }

        if ($action === 'toggle') {
            // 자기 자신 비활성화 불가
            if ($id === (int)(currentUser()['id'] ?? 0)) {
                echo json_encode(['success' => false, 'message' => '본인 계정은 비활성화할 수 없습니다.']); exit;
            }
            $cur = $pdo->prepare("SELECT is_active, role FROM users WHERE id = ?");
            $cur->execute([$id]);
            $u = $cur->fetch();
            if (!$u) { echo json_encode(['success' => false, 'message' => '없는 사용자']); exit; }
            if ($u['role'] === 'super_admin' && !isSuperAdmin()) {
                echo json_encode(['success' => false, 'message' => '권한 없음']); exit;
            }
            $newVal = $u['is_active'] ? 0 : 1;
            $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?")->execute([$newVal, $id]);
            echo json_encode(['success' => true, 'is_active' => $newVal]);

        } elseif ($action === 'password') {
            $input   = json_decode(file_get_contents('php://input'), true);
            $newPass = $input['password'] ?? '';
            if (strlen($newPass) < 4) {
                echo json_encode(['success' => false, 'message' => '비밀번호는 4자 이상이어야 합니다.']); exit;
            }
            $hashed = password_hash($newPass, PASSWORD_BCRYPT);
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed, $id]);
            echo json_encode(['success' => true, 'message' => '비밀번호가 변경되었습니다.']);
        } else {
            echo json_encode(['success' => false, 'message' => '알 수 없는 action']);
        }

    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => '허용되지 않은 메서드']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    $msg = $e->getMessage();
    if ($e->getCode() == 23000 || strpos($msg, 'Duplicate entry') !== false || strpos($msg, '1062') !== false) {
        $msg = '이미 사용 중인 아이디입니다. 다른 아이디를 입력해주세요.';
    }
    echo json_encode(['success' => false, 'message' => $msg]);
}
