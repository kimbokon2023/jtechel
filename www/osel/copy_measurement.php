<?php
require_once '../lib/mydb.php';
session_start();

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION["level"]) || $_SESSION["level"] > 8) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['source_id'])) {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

$source_id = $input['source_id'];

try {
    $pdo = db_connect();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("USE jtechel");

    // 원본 데이터 조회
    $stmt = $pdo->prepare("
        SELECT site_name, measurement_date, measurer_name, measurer_id,
               car_inside_width, car_inside_depth, car_inside_height,
               material_type, material_thickness, elevator_count,
               panel_data, transom_data, notes, project_type,
               panel_corners_excluded, transom_excluded, ipark_check
        FROM panel_measurements 
        WHERE id = ?
    ");
    $stmt->execute([$source_id]);
    $original_data = $stmt->fetch();

    if (!$original_data) {
        echo json_encode(['success' => false, 'message' => '원본 데이터를 찾을 수 없습니다.']);
        exit;
    }

    // 새로운 데이터 생성 (복사)
    $stmt = $pdo->prepare("
        INSERT INTO panel_measurements (
            site_name, measurement_date, measurer_name, measurer_id,
            car_inside_width, car_inside_depth, car_inside_height,
            material_type, material_thickness, elevator_count,
            panel_data, transom_data, notes, project_type,
            panel_corners_excluded, transom_excluded, ipark_check,
            created_at, updated_at
        ) VALUES (
            ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?,
            NOW(), NOW()
        )
    ");

    // 현장명에 '복사본' 추가
    $copied_site_name = $original_data['site_name'] . ' (복사본)';

    $stmt->execute([
        $copied_site_name,
        $original_data['measurement_date'],
        $original_data['measurer_name'],
        $original_data['measurer_id'],
        $original_data['car_inside_width'],
        $original_data['car_inside_depth'],
        $original_data['car_inside_height'],
        $original_data['material_type'],
        $original_data['material_thickness'],
        $original_data['elevator_count'],
        $original_data['panel_data'],
        $original_data['transom_data'],
        $original_data['notes'],
        $original_data['project_type'],
        $original_data['panel_corners_excluded'],
        $original_data['transom_excluded'],
        $original_data['ipark_check']
    ]);

    $new_id = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => '데이터가 성공적으로 복사되었습니다.',
        'new_id' => $new_id
    ]);

} catch (PDOException $e) {
    error_log("Copy measurement error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '데이터베이스 오류가 발생했습니다.']);
}
?>
