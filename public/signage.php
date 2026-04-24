<?php
require_once __DIR__ . '/db.php';
try {
    $pdo   = getDB();
    $menus = $pdo->query("SELECT * FROM menus WHERE is_available = 1 ORDER BY FIELD(category,'커피','라떼','에이드','스무디','차','기타'), id")->fetchAll();
} catch (Exception $e) { $menus = []; }

$grouped = [];
foreach ($menus as $m) {
    if (!isset($grouped[$m['category']])) $grouped[$m['category']] = [];
    $grouped[$m['category']][] = $m;
}

$cat_map = [
    '커피'   => [
        'bg' => 'bright_coffee.png',
        'title' => '깊고 진한 휴식의 시간',
        'desc' => "프리미엄 원두의 향긋함이 일상을 깨웁니다.\n한 잔의 커피로 시작하는 완벽한 하루."
    ],
    '라떼'   => [
        'bg' => 'bright_latte.png',
        'title' => '부드러움이 감싸는 순간',
        'desc' => "고소한 에스프레소와 부드러운 우유의 완벽한 조화.\n당신의 마음까지 따뜻하게 데워줄 거예요."
    ],
    '에이드'  => [
        'bg' => 'bright_ade.png',
        'title' => '지친 마음을 톡, 리프레시',
        'desc' => "숨가쁘게 달려오다 잠시 멈춰선 순간,\n톡 쏘는 청량함으로 상쾌한 에너지를 불어넣어 줄게요."
    ],
    '스무디'  => [
        'bg' => 'bright_smoothie.png',
        'title' => '통통 튀는 빛나는 감각',
        'desc' => "신선한 과일처럼 톡톡 튀는 너만의 아이디어.\n달콤하고 부드러운 한 모금으로 기분 전환!"
    ],
    '차'     => [
        'bg' => 'bright_tea.png',
        'title' => '마음이 차분해지는 온기',
        'desc' => "복잡한 생각은 잠시 내려놓고,\n향긋한 잎차 한 잔과 함께 여유로운 시간을 가져보세요."
    ],
    '기타'   => [
        'bg' => 'bright_coffee.png',
        'title' => '언제나 기분 좋은 선택',
        'desc' => "당신의 다양한 취향을 만족시켜 줄\n특별하고 맛있는 디저트와 메뉴들."
    ]
];

function getThumbPath($menuName, $catBg) {
    $name = str_replace(' ', '', $menuName);
    // Order matters — more specific patterns first
    $map = [
        '콜드브루' => 'menu_coldbrew.png',
        '에스프레소' => 'menu_espresso.png',
        '가쪪카푸치노' => 'menu_cappuccino.png',
        '카푸치노' => 'menu_cappuccino.png',
        '카라멜마키아토' => 'menu_macchiato.png',
        '녹차라떼' => 'menu_greentealatte.png',
        '딸기라떼' => 'menu_strawberrylatte.png',
        '초코라떼' => 'menu_chocolatte.png',
        '흑임자라떼' => 'menu_blacksesamelatte.png',
        '바닐라라떼' => 'menu_vanilla.png',
        '커피라떼' => 'menu_cafelatte.png',
        '라떼' => 'menu_cafelatte.png',
        '아메리카노' => 'menu_hotamericano.png',
        '자몽에이드' => 'menu_grapefruitade.png',
        '레몬에이드' => 'menu_lemonade.png',
        '딸기스무디' => 'menu_strawberrysmoothie.png',
        '망고스무디' => 'menu_mangosmoothie.png',
        '녹차' => 'menu_greentea.png',
        '캐모마일' => 'menu_chamomile.png',
        '유자차' => 'menu_yujatee.png'
    ];
    foreach ($map as $key => $file) {
        if (strpos($name, $key) !== false) {
            return '/assets/images/menu/' . $file;
        }
    }
    return '/assets/images/' . $catBg;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>메뉴 전광판 | Cafe POS</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;700;900&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  html, body {
    width: 100vw;
    height: 100vh;
    background: #000;
    color: #fff;
    font-family: 'Noto Sans KR', sans-serif;
    overflow: hidden;
    margin: 0;
    padding: 0;
  }
  .slideshow {
    position: fixed;
    top: 0; left: 0;
    width: 100vw;
    height: 100vh;
  }
  .slide {
    position: absolute;
    top: 0; left: 0;
    width: 100vw;
    height: 100vh;
    opacity: 0;
    transition: opacity 1.5s ease-in-out, transform 8s linear;
    transform: scale(1.05);
    z-index: 1;
    display: flex;
    flex-direction: column;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
  }
  .slide.active {
    opacity: 1;
    transform: scale(1);
    z-index: 2;
  }
  .slide-overlay {
    position: absolute;
    top: 0; left: 0; width: 100%; height: 100%;
    /* Subtle dark gradient to make white text readable while keeping it bright */
    background: linear-gradient(to bottom, rgba(0,0,0,0.2) 0%, rgba(0,0,0,0.5) 50%, rgba(0,0,0,0.7) 100%);
    z-index: -1;
  }
  
  /* Brand Story Section */
  .story-wrap {
    flex: 0 0 auto;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 20px 40px 16px;
    opacity: 0;
    transform: translateY(20px);
    transition: all 1.2s cubic-bezier(0.2, 0.8, 0.2, 1) 0.3s;
  }
  .slide.active .story-wrap {
    transform: translateY(0);
    opacity: 1;
  }
  .story-title {
    font-size: 2.8rem;
    font-weight: 900;
    letter-spacing: -1px;
    margin-bottom: 14px;
    text-shadow: 0 4px 12px rgba(0,0,0,0.4);
  }
  .story-line {
    width: 40px;
    height: 4px;
    background: #00c853;
    margin-bottom: 14px;
    border-radius: 2px;
  }
  .story-desc {
    font-size: 1rem;
    font-weight: 400;
    line-height: 1.7;
    color: rgba(255, 255, 255, 0.95);
    text-shadow: 0 2px 8px rgba(0,0,0,0.5);
    white-space: pre-line;
  }

  /* Menu List Section */
  .menu-board-wrap {
    padding: 0 40px 20px;
    display: flex;
    justify-content: center;
    align-items: center;
    flex: 1;
    opacity: 0;
    transform: translateY(20px);
    transition: all 1.2s cubic-bezier(0.2, 0.8, 0.2, 1) 0.6s;
  }
  .slide.active .menu-board-wrap {
    transform: translateY(0);
    opacity: 1;
  }
  .menu-board {
    width: 100%;
    max-width: 1400px;
    background: rgba(0, 0, 0, 0.35);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-top: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 20px;
    padding: 20px 40px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    /* height adjusts to content automatically */
  }
  .menu-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 0 50px;
  }
  .menu-item {
    display: flex;
    align-items: center;
    border-bottom: 1px dashed rgba(255,255,255,0.2);
    padding: 10px 0;
    gap: 12px;
  }
  .m-thumb {
    width: 54px;
    height: 54px;
    border-radius: 50%;
    background-color: #fff;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    border: 2px solid rgba(255,255,255,0.15);
    flex-shrink: 0;
  }
  .m-info {
    flex: 1;
    display: flex;
    flex-direction: column;
  }
  .m-name {
    font-size: 1.5rem;
    font-weight: 700;
    letter-spacing: 0.5px;
  }
  .m-price {
    font-size: 1.5rem;
    font-weight: 900;
    color: #ffd700;
    letter-spacing: 1px;
    white-space: nowrap;
  }

  /* Pagination */
  .pagination {
    position: absolute;
    bottom: 30px;
    left: 0;
    width: 100%;
    display: flex;
    justify-content: center;
    gap: 8px;
    z-index: 10;
  }
  .dot {
    width: 8px;
    height: 8px;
    background: rgba(255, 255, 255, 0.4);
    border-radius: 50%;
    transition: all 0.3s;
  }
  .dot.active {
    background: #00c853;
    width: 10px;
    height: 10px;
    box-shadow: 0 0 10px #00c853;
  }
