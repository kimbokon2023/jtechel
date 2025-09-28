<?php
session_start();

$root_dir = $_SERVER['DOCUMENT_ROOT'] ;

ini_set('display_errors','0');  // 화면에 warning 없애기	

 if(!isset($_SESSION["level"]) || $_SESSION["level"]>5) {
          /*   alert("관리자 승인이 필요합니다."); */
		 sleep(1);
         header("Location:http://j-techel.co.kr/game/login/logout.php"); 
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
require_once($_SERVER['DOCUMENT_ROOT'] . "/lib/mydb.php");
$pdo = db_connect();	

 try{
	  $sql = "select * from jtechel.game_fee where num = ? ";
	  $stmh = $pdo->prepare($sql); 
      $stmh->bindValue(1,$num,PDO::PARAM_STR); 
      $stmh->execute();
      $count = $stmh->rowCount();            
	  $row = $stmh->fetch(PDO::FETCH_ASSOC);  // $row 배열로 DB 정보를 불러온다.		
		 
	  include 'rowDB_fee.php';
	  
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
	$mode="modify";
}

 
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

	
@media (max-width: 1000px) {
    .card-title {
        font-size: 40px; /* or any other size that you want */
    }
	
    .card-body {
        font-size: 30px; /* or any other size that you want */
    }

    .form-group label {
        font-size: 40px; /* or any other size that you want */
    }

    .form-group input {
        font-size: 50px; /* or any other size that you want */
    }
	
    .form-group select {
        font-size: 50px; /* or any other size that you want */
    }

    .table th, .table tr, .table td {
        font-size: 40px; /* or any other size that you want */
    }
	
	.table td input{
        font-size: 42px; /* or any other size that you want */
    }	

    .sideBanner {
        font-size: 50px; /* or any other size that you want */		
    }  
}

</style>	
	

<div class="container-fluid">
    <div class="row justify-content-center align-items-center w-100 ">
        <div class="col-lg-9 text-center">
            <div class="card align-middle justify-content-center w-70" style="border-radius: 20px;">
                <div class="card-body">
                    <span class="card-title mt-2 mb-5" style="color: #113366; ">지출 자료 입력(단위:천원)</span> <br>
					
					
                    <form id="board_form" name="board_form" class="form-signin" method="post">
                      
						<input type="hidden" id="mode" name="mode" value="<?=$mode?>">
						<input type="hidden" id="num" name="num" value="<?=$num?>" >                        
						<input type="hidden" id="user_name" name="user_name" value="<?=$user_name?>" size="4" >						
						<input type="hidden" id="mcount" name="mcount"  >

					<div class="form-group mt-5 mb-5">
					  <label for="registedate" class="form-control fs-1" style="width:35%;">등록일자</label>
						 <input type="date" id="registedate" name="registedate" required value="<?=$registedate?>" class="form-control fs-1">
					</div>
<table class="table table-bordered">
    <tr>
        <td colspan="2" class="text-right"><strong>지출합</strong></td>
        <td><span id="totalInput" class="total-amount">0</span></td>
    </tr>
</table>

<style>
    .table td, .table th {
        vertical-align: middle;
    }
</style>

<table class="table table-bordered">
    <thead>
        <tr>
            <th>번호</th>
            <th>지출구분</th>
            <th>금액</th>
        </tr>
    </thead>
    
    <tbody>
        <tr>
            <td>1</td>
            <td>일비</td>
            <td><input type="text" id="text1" name="text1" inputmode="numeric" pattern="[0-9]{1,4}" required class="form-control output-amount" value="<?=$text1?>" oninput="calculateTotal()"></td>
        </tr>
        <tr>
            <td>2</td>
            <td>식대</td>
            <td><input type="text" id="text2" name="text2" inputmode="numeric" pattern="[0-9]{1,4}" required class="form-control output-amount" value="<?=$text2?>" oninput="calculateTotal()"></td>
        </tr>
        <tr>
            <td>3</td>
            <td>집세</td>
            <td><input type="text" id="text3" name="text3" inputmode="numeric" pattern="[0-9]{1,4}" required class="form-control output-amount" value="<?=$text3?>" oninput="calculateTotal()"></td>
        </tr>
        <tr>
            <td>4</td>
            <td>서비스</td>
            <td><input type="text" id="text4" name="text4" inputmode="numeric" pattern="[0-9]{1,4}" required class="form-control output-amount" value="<?=$text4?>" oninput="calculateTotal()"></td>
        </tr>
        <tr>
            <td>5</td>
            <td>기타</td>
            <td><input type="text" id="text5" name="text5" inputmode="numeric" pattern="[0-9]{1,4}" required class="form-control output-amount" value="<?=$text5?>" oninput="calculateTotal()"></td>
        </tr>
    </tbody>
</table>

<script>
    function calculateTotal() {
        var text1 = parseInt(document.getElementById("text1").value.replace(/,/g, "")) || 0;
        var text2 = parseInt(document.getElementById("text2").value.replace(/,/g, "")) || 0;
        var text3 = parseInt(document.getElementById("text3").value.replace(/,/g, "")) || 0;
        var text4 = parseInt(document.getElementById("text4").value.replace(/,/g, "")) || 0;
        var text5 = parseInt(document.getElementById("text5").value.replace(/,/g, "")) || 0;
        
        var total = text1 + text2 + text3 + text4 + text5;
        
        document.getElementById("totalInput").textContent = numberWithCommas(total);
    }
    
    function numberWithCommas(number) {
        return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
</script>


					<div class="form-group mt-3 mb-3">
							<h3 class="form-signin-heading fs-2">메모 </h3>
						 <textarea  rows="4"  cols="30"  id="memo" name="memo" class="form-control fs-2"><?=$memo?></textarea>
					</div>
					<div class="form-group mt-3 mb-3">
							<h3 class="form-signin-heading fs-2">미수 </h3>
						 <textarea  rows="2"  cols="30" id="receivable" name="receivable" class="form-control fs-2"><?=$receivable?></textarea>
					</div>


				
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="sideBanner fs-2">
  <div class="mb-1 mt-1 fs-3">
    <button type="button" class="btn btn-dark rounded-pill saveBtn fs-1">저장</button>
  </div>
  <div class="mb-1 mt-1 text-primary fs-3">
    <button type="button" class="btn btn-danger rounded-pill delBtn fs-1">삭제</button>
   </div>
  <div class="mb-1 mt-1 text-secondary fs-3">
    <button type="button" class="btn btn-secondary rounded-pill closeBtn fs-1">닫기</button>
   </div>
</div>

<script> 

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
 
 </script>





</body>
</html>

<script> 

$(document).ready(function(){
var state =  $('#state').val();  	
// 처리완료인 경우는 수정하기 못하게 한다.

calculateTotal();

$("#closeModalBtn").click(function(){ 
    $('#myModal').modal('hide');
});
	


$(".closeBtn").click(function(){    // 저장하고 창닫기	
		opener.location.reload();
		myalert("창 닫기!");
		
		setTimeout(function() {												        
				 window.close();	
			   }, 1000);	
	 });	
				
$(".saveBtn").click(function(){      // DATA 저장버튼 누름
	var num = $("#num").val();  	    
    var user_name = $("#user_name").val(); 
		
		
if( user_name!=='') {  
   if(Number(num)>0) 
       $("#mode").val('modify');     
      else
          $("#mode").val('insert');  

 console.log($("#board_form").serialize());
	  
	$.ajax({
		url: "insert_fee.php",
		type: "post",		
		data: $("#board_form").serialize(),
		// dataType:"json",
		success : function( data ){
			console.log( data);
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
	} // end of if
		else
		$('#myModal').modal('show');  
		
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
								url: "insert_fee.php",
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

// 두날짜 사이 일자 구하기 
const getDateDiff = (d1, d2) => {
  const date1 = new Date(d1);
  const date2 = new Date(d2);
  
  const diffDate = date1.getTime() - date2.getTime();
  
  return Math.abs(diffDate / (1000 * 60 * 60 * 24)); // 밀리세컨 * 초 * 분 * 시 = 일
}


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
