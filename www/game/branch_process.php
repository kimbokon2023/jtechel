<?php
// 환경별 기본 URL 설정
require_once '../config/environment.php';

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once("../lib/mydb.php");
$pdo = db_connect();

// 사용자 권한 체크
if(!isset($_SESSION["level"]) || $_SESSION["level"] > 5) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}

$mode = isset($_POST['mode']) ? $_POST['mode'] : (isset($_GET['mode']) ? $_GET['mode'] : '');
$response = ['success' => false, 'message' => ''];

try {
    switch($mode) {
        case 'list':
            // 지점 목록 조회
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $offset = ($page - 1) * $limit;
            
            $status = isset($_GET['status']) ? $_GET['status'] : '';
            $type = isset($_GET['type']) ? $_GET['type'] : '';
            $keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';
            
            $where = "WHERE 1=1";
            $params = [];
             
            if($status) {
                $where .= " AND status = :status";
                $params['status'] = $status;
            }
            
            if($type) {
                $where .= " AND branch_type = :type";
                $params['type'] = $type;
            }
            
            if($keyword) {
                $where .= " AND (branch_code LIKE :keyword OR branch_name LIKE :keyword2 OR manager_name LIKE :keyword3)";
                $params['keyword'] = "%$keyword%";
                $params['keyword2'] = "%$keyword%";
                $params['keyword3'] = "%$keyword%";
            }
            
            // 전체 개수 조회
            $countSql = "SELECT COUNT(*) as total FROM jtechel.branches $where";
            $countStmt = $pdo->prepare($countSql);
            foreach($params as $key => $value) {
                $countStmt->bindValue(":$key", $value);
            }
            $countStmt->execute();
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // 데이터 조회
            $sql = "SELECT * FROM jtechel.branches $where ORDER BY sort_order ASC, id DESC LIMIT :offset, :limit";
            $stmt = $pdo->prepare($sql);
            foreach($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response = [
                'success' => true,
                'data' => $branches,
                'total' => $total,
                'page' => $page,
                'totalPages' => ceil($total / $limit)
            ];
            break;
            
        case 'get':
            // 특정 지점 정보 조회
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            
            if($id <= 0) {
                throw new Exception('유효하지 않은 ID입니다.');
            }
            
            $sql = "SELECT * FROM jtechel.branches WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $branch = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(!$branch) {
                throw new Exception('지점 정보를 찾을 수 없습니다.');
            }
            
            $response = [
                'success' => true,
                'data' => $branch
            ];
            break;
            
        case 'insert':
            // 신규 지점 등록
            $branch_code = $_POST['branch_code'] ?? '';
            $branch_name = $_POST['branch_name'] ?? '';
            
            if(empty($branch_code) || empty($branch_name)) {
                throw new Exception('필수 입력 항목을 확인해주세요.');
            }
            
            // 중복 체크
            $checkSql = "SELECT COUNT(*) as cnt FROM jtechel.branches WHERE branch_code = :branch_code";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->bindValue(':branch_code', $branch_code);
            $checkStmt->execute();
            $count = $checkStmt->fetch(PDO::FETCH_ASSOC)['cnt'];
            
            if($count > 0) {
                throw new Exception('이미 등록된 지점코드입니다.');
            }
            
            $sql = "INSERT INTO jtechel.branches (
                        branch_code, branch_name, branch_type, manager_name,
                        phone, fax, email, address, detail_address, zip_code,
                        status, sort_order, note, created_by
                    ) VALUES (
                        :branch_code, :branch_name, :branch_type, :manager_name,
                        :phone, :fax, :email, :address, :detail_address, :zip_code,
                        :status, :sort_order, :note, :created_by
                    )";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':branch_code', $branch_code);
            $stmt->bindValue(':branch_name', $branch_name);
            $stmt->bindValue(':branch_type', $_POST['branch_type'] ?? null);
            $stmt->bindValue(':manager_name', $_POST['manager_name'] ?? null);
            $stmt->bindValue(':phone', $_POST['phone'] ?? null);
            $stmt->bindValue(':fax', $_POST['fax'] ?? null);
            $stmt->bindValue(':email', $_POST['email'] ?? null);
            $stmt->bindValue(':address', $_POST['address'] ?? null);
            $stmt->bindValue(':detail_address', $_POST['detail_address'] ?? null);
            $stmt->bindValue(':zip_code', $_POST['zip_code'] ?? null);
            $stmt->bindValue(':status', $_POST['status'] ?? 'active');
            $stmt->bindValue(':sort_order', $_POST['sort_order'] ?? 0);
            $stmt->bindValue(':note', $_POST['note'] ?? null);
            $stmt->bindValue(':created_by', $_SESSION['name'] ?? null);
            
            if($stmt->execute()) {
                $response = [
                    'success' => true,
                    'message' => '지점이 성공적으로 등록되었습니다.',
                    'id' => $pdo->lastInsertId()
                ];
            } else {
                throw new Exception('지점 등록에 실패했습니다.');
            }
            break;
            
        case 'update':
            // 지점 정보 수정
            $id = $_POST['id'] ?? 0;
            $branch_code = $_POST['branch_code'] ?? '';
            $branch_name = $_POST['branch_name'] ?? '';
            
            if($id <= 0 || empty($branch_code) || empty($branch_name)) {
                throw new Exception('필수 입력 항목을 확인해주세요.');
            }
            
            // 중복 체크 (자기 자신 제외)
            $checkSql = "SELECT COUNT(*) as cnt FROM jtechel.branches WHERE branch_code = :branch_code AND id != :id";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->bindValue(':branch_code', $branch_code);
            $checkStmt->bindValue(':id', $id, PDO::PARAM_INT);
            $checkStmt->execute();
            $count = $checkStmt->fetch(PDO::FETCH_ASSOC)['cnt'];
            
            if($count > 0) {
                throw new Exception('이미 등록된 지점코드입니다.');
            }
            
            $sql = "UPDATE jtechel.branches SET 
                        branch_code = :branch_code,
                        branch_name = :branch_name,
                        branch_type = :branch_type,
                        manager_name = :manager_name,
                        phone = :phone,
                        fax = :fax,
                        email = :email,
                        address = :address,
                        detail_address = :detail_address,
                        zip_code = :zip_code,
                        status = :status,
                        sort_order = :sort_order,
                        note = :note,
                        updated_by = :updated_by
                    WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':branch_code', $branch_code);
            $stmt->bindValue(':branch_name', $branch_name);
            $stmt->bindValue(':branch_type', $_POST['branch_type'] ?? null);
            $stmt->bindValue(':manager_name', $_POST['manager_name'] ?? null);
            $stmt->bindValue(':phone', $_POST['phone'] ?? null);
            $stmt->bindValue(':fax', $_POST['fax'] ?? null);
            $stmt->bindValue(':email', $_POST['email'] ?? null);
            $stmt->bindValue(':address', $_POST['address'] ?? null);
            $stmt->bindValue(':detail_address', $_POST['detail_address'] ?? null);
            $stmt->bindValue(':zip_code', $_POST['zip_code'] ?? null);
            $stmt->bindValue(':status', $_POST['status'] ?? 'active');
            $stmt->bindValue(':sort_order', $_POST['sort_order'] ?? 0);
            $stmt->bindValue(':note', $_POST['note'] ?? null);
            $stmt->bindValue(':updated_by', $_SESSION['name'] ?? null);
            
            if($stmt->execute()) {
                $response = [
                    'success' => true,
                    'message' => '지점 정보가 수정되었습니다.'
                ];
            } else {
                throw new Exception('지점 정보 수정에 실패했습니다.');
            }
            break;
            
        case 'delete':
            // 지점 삭제
            $id = $_POST['id'] ?? 0;
            
            if($id <= 0) {
                throw new Exception('유효하지 않은 ID입니다.');
            }
            
            $sql = "DELETE FROM jtechel.branches WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            
            if($stmt->execute()) {
                $response = [
                    'success' => true,
                    'message' => '지점이 삭제되었습니다.'
                ];
            } else {
                throw new Exception('지점 삭제에 실패했습니다.');
            }
            break;
            
        case 'select_options':
            // SELECT 옵션용 지점 목록 조회
            $sql = "SELECT id, branch_code, branch_name, branch_type 
                    FROM jtechel.branches 
                    WHERE status = 'active' 
                    ORDER BY sort_order ASC, branch_name ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response = [
                'success' => true,
                'data' => $branches
            ];
            break;
            
        default:
            throw new Exception('유효하지 않은 요청입니다.');
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

echo json_encode($response);
?>