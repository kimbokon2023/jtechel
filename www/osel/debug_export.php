<?php
// 디버그용 엑셀 내보내기 테스트 파일
error_log("=== debug_export.php 시작 ===");

// 세션 확인
session_start();
if (!isset($_SESSION["level"]) || $_SESSION["level"] > 8) {
    error_log("세션 레벨 오류: " . ($_SESSION["level"] ?? 'null'));
    die('인증이 필요합니다.');
}

error_log("세션 확인 완료: " . $_SESSION['name'] ?? 'unknown');

// POST 데이터 확인
error_log("POST 데이터: " . print_r($_POST, true));
error_log("GET 데이터: " . print_r($_GET, true));

// 데이터베이스 연결 테스트
require_once '../lib/mydb.php';
$DB = 'jtechel';

try {
    $pdo = db_connect();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("USE $DB");
    error_log("데이터베이스 연결 성공");
} catch (PDOException $e) {
    error_log("데이터베이스 연결 실패: " . $e->getMessage());
    die("데이터베이스 연결 실패");
}

// PHPExcel 라이브러리 테스트
$excel_lib_path = '../PHPExcel_1.8.0/Classes/PHPExcel.php';
error_log("PHPExcel 라이브러리 경로: " . $excel_lib_path);
error_log("PHPExcel 라이브러리 존재: " . (file_exists($excel_lib_path) ? 'YES' : 'NO'));

if (!file_exists($excel_lib_path)) {
    die('PHPExcel 라이브러리를 찾을 수 없습니다.');
}

try {
    require_once $excel_lib_path;
    error_log("PHPExcel 라이브러리 로드 성공");
    
    $objPHPExcel = new PHPExcel();
    error_log("PHPExcel 객체 생성 성공");
    
    // 간단한 테스트 데이터로 시트 생성
    $sheet = $objPHPExcel->getActiveSheet();
    $sheet->setTitle('테스트');
    $sheet->setCellValue('A1', '테스트');
    $sheet->setCellValue('B1', '데이터');
    
    // 파일 출력 테스트
    $filename = 'debug_test_' . date('Y-m-d_H-i-s') . '.xlsx';
    error_log("테스트 파일명: " . $filename);
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save('php://output');
    
    error_log("테스트 Excel 파일 출력 완료");
    
} catch (Exception $e) {
    error_log("PHPExcel 테스트 실패: " . $e->getMessage());
    error_log("스택 트레이스: " . $e->getTraceAsString());
    die('PHPExcel 테스트 실패: ' . $e->getMessage());
}
?>
