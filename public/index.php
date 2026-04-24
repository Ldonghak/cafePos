<?php
require_once __DIR__ . '/db.php';
$menus = [];
try {
    $pdo = getDB();
    $menus = $pdo->query("SELECT * FROM menus WHERE is_available = 1 ORDER BY category, id")->fetchAll();
} catch (Exception $e) {
    // DB 미초기화 상태라면 빈 배열
}

// 카테고리별 그룹화
$grouped = [];
foreach ($menus as $m) {
    $grouped[$m['category']][] = $m;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>카페 POS 결제 시스템</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;700;900&display=swap');

  :root {
    --bg: #0f0f13;
    --surface: #1a1a22;
    --surface2: #242430;
    --accent: #6c63ff;
    --accent2: #ff6584;
    --green: #00d084;
    --amber: #ffb347;
    --text: #f0f0f8;
    --muted: #8888aa;
    --border: rgba(255,255,255,0.07);
    --radius: 16px;
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'Noto Sans KR', sans-serif;
    background: var(--bg);
    color: var(--text);
    height: 100vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }

  /* ─── HEADER ─── */
  header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 24px;
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    flex-shrink: 0;
  }
  header .logo { font-size: 1.3rem; font-weight: 900; letter-spacing: -0.5px; }
  header .logo span { color: var(--accent); }
  header .time { font-size: 0.85rem; color: var(--muted); }
  header .admin-btn {
    padding: 7px 16px; background: var(--surface2);
    border: 1px solid var(--border); border-radius: 8px;
    color: var(--muted); font-size: 0.8rem; cursor: pointer;
    text-decoration: none; transition: all .2s;
  }
  header .admin-btn:hover { border-color: var(--accent); color: var(--accent); }

  /* ─── LAYOUT ─── */
  .layout {
    display: flex;
    flex: 1;
    overflow: hidden;
  }

  /* ─── MENU PANEL (left) ─── */
  .menu-panel {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    scrollbar-width: thin;
    scrollbar-color: var(--surface2) transparent;
  }
  .category-label {
    font-size: 0.72rem; font-weight: 700; letter-spacing: 2px;
    color: var(--muted); text-transform: uppercase;
    margin: 16px 0 10px;
  }
  .category-label:first-child { margin-top: 0; }
  .menu-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 12px;
    margin-bottom: 8px;
  }
  .menu-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 22px 16px;
    cursor: pointer;
    transition: all .2s cubic-bezier(.4,0,.2,1);
    display: flex;
    flex-direction: column;
    gap: 8px;
    position: relative;
    overflow: hidden;
  }
  .menu-card::before {
    content: '';
    position: absolute; inset: 0;
    background: linear-gradient(135deg, var(--accent) 0%, transparent 100%);
    opacity: 0;
    transition: opacity .2s;
  }
  .menu-card:hover { transform: translateY(-3px); border-color: var(--accent); box-shadow: 0 8px 30px rgba(108,99,255,.25); }
  .menu-card:hover::before { opacity: 0.06; }
  .menu-card:active { transform: translateY(0); }
  .menu-card .emoji { font-size: 2rem; }
  .menu-card .name { font-size: 0.95rem; font-weight: 700; }
  .menu-card .price { font-size: 0.85rem; color: var(--accent); font-weight: 700; }
  .menu-card .desc {
    font-size: 0.72rem; color: var(--muted); line-height: 1.4;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
    overflow: hidden;
  }

  /* ─── CART PANEL (right) ─── */
  .cart-panel {
    width: 340px;
    background: var(--surface);
    border-left: 1px solid var(--border);
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
  }
  .cart-header {
    padding: 18px 20px 14px;
    border-bottom: 1px solid var(--border);
    font-weight: 700; font-size: 1rem;
    display: flex; align-items: center; justify-content: space-between;
  }
  .cart-clear {
    font-size: 0.75rem; color: var(--muted); cursor: pointer;
    padding: 4px 10px; border: 1px solid var(--border);
    border-radius: 6px; background: none; color: var(--muted);
    transition: all .2s;
  }
  .cart-clear:hover { border-color: var(--accent2); color: var(--accent2); }

  .cart-items {
    flex: 1;
    overflow-y: auto;
    padding: 12px 16px;
    scrollbar-width: thin;
    scrollbar-color: var(--surface2) transparent;
  }
  .cart-empty {
    text-align: center; color: var(--muted);
    padding: 40px 0; font-size: 0.9rem;
  }
  .cart-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 0;
    border-bottom: 1px solid var(--border);
    animation: slideIn .2s ease;
  }
  @keyframes slideIn {
    from { opacity: 0; transform: translateX(10px); }
    to { opacity: 1; transform: translateX(0); }
  }
  .cart-item .ci-name { flex: 1; font-size: 0.88rem; font-weight: 500; }
  .cart-item .ci-qty {
    display: flex; align-items: center; gap: 6px;
  }
  .qty-btn {
    width: 26px; height: 26px; border-radius: 6px;
    background: var(--surface2); border: 1px solid var(--border);
    color: var(--text); cursor: pointer; font-size: 0.95rem; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    transition: all .15s;
  }
  .qty-btn:hover { background: var(--accent); border-color: var(--accent); }
  .qty-num { font-size: 0.88rem; font-weight: 700; min-width: 20px; text-align: center; }
  .ci-price { font-size: 0.85rem; color: var(--accent); font-weight: 700; white-space: nowrap; }

  .cart-footer {
    padding: 16px 20px;
    border-top: 1px solid var(--border);
  }
  .total-row {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 14px;
  }
  .total-label { font-size: 0.85rem; color: var(--muted); }
  .total-amount { font-size: 1.6rem; font-weight: 900; letter-spacing: -1px; }

  .pay-btn {
    width: 100%; padding: 16px;
    background: linear-gradient(135deg, var(--accent), #8b80ff);
    border: none; border-radius: var(--radius);
    color: white; font-size: 1.05rem; font-weight: 700;
    cursor: pointer; transition: all .2s;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    font-family: 'Noto Sans KR', sans-serif;
  }
  .pay-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(108,99,255,.4); }
  .pay-btn:active { transform: translateY(0); }
  .pay-btn:disabled { background: var(--surface2); color: var(--muted); cursor: not-allowed; transform: none; box-shadow: none; }

  /* ─── MODAL ─── */
  .modal-overlay {
    display: none;
    position: fixed; inset: 0;
    background: rgba(0,0,0,.75);
    z-index: 100;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(6px);
  }
  .modal-overlay.show { display: flex; }
  .modal {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 24px;
    padding: 40px;
    max-width: 420px;
    width: 90%;
    text-align: center;
    animation: popIn .25s cubic-bezier(.4,0,.2,1);
  }
  @keyframes popIn {
    from { opacity: 0; transform: scale(.9); }
    to { opacity: 1; transform: scale(1); }
  }
  .modal .modal-icon { font-size: 3rem; margin-bottom: 16px; }
  .modal h2 { font-size: 1.4rem; font-weight: 800; margin-bottom: 8px; }
  .modal .modal-sub { font-size: 0.9rem; color: var(--muted); margin-bottom: 24px; }
  .modal .modal-total {
    font-size: 2.2rem; font-weight: 900;
    color: var(--accent); margin-bottom: 28px;
  }

  /* 카드 리더기 입력 숨김 필드 */
  #cardInputHidden {
    position: absolute; left: -9999px; opacity: 0;
    width: 1px; height: 1px;
  }

  .card-waiting {
    display: flex; align-items: center; justify-content: center; gap: 10px;
    color: var(--muted); font-size: 0.9rem; margin-bottom: 20px;
  }
  .pulse-dot {
    width: 10px; height: 10px; border-radius: 50%;
    background: var(--green); animation: pulse 1.2s infinite;
  }
  @keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: .4; transform: scale(.8); }
  }

  .modal-actions { display: flex; gap: 10px; justify-content: center; }
  .modal-cancel {
    padding: 12px 24px; background: var(--surface2);
    border: 1px solid var(--border); border-radius: 12px;
    color: var(--muted); cursor: pointer; font-size: 0.9rem;
    font-family: 'Noto Sans KR', sans-serif; transition: all .2s;
  }
  .modal-cancel:hover { border-color: var(--accent2); color: var(--accent2); }

  /* 결제 완료 모달 */
  .success-circle {
    width: 80px; height: 80px; border-radius: 50%;
    background: linear-gradient(135deg, var(--green), #00b894);
    display: flex; align-items: center; justify-content: center;
    font-size: 2rem; margin: 0 auto 20px;
    animation: successPop .4s cubic-bezier(.4,0,.2,1);
  }
  @keyframes successPop {
    0% { transform: scale(0); }
    70% { transform: scale(1.1); }
    100% { transform: scale(1); }
  }

  /* DB 초기화 안내 */
  .db-notice {
    margin: 40px auto; text-align: center; color: var(--muted);
  }
  .db-notice a {
    display: inline-block; margin-top: 12px;
    padding: 10px 20px; background: var(--accent);
    color: white; border-radius: 10px; text-decoration: none;
    font-weight: 700; transition: all .2s;
  }
  .db-notice a:hover { opacity: .85; }
</style>
</head>
<body>

<header>
  <div class="logo">☕ <span>Cafe</span>POS</div>
  <div class="time" id="clock"></div>
  <div style="display:flex;gap:8px">
    <a href="/promo2.php" class="admin-btn" target="_blank">🌱 청춘 스토리</a>
    <a href="/promo.php" class="admin-btn" target="_blank">📖 브랜드 스토리</a>
    <a href="/menu.php" class="admin-btn" target="_blank">🪧 메뉴판</a>
    <a href="/signage.php" class="admin-btn" target="_blank">📺 메뉴판 소개</a>
    <a href="/admin.php" class="admin-btn">⚙ 관리자</a>
  </div>
</header>

<div class="layout">

  <!-- 메뉴 영역 -->
  <div class="menu-panel">
    <?php if (empty($menus)): ?>
      <div class="db-notice">
        <p>메뉴 데이터가 없습니다.<br>DB를 먼저 초기화해주세요.</p>
        <a href="/init_db.php">🗄 DB 초기화 및 기본 메뉴 등록</a>
      </div>
    <?php else: ?>
      <?php
      $emojiMap = ['커피' => '☕', '스무디' => '🍓', '차' => '🍵', '기타' => '🥤'];
      foreach ($grouped as $cat => $items):
        $emoji = $emojiMap[$cat] ?? '🍹';
      ?>
        <div class="category-label"><?= htmlspecialchars($cat) ?></div>
        <div class="menu-grid">
          <?php foreach ($items as $m): ?>
            <div class="menu-card" onclick="addToCart(<?= $m['id'] ?>, '<?= htmlspecialchars($m['name'], ENT_QUOTES) ?>', <?= $m['price'] ?>)">
              <div class="emoji"><?= $emoji ?></div>
              <div class="name"><?= htmlspecialchars($m['name']) ?></div>
              <div class="price"><?= number_format($m['price']) ?>원</div>
              <?php if (!empty($m['description'])): ?>
              <div class="desc"><?= htmlspecialchars($m['description']) ?></div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- 장바구니 영역 -->
  <div class="cart-panel">
    <div class="cart-header">
      <span>🛒 주문서</span>
      <button class="cart-clear" onclick="clearCart()">전체 삭제</button>
    </div>
    <div class="cart-items" id="cartItems">
      <div class="cart-empty">메뉴를 선택해주세요</div>
    </div>
    <div class="cart-footer">
      <div class="total-row">
        <span class="total-label">합계</span>
        <span class="total-amount" id="totalAmount">0원</span>
      </div>
      <button class="pay-btn" id="payBtn" onclick="openPayModal()" disabled>
        💳 카드 결제하기
      </button>
    </div>
  </div>
</div>

<!-- 결제 대기 모달 -->
<div class="modal-overlay" id="payModal">
  <div class="modal">
    <div class="modal-icon">💳</div>
    <h2>카드를 리더기에 태그해주세요</h2>
    <div class="modal-sub">카드를 갖다 대거나 단말기에 삽입해주세요</div>
    <div class="modal-total" id="modalTotal">0원</div>
    <div class="card-waiting">
      <div class="pulse-dot"></div>
      <span>카드 인식 대기중...</span>
    </div>
    <!-- Keyboard Wedge 방식: 리더기 입력을 숨겨진 input이 캡처 -->
    <input type="text" id="cardInputHidden" autocomplete="off">
    <div class="modal-actions">
      <button class="modal-cancel" onclick="closePayModal()">취소</button>
    </div>
  </div>
</div>

<!-- 결제 완료 모달 -->
<div class="modal-overlay" id="successModal">
  <div class="modal">
    <div class="success-circle">✓</div>
    <h2>결제 완료!</h2>
    <div class="modal-sub">카드 결제가 성공적으로 처리되었습니다.</div>
    <div class="modal-total" id="successTotal" style="color:var(--green)"></div>
    <div style="font-size:.8rem; color:var(--muted); margin-bottom:20px;" id="successCard"></div>
    <button class="pay-btn" onclick="closeSuccessModal()" style="max-width:200px; margin:0 auto;">
      새 주문 시작
    </button>
  </div>
</div>

<script>
  // ─── 시계 ───
  function updateClock() {
    const now = new Date();
    document.getElementById('clock').textContent = now.toLocaleString('ko-KR', {
      year: 'numeric', month: '2-digit', day: '2-digit',
      hour: '2-digit', minute: '2-digit', second: '2-digit'
    });
  }
  setInterval(updateClock, 1000);
  updateClock();

  // ─── 장바구니 상태 ───
  let cart = {}; // { id: { id, name, price, quantity } }

  function addToCart(id, name, price) {
    if (cart[id]) {
      cart[id].quantity++;
    } else {
      cart[id] = { id, name, price, quantity: 1 };
    }
    renderCart();
  }

  function changeQty(id, delta) {
    if (!cart[id]) return;
    cart[id].quantity += delta;
    if (cart[id].quantity <= 0) delete cart[id];
    renderCart();
  }

  function clearCart() {
    cart = {};
    renderCart();
  }

  function renderCart() {
    const container = document.getElementById('cartItems');
    const keys = Object.keys(cart);
    if (keys.length === 0) {
      container.innerHTML = '<div class="cart-empty">메뉴를 선택해주세요</div>';
      document.getElementById('totalAmount').textContent = '0원';
      document.getElementById('payBtn').disabled = true;
      return;
    }

    let html = '';
    let total = 0;
    for (const id of keys) {
      const item = cart[id];
      const sub = item.price * item.quantity;
      total += sub;
      html += `
        <div class="cart-item">
          <div class="ci-name">${item.name}</div>
          <div class="ci-qty">
            <button class="qty-btn" onclick="changeQty(${id}, -1)">−</button>
            <span class="qty-num">${item.quantity}</span>
            <button class="qty-btn" onclick="changeQty(${id}, +1)">+</button>
          </div>
          <div class="ci-price">${sub.toLocaleString()}원</div>
        </div>`;
    }
    container.innerHTML = html;
    document.getElementById('totalAmount').textContent = total.toLocaleString() + '원';
    document.getElementById('payBtn').disabled = false;
  }

  function getTotal() {
    return Object.values(cart).reduce((s, i) => s + i.price * i.quantity, 0);
  }

  // ─── 결제 모달 ───
  let cardBuffer = '';
  let cardTimer = null;

  function openPayModal() {
    if (Object.keys(cart).length === 0) return;
    document.getElementById('modalTotal').textContent = getTotal().toLocaleString() + '원';
    document.getElementById('payModal').classList.add('show');

    // Keyboard Wedge 방식으로 카드 입력 캡처
    cardBuffer = '';
    const inp = document.getElementById('cardInputHidden');
    inp.value = '';
    inp.focus();
    inp.addEventListener('input', onCardInput);
  }

  function onCardInput(e) {
    cardBuffer += e.target.value;
    e.target.value = '';

    clearTimeout(cardTimer);
    // 리더기는 보통 100ms 이내에 전체 데이터를 쏴줌 → 300ms 후 확정
    cardTimer = setTimeout(() => {
      if (cardBuffer.length > 4) {
        processCardPayment(cardBuffer);
      }
      cardBuffer = '';
    }, 300);
  }

  function closePayModal() {
    document.getElementById('payModal').classList.remove('show');
    const inp = document.getElementById('cardInputHidden');
    inp.removeEventListener('input', onCardInput);
    clearTimeout(cardTimer);
    cardBuffer = '';
  }

  function processCardPayment(cardData) {
    closePayModal();
    const last4 = cardData.replace(/\D/g, '').slice(-4) || '****';

    const items = Object.values(cart).map(i => ({
      id: i.id, name: i.name, price: i.price, quantity: i.quantity
    }));

    fetch('/api/orders.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ items, card_last4: last4 })
    })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        document.getElementById('successTotal').textContent = res.total_price.toLocaleString() + '원';
        document.getElementById('successCard').textContent = `카드 (****-****-****-${last4})`;
        document.getElementById('successModal').classList.add('show');
      } else {
        alert('결제 실패: ' + res.message);
      }
    })
    .catch(() => alert('네트워크 오류가 발생했습니다.'));
  }

  function closeSuccessModal() {
    document.getElementById('successModal').classList.remove('show');
    clearCart();
  }
</script>
</body>
</html>
