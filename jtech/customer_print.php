<?php
    if(!isset($_SESSION)) 
    { 
        session_start(); 
    } 
    
  if(isset($_REQUEST["num"]))  //수정 버튼을 클릭해서 호출했는지 체크
   $num=$_REQUEST["num"];
  else
   $num="";

  if(isset($_REQUEST["option"]))  //수정 버튼을 클릭해서 호출했는지 체크
   $option=$_REQUEST["option"];   
      
  require_once("../lib/mydb.php");
  $pdo = db_connect();

  try{
      $sql = "select * from jtechel.jtech where num = ? ";
      $stmh = $pdo->prepare($sql); 

    $stmh->bindValue(1,$num,PDO::PARAM_STR); 
      $stmh->execute();
      $count = $stmh->rowCount();              
    if($count<1){  
      print "검색결과가 없습니다.<br>";
     }else{
		$row = $stmh->fetch(PDO::FETCH_ASSOC);
	 }
	 
  	 include 'rowDB.php';  
			 
	$customer_data = $row["customer"];

	// JSON 데이터를 PHP 객체로 디코딩
	$customer_object = json_decode($customer_data);

	if ($customer_object === null) {
		// JSON 디코딩에 실패한 경우 처리
		// (올바르지 않은 JSON 형식일 경우, null을 반환합니다)
		echo "JSON 디코딩에 실패했습니다.";
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
  
  $worklist1 = $material1 . " " .  $material2 . " " .   $material3 . " " .   $material4 . " " .   $material5 ;  

?>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/jtech/load.php' ?>

<title> 고객 작업확인서 출력</title>
<?PHP
if($option==='1')
   print '<link  rel="stylesheet" type="text/css" href="css/print1.css?v=1">	';
  else
	  print '<link  rel="stylesheet" type="text/css" href="css/print2.css?v=1">	';
?>

 </head>

<body>
<div class="container">  
   <div class="d-flex mt-3">  
      <button type="button" id="signatureBtn" class="btn btn-dark fs-2" > 서명하기 </button> &nbsp;&nbsp;	
      <button type="button" id="saveBtn" class="btn btn-dark fs-2" > 확인서 이미지로 저장 </button> &nbsp;&nbsp;	
	<button  type="button" onclick="self.close();" class="btn btn-secondary fs-2 " >닫기 </button> &nbsp;&nbsp;
  </div>
 </div>
 
  

<div id="print">  
<div id="outlineprint">  
    <div class="img">      
	<div class="clear"> </div>
    <div id="row1">   <?=$pjnum?> </div>
    <div class="clear"> </div>			
    <div id="row2">   <?=$workplacename?>  </div>
    <div class="clear"> </div>		
    <div id="row3">   <?=$customer_worklist1?>  </div>    
    <div class="clear"> </div>			
	<div id="row4">  <?=$customer_worklist2?>  </div>   
	<div class="clear"> </div>	   		
	<div id="row5">  <?=$year?>  &nbsp;&nbsp; <?=$month?> &nbsp;&nbsp;  <?=$day?>    </div>   
	<div class="clear"> </div>	   		
	<div id="row6">  <?=$customer_company?>  </div>   
	<div class="clear"> </div>	   		
	<div id="row7">  <?=$customer_address?>  </div>   
	<div class="clear"> </div>	        
	<div id="row8">  <?=$customer_group?>  </div>   
	<div class="clear"> </div>	   		
	<div id="row9"> 
    	<div id="col1">  
			<?=$customer_name?>  
		</div>
    	<div id="col2">  
			<?php
			   if(!empty($image_url))
				   print '<img id="signatureBtn" src="' . $image_url . '" style="width:80%;" >';
			?>
			
		</div>		
	</div>   
	<div class="clear"> </div>	   		
	

 </div>    <!-- end of outline --> 
</div>    <!-- end of print --> 
		<canvas id="canvas" width="1300" height="1840"style="border:1px solid #d3d3d3; display:none;"></canvas>	
</body>
<script>



$(document).ready(function(){	

	var num = '<?php echo $num; ?>';				  
	var image_url = '<?php echo $image_url; ?>';		

    $("#signatureBtn").click(function(){ 	
		popupCenter('signature_pad.php?num=' + num, '고객서명', 800, 800); 		
	});
	
    $("#saveBtn").click(function(){ 		
		partShot('1');		
		// Toastify를 사용하여 토스트 메시지 표시
		Toastify({
			text: "확인서를 이미지로 저장했습니다.",
			duration: 3000,  // 토스트 메시지의 지속 시간 (3초)
			close: true,
			gravity: "top", // `top` or `bottom`
			position: 'center', // `left`, `center` or `right`			
		}).showToast();
	});
	
	
	if(image_url==='')
	{
		setTimeout(function() {        
			popupCenter('signature_pad.php?num=' + num, '고객서명', 800, 800); 
		}, 1000); // 1초 후에 실행          
	}
	
	
	
});

 
	function partShot(number) {
		var workplace = '<?php echo $workplacename; ?>';
			var d = new Date();
			var currentDate = ( d.getMonth() + 1 ) + "-" + d.getDate()  + "_" ;
			var currentTime = d.getHours()  + "_" + d.getMinutes() +"_" + d.getSeconds() ;
			var result = '고객확인용(' + workplace +')' + currentDate + currentTime + '.jpg';		
		
	//특정부분 스크린샷
	html2canvas(document.getElementById("outlineprint"))
	//id outlineprint 부분만 스크린샷
	.then(function (canvas) {
	//jpg 결과값
	drawImg(canvas.toDataURL('image/jpeg'));
	//이미지 저장
	saveAs(canvas.toDataURL(), result);
	}).catch(function (err) {
	console.log(err);
	});
	}

	function drawImg(imgData) {
	console.log(imgData);
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
 	
	


</script>	

</html>
