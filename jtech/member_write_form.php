<?php
session_start();

$check = isset($_COOKIE['check']) ? $_COOKIE['check'] : 'false';
$lastdate = isset($_COOKIE['lastdate']) ? $_COOKIE['lastdate'] : 'false';

$level= $_SESSION["level"];
$user_name= $_SESSION["name"];
										  
isset($_REQUEST["id"])  ? $id=$_REQUEST["id"] :   $id=''; 
require_once($_SERVER['DOCUMENT_ROOT'] . "/lib/mydb.php");
$pdo = db_connect();	

	
if($id!=='null')
{
 try{
	  $sql = "select * from jtechel.member where id = ? ";
	  $stmh = $pdo->prepare($sql); 
      $stmh->bindValue(1,$id,PDO::PARAM_STR); 
      $stmh->execute();
      $count = $stmh->rowCount();            
	  $row = $stmh->fetch(PDO::FETCH_ASSOC);  // $row 배열로 DB 정보를 불러온다.		
		 
		$userid=$row["id"];
		$name=$row["name"];
		$pass=$row["pass"];
		$mylevel=$row["level"];		
		$part=$row["part"];			
		 
	 }catch (PDOException $Exception) {
	   print "오류: ".$Exception->getMessage();
	 }
 // end of if	
 
 	$mode = 'modify';

}

else
{
	$userid='';
	$mylevel= '4';	
	$mode = 'insert';
	
}

if($userid!=='')
	$readonly = 'readonly';
else
	$readonly = '';
 
?>  
  
<?php include $_SERVER['DOCUMENT_ROOT'] . '/jtech/load.php' ?>
 
  
<title> 회원관리(등록/수정) </title> 

    <style>
        .table-hover tbody tr:hover {
            cursor: pointer;
        }
    </style> 
 
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
  
<form id="board_form"  name="board_form" class="form-signin" method="post"  >  

<div class="container ">
    <div class="d-flex justify-content-center align-items-center ">	
        <div class="col-12 text-center">
			<div class="card align-middle" >
				<div class="card" style="padding:10px;margin:10px;">
					<h4 class="card-title text-center" style="color:#113366;"> 회원등록/수정 </h4>
				</div>	
				<div class="card-body text-center">
			  
				<input type="hidden" id="mode" name="mode"  value="<?=$mode?>"  >								
				<span class="form-control">					
					id
					<input type="text" id="id" name="id" value="<?=$userid?>" class="form-control text-center" <?=$readonly?> >	
				</span>
			  
				<span class="form-control">
					성명		
					<input type="text" id="name" name="name" value="<?=$name?>" class="form-control text-center" >	
				</span>

				<span class="form-control">					
					password
					<input type="text" id="pass" name="pass" value="<?=$pass?>" class="form-control text-center" >	
				</span>	
				<span class="form-control">					
					레벨
					<input type="text" id="level" name="level" value="<?=$mylevel?>" class="form-control text-center" >	
				</span>	
							
					<br>
				<span class="form-control">						
				<? if($user_name==='제이테크' ) 
				
					print '<button  class="btn btn-secondary btn-block" type="button" onclick="self.close();" > 닫기 	</button> &nbsp;';
					print '<button id="saveBtn" class="btn btn-dark btn-block" type="button"> 저장 	</button> &nbsp;';
					print '<button id="delBtn" class="btn btn-danger btn-block" type="button">삭제</button>';
				 ?>
				</span>								
					
				</div>
			</div>
		</div>						
  </div>

</div>	

</form>			  

</body>
</html>

	
		  
<script> 

$(document).ready(function(){
	
	var state =  $('#state').val();  	
	// 처리완료인 경우는 수정하기 못하게 한다.

	$("#closeModalBtn").click(function(){ 
		$('#myModal').modal('hide');
	});

	$("#closeBtn").click(function(){    // 저장하고 창닫기	

		 });	
					
$("#saveBtn").click(function(){      // DATA 저장버튼 누름

	$.ajax({
		url: "./member_insert.php",
		type: "post",		
		data: $("#board_form").serialize(),
		dataType:"json",
		success: function(data) {			
		
				console.log(data);
				opener.location.reload();
				window.close();
			
		},
		error: function(jqxhr, status, error) {
			console.error("Error response:", jqxhr.responseText);
		}
		   
			
	   });		
	
		
 }); 
		 
$("#delBtn").click(function(){      // del
var id = $("#id").val();    
var state = $("#state").val();  
   
   $("#mode").val('delete');     
	  
	$.ajax({
		url: "member_insert.php",
		type: "post",		
		data: $("#board_form").serialize(),
		dataType:"json",
		success : function( data ){
			console.log( data);
		    opener.location.reload();
		    window.close();			
		},
			error : function( jqxhr , status , error ){
				console.log( jqxhr , status , error );
			} 			      		
	   });		
   });		

}); // end of ready document

// 두날짜 사이 일자 구하기 
const getDateDiff = (d1, d2) => {
  const date1 = new Date(d1);
  const date2 = new Date(d2);
  
  const diffDate = date1.getTime() - date2.getTime();
  
  return Math.abs(diffDate / (1000 * 60 * 60 * 24)); // 밀리세컨 * 초 * 분 * 시 = 일
}


function updateCheck() {
    let isChecked = document.getElementById('check').checked;
    document.cookie = "check=" + isChecked + ";path=/";    	
    document.cookie = "lastdate=" + $("#askdatefrom").val();
}


</script>
