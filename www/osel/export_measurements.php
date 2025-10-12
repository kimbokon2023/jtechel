<?php
// 강력한 출력 버퍼 관리 - 로컬 환경 대응
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

require_once '../lib/mydb.php';
session_start();
$DB = 'jtechel';

// Initialize database connection
$pdo = db_connect();
 
// Initialize database connection with error handling
try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("USE $DB");
} catch (PDOException $e) {
    error_log("Database connection failed in export_measurements.php: " . $e->getMessage());
    die("데이터베이스 연결에 실패했습니다.");
}

// Get search parameters and selected IDs
$search_site = $_GET['search_site'] ?? '';
$search_date_from = $_GET['search_date_from'] ?? '';
$search_date_to = $_GET['search_date_to'] ?? '';
$search_measurer = $_GET['search_measurer'] ?? '';
$selected_ids = $_GET['selected_ids'] ?? '';

// Build search query
$where_conditions = [];
$params = [];

// If specific IDs are selected, use those instead of search conditions
if (!empty($selected_ids)) {
    $ids_array = array_filter(explode(',', $selected_ids), 'is_numeric');
    if (!empty($ids_array)) {
        $placeholders = str_repeat('?,', count($ids_array) - 1) . '?';
        $where_conditions[] = "id IN ($placeholders)";
        $params = array_merge($params, $ids_array);
    }
} else {
    // Use search conditions if no specific IDs are selected
    if (!empty($search_site)) {
        $where_conditions[] = "site_name LIKE ?";
        $params[] = "%{$search_site}%";
    }

    if (!empty($search_date_from)) {
        $where_conditions[] = "measurement_date >= ?";
        $params[] = $search_date_from;
    }

    if (!empty($search_date_to)) {
        $where_conditions[] = "measurement_date <= ?";
        $params[] = $search_date_to;
    }

    if (!empty($search_measurer)) {
        $where_conditions[] = "measurer_name LIKE ?";
        $params[] = "%{$search_measurer}%";
    }
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

try {
    // First check if table exists
    $table_check = $pdo->query("SHOW TABLES LIKE 'panel_measurements'");

    if ($table_check->rowCount() == 0) {
        die('panel_measurements 테이블이 존재하지 않습니다.');
    }

    // Get all measurement data (no pagination for export)
    $query = "
        SELECT
            id,
            site_name,
            measurement_date,
            measurer_name,
            measurer_id,
            car_inside_width,
            car_inside_depth,
            car_inside_height,
            car_structure,
            material_type,
            material_thickness,
            COALESCE(elevator_count, 1) as elevator_count,
            panel_data,
            transom_data,
            notes,
            created_at,
            updated_at
        FROM panel_measurements
        $where_clause
        ORDER BY site_name, measurement_date, id
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $measurements = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('Export database error: ' . $e->getMessage());
    die('데이터베이스 오류가 발생했습니다.');
}

// PhpSpreadsheet를 사용한 Excel 생성
// Check for required libraries - 환경별 경로 설정
require_once '../config/environment.php';

$excel_lib_path = null;
if (isLocalEnvironment()) {
    // 로컬 환경
    $excel_lib_path = '../PHPExcel_1.8.0/Classes/PHPExcel.php';
} else {
    // 서버 환경 - 가능한 경로들을 순서대로 확인
    $possible_paths = [
        '../PHPExcel_1.8.0/Classes/PHPExcel.php',
        './PHPExcel_1.8.0/Classes/PHPExcel.php',
        '/home/jtechel/public_html/PHPExcel_1.8.0/Classes/PHPExcel.php',
        dirname(__FILE__) . '/../PHPExcel_1.8.0/Classes/PHPExcel.php'
    ];
    
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            $excel_lib_path = $path;
            break;
        }
    }
}

if (!$excel_lib_path || !file_exists($excel_lib_path)) {
    error_log("PHPExcel 라이브러리를 찾을 수 없습니다. 시도한 경로들: " . implode(', ', $possible_paths ?? [$excel_lib_path]));
    die('PHPExcel 라이브러리를 찾을 수 없습니다. 관리자에게 문의하세요.');
}

require_once $excel_lib_path;

// 디버깅: PHPExcel 라이브러리 로드 확인
error_log("=== export_measurements.php 디버깅 시작 ===");
error_log("PHPExcel 라이브러리 경로: " . $excel_lib_path);
error_log("PHPExcel 클래스 존재 여부: " . (class_exists('PHPExcel') ? 'YES' : 'NO'));

