<?php 
// 환경파일 읽어오기 (테이블명 작업 폴더 등)
include 'ini.php';    
session_start(); 
ini_set('display_errors','1');  

// 데이터베이스에서 첫 번째 활성 지점 가져오기
require_once("../lib/mydb.php");
$pdo_temp = db_connect();
try {
    $firstBranchSql = "SELECT branch_name FROM jtechel.branches WHERE status = 'active' ORDER BY sort_order ASC, branch_name ASC LIMIT 1";
    $firstBranchStmt = $pdo_temp->prepare($firstBranchSql);
    $firstBranchStmt->execute();
    $firstBranch = $firstBranchStmt->fetch(PDO::FETCH_ASSOC);
    $defaultBranch = $firstBranch ? $firstBranch['branch_name'] : '서울본사';
} catch (PDOException $e) {
    $defaultBranch = '아우디';
}

$savedbranch = isset($_COOKIE['branch']) ? $_COOKIE['branch'] : $defaultBranch;

$level= intval($_SESSION["level"]);
$user_name= $_SESSION["name"];
$id= $_SESSION["userid"];
$sessionbranch = $_SESSION["branch"];

// 직원들은 해당 지점만 선택가능하게 만든다.
if($level > 3)
	$savedbranch =  $sessionbranch ;	   
 
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
      
// 자정을 넘긴시간에 하나 데이터를 만들어준다.
require_once("../lib/mydb.php");
$pdo = db_connect();

// 그날의 신규 데이터가 있는지 확인
$sql = "SELECT * FROM jtechel.game WHERE registedate = CURDATE() AND item = '신규'";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$rowCount = $stmt->rowCount();
 
if ($rowCount == 0 && date('H:i') >= '00:00') {
    try {
        $pdo->beginTransaction();

        $registedate = date('Y-m-d');
        $item = '신규';
		$branchtmp = $defaultBranch;

        $sql = "INSERT INTO jtechel.game (registedate, item, branch) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(1, $registedate, PDO::PARAM_STR);
        $stmt->bindValue(2, $item, PDO::PARAM_STR);
        $stmt->bindValue(3, $branchtmp, PDO::PARAM_STR);
        $stmt->execute();

        $pdo->commit();
    } catch (PDOException $Exception) {
        $pdo->rollBack();
        print "오류: " . $Exception->getMessage();
    }

    try {
        $pdo->beginTransaction();

        $registedate = date('Y-m-d');
        $item = '신규';
		// 두 번째 지점 가져오기
		try {
		    $secondBranchSql = "SELECT branch_name FROM jtechel.branches WHERE status = 'active' ORDER BY sort_order ASC, branch_name ASC LIMIT 1,1";
		    $secondBranchStmt = $pdo->prepare($secondBranchSql);
		    $secondBranchStmt->execute();
		    $secondBranch = $secondBranchStmt->fetch(PDO::FETCH_ASSOC);
		    $branchtmp = $secondBranch ? $secondBranch['branch_name'] : $defaultBranch;
		} catch (PDOException $e) {
		    $branchtmp = '빅';
		}

        $sql = "INSERT INTO jtechel.game (registedate, item, branch) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(1, $registedate, PDO::PARAM_STR);
        $stmt->bindValue(2, $item, PDO::PARAM_STR);
        $stmt->bindValue(3, $branchtmp, PDO::PARAM_STR);
        $stmt->execute();

        $pdo->commit();
    } catch (PDOException $Exception) {
        $pdo->rollBack();
        print "오류: " . $Exception->getMessage();
    }

    $state = "신규입력";
}
   


?>

<!doctype html>

<html lang="ko">
<head>
<!-- Required meta tags -->
<meta charset="utf-8">


<title>YH 시스템</title>

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
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>

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
<script src="http://j-techel.co.kr/js/date.js"></script>   <!-- 기간을 설정하는 관련 js 포함 -->

</head>

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
.list-cell {
	font-size: 25px; /* or any other size that you want */
	font-weight:normal;
}	


/* 우측배너 제작 */

.sideBanner {
  position: absolute;
  width: calc(100vw - 90vw);
  height:calc(100vh - 70vh);
  top: calc(100vh - 50vh);
  left: calc(100vw - 20vw);  
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
	font-size: 20px; /* or any other size that you want */
}

.sideBanner {
	font-size: 30px; /* or any other size that you want */		
}  

.table td input{
        font-size: 25px; /* or any other size that you want */
    }

.input-group {
	font-size: 25px; /* or any other size that you want */		
}  
input {
	font-size: 25px; /* or any other size that you want */		
	height: 40px;
}  	
	
