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
    // Get original data
    $stmt = $pdo->prepare("
        SELECT site_name, measurer_name, measurer_id, measurement_date,
               car_inside_width, car_inside_depth, car_inside_height,
               material_type, material_thickness, panel_data, transom_data,
               notes, project_type, panel_corners_excluded, transom_excluded,
               elevator_count, ipark_check, production_height, production_height1_11,
               make_panel_data, molding_data, updated_production_settings_at
        FROM panel_measurements
        WHERE id = ?
    ");
    $stmt->execute([$edit_id]);
    $original_data = $stmt->fetch();

    if (!$original_data) {
        ob_clean(); // Clear any output buffer
        header("Content-Type: application/json");
        exit(json_encode(['success' => false, 'message' => '복사할 데이터를 찾을 수 없습니다.']));
    }

    // Check for existing duplicate data before creating new record
    $duplicate_check = $pdo->prepare("
        SELECT COUNT(*) as count FROM panel_measurements 
        WHERE site_name = ? AND measurement_date = ? AND measurer_name = ?
    ");
    $duplicate_check->execute([
        $original_data['site_name'] . ' (복사본)',
        $original_data['measurement_date'],
        $original_data['measurer_name']
    ]);
    $duplicate_count = $duplicate_check->fetch()['count'];
    
    if ($duplicate_count > 0) {
        ob_clean();
        header("Content-Type: application/json");
        exit(json_encode([
            'success' => false, 
            'message' => '이미 동일한 데이터가 존재합니다. 중복 복사를 방지합니다.'
        ]));
    }

    // Create new record with copied data
    $stmt = $pdo->prepare("
        INSERT INTO panel_measurements (
            site_name, measurer_name, measurer_id, measurement_date,
            car_inside_width, car_inside_depth, car_inside_height,
            material_type, material_thickness, panel_data, transom_data,
            notes, project_type, panel_corners_excluded, transom_excluded,
            elevator_count, ipark_check, production_height, production_height1_11,
            make_panel_data, molding_data, created_at, updated_at, updated_production_settings_at
        ) VALUES (
            :site_name, :measurer_name, :measurer_id, :measurement_date,
            :car_inside_width, :car_inside_depth, :car_inside_height,
            :material_type, :material_thickness, :panel_data, :transom_data,
            :notes, :project_type, :panel_corners_excluded, :transom_excluded,
            :elevator_count, :ipark_check, :production_height, :production_height1_11,
            :make_panel_data, :molding_data, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
        )
    ");

    $result = $stmt->execute([
        ':site_name' => $original_data['site_name'] . ' (복사본)',
        ':measurer_name' => $original_data['measurer_name'],
        ':measurer_id' => $original_data['measurer_id'],
        ':measurement_date' => $original_data['measurement_date'],
        ':car_inside_width' => $original_data['car_inside_width'],
        ':car_inside_depth' => $original_data['car_inside_depth'],
        ':car_inside_height' => $original_data['car_inside_height'],
        ':material_type' => $original_data['material_type'],
        ':material_thickness' => $original_data['material_thickness'],
        ':panel_data' => $original_data['panel_data'],
        ':transom_data' => $original_data['transom_data'],
        ':notes' => $original_data['notes'],
        ':project_type' => $original_data['project_type'],
        ':panel_corners_excluded' => $original_data['panel_corners_excluded'],
        ':transom_excluded' => $original_data['transom_excluded'],
        ':elevator_count' => $original_data['elevator_count'],
        ':ipark_check' => $original_data['ipark_check'],
        ':production_height' => $original_data['production_height'],
        ':production_height1_11' => $original_data['production_height1_11'],
        ':make_panel_data' => $original_data['make_panel_data'],
        ':molding_data' => $original_data['molding_data']
    ]);

    if ($result) {
        $new_id = $pdo->lastInsertId();
        
        // Copy related foreign key data (site_group_members)
        try {
            // Get original site_group_members data
            $group_stmt = $pdo->prepare("
                SELECT group_id, added_by 
                FROM site_group_members 
                WHERE measurement_id = ? AND is_deleted = 0
            ");
            $group_stmt->execute([$edit_id]);
            $group_members = $group_stmt->fetchAll();
            
            // Insert new group memberships for the copied measurement
            if (!empty($group_members)) {
                $insert_group_stmt = $pdo->prepare("
                    INSERT INTO site_group_members (group_id, measurement_id, added_by, created_at, updated_at)
                    VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ");
                
                foreach ($group_members as $member) {
                    $insert_group_stmt->execute([
                        $member['group_id'],
                        $new_id,
                        $member['added_by']
                    ]);
                }
                
                error_log("Copied " . count($group_members) . " site group memberships for measurement ID: $new_id");
            }
            
        } catch (PDOException $e) {
            // Log the error but don't fail the entire copy operation
            error_log("Warning: Failed to copy site group memberships: " . $e->getMessage());
        }
        
        // Success response
        ob_clean(); // Clear any output buffer
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => '데이터가 성공적으로 복사되었습니다.',
            'new_id' => $new_id
        ]);
    } else {
        throw new Exception('데이터 복사에 실패했습니다.');
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
