<?php
// 환경별 기본 URL 설정
require_once '../config/environment.php';

session_start();

$root_dir = '..' ;

ini_set('display_errors','0');  // 화면에 warning 없애기	

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

$alias_arr =array();	

// 별명 불러오기
 try{
	  $sql = "select * from jtechel.game_alias ";
	  $stmh = $pdo->query($sql);       
      $stmh->execute();
      $count = $stmh->rowCount();            
	  $row = $stmh->fetch(PDO::FETCH_ASSOC);  // $row 배열로 DB 정보를 불러온다.		
		 
	  $alias_arr = explode(",", $row['alias']);
	  
	 }catch (PDOException $Exception) {
	   print "오류: ".$Exception->getMessage();
	 }


// $nonEmptyCount = count(array_filter($alias_arr, function($value) {
    // return $value !== '';
// }));


// print '별명개수 : ' . $nonEmptyCount ;

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


 try{
	 
	  $sql = "select * from jtechel.game where registedate = ? ";
	  $stmh = $pdo->prepare($sql); 
      $stmh->bindValue(1,$registedate,PDO::PARAM_STR); 
      $stmh->execute();
      $count = $stmh->rowCount();  	 
		  
	
 while ($row = $stmh->fetch(PDO::FETCH_ASSOC)) {
	
	// var_dump($row);
	 
	$registedate = $row["registedate"];
	
    $text1 = $row["text1"] ;
    $text2 = $row["text2"] ;
    $text3 = $row["text3"] ;
    $text4 = $row["text4"] ;
    $text5 = $row["text5"] ;


  // number_format($text5) . '</td>';
      }

  } catch (PDOException $Exception) {
  print "오류: ".$Exception->getMessage();
  }  


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
 // end of if	

	
if($num=='')
{
	$registedate=date("Y-m-d");	

	$mcno = '';
	$inputsum = '';
	$outputsum = '';
}
else // 값이 존재하면 수정모드
{
	$isEditMode = true; // 수정 모드 여부
	$inputvalues = explode(',', $input_arr);
	$outputvalues1 = explode(',', $output_arr1);
	$outputvalues2 = explode(',', $output_arr2);
	$outputvalues3 = explode(',', $output_arr3);
	$outputvalues4 = explode(',', $output_arr4);

	
	$input_arr = $inputvalues;
	$output_arr1 = $outputvalues1;
	$output_arr2 = $outputvalues2;
	$output_arr3 = $outputvalues3;
	$output_arr4 = $outputvalues4;	
	
// 지출부분 읽기
		
	$text_arr1 = explode(',', $text1);
	$text_arr2 = explode(',', $text2);
	$text_arr3 = explode(',', $text3);
	$text_arr4 = explode(',', $text4);
	$text_arr5 = explode(',', $text5);

	// 배열의 각 요소가 0인 경우 공백으로 변경
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

	
	// var_dump($text1);

	$mode="modify";
}


	$total0 = 0;		  
	$total1 = 0;		  
	$total2 = 0;		  
	$total3 = 0;		  
	$total4 = 0;		  
	$total5 = 0;

    $total1 += array_sum($text1);
    $total2 += array_sum($text2);
    $total3 += array_sum($text3);
    $total4 += array_sum($text4);
    $total5 += array_sum($text5);
	
	$total0 += $total1 + $total2 + $total3 + $total4 + $total5 ;	


// if($level!==1)
	// $item = '신규' ;

// var_dump($row);
 
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

    <!-- Modal -->
  <div class="modal fade" id="myModal" role="dialog">
    <div class="modal-dialog  modal-lg modal-center" >
    
      <!-- Modal content-->
      <div class="modal-content modal-lg">
        <div class="modal-header">          
          <h4 class="modal-title">알림</h4>
        </div>
        <div class="modal-body">		
		   <div id=alertmsg class="fs-1 mb-5 justify-content-center" >
		     결재가 진행중입니다. <br> 
		   <br> 
		  수정사항이 있으면 결재권자에게 말씀해 주세요.
			</div>
        </div>
        <div class="modal-footer">
          <button type="button" id="closeModalBtn" class="btn btn-default" data-dismiss="modal">닫기</button>
        </div>
      </div>
      
    </div>
  </div>

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

