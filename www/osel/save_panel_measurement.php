<?php
// Start output buffering to prevent any accidental output
ob_start();

require_once '../lib/mydb.php';
session_start();
$DB = 'jtechel';

// Check authentication
if (!isset($_SESSION["level"]) || $_SESSION["level"] > 8) {
    ob_clean(); // Clear any output buffer
    header("Content-Type: application/json");
    header("HTTP/1.1 403 Forbidden");
    exit(json_encode(['success' => false, 'message' => '인증이 필요합니다.']));
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean(); // Clear any output buffer
    header("Content-Type: application/json");
    header("HTTP/1.1 405 Method Not Allowed");
    exit(json_encode(['success' => false, 'message' => 'POST 요청만 허용됩니다.']));
}

// Initialize database connection
try {
    $pdo = db_connect();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Explicitly select database
    $pdo->exec("USE $DB");
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    ob_clean(); // Clear any output buffer
    header("Content-Type: application/json");
    exit(json_encode(['success' => false, 'message' => '데이터베이스 연결 실패']));
}

// Debug: Log received POST data (reduced verbosity to prevent JSON interference)
error_log("=== SAVE PANEL MEASUREMENT REQUEST ===");
error_log("Project Type: " . ($_POST['project_type'] ?? 'NOT_SET'));
error_log("Panel Layout: " . ($_POST['panel_layout'] ?? 'NOT_SET'));
error_log("Site Name: " . ($_POST['site_name'] ?? 'NOT_SET'));

// Get form data
$site_name = trim($_POST['site_name'] ?? '');
$measurer_name = trim($_POST['measurer'] ?? '');
$measurer_id = $_SESSION["userid"] ?? '';

// Debug measurer info
error_log("Measurer: '" . $measurer_name . "' (from POST: '" . ($_POST['measurer'] ?? 'NOT SET') . "')");
$measurement_date = $_POST['measurement_date'] ?? '';
$car_inside_width = intval($_POST['car_inside_width'] ?? 0);
$car_inside_depth = intval($_POST['car_inside_depth'] ?? 0);
$car_inside_height = intval($_POST['car_inside_height'] ?? 0);
$material_type = trim($_POST['material_type'] ?? '');
$material_thickness = floatval($_POST['material_thickness'] ?? 0);
$panel_data = $_POST['panel_data'] ?? '';
$transom_data = $_POST['transom_data'] ?? '';
$notes = trim($_POST['notes'] ?? '');

// DEBUG: Log transom data
error_log("=== TRANSOM DATA DEBUG ===");
error_log("Raw _POST['transom_data']: " . (isset($_POST['transom_data']) ? "'" . $_POST['transom_data'] . "'" : 'NOT_SET'));
error_log("Parsed transom_data: " . $transom_data);
if (isset($_POST['transom_data'])) {
    error_log("transom_data type: " . gettype($_POST['transom_data']));
    error_log("transom_data length: " . strlen($_POST['transom_data']));
}
$project_type = trim($_POST['project_type'] ?? '신규');
$panel_corners_excluded = isset($_POST['panel_corners_excluded']) ? intval($_POST['panel_corners_excluded']) : 0;
$transom_excluded = isset($_POST['transom_excluded']) ? intval($_POST['transom_excluded']) : 0;
$elevator_count = intval($_POST['elevator_count'] ?? 1);
$ipark_check = intval($_POST['ipark_check'] ?? 0);

// DEBUG: Log project_type POST data
error_log("=== PROJECT_TYPE DEBUG ===");
error_log("Raw _POST['project_type']: " . (isset($_POST['project_type']) ? "'" . $_POST['project_type'] . "'" : 'NOT_SET'));
error_log("Parsed project_type: " . $project_type);
error_log("All POST keys: " . implode(', ', array_keys($_POST)));
if (isset($_POST['project_type'])) {
    error_log("project_type type: " . gettype($_POST['project_type']));
}

