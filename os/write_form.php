<?php

session_start(); 

$level= $_SESSION["level"];
$id_name= $_SESSION["name"];   
$user_name= $_SESSION["name"];  
  
 // $file_dir = './uploads/'; 
  
 $num=$_REQUEST["num"];
 $search=$_REQUEST["search"];  //검색어

 if(isset($_REQUEST["check"])) 
	 $check=$_REQUEST["check"]; 
   else
     $check=$_POST["check"]; 
 
 if(isset($_REQUEST["page"]))
 {
    $page=$_REQUEST["page"]; 
 }
  else
  {
    $page=1;	 
  }
	 
if($num!=null && $num!=0)
{	
 require_once("../lib/mydb.php");
 $pdo = db_connect();
 
 // 사진전 사진 이미 있는 것 불러오기 
$picData=array(); 
$tablename='os';
$item = 'before';

$sql=" select * from jtechel.picuploads where tablename ='$tablename' and item ='$item' and parentnum ='$num' ";	

 try{  
   $stmh = $pdo->query($sql);            // 검색조건에 맞는글 stmh   
   while($row = $stmh->fetch(PDO::FETCH_ASSOC)) {
			array_push($picData, $row["picname"]);			
        }		 
   } catch (PDOException $Exception) {
    print "오류: ".$Exception->getMessage();
}  
$picNum=count($picData);

// 시공 중간 사진 이미 있는 것 불러오기 
$MidpicData=array(); 
$tablename='os';
$item = 'mid';

$sql=" select * from jtechel.picuploads where tablename ='$tablename' and item ='$item' and parentnum ='$num' ";	

 try{  
   $stmh = $pdo->query($sql);            // 검색조건에 맞는글 stmh   
   while($row = $stmh->fetch(PDO::FETCH_ASSOC)) {
			array_push($MidpicData, $row["picname"]);			
        }		 
   } catch (PDOException $Exception) {
    print "오류: ".$Exception->getMessage();
}  
$MidpicNum=count($MidpicData);
  
// 시공 후 사진 이미 있는 것 불러오기 
$AfterpicData=array(); 
$tablename='os';
$item = 'after';

$sql=" select * from jtechel.picuploads where tablename ='$tablename' and item ='$item' and parentnum ='$num' ";	

 try{  
   $stmh = $pdo->query($sql);            // 검색조건에 맞는글 stmh   
   while($row = $stmh->fetch(PDO::FETCH_ASSOC)) {
			array_push($AfterpicData, $row["picname"]);			
        }		 
   } catch (PDOException $Exception) {
    print "오류: ".$Exception->getMessage();
}  
$AfterpicNum=count($AfterpicData);
  
try{
     $sql = "select * from jtechel.os where num=?";
     $stmh = $pdo->prepare($sql);  
     $stmh->bindValue(1, $num, PDO::PARAM_STR);      
     $stmh->execute();            
      
     $row = $stmh->fetch(PDO::FETCH_ASSOC); 	
	  
	 include 'rowDB.php';  

		      if($measureday!="0000-00-00" and $measureday!="1970-01-01" and $measureday!="")   $measureday = date("Y-m-d", strtotime( $measureday) );
					else $measureday="";
		      if($workday!="0000-00-00" and $workday!="1970-01-01"  and $workday!="")  $workday = date("Y-m-d", strtotime( $workday) );
					else $workday="";			      
		      if($demand!="0000-00-00" and $demand!="1970-01-01" and $demand!="")  $demand = date("Y-m-d", strtotime( $demand) );
					else $demand="";			
		      if($doneday!="0000-00-00" and $doneday!="1970-01-01" and $doneday!="")  $doneday = date("Y-m-d", strtotime( $doneday) );
					else $doneday="";			
		      if($regist_day!="0000-00-00" and $regist_day!="1970-01-01" and $regist_day!="")  $regist_day = date("Y-m-d", strtotime( $regist_day) );
					else $regist_day="";									
					
     }catch (PDOException $Exception) {
       print "오류: ".$Exception->getMessage();
     }
}
else   // 신규자료 등록일 경우
{     
	$todate=date("Y-m-d");  // 현재일 저장   
	$regist_day=$todate;
}
   
 ?>
 