/* 개선된 반응형 디자인 */
@media (max-width: 768px) {
    
    .container-fluid {
        padding-left: 15px;
        padding-right: 15px;
        overflow-x: hidden !important;
        max-width: 100% !important;
    }
    
    .card-title {
        font-size: 1.5rem;
    }
    
    .card-body {
        font-size: 1rem;
        padding: 15px;
    }

    .form-group label {
        font-size: 1.1rem;
        margin-bottom: 8px;
    }

    .form-group input {
        font-size: 1rem;
        padding: 10px;
        height: auto;
    }

    .table th, .table tr, .table td {
        font-size: 0.9rem;
        padding: 8px 4px;
    }
    
    .table-responsive {
        font-size: 0.85rem;
    }
    
    .table td input {
        font-size: 0.9rem;
        padding: 5px;
    }

    .input-group {
        font-size: 1rem;
        flex-direction: column;
        gap: 10px;
    }  
    
    .input-group-text {
        font-size: 0.95rem;
        text-align: center;
        width: 100%;
    }
    
    input, select {
        font-size: 1rem;
        height: auto;
        padding: 10px;
        border-radius: 5px;
    }  
    
    span {
        font-size: 1rem;
        height: auto;
        line-height: 1.4;
    }
    
    /* 버튼 개선 */
    .btn {
        font-size: 0.9rem;
        padding: 8px 16px;
        margin: 2px;
    }
    
    .btn-lg {
        font-size: 1rem;
        padding: 10px 20px;
    }
    
    /* 제목 및 부제목 반응형 */
    .responsive-title {
        font-size: 1.3rem !important;
    }
    
    .responsive-subtitle {
        font-size: 1rem !important;
    }

    .sideBanner {
        position: relative;
        width: 100%;
        height: auto;
        top: auto;
        left: auto;
        margin: 20px 0;
    }
    
    /* 액션 버튼 그룹 개선 */
    .action-buttons .btn {
        flex: 1;
        min-width: 120px;
        margin: 2px;
    }
    
    /* 버튼 그룹 반응형 */
    .btn-group-responsive .btn {
        flex: 1;
        min-width: 60px;
        font-size: 0.8rem;
    }
}

@media (max-width: 576px) {
    .container-fluid {
        padding-left: 10px;
        padding-right: 10px;
    }
    
    .responsive-title {
        font-size: 1.1rem !important;
    }
    
    .responsive-subtitle {
        font-size: 0.9rem !important;
    }
    
    .table th, .table tr, .table td {
        font-size: 0.8rem;
        padding: 6px 2px;
    }
    
    .btn {
        font-size: 0.8rem;
        padding: 6px 12px;
        margin: 1px;
    }
    
    .btn-lg {
        font-size: 0.9rem;
        padding: 8px 16px;
    }
    
    /* 모바일에서 버튼 그룹 개선 */
    .d-flex.flex-wrap .btn {
        margin: 2px 1px;
        flex: 1;
        min-width: 90px;
    }
    
    /* 검색 및 날짜 입력 개선 */
    .input-group-text {
        font-size: 0.85rem;
        padding: 8px;
    }
    
    input[type="date"], input[type="text"], select {
        font-size: 0.9rem;
        padding: 8px;
    }
    
    /* 액션 버튼 모바일 최적화 */
    .action-buttons .btn {
        min-width: 100px;
        font-size: 0.85rem;
    }
    
    .btn-group-responsive .btn {
        min-width: 50px;
        font-size: 0.75rem;
        padding: 4px 6px;
    }
}
</style>	

<body>

<?php include $root_dir . '/game/myheader.php'; ?>

 <?php
 
include "_request.php"; 


// 오늘 날짜 가져오기
$today = date('Y-m-d');

// SQL 쿼리 준비 - 첫 번째 지점으로 변경
$stmt = $pdo->prepare("SELECT * FROM jtechel.game WHERE branch=:branch and registedate = :today AND item = '신규'");

// SQL 쿼리에 변수 바인딩
$stmt->bindParam(':today', $today);
$stmt->bindParam(':branch', $defaultBranch);

// SQL 쿼리 실행
$stmt->execute();

// 결과가 있다면 버튼을 비활성화, 아니면 활성화
if ($stmt->fetch()) {
    $buttonState = 'disabled';
} else {
    $buttonState = '';
}

// 두 번째 지점 조회를 위한 SQL 쿼리 준비
try {
    $secondBranchSql2 = "SELECT branch_name FROM jtechel.branches WHERE status = 'active' ORDER BY sort_order ASC, branch_name ASC LIMIT 1,1";
    $secondBranchStmt2 = $pdo->prepare($secondBranchSql2);
    $secondBranchStmt2->execute();
    $secondBranch2 = $secondBranchStmt2->fetch(PDO::FETCH_ASSOC);
    $secondBranchName = $secondBranch2 ? $secondBranch2['branch_name'] : $defaultBranch;
} catch (PDOException $e) {
    $secondBranchName = '빅';
}

$stmt = $pdo->prepare("SELECT * FROM jtechel.game WHERE branch=:branch and registedate = :today AND item = '신규'");

// SQL 쿼리에 변수 바인딩
$stmt->bindParam(':today', $today);
$stmt->bindParam(':branch', $secondBranchName);

// SQL 쿼리 실행
$stmt->execute();

// 결과가 있다면 버튼을 비활성화, 아니면 활성화
if ($stmt->fetch()) {
    $buttonState = 'disabled';
} else {
    $buttonState = '';
}
if ($fromdate == "") {
  // $fromdate=substr(date("Y-m-d",time()),0,4) ;
  // $fromdate=date("Y-m-d",time());
  // $fromdate="2023-06-01";
  // 하루전날
  if ($level > 3)
    $fromdate = date("Y-m-d", strtotime("-1 day"));
  else
    $fromdate = date("Y-m-d", strtotime("-6 day"));
}

