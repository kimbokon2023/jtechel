<?php
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
$group_name = $_POST['group_name'] ?? '그룹';

if (empty($measurements_json)) {
    die('측정 데이터가 없습니다.');
}

$measurements = json_decode($measurements_json, true);

if (!$measurements || !is_array($measurements)) {
    die('유효하지 않은 측정 데이터입니다.');
}

/**
 * 현장명들의 공통 부분을 추출하는 함수
 */
function extractCommonSiteName($measurements) {
    if (empty($measurements)) {
        return '현장';
    }
    
    if (count($measurements) === 1) {
        return $measurements[0]['site_name'] ?? '현장';
    }
    
    // 모든 현장명 추출
    $site_names = array_map(function($m) {
        return $m['site_name'] ?? '';
    }, $measurements);
    
    // 빈 값 제거
    $site_names = array_filter($site_names);
    
    if (empty($site_names)) {
        return '현장';
    }
    
    // 가장 짧은 이름을 기준으로
    $shortest = min(array_map('mb_strlen', $site_names));
    
    // 공통 접두사 찾기
    $common = '';
    for ($i = 0; $i < $shortest; $i++) {
        $char = mb_substr($site_names[0], $i, 1);
        $is_common = true;
        
        foreach ($site_names as $name) {
            if (mb_substr($name, $i, 1) !== $char) {
                $is_common = false;
                break;
            }
        }
        
        if ($is_common) {
            $common .= $char;
        } else {
            break;
        }
    }
    
    // 공통 부분이 너무 짧으면 다른 방법 시도
    if (mb_strlen($common) < 2) {
        // 숫자나 특수문자 제거 후 공통 단어 찾기
        $cleaned_names = array_map(function($name) {
            // 숫자 제거
            $name = preg_replace('/\d+/', '', $name);
            // 특수문자 제거 (공백, 하이픈, 언더스코어는 유지)
            $name = preg_replace('/[^\w\s가-힣-]/u', '', $name);
            return trim($name);
        }, $site_names);
        
        // 공통 접두사 재시도
        $shortest = min(array_map('mb_strlen', $cleaned_names));
        $common = '';
        for ($i = 0; $i < $shortest; $i++) {
            $char = mb_substr($cleaned_names[0], $i, 1);
            $is_common = true;
            
            foreach ($cleaned_names as $name) {
                if (mb_substr($name, $i, 1) !== $char) {
                    $is_common = false;
                    break;
                }
            }
            
            if ($is_common) {
                $common .= $char;
            } else {
                break;
            }
        }
    }
    
    // 공통 부분 정리
    $common = trim($common);
    
    // 마지막 공백, 하이픈, 언더스코어 제거
    $common = rtrim($common, ' -_');
    
    // 여전히 너무 짧으면 첫 번째 현장명 사용
    if (mb_strlen($common) < 2) {
        $common = $site_names[0];
        // 너무 길면 잘라내기
        if (mb_strlen($common) > 20) {
            $common = mb_substr($common, 0, 20);
        }
    }
    
    return $common ?: '현장';
}

// 디버그: 받은 측정 데이터의 elevator_count 확인
error_log("=== export_group_production_data.php 시작 ===");
error_log("받은 측정 데이터 개수: " . count($measurements));
foreach ($measurements as $index => $measurement) {
    error_log("측정 데이터 {$index}: 현장명 = {$measurement['site_name']}, elevator_count = " . ($measurement['elevator_count'] ?? 'NULL'));
}

