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
header('Content-Type: application/json');

try {
    $site_name = $_GET['site_name'] ?? '';
    $measurement_date = $_GET['measurement_date'] ?? '';
    
    if (empty($site_name) || empty($measurement_date)) {
        throw new Exception('현장명과 측정일자가 필요합니다.');
    }
    
    // Get measurement details from unified panel_measurements table
    $stmt = $pdo->prepare("
        SELECT
            id,
            site_name,
            site_address,
            client_name,
            client_phone,
            project_manager,
            elevator_count,
            measurer_name,
            measurer_id,
            measurement_date,
            car_inside_width,
            car_inside_depth,
            car_inside_height,
            material_type,
            material_thickness,
            panel_data,
            transom_data,
            notes,
            project_type,
            panel_corners_excluded,
            transom_excluded,
            molding_included,
            production_height,
            production_height1_11,
            make_panel_data,
            created_at,
            updated_at
        FROM $DB.panel_measurements
        WHERE site_name = ? AND measurement_date = ?
    ");

    $stmt->execute([$site_name, $measurement_date]);
    $measurement_record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$measurement_record) {
        throw new Exception('측정 데이터를 찾을 수 없습니다.');
    }

    // Parse panel_data and transom_data JSON
    $panel_data = null;
    $transom_data = null;

    if (!empty($measurement_record['panel_data'])) {
        $panel_data = json_decode($measurement_record['panel_data'], true);
    }

    if (!empty($measurement_record['transom_data'])) {
        $transom_data = json_decode($measurement_record['transom_data'], true);
    }

    if (!empty($measurement_record['make_panel_data'])) {
        $make_panel_data = json_decode($measurement_record['make_panel_data'], true);
    } else {
        $make_panel_data = null;
    }

    echo json_encode([
        'success' => true,
        'measurement_record' => $measurement_record,
        'panel_data' => $panel_data,
        'transom_data' => $transom_data,
        'make_panel_data' => $make_panel_data,
        'site_name' => $site_name,
        'measurement_date' => $measurement_date
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>