.sideBanner {
  position: absolute;
  width: calc(100vw - 90vw);
  height:calc(100vh - 70vh);
  top: calc(100vh - 70vh);
  left: calc(100vw - 15vw);  
}

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

.sideBanner {
	font-size: 30px; /* or any other size that you want */		
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
    }
	
	.table td input{
        font-size: 28px; /* or any other size that you want */
    }	

    .sideBanner {
        font-size: 50px; /* or any other size that you want */		
    }  
}
		
    th, td{
        vertical-align: middle;
    }

</style>	
	

<div class="container-fluid">
    <div class="row justify-content-center align-items-center w-100 vh-100">
        <div class="col-lg-9 text-center">
            <div class="card align-middle justify-content-center w-70" style="border-radius: 20px;">
                <div class="card-body">
                    <span class="card-title mb-5" style="color: #113366; ">데이터 입력</span> <br>
					
					
                    <form id="board_form" name="board_form" class="form-signin" method="post">
                      
							<input type="hidden" id="mode" name="mode" value="<?=$mode?>">
							<input type="hidden" id="num" name="num" value="<?=$num?>" >                        
							<input type="hidden" id="user_name" name="user_name" value="<?=$user_name?>" size="4" >
							<input type="hidden" id="mcno" name="mcno[]" value="<?=$mcno?>" size="4" >
							<input type="hidden" id="inputsum" name="inputsum" value="" size="4" >
							<input type="hidden" id="outputsum" name="outputsum" value="" size="4" >
							<input type="hidden" id="mcount" name="mcount"  >
							<input type="hidden" id="updatetime" name="updatetime" value="<?=$updatetime?>" >
							<input type="hidden" id="total0" name="total0" value="<?=$total0?>" >

					<div class="form-group mt-5 mb-5">
					  <label for="registedate" class="form-control fs-1" style="width:35%;">등록일자</label>
						 <input type="date" id="registedate" name="registedate" required value="<?=$registedate?>" class="form-control fs-1">
					</div>

					<div class="form-group mt-2 mb-2">
					  <label for="item" class="form-control fs-1" style="width:35%;">입력구분</label>
					  <select id="item" name="item" required class="form-control fs-1">
						<option value="신규" <?php if ($item === '신규') echo 'selected'; ?>>신규</option>
						<option value="누계수정" <?php if ($item === '누계수정') echo 'selected'; ?>>누계수정</option>
						<option value="최초자산" <?php if ($item === '최초자산') echo 'selected'; ?>>최초자산</option>						
					  </select>
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


<table  id="table1" class="table table-bordered " style="width:90%;">
    <tr>
        <th class="text-center"  style="width:60px;">수입</th>
        <th class="text-center"  colspan="1" style="width:100px;">투입합</th>
        <th class="text-center"  colspan="5"><span id="totalInput" class=" text-primary total-amount">0</span></th>
    </tr>        
    <tr>
        <th rowspan="7" class="text-center">지출</th>
		<th class="text-center"  colspan="1">배출합</th>
		<th class="text-center" colspan="5"> <span id="totalOutput" class=" text-danger total-amount">0</span> </th>
    </tr>
    <tr>		
        <th class="text-center">일비</th> 
		
    <?php       
                                
if ($isEditMode) {  			  			         
    for ($j = 0; $j < 5; $j++) {
        $txt_val = isset($text_arr1[$j ]) ? $text_arr1[$j] : '';
        if ($j == 0) { // 첫 번째 요소일 경우
            echo '<td class="text-center" ><input type="text" name="text_arr1[]" inputmode="numeric" pattern="[0-9]{1,4}" required class="form-control text-center" value="' . $txt_val . '" readonly style="background-color: #ccc;"></td>';
        } else { 
            echo '<td class="text-center" ><input type="text" name="text_arr1[]" inputmode="numeric" pattern="[0-9]{1,4}" required class="form-control text-center" value="' . $txt_val . '"></td>';
        }
    }
}
  
		  			
    ?>
		
		
		
    </tr>  
	
	<tr>		
        <th class="text-center">식비</th>
		
    <?php       
                                
                                     
