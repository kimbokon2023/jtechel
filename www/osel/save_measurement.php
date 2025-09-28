<?php
require_once '../lib/mydb.php';
session_start();
$DB = 'jtechel';

// Check authentication
if (!isset($_SESSION["level"]) || $_SESSION["level"] > 8) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '인증이 필요합니다.']);
    exit;
}

// Initialize database connection
$pdo = db_connect();

// Check if POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '허용되지 않는 메서드입니다.']);
    exit;
}

try {
    // Get form data
    $site_name = trim($_POST['site_name'] ?? '');
    $site_address = trim($_POST['site_address'] ?? '');
    $client_name = trim($_POST['client_name'] ?? '');
    $client_phone = trim($_POST['client_phone'] ?? '');
    $project_manager = trim($_POST['project_manager'] ?? '');
    $elevator_count = intval($_POST['elevator_count'] ?? 1);
    $measurement_date = $_POST['measurement_date'] ?? '';
    $measurer_name = trim($_POST['measurer_name'] ?? $_SESSION["name"]);
    $measurer_id = $_POST['measurer_id'] ?? $_SESSION["userid"];
    $car_inside_width = !empty($_POST['car_inside_width']) ? intval($_POST['car_inside_width']) : 0;
    $car_inside_depth = !empty($_POST['car_inside_depth']) ? intval($_POST['car_inside_depth']) : 0;
    $car_inside_height = !empty($_POST['car_inside_height']) ? intval($_POST['car_inside_height']) : 0;
    $material_type = trim($_POST['material_type'] ?? '');
    $material_thickness = !empty($_POST['material_thickness']) ? floatval($_POST['material_thickness']) : null;
    $panel_data = $_POST['panel_data'] ?? '';
    $transom_data = $_POST['transom_data'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    $project_type = trim($_POST['project_type'] ?? '신규');
    $panel_corners_excluded = isset($_POST['panel_corners_excluded']) ? intval($_POST['panel_corners_excluded']) : 1;
    $transom_excluded = isset($_POST['transom_excluded']) ? intval($_POST['transom_excluded']) : 0;
    $molding_included = isset($_POST['molding_included']) ? intval($_POST['molding_included']) : 0;
    $ipark_check = isset($_POST['ipark_check']) ? intval($_POST['ipark_check']) : 0;

    // Validation
    if (empty($site_name)) {
        throw new Exception('현장명을 입력해주세요.');
    }

    if (empty($measurement_date)) {
        throw new Exception('측정일자를 선택해주세요.');
    }

    if (empty($measurer_name)) {
        throw new Exception('측정자명을 입력해주세요.');
    }

    // Validate car inside dimensions
    if ($car_inside_width <= 0 || $car_inside_depth <= 0 || $car_inside_height <= 0) {
        throw new Exception('카 내부 치수를 모두 입력해주세요.');
    }

    // Validate measurement ranges
    if ($car_inside_width < 800 || $car_inside_width > 3000) {
        throw new Exception('카 내부 가로는 800-3000mm 범위여야 합니다.');
    }

    if ($car_inside_depth < 1000 || $car_inside_depth > 3000) {
        throw new Exception('카 내부 깊이는 1000-3000mm 범위여야 합니다.');
    }

    if ($car_inside_height < 2000 || $car_inside_height > 3500) {
        throw new Exception('카 내부 높이는 2000-3500mm 범위여야 합니다.');
    }

    if ($material_thickness !== null && ($material_thickness < 0.8 || $material_thickness > 3.0)) {
        throw new Exception('의장재질 두께는 0.8-3.0mm 범위여야 합니다.');
    }

    // Validate JSON data if provided
    if (!empty($panel_data) && json_decode($panel_data) === null) {
        throw new Exception('패널 데이터 형식이 올바르지 않습니다.');
    }

    if (!empty($transom_data) && json_decode($transom_data) === null) {
        throw new Exception('트랜섬 데이터 형식이 올바르지 않습니다.');
    }
    
    // Begin transaction
    $pdo->beginTransaction();

    // Check if measurement already exists for this site/date
    $check_stmt = $pdo->prepare("
        SELECT id FROM $DB.panel_measurements
        WHERE site_name = ? AND measurement_date = ?
    ");
    $check_stmt->execute([$site_name, $measurement_date]);
    $existing_id = $check_stmt->fetchColumn();

    if ($existing_id) {
        // Update existing measurement record
        $update_stmt = $pdo->prepare("
            UPDATE $DB.panel_measurements SET
                site_address = ?, client_name = ?, client_phone = ?, project_manager = ?,
                elevator_count = ?, measurer_name = ?, measurer_id = ?,
                car_inside_width = ?, car_inside_depth = ?, car_inside_height = ?,
                material_type = ?, material_thickness = ?, panel_data = ?, transom_data = ?,
                notes = ?, project_type = ?, panel_corners_excluded = ?, transom_excluded = ?,
                molding_included = ?, ipark_check = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");

        $update_stmt->execute([
            $site_address, $client_name, $client_phone, $project_manager, $elevator_count,
            $measurer_name, $measurer_id, $car_inside_width, $car_inside_depth, $car_inside_height,
            $material_type, $material_thickness, $panel_data, $transom_data, $notes,
            $project_type, $panel_corners_excluded, $transom_excluded, $molding_included, $ipark_check,
            $existing_id
        ]);

        $message = '측정 데이터가 업데이트되었습니다.';
    } else {
        // Insert new measurement record
        $insert_stmt = $pdo->prepare("
            INSERT INTO $DB.panel_measurements
            (site_name, site_address, client_name, client_phone, project_manager, elevator_count,
             created_by, measurer_name, measurer_id, measurement_date, car_inside_width,
             car_inside_depth, car_inside_height, material_type, material_thickness,
             panel_data, transom_data, notes, project_type, panel_corners_excluded,
             transom_excluded, molding_included, ipark_check)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $insert_stmt->execute([
            $site_name, $site_address, $client_name, $client_phone, $project_manager, $elevator_count,
            $_SESSION["userid"], $measurer_name, $measurer_id, $measurement_date,
            $car_inside_width, $car_inside_depth, $car_inside_height, $material_type, $material_thickness,
            $panel_data, $transom_data, $notes, $project_type, $panel_corners_excluded,
            $transom_excluded, $molding_included, $ipark_check
        ]);

        $message = '새로운 측정 데이터가 저장되었습니다.';
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'site_name' => $site_name,
        'measurement_date' => $measurement_date,
        'record_id' => $existing_id ?: $pdo->lastInsertId()
    ]);
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollback();
    }
    
    error_log('Measurement save error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
 
/**
 * Validate measurement data structure
 */
function validateMeasurementData($site_name, $measurement_date, $car_dimensions, $panel_data = null, $transom_data = null) {
    $result = ['is_valid' => true, 'message' => ''];

    // Basic validation
    if (empty($site_name)) {
        $result['is_valid'] = false;
        $result['message'] = '현장명은 필수입니다.';
        return $result;
    }

    if (empty($measurement_date)) {
        $result['is_valid'] = false;
        $result['message'] = '측정일자는 필수입니다.';
        return $result;
    }

    // Car dimensions validation
    if (!is_array($car_dimensions) || count($car_dimensions) !== 3) {
        $result['is_valid'] = false;
        $result['message'] = '카 내부 치수 데이터가 올바르지 않습니다.';
        return $result;
    }

    // Panel data validation (if provided)
    if ($panel_data !== null) {
        $panels = json_decode($panel_data, true);
        if ($panels === null) {
            $result['is_valid'] = false;
            $result['message'] = '패널 데이터 형식이 올바르지 않습니다.';
            return $result;
        }

        // Validate each panel
        foreach ($panels as $panel_num => $panel_info) {
            if ($panel_num < 1 || $panel_num > 11) {
                $result['is_valid'] = false;
                $result['message'] = "패널 번호 {$panel_num}는 유효하지 않습니다. (1-11 범위)";
                return $result;
            }
        }
    }

    // Transom data validation (if provided)
    if ($transom_data !== null) {
        $transom = json_decode($transom_data, true);
        if ($transom === null) {
            $result['is_valid'] = false;
            $result['message'] = '트랜섬 데이터 형식이 올바르지 않습니다.';
            return $result;
        }
    }

    return $result;
}
?>