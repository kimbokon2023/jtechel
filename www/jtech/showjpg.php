<?php
session_start(); 
 
if(isset($_REQUEST["num"]))  //수정 버튼을 클릭해서 호출했는지 체크
   $num=$_REQUEST["num"];
  else
   $num="";
 
require_once("../lib/mydb.php");
$pdo = db_connect();

     try{
      $sql = "select * from jtechel.jtech where num = ? ";
      $stmh = $pdo->prepare($sql); 

      $stmh->bindValue(1,$num,PDO::PARAM_STR); 
      $stmh->execute();
      $count = $stmh->rowCount();            
	  $row = $stmh->fetch(PDO::FETCH_ASSOC);  // $row 배열로 DB 정보를 불러온다.
    if($count<1){  
      print "검색결과가 없습니다.<br>";
     }else{
   
            include 'rowDB.php';            
					
      }
     }catch (PDOException $Exception) {
       print "오류: ".$Exception->getMessage();
     }
 
// 견적서 분할하는 로직 불러오기 공통사용
include 'load_estimate.php'; 

// 메인 줄수

$rowCount = 24;


// 견적일 문구 만들기

$et_writeday = date("Y", strtotime($et_writeday)) . "년" . date("m", strtotime($et_writeday)) . "월".date("d", strtotime($et_writeday)) . "일" ;	

// 합계금액 나타내기
$hangulAmountStr = "一金" . number2hangul($totalamount_int) . "원整" ;
$hangulAmount = "￦ " . number_format($totalamount_int)  ;

?>

<!DOCTYPE HTML>
<html lang="ko">
<head>
   
<meta charset="utf-8">
  
<title>견적서 출력</title>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>  
<script src="../js/jspdf.min.js"></script>    <!-- pdf저장을 위한 자바스크립트 함수 불러오기 -->  
<script src="../common.js"></script> 

<!-- CSS only -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.1/font/bootstrap-icons.css">
<!-- JavaScript Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script> 
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script> 
<link  rel="stylesheet" type="text/css" href="../css/common.css">
<link  rel="stylesheet" type="text/css" media="print" href="../css/print2.css">
<link  rel="stylesheet" type="text/css" href="./css/screenshot.css">    

</head>

<body>

<form  id="board_form" name="board_form" method="post" > 

<input type=hidden id="imageURL" name="imageURL" >

<br>
&nbsp; &nbsp; <button type="button" id="closeBtn"  class="btn btn-outline-dark"> 창닫기 </button>	
&nbsp; &nbsp; <button type="button" id="saveimageBtn"  class="btn btn-secondary"> 이미지 저장 </button>	
&nbsp; &nbsp; <button type="button" id="saveBtn"  class="btn btn-dark"> PDF파일 저장 </button>	
</form>
<div id="print">  
<div id="outlineprint">  
	<div class="img">      
	
	<div class="clear"> </div>
    <div id="row1">   <?=$et_writeday?> </div>    
	<div class="clear"> </div>
    <div id="row2">   <?=$et_receiver?> </div>
    <div class="clear"> </div>			
    <div id="row3">  <?=$et_itemname?>  </div>   
    <div class="clear"> </div>		
    <div id="row4">   <?=$et_deadline?> </div>        
	<div class="clear"> </div>	
    <div id="row5">   <?=$et_wpname?> </div>           
	<div class="clear"> </div>	
    <div id="row6">   <?=$et_paymethod?> </div>           
	<div class="clear"> </div>	
    <div id="row7">   <?=$et_validation?> </div>           
	<div class="clear"> </div>		
    <div id="row_total">   
			   <div id="col1">   	
					<?=$hangulAmountStr ?> </div>           
			   <div id="col2">   	
					<?=$hangulAmount ?> </div>           
	</div>
	<div class="clear"> </div>		
    <div id="space">   </div>           
	<div class="clear"> </div>		
        
	  
	<?php
			for($i=0 ; $i < $rowCount ; $i++) 	 // 5개의 데이터 기준으로 찍고 하단에 totalamount 기록하기 위함
			{ 
			  if($description[$i]!=null) 
			  {				  
				   print '<div id="row8"> ';
				     
						print '<div id="col1" style="font-weight:bold;"  > '.  $sogaenumbering[$i]  . ' &nbsp;</div>';	
						 // 제목 글씨 두껍게 스타일주기 
                       if(	$sogaenumbering[$i] > 0	)						   
							print '<div id="col2" style="font-weight:bold;" > '.  $description[$i]  . '&nbsp;</div>';			
						else   if(	preg_replace("/\s+/","",$description[$i]) == '소계' 	)	
							print '<div id="col2" style="font-weight:bold;color:red; "> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.  $description[$i]  . '&nbsp;</div>';			
						else 
							print '<div id="col2"> '.  $description[$i]  . '&nbsp;</div>';			
						
						print '<div id="col3"> '.  $spec[$i]  . '&nbsp;</div>';				
						print '<div id="col4"> '.  $unit[$i]  . '&nbsp;</div>';		
					   if((int)$quantity[$i] > 0)				
							print '<div id="col5"> '.  number_format($quantity[$i])  . '&nbsp;</div>';				
						  else
							print '<div id="col5">&nbsp; </div>';			
						if((int)$unitprice[$i] > 0)				
							print '<div id="col6"> '.  number_format($unitprice[$i])  . '&nbsp;</div>';				
						  else
							  print '<div id="col6">&nbsp; </div>';				
						if((int)$amount[$i] > 0)				
							print '<div id="col7"> '.  number_format($amount[$i])  . '&nbsp;</div>';				
						  else
							  print '<div id="col7">&nbsp; </div>';			
						print '<div id="col7"> '.  $comment[$i]  . '&nbsp;</div>';				
				   print '&nbsp;</div> ' ;
			  }
			  else
			    {
				   print '<div id="row8"> ';
						print '<div id="col1"> &nbsp; </div>';				
				   print '</div> ' ;					
					
				}
			   
			}
	  ?>
		<div class="clear"> </div>		  
		<div id="row9"> <?=$totalamount?> </div>           
		<div class="clear"> </div>		  
		<div id="row10"> <?=$et_note?> </div>           
		<div class="clear"> </div>	
	
	</div>  <!-- end of div img-->	
