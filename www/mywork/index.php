<?php 
// 환경파일 읽어오기 (테이블명 작업 폴더 등)
include 'ini.php';
// 공통 함수 로드
if (!class_exists('CommonFunctionsLoaded')) {
    require_once __DIR__ . '/../common/functions.php';
    if (!class_exists('CommonFunctionsLoaded')) {
        class CommonFunctionsLoaded {}
    }
}
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

 if(!isset($_SESSION["level"]) || $_SESSION["level"]>5) {
          /*   alert("관리자 승인이 필요합니다."); */
		 sleep(1);
         redirect('login/login_form.php'); 
         exit;
   }  

?>

<!doctype html>

<html lang="ko">
  <head> 
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="오성이엘(OSEL)" >
    
  <!-- theme meta -->
  <meta name="theme-name" content="오성이엘(OSEL)" />
  
<!--head 태그 내 추가-->
<!-- Favicon-->	
<link rel="icon" type="image/x-icon" href="favicon.ico">   <!-- 33 x 33 -->
<link rel="shortcut icon" type="image/x-icon" href="favicon.ico">    <!-- 144 x 144 -->
<link rel="apple-touch-icon" type="image/x-icon" href="favicon.ico">  
    
<meta name="author" content="j-techel.co.kr">

<title> 오성이엘(OSEL) </title>

  <!-- bootstrap.min css -->
  <link rel="stylesheet" href="plugins/bootstrap/css/bootstrap.min.css">
  <!-- Icofont Css -->
  <link rel="stylesheet" href="plugins/icofont/icofont.min.css">
  <!-- Themify Css -->
  <link rel="stylesheet" href="plugins/themify/css/themify-icons.css">
  <!-- animate.css -->
  <link rel="stylesheet" href="plugins/animate-css/animate.css">
  <!-- Magnify Popup -->
  <link rel="stylesheet" href="plugins/magnific-popup/dist/magnific-popup.css">
  <!-- Owl Carousel CSS -->
  <link rel="stylesheet" href="plugins/slick-carousel/slick/slick.css">
  <link rel="stylesheet" href="plugins/slick-carousel/slick/slick-theme.css">
  <!-- Main Stylesheet -->
  <link rel="stylesheet" href="css/style1.css?v=1">
  
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  
  <!-- Google Fonts - Noto Sans KR -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
<?php echo js('common.js'); ?>  

<style>
/* 개선된 UI/UX 스타일 */
:root {
    --primary-color: #2563eb;
    --secondary-color: #0891b2;
    --accent-color: #f59e0b;
    --text-dark: #1f2937;
    --text-light: #6b7280;
    --bg-light: #f9fafb;
    --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --card-shadow-hover: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

body {
    font-family: 'Noto Sans KR', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Apple SD Gothic Neo', sans-serif;
    color: var(--text-dark);
    line-height: 1.7;
    background: var(--bg-light);
}

/* 슬라이더 섹션 개선 */
.slider {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 80px 0 100px;
    position: relative;
    overflow: hidden;
}

.slider::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,149.3C960,160,1056,160,1152,138.7C1248,117,1344,75,1392,53.3L1440,32L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
    background-size: cover;
    opacity: 0.5;
}

.slider .container {
    position: relative;
    z-index: 1;
}

.slider .subhead {
    font-size: 0.875rem;
    letter-spacing: 2px;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 1rem;
    display: inline-block;
    padding: 8px 20px;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 50px;
    backdrop-filter: blur(10px);
}

.slider h3 {
    font-size: 3rem;
    font-weight: 800;
    line-height: 1.2;
    margin-bottom: 1.5rem;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
}

.slider .text-color {
    color: #fbbf24 !important;
}

/* 카드 디자인 개선 */
.card {
    border: none !important;
    border-radius: 16px !important;
    box-shadow: var(--card-shadow);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    background: white;
    overflow: hidden;
    position: relative;
    height: 100%;
}

.card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
    transform: scaleX(0);
    transition: transform 0.4s ease;
}