error_log("PHPExcel 객체 생성 시도...");
$objPHPExcel = new PHPExcel();
error_log("PHPExcel 객체 생성 성공!");
$objPHPExcel->getProperties()->setCreator("OSEL Panel Measurement System")
                            ->setLastModifiedBy("OSEL Panel Measurement System")
                            ->setTitle("Panel Measurements")
                            ->setSubject("Panel Measurements Export")
                            ->setDescription("Exported panel measurement data from OSEL system");

// 첫 번째 시트: 현장정보
$sheet = $objPHPExcel->getActiveSheet();
$sheet->setTitle('현장정보');

// 헤더 스타일 설정
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => ['rgb' => '4CAF50']],
    'borders' => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]],
    'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER]
];

// 헤더 설정
$headers = [
    'A1' => '고유번호',
    'B1' => '현장명',
    'C1' => '측정일자',
    'D1' => '측정자',
    'E1' => '카 내부 W',
    'F1' => '카 내부 D',
    'G1' => '카 내부 H',
    'H1' => '카 구조',
    'I1' => '의장재질',
    'J1' => '재질 두께',
    'K1' => '패널 수',
    'L1' => '대수',
    'M1' => '비고',
    'N1' => '등록일',
    'O1' => '수정일'
];

foreach ($headers as $cell => $value) {
    $sheet->setCellValue($cell, $value);
}
$sheet->getStyle('A1:O1')->applyFromArray($headerStyle);

// 패널 개수 계산 함수 (site_list.php와 동일)
function calculateActualPanelCount($panel_data, $transom_data, $car_structure = '일반형') {
    // 관통형이면 5,6,7번 패널이 없으므로 기본 패널 수는 6개 (2,3,4,8,9,10)
    // 일반형이면 모든 패널이 있으므로 기본 패널 수는 9개 (2,3,4,5,6,7,8,9,10)
    $panel_count = ($car_structure === '관통형') ? 6 : 9;

    // 1,11번 패널 확인 (각각 개별적으로 확인)
    if (!empty($panel_data) && $panel_data !== '{}') {
        $panel_json = json_decode($panel_data, true);
        if (is_array($panel_json)) {
            // 1번 패널 확인 (실제 측정 데이터가 있고 값이 비어있지 않은지 확인)
            if (isset($panel_json['1']) && is_array($panel_json['1'])) {
                $has_1_data = false;
                if ((isset($panel_json['1']['width']) && !empty(trim($panel_json['1']['width']))) ||
                    (isset($panel_json['1']['height']) && !empty(trim($panel_json['1']['height']))) ||
                    (isset($panel_json['1']['panelType']) && !empty(trim($panel_json['1']['panelType'])))) {
                    $has_1_data = true;
                }
                if ($has_1_data) {
                    $panel_count += 1;
                }
            }
            // 11번 패널 확인 (실제 측정 데이터가 있고 값이 비어있지 않은지 확인)
            if (isset($panel_json['11']) && is_array($panel_json['11'])) {
                $has_11_data = false;
                if ((isset($panel_json['11']['width']) && !empty(trim($panel_json['11']['width']))) ||
                    (isset($panel_json['11']['height']) && !empty(trim($panel_json['11']['height']))) ||
                    (isset($panel_json['11']['panelType']) && !empty(trim($panel_json['11']['panelType'])))) {
                    $has_11_data = true;
                }
                if ($has_11_data) {
                    $panel_count += 1;
                }
            }
        }
    }

    // 12번 transom 패널 확인 (실제 측정 데이터가 있고 값이 비어있지 않은지 확인)
    if (!empty($transom_data) && $transom_data !== '{}') {
        $transom_json = json_decode($transom_data, true);
        if (is_array($transom_json) && isset($transom_json['12'])) {
            $transom_actual_data = $transom_json['12'];
            $has_transom_data = false;
            // 주요 transom 필드 중 하나라도 값이 있으면 카운트
            if ((isset($transom_actual_data['width']) && !empty(trim($transom_actual_data['width']))) ||
                (isset($transom_actual_data['height']) && !empty(trim($transom_actual_data['height']))) ||
                (isset($transom_actual_data['transomPlateHeight']) && !empty(trim($transom_actual_data['transomPlateHeight'])))) {
                $has_transom_data = true;
            }
            if ($has_transom_data) {
                $panel_count += 1;
            }
        }
    }

    return $panel_count; 
}

