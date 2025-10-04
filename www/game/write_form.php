<?php
// 환경별 기본 URL 설정
require_once '../config/environment.php';

session_start();
$root_dir = '..' ;

ini_set('display_errors','On');  // 화면에 warning 없애기	
error_reporting(E_ALL);

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

require_once("../lib/mydb.php");
$pdo = db_connect();

// defaults for new mode
$main_num = '';
$mode = 'insert';
$isEditMode = false;
$mcno = '';
$inputsum = '';
$outputsum = '';
$updatetime = '';
$memo = '';
$receivable = '';
$item = isset($item) ? $item : '신규';
$text1 = isset($text1) ? $text1 : '';
$text2 = isset($text2) ? $text2 : '';
$text3 = isset($text3) ? $text3 : '';
$text4 = isset($text4) ? $text4 : '';
$text5 = isset($text5) ? $text5 : '';
$text6 = isset($text6) ? $text6 : '';
$checkedRows = isset($checkedRows) ? $checkedRows : '';

$alias_arr =array();	


if($num !== '') {
	$main_num =  $num;
	try{
	  $sql = "select * from jtechel.game where num = ? ";
	  $stmh = $pdo->prepare($sql); 
      $stmh->bindValue(1,$num,PDO::PARAM_STR); 
      $stmh->execute();
      $count = $stmh->rowCount();            
	  $row = $stmh->fetch(PDO::FETCH_ASSOC);  // $row 배열로 DB 정보를 불러온다.		
		 
	  $branch = $row["branch"];
	  
	 }catch (PDOException $Exception) {
	   print "오류: ".$Exception->getMessage();
	 }
}

// 지점 선택 헬퍼 사용
require_once('branch_select_helper.php');
$branch = getBranchFromCookie($pdo);
 

// 별명 불러오기
 try{
	if(empty($branch))
		$branch = '아우디';

	  $sql = "select * from jtechel.game_alias where branch='$branch'  ";
	  $stmh = $pdo->query($sql);
      $count = $stmh->rowCount();            
	  $row = $stmh->fetch(PDO::FETCH_ASSOC);  // $row 배열로 DB 정보를 불러온다.		
		 
	  if (!$row || !isset($row['alias'])) {
		  $alias_arr = array_fill(0, 150, '');
	  } else {
		  $alias_arr = array_pad(explode(",", $row['alias']), 150, '');
	  }
	  
	 }catch (PDOException $Exception) {
	   print "오류: ".$Exception->getMessage();
	 }
	 
if($num !== '') {

 try{
	  $sql = "select * from jtechel.game where num = ? ";
	  $stmh = $pdo->prepare($sql); 
      $stmh->bindValue(1,$num,PDO::PARAM_STR); 
      $stmh->execute();
      $count = $stmh->rowCount();            
	  $row = $stmh->fetch(PDO::FETCH_ASSOC);  // $row 배열로 DB 정보를 불러온다.		
		 
	  include 'rowDB.php';
	  
	 }catch (PDOException $Exception) {
	   print "오류: ".$Exception->getMessage();
	 }
	 
}

if ($num === '') {
  $registedate = date("Y-m-d");
  $mcno = '';
  $inputsum = '';
  $outputsum = '';
}
 else
 {
	// 배열이 150개가 안되면 150개를 맞춘다. 빈배열로 채워넣기 
		  $isEditMode = true;
		$input_arr = explode(',', $input_arr);
		$output_arr1 = explode(',', $output_arr1);
		$output_arr2 = explode(',', $output_arr2);
		$output_arr3 = explode(',', $output_arr3);
		$output_arr4 = explode(',', $output_arr4);
		$output_arr5 = explode(',', $output_arr5);

		$namearr_arr1 = explode(',', $namearr1);
		$namearr_arr2 = explode(',', $namearr2);
		$namearr_arr3 = explode(',', $namearr3);
		$namearr_arr4 = explode(',', $namearr4);
		$namearr_arr5 = explode(',', $namearr5);

		// 데이터 배열의 길이가 150개가 아니라면 빈 문자열을 추가하여 길이를 맞춤
		$input_arr = array_pad($input_arr, 150, '');
		$output_arr1 = array_pad($output_arr1, 150, '');
		$output_arr2 = array_pad($output_arr2, 150, '');
		$output_arr3 = array_pad($output_arr3, 150, '');
		$output_arr4 = array_pad($output_arr4, 150, '');
		$output_arr5 = array_pad($output_arr5, 150, '');

		$namearr_arr1 = array_pad($namearr_arr1, 150, '');
		$namearr_arr2 = array_pad($namearr_arr2, 150, '');
		$namearr_arr3 = array_pad($namearr_arr3, 150, '');
		$namearr_arr4 = array_pad($namearr_arr4, 150, '');
		$namearr_arr5 = array_pad($namearr_arr5, 150, '');

  
   // var_dump($output_arr1);
   // var_dump($namearr_arr5);
		  
		  
$mark_arr = array_fill(0, 150, ''); // 초기값으로 'off'로 배열 생성

$marks = ($mark !== null) ? explode(',', $mark) : [];

for ($i = 0; $i < 150; $i++) {
    if (!empty($marks) && isset($marks[$i]) && $marks[$i] === 'on') {
        $mark_arr[$i] = 'on';
    } else {
        $mark_arr[$i] = '';
    }
}

// $mark_arr 배열 확인
// print_r($mark_arr);

$checkedRows = implode(',', $mark_arr);
	
  $text_arr1 = explode(',', $text1);
  $text_arr2 = explode(',', $text2);
  $text_arr3 = explode(',', $text3);
  $text_arr4 = explode(',', $text4);
  $text_arr5 = explode(',', $text5);
  $text_arr6 = explode(',', $text6);
  
  $text_arr1 = array_map(function($value) {
    return ($value == 0) ? '' : $value;
  }, $text_arr1);
  
  $text_arr2 = array_map(function($value) {
    return ($value == 0) ? '' : $value;
  }, $text_arr2);
  
  $text_arr3 = array_map(function($value) {
    return ($value == 0) ? '' : $value;
  }, $text_arr3);
  
  $text_arr4 = array_map(function($value) {
    return ($value == 0) ? '' : $value;
  }, $text_arr4);
  
  $text_arr5 = array_map(function($value) {
    return ($value == 0) ? '' : $value;
  }, $text_arr5);
  
  $text_arr6 = array_map(function($value) {
    return ($value == 0) ? '' : $value;
  }, $text_arr6);
  
  $mode = "modify";
}

$total0 = 0;
$totalAll = 0;

$total1 = array_sum(explode(',', $text1));
$total2 = array_sum(explode(',', $text2));
$total3 = array_sum(explode(',', $text3));
$total4 = array_sum(explode(',', $text4));
$total5 = array_sum(explode(',', $text5));
$total6 = array_sum(explode(',', $text6));
$total0 = $total1 + $total2 + $total3 + $total4 + $total5 + $total6;
$totalAll = $total1 + $total2 + $total3 + $total4 + $total5 + $total6;

$guest_registedate = date("Y-m-d");

// if($level!==1)
// $item = '신규' ;

