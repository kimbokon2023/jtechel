<?php
session_start();
?>

<!DOCTYPE html>
<html lang="ko">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" >
	    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" ></script> 

    <title> 시스템 로그인</title>

  </head>

  <body cellpadding="0" cellspacing="0" width="100%" height="100%" align="center">
	
	
	
	<?php

$id=$_REQUEST["uid"];
$pw=$_REQUEST["upw"];
require_once("../lib/mydb.php");
$pdo=db_connect();

try{
    $sql="select * from jtechel.member where id=?";
    $stmh=$pdo->prepare($sql);
    $stmh->bindValue(1,$id,PDO::PARAM_STR);
    $stmh->execute();
    $count=$stmh->rowCount();
} catch (PDOException $Exception) {
    print "오류: ".$Exception->getMessage();

}

$row=$stmh->fetch(PDO::FETCH_ASSOC);
if($count<1) {
    ?>
<script>
alert("아이디가 틀립니다!");
history.back();
</script>

<?php
}  elseif($pw!=$row["pass"]) {
    ?>
<script>
  alert ("비밀번호가 틀립니다.!");      
  history.back();
</script>
<?php
} else {
    $_SESSION["userid"]=$row["id"];
    $_SESSION["name"]=$row["name"];
    $_SESSION["nick"]=$row["nick"];
    $_SESSION["level"]=$row["level"];
    $_SESSION["ecountID"]=$row["ecountID"];
    $_SESSION["part"]=$row["part"];	
	
// popwindow 띄우기	
	if($_SESSION["name"]=="김보곤") 
	{
	 ?>
	     <script>
        function popup(){
            var url = "/popwin/popup.php";
            var name = "popup test";
            var option = "width = 500, height = 500, top = 100, left = 200, location = no"
            window.open(url, name, option);
        }
    </script>
	 <?php
	}
  	
 $data=date("Y-m-d H:i:s") . " - " . $_SESSION["userid"] . " - " . $_SESSION["name"] ;	
 require_once("../lib/mydb.php");
 $pdo = db_connect();
 $pdo->beginTransaction();
 $sql = "insert into jtechel.logdata(data) values(?) " ;
 $stmh = $pdo->prepare($sql); 
 $stmh->bindValue(1, $data, PDO::PARAM_STR);   
 $stmh->execute();
 $pdo->commit(); 
 
 //$_SESSION["userid"] 값이 있으면 그곳으로 분기함.
   	if(isset($_SESSION["url"]))  
	{
		print 'url';
		
        header ('Location:' . $_SESSION["url"]);
	exit;  
	}  
	
	
// 김재구 팀장 오성이면
  	if($_SESSION["userid"]=='9225' || $_SESSION["part"]=='mywork' )  
	{
       header ("Location:../mywork/index.php");
	exit;  
	} 		
	
// 제이테크 관련 아이디가 있으면
  	if(isset($_SESSION["userid"]))  
	{
       header ("Location:../jtech/index.php");
	exit;  
	} 	
   
	else if($_SESSION["level"]>3) {
			   header ("Location:../index.php");
				exit;  
				}
}

?>

</body>
</html>