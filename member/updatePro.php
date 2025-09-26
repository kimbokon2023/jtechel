
<?php

$id=$_REQUEST["id"];
$pass=$_REQUEST["pass"];    
$name=$_REQUEST["name"];    
$regist_day=date("Y-m-d H:i:s");

require_once("../lib/mydb.php");
$pdo = db_connect();
try {
    $pdo->beginTransaction();
    $sql="update jtechel.member set pass=?,name=?,regist_day=? where id=?";
    $stmh=$pdo->prepare($sql);
    $stmh->bindvalue(1,$pass,PDO::PARAM_STR);
    $stmh->bindvalue(2,$name,PDO::PARAM_STR);
    $stmh->bindvalue(3,$regist_day,PDO::PARAM_STR);
    $stmh->bindvalue(4,$id,PDO::PARAM_STR);
    
    $stmh->execute();
    $pdo->commit();
    
    header("Location:http://j-techel.co.kr/index.php");
    
} catch (PDOException $Exception) {
   $pdo->rollBack();
   print "오류: ".$Exception->getMessage();
}
?>
