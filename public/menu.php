<?php
require_once __DIR__ . '/db.php';
try {
    $pdo   = getDB();
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50) NOT NULL UNIQUE, sort_order INT NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $menus = $pdo->query("SELECT m.* FROM menus m LEFT JOIN categories c ON c.name = m.category WHERE m.is_available = 1 AND (c.id IS NULL OR c.is_hidden = 0) ORDER BY COALESCE(c.sort_order, 9999), m.id")->fetchAll();
} catch (Exception $e) { $menus = []; }

$grouped = [];
foreach ($menus as $m) $grouped[$m['category']][] = $m;

// 카테고리 메타 (각 카테고리별 포인트 컬러와 배경색)
$catMeta = [
    '커피'   => ['icon' => '☕', 'en' => 'COFFEE',    'color' => '#5c4332', 'bg' => '#f2efec'], // 연한 브라운톤
    '라떼'   => ['icon' => '🥛', 'en' => 'LATTE',     'color' => '#4b6c8a', 'bg' => '#edf3f8'], // 연한 블루/밀크톤
    '에이드'  => ['icon' => '🍋', 'en' => 'ADE',       'color' => '#d49b00', 'bg' => '#fefaf0'], // 연한 옐로우
    '스무디'  => ['icon' => '🍓', 'en' => 'SMOOTHIE',  'color' => '#c95b74', 'bg' => '#fdf2f4'], // 연한 핑크
    '차'     => ['icon' => '🍵', 'en' => 'TEA',       'color' => '#3a7d44', 'bg' => '#edf7ed'], // 이쁜 연녹색
    '기타'   => ['icon' => '🥤', 'en' => 'OTHERS',    'color' => '#6b6b8a', 'bg' => '#f2f2f5'], // 연한 그레이톤
];

// 가격 뱃지 색상
function priceColor(int $price): string {
    if ($price <= 3500) return '#c9a96e';
    if ($price <= 5000) return '#88b4a0';
    return '#e8889a';
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="refresh" content="120"> <!-- 2분마다 자동 새로고침 -->
<title>LeeLee Cafe | 메뉴판</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Noto+Sans+KR:wght@300;400;500;700;900&display=swap" rel="stylesheet">
<style>
/* ─── RESET & BASE ─── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --bg:      #ffffff;
  --bg2:     #f7f9f8;
  --gold:    #006241; /* Starbucks Green */
  --gold2:   #1e3932; /* Dark Green */
  --cream:   #111111; /* Dark Text */
  --muted:   #6b6b6b;
  --border:  #e2e2e2;
  --white:   #111111;
}
html, body {
  min-height: 100vh;
  background: var(--bg);
  color: var(--cream);
  font-family: 'Noto Sans KR', sans-serif;
  scroll-behavior: smooth;
}

/* ─── ANIMATED BACKGROUND ─── */
body::before {
  content: '';
  position: fixed; inset: 0; z-index: 0;
  background:
    radial-gradient(ellipse 60% 40% at 20% 20%, rgba(0,98,65,.04) 0%, transparent 60%),
    radial-gradient(ellipse 50% 60% at 80% 80%, rgba(30,57,50,.03) 0%, transparent 60%);
  pointer-events: none;
}

/* ─── HEADER ─── */
.board-header {
  position: sticky; top: 0; z-index: 100;
  background: rgba(255,255,255,.95);
  backdrop-filter: blur(16px);
  border-bottom: 1px solid var(--border);
  padding: 0 40px;
  display: flex; align-items: center; justify-content: space-between;
  height: 70px;
}
.brand {
  display: flex; align-items: center; gap: 14px;
}
.brand-icon {
  font-size: 1.8rem;
  animation: float 3s ease-in-out infinite;
}
@keyframes float {
  0%,100% { transform: translateY(0); }
  50% { transform: translateY(-4px); }
}
.brand-name {
  font-family: 'Playfair Display', serif;
  font-size: 1.45rem; font-weight: 900;
  background: linear-gradient(135deg, var(--gold), var(--gold2));
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  letter-spacing: .5px;
}
.brand-sub {
  font-size: .7rem; color: var(--muted); letter-spacing: 3px;
  text-transform: uppercase; margin-top: 1px;
}
.header-right { display: flex; align-items: center; gap: 20px; }
.clock {
  font-size: 1.1rem; font-weight: 700; color: var(--gold);
  font-variant-numeric: tabular-nums; letter-spacing: 1px;
}
.nav-tabs {
  display: flex; gap: 4px;
  background: #f0f0f0;
  border: 1px solid var(--border);
  border-radius: 50px; padding: 6px;
}
.nav-tab {
  padding: 10px 24px; border-radius: 50px;
  font-size: 1rem; font-weight: 700; letter-spacing: .5px;
  color: var(--muted); cursor: pointer;
  transition: all .25s; border: none; background: none;
  font-family: inherit;
}
.nav-tab.active, .nav-tab:hover {
  background: var(--gold); color: #ffffff; 
}

