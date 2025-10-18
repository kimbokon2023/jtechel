<?php
require_once '../lib/mydb.php';
session_start();
$DB = 'jtechel';

// Check authentication
if (!isset($_SESSION["level"]) || $_SESSION["level"] > 8) {
    header("Location: ../login/login_form.php");
    exit;
}

// Initialize database connection
try {
    $pdo = db_connect(); 
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("USE $DB");
} catch (PDOException $e) {
    error_log("Database connection failed in result.php: " . $e->getMessage());
    die("데이터베이스 연결에 실패했습니다.");
}

// Search parameters
$search_site = $_GET['search_site'] ?? '';
// 기본값: 시작일은 6개월 전, 종료일은 오늘 (list.php와 동일한 로직)
$search_date_from = $_GET['search_date_from'] ?? date('Y-m-d', strtotime('-6 months'));
$search_date_to = $_GET['search_date_to'] ?? date('Y-m-d');
$search_measurer = $_GET['search_measurer'] ?? '';
$selected_measurement = $_GET['measurement_id'] ?? '';

// 디버그: 검색 파라미터를 JavaScript로 출력
$debug_search_params = [
    'search_site' => $search_site,
    'search_date_from' => $search_date_from,
    'search_date_to' => $search_date_to, 
    'search_measurer' => $search_measurer,
    'selected_measurement' => $selected_measurement,
    'all_get_params' => $_GET
];

// Build search query for measurement selection
$where_conditions = [];
$params = [];

if (!empty($search_site)) {
    $where_conditions[] = "site_name LIKE ?";
    $params[] = "%{$search_site}%";
}

if (!empty($search_date_from)) {
    $where_conditions[] = "measurement_date >= ?";
    $params[] = $search_date_from;
}

    if (!empty($search_date_to)) {
        $where_conditions[] = "measurement_date < DATE_ADD(?, INTERVAL 1 DAY)";
        $params[] = $search_date_to;
    }

if (!empty($search_measurer) && $search_measurer !== '전체 측정자') {
    $where_conditions[] = "measurer_name = ?";
    $params[] = $search_measurer;
} 

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// 디버그: WHERE 조건을 JavaScript로 출력
$debug_where_conditions = [
    'where_conditions' => $where_conditions,
    'where_clause' => $where_clause,
    'params' => $params
];

// 디버깅: 검색 조건에 맞는 모든 레코드 확인
$debug_all_records_stmt = $pdo->prepare("SELECT id, site_name, measurement_date, created_at FROM panel_measurements " . $where_clause . " ORDER BY measurement_date DESC, id DESC");
$debug_all_records_stmt->execute($params);
$debug_all_records = $debug_all_records_stmt->fetchAll();
error_log("DEBUG: 검색 조건에 맞는 모든 레코드 수: " . count($debug_all_records));
foreach ($debug_all_records as $record) {
    error_log("  - ID: {$record['id']}, 현장: {$record['site_name']}, 날짜: {$record['measurement_date']}, 생성: {$record['created_at']}");
}

