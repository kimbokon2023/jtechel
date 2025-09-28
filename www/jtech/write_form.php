<?php

session_start(); 

$level= $_SESSION["level"];
$id_name= $_SESSION["name"];   
$user_name= $_SESSION["name"];  

// 모바일 사용여부 확인하는 루틴
$mAgent = array("iPhone","iPod","Android","Blackberry", 
    "Opera Mini", "Windows ce", "Nokia", "sony" );
$chkMobile = false;
for($i=0; $i<sizeof($mAgent); $i++){
    if(stripos( $_SERVER['HTTP_USER_AGENT'], $mAgent[$i] )){
        $chkMobile = true;
		// print '권영철';
        break;
    }
}


// 스케줄에서 띄울때는 네이바를 나타내지 않는다. navibar=1 이면 나타내지 않음
$navibar=$_REQUEST["navibar"];
  
 // $file_dir = './uploads/'; 
  

include "_request.php";

$URLsave = "http://j-techel.co.kr/jtech/viewpic.php?num=" . $num;

require_once("../lib/mydb.php");
$pdo = db_connect();
	 
if($num!=0)
{	  
	try{
		 $sql = "select * from jtechel.jtech where num=?";
		 $stmh = $pdo->prepare($sql);  
		 $stmh->bindValue(1, $num, PDO::PARAM_STR);      
		 $stmh->execute();            
		  
		 $row = $stmh->fetch(PDO::FETCH_ASSOC); 	
		  
		 include 'rowDB.php';  
		 
		 $piclist = $row["piclist"] ?? '{}'; // piclist 값이 없을 경우의 기본값

				  if($measureday!="0000-00-00" and $measureday!="1970-01-01" and $measureday!="")   $measureday = date("Y-m-d", strtotime( $measureday) );
						else $measureday="";
				  if($workday!="0000-00-00" and $workday!="1970-01-01"  and $workday!="")  $workday = date("Y-m-d", strtotime( $workday) );
						else $workday="";			      
				  if($demand!="0000-00-00" and $demand!="1970-01-01" and $demand!="")  $demand = date("Y-m-d", strtotime( $demand) );
						else $demand="";			
				  if($donedemand!="0000-00-00" and $donedemand!="1970-01-01" and $donedemand!="")  $donedemand = date("Y-m-d", strtotime( $donedemand) );
						else $donedemand="";			
				  if($doneday!="0000-00-00" and $doneday!="1970-01-01" and $doneday!="")  $doneday = date("Y-m-d", strtotime( $doneday) );
						else $doneday="";			
				  if($regist_day!="0000-00-00" and $regist_day!="1970-01-01" and $regist_day!="")  $regist_day = date("Y-m-d", strtotime( $regist_day) );
						else $regist_day="";	


					 
			$customer_data = $row["customer"];

			// JSON 데이터를 PHP 객체로 디코딩
			$customer_object = json_decode($customer_data);

			if ($customer_object === null) {
				// JSON 디코딩에 실패한 경우 처리
				// (올바르지 않은 JSON 형식일 경우, null을 반환합니다)
				// echo "JSON 디코딩에 실패했습니다.";
			} else {
				// 디코딩된 데이터를 각 변수에 할당
				$customer_date = $customer_object->customer_date;
				$customer_company = $customer_object->customer_company;
				$customer_address = $customer_object->customer_address;
				$customer_group = $customer_object->customer_group;
				$customer_name = $customer_object->customer_name;
				$customer_worklist1 = $customer_object->customer_worklist1;
				$customer_worklist2 = $customer_object->customer_worklist2;
				$image_url = $customer_object->image_url;
				
				// var_dump($image_url);
				
				// 날짜를 '-'를 기준으로 분할하여 배열로 저장
				$date_parts = explode('-', $customer_date);

				// 연도, 월, 일 추출
				$year = $date_parts[0];
				$month = $date_parts[1];
				$day = $date_parts[2];

				// 이제 각 변수에 할당된 값들을 사용할 수 있습니다.
			}


						
						
		 }catch (PDOException $Exception) {
		   print "오류: ".$Exception->getMessage();
		 }
}
	else   // 신규자료 등록일 경우
	{     
		$todate=date("Y-m-d");  // 현재일 저장   
		$regist_day=$todate;
		$tmpKey = uniqid();		
	}

// copydata인 경우는 num은 null로 지정함

if($mode === 'copydata')
{
   $num=0;
   $tmpKey = uniqid();
}

if($num != 0) {
    $picsGroupedByItem = [];
    $tablename = 'jtech';

    // 모든 사진을 한 번의 쿼리로 가져오기
    $sql = "SELECT * FROM jtechel.picuploads WHERE tablename = '$tablename' AND parentnum = '$num'";

    try {
        $stmh = $pdo->query($sql);
        while ($row = $stmh->fetch(PDO::FETCH_ASSOC)) {
            $picsGroupedByItem[$row['item']][] = $row;
        }
    } catch (PDOException $Exception) {
        print "오류: " . $Exception->getMessage();
    }

    // 각 아이템별로 사진 개수 세기
    $picNum = isset($picsGroupedByItem['before']) ? count($picsGroupedByItem['before']) : 0;
    $MidpicNum = isset($picsGroupedByItem['mid']) ? count($picsGroupedByItem['mid']) : 0;
    $AfterpicNum = isset($picsGroupedByItem['after']) ? count($picsGroupedByItem['after']) : 0;

    // 사진명만을 포함하는 배열 생성
    $picData = isset($picsGroupedByItem['before']) ? array_column($picsGroupedByItem['before'], 'picname') : [];
    $MidpicData = isset($picsGroupedByItem['mid']) ? array_column($picsGroupedByItem['mid'], 'picname') : [];
    $AfterpicData = isset($picsGroupedByItem['after']) ? array_column($picsGroupedByItem['after'], 'picname') : [];
	
    // 목록 구성된 사진배열
    $picDataArr = isset($picsGroupedByItem['beforeArr']) ? array_column($picsGroupedByItem['beforeArr'], 'picname') : [];
    $MidpicDataArr = isset($picsGroupedByItem['midArr']) ? array_column($picsGroupedByItem['midArr'], 'picname') : [];
    $AfterpicDataArr = isset($picsGroupedByItem['afterArr']) ? array_column($picsGroupedByItem['afterArr'], 'picname') : [];
	
	$picIdx = isset($picsGroupedByItem['beforeArr']) ? array_column($picsGroupedByItem['beforeArr'], 'idx') : [];
	$MidpicIdx = isset($picsGroupedByItem['midArr']) ? array_column($picsGroupedByItem['midArr'], 'idx') : [];
	$AfterpicIdx = isset($picsGroupedByItem['afterArr']) ? array_column($picsGroupedByItem['afterArr'], 'idx') : [];
	
}

   
// 시공팀 배열로 가져오기      
$sql="select * from jtechel.member "; 					

