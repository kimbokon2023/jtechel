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

// Get measurement ID
$measurement_id = intval($_GET['measurement_id'] ?? 0);

if ($measurement_id <= 0) {
    die('유효하지 않은 측정 ID입니다.');
}
 
// Initialize database connection
try {
    $pdo = db_connect();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("USE $DB");
} catch (PDOException $e) {
    error_log("Database connection failed in export_production_results.php: " . $e->getMessage());
    die("데이터베이스 연결에 실패했습니다.");
}

// Get measurement data
$stmt = $pdo->prepare("SELECT * FROM panel_measurements WHERE id = ?");
$stmt->execute([$measurement_id]);
$selected_data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$selected_data) {
    die('측정 데이터를 찾을 수 없습니다.');
}

// Get panel data and production settings
$panel_data = [];
if (!empty($selected_data['panel_data'])) {
    $panel_data = json_decode($selected_data['panel_data'], true) ?: [];
}

// 디버그: 패널 데이터 구조 확인
error_log("=== export_production_results.php 패널 데이터 구조 ===");
error_log("원본 패널 데이터: " . print_r($panel_data, true));

// URL 파라미터로 전달된 설정들 우선 사용
$url_molding_included = isset($_GET['molding_included']) ? intval($_GET['molding_included']) : null;
$url_panel_corners_excluded = isset($_GET['panel_corners_excluded']) ? intval($_GET['panel_corners_excluded']) : null;

$production_settings = [
    'production_height' => $selected_data['production_height'] ?? $selected_data['car_inside_height'],
    'production_height1_11' => $selected_data['production_height1_11'] ?? $selected_data['car_inside_height'],
    'panel_corners_excluded' => $url_panel_corners_excluded !== null ? $url_panel_corners_excluded : ($selected_data['panel_corners_excluded'] ?? 1),
    'transom_excluded' => $selected_data['transom_excluded'] ?? 0,
    'molding_included' => $url_molding_included !== null ? $url_molding_included : ($selected_data['molding_included'] ?? 0)
];

// 변수 추출
$production_height = intval($production_settings['production_height']);
$molding_included = $production_settings['molding_included'];
// elevator_count 처리 (NULL인 경우 기본값 1 사용)
$raw_elevator_count = $selected_data['elevator_count'] ?? null;
$elevator_count = is_null($raw_elevator_count) ? 1 : intval($raw_elevator_count);

// 디버그: elevator_count 값 확인
error_log("=== export_production_results.php elevator_count 디버그 ===");
error_log("selected_data['elevator_count']: " . ($raw_elevator_count ?? 'NULL'));
error_log("최종 elevator_count: " . $elevator_count);

// Get production results
$make_panel_data = generateMakePanelData($panel_data, $production_settings);

// 디버그: 제작 패널 데이터 확인
error_log("제작 패널 데이터: " . print_r($make_panel_data, true));

// Generate production results for Excel export
$production_results = [
    'total_panels' => count($panel_data),
    'dimension_summary' => [
        'total_area' => 0,
        'details' => []
    ]
];

