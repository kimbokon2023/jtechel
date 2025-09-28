 <?php
  session_start(); 
  
 $level= $_SESSION["level"];
 if(!isset($_SESSION["level"]) || $level>=5) {
         echo "<script> alert('관리자 승인이 필요합니다.') </script>";
		 sleep(2);
         header ("Location:http://j-techel.co.kr/login/logout.php");
         exit;
   }   
  
  // 추가 / 수정 구분을 위해 작성
isset($_REQUEST["mode"])  ? $mode = $_REQUEST["mode"] : $mode="";   
isset($_REQUEST["num"])  ? $num = $_REQUEST["num"] : $num="";   
isset($_REQUEST["itemname"])  ? $itemname = $_REQUEST["itemname"] : $itemname="";   


if($itemname=="firstord")
{
   $itemtitle = '원청 데이터 등록/수정';
   $companydes = "원청";
}
if($itemname=="secondord")
{
   $itemtitle = '발주처 데이터 등록/수정';
   $companydes = "발주처";
}

   // $userData = ['Name' => $_GET['myName'], 'Age' => $_GET['myAge']];
   // foreach($userData as $value){
      // echo $value. '<br>';
   // }

// 배열을 가져오는 루틴 (전달받은 것을 풀어본다.)
for($i=1;$i<=9;$i++)
{
  $tmpname = 'item' . $i;	
  isset($_REQUEST[$tmpname])  ? $$tmpname = $_REQUEST[$tmpname] : $$tmpname="";     
}

?>

<!DOCTYPE HTML>
<html>
<head> 
<title> <?=$itemtitle?> </title>
<meta charset="utf-8">
<!-- CSS only -->	

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="http://j-techel.co.kr/common.js"></script>

 <!-- 최초화면에서 보여주는 상단메뉴 -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" >
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.1/font/bootstrap-icons.css">

</head>


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
  
</style>
  
<body>

	<form  id="board_form" name="board_form" method="post" > 
			      
       <input type="hidden" id="first_writer" name="first_writer" value="<?=$first_writer?>"  >
       <input type="hidden" id="update_log" name="update_log" value="<?=$update_log?>"  >
       <input type="hidden" id="page" name="page" value="<?=$page?>"  >
       <input type="hidden" id="mode" name="mode" value="<?=$mode?>"  >
       <input type="hidden" id="num"  name="num" value="<?=$num?>"  >
       <input type="hidden" id="itemname"  name="itemname" value="<?=$itemname?>"  >

<div class="container">    
<div class="d-flex mb-1 bt-1 p-2 mt-1 justify-content-center">    		
	
					<fieldset class="groupbox-border"> 		
					<legend class="groupbox-border"> <?=$itemtitle?>
						&nbsp;	&nbsp;	&nbsp;	&nbsp;
						<button type="button" id="saveBtn"  class="btn btn-primary btn-sm"> 저장 </button>	&nbsp;       
						<button type="button"  class="btn btn-secondary btn-sm" onclick="self.close();" > 창닫기 </button>	&nbsp;  
					</legend> 										
				
					<div class="input-group p-1 mb-1">																		  						  
						  <span class="input-group-text ">  <?=$companydes?>&nbsp; 회사명&nbsp;&nbsp; </span>
						  <input type="text"  class="form-control" name="item1" id="item1"  value="<?=$item1?>"  > 							  
						</div> 
					<div class="input-group p-1 mb-1">																		  						  
						  <span class="input-group-text ">  <?=$companydes?>&nbsp; 담당자&nbsp;&nbsp; </span>
						  <input type="text"  class="form-control" name="item2" id="item2"  value="<?=$item2?>"  > 							  
						</div> 
					<div class="input-group p-1 mb-1">																		  						  
						  <span class="input-group-text ">  <?=$companydes?>&nbsp; 연락처&nbsp;&nbsp; </span>
						  <input type="text"  class="form-control" name="item3" id="item3"value="<?=$item3?>"  > 							  
						</div> 
					</fieldset>	
</div>

</div>


		</form>
	 	  
	  
<script>

$(document).ready(function(){
		
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
				opener.location.reload();
				window.close();	
				  // 메시지 창 띄우기  문구, 해당초				

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
			 
			 
		 
	
});

</script> 
	</body>
 </html>
