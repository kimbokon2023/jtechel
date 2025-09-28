<?php 
// 환경파일 읽어오기 (테이블명 작업 폴더 등)
session_start(); 
ini_set('display_errors','1');  // 화면에 warning 없애기	  1은 보이기

// 지점 선택 헬퍼 사용
require_once($_SERVER['DOCUMENT_ROOT'] . "/lib/mydb.php");
$pdo = db_connect();
require_once('branch_select_helper.php');
$branch = getBranchFromCookie($pdo);

$level= $_SESSION["level"];
$user_name= $_SESSION["name"];
$id= $_SESSION["userid"]; 

 if(!isset($_SESSION["level"]) || $_SESSION["level"]>5) {
          /*   alert("관리자 승인이 필요합니다."); */
		 sleep(1);
         header("Location:http://j-techel.co.kr/game/login/login_form.php"); 
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
   									  
isset($_REQUEST["num"])  ? $num=$_REQUEST["num"] :   $num=''; 
require_once($_SERVER['DOCUMENT_ROOT'] . "/lib/mydb.php");
$pdo = db_connect();	

// 별명 불러오기
 try{
	  if($branch =='') 
		     $branch = '아우디';
	  $sql = "select * from jtechel.game_alias where branch='$branch'  ";
	  $stmh = $pdo->query($sql);       
      $stmh->execute();
      $count = $stmh->rowCount();            
	  $row = $stmh->fetch(PDO::FETCH_ASSOC);  // $row 배열로 DB 정보를 불러온다.		
		 
	  $alias_arr = explode(",", $row['alias']);
	  
	 }catch (PDOException $Exception) {
	   print "오류: ".$Exception->getMessage();
	 }
	 
// 기존 자료 불러오기	 

if($num!=='')
{
	 try{
		  $sql = "select * from jtechel.game where num = ?  ";
		  $stmh = $pdo->prepare($sql); 
		  $stmh->bindValue(1,$num,PDO::PARAM_STR); 
		  $stmh->execute();
		  $count = $stmh->rowCount();            
		  $row = $stmh->fetch(PDO::FETCH_ASSOC);  // $row 배열로 DB 정보를 불러온다.		
			 
		  include 'rowDB.php';
		  
		  $input_arr_val = explode(',', $input_arr);
		  $input_plus = explode(',', $input_plus);
		  $input_minus = explode(',', $input_minus);
		  $dispose_plus = explode(',', $dispose_plus);
		  $dispose_minus = explode(',', $dispose_minus);		  		  			 
		  
		 }catch (PDOException $Exception) {
		   print "오류: ".$Exception->getMessage();
		 }	 
		 
		// 별명 불러오기
 try{
	  $sql = "select * from jtechel.game_alias where branch='$branch'  ";
	  $stmh = $pdo->query($sql);       
      $stmh->execute();
      $count = $stmh->rowCount();            
	  $row = $stmh->fetch(PDO::FETCH_ASSOC);  // $row 배열로 DB 정보를 불러온다.		
		 
	  $alias_arr = explode(",", $row['alias']);
	  
	 }catch (PDOException $Exception) {
	   print "오류: ".$Exception->getMessage();
	 } 
		 
		 
		 
		 
		 
		 
}

 // var_dump($alias);
  // var_dump($input_plus);
  // var_dump($input_minus);

?>

<!doctype html>

<html lang="ko">
<head>
<!-- Required meta tags -->
<meta charset="utf-8">


<title>YH 시스템 기계 관리</title>

<?php
$root_dir = $_SERVER['DOCUMENT_ROOT'] ;

?>


<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">

<!-- viewport
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />	
 -->
 
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>

<!-- 화면에 UI창 알람창 -->
<link href="
https://cdn.jsdelivr.net/npm/sweetalert2@11.7.10/dist/sweetalert2.min.css" rel="stylesheet">

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
<script src="http://j-techel.co.kr/js/date.js"></script>   <!-- 기간을 설정하는 관련 js 포함 -->

</head>

<style>
/* Modern Design System */
:root {
	--primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	--secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
	--success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
	--glass-bg: rgba(255, 255, 255, 0.15);
	--glass-border: rgba(255, 255, 255, 0.2);
	--shadow-soft: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
	--shadow-hover: 0 12px 40px 0 rgba(31, 38, 135, 0.5);
	--border-radius: 16px;
	--text-primary: #2d3748;
	--text-secondary: #4a5568;
	--bg-overlay: rgba(255, 255, 255, 0.9);
}

/* Glassmorphism Background */
body {
	background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
	background-size: 400% 400%;
	animation: gradientShift 15s ease infinite;
	min-height: 100vh;
}

@keyframes gradientShift {
	0% { background-position: 0% 50%; }
	50% { background-position: 100% 50%; }
	100% { background-position: 0% 50%; }
}

/* Modern Glass Containers */
.container-fluid {
	background: var(--glass-bg);
	border: 1px solid var(--glass-border);
	box-shadow: var(--shadow-soft);
	backdrop-filter: blur(10px);
	-webkit-backdrop-filter: blur(10px);
	border-radius: var(--border-radius);
	margin: 20px;
	padding: 30px;
}

/* Modern Select Dropdown */
.modern-select {
	background: var(--glass-bg);
	border: 2px solid var(--glass-border);
	border-radius: 12px;
	padding: 12px 20px;
	color: var(--text-primary);
	font-weight: 600;
	backdrop-filter: blur(10px);
	-webkit-backdrop-filter: blur(10px);
	transition: all 0.3s ease;
	box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.modern-select:focus {
	border-color: #667eea;
	box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.3);
	outline: none;
}

/* Enhanced Buttons */
.btn {
	border-radius: 25px;
	padding: 12px 30px;
	font-weight: 600;
	transition: all 0.3s ease;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

.btn-dark {
	background: var(--primary-gradient);
	border: none;
	box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.btn-dark:hover {
	transform: translateY(-2px);
	box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
}

.btn-danger {
	background: var(--secondary-gradient);
	border: none;
	box-shadow: 0 4px 15px rgba(240, 147, 251, 0.4);
}

.btn-danger:hover {
	transform: translateY(-2px);
	box-shadow: 0 6px 20px rgba(240, 147, 251, 0.6);
}

.btn-secondary {
	background: var(--glass-bg);
	border: 2px solid var(--glass-border);
	color: var(--text-primary);
	backdrop-filter: blur(10px);
	-webkit-backdrop-filter: blur(10px);
}

.btn-secondary:hover {
	background: rgba(255, 255, 255, 0.3);
	transform: translateY(-1px);
	box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

/* Modern Tables */
.table {
	background: var(--bg-overlay);
	border-radius: var(--border-radius);
	overflow: hidden;
	box-shadow: var(--shadow-soft);
	backdrop-filter: blur(10px);
	-webkit-backdrop-filter: blur(10px);
	border: none;
}

.table thead {
	background: var(--primary-gradient) !important;
}

.table thead th {
	border: none;
	padding: 20px;
	font-weight: 600;
	text-transform: uppercase;
	letter-spacing: 0.5px;
	color: white !important;
}

.table tbody tr {
	transition: all 0.3s ease;
	border: none;
}

.table tbody tr:hover {
	background-color: rgba(102, 126, 234, 0.1);
	transform: scale(1.002);
}

.table tbody td {
	border: none;
	padding: 15px;
	vertical-align: middle;
}

/* Input Group Styling */
.input-group {
	background: var(--glass-bg);
	border-radius: 12px;
	padding: 8px 16px;
	margin: 8px;
	backdrop-filter: blur(10px);
	-webkit-backdrop-filter: blur(10px);
	box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.text-secondary {
	color: var(--text-secondary) !important;
	font-weight: 600;
}

/* Enhanced Form Inputs */
.custom-input, .form-control {
	background: rgba(255, 255, 255, 0.8);
	border: 1px solid var(--glass-border);
	border-radius: 8px;
	transition: all 0.3s ease;
	backdrop-filter: blur(5px);
}

.custom-input:focus, .form-control:focus {
	border-color: #667eea;
	box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
	outline: none;
	background: rgba(255, 255, 255, 0.95);
}

/* Typography Enhancements */
h1 {
	color: white;
	text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
	font-weight: 700;
}

.fs-1 {
	color: white;
	text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
	font-weight: 600;
}

/* Animation Utilities */
.fade-in {
	animation: fadeIn 0.8s ease-in;
}

@keyframes fadeIn {
	from { opacity: 0; transform: translateY(20px); }
	to { opacity: 1; transform: translateY(0); }
}

.slide-up {
	animation: slideUp 0.6s ease-out;
}

@keyframes slideUp {
	from { transform: translateY(50px); opacity: 0; }
	to { transform: translateY(0); opacity: 1; }
}

 table {
    table-layout: fixed;
    width: 100%;
  }
  
  td {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }  
  
  th, td {
    text-align: center;
    padding: 10px;
  }	
  
  td .custom-input {
    width: 100%;
    box-sizing: border-box;
    /* 원하는 추가 스타일 지정 */
  }
	

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
.list-cell {
	font-size: 25px; /* or any other size that you want */
	font-weight:normal;
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
	font-size: 25px; /* or any other size that you want */
	width : calc(100vw - 65vw) ;
}

.table th, .table tr, .table td {
	font-size: 18px; /* or any other size that you want */
}


.table td input{
        font-size: 16px; /* or any other size that you want */
    }

.form-check-label, .form-check-input {
	font-size: 35px; /* or any other size that you want */		
}  
.input-group {
	font-size: 20px; /* or any other size that you want */		
}  

input {
	font-size: 20px; /* or any other size that you want */		
	height: 40px;
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


    .table th, .table tr {
        font-size: 22px; /* or any other size that you want */
        height: 70px; /* or any other size that you want */
		padding:1px;
    }
	

    .table td {
        font-size: 30px; /* or any other size that you want */    
		padding:1px;
    }
	
	.table td input{
        font-size: 35px; /* or any other size that you want */		
		
    }	

    .input-group {
        font-size: 30px; /* or any other size that you want */		
    }  
	
	input {
		font-size: 30px; /* or any other size that you want */		
		height: 35px;
	}  
		
 
	
}

 td, th {
    vertical-align: middle;
  }
  tr {
    vertical-align: baseline;
  }


  input[type="text"] {
    text-align: left !important ;
  }
  
  input[type="number"] {
    text-align: left !important ;
  }

  .table-fixed {
    table-layout: fixed;
    width: 100%;
  }

</style>	

<body>
    <?php
    $fromdate = isset($_GET["fromdate"]) ? $_GET["fromdate"] : '';
    $todate = isset($_GET["todate"]) ? $_GET["todate"] : '';
    $amountType = isset($_GET["amountType"]) ? $_GET["amountType"] : 'input';
	

    
       $sql = "select * from jtechel.game where branch = '$branch' " ;
    
        require_once("../lib/mydb.php");
        $pdo = db_connect();
    
        // 데이터 가져오기
        $inputArr = array();
        $outputArr = array();
    
        try {
            $stmh = $pdo->query($sql);
            while ($row = $stmh->fetch(PDO::FETCH_ASSOC)) {
                $inputArr[] = explode(",", $row['input_arr']);
                $outputArr[] = explode(",", $row['output_arr']);
            }
        } catch (PDOException $Exception) {
            print "오류: " . $Exception->getMessage();
        }
		

	$stmh = $pdo->query($sql);
	while ($row = $stmh->fetch(PDO::FETCH_ASSOC)) {
							
				$in = explode(",", $row['input_arr']); // input_arr 분리
				$out = explode(",", $row['output_arr1']); // output_arr 분리
				$input_arr = array(); // $input_arr 초기화

				for ($j = 0; $j < count($in); $j++) {
					// 요소를 숫자로 변환하여 저장
					$input = intval($in[$j]);
					$output = intval($out[$j]);

					// 첫 번째 배열에서 두 번째 배열값을 뺀 결과를 $input_arr에 저장
					$input_arr[$j] = $input - $output;
				}
			break;
		}
			
	if ($input_arr !== null && array_sum($input_arr) != 0) {
		$date = substr($row['registedate'], 2);
		// echo "<th scope='col' class='text-center bg-secondary text-white'>" . $date . "</th>";
	}

			
	
						
						
    
    ?>

   <form id="board_form" name="board_form" class="form-signin" method="post">
        <div class="container-fluid justify-content-center align-items-center mt-5 mb-2">                    
                 
                      
							<input type="hidden" id="mode" name="mode" value="<?=$mode?>">
							<input type="hidden" id="num" name="num" value="<?=$num?>" >                        
							<input type="hidden" id="user_name" name="user_name" value="<?=$user_name?>" size="4" >
							<input type="hidden" id="mcno" name="mcno[]" value="<?=$mcno?>" size="4" >
							<input type="hidden" id="inputsum" name="inputsum"  size="4" >							
							<input type="hidden" id="outputsum" name="outputsum" value="" size="4" >														
							<input type="hidden" id="mcount" name="mcount"  >                
							<input type="hidden" id="item" name="item"  >                
							<input type="hidden" id="registedate" name="registedate"  >      
							<input type="hidden" id="updatetime" name="updatetime" value="<?=$updatetime?>" >							
							<input type="hidden" id="input_minus2" name="input_minus2" value="" >							
							<input type="hidden" id="input_minus3" name="input_minus3" value="" >							
							<input type="hidden" id="input_minus4" name="input_minus4" value="" >									
            
        </div>

    <div class="container-fluid justify-content-center align-items-center mt-5 mb-2 fade-in">
        <div class="d-flex mb-1 mt-5 justify-content-center align-items-center">
            <div class="input-group p-3 mb-3 justify-content-center align-items-center">
                <span class="text-secondary fs-1 mt-1 mb-2">
					<i class="bi bi-gear-fill"></i> 기계별 총투입/배출 조정
				</span>
                &nbsp;&nbsp;
				
			<div class="input-group p-3 justify-content-center align-items-center">
				   <span class="text-secondary me-3">
					   <i class="bi bi-geo-alt-fill"></i> 지점선택
				   </span>
				   <select name="branch" id="branch" class="modern-select fs-2 p-2" data-theme="primary">
					   <?php
					   // 데이터베이스에서 동적 지점 선택 구현
					   echo renderBranchSelect($pdo, $branch, false, intval($_SESSION["level"]));
					   ?>
						</select>
			  </div>				
				
				
				
				
				<h1>
					<button type="button" class="btn btn-dark btn-lg saveBtn">
						<i class="bi bi-save"></i> 저장
					</button>
					<button type="button" class="btn btn-danger btn-lg delBtn">
						<i class="bi bi-trash"></i> 삭제
					</button>
					<button type="button" class="btn btn-secondary btn-lg closeBtn">
						<i class="bi bi-x-circle"></i> 닫기
					</button>
				</h1>
            </div>
        </div>

		<div class="col-sm-12 slide-up">
            <div class="table">
                <table class="table table-bordered table-fixed">
                <thead>
                    <tr>
                        <th scope="col" style="width:10%;"  class="text-center bg-secondary text-white" > 지점  </th>
                        <th scope="col" style="width:10%;"  class="text-center bg-secondary text-white" > No </th>
                         <th scope="col" style="width:10%;"  class="text-center bg-secondary text-white" > 별명 </th>
						<th scope='col' class='text-center bg-secondary text-white' > 총투입합 </th>
						<th scope='col' class='text-center bg-secondary text-white' > 투입 더함 </th>
						<th scope='col' class='text-center bg-secondary text-white' > 투입 뺌 </th>
						<th scope='col' class='text-center bg-secondary text-white' > 총배출합 </th>
						<th scope='col' class='text-center bg-secondary text-white' > 배출 더함 </th>
						<th scope='col' class='text-center bg-secondary text-white' > 배출 뺌 </th>
                       
                    </tr>
                </thead>
              <tbody>
<?php
try {
	// 각 기계의 합계를 저장할 배열 선언
	$machineSums = array_fill(0, 150, 0);
	$machineSumsOutput = array_fill(0, 150, 0);
		
for ($i = 0; $i < 150; $i++) {
    $stmh = $pdo->query($sql);
    $is_row_empty = true;
    $row_values = array();
while ($row = $stmh->fetch(PDO::FETCH_ASSOC)) {
    $input_arr_tmp = explode(",", $row['input_arr']);
    $output_1 = explode(",", $row['output_arr1']);
    $output_2 = explode(",", $row['output_arr2']);
    $output_3 = explode(",", $row['output_arr3']);
    $output_4 = explode(",", $row['output_arr4']);
    $output_5 = explode(",", $row['output_arr5']);
	
    $input_plus_val = explode(",", $row['input_plus']);        
    $input_minus_val = explode(",", $row['input_minus']);        
    $dispose_plus_val = explode(",", $row['dispose_plus']);        
    $dispose_minus_val = explode(",", $row['dispose_minus']);        
    
	// 투입부분은 전체 투입량 + 투입 plus - 투입 minus
	// 배출부분은 전체 배출량 + 배출 plus - 배출 minus
    $outputsums = intval($output_1[$i]) + intval($output_2[$i]) + intval($output_3[$i]) + intval($output_4[$i]) + intval($output_5[$i])	+ intval($dispose_plus_val[$i])	- intval($dispose_minus_val[$i])		;
    $machine_val = (intval($input_arr_tmp[$i]) + intval($input_plus_val[$i]) - intval($input_minus_val[$i]));
	
    if ($machine_val != 0) {
        $is_row_empty = false;
    }

    if (is_numeric($machine_val) && $machine_val !== '' && $machine_val !== null) {
        $machineSums[$i] += $machine_val;
    }
	// 배출합 계산
    if (is_numeric($outputsums) && $outputsums !== '' && $outputsums !== null) {
        $machineSumsOutput[$i] += $outputsums;
    }
}




    echo "<tr>";
    echo "<td class='text-center text-secondary'>" . $branch . "</td>"; // 기계 번호 열
    echo "<td class='text-center text-primary'>" . ($i + 1) . "</td>"; // 기계 번호 열
    echo '<td class="text-center text-success " ><input type="text" name="alias[]" class="form-control  text-success" value="' . $alias_arr[$i] . '"  style="text-align:center;" ></td>';
    
    // 총투입 유효성 검사 후 숫자인 경우에만 출력
    echo "<td class='text-center'>" . (is_numeric($machineSums[$i]) ? number_format($machineSums[$i]) : '') . "</td>";
    
    echo '<td><input type="number" name="input_plus[]" inputmode="numeric" pattern="[0-9]{1,4}"  class="custom-input text-center input_plus" value="' . $input_plus[$i] . '"></td>';
    echo '<td><input type="number" name="input_minus[]" inputmode="numeric" pattern="[0-9]{1,4}"  class="custom-input text-center input_minus" value="' . (isset($input_minus[$i]) ? $input_minus[$i] : '') . '"></td>';    
	
	// 총배출 유효성 검사 후 숫자인 경우에만 출력
    echo "<td class='text-center'>" . (is_numeric($machineSumsOutput[$i]) ? number_format($machineSumsOutput[$i]) : '') . "</td>";
    
    echo '<td><input type="number" name="dispose_plus[]" inputmode="numeric" pattern="[0-9]{1,4}"  class="custom-input text-center dispose_plus" value="' . $dispose_plus[$i] . '"></td>';
    echo '<td><input type="number" name="dispose_minus[]" inputmode="numeric" pattern="[0-9]{1,4}"  class="custom-input text-center dispose_minus" value="' . (isset($dispose_minus[$i]) ? $dispose_minus[$i] : '') . '"></td>';
    
    echo "</tr>";
}

    } catch (PDOException $Exception) {
        print "오류: " . $Exception->getMessage();
    }
    ?>
</tbody>

            </table>
        </div>
	  </div>
    </div>
    </form>	
	

	
	
</body>








</html>



<script> 

 
 document.querySelectorAll('input[type="number"]').forEach(function(input) {
    input.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
        calculateTotals();
    });

    input.addEventListener('blur', function() {
        calculateTotals();
    });
});

    // 합계 계산 및 차액 계산
    function calculateTotals() {

    }

    // 페이지 로드 시 합계 초기화
    calculateTotals();
</script>

</body>
</html>

<script> 

$(document).ready(function(){
var state =  $('#state').val();  	
// 처리완료인 경우는 수정하기 못하게 한다.

document.getElementById("branch").addEventListener("change", function() {
    // 이곳에 선택 변경 시 실행할 자바스크립트 코드를 작성합니다.
    // 예: 선택된 값에 따라 다른 동작을 수행하거나 AJAX 요청을 보내는 등의 작업을 수행할 수 있습니다.    
	document.cookie = "branch=" + this.value ;    
    // 추가적인 동작을 수행하도록 코드를 작성합니다.
	location.reload();
  });
	 




$("#closeModalBtn").click(function(){ 
    $('#myModal').modal('hide');
});
	
	
$(".closeBtn").click(function(){    // 저장하고 창닫기	
		opener.location.reload();		
		self.close();	
		
	 });	
				
$(".saveBtn").click(function(){      // DATA 저장버튼 누름
	var num = $("#num").val();  	    
    var user_name = $("#user_name").val(); 
	
	// 작업시간 기록
	// JavaScript 코드
		var time = new Date();
		var hours = time.getHours();
		var minutes = time.getMinutes();
		var seconds = time.getSeconds();

		// 시간 값을 2자리 숫자로 만들기
		hours = ("0" + hours).slice(-2);
		minutes = ("0" + minutes).slice(-2);
		seconds = ("0" + seconds).slice(-2);

		var timeValue = hours + ":" + minutes + ":" + seconds;
		document.getElementById("updatetime").value = timeValue;	
	
	
	// 누계수정, registedate는 자동으로 오늘 날짜 넣는다.
	var today = new Date();
	var year = today.getFullYear();
	var month = String(today.getMonth() + 1).padStart(2, '0');
	var day = String(today.getDate()).padStart(2, '0');

	var formattedDate = year + '-' + month + '-' + day;
	$("#registedate").val(formattedDate); // 오늘날짜 기록
	  $("#item").val('누계수정');
	
	$("#num").val()
			
var inputValues = [];
var dispose_inputValues = [];
var outputValues = [];
var dispose_outputValues = [];

var inputElements = document.getElementsByName('input_plus[]');
var dispose_inputElements = document.getElementsByName('dispose_plus[]');
var outputElements = document.getElementsByName('input_minus[]');
var dispose_outputElements = document.getElementsByName('dispose_minus[]');

for (var i = 0; i < 150; i++) {
  var inputValue = Number(inputElements[i].value);
  var dispose_inputValue = Number(dispose_inputElements[i].value);
  var outputValue = Number(outputElements[i].value);
  var dispose_outputValue = Number(dispose_outputElements[i].value);
  
  inputValues.push(inputValue);
  dispose_inputValues.push(dispose_inputValue);
  outputValues.push(outputValue);
  dispose_outputValues.push(dispose_outputValue);
  
  // outputElements[i].value = '';  // 이것때문에 오류남
}

// inputValues의 합계 계산
var inputSum = inputValues.reduce(function (total, value) {
  if (isNaN(value)) {
    return total + 0;  // 공백인 경우 0으로 처리
  } else {
    return total + value;
  }
}, 0);
var dispose_inputSum = dispose_inputValues.reduce(function (total, value) {
  if (isNaN(value)) {
    return total + 0;  // 공백인 경우 0으로 처리
  } else {
    return total + value;
  }
}, 0);

// outputValues의 합계 계산
var outputSum = outputValues.reduce(function (total, value) {
  if (isNaN(value)) {
    return total + 0;  // 공백인 경우 0으로 처리
  } else {
    return total + value;
  }
}, 0);
var dispose_outputSum = dispose_outputValues.reduce(function (total, value) {
  if (isNaN(value)) {
    return total + 0;  // 공백인 경우 0으로 처리
  } else {
    return total + value;
  }
}, 0);

// 합계를 form 요소에 저장
document.getElementsByName('inputsum')[0].value = inputSum - outputSum ;
document.getElementsByName('outputsum')[0].value = dispose_inputSum - dispose_outputSum;
			
	var nonEmptyInputValues = inputValues.filter(function (value) {
	  return value !== "" && value !== 0; // 값이 비어있거나 0이 아닌 요소만 필터링
	});

	var mcount = nonEmptyInputValues.length; // inputsum의 값이 존재하는 개수

	// mcount를 form 요소에 저장
	$("#mcount").val(mcount);
	console.log('mcount: ' + mcount);
		
	
   if(Number(num)>0) 
       $("#mode").val('modify');     
      else
          $("#mode").val('insert');  

     // console.log($("#board_form").serialize());
	  
	$.ajax({
		url: "insert_wallet.php",
		type: "post",		
		data: $("#board_form").serialize(),
		// dataType:"json",
		success : function( data ){
			 console.log(data);
		     opener.location.reload();
			myalert("파일 저장!");
			
		  setTimeout(function() {												        
					 window.close();	
				   }, 1000);			
		   		
		},
		error : function( jqxhr , status , error ){
			console.log( jqxhr , status , error );
		} 			      		
	   });		
			
 }); 
		 
$(".delBtn").click(function(){      // del
	var num = $("#num").val();    
	var state = $("#state").val();  
	var user_name = $("#user_name").val();  
	   
	// 결재상신이 아닌경우 수정안됨	
	   $("#mode").val('delete');     
	   

		// DATA 삭제버튼 클릭시
			Swal.fire({ 
				   title: '해당 DATA 삭제', 
				   text: " DATA 삭제는 신중하셔야 합니다. '\n 정말 삭제 하시겠습니까?", 
				   icon: 'warning', 
				   showCancelButton: true, 
				   confirmButtonColor: '#3085d6', 
				   cancelButtonColor: '#d33', 
				   confirmButtonText: '삭제', 
				   cancelButtonText: '취소' })
				   .then((result) => { if (result.isConfirmed) { 
						$.ajax({
								url: "insert_wallet.php",
								type: "post",		
								data: $("#board_form").serialize(),
								dataType:"json",
								success : function( data ){
								console.log( data);
								opener.location.reload();
								myalert("파일 삭제 완료!");
								
								setTimeout(function() {												        
										 window.close();	
									   }, 1000);		
							},
								error : function( jqxhr , status , error ){
									console.log( jqxhr , status , error );
								} 			      		
						   });	





				   
				   } });		   
		  
			
 }); 

}); // end of ready document


function myalert(str) {

 Toastify({
		text: str,
		duration: 3000,
		close:true,
		gravity:"top",
		position: "center",
		backgroundColor: "#4fbe87",
		className: "toastify-content",
	}).showToast();	
	
	setTimeout(function() {
		// 시간지연
		}, 1000);
	
}	 

</script>

