<?php
// 공통 인증 헬퍼 — 세션 기반
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * 로그인 여부 확인. 미로그인 시 login.php로 리디렉션.
 * $minRole: 'user' | 'admin' | 'super_admin'
 */
function requireLogin(string $minRole = 'admin'): void {
    if (empty($_SESSION['user'])) {
        header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }

    $roleLevel = ['user' => 1, 'admin' => 2, 'super_admin' => 3];
    $userLevel = $roleLevel[$_SESSION['user']['role']] ?? 0;
    $needLevel = $roleLevel[$minRole] ?? 2;

    if ($userLevel < $needLevel) {
        http_response_code(403);
        echo '접근 권한이 없습니다.';
        exit;
    }
}

function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

function isSuperAdmin(): bool {
    return ($_SESSION['user']['role'] ?? '') === 'super_admin';
}

function isAdmin(): bool {
    $role = $_SESSION['user']['role'] ?? '';
    return in_array($role, ['admin', 'super_admin']);
}
