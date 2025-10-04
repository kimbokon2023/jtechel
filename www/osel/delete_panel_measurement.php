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
    $pdo->exec("USE $DB");
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    ob_clean(); // Clear any output buffer
    header("Content-Type: application/json");
    exit(json_encode(['success' => false, 'message' => '데이터베이스 연결 실패']));
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$edit_id = intval($input['edit_id'] ?? 0);

if ($edit_id <= 0) {
    ob_clean(); // Clear any output buffer
    header("Content-Type: application/json");
    exit(json_encode(['success' => false, 'message' => '유효하지 않은 데이터 ID입니다.']));
}

try {
    // Check if record exists
    $stmt = $pdo->prepare("SELECT id, site_name FROM panel_measurements WHERE id = ?");
    $stmt->execute([$edit_id]);
    $record = $stmt->fetch();

    if (!$record) {
        ob_clean(); // Clear any output buffer
        header("Content-Type: application/json");
        exit(json_encode(['success' => false, 'message' => '삭제할 데이터를 찾을 수 없습니다.']));
    }

    // Delete the record
    $stmt = $pdo->prepare("DELETE FROM panel_measurements WHERE id = ?");
    $result = $stmt->execute([$edit_id]);

    if ($result && $stmt->rowCount() > 0) {
        // Success response
        ob_clean(); // Clear any output buffer
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => "데이터가 성공적으로 삭제되었습니다. (현장명: {$record['site_name']})"
        ]);
    } else {
        throw new Exception('데이터 삭제에 실패했습니다.');
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
