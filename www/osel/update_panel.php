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
    header("Content-Type: application/json");
    header("HTTP/1.1 403 Forbidden");
    exit(json_encode(['success' => false, 'message' => '인증이 필요합니다.']));
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    header("Content-Type: application/json");
    header("HTTP/1.1 405 Method Not Allowed");
    exit(json_encode(['success' => false, 'message' => 'POST 요청만 허용됩니다.']));
}

// Initialize database connection
try {
    $pdo = db_connect();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("USE $DB");
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    ob_clean();
    header("Content-Type: application/json");
    exit(json_encode(['success' => false, 'message' => '데이터베이스 연결 실패']));
}

// Debug: Log received POST data
error_log("=== UPDATE PANEL PRODUCTION SETTINGS REQUEST ===");
error_log("Request data: " . json_encode($_POST));
error_log("Raw production_height1_11 value: " . ($_POST['production_height1_11'] ?? 'NOT SET'));
error_log("Parsed production_height1_11 value: " . intval($_POST['production_height1_11'] ?? 0));

// Get form data
$measurement_id = intval($_POST['measurement_id'] ?? 0);
$project_type = trim($_POST['project_type'] ?? '신규');
$panel_corners_excluded = intval($_POST['panel_corners_excluded'] ?? 0);
$transom_excluded = intval($_POST['transom_excluded'] ?? 0);
$molding_included = intval($_POST['molding_included'] ?? 0);
$elevator_count = intval($_POST['elevator_count'] ?? 1);
$production_height = intval($_POST['production_height'] ?? 0);
$production_height1_11 = intval($_POST['production_height1_11'] ?? 0);

// Validate required fields
if ($measurement_id <= 0) {
    ob_clean();
    header("Content-Type: application/json");
    exit(json_encode(['success' => false, 'message' => '측정 ID가 필요합니다.']));
}

// Check if measurement exists
try {
    $stmt = $pdo->prepare("SELECT id FROM panel_measurements WHERE id = ?");
    $stmt->execute([$measurement_id]);

    if (!$stmt->fetch()) {
        ob_clean();
        header("Content-Type: application/json");
        exit(json_encode(['success' => false, 'message' => '해당 측정 데이터를 찾을 수 없습니다.']));
    }
} catch (PDOException $e) {
    error_log("Measurement check error: " . $e->getMessage());
    ob_clean();
    header("Content-Type: application/json");
    exit(json_encode(['success' => false, 'message' => '측정 데이터 확인 중 오류가 발생했습니다.']));
}

// Check database columns to ensure they exist
try {
    $check_columns = $pdo->query("DESCRIBE panel_measurements");
    $columns = $check_columns->fetchAll(PDO::FETCH_COLUMN);

    $has_project_type = in_array('project_type', $columns);
    $has_panel_corners_excluded = in_array('panel_corners_excluded', $columns);
    $has_transom_excluded = in_array('transom_excluded', $columns);
    $has_molding_included = in_array('molding_included', $columns);
    $has_elevator_count = in_array('elevator_count', $columns);
    $has_production_height = in_array('production_height', $columns);
    $has_production_height1_11 = in_array('production_height1_11', $columns);
    $has_make_panel_data = in_array('make_panel_data', $columns);

    error_log("Available columns - project_type: " . ($has_project_type ? 'YES' : 'NO') .
             ", panel_corners_excluded: " . ($has_panel_corners_excluded ? 'YES' : 'NO') .
             ", transom_excluded: " . ($has_transom_excluded ? 'YES' : 'NO') .
             ", molding_included: " . ($has_molding_included ? 'YES' : 'NO') .
             ", elevator_count: " . ($has_elevator_count ? 'YES' : 'NO') .
             ", production_height: " . ($has_production_height ? 'YES' : 'NO') .
             ", production_height1_11: " . ($has_production_height1_11 ? 'YES' : 'NO') .
             ", make_panel_data: " . ($has_make_panel_data ? 'YES' : 'NO'));

    // If new columns don't exist, provide guidance
    if (!$has_molding_included || !$has_production_height || !$has_production_height1_11 || !$has_make_panel_data) {
        ob_clean();
        header("Content-Type: application/json");
        exit(json_encode([
            'success' => false,
            'message' => '데이터베이스 컬럼이 업데이트되지 않았습니다. add_production_columns.sql을 실행해주세요.',
            'missing_columns' => [
                'molding_included' => $has_molding_included,
                'production_height' => $has_production_height,
                'production_height1_11' => $has_production_height1_11,
                'make_panel_data' => $has_make_panel_data
            ]
        ]));
    }

} catch (Exception $e) {
    error_log("Column check error: " . $e->getMessage());
    ob_clean();
    header("Content-Type: application/json");
    exit(json_encode(['success' => false, 'message' => '데이터베이스 컬럼 확인 중 오류가 발생했습니다.']));
}

// Build update query based on available columns
$update_fields = [];
$update_values = [];

if ($has_project_type) {
    $update_fields[] = "project_type = ?";
    $update_values[] = $project_type;
}

if ($has_panel_corners_excluded) {
    $update_fields[] = "panel_corners_excluded = ?";
    $update_values[] = $panel_corners_excluded;
}

if ($has_transom_excluded) {
    $update_fields[] = "transom_excluded = ?";
    $update_values[] = $transom_excluded;
}

if ($has_molding_included) {
    $update_fields[] = "molding_included = ?";
    $update_values[] = $molding_included;
}