if ($todate == "") {
  $todate = date("Y-m-d", time());
  $Transtodate = $todate;
} else {
  $Transtodate = strtotime($todate);
  $Transtodate = date("Y-m-d", $Transtodate);
}


if (isset($_REQUEST["find"])) {   //목록표에 제목,이름 등 나오는 부분
  $find = $_REQUEST["find"];
}
$SettingDate = "and registedate ";

$common = $SettingDate . " between date('$fromdate') and date('$Transtodate')";
$a = $common . " order by registedate desc, num desc limit $first_num, $scale";    //내림차순
$b = $common . " order by registedate desc, num desc ";    //내림차순 전체

// 검색을 위해 모든 검색변수 공백제거
$search = str_replace(' ', '', $search);

if ($mode == "search") {
  if ($search == "") {
    $branchCondition = ($savedbranch !== 'false' && $savedbranch !== '전체') ? " and branch = '$savedbranch'" : '';
    $sql = "select * from jtechel.game where 1 $branchCondition";
    $sql .= $a;
    $sqlcon = "select * from jtechel.game where 1 $branchCondition";
    $sqlcon .= $b;
  } elseif ($search != "") {
    $branchCondition = ($savedbranch !== 'false' && $savedbranch !== '전체') ? " and branch = '$savedbranch'" : '';
    $sql = "select * from jtechel.game where ((mcno like '%$search%') or (registedate like '%$search%') or (item like '%$search%')) $branchCondition";
    $sql .= $a; 
    $sqlcon = "select * from jtechel.game where ((mcno like '%$search%') or (registedate like '%$search%') or (item like '%$search%')) $branchCondition";
    $sqlcon .= $b;
  }
} elseif ($mode == "") {
  $branchCondition = ($savedbranch !== 'false' && $savedbranch !== '전체') ? " and branch = '$savedbranch'" : '';
  $sql = "select * from jtechel.game where 1 $branchCondition";
  $sql .= $a;
  $sqlcon = "select * from jtechel.game where 1 $branchCondition";
  $sqlcon .= $b;
}

$nowday = date("Y-m-d");   // 현재일자 변수지정

// print '$sql  <br>';
// print $sql;

		
 ?>
		 

<style>

/* 개선된 반응형 폰트 크기 조정 */
.responsive-title {
	font-size: 2rem;
	font-weight: 600;
	color: #495057;
}

.responsive-subtitle {
	font-size: 1.2rem;
	color: #6c757d;
}

.responsive-refresh-btn {
	padding: 8px 12px;
	border-radius: 6px;
}

/* 컨테이너 최적화 */
.container-fluid {
	overflow-x: hidden !important;
	max-width: 100% !important;
	box-sizing: border-box;
}

/* 🎯 Modern Branch Selection Styles */
.branch-selector-container {
	position: relative;
	background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(248, 250, 252, 0.9));
	border-radius: 20px;
	padding: 20px;
	box-shadow: 
		0 10px 30px rgba(0, 0, 0, 0.1),
		0 4px 8px rgba(0, 0, 0, 0.05);
	border: 1px solid rgba(13, 110, 253, 0.2);
	backdrop-filter: blur(10px);
	-webkit-backdrop-filter: blur(10px);
	transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
	overflow: hidden;
}

.branch-selector-container:hover {
	transform: translateY(-2px);
	box-shadow: 
		0 20px 40px rgba(0, 0, 0, 0.15),
		0 8px 16px rgba(0, 0, 0, 0.08);
	border-color: rgba(13, 110, 253, 0.4);
}

.branch-selector-container::before {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	height: 3px;
	background: linear-gradient(90deg, #667eea, #764ba2, #667eea);
	background-size: 200% 100%;
	animation: shimmer 3s linear infinite;
	border-radius: 20px 20px 0 0;
}

@keyframes shimmer {
	0% { background-position: -200% 0; }
	100% { background-position: 200% 0; }
}

.branch-label {
	display: flex;
	align-items: center;
	justify-content: center;
	margin-bottom: 15px;
	font-weight: 600;
	color: #374151;
	font-size: 1.1rem;
}

.branch-label i {
	color: #667eea;
	font-size: 1.3rem;
	filter: drop-shadow(0 2px 4px rgba(102, 126, 234, 0.3));
}

.branch-text {
	background: linear-gradient(135deg, #667eea, #764ba2);
	-webkit-background-clip: text;
	-webkit-text-fill-color: transparent;
	background-clip: text;
	font-weight: 700;
	letter-spacing: 0.5px;
}

.branch-select {
	width: 100%;
	padding: 12px 50px 12px 20px;
	font-size: 1rem;
	font-weight: 500;
	color: #374151;
	background: rgba(255, 255, 255, 0.9);
	border: 2px solid rgba(13, 110, 253, 0.3);
	border-radius: 15px;
	outline: none;
	appearance: none;
	-webkit-appearance: none;
	-moz-appearance: none;
	cursor: pointer;
	transition: all 0.3s ease;
	position: relative;
	z-index: 2;
}

.branch-select:hover {
	border-color: #667eea;
	background: rgba(255, 255, 255, 1);
	transform: scale(1.02);
}

.branch-select:focus {
	border-color: #667eea;
	background: rgba(255, 255, 255, 1);
	box-shadow: 
		0 0 0 4px rgba(102, 126, 234, 0.15),
		0 8px 20px rgba(102, 126, 234, 0.2);
	transform: scale(1.02);
}

.branch-select option {
	padding: 10px;
	font-weight: 500;
	color: #374151;
	background: #ffffff;
}

.branch-select option:hover,
.branch-select option:checked {
	background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.05));
	color: #667eea;
}

