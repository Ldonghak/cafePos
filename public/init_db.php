<?php
require_once __DIR__ . '/db.php';

try {
    $pdo = getDB();

    // 메뉴 테이블 생성
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS menus (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            price INT NOT NULL DEFAULT 0,
            category VARCHAR(50) DEFAULT '음료',
            is_available TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 주문 테이블 생성
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            total_price INT NOT NULL DEFAULT 0,
            status VARCHAR(20) DEFAULT '대기',
            card_last4 VARCHAR(4) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 주문 상세 테이블 생성
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            menu_id INT NOT NULL,
            menu_name VARCHAR(100) NOT NULL,
            price INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 사용자 테이블 생성
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(60) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            name VARCHAR(100) NOT NULL DEFAULT '',
            role ENUM('super_admin','admin','user') NOT NULL DEFAULT 'user',
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 기본 메뉴가 없는 경우에만 삽입
    $count = $pdo->query("SELECT COUNT(*) FROM menus")->fetchColumn();
    if ($count == 0) {
        $stmt = $pdo->prepare("INSERT INTO menus (name, price, category) VALUES (?, ?, ?)");
        $defaultMenus = [
            ['아메리카노', 3000, '커피'],
            ['라떼', 4000, '커피'],
            ['딸기스무디', 5500, '스무디'],
            ['녹차', 3500, '차'],
        ];
        foreach ($defaultMenus as $menu) {
            $stmt->execute($menu);
        }
        echo "✅ 기본 메뉴 4개 등록 완료<br>";
    } else {
        echo "ℹ️ 기존 메뉴 데이터가 존재합니다. ($count 개)<br>";
    }

    // 슈퍼관리자 계정 생성 (없을 경우에만)
    $uCount = $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'nurii'")->fetchColumn();
    if ($uCount == 0) {
        $hashed = password_hash('nurii_world', PASSWORD_BCRYPT);
        $pdo->prepare("INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, 'super_admin')")
            ->execute(['nurii', $hashed, '메인관리자']);
        echo "✅ 슈퍼관리자 계정(nurii) 생성 완료<br>";
    } else {
        echo "ℹ️ 슈퍼관리자 계정이 이미 존재합니다.<br>";
    }

    echo "✅ 데이터베이스 초기화 완료!<br>";
    echo '<a href="/index.php">👉 POS 화면으로 이동</a>';

} catch (PDOException $e) {
    http_response_code(500);
    echo "❌ 오류: " . htmlspecialchars($e->getMessage());
}
