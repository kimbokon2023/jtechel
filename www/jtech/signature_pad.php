<?php include $_SERVER['DOCUMENT_ROOT'] . '/jtech/load.php' ?>

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
       
?>

<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="https://cdnjs.cloudflare.com/ajax/libs/signature_pad/1.5.3/signature_pad.min.js"></script>
    <title>시공완료 서명</title>
</head>
<body>

<div class="container mt-5 mb-5 justify-content-center">	
	  
<div class="row mt-2 mb-2 justify-content-center ">	
       <span class="badge bg-primary fs-3 mt-2 mb-2" > 시공확인 서명 </span>	
</div>	  
<div class="row mt-3 mb-5 justify-content-center" >
    <!-- 서명 이미지 표시할 div -->
    <canvas id="signature-pad" width="400" height="200" style="border: 1px solid black;"></canvas>
</div>  
<div class="d-flex  mt-5 mb-3 justify-content-center ">	
 <button type="button" class="btn btn-dark fs-1" onclick="saveSignature()"> 서명 저장 </button> &nbsp;&nbsp;
<button  type="button" class="btn btn-secondary fs-1" onclick="clearSignature()">서명 지우기</button> &nbsp;&nbsp;
<button  type="button" id="closeBtn" class="btn btn-secondary fs-1" >닫기 </button> &nbsp;&nbsp;
</div>

<script>
// 기존 캔버스 엘리먼트와 이미지 엘리먼트를 가져옴
var signaturePad = new SignaturePad(document.getElementById('signature-pad'));
var canvas = document.getElementById('signature-pad');
var signatureImage = document.getElementById('signature-image');


// 이미지 URL 불러와서 캔버스에 표시
if ('<?php echo $image_url; ?>' !== '') {
    var img = new Image();
    img.src = '<?php echo $image_url; ?>';

    img.onload = function() {
        canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
        // 캔버스에 이미지가 로드되면 서명 패드에도 적용
        signaturePad.fromDataURL('<?php echo $image_url; ?>');
    };
}



function saveSignature() {
    var dataURL = signaturePad.toDataURL();
    sendDataToServer(dataURL);
}

function clearSignature() {
    signaturePad.clear();
    // 이미지를 화면에서 지움
    clearSignatureImage();
}


function clearSignatureImage() {
    signatureImage.innerHTML = ''; // 이미지를 지움
}

	
function sendDataToServer(dataURL) {
    var xhr = new XMLHttpRequest();
	
	var num = '<?php echo $num; ?>';
	
    xhr.open('POST', 'save_signature.php?num=' + num , true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4 && xhr.status == 200) {
                    // Toastify를 사용하여 토스트 메시지 표시
        Toastify({
            text: "서명이 저장되었습니다.",
            duration: 3000,  // 토스트 메시지의 지속 시간 (3초)
            close: true,
            gravity: "top", // `top` or `bottom`
            position: 'center', // `left`, `center` or `right`			
        }).showToast();	 	
		
        }
    };
    xhr.send('img=' + dataURL);
}


$(document).ready(function(){	

    $("#closeBtn").click(function(){ 	     
		// 부모 창(opener)를 새로고침
		opener.location.reload();
		self.close();
	});
});
	
	
</script>
</body>
</html>
