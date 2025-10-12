<?php
// 로컬 환경에서 출력 버퍼 문제 해결을 위한 강력한 초기화
while (ob_get_level()) {
    ob_end_clean();
}

// 환경별 PHPExcel 라이브러리 경로 설정
if (file_exists('../config/environment.php')) {
    require_once '../config/environment.php';
    $phpexcel_path = isLocalEnvironment() ? '../PHPExcel_1.8.0/Classes/PHPExcel.php' : '../PHPExcel_1.8.0/Classes/PHPExcel.php';
} else {
    $phpexcel_path = '../PHPExcel_1.8.0/Classes/PHPExcel.php';
}

// PHPExcel 라이브러리 로드
if (file_exists($phpexcel_path)) {
    require_once $phpexcel_path;
} else {
    error_log("PHPExcel 라이브러리를 찾을 수 없습니다: " . $phpexcel_path);
    die('PHPExcel 라이브러리를 찾을 수 없습니다.');
}

require_once '../lib/mydb.php';
require_once 'generate_make_panel_data.php';
session_start();
$DB = 'jtechel';

// Check authentication
if (!isset($_SESSION["level"]) || $_SESSION["level"] > 8) {
    header("Location: ../login/login_form.php");
    exit;
}

// Get measurements data from POST
$measurements_json = $_POST['measurements'] ?? '';
$group_name = trim($_POST['group_name'] ?? '그룹');

// 그룹명이 비어있거나 공백만 있는 경우 기본값 설정
if (empty($group_name)) {
    $group_name = '그룹';
}

// 디버깅: POST 데이터 확인
error_log("=== export_group_production_data.php POST 데이터 확인 ===");
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
error_log("POST 데이터 개수: " . count($_POST));
error_log("POST 키들: " . implode(', ', array_keys($_POST)));

foreach ($_POST as $key => $value) {
    if ($key === 'measurements') {
        error_log("POST[$key] 길이: " . strlen($value) . " bytes");
        error_log("POST[$key] 샘플: " . substr($value, 0, 200) . "...");
    } else {
        error_log("POST[$key]: " . $value);
    }
}

error_log("그룹명 최종값: " . $group_name);
error_log("측정 데이터 길이: " . strlen($measurements_json));

if (empty($measurements_json)) {
    die('측정 데이터가 없습니다.');
}

$measurements = json_decode($measurements_json, true);
if (!$measurements || !is_array($measurements)) {
    die('유효하지 않은 측정 데이터입니다.');
}

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

