<?php 
// 환경파일 읽어오기 (테이블명 작업 폴더 등)
include 'ini.php';    
session_start(); 

 if(!isset($_SESSION["level"]) || $_SESSION["level"]>5) {
          /*   alert("관리자 승인이 필요합니다."); */
		 sleep(1);
         header("Location:http://j-techel.co.kr/login/login_form.php"); 
         exit;
   }  

?>

<!doctype html>

<html lang="ko">
  <head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="제이테크(J-TECH)">
    
  <!-- theme meta -->
  <meta name="theme-name" content="제이테크(J-TECH)" />
  
<!--head 태그 내 추가-->
<!-- Favicon-->	
<link rel="icon" type="image/x-icon" href="favicon.ico">   <!-- 33 x 33 -->
<link rel="shortcut icon" type="image/x-icon" href="favicon.ico">    <!-- 144 x 144 -->
<link rel="apple-touch-icon" type="image/x-icon" href="favicon.ico">  
  
  
  <meta name="author" content="j-techel.co.kr">

  <title>제이테크(J-TECH)</title>

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
  
<script src="http://j-techel.co.kr/common.js"></script>  

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
				<span class="h6 d-inline-block mb-2 subhead text-uppercase">제이테크(J-TECH)</span>
				<h3 class="text-uppercase text-white mb-1">Step up your <span class="text-color">Elevator Business</span><br>with us</h3>
			
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
	<div class="container  mt-3 mb-3">
		<div class="row ">
			<div class="col-lg-4 col-md-6">
				<div class="card p-5 border-0 rounded-top border-bottom position-relative hover-style-1">
					<span class="number">01</span>
					<h3 class="mt-3"> 수주/발주 등록 </h3>
					<p class="mt-3 mb-4">수주내역 등록</p>
					<?php if(intval($_SESSION["level"]) <=2 ) {
						?>
					<a href="write_form.php" class="text-color text-uppercase font-size-13 letter-spacing font-weight-bold"><i class="ti-minus mr-2 "></i>more Details</a>
					<?php } ?>
				</div>
			</div>
			<div class="col-lg-4 col-md-6">
				<div class="card p-5 border-0 rounded-top border-bottom position-relative hover-style-1">
					<span class="number">02</span>
					<h3 class="mt-3"> 수주/발주 관리 </h3>
					<p class="mt-3 mb-4">수주내역 조회/수정/삭제 등 활용</p>
					<a href="list.php" class="text-color text-uppercase font-size-13 letter-spacing font-weight-bold"><i class="ti-minus mr-2 "></i>more Details</a>
				</div>
			</div>
			<div class="col-lg-4 col-md-6">
				<div class="card p-5 border-0 rounded-top hover-style-1">
					<span class="number">03</span>
					<h3 class="mt-3">종합일정관리</h3>
					<p class="mt-3 mb-4"> 수주/발주 세부 일정관리 </p>
					<a href="http://j-techel.co.kr/jtech/schedule.php" class="text-color text-uppercase font-size-13 letter-spacing font-weight-bold"><i class="ti-minus mr-2 "></i>more Details</a>
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
   