try{  
   $stmh = $pdo->query($sql);               
   $worker_arr=array();

   while($row = $stmh->fetch(PDO::FETCH_ASSOC)) {	
		if($row['part']=='jtech')	    // 제이테크에 해당되는 회원들만 가져오기  
 			  array_push($worker_arr,$row["name"]);			 
	 } 	 
   } catch (PDOException $Exception) {
    print "오류: ".$Exception->getMessage();
}     

// var_dump($worker_arr);
sort($worker_arr);  // 오름차순으로 배열 정렬   
$worker_arr = array_unique($worker_arr);      
sort($worker_arr);  // 오름차순으로 배열 정렬  


if(!in_array($worker, $worker_arr))   // 배열값에 없으면 넣어준다
		array_push($worker_arr,$worker);	 // 마지막에 공백하나 넣기
   else
		array_push($worker_arr,"");	 // 마지막에 공백하나 넣기
   
 ?>
 

<!DOCTYPE html>
<meta charset="UTF-8">
<html>
<head>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<!-- Form Validator  제이쿼리 다음에 나와야 한다. 순서가 중요함 자바스크립트는 순서가 중요함 제이쿼리 먼저 나와야 함 -->
<script src="http://cdnjs.cloudflare.com/ajax/libs/jquery.form/3.32/jquery.form.js"></script>
<script src="http://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.11.1/jquery.validate.min.js"></script>
<link rel="stylesheet" href="https://uicdn.toast.com/tui.pagination/latest/tui-pagination.css" />
<script src="https://uicdn.toast.com/tui.pagination/latest/tui-pagination.js"></script>
<link rel="stylesheet" href="https://uicdn.toast.com/tui-grid/latest/tui-grid.css"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.css">
<script src="https://uicdn.toast.com/tui-grid/latest/tui-grid.js"></script>
<!-- CSS only -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.1/font/bootstrap-icons.css">
<!-- 화면에 UI창 알람창 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>

<!-- Bootstrap 4.3.1 -->
<script src="plugins/bootstrap/js/bootstrap.min.js"></script>
<!-- JavaScript Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Slick Slider -->
<script src="plugins/slick-carousel/slick/slick.min.js"></script>
<!--  Magnific Popup-->
<script src="plugins/magnific-popup/dist/jquery.magnific-popup.min.js"></script>

<!-- Google Map -->
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBu5nZKbeK-WHQ70oqOWo-_4VmwOwKP9YQ"></script>
<script src="plugins/google-map/gmap.js"></script>

<script src="js/script.js"></script>
	
<script src="http://j-techel.co.kr/common.js"></script>

<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.10/dist/sweetalert2.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.js"></script>

<!-- navibarsub css -->  

<link rel="stylesheet" href="css/style2.css">

</head>

<body>
<title> 제이테크 수주내역 </title>

<style>
@import url("https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.1/font/bootstrap-icons.css");

fieldset.groupbox-border {
border: 1px groove #ddd !important; 
padding: 3 3 3 3 !important; 
margin: 3 3 3 3 !important; 
box-shadow: 0px 0px 0px 0px #000; 
} 

legend.groupbox-border { 
    background-color: #F0F0F0;
    color: #000;
    padding: 3px 6px;
font-size: 1.0em !important; 
font-weight: bold !important; 
text-align: left !important; 
border-bottom:none; 
}  

fieldset.groupbox1-border {
border: 1px groove #ddd !important; 
padding: 3 3 3 3 !important; 
margin: 3 3 3 3 !important; 
} 

legend.groupbox1-border { 
    background-color: #F0F0F0;
    color: #000;
    padding: 9px 9px;
font-size: 1.0em !important; 
font-weight: bold !important; 
text-align: left !important; 
border-bottom:none; 
}   


.rotated {
  transform: rotate(90deg);
  -ms-transform: rotate(90deg); /* IE 9 */
  -moz-transform: rotate(90deg); /* Firefox */
  -webkit-transform: rotate(90deg); /* Safari and Chrome */
  -o-transform: rotate(90deg); /* Opera */
}
  
a {
	text-decoration:none;
  }	  
  
.uploaded-image {
    width: 25vw; /* 화면의 4분의 1 가로폭 */
    max-width: 100%; /* 이미지가 컨테이너를 넘어가지 않게 함 */
    height: auto; /* 원본 비율 유지 */
}
  
</style>

<form id="board_form"  name="board_form"  method="post" enctype="multipart/form-data"  >

<div class="container-fluid">


<?php
  if($navibar!='1')
	include 'navbarsub.php'; 
?>

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

	<input type="hidden" id="mode" name="mode" value="<?=$mode?>">
	<input type="hidden" id="num" name="num" value="<?=$num?>" >			  								
	<input type="hidden" id="user_name" name="user_name" value="<?=$user_name?>" > 					
	<input type="hidden" id="update_log" name="update_log" value="<?=$update_log?>"  > 					
	<input type="hidden" id="tablename" name="tablename" value="<?=$tablename?>"  > 					
	<input type="hidden" id="item" name="item" value="<?=$item?>"  > 			
	<input type="hidden" id="page" name="page" value="<?=$page?>"  > 			
	<input type="hidden" id="scale" name="scale" value="<?=$scale?>"  > 			
	<input type="hidden" id="piclist" name="piclist" value="<?=$piclist?>"  > 		
	<input type="hidden" id="tmpKey" name="tmpKey" value="<?=$tmpKey?>">    <!-- 임시키를 부여함 새로작성할때 저장시 num 없음 방지 -->				

	<input id="pInput" name="pInput" type=hidden value="0" >	
	<input id="MidpInput" name="MidpInput" type=hidden value="0" >	
	<input id="AfterpInput" name="AfterpInput" type=hidden value="0" >	
		