// 데이터 입력
$row = 2;
foreach ($measurements as $measurement) {
    $car_structure = $measurement['car_structure'] ?? '일반형';
    $panel_count = calculateActualPanelCount($measurement['panel_data'], $measurement['transom_data'], $car_structure);

    $sheet->setCellValue('A' . $row, $measurement['id']);
    $sheet->setCellValue('B' . $row, $measurement['site_name']);
    $sheet->setCellValue('C' . $row, $measurement['measurement_date']);
    $sheet->setCellValue('D' . $row, $measurement['measurer_name']);
    $sheet->setCellValue('E' . $row, $measurement['car_inside_width'] == 0 ? '' : $measurement['car_inside_width']);
    $sheet->setCellValue('F' . $row, $measurement['car_inside_depth'] == 0 ? '' : $measurement['car_inside_depth']);
    $sheet->setCellValue('G' . $row, $measurement['car_inside_height'] == 0 ? '' : $measurement['car_inside_height']);
    $sheet->setCellValue('H' . $row, $car_structure);
    $sheet->setCellValue('I' . $row, $measurement['material_type']);
    $sheet->setCellValue('J' . $row, $measurement['material_thickness'] == 0 ? '' : $measurement['material_thickness']);
    $sheet->setCellValue('K' . $row, $panel_count == 0 ? '' : $panel_count);
    $sheet->setCellValue('L' . $row, $measurement['elevator_count'] == 0 ? '' : $measurement['elevator_count']);
    $sheet->setCellValue('M' . $row, $measurement['notes']);
    $sheet->setCellValue('N' . $row, date('Y-m-d H:i', strtotime($measurement['created_at'])));
    $sheet->setCellValue('O' . $row, date('Y-m-d H:i', strtotime($measurement['updated_at'])));

    $row++;
}

// 컬럼 폭 고정 설정 (헤더가 잘리지 않도록 넉넉히)
$columnWidths = [
    'A' => 10,  // ID
    'B' => 28,  // 현장명
    'C' => 16,  // 측정일자
    'D' => 16,  // 측정자
    'E' => 14,  // 카 내부 W
    'F' => 14,  // 카 내부 D
    'G' => 14,  // 카 내부 H
    'H' => 12,  // 카 구조
    'I' => 18,  // 의장재질
    'J' => 14,  // 재질 두께
    'K' => 12,  // 패널 수
    'L' => 10,  // 대수
    'M' => 40,  // 비고 (긴 텍스트)
    'N' => 20,  // 등록일
    'O' => 20   // 수정일
];
foreach ($columnWidths as $col => $width) {
    $sheet->getColumnDimension($col)->setAutoSize(false);
    $sheet->getColumnDimension($col)->setWidth($width);
}

// 두 번째 시트: 세부정보 (패널별 상세 데이터)
$detailSheet = $objPHPExcel->createSheet();
$detailSheet->setTitle('세부정보');

// 세부정보 헤더 설정
$detailHeaders = [
    'A1' => '번호',
    'B1' => '고유번호',
    'C1' => '현장명',
    'D1' => '측정일자',
    'E1' => '측정자',
    'F1' => '카 내부 W',
    'G1' => '카 내부 D',
    'H1' => '카 내부 H',
    'I1' => '카 구조',
    'J1' => '의장재질',
    'K1' => '재질 두께',
    'L1' => '패널 번호',
    'M1' => '패널 타입',
    'N1' => '패널 가로',
    'O1' => '패널 세로',
    'P1' => '타공 가로',
    'Q1' => '타공 세로',
    'R1' => '타공 높이(밑기준)',
    'S1' => '출입구방향에서 떨어짐',
    'T1' => '1,11전면 두께',
    'U1' => '1,11전면 날개',
    'V1' => '1,11후면 두께',
    'W1' => '1,11후면 날개',
    'X1' => 'TR 가로',
    'Y1' => 'TR 막판높이',
    'Z1' => 'TR 밑면깊이JD',
    'AA1' => 'TR 날개값',
    'AB1' => 'TR CPI타공 가로',
    'AC1' => 'TR CPI타공 세로',
    'AD1' => 'TR CPI타공높이',
    'AE1' => 'TR 비고',
    'AF1' => '패널 특이사항',
];

foreach ($detailHeaders as $cell => $value) {
    $detailSheet->setCellValue($cell, $value);
}
$detailSheet->getStyle('A1:AF1')->applyFromArray($headerStyle);

