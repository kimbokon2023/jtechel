<?php
    if(!isset($_SESSION)) 
    { 
        session_start(); 
    } 
  
  if(isset($_REQUEST["mode"]))  //수정 버튼을 클릭해서 호출했는지 체크
   $mode=$_REQUEST["mode"];
  else
   $mode="";
  
  if(isset($_REQUEST["num"]))  //수정 버튼을 클릭해서 호출했는지 체크
   $num=$_REQUEST["num"];
  else
   $num="";

   if(isset($_REQUEST["page"]))  //수정 버튼을 클릭해서 호출했는지 체크
   $page=$_REQUEST["page"];
  else
   $page=1;   

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
      $item_file_0 = $row["file_name_0"];
      $item_file_1 = $row["file_name_1"];

      $copied_file_0 = "../uploads/". $row["file_copied_0"];
      $copied_file_1 = "../uploads/". $row["file_copied_1"];
	 }
  
		 include 'rowDB.php';  
		 
    // customer 필드 가져오기 (Json형태의 값)
     $customer_data = $row["customer"];		
	 
	// JSON 데이터를 PHP 객체로 디코딩
	$customer_object = json_decode($customer_data);
	if ($customer_object === null) {
		// JSON 디코딩에 실패한 경우 처리
		// (올바르지 않은 JSON 형식일 경우, null을 반환합니다)
		echo "서명이 존재하지 않습니다.";
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

<title> 고객확인용 서명부분 </title>

</head>
 
<body>   
<form  id="board_form"  name="board_form" method="post" enctype="multipart/form-data"> 
 
 <input type="hidden" id="num" name="num" value="<?=$num?>"  >      
 <input type="hidden" id="image_url" name="image_url"  value="<?=$image_url?>"   >      
<div class="container">

	<div class="card mt-4">
			<div class="card-header text-center">
					 <h4 class="card-title "> 현장 정보</h4> 
			</div> 
			<div class="card-content" >
				<div class="card-body">
				
					<div class="row">						
							<div class="input-group mb-1" >
								<span class="input-group-text" style="width:150px;"  >현장(Project)번호 </span>
								 <input  type="text"  class="text-start <?=$fs?> form-control "  name="pjnum" id="pjnum" value="<?=$pjnum?>" >
							</div>
					</div>
					<div class="row">						
							<div class="input-group mb-1" >
								<span class="input-group-text"  style="width:150px;" > 현장명  </span>
								<input type="text" class="form-control" name="workplacename" value="<?=$workplacename?>"  >                                                    
							</div>												
					</div>
					<div class="row">						
							<div class="input-group mb-1" >
								<span class="input-group-text"  style="width:150px;" > 작업내용  </span>
								<input type="text" class="form-control" id="customer_worklist1"  name="customer_worklist1" value="<?=$customer_worklist1?>"  >                                                    
							</div>												
					</div>
					<div class="row">						
							<div class="input-group mb-1" >
								<span class="input-group-text"  style="width:150px;" > 기타내용 </span>
								<input type="text" class="form-control"  id="customer_worklist2"  name="customer_worklist2" value="<?=$customer_worklist2?>"  >                                                    
							</div>												
					</div>
					</div>
				</div>
			</div>
					
	<div class="card mt-4">
			<div class="card-header text-center">
					 <h4 class="card-title "> 고객확인용 정보 입력</h4> 
			</div> 			
			<div class="card-body">
				
					<div class="row">						
							<div class="input-group mb-1" >
								<span class="input-group-text" style="width:100px;"  >일시 </span>
								<input type="date" class="form-control" id="customer_date" name="customer_date" value="<?=$customer_date?>"  >                                                    
							</div>												
					</div>
					<div class="row">						
							<div class="input-group mb-1" >
								<span class="input-group-text"  style="width:100px;" >상호  </span>
								<input type="text" class="form-control"  id="customer_company"  name="customer_company" value="<?=$customer_company?>"  >                                                    
							</div>
						
						
					</div>
					<div class="row">
						<div class="col-lg-8 mb-1">
							<div class="input-group mb-1" >
								<span class="input-group-text" style="width:100px;"  >현장주소  </span>
								<input type="text" class="form-control" id="customer_address" name="customer_address" value="<?=$customer_address?>" >                                                    
							</div>
						</div>
						    <div class="col-lg-4 mb-1">                                                
						</div>
						
					</div>
					<div class="row">
						<div class="col-lg-8 mb-1">
							<div class="input-group mb-1" >
								<span class="input-group-text" style="width:100px;"  > 소속 :</span>
								<input type="text" class="form-control"  id="customer_group"  name="customer_group" value="<?=$customer_group?>"   >                                                    
							</div>
						</div>
						<div class="col-lg-4 mb-1">                                                
						</div>
						
					</div>
					<div class="row mb-3">						
							<div class="input-group mb-1" >
								<span class="input-group-text" style="width:100px;"  > 성명 :</span>
								<input type="text" class="form-control" id="customer_name"  name="customer_name" value="<?=$customer_name?>"   >                                                    
							</div>						
						
					</div>
									
				<div class="row mb-2">				
			     <div class="d-flex align-items-center">			
					<button type="button" class="btn btn-outline-secondary" onclick="self.close();">								
						닫기	</button> &nbsp; &nbsp; &nbsp; &nbsp; 서명 : 
						
					<?php
					   if(!empty($image_url))
						   print '<img id="signatureBtn" src="' . $image_url . '" style="width:20%;" >';
					?>
							&nbsp; &nbsp; &nbsp; &nbsp;
						<button type="button" id="printBtn"  class="btn btn-primary " >				
							확인서 보기 	</button>
						
						
					</div>
					</div>
					</div>
				</div>

</div>																

	</form>
<script>

window.onload = function () {
    <?php
    $customer_data = $customer_data ?? ''; // PHP 변수가 존재하지 않으면 빈 문자열로 초기화
    if (!empty($customer_data)) {
        echo "try {
            var customerData = JSON.parse('" . addslashes($customer_data) . "');
        } catch (e) {
            console.error('JSON 파싱 오류:', e);
            var customerData = {}; // 오류 시 빈 객체로 초기화
        }";
    } else {
        echo "var customerData = {}; // 데이터가 비어 있을 때 빈 객체로 초기화";
    }
    ?>

    // customer_date 필드 처리
    if (!customerData.customer_date || customerData.customer_date.trim() === "") {
        // customer_date 값이 없거나 공백인 경우 오늘 날짜 설정
        var today = new Date();
        var dd = String(today.getDate()).padStart(2, '0');
        var mm = String(today.getMonth() + 1).padStart(2, '0');
        var yyyy = today.getFullYear();
        var formattedDate = yyyy + '-' + mm + '-' + dd;
        var customerDateElement = document.getElementById("customer_date");
        if (customerDateElement !== null) {
            customerDateElement.value = formattedDate;
        }
    } else {
        // customer_date 값이 있는 경우 해당 값 사용
        var customerDateElement = document.getElementById("customer_date");
        if (customerDateElement !== null) {
            customerDateElement.value = customerData.customer_date;
        }
    }

    // customer_address 필드 처리
    if (!customerData.customer_address || customerData.customer_address.trim() === "") {
        // customer_address 값이 없거나 공백인 경우 PHP 변수 $address 사용
        var address = '<?php echo $address; ?>';
        var customerAddressElement = document.getElementById("customer_address");
        if (customerAddressElement !== null) {
            customerAddressElement.value = address;
        }
    } else {
        // customer_address 값이 있는 경우 해당 값 사용
        var customerAddressElement = document.getElementById("customer_address");
        if (customerAddressElement !== null) {
            customerAddressElement.value = customerData.customer_address;
        }
    }

    // 나머지 필드와 함께 customer_worklist1과 customer_worklist2 필드 처리    
    var customerCompanyElement = document.getElementById("customer_company");
    if (customerCompanyElement !== null) {
        customerCompanyElement.value = customerData.customer_company || '';
    }
    
    var customerGroupElement = document.getElementById("customer_group");
    if (customerGroupElement !== null) {
        customerGroupElement.value = customerData.customer_group || '';
    }
    
    var customerNameElement = document.getElementById("customer_name");
    if (customerNameElement !== null) {
        customerNameElement.value = customerData.customer_name || '';
    }
    
    var customerWorklist1Element = document.getElementById("customer_worklist1");
    if (customerWorklist1Element !== null) {
        customerWorklist1Element.value = customerData.customer_worklist1 || '<?php echo $worklist1; ?>';
    }
    
    var customerWorklist2Element = document.getElementById("customer_worklist2");
    if (customerWorklist2Element !== null) {
        customerWorklist2Element.value = customerData.customer_worklist2 || '<?php echo $worklist2; ?>';
    }
 
    var imageURLElement = document.getElementById("image_url");
    if (imageURLElement !== null) {
        imageURLElement.value = customerData.image_url || '';
    }
    
	
	
};