<div class="d-flex p-2 justify-content-center" >	 
	<div class="card mt-2" style="width:100%;" >	
		<div class="card-header text-center " > 					

		   <?php if($chkMobile)      
			   {
				  $btn = " btn-lg ";	
				  $fs = "fs-2"	  ;
				  $fs_small = "fs-4"	  ;
				  $size = "40";
			   }
			   else
			   {          
				  $fs = "fs-5"	  ;
				  $fs_small = "fs-5"	  ;
				  $size = "60";
			   }		   
			   
			?>	   
					<button type="button" id="listBtn"  class="btn btn-secondary <?=$btn?> "  > <i class="bi bi-card-list"></i> 목록 </button> 
					<?php if($level<=2) { ?>
					<button type="button" id="newBtn"  class="btn btn-secondary <?=$btn?> " > <i class="bi bi-arrow-clockwise"></i> 새입력 </button> 
					<?php  } ?>
					<button type="button" id="saveBtn"  class="btn btn-secondary <?=$btn?> "> <i class="bi bi-hdd-fill"></i> 저장 </button>
					<?php if($level<=2) { ?>
					<button type="button" id="delBtn"  class="btn btn-danger <?=$btn?> " > <i class="bi bi-trash2"></i> 삭제</button>	
					<?php  } ?>
					<?php if($mode!='new' and $level<=2 ) {  ?>
					     <button type="button" id="copydataBtn"  class="btn btn-primary  <?=$btn?> " > <i class="bi bi-clipboard2-data-fill"></i>자료복사</button>	
					<?php  }  ?>
					
					&nbsp;&nbsp;&nbsp;
					<?php if($level<=2) { ?>
					<button id="piclistBtn" class="btn btn-dark  <?=$btn?> " type="button">사진대지 목록 만들기</button>
					<?php  } ?>
					
					&nbsp;&nbsp;&nbsp;
					<?php if($level<=2) { ?>
					<button id="estimateBtn" class="btn btn-success  <?=$btn?> " type="button">견적서 생성</button>
					<?php  } ?>
					
					<input type="hidden" id="Id_uploadfile" name="Id_uploadfile" >						
					<input type="hidden" id="myfiles" name="myfiles[]" >									
				
	</div>
	</div>
	</div>
<div class="d-flex p-1 justify-content-center" >			
 
	<table class="table table-bordered table-sm">
	<thead>
	  <tr>
	   <th class="col" style="width:15%;"> &nbsp; </th>
	   <th class="col" style="width:15%;"> &nbsp; </th>
	   <th class="col" style="width:15%;"> &nbsp; </th>
	   <th class="col" style="width:15%;"> &nbsp; </th>
	   <th class="col" style="width:15%;"> &nbsp; </th>
	   <th class="col" style="width:15%;"> &nbsp; </th>
	   </tr>
	</thead>
	   
  <tbody>
    <tr>
      <td class="text-center <?=$fs?>" >  현장(Project)번호 </td>
      <td colspan="5" >   <input  type="text"  class="text-start <?=$fs?> form-control "  name="pjnum" id="pjnum" value="<?=$pjnum?>"  required > </td>
    </tr>
    <tr>
      <td class="text-center <?=$fs?>" >  공사명 </td>
      <td colspan="5" >   <input  type="text"  class="text-start <?=$fs?> form-control "  name="workplacename" id="workplacename" value="<?=$workplacename?>"  required > </td>
    </tr>
	<tr>
      <td class="col text-center <?=$fs?>" >  주  소 </td>
      <td  colspan="5" >  
	   <input  class="text-left <?=$fs?> form-control "   type="text" name="address" id="address" value="<?=$address?>"  > 							
	  </td>
    </tr>
	
	<tr>
      <td class="col text-center <?=$fs_small?>" >  원청 
	  		<?php if($level<=2) { ?>
			<button type="button" id="searchFirstordBtn"  class="btn btn-secondary <?=$btn?> " > <i class="bi bi-search"></i> </button>	
		<?php  } ?>	
	  </td>
	  <td class="col" >  
		  <input type="text" class="text-left <?=$fs_small?>  form-control"  name="firstord" id="firstord" onkeydown="JavaScript:SearchFristordEnter();"  value="<?=$firstord?>"  > 	
	  </td>
      <td class="col text-center <?=$fs_small?>" >  원청(PM) </td>
	  <td class="col" >  
		 <input type="text" class="text-left <?=$fs_small?> form-control" name="firstordman" id="firstordman"value="<?=$firstordman?>"  > 							
	  </td>
      <td class="col text-center <?=$fs_small?>" >  연락처 </td>
	  <td class="col" >  
		 <input type="text"  class="text-left <?=$fs_small?> form-control"   name="firstordmantel" id="firstordmantel" value="<?=$firstordmantel?>"  > 							  						  
	  </td>
    </tr>
	<tr>
      <td class="col text-center <?=$fs_small?>" >  발주처 
	  		<?php if($level<=2) { ?>
			<button type="button"  id="searchSecondordBtn"  class="btn btn-secondary <?=$btn?> " > <i class="bi bi-search"></i> </button>	
		<?php  } ?>	
	  </td>
	  <td class="col  form-control" >  
		  <input type="text" class="text-left <?=$fs_small?>  form-control"  name="secondord" id="secondord" onkeydown="JavaScript:SearchSecondordEnter();" value="<?=$secondord?>"  > 	

	  </td>
      <td class="col text-center <?=$fs_small?>" >  발주처담당 </td>
	  <td class="col" >  
		 <input type="text" class="text-left <?=$fs_small?> form-control" name="secondordman" id="secondordman"  value="<?=$secondordman?>"  > 					
	  </td>
      <td class="col text-center <?=$fs_small?>" >  연락처 </td>
	  <td class="col" >  
		 <input type="text"  class="text-left <?=$fs_small?> form-control"   name="secondordmantel" id="secondordmantel"  value="<?=$secondordmantel?>"  > 	
	  </td>
    </tr>
	<tr>
      <td class="col text-center <?=$fs_small?>" > 
	  </td>
	  <td class="col  form-control" >  
	  </td>
      <td class="col text-center <?=$fs_small?>" >  현장담당 </td>
	  <td class="col" >  
		 <input type="text" class="text-left <?=$fs_small?> form-control" name="chargedman" id="chargedman" value="<?=$chargedman?>"  > 					
	  </td>
      <td class="col text-center <?=$fs_small?>" >  연락처 </td>
	  <td class="col" >  
		 <input type="text"  class="text-left <?=$fs_small?> form-control"   name="chargedmantel" id="chargedmantel" value="<?=$chargedmantel?>"  > 	
	  </td>
    </tr>
	<tr>
      <td class="col text-center <?=$fs_small?>" > 
	  </td>
	  <td class="col  form-control" >  
	  </td>
      <td class="col text-center text-success <?=$fs_small?>" >  시공팀지정 </td>
	  <td class="col" colspan="2" >  
		
			  <select name="worker" id="worker"  class="form-control text-center text-success <?=$fs_small?>"  >
				   <?php		 

				   for($i=0;$i<count($worker_arr);$i++) {
						 if($worker==$worker_arr[$i])
									print "<option selected value='" . $worker_arr[$i] . "'> " . $worker_arr[$i] .   "</option>";
							 else   
								print "<option value='" . $worker_arr[$i] . "'> " . $worker_arr[$i] .   "</option>";
				   } 		   
						?>	  
				</select> &nbsp;&nbsp;
	  </td>
     
    </tr>
	
	<tr>
      <td class="col text-center <?=$fs_small?>" >  접수 	  		
	  </td>
	  <td class="col  form-control" >  
		  <input type="date"  class="text-left <?=$fs_small?> form-control"  name="regist_day" id="regist_day" value="<?=$regist_day?>"  > 

	  </td>
      <td class="col text-center <?=$fs_small?>" >  실측 </td>
	  <td class="col" >  
		 <input type="date"  class="text-left <?=$fs_small?> form-control"  name="measureday" id="measureday?" value="<?=$measureday?>"  > 
	  </td>
      <td class="col text-center <?=$fs_small?>" >  공사예정 </td>
	  <td class="col" >  
		 <input type="date"  class="text-left <?=$fs_small?> form-control"  name="workday" id="$workday?" value="<?=$workday?>"  > 	
	  </td>
    </tr>
	
	<tr>
      <td class="col text-center <?=$fs_small?>" >  시공완료 	  		
	  </td>
	  <td class="col  form-control" >  
		  <input type="date"  class="text-left <?=$fs_small?> form-control" name="doneday" id="doneday" value="<?=$doneday?>"  > 

	  </td>
      <td class="col text-center <?=$fs_small?>" >  대금청구 </td>
	  <td class="col" >  
		 <input type="date"  class="text-left <?=$fs_small?> form-control" name="demand" id="demand" value="<?=$demand?>"  > 	
	  </td>
      <td class="col text-center <?=$fs_small?>" >  대금지급 </td>
	  <td class="col" >  
		 <input type="date"  class="text-left <?=$fs_small?> form-control" name="donedemand" id="donedemand" value="<?=$donedemand?>"  > 	
	  </td>
    </tr>
	
	<tr> <td> </td>
	</tr>
	
	<tr>
      <td class="col  text-primary text-center <?=$fs_small?>" >  품명1 	  		
	  </td>
	  <td class="col   form-control" >  
	    <input  type="text" class="text-left <?=$fs_small?> form-control" id="material1"  name="material1"  value="<?=$material1?>" >		  

	  </td>
      <td class="col  text-primary text-center <?=$fs_small?>" >  품명2  </td>
	  <td class="col" >  
		 <input  type="text"  class="text-left <?=$fs_small?> form-control"  id="material2" name="material2"  value="<?=$material2?>" >								 
	  </td>
      <td class="col  text-primary  text-center <?=$fs_small?>" > 품명3  </td>
	  <td class="col" >  
		<input  type="text"  class="text-left <?=$fs_small?> form-control"  id="material3" name="material3"  value="<?=$material3?>" > 		
	  </td>
    </tr>
	<tr>
      <td class="col  text-primary text-center <?=$fs_small?>" >  품명4 	  		
	  </td>
	  <td class="col   form-control" >  
	    <input  type="text" class="text-left <?=$fs_small?> form-control" id="material4"  name="material4"  value="<?=$material4?>" >		  

	  </td>
      <td class="col  text-primary text-center <?=$fs_small?>" >  품명5  </td>
	  <td class="col" >  
		 <input  type="text"  class="text-left <?=$fs_small?> form-control"  id="material5" name="material5"  value="<?=$material5?>" >
	  </td>      
    </tr>
	
  </tbody>