// var_dump($branch);
// var_dump($main_num);
// var_dump($mark_arr);
// var_dump($checkedRows);
// var_dump(array_slice($mark_arr, 0, 5));

 
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
					
					
<form id="board_form" name="board_form" class="form-signin" method="post" enctype="multipart/form-data">
<!-- 모달 창을 위한 HTML -->
<div id="nameModal" class="modal fade" role="dialog">
  <div class="modal-dialog modal-dialog-centered " style="max-width: 720px;">
    <div class="modal-content modal-lg">
      <div class="modal-header">
        <h1 class="modal-title">회원 검색</h1>		
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
	  <div class="input-container">
        <input type="text" id="nameSearch" name="nameSearch" class="form-control fs-1 name-input" placeholder="이름, 전화번호로 검색">
		  <span class="clear-input clear1">&#10005;</span>
		</div>
        <div id="nameResult" class="d-flex row fs-1 mb-2 mt-2 p-4 "></div>
      </div>
      <div class="modal-footer">
        <button type="button" id="registerButton" class="btn btn-primary fs-1">등록</button>
        <button type="button" id="nullButton" class="btn btn-danger fs-1">빈칸넣음</button>
        <button type="button" id="closeModalBtn" class="btn btn-default fs-1" data-bs-dismiss="modal">닫기</button>
      </div>
    </div>
  </div>
</div>

<!-- 이름과 전화번호 입력을 위한 모달 창 -->
<div id="inputModal" class="modal fade" role="dialog" >
  <div class="modal-dialog modal-dialog-centered" style="max-width: 720px;">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title"> 회원 이름, 전화번호</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="text" id="guest_registedate" name="guest_registedate" class="form-control fs-1" value="<?=$guest_registedate?>" >
	  <div class="input-container">
        <input type="text" id="guest_name" name="guest_name" class="form-control fs-1 name-input" placeholder="이름">
		  <span class="clear-input clear2">&#10005;</span>
		</div>
	  <div class="input-container">		
        <input type="text" id="guest_tel" name="guest_tel" class="form-control fs-1 name-input" placeholder="전화번호">
		  <span class="clear-input clear3">&#10005;</span>
			</div>
      </div>
      <div class="modal-footer">
        <button type="button" id="saveButton" class="btn btn-primary fs-1">저장</button>
        <button type="button" id="closeInputModalBtn" class="btn btn-default fs-1" data-bs-dismiss="modal">닫기</button>
      </div>
    </div>
  </div>
</div>
   
<style>
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
        font-size: 16px; /* or any other size that you want */
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
        font-size: 35px; /* or any other size that you want */
		padding : 1px;
    }	


}
		
    th, td{
        vertical-align: middle;
    }
	
#table1 {
  border-spacing: 0;
}

#table1 tr {
  border: 4px solid #808080 ;
}

#table1 thead th {
  border: 3px solid;
}

#table2 {
  border-spacing: 0;
}

#table2 tr {
  border: 4px solid;
}

#table2 thead th {
  border: 3px solid;
}

	
/* 모달 창 내부의 Placeholder 스타일 수정 */
.modal input::placeholder,
.modal textarea::placeholder {
   color: rgba(0, 0, 0, 0.4); /* 투명한 회색으로 변경 */
}	
	
	
.input-container {
  position: relative;
}

.clear-input {
  position: absolute;
  top: 50%;
  right: 10px;
  transform: translateY(-50%);
  cursor: pointer;
  color: #999999;
}

.clear-input:hover {
  color: #ff0000;
}	
	
	
#nameResult div:hover {
  cursor: pointer;
}

</style>	
	

<div class="container-fluid">
    <div class="row justify-content-center align-items-center w-100 vh-100">
        <div class="col-lg-9 text-center">
            <div class="card align-middle justify-content-center w-70" style="border-radius: 20px;">
                <div class="card-body">
                    <span class="card-title mb-5" style="color: #113366; ">데이터 입력</span> 
					&nbsp;&nbsp;&nbsp;
					
					<?php if((int)$level===1)   { ?>
						 
					&nbsp;&nbsp;&nbsp;
					<button type="button" class="btn btn-danger delBtn fs-2">삭제</button>
					
					<?php }  ?>
					
					<button type="button" class="btn btn-secondary closeBtn fs-2">닫기</button>
					
					<br>

                      						
							<input type="hidden" id="mode" name="mode" value="<?=$mode?>">
							<input type="hidden" id="num" name="num" value="<?=$main_num?>" >                        
							<input type="hidden" id="user_name" name="user_name" value="<?=$user_name?>" size="4" >
							<input type="hidden" id="mcno" name="mcno[]" value="<?=$mcno?>" size="4" >
							<input type="hidden" id="inputsum" name="inputsum" value="" size="4" >
							<input type="hidden" id="outputsum" name="outputsum" value="" size="4" >
							<input type="hidden" id="mcount" name="mcount"  >
							<input type="hidden" id="updatetime" name="updatetime" value="<?=$updatetime?>" >
							<input type="hidden" id="total0" name="total0" value="<?=$total0?>" >
							<input type="hidden" id="totalAll" name="totalAll" value="<?=$totalAll?>" >
							<input type="hidden" id="checkedRows" name="checkedRows[]"   value="<?=$checkedRows?>">
							<input type="hidden" id="guest_name_arr" name="guest_name_arr[]" >
							<input type="hidden" id="guest_tel_arr" name="guest_tel_arr[]" >
							<input type="hidden" id="checksave" name="checksave" >

					<div class="form-group mt-5 mb-5">
					  <label for="registedate" class="form-control fs-1" style="width:35%;">등록일자</label>
						 <input type="date" id="registedate" name="registedate"   value="<?=$registedate?>" class="form-control fs-1">
					</div>
	<div class="d-flex mb-1 mt-2 justify-content-center align-items-center">  

			<div class="input-group p-2 mb-2  justify-content-center align-items-center">	
					
				   <span class="text-secondary" > 지점선택&nbsp;&nbsp;&nbsp; </span>
				   
				<select name="branch" id="branch" class="fs-2 p-2" style="margin-bottom: 10px; border-width: 5px; border: 2px solid blue; width: 200px;" <?php if (intval($main_num) > 0) echo 'disabled'; ?>>
				  <?php
				  // branches 테이블에서 지점 목록 가져오기
				  require_once('branch_select_helper.php');
				  echo renderBranchSelect($pdo, $branch, false, intval($_SESSION["level"]));
				  ?> 
  
  
</select>

<input type="hidden" name="branch" id="hidden_branch" value="<?=$branch?>"> <!-- name이 두개 있어도 된다고 한다. 마지막에 사용한것이 전송된다는 고 함 chatGPT -->

					 <span class="text-secondary" > 입력구분&nbsp;&nbsp;&nbsp; </span> 
					  <select id="item" name="item"   class="form-control fs-2">
						<option value="신규" <?php if ($item === '신규') echo 'selected'; ?>>신규</option>
						<option value="누계수정" <?php if ($item === '누계수정') echo 'selected'; ?>>누계수정</option>
						<option value="최초자산" <?php if ($item === '최초자산') echo 'selected'; ?>>최초자산</option>						
					  </select>
					</div>
					
					</div>


<?php
// $level 변수의 값에 따라 화면에 보일지 여부를 결정합니다.
$isVisible = ($level == 1 || $level == 3);