// 세부정보 데이터 입력
$detailRow = 2;
$rowNum = 1;
foreach ($measurements as $measurement) {
    // 패널 데이터 파싱
    $panelsToExport = [];
    if (!empty($measurement['panel_data'])) {
        $panel_data = json_decode($measurement['panel_data'], true);
        if ($panel_data && is_array($panel_data)) {
            $panelsToExport = $panel_data;
        }
    } 

    // Transom 데이터 파싱 (신규: 'transom', 구형: '12')
    $transomData = [];
    if (!empty($measurement['transom_data'])) {
        $transom_data = json_decode($measurement['transom_data'], true);
        if ($transom_data && is_array($transom_data)) {
            if (isset($transom_data['transom'])) {
                $transomData = $transom_data['transom'];
            } elseif (isset($transom_data['12'])) {
            $transomData = $transom_data['12'];
            }
        }
    } 

    // 패널이 있으면 패널별로 행 생성, 없으면 기본 정보만 생성
    if (!empty($panelsToExport)) {
        error_log("DEBUG: 패널 데이터 파싱 결과 - " . json_encode($panelsToExport));
        foreach ($panelsToExport as $panelNum => $panelInfo) {
            error_log("DEBUG: 패널 번호: " . $panelNum . ", 패널 정보: " . json_encode($panelInfo));
            
            // Transom(키가 '12' 또는 'transom')은 패널 루프에서 제외하고 별도 행으로 출력
            if ($panelNum === '12' || $panelNum === 12 || $panelNum === 'transom') {
                error_log("DEBUG: Transom 패널 건너뜀: " . $panelNum);
                continue;
            }
            $car_structure = $measurement['car_structure'] ?? '일반형';
            $detailSheet->setCellValue('A' . $detailRow, $rowNum);
            $detailSheet->setCellValue('B' . $detailRow, $measurement['id']);
            $detailSheet->setCellValue('C' . $detailRow, $measurement['site_name']);
            $detailSheet->setCellValue('D' . $detailRow, $measurement['measurement_date']);
            $detailSheet->setCellValue('E' . $detailRow, $measurement['measurer_name']);
            $detailSheet->setCellValue('F' . $detailRow, $measurement['car_inside_width'] == 0 ? '' : $measurement['car_inside_width']);
            $detailSheet->setCellValue('G' . $detailRow, $measurement['car_inside_depth'] == 0 ? '' : $measurement['car_inside_depth']);
            $detailSheet->setCellValue('H' . $detailRow, $measurement['car_inside_height'] == 0 ? '' : $measurement['car_inside_height']);
            $detailSheet->setCellValue('I' . $detailRow, $car_structure);
            $detailSheet->setCellValue('J' . $detailRow, $measurement['material_type']);
            $detailSheet->setCellValue('K' . $detailRow, $measurement['material_thickness'] == 0 ? '' : $measurement['material_thickness']);

            // 패널 정보 (0인 값은 공백으로 표시)
            // 패널번호: transom은 별도 행으로 표시하지 않으므로 패널 데이터에서는 1~11만
            $detailSheet->setCellValue('L' . $detailRow, $panelNum);
            $panelIndex = is_numeric($panelNum) ? (int)$panelNum : (int)preg_replace('/\D+/', '', (string)$panelNum);
            // 패널 타입 - 두 가지 필드명 모두 지원
            $panelType = $panelInfo['panelType'] ?? $panelInfo['panel_type_detail'] ?? '';
            $detailSheet->setCellValue('M' . $detailRow, ($panelIndex === 1 || $panelIndex === 11) ? $panelType : '');
            $detailSheet->setCellValue('N' . $detailRow, ($panelInfo['width'] ?? 0) == 0 ? '' : ($panelInfo['width'] ?? ''));
            $detailSheet->setCellValue('O' . $detailRow, ($panelInfo['height'] ?? 0) == 0 ? '' : ($panelInfo['height'] ?? ''));
            // Drilling fields - 두 가지 필드명 모두 지원
            $drillingWidth = $panelInfo['drilling_width'] ?? $panelInfo['drillingWidth'] ?? '';
            $drillingHeight = $panelInfo['drilling_height'] ?? $panelInfo['drillingHeight'] ?? '';
            $drillingFromFloor = $panelInfo['drilling_from_floor'] ?? $panelInfo['drillingFromFloor'] ?? '';
            $drillingFromEntrance = $panelInfo['drilling_from_entrance'] ?? $panelInfo['drillingFromEntrance'] ?? '';
            
            $detailSheet->setCellValue('P' . $detailRow, (empty($drillingWidth) || $drillingWidth == 0) ? '' : $drillingWidth);
            $detailSheet->setCellValue('Q' . $detailRow, (empty($drillingHeight) || $drillingHeight == 0) ? '' : $drillingHeight);
            $detailSheet->setCellValue('R' . $detailRow, (empty($drillingFromFloor) || $drillingFromFloor == 0) ? '' : $drillingFromFloor);
            $detailSheet->setCellValue('S' . $detailRow, (empty($drillingFromEntrance) || $drillingFromEntrance == 0) ? '' : $drillingFromEntrance);
            // Corner details for 1,11 - 두 가지 필드명 모두 지원
            $frontThickness = $panelInfo['front_thickness'] ?? $panelInfo['frontThickness'] ?? '';
            $frontWing = $panelInfo['front_wing'] ?? $panelInfo['frontWing'] ?? '';
            $backThickness = $panelInfo['back_thickness'] ?? $panelInfo['backThickness'] ?? '';
            $backWing = $panelInfo['back_wing'] ?? $panelInfo['backWing'] ?? '';
            
            $detailSheet->setCellValue('T' . $detailRow, (empty($frontThickness) || $frontThickness == 0) ? '' : $frontThickness);
            $detailSheet->setCellValue('U' . $detailRow, (empty($frontWing) || $frontWing == 0) ? '' : $frontWing);
            $detailSheet->setCellValue('V' . $detailRow, (empty($backThickness) || $backThickness == 0) ? '' : $backThickness);
            $detailSheet->setCellValue('W' . $detailRow, (empty($backWing) || $backWing == 0) ? '' : $backWing);
            // 패널번호 1~11번은 TR 관련 컬럼을 비워둠 (transom만 표시)
            // 패널 번호를 다시 한번 정확히 계산
            $currentPanelNum = is_numeric($panelNum) ? (int)$panelNum : (int)preg_replace('/\D+/', '', (string)$panelNum);
            
            error_log("DEBUG: 패널 " . $currentPanelNum . "번 처리 중");
            
            // 패널 데이터에 TR 관련 값이 있는지 확인
            $hasTrData = false;
            $trFields = ['width', 'height', 'transomPlateHeight', 'bottomDepthJD', 'wingValue', 'cpiDrillingWidth', 'cpiDrillingHeight', 'cpiDrillingHeightFromBottom', 'notes'];
            foreach ($trFields as $field) {
                if (isset($panelInfo[$field]) && !empty(trim($panelInfo[$field]))) {
                    $hasTrData = true;
                    error_log("DEBUG: 패널 " . $currentPanelNum . "번에 TR 필드 '" . $field . "' 값 발견: " . $panelInfo[$field]);
                    break;
                }
            }
            
            error_log("DEBUG: 패널 " . $currentPanelNum . "번 TR 데이터 존재 여부: " . ($hasTrData ? 'YES' : 'NO'));
            
            // 1~11번 패널은 무조건 TR 컬럼을 비워둠 (transom만 표시)
            if ($currentPanelNum >= 1 && $currentPanelNum <= 11) {
                error_log("DEBUG: 패널 " . $currentPanelNum . "번 - TR 컬럼 비우기");
                foreach (['X','Y','Z','AA','AB','AC','AD'] as $col) {
                    $detailSheet->setCellValue($col . $detailRow, '');
                }
            } else {
                // 12번 이상 패널은 기존 로직 유지
                error_log("DEBUG: 패널 " . $currentPanelNum . "번 - TR 컬럼 유지");
                foreach (['X','Y','Z','AA','AB','AC','AD'] as $col) {
                    $detailSheet->setCellValue($col . $detailRow, '');
                }
            }
            // 패널 특이사항 - 두 가지 필드명 모두 지원
            $specialNotes = $panelInfo['notes'] ?? $panelInfo['specialNotes'] ?? '';
            $detailSheet->setCellValue('AF' . $detailRow, $specialNotes);
            $detailRow++;
        }
        // 패널들 출력 후, Transom 데이터가 있으면 별도 행 추가
        error_log("DEBUG: Transom 데이터 확인 - " . json_encode($transomData));
        if (!empty($transomData)) {
            error_log("DEBUG: Transom 행 추가 중"); 
            $detailSheet->setCellValue('A' . $detailRow, $rowNum);
            $detailSheet->setCellValue('B' . $detailRow, $measurement['id']);
            $detailSheet->setCellValue('C' . $detailRow, $measurement['site_name']);
            $detailSheet->setCellValue('D' . $detailRow, $measurement['measurement_date']);
            $detailSheet->setCellValue('E' . $detailRow, $measurement['measurer_name']);
            $detailSheet->setCellValue('F' . $detailRow, $measurement['car_inside_width'] == 0 ? '' : $measurement['car_inside_width']);
            $detailSheet->setCellValue('G' . $detailRow, $measurement['car_inside_depth'] == 0 ? '' : $measurement['car_inside_depth']);
            $detailSheet->setCellValue('H' . $detailRow, $measurement['car_inside_height'] == 0 ? '' : $measurement['car_inside_height']);
            $detailSheet->setCellValue('I' . $detailRow, $car_structure);
            $detailSheet->setCellValue('J' . $detailRow, $measurement['material_type']);
            $detailSheet->setCellValue('K' . $detailRow, $measurement['material_thickness'] == 0 ? '' : $measurement['material_thickness']);
            $detailSheet->setCellValue('L' . $detailRow, 'transom');
            $detailSheet->setCellValue('M' . $detailRow, '');
            // 패널 가로/세로는 N/O에 출력
            $detailSheet->setCellValue('N' . $detailRow, ($transomData['width'] ?? 0) == 0 ? '' : ($transomData['width'] ?? ''));
            $detailSheet->setCellValue('O' . $detailRow, ($transomData['height'] ?? 0) == 0 ? '' : ($transomData['height'] ?? ''));
            // Drilling fields (Transom에는 비워둠)
            $detailSheet->setCellValue('P' . $detailRow, '');
            $detailSheet->setCellValue('Q' . $detailRow, '');
            $detailSheet->setCellValue('R' . $detailRow, '');
            $detailSheet->setCellValue('S' . $detailRow, '');
            // Corner details (Transom에는 비워둠)
            $detailSheet->setCellValue('T' . $detailRow, '');
            $detailSheet->setCellValue('U' . $detailRow, '');
            $detailSheet->setCellValue('V' . $detailRow, '');
            $detailSheet->setCellValue('W' . $detailRow, '');
            // TR 블록: 헤더(X~AE)에 맞춰 정확히 매핑
            $detailSheet->setCellValue('X' . $detailRow, ($transomData['width'] ?? 0) == 0 ? '' : ($transomData['width'] ?? ''));
            $detailSheet->setCellValue('Y' . $detailRow, ($transomData['transomPlateHeight'] ?? 0) == 0 ? '' : ($transomData['transomPlateHeight'] ?? ''));
            $detailSheet->setCellValue('Z' . $detailRow, ($transomData['bottomDepthJD'] ?? 0) == 0 ? '' : ($transomData['bottomDepthJD'] ?? ''));
            $detailSheet->setCellValue('AA' . $detailRow, ($transomData['wingValue'] ?? 0) == 0 ? '' : ($transomData['wingValue'] ?? ''));
            $detailSheet->setCellValue('AB' . $detailRow, ($transomData['cpiDrillingWidth'] ?? 0) == 0 ? '' : ($transomData['cpiDrillingWidth'] ?? ''));
            $detailSheet->setCellValue('AC' . $detailRow, ($transomData['cpiDrillingHeight'] ?? 0) == 0 ? '' : ($transomData['cpiDrillingHeight'] ?? ''));
            $detailSheet->setCellValue('AD' . $detailRow, ($transomData['cpiDrillingHeightFromBottom'] ?? 0) == 0 ? '' : ($transomData['cpiDrillingHeightFromBottom'] ?? ''));
            $detailSheet->setCellValue('AE' . $detailRow, $transomData['notes'] ?? '');
            $detailSheet->setCellValue('AF' . $detailRow, '');
            $detailRow++;
        }
    } else {
        // 패널 데이터가 없는 경우 기본 정보만 표시
        $car_structure = $measurement['car_structure'] ?? '일반형';
        $detailSheet->setCellValue('A' . $detailRow, $rowNum);
        $detailSheet->setCellValue('B' . $detailRow, $measurement['id']);
        $detailSheet->setCellValue('C' . $detailRow, $measurement['site_name']);
        $detailSheet->setCellValue('D' . $detailRow, $measurement['measurement_date']);
        $detailSheet->setCellValue('E' . $detailRow, $measurement['measurer_name']);
        $detailSheet->setCellValue('F' . $detailRow, $measurement['car_inside_width'] == 0 ? '' : $measurement['car_inside_width']);
        $detailSheet->setCellValue('G' . $detailRow, $measurement['car_inside_depth'] == 0 ? '' : $measurement['car_inside_depth']);
        $detailSheet->setCellValue('H' . $detailRow, $measurement['car_inside_height'] == 0 ? '' : $measurement['car_inside_height']);
        $detailSheet->setCellValue('I' . $detailRow, $car_structure);
        $detailSheet->setCellValue('J' . $detailRow, $measurement['material_type']);
        $detailSheet->setCellValue('K' . $detailRow, $measurement['material_thickness'] == 0 ? '' : $measurement['material_thickness']);
        $detailSheet->setCellValue('L' . $detailRow, '-');

        // Transom 정보는 별도 행으로 출력
        if (!empty($transomData)) {
            $detailSheet->setCellValue('A' . $detailRow, $rowNum);
            $detailSheet->setCellValue('B' . $detailRow, $measurement['id']);
            $detailSheet->setCellValue('C' . $detailRow, $measurement['site_name']);
            $detailSheet->setCellValue('D' . $detailRow, $measurement['measurement_date']);
            $detailSheet->setCellValue('E' . $detailRow, $measurement['measurer_name']);
            $detailSheet->setCellValue('F' . $detailRow, $measurement['car_inside_width'] == 0 ? '' : $measurement['car_inside_width']);
            $detailSheet->setCellValue('G' . $detailRow, $measurement['car_inside_depth'] == 0 ? '' : $measurement['car_inside_depth']);
            $detailSheet->setCellValue('H' . $detailRow, $measurement['car_inside_height'] == 0 ? '' : $measurement['car_inside_height']);
            $detailSheet->setCellValue('I' . $detailRow, $car_structure);
            $detailSheet->setCellValue('J' . $detailRow, $measurement['material_type']);
            $detailSheet->setCellValue('K' . $detailRow, $measurement['material_thickness'] == 0 ? '' : $measurement['material_thickness']);
            $detailSheet->setCellValue('L' . $detailRow, 'transom');
            $detailSheet->setCellValue('M' . $detailRow, '');
            $detailSheet->setCellValue('N' . $detailRow, ($transomData['width'] ?? 0) == 0 ? '' : ($transomData['width'] ?? ''));
            $detailSheet->setCellValue('O' . $detailRow, ($transomData['height'] ?? 0) == 0 ? '' : ($transomData['height'] ?? ''));
            // Drilling fields (transom에는 비워둠)
            $detailSheet->setCellValue('P' . $detailRow, '');
            $detailSheet->setCellValue('Q' . $detailRow, '');
            $detailSheet->setCellValue('R' . $detailRow, '');
            $detailSheet->setCellValue('S' . $detailRow, '');
            // Corner details (transom에는 비워둠)
            $detailSheet->setCellValue('T' . $detailRow, '');
            $detailSheet->setCellValue('U' . $detailRow, '');
            $detailSheet->setCellValue('V' . $detailRow, '');
            $detailSheet->setCellValue('W' . $detailRow, '');
            // TR 블록: 헤더(X~AE)에 맞춰 정확히 매핑
            $detailSheet->setCellValue('X' . $detailRow, ($transomData['width'] ?? 0) == 0 ? '' : ($transomData['width'] ?? ''));
            $detailSheet->setCellValue('Y' . $detailRow, ($transomData['transomPlateHeight'] ?? 0) == 0 ? '' : ($transomData['transomPlateHeight'] ?? ''));
            $detailSheet->setCellValue('Z' . $detailRow, ($transomData['bottomDepthJD'] ?? 0) == 0 ? '' : ($transomData['bottomDepthJD'] ?? ''));
            $detailSheet->setCellValue('AA' . $detailRow, ($transomData['wingValue'] ?? 0) == 0 ? '' : ($transomData['wingValue'] ?? ''));
            $detailSheet->setCellValue('AB' . $detailRow, ($transomData['cpiDrillingWidth'] ?? 0) == 0 ? '' : ($transomData['cpiDrillingWidth'] ?? ''));
            $detailSheet->setCellValue('AC' . $detailRow, ($transomData['cpiDrillingHeight'] ?? 0) == 0 ? '' : ($transomData['cpiDrillingHeight'] ?? ''));
            $detailSheet->setCellValue('AD' . $detailRow, ($transomData['cpiDrillingHeightFromBottom'] ?? 0) == 0 ? '' : ($transomData['cpiDrillingHeightFromBottom'] ?? ''));
            $detailSheet->setCellValue('AE' . $detailRow, $transomData['notes'] ?? '');
            $detailSheet->setCellValue('AF' . $detailRow, '');
            $detailRow++;
        }

        $detailRow++;
    }

    $rowNum++;
}

