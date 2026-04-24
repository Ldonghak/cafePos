<?php
session_start();
require_once __DIR__ . '/db.php';

// 이미 로그인 된 경우
if (!empty($_SESSION['user'])) {
    header('Location: /admin.php');
    exit;
}

$error = '';
$redirect = $_GET['redirect'] ?? '/admin.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        try {
            $pdo  = getDB();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // 로그인 성공
                $_SESSION['user'] = [
                    'id'       => $user['id'],
                    'username' => $user['username'],
                    'name'     => $user['name'],
                    'role'     => $user['role'],
                ];
                $dest = (strpos($redirect, '/') === 0) ? $redirect : '/admin.php';
                header('Location: ' . $dest);
                exit;
            } else {
                $error = '아이디 또는 비밀번호가 올바르지 않습니다.';
            }
        } catch (Exception $e) {
            $error = 'DB 오류: ' . $e->getMessage();
        }
    } else {
        $error = '아이디와 비밀번호를 입력해주세요.';
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>로그인 | CafePOS 관리자</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;700;900&display=swap');
  :root {
    --bg: #0f0f13;
    --surface: #1a1a22;
    --surface2: #242430;
    --accent: #6c63ff;
    --accent-glow: rgba(108,99,255,.35);
    --red: #ff4d6d;
    --text: #f0f0f8;
    --muted: #8888aa;
    --border: rgba(255,255,255,0.08);
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'Noto Sans KR', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
  }

  /* 배경 glow orbs */
  body::before, body::after {
    content: '';
    position: fixed;
    border-radius: 50%;
    filter: blur(80px);
    pointer-events: none;
    z-index: 0;
  }
  body::before {
    width: 500px; height: 500px;
    background: rgba(108,99,255,.12);
    top: -100px; left: -100px;
  }
  body::after {
    width: 400px; height: 400px;
    background: rgba(255,101,132,.08);
    bottom: -80px; right: -80px;
  }

  .login-wrap {
    position: relative; z-index: 1;
    width: 100%; max-width: 420px;
  }

  .logo-area {
    text-align: center;
    margin-bottom: 36px;
  }
  .logo-icon {
    font-size: 2.8rem;
    margin-bottom: 10px;
    display: block;
  }
  .logo-text {
    font-size: 1.6rem; font-weight: 900; letter-spacing: -0.5px;
  }
  .logo-text span { color: var(--accent); }
  .logo-sub {
    font-size: 0.82rem; color: var(--muted); margin-top: 5px;
  }

  .card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 24px;
    padding: 36px 32px;
    box-shadow: 0 20px 60px rgba(0,0,0,.4);
  }

  .card-title {
    font-size: 1.05rem; font-weight: 800;
    margin-bottom: 24px; text-align: center;
    color: var(--muted);
    letter-spacing: .5px; text-transform: uppercase; font-size: .78rem;
  }

  .error-box {
    background: rgba(255,77,109,.1);
    border: 1px solid rgba(255,77,109,.3);
    color: var(--red);
    border-radius: 10px;
    padding: 12px 14px;
    font-size: .85rem;
    margin-bottom: 20px;
    display: flex; align-items: center; gap: 8px;
  }

  .form-group { margin-bottom: 16px; }
  label {
    display: block; font-size: .75rem; font-weight: 700;
    color: var(--muted); margin-bottom: 8px; letter-spacing: .5px;
    text-transform: uppercase;
  }
  input[type="text"], input[type="password"] {
    width: 100%; padding: 13px 16px;
    background: var(--surface2);
    border: 1px solid var(--border);
    border-radius: 12px;
    color: var(--text);
    font-size: .95rem;
    font-family: 'Noto Sans KR', sans-serif;
    outline: none;
    transition: border-color .2s, box-shadow .2s;
  }
  input:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px var(--accent-glow);
  }

  .login-btn {
    width: 100%; padding: 15px;
    background: linear-gradient(135deg, var(--accent), #8b80ff);
    border: none; border-radius: 14px;
    color: white; font-size: 1rem; font-weight: 700;
    cursor: pointer; margin-top: 8px;
    font-family: 'Noto Sans KR', sans-serif;
    transition: all .2s;
    letter-spacing: -.3px;
  }
  .login-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 28px var(--accent-glow);
  }
  .login-btn:active { transform: translateY(0); }

  .back-link {
    display: block; text-align: center; margin-top: 20px;
    font-size: .82rem; color: var(--muted); text-decoration: none;
    transition: color .2s;
  }
  .back-link:hover { color: var(--accent); }
</style>
</head>
<body>
<div class="login-wrap">
  <div class="logo-area">
    <span class="logo-icon">☕</span>
    <div class="logo-text"><span>Cafe</span>POS</div>
    <div class="logo-sub">관리자 시스템</div>
  </div>

  <div class="card">
    <div class="card-title">관리자 로그인</div>

    <?php if ($error): ?>
    <div class="error-box">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="/login.php?redirect=<?= urlencode($redirect) ?>">
      <div class="form-group">
        <label>아이디</label>
        <input type="text" name="username" id="username"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
               placeholder="아이디 입력" autofocus autocomplete="username">
      </div>
      <div class="form-group">
        <label>비밀번호</label>
        <input type="password" name="password" id="password"
               placeholder="비밀번호 입력" autocomplete="current-password">
      </div>
      <button type="submit" class="login-btn">로그인</button>
    </form>
  </div>

  <a href="/index.php" class="back-link">← POS 화면으로 돌아가기</a>
</div>
</body>
</html>