try {
    // First check if table exists
    $table_check = $pdo->query("SHOW TABLES LIKE 'panel_measurements'");
    
    // 디버그: 전체 테이블 레코드 수 확인
    $debug_table_info = [];
    if ($table_check->rowCount() > 0) {
        $total_count_stmt = $pdo->query("SELECT COUNT(*) as total FROM panel_measurements");
        $total_count = $total_count_stmt->fetch()['total'];
        $debug_table_info['total_count'] = $total_count;
        
        // 최근 10개 레코드 정보 확인
        $recent_stmt = $pdo->query("SELECT id, site_name, measurement_date, measurer_name, created_at FROM panel_measurements ORDER BY measurement_date DESC LIMIT 10");
        $recent_records = $recent_stmt->fetchAll();
        $debug_table_info['recent_records'] = $recent_records;
        
        // ID별 중복 확인
        $duplicate_check_stmt = $pdo->query("SELECT id, COUNT(*) as count FROM panel_measurements GROUP BY id HAVING COUNT(*) > 1");
        $duplicate_ids = $duplicate_check_stmt->fetchAll();
        $debug_table_info['duplicate_ids'] = $duplicate_ids;
        
        // 검색 조건에 맞는 모든 ID 확인
        $all_ids_stmt = $pdo->prepare("SELECT id, site_name, measurement_date, created_at FROM panel_measurements $where_clause ORDER BY measurement_date DESC, created_at DESC");
        $all_ids_stmt->execute($params);
        $all_ids_records = $all_ids_stmt->fetchAll();
        $debug_table_info['all_ids_records'] = $all_ids_records;
    }

    if ($table_check->rowCount() == 0) {
        $measurements = [];
        $selected_data = null;
        error_log("Table 'panel_measurements' does not exist");
    } else {
        error_log("Table 'panel_measurements' exists, proceeding to load data");
        // Always use normal search mode (ignore group_export)
        $group_export = null; // Always null to disable group export
        
        // Get measurement list for selection (normal case)
    $query = "
        SELECT id, site_name, measurement_date, measurer_name,
               car_inside_width, car_inside_depth, car_inside_height,
               car_structure, material_type, material_thickness, project_type,
               panel_corners_excluded, transom_excluded,
               molding_included, production_height, production_height1_11,
               panel_data, transom_data, notes, created_at, updated_at,
               elevator_count, ipark_check
        FROM panel_measurements
        $where_clause
        ORDER BY measurement_date DESC, id DESC
    ";

    // 디버그: 쿼리와 파라미터를 JavaScript로 출력
    $debug_query_info = [
        'query' => $query,
        'where_clause' => $where_clause,
        'params' => $params
    ];
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $measurements = $stmt->fetchAll();
    
    // elevator_count가 NULL인 경우 기본값 1 설정
    foreach ($measurements as $index => $measurement) {
        if (is_null($measurement['elevator_count'])) {
            $measurements[$index]['elevator_count'] = 1;
        }
    }    
    
    // ID 기준으로 중복 제거 (올바른 구현)
    $unique_measurements = [];
    $seen_ids = [];

    foreach ($measurements as $index => $measurement) {
        
        $id = $measurement['id'];
        if (!isset($seen_ids[$id])) {
            $unique_measurements[] = $measurement;
            $seen_ids[$id] = true;
            error_log("DEBUG: 유지된 측정 데이터 - ID: $id, 현장: {$measurement['site_name']}");            
        } else {
            error_log("DEBUG: 중복 제거됨 - ID: $id, 현장: {$measurement['site_name']}");
        }
    }
    
    error_log("DEBUG: 중복 제거 후 개수: " . count($unique_measurements));
    // 중복 제거된 배열로 교체
    $measurements = $unique_measurements;
    
    $debug_deduplication = [
        'duplicate_ids' => [],
        'duplicate_details' => [],
        'final_count' => count($measurements),
        'final_measurements' => array_map(function($m) {
            return [
                'id' => $m['id'],
                'site_name' => $m['site_name'],
                'measurement_date' => $m['measurement_date'],
                'created_at' => $m['created_at']
            ];
        }, $measurements)
    ];
}

        // 디버깅: 측정 데이터 개수 확인
        error_log("Found " . count($measurements) . " measurements with current search criteria");
        error_log("DEBUG: 측정 데이터 상세 정보:");
        foreach ($measurements as $idx => $measurement) {
            error_log("  [$idx] ID: {$measurement['id']}, 현장: {$measurement['site_name']}, 날짜: {$measurement['measurement_date']}, 생성: {$measurement['created_at']}");
        }
        
        // 중복 제거 전후 비교
        error_log("DEBUG: 중복 제거 전 개수: " . count($measurements));
        error_log("DEBUG: 중복 제거 후 개수: " . count($measurements));
        
        if (empty($measurements)) {
            error_log("Search parameters: site='" . $search_site . "', date_from='" . $search_date_from . "', date_to='" . $search_date_to . "', measurer='" . $search_measurer . "'");

            // 전체 데이터가 있는지 확인
            $total_count_stmt = $pdo->query("SELECT COUNT(*) FROM panel_measurements");
            $total_count = $total_count_stmt->fetchColumn();
            error_log("Total measurements in database: " . $total_count);

            // 검색 조건으로 데이터가 없으면 최근 10개 데이터라도 보여주기
            if ($total_count > 0) {
                $fallback_stmt = $pdo->prepare("
                    SELECT DISTINCT id, site_name, measurement_date, measurer_name,
                           car_inside_width, car_inside_depth, car_inside_height,
                           car_structure, material_type, material_thickness, project_type,
                           panel_corners_excluded, transom_excluded,
                           molding_included, production_height, production_height1_11,
                           panel_data, transom_data, notes, created_at, updated_at,
                           elevator_count, ipark_check
                    FROM panel_measurements
                    ORDER BY measurement_date DESC, id DESC
                    LIMIT 10
                ");
                $fallback_stmt->execute();
                $measurements = $fallback_stmt->fetchAll();
                
                // elevator_count가 NULL인 경우 기본값 1 설정
                foreach ($measurements as &$measurement) {
                    if (is_null($measurement['elevator_count'])) {
                        $measurement['elevator_count'] = 1;
                    }
                }
                
                // ID 기준으로 중복 제거 (fallback 쿼리용)
                $unique_measurements = [];
                $seen_ids = [];
                foreach ($measurements as $measurement) {
                    $id = $measurement['id'];
                    if (!isset($seen_ids[$id])) {
                        $unique_measurements[] = $measurement;
                        $seen_ids[$id] = true;
                    }
                }
                $measurements = $unique_measurements;
                
                error_log("Fallback: showing latest 10 measurements instead");
            }
        }

        // Get selected measurement data
        $selected_data = null;
        if (!empty($selected_measurement)) {
            $selected_stmt = $pdo->prepare("
                SELECT id, site_name, measurement_date, measurer_name,
                       car_inside_width, car_inside_depth, car_inside_height,
                       car_structure, material_type, material_thickness, project_type,
                       panel_corners_excluded, transom_excluded,
                       molding_included, production_height, production_height1_11,
                       panel_data, make_panel_data, transom_data, notes, created_at, updated_at,
                       elevator_count, ipark_check
                FROM panel_measurements
                WHERE id = ?
            ");
            $selected_stmt->execute([$selected_measurement]);
            $selected_data = $selected_stmt->fetch();
            
            // elevator_count가 NULL인 경우 기본값 1 설정
            if ($selected_data && is_null($selected_data['elevator_count'])) {
                $selected_data['elevator_count'] = 1;
            }

            // 디버깅: 선택된 데이터 확인
            if ($selected_data) {
                error_log("Selected measurement loaded successfully: ID=" . $selected_data['id'] . ", Site=" . $selected_data['site_name']);
            } else {
                error_log("No data found for measurement_id: " . $selected_measurement);
            }
        }

    // Get unique measurers for filter
    if ($table_check && $table_check->rowCount() > 0) {
        $measurer_stmt = $pdo->prepare("SELECT DISTINCT measurer_name FROM panel_measurements WHERE measurer_name IS NOT NULL AND measurer_name != '' ORDER BY measurer_name");
        $measurer_stmt->execute();
        $measurers = $measurer_stmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $measurers = [];
    }

} catch (PDOException $e) {
    $measurements = [];
    $measurers = [];
    $selected_data = null;
    $error_message = '데이터베이스 오류가 발생했습니다: ' . $e->getMessage();
}

// Parse JSON data if measurement is selected
$panel_data = [];
$transom_data = [];
$production_results = [];

if ($selected_data) {
    // 제작 패널 데이터 우선 사용, 없으면 원본 패널 데이터 사용
    if (!empty($selected_data['make_panel_data'])) {
        $panel_data = json_decode($selected_data['make_panel_data'], true) ?? [];
        error_log("Using make_panel_data for visualization");
    } elseif (!empty($selected_data['panel_data'])) {
        $panel_data = json_decode($selected_data['panel_data'], true) ?? [];
        error_log("Using original panel_data for visualization");
    }

    if (!empty($selected_data['transom_data'])) {
        $transom_data = json_decode($selected_data['transom_data'], true) ?? [];
        error_log("DEBUG: transom_data loaded from database: " . json_encode($transom_data));
        error_log("DEBUG: transom_data type: " . gettype($transom_data));
        error_log("DEBUG: transom_data count: " . (is_array($transom_data) ? count($transom_data) : 'not array'));
    } else {
        error_log("DEBUG: transom_data is empty in database");
        error_log("DEBUG: selected_data keys: " . implode(', ', array_keys($selected_data)));
        error_log("DEBUG: selected_data transom_data value: " . var_export($selected_data['transom_data'], true));
        $transom_data = [];
    }

    // Calculate production results
    $production_results = calculateProductionResults($panel_data, $transom_data, $selected_data);
}

function calculateProductionResults($panel_data, $transom_data, $measurement_data) {
    // 1,11번 패널 제외 설정 확인
    $panel_corners_excluded = $measurement_data['panel_corners_excluded'] ?? 1;
    $production_height1_11 = $measurement_data['production_height1_11'] ?? 0;

    // Transom 정보 확인 (measurement_detail.php와 동일한 로직)
    $transom_info = [];
    if (!empty($transom_data['12'])) {
        $transom_info = $transom_data['12'];
    } elseif (!empty($panel_data['12'])) {
        $transom_info = $panel_data['12'];
    }

    // 총 패널 수 계산 (1,11번 패널 제외 고려)
    $panel_count = 0;
    foreach ($panel_data as $panel_num => $data) {
        if ($panel_num == '12') continue; // 12번은 Transom으로 따로 계산
        if ($panel_corners_excluded && (intval($panel_num) === 1 || intval($panel_num) === 11)) {
            continue; // 1,11번 패널 제외 설정 시 제외
        }
        $panel_count++;
    }

    $results = [
        'total_panels' => $panel_count + (!empty($transom_info) ? 1 : 0),
        'material_summary' => [],
        'dimension_summary' => [],
        'special_requirements' => [],
        'corner_panels' => [],
        'transom_details' => [],
        'production_height1_11' => [],
        'production_height' => [],
        'panel_corners_excluded' => [],
        'transom_excluded' => [],
        'molding_included' => [],
        'elevator_count' => []
    ];

    // Material summary
    $materials = [];
    foreach ($panel_data as $panel_num => $data) {
        if ($panel_num == '12') continue; // 12번은 Transom으로 따로 처리
        // 1,11번 패널 제외 설정이 켜져있으면 해당 패널 건너뛰기
        if ($panel_corners_excluded && (intval($panel_num) === 1 || intval($panel_num) === 11)) {
            continue;
        }
        $material = $data['materialType'] ?? '미지정';
        if (!isset($materials[$material])) {
            $materials[$material] = 0;
        }
        $materials[$material]++;
    }

    // Transom 재질 추가 (measurement_detail.php와 동일한 로직)
    if (!empty($transom_info)) {
        $material = $transom_info['materialType'] ?? '미지정';
        if (!isset($materials[$material])) {
            $materials[$material] = 0;
        }
        $materials[$material]++;
        error_log("DEBUG: Transom 재질 추가됨 - " . $material . " (총 " . $materials[$material] . "개)");
    } else {
        // transom_info가 비어있어도 transom_data나 panel_data['12']에서 직접 확인
        $transom_material = null;
        
        // transom_data에서 확인
        if (!empty($transom_data)) {
            if (isset($transom_data['12']['materialType'])) {
                $transom_material = $transom_data['12']['materialType'];
            } elseif (isset($transom_data['transom']['materialType'])) {
                $transom_material = $transom_data['transom']['materialType'];
            }
        }
        
        // panel_data['12']에서 확인
        if (!$transom_material && isset($panel_data['12']['materialType'])) {
            $transom_material = $panel_data['12']['materialType'];
        }
        
        if ($transom_material) {
            if (!isset($materials[$transom_material])) {
                $materials[$transom_material] = 0;
            }
            $materials[$transom_material]++;
            error_log("DEBUG: Transom 재질 직접 추가됨 - " . $transom_material . " (총 " . $materials[$transom_material] . "개)");
        } else {
            error_log("DEBUG: Transom 정보가 비어있음 - transom_data: " . json_encode($transom_data) . ", panel_data['12']: " . json_encode($panel_data['12'] ?? null));
        }
    }
    $results['material_summary'] = $materials;

    // Corner panels analysis (1번, 11번)
    foreach ([1, 11] as $corner_num) {
        if (isset($panel_data[$corner_num])) {
            $results['corner_panels'][$corner_num] = [
                'type' => $panel_data[$corner_num]['panelType'] ?? '미지정',
                'front_thickness' => $panel_data[$corner_num]['frontThickness'] ?? null,
                'front_wing' => $panel_data[$corner_num]['frontWing'] ?? null,
                'back_thickness' => $panel_data[$corner_num]['backThickness'] ?? null,
                'back_wing' => $panel_data[$corner_num]['backWing'] ?? null,
                'width' => $panel_data[$corner_num]['width'] ?? null,
                'height' => $panel_data[$corner_num]['height'] ?? null
            ];
        }
    }

    // Transom details 설정
    if (!empty($transom_info)) {
        $results['transom_details'] = [
            'plate_height' => $transom_info['transomPlateHeight'] ?? null,
            'bottom_depth' => $transom_info['bottomDepthJD'] ?? null,
            'wing_value' => $transom_info['wingValue'] ?? null,
            'cpi_drilling_width' => $transom_info['cpiDrillingWidth'] ?? null,
            'cpi_drilling_height' => $transom_info['cpiDrillingHeight'] ?? null,
            'cpi_drilling_height_from_bottom' => $transom_info['cpiDrillingHeightFromBottom'] ?? null,
            'width' => $transom_info['width'] ?? null,
            'height' => $transom_info['height'] ?? null,
            'material_type' => $transom_info['materialType'] ?? null,
            'thickness' => $transom_info['thickness'] ?? null,
            'drilling_width' => $transom_info['drillingWidth'] ?? null,
            'drilling_height' => $transom_info['drillingHeight'] ?? null,
            'drilling_from_floor' => $transom_info['drillingFromFloor'] ?? null,
            'drilling_from_entrance' => $transom_info['drillingFromEntrance'] ?? null,
            'notes' => $transom_info['notes'] ?? null
        ];
    }

    // Dimension summary with molding deduction
    $total_area = 0;
    $dimensions = [];
    $molding_included = $measurement_data['molding_included'] ?? 0;
    $panel_corners_excluded = $measurement_data['panel_corners_excluded'] ?? 1;  

    foreach ($panel_data as $panel_num => $data) {
        // 1,11번 패널 제외 설정이 켜져있으면 해당 패널 건너뛰기
        if ($panel_corners_excluded && (intval($panel_num) === 1 || intval($panel_num) === 11)) {
            continue;
        }
        if (!empty($data['width']) && !empty($data['height'])) {
            $width = floatval($data['width']);
            $height = floatval($data['height']);
            
            // 1,11번 패널의 경우 production_height1_11 값 사용
            if (intval($panel_num) === 1 || intval($panel_num) === 11 ) {
                if (!empty($measurement_data['production_height1_11'])) {
                    $height = floatval($measurement_data['production_height1_11']);
                }
            }
            // 2~10번 패널의 경우 production_height 값 사용
            elseif (intval($panel_num) >= 2 && intval($panel_num) <= 10) {
                if (!empty($measurement_data['production_height'])) {
                    $height = floatval($measurement_data['production_height']);
                }
            }

            // 몰딩포함 시 패널별 width 차감 적용
            if ($molding_included && is_numeric($panel_num) && $panel_num >= 2 && $panel_num <= 10) {
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

            $area = $width * $height / 1000000; // Convert to square meters
            $total_area += $area;
            $dimensions[] = [
                'panel' => $panel_num,
                'width' => $width,
                'height' => $height,
                'area' => $area,
                'molding_deduction' => $molding_included ? ($molding_deduction ?? 0) : 0,
                'production_height1_11' => $production_height1_11 ?? 0,
            ];
        }
    }
    $results['dimension_summary'] = [
        'total_area' => $total_area,
        'details' => $dimensions
    ];

    // Special requirements (drilling, special shapes, etc.)
    foreach ($panel_data as $panel_num => $data) {
        // 1,11번 패널 제외 설정이 켜져있으면 해당 패널 건너뛰기
        if ($panel_corners_excluded && (intval($panel_num) === 1 || intval($panel_num) === 11)) {
            continue;
        }
        if (!empty($data['drillingWidth']) && !empty($data['drillingHeight'])) {
            $results['special_requirements'][] = [
                'panel' => $panel_num,
                'type' => '타공',
                'details' => $data['drillingWidth'] . '×' . $data['drillingHeight'] . 'mm'
            ];
        }
    }

    return $results;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>제작산출 - 실측 데이터 기반 생성</title>

    <!-- Linear Theme CSS -->
    <link rel="stylesheet" href="../components/linear-theme.css">
    <link rel="stylesheet" href="../components/linear-components.css">

    <!-- Theme Toggle JavaScript -->
    <script src="../components/linear-theme-toggle.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">

    <!-- Mobile Modal Handler -->
    <script src="assets/js/mobile-modal-handler.js"></script>
    <script src="assets/js/mobile-modal-enhancement.js"></script>

    <style>
        /* HTML5 date input 스타일링 */
        input[type="date"] {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            position: relative;
        }
        
        input[type="date"]::-webkit-calendar-picker-indicator {
            cursor: pointer;
            opacity: 1;
            background: none;
            color: var(--linear-text-secondary);
            font-size: 1.2rem;
            padding: 4px;
            margin-right: 8px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }
        
        input[type="date"]::-webkit-calendar-picker-indicator:hover {
            background-color: var(--linear-bg-tertiary);
            color: var(--linear-text-primary);
            transform: scale(1.1);
        }
        
        /* Firefox용 스타일 */
        input[type="date"]::-moz-calendar-picker-indicator {
            cursor: pointer;
            opacity: 1;
            background: none;
            color: var(--linear-text-secondary);
            font-size: 1.2rem;
            padding: 4px;
            margin-right: 8px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }
        
        input[type="date"]::-moz-calendar-picker-indicator:hover {
            background-color: var(--linear-bg-tertiary);
            color: var(--linear-text-primary);
            transform: scale(1.1);
        }
        
        /* 커스텀 달력 아이콘 추가 */
        .date-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }
        
        .date-input-wrapper::after {
            content: "▼";
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            font-size: 0.8rem;
            color: var(--linear-text-secondary);
            z-index: 1;
            font-weight: bold;
        }
        
        .date-input-wrapper input[type="date"] {
            padding-right: 40px;
        }
        
        /* 검색 폼 레이아웃 개선 */
        .search-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr auto;
            gap: var(--linear-spacing-md);
            align-items: end;
        }
        
        .search-grid > div {
            min-width: 0;
        }
        
        /* 날짜 입력 필드 너비 제한 */
        .date-input-wrapper {
            max-width: 150px;
        }
        
        /* 측정자 드롭다운 최소 너비 보장 */
        .search-grid select {
            min-width: 120px;
        }
        
        /* 버튼 그룹 */
        .search-buttons {
            display: flex;
            flex-direction: column;
            gap: var(--linear-spacing-sm);
        }
        
        /* 모바일 반응형 */
        @media (max-width: 768px) {
            .search-grid {
                grid-template-columns: 1fr;
                gap: var(--linear-spacing-sm);
            }
            
            .date-input-wrapper {
                max-width: none;
            }
            
            .search-buttons {
                flex-direction: row;
                justify-content: center;
            }
        }

        /* 몰딩 테이블 인쇄 전용 스타일 */
        @media print {
            @page {
                size: A4;
                margin: 1cm;
            }

            .print-only {
                display: block !important;
            }

            .no-print {
                display: none !important;
            }

            .molding-print-container {
                font-family: 'Noto Sans KR', sans-serif;
                color: #000;
                background: #fff;
            }

            .molding-print-header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 2px solid #333;
                padding-bottom: 20px;
            }

            .molding-print-title {
                font-size: 24px;
                font-weight: bold;
                margin-bottom: 10px;
                color: #333;
            }

            .molding-print-info {
                font-size: 14px;
                color: #666;
                margin-bottom: 5px;
            }

            .molding-print-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
                border: 2px solid #333;
            }

            .molding-print-table th,
            .molding-print-table td {
                border: 1px solid #333;
                padding: 12px 8px;
                text-align: center;
                font-size: 12px;
            }

            .molding-print-table th {
                background-color: #f0f0f0 !important;
                font-weight: bold;
                color: #333;
            }

            .molding-print-table .molding-type {
                text-align: left;
                font-weight: 600;
            }

            .molding-print-table .molding-description {
                font-size: 10px;
                color: #666;
                font-style: italic;
            }

            .molding-print-footer {
                margin-top: 30px;
                text-align: right;
                font-size: 12px;
                color: #666;
            }
        }

        .molding-print-container {
            display: none;
        }

        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        .result-container {
            max-width: var(--linear-page-max-width);
            margin: 0 auto;
            padding: var(--linear-spacing-lg);
        }

        .search-container {
            background-color: var(--linear-bg-primary);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-lg);
            padding: var(--linear-spacing-2xl);
            box-shadow: var(--linear-shadow-low);
            margin-bottom: var(--linear-spacing-xl);
        }

        .measurement-selector {
            background-color: var(--linear-bg-primary);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-lg);
            padding: var(--linear-spacing-2xl);
            box-shadow: var(--linear-shadow-low);
            margin-bottom: var(--linear-spacing-xl);
        }

        .results-container {
            background-color: var(--linear-bg-primary);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-lg);
            padding: var(--linear-spacing-2xl);
            box-shadow: var(--linear-shadow-low);
        }

        /* 기존 search-grid 스타일은 위에서 정의됨 */

        .search-form input,
        .search-form select {
            padding: var(--linear-spacing-sm);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-md);
            background-color: var(--linear-bg-primary);
            color: var(--linear-text-primary);
            font-size: var(--linear-text-body);
        }

        .search-form label {
            color: var(--linear-text-primary);
            font-size: var(--linear-text-small);
            font-weight: var(--linear-font-weight-medium);
            margin-bottom: var(--linear-spacing-xs);
        }

        .measurement-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-md);
            background-color: var(--linear-bg-secondary);
        }

        .measurement-item {
            padding: var(--linear-spacing-md);
            border-bottom: 1px solid var(--linear-border-secondary);
            cursor: pointer;
            transition: background-color var(--linear-transition-fast);
        }

        .measurement-item:hover {
            background-color: var(--linear-bg-tertiary);
        }

        .measurement-item.selected {
            background-color: var(--linear-brand-primary);
            color: white;
        }

        .measurement-item:last-child {
            border-bottom: none;
        }

        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: var(--linear-spacing-lg);
            width: 100%;
        }

        .section-header i {
            color: var(--linear-brand-primary);
            margin-right: var(--linear-spacing-sm);
            font-size: 1.5rem;
        }

        .section-header h3 {
            color: var(--linear-text-primary);
            font-size: var(--linear-text-title3);
            font-weight: var(--linear-font-weight-semibold);
            margin: 0;
            flex: 1;
        }

        /* 검색 토글 기능 스타일 */
        .search-toggle {
            padding: var(--linear-spacing-md);
            border-radius: var(--linear-radius-md);
            transition: background-color 0.2s ease;
        }

        .search-toggle:hover {
            background-color: var(--linear-bg-secondary);
        }

        .toggle-icon {
            font-size: 1.2rem;
            color: var(--linear-text-secondary);
            transition: transform 0.3s ease;
        }

        .toggle-icon.rotated {
            transform: rotate(180deg);
        }

        .search-form {
            overflow: hidden;
            transition: max-height 0.3s ease, opacity 0.3s ease;
            max-height: 1000px;
            opacity: 1;
        }

        .search-form.collapsed {
            max-height: 0;
            opacity: 0;
        }

        /* 측정 데이터 리스트 토글 기능 스타일 */
        .measurement-toggle {
            padding: var(--linear-spacing-md);
            border-radius: var(--linear-radius-md);
            transition: background-color 0.2s ease;
        }

        .measurement-toggle:hover {
            background-color: var(--linear-bg-secondary);
        }

        .measurement-list {
            overflow: hidden;
            transition: max-height 0.3s ease, opacity 0.3s ease;
            max-height: 2000px;
            opacity: 1;
        }

        .measurement-list.collapsed {
            max-height: 0;
            opacity: 0;
        }

        /* 제작 조건 설정 스타일 */
        .production-settings {
            background-color: var(--linear-bg-primary);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-lg);
            padding: var(--linear-spacing-2xl);
            box-shadow: var(--linear-shadow-low);
            margin-bottom: var(--linear-spacing-xl);
        }

        .production-toggle {
            padding: var(--linear-spacing-md);
            border-radius: var(--linear-radius-md);
            transition: background-color 0.2s ease;
        }

        .production-toggle:hover {
            background-color: var(--linear-bg-secondary);
        }

        .production-form {
            overflow: hidden;
            transition: max-height 0.3s ease, opacity 0.3s ease;
            max-height: 1000px;
            opacity: 1;
        }

        .production-form.collapsed {
            max-height: 0;
            opacity: 0;
        }

        .production-field {
            margin-bottom: var(--linear-spacing-lg);
        }

        .field-label {
            display: flex;
            align-items: center;
            gap: var(--linear-spacing-sm);
            margin-bottom: var(--linear-spacing-md);
            color: var(--linear-text-primary);
            font-size: var(--linear-text-body);
            font-weight: var(--linear-font-weight-medium);
        }

        /* (신규/MOD 선택) 필드 - 라벨과 토글을 같은 행에 배치 */
        .project-type-field .field-row {
            display: flex;
            align-items: center;
            gap: var(--linear-spacing-lg);
        }

        .project-type-field .field-label {
            margin-bottom: 0;
            flex-shrink: 0;
            min-width: 120px; /* 라벨 최소 너비 고정 */
        }

        .project-type-field .project-type-toggle {
            flex: 1;
            max-width: 200px; /* 토글 최대 너비 제한 */
        }

        /* 높이 입력 필드 - 라벨과 입력을 같은 행에 배치 */
        .height1-11-field .field-row,
        .height-field .field-row {
            display: flex;
            align-items: center;
            gap: var(--linear-spacing-lg);
        }

        .height1-11-field .field-label,
        .height-field .field-label {
            margin-bottom: 0;
            flex-shrink: 0;
            min-width: 180px; /* 라벨 최소 너비 고정 */
        }

        /* 컴팩트한 입력 그룹 */
        .height-input-group.compact-input {
            display: flex !important;
            align-items: center;
            max-width: 140px; /* 단위를 포함해서 너비 증가 */
            gap: 4px;
            border: none !important; /* 외부 컨테이너 테두리 제거 */
            background: transparent !important; /* 배경 투명 */
            border-radius: 0 !important; /* 테두리 반경 제거 */
            padding: 0 !important; /* 패딩 제거 */
        }

        .height-input-group.compact-input input {
            width: 80px; /* 입력 필드 너비 제한 */
            padding: var(--linear-spacing-sm);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-sm);
            background-color: var(--linear-bg-primary);
            color: var(--linear-text-primary);
            font-size: var(--linear-text-body);
            text-align: center;
            flex-shrink: 0;
        }

        .height-input-group.compact-input .unit {
            color: var(--linear-text-secondary) !important;
            font-size: var(--linear-text-caption);
            white-space: nowrap;
            flex-shrink: 0;
            display: inline-block !important;
            visibility: visible !important;
            background: transparent !important; /* 배경 제거 */
            border: none !important; /* 테두리 제거 */
            padding: 0 !important; /* 패딩 제거 */
            margin-left: 4px; /* 간격만 유지 */
        }

        .field-label i {
            color: var(--linear-brand-primary);
        }

        /* (신규/MOD 선택) 토글 스타일 */
        .project-type-toggle {
            display: flex;
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-md);
            background-color: var(--linear-bg-secondary);
            overflow: hidden;
            height: 42px; /* 텍스트가 보이도록 높이 증가 */
        }

        .project-type-toggle input[type="radio"] {
            display: none;
        }

        .toggle-option {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2px;
            padding: 6px 8px;
            background-color: transparent;
            color: var(--linear-text-secondary);
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: var(--linear-text-small);
            font-weight: var(--linear-font-weight-medium);
            height: 100%;
            min-height: 42px; /* 텍스트가 보이도록 높이 증가 */
            line-height: 1.2;
        }

        .toggle-option:hover {
            background-color: var(--linear-bg-tertiary);
            color: var(--linear-text-primary);
        }

        .project-type-toggle input[type="radio"]:checked + .toggle-option {
            background-color: var(--linear-brand-primary);
            color: white;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* 체크박스 그룹 스타일 */
        .exclusion-options {
            display: flex;
            flex-direction: row;
            gap: var(--linear-spacing-lg);
            flex-wrap: wrap;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: var(--linear-spacing-sm);
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--linear-brand-primary);
            cursor: pointer;
        }

        .checkbox-group label {
            display: flex;
            align-items: center;
            gap: var(--linear-spacing-sm);
            cursor: pointer;
            padding: var(--linear-spacing-xs) var(--linear-spacing-sm);
            border-radius: var(--linear-radius-md);
            transition: background-color 0.2s ease;
            color: var(--linear-text-primary);
            font-size: var(--linear-text-body);
        }

        .checkbox-group label:hover {
            background-color: var(--linear-bg-secondary);
        }

        .checkbox-group label i {
            color: var(--linear-brand-primary);
            font-size: 1rem;
        }

        /* 제작 높이 입력 스타일 */
        .height-input-group {
            display: flex;
            align-items: center;
            background-color: var(--linear-bg-secondary);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-md);
            overflow: hidden;
        }

        .height-input-group input {
            flex: 1;
            border: none;
            background: transparent;
            padding: var(--linear-spacing-md);
            color: var(--linear-text-primary);
            font-size: var(--linear-text-body);
            width: 120px;
        }

        .height-input-group input:focus {
            outline: none;
            box-shadow: inset 0 0 0 2px var(--linear-brand-primary);
        }

        .height-input-group .unit {
            padding: var(--linear-spacing-md);
            background-color: var(--linear-bg-tertiary);
            color: var(--linear-text-secondary);
            font-size: var(--linear-text-small);
            font-weight: var(--linear-font-weight-medium);
        }

        /* 액션 버튼 스타일 */
        .production-actions {
            margin-top: var(--linear-spacing-xl);
            padding-top: var(--linear-spacing-lg);
            border-top: 1px solid var(--linear-border-secondary);
        }

        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: var(--linear-spacing-lg);
            margin-bottom: var(--linear-spacing-xl);
        }

        .result-card {
            background-color: var(--linear-bg-secondary);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-lg);
            padding: var(--linear-spacing-lg);
        }

        .result-card h4 {
            color: var(--linear-text-primary);
            font-size: var(--linear-text-large);
            font-weight: var(--linear-font-weight-semibold);
            margin-bottom: var(--linear-spacing-md);
            display: flex;
            align-items: center;
            gap: var(--linear-spacing-sm);
        }

        .result-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--linear-spacing-sm) 0;
            border-bottom: 1px solid var(--linear-border-secondary);
        }

        .result-item:last-child {
            border-bottom: none;
        }

        .result-label {
            color: var(--linear-text-secondary);
            font-weight: var(--linear-font-weight-medium);
        }

        .result-value {
            color: var(--linear-text-primary);
            font-weight: var(--linear-font-weight-semibold);
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: var(--linear-spacing-sm);
            margin-bottom: var(--linear-spacing-lg);
            font-size: var(--linear-text-small);
        }

        .breadcrumb a {
            color: var(--linear-brand-primary);
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .breadcrumb-separator {
            color: var(--linear-text-tertiary);
        }

        .page-title {
            font-size: 2rem;
            font-weight: var(--linear-font-weight-semibold);
            color: var(--linear-text-primary);
            margin-bottom: var(--linear-spacing-xl);
        }

        .no-data {
            text-align: center;
            color: var(--linear-text-secondary);
            padding: var(--linear-spacing-xl);
            background-color: var(--linear-bg-secondary);
            border-radius: var(--linear-radius-md);
        }

        /* Enhanced Production Results Styles */
        .production-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--linear-spacing-md);
            margin-bottom: var(--linear-spacing-xl);
            padding: var(--linear-spacing-lg);
            background: linear-gradient(135deg, var(--linear-bg-secondary) 0%, var(--linear-bg-tertiary) 100%);
            border-radius: var(--linear-radius-lg);
            border: 1px solid var(--linear-border-primary);
        }

        .summary-card {
            background: var(--linear-bg-primary);
            border: 1px solid var(--linear-border-secondary);
            border-radius: var(--linear-radius-md);
            padding: var(--linear-spacing-lg);
            text-align: center;
            transition: all var(--linear-transition-medium);
            position: relative;
            overflow: hidden;
        }

        .summary-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--linear-brand-primary);
            opacity: 0.7;
        }

        .summary-card.primary::before {
            background: var(--linear-brand-primary);
        }

        .summary-card.highlight::before {
            background: var(--linear-color-success);
        }

        .summary-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--linear-shadow-medium);
        }

        .summary-icon {
            font-size: 2rem;
            color: var(--linear-brand-primary);
            margin-bottom: var(--linear-spacing-sm);
        }

        .summary-content h3 {
            font-size: var(--linear-text-title3);
            font-weight: var(--linear-font-weight-semibold);
            color: var(--linear-text-primary);
            margin: 0 0 var(--linear-spacing-xs) 0;
        }

        .summary-content h3.car-dimensions {
            font-size: calc(var(--linear-text-title3) * 0.9); /* 10% 축소 */
        }

        .summary-subtitle {
            font-size: var(--linear-text-small);
            color: var(--linear-text-secondary);
            margin: 0;
        }

        /* Enhanced Result Cards */
        .result-card.enhanced {
            background: var(--linear-bg-primary);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-lg);
            overflow: hidden;
            box-shadow: var(--linear-shadow-low);
            transition: all var(--linear-transition-medium);
        }

        .result-card.enhanced:hover {
            box-shadow: var(--linear-shadow-medium);
            transform: translateY(-1px);
        }

        .card-header {
            background: linear-gradient(135deg, var(--linear-bg-secondary) 0%, var(--linear-bg-tertiary) 100%);
            padding: var(--linear-spacing-lg);
            border-bottom: 1px solid var(--linear-border-secondary);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-header h4 {
            font-size: var(--linear-text-title4);
            font-weight: var(--linear-font-weight-semibold);
            color: var(--linear-text-primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: var(--linear-spacing-sm);
        }

        .card-badge {
            background: var(--linear-brand-primary);
            color: white;
            padding: var(--linear-spacing-xs) var(--linear-spacing-sm);
            border-radius: var(--linear-radius-sm);
            font-size: var(--linear-text-small);
            font-weight: var(--linear-font-weight-medium);
        }

        .card-badge.area-badge {
            background: var(--linear-color-success);
        }

        .card-badge.quantity-badge {
            background: var(--linear-brand-primary);
            color: white;
        }

        .card-badge.corner-badge {
            background: var(--linear-color-warning);
        }

        .card-badge.transom-badge {
            background: var(--linear-color-purple);
        }

        .card-badge.warning-badge {
            background: var(--linear-color-danger);
        }

        .card-content {
            padding: var(--linear-spacing-lg);
        }

        /* Material Grid Styles */
        .material-grid {
            display: grid;
            gap: var(--linear-spacing-md);
        }

        .material-item {
            display: flex;
            align-items: center;
            gap: var(--linear-spacing-md);
            padding: var(--linear-spacing-md);
            background: var(--linear-bg-secondary);
            border-radius: var(--linear-radius-md);
            border: 1px solid var(--linear-border-secondary);
        }

        .material-item.has-transom {
            border-color: var(--linear-accent-border, #0ea5e9);
        }

        .transom-indicator {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--linear-accent-bg, #0ea5e9);
            color: white;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            border: 2px solid var(--linear-bg-primary);
        }

        .material-icon {
            position: relative;
            flex-shrink: 0;
            width: 40px;
            height: 40px;
            background: var(--linear-brand-primary);
            color: white;
            border-radius: var(--linear-radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .material-info {
            flex: 1;
        }

        .material-info h5 {
            font-size: var(--linear-text-body);
            font-weight: var(--linear-font-weight-semibold);
            color: var(--linear-text-primary);
            margin: 0 0 var(--linear-spacing-xs) 0;
        }

        .transom-count {
            color: var(--linear-accent-text, #0369a1);
            font-weight: bold;
            font-size: 0.85em;
        }


        .material-count {
            font-size: var(--linear-text-small);
            color: var(--linear-text-secondary);
        }

        .material-percentage {
            font-size: var(--linear-text-small);
            font-weight: var(--linear-font-weight-medium);
            color: var(--linear-brand-primary);
            margin-top: var(--linear-spacing-xs);
        }

        .material-progress {
            width: 60px;
            height: 6px;
            background: var(--linear-bg-tertiary);
            border-radius: var(--linear-radius-sm);
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background: var(--linear-brand-primary);
            transition: width var(--linear-transition-medium);
        }

        /* Dimension Analysis Styles */
        .total-area-display, .total-quantity-display {
            text-align: center;
            margin-bottom: var(--linear-spacing-xl);
        }

        .area-highlight {
            background: linear-gradient(135deg, var(--linear-color-success) 0%, var(--linear-color-success-light) 100%);
            color: white;
            padding: var(--linear-spacing-xl);
            border-radius: var(--linear-radius-lg);
            display: inline-block;
        }

        .quantity-highlight {
            background: linear-gradient(135deg, var(--linear-brand-primary) 0%, var(--linear-brand-hover) 100%);
            color: white;
            padding: var(--linear-spacing-xl);
            border-radius: var(--linear-radius-lg);
            display: inline-block;
        }

        .area-highlight i, .quantity-highlight i {
            font-size: 2rem;
            margin-bottom: var(--linear-spacing-sm);
            display: block;
        }

        .area-value, .quantity-value {
            font-size: 2.5rem;
            font-weight: var(--linear-font-weight-bold);
            margin-right: var(--linear-spacing-xs);
        }

        .area-unit, .quantity-unit {
            font-size: var(--linear-text-title4);
            font-weight: var(--linear-font-weight-medium);
        }

        .area-label, .quantity-label {
            font-size: var(--linear-text-small);
            margin: var(--linear-spacing-sm) 0 0 0;
            opacity: 0.9;
        }

        .dimension-details {
            margin-top: var(--linear-spacing-lg);
        }

        .details-title {
            font-size: var(--linear-text-body);
            font-weight: var(--linear-font-weight-semibold);
            color: var(--linear-text-primary);
            margin-bottom: var(--linear-spacing-md);
            display: flex;
            align-items: center;
            gap: var(--linear-spacing-sm);
        }

        .dimension-list {
            display: grid;
            gap: var(--linear-spacing-sm);
        }

        .dimension-item {
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: var(--linear-spacing-md);
            padding: var(--linear-spacing-sm);
            background: var(--linear-bg-secondary);
            border-radius: var(--linear-radius-sm);
        }

        .dimension-item.transom-item {
            border: 1px solid var(--linear-accent-border, #0ea5e9);
            background: var(--linear-accent-bg, #f0f9ff);
        }

        .dimension-item.transom-item .panel-number {
            color: var(--linear-accent-text, #0369a1);
            font-weight: bold;
        }

        .panel-number {
            font-weight: var(--linear-font-weight-semibold);
            color: var(--linear-brand-primary);
            font-size: var(--linear-text-small);
        }

        .dimension-specs {
            display: flex;
            flex-direction: column;
            gap: var(--linear-spacing-xs);
        }

        .dimension-size {
            font-weight: var(--linear-font-weight-medium);
            color: var(--linear-text-primary);
        }

        .dimension-area {
            font-size: var(--linear-text-small);
            color: var(--linear-text-secondary);
        }

        .quantity-info {
            display: flex;
            align-items: center;
            gap: var(--linear-spacing-sm);
            min-width: 80px;
        }

        .quantity-label-item {
            font-size: var(--linear-text-small);
            color: var(--linear-text-secondary);
        }

        .quantity-value-item {
            font-weight: var(--linear-font-weight-medium);
            color: var(--linear-brand-primary);
        }

        .dimension-bar {
            display: flex;
            align-items: center;
            gap: var(--linear-spacing-sm);
            min-width: 80px;
        }

        .bar-fill {
            height: 4px;
            background: var(--linear-color-success);
            border-radius: var(--linear-radius-sm);
            min-width: 20px;
        }

        .bar-percentage {
            font-size: var(--linear-text-small);
            color: var(--linear-text-secondary);
            min-width: 35px;
        }

        /* Corner Panels Styles */
        .corner-panels-grid {
            display: grid;
            gap: var(--linear-spacing-lg);
        }

        .corner-panel-item {
            background: var(--linear-bg-secondary);
            border: 1px solid var(--linear-border-secondary);
            border-radius: var(--linear-radius-md);
            padding: var(--linear-spacing-lg);
        }

        .corner-panel-item.transom-panel-item {
            border-color: var(--linear-accent-border, #0ea5e9);
            background: var(--linear-bg-secondary);
            position: relative;
        }

        .corner-panel-item.transom-panel-item > * {
            position: relative;
            z-index: 1;
        }

        .corner-panel-item.transom-panel-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, 
                rgba(14, 165, 233, 0.08) 0%, 
                rgba(14, 165, 233, 0.02) 100%);
            border-radius: var(--linear-radius-md);
            pointer-events: none;
        }

        .corner-panel-item.transom-panel-item .panel-number {
            color: var(--linear-accent-text, #0369a1);
            font-weight: bold;
        }

        .corner-panel-item.transom-panel-item .corner-icon {
            color: var(--linear-accent-text, #0369a1);
        }

        .corner-panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--linear-spacing-md);
        }

        .panel-indicator {
            display: flex;
            flex-direction: column;
            gap: var(--linear-spacing-xs);
        }

        .panel-number {
            font-size: var(--linear-text-title4);
            font-weight: var(--linear-font-weight-bold);
            color: var(--linear-brand-primary);
        }

        .panel-type {
            font-size: var(--linear-text-small);
            color: var(--linear-text-secondary);
        }

        .corner-icon {
            font-size: 1.5rem;
            color: var(--linear-color-warning);
        }

        .corner-specifications {
            display: grid;
            gap: var(--linear-spacing-md);
        }

        .spec-group {
            background: var(--linear-bg-tertiary);
            border-radius: var(--linear-radius-sm);
            padding: var(--linear-spacing-md);
        }

        .spec-group h6 {
            font-size: var(--linear-text-small);
            font-weight: var(--linear-font-weight-semibold);
            color: var(--linear-text-primary);
            margin: 0 0 var(--linear-spacing-sm) 0;
            display: flex;
            align-items: center;
            gap: var(--linear-spacing-xs);
        }

        .spec-group.front {
            border-left: 3px solid var(--linear-color-success);
        }

        .spec-group.back {
            border-left: 3px solid var(--linear-color-info);
        }

        .spec-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--linear-spacing-xs) 0;
        }

        .spec-label {
            font-size: var(--linear-text-small);
            color: var(--linear-text-secondary);
        }

        .spec-value {
            font-weight: var(--linear-font-weight-medium);
            color: var(--linear-text-primary);
        }

        /* Transom Details Styles */
        .transom-basic-info {
            margin-bottom: var(--linear-spacing-lg);
        }

        .transom-overview {
            display: grid;
            gap: var(--linear-spacing-lg);
        }

        .size-display {
            text-align: center;
            background: var(--linear-bg-secondary);
            padding: var(--linear-spacing-lg);
            border-radius: var(--linear-radius-md);
        }

        .size-display i {
            font-size: 1.5rem;
            color: var(--linear-color-purple);
            margin-bottom: var(--linear-spacing-sm);
        }

        .size-value {
            font-size: var(--linear-text-title3);
            font-weight: var(--linear-font-weight-bold);
            color: var(--linear-text-primary);
            display: block;
            margin-bottom: var(--linear-spacing-xs);
        }

        .size-label {
            font-size: var(--linear-text-small);
            color: var(--linear-text-secondary);
        }

        .transom-specs {
            display: flex;
            gap: var(--linear-spacing-md);
            justify-content: center;
        }

        .spec-badge {
            display: flex;
            align-items: center;
            gap: var(--linear-spacing-xs);
            background: var(--linear-bg-secondary);
            border: 1px solid var(--linear-border-secondary);
            padding: var(--linear-spacing-sm) var(--linear-spacing-md);
            border-radius: var(--linear-radius-md);
            font-size: var(--linear-text-small);
            font-weight: var(--linear-font-weight-medium);
        }

        .spec-badge.material {
            border-color: var(--linear-color-success);
            background: var(--linear-color-success-light);
            color: var(--linear-color-success-dark);
        }

        .spec-badge.thickness {
            border-color: var(--linear-color-info);
            background: var(--linear-color-info-light);
            color: var(--linear-color-info-dark);
        }

        .transom-section {
            margin: var(--linear-spacing-lg) 0;
            padding: var(--linear-spacing-lg);
            background: var(--linear-bg-secondary);
            border-radius: var(--linear-radius-md);
        }

        .section-title {
            font-size: var(--linear-text-body);
            font-weight: var(--linear-font-weight-semibold);
            color: var(--linear-text-primary);
            margin: 0 0 var(--linear-spacing-md) 0;
            display: flex;
            align-items: center;
            gap: var(--linear-spacing-sm);
        }

        .section-title.cpi-title {
            color: var(--linear-color-orange);
        }

        .section-title.standard-title {
            color: var(--linear-color-success);
        }

        .section-title.notes-title {
            color: var(--linear-color-warning);
        }

        .spec-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--linear-spacing-md);
        }

        .spec-detail {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--linear-spacing-sm);
            background: var(--linear-bg-tertiary);
            border-radius: var(--linear-radius-sm);
        }

        .spec-name {
            font-size: var(--linear-text-small);
            color: var(--linear-text-secondary);
        }

        .spec-measure {
            font-weight: var(--linear-font-weight-medium);
            color: var(--linear-text-primary);
        }

        .drilling-grid {
            display: grid;
            gap: var(--linear-spacing-sm);
        }

        .drilling-item {
            display: flex;
            align-items: center;
            gap: var(--linear-spacing-md);
            padding: var(--linear-spacing-sm);
            background: var(--linear-bg-tertiary);
            border-radius: var(--linear-radius-sm);
        }

        .drilling-icon {
            color: var(--linear-color-orange);
        }

        .drilling-details {
            flex: 1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .drilling-label {
            font-size: var(--linear-text-small);
            color: var(--linear-text-secondary);
        }

        .drilling-value {
            font-weight: var(--linear-font-weight-medium);
            color: var(--linear-text-primary);
        }

        .standard-drilling {
            text-align: center;
        }

        .drilling-size-display {
            margin-bottom: var(--linear-spacing-md);
        }

        .size-badge {
            background: var(--linear-color-success);
            color: white;
            padding: var(--linear-spacing-sm) var(--linear-spacing-md);
            border-radius: var(--linear-radius-md);
            font-weight: var(--linear-font-weight-medium);
            display: inline-block;
        }

        .drilling-positions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--linear-spacing-md);
        }

        .position-item {
            display: flex;
            align-items: center;
            gap: var(--linear-spacing-sm);
            padding: var(--linear-spacing-sm);
            background: var(--linear-bg-tertiary);
            border-radius: var(--linear-radius-sm);
        }

        .position-label {
            font-size: var(--linear-text-small);
            color: var(--linear-text-secondary);
            flex: 1;
        }

        .position-value {
            font-weight: var(--linear-font-weight-medium);
            color: var(--linear-text-primary);
        }

        .notes-content {
            background: var(--linear-bg-tertiary);
            padding: var(--linear-spacing-md);
            border-radius: var(--linear-radius-sm);
            border-left: 3px solid var(--linear-color-warning);
        }

        .notes-content p {
            margin: 0;
            color: var(--linear-text-primary);
            line-height: 1.5;
        }

        /* Special Requirements Styles */
        .requirements-list {
            display: grid;
            gap: var(--linear-spacing-md);
        }

        .requirement-item {
            display: flex;
            align-items: flex-start;
            gap: var(--linear-spacing-md);
            padding: var(--linear-spacing-md);
            background: var(--linear-bg-secondary);
            border: 1px solid var(--linear-border-secondary);
            border-radius: var(--linear-radius-md);
            border-left: 3px solid var(--linear-color-danger);
        }

        .requirement-indicator {
            flex-shrink: 0;
            width: 32px;
            height: 32px;
            background: var(--linear-color-danger);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }

        .requirement-content {
            flex: 1;
        }

        .requirement-panel {
            font-weight: var(--linear-font-weight-semibold);
            color: var(--linear-text-primary);
            margin-bottom: var(--linear-spacing-xs);
        }

        .requirement-description {
            font-size: var(--linear-text-small);
            color: var(--linear-text-secondary);
            line-height: 1.4;
        }

        .requirement-priority {
            flex-shrink: 0;
        }

        .priority-badge {
            background: var(--linear-color-danger);
            color: white;
            padding: var(--linear-spacing-xs) var(--linear-spacing-sm);
            border-radius: var(--linear-radius-sm);
            font-size: var(--linear-text-small);
            font-weight: var(--linear-font-weight-medium);
        }

        /* Export Section Styles */
        .export-section {
            margin-top: var(--linear-spacing-xl);
            padding: var(--linear-spacing-xl);
            background: var(--linear-bg-secondary);
            border-radius: var(--linear-radius-lg);
            border: 1px solid var(--linear-border-primary);
        }

        .export-header {
            text-align: center;
            margin-bottom: var(--linear-spacing-xl);
        }

        .export-header h4 {
            font-size: var(--linear-text-title4);
            font-weight: var(--linear-font-weight-semibold);
            color: var(--linear-text-primary);
            margin: 0 0 var(--linear-spacing-sm) 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--linear-spacing-sm);
        }

        .export-header p {
            font-size: var(--linear-text-small);
            color: var(--linear-text-secondary);
            margin: 0;
        }

        .export-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--linear-spacing-lg);
        }

        .export-card {
            background: var(--linear-bg-primary);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-md);
            padding: var(--linear-spacing-lg);
            text-align: center;
            transition: all var(--linear-transition-medium);
        }

        .export-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--linear-shadow-medium);
        }

        .export-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto var(--linear-spacing-md);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .export-icon.excel {
            background: var(--linear-color-success);
        }

        .export-icon.print {
            background: var(--linear-color-info);
        }

        .export-icon.share {
            background: var(--linear-color-warning);
        }

        .export-details h5 {
            font-size: var(--linear-text-body);
            font-weight: var(--linear-font-weight-semibold);
            color: var(--linear-text-primary);
            margin: 0 0 var(--linear-spacing-xs) 0;
        }

        .export-details p {
            font-size: var(--linear-text-small);
            color: var(--linear-text-secondary);
            margin: 0 0 var(--linear-spacing-md) 0;
        }

        @media (max-width: 768px) {
            .result-container {
                padding: var(--linear-spacing-md);
            }

            .search-container,
            .measurement-selector,
            .results-container {
                padding: var(--linear-spacing-lg);
            }

            .search-grid {
                grid-template-columns: 1fr 1fr;
            }

            .results-grid {
                grid-template-columns: 1fr;
            }

            /* 모바일에서 토글 개선 */
            .search-toggle,
            .measurement-toggle,
            .production-toggle {
                padding: var(--linear-spacing-sm) var(--linear-spacing-md);
                border-radius: var(--linear-radius-md);
            }

            .search-toggle:active,
            .measurement-toggle:active,
            .production-toggle:active {
                background-color: var(--linear-bg-tertiary);
                transform: scale(0.98);
            }

            /* 제작 조건 설정 모바일 스타일 */
            .production-settings {
                padding: var(--linear-spacing-lg);
            }

            /* Enhanced Production Results Mobile Styles */
            .production-summary {
                grid-template-columns: 1fr;
                gap: var(--linear-spacing-sm);
                padding: var(--linear-spacing-md);
            }

            .summary-card {
                padding: var(--linear-spacing-md);
            }

            .summary-icon {
                font-size: 1.5rem;
            }

            .summary-content h3 {
                font-size: var(--linear-text-title4);
            }

            .summary-content h3.car-dimensions {
                font-size: calc(var(--linear-text-title4) * 0.9); /* 10% 축소 */
            }

            .material-item {
                flex-direction: column;
                text-align: center;
                gap: var(--linear-spacing-sm);
            }

            .material-progress {
                width: 100%;
                margin-top: var(--linear-spacing-sm);
            }

            .area-highlight {
                padding: var(--linear-spacing-lg);
            }

            .area-value {
                font-size: 2rem;
            }

            .dimension-item {
                grid-template-columns: 1fr;
                gap: var(--linear-spacing-sm);
                text-align: center;
            }

            .dimension-bar {
                justify-content: center;
                min-width: auto;
            }

            .corner-specifications {
                gap: var(--linear-spacing-sm);
            }

            .spec-group {
                padding: var(--linear-spacing-sm);
            }

            .transom-overview {
                gap: var(--linear-spacing-md);
            }

            .transom-specs {
                flex-direction: column;
                gap: var(--linear-spacing-sm);
            }

            .drilling-positions {
                grid-template-columns: 1fr;
            }

            .export-options {
                grid-template-columns: 1fr;
                gap: var(--linear-spacing-md);
            }

            .export-card {
                padding: var(--linear-spacing-md);
            }

            .export-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }

            /* 모바일에서 (신규/MOD 선택) 토글을 한 행에 표시 */
            .project-type-field .field-row {
                flex-direction: column;
                gap: var(--linear-spacing-md);
            }

            .project-type-field .field-label {
                min-width: 100px; /* 모바일에서 라벨 너비 줄임 */
                font-size: var(--linear-text-small);
            }

            /* 모바일에서 카구조 정보 표시 스타일 */
            .project-type-field .field-row > div:last-child {
                margin-left: 0;
                margin-top: 10px;
            }

            .project-type-field .field-row > div:last-child span {
                min-width: 100px;
                font-size: 0.8rem;
            }

            .project-type-toggle {
                flex-direction: row;
                height: 38px; /* 모바일에서 텍스트가 보이도록 높이 증가 */
                max-width: 160px; /* 모바일에서 토글 너비 줄임 */
            }

            .toggle-option {
                padding: 4px 6px;
                font-size: var(--linear-text-small);
                min-height: 38px; /* 모바일에서 텍스트가 보이도록 높이 증가 */
                gap: 1px;
            }

            /* 모바일에서 높이 입력 필드 */
            .height1-11-field .field-row,
            .height-field .field-row {
                flex-direction: row;
                gap: var(--linear-spacing-md);
            }

            .height1-11-field .field-label,
            .height-field .field-label {
                min-width: 140px; /* 모바일에서 라벨 너비 줄임 */
                font-size: var(--linear-text-small);
            }

            .height-input-group.compact-input {
                max-width: 120px; /* 모바일에서 단위 포함해서 너비 증가 */
            }

            .height-input-group.compact-input input {
                width: 70px; /* 모바일에서 입력 필드 더 작게 */
                font-size: var(--linear-text-small);
            }

            .height-input-group.compact-input .unit {
                font-size: var(--linear-text-small);
                background: transparent !important; /* 배경 제거 */
                border: none !important; /* 테두리 제거 */
                padding: 0 !important; /* 패딩 제거 */
                margin-left: 3px; /* 모바일에서 간격 조정 */
            }

            .exclusion-options {
                gap: var(--linear-spacing-sm);
                flex-direction: row;
            }

            /* 모바일에서 제작 높이를 라벨과 같은 행에 표시 */
            .production-field.height-field {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: var(--linear-spacing-sd);
            }

            .production-field.height-field .field-label {
                margin-bottom: 0;
                flex-shrink: 0;
            }

            .production-field.height-field .height-input-group {
                flex-shrink: 0;
                min-width: 80px;
            }

            .height-input-group input {
                text-align: center;
                min-width: 80px;
            }

            .section-header h3 {
                font-size: var(--linear-text-body);
            }

            .toggle-icon {
                font-size: 1rem;
            }
        }

        @media (max-width: 576px) {
            .search-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Panel Visualization Styles */
        .panel-visualization-container {
            background-color: var(--linear-bg-primary);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-lg);
            padding: var(--linear-spacing-2xl);
            box-shadow: var(--linear-shadow-low);
            margin-bottom: var(--linear-spacing-xl);
        }

        .car-wall-container {
            position: relative;
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            margin-bottom: var(--linear-spacing-lg);
        }

        .car-wall {
            position: relative;
            width: 100%;
            height: 400px;
            border: 2px solid var(--linear-border-primary);
            background-color: var(--linear-bg-secondary);
            border-radius: var(--linear-radius-md);
        }

        .panel {
            position: absolute;
            border: 2px solid var(--linear-border-primary);
            background-color: var(--linear-bg-primary);
            border-radius: var(--linear-radius-sm);
            cursor: default;
            transition: all var(--linear-transition-fast);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: var(--linear-font-weight-semibold);
            color: var(--linear-text-primary);
            font-size: var(--linear-text-small);
            user-select: none;
        }

        .panel:hover {
            background-color: var(--linear-brand-primary-tint);
            border-color: var(--linear-brand-primary);
            transform: scale(1.05);
        }

        .panel.has-info {
            background-color: var(--linear-success-bg) !important;
            border-color: var(--linear-success-border) !important;
            color: var(--linear-success-text) !important;
        }

        /* Panel positioning - 12개 패널 배치 */
        /* 상단 패널 5,6,7 - 균등 분할 */
        .panel-5 { top: 0%; left: 5%; width: 28%; height: 4%; }
        .panel-6 { top: 0%; left: 35%; width: 30%; height: 4%; }
        .panel-7 { top: 0%; left: 67%; width: 28%; height: 4%; }

        /* 좌측 패널 2,3,4 - 균등 분할 */
        .panel-4 { top: 12%; left: 1%; width: 2%; height: 23%; }
        .panel-3 { top: 37.5%; left: 1%; width: 2%; height: 23%; }
        .panel-2 { top: 63%; left: 1%; width: 2%; height: 23%; }

        /* 우측 패널 8,9,10 - 균등 분할 */
        .panel-8 { top: 12%; left: 98%; width: 2%; height: 23%; }
        .panel-9 { top: 37.5%; left: 98%; width: 2%; height: 23%; }
        .panel-10 { top: 63%; left: 98%; width: 2%; height: 23%; }

        /* 하단 패널 1,12,11 - 균등 분할 */
        .panel-1 { bottom: 2%; left: 5%; width: 28%; height: 4%; }
        .panel-12 { bottom: 2%; left: 35%; width: 30%; height: 4%; background-color: var(--linear-accent-bg); border-color: var(--linear-accent-border); }
        .panel-11 { bottom: 2%; left: 67%; width: 28%; height: 4%; }

        /* Transom 패널 특별 스타일 */
        .transom-panel {
            background-color: var(--linear-accent-bg, #f0f9ff) !important;
            border-color: var(--linear-accent-border, #0ea5e9) !important;
            color: var(--linear-accent-text, #0369a1) !important;
            font-weight: var(--linear-font-weight-bold) !important;
        }

        .transom-panel:hover {
            background-color: var(--linear-accent-bg-hover, #e0f2fe) !important;
            border-color: var(--linear-accent-border-hover, #0284c7) !important;
        }

        /* Panel info display on panels */
        .panel-info {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            line-height: 1.1;
            pointer-events: none;
            z-index: 2;
        }

        .panel-info .material {
            font-size: 0.7rem; /* 원래 크기의 1.6배 (20% 감소) */
            opacity: 0.9;
            font-weight: var(--linear-font-weight-bold);
        }

        .panel-info .dimensions {
            font-size: 0.8rem; 
            opacity: 0.8;
            font-weight: var(--linear-font-weight-semibold);
        }

        /* width 값 스타일링 */
        .panel-info .dimensions .width-value {
            font-size: 1.2rem; /* 20% 크게 */
            font-weight: var(--linear-font-weight-bold);
        }

        /* 패널 width 값 색상 설정 - 라이트/다크 모드 대응 */
        .panel-width-value {
            color: #1a73e8; /* 기본 파란색 */
        }

        /* 다크 모드에서 노란색으로 변경 */
        [data-theme="dark"] .panel-width-value {
            color: #ffd700; /* 노란색 */
        }

        /* 타공 정보 표시 스타일 */
        .panel-info .drilling-info {
            font-size: 0.98rem; /* 0.7rem * 1.4 = 0.98rem */
            color: #ff6b6b; /* panel_measurement.php와 동일한 색상 */
            font-weight: bold;
            margin-top: 2px;
            text-shadow: 0 0 2px rgba(255, 255, 255, 0.8);
            transform: translateX(5ch); /* 센터로 5자 크기만큼 이동 */
            display: block;
        }

        /* 2,3,4번 패널의 타공 정보를 오른쪽으로 16문자 이동 */
        .panel-2 .panel-info .drilling-info,
        .panel-3 .panel-info .drilling-info,
        .panel-4 .panel-info .drilling-info {
            transform: translateX(21ch); /* 5ch + 10ch + 6ch = 21ch */
        }

        /* 8,9,10번 패널의 타공 정보를 왼쪽으로 16문자 이동 */
        .panel-8 .panel-info .drilling-info,
        .panel-9 .panel-info .drilling-info,
        .panel-10 .panel-info .drilling-info {
            transform: translateX(-11ch); /* 5ch - 10ch - 6ch = -11ch */
        }

        /* 타공이 있는 패널의 치수 정보 위치 조정 */
        .panel-3.has-drilling .panel-info .dimensions {
            left: calc(50% + 7em) !important;
            font-size: 0.9rem !important;
            max-width: 150px !important;
            text-align: left !important;
            white-space: normal !important;
            line-height: 1.1 !important;
        }

        .panel-9.has-drilling .panel-info .dimensions {
            left: calc(50% - 7em) !important;
            font-size: 0.9rem !important;
            max-width: 150px !important;
            text-align: left !important;
            white-space: normal !important;
            line-height: 1.1 !important;
        }

        /* 자동 모드에서 시스템 다크 모드일 때 노란색 */
        @media (prefers-color-scheme: dark) {
            html:not([data-theme="light"]) .panel-width-value {
                color: #ffd700; /* 노란색 */
            }
        }

        .panel-info-text {
            text-align: center;
            color: var(--linear-text-secondary);
            font-size: var(--linear-text-small);
            margin-top: var(--linear-spacing-md);
        }

        .panel-info-text i {
            color: var(--linear-brand-primary);
            margin-right: var(--linear-spacing-xs);
        }

        /* 데스크톱에서 2,3,4번 패널의 재질과 치수 정보만 우측으로 80픽셀 이동 (패널 번호는 중앙 유지) */
        .panel-2 .panel-info .material,
        .panel-2 .panel-info .dimensions,
        .panel-3 .panel-info .material,
        .panel-3 .panel-info .dimensions,
        .panel-4 .panel-info .material,
        .panel-4 .panel-info .dimensions {
            transform: translateX(75px);
        }

        /* 데스크톱에서 8,9,10번 패널의 재질과 치수 정보만 좌측으로 100픽셀 이동 (패널 번호는 중앙 유지) */
        .panel-8 .panel-info .material,
        .panel-8 .panel-info .dimensions,
        .panel-9 .panel-info .material,
        .panel-9 .panel-info .dimensions,
        .panel-10 .panel-info .material,
        .panel-10 .panel-info .dimensions {
            transform: translateX(-75px);
        }

        /* ㄷ자 몰딩 스타일 - 'ㄷ' 형태 (2번, 10번 패널용) */
        .ending-molding {
            position: absolute;
            border: 3.5px solid #dc3545; /* 빨간색 3.5px 두께 (30% 감소) */
            background: transparent;
            pointer-events: auto; /* hover 효과를 위해 활성화 */
            z-index: 10;
            transition: transform 0.3s ease;
        }

        /* ㄷ자 몰딩 hover 애니메이션 효과 */
        .ending-molding:hover {
            transform: scale(1.05) rotate(1deg);
            animation: molding-shake 0.6s ease-in-out;
        }

        @keyframes molding-shake {
            0%, 100% { transform: scale(1.05) rotate(1deg); }
            25% { transform: scale(1.08) rotate(-1deg); }
            50% { transform: scale(1.05) rotate(2deg); }
            75% { transform: scale(1.08) rotate(-0.5deg); }
        }

        /* 2번 패널 ㄷ자 몰딩 - 좌측 세로 패널 끝에 붙음 */
        .ending-molding-2 {
            top: 82%; /* 2번 패널 하단 부분에 위치 */
            left: calc(8% - 33px); /* 왼쪽으로 35px 이동 */
            width: 1%; /* 절반 크기로 축소 */
            height: 4%;
            border-bottom: 3.5px solid #dc3545;
            border-left: 3.5px solid #dc3545;
            border-right: 3.5px solid #dc3545;
            border-top: none; /* 'ㄷ' 형태 - 위쪽 열림 */
            border-radius: 0 0 2px 2px;
        }

        /* 10번 패널 ㄷ자 몰딩 - 우측 세로 패널 끝에 붙음 */
        .ending-molding-10 {
            top: 82%; /* 10번 패널 하단 부분에 위치 */
            left: calc(90.5% + 45px); /* 우측으로 35px 이동 */
            width: 1%; /* 절반 크기로 축소 */
            height: 4%; /* 10번 패널 높이(23%)의 20% = 4.6% */
            border-bottom: 3.5px solid #dc3545;
            border-left: 3.5px solid #dc3545;
            border-right: 3.5px solid #dc3545;
            border-top: none; /* 'ㄷ' 형태 - 위쪽 열림 */
            border-radius: 0 0 2px 2px;
        }

        /* 4번 패널 ㄷ자 몰딩 - 관통형용 좌측 상단 */
        .ending-molding-4 {
            top: 10%; /* 4번 패널 상단 가로 라인과 정확히 맞도록 추가 조정 */
            left: calc(8% - 33px); /* 왼쪽으로 35px 이동 */
            width: 1%; /* 절반 크기로 축소 */
            height: 4%;
            border-top: 3.5px solid #dc3545;
            border-left: 3.5px solid #dc3545;
            border-right: 3.5px solid #dc3545;
            border-bottom: none; /* 'ㄷ' 형태 - 아래쪽 열림 */
            border-radius: 2px 2px 0 0;
        }

        /* 8번 패널 ㄷ자 몰딩 - 관통형용 우측 상단 */
        .ending-molding-8 {
            top: 10%; /* 8번 패널 상단 가로 라인과 정확히 맞도록 추가 조정 */
            left: calc(90.5% + 45px); /* 우측으로 35px 이동 */
            width: 1%; /* 절반 크기로 축소 */
            height: 4%;
            border-top: 3.5px solid #dc3545;
            border-left: 3.5px solid #dc3545;
            border-right: 3.5px solid #dc3545;
            border-bottom: none; /* 'ㄷ' 형태 - 아래쪽 열림 */
            border-radius: 2px 2px 0 0;
        }

        /* ㄴ자 몰딩 스타일 - 'ㄴ' 형태 (좌측 패널용 - 시계방향 90도 회전) */
        .center-molding-left {
            position: absolute;
            background: transparent;
            pointer-events: auto; /* hover 효과를 위해 활성화 */
            z-index: 10;
            transition: transform 0.3s ease;
        }

        /* ㄴ자 몰딩 hover 애니메이션 효과 */
        .center-molding-left:hover {
            transform: scale(1.08) rotate(-1deg);
            animation: center-molding-shake 0.5s ease-in-out;
        }

        @keyframes center-molding-shake {
            0%, 100% { transform: scale(1.08) rotate(-1deg); }
            25% { transform: scale(1.12) rotate(1deg); }
            50% { transform: scale(1.08) rotate(-2deg); }
            75% { transform: scale(1.12) rotate(0.5deg); }
        }

        /* 2번과 3번 사이 ㄴ자 몰딩 - 좌측 'ㄴ' 형태 (시계방향 90도 회전) */
        .center-molding-2-3 {
            top: calc(60% - 15px); /* 2번과 3번 패널 사이 (5px 아래로) */
            left: calc(8% - 30px); /* ㄷ자 몰딩과 같은 left 위치 */
            width: 1%; /* 가로 길이 (짧은 선) */
            height: 5%; /* 세로 길이 (긴 선 - 2.5배) */
            border-bottom: 3.5px solid #dc3545; /* 위쪽 수평선 (짧음) */
            border-left: 3.5px solid #dc3545; /* 왼쪽 수직선 (길음) */
            border-radius: 2px 0 0 0;
        }

        /* 3번과 4번 사이 ㄴ자 몰딩 - 좌측 'ㄴ' 형태 (시계방향 90도 회전) */
        .center-molding-3-4 {
            top: calc(35% + 5px); /* 3번과 4번 패널 사이 (5px 아래로) */
            left: calc(8% - 30px); /* ㄷ자 몰딩과 같은 left 위치 */
            width: 1%; /* 가로 길이 (짧은 선) */
            height: 5%; /* 세로 길이 (긴 선 - 2.5배) */
            border-top: 3.5px solid #dc3545; /* 위쪽 수평선 (짧음) */
            border-left: 3.5px solid #dc3545; /* 왼쪽 수직선 (길음) */
            border-radius: 2px 0 0 0;
        }

        /* ㄴ자 몰딩 스타일 - 'ㄱ' 형태 (우측 패널용 - 좌측 대칭) */
        .center-molding-right {
            position: absolute;
            background: transparent;
            pointer-events: auto; /* hover 효과를 위해 활성화 */
            z-index: 10;
            transition: transform 0.3s ease;
        }

        /* ㄴ자 몰딩 우측 hover 애니메이션 효과 */
        .center-molding-right:hover {
            transform: scale(1.08) rotate(1deg);
            animation: center-molding-right-shake 0.5s ease-in-out;
        }

        @keyframes center-molding-right-shake {
            0%, 100% { transform: scale(1.08) rotate(1deg); }
            25% { transform: scale(1.12) rotate(-1deg); }
            50% { transform: scale(1.08) rotate(2deg); }
            75% { transform: scale(1.12) rotate(-0.5deg); }
        }

        /* 8번과 9번 사이 ㄴ자 몰딩 - 우측 'ㄱ' 형태 (좌측 대칭) */
        .center-molding-8-9 {
            top: calc(35% + 5px); /* 8번과 9번 패널 사이 (10px 아래로) */
            left: calc(90.5% + 45px); /* 오른쪽으로 20px 더 이동 (30px + 10px) */
            width: 1%; /* 가로 길이 (짧은 선) */
            height: 5%; /* 세로 길이 (긴 선 - 2.5배) */
            border-top: 3.5px solid #dc3545; /* 위쪽 수평선 (짧음) */
            border-right: 3.5px solid #dc3545; /* 오른쪽 수직선 (길음) */
            border-radius: 0 2px 0 0;
        }

        /* 9번과 10번 사이 ㄴ자 몰딩 - 우측 'ㄱ' 형태 (좌측 대칭) */
        .center-molding-9-10 {
            top: calc(60% - 15px); /* 9번과 10번 패널 사이  */
            left: calc(90.5% + 45px); /* 오른쪽으로 20px 더 이동 (30px + 10px) */
            width: 1%; /* 가로 길이 (짧은 선) */
            height: 5%; /* 세로 길이 (긴 선 - 2.5배) */
            border-bottom: 3.5px solid #dc3545; /* 위쪽 수평선 (짧음) */
            border-right: 3.5px solid #dc3545; /* 오른쪽 수직선 (길음) */
            border-radius: 0 2px 0 0;
        }

        /* rear(상단) ㄴ자 몰딩 스타일 - 'ㄱ' 형태 90도 회전 (가로 길고 세로 짧음) */
        .center-molding-rear {
            position: absolute;
            background: transparent;
            pointer-events: auto; /* hover 효과를 위해 활성화 */
            z-index: 10;
            transition: transform 0.3s ease;
        }

        /* ㄴ자 몰딩 상단 hover 애니메이션 효과 */
        .center-molding-rear:hover {
            transform: scale(1.1) rotate(-0.5deg);
            animation: center-molding-rear-shake 0.4s ease-in-out;
        }

        @keyframes center-molding-rear-shake {
            0%, 100% { transform: scale(1.1) rotate(-0.5deg); }
            25% { transform: scale(1.15) rotate(0.5deg); }
            50% { transform: scale(1.1) rotate(-1deg); }
            75% { transform: scale(1.15) rotate(0.3deg); }
        }

        /* 5번과 6번 사이 ㄴ자 몰딩 - rear 'ㄴ' 형태 (180도 회전) */
        .center-molding-5-6 {
            top: calc(2% + 15px); /* 상단 패널 아래쪽 (10px 아래로) */
            left: 34%; /* 5번과 6번 패널 사이 */
            width: 4%; /* 가로 길이 (긴 선 - 2.5배) */
            height: 2%; /* 세로 길이 (짧은 선) */
            border-left: 3.5px solid #dc3545; /* 오른쪽 수직선 (짧음) */
            border-top: 3.5px solid #dc3545; /* 위쪽 수평선 (길음) */
            border-radius: 0 2px 0 0;
        }

        /* 6번과 7번 사이 ㄴ자 몰딩 - rear 'ㄴ' 형태 (180도 회전) */
        .center-molding-6-7 {
            top: calc(2% + 15px); /* 상단 패널 아래쪽 (10px 아래로) */
            left: 62%; /* 6번과 7번 패널 사이 */
            width: 4%; /* 가로 길이 (긴 선 - 2.5배) */
            height: 2%; /* 세로 길이 (짧은 선) */
            border-right: 3.5px solid #dc3545; /* 왼쪽 수직선 (짧음) */
            border-top: 3.5px solid #dc3545; /* 위쪽 수평선 (길음) */
            border-radius: 2px 0 0 0;
        }

        /* 코너몰딩 스타일 - SVG 기반 복잡한 L자 형태 */
        .corner-molding {
            position: absolute;
            pointer-events: auto; /* hover 효과를 위해 활성화 */
            z-index: 10;
            transition: transform 0.3s ease;
        }

        /* 코너몰딩 hover 애니메이션 효과 */
        .corner-molding:hover {
            transform: scale(1.12) rotate(2deg);
            animation: corner-molding-shake 0.7s ease-in-out;
        }

        @keyframes corner-molding-shake {
            0%, 100% { transform: scale(1.12) rotate(2deg); }
            20% { transform: scale(1.15) rotate(-1deg); }
            40% { transform: scale(1.12) rotate(3deg); }
            60% { transform: scale(1.18) rotate(-0.5deg); }
            80% { transform: scale(1.12) rotate(1.5deg); }
        }

        /* 몰딩 툴팁 스타일 */
        .molding-tooltip {
            position: absolute;
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            white-space: nowrap;
            z-index: 1000;
            pointer-events: none;
            opacity: 0;
            transform: translateY(-10px);
            transition: opacity 0.3s ease, transform 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .molding-tooltip::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 6px solid transparent;
            border-top-color: rgba(0, 0, 0, 0.9);
        }

        .molding-tooltip.show {
            opacity: 1;
            transform: translateY(-5px);
        }

        /* 몰딩 요소에 title 속성이 있을 때 기본 브라우저 툴팁 숨김 */
        .ending-molding[title],
        .center-molding-left[title],
        .center-molding-right[title],
        .center-molding-rear[title],
        .corner-molding[title] {
            position: relative;
        }

        /* 4번과 5번 연결 코너몰딩 - 좌측 상단 */
        .corner-molding-4-5 {
            top: 5%; /* 4번과 5번 패널 연결 위치 */
            left: 1.5%; /* 좌측 위치 */
            width: 9%; /* 코너 영역 크기 (1.5배) */
            height: 9%; /* 코너 영역 크기 (1.5배) */
        }

        /* 7번과 8번 연결 코너몰딩 - 우측 상단 */
        .corner-molding-7-8 {
            top: 5%; /* 7번과 8번 패널 연결 위치 */
            right: 0%; /* 우측 위치 */
            width: 9%; /* 코너 영역 크기 (1.5배) */
            height: 9%; /* 코너 영역 크기 (1.5배) */
        }

        /* 관통형용 코너몰딩 - 2번 시작 (좌측 하단) */
        .corner-molding-2-start {
            bottom: 5%; /* 2번 패널 하단 위치 */
            left: 1.5%; /* 좌측 위치 */
            width: 9%; /* 코너 영역 크기 (1.5배) */
            height: 9%; /* 코너 영역 크기 (1.5배) */
        }

        /* 관통형용 코너몰딩 - 10번 끝 (우측 하단) */
        .corner-molding-10-end {
            bottom: 5%; /* 10번 패널 하단 위치 */
            right: 0%; /* 우측 위치 */
            width: 9%; /* 코너 영역 크기 (1.5배) */
            height: 9%; /* 코너 영역 크기 (1.5배) */
        }

        /* SVG 스타일 */
        .corner-molding svg {
            width: 100%;
            height: 100%;
        }

        /* Sㄷ자 하부몰딩 (Side Ending Molding) - 측면 하부 */
        .s-ending-molding {
            position: absolute;
            background-color:rgb(185, 61, 61); /* 적색 */
            width: 3px;
            pointer-events: none;
            z-index: 8;
        }

        /* 좌측 Sㄷ자 하부몰딩 */
        .s-ending-molding-left {
            left: 4.5%; /* 좌측 패널들 내부 쪽으로 */
            bottom: 14%; /* 하부 위치 */
            height: 75%; /* 세로 길이 */
        }

        /* 우측 Sㄷ자 하부몰딩 */
        .s-ending-molding-right {
            right: 3%; /* 우측 패널들 내부 쪽으로 */
            bottom: 14%; /* 하부 위치 */
            height: 75%; /* 세로 길이 */
        }

        /* Rㄷ자 하부몰딩 (Rear Ending Molding) - 후면 상단 하부 */
        .r-ending-molding {
            position: absolute;
            background-color:rgb(207, 75, 75); /* 적색 */
            height: 3px;
            top: 6.5%; /* 후면 패널들 하부 */
            left: 6%; /* 중앙 정렬을 위한 시작점 */
            width: 90%; /* 후면 패널들 폭에 맞춤 */
            pointer-events: none;
            z-index: 8;
        }

        /* 모바일 반응형 */
        @media (max-width: 768px) {
            .car-wall {
                height: 300px;
            }

            .panel {
                font-size: 0.7rem;
            }

            /* 모바일에서 패널 내 글자 크기 조정 */
            .panel-info .material {
                font-size: 0.8rem; /* 모바일에서 재질 크기 조정 (20% 감소 적용) */
            }

            .panel-info .dimensions {
                font-size: 0.8rem; /* 모바일에서 약간 작게 */
            }

            .panel-info .dimensions .width-value {
                font-size: 0.96rem; /* 모바일에서 width 값 20% 크게 */
            }

            /* 모바일에서 패널 텍스트 위치 조정 - 왼쪽 패널 (2,3,4번) */
            .panel-2, .panel-3, .panel-4 {
                text-align: center !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }

            /* 모바일에서 패널 텍스트 위치 조정 - 오른쪽 패널 (8,9,10번) */
            .panel-8, .panel-9, .panel-10 {
                text-align: center !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }

            /* 모든 패널의 텍스트가 잘리지 않도록 */
            .panel-item {
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
        }
    </style>
    
    <!-- 개선된 디버그 정보를 JavaScript 콘솔에 출력 -->
    <script>
        // 디버그 모드 확인 (개발 환경에서만 활성화)
        const DEBUG_MODE = <?= json_encode(isset($_GET['debug']) && $_GET['debug'] === '1') ?>;
        
        if (DEBUG_MODE) {
            console.group('🔍 검색 디버그 정보');
            
            console.group('📋 검색 파라미터');
            console.log('현장명:', <?= json_encode($debug_search_params['search_site']) ?>);
            console.log('시작일:', <?= json_encode($debug_search_params['search_date_from']) ?>);
            console.log('종료일:', <?= json_encode($debug_search_params['search_date_to']) ?>);
            console.log('측정자:', <?= json_encode($debug_search_params['search_measurer']) ?>);
            console.log('선택된 측정:', <?= json_encode($debug_search_params['selected_measurement']) ?>);
            console.groupEnd();
            
            console.group('🔧 쿼리 정보');
            console.log('WHERE 조건:', <?= json_encode($debug_where_conditions['where_conditions']) ?>);
            console.log('WHERE 절:', <?= json_encode($debug_where_conditions['where_clause']) ?>);
            console.log('파라미터:', <?= json_encode($debug_where_conditions['params']) ?>);
            console.groupEnd();
            
            console.group('📊 결과 정보');
            console.log('총 측정 데이터 수:', <?= json_encode($debug_table_info['total_count'] ?? 'N/A') ?>);
            console.log('검색 결과 수:', <?= count($measurements) ?>);
            console.log('중복 ID 수:', <?= json_encode($debug_table_info['duplicate_ids'] ?? []) ?>);
            console.groupEnd();
            
            console.group('📝 측정 데이터 목록');
            <?php foreach ($measurements as $idx => $measurement): ?>
            console.log(`[${<?= $idx ?>}] ID: ${<?= json_encode($measurement['id']) ?>}, 현장: ${<?= json_encode($measurement['site_name']) ?>}, 날짜: ${<?= json_encode($measurement['measurement_date']) ?>}, 생성: ${<?= json_encode($measurement['created_at']) ?>}`);
            <?php endforeach; ?>
            console.groupEnd();
            
            console.group('🔍 중복 제거 정보');
            console.log('중복 제거된 ID들:', <?= json_encode($debug_deduplication['duplicate_ids']) ?>);
            console.log('중복 상세 정보:', <?= json_encode($debug_deduplication['duplicate_details']) ?>);
            console.log('최종 측정 데이터 수:', <?= json_encode($debug_deduplication['final_count']) ?>);
            console.groupEnd();
            
            console.groupEnd();
        } else {
            // 간단한 검색 정보만 표시
            console.log('🔍 검색 결과:', <?= count($measurements) ?> + '건의 측정 데이터를 찾았습니다.');
            <?php if (count($measurements) > 0): ?>
            console.log('📅 최근 데이터:', <?= json_encode($measurements[0]['site_name']) ?> + ' (' + <?= json_encode($measurements[0]['measurement_date']) ?> + ')');
            <?php endif; ?>
        }
        
        // 검색 성능 모니터링
        if (typeof performance !== 'undefined') {
            const searchTime = performance.now();
            console.log('⚡ 검색 완료 시간:', Math.round(searchTime) + 'ms');
        }
    </script>
</head>
<body>
    <?php
    // Linear 네비게이션 생성
    require_once '../components/LinearComponent.php';
    require_once '../components/LinearNavigation.php';

    $nav = LinearNavigation::withBrand(
        '<div style="display: flex; align-items: center;">
            <a href="../mywork/index.php" style="color: inherit; text-decoration: none; display: flex; align-items: center; margin-right: 1rem;" title="홈으로 이동">
                <i class="bi bi-house" style="font-size: 2rem;"></i>
            </a>
            <div style="display: flex; align-items: center;">
                <a href="index.php" style="color: inherit; text-decoration: none; display: flex; align-items: center;">
                    <i class="bi bi-building"></i> OSEL
                </a>
            </div>
        </div>',
        'index.php'
    )
    ->addAction('
        <button type="button" id="themeToggleBtn" class="linear-btn linear-btn-ghost linear-btn-sm"
                style="margin-right: 0.5rem; min-width: 40px; min-height: 40px;" title="테마 변경">
            <span id="themeIcon">🌙</span>
        </button>
    ')
    ->addAction('<span style="margin-right: 1rem; color: var(--linear-text-secondary);"> ' . htmlspecialchars($_SESSION["name"]) . '님</span>')
    ->addAction('<a href="../login/logout.php" style="color: var(--linear-text-secondary); text-decoration: none;">로그아웃</a>')
    ->fixed();

    echo $nav;
    ?>

    <div class="result-container" style="margin-top: var(--linear-header-height);">
        <!-- Breadcrumb -->
        <nav class="breadcrumb">
            <a href="index.php">대시보드</a>
            <span class="breadcrumb-separator">/</span>
            <span>제작산출</span>
        </nav>

        <h2 class="page-title">
            <i class="bi bi-calculator"></i> 제작산출
        </h2>

        <!-- Search Form -->
        <div class="search-container">
            <div class="section-header search-toggle" id="searchToggle" style="cursor: pointer; user-select: none;">
                <i class="bi bi-search"></i>
                <h3>측정 데이터 검색</h3>
                <i class="bi bi-chevron-up toggle-icon" id="toggleIcon" style="margin-left: auto; transition: transform 0.3s ease;"></i>
            </div>

            <form method="GET" class="search-form" id="searchForm">
                <div class="search-grid">
                    <div>
                        <label for="searchSite">현장명</label>
                        <input type="text" id="searchSite" name="search_site"
                               value="<?= htmlspecialchars($search_site) ?>" placeholder="현장명 검색">
                    </div>
                    <div>
                        <label for="searchDateFrom">측정일자 (시작)</label>
                        <div class="date-input-wrapper">
                            <input type="date" id="searchDateFrom" name="search_date_from"
                                   class="linear-input responsive-input"
                                   value="<?= htmlspecialchars($search_date_from) ?>">
                        </div>
                    </div>
                    <div>
                        <label for="searchDateTo">측정일자 (종료)</label>
                        <div class="date-input-wrapper">
                            <input type="date" id="searchDateTo" name="search_date_to"
                                   class="linear-input responsive-input"
                                   value="<?= htmlspecialchars($search_date_to) ?>">
                        </div>
                    </div>
                    <div>
                        <label for="searchMeasurer">측정자</label>
                        <select id="searchMeasurer" name="search_measurer">
                            <option value="">전체 측정자</option>
                            <?php foreach ($measurers as $measurer): ?>
                                <option value="<?= htmlspecialchars($measurer) ?>"
                                        <?= $search_measurer === $measurer ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($measurer) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="search-buttons">
                        <?php
                        require_once '../components/LinearButton.php';
                        echo LinearButton::primary('<i class="bi bi-search"></i> 검색')
                            ->addAttribute('type', 'submit')
                            ->addAttribute('style', 'width: 100%;');

                        echo '<a href="result.php" style="text-decoration: none;">' .
                             LinearButton::outline('초기화')
                                ->size('sm')
                                ->addAttribute('style', 'width: 100%;') .
                             '</a>';
                        ?>
                    </div>
                </div>
                <?php if (!empty($selected_measurement)): ?>
                    <input type="hidden" name="measurement_id" value="<?= htmlspecialchars($selected_measurement) ?>">
                <?php endif; ?>
            </form>
        </div>

        <!-- Measurement Selection -->
        <div class="measurement-selector">
            <div class="section-header measurement-toggle" id="measurementToggle" style="cursor: pointer; user-select: none;">
                <i class="bi bi-list-check"></i>
                <h3>측정 데이터 선택 (총 <?= count($measurements) ?>건)</h3>
                <div style="margin-left: auto; display: flex; align-items: center; gap: 10px;">
                    <?php if (count($measurements) > 0): ?>
                        <span style="font-size: 0.9rem; color: var(--linear-text-secondary);">
                            최근 업데이트: <?= date('Y-m-d H:i', strtotime($measurements[0]['updated_at'])) ?>
                        </span>
                    <?php endif; ?>
                    <i class="bi bi-chevron-up toggle-icon" id="measurementToggleIcon" style="transition: transform 0.3s ease;"></i>
                </div>
            </div>

            <!-- 선택된 측정 데이터 표시 영역 -->
            <?php if (!empty($selected_data)): ?>
            <div class="selected-measurement-display" id="selectedMeasurementDisplay" style="margin-top: 15px; padding: 15px; background: var(--linear-bg-secondary); border: 1px solid var(--linear-border-primary); border-radius: 8px; cursor: pointer; transition: all 0.3s ease;">
                <div class="selected-measurement-info">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="bi bi-check-circle-fill" style="color: var(--linear-success-text); font-size: 1.2rem;"></i>
                        <div style="flex: 1;">
                            <div style="font-weight: 600; color: var(--linear-text-primary); margin-bottom: 5px;">
                                <?= htmlspecialchars($selected_data['site_name']) ?>
                            </div>
                            <div style="font-size: 0.9rem; color: var(--linear-text-secondary);">
                                <?= htmlspecialchars($selected_data['measurer_name']) ?> | <?= date('Y-m-d', strtotime($selected_data['measurement_date'])) ?>
                                | 카구조:<?= htmlspecialchars($selected_data['car_structure'] ?? '일반형') ?>
                                <?php if (!empty($selected_data['project_type'])): ?>
                                | <?= htmlspecialchars($selected_data['project_type']) ?>
                                <?php endif; ?>
                            </div>
                            <div style="font-size: 0.8rem; color: var(--linear-text-tertiary); margin-top: 2px;">
                                ID: <?= $selected_data['id'] ?> | 
                                등록: <?= date('m-d H:i', strtotime($selected_data['created_at'])) ?>
                            </div>
                        </div>
                        <div style="color: var(--linear-text-secondary); font-size: 0.9rem;">
                            클릭하여 다른 데이터 선택
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($measurements)): ?>

                <div class="measurement-list" id="measurementList">
                    <?php 
                    error_log("DEBUG: 화면 표시 시작 - 총 측정 데이터 개수: " . count($measurements));
                    $display_count = 0;
                    foreach ($measurements as $index => $measurement):
                        $display_count++;
                        error_log("DEBUG: 화면 표시 [$index] - ID: {$measurement['id']}, 현장: {$measurement['site_name']}, 표시 순서: $display_count");
                        
                        // Parse JSON data to count panels
                        $panel_count = 0;
                        $transom_count = 0;

                        if (!empty($measurement['panel_data'])) {
                            $panel_data_temp = json_decode($measurement['panel_data'], true);
                            if ($panel_data_temp && is_array($panel_data_temp)) {
                                $panel_count = count($panel_data_temp);
                                error_log("DEBUG: 패널 데이터 파싱 성공 - ID: {$measurement['id']}, 패널 수: $panel_count");
                            } else {
                                error_log("DEBUG: 패널 데이터 파싱 실패 - ID: {$measurement['id']}, 원본 데이터: " . substr($measurement['panel_data'], 0, 100));
                            }
                        } else {
                            error_log("DEBUG: 패널 데이터 없음 - ID: {$measurement['id']}");
                        }

                        if (!empty($measurement['transom_data'])) {
                            $transom_data_temp = json_decode($measurement['transom_data'], true);
                            if ($transom_data_temp && !empty($transom_data_temp)) {
                                $transom_count = 1;
                            }
                        }

                        $total_panels = $panel_count + $transom_count;
                        $is_selected = ($selected_measurement == $measurement['id']);
                    ?>
                    <div class="measurement-item <?= $is_selected ? 'selected' : '' ?>"
                         style="position: relative;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="flex: 1; cursor: pointer;" onclick="selectMeasurement(<?= $measurement['id'] ?>)">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <strong><?= htmlspecialchars($measurement['site_name']) ?></strong>
                                        <small style="display: block; color: var(--linear-text-secondary); margin-top: 2px;">
                                            측정자: <?= htmlspecialchars($measurement['measurer_name']) ?> |
                                            측정일: <?= $measurement['measurement_date'] ?> |
                                            카구조:<?= htmlspecialchars($measurement['car_structure'] ?? '일반형') ?> |
                                            패널: <?= $total_panels ?>개
                                        </small>
                                        <small style="display: block; color: var(--linear-text-tertiary); font-size: 0.75rem; margin-top: 1px;">
                                            ID: <?= $measurement['id'] ?> | 
                                            등록: <?= date('m-d H:i', strtotime($measurement['created_at'])) ?>
                                        </small>
                                    </div>
                                    <div style="text-align: right;">
                                        <span style="font-family: monospace; font-size: 0.8rem; color: var(--linear-text-tertiary);">
                                            W<?= $measurement['car_inside_width'] ?>×D<?= $measurement['car_inside_depth'] ?>×H<?= $measurement['car_inside_height'] ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php 
                    error_log("DEBUG: 측정 데이터 표시 완료 - ID: {$measurement['id']}, 현장: {$measurement['site_name']}");
                    endforeach; 
                    error_log("DEBUG: 화면 표시 완료 - 총 표시된 데이터 개수: $display_count");
                    ?>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <div style="text-align: center; padding: 40px 20px; color: var(--linear-text-secondary);">
                        <i class="bi bi-search" style="font-size: 3rem; color: var(--linear-text-tertiary); margin-bottom: 20px; display: block;"></i>
                        <h3 style="margin-bottom: 10px; color: var(--linear-text-primary);">검색 결과가 없습니다</h3>
                        <p style="margin-bottom: 20px;">검색 조건에 맞는 측정 데이터가 없습니다.</p>
                        <div style="font-size: 0.9rem; color: var(--linear-text-tertiary);">
                            <p>다음과 같은 방법을 시도해보세요:</p>
                            <ul style="text-align: left; display: inline-block; margin-top: 10px;">
                                <li>검색 조건을 변경해보세요</li>
                                <li>날짜 범위를 넓혀보세요</li>
                                <li>현장명을 부분적으로 입력해보세요</li>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Production Settings -->
        <?php if ($selected_data): ?>
        <div class="production-settings">
            <div class="section-header production-toggle" id="productionToggle" style="cursor: pointer; user-select: none;">
                <i class="bi bi-gear-fill"></i>
                <h3>제작 조건 설정</h3>
                <i class="bi bi-chevron-up toggle-icon" id="productionToggleIcon" style="margin-left: auto; transition: transform 0.3s ease;"></i>
            </div>

            <div class="production-form" id="productionForm">
                <form id="productionSettingsForm">
                    <input type="hidden" name="measurement_id" value="<?= $selected_measurement ?>">

                    <!-- (신규/MOD 선택) 토글 -->
                    <div class="production-field project-type-field">
                        <div class="field-row">
                            <label class="field-label">
                                <i class="bi bi-tag"></i>
                                (신규/MOD 선택)
                            </label>
                            <div class="project-type-toggle">
                                <?php
                                $current_project_type = $selected_data['project_type'] ?? '신규';
                                ?>
                                <input type="radio" id="projectNew" name="project_type" value="신규"
                                       <?= $current_project_type === '신규' ? 'checked' : '' ?>>
                                <label for="projectNew" class="toggle-option">
                                    <i class="bi bi-plus-circle"></i>
                                    신규
                                </label>

                                <input type="radio" id="projectMod" name="project_type" value="MOD"
                                       <?= $current_project_type === 'MOD' ? 'checked' : '' ?>>
                                <label for="projectMod" class="toggle-option">
                                    <i class="bi bi-arrow-repeat"></i>
                                    MOD
                                </label>
                            </div>
                            
                            <!-- 카구조 정보 표시 (읽기 전용) -->
                            <div style="margin-left: 20px; display: flex; align-items: center; gap: 10px;">
                                <label style="color: var(--linear-text-primary); font-size: 0.9rem; white-space: nowrap;">
                                    <i class="bi bi-diagram-3" style="color: var(--linear-brand-primary); margin-right: 5px;"></i>
                                    카구조:
                                </label>
                                <span style="padding: 8px 12px; border: 1px solid var(--linear-border-primary); border-radius: var(--linear-radius-md);
                                            background-color: var(--linear-bg-secondary); color: var(--linear-text-primary);
                                            font-size: 0.9rem; min-width: 120px; display: inline-block; text-align: center;
                                            <?php
                                            $car_structure = $selected_data['car_structure'] ?? '일반형';
                                            if ($car_structure === '관통형') {
                                                echo 'background-color: var(--linear-color-warning-light); border-color: var(--linear-color-warning);';
                                            } else {
                                                echo 'background-color: var(--linear-color-success-light); border-color: var(--linear-color-success);';
                                            }
                                            ?>">
                                    <?= htmlspecialchars($car_structure) ?>
                                </span>
                            </div>

                            <!-- 아이파크 체크 정보 표시 (읽기 전용) -->
                            <div style="margin-left: 20px; display: flex; align-items: center; gap: 10px;">
                                <label style="color: var(--linear-text-primary); font-size: 0.9rem; white-space: nowrap;">
                                    <i class="bi bi-building-check" style="color: var(--linear-brand-primary); margin-right: 5px;"></i>
                                    아이파크 체크:
                                </label>
                                <?php
                                $ipark_check = $selected_data['ipark_check'] ?? 0;
                                ?>
                                <div style="padding: 8px 12px; border: 1px solid var(--linear-border-primary); border-radius: var(--linear-radius-md);
                                            background-color: var(--linear-bg-secondary); color: var(--linear-text-primary);
                                            font-size: 0.9rem; min-width: 80px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                                    <input type="checkbox"
                                           <?= $ipark_check ? 'checked' : '' ?>
                                           disabled
                                           style="width: 18px; height: 18px; accent-color: var(--linear-brand-primary); cursor: not-allowed;">
                                    <span style="font-size: 0.85rem; color: var(--linear-text-secondary);">
                                        <?= $ipark_check ? 'Y' : 'N' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 패널 옵션설정 -->
                    <div class="production-field">
                        <label class="field-label">
                            <i class="bi bi-exclude"></i>
                            옵션설정
                        </label>
                        <div class="exclusion-options">
                            <?php
                            // 저장된 값 읽기 (기본값 설정)
                            $panel_corners_excluded = intval($selected_data['panel_corners_excluded'] ?? 1);
                            $transom_excluded = intval($selected_data['transom_excluded'] ?? 0);
                            ?>
                            <div class="checkbox-group">
                                <input type="checkbox" id="panelCornersExcluded" name="panel_corners_excluded" value="1"
                                       <?= $panel_corners_excluded ? 'checked' : '' ?>>
                                <label for="panelCornersExcluded">
                                    <i class="bi bi-exclude"></i>
                                    1, 11번 패널 제외
                                </label>
                            </div>

                            <div class="checkbox-group">
                                <input type="checkbox" id="transomExcluded" name="transom_excluded" value="1"
                                       <?= $transom_excluded ? 'checked' : '' ?>>
                                <label for="transomExcluded">
                                    <i class="bi bi-exclude"></i>
                                    트랜섬 제외
                                </label>
                            </div>
                            <?php
                            $molding_included = intval($selected_data['molding_included'] ?? 0);
                            ?>
                            <div class="checkbox-group">
                                <input type="checkbox" id="moldingIncluded" name="molding_included" value="1"
                                    <?= $molding_included ? 'checked' : '' ?>>
                                <label for="moldingIncluded">
                                    <i class="bi bi-border-style"></i>
                                    몰딩 포함
                                </label>
                            </div>                            
                        </div>
                    </div>

                    <!-- 엘리베이터 대수 및 제작 높이 설정 (한 행 배치) -->
                    <div class="production-field" style="display: flex; align-items: center; gap: var(--linear-spacing-lg); flex-wrap: wrap;">
                        <!-- 엘리베이터 대수 -->
                        <div style="display: flex; align-items: center; gap: var(--linear-spacing-sm);">
                            <label class="field-label" style="margin: 0; white-space: nowrap;">
                                <i class="bi bi-building"></i>
                                엘리베이터 대수
                            </label>
                            <?php
                            $elevator_count = intval($selected_data['elevator_count'] ?? 1);
                            ?>
                            <div class="height-input-group">
                                <input type="number" id="elevatorCount" name="elevator_count"
                                       value="<?= $elevator_count ?>"
                                       min="1" max="50" step="1"
                                       style="border: 1px solid var(--linear-border-primary); border-radius: var(--linear-radius-sm); padding: var(--linear-spacing-sm); background: var(--linear-bg-primary); color: var(--linear-text-primary); font-size: var(--linear-text-body); text-align: center; min-width: 50px;">
                                <span style="margin-left: var(--linear-spacing-sm); color: var(--linear-text-secondary); font-size: var(--linear-text-caption);">대</span>
                            </div>
                        </div>

                        <!-- 제작 높이 -->
                        <div class="field-row">
                            <label class="field-label">
                                <i class="bi bi-rulers"></i>
                                제작 높이 (H) - 2~10번 패널
                            </label>
                            <?php
                            // 제작 높이 계산 로직:
                            // 1. production_height 값이 있으면 그 값 사용
                            // 2. production_height 값이 없고 ipark_check가 true이면 car_inside_height - 120
                            // 3. 그 외의 경우 car_inside_height 사용
                            $ipark_check_value = $selected_data['ipark_check'] ?? 0;

                            if (!empty($selected_data['production_height'])) {
                                // 저장된 제작 높이가 있으면 사용
                                $production_height = $selected_data['production_height'];
                            } elseif ($ipark_check_value) {
                                // 저장된 값이 없고 아이파크 체크가 true이면 기본 높이에서 120을 뺀 값
                                $production_height = $selected_data['car_inside_height'] - 120;
                            } else {
                                // 그 외의 경우 카 내부 높이 사용
                                $production_height = $selected_data['car_inside_height'];
                            }
                            ?>
                            <div class="height-input-group compact-input">
                                <input type="number" id="productionHeight" name="production_height"
                                       placeholder="제작높이" min="1" max="3200" step="1"
                                       value="<?= $production_height ?>">
                                <span class="unit">mm</span>
                            </div>
                            <?php if ($ipark_check_value): ?>
                            <div style="margin-top: 5px; font-size: 0.75rem; color: var(--linear-text-secondary); font-style: italic;">
                                <i class="bi bi-info-circle" style="font-size: 0.7rem;"></i>
                                아이파크 체크 시 실측 높이에서 -120mm 적용
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 1,11번 패널 높이 입력 (동적 표시) -->
                    <div class="production-field height-field height1-11-field" id="height1_11Field" style="display: none;">
                        <div class="field-row">
                            <label class="field-label">
                                <i class="bi bi-rulers"></i>
                                제작 높이 (H) - 1,11번 패널
                            </label>
                            <?php
                            // 1,11번 패널 제작 높이 계산 로직:
                            // 1. production_height1_11 값이 있으면 그 값 사용
                            // 2. production_height1_11 값이 없으면 위에서 계산된 production_height 사용
                            if (!empty($selected_data['production_height1_11'])) {
                                $production_height1_11 = $selected_data['production_height1_11'];
                            } else {
                                $production_height1_11 = $production_height;
                            }
                            ?>
                            <div class="height-input-group compact-input">
                                <input type="number" id="productionHeight1_11" name="production_height1_11"
                                       placeholder="1,11번 제작높이" min="1" max="3200" step="1"
                                       value="<?= $production_height1_11 ?>">
                                <span class="unit">mm</span>
                            </div>
                        </div>
                    </div>

                    <!-- 저장 버튼 -->
                    <div class="production-actions" style="display: flex; gap: var(--linear-spacing-sm);">
                        <?php
                        require_once '../components/LinearButton.php';
                        echo LinearButton::primary('<i class="bi bi-save"></i> 설정 적용')
                            ->addAttribute('type', 'button')
                            ->addAttribute('onclick', 'applyProductionSettings()')
                            ->addAttribute('style', 'flex: 1;');
                        ?>
                        <?php
                        echo LinearButton::secondary('<i class="bi bi-file-earmark-excel"></i> 엑셀 내보내기')
                            ->addAttribute('type', 'button')
                            ->addAttribute('onclick', 'exportToExcel()')
                            ->addAttribute('style', 'flex: 1;');
                        ?>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Panel Visualization -->
        <?php if ($selected_data): ?>
        <div class="panel-visualization-container">
            <div class="section-header search-toggle" id="panelVisualizationToggle" style="cursor: pointer; user-select: none;">
                <i class="bi bi-grid-3x3"></i>
                <h3>판넬 시각화 (W<?= $selected_data['car_inside_width'] ?>×D<?= $selected_data['car_inside_depth'] ?>) - <?= htmlspecialchars($selected_data['site_name']) ?></h3>
                <i class="bi bi-chevron-up toggle-icon" id="panelVisualizationToggleIcon" style="margin-left: auto; transition: transform 0.3s ease;"></i>
            </div>

            <div id="panelVisualizationContent" style="display: block;">
                <div class="car-wall-container">
                    <div class="car-wall" id="panelVisualization">
                        <!-- Panels 1-11 and Transom 12 -->
                        <div class="panel panel-1" data-panel="1" title="판넬 1">1</div>
                        <div class="panel panel-2" data-panel="2" title="판넬 2">2</div>
                        <div class="panel panel-3" data-panel="3" title="판넬 3">3</div>
                        <div class="panel panel-4" data-panel="4" title="판넬 4">4</div>
                        <div class="panel panel-5" data-panel="5" title="판넬 5">5</div>
                        <div class="panel panel-6" data-panel="6" title="판넬 6">6</div>
                        <div class="panel panel-7" data-panel="7" title="판넬 7">7</div>
                        <div class="panel panel-8" data-panel="8" title="판넬 8">8</div>
                        <div class="panel panel-9" data-panel="9" title="판넬 9">9</div>
                        <div class="panel panel-10" data-panel="10" title="판넬 10">10</div>
                        <div class="panel panel-11" data-panel="11" title="판넬 11">11</div>
                        <div class="panel panel-12 transom-panel" data-panel="12" title="Transom 12">T</div>

                        <!-- ㄷ자 몰딩 요소들 (2번, 10번 패널용) -->
                        <div class="ending-molding ending-molding-2" id="endingMolding2" style="display: none;"
                             data-tooltip="ㄷ자 몰딩 2번 (좌측 하단)"></div>
                        <div class="ending-molding ending-molding-10" id="endingMolding10" style="display: none;"
                             data-tooltip="ㄷ자 몰딩 10번 (우측 하단)"></div>

                        <!-- 관통형용 ㄷ자 몰딩 요소들 (4번, 8번 패널 상단용) -->
                        <div class="ending-molding ending-molding-4" id="endingMolding4" style="display: none;"
                             data-tooltip="ㄷ자 몰딩 4번 (관통형용 - 좌측 상단)"></div>
                        <div class="ending-molding ending-molding-8" id="endingMolding8" style="display: none;"
                             data-tooltip="ㄷ자 몰딩 8번 (관통형용 - 우측 상단)"></div>

                        <!-- ㄴ자 몰딩 요소들 (패널 사이 연결용) -->
                        <div class="center-molding-left center-molding-2-3" id="centerMolding2_3" style="display: none;"
                             data-tooltip="ㄴ자 몰딩 2-3번 연결 (좌측)"></div>
                        <div class="center-molding-left center-molding-3-4" id="centerMolding3_4" style="display: none;"
                             data-tooltip="ㄴ자 몰딩 3-4번 연결 (좌측)"></div>
                        <div class="center-molding-right center-molding-8-9" id="centerMolding8_9" style="display: none;"
                             data-tooltip="ㄴ자 몰딩 8-9번 연결 (우측)"></div>
                        <div class="center-molding-right center-molding-9-10" id="centerMolding9_10" style="display: none;"
                             data-tooltip="ㄴ자 몰딩 9-10번 연결 (우측)"></div>
                        <div class="center-molding-rear center-molding-5-6" id="centerMolding5_6" style="display: none;"
                             data-tooltip="ㄴ자 몰딩 5-6번 연결 (상단)"></div>
                        <div class="center-molding-rear center-molding-6-7" id="centerMolding6_7" style="display: none;"
                             data-tooltip="ㄴ자 몰딩 6-7번 연결 (상단)"></div>

                        <!-- 코너몰딩 요소들 (SVG 기반) -->
                        <div class="corner-molding corner-molding-4-5" id="cornerMolding4_5" style="display: none;"
                             data-tooltip="코너몰딩 4-5번 연결 (좌상단)">
                            <svg version="1.0" xmlns="http://www.w3.org/2000/svg"
                                 width="100%" height="100%" viewBox="0 0 300.000000 223.000000"
                                 preserveAspectRatio="xMidYMid meet">
                                <g transform="translate(0.000000,223.000000) scale(0.100000,-0.100000)"
                                   fill="#ff0000" stroke="none">
                                    <path d="M1205 1955 c-40 -40 -65 -73 -66 -87 -6 -160 -113 -333 -248 -403
                                    -43 -22 -162 -55 -198 -55 -34 0 -106 -55 -142 -107 l-25 -37 1 -506 c1 -558
                                    -2 -535 65 -596 56 -51 87 -67 124 -61 44 7 101 49 139 101 l30 41 3 398 3
                                    397 39 0 c25 0 40 -5 40 -12 0 -7 2 -186 3 -398 l2 -385 24 -35 c46 -66 94
                                    -101 145 -107 43 -4 116 44 160 105 l31 44 3 466 c2 298 6 470 13 477 6 6 183
                                    12 482 15 l472 5 34 25 c42 32 111 118 111 139 0 38 -22 88 -52 122 -65 71
                                    -44 68 -484 74 -218 2 -398 6 -400 7 -3 4 -6 40 -5 61 1 16 27 17 394 17 321
                                    1 397 3 417 15 14 8 21 15 15 15 -5 0 4 7 22 15 33 14 77 74 88 118 11 42 -27
                                    110 -85 156 l-52 41 -519 0 -519 0 -65 -65z m1105 30 c0 -8 6 -15 14 -15 22 0
                                    66 -40 66 -60 0 -10 8 -20 17 -22 12 -2 17 -15 19 -46 2 -34 -1 -42 -16 -42
                                    -11 0 -20 -10 -24 -30 -4 -22 -15 -35 -41 -47 -19 -9 -35 -21 -35 -27 0 -8
                                    -128 -11 -416 -11 l-416 0 -1 -68 -2 -67 418 0 c364 0 417 -2 417 -15 0 -8 8
                                    -15 18 -15 24 -1 50 -22 61 -53 6 -15 16 -26 23 -25 16 4 15 -81 -1 -92 -7 -4
                                    -18 -18 -24 -31 -7 -13 -27 -31 -44 -39 -18 -8 -33 -21 -33 -27 0 -10 -103
                                    -13 -480 -13 -467 0 -481 -1 -500 -20 -19 -19 -20 -33 -20 -500 0 -383 -3
                                    -480 -13 -480 -7 0 -18 -14 -24 -31 -8 -19 -23 -34 -42 -42 -17 -6 -31 -17
                                    -31 -24 0 -9 -16 -13 -50 -13 -38 0 -50 4 -50 15 0 8 -6 15 -14 15 -8 0 -26
                                    15 -40 32 -15 18 -31 38 -36 45 -6 7 -11 176 -12 424 l-3 411 -65 0 -65 0 -2
                                    -411 c-2 -357 -4 -413 -17 -427 -9 -8 -16 -19 -16 -24 0 -16 -43 -50 -62 -50
                                    -10 0 -18 -7 -18 -15 0 -11 -12 -15 -45 -15 -33 0 -45 4 -45 15 0 8 -9 15 -19
                                    15 -21 0 -61 44 -61 66 0 8 -7 14 -15 14 -13 0 -15 64 -15 520 0 456 2 520 15
                                    520 8 0 15 5 15 12 0 16 51 68 67 68 7 0 13 7 13 15 0 11 12 15 49 15 66 0
                                    182 41 250 87 54 37 118 110 147 165 27 51 54 152 54 201 0 31 4 47 13 47 6 1
                                    21 15 32 33 11 18 30 36 43 42 12 5 22 15 22 22 0 10 108 13 520 13 456 0 520
                                    -2 520 -15z"/>
                                    <path d="M1308 1838 c-1 -7 -2 -22 -3 -33 -6 -100 -67 -248 -137 -331 -112
                                    -134 -246 -207 -425 -231 l-43 -6 0 -483 c0 -316 3 -484 10 -484 7 0 10 165
                                    10 475 l0 475 210 0 210 0 0 -475 c0 -310 3 -475 10 -475 7 0 10 161 10 463 0
                                    295 4 476 11 500 6 21 27 56 46 78 70 77 41 74 581 77 297 2 482 7 482 12 0 6
                                    -180 10 -475 10 l-475 0 0 210 0 210 475 0 c310 0 475 3 475 10 0 16 -970 14
                                    -972 -2z m2 -325 c0 -80 -3 -96 -20 -113 -11 -11 -26 -20 -33 -20 -18 0 -80
                                    -67 -98 -105 l-14 -30 -90 2 c-127 2 -127 2 -121 12 3 5 25 18 50 29 24 12 50
                                    27 57 34 8 7 34 28 59 47 53 40 136 144 170 214 31 62 40 47 40 -70z"/>
                                </g>
                            </svg>
                        </div> 
                        <div class="corner-molding corner-molding-7-8" id="cornerMolding7_8" style="display: none;"
                             data-tooltip="코너몰딩 7-8번 연결 (우상단)">
                            <svg version="1.0" xmlns="http://www.w3.org/2000/svg"
                                 width="100%" height="100%" viewBox="0 0 300.000000 223.000000"
                                 preserveAspectRatio="xMidYMid meet" style="transform: scaleX(-1);">
                                <g transform="translate(0.000000,223.000000) scale(0.100000,-0.100000)"
                                   fill="#ff0000" stroke="none">
                                    <path d="M1205 1955 c-40 -40 -65 -73 -66 -87 -6 -160 -113 -333 -248 -403
                                    -43 -22 -162 -55 -198 -55 -34 0 -106 -55 -142 -107 l-25 -37 1 -506 c1 -558
                                    -2 -535 65 -596 56 -51 87 -67 124 -61 44 7 101 49 139 101 l30 41 3 398 3
                                    397 39 0 c25 0 40 -5 40 -12 0 -7 2 -186 3 -398 l2 -385 24 -35 c46 -66 94
                                    -101 145 -107 43 -4 116 44 160 105 l31 44 3 466 c2 298 6 470 13 477 6 6 183
                                    12 482 15 l472 5 34 25 c42 32 111 118 111 139 0 38 -22 88 -52 122 -65 71
                                    -44 68 -484 74 -218 2 -398 6 -400 7 -3 4 -6 40 -5 61 1 16 27 17 394 17 321
                                    1 397 3 417 15 14 8 21 15 15 15 -5 0 4 7 22 15 33 14 77 74 88 118 11 42 -27
                                    110 -85 156 l-52 41 -519 0 -519 0 -65 -65z m1105 30 c0 -8 6 -15 14 -15 22 0
                                    66 -40 66 -60 0 -10 8 -20 17 -22 12 -2 17 -15 19 -46 2 -34 -1 -42 -16 -42
                                    -11 0 -20 -10 -24 -30 -4 -22 -15 -35 -41 -47 -19 -9 -35 -21 -35 -27 0 -8
                                    -128 -11 -416 -11 l-416 0 -1 -68 -2 -67 418 0 c364 0 417 -2 417 -15 0 -8 8
                                    -15 18 -15 24 -1 50 -22 61 -53 6 -15 16 -26 23 -25 16 4 15 -81 -1 -92 -7 -4
                                    -18 -18 -24 -31 -7 -13 -27 -31 -44 -39 -18 -8 -33 -21 -33 -27 0 -10 -103
                                    -13 -480 -13 -467 0 -481 -1 -500 -20 -19 -19 -20 -33 -20 -500 0 -383 -3
                                    -480 -13 -480 -7 0 -18 -14 -24 -31 -8 -19 -23 -34 -42 -42 -17 -6 -31 -17
                                    -31 -24 0 -9 -16 -13 -50 -13 -38 0 -50 4 -50 15 0 8 -6 15 -14 15 -8 0 -26
                                    15 -40 32 -15 18 -31 38 -36 45 -6 7 -11 176 -12 424 l-3 411 -65 0 -65 0 -2
                                    -411 c-2 -357 -4 -413 -17 -427 -9 -8 -16 -19 -16 -24 0 -16 -43 -50 -62 -50
                                    -10 0 -18 -7 -18 -15 0 -11 -12 -15 -45 -15 -33 0 -45 4 -45 15 0 8 -9 15 -19
                                    15 -21 0 -61 44 -61 66 0 8 -7 14 -15 14 -13 0 -15 64 -15 520 0 456 2 520 15
                                    520 8 0 15 5 15 12 0 16 51 68 67 68 7 0 13 7 13 15 0 11 12 15 49 15 66 0
                                    182 41 250 87 54 37 118 110 147 165 27 51 54 152 54 201 0 31 4 47 13 47 6 1
                                    21 15 32 33 11 18 30 36 43 42 12 5 22 15 22 22 0 10 108 13 520 13 456 0 520
                                    -2 520 -15z"/>
                                    <path d="M1308 1838 c-1 -7 -2 -22 -3 -33 -6 -100 -67 -248 -137 -331 -112
                                    -134 -246 -207 -425 -231 l-43 -6 0 -483 c0 -316 3 -484 10 -484 7 0 10 165
                                    10 475 l0 475 210 0 210 0 0 -475 c0 -310 3 -475 10 -475 7 0 10 161 10 463 0
                                    295 4 476 11 500 6 21 27 56 46 78 70 77 41 74 581 77 297 2 482 7 482 12 0 6
                                    -180 10 -475 10 l-475 0 0 210 0 210 475 0 c310 0 475 3 475 10 0 16 -970 14
                                    -972 -2z m2 -325 c0 -80 -3 -96 -20 -113 -11 -11 -26 -20 -33 -20 -18 0 -80
                                    -67 -98 -105 l-14 -30 -90 2 c-127 2 -127 2 -121 12 3 5 25 18 50 29 24 12 50
                                    27 57 34 8 7 34 28 59 47 53 40 136 144 170 214 31 62 40 47 40 -70z"/>
                                </g>
                            </svg>
                        </div>

                        <!-- 관통형용 코너몰딩 요소들 (2번과 10번 패널 위치) -->
                        <div class="corner-molding corner-molding-2-start" id="cornerMolding2Start" style="display: none;"
                             data-tooltip="코너몰딩 2번 시작 (관통형용)">
                            <svg version="1.0" xmlns="http://www.w3.org/2000/svg"
                                 width="100%" height="100%" viewBox="0 0 300.000000 223.000000"
                                 preserveAspectRatio="xMidYMid meet">
                                <g transform="translate(0.000000,223.000000) scale(0.100000,-0.100000)"
                                   fill="#ff0000" stroke="none">
                                    <path d="M1205 1955 c-40 -40 -65 -73 -66 -87 -6 -160 -113 -333 -248 -403
                                    -43 -22 -162 -55 -198 -55 -34 0 -106 -55 -142 -107 l-25 -37 1 -506 c1 -558
                                    -2 -535 65 -596 56 -51 87 -67 124 -61 44 7 101 49 139 101 l30 41 3 398 3
                                    397 39 0 c25 0 40 -5 40 -12 0 -7 2 -186 3 -398 l2 -385 24 -35 c46 -66 94
                                    -101 145 -107 43 -4 116 44 160 105 l31 44 3 466 c2 298 6 470 13 477 6 6 183
                                    12 482 15 l472 5 34 25 c42 32 111 118 111 139 0 38 -22 88 -52 122 -65 71
                                    -44 68 -484 74 -218 2 -398 6 -400 7 -3 4 -6 40 -5 61 1 16 27 17 394 17 321
                                    1 397 3 417 15 14 8 21 15 15 15 -5 0 4 7 22 15 33 14 77 74 88 118 11 42 -27
                                    110 -85 156 l-52 41 -519 0 -519 0 -65 -65z m1105 30 c0 -8 6 -15 14 -15 22 0
                                    66 -40 66 -60 0 -10 8 -20 17 -22 12 -2 17 -15 19 -46 2 -34 -1 -42 -16 -42
                                    -11 0 -20 -10 -24 -30 -4 -22 -15 -35 -41 -47 -19 -9 -35 -21 -35 -27 0 -8
                                    -128 -11 -416 -11 l-416 0 -1 -68 -2 -67 418 0 c364 0 417 -2 417 -15 0 -8 8
                                    -15 18 -15 24 -1 50 -22 61 -53 6 -15 16 -26 23 -25 16 4 15 -81 -1 -92 -7 -4
                                    -18 -18 -24 -31 -7 -13 -27 -31 -44 -39 -18 -8 -33 -21 -33 -27 0 -10 -103
                                    -13 -480 -13 -467 0 -481 -1 -500 -20 -19 -19 -20 -33 -20 -500 0 -383 -3
                                    -480 -13 -480 -7 0 -18 -14 -24 -31 -8 -19 -23 -34 -42 -42 -17 -6 -31 -17
                                    -31 -24 0 -9 -16 -13 -50 -13 -38 0 -50 4 -50 15 0 8 -6 15 -14 15 -8 0 -26
                                    15 -40 32 -15 18 -31 38 -36 45 -6 7 -11 176 -12 424 l-3 411 -65 0 -65 0 -2
                                    -411 c-2 -357 -4 -413 -17 -427 -9 -8 -16 -19 -16 -24 0 -16 -43 -50 -62 -50
                                    -10 0 -18 -7 -18 -15 0 -11 -12 -15 -45 -15 -33 0 -45 4 -45 15 0 8 -9 15 -19
                                    15 -21 0 -61 44 -61 66 0 8 -7 14 -15 14 -13 0 -15 64 -15 520 0 456 2 520 15
                                    520 8 0 15 5 15 12 0 16 51 68 67 68 7 0 13 7 13 15 0 11 12 15 49 15 66 0
                                    182 41 250 87 54 37 118 110 147 165 27 51 54 152 54 201 0 31 4 47 13 47 6 1
                                    21 15 32 33 11 18 30 36 43 42 12 5 22 15 22 22 0 10 108 13 520 13 456 0 520
                                    -2 520 -15z"/>
                                    <path d="M1308 1838 c-1 -7 -2 -22 -3 -33 -6 -100 -67 -248 -137 -331 -112
                                    -134 -246 -207 -425 -231 l-43 -6 0 -483 c0 -316 3 -484 10 -484 7 0 10 165
                                    10 475 l0 475 210 0 210 0 0 -475 c0 -310 3 -475 10 -475 7 0 10 161 10 463 0
                                    295 4 476 11 500 6 21 27 56 46 78 70 77 41 74 581 77 297 2 482 7 482 12 0 6
                                    -180 10 -475 10 l-475 0 0 210 0 210 475 0 c310 0 475 3 475 10 0 16 -970 14
                                    -972 -2z m2 -325 c0 -80 -3 -96 -20 -113 -11 -11 -26 -20 -33 -20 -18 0 -80
                                    -67 -98 -105 l-14 -30 -90 2 c-127 2 -127 2 -121 12 3 5 25 18 50 29 24 12 50
                                    27 57 34 8 7 34 28 59 47 53 40 136 144 170 214 31 62 40 47 40 -70z"/>
                                </g>
                            </svg>
                        </div>
                        <div class="corner-molding corner-molding-10-end" id="cornerMolding10End" style="display: none;"
                             data-tooltip="코너몰딩 10번 끝 (관통형용)">
                            <svg version="1.0" xmlns="http://www.w3.org/2000/svg"
                                 width="100%" height="100%" viewBox="0 0 300.000000 223.000000"
                                 preserveAspectRatio="xMidYMid meet" style="transform: scaleX(-1);">
                                <g transform="translate(0.000000,223.000000) scale(0.100000,-0.100000)"
                                   fill="#ff0000" stroke="none">
                                    <path d="M1205 1955 c-40 -40 -65 -73 -66 -87 -6 -160 -113 -333 -248 -403
                                    -43 -22 -162 -55 -198 -55 -34 0 -106 -55 -142 -107 l-25 -37 1 -506 c1 -558
                                    -2 -535 65 -596 56 -51 87 -67 124 -61 44 7 101 49 139 101 l30 41 3 398 3
                                    397 39 0 c25 0 40 -5 40 -12 0 -7 2 -186 3 -398 l2 -385 24 -35 c46 -66 94
                                    -101 145 -107 43 -4 116 44 160 105 l31 44 3 466 c2 298 6 470 13 477 6 6 183
                                    12 482 15 l472 5 34 25 c42 32 111 118 111 139 0 38 -22 88 -52 122 -65 71
                                    -44 68 -484 74 -218 2 -398 6 -400 7 -3 4 -6 40 -5 61 1 16 27 17 394 17 321
                                    1 397 3 417 15 14 8 21 15 15 15 -5 0 4 7 22 15 33 14 77 74 88 118 11 42 -27
                                    110 -85 156 l-52 41 -519 0 -519 0 -65 -65z m1105 30 c0 -8 6 -15 14 -15 22 0
                                    66 -40 66 -60 0 -10 8 -20 17 -22 12 -2 17 -15 19 -46 2 -34 -1 -42 -16 -42
                                    -11 0 -20 -10 -24 -30 -4 -22 -15 -35 -41 -47 -19 -9 -35 -21 -35 -27 0 -8
                                    -128 -11 -416 -11 l-416 0 -1 -68 -2 -67 418 0 c364 0 417 -2 417 -15 0 -8 8
                                    -15 18 -15 24 -1 50 -22 61 -53 6 -15 16 -26 23 -25 16 4 15 -81 -1 -92 -7 -4
                                    -18 -18 -24 -31 -7 -13 -27 -31 -44 -39 -18 -8 -33 -21 -33 -27 0 -10 -103
                                    -13 -480 -13 -467 0 -481 -1 -500 -20 -19 -19 -20 -33 -20 -500 0 -383 -3
                                    -480 -13 -480 -7 0 -18 -14 -24 -31 -8 -19 -23 -34 -42 -42 -17 -6 -31 -17
                                    -31 -24 0 -9 -16 -13 -50 -13 -38 0 -50 4 -50 15 0 8 -6 15 -14 15 -8 0 -26
                                    15 -40 32 -15 18 -31 38 -36 45 -6 7 -11 176 -12 424 l-3 411 -65 0 -65 0 -2
                                    -411 c-2 -357 -4 -413 -17 -427 -9 -8 -16 -19 -16 -24 0 -16 -43 -50 -62 -50
                                    -10 0 -18 -7 -18 -15 0 -11 -12 -15 -45 -15 -33 0 -45 4 -45 15 0 8 -9 15 -19
                                    15 -21 0 -61 44 -61 66 0 8 -7 14 -15 14 -13 0 -15 64 -15 520 0 456 2 520 15
                                    520 8 0 15 5 15 12 0 16 51 68 67 68 7 0 13 7 13 15 0 11 12 15 49 15 66 0
                                    182 41 250 87 54 37 118 110 147 165 27 51 54 152 54 201 0 31 4 47 13 47 6 1
                                    21 15 32 33 11 18 30 36 43 42 12 5 22 15 22 22 0 10 108 13 520 13 456 0 520
                                    -2 520 -15z"/>
                                    <path d="M1308 1838 c-1 -7 -2 -22 -3 -33 -6 -100 -67 -248 -137 -331 -112
                                    -134 -246 -207 -425 -231 l-43 -6 0 -483 c0 -316 3 -484 10 -484 7 0 10 165
                                    10 475 l0 475 210 0 210 0 0 -475 c0 -310 3 -475 10 -475 7 0 10 161 10 463 0
                                    295 4 476 11 500 6 21 27 56 46 78 70 77 41 74 581 77 297 2 482 7 482 12 0 6
                                    -180 10 -475 10 l-475 0 0 210 0 210 475 0 c310 0 475 3 475 10 0 16 -970 14
                                    -972 -2z m2 -325 c0 -80 -3 -96 -20 -113 -11 -11 -26 -20 -33 -20 -18 0 -80
                                    -67 -98 -105 l-14 -30 -90 2 c-127 2 -127 2 -121 12 3 5 25 18 50 29 24 12 50
                                    27 57 34 8 7 34 28 59 47 53 40 136 144 170 214 31 62 40 47 40 -70z"/>
                                </g>
                            </svg>
                        </div>

                        <!-- Sㄷ자 하부몰딩 (Side Ending Molding) - 측면 하부 3px 라인 -->
                        <div class="s-ending-molding s-ending-molding-left" id="sEndingMoldingLeft" style="display: none;"></div>
                        <div class="s-ending-molding s-ending-molding-right" id="sEndingMoldingRight" style="display: none;"></div>

                        <!-- Rㄷ자 하부몰딩 (Rear Ending Molding) - 후면 상단 하부 3px 라인 -->
                        <div class="r-ending-molding" id="rEndingMolding" style="display: none;"></div>
                    </div>
                </div>

                <div class="panel-info-text">
                    <i class="bi bi-info-circle"></i>
                    패널에 마우스를 올리면 측정 정보를 확인할 수 있습니다.
                </div>

                <!-- 몰딩 절단치수 정보 테이블 -->
                <div class="molding-info-container" id="moldingInfoContainer" style="display: none; margin-top: var(--linear-spacing-lg);">
                    <div class="section-subtitle" style="margin-bottom: var(--linear-spacing-md); color: var(--linear-text-primary); font-weight: 600; display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center;">
                            <i class="bi bi-scissors" style="margin-right: var(--linear-spacing-xs);"></i>
                            <span id="moldingInfoTitle">몰딩 절단치수 정보 (<?= $selected_data['elevator_count'] ?>대)</span>
                        </div>
                        <button
                            id="printMoldingButton"
                            onclick="printMoldingTable()"
                            style="
                                background: var(--linear-brand-primary);
                                color: white;
                                border: none;
                                border-radius: var(--linear-radius-sm);
                                padding: var(--linear-spacing-xs) var(--linear-spacing-sm);
                                font-size: 0.875rem;
                                font-weight: 500;
                                cursor: pointer;
                                display: flex;
                                align-items: center;
                                gap: var(--linear-spacing-xs);
                                transition: all 0.2s;
                            "
                            onmouseover="this.style.background='var(--linear-brand-hover)'"
                            onmouseout="this.style.background='var(--linear-brand-primary)'"
                        >
                            <i class="bi bi-printer"></i>
                            <span>인쇄</span>
                        </button>
                    </div>

                    <div class="molding-table-container" style="background: var(--linear-bg-secondary); border-radius: var(--linear-radius-md); padding: var(--linear-spacing-md); border: 1px solid var(--linear-border-secondary);">
                        <table class="molding-info-table" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: var(--linear-bg-tertiary); border-bottom: 2px solid var(--linear-border-primary);">
                                    <th style="padding: var(--linear-spacing-sm); text-align: left; font-weight: 600; color: var(--linear-text-primary);">몰딩 종류</th>
                                    <th style="padding: var(--linear-spacing-sm); text-align: center; font-weight: 600; color: var(--linear-text-primary);">절단치수</th>
                                    <th style="padding: var(--linear-spacing-sm); text-align: center; font-weight: 600; color: var(--linear-text-primary);">개수</th>
                                    <th style="padding: var(--linear-spacing-sm); text-align: center; font-weight: 600; color: var(--linear-text-primary);">대수</th>
                                    <th style="padding: var(--linear-spacing-sm); text-align: center; font-weight: 600; color: var(--linear-text-primary);">총개수</th>
                                </tr>
                            </thead>
                            <tbody id="moldingTableBody">
                                <!-- 몰딩 정보가 JavaScript로 채워집니다 -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Production Results -->
        <?php if ($selected_data && !empty($production_results)): ?>
        <div class="results-container">
            <div class="section-header search-toggle" id="productionResultsToggle" style="cursor: pointer; user-select: none; display: flex; align-items: center;">
                <i class="bi bi-gear"></i>
                <h3>제작산출 결과 - <?= htmlspecialchars($selected_data['site_name']) ?></h3>
                <button
                    id="excelExportBtn"
                    onclick="event.stopPropagation(); exportToExcel()"
                    style="
                        background: var(--linear-color-success);
                        color: white;
                        border: none;
                        border-radius: var(--linear-radius-sm);
                        padding: var(--linear-spacing-xs) var(--linear-spacing-sm);
                        font-size: 0.875rem;
                        font-weight: 500;
                        cursor: pointer;
                        display: flex;
                        align-items: center;
                        gap: var(--linear-spacing-xs);
                        transition: all 0.2s;
                        margin-left: auto;
                        margin-right: var(--linear-spacing-sm);
                    "
                    onmouseover="this.style.background='var(--linear-color-success-dark)'"
                    onmouseout="this.style.background='var(--linear-color-success)'"
                >
                    <i class="bi bi-file-earmark-excel"></i>
                    <span>Excel 내보내기</span>
                </button>
                <i class="bi bi-chevron-up toggle-icon" id="productionResultsToggleIcon" style="transition: transform 0.3s ease;"></i>
            </div>

            <div id="productionResultsContent" style="display: block;">
                <!-- 상단 요약 카드 섹션 -->
                <div class="production-summary">
                    <div class="summary-card primary">
                        <div class="summary-icon">
                            <i class="bi bi-building"></i>
                        </div>
                        <div class="summary-content">
                            <h3><?= htmlspecialchars($selected_data['site_name']) ?></h3>
                            <p class="summary-subtitle">현장명</p>
                        </div>
                    </div>

                    <div class="summary-card">
                        <div class="summary-icon">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <div class="summary-content">
                            <h3><?= date('Y.m.d', strtotime($selected_data['measurement_date'])) ?></h3>
                            <p class="summary-subtitle">측정일자</p>
                        </div>
                    </div>

                    <div class="summary-card">
                        <div class="summary-icon">
                            <i class="bi bi-person-badge"></i>
                        </div>
                        <div class="summary-content">
                            <h3><?= htmlspecialchars($selected_data['measurer_name']) ?></h3>
                            <p class="summary-subtitle">측정자</p>
                        </div>
                    </div>

                    <div class="summary-card">
                        <div class="summary-icon">
                            <i class="bi bi-bounding-box"></i>
                        </div>
                        <div class="summary-content">
                            <h3 class="car-dimensions">W<?= $selected_data['car_inside_width'] ?>×D<?= $selected_data['car_inside_depth'] ?>×H<?= $selected_data['car_inside_height'] ?></h3>
                            <p class="summary-subtitle">카 내부 치수 (mm)</p>
                        </div>
                    </div>

                    <div class="summary-card highlight">
                        <div class="summary-icon">
                            <i class="bi bi-grid-3x3"></i>
                        </div>
                        <div class="summary-content">
                            <h3><?= $production_results['total_panels'] ?>개</h3>
                            <p class="summary-subtitle">총 패널 수</p>
                        </div>
                    </div>
                </div>

                <div class="results-grid">

                <!-- Material Summary -->
                <div class="result-card enhanced">
                    <div class="card-header">
                        <h4><i class="bi bi-layers"></i> 재질별 패널 구성</h4>
                        <span class="card-badge">Material Analysis</span>
                    </div>
                    <div class="card-content">
                        <?php if (!empty($production_results['material_summary'])): ?>
                            <div class="material-grid">
                                <?php foreach ($production_results['material_summary'] as $material => $count): ?>
                                <?php
                                // 재질명이 비어있는 경우 처리
                                $display_material = !empty($material) ? $material : '미지정';
                                
                                // Transom 정보 확인 - 여러 소스에서 확인
                                $has_transom = false;
                                
                                // 1. transom_details에서 확인
                                if (!empty($production_results['transom_details']) && 
                                    ($production_results['transom_details']['material_type'] ?? '') === $material) {
                                    $has_transom = true;
                                }
                                
                                // 2. transom_data에서 직접 확인
                                if (!$has_transom && !empty($transom_data)) {
                                    $transom_material = null;
                                    if (isset($transom_data['12']['materialType'])) {
                                        $transom_material = $transom_data['12']['materialType'];
                                    } elseif (isset($transom_data['transom']['materialType'])) {
                                        $transom_material = $transom_data['transom']['materialType'];
                                    }
                                    if ($transom_material === $material) {
                                        $has_transom = true;
                                    }
                                }
                                
                                // 3. panel_data['12']에서 확인
                                if (!$has_transom && isset($panel_data['12']['materialType']) && 
                                    $panel_data['12']['materialType'] === $material) {
                                    $has_transom = true;
                                }
                                
                                // 4. transom_data 존재 여부로 확인 (재질이 매치되지 않더라도 transom이 있으면 표시)
                                if (!$has_transom && !empty($transom_data)) {
                                    $has_transom = true;
                                }
                                $transom_count = 0;
                                if ($has_transom) {
                                    $transom_count = 1;
                                    $panel_count = $count - 1; // transom 1개 제외
                                } else {
                                    $panel_count = $count;
                                }
                                
                                // 디버깅을 위한 로그
                                error_log("DEBUG: 재질별 패널 구성 - 재질: '$display_material', 총 개수: $count, 패널 개수: $panel_count, Transom 포함: " . ($has_transom ? 'Yes' : 'No'));
                                ?>
                                <div class="material-item <?= $has_transom ? 'has-transom' : '' ?>">
                                    <div class="material-icon">
                                        <i class="bi bi-pentagon"></i>
                                        <?php if ($has_transom): ?>
                                        <div class="transom-indicator" title="Transom 포함">
                                            <i class="bi bi-triangle"></i>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="material-info">
                                        <h5><?= htmlspecialchars($display_material) ?></h5>
                                        <span class="material-count">
                                            <?= $panel_count ?>개 패널
                                            <?php if ($has_transom): ?>
                                            <span class="transom-count">+ T 1개</span>
                                            <?php endif; ?>
                                        </span>
                                        <div class="material-percentage">
                                            <?php
                                            $percentage = round(($count / $production_results['total_panels']) * 100, 1);
                                            echo $percentage . '%';
                                            ?>
                                        </div>
                                    </div>
                                    <div class="material-progress">
                                        <div class="progress-bar" style="width: <?= $percentage ?>%"></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="bi bi-info-circle"></i>
                                <p>재질 정보가 없습니다</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Dimension and Quantity Summary -->
                <div class="result-card enhanced" id="dimensionQuantityCard">
                    <div class="card-header">
                        <h4><i class="bi bi-rulers"></i> <span id="dimensionQuantityTitle">치수 및 수량</span></h4>
                        <span class="card-badge quantity-badge" id="quantityBadge"><?= isset($selected_data['elevator_count']) ? $selected_data['elevator_count'] : 1 ?>대</span>
                    </div>
                    <div class="card-content">
                        <div class="total-quantity-display">
                            <div class="quantity-highlight">
                                <i class="bi bi-stack"></i>
                                <span class="quantity-value" id="totalQuantityValue"><?= isset($selected_data['elevator_count']) ? $selected_data['elevator_count'] : 1 ?></span>
                                <span class="quantity-unit">대</span>
                                <p class="quantity-label">총 제작 대수</p>
                            </div>
                        </div>

                        <?php if (!empty($production_results['dimension_summary']['details'])): ?>
                        <div class="dimension-details">
                            <h5 class="details-title">
                                <i class="bi bi-list-ul"></i> 패널별 상세 치수
                            </h5>
                            <div class="dimension-list" id="panelDimensionList">
                                <?php
                                $elevator_count = isset($selected_data['elevator_count']) ? intval($selected_data['elevator_count']) : 1;
                                foreach ($production_results['dimension_summary']['details'] as $detail):
                                ?>
                                <div class="dimension-item">
                                    <div class="panel-number">Panel <?= $detail['panel'] ?></div>
                                    <div class="dimension-specs">
                                        <span class="dimension-size">
                                            <?php
                                            // 1,11번 패널의 경우 production_height1_11 값 사용
                                            $display_height = $detail['height'];
                                            if ($detail['panel'] === '1' || $detail['panel'] === '11') {
                                                if (!empty($selected_data['production_height1_11'])) {
                                                    $display_height = $selected_data['production_height1_11'];
                                                }
                                            }
                                            ?>
                                            <?= number_format($detail['width']) ?> × <?= number_format($display_height) ?>mm
                                        </span>
                                    </div>
                                    <div class="quantity-info">
                                        <span class="quantity-label-item">수량/대수</span>
                                        <span class="quantity-value-item"><?= $elevator_count ?></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                
                                <!-- Transom 정보 추가 -->
                                <?php if (!empty($production_results['transom_details']) || !empty($transom_data)): ?>
                                <?php
                                // transom_details가 없으면 transom_data에서 직접 가져오기
                                if (!empty($production_results['transom_details'])) {
                                    $transom_details = $production_results['transom_details'];
                                } else {
                                    // transom_data에서 직접 정보 구성 (transom 전용 필드 매핑)
                                    $transom_details = [];
                                    if (!empty($transom_data)) {
                                        if (isset($transom_data['12'])) {
                                            $raw_data = $transom_data['12'];
                                        } elseif (isset($transom_data['transom'])) {
                                            $raw_data = $transom_data['transom'];
                                        }
                                        
                                        if (isset($raw_data)) {
                                            // transom 전용 필드 매핑
                                            $transom_details = [
                                                'width' => $raw_data['width'] ?? null,
                                                'plate_height' => $raw_data['transomPlateHeight'] ?? null,
                                                'bottom_depth' => $raw_data['bottomDepthJD'] ?? null,
                                                'wing_value' => $raw_data['wingValue'] ?? null,
                                                'material_type' => $raw_data['materialType'] ?? null,
                                                'thickness' => $raw_data['thickness'] ?? null,
                                                'cpi_drilling_width' => $raw_data['cpiDrillingWidth'] ?? null,
                                                'cpi_drilling_height' => $raw_data['cpiDrillingHeight'] ?? null,
                                                'cpi_drilling_height_from_bottom' => $raw_data['cpiDrillingHeightFromBottom'] ?? null,
                                                'drilling_width' => $raw_data['drillingWidth'] ?? null,
                                                'drilling_height' => $raw_data['drillingHeight'] ?? null,
                                                'drilling_from_floor' => $raw_data['drillingFromFloor'] ?? null,
                                                'drilling_from_entrance' => $raw_data['drillingFromEntrance'] ?? null,
                                                'notes' => $raw_data['notes'] ?? null
                                            ];
                                        }
                                    } elseif (isset($panel_data['12'])) {
                                        $raw_data = $panel_data['12'];
                                        // transom 전용 필드 매핑
                                        $transom_details = [
                                            'width' => $raw_data['width'] ?? null,
                                            'plate_height' => $raw_data['transomPlateHeight'] ?? null,
                                            'bottom_depth' => $raw_data['bottomDepthJD'] ?? null,
                                            'wing_value' => $raw_data['wingValue'] ?? null,
                                            'material_type' => $raw_data['materialType'] ?? null,
                                            'thickness' => $raw_data['thickness'] ?? null,
                                            'cpi_drilling_width' => $raw_data['cpiDrillingWidth'] ?? null,
                                            'cpi_drilling_height' => $raw_data['cpiDrillingHeight'] ?? null,
                                            'cpi_drilling_height_from_bottom' => $raw_data['cpiDrillingHeightFromBottom'] ?? null,
                                            'drilling_width' => $raw_data['drillingWidth'] ?? null,
                                            'drilling_height' => $raw_data['drillingHeight'] ?? null,
                                            'drilling_from_floor' => $raw_data['drillingFromFloor'] ?? null,
                                            'drilling_from_entrance' => $raw_data['drillingFromEntrance'] ?? null,
                                            'notes' => $raw_data['notes'] ?? null
                                        ];
                                    }
                                }
                                $elevator_count = isset($selected_data['elevator_count']) ? intval($selected_data['elevator_count']) : 1;
                                ?>
                                <div class="dimension-item transom-item">
                                    <div class="panel-number">Transom</div>
                                    <div class="dimension-specs">
                                        <span class="dimension-size">
                                            <?php if ($transom_details['width']): ?>
                                                <?= number_format($transom_details['width']) ?>mm (가로)
                                                <?php if ($transom_details['plate_height']): ?>
                                                    × <?= number_format($transom_details['plate_height']) ?>mm (막판높이)
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span style="color: var(--linear-text-tertiary);">치수 미지정</span>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="quantity-info">
                                        <span class="quantity-label-item">수량/대수</span>
                                        <span class="quantity-value-item"><?= $elevator_count ?></span>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Corner Panels -->
                <?php if (!empty($production_results['corner_panels'])): ?>
                <div class="result-card enhanced corner-panels">
                    <div class="card-header">
                        <h4><i class="bi bi-corners"></i> 코너 패널 상세</h4>
                        <span class="card-badge corner-badge">Critical Panels</span>
                    </div>
                    <div class="card-content">
                        <div class="corner-panels-grid">
                            <?php foreach ($production_results['corner_panels'] as $panel_num => $details): ?>
                            <div class="corner-panel-item">
                                <div class="corner-panel-header">
                                    <div class="panel-indicator">
                                        <span class="panel-number"><?= $panel_num ?></span>
                                        <span class="panel-type"><?= htmlspecialchars($details['type']) ?></span>
                                    </div>
                                    <div class="corner-icon">
                                        <i class="bi bi-box-seam"></i>
                                    </div>
                                </div>

                                <div class="corner-specifications">
                                    <?php if ($details['front_thickness'] || $details['front_wing']): ?>
                                    <div class="spec-group front">
                                        <h6><i class="bi bi-arrow-right"></i> 전면부</h6>
                                        <?php if ($details['front_thickness']): ?>
                                        <div class="spec-item">
                                            <span class="spec-label">두께</span>
                                            <span class="spec-value"><?= $details['front_thickness'] ?>mm</span>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($details['front_wing']): ?>
                                        <div class="spec-item">
                                            <span class="spec-label">날개</span>
                                            <span class="spec-value"><?= $details['front_wing'] ?>mm</span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($details['back_thickness'] || $details['back_wing']): ?>
                                    <div class="spec-group back">
                                        <h6><i class="bi bi-arrow-left"></i> 후면부</h6>
                                        <?php if ($details['back_thickness']): ?>
                                        <div class="spec-item">
                                            <span class="spec-label">두께</span>
                                            <span class="spec-value"><?= $details['back_thickness'] ?>mm</span>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($details['back_wing']): ?>
                                        <div class="spec-item">
                                            <span class="spec-label">날개</span>
                                            <span class="spec-value"><?= $details['back_wing'] ?>mm</span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <!-- Transom 정보 추가 -->
                            <?php if (!empty($production_results['transom_details']) || !empty($transom_data)): ?>
                            <?php
                            // transom_details가 없으면 transom_data에서 직접 가져오기
                            if (!empty($production_results['transom_details'])) {
                                $transom_details = $production_results['transom_details'];
                            } else {
                                // transom_data에서 직접 정보 구성 (transom 전용 필드 매핑)
                                $transom_details = [];
                                if (!empty($transom_data)) {
                                    if (isset($transom_data['12'])) {
                                        $raw_data = $transom_data['12'];
                                    } elseif (isset($transom_data['transom'])) {
                                        $raw_data = $transom_data['transom'];
                                    }
                                    
                                    if (isset($raw_data)) {
                                        // transom 전용 필드 매핑
                                        $transom_details = [
                                            'width' => $raw_data['width'] ?? null,
                                            'plate_height' => $raw_data['transomPlateHeight'] ?? null,
                                            'bottom_depth' => $raw_data['bottomDepthJD'] ?? null,
                                            'wing_value' => $raw_data['wingValue'] ?? null,
                                            'material_type' => $raw_data['materialType'] ?? null,
                                            'thickness' => $raw_data['thickness'] ?? null,
                                            'cpi_drilling_width' => $raw_data['cpiDrillingWidth'] ?? null,
                                            'cpi_drilling_height' => $raw_data['cpiDrillingHeight'] ?? null,
                                            'cpi_drilling_height_from_bottom' => $raw_data['cpiDrillingHeightFromBottom'] ?? null,
                                            'drilling_width' => $raw_data['drillingWidth'] ?? null,
                                            'drilling_height' => $raw_data['drillingHeight'] ?? null,
                                            'drilling_from_floor' => $raw_data['drillingFromFloor'] ?? null,
                                            'drilling_from_entrance' => $raw_data['drillingFromEntrance'] ?? null,
                                            'notes' => $raw_data['notes'] ?? null
                                        ];
                                    }
                                } elseif (isset($panel_data['12'])) {
                                    $raw_data = $panel_data['12'];
                                    // transom 전용 필드 매핑
                                    $transom_details = [
                                        'width' => $raw_data['width'] ?? null,
                                        'plate_height' => $raw_data['transomPlateHeight'] ?? null,
                                        'bottom_depth' => $raw_data['bottomDepthJD'] ?? null,
                                        'wing_value' => $raw_data['wingValue'] ?? null,
                                        'material_type' => $raw_data['materialType'] ?? null,
                                        'thickness' => $raw_data['thickness'] ?? null,
                                        'cpi_drilling_width' => $raw_data['cpiDrillingWidth'] ?? null,
                                        'cpi_drilling_height' => $raw_data['cpiDrillingHeight'] ?? null,
                                        'cpi_drilling_height_from_bottom' => $raw_data['cpiDrillingHeightFromBottom'] ?? null,
                                        'drilling_width' => $raw_data['drillingWidth'] ?? null,
                                        'drilling_height' => $raw_data['drillingHeight'] ?? null,
                                        'drilling_from_floor' => $raw_data['drillingFromFloor'] ?? null,
                                        'drilling_from_entrance' => $raw_data['drillingFromEntrance'] ?? null,
                                        'notes' => $raw_data['notes'] ?? null
                                    ];
                                }
                            }
                            ?>
                            <div class="corner-panel-item transom-panel-item">
                                <div class="corner-panel-header">
                                    <div class="panel-indicator">
                                        <span class="panel-number">T</span>
                                        <span class="panel-type">Transom</span>
                                    </div>
                                    <div class="corner-icon">
                                        <i class="bi bi-triangle"></i>
                                    </div>
                                </div>

                                <div class="corner-specifications">
                                    <?php if ($transom_details['width'] || $transom_details['plate_height']): ?>
                                    <div class="spec-group dimensions">
                                        <h6><i class="bi bi-aspect-ratio"></i> 치수</h6>
                                        <?php if ($transom_details['width']): ?>
                                        <div class="spec-item">
                                            <span class="spec-label">가로</span>
                                            <span class="spec-value"><?= number_format($transom_details['width']) ?>mm</span>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($transom_details['plate_height']): ?>
                                        <div class="spec-item">
                                            <span class="spec-label">막판높이</span>
                                            <span class="spec-value"><?= number_format($transom_details['plate_height']) ?>mm</span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($transom_details['material_type'] || $transom_details['thickness']): ?>
                                    <div class="spec-group material">
                                        <h6><i class="bi bi-layers"></i> 재질</h6>
                                        <?php if ($transom_details['material_type']): ?>
                                        <div class="spec-item">
                                            <span class="spec-label">재질</span>
                                            <span class="spec-value"><?= htmlspecialchars($transom_details['material_type']) ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($transom_details['thickness']): ?>
                                        <div class="spec-item">
                                            <span class="spec-label">두께</span>
                                            <span class="spec-value"><?= htmlspecialchars($transom_details['thickness']) ?>t</span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($transom_details['plate_height'] || $transom_details['bottom_depth'] || $transom_details['wing_value']): ?>
                                    <div class="spec-group special">
                                        <h6><i class="bi bi-gear"></i> 특수 사양</h6>
                                        <?php if ($transom_details['plate_height']): ?>
                                        <div class="spec-item">
                                            <span class="spec-label">막판높이</span>
                                            <span class="spec-value"><?= $transom_details['plate_height'] ?>mm</span>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($transom_details['bottom_depth']): ?>
                                        <div class="spec-item">
                                            <span class="spec-label">밑면깊이(JD)</span>
                                            <span class="spec-value"><?= $transom_details['bottom_depth'] ?>mm</span>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($transom_details['wing_value']): ?>
                                        <div class="spec-item">
                                            <span class="spec-label">날개값</span>
                                            <span class="spec-value"><?= $transom_details['wing_value'] ?>mm</span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Transom Details -->
                <?php if (!empty($production_results['transom_details']) || !empty($transom_data)): ?>
                <div class="result-card enhanced transom-details">
                    <div class="card-header">
                        <h4><i class="bi bi-triangle"></i> Transom 상세 분석</h4>
                        <span class="card-badge transom-badge">Specialized Component</span>
                    </div>
                    <div class="card-content">
                        <?php
                        // transom_details가 없으면 transom_data에서 직접 가져오기
                        if (!empty($production_results['transom_details'])) {
                            $transom_details = $production_results['transom_details'];
                        } else {
                            // transom_data에서 직접 정보 구성 (transom 전용 필드 매핑)
                            $transom_details = [];
                            if (!empty($transom_data)) {
                                if (isset($transom_data['12'])) {
                                    $raw_data = $transom_data['12'];
                                } elseif (isset($transom_data['transom'])) {
                                    $raw_data = $transom_data['transom'];
                                }
                                
                                if (isset($raw_data)) {
                                    // transom 전용 필드 매핑
                                    $transom_details = [
                                        'width' => $raw_data['width'] ?? null,
                                        'plate_height' => $raw_data['transomPlateHeight'] ?? null,
                                        'bottom_depth' => $raw_data['bottomDepthJD'] ?? null,
                                        'wing_value' => $raw_data['wingValue'] ?? null,
                                        'material_type' => $raw_data['materialType'] ?? null,
                                        'thickness' => $raw_data['thickness'] ?? null,
                                        'cpi_drilling_width' => $raw_data['cpiDrillingWidth'] ?? null,
                                        'cpi_drilling_height' => $raw_data['cpiDrillingHeight'] ?? null,
                                        'cpi_drilling_height_from_bottom' => $raw_data['cpiDrillingHeightFromBottom'] ?? null,
                                        'drilling_width' => $raw_data['drillingWidth'] ?? null,
                                        'drilling_height' => $raw_data['drillingHeight'] ?? null,
                                        'drilling_from_floor' => $raw_data['drillingFromFloor'] ?? null,
                                        'drilling_from_entrance' => $raw_data['drillingFromEntrance'] ?? null,
                                        'notes' => $raw_data['notes'] ?? null
                                    ];
                                }
                            } elseif (isset($panel_data['12'])) {
                                $raw_data = $panel_data['12'];
                                // transom 전용 필드 매핑
                                $transom_details = [
                                    'width' => $raw_data['width'] ?? null,
                                    'plate_height' => $raw_data['transomPlateHeight'] ?? null,
                                    'bottom_depth' => $raw_data['bottomDepthJD'] ?? null,
                                    'wing_value' => $raw_data['wingValue'] ?? null,
                                    'material_type' => $raw_data['materialType'] ?? null,
                                    'thickness' => $raw_data['thickness'] ?? null,
                                    'cpi_drilling_width' => $raw_data['cpiDrillingWidth'] ?? null,
                                    'cpi_drilling_height' => $raw_data['cpiDrillingHeight'] ?? null,
                                    'cpi_drilling_height_from_bottom' => $raw_data['cpiDrillingHeightFromBottom'] ?? null,
                                    'drilling_width' => $raw_data['drillingWidth'] ?? null,
                                    'drilling_height' => $raw_data['drillingHeight'] ?? null,
                                    'drilling_from_floor' => $raw_data['drillingFromFloor'] ?? null,
                                    'drilling_from_entrance' => $raw_data['drillingFromEntrance'] ?? null,
                                    'notes' => $raw_data['notes'] ?? null
                                ];
                            }
                        }
                        ?>

                        <!-- 기본 정보 섹션 -->
                        <div class="transom-basic-info">
                            <div class="transom-overview">
                                <div class="transom-size">
                                    <?php if ($transom_details['width'] || $transom_details['plate_height']): ?>
                                    <div class="size-display">
                                        <i class="bi bi-aspect-ratio"></i>
                                        <span class="size-value">
                                            <?= !empty($transom_details['width']) ? number_format($transom_details['width']) : '0' ?>mm
                                            <?php if ($transom_details['plate_height']): ?>
                                                × <?= number_format($transom_details['plate_height']) ?>mm
                                            <?php endif; ?>
                                        </span>
                                        <span class="size-label">Transom 크기</span>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <div class="transom-specs">
                                    <?php if ($transom_details['material_type']): ?>
                                    <div class="spec-badge material">
                                        <i class="bi bi-layers"></i>
                                        <span><?= htmlspecialchars($transom_details['material_type']) ?></span>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($transom_details['thickness']): ?>
                                    <div class="spec-badge thickness">
                                        <i class="bi bi-rulers"></i>
                                        <span><?= htmlspecialchars($transom_details['thickness']) ?>t</span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Transom 상세 정보 -->
                        <?php
                        $transom_specific = [];
                        if ($transom_details['plate_height']) $transom_specific['트랜섬 막판높이'] = $transom_details['plate_height'] . 'mm';
                        if ($transom_details['bottom_depth']) $transom_specific['밑면깊이(JD)'] = $transom_details['bottom_depth'] . 'mm';
                        if ($transom_details['wing_value']) $transom_specific['날개값'] = $transom_details['wing_value'] . 'mm';

                        if (!empty($transom_specific)):
                        ?>
                        <div class="transom-section specifications">
                            <h5 class="section-title">
                                <i class="bi bi-gear"></i> Transom 치수 상세
                            </h5>
                            <div class="spec-grid">
                                <?php foreach ($transom_specific as $label => $value): ?>
                                <div class="spec-detail">
                                    <span class="spec-name"><?= $label ?></span>
                                    <span class="spec-measure"><?= $value ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- CPI 타공 정보 -->
                        <?php
                        $cpi_drilling = [];
                        if ($transom_details['cpi_drilling_width']) $cpi_drilling['CPI타공 가로'] = $transom_details['cpi_drilling_width'] . 'mm';
                        if ($transom_details['cpi_drilling_height']) $cpi_drilling['CPI타공 세로'] = $transom_details['cpi_drilling_height'] . 'mm';
                        if ($transom_details['cpi_drilling_height_from_bottom']) $cpi_drilling['CPI타공높이(밑면기준)'] = $transom_details['cpi_drilling_height_from_bottom'] . 'mm';

                        if (!empty($cpi_drilling)):
                        ?>
                        <div class="transom-section drilling cpi">
                            <h5 class="section-title cpi-title">
                                <i class="bi bi-tools"></i> CPI 타공 정보
                            </h5>
                            <div class="drilling-grid">
                                <?php foreach ($cpi_drilling as $label => $value): ?>
                                <div class="drilling-item cpi-item">
                                    <div class="drilling-icon">
                                        <i class="bi bi-circle"></i>
                                    </div>
                                    <div class="drilling-details">
                                        <span class="drilling-label"><?= $label ?></span>
                                        <span class="drilling-value"><?= $value ?></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- 일반 타공 정보 -->
                        <?php if ($transom_details['drilling_width'] || $transom_details['drilling_height']): ?>
                        <div class="transom-section drilling standard">
                            <h5 class="section-title standard-title">
                                <i class="bi bi-bullseye"></i> 일반 타공 정보
                            </h5>
                            <div class="standard-drilling">
                                <div class="drilling-size-display">
                                    <div class="size-badge">
                                        <?= number_format($transom_details['drilling_width']) ?>×<?= number_format($transom_details['drilling_height']) ?>mm
                                    </div>
                                </div>

                                <div class="drilling-positions">
                                    <?php if ($transom_details['drilling_from_floor']): ?>
                                    <div class="position-item">
                                        <i class="bi bi-arrow-up"></i>
                                        <span class="position-label">바닥부터 높이</span>
                                        <span class="position-value"><?= number_format($transom_details['drilling_from_floor']) ?>mm</span>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($transom_details['drilling_from_entrance']): ?>
                                    <div class="position-item">
                                        <i class="bi bi-arrow-right"></i>
                                        <span class="position-label">입구부터 거리</span>
                                        <span class="position-value"><?= number_format($transom_details['drilling_from_entrance']) ?>mm</span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- 특이사항 -->
                        <?php if ($transom_details['notes']): ?>
                        <div class="transom-section notes">
                            <h5 class="section-title notes-title">
                                <i class="bi bi-exclamation-circle"></i> 특이사항
                            </h5>
                            <div class="notes-content">
                                <p><?= htmlspecialchars($transom_details['notes']) ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Special Requirements -->
                <?php if (!empty($production_results['special_requirements'])): ?>
                <div class="result-card enhanced special-requirements">
                    <div class="card-header">
                        <h4><i class="bi bi-exclamation-triangle"></i> 특수 요구사항</h4>
                        <span class="card-badge warning-badge">Attention Required</span>
                    </div>
                    <div class="card-content">
                        <div class="requirements-list">
                            <?php foreach ($production_results['special_requirements'] as $requirement): ?>
                            <div class="requirement-item">
                                <div class="requirement-indicator">
                                    <i class="bi bi-info-circle"></i>
                                </div>
                                <div class="requirement-content">
                                    <div class="requirement-panel">
                                        Panel <?= $requirement['panel'] ?> - <?= $requirement['type'] ?>
                                    </div>
                                    <div class="requirement-description">
                                        <?= htmlspecialchars($requirement['details']) ?>
                                    </div>
                                </div>
                                <div class="requirement-priority">
                                    <span class="priority-badge">중요</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

                <!-- Enhanced Export & Action Options -->
                <div class="export-section">
                    <div class="export-header">
                        <h4><i class="bi bi-download"></i> 결과 내보내기 및 인쇄</h4>
                        <p>제작산출 결과를 다양한 형태로 저장하거나 출력할 수 있습니다.</p>
                    </div>

                    <div class="export-options">
                        <div class="export-card">
                            <div class="export-icon excel">
                                <i class="bi bi-file-earmark-excel"></i>
                            </div>
                            <div class="export-details">
                                <h5>Excel 파일</h5>
                                <p>스프레드시트 형태로 데이터 저장</p>
                            </div>
                            <?php
                            echo LinearButton::secondary('내보내기')
                                ->addAttribute('onclick', 'exportToExcel()');
                            ?>
                        </div>

                        <div class="export-card">
                            <div class="export-icon print">
                                <i class="bi bi-printer"></i>
                            </div>
                            <div class="export-details">
                                <h5>인쇄</h5>
                                <p>현재 결과를 직접 인쇄</p>
                            </div>
                            <?php
                            echo LinearButton::outline('인쇄하기')
                                ->addAttribute('onclick', 'window.print()');
                            ?>
                        </div>

                        <div class="export-card">
                            <div class="export-icon share">
                                <i class="bi bi-share"></i>
                            </div>
                            <div class="export-details">
                                <h5>공유</h5>
                                <p>결과 링크를 복사하여 공유</p>
                            </div>
                            <?php
                            echo LinearButton::outline('링크 복사')
                                ->addAttribute('onclick', 'copyResultLink()');
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php elseif (!empty($measurements)): ?>
        <div class="results-container">
            <div class="no-data">
                <i class="bi bi-arrow-up" style="font-size: 2rem; color: var(--linear-brand-primary); margin-bottom: var(--linear-spacing-md);"></i>
                <p>위에서 측정 데이터를 선택하여 제작산출 결과를 확인하세요.</p>
            </div>
        </div>
        <?php else: ?>
        <div class="results-container">
            <div class="no-data">
                <i class="bi bi-search" style="font-size: 2rem; color: var(--linear-text-tertiary); margin-bottom: var(--linear-spacing-md);"></i>
                <p>검색 조건을 입력하여 측정 데이터를 찾아보세요.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 전역 변수로 현재 선택된 데이터 초기화
            window.currentSelectedData = <?= json_encode($selected_data) ?>;
            
            // 날짜 선택기 초기화 (HTML5 date input 사용으로 불필요)
            
            const themeToggleBtn = document.getElementById('themeToggleBtn');
            const themeIcon = document.getElementById('themeIcon');

            if (!themeToggleBtn || !themeIcon) return;

            // 테마 상태 관리
            let currentTheme = localStorage.getItem('linear-theme') || 'auto';

            // 아이콘 업데이트 함수
            function updateThemeIcon() {
                const icons = {
                    'light': '☀️',
                    'dark': '🌙',
                    'auto': '⚙️'
                };
                themeIcon.textContent = icons[currentTheme] || '⚙️';

                // title 업데이트
                const titles = {
                    'light': '라이트 모드 (다크 모드로 변경)',
                    'dark': '다크 모드 (자동 모드로 변경)',
                    'auto': '자동 모드 (라이트 모드로 변경)'
                };
                themeToggleBtn.title = titles[currentTheme] || '테마 변경';
            }
            

            // 테마 적용 함수
            function applyTheme(theme) {
                if (theme === 'auto') {
                    document.documentElement.removeAttribute('data-theme');
                } else {
                    document.documentElement.setAttribute('data-theme', theme);
                }
                localStorage.setItem('linear-theme', theme);
                currentTheme = theme;
                updateThemeIcon();
            }

            // 초기 테마 적용
            applyTheme(currentTheme);

            // 버튼 클릭 이벤트
            themeToggleBtn.addEventListener('click', function() {
                const themeOrder = ['light', 'dark', 'auto'];
                const currentIndex = themeOrder.indexOf(currentTheme);
                const nextIndex = (currentIndex + 1) % themeOrder.length;
                applyTheme(themeOrder[nextIndex]);
            });

            // 시스템 테마 변경 감지 (auto 모드일 때)
            if (window.matchMedia) {
                const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
                darkModeQuery.addEventListener('change', function() {
                    if (currentTheme === 'auto') {
                        // auto 모드에서는 data-theme 속성을 제거하여 CSS의 @media 규칙이 적용되도록 함
                        document.documentElement.removeAttribute('data-theme');
                    }
                });
            }
        });

        // 검색 토글 기능 개선
        document.addEventListener('DOMContentLoaded', function() {
            const searchToggle = document.getElementById('searchToggle');
            const searchForm = document.getElementById('searchForm');
            const toggleIcon = document.getElementById('toggleIcon');

            // 요소 존재 확인
            if (!searchToggle || !searchForm || !toggleIcon) {
                console.error('검색 폼 요소를 찾을 수 없습니다.');
                return;
            }

            // 초기 상태: 검색 조건이 있으면 열림, 없으면 닫힘
            const hasSearchParams = new URLSearchParams(window.location.search).get('search_site') ||
                                   new URLSearchParams(window.location.search).get('search_date_from') ||
                                   new URLSearchParams(window.location.search).get('search_date_to') ||
                                   new URLSearchParams(window.location.search).get('search_measurer');

            // localStorage에서 토글 상태 확인 (사용자가 마지막에 설정한 상태 유지)
            const savedToggleState = localStorage.getItem('search-form-expanded');
            let isExpanded = savedToggleState !== null ? savedToggleState === 'true' : hasSearchParams;

            // 초기 상태 설정
            if (!isExpanded) {
                searchForm.classList.add('collapsed');
                toggleIcon.classList.add('rotated');
            }

            // 클릭 이벤트 리스너 추가 (이벤트 위임 사용)
            searchToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleSearchForm();
            });

            // 터치 이벤트도 지원 (모바일)
            searchToggle.addEventListener('touchend', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleSearchForm();
            });

            function toggleSearchForm() {
                isExpanded = !isExpanded;

                if (isExpanded) {
                    searchForm.classList.remove('collapsed');
                    toggleIcon.classList.remove('rotated');
                    console.log('검색 폼이 열렸습니다.');
                } else {
                    searchForm.classList.add('collapsed');
                    toggleIcon.classList.add('rotated');
                    console.log('검색 폼이 닫혔습니다.');
                }

                // 상태를 localStorage에 저장
                localStorage.setItem('search-form-expanded', isExpanded.toString());
            }

            // ESC 키로 검색 폼 닫기
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && isExpanded) {
                    toggleSearchForm();
                }
            });

            // 검색 폼 제출 시 유효성 검사 및 로딩 상태 표시
            searchForm.addEventListener('submit', function(e) {
                // 유효성 검사
                const searchSite = document.getElementById('searchSite').value.trim();
                const searchDateFrom = document.getElementById('searchDateFrom').value;
                const searchDateTo = document.getElementById('searchDateTo').value;
                
                // 날짜 유효성 검사
                if (searchDateFrom && searchDateTo) {
                    const fromDate = new Date(searchDateFrom);
                    const toDate = new Date(searchDateTo);
                    
                    if (fromDate > toDate) {
                        e.preventDefault();
                        alert('시작일은 종료일보다 이전이어야 합니다.');
                        return false;
                    }
                    
                    // 날짜 범위가 너무 넓은지 확인 (1년 이상)
                    const diffTime = Math.abs(toDate - fromDate);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    
                    if (diffDays > 365) {
                        if (!confirm('검색 기간이 1년을 초과합니다. 계속하시겠습니까?')) {
                            e.preventDefault();
                            return false;
                        }
                    }
                }
                
                // 검색 조건이 하나도 없는 경우 확인
                if (!searchSite && !searchDateFrom && !searchDateTo && !document.getElementById('searchMeasurer').value) {
                    if (!confirm('검색 조건이 없습니다. 모든 데이터를 조회하시겠습니까?')) {
                        e.preventDefault();
                        return false;
                    }
                }
                
                const submitButton = searchForm.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<i class="bi bi-hourglass-split"></i> 검색 중...';
                    
                    // 3초 후 버튼 복원 (타임아웃 방지)
                    setTimeout(() => {
                        submitButton.disabled = false;
                        submitButton.innerHTML = '<i class="bi bi-search"></i> 검색';
                    }, 3000);
                }
            });
            
            // 실시간 검색 조건 유효성 검사
            const searchDateFrom = document.getElementById('searchDateFrom');
            const searchDateTo = document.getElementById('searchDateTo');
            
            function validateDateRange() {
                if (searchDateFrom.value && searchDateTo.value) {
                    const fromDate = new Date(searchDateFrom.value);
                    const toDate = new Date(searchDateTo.value);
                    
                    if (fromDate > toDate) {
                        searchDateTo.setCustomValidity('종료일은 시작일보다 이후여야 합니다.');
                        searchDateTo.style.borderColor = 'var(--linear-error-border)';
                    } else {
                        searchDateTo.setCustomValidity('');
                        searchDateTo.style.borderColor = '';
                    }
                } else {
                    searchDateTo.setCustomValidity('');
                    searchDateTo.style.borderColor = '';
                }
            }
            
            searchDateFrom.addEventListener('change', validateDateRange);
            searchDateTo.addEventListener('change', validateDateRange);
            
            // 키보드 단축키 기능
            document.addEventListener('keydown', function(e) {
                // Ctrl + F: 검색 폼 포커스
                if (e.ctrlKey && e.key === 'f') {
                    e.preventDefault();
                    if (isExpanded) {
                        document.getElementById('searchSite').focus();
                    } else {
                        toggleSearchForm();
                        setTimeout(() => {
                            document.getElementById('searchSite').focus();
                        }, 300);
                    }
                }
                
                // Enter: 검색 실행 (검색 폼이 열려있을 때)
                if (e.key === 'Enter' && isExpanded && e.target.tagName !== 'BUTTON') {
                    const activeElement = document.activeElement;
                    if (activeElement && (activeElement.tagName === 'INPUT' || activeElement.tagName === 'SELECT')) {
                        e.preventDefault();
                        searchForm.submit();
                    }
                }
            });
        });

        // 측정 데이터 선택 UI 관리 시스템
        document.addEventListener('DOMContentLoaded', function() {
            const measurementToggle = document.getElementById('measurementToggle');
            const measurementList = document.getElementById('measurementList');
            const measurementToggleIcon = document.getElementById('measurementToggleIcon');
            const selectedMeasurementDisplay = document.getElementById('selectedMeasurementDisplay');

            // 요소 존재 확인
            if (!measurementToggle || !measurementList || !measurementToggleIcon) {
                return;
            }

            // 초기 상태: 선택된 측정 데이터가 있으면 선택된 항목만 표시, 없으면 리스트 표시
            const selectedMeasurement = new URLSearchParams(window.location.search).get('measurement_id');
            let isListExpanded = !selectedMeasurement; // 선택된 데이터가 없으면 리스트 펼치기

            // 초기 UI 상태 설정
            function setInitialState() {
                if (selectedMeasurement && selectedMeasurementDisplay) {
                    // 선택된 데이터가 있으면: 선택된 항목 표시, 리스트 숨김
                    selectedMeasurementDisplay.style.display = 'block';
                    measurementList.style.display = 'none';
                    measurementToggleIcon.classList.add('rotated');
                    measurementToggleIcon.classList.remove('bi-chevron-up');
                    measurementToggleIcon.classList.add('bi-chevron-down');
                    isListExpanded = false;
                } else {
                    // 선택된 데이터가 없으면: 리스트 표시
                    if (selectedMeasurementDisplay) {
                        selectedMeasurementDisplay.style.display = 'none';
                    }
                    measurementList.style.display = 'block';
                    measurementToggleIcon.classList.remove('rotated');
                    measurementToggleIcon.classList.remove('bi-chevron-down');
                    measurementToggleIcon.classList.add('bi-chevron-up');
                    isListExpanded = true;
                }
            }

            // 초기 상태 적용
            setInitialState();

            // 토글 기능
            function toggleMeasurementView() {
                isListExpanded = !isListExpanded;

                if (isListExpanded) {
                    // 리스트 표시 모드
                    if (selectedMeasurementDisplay) {
                        selectedMeasurementDisplay.style.display = 'none';
                    }
                    measurementList.style.display = 'block';
                    measurementToggleIcon.classList.remove('rotated', 'bi-chevron-down');
                    measurementToggleIcon.classList.add('bi-chevron-up');
                } else {
                    // 선택된 항목 표시 모드 (선택된 데이터가 있을 때만)
                    if (selectedMeasurementDisplay && selectedMeasurement) {
                        selectedMeasurementDisplay.style.display = 'block';
                    }
                    measurementList.style.display = 'none';
                    measurementToggleIcon.classList.add('rotated', 'bi-chevron-down');
                    measurementToggleIcon.classList.remove('bi-chevron-up');
                }
            }

            // 헤더 클릭 이벤트
            measurementToggle.addEventListener('click', function(e) {
                e.preventDefault();
                toggleMeasurementView();
            });

            // 터치 이벤트도 지원 (모바일)
            measurementToggle.addEventListener('touchend', function(e) {
                e.preventDefault();
                toggleMeasurementView();
            });

            // 선택된 측정 데이터 영역 클릭 이벤트 (리스트 표시)
            if (selectedMeasurementDisplay) {
                selectedMeasurementDisplay.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (!isListExpanded) {
                        toggleMeasurementView();
                    }
                });

                // 호버 효과
                selectedMeasurementDisplay.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = 'var(--linear-bg-tertiary)';
                    this.style.borderColor = 'var(--linear-border-secondary)';
                });

                selectedMeasurementDisplay.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = 'var(--linear-bg-secondary)';
                    this.style.borderColor = 'var(--linear-border-primary)';
                });
            }

            // ESC 키로 측정 데이터 리스트 닫기 (선택된 데이터가 있을 때만)
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && isListExpanded && selectedMeasurement) {
                    toggleMeasurementView();
                }
            });
        });

        // 제작 조건 설정 토글 기능
        document.addEventListener('DOMContentLoaded', function() {
            const productionToggle = document.getElementById('productionToggle');
            const productionForm = document.getElementById('productionForm');
            const productionToggleIcon = document.getElementById('productionToggleIcon');

            // 요소 존재 확인
            if (!productionToggle || !productionForm || !productionToggleIcon) {
                return;
            }

            // localStorage에서 토글 상태 확인
            const savedProductionToggleState = localStorage.getItem('production-form-expanded');
            let isProductionExpanded = savedProductionToggleState !== null ?
                                      savedProductionToggleState === 'true' : true; // 기본값: 열림

            // 초기 상태 설정
            if (!isProductionExpanded) {
                productionForm.classList.add('collapsed');
                productionToggleIcon.classList.add('rotated');
            }

            // 클릭 이벤트 리스너 추가
            productionToggle.addEventListener('click', function(e) {
                e.preventDefault();
                toggleProductionForm();
            });

            // 터치 이벤트도 지원 (모바일)
            productionToggle.addEventListener('touchend', function(e) {
                e.preventDefault();
                toggleProductionForm();
            });

            function toggleProductionForm() {
                isProductionExpanded = !isProductionExpanded;

                if (isProductionExpanded) {
                    productionForm.classList.remove('collapsed');
                    productionToggleIcon.classList.remove('rotated');
                } else {
                    productionForm.classList.add('collapsed');
                    productionToggleIcon.classList.add('rotated');
                }

                // 상태를 localStorage에 저장
                localStorage.setItem('production-form-expanded', isProductionExpanded.toString());
            }

            // 체크박스 상태 변경 이벤트
            const checkboxes = document.querySelectorAll('#productionForm input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                });
            });

            // 라디오 버튼 상태 변경 이벤트
            const radioButtons = document.querySelectorAll('#productionForm input[type="radio"]');
            radioButtons.forEach(radio => {
                radio.addEventListener('change', function() {
                });
            });

            // 엘리베이터 대수 실시간 변경 이벤트
            const elevatorCountInput = document.getElementById('elevatorCount');
            if (elevatorCountInput) {
                elevatorCountInput.addEventListener('input', function() {
                    const newElevatorCount = parseInt(this.value) || 1;

                    // 몰딩이 포함된 경우에만 실시간 업데이트
                    const moldingCheckbox = document.getElementById('moldingIncluded');
                    if (moldingCheckbox && moldingCheckbox.checked) {
                        // 함수 존재 확인 후 호출
                        if (typeof window.updateMoldingTableRealtime === 'function') {
                            window.updateMoldingTableRealtime(newElevatorCount);
                        } else {
                            console.warn('updateMoldingTableRealtime 함수를 찾을 수 없음');
                        }
                    }
                });
            }
        });

        // 전역 몰딩 계산 함수
        window.calculateMoldingData = function() {
            <?php if ($selected_data): ?>
            const selectedData = <?= json_encode($selected_data) ?>;
            const elevatorCount = parseInt(selectedData.elevator_count) || 1;
            const productionHeight = parseInt(selectedData.production_height) || parseInt(selectedData.car_inside_height);
            const carWidth = parseInt(selectedData.car_inside_width);
            const carDepth = parseInt(selectedData.car_inside_depth);
            
            // 관통형 체크
            const isPassThrough = selectedData && selectedData.car_structure === '관통형';

            const result = [
                {
                    type: 'ㄷ자 몰딩',
                    size: productionHeight,
                    count: isPassThrough ? 4 : 2, // 관통형일 때 4개 (2, 4, 8, 10번), 일반형일 때 2개 (2, 10번)
                    elevatorCount: elevatorCount,
                    totalCount: (isPassThrough ? 4 : 2) * elevatorCount,
                    description: isPassThrough ? '2번, 4번, 8번, 10번 패널용 (관통형)' : '2번, 10번 패널용'
                },
                {
                    type: 'ㄴ자 몰딩',
                    size: productionHeight,
                    count: isPassThrough ? 4 : 6, // 관통형일 때 5-6, 6-7번 ㄴ자 몰딩 제외
                    elevatorCount: elevatorCount,
                    totalCount: (isPassThrough ? 4 : 6) * elevatorCount,
                    description: isPassThrough ? '2-3, 3-4, 8-9, 9-10번 연결용' : '패널 사이 연결용'
                },
                {
                    type: 'Sㄷ자 하부몰딩',
                    size: carDepth - 5,
                    count: 2,
                    elevatorCount: elevatorCount,
                    totalCount: 2 * elevatorCount,
                    description: '측면 하부 가로'
                }
            ];

            // 관통형일 때는 별도 ㄷ자 몰딩 항목 추가하지 않음 (기존 ㄷ자 몰딩에 통합)
            // 일반형일 때만 코너몰딩과 Rㄷ자 하부몰딩 추가
            if (!isPassThrough) {
                result.push({
                    type: '코너몰딩',
                    size: productionHeight,
                    count: 2,
                    elevatorCount: elevatorCount,
                    totalCount: 2 * elevatorCount,
                    description: '4-5, 7-8번 연결용'
                });
                result.push({
                    type: 'Rㄷ자 하부몰딩',
                    size: carWidth - 2,
                    count: 1,
                    elevatorCount: elevatorCount, 
                    totalCount: 1 * elevatorCount,
                    description: '후면 하부 가로'
                });
            }

            return result;
            <?php else: ?>
            return [];
            <?php endif; ?>
        };

        // 전역 몰딩 테이블 렌더링 함수
        window.renderMoldingTable = function(moldingData) {
            const tableBody = document.getElementById('moldingTableBody');
            if (!tableBody) return;

            tableBody.innerHTML = '';

            moldingData.forEach(item => {
                const row = document.createElement('tr');
                row.style.borderBottom = '1px solid var(--linear-border-secondary)';

                row.innerHTML = `
                    <td style="padding: var(--linear-spacing-sm); color: var(--linear-text-primary); font-weight: 500;">
                        ${item.type}
                        <div style="font-size: 0.8rem; color: var(--linear-text-secondary); margin-top: 2px;">
                            ${item.description}
                        </div>
                    </td>
                    <td style="padding: var(--linear-spacing-sm); text-align: center; color: var(--linear-brand-primary); font-weight: 600; font-family: monospace;">
                        ${item.size}mm
                    </td>
                    <td style="padding: var(--linear-spacing-sm); text-align: center; color: var(--linear-text-primary);">
                        ${item.count}EA
                    </td>
                    <td style="padding: var(--linear-spacing-sm); text-align: center; color: var(--linear-success-text); font-weight: 600;">
                        ${item.elevatorCount}대
                    </td>
                    <td style="padding: var(--linear-spacing-sm); text-align: center; color: var(--linear-accent-text); font-weight: 600;">
                        ${item.totalCount}EA
                    </td>
                `;

                tableBody.appendChild(row);
            });
        };

        // 전역 실시간 몰딩 테이블 업데이트 함수
        window.updateMoldingTableRealtime = function(newElevatorCount) {
            <?php if ($selected_data): ?>
            try {
                // 기본 데이터 가져오기
                const selectedData = <?= json_encode($selected_data) ?>;
                const productionHeight = parseInt(selectedData.production_height) || parseInt(selectedData.car_inside_height);
                const carWidth = parseInt(selectedData.car_inside_width);
                const carDepth = parseInt(selectedData.car_inside_depth);

                // 새로운 엘리베이터 대수로 몰딩 데이터 직접 계산
                const moldingData = [
                    {
                        type: 'ㄷ자 몰딩',
                        size: productionHeight,
                        count: 2,
                        elevatorCount: newElevatorCount,
                        totalCount: 2 * newElevatorCount,
                        description: '2번, 10번 패널용'
                    },
                    {
                        type: 'ㄴ자 몰딩',
                        size: productionHeight,
                        count: 6,
                        elevatorCount: newElevatorCount,
                        totalCount: 6 * newElevatorCount,
                        description: '3번, 4번, 7번, 8번, 9번 패널용'
                    },
                    {
                        type: '코너몰딩',
                        size: productionHeight,
                        count: 2,
                        elevatorCount: newElevatorCount,
                        totalCount: 2 * newElevatorCount,
                        description: '5번, 6번 패널용'
                    },
                    {
                        type: 'Sㄷ자 하부몰딩',
                        size: carWidth,
                        count: 2,
                        elevatorCount: newElevatorCount,
                        totalCount: 2 * newElevatorCount,
                        description: '측면 하부 가로'
                    },
                    {
                        type: 'Rㄷ자 하부몰딩',
                        size: carWidth - 2,
                        count: 1,
                        elevatorCount: newElevatorCount,
                        totalCount: 1 * newElevatorCount,
                        description: '후면 하부 가로'
                    }
                ];

                // 테이블 업데이트
                window.renderMoldingTable(moldingData);

                // 제목 업데이트
                const moldingInfoTitle = document.getElementById('moldingInfoTitle');
                if (moldingInfoTitle) {
                    moldingInfoTitle.textContent = `몰딩 절단치수 정보 (${newElevatorCount}대)`;
                }


            } catch (error) {
                console.error('실시간 몰딩 테이블 업데이트 오류:', error);
            }
            <?php endif; ?>
        };

        // 판넬 시각화 토글 기능
        function initializePanelVisualizationToggle() {
            const panelVisualizationToggle = document.getElementById('panelVisualizationToggle');
            const panelVisualizationContent = document.getElementById('panelVisualizationContent');
            const panelVisualizationToggleIcon = document.getElementById('panelVisualizationToggleIcon');

            if (!panelVisualizationToggle || !panelVisualizationContent || !panelVisualizationToggleIcon) {
                return;
            }

            // 초기 상태 (펼쳐진 상태)
            let isPanelVisualizationExpanded = true;

            function togglePanelVisualization() {
                isPanelVisualizationExpanded = !isPanelVisualizationExpanded;

                if (isPanelVisualizationExpanded) {
                    // 펼치기
                    panelVisualizationContent.style.display = 'block';
                    panelVisualizationToggleIcon.classList.remove('rotated');
                    panelVisualizationToggleIcon.classList.remove('bi-chevron-down');
                    panelVisualizationToggleIcon.classList.add('bi-chevron-up');
                } else {
                    // 접기
                    panelVisualizationContent.style.display = 'none';
                    panelVisualizationToggleIcon.classList.add('rotated');
                    panelVisualizationToggleIcon.classList.remove('bi-chevron-up');
                    panelVisualizationToggleIcon.classList.add('bi-chevron-down');
                }
            }

            // 클릭 이벤트 리스너 추가
            panelVisualizationToggle.addEventListener('click', function(e) {
                e.preventDefault();
                togglePanelVisualization();
            });

            // 터치 이벤트도 지원 (모바일)
            panelVisualizationToggle.addEventListener('touchend', function(e) {
                e.preventDefault();
                togglePanelVisualization();
            });

        }

        // 제작산출 결과 토글 기능
        function initializeProductionResultsToggle() {
            const productionResultsToggle = document.getElementById('productionResultsToggle');
            const productionResultsContent = document.getElementById('productionResultsContent');
            const productionResultsToggleIcon = document.getElementById('productionResultsToggleIcon');

            if (!productionResultsToggle || !productionResultsContent || !productionResultsToggleIcon) {
                return;
            }

            // 초기 상태 (펼쳐진 상태)
            let isProductionResultsExpanded = true;

            function toggleProductionResults() {
                isProductionResultsExpanded = !isProductionResultsExpanded;

                if (isProductionResultsExpanded) {
                    // 펼치기
                    productionResultsContent.style.display = 'block';
                    productionResultsToggleIcon.classList.remove('rotated');
                    productionResultsToggleIcon.classList.remove('bi-chevron-down');
                    productionResultsToggleIcon.classList.add('bi-chevron-up');
                } else {
                    // 접기
                    productionResultsContent.style.display = 'none';
                    productionResultsToggleIcon.classList.add('rotated');
                    productionResultsToggleIcon.classList.remove('bi-chevron-up');
                    productionResultsToggleIcon.classList.add('bi-chevron-down');
                }
            }

            // 클릭 이벤트 리스너 추가
            productionResultsToggle.addEventListener('click', function(e) {
                e.preventDefault();
                toggleProductionResults();
            });

            // 터치 이벤트도 지원 (모바일)
            productionResultsToggle.addEventListener('touchend', function(e) {
                e.preventDefault();
                toggleProductionResults();
            });

        }

        function initializeMoldingToggle() {
            const moldingCheckbox = document.getElementById('moldingIncluded');

            if (!moldingCheckbox) {
                return;
            }

            // 초기 상태 설정
            updateMoldingDisplay();

            // 체크박스 변경 이벤트 리스너
            moldingCheckbox.addEventListener('change', function() {
                updateMoldingDisplay();
            });

            function updateMoldingDisplay() {
                const moldingIncluded = moldingCheckbox.checked;
                
                // 관통형 체크
                const selectedData = window.currentSelectedData || <?= json_encode($selected_data) ?>;
                const isPassThrough = selectedData && selectedData.car_structure === '관통형';

                // ㄷ자 몰딩 요소들
                const endingMolding2 = document.getElementById('endingMolding2');
                const endingMolding10 = document.getElementById('endingMolding10');
                const endingMolding4 = document.getElementById('endingMolding4');
                const endingMolding8 = document.getElementById('endingMolding8');

                // ㄴ자 몰딩 요소들
                const centerMolding2_3 = document.getElementById('centerMolding2_3');
                const centerMolding3_4 = document.getElementById('centerMolding3_4');
                const centerMolding8_9 = document.getElementById('centerMolding8_9');
                const centerMolding9_10 = document.getElementById('centerMolding9_10');
                const centerMolding5_6 = document.getElementById('centerMolding5_6');
                const centerMolding6_7 = document.getElementById('centerMolding6_7');

                // 코너몰딩 요소들
                const cornerMolding4_5 = document.getElementById('cornerMolding4_5');
                const cornerMolding7_8 = document.getElementById('cornerMolding7_8');
                
                // 관통형용 코너몰딩 요소들
                const cornerMolding2Start = document.getElementById('cornerMolding2Start');
                const cornerMolding10End = document.getElementById('cornerMolding10End');

                // Sㄷ자 하부몰딩 및 Rㄷ자 하부몰딩 요소들
                const sEndingMoldingLeft = document.getElementById('sEndingMoldingLeft');
                const sEndingMoldingRight = document.getElementById('sEndingMoldingRight');
                const rEndingMolding = document.getElementById('rEndingMolding');

                if (moldingIncluded) {
                    // ㄷ자 몰딩 표시
                    if (isPassThrough) {
                        // 관통형: 2번, 10번 하단 ㄷ자 몰딩 + 4번, 8번 상단 ㄷ자 몰딩 모두 표시
                        if (endingMolding2) endingMolding2.style.display = 'block';
                        if (endingMolding10) endingMolding10.style.display = 'block';
                        if (endingMolding4) endingMolding4.style.display = 'block';
                        if (endingMolding8) endingMolding8.style.display = 'block';
                    } else {
                        // 일반형: 2번, 10번 하단 ㄷ자 몰딩만 표시
                        if (endingMolding2) endingMolding2.style.display = 'block';
                        if (endingMolding10) endingMolding10.style.display = 'block';
                        // 4번, 8번 상단 ㄷ자 몰딩 숨김
                        if (endingMolding4) endingMolding4.style.display = 'none';
                        if (endingMolding8) endingMolding8.style.display = 'none';
                    }

                    // ㄴ자 몰딩 표시
                    if (centerMolding2_3) centerMolding2_3.style.display = 'block';
                    if (centerMolding3_4) centerMolding3_4.style.display = 'block';
                    if (centerMolding8_9) centerMolding8_9.style.display = 'block';
                    if (centerMolding9_10) centerMolding9_10.style.display = 'block';
                    
                    // 관통형인 경우 5, 6, 7번 패널 관련 ㄴ자 몰딩 숨김
                    if (isPassThrough) {
                        if (centerMolding5_6) centerMolding5_6.style.display = 'none';
                        if (centerMolding6_7) centerMolding6_7.style.display = 'none';
                    } else {
                        if (centerMolding5_6) centerMolding5_6.style.display = 'block';
                        if (centerMolding6_7) centerMolding6_7.style.display = 'block';
                    }

                    // 코너몰딩 처리
                    console.log('🔍 DEBUG: isPassThrough =', isPassThrough);
                    console.log('🔍 DEBUG: cornerMolding2Start element =', cornerMolding2Start);
                    console.log('🔍 DEBUG: cornerMolding10End element =', cornerMolding10End);
                    
                    if (isPassThrough) {
                        // 관통형: 모든 코너몰딩 숨김
                        if (cornerMolding4_5) cornerMolding4_5.style.display = 'none';
                        if (cornerMolding7_8) cornerMolding7_8.style.display = 'none';
                        if (cornerMolding2Start) cornerMolding2Start.style.display = 'none';
                        if (cornerMolding10End) cornerMolding10End.style.display = 'none';
                        console.log('✅ 관통형: 모든 코너몰딩 숨김');
                    } else {
                        // 일반형: 기존 코너몰딩 표시
                        if (cornerMolding4_5) cornerMolding4_5.style.display = 'block';
                        if (cornerMolding7_8) cornerMolding7_8.style.display = 'block';
                        
                        // 일반형: 관통형용 코너몰딩 숨김
                        if (cornerMolding2Start) cornerMolding2Start.style.display = 'none';
                        if (cornerMolding10End) cornerMolding10End.style.display = 'none';
                        console.log('✅ 일반형: 코너몰딩 설정 완료');
                    }

                    // Sㄷ자 하부몰딩 및 Rㄷ자 하부몰딩 표시
                    if (sEndingMoldingLeft) sEndingMoldingLeft.style.display = 'block';
                    if (sEndingMoldingRight) sEndingMoldingRight.style.display = 'block';
                    
                    // Rㄷ자 하부몰딩 처리 (관통형일 때는 숨김)
                    if (isPassThrough) {
                        if (rEndingMolding) rEndingMolding.style.display = 'none';
                    } else {
                        if (rEndingMolding) rEndingMolding.style.display = 'block';
                    }

                } else {
                    // ㄷ자 몰딩 숨김
                    if (endingMolding2) endingMolding2.style.display = 'none';
                    if (endingMolding10) endingMolding10.style.display = 'none';
                    if (endingMolding4) endingMolding4.style.display = 'none';
                    if (endingMolding8) endingMolding8.style.display = 'none';

                    // ㄴ자 몰딩 숨김
                    if (centerMolding2_3) centerMolding2_3.style.display = 'none';
                    if (centerMolding3_4) centerMolding3_4.style.display = 'none';
                    if (centerMolding8_9) centerMolding8_9.style.display = 'none';
                    if (centerMolding9_10) centerMolding9_10.style.display = 'none';
                    if (centerMolding5_6) centerMolding5_6.style.display = 'none';
                    if (centerMolding6_7) centerMolding6_7.style.display = 'none';

                    // 코너몰딩 숨김
                    if (cornerMolding4_5) cornerMolding4_5.style.display = 'none';
                    if (cornerMolding7_8) cornerMolding7_8.style.display = 'none';
                    if (cornerMolding2Start) cornerMolding2Start.style.display = 'none';
                    if (cornerMolding10End) cornerMolding10End.style.display = 'none';

                    // Sㄷ자 하부몰딩 및 Rㄷ자 하부몰딩 숨김
                    if (sEndingMoldingLeft) sEndingMoldingLeft.style.display = 'none';
                    if (sEndingMoldingRight) sEndingMoldingRight.style.display = 'none';
                    if (rEndingMolding) rEndingMolding.style.display = 'none';

                }

                // 몰딩 변경 후 패널 치수 재렌더링
                refreshPanelDimensions();

                // 몰딩 정보 테이블 업데이트
                updateMoldingInfoTable();
            }

            function refreshPanelDimensions() {
                // 패널 시각화 재렌더링 (몰딩 차감 적용) - 제작패널데이터 우선 사용
                <?php if ($selected_data): ?>
                const selectedData = <?= json_encode($selected_data) ?>;

                // 제작패널데이터를 우선 사용, 없으면 원본 패널 데이터 사용
                let panelData = null;
                if (selectedData.make_panel_data) {
                    panelData = JSON.parse(selectedData.make_panel_data);
                } else if (selectedData.panel_data) {
                    panelData = JSON.parse(selectedData.panel_data);
                }

                if (panelData) {
                    const transomData = selectedData.transom_data ? JSON.parse(selectedData.transom_data) : null;

                    // 패널 시각화 재렌더링
                    renderPanelVisualization(panelData, transomData);

                    // 툴팁도 업데이트된 몰딩 차감 적용을 위해 호버 효과 재적용
                    addPanelHoverEffects(panelData, transomData);

                }
                <?php endif; ?>
            }

            function updateMoldingInfoTable() {
                const moldingCheckbox = document.getElementById('moldingIncluded');
                const moldingInfoContainer = document.getElementById('moldingInfoContainer');

                if (!moldingCheckbox || !moldingInfoContainer) return;

                if (moldingCheckbox.checked) {
                    // 몰딩 정보 계산 및 테이블 생성
                    const moldingData = calculateMoldingData();
                    renderMoldingTable(moldingData);
                    moldingInfoContainer.style.display = 'block';
                } else {
                    moldingInfoContainer.style.display = 'none';
                }
            }

            function calculateMoldingData() {
                <?php if ($selected_data): ?>
                const selectedData = <?= json_encode($selected_data) ?>;
                const elevatorCount = parseInt(selectedData.elevator_count) || 1;
                const productionHeight = parseInt(selectedData.production_height) || parseInt(selectedData.car_inside_height);
                const carWidth = parseInt(selectedData.car_inside_width);
                const carDepth = parseInt(selectedData.car_inside_depth);
                
                // 관통형 체크
                const isPassThrough = selectedData && selectedData.car_structure === '관통형';

                const result = [
                    {
                        type: 'ㄷ자 몰딩',
                        size: productionHeight,
                        count: isPassThrough ? 4 : 2, // 관통형일 때 4개 (2, 4, 8, 10번), 일반형일 때 2개 (2, 10번)
                        elevatorCount: elevatorCount,
                        totalCount: (isPassThrough ? 4 : 2) * elevatorCount,
                        description: isPassThrough ? '2번, 4번, 8번, 10번 패널용 (관통형)' : '2번, 10번 패널용'
                    },
                    {
                        type: 'ㄴ자 몰딩',
                        size: productionHeight,
                        count: isPassThrough ? 4 : 6, // 관통형일 때 5-6, 6-7번 ㄴ자 몰딩 제외
                        elevatorCount: elevatorCount,
                        totalCount: (isPassThrough ? 4 : 6) * elevatorCount,
                        description: isPassThrough ? '2-3, 3-4, 8-9, 9-10번 연결용' : '패널 사이 연결용'
                    },
                    {
                        type: 'Sㄷ자 하부몰딩',
                        size: carDepth - 5,
                        count: 2,
                        elevatorCount: elevatorCount,
                        totalCount: 2 * elevatorCount,
                        description: '측면 하부 가로'
                    }
                ];

                // 관통형일 때는 별도 ㄷ자 몰딩 항목 추가하지 않음 (기존 ㄷ자 몰딩에 통합)
                // 일반형일 때만 코너몰딩과 Rㄷ자 하부몰딩 추가
                if (!isPassThrough) {
                    result.push({
                        type: '코너몰딩',
                        size: productionHeight,
                        count: 2,
                        elevatorCount: elevatorCount,
                        totalCount: 2 * elevatorCount,
                        description: '4-5, 7-8번 연결용'
                    });
                    result.push({
                        type: 'Rㄷ자 하부몰딩',
                        size: carWidth - 2,
                        count: 1,
                        elevatorCount: elevatorCount,
                        totalCount: 1 * elevatorCount,
                        description: '후면 하부 가로'
                    });
                }

                return result;
                <?php else: ?>
                return [];
                <?php endif; ?>
            }

            function renderMoldingTable(moldingData) {
                const tableBody = document.getElementById('moldingTableBody');
                if (!tableBody) return;

                tableBody.innerHTML = '';

                moldingData.forEach(item => {
                    const row = document.createElement('tr');
                    row.style.borderBottom = '1px solid var(--linear-border-secondary)';

                    row.innerHTML = `
                        <td style="padding: var(--linear-spacing-sm); color: var(--linear-text-primary); font-weight: 500;">
                            ${item.type}
                            <div style="font-size: 0.8rem; color: var(--linear-text-secondary); margin-top: 2px;">
                                ${item.description}
                            </div>
                        </td>
                        <td style="padding: var(--linear-spacing-sm); text-align: center; color: var(--linear-brand-primary); font-weight: 600; font-family: monospace;">
                            ${item.size}mm
                        </td>
                        <td style="padding: var(--linear-spacing-sm); text-align: center; color: var(--linear-text-primary);">
                            ${item.count}EA
                        </td>
                        <td style="padding: var(--linear-spacing-sm); text-align: center; color: var(--linear-success-text); font-weight: 600;">
                            ${item.elevatorCount}대
                        </td>
                        <td style="padding: var(--linear-spacing-sm); text-align: center; color: var(--linear-accent-text); font-weight: 600;">
                            ${item.totalCount}EA
                        </td>
                    `;

                    tableBody.appendChild(row);
                });
            }

        }

        // 설정 저장 후 실시간 UI 업데이트 함수
        function updateUIWithNewSettings(updatedData) {

            try {
                // 1. 전역 selectedData 업데이트
                <?php if ($selected_data): ?>
                const currentData = <?= json_encode($selected_data) ?>;
                Object.assign(currentData, updatedData);
                <?php endif; ?>

                // 2. 엘리베이터 대수 필드 업데이트
                const elevatorCountInput = document.getElementById('elevatorCount');
                if (elevatorCountInput && updatedData.elevator_count) {
                    elevatorCountInput.value = updatedData.elevator_count;
                }

                // 3. 몰딩 테이블 실시간 업데이트 (몰딩이 포함된 경우)
                const moldingCheckbox = document.getElementById('moldingIncluded');
                if (moldingCheckbox && moldingCheckbox.checked && updatedData.elevator_count) {
                    // updateMoldingTableRealtime 함수를 사용하여 실시간 업데이트
                    if (typeof window.updateMoldingTableRealtime === 'function') {
                        window.updateMoldingTableRealtime(parseInt(updatedData.elevator_count));
                    } else {
                        console.warn('updateMoldingTableRealtime 함수를 찾을 수 없음');
                    }
                }

                // 4. (신규/MOD 선택) 라디오 버튼 업데이트
                if (updatedData.project_type) {
                    const projectTypeRadio = document.querySelector(`input[name="project_type"][value="${updatedData.project_type}"]`);
                    if (projectTypeRadio) {
                        projectTypeRadio.checked = true;
                    }
                }

                // 5. 체크박스 상태 업데이트 (이벤트 트리거 방지)
                const checkboxUpdates = [
                    { id: 'panelCornersExcluded', field: 'panel_corners_excluded' },
                    { id: 'transomExcluded', field: 'transom_excluded' },
                    { id: 'moldingIncluded', field: 'molding_included' }
                ];

                checkboxUpdates.forEach(item => {
                    const checkbox = document.getElementById(item.id);
                    if (checkbox && updatedData.hasOwnProperty(item.field)) {
                        // 이벤트 리스너 임시 제거하여 change 이벤트 방지
                        const originalHandler = checkbox.onchange;
                        checkbox.onchange = null;

                        checkbox.checked = updatedData[item.field] == 1;

                        // 이벤트 리스너 복원
                        setTimeout(() => {
                            checkbox.onchange = originalHandler;
                        }, 100);
                    }
                });

                // 6. 제작 높이 필드 업데이트
                const productionHeightInput = document.getElementById('productionHeight');
                if (productionHeightInput && updatedData.production_height) {
                    productionHeightInput.value = updatedData.production_height;
                }

                // 7. 치수 및 수량 섹션 업데이트
                if (updatedData.elevator_count) {
                    const elevatorCount = parseInt(updatedData.elevator_count);

                    // 배지 업데이트
                    const quantityBadge = document.getElementById('quantityBadge');
                    if (quantityBadge) {
                        quantityBadge.textContent = elevatorCount + '대';
                    }

                    // 총 제작 대수 업데이트
                    const totalQuantityValue = document.getElementById('totalQuantityValue');
                    if (totalQuantityValue) {
                        totalQuantityValue.textContent = elevatorCount;
                    }

                    // 패널별 수량/대수 업데이트
                    const panelDimensionList = document.getElementById('panelDimensionList');
                    if (panelDimensionList) {
                        const quantityElements = panelDimensionList.querySelectorAll('.quantity-value-item');
                        quantityElements.forEach(element => {
                            element.textContent = elevatorCount;
                        });
                    }

                }

                // 8. 제작사이즈 변경 확인 및 페이지 새로고침 (make_panel_data 업데이트 반영)
                const productionSettingsChanged = updatedData.production_height || updatedData.production_height1_11 ||
                                                 updatedData.hasOwnProperty('panel_corners_excluded');

                if (productionSettingsChanged) {
                    setTimeout(() => {
                        window.location.reload();
                    }, 2500); // 성공 메시지와 UI 업데이트 완료 후 새로고침
                    return;
                }

                // 9. 성공 메시지 (제작사이즈 변경이 없는 경우)

            } catch (error) {
                console.error('❌ 실시간 UI 업데이트 오류:', error);
                // 오류 발생 시 안전하게 새로고침
                setTimeout(() => window.location.reload(), 1000);
            }
        }

        // 엘리베이터 대수 변경 시 실시간 몰딩 테이블 업데이트
        function updateMoldingTableRealtime(newElevatorCount) {
            <?php if ($selected_data): ?>
            try {
                // 임시로 엘리베이터 대수 업데이트
                const originalData = <?= json_encode($selected_data) ?>;
                const originalElevatorCount = originalData.elevator_count;

                // 임시 업데이트
                originalData.elevator_count = newElevatorCount;

                // 몰딩 데이터 재계산
                const moldingData = calculateMoldingData();
                renderMoldingTable(moldingData);

                // 제목 업데이트
                const moldingInfoTitle = document.getElementById('moldingInfoTitle');
                if (moldingInfoTitle) {
                    moldingInfoTitle.textContent = `몰딩 절단치수 정보 (${newElevatorCount}대)`;
                }

                // 원래 값으로 복원
                originalData.elevator_count = originalElevatorCount;


            } catch (error) {
                console.error('실시간 몰딩 테이블 업데이트 오류:', error);
            }
            <?php endif; ?>
        }

        // 1,11번 패널 높이 필드 동적 표시/숨김 기능
        function initializeDynamicHeightFields() {
            const panelCornersCheckbox = document.getElementById('panelCornersExcluded');
            const height1_11Field = document.getElementById('height1_11Field');

            function toggleHeight1_11Field() {
                if (panelCornersCheckbox && height1_11Field) {
                    // 1,11번 패널 제외가 체크되지 않은 경우에만 높이 필드 표시
                    const showField = !panelCornersCheckbox.checked;
                    height1_11Field.style.display = showField ? 'block' : 'none';

                }
            }

        // 페이지 로드시 초기 상태 설정
        toggleHeight1_11Field();

        // 체크박스 변경시 동적 업데이트
        if (panelCornersCheckbox) {
            panelCornersCheckbox.addEventListener('change', toggleHeight1_11Field);
        }

        // 그룹 내보내기인 경우 자동으로 엑셀 내보내기 실행
        <?php if ($group_export): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // 그룹의 모든 현장 데이터를 직접 엑셀로 내보내기
            setTimeout(() => {
                const measurements = <?= json_encode($measurements) ?>;
                if (measurements && measurements.length > 0) {
                    // 폼 생성하여 엑셀 내보내기 실행
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'export_merged_production_data.php';
                    form.target = '_blank';
                    
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'measurements';
                    input.value = JSON.stringify(measurements);
                    
                    form.appendChild(input);
                    document.body.appendChild(form);
                    form.submit();
                    document.body.removeChild(form);
                }
            }, 1000);
        });
        <?php endif; ?>
        }

        // 제작 조건 설정 적용 함수
        function applyProductionSettings() {
            const form = document.getElementById('productionSettingsForm');
            if (!form) {
                console.error('제작 조건 설정 폼을 찾을 수 없습니다.');
                return;
            }

            const formData = new FormData(form);

            // FormData 내용 디버깅
            console.log('=== FORM DATA DEBUG ===');
            for (let [key, value] of formData.entries()) {
                console.log(`${key}: ${value}`);
            }
            console.log('=== END FORM DATA DEBUG ===');

            // 체크박스 값 처리 (체크되지 않은 경우 0으로 설정)
            const settings = {
                measurement_id: formData.get('measurement_id'),
                project_type: formData.get('project_type') || '신규',
                panel_corners_excluded: formData.get('panel_corners_excluded') ? 1 : 0,
                transom_excluded: formData.get('transom_excluded') ? 1 : 0,
                molding_included: formData.get('molding_included') ? 1 : 0,
                elevator_count: parseInt(formData.get('elevator_count')) || 1,
                production_height: parseInt(formData.get('production_height')) || 0,
                production_height1_11: parseInt(formData.get('production_height1_11')) || 0
            };

            // molding_data 추가 (몰딩이 포함된 경우에만)
            if (settings.molding_included) {
                try {
                    // 현재 molding 데이터 계산
                    const moldingData = window.calculateMoldingData ? window.calculateMoldingData() : [];
                    if (moldingData && moldingData.length > 0) {
                        settings.molding_data = JSON.stringify(moldingData);
                        console.log('Added molding_data to settings:', moldingData);
                    }
                } catch (error) {
                    console.error('Error calculating molding data:', error);
                }
            }
            
            console.log('=== SETTINGS DEBUG ===');
            console.log('Raw production_height1_11 from form:', formData.get('production_height1_11'));
            console.log('Parsed production_height1_11:', parseInt(formData.get('production_height1_11')) || 0);
            console.log('Final settings:', settings);
            console.log('=== END SETTINGS DEBUG ===');

            // 엘리베이터 대수 추출 세부 확인
            const rawElevatorCount = formData.get('elevator_count');
            const parsedElevatorCount = parseInt(rawElevatorCount);


            // 로딩 상태 표시
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '설정 적용 중...',
                    text: '제작 조건을 저장하고 있습니다.',
                    icon: 'info',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    willOpen: () => {
                        Swal.showLoading();
                    },
                    didOpen: () => {
                        // Register SweetAlert2 modal for mobile handler
                        if (window.mobileModalHandler) {
                            window.mobileModalHandler.registerModalOpen({
                                id: 'swal_applying_settings',
                                type: 'swal',
                                closeCallback: () => {
                                    if (Swal.isVisible()) {
                                        Swal.close();
                                    }
                                }
                            });
                        }
                    },
                    willClose: () => {
                        // Register modal close when SweetAlert2 closes
                        if (window.mobileModalHandler) {
                            window.mobileModalHandler.registerModalClose('swal_applying_settings');
                        }
                    }
                });
            }

            // AJAX로 서버에 설정 저장
            fetch('update_panel.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(settings)
            })
            .then(response => {
                return response.json();
            })
            .then(data => {

                if (data.success) {
                    // 제작 조건이 성공적으로 저장되었으므로 패널 시각화 업데이트
                    updatePanelVisualizationWithNewSettings(data.data);

                    // 엘리베이터 대수가 제대로 저장되었는지 확인
                    if (data.data && data.data.elevator_count) {
                    } else {
                        console.warn('⚠️ 엘리베이터 대수가 응답에 포함되지 않음');
                    }
                    
                    // 패널별 상세 치수가 서버에서 다시 계산되도록 페이지 새로고침
                    setTimeout(() => {
                        location.reload();
                    }, 1000);

                    // 몰딩 테이블 즉시 업데이트 (엘리베이터 대수 반영)
                    if (typeof calculateMoldingData === 'function' && typeof renderMoldingTable === 'function') {
                        // 업데이트된 설정으로 몰딩 데이터 재계산
                        <?php if ($selected_data): ?>
                        // 몰딩이 포함된 경우에만 테이블 업데이트
                        if (settings.molding_included) {
                            // 임시로 엘리베이터 대수 업데이트하여 계산
                            const originalData = <?= json_encode($selected_data) ?>;
                            const tempData = {
                                ...originalData,
                                elevator_count: settings.elevator_count,
                                production_height: settings.production_height
                            };

                            // 임시 계산을 위해 전역 selectedData 임시 업데이트
                            const originalElevatorCount = originalData.elevator_count;
                            const originalProductionHeight = originalData.production_height;

                            // 임시 업데이트
                            originalData.elevator_count = settings.elevator_count;
                            originalData.production_height = settings.production_height;

                            const moldingData = calculateMoldingData();
                            renderMoldingTable(moldingData);

                            // 몰딩 테이블 표시
                            const moldingInfoContainer = document.getElementById('moldingInfoContainer');
                            if (moldingInfoContainer) {
                                moldingInfoContainer.style.display = 'block';
                            }

                            // 몰딩 정보 제목 업데이트
                            const moldingInfoTitle = document.getElementById('moldingInfoTitle');
                            if (moldingInfoTitle) {
                                moldingInfoTitle.textContent = `몰딩 절단치수 정보 (${settings.elevator_count}대)`;
                            }

                            // 원래 값으로 복원 (페이지 새로고침 전까지만 임시 적용)
                            originalData.elevator_count = originalElevatorCount;
                            originalData.production_height = originalProductionHeight;

                        }
                        <?php endif; ?>
                    }

                    // 성공 메시지 표시
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: '설정 적용 완료',
                            text: `제작 조건이 성공적으로 저장되었습니다.\n엘리베이터 ${settings.elevator_count}대 기준으로 몰딩 수량이 계산됩니다.`,
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false,
                            didOpen: () => {
                                // Register SweetAlert2 modal for mobile handler
                                if (window.mobileModalHandler) {
                                    window.mobileModalHandler.registerModalOpen({
                                        id: 'swal_settings_success',
                                        type: 'swal',
                                        closeCallback: () => {
                                            if (Swal.isVisible()) {
                                                Swal.close();
                                            }
                                        }
                                    });
                                }
                            },
                            willClose: () => {
                                // Register modal close when SweetAlert2 closes
                                if (window.mobileModalHandler) {
                                    window.mobileModalHandler.registerModalClose('swal_settings_success');
                                }
                            }
                        }).then(() => {
                            // 실시간 화면 업데이트 (새로고침 없이)
                            updateUIWithNewSettings(data.data);
                        });
                    } else {
                        alert('제작 조건이 성공적으로 저장되었습니다.');
                        // 실시간 화면 업데이트 (새로고침 없이)
                        setTimeout(() => {
                            updateUIWithNewSettings(data.data);
                        }, 1000);
                    }

                } else {
                    console.error('❌ 제작 조건 저장 실패:', data.message);

                    // 오류 메시지 표시
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: '설정 적용 실패',
                            text: data.message || '제작 조건 저장 중 오류가 발생했습니다.',
                            icon: 'error',
                            confirmButtonText: '확인',
                            didOpen: () => {
                                // Register SweetAlert2 modal for mobile handler
                                if (window.mobileModalHandler) {
                                    window.mobileModalHandler.registerModalOpen({
                                        id: 'swal_settings_error',
                                        type: 'swal',
                                        closeCallback: () => {
                                            if (Swal.isVisible()) {
                                                Swal.close();
                                            }
                                        }
                                    });
                                }
                            },
                            willClose: () => {
                                // Register modal close when SweetAlert2 closes
                                if (window.mobileModalHandler) {
                                    window.mobileModalHandler.registerModalClose('swal_settings_error');
                                }
                            }
                        });
                    } else {
                        alert('오류: ' + (data.message || '제작 조건 저장 중 오류가 발생했습니다.'));
                    }
                }
            })
            .catch(error => {
                console.error('🔥 AJAX 요청 오류:', error);

                // 네트워크 오류 메시지 표시
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: '통신 오류',
                        text: '서버와의 통신 중 오류가 발생했습니다. 네트워크 연결을 확인해주세요.',
                        icon: 'error',
                        confirmButtonText: '확인',
                        didOpen: () => {
                            // Register SweetAlert2 modal for mobile handler
                            if (window.mobileModalHandler) {
                                window.mobileModalHandler.registerModalOpen({
                                    id: 'swal_network_error',
                                    type: 'swal',
                                    closeCallback: () => {
                                        if (Swal.isVisible()) {
                                            Swal.close();
                                        }
                                    }
                                });
                            }
                        },
                        willClose: () => {
                            // Register modal close when SweetAlert2 closes
                            if (window.mobileModalHandler) {
                                window.mobileModalHandler.registerModalClose('swal_network_error');
                            }
                        }
                    });
                } else {
                    alert('통신 오류: 서버와의 통신 중 문제가 발생했습니다.');
                }
            });
        }

        // selectMeasurement 함수를 전역으로 정의
        window.selectMeasurement = function(measurementId) {
            // 페이지 이동 - 새로운 UI 시스템이 자동으로 선택된 항목을 표시함
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('measurement_id', measurementId);
            window.location.href = currentUrl.toString();
        };

        // 카구조 필터 기능 제거 (읽기 전용으로 변경됨)
        // 이제 카구조는 선택된 데이터의 정보만 표시하고 필터링하지 않음





        function copyResultLink() {
            const currentUrl = window.location.href;

            // 클립보드에 복사 시도
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(currentUrl).then(function() {
                    Swal.fire({
                        icon: 'success',
                        title: '링크 복사 완료',
                        text: '제작산출 결과 링크가 클립보드에 복사되었습니다.',
                        showConfirmButton: false,
                        timer: 2000,
                        toast: true,
                        position: 'top-end',
                        didOpen: () => {
                            // Register SweetAlert2 modal for mobile handler
                            if (window.mobileModalHandler) {
                                window.mobileModalHandler.registerModalOpen({
                                    id: 'swal_copy_success',
                                    type: 'swal',
                                    closeCallback: () => {
                                        if (Swal.isVisible()) {
                                            Swal.close();
                                        }
                                    }
                                });
                            }
                        },
                        willClose: () => {
                            // Register modal close when SweetAlert2 closes
                            if (window.mobileModalHandler) {
                                window.mobileModalHandler.registerModalClose('swal_copy_success');
                            }
                        }
                    });
                }).catch(function(err) {
                    fallbackCopyTextToClipboard(currentUrl);
                });
            } else {
                fallbackCopyTextToClipboard(currentUrl);
            }
        }

        function fallbackCopyTextToClipboard(text) {
            const textArea = document.createElement("textarea");
            textArea.value = text;

            // 화면에 보이지 않게 설정
            textArea.style.top = "0";
            textArea.style.left = "0";
            textArea.style.position = "fixed";
            textArea.style.opacity = "0";

            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    Swal.fire({
                        icon: 'success',
                        title: '링크 복사 완료',
                        text: '제작산출 결과 링크가 클립보드에 복사되었습니다.',
                        showConfirmButton: false,
                        timer: 2000,
                        toast: true,
                        position: 'top-end',
                        didOpen: () => {
                            // Register SweetAlert2 modal for mobile handler
                            if (window.mobileModalHandler) {
                                window.mobileModalHandler.registerModalOpen({
                                    id: 'swal_copy_fallback_success',
                                    type: 'swal',
                                    closeCallback: () => {
                                        if (Swal.isVisible()) {
                                            Swal.close();
                                        }
                                    }
                                });
                            }
                        },
                        willClose: () => {
                            // Register modal close when SweetAlert2 closes
                            if (window.mobileModalHandler) {
                                window.mobileModalHandler.registerModalClose('swal_copy_fallback_success');
                            }
                        }
                    });
                } else {
                    showManualCopyDialog(text);
                }
            } catch (err) {
                showManualCopyDialog(text);
            }

            document.body.removeChild(textArea);
        }

        function showManualCopyDialog(text) {
            Swal.fire({
                title: '링크 복사',
                html: `
                    <p>아래 링크를 수동으로 복사해주세요:</p>
                    <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0; word-break: break-all; font-family: monospace; font-size: 12px;">
                        ${text}
                    </div>
                `,
                icon: 'info',
                confirmButtonText: '확인',
                confirmButtonColor: '#007bff',
                didOpen: () => {
                    // Register SweetAlert2 modal for mobile handler
                    if (window.mobileModalHandler) {
                        window.mobileModalHandler.registerModalOpen({
                            id: 'swal_copy_manual',
                            type: 'swal',
                            closeCallback: () => {
                                if (Swal.isVisible()) {
                                    Swal.close();
                                }
                            }
                        });
                    }
                },
                willClose: () => {
                    // Register modal close when SweetAlert2 closes
                    if (window.mobileModalHandler) {
                        window.mobileModalHandler.registerModalClose('swal_copy_manual');
                    }
                }
            });
        }

        // Panel Visualization Functions
        document.addEventListener('DOMContentLoaded', function() {
            initializePanelVisualization();
            initializeDynamicHeightFields();
            initializePanelVisualizationToggle();
            initializeProductionResultsToggle();
            initializeMoldingToggle();
            initializeMoldingTooltips();
        });

        function initializePanelVisualization() {
            <?php if ($selected_data): ?>
            const selectedData = <?= json_encode($selected_data) ?>;
            const panelData = <?= json_encode($panel_data) ?>;
            const transomData = <?= json_encode($transom_data) ?>;
            
            console.log('🔍 DEBUG: selectedData 전체 구조:', selectedData);
            console.log('🔍 DEBUG: selectedData.transom_data 원본:', selectedData.transom_data);
            console.log('🔍 DEBUG: PHP에서 로드된 transomData:', transomData);
            console.log('🔍 DEBUG: transomData 타입:', typeof transomData);
            console.log('🔍 DEBUG: transomData 길이:', Array.isArray(transomData) ? transomData.length : Object.keys(transomData).length);


            // Apply project settings to panel visibility
            applyProjectSettings(selectedData);

            // Render panel information
            renderPanelVisualization(panelData, transomData);

            // Add hover effects
            addPanelHoverEffects(panelData, transomData);
            <?php endif; ?>
        }

        // 제작 조건 설정이 업데이트된 후 패널 시각화를 실시간으로 업데이트하는 함수
        function updatePanelVisualizationWithNewSettings(updatedData) {
            if (!updatedData) {
                console.warn('업데이트된 데이터가 없습니다.');
                return;
            }

            // 업데이트된 제작 조건을 전역 변수에 저장
            if (window.currentSelectedData) {
                window.currentSelectedData.production_height = updatedData.production_height;
                window.currentSelectedData.production_height1_11 = updatedData.production_height1_11;
                window.currentSelectedData.panel_corners_excluded = updatedData.panel_corners_excluded;
                window.currentSelectedData.transom_excluded = updatedData.transom_excluded;
                window.currentSelectedData.molding_included = updatedData.molding_included;
            }

            // 패널 시각화 다시 렌더링
            const panelData = <?= json_encode($selected_data['panel_data']) ?>;
            const transomData = <?= json_encode($selected_data['transom_data']) ?>;
            console.log('🔍 DEBUG: selected_data에서 로드된 transomData:', transomData);
            console.log('🔍 DEBUG: transomData 타입:', typeof transomData);
            console.log('🔍 DEBUG: transomData 길이:', Array.isArray(transomData) ? transomData.length : Object.keys(transomData).length);
            
            // transom 데이터가 존재하면 렌더링 (빈 객체여도 처리)
            if (panelData) {
                console.log('🔍 DEBUG: renderPanelVisualization 호출 전 - panelData:', panelData);
                console.log('🔍 DEBUG: renderPanelVisualization 호출 전 - transomData:', transomData);
                renderPanelVisualization(panelData, transomData);
            } else {
                console.log('⚠️ DEBUG: panelData가 없어서 렌더링을 건너뜀');
            }

            // 패널별 상세 치수도 업데이트 (새로고침으로 인해 서버에서 다시 계산됨)
            console.log('패널 시각화와 상세 치수가 새로운 제작 조건으로 업데이트되었습니다.');
        }

        function applyProjectSettings(selectedData) {
            // 저장된 값 읽기 (정수로 변환하여 0/1 값으로 처리)
            const panelCornersExcluded = parseInt(selectedData.panel_corners_excluded) || 0;
            const transomExcluded = parseInt(selectedData.transom_excluded) || 0;


            // 2-10번 패널은 항상 표시
            for (let i = 2; i <= 10; i++) {
                const panel = document.querySelector(`.panel-${i}`);
                if (panel) panel.style.display = 'flex';
            }

            // 1,11번 패널 제어 (저장된 값에 따라)
            const panel1 = document.querySelector('.panel-1');
            const panel11 = document.querySelector('.panel-11');

            if (panelCornersExcluded === 1) {
                // 1,11번 패널 제외가 체크된 경우 숨김
                if (panel1) {
                    panel1.style.display = 'none';
                }
                if (panel11) {
                    panel11.style.display = 'none';
                }
            } else {
                // 1,11번 패널 제외가 해제된 경우 표시
                if (panel1) {
                    panel1.style.display = 'flex';
                }
                if (panel11) {
                    panel11.style.display = 'flex';
                }
            }

            // 트랜섬 패널 제어 (저장된 값에 따라)
            const panel12 = document.querySelector('.panel-12');
            if (transomExcluded === 1) {
                // 트랜섬 제외가 체크된 경우 숨김
                if (panel12) {
                    panel12.style.display = 'none';
                }
            } else {
                // 트랜섬 제외가 해제된 경우 표시
                if (panel12) {
                    panel12.style.display = 'flex';
                }
            }
        }

        function renderPanelVisualization(panelData, transomData) {
            // 관통형인 경우 5, 6, 7번 패널 숨기기
            const selectedData = window.currentSelectedData || <?= json_encode($selected_data) ?>;
            const isPassThrough = selectedData && selectedData.car_structure === '관통형';
            
            if (isPassThrough) {
                // 관통형인 경우 5, 6, 7번 패널 숨기기
                const panelsToHide = ['5', '6', '7'];
                panelsToHide.forEach(panelNum => {
                    const panelElement = document.querySelector(`.panel-${panelNum}`);
                    if (panelElement) {
                        panelElement.style.display = 'none';
                        console.log(`관통형으로 인해 패널 ${panelNum} 숨김`);
                    }
                });
            } else {
                // 일반형인 경우 5, 6, 7번 패널 보이기
                const panelsToShow = ['5', '6', '7'];
                panelsToShow.forEach(panelNum => {
                    const panelElement = document.querySelector(`.panel-${panelNum}`);
                    if (panelElement) {
                        panelElement.style.display = 'flex';
                        console.log(`일반형으로 인해 패널 ${panelNum} 표시`);
                    }
                });
            }
            
            // Combine panel and transom data
            const allData = { ...panelData };
            
            // transom 데이터 처리 - 더 강력한 검증 및 디버깅
            if (transomData && typeof transomData === 'object') {
                console.log('🔍 DEBUG: transomData 존재:', transomData);
                console.log('🔍 DEBUG: transomData 타입:', typeof transomData);
                console.log('🔍 DEBUG: transomData 길이:', Array.isArray(transomData) ? transomData.length : Object.keys(transomData).length);
                
                // 빈 배열이나 빈 객체 체크
                const isEmpty = Array.isArray(transomData) ? transomData.length === 0 : Object.keys(transomData).length === 0;
                
                if (isEmpty) {
                    console.log('⚠️ DEBUG: transomData가 비어있음 (빈 배열 또는 빈 객체)');
                } else {
                    // transom 데이터의 모든 키 확인
                    const availableKeys = Object.keys(transomData);
                    console.log('🔍 DEBUG: transomData 사용 가능한 키들:', availableKeys);
                    
                    if (transomData['12']) {
                        allData['12'] = transomData['12'];
                        console.log('✅ DEBUG: transom 데이터 적용됨 (12 키):', transomData['12']);
                    } else if (transomData['transom']) {
                        allData['12'] = transomData['transom'];
                        console.log('✅ DEBUG: transom 데이터 적용됨 (transom 키):', transomData['transom']);
                    } else {
                        console.log('⚠️ DEBUG: transom 데이터에 12 또는 transom 키가 없음');
                        console.log('🔍 DEBUG: transom 데이터 구조:', JSON.stringify(transomData, null, 2));
                        
                        // 다른 가능한 키들 확인
                        const possibleKeys = availableKeys.filter(key => 
                            key.includes('transom') || key.includes('12') || key.includes('transom')
                        );
                        if (possibleKeys.length > 0) {
                            console.log('🔍 DEBUG: 가능한 transom 관련 키들:', possibleKeys);
                        }
                    }
                }
            } else {
                console.log('⚠️ DEBUG: transomData가 null, undefined 또는 객체가 아님:', transomData);
            }

            Object.keys(allData).forEach(panelNumber => {
                const data = allData[panelNumber];
                const panelElement = document.querySelector(`.panel-${panelNumber}`);

                if (panelElement && data) {
                    // Mark panel as having data
                    panelElement.classList.add('has-info');

                    // Add drilling class if panel has drilling information
                    if (data.has_drilling && data.drilling_width && data.drilling_height) {
                        panelElement.classList.add('has-drilling');
                    } else {
                        panelElement.classList.remove('has-drilling');
                    }

                    // Create panel info content
                    const panelInfo = createPanelInfoContent(panelNumber, data);

                    // Clear existing content and add new info
                    panelElement.innerHTML = '';
                    panelElement.appendChild(panelInfo);

                }
            });
        }

        function createPanelInfoContent(panelNumber, data) {
            const infoDiv = document.createElement('div');
            infoDiv.className = 'panel-info';

            let content = '';

            // Panel number
            if (panelNumber === '12') {
                content += '<div style="font-weight: bold; color: var(--linear-accent-text);">T</div>';
            } else {
                content += `<div style="font-weight: bold;">${panelNumber}</div>`;
            }

            // Material type
            if (data.materialType) {
                content += `<div class="material">${data.materialType}</div>`;
            }

            // Dimensions with production height and molding deduction
            if (data.width && data.height) {
                // 제작 높이 적용 - 실시간 업데이트된 설정값 사용
                const selectedData = window.currentSelectedData || <?= json_encode($selected_data) ?>;
                let displayHeight = data.height;
                let displayWidth = data.width;

                // 2~10번 패널: production_height 사용
                if (panelNumber >= '2' && panelNumber <= '10') {
                    if (selectedData.production_height) {
                        displayHeight = selectedData.production_height;
                    }
                }
                // 1,11번 패널: production_height1_11 사용 (있으면), 없으면 production_height 사용
                else if (panelNumber === '1' || panelNumber === '11') {
                    if (selectedData.production_height1_11) {
                        displayHeight = selectedData.production_height1_11;
                    } else if (selectedData.production_height) {
                        displayHeight = selectedData.production_height;
                    }
                }

                // 몰딩포함 시 패널별 width 차감 적용
                const moldingCheckbox = document.getElementById('moldingIncluded');
                if (moldingCheckbox && moldingCheckbox.checked) {
                    const panelNum = parseInt(panelNumber);
                    if (panelNum >= 2 && panelNum <= 10) {
                        // 패널별 몰딩 차감값 정의
                        let moldingDeduction = 0;
                        const selectedData = window.currentSelectedData || <?= json_encode($selected_data) ?>;
                        const isPassThrough = selectedData && selectedData.car_structure === '관통형';
                        
                        if (panelNum === 2 || panelNum === 10) {
                            moldingDeduction = 5; // 2번, 10번: -5
                        } else if (panelNum === 3 || panelNum === 6 || panelNum === 9) {
                            moldingDeduction = 4; // 3번, 6번, 9번: -4
                        } else if (panelNum === 4 || panelNum === 5 || panelNum === 7 || panelNum === 8) {
                            // 관통형일 때 4번, 8번은 -5, 일반형일 때는 -10
                            if (isPassThrough && (panelNum === 4 || panelNum === 8)) {
                                moldingDeduction = 5; // 관통형 4번, 8번: -5
                            } else {
                                moldingDeduction = 10; // 일반형 또는 5번, 7번: -10
                            }
                        }
                        displayWidth = data.width - moldingDeduction;
                    }
                }

                content += `<div class="dimensions">`;
                content += `<span class="width-value panel-width-value">${Math.round(displayWidth)}</span>×`;
                content += `<span class="panel-height-value" style="color: var(--linear-success-text); font-weight: bold;">${Math.round(displayHeight)}</span>`;
                content += `</div>`;
            }

            // Thickness
            if (data.thickness) {
                content += `<div class="dimensions">${data.thickness}t</div>`;
            }

            // Drilling information
            if (data.has_drilling && data.drilling_width && data.drilling_height) {
                // 아이파크 프로젝트 확인 (ipark_check 필드 사용)
                const isIparkProject = window.currentSelectedData &&
                    (window.currentSelectedData.ipark_check == 1 || window.currentSelectedData.ipark_check === true);

                // 디버깅: 아이파크 체크 상태 확인
                console.log(`[패널 ${panelNumber}] 아이파크 체크: ${isIparkProject}, ipark_check 값: ${window.currentSelectedData?.ipark_check}`);

                let displayWidth = data.drilling_width;
                let displayHeight = data.drilling_height;
                let displayFromFloor = data.drilling_from_floor;
                let displayFromEntrance = data.drilling_from_entrance;

                // 디버깅: 패널 번호와 width, drilling_width 값 확인
                if (panelNumber === '3' || panelNumber === '6' || panelNumber === '9') {
                    console.log(`[패널 ${panelNumber}] width: ${data.width}, 실측 drilling_width: ${data.drilling_width}, type: ${typeof data.drilling_width}`);
                }

                // 아이파크 프로젝트인 경우 제작치수 적용
                if (isIparkProject) {
                    // 제작가로 = 실측가로 - 70
                    displayWidth = parseInt(data.drilling_width) - 70;

                    // 제작세로 = 실측세로 - 13
                    displayHeight = parseInt(data.drilling_height) - 13;

                    // 제작바닥높이 = 실측바닥높이 + 6
                    if (data.drilling_from_floor) {
                        displayFromFloor = parseInt(data.drilling_from_floor) + 6;
                    }

                    // 제작출입구위치 = (제작패널전체폭 - 제작가로크기확정) / 2
                    if (data.drilling_from_entrance && data.width) {
                        const productionWidth = parseInt(data.width); // 제작패널전체폭
                        const productionDrillingWidth = displayWidth; // 제작가로크기확정
                        displayFromEntrance = (productionWidth - productionDrillingWidth) / 2;
                    }
                }
                
                // panel_measurement.php와 동일한 형식: ⊙가로×세로H높이D거리
                let drillingText = `⊙${Math.round(displayWidth)}×${Math.round(displayHeight)}`;
                
                // 높이(바닥부터) 추가
                if (displayFromFloor) {
                    drillingText += `H${Math.round(displayFromFloor)}`;
                }
                
                // 거리(출입구에서) 추가
                if (displayFromEntrance) {
                    drillingText += `D${Math.round(displayFromEntrance)}`;
                }
                
                content += `<div class="drilling-info">${drillingText}</div>`;
            }

            infoDiv.innerHTML = content;
            return infoDiv;
        }

        function addPanelHoverEffects(panelData, transomData) {
            const allData = { ...panelData };
            
            // transom 데이터 처리 - '12' 키 또는 'transom' 키 모두 지원
            if (transomData) {
                if (transomData['12']) {
                    allData['12'] = transomData['12'];
                } else if (transomData['transom']) {
                    allData['12'] = transomData['transom'];
                }
            }

            Object.keys(allData).forEach(panelNumber => {
                const data = allData[panelNumber];
                const panelElement = document.querySelector(`.panel-${panelNumber}`);

                if (panelElement && data) {
                    // Create detailed tooltip content
                    const tooltip = createDetailedTooltip(panelNumber, data);

                    panelElement.addEventListener('mouseenter', function(e) {
                        showTooltip(e, tooltip);
                    });

                    panelElement.addEventListener('mouseleave', function() {
                        hideTooltip();
                    });

                    panelElement.addEventListener('mousemove', function(e) {
                        moveTooltip(e);
                    });
                }
            });
        }

        function createDetailedTooltip(panelNumber, data) {
            let tooltip = `<div style="font-weight: bold; margin-bottom: 8px; color: var(--linear-brand-primary);">`;

            if (panelNumber === '12') {
                tooltip += `Transom 패널`;
            } else {
                tooltip += `패널 ${panelNumber}번`;
            }
            tooltip += `</div>`;

            if (data.materialType) {
                tooltip += `<div><strong>재질:</strong> ${data.materialType}</div>`;
            }

            if (data.thickness) {
                tooltip += `<div><strong>두께:</strong> ${data.thickness}t</div>`;
            }

            if (data.width && data.height) {
                // 제작 높이 적용된 치수 표시 - 실시간 업데이트된 설정값 사용
                const selectedData = window.currentSelectedData || <?= json_encode($selected_data) ?>;
                let displayHeight = data.height;
                let displayWidth = data.width;

                // 2~10번 패널: production_height 사용
                if (panelNumber >= '2' && panelNumber <= '10') {
                    if (selectedData.production_height) {
                        displayHeight = selectedData.production_height;
                    }
                }
                // 1,11번 패널: production_height1_11 사용 (있으면), 없으면 production_height 사용
                else if (panelNumber === '1' || panelNumber === '11') {
                    if (selectedData.production_height1_11) {
                        displayHeight = selectedData.production_height1_11;
                    } else if (selectedData.production_height) {
                        displayHeight = selectedData.production_height;
                    }
                }

                // 몰딩포함 시 패널별 width 차감 적용
                const moldingCheckbox = document.getElementById('moldingIncluded');
                if (moldingCheckbox && moldingCheckbox.checked) {
                    const panelNum = parseInt(panelNumber);
                    if (panelNum >= 2 && panelNum <= 10) {
                        // 패널별 몰딩 차감값 정의
                        let moldingDeduction = 0;
                        const selectedData = window.currentSelectedData || <?= json_encode($selected_data) ?>;
                        const isPassThrough = selectedData && selectedData.car_structure === '관통형';
                        
                        if (panelNum === 2 || panelNum === 10) {
                            moldingDeduction = 5; // 2번, 10번: -5
                        } else if (panelNum === 3 || panelNum === 6 || panelNum === 9) {
                            moldingDeduction = 4; // 3번, 6번, 9번: -4
                        } else if (panelNum === 4 || panelNum === 5 || panelNum === 7 || panelNum === 8) {
                            // 관통형일 때 4번, 8번은 -5, 일반형일 때는 -10
                            if (isPassThrough && (panelNum === 4 || panelNum === 8)) {
                                moldingDeduction = 5; // 관통형 4번, 8번: -5
                            } else {
                                moldingDeduction = 10; // 일반형 또는 5번, 7번: -10
                            }
                        }
                        displayWidth = data.width - moldingDeduction;
                    }
                }

                // 원본 측정치수 표시
                tooltip += `<div><strong>측정치수:</strong> ${Math.round(data.width)}×${Math.round(data.height)}mm</div>`;

                // 제작치수 표시 (높이 변경 또는 몰딩 차감이 있는 경우)
                const hasHeightChange = Math.round(displayHeight) !== Math.round(data.height);
                const hasWidthChange = Math.round(displayWidth) !== Math.round(data.width);

                if (hasHeightChange || hasWidthChange) {
                    tooltip += `<div><strong>제작치수:</strong> `;
                    if (hasWidthChange) {
                        tooltip += `<span style="color: var(--linear-warning-text); font-weight: bold;">${Math.round(displayWidth)}</span>`;
                    } else {
                        tooltip += `${Math.round(displayWidth)}`;
                    }
                    tooltip += `×`;
                    if (hasHeightChange) {
                        tooltip += `<span style="color: var(--linear-success-text); font-weight: bold;">${Math.round(displayHeight)}</span>`;
                    } else {
                        tooltip += `${Math.round(displayHeight)}`;
                    }
                    tooltip += `mm</div>`;
                }
            }

            // Panel type for corner panels (1, 11)
            if ((panelNumber === '1' || panelNumber === '11') && data.panelType) {
                tooltip += `<div><strong>타입:</strong> ${data.panelType}</div>`;
            }

            // Drilling information
            if (data.drillingWidth && data.drillingHeight) {
                tooltip += `<div><strong>타공:</strong> ${Math.round(data.drillingWidth)}×${Math.round(data.drillingHeight)}mm</div>`;
            }

            // Transom specific information
            if (panelNumber === '12') {
                if (data.transomPlateHeight) {
                    tooltip += `<div><strong>막판높이:</strong> ${data.transomPlateHeight}mm</div>`;
                }
                if (data.bottomDepthJD) {
                    tooltip += `<div><strong>밑면깊이(JD):</strong> ${data.bottomDepthJD}mm</div>`;
                }
                if (data.wingValue) {
                    tooltip += `<div><strong>날개값:</strong> ${data.wingValue}mm</div>`;
                }
            }

            if (data.notes) {
                tooltip += `<div style="margin-top: 8px; font-style: italic; color: var(--linear-text-secondary);"><strong>특이사항:</strong> ${data.notes}</div>`;
            }

            return tooltip;
        }

        let tooltipElement = null;

        function showTooltip(event, content) {
            hideTooltip(); // Remove any existing tooltip

            tooltipElement = document.createElement('div');
            tooltipElement.style.cssText = `
                position: fixed;
                background: var(--linear-bg-primary);
                border: 1px solid var(--linear-border-primary);
                border-radius: var(--linear-radius-md);
                padding: var(--linear-spacing-md);
                font-size: var(--linear-text-small);
                color: var(--linear-text-primary);
                box-shadow: var(--linear-shadow-medium);
                z-index: 10000;
                max-width: 250px;
                line-height: 1.4;
                pointer-events: none;
            `;
            tooltipElement.innerHTML = content;
            document.body.appendChild(tooltipElement);

            moveTooltip(event);
        }

        function moveTooltip(event) {
            if (!tooltipElement) return;

            const rect = tooltipElement.getBoundingClientRect();
            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight;

            let x = event.clientX + 10;
            let y = event.clientY + 10;

            // Adjust position if tooltip would go off screen
            if (x + rect.width > viewportWidth) {
                x = event.clientX - rect.width - 10;
            }
            if (y + rect.height > viewportHeight) {
                y = event.clientY - rect.height - 10;
            }

            tooltipElement.style.left = x + 'px'; 
            tooltipElement.style.top = y + 'px';
        }

        function hideTooltip() {
            if (tooltipElement) {
                document.body.removeChild(tooltipElement);
                tooltipElement = null;
            }
        }

        // 몰딩 툴팁 기능 초기화
        function initializeMoldingTooltips() {
            const moldingElements = document.querySelectorAll('[data-tooltip]');
            let currentTooltip = null;

            moldingElements.forEach(element => {
                element.addEventListener('mouseenter', function(e) {
                    const tooltipText = this.getAttribute('data-tooltip');
                    if (!tooltipText) return;

                    // 기존 툴팁 제거
                    if (currentTooltip) {
                        currentTooltip.remove();
                    }

                    // 새 툴팁 생성
                    currentTooltip = document.createElement('div');
                    currentTooltip.className = 'molding-tooltip show';
                    currentTooltip.textContent = tooltipText;
                    document.body.appendChild(currentTooltip);

                    // 툴팁 위치 설정
                    positionTooltip(currentTooltip, e, this);
                });

                element.addEventListener('mouseleave', function() {
                    if (currentTooltip) {
                        currentTooltip.classList.remove('show');
                        setTimeout(() => {
                            if (currentTooltip) {
                                currentTooltip.remove();
                                currentTooltip = null;
                            }
                        }, 300);
                    }
                });

                element.addEventListener('mousemove', function(e) {
                    if (currentTooltip) {
                        positionTooltip(currentTooltip, e, this);
                    }
                });
            });
 
            function positionTooltip(tooltip, event, element) {
                const rect = element.getBoundingClientRect();
                const tooltipRect = tooltip.getBoundingClientRect();
                const viewportWidth = window.innerWidth;
                const viewportHeight = window.innerHeight;

                // 기본 위치: 요소 위쪽 중앙
                let x = rect.left + (rect.width / 2) - (tooltipRect.width / 2);
                let y = rect.top - tooltipRect.height - 10;

                // 화면 경계 체크 및 조정
                if (x < 10) {
                    x = 10;
                } else if (x + tooltipRect.width > viewportWidth - 10) {
                    x = viewportWidth - tooltipRect.width - 10;
                }

                if (y < 10) {
                    // 위쪽에 공간이 없으면 아래쪽에 표시
                    y = rect.bottom + 10;
                }

                tooltip.style.left = x + 'px';
                tooltip.style.top = y + 'px';
            }
        }

        // 몰딩 테이블 인쇄 함수
        function printMoldingTable() {
            <?php if ($selected_data): ?>
            try {
                // 현재 몰딩 데이터 가져오기
                const selectedData = <?= json_encode($selected_data) ?>;
                const elevatorCountInput = document.getElementById('elevatorCount');
                const currentElevatorCount = elevatorCountInput ? parseInt(elevatorCountInput.value) || parseInt(selectedData.elevator_count) : parseInt(selectedData.elevator_count);

                const productionHeight = parseInt(selectedData.production_height) || parseInt(selectedData.car_inside_height);
                const carWidth = parseInt(selectedData.car_inside_width);
                const carDepth = parseInt(selectedData.car_inside_depth);

                // 몰딩 데이터 생성
                const moldingData = [
                    {
                        type: 'ㄷ자 몰딩',
                        size: productionHeight,
                        count: 2,
                        elevatorCount: currentElevatorCount,
                        totalCount: 2 * currentElevatorCount,
                        description: '2번, 10번 패널용'
                    },
                    {
                        type: 'ㄴ자 몰딩',
                        size: productionHeight,
                        count: 6,
                        elevatorCount: currentElevatorCount,
                        totalCount: 6 * currentElevatorCount,
                        description: '3번, 4번, 7번, 8번, 9번 패널용'
                    },
                    {
                        type: '코너몰딩',
                        size: productionHeight,
                        count: 2, 
                        elevatorCount: currentElevatorCount,
                        totalCount: 2 * currentElevatorCount,
                        description: '5번, 6번 패널용'
                    },
                    {
                        type: 'Sㄷ자 하부몰딩',
                        size: carWidth,
                        count: 2,
                        elevatorCount: currentElevatorCount,
                        totalCount: 2 * currentElevatorCount,
                        description: '측면 하부 가로'
                    },
                    {
                        type: 'Rㄷ자 하부몰딩',
                        size: carWidth - 2,
                        count: 1,
                        elevatorCount: currentElevatorCount,
                        totalCount: 1 * currentElevatorCount,
                        description: '후면 하부 가로'
                    }
                ];

                // 인쇄용 HTML 생성
                const printContent = `
                    <div class="molding-print-container">
                        <div class="molding-print-header">
                            <div class="molding-print-title">몰딩 절단치수 정보</div>
                            <div class="molding-print-info">현장명: ${selectedData.site_name || '미입력'}</div>
                            <div class="molding-print-info">엘리베이터 대수: ${currentElevatorCount}대</div>
                            <div class="molding-print-info">인쇄일자: ${new Date().toLocaleDateString('ko-KR')}</div>
                        </div>

                        <table class="molding-print-table">
                            <thead>
                                <tr>
                                    <th>몰딩 종류</th>
                                    <th>절단치수</th>
                                    <th>개수</th>
                                    <th>대수</th>
                                    <th>총개수</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${moldingData.map(item => `
                                    <tr>
                                        <td class="molding-type">
                                            <div>${item.type}</div>
                                            <div class="molding-description">${item.description}</div>
                                        </td>
                                        <td>${item.size}mm</td>
                                        <td>${item.count}EA</td>
                                        <td>${item.elevatorCount}대</td>
                                        <td><strong>${item.totalCount}EA</strong></td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>

                        <div class="molding-print-footer">
                            오성이엘 엘리베이터 | OSEL Elevator
                        </div>
                    </div>
                `;

                // 새 창 열기
                const printWindow = window.open('', '_blank', 'width=800,height=600');

                // 인쇄용 HTML 작성
                printWindow.document.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>몰딩 절단치수 정보 - ${selectedData.site_name || '미입력'}</title>
                        <meta charset="UTF-8">
                        <style>
                            @page {
                                size: A4;
                                margin: 1cm;
                            }

                            body {
                                font-family: 'Noto Sans KR', Arial, sans-serif;
                                margin: 0;
                                padding: 20px;
                                color: #000;
                                background: #fff;
                            }

                            .molding-print-container {
                                max-width: 100%;
                            }

                            .molding-print-header {
                                text-align: center;
                                margin-bottom: 30px;
                                border-bottom: 2px solid #333;
                                padding-bottom: 20px;
                            }

                            .molding-print-title {
                                font-size: 24px;
                                font-weight: bold;
                                margin-bottom: 15px;
                                color: #333;
                            }

                            .molding-print-info {
                                font-size: 14px;
                                color: #666;
                                margin-bottom: 5px;
                            }

                            .molding-print-table {
                                width: 100%;
                                border-collapse: collapse;
                                margin-top: 20px;
                                border: 2px solid #333;
                            }

                            .molding-print-table th,
                            .molding-print-table td {
                                border: 1px solid #333;
                                padding: 12px 8px;
                                text-align: center;
                                font-size: 12px;
                            }

                            .molding-print-table th {
                                background-color: #f0f0f0;
                                font-weight: bold;
                                color: #333;
                            }

                            .molding-print-table .molding-type {
                                text-align: left;
                                font-weight: 600;
                            }

                            .molding-print-table .molding-description {
                                font-size: 10px;
                                color: #666;
                                font-style: italic;
                                margin-top: 3px;
                            }

                            .molding-print-footer {
                                margin-top: 30px;
                                text-align: right;
                                font-size: 12px;
                                color: #666;
                                border-top: 1px solid #ddd;
                                padding-top: 15px;
                            }

                            @media print {
                                body {
                                    padding: 0;
                                }
                            }
                        </style>
                    </head>
                    <body>
                        ${printContent}
                    </body>
                    </html>
                `);

                printWindow.document.close();

                // 문서 로드 후 인쇄 대화상자 열기
                printWindow.onload = function() {
                    setTimeout(() => {
                        printWindow.print();
                        // 인쇄 완료 후 창 닫기 (사용자가 취소하면 창은 열린 상태로 유지)
                        printWindow.onafterprint = function() {
                            printWindow.close();
                        };
                    }, 500);
                };


            } catch (error) {
                console.error('몰딩 테이블 인쇄 오류:', error);
                alert('인쇄 중 오류가 발생했습니다: ' + error.message);
            }
            <?php else: ?>
            alert('인쇄할 몰딩 데이터가 없습니다.');
            <?php endif; ?>
        }

        // Excel 내보내기 함수
        function exportToExcel() {
            <?php if ($selected_data): ?>
            const measurementId = <?= $selected_data['id'] ?>;

            // 현재 페이지의 설정 상태 가져오기
            const moldingIncluded = document.getElementById('moldingIncluded').checked ? 1 : 0;
            const panelCornersExcluded = document.getElementById('panelCornersExcluded').checked ? 1 : 0;

            // 확인 대화상자 없이 바로 Excel 다운로드 실행 (현재 설정 포함)
            window.location.href = 'export_production_results.php?measurement_id=' + measurementId + '&molding_included=' + moldingIncluded + '&panel_corners_excluded=' + panelCornersExcluded;
            return;

            // 기존 코드 (비활성화)
            if (false && typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Excel 내보내기',
                    text: '제작산출 결과를 Excel로 내보내시겠습니까?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#198754',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="bi bi-file-earmark-excel"></i> Excel 내보내기',
                    cancelButtonText: '취소',
                    didOpen: () => {
                        // Register SweetAlert2 modal for mobile handler
                        if (window.mobileModalHandler) {
                            window.mobileModalHandler.registerModalOpen({
                                id: 'swal_export_confirm_' + measurementId,
                                type: 'swal',
                                closeCallback: () => {
                                    if (Swal.isVisible()) {
                                        Swal.close();
                                    }
                                }
                            });
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) { 
                        // 로딩 표시
                        Swal.fire({
                            title: 'Excel 파일 생성 중...',
                            text: '잠시만 기다려주세요.',
                            icon: 'info',
                            allowOutsideClick: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                Swal.showLoading();
                                // Register SweetAlert2 modal for mobile handler
                                if (window.mobileModalHandler) {
                                    window.mobileModalHandler.registerModalOpen({
                                        id: 'swal_export_loading',
                                        type: 'swal',
                                        closeCallback: () => {
                                            if (Swal.isVisible()) {
                                                Swal.close();
                                            }
                                        }
                                    });
                                }
                            }
                        });

                        // Excel 파일 다운로드
                        window.location.href = 'export_production_results.php?measurement_id=' + measurementId;

                        // 다운로드 시작 후 로딩 닫기
                        setTimeout(() => {
                            Swal.close();
                        }, 2000);
                    }
                });
            } else {
                // 기존 confirm 대화상자도 비활성화
                // if (confirm('제작산출 결과를 Excel로 내보내시겠습니까?')) {
                //     window.location.href = 'export_production_results.php?measurement_id=' + measurementId;
                // }
            }
            <?php else: ?>
            alert('내보낼 데이터가 없습니다.');
            <?php endif; ?>
        }

        // HTML5 date input 사용으로 복잡한 커스텀 날짜 선택기 불필요
    </script>
</body>
</html>  