.card:hover::before {
    transform: scaleX(1);
}

.card:hover {
    transform: translateY(-8px);
    box-shadow: var(--card-shadow-hover);
}

.card .number {
    position: absolute;
    top: 20px;
    right: 20px;
    font-size: 3.5rem;
    font-weight: 800;
    color: rgba(37, 99, 235, 0.08);
    line-height: 1;
    transition: all 0.3s ease;
}

.card:hover .number {
    color: rgba(37, 99, 235, 0.15);
    transform: scale(1.1);
}

.card h3 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 1rem;
    line-height: 1.4;
}

.card p {
    color: var(--text-light);
    font-size: 0.95rem;
    line-height: 1.6;
    margin-bottom: 1.5rem;
}

.card a {
    color: var(--primary-color);
    font-weight: 600;
    text-decoration: none;
    font-size: 0.875rem;
    display: inline-flex;
    align-items: center;
    transition: all 0.3s ease;
    letter-spacing: 0.5px;
}

.card a:hover {
    color: var(--secondary-color);
    transform: translateX(4px);
}

.card a i {
    margin-right: 8px;
    font-size: 1rem;
}

/* 카드 색상 구분 */
.col-lg-3:nth-child(1) .card h3 {
    color: #2563eb;
}

.col-lg-3:nth-child(2) .card h3 {
    color: #0891b2;
}

.col-lg-3:nth-child(3) .card h3 {
    color: #f59e0b;
}

.col-lg-3:nth-child(4) .card h3 {
    color: #8b5cf6;
}

/* 섹션 제목 스타일 */
.section-title {
    position: relative;
    display: inline-block;
}

.section-title::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
    border-radius: 2px;
}

.section-subtitle {
    animation: fadeInUp 0.8s ease-out 0.2s both;
}

/* 반응형 개선 */
@media (max-width: 991px) {
    .slider h3 {
        font-size: 2rem;
    }
    
    .section-title {
        font-size: 2rem !important;
    }
    
    .section-subtitle {
        font-size: 1rem !important;
    }
    
    .card {
        margin-bottom: 1.5rem;
    }
}

@media (max-width: 767px) {
    .slider {
        padding: 60px 0 80px;
    }
    
    .slider h3 {
        font-size: 1.75rem;
    }
    
    .slider .subhead {
        font-size: 0.75rem;
        padding: 6px 16px;
    }
    
    .section-title {
        font-size: 1.75rem !important;
    }
    
    .section-subtitle {
        font-size: 0.9rem !important;
        padding: 0 1rem;
    }
    
    .card {
        padding: 2rem !important;
        margin-bottom: 1.5rem;
    }
    
    .card .number {
        font-size: 2.5rem;
        top: 15px;
        right: 15px;
    }
    
    .card h3 {
        font-size: 1.25rem;
        margin-top: 1rem !important;
    }
    
    .card p {
        font-size: 0.875rem;
    }
    
    .card a {
        font-size: 0.8125rem;
    }
    
    .card i.bi {
        font-size: 2rem !important;
    }
}

/* 섹션 간격 개선 */
.mt-100px {
    margin-top: 60px !important;
}

section.mt-3 {
    margin-top: 3rem !important;
    margin-bottom: 4rem;
}

/* 애니메이션 효과 */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card {
    animation: fadeInUp 0.6s ease-out;
    animation-fill-mode: both;
}

.col-lg-3:nth-child(1) .card {
    animation-delay: 0.1s;
}

.col-lg-3:nth-child(2) .card {
    animation-delay: 0.2s;
}

.col-lg-3:nth-child(3) .card {
    animation-delay: 0.3s;
}

.col-lg-3:nth-child(4) .card {
    animation-delay: 0.4s;
}

/* 접근성 개선 */
.card:focus-within {
    outline: 3px solid var(--primary-color);
    outline-offset: 2px;
}

