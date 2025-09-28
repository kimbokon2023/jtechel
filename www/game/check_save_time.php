<?php
session_start();

ini_set('display_errors', 'On');

$level = $_SESSION["level"];
$user_name = $_SESSION["name"];
$id = $_SESSION["userid"];

header("Content-Type: application/json");
			  
require_once($_SERVER['DOCUMENT_ROOT'] . "/lib/mydb.php");
$pdo = db_connect();

     try{
        $sql = "select * from jtechel.checksave where num=? ";  // get target record
        $stmh = $pdo->prepare($sql); 
        $stmh->bindValue(1,'1',PDO::PARAM_STR); 
        $stmh->execute(); 
        while($row = $stmh->fetch(PDO::FETCH_ASSOC)) {
		
		  $checksave = $row["checksave"];
			
		}
		
     } catch (PDOException $Exception) {
        $pdo->rollBack();
        print "오류: ".$Exception->getMessage();
     } 


  // 저장된 시간 정보를 반환
  $data = array(   
    "savedTime" =>  $checksave
  );

  // JSON 출력
echo(json_encode($data, JSON_UNESCAPED_UNICODE));   

?>
