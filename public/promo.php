<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>우리카페 역사와 홍보 | CafePOS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Nanum+Myeongjo:wght@400;700;800&family=Noto+Sans+KR:wght@300;400;700&display=swap" rel="stylesheet">
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
  background: linear-gradient(to bottom, rgba(0,0,0,0.3) 0%, rgba(0,0,0,0.7) 100%);
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
  font-family: 'Nanum Myeongjo', serif;
  font-size: 3.5rem;
  font-weight: 800;
  margin-bottom: 24px;
  line-height: 1.3;
  text-shadow: 0 4px 12px rgba(0,0,0,0.5);
}
.subtitle {
  font-size: 1.25rem;
  font-weight: 300;
  line-height: 1.8;
  color: #eaeaea;
  text-shadow: 0 2px 8px rgba(0,0,0,0.5);
  word-break: keep-all;
}
.divider {
  width: 40px; height: 3px;
  background: #c9a96e;
  margin: 30px auto;
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
  background: rgba(255,255,255,0.3);
  cursor: pointer;
  transition: all 0.3s ease;
}
.dot.active {
  background: #c9a96e;
  transform: scale(1.3);
}

/* ─── PROGRESS BAR ─── */
.progress-bar {
  position: absolute;
  bottom: 0; left: 0;
  height: 4px;
  background: #c9a96e;
  width: 0%;
  z-index: 10;
}
</style>
</head>
<body>

<div class="slider" id="slider">

  <!-- Slide 1 -->
  <div class="slide active">
    <div class="slide-bg" style="background-image: url('/img/promo_real_bg1_1776994270251.png');"></div>
    <div class="overlay"></div>
    <div class="content">
      <div class="title">1세대 바리스타의<br>혼이 담긴 커피</div>
      <div class="divider"></div>
      <div class="subtitle">
        대한민국에 원두커피를 최초로 들여오며 시작된 역사.<br>
        전자동 에스프레소 머신이 넘쳐나는 지금도,<br>
        우리는 직접 손으로 내리는 커피의 깊은 가치를 믿습니다.
      </div>
    </div>
  </div>

  <!-- Slide 2 -->
  <div class="slide">
    <div class="slide-bg" style="background-image: url('/img/promo_real_bg2_new_1776994586680.png');"></div>
    <div class="overlay"></div>
    <div class="content">
      <div class="title">1988년,<br>가배 보헤미안의 철학</div>
      <div class="divider"></div>
      <div class="subtitle">
        대학로에서 시작된 원두커피의 성지.<br>
        커피를 향한 끊임없는 연구와 지식의 공유를 통해<br>
        대한민국 커피 문화의 첫 발걸음을 이끌어왔습니다.
      </div>
    </div>
  </div>

  <!-- Slide 3 -->
  <div class="slide">
    <div class="slide-bg" style="background-image: url('/img/promo_real_bg3_1776994306054.png');"></div>
    <div class="overlay"></div>
    <div class="content">
      <div class="title">수천 번의 로스팅,<br>변하지 않는 고집</div>
      <div class="divider"></div>
      <div class="subtitle">
        원두를 섞어 갈아내는 특유의 블렌딩과 고집스러운 장인정신.<br>
        "커피 맛을 내는 데 가장 중요한 것은 그 무엇도 아닌 사람입니다."<br>
        한 잔의 커피를 위해 매일같이 수천 번 주전자를 돌립니다.
      </div>
    </div>
  </div>

  <!-- Slide 4 -->
  <div class="slide">
    <div class="slide-bg" style="background-image: url('/img/promo_real_bg4_1776994322245.png');"></div>
    <div class="overlay"></div>
    <div class="content">
      <div class="title">인생을 풍요롭게 하는<br>단 한 잔</div>
      <div class="divider"></div>
      <div class="subtitle">
        "커피가 가진 가장 큰 매력은 삶의 여유입니다."<br>
        단순히 갈증을 해소하는 음료가 아닌,<br>
        인생을 함께하는 삶의 동반자로서의 커피를 선보입니다.
      </div>
    </div>
  </div>

  <!-- Slide 5 -->
  <div class="slide">
    <div class="slide-bg" style="background-image: url('/img/promo_real_bg5_1776994335994.png');"></div>
    <div class="overlay"></div>
    <div class="content">
      <div class="title">바다의 바람과 함께<br>당신의 쉼을 응원합니다</div>
      <div class="divider"></div>
      <div class="subtitle">
        강릉 연곡의 맑은 공기 속에서 커피를 향한 여정은 계속됩니다.<br>
        최고의 원두와 세심한 핸드드립이 만난 이곳에서,<br>
        여러분의 일상에 편안하고 따뜻한 쉼표를 찍어보세요.
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
  let progressAnimation;

  // 네비게이션 점 생성
  const dotsContainer = document.getElementById('nav-dots');
  for (let i = 0; i < totalSlides; i++) {
    const dot = document.createElement('div');
    dot.classList.add('dot');
    if (i === 0) dot.classList.add('active');
    dot.addEventListener('click', () => goToSlide(i));
    dotsContainer.appendChild(dot);
  }
  const dots = document.querySelectorAll('.dot');

  // 프로그레스 바 요소
  const progressBar = document.getElementById('progress');

  function startProgress() {
    progressBar.style.transition = 'none';
    progressBar.style.width = '0%';
    // 강제 리플로우
    void progressBar.offsetWidth;
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

  // 초기 실행
  resetInterval();
</script>
</body>
</html>