if ($isEditMode) {  			  			         
    for ($j = 0; $j < 5; $j++) {
        $txt_val = isset($text_arr2[$j]) ? $text_arr2[$j] : '';
        if ($j == 0) { // 첫 번째 요소일 경우
            echo '<td class="text-center" ><input type="text" name="text_arr2[]" inputmode="numeric" pattern="[0-9]{1,4}" required class="form-control   text-center" value="' . $txt_val . '" readonly style="background-color: #ccc;"></td>';
        } else { 
            echo '<td class="text-center" ><input type="text" name="text_arr2[]" inputmode="numeric" pattern="[0-9]{1,4}" required class="form-control  text-center" value="' . $txt_val . '"></td>';
        }
    }
}
 
		  			
    ?>
		
		
    </tr>
  
	<tr>		
        <th class="text-center">집세 </th>
		
    <?php       
                                
 											   
	if ($isEditMode) {  			  			         
		for ($j = 0; $j < 5; $j++) {
			$txt_val = isset($text_arr3[$j]) ? $text_arr3[$j] : '';
			if ($j == 0) { // 첫 번째 요소일 경우
				echo '<td class="text-center" ><input type="text" name="text_arr3[]" inputmode="numeric" pattern="[0-9]{1,4}" required class="form-control   text-center" value="' . $txt_val . '" readonly style="background-color: #ccc;"></td>';
			} else { 
				echo '<td class="text-center" ><input type="text" name="text_arr3[]" inputmode="numeric" pattern="[0-9]{1,4}" required class="form-control  text-center" value="' . $txt_val . '"></td>';
			}
		}
	}
                                   

		  			   
		  			
    ?>
		
    </tr>
	
	<tr>		
        <th rowspan="2" class="text-center">서비스 </th>
		
    <?php       
                                
											   
	if ($isEditMode) {  			  			         
		for ($j = 0; $j < 5; $j++) {
			$txt_val = isset($text_arr4[$j]) ? $text_arr4[$j] : '';
			if ($j == 0) { // 첫 번째 요소일 경우
				echo '<td rowspan="2" class="text-center" ><input type="text" name="text_arr4[]" inputmode="numeric" pattern="[0-9]{1,4}" required class="form-control  text-center" value="' . $txt_val . '" readonly style="background-color: #ccc;"></td>';
			} else { 
				echo '<td class="text-center" ><input type="text" name="text_arr4[]" inputmode="numeric" pattern="[0-9]{1,4}" required class="form-control  text-center" value="' . $txt_val . '"></td>';
			}
		}
	}

		  			
    ?>
		
    </tr>	
	
	<tr>		
    
		
    <?php       
                                
											   
	if ($isEditMode) {  			  			         
		for ($j = 5; $j < 9; $j++) {
			$txt_val = isset($text_arr4[$j]) ? $text_arr4[$j] : '';
				echo '<td class="text-center" ><input type="text" name="text_arr4[]" inputmode="numeric" pattern="[0-9]{1,4}" required class="form-control  text-center" value="' . $txt_val . '"></td>';			
		}
	}

		  			
    ?>
		
    </tr>	
	
	<tr>		
        <th class="text-center">기타 </th>
		
    <?php       
                                
if ($isEditMode) {  			  			         
    for ($j = 0; $j < 5; $j++) {
        $txt_val = isset($text_arr5[$j]) ? $text_arr5[$j] : '';
        if ($j == 0) { // 첫 번째 요소일 경우
            echo '<td class="text-center" ><input type="text" name="text_arr5[]" inputmode="numeric" pattern="[0-9]{1,4}" required class="form-control  text-center" value="' . $txt_val . '" readonly style="background-color: #ccc;"></td>';
        } else { 
            echo '<td class="text-center" ><input type="text" name="text_arr5[]" inputmode="numeric" pattern="[0-9]{1,4}" required class="form-control  text-center" value="' . $txt_val . '"></td>';
        }
    }
}

		  				
    ?>
		
    </tr>	
	
		
    <tr>
        <th  colspan="2" class="text-center">계</th>
        <th  colspan="5"> <span id="totalExpense" class="total-amount"><?=number_format($total0)?></span></th>
    </tr>
    <tr>
        <th colspan="2" class="text-center">잔액</th>
        <th colspan="6"> <span id="difference" class="total-amount">0</span></th>
    </tr>	