if ($has_elevator_count) {
    $update_fields[] = "elevator_count = ?";
    $update_values[] = $elevator_count;
}

if ($has_production_height) {
    $update_fields[] = "production_height = ?";
    $update_values[] = $production_height;
}

if ($has_production_height1_11) {
    $update_fields[] = "production_height1_11 = ?";
    $update_values[] = $production_height1_11;
}

// Generate make_panel_data if production settings changed
$make_panel_data_json = null;
if ($has_make_panel_data && ($has_production_height || $has_production_height1_11 || $has_panel_corners_excluded)) {
    try {
        // Get original panel_data for make_panel_data generation
        $panel_data_query = "SELECT panel_data FROM panel_measurements WHERE id = ?";
        $panel_stmt = $pdo->prepare($panel_data_query);
        $panel_stmt->execute([$measurement_id]);
        $panel_result = $panel_stmt->fetch(PDO::FETCH_ASSOC);

        if ($panel_result && !empty($panel_result['panel_data'])) {
            $original_panel_data = json_decode($panel_result['panel_data'], true);

            if ($original_panel_data) {
                // Create production settings for make_panel_data generation
                $production_settings = [
                    'production_height' => $production_height ?: 2400, // Default if not set
                    'production_height1_11' => $production_height1_11, // 0이어도 전달 (명시적으로 설정된 값)
                    'panel_corners_excluded' => $panel_corners_excluded,
                    'transom_excluded' => $transom_excluded,
                    'molding_included' => $molding_included
                ];
                
                error_log("DEBUG: Production settings for make_panel_data generation: " . json_encode($production_settings));

                // Generate make_panel_data
                $make_panel_data = generateMakePanelData($original_panel_data, $production_settings);
                $make_panel_data_json = json_encode($make_panel_data, JSON_UNESCAPED_UNICODE);

                // Add to update fields
                $update_fields[] = "make_panel_data = ?";
                $update_values[] = $make_panel_data_json;

                error_log("Generated make_panel_data for measurement_id: $measurement_id");
            }
        }
    } catch (Exception $e) {
        error_log("Failed to generate make_panel_data: " . $e->getMessage());
        // Continue without updating make_panel_data
    }
}

// Always update the timestamp
$update_fields[] = "updated_at = CURRENT_TIMESTAMP";

// Add measurement_id for WHERE clause
$update_values[] = $measurement_id;

if (empty($update_fields)) {
    ob_clean();
    header("Content-Type: application/json");
    exit(json_encode(['success' => false, 'message' => '업데이트할 필드가 없습니다.']));
}

try {
    // Build and execute update query
    $update_query = "UPDATE panel_measurements SET " . implode(", ", $update_fields) . " WHERE id = ?";

    error_log("Update query: " . $update_query);
    error_log("Update values: " . json_encode($update_values));

    $stmt = $pdo->prepare($update_query);
    $result = $stmt->execute($update_values);

    if ($result) {
        $affected_rows = $stmt->rowCount();

        // 제작 패널 데이터 생성 및 저장
        $production_settings = [
            'production_height' => $production_height,
            'production_height1_11' => $production_height1_11,
            'panel_corners_excluded' => $panel_corners_excluded,
            'transom_excluded' => $transom_excluded,
            'molding_included' => $molding_included
        ];
        
        error_log("DEBUG: Final production settings for updateMakePanelDataInDB: " . json_encode($production_settings));

        $make_data_result = updateMakePanelDataInDB($pdo, $measurement_id, $production_settings);
        if (!$make_data_result) {
            error_log("Warning: Failed to generate make_panel_data for measurement_id: {$measurement_id}");
        }

        // Get updated data for response
        $select_query = "SELECT id, site_name, project_type, panel_corners_excluded, transom_excluded";
        if ($has_molding_included) $select_query .= ", molding_included";
        if ($has_elevator_count) $select_query .= ", elevator_count";
        if ($has_production_height) $select_query .= ", production_height";
        if ($has_production_height1_11) $select_query .= ", production_height1_11";
        if ($has_make_panel_data) $select_query .= ", make_panel_data";
        $select_query .= ", updated_at FROM panel_measurements WHERE id = ?";

        $stmt = $pdo->prepare($select_query);
        $stmt->execute([$measurement_id]);
        $updated_data = $stmt->fetch(PDO::FETCH_ASSOC);

        ob_clean();
        header("Content-Type: application/json");
        echo json_encode([
            'success' => true,
            'message' => '제작 조건이 성공적으로 업데이트되고 제작 패널 데이터가 생성되었습니다.',
            'affected_rows' => $affected_rows,
            'make_data_generated' => $make_data_result,
            'data' => $updated_data
        ]);

        error_log("UPDATE SUCCESS: Affected rows: " . $affected_rows);

    } else {
        throw new Exception("업데이트 쿼리 실행 실패");
    }

} catch (PDOException $e) {
    error_log("Database update error: " . $e->getMessage());
    ob_clean();
    header("Content-Type: application/json");
    exit(json_encode([
        'success' => false,
        'message' => '데이터베이스 업데이트 중 오류가 발생했습니다: ' . $e->getMessage()
    ]));
} catch (Exception $e) {
    error_log("General update error: " . $e->getMessage());
    ob_clean();
    header("Content-Type: application/json");
    exit(json_encode([
        'success' => false,
        'message' => '업데이트 중 오류가 발생했습니다: ' . $e->getMessage()
    ]));
}
?>