.branch-select-arrow {
	position: absolute;
	right: 35px;
	top: 50%;
	transform: translateY(-50%);
	color: #667eea;
	font-size: 1.2rem;
	pointer-events: none;
	z-index: 3;
	transition: all 0.3s ease;
}

.branch-selector-container:hover .branch-select-arrow {
	transform: translateY(-50%) scale(1.1);
	color: #764ba2;
}

.branch-selector-container.focused {
	border-color: #667eea;
	box-shadow: 
		0 0 0 4px rgba(102, 126, 234, 0.15),
		0 20px 40px rgba(102, 126, 234, 0.2);
}

/* 🎆 Bounce Animation */
@keyframes bounce {
	0%, 20%, 50%, 80%, 100% {
		transform: translateY(0);
	}
	40% {
		transform: translateY(-6px);
	}
	60% {
		transform: translateY(-3px);
	}
}

/* 💫 Loading State */
.branch-select:disabled {
	cursor: not-allowed;
	background: rgba(248, 250, 252, 0.8);
}

/* 🌟 Selection Pulse Effect */
@keyframes selectionPulse {
	0% {
		box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.4);
	}
	70% {
		box-shadow: 0 0 0 10px rgba(102, 126, 234, 0);
	}
	100% {
		box-shadow: 0 0 0 0 rgba(102, 126, 234, 0);
	}
}

/* PC 화면 최적화 - 스크롤 방지 */
@media (min-width: 1200px) {
	html, body {
		overflow-x: hidden !important;
		max-width: 100vw !important;
	}
	
	.responsive-title {
		font-size: 2.2rem;
	}
	.responsive-subtitle {
		font-size: 1.3rem;
	}
	.container-fluid {
		max-width: 1400px;
		margin: 0 auto;
		overflow-x: hidden !important;
		box-sizing: border-box;
	}
}

@media (max-width: 768px) {
	.responsive-title {
		font-size: 1.4rem;
	}
	.responsive-subtitle {
		font-size: 1rem;
	}
	.responsive-refresh-btn {
		padding: 6px 10px;
		font-size: 0.9rem;
	}
	
	/* 📱 Mobile Branch Selection */
	.branch-selector-container {
		padding: 25px 20px;
		border-radius: 18px;
		margin: 0 10px;
	}
	
	.branch-label {
		font-size: 1.3rem;
		margin-bottom: 20px;
	}
	
	.branch-label i {
		font-size: 1.5rem;
		margin-right: 10px;
	}
	
	.branch-text {
		font-size: 1.3rem;
		letter-spacing: 0.8px;
	}
	
	.branch-select {
		padding: 18px 60px 18px 25px;
		font-size: 1.2rem;
		font-weight: 600;
		border-width: 3px;
		border-radius: 18px;
		min-height: 60px;
	}
	
	.branch-select-arrow {
		right: 25px;
		font-size: 1.5rem;
	}
	
	.branch-selector-container:hover {
		transform: translateY(-3px);
	}
}

@media (max-width: 576px) {
	.responsive-title {
		font-size: 1.2rem;
	}
	.responsive-subtitle {
		font-size: 0.9rem;
	}
	.responsive-refresh-btn {
		padding: 5px 8px;
		font-size: 0.8rem;
	}
	
	/* 📱 Extra Small Mobile Branch Selection */
	.branch-selector-container {
		padding: 22px 15px;
		border-radius: 16px;
		margin: 0 5px;
	}
	
	.branch-label {
		font-size: 1.2rem;
		margin-bottom: 18px;
	}
	
	.branch-label i {
		font-size: 1.4rem;
		margin-right: 8px;
	}
	
	.branch-text {
		font-size: 1.2rem;
		letter-spacing: 0.6px;
	}
	
	.branch-select {
		padding: 16px 55px 16px 20px;
		font-size: 1.1rem;
		border-width: 2px;
		border-radius: 16px;
		min-height: 55px;
	}
	
	.branch-select-arrow {
		right: 20px;
		font-size: 1.4rem;
	}
}
</style>
<div class="container-fluid py-3">
	<div class="row">
		<div class="col-12">
			<div class="d-flex mb-3 justify-content-center align-items-center flex-wrap text-center">
				<h1 class="responsive-title mb-0 me-2">기계 투입/배출 자료 리스트</h1>
				<button type="button" class="btn btn-outline-secondary responsive-refresh-btn" title="새로고침" onclick="location.reload();">
					<i class="bi bi-arrow-clockwise"></i>
				</button>
			</div>
			<div class="d-flex mb-3 justify-content-center align-items-center text-center">
				<p class="responsive-subtitle text-primary mb-0">직원은 구분의 '신규'만 수정가능</p>
			</div>
		</div>
	</div>
