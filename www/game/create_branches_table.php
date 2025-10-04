<?php
// 환경별 기본 URL 설정
require_once '../config/environment.php';

session_start();
header('Content-Type: text/html; charset=utf-8');

// 권한 체크 - 관리자만 실행 가능
if(!isset($_SESSION["level"]) || $_SESSION["level"] != '1') {
    die('관리자만 실행 가능합니다.');
}

require_once("../lib/mydb.php");
$pdo = db_connect();

try {
    // branches 테이블 생성
    $sql = "
    CREATE TABLE IF NOT EXISTS `branches` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `branch_code` varchar(20) NOT NULL COMMENT '지점코드',
        `branch_name` varchar(100) NOT NULL COMMENT '지점명',
        `branch_type` varchar(20) DEFAULT NULL COMMENT '지점유형 (본사, 지점, 영업소, 대리점)',
        `manager_name` varchar(50) DEFAULT NULL COMMENT '지점장명',
        `phone` varchar(20) DEFAULT NULL COMMENT '전화번호',
        `fax` varchar(20) DEFAULT NULL COMMENT '팩스번호',
        `email` varchar(100) DEFAULT NULL COMMENT '이메일',
        `address` varchar(200) DEFAULT NULL COMMENT '주소',
        `detail_address` varchar(100) DEFAULT NULL COMMENT '상세주소',
        `zip_code` varchar(10) DEFAULT NULL COMMENT '우편번호',
        `status` varchar(10) DEFAULT 'active' COMMENT '상태 (active, inactive)',
        `sort_order` int(11) DEFAULT 0 COMMENT '정렬순서',
        `note` text DEFAULT NULL COMMENT '비고',
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
        `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
        `created_by` varchar(50) DEFAULT NULL COMMENT '생성자',
        `updated_by` varchar(50) DEFAULT NULL COMMENT '수정자',
        PRIMARY KEY (`id`),
        UNIQUE KEY `branch_code` (`branch_code`),
        KEY `idx_status` (`status`),
        KEY `idx_sort_order` (`sort_order`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='지점 관리 테이블';
    ";
    
    $pdo->exec($sql);
    echo "<h3>✅ branches 테이블이 생성되었습니다!</h3>";
    
    // 샘플 데이터 추가
    $sampleData = [
        [
            'branch_code' => 'HQ001',
            'branch_name' => '서울본사',
            'branch_type' => '본사',
            'manager_name' => '김본부',
            'phone' => '02-1234-5678',
            'email' => 'hq@j-techel.co.kr',
            'address' => '서울시 강남구 역삼동',
            'status' => 'active',
            'sort_order' => 1
        ],
        [
            'branch_code' => 'BR001', 
            'branch_name' => '부산지점',
            'branch_type' => '지점',
            'manager_name' => '이지점',
            'phone' => '051-987-6543',
            'email' => 'busan@j-techel.co.kr',
            'address' => '부산시 해운대구',
            'status' => 'active',
            'sort_order' => 2
        ],
        [
            'branch_code' => 'BR002',
            'branch_name' => '대구영업소',
            'branch_type' => '영업소',
            'manager_name' => '박영업',
            'phone' => '053-555-1234',
            'email' => 'daegu@j-techel.co.kr',
            'address' => '대구시 수성구',
            'status' => 'active',
            'sort_order' => 3
        ]
    ];
    
    $insertSql = "INSERT INTO branches (
        branch_code, branch_name, branch_type, manager_name, 
        phone, email, address, status, sort_order, created_by
    ) VALUES (
        :branch_code, :branch_name, :branch_type, :manager_name,
        :phone, :email, :address, :status, :sort_order, :created_by
    )";
    
    $stmt = $pdo->prepare($insertSql);
    
    foreach($sampleData as $data) {
        $stmt->execute([
            ':branch_code' => $data['branch_code'],
            ':branch_name' => $data['branch_name'],
            ':branch_type' => $data['branch_type'],
            ':manager_name' => $data['manager_name'],
            ':phone' => $data['phone'],
            ':email' => $data['email'],
            ':address' => $data['address'],
            ':status' => $data['status'],
            ':sort_order' => $data['sort_order'],
            ':created_by' => $_SESSION['name'] ?? '시스템'
        ]);
    }
    
    echo "<h3>✅ 샘플 데이터가 추가되었습니다!</h3>";
    echo "<p><strong>추가된 지점:</strong></p>";
    echo "<ul>";
    foreach($sampleData as $data) {
        echo "<li>{$data['branch_code']} - {$data['branch_name']} ({$data['branch_type']})</li>";
    }
    echo "</ul>";
    
    echo '<p><a href="branch_manage.php" class="btn btn-primary">지점 관리로 이동</a></p>';
    
} catch (Exception $e) {
    echo "<h3>❌ 오류 발생:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>

<style>
body { 
    font-family: Arial, sans-serif; 
    margin: 40px; 
    background: #f5f5f5; 
}
.btn { 
    display: inline-block; 
    padding: 10px 20px; 
    background: #007bff; 
    color: white; 
    text-decoration: none; 
    border-radius: 5px;
    margin-top: 20px;
}
.btn:hover { 
    background: #0056b3; 
}
h3 { 
    color: #333; 
}
ul { 
    background: white; 
    padding: 15px; 
    border-radius: 5px; 
}
</style>