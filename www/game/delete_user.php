<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// 인증 확인
if (!isset($_SESSION["level"]) || $_SESSION["level"] > 5) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => '관리자 권한이 필요합니다.'
    ]);
    exit;
}

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'POST 요청만 허용됩니다.'
    ]);
    exit;
}

try {
    // 데이터베이스 연결
    require_once($_SERVER['DOCUMENT_ROOT'] . "/lib/mydb.php");
    $pdo = db_connect();
    
    // 사용자 번호 받기
    $num = isset($_POST['num']) ? intval($_POST['num']) : 0;
    
    if (empty($num)) {
        echo json_encode([
            'success' => false,
            'message' => '삭제할 사용자 번호가 필요합니다.'
        ]);
        exit;
    }
    
    $pdo->beginTransaction();
    
    // 사용자 존재 확인
    $checkSql = "SELECT name, id FROM jtechel.game_member WHERE num = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$num]);
    $user = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('삭제할 사용자를 찾을 수 없습니다.');
    }
    
    // 사용자 삭제
    $deleteSql = "DELETE FROM jtechel.game_member WHERE num = ?";
    $deleteStmt = $pdo->prepare($deleteSql);
    $result = $deleteStmt->execute([$num]);
    
    if (!$result) {
        throw new Exception('사용자 삭제에 실패했습니다.');
    }
    
    $affectedRows = $deleteStmt->rowCount();
    
    if ($affectedRows === 0) {
        throw new Exception('삭제할 사용자가 없습니다.');
    }
    
    $pdo->commit();
    
    // 성공 응답
    echo json_encode([
        'success' => true,
        'message' => "사용자 '{$user['name']}({$user['id']})'가 성공적으로 삭제되었습니다.",
        'deleted_user' => [
            'num' => $num,
            'name' => $user['name'],
            'id' => $user['id']
        ]
    ]);
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log('Database error in delete_user.php: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => '데이터베이스 오류가 발생했습니다.',
        'error' => $e->getMessage()
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log('Error in delete_user.php: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>