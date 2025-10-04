<?php 

if (session_status() == PHP_SESSION_NONE) {
    session_start();
} 

// 환경별 기본 URL 설정
require_once '../config/environment.php';

// 이름으로 구분해서 조회되도록 만듬
$user_name = $_SESSION["name"];
$level = $_SESSION["level"];

header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header ("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header ("Pragma: no-cache"); // HTTP/1.0
header("Expires: 0"); // rfc2616 - Section 14.21   
//header("Refresh:0");  // reload refresh  

$Path = getBaseUrl() . "/mywork/";

$rootPath = getBaseUrl() . "/";

include 'common.php';

// 모바일 사용여부 확인하는 루틴
$mAgent = array("iPhone","iPod","Android","Blackberry", 
    "Opera Mini", "Windows ce", "Nokia", "sony" );
$chkMobile = false;
for($i=0; $i<sizeof($mAgent); $i++){
    if(stripos( $_SERVER['HTTP_USER_AGENT'], $mAgent[$i] )){
        $chkMobile = true;		
        break;
    }
}

require_once("../lib/mydb.php");
$pdo = db_connect();	

?>

<!DOCTYPE html>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<meta name="description" content="JK-테크(JK-TECH)" >    
<!-- theme meta -->
<meta name="theme-name" content="JK-테크(JK-TECH)" />
<html>
 
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<link rel="stylesheet" href="https://uicdn.toast.com/tui.pagination/latest/tui-pagination.css" />
<script src="https://uicdn.toast.com/tui.pagination/latest/tui-pagination.js"></script>
<link rel="stylesheet" href="https://uicdn.toast.com/tui-grid/latest/tui-grid.css"/>
<script src="https://uicdn.toast.com/tui-grid/latest/tui-grid.js"></script>
<!-- CSS only -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdnjs.cloudflare.com/ajax/libs/toastify-js/1.12.0/toastify.min.css" rel="stylesheet">

<script src="https://unpkg.com/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.10/sweetalert2.all.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastify-js/1.12.0/toastify.min.js"></script>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>  
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script> 
<script src="https://code.highcharts.com/highcharts.js"></script>
 <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">   <!--날짜 선택 창 UI 필요 -->

<script src="<?=$Path?>js/script.js"></script>
<script src="<?=$rootPath?>common.js"></script>

<script src="<?=$rootPath?>js/html2canvas.js"></script>    <!-- 스크린샷을 위한 자바스크립트 함수 불러오기 -->  

<link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.18/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.18/summernote-lite.min.js"></script>

<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>	
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">


<link rel="stylesheet" href="<?=$rootPath?>css/style.css"/>  
<link rel="stylesheet" href="<?=$Path?>css/style.css?v=1"/>
<!-- navibarsub css -->  
<link rel="stylesheet" href="<?=$Path?>css/style2.css">

<link href="<?=$Path?>css/calendar.css" rel="stylesheet">

	
  <!-- Icofont Css -->
  <link rel="stylesheet" href="<?=$Path?>plugins/icofont/icofont.min.css">
  <!-- Themify Css -->
  <link rel="stylesheet" href="<?=$Path ?>plugins/themify/css/themify-icons.css">
  <!-- animate.css -->
  <link rel="stylesheet" href="<?=$Path ?>plugins/animate-css/animate.css">
  <!-- Magnify Popup -->
  <link rel="stylesheet" href="<?=$Path ?>plugins/magnific-popup/dist/magnific-popup.css">
  <!-- Owl Carousel CSS -->
  <link rel="stylesheet" href="<?=$Path ?>plugins/slick-carousel/slick/slick.css">
  <link rel="stylesheet" href="<?=$Path ?>plugins/slick-carousel/slick/slick-theme.css">	
  
  
  
<script>
// DOMContentLoaded 이벤트 리스너 추가
document.addEventListener('DOMContentLoaded', function () {
    // showdate 요소와 showframe 요소가 페이지에 존재하는지 확인
    var showdate = document.getElementById('showdate');
    var showframe = document.getElementById('showframe');
    
    // 요소가 존재하지 않으면 나머지 코드는 실행하지 않음
    if (!showdate || !showframe) {
        return;
    }

    var hideTimeout; // 프레임을 숨기기 위한 타이머 변수

    // 요소가 존재한다면 이벤트 리스너를 추가
    showdate.addEventListener('mouseenter', function(event) {
        clearTimeout(hideTimeout);  // 이미 설정된 타이머가 있다면 취소
        showframe.style.top = (showdate.offsetTop + showdate.offsetHeight) + 'px';
        showframe.style.left = showdate.offsetLeft + 'px';
        showframe.style.display = 'block';
    });

    showdate.addEventListener('mouseleave', startHideTimer);

    showframe.addEventListener('mouseenter', function() {
        clearTimeout(hideTimeout);  // 이미 설정된 타이머가 있다면 취소
    });

    showframe.addEventListener('mouseleave', startHideTimer);

    // 타이머를 시작하는 함수
    function startHideTimer() {
        hideTimeout = setTimeout(function() {
            showframe.style.display = 'none';
        }, 300);  // 300ms 후에 프레임을 숨깁니다.
    }
});



// 특정 날짜를 추출하는 기간
document.addEventListener('DOMContentLoaded', function () {
    // showspecialdate 요소와 showspecialframe 요소가 페이지에 존재하는지 확인
    var showspecialdate = document.getElementById('showspecialdate');
    var showspecialframe = document.getElementById('showspecialframe');
    
    // 요소가 존재하지 않으면 나머지 코드는 실행하지 않음
    if (!showspecialdate || !showspecialframe) {
        return;
    }

    var hideTimeout; // 프레임을 숨기기 위한 타이머 변수

    // 요소가 존재한다면 이벤트 리스너를 추가
    showspecialdate.addEventListener('mouseenter', function(event) {
        clearTimeout(hideTimeout);  // 이미 설정된 타이머가 있다면 취소
        showspecialframe.style.top = (showspecialdate.offsetTop + showspecialdate.offsetHeight) + 'px';
        showspecialframe.style.left = showspecialdate.offsetLeft + 'px';
        showspecialframe.style.display = 'block';
    });

    showspecialdate.addEventListener('mouseleave', startHideTimer);

    showspecialframe.addEventListener('mouseenter', function() {
        clearTimeout(hideTimeout);  // 이미 설정된 타이머가 있다면 취소
    });

    showspecialframe.addEventListener('mouseleave', startHideTimer);

    // 타이머를 시작하는 함수
    function startHideTimer() {
        hideTimeout = setTimeout(function() {
            showspecialframe.style.display = 'none';
        }, 300);  // 300ms 후에 프레임을 숨깁니다.
    }
});




</script>
