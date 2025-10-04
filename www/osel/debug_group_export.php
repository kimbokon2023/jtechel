<?php
// 실제 그룹 데이터로 엑셀 내보내기 테스트
error_log("=== debug_group_export.php 시작 ===");

// 세션 확인
session_start();
if (!isset($_SESSION["level"]) || $_SESSION["level"] > 8) {
    error_log("세션 레벨 오류: " . ($_SESSION["level"] ?? 'null'));
    die('인증이 필요합니다.');
}

error_log("세션 확인 완료: " . ($_SESSION['name'] ?? 'unknown'));

// 데이터베이스 연결
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

// 그룹 목록 조회
$stmt = $pdo->query("SELECT * FROM site_groups WHERE is_deleted = 0 ORDER BY created_at DESC LIMIT 5");
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
error_log("그룹 목록 조회 완료: " . count($groups) . "개");

if (empty($groups)) {
    die('그룹이 없습니다. 먼저 그룹을 생성해주세요.');
}

// 첫 번째 그룹의 측정 데이터 조회
$group_id = $groups[0]['id'];
error_log("테스트할 그룹 ID: " . $group_id);

$stmt = $pdo->prepare("
    SELECT pm.*, sgm.created_at as added_to_group_at
    FROM panel_measurements pm
    INNER JOIN site_group_members sgm ON pm.id = sgm.measurement_id
    WHERE sgm.group_id = ? AND sgm.is_deleted = 0
    ORDER BY sgm.created_at DESC
");
$stmt->execute([$group_id]);
$measurements = $stmt->fetchAll(PDO::FETCH_ASSOC);

error_log("측정 데이터 조회 완료: " . count($measurements) . "개");
if (!empty($measurements)) {
    error_log("첫 번째 측정 데이터: " . print_r($measurements[0], true));
}

if (empty($measurements)) {
    die('그룹에 측정 데이터가 없습니다.');
}

// PHPExcel 라이브러리 로드
$excel_lib_path = '../PHPExcel_1.8.0/Classes/PHPExcel.php';
if (!file_exists($excel_lib_path)) {
    die('PHPExcel 라이브러리를 찾을 수 없습니다.');
}

require_once $excel_lib_path;
error_log("PHPExcel 라이브러리 로드 완료");

// PHPExcel 객체 생성
$objPHPExcel = new PHPExcel();
error_log("PHPExcel 객체 생성 완료");

// 첫 번째 시트 설정
$sheet = $objPHPExcel->getActiveSheet();
$sheet->setTitle('현장기초정보');

// 헤더 설정
$headers = [
    'A1' => 'ID',
    'B1' => '현장명',
    'C1' => '측정일자',
    'D1' => '측정자',
    'E1' => '카 내부 가로(mm)',
    'F1' => '카 내부 깊이(mm)',
    'G1' => '카 내부 높이(mm)',
    'H1' => '엘리베이터 대수',
    'I1' => '총 패널 수'
];

foreach ($headers as $cell => $value) {
    $sheet->setCellValue($cell, $value);
}

// 데이터 작성
$row = 2;
foreach ($measurements as $measurement) {
    error_log("측정 데이터 처리 중: " . $measurement['site_name']);
    
    $sheet->setCellValue('A' . $row, $measurement['id']);
    $sheet->setCellValue('B' . $row, $measurement['site_name']);
    $sheet->setCellValue('C' . $row, date('Y-m-d', strtotime($measurement['measurement_date'])));
    $sheet->setCellValue('D' . $row, $measurement['measurer_name']);
    $sheet->setCellValue('E' . $row, $measurement['car_inside_width']);
    $sheet->setCellValue('F' . $row, $measurement['car_inside_depth']);
    $sheet->setCellValue('G' . $row, $measurement['car_inside_height']);
    $sheet->setCellValue('H' . $row, $measurement['elevator_count'] ?? 1);
    $sheet->setCellValue('I' . $row, '계산중...');
    
    $row++;
}

error_log("데이터 작성 완료: " . ($row - 2) . "행");

// 파일 출력
$filename = 'debug_group_test_' . date('Y-m-d_H-i-s') . '.xlsx';
error_log("파일명: " . $filename);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');

error_log("디버그 그룹 엑셀 파일 출력 완료");
?>