// Initialize database connection
try {
    $pdo = db_connect();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("USE $DB");
} catch (PDOException $e) {
    error_log("Database connection failed in export_group_production_data.php: " . $e->getMessage());
    die("데이터베이스 연결에 실패했습니다.");
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
        ->setTitle("그룹 제작산출 결과 - " . count($measurements) . "개 현장")
            ->setSubject("Group Panel Measurement Production Results")
            ->setDescription("Group panel measurement production results exported from J-TECH system")
        ->setKeywords("group panel measurement production results excel export")
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
        'I1' => '아이파크 체크',
        'J1' => '재질두께(mm)',
        'K1' => '프로젝트 타입',
        'L1' => '1,11번 제외',
        'M1' => '트랜섬 제외',
        'N1' => '몰딩 포함',
        'O1' => '엘리베이터 대수',
        'P1' => '제작 높이(mm)',
        'Q1' => '1,11번 높이(mm)',
        'R1' => '총 패널 수',
        'S1' => '특이사항'
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

    $sheet->getStyle('A1:S1')->applyFromArray($headerStyle);

    // 데이터 행 작성
    $row = 2;

    foreach ($measurements as $measurement) {
        // 패널 데이터 파싱
        $panel_data = [];
        if (!empty($measurement['panel_data'])) {
            $panel_data = json_decode($measurement['panel_data'], true) ?: [];
        }

        // 제작 설정
        $production_settings = [
            'production_height' => $measurement['production_height'] ?? $measurement['car_inside_height'],
            'production_height1_11' => $measurement['production_height1_11'] ?? $measurement['car_inside_height'],
            'panel_corners_excluded' => $measurement['panel_corners_excluded'] ?? 1,
            'transom_excluded' => $measurement['transom_excluded'] ?? 0,
            'molding_included' => $measurement['molding_included'] ?? 0
        ];

        // 제작 패널 데이터 생성
        $make_panel_data = generateMakePanelData($panel_data, $production_settings);
        
        // 제작 조건 설정 값들 처리
        $project_type = $measurement['project_type'] ?? '신규';
        $panel_corners_excluded_display = isset($measurement['panel_corners_excluded']) ? ($measurement['panel_corners_excluded'] ? 'Y' : 'N') : 'Y';
        $transom_excluded = isset($measurement['transom_excluded']) ? ($measurement['transom_excluded'] ? 'Y' : 'N') : 'N';
        $molding_included_display = isset($measurement['molding_included']) ? ($measurement['molding_included'] ? 'Y' : 'N') : 'N';
        // elevator_count 처리 (NULL인 경우 기본값 1 사용)
        $raw_elevator_count = $measurement['elevator_count'] ?? null;
        $current_elevator_count = is_null($raw_elevator_count) ? 1 : intval($raw_elevator_count);
        $production_height = $measurement['production_height'] ?? $measurement['car_inside_height'];
        $production_height1_11 = $measurement['production_height1_11'] ?? $measurement['car_inside_height'];

        // 디버그: 각 측정 데이터의 elevator_count 확인
        error_log("=== export_group_production_data.php elevator_count 디버그 ===");
        error_log("현장명: " . $measurement['site_name']);
        error_log("measurement['elevator_count']: " . ($raw_elevator_count ?? 'NULL'));
        error_log("최종 elevator_count: " . $current_elevator_count);

        // 아이파크 체크 여부 확인
        $ipark_check = 'N';
        if (isset($measurement['site_name']) && strpos($measurement['site_name'], '아이파크') !== false) {
            $ipark_check = 'Y';
        }

        $data = [
            'A' => $measurement['id'],
            'B' => $measurement['site_name'],
            'C' => date('Y-m-d', strtotime($measurement['measurement_date'])),
            'D' => $measurement['measurer_name'],
            'E' => $measurement['car_inside_width'],
            'F' => $measurement['car_inside_depth'],
            'G' => $measurement['car_inside_height'],
            'H' => $measurement['material_type'] ?? '',
            'I' => $ipark_check,
            'J' => $measurement['material_thickness'] ?? '',
            'K' => $project_type,
            'L' => $panel_corners_excluded_display,
            'M' => $transom_excluded,
            'N' => $molding_included_display,
            'O' => $current_elevator_count,
            'P' => $production_height,
            'Q' => $production_height1_11,
            'R' => count($panel_data),
            'S' => $measurement['notes'] ?? ''
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

    $sheet->getStyle('A2:S' . ($row - 1))->applyFromArray($dataStyle);

    // 컬럼 너비 설정
    $sheet->getColumnDimension('B')->setWidth(40); // 현장명
    $sheet->getColumnDimension('D')->setWidth(12); // 측정자
    $sheet->getColumnDimension('S')->setWidth(40); // 특이사항
    $sheet->getColumnDimension('E')->setWidth(24); // 카 내부 가로
    $sheet->getColumnDimension('F')->setWidth(24); // 카 내부 깊이
    $sheet->getColumnDimension('G')->setWidth(24); // 카 내부 높이
    
    // H열~R열까지 너비 20으로 설정
    $wideCols = ['H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R'];
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

    // 제작산출결과 시트 헤더
    $productionHeaders = [
        'A1' => '번호',
        'B1' => '고유번호',
        'C1' => '현장명',
        'D1' => '측정일자',
        'E1' => '측정자',
        'F1' => '카 내부 W',
        'G1' => '카 내부 D',
        'H1' => '카 내부 H',
        'I1' => '아이파크 체크',
        'J1' => '의장재질',
        'K1' => '재질 두께',
        'L1' => '패널 번호',
        'M1' => '제작 대수',
        'N1' => '패널 타입',
        'O1' => '제작폭',
        'P1' => '제작높이',
        'Q1' => '타공 가로',
        'R1' => '타공 세로',
        'S1' => '타공 높이(밑기준)',
        'T1' => '출입구방향에서 떨어짐',
        'U1' => '1,11전면 두께',
        'V1' => '1,11전면 날개',
        'W1' => '1,11후면 두께',
        'X1' => '1,11후면 날개',
        'Y1' => 'TR 가로',
        'Z1' => 'TR 막판높이',
        'AA1' => 'TR 밑면깊이JD',
        'AB1' => 'TR 날개값',
        'AC1' => 'TR CPI타공 가로',
        'AD1' => 'TR CPI타공 세로',
        'AE1' => 'TR CPI타공높이',
        'AF1' => 'TR 비고',
        'AG1' => '패널 특이사항'
    ];

    foreach ($productionHeaders as $cell => $value) {
        $productionSheet->setCellValue($cell, $value);
    }

    // 제작산출결과 헤더 스타일
    $productionSheet->getStyle('A1:AG1')->applyFromArray($headerStyle);

    // 패널 데이터 작성
    $productionRow = 2;
    $rowNum = 1;

    foreach ($measurements as $measurement) {
        // 패널 데이터 파싱
        $panel_data = [];
        if (!empty($measurement['panel_data'])) {
            $panel_data = json_decode($measurement['panel_data'], true) ?: [];
        }

        // elevator_count 처리 (패널 데이터 처리용)
        $raw_elevator_count = $measurement['elevator_count'] ?? null;
        $current_elevator_count = is_null($raw_elevator_count) ? 1 : intval($raw_elevator_count);
        
        // 디버그: 패널 데이터 처리 루프에서 elevator_count 확인
        error_log("패널 데이터 처리 루프 - 현장: {$measurement['site_name']}, elevator_count: {$current_elevator_count}");

        // 제작 설정
        $production_settings = [
            'production_height' => $measurement['production_height'] ?? $measurement['car_inside_height'],
            'production_height1_11' => $measurement['production_height1_11'] ?? $measurement['car_inside_height'],
            'panel_corners_excluded' => $measurement['panel_corners_excluded'] ?? 1,
            'transom_excluded' => $measurement['transom_excluded'] ?? 0,
            'molding_included' => $measurement['molding_included'] ?? 0
        ];

        // 제작 패널 데이터 생성
        $make_panel_data = generateMakePanelData($panel_data, $production_settings);
        $display_panel_data = !empty($make_panel_data) ? $make_panel_data : $panel_data;

        // Transom 데이터 파싱
        $transomData = [];
        if (!empty($measurement['transom_data'])) {
            $transom_data = json_decode($measurement['transom_data'], true);
            if ($transom_data && is_array($transom_data)) {
                if (isset($transom_data['transom'])) {
                    $transomData = $transom_data['transom'];
                } elseif (isset($transom_data['12'])) {
                    $transomData = $transom_data['12'];
                } else {
                    foreach ($transom_data as $key => $value) {
                        if (is_array($value) && !empty($value)) {
                            $transomData = $value;
                            break;
                        }
                    }
                }
            }
        }

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
                $productionSheet->setCellValue('I' . $productionRow, $ipark_check);
                $productionSheet->setCellValue('J' . $productionRow, $measurement['material_type']);
                $productionSheet->setCellValue('K' . $productionRow, $measurement['material_thickness'] == 0 ? '' : $measurement['material_thickness']);

                // 패널 정보
                $productionSheet->setCellValue('L' . $productionRow, $panel_num);
                $productionSheet->setCellValue('M' . $productionRow, $current_elevator_count);
                
                // 디버그: 패널별 elevator_count 확인
                error_log("패널 데이터 처리 - 현장: {$measurement['site_name']}, 패널: {$panel_num}, elevator_count: {$current_elevator_count}");
                
                $panelIndex = is_numeric($panel_num) ? (int)$panel_num : (int)preg_replace('/\D+/', '', (string)$panel_num);
                $productionSheet->setCellValue('N' . $productionRow, ($panelIndex === 1 || $panelIndex === 11) ? ($panel_info['panel_type_detail'] ?? '') : '');

                // 제작사이즈 적용
                $width = $panel_info['width'] ?? 0;

                // 몰딩포함 시 패널별 width 차감 적용
                if ($production_settings['molding_included'] && is_numeric($panel_num) && $panel_num >= 2 && $panel_num <= 10) {
                    $molding_deduction = 0;
                    $panel_number = intval($panel_num);

                    if ($panel_number === 2 || $panel_number === 10) {
                        $molding_deduction = 5;
                    } elseif ($panel_number === 3 || $panel_number === 6 || $panel_number === 9) {
                        $molding_deduction = 4;
                    } elseif ($panel_number === 4 || $panel_number === 5 || $panel_number === 7 || $panel_number === 8) {
                        $molding_deduction = 10;
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
                $productionSheet->setCellValue('O' . $productionRow, $width == 0 ? '' : $width);
                $productionSheet->setCellValue('P' . $productionRow, $height == 0 ? '' : $height);

                // 타공 정보 추출 (다양한 형태의 데이터 구조 지원)
                $hole_width = '';
                $hole_height = '';
                $hole_floor_height = '';
                $hole_entrance_distance = '';

                // 아이파크 프로젝트 확인
                $is_ipark_project = false;
                if (isset($measurement['site_name']) && strpos($measurement['site_name'], '아이파크') !== false) {
                    $is_ipark_project = true;
                    error_log("아이파크 프로젝트 감지됨: " . $measurement['site_name']);
                }

                // 1. drilling_ 접두사 속성이 있는 경우 (실제 데이터 구조)
                if (isset($panel_info['drilling_width']) || isset($panel_info['drilling_height'])) {
                    if (isset($panel_info['drilling_width']) && isset($panel_info['drilling_height'])) {
                        $hole_width = $panel_info['drilling_width'];
                        $hole_height = $panel_info['drilling_height'];
                        
                        // 아이파크 프로젝트인 경우 제작치수 적용
                        if ($is_ipark_project) {
                            $original_width = $hole_width;
                            $original_height = $hole_height;
                            $hole_width = $hole_width - 70;  // 제작가로 = 실측가로 - 70
                            $hole_height = $hole_height - 13; // 제작세로 = 실측세로 - 13
                            error_log("아이파크 제작치수 적용 - 패널 {$panel_num}: 가로 {$original_width}→{$hole_width}, 세로 {$original_height}→{$hole_height}");
                        }
                    }
                    if (isset($panel_info['drilling_from_floor'])) {
                        $hole_floor_height = $panel_info['drilling_from_floor'];
                        
                        // 아이파크 프로젝트인 경우 제작치수 적용
                        if ($is_ipark_project) {
                            $original_floor = $hole_floor_height;
                            $hole_floor_height = $hole_floor_height + 6; // 제작바닥높이 = 실측바닥높이 + 6
                            error_log("아이파크 제작치수 적용 - 패널 {$panel_num}: 바닥높이 {$original_floor}→{$hole_floor_height}");
                        }
                    }
                    if (isset($panel_info['drilling_from_entrance'])) {
                        $hole_entrance_distance = $panel_info['drilling_from_entrance'];
                        
                        // 아이파크 프로젝트인 경우 제작치수 적용
                        if ($is_ipark_project && isset($panel_info['width'])) {
                            $original_entrance = $hole_entrance_distance;
                            $production_width = $panel_info['width']; // 제작패널전체폭
                            $production_drilling_width = $hole_width; // 제작가로크기확정
                            $hole_entrance_distance = ($production_width - $production_drilling_width) / 2; // 제작출입구위치
                            error_log("아이파크 제작치수 적용 - 패널 {$panel_num}: 출입구거리 {$original_entrance}→{$hole_entrance_distance} (패널폭:{$production_width}, 제작가로:{$production_drilling_width})");
                        }
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

                // 타공 정보 설정 (올바른 열에 각각의 값 설정)
                $productionSheet->setCellValue('Q' . $productionRow, $hole_width);  // 타공 가로
                $productionSheet->setCellValue('R' . $productionRow, $hole_height); // 타공 세로
                $productionSheet->setCellValue('S' . $productionRow, $hole_floor_height); // 타공 높이(밑기준)
                $productionSheet->setCellValue('T' . $productionRow, $hole_entrance_distance); // 입구방향에서 떨어짐

                // Corner details for 1,11
                $productionSheet->setCellValue('U' . $productionRow, ($panel_info['frontThickness'] ?? 0) == 0 ? '' : ($panel_info['frontThickness'] ?? ''));
                $productionSheet->setCellValue('V' . $productionRow, ($panel_info['frontWing'] ?? 0) == 0 ? '' : ($panel_info['frontWing'] ?? ''));
                $productionSheet->setCellValue('W' . $productionRow, ($panel_info['backThickness'] ?? 0) == 0 ? '' : ($panel_info['backThickness'] ?? ''));
                $productionSheet->setCellValue('X' . $productionRow, ($panel_info['backWing'] ?? 0) == 0 ? '' : ($panel_info['backWing'] ?? ''));

                // 1~11번 패널은 TR 관련 컬럼을 비워둠
                $currentPanelNum = is_numeric($panel_num) ? (int)$panel_num : (int)preg_replace('/\D+/', '', (string)$panel_num);
                if ($currentPanelNum >= 1 && $currentPanelNum <= 11) {
                    foreach (['Y','Z','AA','AB','AC','AD','AE'] as $col) {
                        $productionSheet->setCellValue($col . $productionRow, '');
                    }
                } else {
                    foreach (['Y','Z','AA','AB','AC','AD','AE'] as $col) {
                        $productionSheet->setCellValue($col . $productionRow, '');
                    }
                }

                $productionSheet->setCellValue('AF' . $productionRow, $panel_info['specialNotes'] ?? '');
                $productionRow++;
                $rowNum++;
            }

            // Transom 데이터가 있으면 별도 행 추가
            if (!empty($transomData)) {
                $productionSheet->setCellValue('A' . $productionRow, $rowNum);
                $productionSheet->setCellValue('B' . $productionRow, $measurement['id']);
                $productionSheet->setCellValue('C' . $productionRow, $measurement['site_name']);
                $productionSheet->setCellValue('D' . $productionRow, date('Y-m-d', strtotime($measurement['measurement_date'])));
                $productionSheet->setCellValue('E' . $productionRow, $measurement['measurer_name']);
                $productionSheet->setCellValue('F' . $productionRow, $measurement['car_inside_width'] == 0 ? '' : $measurement['car_inside_width']);
                $productionSheet->setCellValue('G' . $productionRow, $measurement['car_inside_depth'] == 0 ? '' : $measurement['car_inside_depth']);
                $productionSheet->setCellValue('H' . $productionRow, $measurement['car_inside_height'] == 0 ? '' : $measurement['car_inside_height']);
                $productionSheet->setCellValue('I' . $productionRow, $ipark_check);
                $productionSheet->setCellValue('J' . $productionRow, $measurement['material_type']);
                $productionSheet->setCellValue('K' . $productionRow, $measurement['material_thickness'] == 0 ? '' : $measurement['material_thickness']);
                $productionSheet->setCellValue('L' . $productionRow, 'transom');
                $productionSheet->setCellValue('M' . $productionRow, $current_elevator_count);
                
                // 디버그: Transom elevator_count 확인
                error_log("Transom 데이터 처리 - 현장: {$measurement['site_name']}, elevator_count: {$current_elevator_count}");
                
                $productionSheet->setCellValue('N' . $productionRow, '');
                $productionSheet->setCellValue('O' . $productionRow, ($transomData['width'] ?? 0) == 0 ? '' : ($transomData['width'] ?? ''));
                $productionSheet->setCellValue('P' . $productionRow, ($transomData['height'] ?? 0) == 0 ? '' : ($transomData['height'] ?? ''));
                
                // Drilling fields (Transom에는 비워둠)
                $productionSheet->setCellValue('Q' . $productionRow, '');
                $productionSheet->setCellValue('R' . $productionRow, '');
                $productionSheet->setCellValue('S' . $productionRow, '');
                $productionSheet->setCellValue('T' . $productionRow, '');
                
                // Corner details (Transom에는 비워둠)
                $productionSheet->setCellValue('U' . $productionRow, '');
                $productionSheet->setCellValue('U' . $productionRow, '');
                $productionSheet->setCellValue('V' . $productionRow, '');
                $productionSheet->setCellValue('W' . $productionRow, '');
                
                // TR 블록
                $productionSheet->setCellValue('V' . $productionRow, '');
                $productionSheet->setCellValue('W' . $productionRow, '');
                $productionSheet->setCellValue('X' . $productionRow, '');
                $productionSheet->setCellValue('Y' . $productionRow, ($transomData['width'] ?? 0) == 0 ? '' : ($transomData['width'] ?? ''));
                $productionSheet->setCellValue('Z' . $productionRow, ($transomData['transomPlateHeight'] ?? 0) == 0 ? '' : ($transomData['transomPlateHeight'] ?? ''));
                $productionSheet->setCellValue('AA' . $productionRow, ($transomData['bottomDepthJD'] ?? 0) == 0 ? '' : ($transomData['bottomDepthJD'] ?? ''));
                $productionSheet->setCellValue('AB' . $productionRow, ($transomData['wingValue'] ?? 0) == 0 ? '' : ($transomData['wingValue'] ?? ''));
                $productionSheet->setCellValue('AC' . $productionRow, ($transomData['cpiDrillingWidth'] ?? 0) == 0 ? '' : ($transomData['cpiDrillingWidth'] ?? ''));
                $productionSheet->setCellValue('AD' . $productionRow, ($transomData['cpiDrillingHeight'] ?? 0) == 0 ? '' : ($transomData['cpiDrillingHeight'] ?? ''));
                $productionSheet->setCellValue('AE' . $productionRow, ($transomData['cpiDrillingHeightFromBottom'] ?? 0) == 0 ? '' : ($transomData['cpiDrillingHeightFromBottom'] ?? ''));
                $productionSheet->setCellValue('AF' . $productionRow, $transomData['notes'] ?? '');
                $productionSheet->setCellValue('AG' . $productionRow, '');
                $productionRow++;
                $rowNum++;
            }
        }
    }

    // 제작산출결과 데이터 스타일
    if ($productionRow > 2) {
        $productionSheet->getStyle('A2:AG' . ($productionRow - 1))->applyFromArray($dataStyle);
    }

    // 제작산출결과 시트 컬럼 너비 설정
    $defaultDetailWidth = 14;
    foreach (range('A','Z') as $columnID) {
        $productionSheet->getColumnDimension($columnID)->setAutoSize(false);
        $productionSheet->getColumnDimension($columnID)->setWidth($defaultDetailWidth);
    }
    foreach (['AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG'] as $columnID) {
        $productionSheet->getColumnDimension($columnID)->setAutoSize(false);
        $productionSheet->getColumnDimension($columnID)->setWidth($defaultDetailWidth);
    }

    // 긴 텍스트/헤더 열은 더 넓게 지정
    $detailWideColumns = [
        'A' => 5, 'B' => 8, 'C' => 30, 'D' => 12, 'E' => 12, 'I' => 12, 'L' => 12, 'M' => 12,
        'N' => 15, 'O' => 14, 'P' => 14, 'Q' => 14, 'R' => 14, 'S' => 18, 'T' => 18,
        'AD' => 18, 'AE' => 18, 'AF' => 18, 'AG' => 30
    ];
    foreach ($detailWideColumns as $col => $width) {
        $productionSheet->getColumnDimension($col)->setWidth($width);
    }

    // === 세 번째 시트: 몰딩 정보 (그룹 병합) ===
    $moldingSheet = $objPHPExcel->createSheet();
    $moldingSheet->setTitle('Molding');

    // 그룹 전체의 몰딩 데이터 병합
    $mergedMoldingData = [];

    foreach ($measurements as $measurement) {
        $productionHeight = intval($measurement['production_height'] ?? $measurement['car_inside_height']);
        $carWidth = intval($measurement['car_inside_width']);
        $carDepth = intval($measurement['car_inside_depth']);
        
        // 몰딩 시트용 elevator_count 처리
        $raw_elevator_count = $measurement['elevator_count'] ?? null;
        $elevatorCount = is_null($raw_elevator_count) ? 1 : intval($raw_elevator_count);
        
        // 디버그: 몰딩 시트 elevator_count 확인
        error_log("몰딩 시트 처리 - 현장: {$measurement['site_name']}, elevator_count: {$elevatorCount}");

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

        // 몰딩 데이터 병합
        foreach ($moldingData as $molding) {
            $key = $molding['type'] . '_' . $molding['size'];
            if (isset($mergedMoldingData[$key])) {
                $mergedMoldingData[$key]['totalCount'] += $molding['totalCount'];
                $mergedMoldingData[$key]['elevatorCount'] += $molding['elevatorCount'];
            } else {
                $mergedMoldingData[$key] = $molding;
            }
        }
    }

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

    // 몰딩 데이터를 종류별로 그룹핑하여 정렬
    $groupedMoldingData = [];
    foreach ($mergedMoldingData as $molding) {
        $groupedMoldingData[$molding['type']][] = $molding;
    }
    
    // 몰딩 종류별로 정렬 (엔딩몰딩, 센터몰딩, 코너몰딩, S엔딩몰딩, R엔딩몰딩 순서)
    $moldingTypeOrder = ['엔딩몰딩', '센터몰딩', '코너몰딩', 'S엔딩몰딩', 'R엔딩몰딩'];
    
    // 몰딩 데이터 작성 (종류별로 그룹핑하여 출력)
    $moldingRow = 2;
    foreach ($moldingTypeOrder as $type) {
        if (isset($groupedMoldingData[$type])) {
            // 같은 종류의 몰딩들을 크기순으로 정렬
            usort($groupedMoldingData[$type], function($a, $b) {
                return $a['size'] - $b['size'];
            });
            
            // 같은 종류의 몰딩들을 연속으로 출력
            foreach ($groupedMoldingData[$type] as $molding) {
                $moldingSheet->setCellValue('A' . $moldingRow, $molding['type']);
                $moldingSheet->setCellValue('B' . $moldingRow, $molding['size']);
                $moldingSheet->setCellValue('C' . $moldingRow, $molding['count']);
                $moldingSheet->setCellValue('D' . $moldingRow, $molding['elevatorCount']);
                $moldingSheet->setCellValue('E' . $moldingRow, $molding['totalCount']);
                $moldingSheet->setCellValue('F' . $moldingRow, $molding['description']);
                $moldingRow++;
            }
        }
    }

    // 몰딩 데이터 스타일
    $moldingSheet->getStyle('A2:F' . ($moldingRow - 1))->applyFromArray($dataStyle);

    // 몰딩 시트 컬럼 너비 설정
    $moldingSheet->getColumnDimension('A')->setWidth(21);
    $moldingSheet->getColumnDimension('B')->setWidth(17);
    $moldingSheet->getColumnDimension('C')->setWidth(14);
    $moldingSheet->getColumnDimension('D')->setWidth(11);
    $moldingSheet->getColumnDimension('E')->setWidth(14);
    $moldingSheet->getColumnDimension('F')->setAutoSize(true);

    // 첫 번째 시트를 활성화
    $objPHPExcel->setActiveSheetIndex(0);

    // 파일명 생성 - 현장명공통부분_그룹명_일자 형태
    $common_site_name = extractCommonSiteName($measurements);
    $safe_group_name = preg_replace('/[^가-힣a-zA-Z0-9\s]/', '', $group_name);
    $safe_group_name = trim($safe_group_name);
    
    // 파일명 길이 제한 (너무 길면 잘라내기)
    if (mb_strlen($common_site_name) > 30) {
        $common_site_name = mb_substr($common_site_name, 0, 30);
    }
    if (mb_strlen($safe_group_name) > 20) {
        $safe_group_name = mb_substr($safe_group_name, 0, 20);
    }
    
    $filename = $common_site_name . '_' . $safe_group_name . '_' . date('Y-m-d') . '.xlsx';
    $filename = preg_replace('/[^가-힣a-zA-Z0-9._-]/', '_', $filename);
    
    // 로그 기록
    error_log("생성된 파일명: " . $filename);
    error_log("공통 현장명: " . $common_site_name);
    error_log("그룹명: " . $safe_group_name);

    // Excel 파일 출력
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Cache-Control: max-age=1');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: cache, must-revalidate');
    header('Pragma: public');

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
    exit;

} catch (Exception $e) {
    error_log("Group Excel export error: " . $e->getMessage());
    die('그룹 Excel 파일 생성 중 오류가 발생했습니다: ' . $e->getMessage());
}
?>