/* ─── HERO TICKER ─── */
.ticker-wrap {
  background: var(--gold);
  overflow: hidden; height: 36px; display: flex; align-items: center;
  position: relative; z-index: 1;
}
.ticker-text {
  white-space: nowrap;
  animation: ticker 56s linear infinite;
  font-size: .78rem; font-weight: 700; color: #ffffff;
  letter-spacing: 1.5px; text-transform: uppercase;
}
@keyframes ticker {
  from { transform: translateX(100vw); }
  to   { transform: translateX(-100%); }
}

/* ─── MAIN CONTENT ─── */
.main { max-width: 1400px; margin: 0 auto; padding: 40px 32px 60px; position: relative; z-index: 1; }

/* ─── CATEGORY SECTION ─── */
.cat-section {
  margin-bottom: 40px;
  padding: 36px 40px 48px;
  border-radius: 28px;
  opacity: 0; transform: translateY(24px);
  animation: fadeUp .5s ease forwards;
}
.cat-section:nth-child(1){ animation-delay:.05s }
.cat-section:nth-child(2){ animation-delay:.12s }
.cat-section:nth-child(3){ animation-delay:.19s }
.cat-section:nth-child(4){ animation-delay:.26s }
.cat-section:nth-child(5){ animation-delay:.33s }
.cat-section:nth-child(6){ animation-delay:.4s }
@keyframes fadeUp {
  to { opacity:1; transform:translateY(0); }
}

.cat-header {
  display: flex; align-items: center; gap: 16px;
  margin-bottom: 24px; padding-bottom: 14px;
  border-bottom: 1px solid var(--border);
}
.cat-icon { font-size: 2rem; }
.cat-title-wrap {}
.cat-title {
  font-family: 'Playfair Display', serif;
  font-size: 1.6rem; font-weight: 900; line-height: 1;
  background: linear-gradient(135deg, var(--gold) 0%, var(--gold2) 100%);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
}
.cat-en {
  font-size: .65rem; font-weight: 700; letter-spacing: 4px;
  color: var(--muted); margin-top: 4px;
}
.cat-count {
  margin-left: auto;
  background: rgba(0,98,65,.08); border: 1px solid rgba(0,98,65,.15);
  border-radius: 50px; padding: 4px 12px;
  font-size: .72rem; color: var(--gold); font-weight: 700;
}

/* ─── MENU GRID ─── */
.menu-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 16px;
}

/* ─── MENU CARD ─── */
.menu-card {
  background: #ffffff;
  border: 1px solid var(--border);
  border-radius: 18px;
  padding: 24px 22px;
  display: flex; gap: 16px; align-items: flex-start;
  transition: all .28s cubic-bezier(.4,0,.2,1);
  cursor: default;
  position: relative; overflow: hidden;
  box-shadow: 0 4px 12px rgba(0,0,0,.03);
}
.menu-card::after {
  content: '';
  position: absolute; inset: 0;
  background: linear-gradient(135deg, rgba(0,98,65,.04) 0%, transparent 70%);
  opacity: 0; transition: opacity .28s;
}
.menu-card:hover {
  border-color: rgba(0,98,65,.3);
  transform: translateY(-3px);
  box-shadow: 0 12px 30px rgba(0,0,0,.08), 0 0 0 1px rgba(0,98,65,.1);
}
.menu-card:hover::after { opacity: 1; }