<meta charset="utf-8"> 
<!DOCTYPE html>
<html lang="ko">
  <head>
  
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="">
    <meta name="author" content="">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" >
	    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>	    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" ></script> 
	
	<script src="http://j-techel.co.kr/common.js"></script>
    
</head>
<title> 오성 현장 관리시스템 </title>

<body>
<style>

    .rotated {
  transform: rotate(90deg);
  -ms-transform: rotate(90deg); /* IE 9 */
  -moz-transform: rotate(90deg); /* Firefox */
  -webkit-transform: rotate(90deg); /* Safari and Chrome */
  -o-transform: rotate(90deg); /* Opera */
}
</style> 

<div class="container-fluid"> 
<div id="top-menu">
<?php
    if(!isset($_SESSION["userid"]))
	{
?>
          <a href="../login/login_form.php">로그인</a> | <a href="../member/insertForm.php">회원가입</a>
<?php
	}
	else
	 {
?>
			<div class="row">
           <div class="col-12"> 
		         <h3 class="display-5 font-center text-left"> 
					<?=$_SESSION["nick"]?> | 
					<a href="../login/logout.php">로그아웃</a> | <a href="../member/updateForm.php?id=<?=$_SESSION["userid"]?>">정보수정</a>
		
<?php
	 }
?>
</h3>
</div> </div>  </div>


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
			
    <div class="row d-flex justify-content-center align-items-center h-20">	
        <div class="col-12 text-center">
			<div class="card align-middle" style="width:42rem; border-radius:20px;">		
			
				<div class="card" style="padding:10px;margin:10px;">
					
					<h2 class="display-5 card-title text-center" style="color:#113366;"> 
		              <input type="button" class="btn btn-secondary btn-lg " value="목록" onclick="location.href='index.php?check=<?=$check?>';"> 					
					  &nbsp;&nbsp;&nbsp;&nbsp;
					  공사현장 등록/수정/삭제 
					  </h2>
				</div>	
				<div class="card-body text-center">
				<form id="board_form" method="post" enctype="multipart/form-data"  action="insert.php" >
				<input type="hidden" id="mode" name="mode">
				<input type="hidden" id="num" name="num" value="<?=$num?>" >			  								
				<input type="hidden" id="user_name" name="user_name" value="<?=$user_name?>" > 					
				<input type="hidden" id="update_log" name="update_log" value="<?=$update_log?>"  > 					
				<input type="hidden" id="tablename" name="tablename" value="<?=$tablename?>"  > 					
				<input type="hidden" id="item" name="item" value="<?=$item?>"  > 					
			  
				<span class="form-control">
					<h2 class="text-center" > 
					공사명 &nbsp;
					<input  type="text" name="workplacename" id="workplacename" value="<?=$workplacename?>"  > 	
					</h2>
                    </span>
					<span class="form-control">					
					<h2 class="text-center" > 
					주소 &nbsp;
					<input class="input-form" type="text" name="address" id="address" value="<?=$address?>"  > 										
					</h2>
				</span>
				<span class="form-control">
					<h2 class="text-center" > 	발주접수일 &nbsp;
					<input type="date" name="regist_day" id="regist_day" value="<?=$regist_day?>"  > 					
					</h2>
					<h2 class=" text-center" > 	실&nbsp;&nbsp; 측&nbsp;&nbsp; 일  &nbsp;
					<input type="date" name="measureday" id="measureday?" value="<?=$measureday?>"  > 					
					</h2>
					<h2 class=" text-center" > 	공사예정일  &nbsp;
					<input type="date" name="workday" id="$workday?" value="<?=$workday?>"  > 					
					</h2>
					<h2 class=" text-center" > 	시공완료일 &nbsp;
					<input type="date" name="doneday" id="doneday" value="<?=$doneday?>"  > 					
					</h2>
					<h2 class=" text-center" > 	대금청구일 &nbsp;
					<input type="date" name="demand" id="demand" value="<?=$demand?>"  > 					
					</h2>
				</span>
				<span class="form-control">
					<h2 class=" text-left" > 
					원청 &nbsp;
					<input type="text" name="firstord" id="firstord" value="<?=$firstord?>"  > 										
					<br>
					원청담당(PM) &nbsp;
					<input type="text" name="firstordman" id="firstordman" size= 11 value="<?=$firstordman?>"  > 					
					<br>
					연락처  &nbsp;
					<input type="text" name="firstordmantel" id="firstordmantel" size=11 value="<?=$firstordmantel?>"  > 					
					</h2>
				</span>
				<span class="form-control">
					<h2 class="display-5 text-left" > 
					발주처 &nbsp;
					<input type="text" name="secondord" id="secondord" value="<?=$secondord?>"  > 					
					<br>
					발주처담당 &nbsp;
					<input type="text" name="secondordman" id="secondordman" size= 11 value="<?=$secondordman?>"  > 					
					<br>
					연락처  &nbsp;
					<input type="text" name="secondordmantel" id="secondordmantel" size=11 value="<?=$secondordmantel?>"  > 					
					</h2>
				</span>
				<span class="form-control">
					<h2 class="display-5 text-left" > 	
					현장소장 &nbsp;
					<input type="text" name="chargedman" id="chargedman" size= 10 value="<?=$chargedman?>"  > 					
					<br>
					연락처  &nbsp;
					<input type="text" name="chargedmantel" id="chargedmantel" size=11 value="<?=$chargedmantel?>"  > 					
					</h2>
				</span>
				<span class="form-control">
					<h2 class="display-5 text-left" > 
					소재 1 :  	<input type="text" id=material1  name=material1 size=22  value="<?=$material1?>"  >  <br>
					소재 2 :  	<input type="text" id=material2  name=material2 size=22  value="<?=$material2?>"  >  <br>
					소재 3 :  	<input type="text" id=material3  name=material3 size=22  value="<?=$material3?>"  >  <br>
					소재 4 :  	<input type="text" id=material4  name=material4 size=22  value="<?=$material4?>"  >  <br>
					소재 5 :  	<input type="text" id=material5  name=material5 size=22  value="<?=$material5?>"  >  <br>					
					</h2>
				</span>
				<span class="form-control">
					<h2 class="display-5 text-left" > 	 시공팀 &nbsp;
					<input type="text" name="worker" id="worker" size= 6 value="<?=$worker?>"  > 					
						
					</h2>
				</span>		
				<span class="form-control">
					<h2 class="display-5 text-left" > 
					세부 발주내역  
					</h2>
					<h2 class="display-5 text-left " > 
					<textarea type="text"  id=memo  name=memo rows=3 cols=30 ><?=$memo?></textarea>
					</h2>
				</span>
				<span class="form-control">
					<h2 class="display-5 text-left text-primary" > 
					정산 참고내역  
					</h2>
					<h2 class="display-5 text-left text-danger" > 
					<textarea id=memo2  name=memo2 type="text" rows=3 cols=30 ><?=$memo2?></textarea>
					</h2>
				</span>
				<span class="form-control">
				<h2 class="display-5 text-left text-secondary" > 				
				<span style="color:gray">시공전 사진(이미지) </span>	
                  <?php if($filename!=null) print $filename ?>	&nbsp;&nbsp;&nbsp;&nbsp;			   
                   <input id="upfile"  name="upfile[]" class="input" type="file" onchange="this.value" multiple accept=".gif, .jpg, .png">
				  </h2>
				</span>	
				<span class="form-control">
				<h2 class="display-5 text-left text-success" > 				
				<span style="color:green">시공 중간 사진(이미지) </span>	
                  <?php if($filename!=null) print $filename ?>	&nbsp;&nbsp;&nbsp;&nbsp;			   
                   <input id="Midupfile"  name="Midupfile[]" class="input" type="file" onchange="this.value" multiple accept=".gif, .jpg, .png" >				  
				  </h2>
				</span>	
				
				<span class="form-control">
				<h2 class="display-5 text-left text-danger" > 								
				<span style="color:red">시공 후 사진(이미지) </span>	
                  <?php if($filename!=null) print $filename ?>	&nbsp;&nbsp;&nbsp;&nbsp;			   
                   <input id="Afterupfile"  name="Afterupfile[]" class="input" type="file" onchange="this.value" multiple  accept=".gif, .jpg, .png">				  
				</h2>
				</span>		

				<br> 	  
				<button id="saveBtn" class="btn btn-lg btn-dark btn-block" type="button">
				<? if((int)$num>0) print '수정';  else print '등록'; ?></button>
				<? if($user_name=='김재구' || $user_name=='김보곤') {  ?>				
				<button id="delBtn" class="btn btn-lg btn-danger btn-block" type="button">삭제</button>
				<button id="estimateBtn" class="btn btn-lg btn-primary btn-block" type="button">견적서 생성</button>
				<? } ?>
				<button id="backBtn" class="btn btn-lg btn-secondary btn-block" type="button" onclick="location.href='index.php?check=<?=$check?>';">
				목록으로 돌아가기</button>
				</form>			  
				</div>
       	 
				<div class="card-body text-center">
				 <h2 class="display-5 text-center text-secondary" > 	
				    시공 전 사진(이미지)
				 </h2>
					<div id = "displayPicture" style="display:none;" >  </div>   
					<br>
				 <h2 class="display-5 text-center text-success" > 	
				    시공 중간 사진(이미지)
				 </h2>
					<div id = "MiddisplayPicture" style="display:none;" >  </div>   
					<br>
				 <h2 class="display-5 text-center text-danger" > 	
				    시공 후 사진(이미지)
				 </h2>
					<div id = "AfterdisplayPicture" style="display:none;" >  </div>   
					<br>

					
					<input id="pInput" name="pInput" type=hidden value="0" >	
					<input id="MidpInput" name="MidpInput" type=hidden value="0" >	
					<input id="AfterpInput" name="AfterpInput" type=hidden value="0" >	
				</div>

			</div>
		</div>		
				
     </div>

