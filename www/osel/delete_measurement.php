<?php
require_once '../lib/mydb.php';
session_start();
$DB = 'jtechel';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION["level"]) || $_SESSION["level"] > 8) {
    echo json_encode(['success' => false, 'message' => '인증이 필요합니다.']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST 요청만 허용됩니다.']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['id'])) {
    echo json_encode(['success' => false, 'message' => '삭제할 측정 ID가 제공되지 않았습니다.']);
    exit;
}

$measurement_id = intval($input['id']);

if ($measurement_id <= 0) {
    echo json_encode(['success' => false, 'message' => '유효하지 않은 측정 ID입니다.']);
    exit;
}

try {
    $pdo = db_connect();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("USE $DB");

    // 먼저 해당 측정 데이터가 존재하는지 확인하고 권한 체크를 위한 정보도 가져옴
    $stmt = $pdo->prepare("SELECT id, site_name, measurer_id FROM panel_measurements WHERE id = ?");
    $stmt->execute([$measurement_id]);
    $measurement = $stmt->fetch();

    if (!$measurement) {
        echo json_encode(['success' => false, 'message' => '삭제할 측정 데이터를 찾을 수 없습니다.']);
        exit;
    }

    // 권한 확인: 본인이 작성한 데이터만 삭제 가능 (또는 관리자)
    $current_user_id = $_SESSION["userid"] ?? '';
    $current_level = $_SESSION["level"] ?? 10;

    if ($measurement['measurer_id'] !== $current_user_id && $current_level > 5) {
        echo json_encode(['success' => false, 'message' => '본인이 작성한 측정 데이터만 삭제할 수 있습니다.']);
        exit;
    }

    // 측정 데이터 삭제
    $delete_stmt = $pdo->prepare("DELETE FROM panel_measurements WHERE id = ?");
    $result = $delete_stmt->execute([$measurement_id]);

    if ($result && $delete_stmt->rowCount() > 0) {
        // 삭제 로그 기록
        error_log("Panel measurement deleted - ID: {$measurement_id}, Site: {$measurement['site_name']}, By: {$current_user_id}");

        echo json_encode([
            'success' => true,
            'message' => '측정 데이터가 성공적으로 삭제되었습니다.',
            'deleted_id' => $measurement_id,
            'site_name' => $measurement['site_name']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => '데이터 삭제에 실패했습니다.']);
    }

} catch (PDOException $e) {
    error_log("Database error in delete_measurement.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '데이터베이스 오류가 발생했습니다.']);
} catch (Exception $e) {
    error_log("General error in delete_measurement.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