</table>
	
	

					<div class="form-group mt-3 mb-3">
							<h3 class="form-signin-heading fs-3">메모 </h3>
						 <textarea  style="width:80%;" rows="2"   id="memo" name="memo" class="form-control fs-2"><?=$memo?></textarea>
					</div>
					<div class="form-group mt-3 mb-3">
							<h3 class="form-signin-heading fs-3">미수 </h3>
						 <textarea  style="width:80%;"  rows="2"  id="receivable" name="receivable" class="form-control fs-2"><?=$receivable?></textarea>
					</div>
		</div>



		<table id="table2" class="table table-bordered" style="width:90%;">
			<thead>
				<tr>
					<th rowspan="2">기계</th>
					<th rowspan="2">별명</th>
					<th rowspan="2">투입합</th>
					<th rowspan="2">배출합</th>
					<th colspan="4">배출상세</th>
				</tr>
				<tr>
					<th>배출1</th>
					<th>배출2</th>
					<th>배출3</th>
					<th>배출4</th>
				</tr>
			</thead>
		
<tbody>

    <?php
        for ($i = 1; $i <= 150; $i++) {
            echo '<tr>';
            echo '<td class="text-center text-primary">' . $i . '</td>';
            echo '<td class="text-center text-success">' . $alias_arr[$i-1] . '</td>';
            
            if ($isEditMode) {
                $input_value = isset($input_arr[$i - 1]) ? $input_arr[$i - 1] : '';
                $output_value1 = isset($output_arr1[$i - 1]) ? $output_arr1[$i - 1] : '';
                $output_value2 = isset($output_arr2[$i - 1]) ? $output_arr2[$i - 1] : '';
                $output_value3 = isset($output_arr3[$i - 1]) ? $output_arr3[$i - 1] : '';
                $output_value4 = isset($output_arr4[$i - 1]) ? $output_arr4[$i - 1] : '';
				
			     $output_arr = array($output_arr1, $output_arr2, $output_arr3, $output_arr4);
				 if($output_value1 + $output_value2 + $output_value3 + $output_value4 > 0)
				      $sum = $output_value1 + $output_value2 + $output_value3 + $output_value4 ;
				    else
						$sum = '';
				
                echo '<td class="text-center"><input type="text" name="input_amount[]" inputmode="numeric" pattern="[0-9]{1,4}" required class="form-control input-amount text-center" value="' . $input_value . '" ></td>';
                echo '<td class="text-center" ><input  style="background-color:#e2e2e2;" type="text" name="output_sum[]" inputmode="numeric" pattern="[0-9]{1,4}" required class="form-control output-sum text-center" value="' . $sum . ' "  readonly ></td>';
                
         
                for ($j = 0; $j < 4; $j++) {
                    $output_value = isset($output_arr[$j][$i - 1]) ? $output_arr[$j][$i - 1] : '';
                    echo '<td class="text-center" ><input type="text" name="output_amount' . ($j + 1) . '[]" inputmode="numeric" pattern="[0-9]{1,4}" required class="form-control output-amount' . ($j + 1) . ' text-center" value="' . $output_value . '"></td>';
                }

            } else {
				$sum = '';
                echo '<td class="text-center"><input type="text" name="input_amount[]" inputmode="numeric" pattern="[0-9]{1,4}" required class="form-control input-amount  text-center" ></td>';
                echo '<td class="text-center"><input type="text" name="output_sum[]" inputmode="numeric" pattern="[0-9]{1,4}" required class="form-control output-sum text-center" value="' . $sum . '   "  readonly " ></td>';
                
                for ($j = 1; $j <= 4; $j++) {
                    echo '<td class="text-center"><input type="text" name="output_amount' . $j . '[]" inputmode="numeric" pattern="[0-9]{1,4}" required class="form-control output-amount' . ($j + 1) . '  text-center"></td>';
                }
            }

            echo '</tr>';
        }
    ?>
