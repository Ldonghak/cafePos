<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>청춘 스토리 | CafePOS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Nanum+Myeongjo:wght@400;700;800&family=Noto+Sans+KR:wght@300;400;700;900&display=swap" rel="stylesheet">
<style>
/* ─── RESET & BASE ─── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body, html {
  width: 100%; height: 100%;
  overflow: hidden;
  background: #000;
  font-family: 'Noto Sans KR', sans-serif;
}

/* ─── SLIDER CONTAINER ─── */
.slider {
  position: relative;
  width: 100vw; height: 100vh;
}

.slide {
  position: absolute;
  inset: 0;
  opacity: 0;
  visibility: hidden;
  transition: opacity 2s ease-in-out, visibility 2s ease-in-out;
  display: flex;
  align-items: center; justify-content: center;
  flex-direction: column;
  z-index: 1;
}

.slide.active {
  opacity: 1;
  visibility: visible;
  z-index: 3;
}

.slide.last-active {
  opacity: 0;
  visibility: hidden;
  z-index: 2;
}

/* ─── BACKGROUND IMAGES ─── */
.slide-bg {
  position: absolute;
  inset: 0;
  background-size: cover;
  background-position: center;
  z-index: -2;
  transform: scale(1.05);
  transition: transform 12s ease-out;
}
.slide.active .slide-bg, .slide.last-active .slide-bg {
  transform: scale(1);
}

/* ─── OVERLAY ─── */
.overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to bottom, rgba(0,0,0,0.15) 0%, rgba(0,0,0,0.6) 100%);
  z-index: -1;
}

/* ─── CONTENT ─── */
.content {
  text-align: center;
  color: #fff;
  padding: 0 20px;
  max-width: 800px;
  opacity: 0;
  transform: translateY(30px);
  transition: opacity 0s 2s, transform 0s 2s;
}
.slide.active .content {
  opacity: 1;
  transform: translateY(0);
  transition: opacity 1.5s 0.5s ease-out, transform 1.5s 0.5s cubic-bezier(0.2, 0.8, 0.2, 1);
}
.slide.last-active .content {
  opacity: 1;
  transform: translateY(0);
  transition: none;
}

.title {
  font-family: 'Noto Sans KR', sans-serif;
  font-size: 3.8rem;
  font-weight: 900;
  margin-bottom: 24px;
  line-height: 1.3;
  letter-spacing: -1px;
  text-shadow: 0 4px 16px rgba(0,0,0,0.4);
}
.subtitle {
  font-size: 1.35rem;
  font-weight: 400;
  line-height: 1.8;
  color: #f8f9fa;
  text-shadow: 0 2px 8px rgba(0,0,0,0.5);
  word-break: keep-all;
}
.divider {
  width: 50px; height: 4px;
  background: #00d26a; /* Fresh green */
  margin: 30px auto;
  border-radius: 2px;
}

/* ─── NAVIGATION DOTS ─── */
.nav-dots {
  position: absolute;
  bottom: 40px;
  left: 0; right: 0;
  display: flex; justify-content: center; gap: 12px;
  z-index: 10;
}
.dot {
  width: 12px; height: 12px;
  border-radius: 50%;
  background: rgba(255,255,255,0.4);
  cursor: pointer;
  transition: all 0.3s ease;
}
.dot.active {
  background: #00d26a;
  transform: scale(1.3);
}

/* ─── PROGRESS BAR ─── */
.progress-bar {
  position: absolute;
  bottom: 0; left: 0;
  height: 5px;
  background: #00d26a;
  width: 0%;
  z-index: 10;
}
</style>
</head>
<body>