try {
    // Create new PHPExcel object
    $objPHPExcel = new PHPExcel();

    // Set document properties
    $objPHPExcel->getProperties()
        ->setCreator("J-TECH Elevator")
        ->setLastModifiedBy($_SESSION['name'] ?? 'User')
        ->setTitle("그룹 제작산출 결과 - " . $group_name)
        ->setSubject("Group Panel Measurement Production Results")
        ->setDescription("Group panel measurement production results exported from J-TECH system")
        ->setKeywords("group panel measurement production results excel export")
        ->setCategory("Production Data");

    // === 첫 번째 시트: 현장기초정보 ===
    $sheet = $objPHPExcel->getActiveSheet();
    $sheet->setTitle('현장기초정보');

    // 헤더 설정 (export_production_results.php와 동일)
    $headers = [
        'A1' => 'ID',
        'B1' => '현장명',
        'C1' => '측정일자',
        'D1' => '측정자',
        'E1' => '카 내부 가로(mm)',
        'F1' => '카 내부 깊이(mm)',
        'G1' => '카 내부 높이(mm)',
        'H1' => '카 구조',
        'I1' => '의장재질',
        'J1' => '아이파크 체크',
        'K1' => '재질두께(mm)',
        // 제작 조건 설정 컬럼 추가
        'L1' => '프로젝트 타입',
        'M1' => '1,11번 제외',
        'N1' => '트랜섬 제외',
        'O1' => '몰딩 포함',
        'P1' => '엘리베이터 대수',
        'Q1' => '제작 높이(mm)',
        'R1' => '1,11번 높이(mm)',
        // 패널 정보
        'S1' => '총 패널 수',
        'T1' => '특이사항'
    ];

    // 헤더 작성
    foreach ($headers as $cell => $value) {
        $sheet->setCellValue($cell, $value);
    }

    // 헤더 스타일 설정
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF']
        ],
        'fill' => [
            'type' => PHPExcel_Style_Fill::FILL_SOLID,
            'color' => ['rgb' => '4472C4']
        ],
        'alignment' => [
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
        ],
        'borders' => [
            'allborders' => [
                'style' => PHPExcel_Style_Border::BORDER_THIN
            ]
        ]
    ];

    $sheet->getStyle('A1:T1')->applyFromArray($headerStyle);

    // 데이터 행 작성
    $row = 2;
    
    foreach ($measurements as $measurement) {
        // Get panel data and production settings
        $panel_data = [];
        if (!empty($measurement['panel_data'])) {
            $panel_data = json_decode($measurement['panel_data'], true) ?: [];
        }

        $production_settings = [
            'production_height' => $measurement['production_height'] ?? $measurement['car_inside_height'],
            'production_height1_11' => $measurement['production_height1_11'] ?? $measurement['car_inside_height'],
            'panel_corners_excluded' => $measurement['panel_corners_excluded'] ?? 1,
            'transom_excluded' => $measurement['transom_excluded'] ?? 0,
            'molding_included' => $measurement['molding_included'] ?? 0
        ];

        // elevator_count 처리 (NULL인 경우 기본값 1 사용)
        $raw_elevator_count = $measurement['elevator_count'] ?? null;
        $elevator_count = is_null($raw_elevator_count) ? 1 : intval($raw_elevator_count);

        // 제작 조건 설정 값들 처리
        $project_type = $measurement['project_type'] ?? '신규';
        $panel_corners_excluded_display = isset($measurement['panel_corners_excluded']) ? ($measurement['panel_corners_excluded'] ? 'Y' : 'N') : 'Y';
        $transom_excluded = isset($measurement['transom_excluded']) ? ($measurement['transom_excluded'] ? 'Y' : 'N') : 'N';
        $molding_included_display = isset($measurement['molding_included']) ? ($measurement['molding_included'] ? 'Y' : 'N') : 'N';
        $production_height = $measurement['production_height'] ?? $measurement['car_inside_height'];
        $production_height1_11 = $measurement['production_height1_11'] ?? $measurement['car_inside_height'];

        // 아이파크 체크 여부 확인
        $ipark_check = 'N';
        if (isset($measurement['site_name']) && strpos($measurement['site_name'], '아이파크') !== false) {
            $ipark_check = 'Y';
        }

        // 총 패널 수 계산 (result.php의 calculateActualPanelCount 함수 참조)
        $total_panels = count($panel_data);
        $car_structure = $measurement['car_structure'] ?? '일반형';
        
        if ($car_structure === '관통형') {
            // 관통형: 5,6,7번 패널 제외
            $total_panels = 6; // 1,2,3,4,8,9,10,11번 (transom 제외)
        } else {
            // 일반형: 기본 패널 수
            $total_panels = count($panel_data);
        }

        // transom이 있는 경우 +1
        if (isset($measurement['transom_data']) && !empty($measurement['transom_data'])) {
            $transom_data = json_decode($measurement['transom_data'], true);
            if ($transom_data && !empty($transom_data)) {
                $total_panels += 1;
            }
        }

        $data = [
            'A' => $measurement['id'],
            'B' => $measurement['site_name'],
            'C' => date('Y-m-d', strtotime($measurement['measurement_date'])),
            'D' => $measurement['measurer_name'],
            'E' => $measurement['car_inside_width'],
            'F' => $measurement['car_inside_depth'],
            'G' => $measurement['car_inside_height'],
            'H' => $car_structure,
            'I' => $measurement['material_type'] ?? '',
            'J' => $ipark_check,
            'K' => $measurement['material_thickness'] ?? '',
            // 제작 조건 설정
            'L' => $project_type,
            'M' => $panel_corners_excluded_display,
            'N' => $transom_excluded,
            'O' => $molding_included_display,
            'P' => $elevator_count,
            'Q' => $production_height,
            'R' => $production_height1_11,
            // 패널 정보
            'S' => $total_panels,
            'T' => $measurement['notes'] ?? ''
        ];

        foreach ($data as $col => $value) {
            $sheet->setCellValue($col . $row, $value);
        }

        $row++;
    }

    // 데이터 스타일 설정
    $dataStyle = [
        'borders' => [
            'allborders' => [
                'style' => PHPExcel_Style_Border::BORDER_THIN
            ]
        ],
        'alignment' => [
            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
        ]
    ];

    $sheet->getStyle('A2:T' . ($row - 1))->applyFromArray($dataStyle);

    // 컬럼 너비 설정 (export_production_results.php와 동일)
    $sheet->getColumnDimension('B')->setWidth(40); // 현장명
    $sheet->getColumnDimension('D')->setWidth(12); // 측정자
    $sheet->getColumnDimension('S')->setWidth(40); // 특이사항
    $sheet->getColumnDimension('E')->setWidth(24); // 카 내부 가로
    $sheet->getColumnDimension('F')->setWidth(24); // 카 내부 깊이
    $sheet->getColumnDimension('G')->setWidth(24); // 카 내부 높이
    $wideCols = ['H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R'];
    foreach ($wideCols as $col) {
        $sheet->getColumnDimension($col)->setWidth(20);
    }
    $autoSizeCols = ['A', 'C'];
    foreach ($autoSizeCols as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // === 두 번째 시트: 제작산출결과 (패널별 상세) ===
    $productionSheet = $objPHPExcel->createSheet();
    $productionSheet->setTitle('제작산출결과');

    // 제작산출결과 시트 헤더 (export_production_results.php와 동일)
    $productionHeaders = [
        'A1' => '번호',
        'B1' => '고유번호',
        'C1' => '현장명',
        'D1' => '측정일자',
        'E1' => '측정자',
        'F1' => '카 내부 W',
        'G1' => '카 내부 D',
        'H1' => '카 내부 H',
        'I1' => '카 구조',
        'J1' => '아이파크 체크',
        'K1' => '의장재질',
        'L1' => '재질 두께',
        'M1' => '패널 번호',
        'N1' => '제작 대수',
        'O1' => '패널 타입',
        'P1' => '제작폭',
        'Q1' => '제작높이',
        'R1' => '타공 가로',
        'S1' => '타공 세로',
        'T1' => '타공 높이(밑기준)',
        'U1' => '출입구방향에서 떨어짐',
        'V1' => '1,11전면 두께',
        'W1' => '1,11전면 날개',
        'X1' => '1,11후면 두께',
        'Y1' => '1,11후면 날개',
        'Z1' => 'TR 가로',
        'AA1' => 'TR 막판높이',
        'AB1' => 'TR 밑면깊이JD',
        'AC1' => 'TR 날개값',
        'AD1' => 'TR CPI타공 가로',
        'AE1' => 'TR CPI타공 세로',
        'AF1' => 'TR CPI타공높이',
        'AG1' => 'TR 비고',
        'AH1' => '패널 특이사항'
    ];

    foreach ($productionHeaders as $cell => $value) {
        $productionSheet->setCellValue($cell, $value);
    }

    // 제작산출결과 헤더 스타일
    $productionSheet->getStyle('A1:AH1')->applyFromArray($headerStyle);

    // 패널 데이터 작성
    $productionRow = 2;
    $rowNum = 1;

    foreach ($measurements as $measurement) {
        // Get panel data
        $panel_data = [];
        if (!empty($measurement['panel_data'])) {
            $panel_data = json_decode($measurement['panel_data'], true) ?: [];
        }

        // production settings
        $production_settings = [
            'production_height' => $measurement['production_height'] ?? $measurement['car_inside_height'],
            'production_height1_11' => $measurement['production_height1_11'] ?? $measurement['car_inside_height'],
            'panel_corners_excluded' => $measurement['panel_corners_excluded'] ?? 1,
            'transom_excluded' => $measurement['transom_excluded'] ?? 0,
            'molding_included' => $measurement['molding_included'] ?? 0
        ];

        // elevator_count 처리
        $raw_elevator_count = $measurement['elevator_count'] ?? null;
        $elevator_count = is_null($raw_elevator_count) ? 1 : intval($raw_elevator_count);

        // Get production panel data
        $make_panel_data = generateMakePanelData($panel_data, $production_settings);
        $display_panel_data = !empty($make_panel_data) ? $make_panel_data : $panel_data;

        // 관통형 체크
        $car_structure = $measurement['car_structure'] ?? '일반형';
        $is_pass_through = $car_structure === '관통형';

        if (!empty($display_panel_data)) {
            foreach ($display_panel_data as $panel_num => $panel_info) {
                // 관통형일 때 5,6,7번 패널 제외
                if ($is_pass_through && in_array(intval($panel_num), [5, 6, 7])) {
                    continue;
                }

                // 1,11번 패널 제외 설정이 켜져있으면 해당 패널 건너뛰기
                if ($production_settings['panel_corners_excluded'] && (intval($panel_num) === 1 || intval($panel_num) === 11)) {
                    continue;
                }

                // Transom(키가 '12' 또는 'transom')은 패널 루프에서 제외하고 별도 행으로 출력
                if ($panel_num === '12' || $panel_num === 12 || $panel_num === 'transom') {
                    continue;
                }

                // 아이파크 체크 여부 확인
                $ipark_check = 'N';
                if (isset($measurement['site_name']) && strpos($measurement['site_name'], '아이파크') !== false) {
                    $ipark_check = 'Y';
                }

                // 기본 현장 정보
                $productionSheet->setCellValue('A' . $productionRow, $rowNum);
                $productionSheet->setCellValue('B' . $productionRow, $measurement['id']);
                $productionSheet->setCellValue('C' . $productionRow, $measurement['site_name']);
                $productionSheet->setCellValue('D' . $productionRow, date('Y-m-d', strtotime($measurement['measurement_date'])));
                $productionSheet->setCellValue('E' . $productionRow, $measurement['measurer_name']);
                $productionSheet->setCellValue('F' . $productionRow, $measurement['car_inside_width'] == 0 ? '' : $measurement['car_inside_width']);
                $productionSheet->setCellValue('G' . $productionRow, $measurement['car_inside_depth'] == 0 ? '' : $measurement['car_inside_depth']);
                $productionSheet->setCellValue('H' . $productionRow, $measurement['car_inside_height'] == 0 ? '' : $measurement['car_inside_height']);
                $productionSheet->setCellValue('I' . $productionRow, $car_structure);
                $productionSheet->setCellValue('J' . $productionRow, $ipark_check);
                $productionSheet->setCellValue('K' . $productionRow, $measurement['material_type']);
                $productionSheet->setCellValue('L' . $productionRow, $measurement['material_thickness'] == 0 ? '' : $measurement['material_thickness']);

                // 패널 정보
                $productionSheet->setCellValue('M' . $productionRow, $panel_num);
                $productionSheet->setCellValue('N' . $productionRow, $elevator_count);
                $panelIndex = is_numeric($panel_num) ? (int)$panel_num : (int)preg_replace('/\D+/', '', (string)$panel_num);
                $productionSheet->setCellValue('O' . $productionRow, ($panelIndex === 1 || $panelIndex === 11) ? ($panel_info['panel_type_detail'] ?? '') : '');

                // 제작사이즈 적용 (result.php의 몰딩 차감 로직 적용)
                $width = $panel_info['width'] ?? 0;

                // 몰딩포함 시 패널별 width 차감 적용 (result.php와 동일)
                if ($production_settings['molding_included'] && is_numeric($panel_num) && $panel_num >= 2 && $panel_num <= 10) {
                    $molding_deduction = 0;
                    $panel_number = intval($panel_num);

                    if ($panel_number === 2 || $panel_number === 10) {
                        $molding_deduction = 5; // 2번, 10번: -5
                    } elseif ($panel_number === 3 || $panel_number === 6 || $panel_number === 9) {
                        $molding_deduction = 4; // 3번, 6번, 9번: -4
                    } elseif ($panel_number === 4 || $panel_number === 5 || $panel_number === 7 || $panel_number === 8) {
                        // 관통형일 때 4번, 8번은 -5, 일반형일 때는 -10
                        if ($is_pass_through && ($panel_number === 4 || $panel_number === 8)) {
                            $molding_deduction = 5; // 관통형 4번, 8번: -5
                        } else {
                            $molding_deduction = 10; // 일반형 또는 5번, 7번: -10
                        }
                    }

                    $width = $width - $molding_deduction;
                }

                // 높이는 제작 높이 적용
                if (in_array($panel_num, ['1', '11'])) {
                    $height = $production_settings['production_height1_11'];
                } else {
                    $height = $production_settings['production_height'];
                }

                // 제작폭, 제작높이 설정
                $productionSheet->setCellValue('P' . $productionRow, $width == 0 ? '' : $width);
                $productionSheet->setCellValue('Q' . $productionRow, $height == 0 ? '' : $height);

                // 타공 정보 추출 (export_production_results.php와 동일한 로직)
                $hole_width = '';
                $hole_height = '';
                $hole_floor_height = '';
                $hole_entrance_distance = '';

                // 아이파크 프로젝트 확인
                $is_ipark_project = false;
                if (isset($measurement['site_name']) && strpos($measurement['site_name'], '아이파크') !== false) {
                    $is_ipark_project = true;
                }

                // 타공 정보 추출 (export_production_results.php의 로직 그대로 적용)
                if (isset($panel_info['drilling_width']) || isset($panel_info['drilling_height'])) {
                    if (isset($panel_info['drilling_width']) && isset($panel_info['drilling_height'])) {
                        $hole_width = $panel_info['drilling_width'];
                        $hole_height = $panel_info['drilling_height'];
                        
                        if ($is_ipark_project) {
                            $hole_width = $hole_width - 70;
                            $hole_height = $hole_height - 13;
                        }
                    }
                    if (isset($panel_info['drilling_from_floor'])) {
                        $hole_floor_height = $panel_info['drilling_from_floor'];
                        
                        if ($is_ipark_project) {
                            $hole_floor_height = $hole_floor_height + 6;
                        }
                    }
                    if (isset($panel_info['drilling_from_entrance'])) {
                        $hole_entrance_distance = $panel_info['drilling_from_entrance'];
                        
                        if ($is_ipark_project && isset($panel_info['width'])) {
                            $production_width = $panel_info['width'];
                            $production_drilling_width = $hole_width;
                            $hole_entrance_distance = ($production_width - $production_drilling_width) / 2;
                        }
                    }
                }

                // 타공 정보 설정
                $productionSheet->setCellValue('R' . $productionRow, $hole_width);
                $productionSheet->setCellValue('S' . $productionRow, $hole_height);
                $productionSheet->setCellValue('T' . $productionRow, $hole_floor_height);
                $productionSheet->setCellValue('U' . $productionRow, $hole_entrance_distance);

                // Corner details for 1,11
                $productionSheet->setCellValue('V' . $productionRow, ($panel_info['frontThickness'] ?? 0) == 0 ? '' : ($panel_info['frontThickness'] ?? ''));
                $productionSheet->setCellValue('W' . $productionRow, ($panel_info['frontWing'] ?? 0) == 0 ? '' : ($panel_info['frontWing'] ?? ''));
                $productionSheet->setCellValue('X' . $productionRow, ($panel_info['backThickness'] ?? 0) == 0 ? '' : ($panel_info['backThickness'] ?? ''));
                $productionSheet->setCellValue('Y' . $productionRow, ($panel_info['backWing'] ?? 0) == 0 ? '' : ($panel_info['backWing'] ?? ''));

                // 1~11번 패널은 TR 관련 컬럼을 비워둠
                $currentPanelNum = is_numeric($panel_num) ? (int)$panel_num : (int)preg_replace('/\D+/', '', (string)$panel_num);
                if ($currentPanelNum >= 1 && $currentPanelNum <= 11) {
                    foreach (['Z','AA','AB','AC','AD','AE','AF'] as $col) {
                        $productionSheet->setCellValue($col . $productionRow, '');
                    }
                } else {
                    foreach (['Z','AA','AB','AC','AD','AE','AF'] as $col) {
                        $productionSheet->setCellValue($col . $productionRow, '');
                    }
                }

                $productionSheet->setCellValue('AG' . $productionRow, $panel_info['specialNotes'] ?? '');
                $productionSheet->setCellValue('AH' . $productionRow, '');
                $productionRow++;
            }

            // Transom 데이터 처리 (export_production_results.php와 동일)
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
            
            if (!empty($transomData)) {
                $productionSheet->setCellValue('A' . $productionRow, $rowNum);
                $productionSheet->setCellValue('B' . $productionRow, $measurement['id']);
                $productionSheet->setCellValue('C' . $productionRow, $measurement['site_name']);
                $productionSheet->setCellValue('D' . $productionRow, date('Y-m-d', strtotime($measurement['measurement_date'])));
                $productionSheet->setCellValue('E' . $productionRow, $measurement['measurer_name']);
                $productionSheet->setCellValue('F' . $productionRow, $measurement['car_inside_width'] == 0 ? '' : $measurement['car_inside_width']);
                $productionSheet->setCellValue('G' . $productionRow, $measurement['car_inside_depth'] == 0 ? '' : $measurement['car_inside_depth']);
                $productionSheet->setCellValue('H' . $productionRow, $measurement['car_inside_height'] == 0 ? '' : $measurement['car_inside_height']);
                $productionSheet->setCellValue('I' . $productionRow, $car_structure);
                $productionSheet->setCellValue('J' . $productionRow, $ipark_check);
                $productionSheet->setCellValue('K' . $productionRow, $measurement['material_type']);
                $productionSheet->setCellValue('L' . $productionRow, $measurement['material_thickness'] == 0 ? '' : $measurement['material_thickness']);
                $productionSheet->setCellValue('M' . $productionRow, 'transom');
                $productionSheet->setCellValue('N' . $productionRow, $elevator_count);
                $productionSheet->setCellValue('O' . $productionRow, '');
                $productionSheet->setCellValue('P' . $productionRow, ($transomData['width'] ?? 0) == 0 ? '' : ($transomData['width'] ?? ''));
                $productionSheet->setCellValue('Q' . $productionRow, ($transomData['height'] ?? 0) == 0 ? '' : ($transomData['height'] ?? ''));
                
                // 나머지 컬럼들 비워둠
                foreach (['R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH'] as $col) {
                    $productionSheet->setCellValue($col . $productionRow, '');
                }
                
                $productionRow++;
            }
        }

        $rowNum++;
    }

    // 제작산출결과 데이터 스타일
    if ($productionRow > 2) {
        $productionSheet->getStyle('A2:AH' . ($productionRow - 1))->applyFromArray($dataStyle);
    }

    // 제작산출결과 시트 컬럼 너비 설정
    $defaultDetailWidth = 14;
    foreach (range('A','Z') as $columnID) {
        $productionSheet->getColumnDimension($columnID)->setAutoSize(false);
        $productionSheet->getColumnDimension($columnID)->setWidth($defaultDetailWidth);
    }
    foreach (['AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH'] as $columnID) {
        $productionSheet->getColumnDimension($columnID)->setAutoSize(false);
        $productionSheet->getColumnDimension($columnID)->setWidth($defaultDetailWidth);
    }
    
    // 긴 텍스트/헤더 열은 더 넓게 지정
    $detailWideColumns = [
        'A' => 5, 'B' => 8, 'C' => 30, 'D' => 12, 'E' => 12, 'I' => 12, 'J' => 12,
        'M' => 12, 'N' => 12, 'O' => 15, 'P' => 14, 'Q' => 14, 'R' => 14, 'S' => 14,
        'T' => 18, 'U' => 18, 'AD' => 18, 'AE' => 18, 'AF' => 18, 'AG' => 20, 'AH' => 30
    ];
    foreach ($detailWideColumns as $col => $width) {
        $productionSheet->getColumnDimension($col)->setWidth($width);
    }

    // === 세 번째 시트: 몰딩 정보 ===
    $moldingSheet = $objPHPExcel->createSheet();
    $moldingSheet->setTitle('Molding');

    // 모든 몰딩 데이터를 수집
    $allMoldingData = [];
    
    foreach ($measurements as $measurement) {
        $productionHeight = intval($measurement['production_height'] ?? $measurement['car_inside_height']);
        $carWidth = intval($measurement['car_inside_width']);
        $carDepth = intval($measurement['car_inside_depth']);
        $raw_elevator_count = $measurement['elevator_count'] ?? null;
        $elevatorCount = is_null($raw_elevator_count) ? 1 : intval($raw_elevator_count);

        // 관통형 체크
        $car_structure = $measurement['car_structure'] ?? '일반형';
        $is_pass_through = $car_structure === '관통형';
        
        $moldingData = [
            [
                'type' => '엔딩몰딩',
                'size' => $productionHeight,
                'count' => $is_pass_through ? 4 : 2, // 관통형일 때 4개 (2, 4, 8, 10번), 일반형일 때 2개 (2, 10번)
                'elevatorCount' => $elevatorCount,
                'totalCount' => ($is_pass_through ? 4 : 2) * $elevatorCount,
                'description' => $is_pass_through ? '2번, 10번, 4번, 8번 패널용 (관통형)' : '2번, 10번 패널용'
            ],
            [
                'type' => '센터몰딩',
                'size' => $productionHeight,
                'count' => $is_pass_through ? 4 : 6, // 관통형일 때 5-6, 6-7번 센터몰딩 제외
                'elevatorCount' => $elevatorCount,
                'totalCount' => ($is_pass_through ? 4 : 6) * $elevatorCount,
                'description' => $is_pass_through ? '2-3, 3-4, 8-9, 9-10번 연결용' : '패널 사이 연결용'
            ],
            [
                'type' => 'S엔딩몰딩',
                'size' => $carDepth - 5,
                'count' => 2,
                'elevatorCount' => $elevatorCount,
                'totalCount' => 2 * $elevatorCount,
                'description' => '측면 하부 가로'
            ]
        ];
        
        // 일반형일 때만 코너몰딩과 R엔딩몰딩 추가
        if (!$is_pass_through) {
            $moldingData[] = [
                'type' => '코너몰딩',
                'size' => $productionHeight,
                'count' => 2,
                'elevatorCount' => $elevatorCount,
                'totalCount' => 2 * $elevatorCount,
                'description' => '4-5, 7-8번 연결용'
            ];
            $moldingData[] = [
                'type' => 'R엔딩몰딩',
                'size' => $carWidth - 2,
                'count' => 1,
                'elevatorCount' => $elevatorCount,
                'totalCount' => 1 * $elevatorCount,
                'description' => '후면 하부 가로'
            ];
        }

        // 몰딩 데이터를 전체 배열에 추가
        foreach ($moldingData as $molding) {
            $allMoldingData[] = $molding;
        }
    }

    // 몰딩 데이터를 종류별로 정렬
    $moldingTypeOrder = [
        '엔딩몰딩' => 1,
        '센터몰딩' => 2, 
        'S엔딩몰딩' => 3,
        '코너몰딩' => 4,
        'R엔딩몰딩' => 5
    ];
    
    usort($allMoldingData, function($a, $b) use ($moldingTypeOrder) {
        $typeA = $moldingTypeOrder[$a['type']] ?? 999;
        $typeB = $moldingTypeOrder[$b['type']] ?? 999;
        
        if ($typeA === $typeB) {
            // 같은 타입이면 크기로 정렬
            return $a['size'] - $b['size'];
        }
        
        return $typeA - $typeB;
    });

    // 헤더 작성
    $moldingHeaders = [
        'A1' => '몰딩 종류',
        'B1' => '절단치수(mm)',
        'C1' => '개수(EA)',
        'D1' => '대수',
        'E1' => '총개수(EA)',
        'F1' => '설명'
    ];

    foreach ($moldingHeaders as $cell => $value) {
        $moldingSheet->setCellValue($cell, $value);
    }

    // 몰딩 헤더 스타일
    $moldingSheet->getStyle('A1:F1')->applyFromArray($headerStyle);

    // 정렬된 몰딩 데이터 작성
    $moldingRow = 2;
    foreach ($allMoldingData as $molding) {
        $moldingSheet->setCellValue('A' . $moldingRow, $molding['type']);
        $moldingSheet->setCellValue('B' . $moldingRow, $molding['size']);
        $moldingSheet->setCellValue('C' . $moldingRow, $molding['count']);
        $moldingSheet->setCellValue('D' . $moldingRow, $molding['elevatorCount']);
        $moldingSheet->setCellValue('E' . $moldingRow, $molding['totalCount']);
        $moldingSheet->setCellValue('F' . $moldingRow, $molding['description']);
        $moldingRow++;
    }

    // 몰딩 데이터 스타일
    if ($moldingRow > 2) {
        $moldingSheet->getStyle('A2:F' . ($moldingRow - 1))->applyFromArray($dataStyle);
    }

    // 몰딩 시트 컬럼 너비 설정
    $moldingSheet->getColumnDimension('A')->setWidth(21);
    $moldingSheet->getColumnDimension('B')->setWidth(17);
    $moldingSheet->getColumnDimension('C')->setWidth(14);
    $moldingSheet->getColumnDimension('D')->setWidth(11);
    $moldingSheet->getColumnDimension('E')->setWidth(14);
    $moldingSheet->getColumnDimension('F')->setAutoSize(true);

    // 첫 번째 시트를 활성화
    $objPHPExcel->setActiveSheetIndex(0);

    // 파일명 생성 (그룹명_날짜시간분.xlsx 형태)
    error_log("=== 파일명 생성 디버깅 ===");
    error_log("원본 그룹명: " . $group_name);
    
    // Windows 파일명에서 사용할 수 없는 문자만 제거 (한글은 유지)
    $group_name_clean = preg_replace('/[<>:\"\/\\\\|?*]/', '_', $group_name);
    error_log("특수문자 변환 후: " . $group_name_clean);
    
    // 빈 문자열이면 기본값 사용
    if (empty(trim($group_name_clean))) {
        $group_name_clean = '그룹';
    }
    
    $filename = $group_name_clean . '_' . date('YmdHi') . '.xlsx';
    error_log("최종 파일명: " . $filename);

    // 출력 버퍼 정리
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // 브라우저 호환성을 위한 헤더 설정
    header('HTTP/1.1 200 OK');
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Accept-Ranges: bytes');
    header('Connection: close');
    header('X-Content-Type-Options: nosniff');
    header('X-Download-Options: noopen');

    // Writer 생성 및 저장
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    
    // 파일 크기 계산
    $temp_file = tempnam(sys_get_temp_dir(), 'excel_group_production_');
    $objWriter->save($temp_file);
    $file_size = filesize($temp_file);

    if ($file_size === false || $file_size < 1000) {
        $file_size = 10240;
    }

    header('Content-Length: ' . $file_size);

    // 출력 버퍼 정리
    if (ob_get_level()) {
        ob_end_clean();
    }

    // 파일 내용 출력
    $file_content = file_get_contents($temp_file);
    print($file_content);

    // 임시 파일 삭제
    unlink($temp_file);

    exit;

} catch (Exception $e) {
    error_log("Group Excel export error: " . $e->getMessage());
    die('Excel 파일 생성 중 오류가 발생했습니다: ' . $e->getMessage());
}
?>