</tbody>



				</table>
				
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="sideBanner fs-2">
 <!-- <div class="mb-1 mt-1 fs-3">
    <button type="button" class="btn btn-dark rounded-pill saveBtn fs-1">저장</button>
  </div> -->
  <?php if($level==1000) { ?>
  <div class="mb-1 mt-1 text-primary fs-3">
    <button type="button" class="btn btn-danger rounded-pill delBtn fs-1">삭제</button>
   </div>
  <?php } ?>
  <div class="mb-1 mt-1 text-secondary fs-3">
   
   </div>
</div>

<script> 

window.onload = function() {
    var outputAmountFields = document.querySelectorAll('.output-amount1, .output-amount2, .output-amount3, .output-amount4');
    var textAmountFieldNames = Array.from({length: 5}, (_, i) => 'text_arr' + (i + 1)); 

    outputAmountFields.forEach(function(field) {
        field.addEventListener('input', function() {
            var tr = this.closest('tr');
            var outputAmountsInRow = tr.querySelectorAll('.output-amount1, .output-amount2, .output-amount3, .output-amount4');
            var sum = 0;
            outputAmountsInRow.forEach(function(amountField) {
                sum += parseInt(amountField.value) || 0;
            });

		var outputSumField = tr.querySelector('.output-sum');
		outputSumField.value = (sum !== 0) ? sum : '';

            // calculateTotals();
        });
    });
	
		

	textAmountFieldNames.forEach(function(fieldName) {
		var fields = Array.from(document.getElementsByName(fieldName + '[]'));
		fields.forEach(function(field, index) {
			field.addEventListener('input', function() {
				var sum = fields.reduce(function(total, currField) {
					return total + (parseInt(currField.value) || 0);
				}, 0);
				fields[0].value = sum;
				fields[0].dispatchEvent(new Event('change')); // change 이벤트 발생
				console.log('text-arr :' + sum);
			});
		});
	});




	document.querySelectorAll('textarea').forEach(function(input) {
		input.addEventListener('input', function() {        
			calculateTotals();
		});

		input.addEventListener('blur', function() {        
			calculateTotals();
			reload();
		});
	});

	document.querySelectorAll('input[type="text"]').forEach(function(input) {
		input.addEventListener('input', function() {
			this.value = this.value.replace(/[^0-9]/g, '');
			calculateTotals();
		});

		input.addEventListener('blur', function() {        
			calculateTotals();
			// reload();
		});
	});
}


// 전자결재를 위해 띄우는 창
// 기본 위치(top)값
var floatPosition = parseInt($(".sideBanner").css('top'))

// scroll 인식
$(window).scroll(function() {
  // 모바일에선 나타나지 않게 하기  
    // 현재 스크롤 위치
    var currentTop = $(window).scrollTop();
    var bannerTop = currentTop + floatPosition + "px";

    //이동 애니메이션
    $(".sideBanner").stop().animate({
      "top" : bannerTop
    }, 500);
  
}).scroll();