</div>	 
<div class="container-fluid py-4">  		 
  <form name="board_form" id="board_form"  method="post" action="index.php?mode=search&search=<?=$search?>">  
  
			<input type="hidden" id="done_check_val" name="done_check_val" value="<?=$done_check_val?>" >
			<input type="hidden" id="voc_alert" name="voc_alert" value="<?=$voc_alert?>" size="5" > 	
			<input type="hidden" id="ma_alert" name="ma_alert" value="<?=$ma_alert?>" size="5" > 	
			<input type="hidden" id="order_alert" name="order_alert" value="<?=$order_alert?>" size="5" > 	
			<input type="hidden" id="page" name="page" value="<?=$page?>" size="5" > 	
			<input type="hidden" id="scale" name="scale" value="<?=$scale?>" size="5" > 	
			<input type="hidden" id="yearcheckbox" name="yearcheckbox" value="<?=$yearcheckbox?>" size="5" > 	
			<input type="hidden" id="year" name="year" value="<?=$year?>" size="5" > 	
			<input type="hidden" id="check" name="check" value="<?=$check?>" size="5" > 	
			<input type="hidden" id="output_check" name="output_check" value="<?=$output_check?>" size="5" > 	
			<input type="hidden" id="plan_output_check" name="plan_output_check" value="<?=$plan_output_check?>" size="5" > 	
			<input type="hidden" id="team_check" name="team_check" value="<?=$team_check?>" size="5" > 	
			<input type="hidden" id="measure_check" name="measure_check" value="<?=$measure_check?>" size="5" > 	
			<input type="hidden" id="cursort" name="cursort" value="<?=$cursort?>" size="5" > 	
			<input type="hidden" id="sortof" name="sortof" value="<?=$sortof?>" size="5" > 	
			<input type="hidden" id="stable" name="stable" value="<?=$stable?>" size="5" > 	
			<input type="hidden" id="sqltext" name="sqltext" value="<?=$sqltext?>" > 
			
			<input type="hidden" id="updatetime" name="updatetime" value="<?=$updatetime?>" >			
			
				
	<div class="d-flex mb-1 mt-1 justify-content-center align-items-center">
	</div>	
	 
	<!-- 🎯 Modern Branch Selection -->
	<div class="row mb-4">
		<div class="col-12 col-md-8 col-lg-6 mx-auto">
			<div class="branch-selector-container">
				<div class="branch-label">
					<i class="bi bi-building-fill me-2"></i>
					<span class="branch-text">지점 선택</span>
				</div>
				<select name="branch" id="branch" class="branch-select">
					   <?php		 
					   // 데이터베이스에서 활성화된 지점 목록 가져오기
					   try {
						   $branchSql = "SELECT branch_code, branch_name FROM jtechel.branches WHERE status = 'active' ORDER BY sort_order ASC, branch_name ASC";
						   $branchStmt = $pdo->prepare($branchSql);
						   $branchStmt->execute();
						   $branches = $branchStmt->fetchAll(PDO::FETCH_ASSOC);
						   
						   // 전체 옵션 추가 (관리자만)
						   if($level <= 3) {
							   if($savedbranch == '전체')
								   print "<option selected value='전체'>전체</option>";
							   else   
								   print "<option value='전체'>전체</option>";
						   }
						   
						   // 지점 목록 출력
						   foreach($branches as $branch) {
							   if($savedbranch == $branch['branch_name'])
								   print "<option selected value='" . $branch['branch_name'] . "'>" . $branch['branch_name'] . "</option>";
							   else   
								   print "<option value='" . $branch['branch_name'] . "'>" . $branch['branch_name'] . "</option>";
						   }
					   } catch (PDOException $e) {
						   // 에러 발생 시 기본값 사용
						   $brancharr = array('아우디','빅','전체');
						   for($i=0; $i<count($brancharr); $i++) {
							   if($savedbranch == $brancharr[$i])
								   print "<option selected value='" . $brancharr[$i] . "'>" . $brancharr[$i] . "</option>";
							   else   
								   print "<option value='" . $brancharr[$i] . "'>" . $brancharr[$i] . "</option>";
						   }
					   }
					   ?>	  
				</select>
				<div class="branch-select-arrow">
					<i class="bi bi-chevron-down"></i>
				</div>
			</div>
		</div>
	</div>
	
   <?php if($level=='1') {  ?>
	

			  	
	<div class="d-flex mb-1 mt-2 justify-content-center align-items-center">  
			<div class="input-group p-2 mb-2  justify-content-center align-items-center">	 
				   <span class="text-secondary" >  ▷ 총&nbsp; <span id="total_row"> </span>&nbsp; 개 자료 	</span>&nbsp; &nbsp;  &nbsp; &nbsp;				    
				   <span class="text-secondary" > 화면 목록수 &nbsp; </span>
				   <select name="scaleval" id="scaleval" >
					   <?php		 
								
					   $scalearr = array();
					   array_push($scalearr,'10','20','30','50','100');
					   
					   for($i=0; $i<count($scalearr); $i++) {
								 if($scale==$scalearr[$i])
											print "<option selected value='" . $$scalearr[$i] . "'> " . $scalearr[$i] .   "</option>";
									 else   
							   print "<option value='" . $scalearr[$i] . "'> " . $scalearr[$i] .   "</option>";
						   } 		   
						   

								?>	  
						</select> 	
		     	</div>
	  </div>
	    <?php } ?>
		   <?php if($level=='1') {  ?>
			<div class="d-flex mb-1 mt-2 justify-content-center align-items-center">  		
				
			 &nbsp; &nbsp;
				<button type="button" id="preyear" class="btn btn-secondary btn-lg "   onclick='pre_year()' > 전년도 </button>  &nbsp;  	
				<button type="button" id="three_month" class="btn btn-secondary btn-lg "  onclick='three_month_ago()' > M-3월 </button> &nbsp;  	
				<button type="button" id="prepremonth" class="btn btn-secondary btn-lg "  onclick='prepre_month()' > 전전월 </button>	&nbsp;  
				<button type="button" id="premonth" class="btn btn-secondary btn-lg "  onclick='pre_month()' > 전월 </button>  &nbsp; 	
				<button type="button" class="btn btn-secondary btn-lg "  onclick='yesterday()' > 전일 </button>  &nbsp; 	
				<button type="button" class="btn btn-dark btn-lg "  onclick='javascript:today()' > 당일</button>	&nbsp;  	   
				<button type="button" id="thismonth" class="btn btn-dark btn-lg "  onclick='this_month()' > 당월 </button>	&nbsp;  	   
				<button type="button" id="thisyear" class="btn btn-dark btn-lg "  onclick='this_year()' > 당해년도 </button> &nbsp;  			
			</div>
			<div class="d-flex mb-1 mt-2 justify-content-center align-items-center">  					
			   <span class='input-group-text align-items-center' style='width:400 px;'>  
			   <input type="date" id="fromdate" name="fromdate" size="12" value="<?=$fromdate?>" placeholder="기간 시작일">  &nbsp; 부터 &nbsp;  
			   <input type="date" id="todate" name="todate" size="12"  value="<?=$todate?>" placeholder="기간 끝">  &nbsp;  까지    </span>  &nbsp;

            <?php 
			
			  if($chkMobile)
			  {
				  print '</div>
								<div class="d-flex mb-1 mt-3 justify-content-center align-items-center">  					';
			  }
			  ?>
			   <input type="text" name="search" id="search" style="width:150px;" value="<?=$search?>" onkeydown="JavaScript:SearchEnter();" placeholder="검색어"> 
			    &nbsp; &nbsp; 
				<button type="button" id="searchBtn" class="btn btn-dark  btn-lg "  > 검색 </button>	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				
			
		  
		  <?php } 
		     
			 else
			 {  
		       print '<div class="d-flex mb-5 mt-5 justify-content-center align-items-center">  	';
			 }
		  ?>
			
		<!--	<button type="button" class="btn btn-success btn-lg" onclick="popupCenter('./write_form_member.php', '회원별 투입/배출', 1050, 900)"> 회원별</button> &nbsp; -->
		<!--	<button type="button" class="btn btn-success btn-lg"  onclick="popupCenter('./write.php', 'test', 1050, 900)">t</button> &nbsp; -->
			<div class="action-buttons d-flex flex-wrap gap-2 justify-content-center mt-3">
				<button type="button" class="btn btn-success" onclick="popupCenter('./write_form.php', '투입/배출', 1050, 900)">
					<i class="bi bi-plus-circle me-1"></i>투입/배출
				</button>
				
				<?php if($level=='1') { ?>
				<button id="statistics" type="button" class="btn btn-info">
					<i class="bi bi-bar-chart me-1"></i>기계별통계
				</button>
				<button id="mywallet" type="button" class="btn btn-warning">
					<i class="bi bi-calculator me-1"></i>기계합계 조정
				</button>
				<?php } ?>
			</div>
			
			   </div>
      </div>
	  
