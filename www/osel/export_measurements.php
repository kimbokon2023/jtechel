<?php
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
require_once '../PHPExcel_1.8.0/Classes/PHPExcel.php';

$objPHPExcel = new PHPExcel();
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
    'H1' => '의장재질',
    'I1' => '재질 두께',
    'J1' => '패널 수',
    'K1' => '대수',
    'L1' => '비고',
    'M1' => '등록일',
    'N1' => '수정일'
];

foreach ($headers as $cell => $value) {
    $sheet->setCellValue($cell, $value);
}
$sheet->getStyle('A1:N1')->applyFromArray($headerStyle);

// 패널 개수 계산 함수 (site_list.php와 동일)
function calculateActualPanelCount($panel_data, $transom_data) {
    $panel_count = 9; // 기본 2-10번 패널

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
    $panel_count = calculateActualPanelCount($measurement['panel_data'], $measurement['transom_data']);

    $sheet->setCellValue('A' . $row, $measurement['id']);
    $sheet->setCellValue('B' . $row, $measurement['site_name']);
    $sheet->setCellValue('C' . $row, $measurement['measurement_date']);
    $sheet->setCellValue('D' . $row, $measurement['measurer_name']);
    $sheet->setCellValue('E' . $row, $measurement['car_inside_width'] == 0 ? '' : $measurement['car_inside_width']);
    $sheet->setCellValue('F' . $row, $measurement['car_inside_depth'] == 0 ? '' : $measurement['car_inside_depth']);
    $sheet->setCellValue('G' . $row, $measurement['car_inside_height'] == 0 ? '' : $measurement['car_inside_height']);
    $sheet->setCellValue('H' . $row, $measurement['material_type']);
    $sheet->setCellValue('I' . $row, $measurement['material_thickness'] == 0 ? '' : $measurement['material_thickness']);
    $sheet->setCellValue('J' . $row, $panel_count == 0 ? '' : $panel_count);
    $sheet->setCellValue('K' . $row, $measurement['elevator_count'] == 0 ? '' : $measurement['elevator_count']);
    $sheet->setCellValue('L' . $row, $measurement['notes']);
    $sheet->setCellValue('M' . $row, date('Y-m-d H:i', strtotime($measurement['created_at'])));
    $sheet->setCellValue('N' . $row, date('Y-m-d H:i', strtotime($measurement['updated_at'])));

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
    'H' => 18,  // 의장재질
    'I' => 14,  // 재질 두께
    'J' => 12,  // 패널 수
    'K' => 10,  // 대수
    'L' => 40,  // 비고 (긴 텍스트)
    'M' => 20,  // 등록일
    'N' => 20   // 수정일
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
    'I1' => '의장재질',
    'J1' => '재질 두께',
    'K1' => '패널 번호',
    'L1' => '패널 타입',
    'M1' => '패널 가로',
    'N1' => '패널 세로',
    'O1' => '타공 가로',
    'P1' => '타공 세로',
    'Q1' => '타공 높이(밑기준)',
    'R1' => '출입구방향에서 떨어짐',
    'S1' => '1,11전면 두께',
    'T1' => '1,11전면 날개',
    'U1' => '1,11후면 두께',
    'V1' => '1,11후면 날개',
    'W1' => 'TR 가로',
    'X1' => 'TR 세로',
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
            $detailSheet->setCellValue('A' . $detailRow, $rowNum);
            $detailSheet->setCellValue('B' . $detailRow, $measurement['id']);
            $detailSheet->setCellValue('C' . $detailRow, $measurement['site_name']);
            $detailSheet->setCellValue('D' . $detailRow, $measurement['measurement_date']);
            $detailSheet->setCellValue('E' . $detailRow, $measurement['measurer_name']);
            $detailSheet->setCellValue('F' . $detailRow, $measurement['car_inside_width'] == 0 ? '' : $measurement['car_inside_width']);
            $detailSheet->setCellValue('G' . $detailRow, $measurement['car_inside_depth'] == 0 ? '' : $measurement['car_inside_depth']);
            $detailSheet->setCellValue('H' . $detailRow, $measurement['car_inside_height'] == 0 ? '' : $measurement['car_inside_height']);
            $detailSheet->setCellValue('I' . $detailRow, $measurement['material_type']);
            $detailSheet->setCellValue('J' . $detailRow, $measurement['material_thickness'] == 0 ? '' : $measurement['material_thickness']);

            // 패널 정보 (0인 값은 공백으로 표시)
            // 패널번호: transom은 별도 행으로 표시하지 않으므로 패널 데이터에서는 1~11만
            $detailSheet->setCellValue('K' . $detailRow, $panelNum);
            $panelIndex = is_numeric($panelNum) ? (int)$panelNum : (int)preg_replace('/\D+/', '', (string)$panelNum);
            $detailSheet->setCellValue('L' . $detailRow, ($panelIndex === 1 || $panelIndex === 11) ? ($panelInfo['panel_type_detail'] ?? '') : '');
            $detailSheet->setCellValue('M' . $detailRow, ($panelInfo['width'] ?? 0) == 0 ? '' : ($panelInfo['width'] ?? ''));
            $detailSheet->setCellValue('N' . $detailRow, ($panelInfo['height'] ?? 0) == 0 ? '' : ($panelInfo['height'] ?? ''));
            // Drilling fields
            $detailSheet->setCellValue('O' . $detailRow, (($panelInfo['drillingWidth'] ?? '') === '' || ($panelInfo['drillingWidth'] ?? 0) == 0) ? '' : ($panelInfo['drillingWidth'] ?? ''));
            $detailSheet->setCellValue('P' . $detailRow, (($panelInfo['drillingHeight'] ?? '') === '' || ($panelInfo['drillingHeight'] ?? 0) == 0) ? '' : ($panelInfo['drillingHeight'] ?? ''));
            $detailSheet->setCellValue('Q' . $detailRow, (($panelInfo['drillingFromFloor'] ?? '') === '' || ($panelInfo['drillingFromFloor'] ?? 0) == 0) ? '' : ($panelInfo['drillingFromFloor'] ?? ''));
            $detailSheet->setCellValue('R' . $detailRow, (($panelInfo['drillingFromEntrance'] ?? '') === '' || ($panelInfo['drillingFromEntrance'] ?? 0) == 0) ? '' : ($panelInfo['drillingFromEntrance'] ?? ''));
            // Corner details for 1,11
            $detailSheet->setCellValue('S' . $detailRow, ($panelInfo['frontThickness'] ?? 0) == 0 ? '' : ($panelInfo['frontThickness'] ?? ''));
            $detailSheet->setCellValue('T' . $detailRow, ($panelInfo['frontWing'] ?? 0) == 0 ? '' : ($panelInfo['frontWing'] ?? ''));
            $detailSheet->setCellValue('U' . $detailRow, ($panelInfo['backThickness'] ?? 0) == 0 ? '' : ($panelInfo['backThickness'] ?? ''));
            $detailSheet->setCellValue('V' . $detailRow, ($panelInfo['backWing'] ?? 0) == 0 ? '' : ($panelInfo['backWing'] ?? ''));
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
                foreach (['W','X','Y','Z','AA','AB','AC','AD','AE'] as $col) {
                    $detailSheet->setCellValue($col . $detailRow, '');
                }
            } else {
                // 12번 이상 패널은 기존 로직 유지
                error_log("DEBUG: 패널 " . $currentPanelNum . "번 - TR 컬럼 유지");
                foreach (['W','X','Y','Z','AA','AB','AC','AD','AE'] as $col) {
                    $detailSheet->setCellValue($col . $detailRow, '');
                }
            }
            $detailSheet->setCellValue('AF' . $detailRow, $panelInfo['specialNotes'] ?? '');
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
            $detailSheet->setCellValue('I' . $detailRow, $measurement['material_type']);
            $detailSheet->setCellValue('J' . $detailRow, $measurement['material_thickness'] == 0 ? '' : $measurement['material_thickness']);
            $detailSheet->setCellValue('K' . $detailRow, 'transom');
            $detailSheet->setCellValue('L' . $detailRow, '');
            // 패널 가로/세로는 M/N에 출력
            $detailSheet->setCellValue('M' . $detailRow, ($transomData['width'] ?? 0) == 0 ? '' : ($transomData['width'] ?? ''));
            $detailSheet->setCellValue('N' . $detailRow, ($transomData['height'] ?? 0) == 0 ? '' : ($transomData['height'] ?? ''));
            // Drilling fields (Transom에는 비워둠)
            $detailSheet->setCellValue('O' . $detailRow, '');
            $detailSheet->setCellValue('P' . $detailRow, '');
            $detailSheet->setCellValue('Q' . $detailRow, '');
            $detailSheet->setCellValue('R' . $detailRow, '');
            // Corner details (Transom에는 비워둠)
            $detailSheet->setCellValue('S' . $detailRow, '');
            $detailSheet->setCellValue('T' . $detailRow, '');
            $detailSheet->setCellValue('U' . $detailRow, '');
            $detailSheet->setCellValue('V' . $detailRow, '');
            // TR 블록: 헤더(W~AE)에 맞춰 정확히 매핑
            $detailSheet->setCellValue('W' . $detailRow, ($transomData['width'] ?? 0) == 0 ? '' : ($transomData['width'] ?? ''));
            $detailSheet->setCellValue('X' . $detailRow, ($transomData['height'] ?? 0) == 0 ? '' : ($transomData['height'] ?? ''));
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
        $detailSheet->setCellValue('A' . $detailRow, $rowNum);
        $detailSheet->setCellValue('B' . $detailRow, $measurement['id']);
        $detailSheet->setCellValue('C' . $detailRow, $measurement['site_name']);
        $detailSheet->setCellValue('D' . $detailRow, $measurement['measurement_date']);
        $detailSheet->setCellValue('E' . $detailRow, $measurement['measurer_name']);
        $detailSheet->setCellValue('F' . $detailRow, $measurement['car_inside_width'] == 0 ? '' : $measurement['car_inside_width']);
        $detailSheet->setCellValue('G' . $detailRow, $measurement['car_inside_depth'] == 0 ? '' : $measurement['car_inside_depth']);
        $detailSheet->setCellValue('H' . $detailRow, $measurement['car_inside_height'] == 0 ? '' : $measurement['car_inside_height']);
        $detailSheet->setCellValue('I' . $detailRow, $measurement['material_type']);
        $detailSheet->setCellValue('J' . $detailRow, $measurement['material_thickness'] == 0 ? '' : $measurement['material_thickness']);
        $detailSheet->setCellValue('K' . $detailRow, '-');

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
            $detailSheet->setCellValue('I' . $detailRow, $measurement['material_type']);
            $detailSheet->setCellValue('J' . $detailRow, $measurement['material_thickness'] == 0 ? '' : $measurement['material_thickness']);
            $detailSheet->setCellValue('K' . $detailRow, 'transom');
            $detailSheet->setCellValue('L' . $detailRow, '');
            $detailSheet->setCellValue('M' . $detailRow, ($transomData['width'] ?? 0) == 0 ? '' : ($transomData['width'] ?? ''));
            $detailSheet->setCellValue('N' . $detailRow, ($transomData['height'] ?? 0) == 0 ? '' : ($transomData['height'] ?? ''));
            // Drilling fields (transom에는 비워둠)
            $detailSheet->setCellValue('O' . $detailRow, '');
            $detailSheet->setCellValue('P' . $detailRow, '');
            $detailSheet->setCellValue('Q' . $detailRow, '');
            $detailSheet->setCellValue('R' . $detailRow, '');
            // Corner details (transom에는 비워둠)
            $detailSheet->setCellValue('S' . $detailRow, '');
            $detailSheet->setCellValue('T' . $detailRow, '');
            $detailSheet->setCellValue('U' . $detailRow, '');
            $detailSheet->setCellValue('V' . $detailRow, '');
            // TR 블록: 헤더(W~AE)에 맞춰 정확히 매핑 (패널 데이터가 없는 경우)
            $detailSheet->setCellValue('W' . $detailRow, ($transomData['width'] ?? 0) == 0 ? '' : ($transomData['width'] ?? ''));
            $detailSheet->setCellValue('X' . $detailRow, ($transomData['height'] ?? 0) == 0 ? '' : ($transomData['height'] ?? ''));
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
    'K' => 12,  // 패널 번호
    'L' => 15,  // 패널 타입
    'O' => 14,  // 타공 가로
    'P' => 14,  // 타공 세로
    'Q' => 18,  // 타공 높이(밑기준)
    'R' => 18,  // 출입구방향에서 떨어짐
    'AB' => 18, // Transom CPI타공 가로
    'AC' => 18, // Transom CPI타공 세로
    'AD' => 18, // Transom CPI타공높이
    'AE' => 20, // Transom 비고
    'AF' => 30  // 패널 특이사항
];
foreach ($detailWideColumns as $col => $width) {
    $detailSheet->getColumnDimension($col)->setWidth($width);
}

// 파일명 생성
$filename = 'panel_measurements_' . date('Y-m-d_H-i-s') . '.xlsx';

// Excel 파일 생성 및 다운로드
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

// 헤더 설정
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Cache-Control: max-age=1');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: cache, must-revalidate');
header('Pragma: public');

$objWriter->save('php://output'); 