// 합계 계산 및 차액 계산
function calculateTotals() {
    var feetotal = $("#total0").val();
    let totalInput = 0;
    let totalOutput = 0;
    let totalExpense = 0;

    document.querySelectorAll('.input-amount').forEach(input => {
        totalInput += parseInt(input.value) || 0;
    });

    for (let i = 1; i <= 4; i++) {
        document.querySelectorAll('.output-amount' + i).forEach(input => {
            totalOutput += parseInt(input.value) || 0;
        });
    }
	
	

		// 변경이 일어날때마다 저장함
  			      var num = $("#num").val();  	    
			
						function getValuesFromElements(elementName) {
						var elements = document.getElementsByName(elementName);
						return Array.from(elements).map(el => Number(el.value));
					}

					function sumArray(array) {
						return array.reduce((total, value) => total + value, 0);
					}

					// input_amount 배열의 값을 읽어옴
					var inputValues = getValuesFromElements('input_amount[]');
					var inputSum = sumArray(inputValues);

					var outputValues = Array.from({length: 4}, (_, i) => getValuesFromElements('output_amount' + (i + 1) + '[]'));
					var outputSums = outputValues.map(sumArray).map(function(sum) {
						return (sum !== 0) ? sum : '';
					});

					// 합계를 form 요소에 저장
					document.getElementsByName('inputsum')[0].value = inputSum;
					var outputsumElement = document.getElementsByName('outputsum')[0];
					outputsumElement.value = (sumArray(outputSums) !== 0) ? sumArray(outputSums) : '';
						
				var nonEmptyInputValues = inputValues.filter(function (value) {
				  return value !== "" && value !== 0; // 값이 비어있거나 0이 아닌 요소만 필터링
				});			
				var nonEmptyOutputValues = outputValues.filter(function (value) {
				  return value !== "" && value !== 0; // 값이 비어있거나 0이 아닌 요소만 필터링
				});

				var mcount = 0;
				
				if(nonEmptyInputValues.length > nonEmptyOutputValues.length )
						   mcount =  nonEmptyInputValues.length ;
					   else
						   mcount =  nonEmptyOutputValues.length ;
					   
			// 지출부분 합계표작성
			var textAmountFieldNames = Array.from({length: 5}, (_, i) => 'text_arr' + (i + 1)); 					   
							
			textAmountFieldNames.forEach(function(fieldName) {
				var fields = Array.from(document.getElementsByName(fieldName + '[]'));
				var sum = 0;

				fields.forEach(function(field, index) {
					field.addEventListener('input', function() {
						sum = fields.slice(1).reduce(function(total, currField) {
							return total + (parseInt(currField.value) || 0);
						}, 0);
						
						fields[0].value = sum;
						fields[0].dispatchEvent(new Event('change')); // change 이벤트 발생
						console.log('text-arr :' + sum);
					});
				});
			});
			
			
    // 합계표 계산
	
	// 지출부분 차감

	for (let i = 1; i <= 5; i++) {
		let input = document.querySelector('input[name="text_arr' + i + '[]"]:first-of-type');
		totalExpense += parseInt(input.value) || 0;
	}

    totalExpense += totalOutput;

    document.getElementById('totalInput').textContent = totalInput.toLocaleString();
    document.getElementById('totalOutput').textContent = totalOutput.toLocaleString();
    document.getElementById('totalExpense').textContent = totalExpense.toLocaleString();
    document.getElementById('difference').textContent = (totalInput - totalExpense ).toLocaleString();

					

				
		// 변경이 일어날때마다 저장함
				
				// mcount를 form 요소에 저장
				$("#mcount").val(mcount);
				console.log('mcount: ' + mcount);
								
			   if(Number(num)>0) 
				   $("#mode").val('modify');     
				  else
					  $("#mode").val('insert');  
			  
				$.ajax({
					url: "insert.php",
					type: "post",		
					data: $("#board_form").serialize(),
					// dataType:"json",
					success : function( data ){	

                              console.log(data);					
							// reload();
					},
					error : function( jqxhr , status , error ){
						console.log( jqxhr , status , error );
					} 			      		
				   });		
			
    }

	
	
// 이전 데이터 저장을 위한 변수
var previousData = null;