// 화면에 보이는 부분
if ($isVisible)  
    echo ' <div id="tableWrapper" > ';	
 else
	 echo ' <div id="tableWrapper" style="display: none;"> ';


?>


<table  id="table1" class="table table-bordered   text-center" >
    <tr>
        <th rowspan="2" class="text-center table-dark"  style="width:60px;">기계</th>
        <th class="text-center"  colspan="1" style="width:100px;">투입합</th>
        <th class="text-center"  colspan="6"><span id="totalInput" class=" text-primary total-amount">0</span></th>
    </tr>        
    <tr>        
		<th class="text-center"  colspan="1">배출합</th>
		<th class="text-center" colspan="6"> <span id="totalOutput" class=" text-danger total-amount">0</span> </th>
    </tr>
    <tr>
		<th rowspan="8" class="text-center  table-dark">지출</th>	
        <th class="text-center">일비</th> 
		
    <?php       
                                
if ($isEditMode) {  			  			         
    for ($j = 0; $j < 6; $j++) {
        $txt_val = isset($text_arr1[$j ]) ? $text_arr1[$j] : '';
        if ($j == 0) { // 첫 번째 요소일 경우
            echo '<td class="text-center" ><input type="text" name="text_arr1[]" inputmode="numeric" pattern="[0-9]{1,4}"   class="form-control text-center  text-amount1" value="' . $txt_val . '" readonly style="background-color: #ccc;"></td>';
        } else { 
            echo '<td class="text-center" ><input type="text" name="text_arr1[]" inputmode="numeric" pattern="[0-9]{1,4}"   class="form-control text-center  text-amount1" value="' . $txt_val . '"></td>';
        }
    }
}
  
		  			
    ?>
		
		
		
    </tr>  
	
	<tr>		
        <th class="text-center">식비</th>
		
    <?php       
                                
                                     
if ($isEditMode) {  			  			         
    for ($j = 0; $j < 6; $j++) {
        $txt_val = isset($text_arr2[$j]) ? $text_arr2[$j] : '';
        if ($j == 0) { // 첫 번째 요소일 경우
            echo '<td class="text-center" ><input type="text" name="text_arr2[]" inputmode="numeric" pattern="[0-9]{1,4}"   class="form-control   text-center  text-amount2" value="' . $txt_val . '" readonly style="background-color: #ccc;"></td>';
        } else { 
            echo '<td class="text-center" ><input type="text" name="text_arr2[]" inputmode="numeric" pattern="[0-9]{1,4}"   class="form-control  text-center text-amount2" value="' . $txt_val . '"></td>';
        }
    }
}
 
		  			
    ?>
		
		
    </tr>
  
	<tr>		
        <th class="text-center">집세 </th>
		
    <?php       
                                
 											   
	if ($isEditMode) {  			  			         
		for ($j = 0; $j < 6; $j++) {
			$txt_val = isset($text_arr3[$j]) ? $text_arr3[$j] : '';
			if ($j == 0) { // 첫 번째 요소일 경우
				echo '<td class="text-center" ><input type="text" name="text_arr3[]" inputmode="numeric" pattern="[0-9]{1,4}"   class="form-control   text-center text-amount3" value="' . $txt_val . '" readonly style="background-color: #ccc;"></td>';
			} else { 
				echo '<td class="text-center" ><input type="text" name="text_arr3[]" inputmode="numeric" pattern="[0-9]{1,4}"   class="form-control  text-center text-amount3" value="' . $txt_val . '"></td>';
			}
		}
	}
                                   

		  			   
		  			
    ?>
		
    </tr>
	
	<tr style="border-bottom: 1px solid!important;">
        <th rowspan="3" class="text-center">서비스 </th>		
    <?php       
	if ($isEditMode) {  			  			         
		for ($j = 0; $j < 6; $j++) {
			$txt_val = isset($text_arr4[$j]) ? $text_arr4[$j] : '';
			if ($j == 0) { // 첫 번째 요소일 경우
				echo '<td rowspan="3" class="text-center" ><input type="text" name="text_arr4[]" inputmode="numeric" pattern="[0-9]{1,4}"  class="form-control  text-center text-amount4" value="' . $txt_val . '" readonly style="background-color: #ccc;"></td>';
			} else { 
				echo '<td class="text-center" ><input type="text" name="text_arr4[]" inputmode="numeric" pattern="[0-9]{1,4}"  class="form-control  text-center text-amount4" value="' . $txt_val . '"></td>';
			}
		}
	}		  			
    ?>		
    </tr>		
	<tr style="border-top: 1px solid!important; border-bottom: 1px solid!important;">	
    <?php       							   
	if ($isEditMode) {  			  			         
		for ($j = 6; $j < 11; $j++) {
			$txt_val = isset($text_arr4[$j]) ? $text_arr4[$j] : '';
				echo '<td class="text-center" ><input type="text" name="text_arr4[]" inputmode="numeric" pattern="[0-9]{1,4}"   class="form-control  text-center text-amount4" value="' . $txt_val . '"></td>';			
		}
	}		  			
    ?>		
    </tr>		
	<tr style="border-top: 1px solid!important; border-bottom: 1px solid!important;">	
    <?php       							   
	if ($isEditMode) {  			  			         
		for ($j = 11; $j < 16; $j++) {
			$txt_val = isset($text_arr4[$j]) ? $text_arr4[$j] : '';
				echo '<td class="text-center" ><input type="text" name="text_arr4[]" inputmode="numeric" pattern="[0-9]{1,4}"   class="form-control  text-center text-amount4" value="' . $txt_val . '"></td>';			
		}
	}		  			
    ?>		
    </tr>		
	<tr>		
        <th class="text-center">간식 </th>
		
    <?php       
                                
if ($isEditMode) {  			  			         
    for ($j = 0; $j < 6; $j++) {
        $txt_val = isset($text_arr5[$j]) ? $text_arr5[$j] : '';
        if ($j == 0) { // 첫 번째 요소일 경우
            echo '<td class="text-center" ><input type="text" name="text_arr5[]" inputmode="numeric" pattern="[0-9]{1,4}"   class="form-control  text-center text-amount5" value="' . $txt_val . '" readonly style="background-color: #ccc;"></td>';
        } else { 
            echo '<td class="text-center" ><input type="text" name="text_arr5[]" inputmode="numeric" pattern="[0-9]{1,4}"   class="form-control  text-center text-amount5" value="' . $txt_val . '"></td>';
        }
    }
}

		  				
    ?>
		
    </tr>		
	<tr>		
        <th class="text-center">기타 </th>
		
    <?php       
                                
if ($isEditMode) {  			  			         
    for ($j = 0; $j < 6; $j++) {
        $txt_val = isset($text_arr6[$j]) ? $text_arr6[$j] : '';
        if ($j == 0) { // 첫 번째 요소일 경우
            echo '<td class="text-center" ><input type="text" name="text_arr6[]" inputmode="numeric" pattern="[0-9]{1,4}"   class="form-control  text-center text-amount6" value="' . $txt_val . '" readonly style="background-color: #ccc;"></td>';
        } else { 
            echo '<td class="text-center" ><input type="text" name="text_arr6[]" inputmode="numeric" pattern="[0-9]{1,4}"   class="form-control  text-center text-amount6" value="' . $txt_val . '"></td>';
        }
    }
}
		  				
    ?>		
	
    </tr>	
	
    <tr>
        <th  colspan="2" class="text-center  table-dark">지출소계 </th>
        <th  colspan="2"> <span id="totalExpense" class="total-amount"><?=number_format($total0)?></span></th>
        <th  colspan="2" class="text-center  table-dark"> 소계(배출포함)</th>
        <th  colspan="2"> <span id="totalExpenseIncludedispose" class="total-amount"><?=number_format($totalAll)?></span></th>
    </tr>
    <tr>
        <th colspan="2" class="text-center  table-dark">잔액</th>
        <th colspan="6"> <span id="difference" class="total-amount">0</span></th>
    </tr>	