<div class="container-fluid">
    <div class="table-responsive">
        <table class="table table-hover table-striped table-bordered">
      <thead class="table-dark">
        <tr>
          <th scope="col" class="text-center">번호</th>
          <th scope="col" class="text-center">지점</th>
          <th scope="col" class="text-center">일자</th>
          <th scope="col" class="text-center">수정</th>
          <th scope="col" class="text-center">구분</th>
          <th scope="col" class="text-center">총투입</th>
          <th scope="col" class="text-center">총배출</th>
          <th scope="col" class="text-center">총지출</th>
          <th scope="col" class="text-center">수익(배10포함)</th>
        </tr>
      </thead>
      
 <?php
	 try{  
	  $allstmh = $pdo->query($sqlcon);         // 검색 조건에 맞는 쿼리 전체 개수
      $temp2=$allstmh->rowCount();  
	  $stmh = $pdo->query($sql);            // 검색조건에 맞는글 stmh
      $temp1=$stmh->rowCount();
	      
	  $total_row = $temp2;     // 전체 글수	  		
         					 
     $total_page = ceil($total_row / $scale); // 검색 전체 페이지 블록 수
	 $current_page = ceil($page/$page_scale); //현재 페이지 블록 위치계산			 
   //   print "$page&nbsp;$total_page&nbsp;$current_page&nbsp;$search&nbsp;$mode"; 
 
 
		  if ($page<=1)  
			$start_num=$total_row;    // 페이지당 표시되는 첫번째 글순번
		     else 
		      	$start_num=$total_row-($page-1) * $scale;
	    
	       while($row = $stmh->fetch(PDO::FETCH_ASSOC)) {
	  
                 include 'rowDB.php';		

								
			$text1_arr = $text1 !== null && $text1 !== '0' ? explode(',', $text1) : [];
			$text2_arr = $text2 !== null && $text2 !== '0' ? explode(',', $text2) : [];
			$text3_arr = $text3 !== null && $text3 !== '0' ? explode(',', $text3) : [];
			$text4_arr = $text4 !== null && $text4 !== '0' ? explode(',', $text4) : [];
			$text5_arr = $text5 !== null && $text5 !== '0' ? explode(',', $text5) : [];
			$text6_arr = $text6 !== null && $text6 !== '0' ? explode(',', $text6) : [];

			$expensesum = 0;			
			$expensesum += intval($text1_arr[0]);
			$expensesum += intval($text2_arr[0]);
			$expensesum += intval($text3_arr[0]);
			$expensesum += intval($text4_arr[0]);
			$expensesum += intval($text5_arr[0]);
			$expensesum += intval($text6_arr[0]);
			   
	
	 if($outdate!="") {
    $week = array("(일)" , "(월)"  , "(화)" , "(수)" , "(목)" , "(금)" ,"(토)") ;
    $outdate = $outdate . $week[ date('w',  strtotime($outdate)  ) ] ;
	
	if($item=='신규' or $item=='배출자르기' )
	  $profit = (int) $inputsum - (int)$outputsum - $expensesum ;
       else
		   $profit = 0;
}
	
 if($item=='배출자르기')
	 $date_font = "text-secondary" ;
   else if($item=='누계수정')
	 $date_font = "text-danger" ;
   else if($item=='신규')
	 $date_font = "text-primary" ;	 
   else if($item=='최초자산')
	 $date_font = "text-dark" ;	   
 
	
?>
			  
<tbody>  
<tr onclick="view('<?=$row["num"]?>','<?=$row["item"]?>');">
  <td class="text-center"><?=$start_num?></td>
  <td class="text-center"><?=$branch?></td>
  <td class="<?=$date_font?> text-center"><?=iconv_substr($row["registedate"], 0, 15, "utf-8")?></td>
  <td class="text-center"><?=iconv_substr($row["updatetime"], 0, 8, "utf-8")?></td>
  <td class="<?=$date_font?> text-center"><?=$row["item"]?></td>
  <td class="text-center"><?=number_format(intval($row["inputsum"]))?></td>
  <td class="text-center"><?=number_format(intval($row["outputsum"]))?></td>
  <td class="text-center"><?=number_format($expensesum)?></td>
  <?php 
    if ($row["item"] === '신규') {
      $profit += intval($row["outputsum"]) * 0.1;
    }
  ?>
  <td class="text-center"><?=number_format($profit)?></td>
</tr>
  
<?php
			$start_num--;  
			 } 
  } catch (PDOException $Exception) {
  print "오류: ".$Exception->getMessage();
  }  
   // 페이지 구분 블럭의 첫 페이지 수 계산 ($start_page)
      $start_page = ($current_page - 1) * $page_scale + 1;
   // 페이지 구분 블럭의 마지막 페이지 수 계산 ($end_page)
      $end_page = $start_page + $page_scale - 1;  
 ?>
  
	</tbody>
