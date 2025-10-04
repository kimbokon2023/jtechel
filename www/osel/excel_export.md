# Excel Export 기술 문서

## 개요
이 문서는 J-TECH 시스템에서 PHPExcel_1.8.0 라이브러리를 사용하여 Excel 파일을 생성하고 시트별로 데이터를 저장하는 기술에 대해 설명합니다.

## 목차
1. [시스템 구조](#시스템-구조)
2. [PHPExcel_1.8.0 라이브러리 설정](#phpexcel_180-라이브러리-설정)
3. [기본 Excel 내보내기 구현](#기본-excel-내보내기-구현)
4. [시트별 데이터 저장](#시트별-데이터-저장)
5. [스타일링 및 포맷팅](#스타일링-및-포맷팅)
6. [실제 구현 예제](#실제-구현-예제)
7. [고급 기능](#고급-기능)

## 시스템 구조

### 파일 구조
```
www/
├── PHPExcel_1.8.0/           # PHPExcel 라이브러리
│   └── Classes/
│       ├── PHPExcel.php      # 메인 클래스
│       └── PHPExcel/
│           ├── IOFactory.php # 입출력 팩토리
│           ├── Style/        # 스타일 관련 클래스
│           └── Writer/       # Writer 클래스들
├── osel/
│   ├── result.php           # 메인 결과 페이지
│   ├── export_production_results.php  # 제작산출 결과 내보내기
│   ├── export_measurements.php       # 측정 데이터 내보내기
│   └── export_group_production_data.php # 그룹 데이터 내보내기
└── lib/
    └── mydb.php             # 데이터베이스 연결
```

### 데이터 흐름
1. **사용자 요청**: result.php에서 "엑셀 내보내기" 버튼 클릭
2. **JavaScript 호출**: `exportToExcel()` 함수 실행
3. **파라미터 전달**: 현재 설정값들을 URL 파라미터로 전달
4. **서버 처리**: export_production_results.php에서 데이터 처리
5. **Excel 생성**: PHPExcel을 사용하여 시트별로 데이터 구성
6. **파일 다운로드**: 브라우저로 Excel 파일 전송

## PHPExcel_1.8.0 라이브러리 설정

### 라이브러리 로드
```php
// 라이브러리 경로 확인
$excel_lib_path = '../PHPExcel_1.8.0/Classes/PHPExcel.php';
if (!file_exists($excel_lib_path)) {
    die('PHPExcel 라이브러리를 찾을 수 없습니다.');
}

require_once $excel_lib_path;
```

### 기본 객체 생성
```php
try {
    // PHPExcel 객체 생성
    $objPHPExcel = new PHPExcel();
    
    // 문서 속성 설정
    $objPHPExcel->getProperties()
        ->setCreator("J-TECH Elevator")
        ->setLastModifiedBy($_SESSION['name'] ?? 'User')
        ->setTitle("제작산출 결과 - " . $site_name)
        ->setSubject("Panel Measurement Production Results")
        ->setDescription("Panel measurement production results exported from J-TECH system")
        ->setKeywords("panel measurement production results excel export")
        ->setCategory("Production Data");
        
} catch (Exception $e) {
    error_log("Excel export error: " . $e->getMessage());
    die("Excel 파일 생성 중 오류가 발생했습니다.");
}
```

## 기본 Excel 내보내기 구현

### 1. JavaScript에서 서버 호출
```javascript
function exportToExcel() {
    const measurementId = <?= $selected_data['id'] ?>;
    
    // 현재 페이지의 설정 상태 가져오기
    const moldingIncluded = document.getElementById('moldingIncluded').checked ? 1 : 0;
    const panelCornersExcluded = document.getElementById('panelCornersExcluded').checked ? 1 : 0;
    
    // 현재 설정을 포함하여 Excel 다운로드
    window.location.href = 'export_production_results.php?measurement_id=' + measurementId + 
                          '&molding_included=' + moldingIncluded + 
                          '&panel_corners_excluded=' + panelCornersExcluded;
}
```

### 2. 서버에서 파라미터 처리
```php
// URL 파라미터로 전달된 설정들 우선 사용
$url_molding_included = isset($_GET['molding_included']) ? intval($_GET['molding_included']) : null;
$url_panel_corners_excluded = isset($_GET['panel_corners_excluded']) ? intval($_GET['panel_corners_excluded']) : null;

// 설정값 결정 (URL 파라미터 우선, 없으면 DB 저장값 사용)
$molding_included = $url_molding_included !== null ? $url_molding_included : intval($selected_data['molding_included']);
$panel_corners_excluded = $url_panel_corners_excluded !== null ? $url_panel_corners_excluded : intval($selected_data['panel_corners_excluded']);
```

## 시트별 데이터 저장

### 1. 기본 시트 생성 및 설정
```php
// 첫 번째 시트 (기본 활성 시트)
$sheet = $objPHPExcel->getActiveSheet();
$sheet->setTitle('현장기초정보');

// 두 번째 시트 생성
$productionSheet = $objPHPExcel->createSheet();
$productionSheet->setTitle('제작산출결과');

// 세 번째 시트 생성
$moldingSheet = $objPHPExcel->createSheet();
$moldingSheet->setTitle('Molding');

// 시트 활성화 (첫 번째 시트로 돌아가기)
$objPHPExcel->setActiveSheetIndex(0);
```

### 2. 헤더 설정
```php
// 헤더 배열 정의
$headers = [
    'A1' => 'ID',
    'B1' => '현장명',
    'C1' => '측정일자',
    'D1' => '측정자',
    'E1' => '카 내부 가로',
    'F1' => '카 내부 세로',
    'G1' => '카 내부 높이',
    'H1' => '재질',
    'I1' => '재질 두께',
    'J1' => '프로젝트 타입',
    'K1' => '코너 패널 제외',
    'L1' => 'Transom 제외',
    'M1' => '몰딩 포함',
    'N1' => '제작 높이',
    'O1' => '1,11번 제작 높이',
    'P1' => '엘리베이터 대수',
    'Q1' => '총 패널 수',
    'R1' => '비고'
];

// 헤더 데이터 입력
foreach ($headers as $cell => $value) {
    $sheet->setCellValue($cell, $value);
}
```

### 3. 데이터 입력
```php
$row = 2; // 데이터 시작 행

// 기본 정보 입력
$sheet->setCellValue('A' . $row, $selected_data['id']);
$sheet->setCellValue('B' . $row, $selected_data['site_name']);
$sheet->setCellValue('C' . $row, $selected_data['measurement_date']);
$sheet->setCellValue('D' . $row, $selected_data['measurer_name']);
$sheet->setCellValue('E' . $row, $selected_data['car_inside_width']);
$sheet->setCellValue('F' . $row, $selected_data['car_inside_depth']);
$sheet->setCellValue('G' . $row, $selected_data['car_inside_height']);
$sheet->setCellValue('H' . $row, $selected_data['material_type']);
$sheet->setCellValue('I' . $row, $selected_data['material_thickness']);
$sheet->setCellValue('J' . $row, $selected_data['project_type']);
$sheet->setCellValue('K' . $row, $panel_corners_excluded ? '예' : '아니오');
$sheet->setCellValue('L' . $row, $transom_excluded ? '예' : '아니오');
$sheet->setCellValue('M' . $row, $molding_included ? '예' : '아니오');
$sheet->setCellValue('N' . $row, $production_height);
$sheet->setCellValue('O' . $row, $production_height1_11);
$sheet->setCellValue('P' . $row, $elevator_count);
$sheet->setCellValue('Q' . $row, $production_results['total_panels']);
$sheet->setCellValue('R' . $row, $selected_data['notes'] ?? '');
```

### 4. 패널별 상세 데이터 입력
```php
// 제작산출결과 시트에 패널별 데이터 입력
$productionRow = 2;
foreach ($production_results['dimension_summary']['details'] as $detail) {
    $productionSheet->setCellValue('A' . $productionRow, $detail['panel']);
    $productionSheet->setCellValue('B' . $productionRow, $detail['material'] ?? '');
    $productionSheet->setCellValue('C' . $productionRow, $detail['thickness'] ?? '');
    $productionSheet->setCellValue('D' . $productionRow, $detail['width']);
    $productionSheet->setCellValue('E' . $productionRow, $detail['height']);
    $productionSheet->setCellValue('F' . $productionRow, $detail['area']);
    $productionSheet->setCellValue('G' . $productionRow, $elevator_count);
    $productionSheet->setCellValue('H' . $productionRow, $detail['total_area']);
    $productionRow++;
}

// Transom 데이터가 있으면 별도 행 추가
if (!empty($transom_data)) {
    $transom_material = null;
    if (isset($transom_data['12']['materialType'])) {
        $transom_material = $transom_data['12']['materialType'];
    } elseif (isset($transom_data['transom']['materialType'])) {
        $transom_material = $transom_data['transom']['materialType'];
    }
    
    $productionSheet->setCellValue('A' . $productionRow, 'T');
    $productionSheet->setCellValue('B' . $productionRow, $transom_material ?? '');
    $productionSheet->setCellValue('C' . $productionRow, $transom_data['thickness'] ?? '');
    $productionSheet->setCellValue('D' . $productionRow, $transom_data['width'] ?? '');
    $productionSheet->setCellValue('E' . $productionRow, $transom_data['plate_height'] ?? '');
    $productionSheet->setCellValue('F' . $productionRow, '');
    $productionSheet->setCellValue('G' . $productionRow, $elevator_count);
    $productionSheet->setCellValue('H' . $productionRow, '');
}
```

## 스타일링 및 포맷팅

### 1. 헤더 스타일
```php
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

// 헤더 스타일 적용
$sheet->getStyle('A1:R1')->applyFromArray($headerStyle);
```

### 2. 데이터 스타일
```php
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

// 데이터 스타일 적용
$sheet->getStyle('A2:R' . $row)->applyFromArray($dataStyle);
```

### 3. 컬럼 너비 설정
```php
// 자동 너비 조정
$autoSizeCols = ['A', 'B', 'C', 'D', 'H', 'I', 'J', 'K', 'L', 'M', 'R'];
foreach ($autoSizeCols as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// 고정 너비 설정
$fixedWidths = [
    'E' => 15, 'F' => 15, 'G' => 15,  // 치수 컬럼들
    'N' => 12, 'O' => 15, 'P' => 12, 'Q' => 12  // 기타 컬럼들
];
foreach ($fixedWidths as $col => $width) {
    $sheet->getColumnDimension($col)->setWidth($width);
}
```

## 실제 구현 예제

### 완전한 Excel 내보내기 함수
```php
function exportProductionResultsToExcel($measurement_id, $molding_included, $panel_corners_excluded) {
    // 라이브러리 로드
    require_once '../PHPExcel_1.8.0/Classes/PHPExcel.php';
    
    // 데이터베이스에서 데이터 조회
    $pdo = db_connect();
    $stmt = $pdo->prepare("SELECT * FROM panel_measurements WHERE id = ?");
    $stmt->execute([$measurement_id]);
    $selected_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 패널 데이터 파싱
    $panel_data = json_decode($selected_data['panel_data'], true) ?: [];
    $transom_data = json_decode($selected_data['transom_data'], true) ?: [];
    
    // 제작 결과 계산
    $production_results = calculateProductionResults($panel_data, $transom_data, $selected_data);
    
    // PHPExcel 객체 생성
    $objPHPExcel = new PHPExcel();
    
    // === 시트 1: 현장기초정보 ===
    $sheet = $objPHPExcel->getActiveSheet();
    $sheet->setTitle('현장기초정보');
    
    // 헤더 설정
    $headers = [
        'A1' => 'ID', 'B1' => '현장명', 'C1' => '측정일자',
        'D1' => '측정자', 'E1' => '카 내부 가로', 'F1' => '카 내부 세로'
    ];
    
    foreach ($headers as $cell => $value) {
        $sheet->setCellValue($cell, $value);
    }
    
    // 데이터 입력
    $row = 2;
    $sheet->setCellValue('A' . $row, $selected_data['id']);
    $sheet->setCellValue('B' . $row, $selected_data['site_name']);
    $sheet->setCellValue('C' . $row, $selected_data['measurement_date']);
    // ... 기타 데이터
    
    // === 시트 2: 제작산출결과 ===
    $productionSheet = $objPHPExcel->createSheet();
    $productionSheet->setTitle('제작산출결과');
    
    // 패널별 상세 데이터 입력
    $productionRow = 2;
    foreach ($production_results['dimension_summary']['details'] as $detail) {
        $productionSheet->setCellValue('A' . $productionRow, $detail['panel']);
        $productionSheet->setCellValue('B' . $productionRow, $detail['material']);
        $productionSheet->setCellValue('C' . $productionRow, $detail['thickness']);
        $productionSheet->setCellValue('D' . $productionRow, $detail['width']);
        $productionSheet->setCellValue('E' . $productionRow, $detail['height']);
        $productionSheet->setCellValue('F' . $productionRow, $detail['area']);
        $productionRow++;
    }
    
    // Transom 데이터 추가
    if (!empty($transom_data)) {
        $productionSheet->setCellValue('A' . $productionRow, 'T');
        $productionSheet->setCellValue('B' . $productionRow, $transom_data['materialType'] ?? '');
        $productionSheet->setCellValue('C' . $productionRow, $transom_data['thickness'] ?? '');
        $productionSheet->setCellValue('D' . $productionRow, $transom_data['width'] ?? '');
        $productionSheet->setCellValue('E' . $productionRow, $transom_data['plate_height'] ?? '');
    }
    
    // === 시트 3: 몰딩 정보 ===
    $moldingSheet = $objPHPExcel->createSheet();
    $moldingSheet->setTitle('Molding');
    
    // 몰딩 데이터 계산 및 입력
    $moldingData = calculateMoldingData($selected_data, $molding_included, $panel_corners_excluded);
    
    $moldingHeaders = [
        'A1' => '몰딩 종류', 'B1' => '절단치수(mm)', 'C1' => '개수(EA)',
        'D1' => '대수', 'E1' => '총개수(EA)', 'F1' => '설명'
    ];
    
    foreach ($moldingHeaders as $cell => $value) {
        $moldingSheet->setCellValue($cell, $value);
    }
    
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
    
    // 파일명 생성
    $filename = '제작산출결과_' . $selected_data['site_name'] . '_' . 
                date('Y-m-d', strtotime($selected_data['measurement_date'])) . '.xlsx';
    $filename = preg_replace('/[^가-힣a-zA-Z0-9._-]/', '_', $filename);
    
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
}
```

## 고급 기능

### 1. 조건부 스타일링
```php
// 숫자 포맷 적용
$sheet->getStyle('E2:R2')->getNumberFormat()->setFormatCode('#,##0');

// 날짜 포맷 적용
$sheet->getStyle('C2')->getNumberFormat()->setFormatCode('yyyy-mm-dd');

// 조건부 서식 (예: 0보다 큰 값만 표시)
$conditionalStyle = new PHPExcel_Style_Conditional();
$conditionalStyle->setConditionType(PHPExcel_Style_Conditional::CONDITION_CELLIS);
$conditionalStyle->setConditionOperator(PHPExcel_Style_Conditional::OPERATOR_GREATERTHAN);
$conditionalStyle->addCondition(0);
$conditionalStyle->getStyle()->getFont()->setColor(new PHPExcel_Style_Color('FF0066CC'));
```

### 2. 차트 추가
```php
// 차트 데이터 범위 설정
$dataSeriesLabels = ['제작산출결과'];
$xAxisTickValues = ['패널1', '패널2', '패널3', 'Transom'];
$dataSeriesValues = ['제작산출결과!$F$2:$F$5'];

// 차트 생성
$chart = new PHPExcel_Chart_DataSeries(
    PHPExcel_Chart_DataSeries::TYPE_BARCHART,
    PHPExcel_Chart_DataSeries::GROUPING_CLUSTERED,
    range(0, count($dataSeriesValues)-1),
    $dataSeriesLabels,
    $xAxisTickValues,
    $dataSeriesValues
);

$chart->setTitle(new PHPExcel_Chart_Title('패널별 제작산출 결과'));
$plotArea = new PHPExcel_Chart_PlotArea(null, [$chart]);
$legend = new PHPExcel_Chart_Legend(PHPExcel_Chart_Legend::POSITION_RIGHT, null, false);
$title = new PHPExcel_Chart_Title('패널별 제작산출 결과');

$chart = new PHPExcel_Chart(
    'chart1',
    $title,
    $legend,
    $plotArea,
    true,
    0,
    null,
    null
);

$chart->setTopLeftPosition('A10');
$chart->setBottomRightPosition('H25');
$sheet->addChart($chart);
```

### 3. 수식 사용
```php
// 합계 수식 추가
$sheet->setCellValue('Q2', '=SUM(F:F)');

// 평균 수식 추가
$sheet->setCellValue('R2', '=AVERAGE(F:F)');

// 조건부 수식 (IF 함수)
$sheet->setCellValue('S2', '=IF(E2>1000,"대형","소형")');
```

### 4. 하이퍼링크 추가
```php
// 웹사이트 링크
$sheet->setCellValue('T2', 'J-TECH 홈페이지');
$sheet->getCell('T2')->getHyperlink()->setUrl('https://www.jtech.co.kr');

// 이메일 링크
$sheet->setCellValue('U2', 'support@jtech.co.kr');
$sheet->getCell('U2')->getHyperlink()->setUrl('mailto:support@jtech.co.kr');
```

### 5. 데이터 유효성 검사
```php
// 드롭다운 리스트 생성
$objValidation = $sheet->getCell('H2')->getDataValidation();
$objValidation->setType(PHPExcel_Cell_DataValidation::TYPE_LIST);
$objValidation->setErrorStyle(PHPExcel_Cell_DataValidation::STYLE_INFORMATION);
$objValidation->setAllowBlank(false);
$objValidation->setShowInputMessage(true);
$objValidation->setShowErrorMessage(true);
$objValidation->setShowDropDown(true);
$objValidation->setErrorTitle('입력 오류');
$objValidation->setError('선택된 값이 목록에 없습니다.');
$objValidation->setPromptTitle('재질 선택');
$objValidation->setPrompt('재질을 선택하세요.');
$objValidation->setFormula1('"스테인리스,아연도금강판,일반강판"');
```

## 주의사항 및 팁

### 1. 메모리 관리
```php
// 대용량 데이터 처리 시 메모리 제한 증가
ini_set('memory_limit', '512M');

// 가비지 컬렉션 강제 실행
gc_collect_cycles();
```

### 2. 에러 처리
```php
try {
    $objPHPExcel = new PHPExcel();
    // ... Excel 작업
} catch (Exception $e) {
    error_log("Excel export error: " . $e->getMessage());
    
    // 사용자에게 친화적인 에러 메시지
    if (strpos($e->getMessage(), 'memory') !== false) {
        die('데이터가 너무 많아 Excel 파일을 생성할 수 없습니다.');
    } else {
        die('Excel 파일 생성 중 오류가 발생했습니다.');
    }
}
```

### 3. 파일명 처리
```php
// 한글 파일명 지원을 위한 인코딩
$filename = '제작산출결과_' . $site_name . '.xlsx';
$filename = mb_convert_encoding($filename, 'UTF-8', 'auto');

// 특수문자 제거
$filename = preg_replace('/[^가-힣a-zA-Z0-9._-]/', '_', $filename);

// 파일명 길이 제한
if (strlen($filename) > 100) {
    $filename = substr($filename, 0, 100) . '.xlsx';
}
```

### 4. 성능 최적화
```php
// 불필요한 스타일 계산 비활성화
PHPExcel_Settings::setCalculationCacheEnabled(false);

// 임시 파일 디렉토리 설정
PHPExcel_Shared_File::setUseUploadTempDirectory(true);
```

이 문서는 J-TECH 시스템의 Excel 내보내기 기능을 구현하고 확장하는 데 필요한 모든 기술적 세부사항을 포함하고 있습니다. 시트별 데이터 저장, 스타일링, 고급 기능 등을 활용하여 다양한 형태의 Excel 파일을 생성할 수 있습니다.
