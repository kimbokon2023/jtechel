<?php
 session_start();
 
$id=$_REQUEST["id"];
require_once("../lib/mydb.php");
$pdo = db_connect();
try{
    $sql="select * from jtechel.member where id=?";
    $stmh=$pdo->prepare($sql);
    $stmh->bindValue(1,$id,PDO::PARAM_STR);
    $stmh->execute();
    $count=$stmh->rowCount();
} catch (PDOException $Exception) {
    print "오류: ".$Exception->getMessage();

}

if($count<1) {
   print "검색결과가 없습니다.<br>";
} else {
    while($row=$stmh->fetch(PDO::FETCH_ASSOC)){
        $hp=explode("-",$row["hp"]);
        $hp2=$hp[1];
        $hp3=$hp[2];
        
        $email=explode("@",$row["email"]);
        $email1=$email[0];
        $email2=$email[1];
		$id=$row["id"];
		$name=$row["name"];
	}
}
    ?>
	
	
<!DOCTYPE html>
<html lang="ko">
  <head>
    <meta charset="utf-8">   
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->

	    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
<!-- 최초화면에서 보여주는 상단메뉴 -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" >
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.1/font/bootstrap-icons.css">

    <title>사용자 정보 수정</title>

  </head>

<body >
<div class="container-fluid">  
<div class="d-flex mt-5 mb-5 justify-content-center">  
	<div class="card align-middle  justify-content-center align-items-center" style="width:25rem; border-radius:20px;">
		<div class="card-title" style="margin-top:30px;">
			<h3 class="card-title text-center" style="color:#113366;"> 사용자 정보 수정 </h3>
		</div>
		<div class="card-body">
      <form class="form-signin" name="member_form" method="get" action="../member/updatePro.php?id=<?=$id?>">
        <h5 class="form-signin-heading">수정할 비밀번호를 입력하세요</h5>
		 <h4 class="form-signin-heading">사용자 ID : <?=$id?> </h4>
		 <h4 class="form-signin-heading">사용자 이름 : <?=$name?> </h4>
        <label for="inputPassword" class="sr-only">수정할 Password </label>
        <input type="password"  name="pass" class="form-control" placeholder="수정할 Password" required autofocus ><br>
       <label for="inputPassword"  class="sr-only"> Password 확인 </label>
        <input type="password" name="pass_confirm" class="form-control" placeholder="Password 확인" required><br>			
			  
				<input type="hidden" id="id" name="id" value="<?=$id?>" size="5" > 	  
				<input type="hidden" id="name" name="name" value="<?=$name?>" size="5" > 	  
          <!--     <div class="checkbox">
    <label>
            <input type="checkbox" value="remember-me"> 기억하기
          </label> 
        </div> -->
        <button type="button" id="btn-Yes" class="form-control mt-2 mb-2 btn btn-primary btn-block" onclick="check_input();"> 정보수정 완료</button>
        <button type="button" class="form-control btn btn-secondary btn-block " onclick="history.back(-1);"> 이전화면으로  돌아가기 </button>
      </form>
      
		</div>
	</div>
	</div>
	</div>
	</body>
<script>
  function check_id()
  {
    window.open("check_id.php?id="+document.member_form.id.value,"IDcheck","left=200, top=200,width=250,height=80,scrollbars=no, resizable=yes");
  }

  function check_nick()
  {
    window.open("check_nick.php?nick="+document.member_form.nick.value,"NICKcheck", "left=200,top=200,width=250,height=80, scrollbars=no, resizable=yes");
  }

function check_input()
	 {
	   if(document.member_form.pass.value == '' )
	   {
	     alert("수정할password가 비어있습니다. \n다시 입력해주세요.");
	     document.member_form.pass.focus();
	     document.member_form.pass.select();
	     return;
	   }		 
	   if(document.member_form.pass.value != document.member_form.pass_confirm.value)
	   {
	     alert("비밀번호가 일치하지 않습니다.\n다시 입력해주세요.");
	     document.member_form.pass.focus();
	     document.member_form.pass.select();
	     return;
	   }

   document.member_form.submit();

   }
   
</script>



	</html>