</table>
    </div>
</div>
  
 
<div class="row row-cols-auto mt-5 mb-5 justify-content-center align-items-center"> 
 <?php
 
 
   $BigsearchTag = str_replace(' ','|',$Bigsearch);
 
	if($page!=1 && $page>$page_scale){
              $prev_page = $page - $page_scale;    
              // 이전 페이지값은 해당 페이지 수에서 리스트에 표시될 페이지수 만큼 감소
              if($prev_page <= 0) 
              $prev_page = 1;  // 만약 감소한 값이 0보다 작거나 같으면 1로 고정
		      print '<button class="btn btn-outline-secondary  btn-lg" type="button" id=previousListBtn  onclick="javascript:movetoPage(' . $prev_page . ')"> ◀ </button> &nbsp;' ;              
            }
            for($i=$start_page; $i<=$end_page && $i<= $total_page; $i++) {        // [1][2][3] 페이지 번호 목록 출력
              if($page==$i) // 현재 위치한 페이지는 링크 출력을 하지 않도록 설정.
                print '<span class="text-secondary fs-3" >  ' . $i . '  </span>'; 
              else 
                   print '<button class="btn btn-outline-secondary btn-lg" type="button" id=moveListBtn onclick="javascript:movetoPage(' . $i . ')"> ' . $i . '</button> &nbsp;' ;     			
            }

            if($page<$total_page){
              $next_page = $page + $page_scale;
              if($next_page > $total_page) 
                     $next_page = $total_page;
                // netx_page 값이 전체 페이지수 보다 크면 맨 뒤 페이지로 이동시킴
				  print '<button class="btn btn-outline-secondary  btn-lg" type="button" id=nextListBtn onclick="javascript:movetoPage(' . $next_page . ')"> ▶ </button> &nbsp;' ; 
            }
            ?>         