</table>	
		<div class="form-group mt-3 mb-3 align-items-center">
				<h3 class="form-signin-heading fs-3  ">메모 </h3> &nbsp;&nbsp;
			 <textarea  style="width:85%;" rows="2"   id="memo" name="memo" class="form-control fs-2"><?=$memo?></textarea>
		</div>
		<div class="form-group mt-3 mb-3 align-items-center">
				<h3 class="form-signin-heading fs-3  ">미수 </h3>&nbsp;&nbsp;
			 <textarea  style="width:85%;"  rows="2"  id="receivable" name="receivable" class="form-control fs-2"><?=$receivable?></textarea>
		</div>
		</div>

		<table id="table2" class="table table-bordered   text-center custom-table" >	
	
			<thead class="table-dark" >
				<tr>
					<th rowspan="3">기계</th>
					<th rowspan="3">별명</th>
					<th rowspan="3">체크</th>
					<th rowspan="2">투입합</th>					
					<th colspan="5">배출상세</th>
				</tr>
				
				<tr>				   
					<th>회원1</th>
					<th>회원2</th>
					<th>회원3</th>
					<th>회원4</th>
					<th>회원5</th>
				</tr>
				<tr>
				<th rowspan="1">배출합</th>										
					<th>배출1</th>
					<th>배출2</th>
					<th>배출3</th>
					<th>배출4</th>
					<th>배출5</th>
				</tr>
			</thead>
		
<tbody >

    <style>
        .mark-check {
            width: 40px;
            height: 40px;
        }
		

		::placeholder {
			color: #9acce3;
		}

		
    </style>

<?php
	for ($i = 1; $i <= 150; $i++) {
			echo '<tr style="border-bottom: 1px solid!important;">';
			echo '<td rowspan="2" class="text-center text-primary">' . $i . '</td>';
			echo '<td  rowspan="2" class="text-center text-success">' . $alias_arr[$i-1] . '</td>';
			  echo '<td rowspan="2" class="text-center"><input type="checkbox" value="on" class="mark-check"';
			  if ($mark_arr[$i-1] === 'on') {
				echo ' checked';
			  }
			  echo '></td>';
	
					
		if ($isEditMode) {
			$input_value = isset($input_arr[$i - 1]) ? $input_arr[$i - 1] : '';
			$output_value1 = isset($output_arr1[$i - 1]) ? $output_arr1[$i - 1] : '';
			$output_value2 = isset($output_arr2[$i - 1]) ? $output_arr2[$i - 1] : '';
			$output_value3 = isset($output_arr3[$i - 1]) ? $output_arr3[$i - 1] : '';
			$output_value4 = isset($output_arr4[$i - 1]) ? $output_arr4[$i - 1] : '';
			$output_value5 = isset($output_arr5[$i - 1]) ? $output_arr5[$i - 1] : '';
			

			$output_arr = array($output_arr1, $output_arr2, $output_arr3, $output_arr4, $output_arr5);
			$namesum_arr = array($namearr_arr1, $namearr_arr2, $namearr_arr3, $namearr_arr4, $namearr_arr5);  // for문을 사용하기 위해 저장함.

			// 변수들을 숫자로 변환한 후 합산
			$sum = intval($output_value1) + intval($output_value2) + intval($output_value3) + intval($output_value4) + intval($output_value5);
										
                echo '<td  class="text-center"><input type="text" name="input_amount[]" inputmode="numeric" pattern="[0-9]{1,4}"  class="form-control input-amount text-center"   style="background-color:#E1F5Fe;  " placeholder="투입" value="' . $input_value . '" ></td>';                
                
                 // 이름불러오기
                for ($j = 0; $j < 5; $j++) {  
					$output_value = isset($namesum_arr[$j][$i - 1]) ? $namesum_arr[$j][$i - 1] : '';				
                    echo '<td class="text-center" ><input type="text" name="namearr_arr' . ($j + 1) . '[]" class="form-control namearr_arr' . ($j + 1) . ' text-center"  value="' . $output_value . '"></td>';
                }
				
				echo '</tr>';
				echo '<tr style="border-top: 1px solid!important;">';
                echo '<td  class="text-center" ><input  style="background-color:#e2e2e2;" type="text" name="output_sum[]"  class="form-control output-sum text-center" value="' . $sum . ' "  readonly ></td>';
                for ($j = 0; $j < 5; $j++) {
                    $output_value = isset($output_arr[$j][$i - 1]) ? $output_arr[$j][$i - 1] : '';
                    echo '<td class="text-center" ><input type="text" name="output_amount' . ($j + 1) . '[]" inputmode="numeric" pattern="[0-9]{1,4}" class="form-control output-amount' . ($j + 1) . ' text-center" value="' . $output_value . '"></td>';
                }

            } else {
				
				// 신규로 새로 입력할 경우 
				// 데이터값 없음
				$sum = '';
                echo '<td class="text-center"><input type="text" name="input_amount[]" inputmode="numeric" pattern="[0-9]{1,4}" class="form-control input-amount  text-center" ></td>';                
                
                for ($j = 0; $j < 5; $j++) {  					
                    echo '<td class="text-center" ><input type="text" name="namearr_arr' . ($j + 1) . '[]" class="form-control namearr_arr' . ($j + 1) . ' text-center" ></td>';
                }
				
				echo '</tr>';
				echo '<tr >';		
				
				echo '<td class="text-center"><input type="text" name="output_sum[]" class="form-control output-sum text-center"  readonly  ></td>';
				
                for ($j = 1; $j <= 5; $j++) {
                    echo '<td class="text-center"><input type="text" name="output_amount' . $j . '[]" inputmode="numeric" pattern="[0-9]{1,4}"  class="form-control output-amount' . ($j + 1) . '  text-center my-input  "     ></td>';
                }
            }

            echo '</tr>';
			}
		?>
		</tbody>



				</table>
				
        
                </div>
            </div>
        </div>
    </div>
</div>

</form>


</body>
</html>


<script> 

	 
var ajaxRequest = null;
var ajaxRequest1 = null;
var ajaxRequest2 = null;
var ajaxRequest3 = null; // 변수를 전역으로 선언		
var ajaxRequest4 = null; // 변수를 전역으로 선언		

