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


$maxrow  = 150;
   

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
		font-weight: normal;
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
		padding : 8px;
		font-weight: normal;
    }
	
	.table td input{
        font-size: 28px; /* or any other size that you want */
		font-weight: normal;
	
    }	


}
		
    th, td{
        vertical-align: middle;
        text-align: center;
    }

</style>	
	

<form id="board_form" name="board_form" class="form-signin" method="post">
<div class="container-fluid">
    <div class="row justify-content-center align-items-center mt-2">        
            <div class="card align-middle justify-content-center mt-2 " style="border-radius: 20px;">
                <div class="card-body  justify-content-center mt-2">
				  <div class="d-flex mb-4 mt-4 justify-content-center align-items-center">
                    <span class="card-title" style="color: #113366; ">회원별 입력</span> 
					&nbsp;&nbsp;&nbsp; 
					&nbsp;&nbsp;
					<button type="button" class="btn btn-primary saveBtn fs-2">저장</button>	
					&nbsp;&nbsp;
					<button type="button" class="btn btn-danger delBtn fs-2">삭제</button>	
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;					
					<button type="button" class="btn btn-secondary closeBtn fs-2">닫기</button>					
					</div>
					
					
                    
                      
							<input type="hidden" id="mode" name="mode" value="<?=$mode?>">
							<input type="hidden" id="num" name="num" value="<?=$num?>" >                        
							<input type="hidden" id="user_name" name="user_name" value="<?=$user_name?>" size="4" >
							<input type="hidden" id="mcno" name="mcno[]" value="<?=$mcno?>" size="4" >
							<input type="hidden" id="inputsum" name="inputsum" value="" size="4" >
							<input type="hidden" id="outputsum" name="outputsum" value="" size="4" >
							<input type="hidden" id="mcount" name="mcount"  >
							<input type="hidden" id="updatetime" name="updatetime" value="<?=$updatetime?>" >
							<input type="hidden" id="total0" name="total0" value="<?=$total0?>" >

<div class="form-group mb-2 mt-5">
  <div class="row align-items-center">
    <div class="col">
      <label for="registedate" class="d-inline-block" style="white-space: nowrap;">등록 일자</label>
    </div>
    <div class="col-sm-6">
      <input type="date" id="registedate" name="registedate" required value="<?=$registedate?>" class="form-control" style="width: 100%;">
    </div>
    <div class="col-sm-3">
      &nbsp;
    </div>
  </div>
</div>

<div class="form-group mb-3 mt-1">
  <div class="row align-items-center">
    <div class="col">
      <label for="search" class="d-inline-block" style="white-space: nowrap;">회원 검색</label>
    </div>
    <div class="col">        
      <input type="text" id="search" name="search" value="<?$search?>" class="form-control" style="text-align: left;" onkeydown="handleKeyPress(event)">        
    </div>
    <div class="col">        		  
      <button type="button" id="searchBtn" class="btn btn-dark btn-lg form-control fs-3" onclick="openPopup()">검색</button>      
    </div>
  </div>
</div> 


  <div class="d-flex align-items-center  justify-content-center mb-2 mt-3">
    <div class="col-md-2">
      <span> 회원 이름 </span>
    </div>
    <div class="col-md-3">
      <input type="text" id="guest_name" name="guest_name" value="<?$guest_name?>"  size=8 style="text-align: left;" >        
    </div>
    <div class="col-md-2">
     <span> 전화 번호 </span>
    </div>
    <div class="col-md-3">
      <input type="text" id="guest_tel" name="guest_tel" value="<?$guest_tel?>" size=10 style="text-align: left;" >        
    </div>
  </div>

		<table id="table2" class="table table-bordered" >
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
        for ($i = 1; $i <= $maxrow ; $i++) {
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

<script> 

  function receive_guest(guest_name, guest_tel) {
    // 부모 창에서 전달받은 값 처리 로직을 구현합니다.
   $("#guest_name").val(guest_name);
   $("#guest_tel").val(guest_tel);
  }

  function handleKeyPress(event) {
    if (event.keyCode === 13) {
      openPopup();
    }
  }
  
  function openPopup() {
    // 팝업 창을 여는 로직을 구현합니다.
    // 예시로 alert 함수를 사용하여 경고창을 띄우도록 작성하였습니다.
    popupCenter('./search_member.php?search=' + $("#search").val(), '회원검색', 800, 600)
  }


$(document).ready(function(){
	
	
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
								url: "insert_member.php",
								type: "post",		
								data: $("#board_form").serialize(),
								dataType:"json",
								success : function( data ){
								console.log( data);
								opener.location.reload();
								// myalert("파일 삭제 완료!");
								
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
