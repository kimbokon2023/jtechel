<?php
header("Content-Type: application/json");  //json을 사용하기 위해 필요한 구문

if(isset($_REQUEST["worker"])) 
	 $worker=$_REQUEST["worker"]; 
 
 // 제이테크인 경우는 전체
 
 if($worker=='제이테크')
       $sql = "select * from jtechel.jtech ";
   else
		$sql = "select * from jtechel.jtech where worker='$worker' ";
   

require_once("../lib/mydb.php");
$pdo = db_connect();	

$now = date("Y-m-d");	     // 현재 날짜와 크거나 같으면 출고예정으로 구분
$nowtime = date("H:i:s");	 // 현재시간	

$data = array();

try{  
// 레코드 전체 sql 설정
   $stmh = $pdo->query($sql);            // 검색조건에 맞는글 stmh
   
   while($row = $stmh->fetch(PDO::FETCH_ASSOC)) {       
		    array_push($data, $row);     
     }
   } catch (PDOException $Exception) {
    print "오류: ".$Exception->getMessage();
}  

//각각의 정보를 하나의 배열 변수에 넣어준다.

$data = array(
	"data" =>      $data,
);
   
//json 출력
echo(json_encode($data, JSON_UNESCAPED_UNICODE));

?>