// ready.document 보다 나음 이게 더 나음...
window.onload = function () {
	
  // var outputAmountArrays = document.querySelectorAll('input[name="output_amount1"]');
// console.log('outputAmountArrays[i].value');
  // // 배열 요소를 콘솔에 출력하는 반복문
  // for (var i = 0; i < outputAmountArrays.length; i++) {
    // console.log(outputAmountArrays[i].value);
  // }
			
// 입력 필드와 관련된 정보를 저장하는 변수
var inputFieldInfo = {
  inputField: null, // 입력 필드 DOM 요소
  parentElement: null, // 입력 필드의 부모 요소
  index: -1 // 입력 필드의 인덱스
};	

	
	// 입력창에서 x마크 누르면 글자 지워지게 만들기
	document.querySelector('.clear1').addEventListener('click', function() {
	  document.getElementById('nameSearch').value = '';
	  document.getElementById('nameSearch').focus();
	});	
	document.querySelector('.clear2').addEventListener('click', function() {
	  document.getElementById('guest_name').value = '';
	  document.getElementById('guest_name').focus();
	});	
	document.querySelector('.clear3').addEventListener('click', function() {
	  document.getElementById('guest_tel').value = '';
	  document.getElementById('guest_tel').focus();
	});	
		
	  document.getElementById("branch").addEventListener("change", function() {
		// 이곳에 선택 변경 시 실행할 자바스크립트 코드를 작성합니다.
		// 예: 선택된 값에 따라 다른 동작을 수행하거나 AJAX 요청을 보내는 등의 작업을 수행할 수 있습니다.    
		document.cookie = "branch=" + this.value ;    
		
		// disabled되면 form에서 전송안되는 부분을 해결하기 위함
		  document.getElementById('hidden_branch').value = this.value;
		  
		// 추가적인 동작을 수행하도록 코드를 작성합니다.
		location.reload();
	  });
			
	
	 reload();	 
	 
	 
	 // 전화번호 이름 검색을 위해서 DB를 구성하는 구간
	 // 회원 자료를 이름+전화번호 형태로 구성함
function loadGuestData() {
  var URL = "../game/load_guest.php?branch=" + $("#branch").val();

  fetch(URL, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
  })
    .then(response => response.json())
    .then(guest_data => {
      var guestNames = guest_data['guest_name'];
      var guestTels = guest_data['guest_tel'];

      names = guestNames.slice(0, 10).map(function (name, index) {
        var tel = guestTels[index] || '';
        return name + ' ' + tel;
      });

      // names 배열이 업데이트된 이후에 실행되어야 할 코드
      var nameFields = document.querySelectorAll('.namearr_arr1, .namearr_arr2, .namearr_arr3, .namearr_arr4, .namearr_arr5');
      nameFields.forEach(function (field, index) {
        field.addEventListener('click', function () {
          // 클릭한 입력 필드의 정보 저장
          inputFieldInfo.inputField = this;
          inputFieldInfo.parentElement = this.parentElement;
          inputFieldInfo.index = index;

          // 모달 창 열기
          $("#nameModal").modal("show");
          $("#nameModal").on("shown.bs.modal", function () {		  

            // 입력 필드에 포커스 이동
            var nameSearchInput = document.getElementById('nameSearch');
            setTimeout(function () {
              nameSearchInput.focus();
            }, 300);			
			
			  // 검색결과 10 화면에 출력해 주기

      // 검색 필드 입력 이벤트 리스너
      var nameSearchInput = document.getElementById('nameSearch');
      var nameResult = document.getElementById('nameResult');

      nameSearchInput.addEventListener('input', function () {
        var searchText = this.value.trim().toLowerCase();
        
        if (searchText === '') {
          // 검색어가 없을 경우 기본 10개의 이름과 전화번호 보여주기
          names = guestNames.slice(0, 10).map(function (name, index) {
            var tel = guestTels[index] || '';
            return name + ' ' + tel;
          });
        } else {
          // 검색어가 있을 경우 검색된 이름과 전화번호 보여주기
          names = guestNames.reduce(function (filtered, name, index) {
            var tel = guestTels[index] || '';
            var fullName = name + ' ' + tel;
            if (fullName.toLowerCase().includes(searchText)) {
              filtered.push(fullName);
            }
            return filtered;
          }, []);
        }

        // 검색 결과 초기화
        nameResult.innerHTML = '';
        names.forEach(function (name) {
          var nameElement = document.createElement('div');
          nameElement.textContent = name;
          nameElement.style.display = 'block'; // 블록 요소로 스타일링
          nameElement.addEventListener('click', function () {
            var clickedName = this.textContent;
            if (inputFieldInfo.inputField) {
              // 클릭한 이름을 해당 위치의 입력 필드에 설정
              inputFieldInfo.inputField.value = clickedName;
              setTimeout(function () {
                $("#nameModal").modal("hide");
              }, 300);
              // calculateTotals 함수 호출
              calculateTotals();
            }
          });

          // 줄 간격을 조절하기 위한 CSS 스타일 적용
          nameElement.style.marginBottom = '15px';
          nameResult.appendChild(nameElement);
        });

        // 검색 결과가 있을 때 화면에 보이도록 함
        nameResult.style.display = 'block';
      });
    
	
				     
			  

			
			
			
			
          });
        });
      });


	
	
	
	
	
	
	
	
	
	
	
	
	});
}

// 페이지 로드 시 함수 호출
loadGuestData();


$("#nameModal").on("hidden.bs.modal", function () {			
	loadGuestData();	 
});

$("#inputModal").on("hidden.bs.modal", function () {	  		
	loadGuestData();	 
});
  
  
  
  
// 10초마다 갱신됨  
var intervalId = setInterval(function() {

	if (ajaxRequest !== null) {
		ajaxRequest.abort();
	}

	ajaxRequest = $.ajax({
		url: "check_save_time.php", // 저장된 시간을 반환하는 PHP 파일의 경로
		type: "post",
		datatype: "json",
		success: function(data) {
			ajaxRequest = null;
		  var savedTime = data.savedTime; // 저장된 시간 값을 받아옴
		  
		  var currentSavedTime = $("#checksave").val(); // 현재 checksave 필드의 값

		  // 저장된 시간과 현재 checksave 필드의 값이 다르다면 데이터 다시 불러오기
		  if (savedTime !== currentSavedTime) {			
		   console.log('reload ');		  		  
			  console.log('currentSavedTime');
			  console.log(currentSavedTime);
			  console.log( 'savedTime');
			  console.log( savedTime);		   
		   
			reload();
		  }
		},
		error: function(jqxhr, status, error) {
		  console.log(jqxhr, status, error);
		  // 오류 처리
		}
  });
}, 10000);


  
var branchSelect = document.getElementById("branch");

branchSelect.addEventListener("change", function() {
  calculateTotals();
});  
   
	
	var markCheckboxes = document.querySelectorAll('.mark-check');

	markCheckboxes.forEach(function (checkbox) {
	  checkbox.addEventListener('change', function () {
		var checkedRows = Array.from(markCheckboxes).map(function (checkbox) {
		  return checkbox.checked ? 'on' : '';
		});

		// 배열을 문자열로 변환하여 hidden input에 저장
		document.getElementById('checkedRows').value = checkedRows.join(',');		
		
	  });
	});
						
				
	// 빈칸 넣음 
		$("#nullButton").click(function(){ 			
			
		  if (inputFieldInfo.inputField) {
				// 클릭한 이름을 해당 위치의 입력 필드에 설정
				inputFieldInfo.inputField.value = '';

				// 모달 창 닫기			
					setTimeout(function () {
					   $("#nameModal").modal("hide");
					}, 800);			  
				
				// calculateTotals 함수 호출
				calculateTotals();		
		  }		  
		});		