// Calculate total area from panel data
if (!empty($panel_data)) {
    $total_area = 0;
    $details = [];

    foreach ($panel_data as $panel_num => $panel_info) {
        if (isset($panel_info['width']) && isset($panel_info['height'])) {
            $width = floatval($panel_info['width']);
            $height = floatval($panel_info['height']);
            $area = ($width * $height) / 1000000; // mm² to m²

            $total_area += $area;
            $details[] = [
                'panel' => $panel_num,
                'width' => $width,
                'height' => $height,
                'area' => $area
            ];
        }
    }

    $production_results['dimension_summary']['total_area'] = $total_area;
    $production_results['dimension_summary']['details'] = $details;
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
    // 디버깅: PHPExcel 라이브러리 로드 확인
    error_log("=== export_production_results.php 디버깅 시작 ===");
    error_log("PHPExcel 라이브러리 경로: " . $excel_lib_path);
    error_log("PHPExcel 클래스 존재 여부: " . (class_exists('PHPExcel') ? 'YES' : 'NO'));
    
    // Create new PHPExcel object
    error_log("PHPExcel 객체 생성 시도...");
    $objPHPExcel = new PHPExcel();
    error_log("PHPExcel 객체 생성 성공!");

    // Set document properties
    $objPHPExcel->getProperties()
        ->setCreator("J-TECH Elevator")
        ->setLastModifiedBy($_SESSION['name'] ?? 'User')
        ->setTitle("제작산출 결과 - " . $selected_data['site_name'])
        ->setSubject("Panel Measurement Production Results")
        ->setDescription("Panel measurement production results exported from J-TECH system")
        ->setKeywords("panel measurement production results excel export")
        ->setCategory("Production Data");

    // === 첫 번째 시트: 현장기초정보 ===
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
        'H1' => '의장재질',
        'I1' => '재질두께(mm)',
        // 제작 조건 설정 컬럼 추가
        'J1' => '프로젝트 타입',
        'K1' => '1,11번 제외',
        'L1' => '트랜섬 제외',
        'M1' => '몰딩 포함',
        'N1' => '엘리베이터 대수',
        'O1' => '제작 높이(mm)',
        'P1' => '1,11번 높이(mm)',
        // 패널 정보
        'Q1' => '총 패널 수',
        'R1' => '특이사항'
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

    $sheet->getStyle('A1:R1')->applyFromArray($headerStyle);

    // 데이터 행 작성
    $row = 2;

    // 제작 조건 설정 값들 처리
    $project_type = $selected_data['project_type'] ?? '신규';
    $panel_corners_excluded_display = $url_panel_corners_excluded !== null ? ($url_panel_corners_excluded ? 'Y' : 'N') : (isset($selected_data['panel_corners_excluded']) ? ($selected_data['panel_corners_excluded'] ? 'Y' : 'N') : 'Y');
    $transom_excluded = isset($selected_data['transom_excluded']) ? ($selected_data['transom_excluded'] ? 'Y' : 'N') : 'N';
    $molding_included_display = $url_molding_included !== null ? ($url_molding_included ? 'Y' : 'N') : (isset($selected_data['molding_included']) ? ($selected_data['molding_included'] ? 'Y' : 'N') : 'N');
    // $elevator_count는 이미 위에서 정의됨
    $production_height = $selected_data['production_height'] ?? $selected_data['car_inside_height'];
    $production_height1_11 = $selected_data['production_height1_11'] ?? $selected_data['car_inside_height'];

    $data = [
        'A' => $selected_data['id'],
        'B' => $selected_data['site_name'],
        'C' => date('Y-m-d', strtotime($selected_data['measurement_date'])),
        'D' => $selected_data['measurer_name'],
        'E' => $selected_data['car_inside_width'],
        'F' => $selected_data['car_inside_depth'],
        'G' => $selected_data['car_inside_height'],
        'H' => $selected_data['material_type'] ?? '',
        'I' => $selected_data['material_thickness'] ?? '',
        // 제작 조건 설정
        'J' => $project_type,
        'K' => $panel_corners_excluded_display,
        'L' => $transom_excluded,
        'M' => $molding_included_display,
        'N' => $elevator_count,
        'O' => $production_height,
        'P' => $production_height1_11,
        // 패널 정보
        'Q' => $production_results['total_panels'] ?? 0,
        'R' => $selected_data['notes'] ?? ''
    ];

    foreach ($data as $col => $value) {
        $sheet->setCellValue($col . $row, $value);
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

    $sheet->getStyle('A2:R' . $row)->applyFromArray($dataStyle);

    // 컬럼 너비 사용자 지정 설정
    // 현장명(B열), 특이사항(R열) 2배 확장, 측정자(D열) 축소
    $sheet->getColumnDimension('B')->setWidth(40); // 현장명
    $sheet->getColumnDimension('D')->setWidth(12); // 측정자 (30% 축소)
    $sheet->getColumnDimension('R')->setWidth(40); // 특이사항

    // E, F, G열 너비 20% 확장
    $sheet->getColumnDimension('E')->setWidth(24); // 카 내부 가로 (20% 확장)
    $sheet->getColumnDimension('F')->setWidth(24); // 카 내부 깊이 (20% 확장)
    $sheet->getColumnDimension('G')->setWidth(24); // 카 내부 높이 (20% 확장)

    // H열~Q열까지 너비 20으로 설정
    $wideCols = ['H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q'];
    foreach ($wideCols as $col) {
        $sheet->getColumnDimension($col)->setWidth(20);
    }

    // 나머지 컬럼은 헤더 길이에 맞춰 자동 조정
    $autoSizeCols = ['A', 'C'];
    foreach ($autoSizeCols as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // === 두 번째 시트: 제작산출결과 (패널별 상세) ===
    $productionSheet = $objPHPExcel->createSheet();
    $productionSheet->setTitle('제작산출결과');

    // 제작산출결과 시트 헤더 (export_measurements.php의 세부정보 구조 참고)
    $productionHeaders = [
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
        'L1' => '제작 대수',
        'M1' => '패널 타입',
        'N1' => '제작폭',
        'O1' => '제작높이',
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
        'AF1' => '패널 특이사항'
    ];

    foreach ($productionHeaders as $cell => $value) {
        $productionSheet->setCellValue($cell, $value);
    }

    // 제작산출결과 헤더 스타일
    $productionSheet->getStyle('A1:AF1')->applyFromArray($headerStyle);

    // 패널 데이터 작성
    $productionRow = 2;
    $rowNum = 1;

    // 제작패널데이터를 우선 사용, 없으면 원본 패널 데이터 사용
    $display_panel_data = !empty($make_panel_data) ? $make_panel_data : $panel_data;

    // Transom 데이터 파싱
    $transomData = [];
    error_log("=== Transom 데이터 파싱 시작 ===");
    error_log("selected_data['transom_data']: " . print_r($selected_data['transom_data'], true));
    
    if (!empty($selected_data['transom_data'])) {
        $transom_data = json_decode($selected_data['transom_data'], true);
        error_log("JSON 파싱된 transom_data: " . print_r($transom_data, true));
        
        if ($transom_data && is_array($transom_data)) {
            if (isset($transom_data['transom'])) {
                $transomData = $transom_data['transom'];
                error_log("transom 키로 데이터 추출: " . print_r($transomData, true));
            } elseif (isset($transom_data['12'])) {
                $transomData = $transom_data['12'];
                error_log("12 키로 데이터 추출: " . print_r($transomData, true));
            } else {
                error_log("transom 또는 12 키를 찾을 수 없음. 사용 가능한 키들: " . implode(', ', array_keys($transom_data)));
                // 모든 키를 시도해보기
                foreach ($transom_data as $key => $value) {
                    if (is_array($value) && !empty($value)) {
                        $transomData = $value;
                        error_log("키 '{$key}'로 데이터 추출: " . print_r($transomData, true));
                        break;
                    }
                }
            }
        }
    } else {
        error_log("transom_data가 비어있음");
    }
    
    error_log("최종 transomData: " . print_r($transomData, true));

    if (!empty($display_panel_data)) {
        foreach ($display_panel_data as $panel_num => $panel_info) {
            // 1,11번 패널 제외 설정이 켜져있으면 해당 패널 건너뛰기
            if ($production_settings['panel_corners_excluded'] && (intval($panel_num) === 1 || intval($panel_num) === 11)) {
                continue;
            }

            // Transom(키가 '12' 또는 'transom')은 패널 루프에서 제외하고 별도 행으로 출력
            if ($panel_num === '12' || $panel_num === 12 || $panel_num === 'transom') {
                continue;
            }

            // 디버그: 패널 데이터 구조 확인
            error_log("패널 {$panel_num} 데이터 구조: " . print_r($panel_info, true));

            // 기본 현장 정보
            $productionSheet->setCellValue('A' . $productionRow, $rowNum);
            $productionSheet->setCellValue('B' . $productionRow, $selected_data['id']);
            $productionSheet->setCellValue('C' . $productionRow, $selected_data['site_name']);
            $productionSheet->setCellValue('D' . $productionRow, date('Y-m-d', strtotime($selected_data['measurement_date'])));
            $productionSheet->setCellValue('E' . $productionRow, $selected_data['measurer_name']);
            $productionSheet->setCellValue('F' . $productionRow, $selected_data['car_inside_width'] == 0 ? '' : $selected_data['car_inside_width']);
            $productionSheet->setCellValue('G' . $productionRow, $selected_data['car_inside_depth'] == 0 ? '' : $selected_data['car_inside_depth']);
            $productionSheet->setCellValue('H' . $productionRow, $selected_data['car_inside_height'] == 0 ? '' : $selected_data['car_inside_height']);
            $productionSheet->setCellValue('I' . $productionRow, $selected_data['material_type']);
            $productionSheet->setCellValue('J' . $productionRow, $selected_data['material_thickness'] == 0 ? '' : $selected_data['material_thickness']);

            // 패널 정보
            $productionSheet->setCellValue('K' . $productionRow, $panel_num);
            $productionSheet->setCellValue('L' . $productionRow, $elevator_count);
            $panelIndex = is_numeric($panel_num) ? (int)$panel_num : (int)preg_replace('/\D+/', '', (string)$panel_num);
            $productionSheet->setCellValue('M' . $productionRow, ($panelIndex === 1 || $panelIndex === 11) ? ($panel_info['panel_type_detail'] ?? '') : '');

            // 제작사이즈 적용 (제작패널데이터의 width 사용)
            $width = $panel_info['width'] ?? 0;

            // 몰딩포함 시 패널별 width 차감 적용
            if ($production_settings['molding_included'] && is_numeric($panel_num) && $panel_num >= 2 && $panel_num <= 10) {
                $molding_deduction = 0;
                $panel_number = intval($panel_num);

                if ($panel_number === 2 || $panel_number === 10) {
                    $molding_deduction = 5; // 2번, 10번: -5
                } elseif ($panel_number === 3 || $panel_number === 6 || $panel_number === 9) {
                    $molding_deduction = 4; // 3번, 6번, 9번: -4
                } elseif ($panel_number === 4 || $panel_number === 5 || $panel_number === 7 || $panel_number === 8) {
                    $molding_deduction = 10; // 4번, 5번, 7번, 8번: -10
                }

                $width = $width - $molding_deduction;
            }

            // 높이는 제작 높이 적용 (1,11번 패널은 별도 높이, 나머지는 기본 제작 높이)
            if (in_array($panel_num, ['1', '11'])) {
                $height = $production_height1_11;
            } else {
                $height = $production_height;
            }

            // 제작폭, 제작높이 설정
            $productionSheet->setCellValue('N' . $productionRow, $width == 0 ? '' : $width);
            $productionSheet->setCellValue('O' . $productionRow, $height == 0 ? '' : $height);

            // 타공 정보 추출 (다양한 형태의 데이터 구조 지원)
            $hole_width = '';
            $hole_height = '';
            $hole_floor_height = '';
            $hole_entrance_distance = '';

            // 1. drilling_ 접두사 속성이 있는 경우 (실제 데이터 구조)
            if (isset($panel_info['drilling_width']) || isset($panel_info['drilling_height'])) {
                if (isset($panel_info['drilling_width']) && isset($panel_info['drilling_height'])) {
                    $hole_width = $panel_info['drilling_width'];
                    $hole_height = $panel_info['drilling_height'];
                }
                if (isset($panel_info['drilling_from_floor'])) {
                    $hole_floor_height = $panel_info['drilling_from_floor'];
                }
                if (isset($panel_info['drilling_from_entrance'])) {
                    $hole_entrance_distance = $panel_info['drilling_from_entrance'];
                }
            }
            // 2. holes 배열이 있는 경우 (다중 타공)
            elseif (isset($panel_info['holes']) && is_array($panel_info['holes'])) {
                $hole_details = [];
                $floor_heights = [];
                $entrance_distances = [];

                foreach ($panel_info['holes'] as $hole) {
                    if (isset($hole['width']) && isset($hole['height'])) {
                        $hole_details[] = $hole['width'] . '×' . $hole['height'];
                    }
                    if (isset($hole['floor_height'])) {
                        $floor_heights[] = $hole['floor_height'];
                    }
                    if (isset($hole['entrance_distance'])) {
                        $entrance_distances[] = $hole['entrance_distance'];
                    }
                }

                $hole_width = implode(', ', array_column($panel_info['holes'], 'width'));
                $hole_height = implode(', ', array_column($panel_info['holes'], 'height'));
                $hole_floor_height = implode(', ', $floor_heights);
                $hole_entrance_distance = implode(', ', $entrance_distances);
            }
            // 3. drillingWidth, drillingHeight 속성이 있는 경우 (단일 타공)
            elseif (isset($panel_info['drillingWidth']) || isset($panel_info['drillingHeight'])) {
                if (isset($panel_info['drillingWidth']) && isset($panel_info['drillingHeight'])) {
                    $hole_width = $panel_info['drillingWidth'];
                    $hole_height = $panel_info['drillingHeight'];
                }
                if (isset($panel_info['drillingFromFloor'])) {
                    $hole_floor_height = $panel_info['drillingFromFloor'];
                }
                if (isset($panel_info['drillingFromEntrance'])) {
                    $hole_entrance_distance = $panel_info['drillingFromEntrance'];
                }
            }
            // 4. hole_ 접두사 속성이 있는 경우
            elseif (isset($panel_info['hole_width']) || isset($panel_info['hole_height'])) {
                if (isset($panel_info['hole_width']) && isset($panel_info['hole_height'])) {
                    $hole_width = $panel_info['hole_width'];
                    $hole_height = $panel_info['hole_height'];
                }
                if (isset($panel_info['hole_floor_height'])) {
                    $hole_floor_height = $panel_info['hole_floor_height'];
                }
                if (isset($panel_info['hole_entrance_distance'])) {
                    $hole_entrance_distance = $panel_info['hole_entrance_distance'];
                }
            }
            // 5. 9번 패널 특별 처리 (문 타공)
            elseif ($panel_num === '9') {
                // 9번 패널의 경우 보통 문 타공이 있음
                if (isset($panel_info['door_width']) && isset($panel_info['door_height'])) {
                    $hole_width = $panel_info['door_width'];
                    $hole_height = $panel_info['door_height'];
                }
                if (isset($panel_info['door_floor_height'])) {
                    $hole_floor_height = $panel_info['door_floor_height'];
                }
                if (isset($panel_info['door_center_distance'])) {
                    $hole_entrance_distance = $panel_info['door_center_distance'];
                }
            }

            // 디버그: 타공 정보 추출 결과
            error_log("패널 {$panel_num} 타공 정보: width={$hole_width}, height={$hole_height}, floor={$hole_floor_height}, entrance={$hole_entrance_distance}");

            // 패널 타입 결정
            $panel_type = '';
            $panel_description = '';

            if (in_array($panel_num, ['1', '11'])) {
                $panel_type = '1,11번 패널 (코너패널)';
                $panel_description = '엘리베이터 쪽 모서리 기둘패널';
            } elseif ($panel_num === '12') {
                $panel_type = '트랜섬 패널';
                $panel_description = '엘리베이터 청 상단 패널';
            } elseif ($panel_num === '9') {
                $panel_type = '출입구 패널';
                $panel_description = '승객 출입문이 위치한 전면 패널';
            } elseif (in_array($panel_num, ['2', '10'])) {
                $panel_type = '측면 패널';
                $panel_description = '엘리베이터 양측 측벽 패널';
            } elseif (in_array($panel_num, ['3', '4', '5', '6', '7', '8'])) {
                $panel_type = '후면 패널';
                $panel_description = '엘리베이터 후면부 패널';
            } else {
                $panel_type = '일반 패널';
                $panel_description = '기본 엘리베이터 내장 패널';
            }

            // 기타 정보
            $material = $panel_info['material'] ?? $selected_data['material_type'] ?? '스테인리스 스틸';
            $surface_treatment = $panel_info['surface_treatment'] ?? $selected_data['surface_treatment'] ?? '헤어라인 마감';
            $notes = $panel_info['notes'] ?? $panel_description;
            $quantity = $elevator_count; // 엘리베이터 대수만큼 제작
            $remarks = '';

            // 1,11번 패널 추가 정보
            if (in_array($panel_num, ['1', '11'])) {
                if (isset($panel_info['corner_details'])) {
                    $remarks .= '코너상세: ' . $panel_info['corner_details'] . ' ';
                }
                if (isset($panel_info['bracket_info'])) {
                    $remarks .= '브라켓: ' . $panel_info['bracket_info'] . ' ';
                }
                if (isset($panel_info['safety_features'])) {
                    $remarks .= '안전장치: ' . $panel_info['safety_features'] . ' ';
                }
                if (isset($panel_info['corner_type'])) {
                    $remarks .= '코너타입: ' . $panel_info['corner_type'] . ' ';
                }
                if (isset($panel_info['thickness'])) {
                    $remarks .= '두께: ' . $panel_info['thickness'] . 'mm ';
                }
                if (isset($panel_info['reinforcement'])) {
                    $remarks .= '보강재: ' . $panel_info['reinforcement'] . ' ';
                }
                if (isset($panel_info['mounting_method'])) {
                    $remarks .= '설치방법: ' . $panel_info['mounting_method'] . ' ';
                }
            }

            // 트랜섬 패널 추가 정보 (카 컬럼 정보)
            if ($panel_num === '12') {
                if (isset($panel_info['car_column_width'])) {
                    $remarks .= '카컬럼폭: ' . $panel_info['car_column_width'] . 'mm ';
                }
                if (isset($panel_info['car_column_depth'])) {
                    $remarks .= '카컬럼깊이: ' . $panel_info['car_column_depth'] . 'mm ';
                }
                if (isset($panel_info['car_column_height'])) {
                    $remarks .= '카컬럼높이: ' . $panel_info['car_column_height'] . 'mm ';
                }
                if (isset($panel_info['car_column_material'])) {
                    $remarks .= '카컬럼재질: ' . $panel_info['car_column_material'] . ' ';
                }
                if (isset($panel_info['car_column_position'])) {
                    $remarks .= '카컬럼위치: ' . $panel_info['car_column_position'] . ' ';
                }
                if (isset($panel_info['ventilation_info'])) {
                    $remarks .= '환기구: ' . $panel_info['ventilation_info'] . ' ';
                }
                if (isset($panel_info['ventilation_size'])) {
                    $remarks .= '환기구크기: ' . $panel_info['ventilation_size'] . ' ';
                }
                if (isset($panel_info['support_bracket'])) {
                    $remarks .= '지지브라켓: ' . $panel_info['support_bracket'] . ' ';
                }
                if (isset($panel_info['cable_management'])) {
                    $remarks .= '케이블관리: ' . $panel_info['cable_management'] . ' ';
                }
            }

            // 타공 정보 설정 (올바른 열에 각각의 값 설정)
            $productionSheet->setCellValue('P' . $productionRow, $hole_width);  // 타공 가로
            $productionSheet->setCellValue('Q' . $productionRow, $hole_height); // 타공 세로
            $productionSheet->setCellValue('R' . $productionRow, $hole_floor_height); // 타공 높이(밑기준)
            $productionSheet->setCellValue('S' . $productionRow, $hole_entrance_distance); // 입구방향에서 떨어짐

            // Corner details for 1,11
            $productionSheet->setCellValue('T' . $productionRow, ($panel_info['frontThickness'] ?? 0) == 0 ? '' : ($panel_info['frontThickness'] ?? ''));
            $productionSheet->setCellValue('U' . $productionRow, ($panel_info['frontWing'] ?? 0) == 0 ? '' : ($panel_info['frontWing'] ?? ''));
            $productionSheet->setCellValue('V' . $productionRow, ($panel_info['backThickness'] ?? 0) == 0 ? '' : ($panel_info['backThickness'] ?? ''));
            $productionSheet->setCellValue('W' . $productionRow, ($panel_info['backWing'] ?? 0) == 0 ? '' : ($panel_info['backWing'] ?? ''));

            // 1~11번 패널은 TR 관련 컬럼을 비워둠 (transom만 표시)
            $currentPanelNum = is_numeric($panel_num) ? (int)$panel_num : (int)preg_replace('/\D+/', '', (string)$panel_num);
            if ($currentPanelNum >= 1 && $currentPanelNum <= 11) {
                foreach (['X','Y','Z','AA','AB','AC','AD','AE'] as $col) {
                    $productionSheet->setCellValue($col . $productionRow, '');
                }
            } else {
                foreach (['X','Y','Z','AA','AB','AC','AD','AE'] as $col) {
                    $productionSheet->setCellValue($col . $productionRow, '');
                }
            }

            $productionSheet->setCellValue('AF' . $productionRow, $panel_info['specialNotes'] ?? '');
            $productionRow++;
        }

        // 패널들 출력 후, Transom 데이터가 있으면 별도 행 추가
        error_log("=== Transom 데이터 출력 확인 ===");
        error_log("transomData 비어있지 않은지 확인: " . (!empty($transomData) ? 'YES' : 'NO'));
        error_log("transomData 내용: " . print_r($transomData, true));
        
        if (!empty($transomData)) {
            error_log("Transom 행 추가 시작");
            $productionSheet->setCellValue('A' . $productionRow, $rowNum);
            $productionSheet->setCellValue('B' . $productionRow, $selected_data['id']);
            $productionSheet->setCellValue('C' . $productionRow, $selected_data['site_name']);
            $productionSheet->setCellValue('D' . $productionRow, date('Y-m-d', strtotime($selected_data['measurement_date'])));
            $productionSheet->setCellValue('E' . $productionRow, $selected_data['measurer_name']);
            $productionSheet->setCellValue('F' . $productionRow, $selected_data['car_inside_width'] == 0 ? '' : $selected_data['car_inside_width']);
            $productionSheet->setCellValue('G' . $productionRow, $selected_data['car_inside_depth'] == 0 ? '' : $selected_data['car_inside_depth']);
            $productionSheet->setCellValue('H' . $productionRow, $selected_data['car_inside_height'] == 0 ? '' : $selected_data['car_inside_height']);
            $productionSheet->setCellValue('I' . $productionRow, $selected_data['material_type']);
            $productionSheet->setCellValue('J' . $productionRow, $selected_data['material_thickness'] == 0 ? '' : $selected_data['material_thickness']);
            $productionSheet->setCellValue('K' . $productionRow, 'transom');
            $productionSheet->setCellValue('L' . $productionRow, $elevator_count);
            $productionSheet->setCellValue('M' . $productionRow, '');
            // 패널 가로/세로는 N/O에 출력
            $productionSheet->setCellValue('N' . $productionRow, ($transomData['width'] ?? 0) == 0 ? '' : ($transomData['width'] ?? ''));
            $productionSheet->setCellValue('O' . $productionRow, ($transomData['height'] ?? 0) == 0 ? '' : ($transomData['height'] ?? ''));
            // Drilling fields (Transom에는 비워둠)
            $productionSheet->setCellValue('P' . $productionRow, '');
            $productionSheet->setCellValue('Q' . $productionRow, '');
            $productionSheet->setCellValue('R' . $productionRow, '');
            $productionSheet->setCellValue('S' . $productionRow, '');
            // Corner details (Transom에는 비워둠)
            $productionSheet->setCellValue('T' . $productionRow, '');
            $productionSheet->setCellValue('U' . $productionRow, '');
            $productionSheet->setCellValue('V' . $productionRow, '');
            $productionSheet->setCellValue('W' . $productionRow, '');
            
            // TR 블록: 헤더(X~AE)에 맞춰 정확히 매핑 (TR 세로 제거됨)
            error_log("=== TR 데이터 설정 시작 ===");
            error_log("transomData['width']: " . ($transomData['width'] ?? 'NULL'));
            error_log("transomData['transomPlateHeight']: " . ($transomData['transomPlateHeight'] ?? 'NULL'));
            error_log("transomData['bottomDepthJD']: " . ($transomData['bottomDepthJD'] ?? 'NULL'));
            error_log("transomData['wingValue']: " . ($transomData['wingValue'] ?? 'NULL'));
            error_log("transomData['cpiDrillingWidth']: " . ($transomData['cpiDrillingWidth'] ?? 'NULL'));
            error_log("transomData['cpiDrillingHeight']: " . ($transomData['cpiDrillingHeight'] ?? 'NULL'));
            error_log("transomData['cpiDrillingHeightFromBottom']: " . ($transomData['cpiDrillingHeightFromBottom'] ?? 'NULL'));
            error_log("transomData['notes']: " . ($transomData['notes'] ?? 'NULL'));
            
            $productionSheet->setCellValue('X' . $productionRow, ($transomData['width'] ?? 0) == 0 ? '' : ($transomData['width'] ?? ''));
            $productionSheet->setCellValue('Y' . $productionRow, ($transomData['transomPlateHeight'] ?? 0) == 0 ? '' : ($transomData['transomPlateHeight'] ?? ''));
            $productionSheet->setCellValue('Z' . $productionRow, ($transomData['bottomDepthJD'] ?? 0) == 0 ? '' : ($transomData['bottomDepthJD'] ?? ''));
            $productionSheet->setCellValue('AA' . $productionRow, ($transomData['wingValue'] ?? 0) == 0 ? '' : ($transomData['wingValue'] ?? ''));
            $productionSheet->setCellValue('AB' . $productionRow, ($transomData['cpiDrillingWidth'] ?? 0) == 0 ? '' : ($transomData['cpiDrillingWidth'] ?? ''));
            $productionSheet->setCellValue('AC' . $productionRow, ($transomData['cpiDrillingHeight'] ?? 0) == 0 ? '' : ($transomData['cpiDrillingHeight'] ?? ''));
            $productionSheet->setCellValue('AD' . $productionRow, ($transomData['cpiDrillingHeightFromBottom'] ?? 0) == 0 ? '' : ($transomData['cpiDrillingHeightFromBottom'] ?? ''));
            $productionSheet->setCellValue('AE' . $productionRow, $transomData['notes'] ?? '');
            $productionSheet->setCellValue('AF' . $productionRow, '');
            $productionRow++;
            error_log("Transom 행 추가 완료");
        } else {
            error_log("Transom 데이터가 없어서 행을 추가하지 않음");
        }
    } else {
        error_log("display_panel_data가 비어있음");
        
        // 패널 데이터가 없는 경우에도 Transom 데이터가 있으면 표시
        if (!empty($transomData)) {
            error_log("패널 데이터는 없지만 Transom 데이터가 있어서 행 추가");
            $productionSheet->setCellValue('A' . $productionRow, $rowNum);
            $productionSheet->setCellValue('B' . $productionRow, $selected_data['id']);
            $productionSheet->setCellValue('C' . $productionRow, $selected_data['site_name']);
            $productionSheet->setCellValue('D' . $productionRow, date('Y-m-d', strtotime($selected_data['measurement_date'])));
            $productionSheet->setCellValue('E' . $productionRow, $selected_data['measurer_name']);
            $productionSheet->setCellValue('F' . $productionRow, $selected_data['car_inside_width'] == 0 ? '' : $selected_data['car_inside_width']);
            $productionSheet->setCellValue('G' . $productionRow, $selected_data['car_inside_depth'] == 0 ? '' : $selected_data['car_inside_depth']);
            $productionSheet->setCellValue('H' . $productionRow, $selected_data['car_inside_height'] == 0 ? '' : $selected_data['car_inside_height']);
            $productionSheet->setCellValue('I' . $productionRow, $selected_data['material_type']);
            $productionSheet->setCellValue('J' . $productionRow, $selected_data['material_thickness'] == 0 ? '' : $selected_data['material_thickness']);
            $productionSheet->setCellValue('K' . $productionRow, 'transom');
            $productionSheet->setCellValue('L' . $productionRow, $elevator_count);
            $productionSheet->setCellValue('M' . $productionRow, '');
            // 패널 가로/세로는 N/O에 출력
            $productionSheet->setCellValue('N' . $productionRow, ($transomData['width'] ?? 0) == 0 ? '' : ($transomData['width'] ?? ''));
            $productionSheet->setCellValue('O' . $productionRow, ($transomData['height'] ?? 0) == 0 ? '' : ($transomData['height'] ?? ''));
            // Drilling fields (transom에는 비워둠)
            $productionSheet->setCellValue('P' . $productionRow, '');
            $productionSheet->setCellValue('Q' . $productionRow, '');
            $productionSheet->setCellValue('R' . $productionRow, '');
            $productionSheet->setCellValue('S' . $productionRow, '');
            // Corner details (transom에는 비워둠)
            $productionSheet->setCellValue('T' . $productionRow, '');
            $productionSheet->setCellValue('U' . $productionRow, '');
            $productionSheet->setCellValue('V' . $productionRow, '');
            $productionSheet->setCellValue('W' . $productionRow, '');
            // TR 블록: 헤더(X~AE)에 맞춰 정확히 매핑 (TR 세로 제거됨)
            error_log("=== TR 데이터 설정 시작 (패널 데이터 없는 경우) ===");
            error_log("transomData['width']: " . ($transomData['width'] ?? 'NULL'));
            error_log("transomData['transomPlateHeight']: " . ($transomData['transomPlateHeight'] ?? 'NULL'));
            error_log("transomData['bottomDepthJD']: " . ($transomData['bottomDepthJD'] ?? 'NULL'));
            error_log("transomData['wingValue']: " . ($transomData['wingValue'] ?? 'NULL'));
            error_log("transomData['cpiDrillingWidth']: " . ($transomData['cpiDrillingWidth'] ?? 'NULL'));
            error_log("transomData['cpiDrillingHeight']: " . ($transomData['cpiDrillingHeight'] ?? 'NULL'));
            error_log("transomData['cpiDrillingHeightFromBottom']: " . ($transomData['cpiDrillingHeightFromBottom'] ?? 'NULL'));
            error_log("transomData['notes']: " . ($transomData['notes'] ?? 'NULL'));
            
            $productionSheet->setCellValue('X' . $productionRow, ($transomData['width'] ?? 0) == 0 ? '' : ($transomData['width'] ?? ''));
            $productionSheet->setCellValue('Y' . $productionRow, ($transomData['transomPlateHeight'] ?? 0) == 0 ? '' : ($transomData['transomPlateHeight'] ?? ''));
            $productionSheet->setCellValue('Z' . $productionRow, ($transomData['bottomDepthJD'] ?? 0) == 0 ? '' : ($transomData['bottomDepthJD'] ?? ''));
            $productionSheet->setCellValue('AA' . $productionRow, ($transomData['wingValue'] ?? 0) == 0 ? '' : ($transomData['wingValue'] ?? ''));
            $productionSheet->setCellValue('AB' . $productionRow, ($transomData['cpiDrillingWidth'] ?? 0) == 0 ? '' : ($transomData['cpiDrillingWidth'] ?? ''));
            $productionSheet->setCellValue('AC' . $productionRow, ($transomData['cpiDrillingHeight'] ?? 0) == 0 ? '' : ($transomData['cpiDrillingHeight'] ?? ''));
            $productionSheet->setCellValue('AD' . $productionRow, ($transomData['cpiDrillingHeightFromBottom'] ?? 0) == 0 ? '' : ($transomData['cpiDrillingHeightFromBottom'] ?? ''));
            $productionSheet->setCellValue('AE' . $productionRow, $transomData['notes'] ?? '');
            $productionSheet->setCellValue('AF' . $productionRow, '');
            $productionRow++;
        }
    }

    // 제작산출결과 데이터 스타일
    if ($productionRow > 2) {
        $productionSheet->getStyle('A2:AF' . ($productionRow - 1))->applyFromArray($dataStyle);
    }

    // 제작산출결과 시트 컬럼 너비 설정 (export_measurements.php와 동일)
    $defaultDetailWidth = 14;
    foreach (range('A','Z') as $columnID) {
        $productionSheet->getColumnDimension($columnID)->setAutoSize(false);
        $productionSheet->getColumnDimension($columnID)->setWidth($defaultDetailWidth);
    }
    // AA-AF 컬럼도 설정
    foreach (['AA', 'AB', 'AC', 'AD', 'AE', 'AF'] as $columnID) {
        $productionSheet->getColumnDimension($columnID)->setAutoSize(false);
        $productionSheet->getColumnDimension($columnID)->setWidth($defaultDetailWidth);
    }
    // 긴 텍스트/헤더 열은 더 넓게 지정
    $detailWideColumns = [
        'A' => 5,  // 번호
        'B' => 8,  // 문서 고유번호
        'C' => 30,  // 현장명
        'D' => 12,  // 측정일자
        'E' => 12,  // 측정자
        'K' => 12,  // 패널 번호
        'L' => 12,  // 제작 대수
        'M' => 15,  // 패널 타입
        'N' => 14,  // 제작폭
        'O' => 14,  // 제작높이
        'P' => 14,  // 타공 가로
        'Q' => 14,  // 타공 세로
        'R' => 18,  // 타공 높이(밑기준)
        'S' => 18,  // 출입구방향에서 떨어짐
        'AC' => 18, // Transom CPI타공 가로
        'AD' => 18, // Transom CPI타공 세로
        'AE' => 18, // Transom CPI타공높이
        'AF' => 30  // 패널 특이사항
    ];
    foreach ($detailWideColumns as $col => $width) {
        $productionSheet->getColumnDimension($col)->setWidth($width);
    }

    // === 세 번째 시트: 몰딩 정보 ===
    // 몰딩 시트는 항상 생성
    $moldingSheet = $objPHPExcel->createSheet();
    $moldingSheet->setTitle('Molding');

    // 몰딩 데이터 계산
    $productionHeight = intval($production_height);
    $carWidth = intval($selected_data['car_inside_width']);
    $carDepth = intval($selected_data['car_inside_depth']);
    $elevatorCount = intval($elevator_count);

    $moldingData = [
            [
                'type' => '엔딩몰딩',
                'size' => $productionHeight,
                'count' => 2,
                'elevatorCount' => $elevatorCount,
                'totalCount' => 2 * $elevatorCount,
                'description' => '2번, 10번 패널용'
            ],
            [
                'type' => '센터몰딩',
                'size' => $productionHeight,
                'count' => 6,
                'elevatorCount' => $elevatorCount,
                'totalCount' => 6 * $elevatorCount,
                'description' => '3번, 4번, 7번, 8번, 9번 패널용'
            ],
            [
                'type' => '코너몰딩',
                'size' => $productionHeight,
                'count' => 2,
                'elevatorCount' => $elevatorCount,
                'totalCount' => 2 * $elevatorCount,
                'description' => '5번, 6번 패널용'
            ],
            [
                'type' => 'S엔딩몰딩',
                'size' => $carDepth - 5,
                'count' => 2,
                'elevatorCount' => $elevatorCount,
                'totalCount' => 2 * $elevatorCount,
                'description' => '측면 하부 가로'
            ],
            [
                'type' => 'R엔딩몰딩',
                'size' => $carWidth - 2,
                'count' => 1,
                'elevatorCount' => $elevatorCount,
                'totalCount' => 1 * $elevatorCount,
                'description' => '후면 하부 가로'
            ]
    ];

    // 몰딩 시트 헤더
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

    // 몰딩 데이터 작성
    $moldingRow = 2;
    foreach ($moldingData as $molding) {
        $moldingSheet->setCellValue('A' . $moldingRow, $molding['type']);
        $moldingSheet->setCellValue('B' . $moldingRow, $molding['size']);
        $moldingSheet->setCellValue('C' . $moldingRow, $molding['count']);
        $moldingSheet->setCellValue('D' . $moldingRow, $molding['elevatorCount']);
        $moldingSheet->setCellValue('E' . $moldingRow, $molding['totalCount']);
        $moldingSheet->setCellValue('F' . $moldingRow, $molding['description']);
        $moldingRow++;
    }

    // 몰딩 데이터 스타일
    $moldingSheet->getStyle('A2:F' . ($moldingRow - 1))->applyFromArray($dataStyle);

    // 몰딩 시트 컬럼 너비 설정
    // A열~E열은 30% 축소, F열은 자동 조정
    $moldingSheet->getColumnDimension('A')->setWidth(21); // 몰딩 종류 (30% 축소)
    $moldingSheet->getColumnDimension('B')->setWidth(17); // 절단치수 (30% 축소)
    $moldingSheet->getColumnDimension('C')->setWidth(14); // 개수 (30% 축소)
    $moldingSheet->getColumnDimension('D')->setWidth(11); // 대수 (30% 축소)
    $moldingSheet->getColumnDimension('E')->setWidth(14); // 총개수 (30% 축소)
    $moldingSheet->getColumnDimension('F')->setAutoSize(true); // 설명 (자동 조정)

    // 첫 번째 시트를 활성화
    $objPHPExcel->setActiveSheetIndex(0);

    // 파일명 생성 (영어로 변경하여 브라우저 호환성 개선)
    $site_name = $selected_data['site_name'];
    
    // 한글 현장명을 영어로 변환하거나 간소화
    $site_name_mapping = [
        '아이파크_음성#15(장애인용)' => 'iPark_Voice_15_Disabled',
        '아이파크_음성#1~#14' => 'iPark_Voice_1_14',
        '아이파크' => 'iPark',
        '음성' => 'Voice',
        '장애인용' => 'Disabled',
        '#' => '_',
        '~' => '_to_',
        '(' => '_',
        ')' => '',
        ' ' => '_'
    ];
    
    // 현장명 변환
    foreach ($site_name_mapping as $korean => $english) {
        $site_name = str_replace($korean, $english, $site_name);
    }
    
    // 연속된 언더스코어를 하나로 합치기
    $site_name = preg_replace('/_+/', '_', $site_name);
    
    // 앞뒤 언더스코어 제거
    $site_name = trim($site_name, '_');
    
    // 최종 파일명 생성
    $filename = 'Production_Results_' . $site_name . '_' . date('Y-m-d', strtotime($selected_data['measurement_date'])) . '.xlsx';
    
    // 안전한 문자만 남기기 (연속 언더스코어는 이미 처리됨)
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    
    // 디버깅: 파일명과 헤더 정보
    error_log("생성된 파일명: " . $filename);
    error_log("현재 출력 버퍼 레벨: " . ob_get_level());
    error_log("현재 출력 버퍼 내용 길이: " . strlen(ob_get_contents()));

    // 로컬 환경에서 추가 안전장치
    if (ob_get_level()) {
        ob_end_clean();
        error_log("출력 버퍼 정리 완료");
    }
    
    // 브라우저 호환성을 위한 최종 헤더 설정
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

    // 디버깅: Writer 생성 및 저장
    error_log("PHPExcel Writer 생성 시도...");
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    error_log("PHPExcel Writer 생성 성공!");
    
    // 파일 크기 먼저 계산
    $temp_file = tempnam(sys_get_temp_dir(), 'excel_production_');
    $objWriter->save($temp_file);
    $file_size = filesize($temp_file);
    error_log("생성된 Excel 파일 크기: " . $file_size . " bytes");
    error_log("임시 파일 경로: " . $temp_file);

    // 파일 크기가 유효한지 확인
    if ($file_size === false || $file_size < 1000) {
        error_log("경고: 파일 크기가 너무 작습니다. " . $file_size);
        $file_size = 10240; // 기본값 설정
    }

    // Content-Length 헤더 추가
    header('Content-Length: ' . $file_size);
    error_log("Content-Length 헤더 설정: " . $file_size);

    // 로컬 환경에서 추가 안전장치
    if (ob_get_level()) {
        ob_end_clean();
    }

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

} catch (Exception $e) {
    error_log("Excel export error: " . $e->getMessage());
    die('Excel 파일 생성 중 오류가 발생했습니다: ' . $e->getMessage());
}
?>