// DEBUG: Log checkbox POST data
error_log("=== CHECKBOX DEBUG ===");
error_log("Raw _POST['panel_corners_excluded']: " . (isset($_POST['panel_corners_excluded']) ? "'" . $_POST['panel_corners_excluded'] . "'" : 'NOT_SET'));
error_log("Parsed panel_corners_excluded: " . $panel_corners_excluded);
error_log("Raw _POST['transom_excluded']: " . (isset($_POST['transom_excluded']) ? "'" . $_POST['transom_excluded'] . "'" : 'NOT_SET'));
error_log("Parsed transom_excluded: " . $transom_excluded);

// Page leave protection - redirect handling
$redirect_after_save = trim($_POST['redirect_after_save'] ?? '');
error_log("Redirect after save: '" . $redirect_after_save . "'");

// Check database columns
try {
    $check_columns = $pdo->query("DESCRIBE panel_measurements");
    $columns = $check_columns->fetchAll(PDO::FETCH_COLUMN);
    $has_project_type = in_array('project_type', $columns);
    $has_panel_corners_excluded = in_array('panel_corners_excluded', $columns);
    $has_transom_excluded = in_array('transom_excluded', $columns);
    $has_elevator_count = in_array('elevator_count', $columns);
    $has_ipark_check = in_array('ipark_check', $columns);

    error_log("Columns - project_type: " . ($has_project_type ? 'YES' : 'NO') .
             ", panel_corners_excluded: " . ($has_panel_corners_excluded ? 'YES' : 'NO') .
             ", transom_excluded: " . ($has_transom_excluded ? 'YES' : 'NO') .
             ", elevator_count: " . ($has_elevator_count ? 'YES' : 'NO') .
             ", ipark_check: " . ($has_ipark_check ? 'YES' : 'NO'));
} catch (Exception $e) {
    error_log("Column check error: " . $e->getMessage());
    $has_project_type = false;
    $has_panel_corners_excluded = false;
    $has_transom_excluded = false;
    $has_elevator_count = false;
    $has_ipark_check = false;
}

// Check if this is an edit operation
$edit_id = intval($_POST['edit_id'] ?? 0);
$is_edit = $edit_id > 0;

// Debug: Log edit operation status
error_log("=== EDIT MODE DEBUG ===");
error_log("POST edit_id: " . ($_POST['edit_id'] ?? 'NOT_SET'));
error_log("Parsed edit_id: " . $edit_id);
error_log("Is edit mode: " . ($is_edit ? 'YES' : 'NO'));


// Validate required fields
if (empty($site_name)) {
    ob_clean(); // Clear any output buffer
    header("Content-Type: application/json");
    exit(json_encode(['success' => false, 'message' => '현장명을 입력해주세요.']));
}
if (empty($measurer_name)) {
    ob_clean(); // Clear any output buffer
    header("Content-Type: application/json");
    exit(json_encode(['success' => false, 'message' => '측정자명을 입력해주세요.']));
}
if (empty($measurement_date)) {
    ob_clean(); // Clear any output buffer
    header("Content-Type: application/json");
    exit(json_encode(['success' => false, 'message' => '측정일자를 선택해주세요.']));
}
if ($car_inside_width <= 0 || $car_inside_depth <= 0 || $car_inside_height <= 0) {
    ob_clean(); // Clear any output buffer
    header("Content-Type: application/json");
    exit(json_encode(['success' => false, 'message' => '카 내부 치수를 올바르게 입력해주세요.']));
}

// Validate JSON data
if (!empty($panel_data)) {
    $decoded_panel = json_decode($panel_data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        ob_clean(); // Clear any output buffer
        header("Content-Type: application/json");
        exit(json_encode(['success' => false, 'message' => '패널 데이터 형식이 올바르지 않습니다.']));
    }
}

if (!empty($transom_data)) {
    $decoded_transom = json_decode($transom_data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Transom JSON decode error: " . json_last_error_msg());
        error_log("Transom data that failed to decode: " . $transom_data);
        ob_clean(); // Clear any output buffer
        header("Content-Type: application/json");
        exit(json_encode(['success' => false, 'message' => 'Transom 데이터 형식이 올바르지 않습니다.']));
    } else {
        error_log("Transom JSON decode success: " . print_r($decoded_transom, true));
    }
} else {
    error_log("Transom data is empty");
}