/* 스크롤 애니메이션 */
@keyframes slideInFromBottom {
    from {
        opacity: 0;
        transform: translateY(50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* 버튼 스타일 개선 */
.card a.text-color {
    position: relative;
    overflow: hidden;
}

.card a.text-color::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 0;
    height: 2px;
    background: var(--primary-color);
    transition: width 0.3s ease;
}

.card a.text-color:hover::after {
    width: 100%;
}

/* 툴팁 스타일 */
[data-tooltip] {
    position: relative;
    cursor: help;
}

[data-tooltip]:hover::before {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    padding: 8px 12px;
    background: rgba(0, 0, 0, 0.9);
    color: white;
    font-size: 0.75rem;
    white-space: nowrap;
    border-radius: 6px;
    z-index: 1000;
}

/* 포커스 스타일 개선 */
a:focus-visible {
    outline: 3px solid var(--primary-color);
    outline-offset: 2px;
    border-radius: 4px;
}

/* 다크 모드 대응 준비 */
@media (prefers-color-scheme: dark) {
    /* 향후 다크 모드 지원을 위한 준비 */
}

/* 인쇄 최적화 */
@media print {
    .slider {
        background: white;
        color: black;
    }
    
    .card {
        page-break-inside: avoid;
        box-shadow: none;
        border: 1px solid #ddd !important;
    }
    
    .card .number {
        color: rgba(0, 0, 0, 0.1) !important;
    }
}

/* 고대비 모드 지원 */
@media (prefers-contrast: high) {
    .card {
        border: 2px solid #000 !important;
    }
    
    .card a {
        text-decoration: underline;
    }
}

/* 애니메이션 감소 옵션 */
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
</style>

</head>
<body>

<div class="container-fluid">
	<?php include 'navbar.php'; ?>
</div>

<!-- Header Close -->

<div class="main-wrapper ">
<!-- Section Menu End -->

<!-- Section Slider Start -->
<!-- Slider Start -->
<section class="slider">
	<div class="container">
		<div class="row">
			<div class="col-md-8">
				<span class="h6 d-inline-block mb-2 subhead text-uppercase">오성이엘(OSEL)</span>
				<h3 class="text-uppercase text-white mb-1">새로운 시작 <span class="text-color">엘리베이터는 </span><br> 오성이엘</h3>
			
			<!--	<a href="pricing.html" target="_blank" class="btn btn-main " >Join Us <i class="ti-angle-right ml-3"></i></a>  -->
			</div>
		</div>
	</div>
</section>
<!-- Section Slider End -->

<!-- Section Intro Start -->
<section class="mt-100px mb-5 mt-2">	
<div class="container-fluid mb-3">
		
		<?php include 'load_list.php'; ?>
		
	</div>	
</section>
<!-- Section Intro End -->

<!-- Section Intro Start -->
<section class="mt-3">
	<div class="container  mt-3 ">
		<!-- 섹션 헤더 -->
		<div class="row mb-5">
			<div class="col-12 text-center">
				<h2 class="section-title" style="font-size: 2.5rem; font-weight: 800; color: #1f2937; margin-bottom: 1rem;">
					주요 기능
				</h2>
				<p class="section-subtitle" style="font-size: 1.125rem; color: #6b7280; max-width: 600px; margin: 0 auto;">
					오성이엘의 엘리베이터 관리 시스템으로 효율적인 업무 처리를 경험하세요
				</p>
			</div>
		</div>
		
		<div class="row ">
			<div class="col-lg-3 col-md-6">
				<div class="card p-5 border-0 rounded-top border-bottom position-relative hover-style-1">
					<span class="number">01</span>
					<div style="margin-bottom: 1rem;">
						<i class="bi bi-rulers" style="font-size: 2.5rem; color: #2563eb; opacity: 0.8;"></i>
					</div>
					<h3 class="mt-2"> EL 카 판넬 측정 </h3>
					<p class="mt-3 mb-2">스마트한 판넬 측정 시스템으로 빠르고 정확한 측정이 가능합니다</p>
					<a href="<?php echo url('osel/index.php'); ?>" class="text-color font-size-13 letter-spacing font-weight-bold">
						<i class="bi bi-arrow-right-circle"></i> 측정 시스템 바로가기
					</a>
				</div>
			</div>
			<div class="col-lg-3 col-md-6">
				<div class="card p-5 border-0 rounded-top border-bottom position-relative hover-style-1">
					<span class="number">02</span>
					<div style="margin-bottom: 1rem;">
						<i class="bi bi-file-earmark-plus" style="font-size: 2.5rem; color: #0891b2; opacity: 0.8;"></i>
					</div>
					<h3 class="mt-2"> 수주/발주 등록 </h3>
					<p class="mt-3 mb-4">새로운 프로젝트 수주내역을 간편하게 등록할 수 있습니다</p>
					<?php if(intval($_SESSION["level"]) <=2 ) { ?>
					<a href="write_form.php" class="text-color font-size-13 letter-spacing font-weight-bold">
						<i class="bi bi-arrow-right-circle"></i> 등록하기
					</a>
					<?php } else { ?>
					<span style="color: #9ca3af; font-size: 0.875rem;">
						<i class="bi bi-lock"></i> 접근 권한이 필요합니다
					</span>
					<?php } ?>
				</div>
			</div>
			<div class="col-lg-3 col-md-6">
				<div class="card p-5 border-0 rounded-top border-bottom position-relative hover-style-1">
					<span class="number">03</span>
					<div style="margin-bottom: 1rem;">
						<i class="bi bi-folder-open" style="font-size: 2.5rem; color: #f59e0b; opacity: 0.8;"></i>
					</div>
					<h3 class="mt-2"> 수주/발주 관리 </h3>
					<p class="mt-3 mb-4">수주내역 조회, 수정, 삭제 등 통합 관리가 가능합니다</p>
					<a href="list.php" class="text-color font-size-13 letter-spacing font-weight-bold">
						<i class="bi bi-arrow-right-circle"></i> 관리 화면 열기
					</a>
				</div>
			</div>
			<div class="col-lg-3 col-md-6">
				<div class="card p-5 border-0 rounded-top hover-style-1">
					<span class="number">04</span>
					<div style="margin-bottom: 1rem;">
						<i class="bi bi-calendar-check" style="font-size: 2.5rem; color: #8b5cf6; opacity: 0.8;"></i>
					</div>
					<h3 class="mt-2">종합일정관리</h3>
					<p class="mt-3 mb-4">수주/발주 세부 일정을 한눈에 확인하고 관리합니다</p>
					<a href="<?php echo url('jtech/schedule.php'); ?>" class="text-color font-size-13 letter-spacing font-weight-bold">
						<i class="bi bi-arrow-right-circle"></i> 일정 확인하기
					</a>
				</div>
			</div>           
		</div> 
	</div>     
</section>
<!-- Section Intro End -->

<?php include 'footer.php'; ?>

   <!-- 
    Essential Scripts
    =====================================-->


   <!-- Main jQuery -->
   <script src="plugins/jquery/jquery.js"></script>
   <!-- Bootstrap 4.3.1 -->
   <script src="plugins/bootstrap/js/bootstrap.min.js"></script>
   <!-- Slick Slider -->
   <script src="plugins/slick-carousel/slick/slick.min.js"></script>
   <!--  Magnific Popup-->
   <script src="plugins/magnific-popup/dist/jquery.magnific-popup.min.js"></script>
   <!-- Form Validator -->
   <script src="http://cdnjs.cloudflare.com/ajax/libs/jquery.form/3.32/jquery.form.js"></script>
   <script src="http://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.11.1/jquery.validate.min.js"></script>
   <!-- Google Map -->
   <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBu5nZKbeK-WHQ70oqOWo-_4VmwOwKP9YQ"></script>
   <script src="plugins/google-map/gmap.js"></script>

   <script src="js/script.js"></script>

   </body>

   </html>
   