// 세부정보 시트 컬럼 폭 고정 설정 (A-Z, AA-AE)
$defaultDetailWidth = 14;
foreach (range('A','Z') as $columnID) {
    $detailSheet->getColumnDimension($columnID)->setAutoSize(false);
    $detailSheet->getColumnDimension($columnID)->setWidth($defaultDetailWidth);
}
// AA-AF 컬럼도 설정
foreach (['AA', 'AB', 'AC', 'AD', 'AE', 'AF'] as $columnID) {
    $detailSheet->getColumnDimension($columnID)->setAutoSize(false);
    $detailSheet->getColumnDimension($columnID)->setWidth($defaultDetailWidth);
}
// 긴 텍스트/헤더 열은 더 넓게 지정
$detailWideColumns = [
    'A' => 5,  // 번호
    'B' => 8,  // 문서 고유번호
    'C' => 30,  // 현장명
    'D' => 12,  // 측정일자
    'E' => 12,  // 측정자
    'I' => 12,  // 카 구조
    'L' => 12,  // 패널 번호 
    'M' => 15,  // 패널 타입
    'P' => 14,  // 타공 가로
    'Q' => 14,  // 타공 세로
    'R' => 18,  // 타공 높이(밑기준)
    'S' => 18,  // 출입구방향에서 떨어짐
    'AB' => 18, // Transom CPI타공 가로
    'AC' => 18, // Transom CPI타공 세로
    'AD' => 18, // Transom CPI타공높이
    'AE' => 20, // TR 비고
    'AF' => 30  // 패널 특이사항
];
foreach ($detailWideColumns as $col => $width) {
    $detailSheet->getColumnDimension($col)->setWidth($width);
}