function reload(data) {
    console.log('실행됨');
	$("#memo").val(data.memo);
	$("#receivable").val(data.receivable);
	
	
    for (var i = 0; i < 5; i++) {        
        var textSum1 = data.text_arr1.split(',')[i];
        var textSum2 = data.text_arr2.split(',')[i];
        var textSum3 = data.text_arr3.split(',')[i];
        var textSum4 = data.text_arr4.split(',')[i];
        var textSum5 = data.text_arr5.split(',')[i];

        var row = document.querySelector('#table1 tbody tr:nth-child(' + (i + 1) + ')');
		
        if (row) {  
            var textAmount1 = row.querySelector('.text-amount1');
            var textAmount2 = row.querySelector('.text-amount2');
            var textAmount3 = row.querySelector('.text-amount3');
            var textAmount4 = row.querySelector('.text-amount4');
            var textAmount5 = row.querySelector('.text-amount5');

			if (textAmount1) {
				if (textSum1 !== 0) {
					textAmount1.value = textSum1;
				} else {
					textAmount1.value = '';
				}
			}
            if (textAmount2 && textSum2 !== textAmount2.value) {
                textAmount2.value = textSum2;
            }
            if (textAmount3 && textSum3 !== textAmount3.value) {
                textAmount3.value = textSum3;
            }
            if (textAmount4 && textSum4 !== textAmount4.value) {                
                textAmount4.value = textSum4;
            }
            if (textAmount5 && textSum5 !== textAmount5.value) {                
                textAmount5.value = textSum5;
            }
        }
		
	}  // end of for
    for (var i = 0; i < 150; i++) {
        var inputVal  =  data.input_arr.split(',')[i];
        var outputSumVal = Number(data.output_arr1.split(',')[i]) +  Number(data.output_arr2.split(',')[i]) +  Number(data.output_arr3.split(',')[i]) +  Number(data.output_arr4.split(',')[i])  ;
        var outputVal1 = data.output_arr1.split(',')[i];
        var outputVal2 = data.output_arr2.split(',')[i];
        var outputVal3 = data.output_arr3.split(',')[i];
        var outputVal4 = data.output_arr4.split(',')[i];

        var row = document.querySelector('#table2 tbody tr:nth-child(' + (i + 1) + ')');
        if (row) {
            var inputAmount = row.querySelector('.input-amount');
            var outputsum = row.querySelector('.output-sum');
            var outputAmount1 = row.querySelector('.output-amount1');
            var outputAmount2 = row.querySelector('.output-amount2');
            var outputAmount3 = row.querySelector('.output-amount3');
            var outputAmount4 = row.querySelector('.output-amount4');

            if (inputAmount && inputVal !== inputAmount.value) {
                inputAmount.value = inputVal;
            }
			if (outputsum) {
				if (outputSumVal !== 0) {
					outputsum.value = outputSumVal;
				} else {
					outputsum.value = '';
				}
			}

            if (outputAmount1 && outputVal1 !== outputAmount1.value) {
                outputAmount1.value = outputVal1;
            }
            if (outputAmount2 && outputVal2 !== outputAmount2.value) {
                outputAmount2.value = outputVal2;
            }
            if (outputAmount3 && outputVal3 !== outputAmount3.value) {
                outputAmount3.value = outputVal3;
            }
            if (outputAmount4 && outputVal4 !== outputAmount4.value) {                
                outputAmount4.value = outputVal4;
            }
        }
	}  // end of for
	
   		    
}

// 1초마다 updatetime 체크 및 함수 실행
var intervalId = setInterval(function() {
    var num = $("#num").val();
    $.ajax({
        url: "load_data.php?num=" + num,
        type: "post",
        data: "",
        success: function(data) {
            var parsedData = JSON.parse(data);
            console.log('현재 저장 시간 ' + parsedData.updatetime);

            // 이전 데이터와 현재 데이터 비교
            if (previousData !== null && JSON.stringify(parsedData) === JSON.stringify(previousData)) {
                return; // 데이터가 변경되지 않았으므로 업데이트하지 않음
            }

            previousData = parsedData; // 현재 데이터를 이전 데이터로 저장

            reload(parsedData); // 데이터 업데이트
            calculateTotals();
        },
        error: function(jqxhr, status, error) {
            console.log(jqxhr, status, error);
        }
    });
}, 3000);
	


$(document).ready(function(){
	

// 페이지 로드 시 합계 초기화
calculateTotals();	
	
var state =  $('#state').val();  	
// 처리완료인 경우는 수정하기 못하게 한다.

$("#closeModalBtn").click(function(){ 
    $('#myModal').modal('hide');
});
	
$(".closeBtn").click(function(){    // 저장하고 창닫기	

		myalert("창 닫기!");		
        opener.location.reload();		
		 window.close();	
 });	
		 
$(".delBtn").click(function(){      // del

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
				   cancelButtonText: '취소' })
				   .then((result) => { if (result.isConfirmed) { 
						$.ajax({
								url: "insert.php",
								type: "post",		
								data: $("#board_form").serialize(),
								dataType:"json",
								success : function( data ){
								console.log( data);
								opener.location.reload();
								myalert("파일 삭제 완료!");
								
								setTimeout(function() {												        
										 window.close();	
									   }, 500);		
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

</body>
</html>