function captureReturnKey(e) {
    if(e.keyCode==13 && e.srcElement.type != 'textarea')
    return false;
}

function recaptureReturnKey(e) {
    if (e.keyCode==13)
        exe_search();
}

$(document).ready(function(){	
    $("#printBtn").click(function(){ 	
        const num = $("#num").val();	 
        
        // 폼의 각 요소를 console.log로 출력
        var form = $('#board_form')[0];
        form.querySelectorAll("input, select").forEach(function(element) {
            console.log(element.name, element.value);
        });

        // 입력된 고객 데이터를 가져오기
        var customerData = {
            customer_date: document.getElementById("customer_date").value,
            customer_company: document.getElementById("customer_company").value,
            customer_address: document.getElementById("customer_address").value,
            customer_group: document.getElementById("customer_group").value,
            customer_name: document.getElementById("customer_name").value,
            customer_worklist1: document.getElementById("customer_worklist1").value,
            customer_worklist2: document.getElementById("customer_worklist2").value,
            image_url: document.getElementById("image_url").value
        };

        // 폼 데이터 생성
        var formData = new FormData(form);
        formData.append('customerData', JSON.stringify(customerData)); // JSON 데이터를 문자열로 변환하여 추가

        // AJAX 요청
        $.ajax({
            enctype: 'multipart/form-data',    // file을 서버에 전송하려면 이렇게 해야 함 주의
            processData: false,    
            contentType: false,      
            cache: false,           
            timeout: 600000, 			
            url: "customer_save.php",
            type: "post",		
            data: formData, // 폼 데이터를 전송
            dataType: "json",  // JSON 형태로 응답을 기대
            success : function(data) {	   
                console.log(data);
            },
            error : function(jqxhr, status, error) {
                console.log(jqxhr, status, error);
            } 			      		
        });

        // Toastify를 사용하여 토스트 메시지 표시
        Toastify({
            text: "내용이 저장되었습니다.",
            duration: 3000,  // 토스트 메시지의 지속 시간 (3초)
            close: true,
            gravity: "top", // `top` or `bottom`
            position: 'right', // `left`, `center` or `right`			
        }).showToast();	 		

        setTimeout(function() {
			option ='<?php echo $option; ?>';
            popupCenter('customer_print.php?num=' + num  + '&option=' + option ,'고객확인용', 1600, 1000);             
        }, 2000); // 2초 후에 실행
    });
});


</script>


	</body>
 </html>