/* 인기 뱃지 */
.menu-card.popular::before {
  content: '인기 ✦';
  position: absolute; top: 12px; right: 12px;
  background: linear-gradient(135deg, #e8a030, #c96e30);
  color: #fff; font-size: .62rem; font-weight: 800;
  letter-spacing: .5px; padding: 3px 9px; border-radius: 20px;
  z-index: 2;
}
/* NEW 뱃지 */
.menu-card.isnew::before {
  content: 'NEW ✦';
  position: absolute; top: 12px; right: 12px;
  background: linear-gradient(135deg, #60b4a0, #3890a0);
  color: #fff; font-size: .62rem; font-weight: 800;
  letter-spacing: .5px; padding: 3px 9px; border-radius: 20px;
  z-index: 2;
}

.card-emoji {
  font-size: 2.4rem; flex-shrink: 0;
  width: 56px; height: 56px;
  background: var(--bg2);
  border: 1px solid var(--border);
  border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
}
.card-body { flex: 1; min-width: 0; }
.card-name {
  font-size: 1rem; font-weight: 700;
  color: var(--white); margin-bottom: 5px;
  letter-spacing: -.2px;
}
.card-desc {
  font-size: .78rem; color: var(--muted); line-height: 1.55;
  margin-bottom: 12px;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
  overflow: hidden;
}
.card-footer {
  display: flex; align-items: center; justify-content: space-between;
}
.card-price {
  font-size: 1.05rem; font-weight: 900;
  color: var(--gold2); letter-spacing: -.3px;
}
.card-tag {
  font-size: .66rem; font-weight: 700; letter-spacing: .5px;
  padding: 3px 9px; border-radius: 20px;
  border: 1px solid;
}

/* ─── DIVIDER ─── */
.section-divider {
  text-align: center; margin: 16px 0 48px;
  color: var(--muted); font-size: .75rem; letter-spacing: 3px;
  display: flex; align-items: center; gap: 16px;
}
.section-divider::before, .section-divider::after {
  content: ''; flex: 1; height: 1px;
  background: linear-gradient(90deg, transparent, var(--border), transparent);
}

/* ─── FOOTER ─── */
.board-footer {
  text-align: center;
  padding: 24px;
  border-top: 1px solid var(--border);
  color: var(--muted); font-size: .72rem; letter-spacing: 1px;
  position: relative; z-index: 1;
}

/* ─── RESPONSIVE ─── */
@media (max-width: 768px) {
  .board-header { padding: 0 16px; }
  .main { padding: 24px 16px 40px; }
  .cat-section { padding: 24px 20px 32px; border-radius: 20px; }
  .menu-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
  .menu-card { padding: 16px 14px; gap: 10px; }
  .nav-tabs { display: none; }
}
@media (max-width: 480px) {
  .menu-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<!-- ─── HEADER ─── -->
<header class="board-header">
  <div class="brand">
    <div class="brand-icon">🌿</div>
    <div>
      <div class="brand-name">LeeLee Cafe</div>
      <div class="brand-sub">Premium Handcrafted Drinks</div>
    </div>
  </div>
  <div class="header-right">
    <div class="nav-tabs">
      <?php foreach (array_keys($grouped) as $i => $cat): ?>
      <button class="nav-tab <?= $i===0?'active':'' ?>" onclick="scrollTo('cat-<?= $i ?>')"><?= $catMeta[$cat]['icon']??'🍹' ?> <?= htmlspecialchars($cat) ?></button>
      <?php endforeach; ?>
    </div>
    <div class="clock" id="clock">--:--</div>
  </div>
</header>

<!-- ─── TICKER ─── -->
<div class="ticker-wrap">
  <div class="ticker-text">
    ✦ 오늘의 추천: 콜드브루 &nbsp;|&nbsp; 딸기라떼 &nbsp;|&nbsp; 망고스무디 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    ✦ TODAY'S SPECIAL: COLD BREW · STRAWBERRY LATTE · MANGO SMOOTHIE &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    ✦ 모든 음료는 주문 즉시 신선하게 만들어집니다 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    ✦ FRESHLY MADE ON EVERY ORDER &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    ✦ 오늘의 추천: 콜드브루 &nbsp;|&nbsp; 딸기라떼 &nbsp;|&nbsp; 망고스무디 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    ✦ TODAY'S SPECIAL: COLD BREW · STRAWBERRY LATTE · MANGO SMOOTHIE
  </div>
</div>

<!-- ─── MENU CONTENT ─── -->
<main class="main">

<?php
// 인기/신규 뱃지 지정
$popular = ['카라멜마키아토','콜드브루','딸기라떼','바닐라라떼','망고스무디'];
$isnew   = ['흑임자라떼','콜드브루','자몽에이드'];

// 카테고리 이모지 (카드 이모지 별도)
$cardEmoji = [
    '아메리카노' => '☕', '카페라떼' => '☕', '카푸치노' => '☕',
    '바닐라라떼' => '✨', '카라멜마키아토' => '🍮', '콜드브루' => '🧊',
    '에스프레소' => '⚡', '녹차라떼' => '🍵', '딸기라떼' => '🍓',
    '초코라떼' => '🍫', '흑임자라떼' => '🌑', '자몽에이드' => '🍊',
    '레몬에이드' => '🍋', '딸기스무디' => '🍓', '망고스무디' => '🥭',
    '녹차' => '🍵', '캐모마일' => '🌼', '유자차' => '🍋',
    '라떼' => '🥛',
];

$catIdx = 0;
foreach ($grouped as $cat => $items):
    $meta = $catMeta[$cat] ?? ['icon'=>'🍹','en'=>strtoupper($cat),'color'=>'#006241','bg'=>'#f0f0f0'];
?>
<section class="cat-section" id="cat-<?= $catIdx ?>" style="background: <?= $meta['bg'] ?>;">
  <div class="cat-header">
    <div class="cat-icon"><?= $meta['icon'] ?></div>
    <div class="cat-title-wrap">
      <div class="cat-title"><?= htmlspecialchars($cat) ?></div>
      <div class="cat-en"><?= $meta['en'] ?></div>
    </div>
    <div class="cat-count"><?= count($items) ?>가지 음료</div>
  </div>

  <div class="menu-grid">
  <?php foreach ($items as $m):
    $isP = in_array($m['name'], $popular);
    $isN = in_array($m['name'], $isnew);
    $cls = $isP ? 'popular' : ($isN ? 'isnew' : '');
    $emoji = $cardEmoji[$m['name']] ?? $meta['icon'];
    $pc = priceColor((int)$m['price']);
  ?>
    <div class="menu-card <?= $cls ?>">
      <div class="card-emoji"><?= $emoji ?></div>
      <div class="card-body">
        <div class="card-name"><?= htmlspecialchars($m['name']) ?></div>
        <?php if (!empty($m['description'])): ?>
        <div class="card-desc"><?= htmlspecialchars($m['description']) ?></div>
        <?php endif; ?>
        <div class="card-footer">
          <div class="card-price"><?= number_format($m['price']) ?>원</div>
          <div class="card-tag" style="color:<?= $meta['color'] ?>;border-color:<?= $meta['color'] ?>30;background:<?= $meta['color'] ?>12">
            <?= htmlspecialchars($cat) ?>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  </div>
</section>

<?php
  if ($catIdx < count($grouped) - 1):
?>
<div class="section-divider">✦ &nbsp; &nbsp; ✦ &nbsp; &nbsp; ✦</div>
<?php endif; ?>

<?php $catIdx++; endforeach; ?>

<?php if (empty($grouped)): ?>
<div style="text-align:center;padding:80px 0;color:var(--muted)">
  <div style="font-size:3rem;margin-bottom:16px">☕</div>
  <p>메뉴를 준비 중입니다.</p>
  <p style="font-size:.85rem;margin-top:8px">관리자 페이지에서 메뉴를 등록해 주세요.</p>
</div>
<?php endif; ?>

</main>

<!-- ─── FOOTER ─── -->
<footer class="board-footer">
  <p>✦ &nbsp; 모든 음료는 주문 후 신선하게 제조됩니다 &nbsp; · &nbsp; All beverages are freshly prepared upon order &nbsp; ✦</p>
  <p style="margin-top:6px">가격은 부가세 포함 · Prices include VAT</p>
</footer>

<script>
// 시계
function tick() {
  const now = new Date();
  const h = String(now.getHours()).padStart(2,'0');
  const m = String(now.getMinutes()).padStart(2,'0');
  document.getElementById('clock').textContent = h + ':' + m;
}
tick(); setInterval(tick, 1000);

// 카테고리 네비게이션
function scrollTo(id) {
  document.getElementById(id)?.scrollIntoView({ behavior:'smooth', block:'start' });
  document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
  event.target.classList.add('active');
}

// 스크롤에 따라 네비 활성화
const sections = document.querySelectorAll('.cat-section');
const tabs = document.querySelectorAll('.nav-tab');
const obs = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      const idx = [...sections].indexOf(e.target);
      tabs.forEach((t,i) => t.classList.toggle('active', i===idx));
    }
  });
}, { rootMargin: '-40% 0px -40% 0px' });
sections.forEach(s => obs.observe(s));
</script>
</body>
</html>
