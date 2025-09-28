<?php
// Start output buffering to prevent any accidental output
ob_start();

require_once '../lib/mydb.php';
require_once 'generate_make_panel_data.php';
session_start();
$DB = 'jtechel';

// Check authentication
if (!isset($_SESSION["level"]) || $_SESSION["level"] > 8) {
    ob_clean();
    header("Location: ../login/login_form.php");
    exit;
}

// Check if measurements data is provided
if (!isset($_POST['measurements'])) {
    ob_clean();
    die('측정 데이터가 제공되지 않았습니다.');
}

try {
    $measurements = json_decode($_POST['measurements'], true);
    
    if (!$measurements || !is_array($measurements)) {
        throw new Exception('유효하지 않은 측정 데이터입니다.');
    }

    // Check for required libraries
    $excel_lib_path = '../PHPExcel_1.8.0/Classes/PHPExcel.php';
    if (!file_exists($excel_lib_path)) {
        ob_clean();
        die('PHPExcel 라이브러리를 찾을 수 없습니다.');
    }

    require_once $excel_lib_path;

    // Initialize database connection
    $pdo = db_connect();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("USE $DB");
    
    error_log("Database connection established successfully");

    // Create new PHPExcel object
    $objPHPExcel = new PHPExcel();
    $objPHPExcel->getProperties()
        ->setCreator("J-TECH Elevator")
        ->setLastModifiedBy($_SESSION['name'] ?? 'User')
        ->setTitle("합쳐진 제작산출 결과")
        ->setSubject("Multiple Sites Panel Measurement Production Results")
        ->setDescription("Multiple sites panel measurement production results exported from J-TECH system")
        ->setKeywords("panel measurement production results excel export merged")
        ->setCategory("Production Data");
    
    error_log("PHPExcel object created successfully");

    // 공통 스타일 정의 (기존 export_production_results.php와 동일)
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

    // === 첫 번째 시트: 현장기초정보 ===
    $sheet = $objPHPExcel->getActiveSheet();
    $sheet->setTitle('현장기초정보');

    // 헤더 설정 (기존 export_production_results.php와 동일)
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
    $sheet->getStyle('A1:R1')->applyFromArray($headerStyle);

    // 데이터 행 작성
    $row = 2;
    $allMoldingData = [];

    foreach ($measurements as $measurement) {
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

        // 제작 조건 설정 값들 처리
        $project_type = $measurement['project_type'] ?? '신규';
        $panel_corners_excluded_display = $measurement['panel_corners_excluded'] ? 'Y' : 'N';
        $transom_excluded = $measurement['transom_excluded'] ? 'Y' : 'N';
        $molding_included_display = $measurement['molding_included'] ? 'Y' : 'N';
        $elevator_count = $measurement['elevator_count'] ?? 1;
        $production_height = $measurement['production_height'] ?? $measurement['car_inside_height'];
        $production_height1_11 = $measurement['production_height1_11'] ?? $measurement['car_inside_height'];

        // 실제 제작되는 패널 수 계산 (제작 조건 적용)
        $make_panel_data = generateMakePanelData($panel_data, $production_settings);
        $actual_panel_count = 0;
        
        if (!empty($make_panel_data)) {
            foreach ($make_panel_data as $panel_num => $panel_info) {
                // 1,11번 패널 제외 설정이 켜져있으면 해당 패널 건너뛰기
                if ($production_settings['panel_corners_excluded'] && (intval($panel_num) === 1 || intval($panel_num) === 11)) {
                    continue;
                }
                $actual_panel_count++;
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
            'H' => $measurement['material_type'] ?? '',
            'I' => $measurement['material_thickness'] ?? '',
            // 제작 조건 설정
            'J' => $project_type,
            'K' => $panel_corners_excluded_display,
            'L' => $transom_excluded,
            'M' => $molding_included_display,
            'N' => $elevator_count,
            'O' => $production_height,
            'P' => $production_height1_11,
            // 패널 정보 (실제 제작되는 패널 수)
            'Q' => $actual_panel_count,
            'R' => $measurement['notes'] ?? ''
        ];

        foreach ($data as $col => $value) {
            $sheet->setCellValue($col . $row, $value);
        }

        // 몰딩 데이터 수집 (각 현장별로)
        if ($measurement['molding_included']) {
            $allMoldingData[] = [
                'site_name' => $measurement['site_name'],
                'elevator_count' => $elevator_count,
                'production_height' => $production_height,
                'car_width' => $measurement['car_inside_width'],
                'car_depth' => $measurement['car_inside_depth']
            ];
        }

        $row++;
    }

    // 데이터 스타일 설정
    if ($row > 2) {
        $sheet->getStyle('A2:R' . ($row - 1))->applyFromArray($dataStyle);
    }

    // 컬럼 너비 설정 (기존과 동일)
    $sheet->getColumnDimension('B')->setWidth(40); // 현장명
    $sheet->getColumnDimension('D')->setWidth(12); // 측정자
    $sheet->getColumnDimension('R')->setWidth(40); // 특이사항
    $sheet->getColumnDimension('E')->setWidth(24); // 카 내부 가로
    $sheet->getColumnDimension('F')->setWidth(24); // 카 내부 깊이
    $sheet->getColumnDimension('G')->setWidth(24); // 카 내부 높이

    $wideCols = ['H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q'];
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

    // 제작산출결과 시트 헤더 (기존 export_production_results.php와 동일)
    $productionHeaders = [
        'A1' => '현장명',
        'B1' => '패널번호',
        'C1' => '폭(mm)',
        'D1' => '높이(mm)',
        'E1' => '타공크기',
        'F1' => '타공위치_바닥높이',
        'G1' => '타공위치_출입구거리',
        'H1' => '패널재질',
        'I1' => '특이사항',
        'J1' => '제작수량',
        'K1' => '비고'
    ];

    foreach ($productionHeaders as $cell => $value) {
        $productionSheet->setCellValue($cell, $value);
    }

    // 제작산출결과 헤더 스타일
    $productionSheet->getStyle('A1:K1')->applyFromArray($headerStyle);

    // 패널 데이터 작성
    $productionRow = 2;

    foreach ($measurements as $measurement) {
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

        $make_panel_data = generateMakePanelData($panel_data, $production_settings);
        $elevator_count = $measurement['elevator_count'] ?? 1;

        if (!empty($make_panel_data)) {
            foreach ($make_panel_data as $panel_num => $panel_info) {
                // 1,11번 패널 제외 설정이 켜져있으면 해당 패널 건너뛰기
                if ($production_settings['panel_corners_excluded'] && (intval($panel_num) === 1 || intval($panel_num) === 11)) {
                    continue;
                }

                // 제작사이즈 적용
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

                // 높이는 제작 높이 적용
                if (in_array($panel_num, ['1', '11'])) {
                    $height = $measurement['production_height1_11'] ?? $measurement['car_inside_height'];
                } else {
                    $height = $measurement['production_height'] ?? $measurement['car_inside_height'];
                }

                // 타공 정보 추출
                $hole_size = '';
                $hole_floor_height = '';
                $hole_entrance_distance = '';

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

                // 패널 타입 결정
                $panel_type = '';
                if (in_array($panel_num, ['1', '11'])) {
                    $panel_type = '1,11번 패널 (코너패널)';
                } elseif ($panel_num === '12') {
                    $panel_type = '트랜섬 패널';
                } elseif ($panel_num === '9') {
                    $panel_type = '출입구 패널';
                } elseif (in_array($panel_num, ['2', '10'])) {
                    $panel_type = '측면 패널';
                } elseif (in_array($panel_num, ['3', '4', '5', '6', '7', '8'])) {
                    $panel_type = '후면 패널';
                } else {
                    $panel_type = '일반 패널';
                }

                // 기타 정보
                $material = $panel_info['material'] ?? $measurement['material_type'] ?? '스테인리스 스틸';
                $notes = $panel_info['notes'] ?? $panel_type;
                $quantity = $elevator_count;
                $remarks = '';

                $productionData = [
                    'A' => $measurement['site_name'],
                    'B' => $panel_num,
                    'C' => $width,
                    'D' => $height,
                    'E' => $hole_size,
                    'F' => $hole_floor_height,
                    'G' => $hole_entrance_distance,
                    'H' => $material,
                    'I' => $notes,
                    'J' => $quantity,
                    'K' => trim($remarks)
                ];

                foreach ($productionData as $col => $value) {
                    $productionSheet->setCellValue($col . $productionRow, $value);
                }

                $productionRow++;
            }
        }
    }

    // 제작산출결과 데이터 스타일
    if ($productionRow > 2) {
        $productionSheet->getStyle('A2:K' . ($productionRow - 1))->applyFromArray($dataStyle);
    }

    // 제작산출결과 시트 컬럼 너비 설정 (기존과 동일)
    $productionSheet->getColumnDimension('A')->setWidth(20); // 현장명
    $productionSheet->getColumnDimension('B')->setWidth(14); // 패널번호
    $productionSheet->getColumnDimension('C')->setWidth(17); // 폭
    $productionSheet->getColumnDimension('D')->setWidth(17); // 높이
    $productionSheet->getColumnDimension('E')->setWidth(21); // 타공크기
    $productionSheet->getColumnDimension('F')->setWidth(21); // 타공위치_바닥높이
    $productionSheet->getColumnDimension('G')->setWidth(30); // 타공위치_출입구거리
    $productionSheet->getColumnDimension('H')->setWidth(24); // 패널재질
    $productionSheet->getColumnDimension('I')->setWidth(40); // 특이사항
    $productionSheet->getColumnDimension('J')->setWidth(14); // 제작수량
    $productionSheet->getColumnDimension('K')->setWidth(50); // 비고

    // === 세 번째 시트: 몰딩 정보 ===
    $moldingSheet = $objPHPExcel->createSheet();
    $moldingSheet->setTitle('Molding');

    // 몰딩 시트 헤더 (기존 export_production_results.php와 동일)
    $moldingHeaders = [
        'A1' => '현장명',
        'B1' => '몰딩 종류',
        'C1' => '절단치수(mm)',
        'D1' => '개수(EA)',
        'E1' => '대수',
        'F1' => '총개수(EA)',
        'G1' => '설명'
    ];

    foreach ($moldingHeaders as $cell => $value) {
        $moldingSheet->setCellValue($cell, $value);
    }

    // 몰딩 헤더 스타일
    $moldingSheet->getStyle('A1:G1')->applyFromArray($headerStyle);

    // 몰딩 데이터 작성
    $moldingRow = 2;
    $moldingSummary = [];

    foreach ($allMoldingData as $moldingSite) {
        $productionHeight = intval($moldingSite['production_height']);
        $carWidth = intval($moldingSite['car_width']);
        $carDepth = intval($moldingSite['car_depth']);
        $elevatorCount = intval($moldingSite['elevator_count']);

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

        foreach ($moldingData as $molding) {
            $moldingSheet->setCellValue('A' . $moldingRow, $moldingSite['site_name']);
            $moldingSheet->setCellValue('B' . $moldingRow, $molding['type']);
            $moldingSheet->setCellValue('C' . $moldingRow, $molding['size']);
            $moldingSheet->setCellValue('D' . $moldingRow, $molding['count']);
            $moldingSheet->setCellValue('E' . $moldingRow, $molding['elevatorCount']);
            $moldingSheet->setCellValue('F' . $moldingRow, $molding['totalCount']);
            $moldingSheet->setCellValue('G' . $moldingRow, $molding['description']);

            // 몰딩 요약 계산 (같은 종류와 크기별로 그룹화)
            $summaryKey = $molding['type'] . '_' . $molding['size'];
            if (!isset($moldingSummary[$summaryKey])) {
                $moldingSummary[$summaryKey] = [
                    'type' => $molding['type'],
                    'size' => $molding['size'],
                    'description' => $molding['description'],
                    'totalCount' => 0
                ];
            }
            $moldingSummary[$summaryKey]['totalCount'] += $molding['totalCount'];

            $moldingRow++;
        }
    }

    // 몰딩 데이터 스타일
    if ($moldingRow > 2) {
        $moldingSheet->getStyle('A2:G' . ($moldingRow - 1))->applyFromArray($dataStyle);
    }

    // 몰딩 합계 행 추가 (중복 제거된 합계)
    if (!empty($moldingSummary)) {
        $summaryRow = $moldingRow + 1;
        $moldingSheet->setCellValue('A' . $summaryRow, '=== 합계 (중복 제거) ===');
        $moldingSheet->getStyle('A' . $summaryRow . ':G' . $summaryRow)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'color' => ['rgb' => 'FFF3E0']
            ]
        ]);

        $summaryRow++;
        foreach ($moldingSummary as $summary) {
            $moldingSheet->setCellValue('B' . $summaryRow, $summary['type']);
            $moldingSheet->setCellValue('C' . $summaryRow, $summary['size']);
            $moldingSheet->setCellValue('D' . $summaryRow, '');
            $moldingSheet->setCellValue('E' . $summaryRow, '');
            $moldingSheet->setCellValue('F' . $summaryRow, $summary['totalCount']);
            $moldingSheet->setCellValue('G' . $summaryRow, $summary['description']);

            $moldingSheet->getStyle('A' . $summaryRow . ':G' . $summaryRow)->applyFromArray($dataStyle);
            $summaryRow++;
        }
    }

    // 몰딩 시트 컬럼 너비 설정 (기존과 동일)
    $moldingSheet->getColumnDimension('A')->setWidth(20); // 현장명
    $moldingSheet->getColumnDimension('B')->setWidth(21); // 몰딩 종류
    $moldingSheet->getColumnDimension('C')->setWidth(17); // 절단치수
    $moldingSheet->getColumnDimension('D')->setWidth(14); // 개수
    $moldingSheet->getColumnDimension('E')->setWidth(11); // 대수
    $moldingSheet->getColumnDimension('F')->setWidth(14); // 총개수
    $moldingSheet->getColumnDimension('G')->setAutoSize(true); // 설명

    // 첫 번째 시트를 활성화
    $objPHPExcel->setActiveSheetIndex(0);

    // 파일명 생성 (안전한 ASCII 문자만 사용)
    $siteCount = count($measurements);
    $dateStr = date('Y-m-d');
    $filename = 'Merged_Production_Data_' . $siteCount . '_Sites_' . $dateStr . '.xlsx';
    
    // 출력 버퍼 정리
    ob_clean();
    
    // Excel 파일 출력 헤더 설정
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Cache-Control: max-age=1');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: cache, must-revalidate');
    header('Pragma: public');

    // PHPExcel Writer 생성 및 출력
    error_log("Creating PHPExcel Writer...");
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    
    error_log("Saving Excel file to output...");
    $objWriter->save('php://output');
    
    error_log("Excel file export completed successfully");
    exit;

} catch (Exception $e) {
    error_log("Excel export error: " . $e->getMessage());
    ob_clean();
    header("Content-Type: text/plain; charset=UTF-8");
    die('Excel 파일 생성 중 오류가 발생했습니다: ' . $e->getMessage());
}
?>
