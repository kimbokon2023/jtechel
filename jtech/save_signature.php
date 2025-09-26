<?php

$myDir = 'jtech';

if (isset($_POST['img'])) {
    $img = $_POST['img'];
    $img = str_replace('data:image/png;base64,', '', $img);
    $img = str_replace(' ', '+', $img);
    $data = base64_decode($img);
    $file = '../' . $myDir . '/signatures/' . uniqid() . '.png';

    if (!file_exists('../' . $myDir . '/signatures/')) {
        mkdir('signatures', 0777, true);
    }

    file_put_contents($file, $data);
    echo "Saved!";
	
	
// 서버에 파일명 저장 부분 구현	

// 필요한 필드 및 데이터 추출
$num = isset($_REQUEST["num"]) ? $_REQUEST["num"] : "";

require_once("../lib/mydb.php");
$pdo = db_connect();

try {
    $sql = "select * from jtechel.jtech where num = ? ";
    $stmh = $pdo->prepare($sql);

    $stmh->bindValue(1, $num, PDO::PARAM_STR);
    $stmh->execute();
    $count = $stmh->rowCount();
    if ($count < 1) {
        print "검색결과가 없습니다.<br>";
    } else {
        $row = $stmh->fetch(PDO::FETCH_ASSOC);
    }

    // 기존 JSON 데이터 디코딩
    $customerData = json_decode($row["customer"], true);

    // 이미지 파일의 경로와 파일명 생성
    $fileUrl = 'signatures/' . basename($file);

    // 이미지 URL을 기존 JSON 데이터에 추가
    $customerData['image_url'] = $fileUrl;

    // JSON 데이터를 다시 문자열로 인코딩
    $jsonDataString = json_encode($customerData);

    // 업데이트 쿼리 생성
    $table = 'jtech';
    $field = 'customer';
    $sql = "UPDATE jtechel.$table SET $field = ? WHERE num = ? LIMIT 1";

    // 트랜잭션 시작
    $pdo->beginTransaction();

    // 쿼리 실행
    $stmh = $pdo->prepare($sql);
    $stmh->bindValue(1, $jsonDataString, PDO::PARAM_STR);
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


	
} else {
    echo "No image data received";
}
?>
