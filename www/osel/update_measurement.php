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

// Get JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => '잘못된 데이터 형식입니다.']);
    exit;
}

try {
    // Extract data
    $site_name = $data['site_name'] ?? '';
    $measurement_date = $data['measurement_date'] ?? '';
    $panel_number = intval($data['panel_number'] ?? 0);
    $panel_width = !empty($data['panel_width']) ? floatval($data['panel_width']) : null;
    $panel_height = !empty($data['panel_height']) ? floatval($data['panel_height']) : null;
    $panel_thickness = !empty($data['panel_thickness']) ? floatval($data['panel_thickness']) : null;
    $material_type = trim($data['material_type'] ?? '');
    $notes = trim($data['notes'] ?? '');
    
    // Validation
    if (empty($site_name)) {
        throw new Exception('현장명이 없습니다.');
    }
    
    if (empty($measurement_date)) {
        throw new Exception('측정일자가 없습니다.');
    }
    
    if ($panel_number < 1 || $panel_number > 11) {
        throw new Exception('유효하지 않은 판넬 번호입니다.');
    }
    
    // Validate measurement ranges if provided
    if ($panel_width !== null && ($panel_width < 1 || $panel_width > 5000)) {
        throw new Exception('가로 값은 1-5000mm 범위여야 합니다.');
    }
    
    if ($panel_height !== null && ($panel_height < 1 || $panel_height > 5000)) {
        throw new Exception('세로 값은 1-5000mm 범위여야 합니다.');
    }
    
    if ($panel_thickness !== null && ($panel_thickness < 0.1 || $panel_thickness > 100)) {
        throw new Exception('두께 값은 0.1-100mm 범위여야 합니다.');
    }
    
    // Check if measurement exists
    $check_stmt = $pdo->prepare("
        SELECT id FROM $DB.panel_measurements 
        WHERE site_name = ? AND measurement_date = ? AND panel_number = ?
    ");
    $check_stmt->execute([$site_name, $measurement_date, $panel_number]);
    $existing_id = $check_stmt->fetchColumn();
    
    if ($existing_id) {
        // Update existing measurement
        $update_stmt = $pdo->prepare("
            UPDATE $DB.panel_measurements SET
                panel_width = ?, 
                panel_height = ?,
                panel_thickness = ?, 
                material_type = ?, 
                notes = ?, 
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $update_stmt->execute([
            $panel_width, 
            $panel_height, 
            $panel_thickness, 
            $material_type, 
            $notes, 
            $existing_id
        ]);
        
        $message = '측정 데이터가 업데이트되었습니다.';
    } else {
        // Insert new measurement (for panels that didn't exist before)
        $insert_stmt = $pdo->prepare("
            INSERT INTO $DB.panel_measurements 
            (site_name, measurement_date, measurer, measurer_id, panel_number,
             panel_width, panel_height, panel_thickness, material_type, notes, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $insert_stmt->execute([
            $site_name, 
            $measurement_date, 
            $_SESSION["name"], 
            $_SESSION["userid"], 
            $panel_number,
            $panel_width, 
            $panel_height, 
            $panel_thickness, 
            $material_type, 
            $notes
        ]);
        
        $message = '새로운 패널 데이터가 추가되었습니다.';
    }
    
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'panel_number' => $panel_number
    ]);
    
} catch (Exception $e) {
    error_log('Measurement update error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>