</div>		 
			  
		
  </body>
</html>    
 
 
 <script >
 
$(document).ready(function(){
	
	$("#estimateBtn").click(function(){    // 견적서 클릭하면 이동하기
	    popupCenter('estimate.php?num=' + $("#num").val(), '견적서', 700, 900);
	 });	
				
	
$("#pInput").val('50'); // 최초화면 사진파일 보여주기
$("#MidpInput").val('50'); // 최초화면 사진파일 보여주기
$("#AfterpInput").val('50'); // 최초화면 사진파일 보여주기
	
 let timer3 = setInterval(() => {  // 2초 간격으로 사진업데이트 체크한다.
	      if($("#pInput").val()=='100')   // 사진이 등록된 경우
		  {
	             displayPicture();  
				 // console.log(100);
		  }	      
		  if($("#pInput").val()=='50')   // 사진이 등록된 경우
		  {
	             displayPictureLoad();				 
		  }
	      if($("#MidpInput").val()=='100')   // 사진이 등록된 경우
		  {
	             MiddisplayPicture();  
				 // console.log(100);
		  }	      
		  if($("#MidpInput").val()=='50')   // 사진이 등록된 경우
		  {
	             MiddisplayPictureLoad();				 
		  }
	      if($("#AfterpInput").val()=='100')   // 사진이 등록된 경우
		  {
	             AfterdisplayPicture();  
				 // console.log(100);
		  }	      
		  if($("#AfterpInput").val()=='50')   // 사진이 등록된 경우
		  {
	             AfterdisplayPictureLoad();				 
		  }
		   
	 }, 2000);	
	 


  
delPicFn = function(divID, delChoice) {
	console.log(divID, delChoice);

	$.ajax({
		url:'delpic.php?picname=' + delChoice ,
		type:'post',
		data: $("board_form").serialize(),
		dataType: 'json',
		}).done(function(data){						
		   const picname = data["picname"];		   
		   
		  // 시공전사진 삭제 
			$("#pic" + divID).remove();  // 그림요소 삭제
			$("#delPic" + divID).remove();  // 그림요소 삭제
		    $("#pInput").val('');			
			
		  // 시공 중간 사진 삭제 
			$("#Midpic" + divID).remove();  // 그림요소 삭제
			$("#MiddelPic" + divID).remove();  // 그림요소 삭제
		    $("#MidpInput").val('');			
			
		  // 시공전사진 삭제 
			$("#Afterpic" + divID).remove();  // 그림요소 삭제
			$("#AfterdelPic" + divID).remove();  // 그림요소 삭제
		    $("#AfterpInput").val('');			
			
        });		

}
	  	 
	 
// 시공전 사진 멀티업로드	
$("#upfile").change(function(e) {	    
	    $("#item").val('before');
	    var item = $("#item").val();
		FileProcess(item);	
});	 
	
// 시공 중간 사진 멀티업로드		
$("#Midupfile").change(function(e) {	    
	    $("#item").val('mid');
	    var item = $("#item").val();
		FileProcess(item);	
});	

// 시공 후 사진 멀티업로드		
$("#Afterupfile").change(function(e) {	    
	    $("#item").val('after');
	    var item = $("#item").val();
		FileProcess(item);	
});
 
function FileProcess(item) {
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
  // 사진 서버에 저장하는 구간	
        //tablename 설정
       $("#tablename").val('os');  
		// 폼데이터 전송시 사용함 Get form         
		var form = $('#board_form')[0];  	    
		// Create an FormData object          
		var data = new FormData(form); 

		tmp='사진을 저장중입니다. 잠시만 기다려주세요.';		
		$('#alertmsg').html(tmp); 			  
		$('#myModal').modal('show'); 	

		$.ajax({
			enctype: 'multipart/form-data',  // file을 서버에 전송하려면 이렇게 해야 함 주의
			processData: false,    
			contentType: false,      
			cache: false,           
			timeout: 600000, 			
			url: "pic_insert.php",
			type: "post",		
			data: data,						
			success : function(data){
				console.log(data);
				// opener.location.reload();
				// window.close();	
				setTimeout(function() {
					$('#myModal').modal('hide');  
					}, 1000);	
                // 사진이 등록되었으면 100 입력됨
                 $("#pInput").val('100');					
                 $("#MidpInput").val('100');					
                 $("#AfterpInput").val('100');					

			},
			error : function( jqxhr , status , error ){
				console.log( jqxhr , status , error );
						} 			      		
		   });	

}		   
 
 
	 	 
$("#closeModalBtn").click(function(){ 
    $('#myModal').modal('hide');
});
		
$("#closeBtn").click(function(){    // 저장하고 창닫기	
	 });	
			
$("#saveBtn").click(function(){      // DATA 저장버튼 누름
	var num = $("#num").val();  	
	   	   
// 결재상신이 아닌경우 수정안됨	 
if(Number(num)>0) 
		   $("#mode").val('modify');     
		  else
			  $("#mode").val('insert');     
		  
// 자료 삽입/수정하는 모듈		  
Fninsert();	
		
 }); 
 

// 삽입/수정하는 모듈 
function Fninsert() {	 
	   
console.log($("#mode").val());    

// 폼데이터 전송시 사용함 Get form         
var form = $('#board_form')[0];  	    
// Create an FormData object          
var data = new FormData(form); 

tmp='파일을 저장중입니다. 잠시만 기다려주세요.';		
$('#alertmsg').html(tmp); 			  
$('#myModal').modal('show'); 	

console.log(data);		  

$.ajax({
	enctype: 'multipart/form-data',    // file을 서버에 전송하려면 이렇게 해야 함 주의
	processData: false,    
	contentType: false,      
	cache: false,           
	timeout: 600000, 			
	url: "insert.php",
	type: "post",		
	data: data,			
	// dataType:"text",  // text형태로 보냄
	success : function(data){
		console.log(data);
		// opener.location.reload();
		// window.close();	
		setTimeout(function() {
			$('#myModal').modal('hide');  
			}, 1000);
		
		if($("#mode").val()=='insert')  // 삽입인 경우는 목록으로 이동
		   {					      
				  location.href='write_form.php?num=' + data ;			
				  
		   }

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

	if( (user_name=='김재구') || user_name=='김보곤') {   
	
         if(confirm("한번 삭제한 자료는 복구할 방법이 없습니다.\n\n정말 삭제하시겠습니까?")) {
	   $("#mode").val('delete');     
		  
		$.ajax({
			url: "insert.php",
			type: "post",		
			data: $("#board_form").serialize(),
			dataType:"text",  // text형태로 보냄
			success : function( data ){
				console.log( data);
				location.href='http://j-techel.co.kr/os/index.php';				
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
 

// 시공전 이미지 불러오기
function displayPicture() {       
	$('#displayPicture').show();
	params = $("#num").val();	
	$("#tablename").val('os');
	$("#item").val('before');	
	
    var tablename = $("#tablename").val();    
    var item = $("#item").val();	
	
	$.ajax({
		url:'load_pic.php?num=' + params + '&tablename=' + tablename + '&item=' + item ,
		type:'post',
		data: $("board_form").serialize(),
		dataType: 'json',
		}).done(function(data){						
		   const recnum = data["recnum"];		   
		   console.log(data);
		   $("#displayPicture").html('');
		   for(i=0;i<recnum;i++) {			   
			   $("#displayPicture").append("<img id=pic" + i + " src ='./uploads/" + data["img_arr"][i] + "' style='width:100%; height:100%'  > <br> " );			   
         	   $("#displayPicture").append("&nbsp;<button type='button' class='btn btn-secondary' id='delPic" + i + "' onclick=delPicFn('" + i + "','" +  data["img_arr"][i] + "')> 삭제 </button>&nbsp;<br><br>");					   
		      }		   
			    $("#pInput").val('');			
    });	
}

// 시공전 기존 있는 이미지 화면에 보여주기
function displayPictureLoad() {    
	$('#displayPicture').show();
	var picNum = "<? echo $picNum; ?>"; 					
	var picData = <?php echo json_encode($picData);?> ;	
	console.log(picNum);
	console.log(picData);
    for(i=0;i<picNum;i++) {
       $("#displayPicture").append("<img id=pic" + i + " src ='./uploads/" + picData[i] + "' style='width:100%; height:100%' > <br>" );			
	   $("#displayPicture").append("&nbsp;<button type='button' class='btn btn-secondary' id='delPic" + i + "' onclick=delPicFn('" + i + "','" + picData[i] + "')> 삭제 </button>&nbsp;<br><br>");			   
	  }		   
		$("#pInput").val('');	
}
	
// 시공 중간 이미지 불러오기
// 시공 중간 이미지 불러오기
// 시공 중간 이미지 불러오기
function MiddisplayPicture() {       
	$('#MiddisplayPicture').show();
	params = $("#num").val();	
	$("#tablename").val('os');
	$("#item").val('mid');	
	
    var tablename = $("#tablename").val();    
    var item = $("#item").val();	
	
	$.ajax({
		url:'load_pic.php?num=' + params + '&tablename=' + tablename + '&item=' + item ,
		type:'post',
		data: $("board_form").serialize(),
		dataType: 'json',
		}).done(function(data){						
		   const recnum = data["recnum"];		   		   
		   $("#MiddisplayPicture").html('');
		   for(i=0;i<recnum;i++) {			   
			   $("#MiddisplayPicture").append("<img id=Midpic" + i + " src ='./uploads/" + data["img_arr"][i] + "' style='width:100%; height:100%'  > <br>" );			   
         	   $("#MiddisplayPicture").append("&nbsp;<button type='button' class='btn btn-secondary' id='MiddelPic" + i + "' onclick=delPicFn('" + i + "','" +  data["img_arr"][i] + "')> 삭제 </button>&nbsp;<br><br>");					   
		      }		   
			    $("#MidpInput").val('');			
    });	
}

// 시공전 기존 있는 이미지 화면에 보여주기
function MiddisplayPictureLoad() {    
	$('#MiddisplayPicture').show();
	var MidpicNum = "<? echo $MidpicNum; ?>"; 					
	var MidpicData = <?php echo json_encode($MidpicData);?> ;	
    for(i=0;i<MidpicNum;i++) {
       $("#MiddisplayPicture").append("<img id=Midpic" + i + " src ='./uploads/" + MidpicData[i] + "' style='width:100%; height:100%' > <br>"  );			
	   $("#MiddisplayPicture").append("&nbsp;<button type='button' class='btn btn-secondary' id='MiddelPic" + i + "' onclick=delPicFn('" + i + "','" + MidpicData[i] + "')> 삭제 </button>&nbsp;<br><br>");			   
	  }		   
		$("#MidpInput").val('');	
}
	
	
// 시공 후 이미지 불러오기
// 시공 후 이미지 불러오기
// 시공 후 이미지 불러오기
function AfterdisplayPicture() {       
	$('#AfterdisplayPicture').show();
	params = $("#num").val();	
	$("#tablename").val('os');
	$("#item").val('after');	
	
    var tablename = $("#tablename").val();    
    var item = $("#item").val();	
	
	$.ajax({
		url:'load_pic.php?num=' + params + '&tablename=' + tablename + '&item=' + item ,
		type:'post',
		data: $("board_form").serialize(),
		dataType: 'json',
		}).done(function(data){						
		   const recnum = data["recnum"];		   
		   console.log(data);
		   $("#AfterdisplayPicture").html('');
		   for(i=0;i<recnum;i++) {			   
			   $("#AfterdisplayPicture").append("<img id=Afterpic" + i + " src ='./uploads/" + data["img_arr"][i] + "' style='width:100%; height:100%'  > <br> " );			   
         	   $("#AfterdisplayPicture").append("&nbsp;<button type='button' class='btn btn-secondary' id='AfterdelPic" + i + "' onclick=delPicFn('" + i + "','" +  data["img_arr"][i] + "')> 삭제 </button>&nbsp;<br><br>");					   
		      }		   
			    $("#AfterpInput").val('');			
    });	
}

// 시공전 기존 있는 이미지 화면에 보여주기
function AfterdisplayPictureLoad() {    
	$('#AfterdisplayPicture').show();
	var AfterpicNum = "<? echo $AfterpicNum; ?>"; 					
	var AfterpicData = <?php echo json_encode($AfterpicData);?> ;	
    for(i=0;i<AfterpicNum;i++) {
       $("#AfterdisplayPicture").append("<img id=Afterpic" + i + " src ='./uploads/" + AfterpicData[i] + "' style='width:100%; height:100%' > <br>" );			
	   $("#AfterdisplayPicture").append("&nbsp;<button type='button' class='btn btn-secondary' id='AfterdelPic" + i + "' onclick=delPicFn('" + i + "','" + AfterpicData[i] + "')> 삭제 </button>&nbsp;<br><br>");			   
	  }		   
		$("#AfterpInput").val('');	
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
</script>