</table>					 
</div>


 <div class="card text-center" >	  
 
	<div class="card-header mt-2 mb-2 text-center" > 	

			<?php
			   if(!empty($image_url))
				   print '<img id="signatureBtn" src="' . $image_url . '" style="width:10%;" >';
			?>	
					
				<button type="button" id="signatureBtn" class="btn btn-primary fs-2 "  > 현대 고객확인용 서명 </button>  &nbsp;  	
				<button type="button" id="signatureBtn2" class="btn btn-success fs-2 "  > 일반 고객확인용 서명  </button>  &nbsp;  	
    </div>
</div>	

	<div class="card" >	
		<div class="card-header" > 			
		
					<?php if($level<=2) { ?>
					<div class="card"> 					 
				
					   <div class="card-header <?=$fs_small?>">세부발주 내역 (본사만 보는 내용)</div> 					 
						<div class="card-body">	
			               <div class="d-flex  justify-content-center" >	  									
							<textarea type="text" class="form-control <?=$fs_small?>" id="memo"  name="memo" rows=2 placeholder="세부내역 입력" ><?=$memo?></textarea>						
							
						</div> 	
						</div> 	
					 </div>		  
					<?php } ?>
					<div class="card"> 					 						
					   <div class="card-header text-danger <?=$fs_small?>">시공팀 기록 내역 (파트너,시공팀 전달내용 기록란)</div> 					 
						<div class="card-body">	
			               <div class="d-flex  justify-content-center" >	  									
							<textarea type="text" class="form-control <?=$fs_small?>" id="memo2"  name="memo2" rows=2 placeholder="시공팀, 파트너 등 전달사항 등 입력합니다." ><?=$memo2?></textarea>						
							
						</div> 	
						</div> 	
					 </div>	
			
					 
				<div class="d-flex justify-content-center m-1 <?=$fs_small?>">	 
					<fieldset class="groupbox-border col-sm-4 justify-content-center <?=$fs_small?>"> 
					   <legend class="groupbox-border justify-content-center"> <strong> 시공(전) 사진 </strong>
					        <?php if($filename!=null) print $filename ?>	&nbsp;&nbsp;&nbsp;&nbsp;			   
							<input id="upfile"  name="upfile[]" class="form-control <?=$fs_small?>"  type="file" onchange="this.value" multiple accept=".gif, .jpg, .png">
					   </legend> 					 
						<div class="input-group p-1 mb-1 mt-1 justify-content-center" id="displayBeforePicture">	
						
						</div> 				
					 </fieldset>
					<fieldset class="groupbox-border col-sm-4 justify-content-center"> 
					   <legend class="groupbox-border text-success justify-content-center"> <strong> 시공(중) 사진 </strong>
					        <?php if($filename!=null) print $filename ?>	&nbsp;&nbsp;&nbsp;&nbsp;			   
							<input id="Midupfile"  name="Midupfile[]" class="form-control <?=$fs_small?>"  type="file" onchange="this.value" multiple accept=".gif, .jpg, .png">
					   </legend> 					 
						<div class="input-group p-1 mb-1 mt-1  justify-content-center" id = "displayMidPicture" >	
						
						</div> 				
					 </fieldset>
					<fieldset class="groupbox-border col-sm-4 justify-content-center"> 
					   <legend class="groupbox-border text-danger justify-content-center"> <strong> 시공(후) 사진 </strong>
					        <?php if($filename!=null) print $filename ?>	&nbsp;&nbsp;&nbsp;&nbsp;			   
							<input id="Afterupfile"  name="Afterupfile[]" class="form-control <?=$fs_small?>"  type="file" onchange="this.value" multiple accept=".gif, .jpg, .png">
					   </legend> 					 
						<div class="input-group p-1 mb-1 mt-1  justify-content-center" id = "displayAfterPicture">	
						
						</div> 				
					 </fieldset>
					</div>			   	
					
				</div>
				</div>
 
 <div class="card text-center" >	  
 
	<div class="card-header mt-2 mb-2 text-center" > 	
	
				 <span class="badge bg-primary <?=$fs?> mt-2 mb-2"> 목록별 사진대지 List </span>		&nbsp;  &nbsp;  &nbsp;  &nbsp;  
					
				<button type="button" id="urlsave" class="btn btn-outline-primary mt-2 mb-2 <?=$fs_small?>"  > 시공사진 URL 복사하기 </button>  &nbsp;  	
				<button type="button" id="preview" class="btn btn-outline-danger mt-2 mb-2 <?=$fs_small?>"  > 미리보기 </button>  &nbsp;  	
                <input  class="mt-2 mb-2 text-center form-control <?=$fs_small?>" name="URL" id="URL" value="<?=$URLsave?>" readonly >
                &nbsp; &nbsp; &nbsp;
			
