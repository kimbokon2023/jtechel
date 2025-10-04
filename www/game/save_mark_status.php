<?php
// 환경별 기본 URL 설정
require_once '../config/environment.php';

session_start();

ini_set('display_errors', 'On');

$level = $_SESSION["level"];
$user_name = $_SESSION["name"];
$id = $_SESSION["userid"];

isset($_POST["num"]) ? $num = $_POST["num"] : $num = "";
isset($_POST["index"]) ? $index = $_POST["index"] : $index = "";
isset($_POST["isChecked"]) ? $isChecked = $_POST["isChecked"] : $isChecked = "";

// 체크 상태를 서버에 저장하는 로직을 추가하세요.

$mark_arr = [];

// 기존에 저장된 배열을 불러오는 로직
if (isset($_SESSION["mark_arr"])) {
  $mark_arr = $_SESSION["mark_arr"];
} else {
  // 초기값으로 150개의 '0'으로 채워진 배열 생성
  $mark_arr = array_fill(0, 150, '0');
}

// 체크 상태 업데이트
$mark_arr[$index] = $isChecked;

// 업데이트된 체크 상태 배열을 세션에 저장
$_SESSION["mark_arr"] = $mark_arr;

// 데이터베이스에 저장
require_once("../lib/mydb.php");
$pdo = db_connect();

try {
  $pdo->beginTransaction();
  $sql = "UPDATE jtechel.game SET mark = ? WHERE num = ? LIMIT 1";
  $stmh = $pdo->prepare($sql);
  $mark_str = implode(',', $mark_arr); // 배열을 문자열로 변환
  $stmh->bindValue(1, $mark_str, PDO::PARAM_STR);
  $stmh->bindValue(2, $num, PDO::PARAM_STR);
  $stmh->execute();
  $pdo->commit();
} catch (PDOException $Exception) {
  $pdo->rollBack();
  print "오류: " . $Exception->getMessage();
}

// 응답 데이터
$response = [
  "num" => $num,
  "status" => "success",
  "message" => "체크 상태 저장 완료",
  "checkedRows" => $mark_arr
];

// JSON 형식으로 응답 출력
header("Content-Type: application/json");
echo json_encode($response);

?>
