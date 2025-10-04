<?php
// 환경별 기본 URL 설정
require_once '../config/environment.php';

session_start();

$root_dir = '..' ;

ini_set('display_errors','1');  // 화면에 warning 표시 1 없앰 0

 if(!isset($_SESSION["level"]) || $_SESSION["level"]>5) {
          /*   alert("관리자 승인이 필요합니다."); */
		 sleep(1);
         header("Location:" . getBaseUrl() . "/game/login/logout.php"); 
         exit;
   }
   
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
   

$level= $_SESSION["level"];
$user_name= $_SESSION["name"];
					  
								  
isset($_REQUEST["num"])  ? $num=$_REQUEST["num"] :   $num=''; 


?>  

<!DOCTYPE html>
<html lang="ko">
<head> 
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>

<!-- 화면에 UI창 알람창 -->
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.10/dist/sweetalert2.min.css" rel="stylesheet">

<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script> 
<script src="https://code.highcharts.com/highcharts.js"></script>
 <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">   <!--날짜 선택 창 UI 필요 -->

<!-- 최초화면에서 보여주는 상단메뉴 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" >
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.css">


<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js" ></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.10/dist/sweetalert2.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.js"></script>


<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js" integrity="sha512-d9xgZrVZpmmQlfonhQUvTR7lMPtO7NkZMkA0ABN3PHCbKA5nqylQ/yWlFAyY6hYgdF1Qh6nYiuADWwKB4C2WSw==" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.bundle.js" integrity="sha512-zO8oeHCxetPn1Hd9PdDleg5Tw1bAaP0YmNvPY8CwcRyUk7d7/+nyElmFrB6f7vg4f7Fv4sui1mcep8RIEShczg==" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.bundle.min.js" integrity="sha512-SuxO9djzjML6b9w9/I07IWnLnQhgyYVSpHZx0JV97kGBfTIsUYlWflyuW4ypnvhBrslz1yJ3R+S14fdCWmSmSA==" crossorigin="anonymous"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.css" integrity="sha512-C7hOmCgGzihKXzyPU/z4nv97W0d9bv4ALuuEbSf6hm93myico9qa0hv4dODThvCsqQUmKmLcJmlpRmCaApr83g==" crossorigin="anonymous" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.js" integrity="sha512-hZf9Qhp3rlDJBvAKvmiG+goaaKRZA6LKUO35oK6EsM0/kjPK32Yw7URqrq3Q+Nvbbt8Usss+IekL7CRn83dYmw==" crossorigin="anonymous"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.css" integrity="sha512-/zs32ZEJh+/EO2N1b0PEdoA10JkdC3zJ8L5FTiQu82LR9S/rOQNfQN7U59U9BC12swNeRAz3HSzIL2vpp4fv3w==" crossorigin="anonymous" />

<link rel="stylesheet" href="https://uicdn.toast.com/tui.pagination/latest/tui-pagination.css" />
<script src="https://uicdn.toast.com/tui.pagination/latest/tui-pagination.js"></script>
<link rel="stylesheet" href="https://uicdn.toast.com/tui-grid/latest/tui-grid.css"/>
 <script src="https://uicdn.toast.com/tui-grid/latest/tui-grid.js"></script>	

<link rel="stylesheet" href="<?$root_dir?>/css/style.css">
  
<script src="http://j-techel.co.kr/common.js"></script>  
    
  </head>
 
<body>


<style>

	.fixed-table {
		position: sticky;
		top: 0;
		background-color: #fff;
		z-index: 1;
		margin-bottom: 10px;
		box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
	}
	
    .form-group {
        display: flex;
        justify-content: center;
    }

    .form-group input {
        margin: auto;
		text-align : center;
    }	


/* 우측배너 제작 */

.toastify-content {
    font-size: 40px;
}

.card-title {
	font-size: 40px; /* or any other size that you want */
}

.card-body {
	font-size: 25px; /* or any other size that you want */
}

.form-group label {
	font-size: 30px; /* or any other size that you want */
}

.form-group input {
	font-size: 20px; /* or any other size that you want */
	width : calc(100vw - 65vw) ;
}

.table th, .table tr, .table td {
	font-size: 20px; /* or any other size that you want */
}



.table td input{
        font-size: 20px; /* or any other size that you want */
    }

	
@media (max-width: 1000px) {
    .card-title {
        font-size: 40px; /* or any other size that you want */
    }
	
    .card-body {
        font-size: 30px; /* or any other size that you want */
    }

    .form-group label {
        font-size: 30px; /* or any other size that you want */
    }

    .form-group input {
        font-size: 30px; /* or any other size that you want */
    }
	
    .form-group select {
        font-size: 30px; /* or any other size that you want */
    }

    .table th, .table tr, .table td {
        font-size: 28px; /* or any other size that you want */
		padding : 2px;
    }
	
	.table td input{
        font-size: 28px; /* or any other size that you want */
	
    }	


}
		
    th, td{
        vertical-align: middle;
    }

</style>	
	

 <input type="hidden" id="num" name="num" value="<?=$num?>" >	

<div class="container-fluid">
    <div class="row justify-content-center align-items-center w-100 vh-100">
        <div class="col-sm-12 text-center">
            <div class="card align-middle justify-content-center w-100" style="border-radius: 20px;">
                <div class="card-body">
					<div class="form-group mt-5 mb-5 fs-1">
                    <span class="card-title mb-5" style="color: #113366; font-size:60px; ">  메뉴 선택  </span> 
					</div>
					
					
					<div class="form-group mt-5 mb-5">
						 
					<button type="button" id="statisticsBtn" class="btn btn-success btn-lg fs-1">사용 통계</button> &nbsp;&nbsp;&nbsp;
					<button type="button" id="updateBtn" class="btn btn-primary  btn-lg fs-1">회원 정보수정</button>&nbsp;&nbsp;&nbsp;
					
					
					
					<button type="button" class="btn btn-secondary  btn-lg  closeBtn fs-1">닫기</button>
					
					</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script> 

	
// 이전 데이터 저장을 위한 변수
var previousData = null;

function reload(data) {
   	   		    
}



$(document).ready(function(){
	
	$(".closeBtn").click(function(){    // 창닫기	

		 opener.location.reload();		
		 window.close();	
		 
	 });

					
	$("#statisticsBtn").click(function(){      
		var num = "<?php echo $num; ?>";
		
		popupCenter('./guest_statistics.php?num=' + num , '사용통계', 1050, 900);
			

	 }); 					
	$("#updateBtn").click(function(){      
		var num = "<?php echo $num; ?>";
		
		popupCenter('./guest_write_form.php?num=' + num , '누계수정', 1050, 900);
			

	 }); 
		 

}); // end of ready document


</script>

</body>
</html>
