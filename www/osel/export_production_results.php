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
$elevator_count = intval($selected_data['elevator_count'] ?? 1);

// Get production results
$make_panel_data = generateMakePanelData($panel_data, $production_settings);

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

// Check for required libraries
$excel_lib_path = '../PHPExcel_1.8.0/Classes/PHPExcel.php';
if (!file_exists($excel_lib_path)) {
    die('PHPExcel 라이브러리를 찾을 수 없습니다.');
}

require_once $excel_lib_path;

try {
    // Create new PHPExcel object
    $objPHPExcel = new PHPExcel();

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
    $elevator_count = $selected_data['elevator_count'] ?? 1;
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

    // 제작산출결과 시트 헤더 (D열 면적 제거, 제작사이즈 적용)
    $productionHeaders = [
        'A1' => '패널번호',
        'B1' => '폭(mm)',
        'C1' => '높이(mm)',
        'D1' => '타공크기',
        'E1' => '타공위치_바닥높이',
        'F1' => '타공위치_출입구거리',
        'G1' => '패널재질',
        'H1' => '특이사항',
        'I1' => '제작수량',
        'J1' => '비고'
    ];

    foreach ($productionHeaders as $cell => $value) {
        $productionSheet->setCellValue($cell, $value);
    }

    // 제작산출결과 헤더 스타일 (D열 면적 제거로 J1까지)
    $productionSheet->getStyle('A1:J1')->applyFromArray($headerStyle);

    // 패널 데이터 작성
    $productionRow = 2;

    // 제작패널데이터를 우선 사용, 없으면 원본 패널 데이터 사용
    $display_panel_data = !empty($make_panel_data) ? $make_panel_data : $panel_data;

    if (!empty($display_panel_data)) {
        foreach ($display_panel_data as $panel_num => $panel_info) {
            // 1,11번 패널 제외 설정이 켜져있으면 해당 패널 건너뛰기
            if ($production_settings['panel_corners_excluded'] && (intval($panel_num) === 1 || intval($panel_num) === 11)) {
                continue;
            }
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

            // 타공 정보 추출 (다양한 형태의 데이터 구조 지원)
            $hole_size = '';
            $hole_floor_height = '';
            $hole_entrance_distance = '';

            // holes 배열이 있는 경우
            if (isset($panel_info['holes']) && is_array($panel_info['holes'])) {
                $hole_details = [];
                $floor_heights = [];
                $entrance_distances = [];

                foreach ($panel_info['holes'] as $hole) {
                    if (isset($hole['width']) && isset($hole['height'])) {
                        $hole_details[] = $hole['width'] . '×' . $hole['height'];
                    }
                    if (isset($hole['floor_height'])) {
                        $floor_heights[] = $hole['floor_height'] . 'mm';
                    }
                    if (isset($hole['entrance_distance'])) {
                        $entrance_distances[] = $hole['entrance_distance'] . 'mm';
                    }
                }

                $hole_size = implode(', ', $hole_details);
                $hole_floor_height = implode(', ', $floor_heights);
                $hole_entrance_distance = implode(', ', $entrance_distances);
            }
            // 개별 타공 속성이 있는 경우
            elseif (isset($panel_info['hole_width']) || isset($panel_info['hole_height'])) {
                if (isset($panel_info['hole_width']) && isset($panel_info['hole_height'])) {
                    $hole_size = $panel_info['hole_width'] . '×' . $panel_info['hole_height'];
                }
                if (isset($panel_info['hole_floor_height'])) {
                    $hole_floor_height = $panel_info['hole_floor_height'] . 'mm';
                }
                if (isset($panel_info['hole_entrance_distance'])) {
                    $hole_entrance_distance = $panel_info['hole_entrance_distance'] . 'mm';
                }
            }
            // 9번 패널 특별 처리
            elseif ($panel_num === '9') {
                // 9번 패널의 경우 보통 문 타공이 있음
                if (isset($panel_info['door_width']) && isset($panel_info['door_height'])) {
                    $hole_size = $panel_info['door_width'] . '×' . $panel_info['door_height'] . ' (문타공)';
                }
                if (isset($panel_info['door_floor_height'])) {
                    $hole_floor_height = $panel_info['door_floor_height'] . 'mm';
                }
                if (isset($panel_info['door_center_distance'])) {
                    $hole_entrance_distance = $panel_info['door_center_distance'] . 'mm';
                }
            }

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

            $productionData = [
                'A' => $panel_num,
                'B' => $width,
                'C' => $height,
                'D' => $hole_size,
                'E' => $hole_floor_height,
                'F' => $hole_entrance_distance,
                'G' => $material,
                'H' => $notes,
                'I' => $quantity,
                'J' => trim($remarks)
            ];

            foreach ($productionData as $col => $value) {
                $productionSheet->setCellValue($col . $productionRow, $value);
            }

            $productionRow++;
        }
    }

    // 제작산출결과 데이터 스타일 (D열 면적 제거로 J열까지)
    if ($productionRow > 2) {
        $productionSheet->getStyle('A2:J' . ($productionRow - 1))->applyFromArray($dataStyle);
    }

    // 제작산출결과 시트 컬럼 너비 2배 확장
    $productionSheet->getColumnDimension('A')->setWidth(14); // 패널번호 (30% 축소)
    $productionSheet->getColumnDimension('B')->setWidth(17); // 폭 (30% 축소)
    $productionSheet->getColumnDimension('C')->setWidth(17); // 높이 (30% 축소)
    $productionSheet->getColumnDimension('D')->setWidth(21); // 타공크기 (30% 축소) - 기존 E열
    $productionSheet->getColumnDimension('E')->setWidth(21); // 타공위치_바닥높이 (30% 축소) - 기존 F열
    $productionSheet->getColumnDimension('F')->setWidth(30); // 타공위치_출입구거리 (2배 확장) - 기존 G열
    $productionSheet->getColumnDimension('G')->setWidth(24); // 패널재질 (2배 확장) - 기존 H열
    $productionSheet->getColumnDimension('H')->setWidth(40); // 특이사항 (2배 확장) - 기존 I열
    $productionSheet->getColumnDimension('I')->setWidth(14); // 제작수량 (30% 축소) - 기존 J열
    $productionSheet->getColumnDimension('J')->setWidth(50); // 비고 (2배 확장) - 기존 K열

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

    // 파일명 생성
    $filename = '제작산출결과_' . $selected_data['site_name'] . '_' . date('Y-m-d', strtotime($selected_data['measurement_date'])) . '.xlsx';
    $filename = preg_replace('/[^가-힣a-zA-Z0-9._-]/', '_', $filename); // 특수문자 제거

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
    error_log("Excel export error: " . $e->getMessage());
    die('Excel 파일 생성 중 오류가 발생했습니다: ' . $e->getMessage());
}
?>