<!-- 수정된 HTML / PHP 코드 -->
<?php
$decodedPiclist = json_decode($piclist, true);
$counter = 1; 

if(intval($num) > 0) {
    foreach ($decodedPiclist as $column => $items) {
        if (is_array($items)) {
            foreach ($items as $item) {
                if ($item != '') {
                    echo '<div class="card">';
                    echo '<div class="card-body">';
                    echo '<div class="row">';
                    
                    echo '<div class="col-sm-1  fs-3 text-dark">' . $counter . '</div>';
                    echo '<div class="col fs-3 text-dark">' . htmlspecialchars($item) . '</div>'; // XSS 방지
                    
                    // 시공전
                    createImageInputAndButton($counter, 'beforeArr', 'secondary', $picDataArr);
                    // 시공중
                    createImageInputAndButton($counter, 'midArr', 'dark', $MidpicDataArr);
                    // 시공후
                    createImageInputAndButton($counter, 'afterArr', 'primary', $AfterpicDataArr);
                    
                    echo '</div></div></div>'; // Close divs
                    
                    $counter++;
                }
            }
        }
    }
}

function createImageInputAndButton($counter, $type, $btnColor, $picData) {
    echo '<div class="col" style="display:none;">';
    echo '<input type="file" multiple accept=".gif, .jpg, .png" id="'.$type.'Input_'.$counter.'" onchange="FileProcess(\''.$type.'\', '.$counter.', this)">';
    echo '</div>';
    echo '<div class="col text-center">';
    echo '<button type="button" class="btn btn-outline-'.$btnColor.'  text-center" onclick="document.getElementById(\''.$type.'Input_'.$counter.'\').click()">';
		
	switch (trim($type))
	{
		case 'beforeArr' :
		   $msg = '전';
		   break;
		case 'midArr' :
		   $msg = '중';
		   break;
		case 'afterArr' :
		   $msg = '후';
		   break;
		default:
		   $msg = '알 수 없음';
		   break;
	}
	
    echo '<ion-icon name="image-outline"></ion-icon> 시공(' . $msg . ')</button>';
    echo '<div class="col mt-3 mb-5" id="'.$type.'Images_'.$counter.'"  >';
    // if (!empty($picData) && isset($picData[$counter - 1])) {
        // echo '<img src="' . htmlspecialchars($picData[$counter - 1]) . '" class="uploaded-image">';
    // }
    echo '</div>';
    echo '</div>';
}
?>

  <div class="d-flex  mt-5 mb-5 " >	 
 
  </div> 
	</div>
			
	</div>
	</div>
	</form>		
  </body>
</html>    
 
 
<?php if($num != 0): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    let picIdx = <?php echo json_encode($picIdx); ?>;
    let MidpicIdx = <?php echo json_encode($MidpicIdx); ?>;
    let AfterpicIdx = <?php echo json_encode($AfterpicIdx); ?>;
    
    let picDataArr = <?php echo json_encode($picDataArr); ?>;
    let MidpicDataArr = <?php echo json_encode($MidpicDataArr); ?>;
    let AfterpicDataArr = <?php echo json_encode($AfterpicDataArr); ?>;

    // console.log('picIdx', picIdx);
    // console.log('MidpicIdx', MidpicIdx);
    // console.log('AfterpicIdx', AfterpicIdx);

    // console.log('picDataArr', picDataArr);
    // console.log('MidpicDataArr', MidpicDataArr);
    // console.log('AfterpicDataArr', AfterpicDataArr);

    ['beforeArr', 'midArr', 'afterArr'].forEach(type => {
        let currentData, currentIdx, itemType;

        switch (type) {
            case 'beforeArr':
                currentData = picDataArr;
                currentIdx = picIdx;
                itemType = 'before';
                break;
            case 'midArr':
                currentData = MidpicDataArr;
                currentIdx = MidpicIdx;
                itemType = 'mid';
                break;
            case 'afterArr':
                currentData = AfterpicDataArr;
                currentIdx = AfterpicIdx;
                itemType = 'after';
                break;
        }

        currentData.forEach((picName, index) => {
            if (currentIdx[index]) { // idx가 있을 경우에만
                let container = document.getElementById(`${type}Images_${currentIdx[index]}`);
                
                // Create the image element
                let img = document.createElement('img');
                img.src = './uploads/' + picName;
                img.classList.add('uploaded-image');
                img.id = itemType + 'Pic' + currentIdx[index];
                container.appendChild(img);

                // Create the delete button
                let cleanedPath = picName.replace('./uploads/', '');
                let btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-outline-danger mt-1 mb-1';
                btn.id = 'del' + img.id;
                btn.innerHTML = '<ion-icon name="trash-bin-outline"></ion-icon>';
                btn.onclick = function() {
                    delPicFn(currentIdx[index], cleanedPath, itemType);
                };
                container.appendChild(btn);
            }
        });
    });
});

    </script>
<?php endif; ?>

<script>
// 클릭시 동작하도록 trigger
function SearchFristordEnter(){
    if(event.keyCode == 13){		
		$("#searchFirstordBtn").trigger("click");        
    }	
}	
// 클릭시 동작하도록 trigger
function SearchSecondordEnter(){
    if(event.keyCode == 13){		
		$("#searchSecondordBtn").trigger("click");        
    }	
}	
 