//  회원 등록 버튼 클릭 이벤트 리스너
	$("#registerButton").click(function(){ 		
	
    // 버튼 비활성화    
	
		$('#guest_name').val($('#nameSearch').val());
		$('#guest_tel').val('');
		
		$("#inputModal").modal("show");			 
		
		$("#inputModal").on("shown.bs.modal", function () {
			
			  $('#saveButton').on('click', function(e) {
					e.preventDefault(); // 기본 이벤트 동작 막기
					// 클릭 이벤트 처리 로직    
			  });
			  
			  if( document.getElementById('guest_name').value !== '')
						document.getElementById('guest_tel').focus();
					else
						document.getElementById('guest_name').focus();
				
		// 입력 모달 창의 저장 버튼 클릭 이벤트 리스너
		var saveButton = document.getElementById('saveButton');
				saveButton.addEventListener('click', function () {					
					
					// // 모달 창 내용 초기화
					// $("#inputModal").find("input").val("");
					// $("#inputModal").find("select").val("");					
					
					// 버튼 비활성화
					saveButton.disabled = true;					
					var guest_registedate = document.getElementById('guest_registedate').value;
					var guest_name = document.getElementById('guest_name').value;
					var guest_tel = document.getElementById('guest_tel').value;
					
					 $("#mode").val('insert');  									
					
				   if (ajaxRequest3 !== null) {
						ajaxRequest3.abort();
					}
					
			ajaxRequest3 = $.ajax({

							url: "guest_insert.php?branch=" + $("#branch").val() ,
							type: "post",		
							data: $("#board_form").serialize(),
							dataType:"json",
							success : function( data ){
								
								
								ajaxRequest3 = null;
								 // console.log( data);
								 // alert(data["guest_name"]);
								 console.log(data["guest_name"]);
								 
								 // 변수 초기화
								 guest_name = '';
								 guest_tel = '';
									  
								 var clickedName = data["guest_name"];
								  if (inputFieldInfo.inputField) {
										// 클릭한 이름을 해당 위치의 입력 필드에 설정
										inputFieldInfo.inputField.value = clickedName;

										saveButton.disabled = false;
										// 모달 창 닫기										
											setTimeout(function () {											  
											  $("#nameModal").modal("hide");												  
								 											  
											}, 500);										
											setTimeout(function () {											  											  
											  $("#inputModal").modal("hide");									 											  
											}, 500);										  
																	
										 calculateTotals();
								  }
								  
						  
					},
					complete: function() {
						// AJAX 요청 완료 후 버튼 활성화
						saveButton.disabled = false;
						ajaxRequest3 = null;
					},
					error : function( jqxhr , status , error ){
						console.log( jqxhr , status , error );
						saveButton.disabled = false;
						ajaxRequest3 = null;
					} 			      		
				   });		
						
			
		});
	});
});

 // // 윈도우 상단의 X 아이콘 클릭 이벤트 리스너 등록
  // window.addEventListener('beforeunload', function(event) {
    // // 닫기 버튼 클릭과 동일한 동작 수행
    // // 이벤트 취소를 위해 메시지를 반환합니다.
    // event.returnValue = '';
	// closeWindow();
  // });
	
		
  // 페이지 로드 시 합계 초기화
  //  calculateTotals();

  var state = $('#state').val();

  // 처리완료인 경우는 수정하기 못하게 한다.
  $("#closeModalBtn").click(function () {
    $('#nameModal').modal('hide');
  });

  $(".closeBtn").click(function () { // 저장하고 창닫기	
		closeWindow() ;
  });

  $(".delBtn").click(function () { // del
    clearInterval(intervalId);
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
      cancelButtonText: '취소'
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: "insert.php",
          type: "post",
          data: $("#board_form").serialize(),
          dataType: "json",
          success: function (data) {
           //  console.log(data);
            opener.location.reload();
            // myalert("파일 삭제 완료!");

            setTimeout(function () {
              window.close();
            }, 500);
          },
          error: function (jqxhr, status, error) {
            console.log(jqxhr, status, error);
          }
        });
      }
    });
  });

	
// 체크박스 이벤트가 발생하면 calculateTotals() 함수 호출
var markCheckboxes = document.querySelectorAll('.mark-check');

	markCheckboxes.forEach(function (checkbox) {
	checkbox.addEventListener('change', calculateTotals);
});
	
  var outputAmountFields = document.querySelectorAll('.output-amount1, .output-amount2, .output-amount3, .output-amount4, .output-amount5');
  
	   // 배출합을 더해야 각 행의 첫번째 배출합계로 나타내는 구간 (자식구간을 더하는 로직)   
	outputAmountFields.forEach(function (field) {
	  field.addEventListener('input', function () {
		var table = field.closest('table');
		var tr = field.closest('tr');
		var outputSumField = tr.querySelector('.output-sum');
		var outputAmountsInRow = tr.querySelectorAll('.output-amount1, .output-amount2, .output-amount3, .output-amount4, .output-amount5');
		var sum = 0;

		outputAmountsInRow.forEach(function (amountField) {
		  var value = amountField.value || '';
		  sum += parseInt(value) || 0;
		});

		outputSumField.value = (sum !== 0) ? sum : '';
		
		
	  });
	});

	var textAmountFieldNames = Array.from({ length: 6 }, (_, i) => 'text_arr' + (i + 1));
	textAmountFieldNames.forEach(function (fieldName) {
	  var fields = Array.from(document.getElementsByName(fieldName + '[]'));
	  var sum = fields.slice(1).reduce(function (total, currField) {
		return total + (parseInt(currField.value) || 0);
	  }, 0);
	  if (fields.length > 0 && fields[0]) {
		fields[0].value = sum;
	  }
	  // console.log('text-arr :' + sum);
	});



	for (let i = 1; i <= 6; i++) {  // 지출항목 6개 줄.. 간식추가함
	  var firstField = document.querySelector('input[name="text_arr' + i + '[]"]');
	  var value = firstField ? (firstField.value || '') : '';
	  totalExpense += parseInt(value) || 0;
	}
 
  totalExpenseIncludedispose = totalExpense + totalOutput;

  document.getElementById('totalInput').textContent = numberWithCommas(totalInput * 1000);
  document.getElementById('totalOutput').textContent = numberWithCommas(totalOutput * 1000);
  document.getElementById('totalExpense').textContent = numberWithCommas(totalExpense * 1000);
  document.getElementById('totalExpenseIncludedispose').textContent = numberWithCommas(totalExpenseIncludedispose * 1000);
  document.getElementById('difference').textContent = numberWithCommas((totalInput - totalExpenseIncludedispose) * 1000); 
    
}

function calculateTotals() {
	OnlyCalculate();
	saveBtn();
}

