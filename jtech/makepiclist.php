<?php
session_start();

header("Content-Type: application/json");

// raw POST data를 가져옵니다.
$jsonData = json_decode(file_get_contents("php://input"), true);

$num = $jsonData['num']; // num 값을 가져옵니다.
$data = $jsonData['data']; // columns 데이터를 가져옵니다.

if (intval($num) > 0) {
    require_once("../lib/mydb.php");
    $pdo = db_connect();
    try {
        $pdo->beginTransaction();
        
        // SQL 쿼리를 올바르게 수정합니다.
        $sql = "UPDATE jtechel.jtech SET piclist = ? WHERE num = ?"; // 테이블명과 조건을 확인해야 합니다.
        
        $stmh = $pdo->prepare($sql);
        $stmh->bindValue(1, json_encode($data), PDO::PARAM_STR); // JSON 문자열로 저장
        $stmh->bindValue(2, $num, PDO::PARAM_INT); // num 값 바인딩
        
        $stmh->execute();
        
        $pdo->commit();
    } catch (PDOException $Exception) {
        $pdo->rollBack();
        print "오류: " . $Exception->getMessage();
    }
}

echo json_encode(["status" => "success", "num" => $num], JSON_UNESCAPED_UNICODE);
?>