$(document).ready(function(){	


    console.log($("#tmpKey").val());
	
	// 윈도우 닫기 버튼 클릭 (부모창 변경사항 적용)
	window.addEventListener('unload',e => opener.location.reload() );
		
	$("#urlsave").click(function() {    
		var content = document.getElementById('URL');
		
		content.select();
		console.log(document.execCommand('copy'));

		// Toastify를 사용하여 토스트 메시지 표시
		Toastify({
			text: "URL이 복사되었습니다. 붙여넣기 하세요",
			duration: 3000,  // 토스트 메시지의 지속 시간 (3초)
			close: true,
			gravity: "top", // `top` or `bottom`
			position: 'center', // `left`, `center` or `right`			
		}).showToast();
	});
		
	$("#preview").click(function() {    
		
		popupCenter($("#URL").val(),'현장사진 미리보기',1200,900);
		
	});


	

	// 제이테크 아닐때 read-only 모드로 변경사항
	let user_name = '<?php echo $user_name; ?>';
	if(user_name!='제이테크')
	{
		
		$('#workplacename').attr('readonly',true);
		$('#address').attr('readonly',true);
		$('#firstord').attr('readonly',true);
		$('#firstordman').attr('readonly',true);
		$('#firstordmantel').attr('readonly',true);
		$('#secondord').attr('readonly',true);
		$('#secondordman').attr('readonly',true);
		$('#secondordmantel').attr('readonly',true);
		$('#chargedman').attr('readonly',true);
		$('#chargedmantel').attr('readonly',true);
		$('#worker').prop('disabled', true);
		$('#material1').attr('readonly',true);
		$('#material2').attr('readonly',true);
		$('#material3').attr('readonly',true);
		$('#material4').attr('readonly',true);
		$('#material5').attr('readonly',true);
		$('#memo').attr('readonly',true);	
		$('#regist_day').attr('readonly',true);
		$('#donedemand').attr('readonly',true);		
		
	}

	// 사진대지 리스트
	$("#piclistBtn").click(function(){    
	    if( Number($("#num").val()) > 0 )		
			popupCenter('uploadpiclist.php?num=' + $("#num").val() , '사진대지 리스트', 1200, 900);
		  else
		  {
			  if(saveAction()!==false)
			  {
				  setTimeout(function() {
					   popupCenter('uploadpiclist.php?num=' + $("#num").val() , '사진대지 리스트', 1200, 900);				
				   }, 2000);		  			  
			  }
			  
		  }
	 });	
	 
	// 원청 검색버튼
	$("#searchFirstordBtn").click(function(){    
		  popupCenter('./search/list.php?itemname=firstord&num=' + $("#num").val() + '&search=' +  $("#firstord").val() , '원청 검색', 800, 600);
	 });	

	// 발주처 검색버튼
	$("#searchSecondordBtn").click(function(){    
		  popupCenter('./search/list.php?itemname=secondord&num=' + $("#num").val() + '&search=' +  $("#secondord").val() , '발주처 검색', 800, 600);
	 });	

	// 현대 서명버튼
	$("#signatureBtn").click(function(){    
	      var option = '1';
		  
	    if( Number($("#num").val()) > 0 )		
			popupCenter('customer_input.php?num=' + $("#num").val()+ '&option=' + option  , '서명등록', 800, 800);
		  else
		  {
			  if(saveAction()!==false)
			  {
				  setTimeout(function() {
					   popupCenter('customer_input.php?num=' + $("#num").val()+ '&option=' + option  , '서명등록', 800, 800);			
				   }, 2000);		  			  
			  }
			  
		  }		  
	  
		  
	 });	
	// 일반 서명버튼
	$("#signatureBtn2").click(function(){    
	      var option = '2';
	    if( Number($("#num").val()) > 0 )		
			popupCenter('customer_input.php?num=' + $("#num").val() + '&option=' + option  , '서명등록', 800, 800);
		  else
		  {
			  if(saveAction()!==false)
			  {
				  setTimeout(function() {
						popupCenter('customer_input.php?num=' + $("#num").val() + '&option=' + option  , '서명등록', 800, 800);
				   }, 2000);		  			  
			  }
			  
		  }				  
		  
		  
	 });	


	$("#listBtn").click(function(){    
		  page = $("#page").val();
		  scale = $("#scale").val();
		  
		  location.href='list.php?page=' + page + '&scale=' + scale;
	 });	
	 // 자료데이터 복사
	$("#copydataBtn").click(function(){    
		  mode = 'copydata';
		  num = $("#num").val();

		  // 순서가 중요함 이동할 주소가 먼저 나와야 함 (모달창보다 먼저 나와야 화면에 나타냄)
		  setTimeout(function() {
			location.href='write_form.php?mode=' + mode + "&num=" + num;
		   }, 1000);
		  
		  // 메시지 창 띄우기  문구, 해당초
		  alertmodal("자료가 복사되었습니다.", 1500);
		  
		  
	 });		
	$("#estimateBtn").click(function(){    // 견적서 클릭하면 이동하기
			popupCenter('estimate.php?num=' + $("#num").val(), '견적서 작성하기', 1400, 950);
	 });	
		
	// 처음 실행한 후 한번 실행	
	  displayPictureLoad();		
		 

	window.delPicFn = function(index, picName, itemType) {
		// console.log(index, picName);

		if (confirm("한번 삭제한 자료는 복구할 방법이 없습니다.\n\n정말 삭제하시겠습니까?")) {
			$.ajax({
				url: 'delpic.php?picname=' + picName,
				type: 'post',
				data: $("board_form").serialize(),
				dataType: 'json',
			}).done(function(data) {
				const picname = data["picname"];

				let imgId, btnId, inputId;
				switch (itemType) {
					case 'before':
						imgId = 'beforePic' + index;
						btnId = 'delbeforePic' + index;						
						break;

					case 'mid':
						imgId = 'midPic' + index;
						btnId = 'delmidPic' + index;						
						break;

					case 'after':
						imgId = 'afterPic' + index;
						btnId = 'delafterPic' + index;						
						break;
						
					case 'beforeArr':
						imgId = 'beforeArrImages_' + index;
						btnId = 'delbeforeArrImages_' + index;						
						break;

					case 'midArr':
						imgId = 'midArrImages_' + index;
						btnId = 'delmidArrImages_' + index;						
						break;

					case 'afterArr':
						imgId = 'afterArrImages_' + index;
						btnId = 'delafterArrImages_' + index;						
						break;
				}

				$("#" + imgId).remove();
				$("#" + btnId).remove();
			});
		}
	}
				 
	// 새입력 누를시
	$("#newBtn").click(function(e) {	    
		 $("#mode").val('new');
		 location.href='write_form.php?mode=new';			
	});	 	 
	
	// 시공전 사진 멀티업로드	
	$("#upfile").change(function(e) {	    
		var item = 'before';
		FileProcess(item, '', this); // 'this'는 현재 선택된 입력 요소를 참조합니다.
	});	 
			
	// 시공 중간 사진 멀티업로드		
	$("#Midupfile").change(function(e) {	    
		var item = 'mid';
		FileProcess(item, '', this); 
	});	

	// 시공 후 사진 멀티업로드		
	$("#Afterupfile").change(function(e) {	    
		var item = 'after';
		FileProcess(item, '', this); 
	});
	 
 window.FileProcess = function(item, idx, inputElement) {  // 전역함수 선언
	
	$('#item').val(item);
	
	   //do whatever you want here
    	num = $("#num").val();
	
		if(Number(num)==0) {
			 if(confirm("사진을 저장하려면 자료를 생성해야 합니다.\n\n 지금 자료를 등록하시겠습니까?")) {
				 
				$("#mode").val('insert');  // 삽입모드로 변경함.
				// 자료 삽입/수정하는 모듈		  
				Fninsert();				 
				if($("#mode").val()=='insert')  
					   {					      
							  location.href='write_form.php?num=' + data ;	// 실행되면 수정모드가 됨.		
					   }
			   }
		   }
		  // 사진 서버에 저장하는 구간			  
		  //tablename 설정
		   $("#tablename").val('jtech');  
			// 폼데이터 전송시 사용함 Get form         
			var form = $('#board_form')[0];  	    
			// Create an FormData object          
			var data = new FormData(form); 				
						
			data.append('idx', idx);    			
				
			// 선택한 파일들을 FormData에 추가
			var files = inputElement.files;
			for (var i = 0; i < files.length; i++) {
				data.append('file[]', files[i]);
			}		

            // console.log('files : ', files);			

			// tmp='사진을 저장중입니다. 잠시만 기다려주세요.';		
			// $('#alertmsg').html(tmp); 			  
			// $('#myModal').modal('show'); 	

				$.ajax({
					enctype: 'multipart/form-data',  // file을 서버에 전송하려면 이렇게 해야 함 주의
					processData: false,    
					contentType: false,      
					cache: false,           
					timeout: 600000, 			
					url: "pic_insert.php",
					type: "post",		
					data: data,	
					dataType : 'json',
					success : function(response){
						
					 // console.log('response.status ', response.status);
					  if(response.status == "array") {							
							// Update the display with new images and delete buttons
							switch (item) {
								case 'beforeArr':
									AdddisplayImagesArray('#beforeArrImages_' + idx, response.filepaths, item);
									break;
								case 'midArr':
									AdddisplayImagesArray('#midArrImages_' + idx, response.filepaths, item);
									break;
								case 'afterArr':
									AdddisplayImagesArray('#afterArrImages_' + idx, response.filepaths, item);
									break;
							}							
							
							
						}
						
					  if(response.status == "basic") {
							// Update the display with new images and delete buttons
							switch (item) {
								case 'before':
									AdddisplayImagesMain('#displayBeforePicture', response.filepaths, item);
									break;
								case 'mid':
									AdddisplayImagesMain('#displayMidPicture', response.filepaths, item);
									break;
								case 'after':
									AdddisplayImagesMain('#displayAfterPicture', response.filepaths, item);
									break;
							}
						}
						
						// console.log('실행결과 ', data);
						// opener.location.reload();
						// window.close();	
						
						setTimeout(function() {
							$('#myModal').modal('hide');  
						}, 1000);	
						
						
						
						
						
					  },
					error : function( jqxhr , status , error ){
						console.log( jqxhr , status , error );
								} 			      		
				   });	

	}


window.displayImages = function(filepaths, type, counter) {  // 전역함수 선언
    let container;
    switch(type) {
        case 'beforeArr':
            container = $('#beforeImages_'+counter);
            break;
        case 'midArr':
            container = $('#midImages_'+counter);
            break;
        case 'afterArr':
            container = $('#afterImages_'+counter);
            break;
        default:
            console.error("Invalid type provided to displayImages:", type);
            return;
    } 

    container.empty(); // Clear the container

    for(let i = 0; i < filepaths.length; i++) {
        container.append('<img src="' + filepaths[i] + '" width="25vw" height="auto" alt="Image ' + (i+1) + '">');
    }
	
    // console.log('filepaths', filepaths);
}

	
			 
	$("#closeModalBtn").click(function(){ 
		$('#myModal').modal('hide');
	});
			
	$("#closeBtn").click(function(){    // 저장하고 창닫기	
		 });	
					
	// saveAction 함수 생성
	function saveAction() {
		var num = $("#num").val();  
		
		if( $("#workplacename").val() == '') {  
			alertmodal("현장명은 필수입력사항입니다.", 1500);
			return false;
		}
		else {  
			// 결재상신이 아닌경우 수정안됨     
			if(Number(num) > 0) 
				$("#mode").val('modify');     
			else
				$("#mode").val('insert');     
			  
			// 자료 삽입/수정하는 모듈       
			Fninsert();  
            return true;			
		}
	}

	$("#saveBtn").click(function() {  // DATA 저장버튼 누름
		saveAction();
	}); 

	 

	// 삽입/수정하는 모듈 
	function Fninsert() {	 
		   
	//  console.log($("#mode").val());    

	// 폼데이터 전송시 사용함 Get form         
	var form = $('#board_form')[0];  	    
	// Create an FormData object          
	var data = new FormData(form); 

	// console.log(data);

	$.ajax({
		enctype: 'multipart/form-data',    // file을 서버에 전송하려면 이렇게 해야 함 주의
		processData: false,    
		contentType: false,      
		cache: false,           
		timeout: 600000, 			
		url: "insert.php",
		type: "post",		
		data: data,			
		dataType:"text",  // text형태로 보냄
		success : function(data){
			  console.log('data num : ' , data);
			// opener.location.reload();
			// window.close();	
			  // 메시지 창 띄우기  문구, 해당초
			  $("#mode").val('modify');    
			  $("#num").val(Number(data));    
			  $("#tmpKey").val(0);    
			 // alertmodal("파일 저장중입니다. 잠시 기다려주세요.", 1500);			   
			  Swal.fire(
				  '자료등록 완료',
				  '데이터가 성공적으로 등록되었습니다.',
				  'success'
				);
			  

		},
		error : function( jqxhr , status , error ){
			console.log( jqxhr , status , error );
					} 			      		
	   });		

		// else
		  // {
		  // tmp='보고자만 결재상신 상태가 아닌 경우 수정이 가능합니다.';		
		  // $('#alertmsg').html(tmp); 			  
			// $('#myModal').modal('show');  
		  // }
	 }
	 
				 
	$("#delBtn").click(function(){      // del
		var num = $("#num").val();    
		var reporter = $("#reporter").val();    
		var approve = $("#approve").val();  
		var user_name = $("#user_name").val();  

		if( (user_name=='제이테크') || user_name=='김보곤') {   
		
			 if(confirm("한번 삭제한 자료는 복구할 방법이 없습니다.\n\n정말 삭제하시겠습니까?")) {
		   $("#mode").val('delete');     
			  
			$.ajax({
				url: "insert.php",
				type: "post",		
				data: $("#board_form").serialize(),
				dataType:"text",  // text형태로 받음
				success : function( data ){			
							
				  // 순서가 중요함 이동할 주소가 먼저 나와야 함 (모달창보다 먼저 나와야 화면에 나타냄)
				  setTimeout(function() {
						location.href='http://j-techel.co.kr/jtech/list.php';	
				   }, 1500);
				  
				  // 메시지 창 띄우기  문구, 해당초
				  alertmodal("자료가 삭제되었습니다.", 1500);				
								
				},
				error : function( jqxhr , status , error ){
					console.log( jqxhr , status , error );
				} 			      		
			   });	
			 }		   
			} // end of if
			else
			  {
			  tmp='삭제권한이 없습니다.';		
			  $('#alertmsg').html(tmp); 			  
				$('#myModal').modal('show');  
			  }
				
	 }); // end of function
				 
	 

}); // end of ready document
 