// 파일명 생성 (브라우저 호환성 강화)
$filename = 'Measurement_Data_' . date('Y-m-d_H-i-s') . '.xlsx';

// 강화된 디버깅: 파일명과 헤더 정보
error_log("=== 엑셀 내보내기 디버깅 시작 ===");
error_log("생성된 파일명: " . $filename);
error_log("현재 출력 버퍼 레벨: " . ob_get_level());
error_log("현재 출력 버퍼 내용 길이: " . strlen(ob_get_contents()));
error_log("현재 시간: " . date('Y-m-d H:i:s'));
error_log("PHP 버전: " . phpversion());
error_log("서버 소프트웨어: " . $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown');

// 모든 출력 버퍼 강제 정리
while (ob_get_level()) {
    ob_end_clean();
}
error_log("모든 출력 버퍼 정리 완료");
 
// Excel 파일 생성 및 다운로드
// 디버깅: Writer 생성 및 저장
error_log("PHPExcel Writer 생성 시도...");
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
error_log("PHPExcel Writer 생성 성공!");

// 헤더 전송 전 완전한 출력 버퍼 정리 (로컬 환경 대응)
while (ob_get_level()) {
    ob_end_clean();
}

error_log("=== 헤더 전송 시작 ===");
error_log("헤더 전송 전 출력 버퍼 레벨: " . ob_get_level());

// 파일 크기 먼저 계산
$temp_file = tempnam(sys_get_temp_dir(), 'excel_debug_');
$objWriter->save($temp_file);
$file_size = filesize($temp_file);
error_log("생성된 Excel 파일 크기: " . $file_size . " bytes");
error_log("임시 파일 경로: " . $temp_file);

// 파일 크기가 유효한지 확인
if ($file_size === false || $file_size < 1000) {
    error_log("경고: 파일 크기가 너무 작습니다. " . $file_size);
    $file_size = 10240; // 기본값 설정
}

// 브라우저 호환성을 위한 최종 헤더 설정
header('HTTP/1.1 200 OK');
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . $file_size);
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Accept-Ranges: bytes');
header('Connection: close');
header('X-Content-Type-Options: nosniff');
header('X-Download-Options: noopen');
error_log("최종 헤더 전송 완료: filename=" . $filename);

// 로컬 환경에서 추가 안전장치
if (ob_get_level()) {
    ob_end_clean();
}

error_log("=== Excel 파일 출력 시작 ===");
error_log("출력 전 최종 버퍼 상태 - 레벨: " . ob_get_level());

// 로컬 환경에서 추가 안전장치
if (ob_get_level()) {
    ob_end_clean();
    error_log("최종 출력 전 버퍼 정리 완료");
}

// 임시 파일이 이미 생성되었으므로 재사용

// 직접 파일 읽기 및 출력 (브라우저 호환성 강화)
error_log("임시 파일에서 직접 읽기 시작...");
$file_content = file_get_contents($temp_file);
$actual_size = strlen($file_content);
error_log("실제 읽은 파일 크기: " . $actual_size . " bytes");

// 바이너리 모드로 출력
if (ob_get_level()) {
    ob_end_clean();
}

// 직접 파일 내용 출력
print($file_content);
error_log("파일 내용 직접 출력 완료!");

// 임시 파일 삭제
unlink($temp_file);
error_log("임시 파일 삭제 완료");

error_log("=== Excel 파일 출력 완료 ===");
exit; 