<div class="slider" id="slider">

  <!-- Slide 1 -->
  <div class="slide active">
    <div class="slide-bg" style="background-image: url('/img/youth_bg1_1776994766210.png');"></div>
    <div class="overlay"></div>
    <div class="content">
      <div class="title">시작의 싱그러움</div>
      <div class="divider"></div>
      <div class="subtitle">
        눈부시게 쏟아지는 아침 햇살과 시원한 아이스 아메리카노.<br>
        새로운 하루를 기분 좋게 깨우는<br>
        너의 상쾌한 오늘을 언제나 응원해!
      </div>
    </div>
  </div>

  <!-- Slide 2 -->
  <div class="slide">
    <div class="slide-bg" style="background-image: url('/img/youth_bg2_1776994786979.png');"></div>
    <div class="overlay"></div>
    <div class="content">
      <div class="title">뜨거운 열정, 너의 꿈</div>
      <div class="divider"></div>
      <div class="subtitle">
        노트북 앞에서 끊임없이 고민하며 달려가는 너.<br>
        때로는 지치지만, 달콤한 과일 스무디 한 잔이<br>
        너의 꿈을 향한 에너지가 되어줄 거야.
      </div>
    </div>
  </div>

  <!-- Slide 3 -->
  <div class="slide">
    <div class="slide-bg" style="background-image: url('/img/youth_bg3_1776994800540.png');"></div>
    <div class="overlay"></div>
    <div class="content">
      <div class="title">우리가 함께하는 시간</div>
      <div class="divider"></div>
      <div class="subtitle">
        친구들과 가볍게 부딪히는 시원한 라떼 잔,<br>
        우리의 밝은 웃음소리로 가득 채워지는 이 공간이<br>
        가장 아름다운 청춘의 한 페이지 아닐까?
      </div>
    </div>
  </div>

  <!-- Slide 4 -->
  <div class="slide">
    <div class="slide-bg" style="background-image: url('/img/youth_bg4_1776994814865.png');"></div>
    <div class="overlay"></div>
    <div class="content">
      <div class="title">통통 튀는 빛나는 감각</div>
      <div class="divider"></div>
      <div class="subtitle">
        상큼한 딸기 라떼처럼 톡톡 튀는 너만의 아이디어.<br>
        남들과 달라도 괜찮아,<br>
        그게 바로 가장 너다운 빛깔이니까!
      </div>
    </div>
  </div>

  <!-- Slide 5 -->
  <div class="slide">
    <div class="slide-bg" style="background-image: url('/img/youth_bg5_1776994829958.png');"></div>
    <div class="overlay"></div>
    <div class="content">
      <div class="title">어디로든 향하는 발걸음</div>
      <div class="divider"></div>
      <div class="subtitle">
        드넓고 푸른 하늘 아래, 가벼운 커피 한 잔 손에 쥐고.<br>
        발길 닿는 곳이 곧 길이 되는 자유로움,<br>
        그것이 바로 청춘이 가진 가장 큰 무기.
      </div>
    </div>
  </div>

  <!-- Slide 6 -->
  <div class="slide">
    <div class="slide-bg" style="background-image: url('/img/youth_bg6_1776994845161.png');"></div>
    <div class="overlay"></div>
    <div class="content">
      <div class="title">지친 마음을 톡, 리프레시</div>
      <div class="divider"></div>
      <div class="subtitle">
        숨가쁘게 달려오다 잠시 멈춰선 순간,<br>
        톡 쏘는 청포도 에이드의 청량함으로<br>
        지친 너의 하루에 상쾌한 에너지를 불어넣어 줄게.
      </div>
    </div>
  </div>

  <!-- Slide 7 -->
  <div class="slide">
    <div class="slide-bg" style="background-image: url('/img/youth_bg7_1776994860161.png');"></div>
    <div class="overlay"></div>
    <div class="content">
      <div class="title">찬란하게 빛날 너의 매일</div>
      <div class="divider"></div>
      <div class="subtitle">
        초록빛 생기로 가득 찬 햇살 가득한 이곳에서.<br>
        오늘도 내일도 눈부시게 빛날<br>
        여러분의 모든 청춘과 순간을 진심으로 응원합니다!
      </div>
    </div>
  </div>

  <div class="nav-dots" id="nav-dots"></div>
  <div class="progress-bar" id="progress"></div>

</div>

<script>
  const slides = document.querySelectorAll('.slide');
  const totalSlides = slides.length;
  let current = 0;
  const intervalTime = 10000; // 10초
  let slideInterval;

  const dotsContainer = document.getElementById('nav-dots');
  for (let i = 0; i < totalSlides; i++) {
    const dot = document.createElement('div');
    dot.classList.add('dot');
    if (i === 0) dot.classList.add('active');
    dot.addEventListener('click', () => goToSlide(i));
    dotsContainer.appendChild(dot);
  }
  const dots = document.querySelectorAll('.dot');
  const progressBar = document.getElementById('progress');

  function startProgress() {
    progressBar.style.transition = 'none';
    progressBar.style.width = '0%';
    void progressBar.offsetWidth; // Force reflow
    progressBar.style.transition = `width ${intervalTime}ms linear`;
    progressBar.style.width = '100%';
  }

  function goToSlide(index) {
    slides.forEach(s => s.classList.remove('last-active'));

    slides[current].classList.remove('active');
    slides[current].classList.add('last-active');
    dots[current].classList.remove('active');
    
    current = index;
    
    slides[current].classList.add('active');
    dots[current].classList.add('active');
    
    resetInterval();
  }

  function nextSlide() {
    let next = (current + 1) % totalSlides;
    goToSlide(next);
  }

  function resetInterval() {
    clearInterval(slideInterval);
    slideInterval = setInterval(nextSlide, intervalTime);
    startProgress();
  }

  resetInterval();
</script>
</body>
</html>