function displayPictureLoad() {    
    // 시공전 이미지 화면에 보여주기
    var picNum = "<?php echo $picNum; ?>";                     
    var picData = <?php echo json_encode($picData);?> ;   
    displayImagesMain('#displayBeforePicture', picNum, picData, 'before');

    // 시공중 이미지 화면에 보여주기
    var midPicNum = "<?php echo $MidpicNum; ?>";                     
    var midPicData = <?php echo json_encode($MidpicData);?> ;   
    displayImagesMain('#displayMidPicture', midPicNum, midPicData, 'mid');

    // 시공후 이미지 화면에 보여주기
    var afterPicNum = "<?php echo $AfterpicNum; ?>";                     
    var afterPicData = <?php echo json_encode($AfterpicData);?> ;   
    displayImagesMain('#displayAfterPicture', afterPicNum, afterPicData, 'after');
	
	console.log('picData', picData);
	console.log('midPicData', midPicData);
	
}

function displayImagesMain(containerId, picNum, picData, itemType) {
    $(containerId).empty(); // 현재 내용을 지워줍니다.

    for(var i = 0; i < picNum; i++) {
        var imgSrc = './uploads/' + picData[i];
		console.log(imgSrc);
        $(containerId).append('<img id="' + itemType + 'Pic' + i + '" src="' + imgSrc + '" style="width:100%; height:100%" class="mb-1 mt-1">');
        $(containerId).append('&nbsp;<button type="button" class="btn btn-outline-danger" id="del' + itemType + 'Pic' + i + '" onclick="delPicFn(\'' + i + '\',\'' + picData[i] + '\', \'' + itemType + '\')" ><ion-icon name="trash-bin-outline"></ion-icon></button>');
		
		//  console.log('&nbsp;<button type="button" class="btn btn-outline-danger" id="del' + itemType + 'Pic' + i + '" onclick="delPicFn(\'' + i + '\',\'' + picData[i] + '\', \'' + itemType + '\')" ><ion-icon name="trash-bin-outline"></ion-icon></button>');
    }
}

