<?php
// 환경별 기본 URL 설정
require_once '../config/environment.php';

session_start();

ini_set('display_errors', 'On');

$level = $_SESSION["level"];
$user_name = $_SESSION["name"];
$id = $_SESSION["userid"];

isset($_REQUEST["num"]) ? $num = $_REQUEST["num"] : $num = "";

// 데이터베이스에서 체크 상태를 가져오는 로직을 추가하세요.

require_once("../lib/mydb.php");
$pdo = db_connect();

     try{
        $sql = "select * from jtechel.game where num=?";  // get target record
        $stmh = $pdo->prepare($sql); 
        $stmh->bindValue(1,$num,PDO::PARAM_STR); 
        $stmh->execute(); 
        $row = $stmh->fetch(PDO::FETCH_ASSOC);
				
      $mark = $row['mark'];

  $mark_arr = json_decode($row['mark'], true); // JSON 문자열을 배열로 변환
 } catch (PDOException $Exception) {
	$pdo->rollBack();
	print "오류: ".$Exception->getMessage();
 } 


// 응답 데이터
$response = [
  "num" => $num,
  "status" => "success",
  "message" => "체크 상태 가져오기 완료",
  "checkedRows" => $mark_arr,
  "mark" => $mark,
];

// JSON 형식으로 응답 출력
header("Content-Type: application/json");
echo json_encode($response);
?>