</div>    <!-- end of outlineprint --> 	
</div>    <!-- end of print --> 

<canvas id="canvas" width="1300" height="1840"style="border:1px solid #d3d3d3;display:none"> </canvas>	
</body>

<script>

function partShot() {
  
var d = new Date();
var currentDate = ( d.getMonth() + 1 ) + "-" + d.getDate()  + "_" ;
var currentTime = d.getHours()  + "_" + d.getMinutes() +"_" + d.getSeconds() ;
var result = 'estimate' + currentDate + currentTime + '.jpg';		


	
//특정부분 스크린샷
html2canvas(document.getElementById("outlineprint"))
//id outlineprint 부분만 스크린샷
.then(function (canvas) {
//jpg 결과값
	drawImg(canvas.toDataURL('image/jpeg'));
	const imgBase64 = canvas.toDataURL('image/jpeg', 'image/octet-stream');
	const decodImg = atob(imgBase64.split(',')[1]);
	// console.log(decodImg);

  let array = [];
  for (let i = 0; i < decodImg .length; i++) {
    array.push(decodImg .charCodeAt(i));
  }

  const file = new Blob([new Uint8Array(array)], {type: 'image/jpeg'});
  const fileName = 'canvas_img_' + new Date().getMilliseconds() + '.jpg';
  let formData = new FormData();
  formData.append('file', file, fileName);

  $.ajax({
    type: 'post',
    url: 'http://j-techel.co.kr/jtech/imgupload.php',
    cache: false,
    data: formData,
    processData: false,
    contentType: false,
    success: function (data) {
      // alert('Uploaded !!');
      console.log('Uploaded !');      
	  var data = data.replaceAll("\"", "");
	  data = data.replaceAll("\r", "");
	  data = data.replaceAll("\n", "");
	  data = data.replace(/ /g, '');
	  $('#imageURL').val(data);	  
	  console.log(data);      	  
    }
  });
});
 

}  // end of function
  
function drawImg(imgData) {
// console.log(imgData);
//imgData의 결과값을 console 로그롤 보실 수 있습니다.
return new Promise(function reslove() {
//내가 결과 값을 그릴 canvas 부분 설정
var canvas = document.getElementById('canvas');
var ctx = canvas.getContext('2d');
//canvas의 뿌려진 부분 초기화
ctx.clearRect(0, 0, canvas.width, canvas.height);

var imageObj = new Image();
imageObj.onload = function () {
ctx.drawImage(imageObj, 10, 10);
//canvas img를 그리겠다.
};
imageObj.src = imgData;
//그릴 image데이터를 넣어준다.

}, function reject() { });

}

function saveAs(uri, filename) {
	var link = document.createElement('a');
	if (typeof link.download === 'string') {
		// 서버를 활용해서 
	link.href = uri;
	link.download = filename;
	document.body.appendChild(link);
	link.click();
	document.body.removeChild(link);
	} else {
	window.open(uri);
	}

}

function cleardiv() {
	 $('#outlineprint').empty();
}


$(document).ready(function(){	
		
	$("#closeBtn").click(function(){ 

		$.ajax({
			url: "delalljpg.php",
			type: "post",		
			data: $("#board_form").serialize(),			
			success : function( data ){
				console.log( data);
				window.close();					
			},
			  error : function( jqxhr , status , error ){
				   console.log( jqxhr , status , error );
			} 			      		
		   });	
	 
	});			
	
	$("#saveBtn").click(function(){  	 
        // $("#board_form").submit();
		popupCenter('pdf1.php?imageURL=' + $('#imageURL').val(), 'PDF파일보기/저장', 1300,800) ;		
	});			

	$("#saveimageBtn").click(function(){  	 
        tmp1 = '<?php echo $et_receiver; ?>';
        tmp2 = '<?php echo $et_itemname; ?>';
		
        // image파일 다운로드 할 수 있도록 한다.
		
		saveAs( $('#imageURL').val() , tmp1 + '(' + tmp2 + ')견적서.jpg' ) ;
						
	});			

	//윈도우 창을 닫을때 jpg 파일 삭제함  - 이것때문에 계속 오류가 발생한 것임... 창을 닫는 다는 것이 새로운 창을 띄운것과 같이 되므로...
	$(window).bind("beforeunload", function (e){	
		$.ajax({
			url: "delalljpg.php",
			type: "post",		
			data: $("#board_form").serialize(),			
			success : function( data ){
				console.log( data);
				window.close();					
			},
			  error : function( jqxhr , status , error ){
				   console.log( jqxhr , status , error );
			} 			      		
		   });
	});	
	
});


setTimeout(function() {
 partShot();
}, 500);
</script>	

</html>