function AdddisplayImagesMain(containerId, filepaths, itemType) {
    // Get the starting index based on how many <img> tags already exist in the container
    var startingIndex = $(containerId).children('img').length;

    for (var i = 0; i < filepaths.length; i++) {
        var imgSrc = filepaths[i];
        var currentIndex = startingIndex + i;
        var uniqueId = itemType + 'Pic' + currentIndex; 
        
        $(containerId).append('<img id="' + uniqueId + '" src="' + imgSrc + '" style="width:100%; height:100%" class="mb-1 mt-1">');
		var cleanedPath = filepaths[i].replace('./uploads/', '');
		$(containerId).append('&nbsp;<button type="button" class="btn btn-outline-danger" id="del' + uniqueId + '" onclick="delPicFn(\'' + currentIndex + '\',\'' + cleanedPath + '\', \'' + itemType + '\')" ><ion-icon name="trash-bin-outline"></ion-icon></button>');

    }
}

function AdddisplayImagesArray(containerId, filepaths, itemType) {
    // 이미 컨테이너 내에 존재하는 <img> 태그의 수를 기반으로 시작 인덱스를 얻습니다.
    var startingIndex = $(containerId).children('img').length;

    for (var i = 0; i < filepaths.length; i++) {
        var imgSrc = filepaths[i];
        var currentIndex = startingIndex + i;
        var uniqueId = itemType + 'Images_' + currentIndex;

        // 이미지 추가
        $(containerId).append('<img id="' + uniqueId + '" src="' + imgSrc + '" style="width:100%; height:100%" class="mb-1 mt-1">');
        var cleanedPath = imgSrc.replace('./uploads/', '');

        // 삭제 버튼 추가
        var deleteButton = '<button type="button" class="btn btn-outline-danger" id="del' + uniqueId + '" onclick="delPicFn(\'' + currentIndex + '\',\'' + cleanedPath + '\', \'' + itemType + '\')"><ion-icon name="trash-bin-outline"></ion-icon></button>';
        $(containerId).append(deleteButton);
    }
}


	
 function del(href) 
     {
		 var level=Number($('#session_level').val());
		 if(level>2)
		     alert("삭제하려면 관리자에게 문의해 주세요");
		 else {
         if(confirm("한번 삭제한 자료는 복구할 방법이 없습니다.\n\n정말 삭제하시겠습니까?")) {
           document.location.href = href;
          } 
		 }

     } 


// 사진 회전하기
function rotate_image()
{	
	 var box = $('.imagediv');
	 var imgObj = new Image();
	 var imgObj2 = new Image();
	 imgObj.src = "<? echo $imgurl1; ?>" ; 
	 imgObj2.src = "<? echo $imgurl2; ?>" ; 
	 box.css('width','800px');
	 box.css('height','1000px');
	 box.css('margin-top','200px');
	 
	 if( imgObj.width > imgObj.height  ||  imgObj2.width > imgObj2.height)
	   {
			$('.before_work').addClass('rotated');
			$('.after_work').addClass('rotated');		
	   }

}

setTimeout(function() {
 // console.log('Works!');
 rotate_image();
}, 500);

function alertmodal(tmp, second)
{	
	$('#alertmsg').html(tmp); 			  
	$('#myModal').modal('show'); 	
	
	setTimeout(function() {
	$('#myModal').modal('hide');  
	}, second);		
	
}


</script>
