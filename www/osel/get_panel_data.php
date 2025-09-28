<?php
session_start();
$DB = 'jtechel';
require_once '../lib/mydb.php';
// Initialize database connection
$pdo = db_connect();
header('Content-Type: application/json');

try {
    $site_name = $_GET['site_name'] ?? '';
    $panel_number = intval($_GET['panel_number'] ?? 0);
    
    if (empty($site_name) || $panel_number < 2 || $panel_number > 10) {
        throw new Exception('유효하지 않은 요청입니다.');
    }
    
    // Get existing measurement for this panel
    $stmt = $pdo->prepare("
        SELECT 
            panel_width,
            panel_height, 
            panel_thickness,
            material_type,
            notes
        FROM panel_measurements 
        WHERE site_name = ? AND panel_number = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    
    $stmt->execute([$site_name, $panel_number]);
    $measurements = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($measurements) {
        echo json_encode([
            'success' => true,
            'measurements' => $measurements
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '기존 측정 데이터가 없습니다.'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>