</div>



     </div>   
 
	</form>	 

   
<br>
<br>
<div class="container-fluid">
<? include './footer.php'; ?>
</div>
  
   
<script>


$(document).ready(function() { 



  // 🎯 Enhanced Branch Selection with Modern Effects
  const branchSelect = document.getElementById("branch");
  const branchContainer = document.querySelector('.branch-selector-container');
  const branchArrow = document.querySelector('.branch-select-arrow i');
  
  if (branchSelect && branchContainer) {
    // Branch selection change event
    branchSelect.addEventListener("change", function() {
      // Add selection effect
      branchContainer.style.transform = 'translateY(-4px) scale(1.02)';
      branchContainer.style.boxShadow = '0 25px 50px rgba(102, 126, 234, 0.25)';
      
      // Set cookie and submit form
      document.cookie = "branch=" + this.value;
      
      // Add loading state
      branchSelect.style.opacity = '0.7';
      branchSelect.disabled = true;
      
      // Submit form after brief animation
      setTimeout(() => {
        $("#board_form").submit();
      }, 300);
    });
    
    // Focus effects
    branchSelect.addEventListener('focus', function() {
      branchArrow.style.transform = 'rotate(180deg)';
      branchContainer.classList.add('focused');
    });
    
    branchSelect.addEventListener('blur', function() {
      branchArrow.style.transform = 'rotate(0deg)';
      branchContainer.classList.remove('focused');
    });
    
    // Hover effects for arrow
    branchContainer.addEventListener('mouseenter', function() {
      branchArrow.style.animation = 'bounce 0.6s ease infinite';
    });
    
    branchContainer.addEventListener('mouseleave', function() {
      branchArrow.style.animation = 'none';
    });
  }




$("#total_row").text('<?php echo $total_row; ?>');  // 화면표시 유지

	// 화면보기 변경시 적용  
	$("#scaleval").on("change", function(){
		//selected value
		$("#scale").val($(this).val());
		$("#stable").val('1');  // 화면표시 유지
		$('#board_form').submit();			
		
	});	

$("#searchBtn").click(function(){ 	
	  // page 1로 초기화 해야함
     $("#page").val('1');
	 document.getElementById('board_form').submit();    
 
 });	
 
$("#statistics").click(function(){ 	
    var fromdate = $('#fromdate').val();
    var todate = $('#todate').val();
	
    popupCenter('./statistics.php?fromdate=' + fromdate + '&todate=' + todate, '기간통계', 1050, 900);   
 
 });	

$("#mywallet").click(function(){ 	
    var fromdate = $('#fromdate').val();
    var todate = $('#todate').val();
	
    popupCenter('./mywallet.php?fromdate=' + fromdate + '&todate=' + todate, '기계별 총계', 1050, 900);   
 
 });	

      

}); // end of ready document


function view(num, item) {
	var level =<?php echo $level; ?>;	
	
	if(item == '신규')
	    popupCenter('./write_form.php?num=' + num + '&mode=update', '자료수정', 1050, 900);
	
	if(level==1 && item == '최초자산')
	    popupCenter('./write_form.php?num=' + num + '&mode=update', '자료수정', 1050, 900);
	
	if(level==1 && item == '누계수정')
	    popupCenter('./mywallet.php?num=' + num + '&mode=update', '누계수정', 1050, 900);
	
}

function blinker() {
	$('.blinking').fadeOut(500);
	$('.blinking').fadeIn(500);
}
setInterval(blinker, 1000);

function SearchEnter(){

    if(event.keyCode == 13){
	
    $("#page").val('1');		
	document.getElementById('board_form').submit(); 
    }
}

 

function comma(str) { 
    str = String(str); 
    return str.replace(/(\d)(?=(?:\d{3})+(?!\d))/g, '$1,'); 
} 
function uncomma(str) { 
    str = String(str); 
    return str.replace(/[^\d]+/g, ''); 
}


function movetoPage(page){ 	  
	  $("#page").val(page); 
	 $("#board_form").submit();  
	}		
	
	
function popupCenter(href, pop_name, w, h) {
	var xPos = (document.body.offsetWidth/2) - (w/2); // 가운데 정렬
	xPos += window.screenLeft; // 듀얼 모니터일 때
	var yPos = (document.body.offsetHeight/2) - (h/2);

	window.open(href, pop_name, "width="+w+", height="+h+", left="+xPos+", top="+yPos+", target=_blank , menubar=yes, status=yes, titlebar=yes, resizable=yes");
}
	

</script>




</body>

</html>