</style>
</head>
<body>

<div class="slideshow" id="slideshow">
  <?php 
  $idx = 0;
  $categories = array_keys($grouped);
  foreach ($grouped as $cat => $items): 
      $meta = $cat_map[$cat] ?? $cat_map['기타'];
  ?>
  <div class="slide <?= $idx === 0 ? 'active' : '' ?>" style="background-image: url('/assets/images/<?= $meta['bg'] ?>')">
    <div class="slide-overlay"></div>
    
    <div class="story-wrap">
      <div class="story-title"><?= htmlspecialchars($meta['title']) ?></div>
      <div class="story-line"></div>
      <div class="story-desc"><?= htmlspecialchars($meta['desc']) ?></div>
    </div>

    <div class="menu-board-wrap">
      <div class="menu-board">
        <div class="menu-list">
          <?php foreach ($items as $m): 
            $thumb = getThumbPath($m['name'], $meta['bg']);
          ?>
          <div class="menu-item">
            <div class="m-thumb" style="background-image: url('<?= $thumb ?>')"></div>
            <div class="m-info">
              <span class="m-name"><?= htmlspecialchars($m['name']) ?></span>
              <?php if(!empty($m['description'])): ?>
              <div class="m-desc" style="font-size: 0.9rem; color: #aaa; margin-top: 4px; font-weight: 300;"><?= htmlspecialchars($m['description']) ?></div>
              <?php endif; ?>
            </div>
            <span class="m-price"><?= number_format($m['price']) ?>원</span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
  <?php $idx++; endforeach; ?>

  <div class="pagination" id="pagination">
    <?php for ($i = 0; $i < count($categories); $i++): ?>
      <div class="dot <?= $i === 0 ? 'active' : '' ?>"></div>
    <?php endfor; ?>
  </div>
</div>

<script>
  const slides = document.querySelectorAll('.slide');
  const dots = document.querySelectorAll('.dot');
  let currentSlide = 0;
  const slideInterval = 10000; // 10 seconds per slide

  function nextSlide() {
    slides[currentSlide].classList.remove('active');
    dots[currentSlide].classList.remove('active');
    
    currentSlide = (currentSlide + 1) % slides.length;
    
    slides[currentSlide].classList.add('active');
    dots[currentSlide].classList.add('active');
  }

  if (slides.length > 1) {
    setInterval(nextSlide, slideInterval);
  }
  
  // Reload the page periodically to get new menu updates
  setTimeout(() => {
    window.location.reload();
  }, 1000 * 60 * 15); // Reload every 15 minutes
</script>
</body>
</html>
