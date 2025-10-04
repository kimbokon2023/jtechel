<?php
// 환경별 기본 URL 설정
require_once '../config/environment.php';

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
    require_once("../lib/mydb.php");
    $pdo = db_connect();
    
    // 데이터베이스 연결 확인
    if (!$pdo) {
        throw new Exception('데이터베이스 연결에 실패했습니다.');
    }
    
    // 테이블 존재 확인 (디버그 목적)
    $tableCheckSql = "SHOW TABLES FROM jtechel LIKE 'game_member'";
    $tableCheckStmt = $pdo->query($tableCheckSql);
    if ($tableCheckStmt->rowCount() === 0) {
        throw new Exception('game_member 테이블이 존재하지 않습니다.');
    }
    
    // 입력 데이터 받기 및 검증
    $num = isset($_POST['num']) ? trim($_POST['num']) : '';
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $branchCode = isset($_POST['branch']) ? trim($_POST['branch']) : '';
    $id = isset($_POST['id']) ? trim($_POST['id']) : '';
    $pass = isset($_POST['pass']) ? trim($_POST['pass']) : '';
    $level = isset($_POST['level']) ? intval($_POST['level']) : 0;
    $mode = isset($_POST['mode']) ? trim($_POST['mode']) : '';
    
    // 지점 코드를 지점 이름으로 변환
    $branch = '';
    if (!empty($branchCode)) {
        // branch_select_helper.php 함수 활용
        require_once('branch_select_helper.php');
        $activeBranches = getActiveBranches($pdo);
        
        foreach ($activeBranches as $branchData) {
            if ($branchData['branch_code'] === $branchCode) {
                $branch = $branchData['branch_name'];
                break;
            }
        }
        
        // 지점 이름을 찾지 못한 경우 오류 처리
        if (empty($branch)) {
            throw new Exception('선택한 지점 정보를 찾을 수 없습니다. (코드: ' . $branchCode . ')');
        }
    }
    
    // 필수 필드 검증
    $errors = [];
    
    if (empty($name)) {
        $errors[] = '이름을 입력해주세요.';
    } elseif (mb_strlen($name) < 2) {
        $errors[] = '이름은 최소 2글자 이상이어야 합니다.';
    }
    
    if (empty($branch)) {
        $errors[] = '지점을 선택해주세요.';
    }
    
    if (empty($id)) {
        $errors[] = '아이디를 입력해주세요.';
    } elseif (strlen($id) < 3) {
        $errors[] = '아이디는 최소 3글자 이상이어야 합니다.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $id)) {
        $errors[] = '아이디는 영문, 숫자, 밑줄(_)만 사용 가능합니다.';
    }
    
    if (empty($pass)) {
        $errors[] = '패스워드를 입력해주세요.';
    } elseif (strlen($pass) < 4) {
        $errors[] = '패스워드는 최소 4자 이상이어야 합니다.';
    }
    
    if (!in_array($level, [1, 4])) {
        $errors[] = '올바른 권한 레벨을 선택해주세요.';
    }
    
    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => '입력 오류',
            'errors' => $errors
        ]);
        exit;
    }
    
    $pdo->beginTransaction();
    
    if ($mode === 'modify' && !empty($num)) {
        // 수정 모드
        
        // 아이디 중복 체크 (자신 제외)
        $checkSql = "SELECT COUNT(*) FROM jtechel.game_member WHERE id = ? AND num != ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$id, $num]);
        
        if ($checkStmt->fetchColumn() > 0) {
            throw new Exception('이미 사용 중인 아이디입니다.');
        }
        
        // 기존 데이터 존재 확인
        $existSql = "SELECT COUNT(*) FROM jtechel.game_member WHERE num = ?";
        $existStmt = $pdo->prepare($existSql);
        $existStmt->execute([$num]);
        
        if ($existStmt->fetchColumn() == 0) {
            throw new Exception('수정할 사용자를 찾을 수 없습니다.');
        }
        
        // 사용자 정보 업데이트 (nick 포함)
        $updateSql = "UPDATE jtechel.game_member 
                      SET name = ?, branch = ?, id = ?, pass = ?, level = ?, nick = ?, 
                          updated_at = NOW()
                      WHERE num = ?";
        $updateStmt = $pdo->prepare($updateSql);
        
        if (!$updateStmt) {
            $errorInfo = $pdo->errorInfo();
            throw new PDOException('SQL 준비 실패: ' . $errorInfo[2], $errorInfo[1]);
        }
        
        // nick은 name과 동일하게 설정
        $nick = $name;
        $result = $updateStmt->execute([$name, $branch, $id, $pass, $level, $nick, $num]);
        
        if (!$result) {
            $errorInfo = $updateStmt->errorInfo();
            throw new PDOException('사용자 정보 수정 실패: ' . $errorInfo[2], $errorInfo[1]);
        }
        
        $message = '사용자 정보가 성공적으로 수정되었습니다.';
        $action = 'updated';
        
    } else {
        // 등록 모드
        
        // 아이디 중복 체크
        $checkSql = "SELECT COUNT(*) FROM jtechel.game_member WHERE id = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$id]);
        
        if ($checkStmt->fetchColumn() > 0) {
            throw new Exception('이미 사용 중인 아이디입니다.');
        }
        
        // 새 사용자 등록 (기본값과 함께)
        $insertSql = "INSERT INTO jtechel.game_member 
                      (name, branch, id, pass, level, nick, hp, email, regist_day, created_at, updated_at) 
                      VALUES (?, ?, ?, ?, ?, ?, '', '', DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i'), NOW(), NOW())";
        $insertStmt = $pdo->prepare($insertSql);
        
        if (!$insertStmt) {
            $errorInfo = $pdo->errorInfo();
            throw new PDOException('SQL 준비 실패: ' . $errorInfo[2], $errorInfo[1]);
        }
        
        // nick은 name과 동일하게 설정
        $nick = $name;
        $result = $insertStmt->execute([$name, $branch, $id, $pass, $level, $nick]);
        
        if (!$result) {
            $errorInfo = $insertStmt->errorInfo();
            throw new PDOException('사용자 등록 실패: ' . $errorInfo[2], $errorInfo[1]);
        }
        
        $num = $pdo->lastInsertId();
        $message = '새 사용자가 성공적으로 등록되었습니다.';
        $action = 'created';
    }
    
    $pdo->commit();
    
    // 성공 응답
    echo json_encode([
        'success' => true,
        'message' => $message,
        'action' => $action,
        'data' => [
            'num' => $num,
            'name' => $name,
            'branch' => $branch,
            'id' => $id,
            'level' => $level
        ]
    ]);
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // 상세한 오류 로깅
    $errorDetails = [
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
    
    error_log('Database error in save_user.php: ' . json_encode($errorDetails));
    
    // 개발/디버그 모드에서는 상세 정보 포함
    $isDevelopment = (error_reporting() & E_ERROR);
    
    echo json_encode([
        'success' => false,
        'message' => '데이터베이스 오류가 발생했습니다.',
        'error' => $e->getMessage(),
        'error_code' => $e->getCode(),
        'debug_info' => $isDevelopment ? [
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
            'sql_state' => $e->getCode(),
            'received_data' => [
                'name' => $name ?? 'null',
                'branch_code' => $branchCode ?? 'null',
                'branch_name' => $branch ?? 'null', 
                'id' => $id ?? 'null',
                'level' => $level ?? 'null',
                'mode' => $mode ?? 'null',
                'num' => $num ?? 'null'
            ]
        ] : null
    ]); 
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // 상세한 오류 로깅
    $errorDetails = [
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]; 
    
    error_log('Error in save_user.php: ' . json_encode($errorDetails));
    
    // 개발/디버그 모드에서는 상세 정보 포함
    $isDevelopment = (error_reporting() & E_ERROR);
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => $e->getMessage(),
        'debug_info' => $isDevelopment ? [
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
            'received_data' => [
                'name' => $name ?? 'null',
                'branch_code' => $branchCode ?? 'null',
                'branch_name' => $branch ?? 'null',
                'id' => $id ?? 'null', 
                'level' => $level ?? 'null',
                'mode' => $mode ?? 'null',
                'num' => $num ?? 'null'
            ]
        ] : null
    ]);
}
?>