// 체크박스 상태에 따른 패널 데이터 필터링
// 체크가 더 우선시되므로 체크되어 있으면 해당 패널 데이터 제거
if (!empty($panel_data)) {
    $decoded_panel = json_decode($panel_data, true);
    if (is_array($decoded_panel)) {
        // 1,11번 패널 제외 체크박스가 체크되어 있으면 1번, 11번 패널 데이터 제거
        if ($panel_corners_excluded == 1) {
            unset($decoded_panel['1']);
            unset($decoded_panel['11']);
            error_log("패널 저장: 1,11번 패널 제외 체크박스로 인해 1,11번 패널 데이터 제거됨");
        }

        // 필터링된 패널 데이터를 다시 JSON으로 변환
        $panel_data = json_encode($decoded_panel);
        error_log("패널 저장: 필터링된 panel_data = " . $panel_data);
    }
}

// 트랜섬 제외 체크박스가 체크되어 있으면 트랜섬 데이터 제거
if ($transom_excluded == 1) {
    $transom_data = '{}'; // 빈 JSON 객체로 설정
    error_log("패널 저장: 트랜섬 제외 체크박스로 인해 트랜섬 데이터 제거됨");
}

try {
    // Check if table exists, create if not
    $table_check = $pdo->query("SHOW TABLES LIKE 'panel_measurements'");
    if ($table_check->rowCount() == 0) {
        $create_table_sql = "
        CREATE TABLE panel_measurements (
            id INT AUTO_INCREMENT PRIMARY KEY COMMENT '측정 ID',
            site_name VARCHAR(255) NOT NULL COMMENT '현장명',
            measurer_name VARCHAR(100) NOT NULL COMMENT '측정자명',
            measurer_id VARCHAR(50) NOT NULL COMMENT '측정자 ID',
            measurement_date DATE NOT NULL COMMENT '측정일자',
            car_inside_width INT NOT NULL COMMENT '카 내부 가로 (mm)',
            car_inside_depth INT NOT NULL COMMENT '카 내부 깊이 (mm)',
            car_inside_height INT NOT NULL COMMENT '카 내부 높이 (mm)',
            material_type VARCHAR(50) COMMENT '의장재질 타입',
            material_thickness DECIMAL(3,1) COMMENT '의장재질 두께 (mm)',
            panel_data TEXT COMMENT '패널 1~11번 정보 (JSON 형태)',
            transom_data TEXT COMMENT 'Transom 12번 정보 (JSON 형태)',
            notes TEXT COMMENT '특이사항',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '최종 수정일시',
            INDEX idx_site_name (site_name),
            INDEX idx_measurer_id (measurer_id),
            INDEX idx_measurement_date (measurement_date),
            INDEX idx_material_type (material_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='패널 측정 정보'";

        $pdo->exec($create_table_sql);
    }

    // 컬럼 존재 여부에 따라 SQL 동적 생성
    $project_columns = '';
    $project_values = '';
    $project_params_insert = '';
    $project_params_values = '';

    if ($has_project_type) {
        $project_columns .= ', project_type = :project_type';
        $project_values .= ', project_type';
        $project_params_insert .= ', :project_type';
    }

    if ($has_panel_corners_excluded) {
        $project_columns .= ', panel_corners_excluded = :panel_corners_excluded';
        $project_values .= ', panel_corners_excluded';
        $project_params_insert .= ', :panel_corners_excluded';
    }

    if ($has_transom_excluded) {
        $project_columns .= ', transom_excluded = :transom_excluded';
        $project_values .= ', transom_excluded';
        $project_params_insert .= ', :transom_excluded';
    }

    if ($has_elevator_count) {
        $project_columns .= ', elevator_count = :elevator_count';
        $project_values .= ', elevator_count';
        $project_params_insert .= ', :elevator_count';
    }

    if ($has_ipark_check) {
        $project_columns .= ', ipark_check = :ipark_check';
        $project_values .= ', ipark_check';
        $project_params_insert .= ', :ipark_check';
    }


    // Prepare SQL based on edit mode
    if ($is_edit) {
        // Update existing record
        $sql = "UPDATE panel_measurements SET
            site_name = :site_name,
            measurer_name = :measurer_name,
            measurer_id = :measurer_id,
            measurement_date = :measurement_date,
            car_inside_width = :car_inside_width,
            car_inside_depth = :car_inside_depth,
            car_inside_height = :car_inside_height,
            material_type = :material_type,
            material_thickness = :material_thickness,
            panel_data = :panel_data,
            transom_data = :transom_data,
            notes = :notes" . $project_columns . ",
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :edit_id";
    } else {
        // Insert new record
        $sql = "INSERT INTO panel_measurements (
            site_name, measurer_name, measurer_id, measurement_date,
            car_inside_width, car_inside_depth, car_inside_height,
            material_type, material_thickness,
            panel_data, transom_data, notes" . $project_values . "
        ) VALUES (
            :site_name, :measurer_name, :measurer_id, :measurement_date,
            :car_inside_width, :car_inside_depth, :car_inside_height,
            :material_type, :material_thickness,
            :panel_data, :transom_data, :notes" . $project_params_insert . "
        )";
    }

    $stmt = $pdo->prepare($sql);

    // Prepare parameters
    $params = [
        ':site_name' => $site_name,
        ':measurer_name' => $measurer_name,
        ':measurer_id' => $measurer_id,
        ':measurement_date' => $measurement_date,
        ':car_inside_width' => $car_inside_width,
        ':car_inside_depth' => $car_inside_depth,
        ':car_inside_height' => $car_inside_height,
        ':material_type' => $material_type,
        ':material_thickness' => $material_thickness,
        ':panel_data' => $panel_data,
        ':transom_data' => $transom_data,
        ':notes' => $notes
    ];

    // 컬럼이 존재하는 경우에만 파라미터 추가
    if ($has_project_type) {
        $params[':project_type'] = $project_type;
    }
    if ($has_panel_corners_excluded) {
        $params[':panel_corners_excluded'] = $panel_corners_excluded;
    }
    if ($has_transom_excluded) {
        $params[':transom_excluded'] = $transom_excluded;
    }
    if ($has_elevator_count) {
        $params[':elevator_count'] = $elevator_count;
    }
    if ($has_ipark_check) {
        $params[':ipark_check'] = $ipark_check;
    }

    // Add edit_id parameter if updating
    if ($is_edit) {
        $params[':edit_id'] = $edit_id;
    }

    // Debug: Log SQL execution
    error_log("SQL " . ($is_edit ? "UPDATE" : "INSERT") . " - project_type: '" . ($params[':project_type'] ?? 'N/A') .
             "', panel_corners_excluded: '" . ($params[':panel_corners_excluded'] ?? 'N/A') .
             "', transom_excluded: '" . ($params[':transom_excluded'] ?? 'N/A') .
             "', ipark_check: '" . ($params[':ipark_check'] ?? 'N/A') . "'");

    // 에러 발생 시 JSON으로 응답하도록 수정
    try {
        $result = $stmt->execute($params);
    } catch (Exception $e) {
        error_log("SQL execution error: " . $e->getMessage());
        ob_clean(); // Clear any output buffer
        header("Content-Type: application/json");
        exit(json_encode(['success' => false, 'message' => 'SQL 실행 오류: ' . $e->getMessage()]));
    }

    if ($result) {
        if ($is_edit) {
            // For updates, use the existing ID
            $measurement_id = $edit_id;
            $message = '패널 측정 데이터가 성공적으로 수정되었습니다.';
        } else {
            // For inserts, get the new ID
            $measurement_id = $pdo->lastInsertId();
            $message = '패널 측정 데이터가 성공적으로 저장되었습니다.';
        }

        // Determine redirect URL
        $redirect_url = '';
        if (!empty($redirect_after_save) && $redirect_after_save !== 'stay') {
            $redirect_url = $redirect_after_save;
        }

        // Success response
        ob_clean(); // Clear any output buffer
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => $message,
            'measurement_id' => $measurement_id,
            'redirect_url' => $redirect_url,
            'should_redirect' => !empty($redirect_url)
        ]);
    } else {
        throw new Exception('데이터 저장에 실패했습니다.');
    }

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    ob_clean(); // Clear any output buffer
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => '데이터베이스 오류가 발생했습니다: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    ob_clean(); // Clear any output buffer
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// End output buffering and ensure clean exit
ob_end_flush();
?>