function OnlyCalculate() {
	var feetotal = $("#total0").val();
	var totalInput = 0;
	var totalOutput = 0;
	var totalExpense = 0;
	var totalExpenseIncludedispose = 0;

	// 체크박스 초기화 및 복원
	var markCheckboxes = document.querySelectorAll('.mark-check');
	markCheckboxes.forEach(function (checkbox) {
		checkbox.checked = false;
	});

	var checkarr = $("#checkedRows").val() || '';
	var markArr = checkarr ? checkarr.split(',') : Array(150).fill('');
	markCheckboxes.forEach(function (checkbox, index) {
	  checkbox.checked = markArr[index] === 'on';
	});
	var checkedRows = Array.from(markCheckboxes).map(function (checkbox) {
	  return checkbox.checked ? 'on' : '';
	});
	document.getElementById('checkedRows').value = checkedRows.join(',');

	// 총 투입/배출 합산
	document.querySelectorAll('.input-amount').forEach(function(input){
		var value = input.value || '';
		totalInput += parseInt(value) || 0;
	});
	for (var i = 1; i <= 5; i++) {
		document.querySelectorAll('.output-amount' + i).forEach(function(input){
			var value = input.value || '';
			totalOutput += parseInt(value) || 0;
		});
	}

	// hidden 합계 필드 갱신
	function getValuesFromElements(elementName) {
		var elements = document.getElementsByName(elementName);
		return Array.from(elements).map(function(el){ return Number(el.value); });
	}
	function sumArray(array) {
		return array.reduce(function(total, value){ return total + value; }, 0);
	}

	var inputValues = getValuesFromElements('input_amount[]');
	var inputSum = sumArray(inputValues);
	var outputValues = Array.from({ length: 5 }, function(_, i){ return getValuesFromElements('output_amount' + (i + 1) + '[]'); });
	var outputSums = outputValues.map(sumArray).map(function (sum) { return (sum !== 0) ? sum : ''; });

	if (document.getElementsByName('inputsum')[0]) {
		document.getElementsByName('inputsum')[0].value = inputSum;
	}
	var outputsumElement = document.getElementsByName('outputsum')[0];
	if (outputsumElement) {
		var totalOutputs = sumArray(outputSums.map(function(v){ return Number(v) || 0; }));
		outputsumElement.value = (totalOutputs !== 0) ? totalOutputs : '';
	}

	// 지출 배열의 첫 칸 자동합
	var textAmountFieldNames = Array.from({ length: 6 }, function(_, i){ return 'text_arr' + (i + 1); });
	textAmountFieldNames.forEach(function (fieldName) {
	  var fields = Array.from(document.getElementsByName(fieldName + '[]'));
	  var sum = fields.slice(1).reduce(function (total, currField) {
		return total + (parseInt(currField.value) || 0);
	  }, 0);
	  if (fields.length > 0 && fields[0]) {
		fields[0].value = sum;
	  }
	});

	for (var j = 1; j <= 6; j++) {
	  var firstField = document.querySelector('input[name="text_arr' + j + '[]"]');
	  var v = firstField ? (firstField.value || '') : '';
	  totalExpense += parseInt(v) || 0;
	}

	totalExpenseIncludedispose = totalExpense + totalOutput;

	// 합계표 표시 업데이트
	var elTotalInput = document.getElementById('totalInput');
	var elTotalOutput = document.getElementById('totalOutput');
	var elTotalExpense = document.getElementById('totalExpense');
	var elTotalExpenseInc = document.getElementById('totalExpenseIncludedispose');
	var elDifference = document.getElementById('difference');
	if (elTotalInput) elTotalInput.textContent = numberWithCommas(totalInput * 1000);
	if (elTotalOutput) elTotalOutput.textContent = numberWithCommas(totalOutput * 1000);
	if (elTotalExpense) elTotalExpense.textContent = numberWithCommas(totalExpense * 1000);
	if (elTotalExpenseInc) elTotalExpenseInc.textContent = numberWithCommas(totalExpenseIncludedispose * 1000);
	if (elDifference) elDifference.textContent = numberWithCommas((totalInput - totalExpenseIncludedispose) * 1000);
}

function saveBtn()
{
	
	var num = $("#num").val();
	
	if (Number(num) > 0)
		$("#mode").val('modify');
	else
	   $("#mode").val('insert');

     // console.log('num : ' + num);


   if (ajaxRequest1 !== null) {
	ajaxRequest1.abort();
	}


// 자료 추적을 위한 내용
// var serializedData = $("#board_form").serialize();
// var decodedData = decodeURIComponent(serializedData.replace(/\+/g, ' '));

// console.log('서버 전송전값');
// console.log(JSON.stringify(queryStringToObject(decodedData), null, 2));

// var serializedData = $("#board_form").serialize();
// var decodedData = decodeURIComponent(serializedData.replace(/\+/g, ' '));

// function queryStringToObject(queryString) {
    // var pairs = queryString.split('&');
    // var result = {};

    // pairs.forEach(function(pair) {
        // pair = pair.split('=');
        // result[pair[0]] = decodeURIComponent(pair[1] || '');
    // });

    // return result;
// }

// var resultObject = queryStringToObject(decodedData);

// // 로그를 통해 output_amount1의 모든 값을 출력합니다.
// console.log("서버 전송전 값 output_amount1[]");
// console.log(resultObject["output_amount1"]);



	// data 전송해서 php 값을 넣기 위해 필요한 구문
	ajaxRequest1 = $.ajax({
		url: "insert.php",
		type: "post",
		data: $("#board_form").serialize(),
		datatype: 'json',
		success: function (data) {			
		  ajaxRequest1 = null;
		  // console.log('저장 후 ');
		  // console.log('$outputAmounts1 ');
		  // console.log(data.outputAmounts1);	
		  // // console.log('저장 후 checkbox  : ');
		  // // console.log(data.mark);
		  
		  // // console.log('저장 후 insert 이후 데이터 받은 자료 data.output_arr1  : ');
		  // // console.log(data.output_arr1);
		  
		  reload();
		  
		  
		 },
		error: function (jqxhr, status, error) {
		  console.log(jqxhr, status, error);
		  // Handle error
		}
	  });
}

function myalert(str) {
  Toastify({
    text: str,
    duration: 3000,
    close: true,
    gravity: "top",
    position: "center",
    backgroundColor: "#4fbe87",
    className: "toastify-content",
  }).showToast();

  setTimeout(function () {
    // 시간지연
  }, 1000);
}
  
// 페이지를 나갈 때 경고를 표시하지 않고 페이지를 떠나는 동작 수행
// window.onbeforeunload = null;



function numberWithCommas(number) {
  var formattedNumber = number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  return formattedNumber + " (원)";
}


// 함수 정의
function closeWindow() {  
  opener.location.reload();  
  window.close();
}

// 이전 데이터 저장을 위한 변수
var previousData = null;

