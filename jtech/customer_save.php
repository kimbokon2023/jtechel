<?php
session_start();

$level = $_SESSION["level"];
$user_name = $_SESSION["name"];

$jsonData = isset($_REQUEST['customerData']) ? $_REQUEST['customerData'] : '';

// $jsonData를 JSON으로 파싱
// $data = json_decode($jsonData, true);

if ($jsonData === null) {
    // JSON 데이터 파싱에 실패한 경우
    http_response_code(400); // Bad Request
    echo json_encode(array("message" => "Invalid JSON data."));
    exit();
}

// 필요한 필드 및 데이터 추출
$num = isset($_REQUEST["num"]) ? $_REQUEST["num"] : "";

if (empty($num)) {
    // 필요한 데이터가 없는 경우 오류 응답
    http_response_code(400); // Bad Request
    echo json_encode(array("message" => "Missing 'num' parameter."));
    exit();
}

// 여기에서 필요한 업데이트 또는 삽입 작업 수행
$table = 'jtech';
$field = 'customer';

try {
    // 업데이트 쿼리 생성
    $sql = "UPDATE jtechel.$table SET $field = ? WHERE num = ? LIMIT 1";

    // 데이터베이스 연결
    require_once("../lib/mydb.php");
    $pdo = db_connect();

    $pdo->beginTransaction();

    // 쿼리 실행
    $stmh = $pdo->prepare($sql);
    $stmh->bindValue(1, $jsonData, PDO::PARAM_STR);
    $stmh->bindValue(2, $num, PDO::PARAM_STR);
    $stmh->execute();

    // 트랜잭션 커밋
    $pdo->commit();
} catch (PDOException $Exception) {
    $pdo->rollBack();
    // 오류 응답
    http_response_code(500); // Internal Server Error
    echo json_encode(array("message" => "Database error: " . $Exception->getMessage()));
    exit();
}

// 성공 응답
http_response_code(200); // OK
echo json_encode(array("message" => $jsonData ));
?>
