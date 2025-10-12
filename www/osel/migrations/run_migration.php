<?php
/**
 * 서버 마이그레이션 실행 스크립트
 * URL: http://j-techel.co.kr/osel/migrations/run_migration.php
 * 
 * 보안: 실행 후 이 파일을 삭제하거나 접근을 제한하세요
 */

// 직접 실행만 허용
if (basename($_SERVER['PHP_SELF']) !== 'run_migration.php') {
    die('직접 접근만 허용됩니다.');
}

// 보안 토큰 확인 (URL에 ?token=your_secret_token 추가)
$requiredToken = 'jtechel_migration_2025'; // 원하는 보안 토큰으로 변경하세요
if (!isset($_GET['token']) || $_GET['token'] !== $requiredToken) {
    die('❌ 유효하지 않은 접근입니다. 올바른 토큰이 필요합니다.');
}

// 환경 설정 로드
require_once '../../config/environment.php';
require_once '../../lib/mydb.php';

// 서버 환경인지 확인
if (Environment::isLocal()) {
    echo "⚠️ 로컬 환경에서는 이 스크립트를 실행하지 마세요.<br>";
    echo "로컬에서는 MySQL 명령어를 직접 사용하세요.<br>";
    exit;
}

echo "<!DOCTYPE html>
<html lang='ko'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>데이터베이스 마이그레이션</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2563eb;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 10px;
        }
        .success {
            background: #d1fae5;
            border-left: 4px solid #10b981;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .error {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .info {
            background: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .warning {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        code {
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        pre {
            background: #1f2937;
            color: #f9fafb;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🗄️ 데이터베이스 마이그레이션</h1>
        <div class='info'>
            <strong>📋 마이그레이션 내용:</strong> car_structure 컬럼 추가
        </div>
";

try {
    // 데이터베이스 연결
    $DB = 'jtechel';
    $pdo = db_connect();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("USE $DB");
    
    echo "<div class='success'>✅ 데이터베이스 연결 성공</div>";
    
    // 마이그레이션 시작
    echo "<h2>🚀 마이그레이션 실행 중...</h2>";
    
    // 1. 컬럼이 이미 존재하는지 확인
    $stmt = $pdo->query("
        SELECT COUNT(*) as count
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = '$DB'
          AND TABLE_NAME = 'panel_measurements'
          AND COLUMN_NAME = 'car_structure'
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        echo "<div class='warning'>⚠️ <code>car_structure</code> 컬럼이 이미 존재합니다. 건너뜁니다.</div>";
    } else {
        // 2. car_structure 컬럼 추가
        $sql1 = "ALTER TABLE `panel_measurements`
                 ADD COLUMN `car_structure` VARCHAR(20) NOT NULL DEFAULT '일반형' 
                 COMMENT '카 구조 (일반형/관통형)' 
                 AFTER `car_inside_height`";
        
        $pdo->exec($sql1);
        echo "<div class='success'>✅ <code>car_structure</code> 컬럼 추가 완료</div>";
        
        // 3. 인덱스 추가
        try {
            $sql2 = "ALTER TABLE `panel_measurements`
                     ADD KEY `idx_car_structure` (`car_structure`)";
            $pdo->exec($sql2);
            echo "<div class='success'>✅ <code>idx_car_structure</code> 인덱스 추가 완료</div>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "<div class='warning'>⚠️ <code>idx_car_structure</code> 인덱스가 이미 존재합니다.</div>";
            } else {
                throw $e;
            }
        }
    }
    
    // 4. 결과 확인
    echo "<h2>📊 마이그레이션 결과 확인</h2>";
    $stmt = $pdo->query("
        SELECT 
            COLUMN_NAME,
            COLUMN_TYPE,
            IS_NULLABLE,
            COLUMN_DEFAULT,
            COLUMN_COMMENT
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = '$DB'
          AND TABLE_NAME = 'panel_measurements'
          AND COLUMN_NAME = 'car_structure'
    ");
    $columnInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($columnInfo) {
        echo "<pre>";
        echo "컬럼명: " . $columnInfo['COLUMN_NAME'] . "\n";
        echo "타입: " . $columnInfo['COLUMN_TYPE'] . "\n";
        echo "NULL 허용: " . $columnInfo['IS_NULLABLE'] . "\n";
        echo "기본값: " . $columnInfo['COLUMN_DEFAULT'] . "\n";
        echo "설명: " . $columnInfo['COLUMN_COMMENT'] . "\n";
        echo "</pre>";
        
        echo "<div class='success'><strong>🎉 마이그레이션이 성공적으로 완료되었습니다!</strong></div>";
    } else {
        echo "<div class='error'>❌ 컬럼 정보를 확인할 수 없습니다.</div>";
    }
    
    // 5. 보안 경고
    echo "<div class='warning'>
            <strong>⚠️ 보안 주의사항:</strong><br>
            마이그레이션이 완료되었습니다. 보안을 위해 이 파일을 삭제하거나 접근을 차단하세요.<br>
            <code>rm /path/to/www/osel/migrations/run_migration.php</code>
          </div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<strong>❌ 오류 발생:</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "</div>";
    error_log("Migration error: " . $e->getMessage());
}

echo "
    </div>
</body>
</html>";
?>