function reload() {	


	var num = '<?php echo $num; ?>';	   
	if (ajaxRequest !== null) {
	ajaxRequest.abort();
	}

	// data 전송해서 php 값을 넣기 위해 필요한 구문
	ajaxRequest = $.ajax({
    url: "load_data.php?num=" + num,
    type: "post",
    data: "",
	datatype : 'json',
    success: function (data) {
		
		$("#checksave").val(data.savedTime) ; 
		console.log( "저장된 시간 불러오기 " + data.savedTime) ; 
		
	  ajaxRequest = null;
      // console.log('현재 저장 시간 ' + data.updatetime);
	  // console.log('branch ');
	  // console.log(data.branch);	
	  
        // 체크박스 초기화 (모두 체크 해제)
        var markCheckboxes = document.querySelectorAll('.mark-check');
        markCheckboxes.forEach(function (checkbox) {
            checkbox.checked = false;
        });

        // 서버에서 전달받은 데이터를 기반으로 체크박스 상태 업데이트
        var markArr = (data.mark && data.mark.split(',')) || Array(150).fill('');
        markCheckboxes.forEach(function (checkbox, index) {
            checkbox.checked = markArr[index] === 'on';
        });	  
	  
	   var checkedRows = Array.from(markCheckboxes).map(function (checkbox) {
			  return checkbox.checked ? 'on' : '';
			});

	  			// 배열을 문자열로 변환하여 hidden input에 저장
		document.getElementById('checkedRows').value = checkedRows.join(',');	
	  
	  
	  // console.log('num ');
	  // console.log(data.num);
	  // console.log('interval 후 data.output_arr1 로드한 값');
	  // console.log(data.output_arr1);

					 									  
					  if (data && data.memo) {
						$("#memo").val(data.memo);
					  }
					  if (data && data.receivable) {
						$("#receivable").val(data.receivable);
					  }
								
						var textAmountFieldNames = ['text_arr1', 'text_arr2', 'text_arr3', 'text_arr4', 'text_arr5', 'text_arr6'];

						textAmountFieldNames.forEach(function (fieldName) {
						  var fields = Array.from(data[fieldName].split(','));
						  
						  // console.log(fieldName);
						  // console.log(fields);

													
						  fields.forEach(function (field, index) {
							var inputFields = Array.from(document.getElementsByName(fieldName + '[]'));
							var currentField = inputFields[index];

							if (currentField) {
							  currentField.value = field;
							}
						  });

						});

		// 고객이름 부분 데이터 넣기
		for (var i = 0; i < 150; i++) {
		  var nameVal1 = (data.namearr1 && data.namearr1.split(',')[i]) || '';
		  var nameVal2 = (data.namearr2 && data.namearr2.split(',')[i]) || '';
		  var nameVal3 = (data.namearr3 && data.namearr3.split(',')[i]) || '';
		  var nameVal4 = (data.namearr4 && data.namearr4.split(',')[i]) || '';
		  var nameVal5 = (data.namearr5 && data.namearr5.split(',')[i]) || '';

		  var row = document.querySelector('#table2 tbody tr:nth-child(' + ( (i+1) * 2 ) + ')');
		  if (row) {
			
			var nameSel1 = row.querySelector('.namearr_arr1');
			var nameSel2 = row.querySelector('.namearr_arr2');
			var nameSel3 = row.querySelector('.namearr_arr3');
			var nameSel4 = row.querySelector('.namearr_arr4');
			var nameSel5 = row.querySelector('.namearr_arr5');
			
			// console.log('nameSel1');
			// console.log(nameSel1);

			if (inputAmount) {
			  inputAmount.value = inputVal;
			}
			if (outputsum) {
			  if (outputSumVal !== 0) {
				outputsum.value = outputSumVal;
			  } else {
				outputsum.value = '';
			  }
			}

			if (nameSel1) {
			  nameSel1.value = nameVal1;
			}
			if (nameSel2) {
			  nameSel2.value = nameVal2;
			}
			if (nameSel3) {
			  nameSel3.value = nameVal3;
			}
			if (nameSel4) {
			  nameSel4.value = nameVal4;
			}
			if (nameSel5) {
			  nameSel5.value = nameVal5;
			}
		  }
		}

										
					// 투입부분 수정
					for (var i = 0; i < 150; i++) {
					  var inputVal = (data.input_arr && data.input_arr.split(',')[i]) || '';
					  var outputSumVal = (Number(data.output_arr1.split(',')[i] || 0) + Number(data.output_arr2.split(',')[i] || 0) + Number(data.output_arr3.split(',')[i] || 0) + Number(data.output_arr4.split(',')[i] || 0) + Number(data.output_arr5.split(',')[i] || 0)) || 0;  

					  var row = document.querySelector('#table2 tbody tr:nth-child(' + ( (i+1) * 2 ) + ')');
					  
					 // console.log(  (i+1) * 2  );
					  
					  if (row) {
						var inputAmount = row.querySelector('.input-amount');    

						if (inputAmount) {
						  inputAmount.value = inputVal;
						}  
					  }
					}

					 //  console.log(data.output_arr1);
								
			// 배출부분 수정
			for (var i = 0; i < 150; i++) {  
			  var outputSumVal = (Number(data.output_arr1.split(',')[i] || 0) + Number(data.output_arr2.split(',')[i] || 0) + Number(data.output_arr3.split(',')[i] || 0) + Number(data.output_arr4.split(',')[i] || 0) + Number(data.output_arr5.split(',')[i] || 0)) || 0;
			  var outputVal1 = (data.output_arr1 && data.output_arr1.split(',')[i]) || '';
			  var outputVal2 = (data.output_arr2 && data.output_arr2.split(',')[i]) || '';
			  var outputVal3 = (data.output_arr3 && data.output_arr3.split(',')[i]) || '';
			  var outputVal4 = (data.output_arr4 && data.output_arr4.split(',')[i]) || '';
			  var outputVal5 = (data.output_arr5 && data.output_arr5.split(',')[i]) || '';
			  
			  // th 부분도 tr이 있으니 이것도 계산해야 한다. 이것 오류로 시간 많이 허비함
			  var rowOutput = document.querySelector('#table2 tbody tr:nth-child(' + (2 * i + 3) + ')');

			  if (rowOutput) {    
				var outputsum = rowOutput.querySelector('.output-sum');
				var outputAmount1 = rowOutput.querySelector('.output-amount1');
				var outputAmount2 = rowOutput.querySelector('.output-amount2');
				var outputAmount3 = rowOutput.querySelector('.output-amount3');
				var outputAmount4 = rowOutput.querySelector('.output-amount4');
				var outputAmount5 = rowOutput.querySelector('.output-amount5');

				if (outputsum) {
				  if (outputSumVal !== 0) {
					outputsum.value = outputSumVal;
				  } else {
					outputsum.value = '';
				  }
				}

				if (outputAmount1) {
				  outputAmount1.value = outputVal1 ;
				}
				if (outputAmount2) {
				  outputAmount2.value = outputVal2 ;
				}
				if (outputAmount3) {
				  outputAmount3.value = outputVal3 ;
				}
				if (outputAmount4) {
				  outputAmount4.value = outputVal4 ;
				}
				if (outputAmount5) {
				  outputAmount5.value = outputVal5 ;
				}
			  }
			}


            // 합계표 재계산
			OnlyCalculate();

					},
					error: function (jqxhr, status, error) {
					  console.log(jqxhr, status, error);
					  ajaxRequest = null;
					}
				  });								
				  
}  // end of function name 'reload'

// URL 쿼리 문자열을 객체로 변환하는 함수
function queryStringToObject(queryString) {
  var params = {};
  var pairs = queryString.split('&');
  
  for (var i = 0; i < pairs.length; i++) {
    var pair = pairs[i].split('=');
    var key = decodeURIComponent(pair[0]);
    var value = decodeURIComponent(pair[1] || '');

    if (key.length) {
      if (params.hasOwnProperty(key)) {
        if (Array.isArray(params[key])) {
          params[key].push(value);
        } else {
          params[key] = [params[key], value];
        }
      } else {
        params[key] = value;
      }
    }
  }
  
  return params;
}

		
</script>
