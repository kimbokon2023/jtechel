<?php
require_once '../lib/mydb.php';
session_start();
$DB = 'jtechel';

// Check authentication
if (!isset($_SESSION["level"]) || $_SESSION["level"] > 8) {
    header("Location: ../login/login_form.php");
    exit;
}

// Initialize database connection
try {
    $pdo = db_connect();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("USE $DB");
} catch (PDOException $e) {
    die("데이터베이스 연결에 실패했습니다.");
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'create_group':
                $group_name = trim($_POST['group_name'] ?? '');
                $group_description = trim($_POST['group_description'] ?? '');
                $measurement_ids = $_POST['measurement_ids'] ?? [];
                
                if (empty($group_name)) {
                    echo json_encode(['success' => false, 'message' => '그룹명을 입력해주세요.']);
                    exit;
                }
                
                // 중복 그룹명 확인
                $stmt = $pdo->prepare("SELECT id FROM site_groups WHERE group_name = ? AND is_deleted = 0");
                $stmt->execute([$group_name]);
                if ($stmt->fetch()) {
                    echo json_encode(['success' => false, 'message' => '이미 존재하는 그룹명입니다.']);
                    exit;
                }
                
                try {
                    $pdo->beginTransaction();
                    
                    // 그룹 생성
                    $stmt = $pdo->prepare("INSERT INTO site_groups (group_name, group_description, created_by) VALUES (?, ?, ?)");
                    $stmt->execute([$group_name, $group_description, $_SESSION['name'] ?? 'admin']);
                    $group_id = $pdo->lastInsertId();
                    
                    // 선택된 현장들을 그룹에 추가
                    $added_count = 0;
                    if (!empty($measurement_ids) && is_array($measurement_ids)) {
                        $stmt = $pdo->prepare("INSERT INTO site_group_members (group_id, measurement_id, added_by) VALUES (?, ?, ?)");
                        foreach ($measurement_ids as $measurement_id) {
                            $measurement_id = intval($measurement_id);
                            if ($measurement_id > 0) {
                                $stmt->execute([$group_id, $measurement_id, $_SESSION['name'] ?? 'admin']);
                                $added_count++;
                            }
                        }
                    }
                    
                    $pdo->commit();
                    
                    $message = "그룹이 생성되었습니다.";
                    if ($added_count > 0) {
                        $message .= " ({$added_count}개 현장이 추가되었습니다.)";
                    }
                    
                    echo json_encode(['success' => true, 'message' => $message, 'group_id' => $group_id]);
                } catch (Exception $e) {
                    $pdo->rollback();
                    echo json_encode(['success' => false, 'message' => '그룹 생성 중 오류가 발생했습니다: ' . $e->getMessage()]);
                }
                break;
                
            case 'delete_group':
                $group_id = intval($_POST['group_id'] ?? 0);
                
                if ($group_id <= 0) {
                    echo json_encode(['success' => false, 'message' => '유효하지 않은 그룹 ID입니다.']);
                    exit;
                }
                
                // 그룹 소프트 삭제
                $stmt = $pdo->prepare("UPDATE site_groups SET is_deleted = 1, deleted_at = CURRENT_TIMESTAMP, deleted_by = ? WHERE id = ?");
                $stmt->execute([$_SESSION['name'] ?? 'admin', $group_id]);
                
                // 그룹 멤버들도 소프트 삭제
                $stmt = $pdo->prepare("UPDATE site_group_members SET is_deleted = 1, deleted_at = CURRENT_TIMESTAMP, deleted_by = ? WHERE group_id = ?");
                $stmt->execute([$_SESSION['name'] ?? 'admin', $group_id]);
                
                echo json_encode(['success' => true, 'message' => '그룹이 삭제되었습니다.']);
                break;
                
            case 'add_measurements_to_group':
                $group_id = intval($_POST['group_id'] ?? 0);
                $measurement_ids = $_POST['measurement_ids'] ?? [];
                
                if ($group_id <= 0 || empty($measurement_ids)) {
                    echo json_encode(['success' => false, 'message' => '그룹과 현장을 선택해주세요.']);
                    exit;
                }
                
                $added_count = 0;
                $already_exists_count = 0;
                
                foreach ($measurement_ids as $measurement_id) {
                    $measurement_id = intval($measurement_id);
                    
                    // 이미 그룹에 속해있는지 확인
                    $stmt = $pdo->prepare("SELECT id FROM site_group_members WHERE group_id = ? AND measurement_id = ? AND is_deleted = 0");
                    $stmt->execute([$group_id, $measurement_id]);
                    
                    if (!$stmt->fetch()) {
                        $stmt = $pdo->prepare("INSERT INTO site_group_members (group_id, measurement_id, added_by) VALUES (?, ?, ?)");
                        $stmt->execute([$group_id, $measurement_id, $_SESSION['name'] ?? 'admin']);
                        $added_count++;
                    } else {
                        $already_exists_count++;
                    }
                }
                
                $message = "{$added_count}개 현장이 그룹에 추가되었습니다.";
                if ($already_exists_count > 0) {
                    $message .= " ({$already_exists_count}개는 이미 그룹에 속해있습니다.)";
                }
                
                echo json_encode(['success' => true, 'message' => $message]);
                break;
                
            case 'remove_measurement_from_group':
                $group_id = intval($_POST['group_id'] ?? 0);
                $measurement_id = intval($_POST['measurement_id'] ?? 0);
                
                if ($group_id <= 0 || $measurement_id <= 0) {
                    echo json_encode(['success' => false, 'message' => '유효하지 않은 ID입니다.']);
                    exit;
                }
                
                $stmt = $pdo->prepare("UPDATE site_group_members SET is_deleted = 1, deleted_at = CURRENT_TIMESTAMP, deleted_by = ? WHERE group_id = ? AND measurement_id = ?");
                $stmt->execute([$_SESSION['name'] ?? 'admin', $group_id, $measurement_id]);
                
                echo json_encode(['success' => true, 'message' => '현장이 그룹에서 제거되었습니다.']);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => '알 수 없는 작업입니다.']);
        }
    } catch (Exception $e) {
        error_log("Site groups error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => '오류가 발생했습니다: ' . $e->getMessage()]);
    }
    exit;
}

// Get all active groups
$groups_stmt = $pdo->prepare("
    SELECT sg.*, 
           COUNT(sgm.measurement_id) as member_count
    FROM site_groups sg
    LEFT JOIN site_group_members sgm ON sg.id = sgm.group_id AND sgm.is_deleted = 0
    WHERE sg.is_deleted = 0
    GROUP BY sg.id
    ORDER BY sg.created_at DESC
");
$groups_stmt->execute();
$groups = $groups_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all measurements for selection (그룹 생성용)
$measurements_stmt = $pdo->prepare("
    SELECT pm.id, pm.site_name, pm.measurement_date, pm.measurer_name,
           pm.car_inside_width, pm.car_inside_depth, pm.car_inside_height,
           GROUP_CONCAT(sg.group_name ORDER BY sg.group_name SEPARATOR ', ') as group_names
    FROM panel_measurements pm
    LEFT JOIN site_group_members sgm ON pm.id = sgm.measurement_id AND sgm.is_deleted = 0
    LEFT JOIN site_groups sg ON sgm.group_id = sg.id AND sg.is_deleted = 0
    GROUP BY pm.id, pm.site_name, pm.measurement_date, pm.measurer_name, pm.car_inside_width, pm.car_inside_depth, pm.car_inside_height
    ORDER BY pm.measurement_date DESC, pm.site_name
");
$measurements_stmt->execute();
$measurements = $measurements_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get measurements for a specific group (if selected)
$selected_group_id = $_GET['group_id'] ?? null;
$group_measurements = [];

if ($selected_group_id) {
    $group_measurements_stmt = $pdo->prepare("
        SELECT pm.*, sgm.added_at as added_to_group_at
        FROM panel_measurements pm
        INNER JOIN site_group_members sgm ON pm.id = sgm.measurement_id
        WHERE sgm.group_id = ? AND sgm.is_deleted = 0
        ORDER BY sgm.added_at DESC
    ");
    $group_measurements_stmt->execute([$selected_group_id]);
    $group_measurements = $group_measurements_stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>현장 그룹 관리 - J-TECH</title>
    
    <!-- Linear Theme CSS -->
    <link rel="stylesheet" href="../components/linear-theme.css">
    <link rel="stylesheet" href="../components/linear-components.css">
    
    <!-- Theme Toggle JavaScript -->
    <script src="../components/linear-theme-toggle.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">

    <!-- Mobile Modal Handler -->
    <script src="assets/js/mobile-modal-handler.js"></script>
    <script src="assets/js/mobile-modal-enhancement.js"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background: var(--linear-bg-primary);
            color: var(--linear-text-primary);
        }
        
        .result-container {
            padding: var(--linear-spacing-lg);
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: var(--linear-spacing-sm);
            margin-bottom: var(--linear-spacing-lg);
            font-size: var(--linear-text-small);
        }

        .breadcrumb a {
            color: var(--linear-brand-primary);
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .breadcrumb-separator {
            color: var(--linear-text-tertiary);
        }

        .page-title {
            font-size: 2rem;
            font-weight: var(--linear-font-weight-semibold);
            color: var(--linear-text-primary);
            margin-bottom: var(--linear-spacing-xl);
            display: flex;
            align-items: center;
            gap: var(--linear-spacing-md);
        }

        .search-container {
            background-color: var(--linear-bg-secondary);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-border-radius-lg);
            padding: var(--linear-spacing-xl);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: var(--linear-spacing-xl);
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: var(--linear-spacing-sm);
            margin-bottom: var(--linear-spacing-lg);
            padding: var(--linear-spacing-md) 0;
            border-bottom: 1px solid var(--linear-border-secondary);
        }

        .section-header h3 {
            font-size: var(--linear-text-lg);
            font-weight: var(--linear-font-weight-semibold);
            color: var(--linear-brand-primary);
            margin: 0;
        }

        .section-header i {
            color: var(--linear-brand-primary);
        }

        .search-form {
            display: block;
        }

        .search-form.collapsed {
            display: none;
        }

        .search-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: var(--linear-spacing-md);
            align-items: end;
        }

        .search-actions {
            display: flex;
            flex-direction: column;
            gap: var(--linear-spacing-sm);
        }

        .search-grid label {
            display: block;
            font-size: var(--linear-text-sm);
            font-weight: var(--linear-font-weight-medium);
            color: var(--linear-text-primary);
            margin-bottom: var(--linear-spacing-xs);
        }

        .search-grid input,
        .search-grid select {
            width: 100%;
            padding: var(--linear-spacing-sm) var(--linear-spacing-md);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-border-radius-sm);
            background: var(--linear-bg-primary);
            color: var(--linear-text-primary);
            font-size: var(--linear-text-base);
            transition: border-color 0.2s ease;
        }

        .search-grid input:focus,
        .search-grid select:focus {
            outline: none;
            border-color: var(--linear-brand-primary);
            box-shadow: 0 0 0 2px rgba(var(--linear-brand-primary-rgb), 0.1);
        }

        @media (max-width: 768px) {
            .search-grid {
                grid-template-columns: 1fr;
                gap: var(--linear-spacing-sm);
            }

            .search-actions {
                flex-direction: row;
                justify-content: space-between;
            }

            .search-container {
                padding: var(--linear-spacing-lg);
            }
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 3fr;
            gap: var(--linear-spacing-xl);
            align-items: start;
            min-height: 500px;
        }
        
        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
                gap: var(--linear-spacing-lg);
            }
            
            .content-grid > .linear-card:first-child {
                grid-column: 1;
                grid-row: 1;
            }

            .content-grid > .linear-card:last-child {
                grid-column: 1;
                grid-row: 2;
            }
        }

        @media (min-width: 769px) {
            .content-grid {
                min-height: 600px;
            }
            
            .content-grid > .linear-card:last-child {
                min-width: 400px;
            }
        }
        
        .linear-card {
            background: var(--linear-bg-secondary);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-border-radius-lg);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* 그리드 아이템들이 한 행에 배치되도록 */
        .content-grid > .linear-card:first-child {
            grid-column: 1;
            grid-row: 1;
            height: fit-content;
        }

        .content-grid > .linear-card:last-child {
            grid-column: 2;
            grid-row: 1;
            min-height: 400px;
        }
        
        .card-header {
            padding: var(--linear-spacing-lg);
            border-bottom: 1px solid var(--linear-border-primary);
            background: var(--linear-bg-tertiary);
        }
        
        .card-title {
            font-size: var(--linear-text-lg);
            font-weight: var(--linear-font-weight-semibold);
            color: var(--linear-text-primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: var(--linear-spacing-sm);
        }
        
        .card-body {
            padding: var(--linear-spacing-lg);
            flex-grow: 1;
            overflow-y: auto;
        }
        
        .group-card {
            background: var(--linear-bg-secondary);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-border-radius-md);
            padding: var(--linear-spacing-lg);
            margin-bottom: var(--linear-spacing-md);
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .group-card:hover {
            border-color: var(--linear-brand-primary);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .group-card.selected {
            border-color: var(--linear-brand-primary);
            box-shadow: 0 0 0 2px rgba(var(--linear-brand-primary-rgb), 0.2);
            background: var(--linear-bg-tertiary);
        }
        
        .group-name {
            font-size: var(--linear-text-lg);
            font-weight: var(--linear-font-weight-semibold);
            color: var(--linear-text-primary);
            margin-bottom: var(--linear-spacing-sm);
        }
        
        .group-description {
            color: var(--linear-text-secondary);
            margin-bottom: var(--linear-spacing-md);
        }
        
        .group-stats {
            font-size: var(--linear-text-sm);
            color: var(--linear-text-secondary);
            display: flex;
            align-items: center;
            gap: var(--linear-spacing-md);
        }
        
        .measurement-item {
            background: var(--linear-bg-secondary);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-border-radius-md);
            padding: var(--linear-spacing-md);
            margin-bottom: var(--linear-spacing-sm);
            transition: background-color 0.2s ease;
        }
        
        .measurement-item:hover {
            background: var(--linear-bg-tertiary);
        }
        
        .measurement-name {
            font-size: var(--linear-text-base);
            font-weight: var(--linear-font-weight-medium);
            color: var(--linear-text-primary);
            margin-bottom: var(--linear-spacing-xs);
        }
        
        .measurement-meta {
            font-size: var(--linear-text-sm);
            color: var(--linear-text-secondary);
            margin-bottom: var(--linear-spacing-xs);
        }
        
        .measurement-dimensions {
            font-size: var(--linear-text-sm);
            color: var(--linear-text-secondary);
        }
        
        .empty-state {
            text-align: center;
            padding: var(--linear-spacing-3xl) var(--linear-spacing-lg);
            color: var(--linear-text-secondary);
        }
        
        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: var(--linear-spacing-lg);
            opacity: 0.5;
        }
        
        .empty-state-title {
            font-size: var(--linear-text-lg);
            font-weight: var(--linear-font-weight-medium);
            margin-bottom: var(--linear-spacing-sm);
        }
        
        .empty-state-description {
            margin-bottom: var(--linear-spacing-lg);
        }
        
        .action-buttons {
            display: flex;
            gap: var(--linear-spacing-sm);
            align-items: center;
        }
        
        .dropdown-toggle {
            background: transparent;
            border: 1px solid var(--linear-border-primary);
            color: var(--linear-text-secondary);
            padding: var(--linear-spacing-xs) var(--linear-spacing-sm);
            border-radius: var(--linear-border-radius-sm);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .dropdown-toggle:hover {
            background: var(--linear-bg-tertiary);
            border-color: var(--linear-border-secondary);
        }
        
        .dropdown-menu {
            background: var(--linear-bg-secondary);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-border-radius-md);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .dropdown-item {
            color: var(--linear-text-primary);
            padding: var(--linear-spacing-sm) var(--linear-spacing-md);
            transition: background-color 0.2s ease;
        }
        
        .dropdown-item:hover {
            background: var(--linear-bg-tertiary);
            color: var(--linear-text-primary);
        }
        
        .dropdown-item.text-danger {
            color: var(--linear-error-primary);
        }
        
        .dropdown-item.text-danger:hover {
            background: var(--linear-error-bg);
            color: var(--linear-error-primary);
        }

        /* 모달 관련 스타일 */
        .modal {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            z-index: 1055 !important;
            overflow: auto !important;
            outline: 0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 1.75rem !important;
        }

        .modal-backdrop {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            background-color: rgba(0, 0, 0, 0.5) !important;
            z-index: 1050 !important;
        }

        .modal.show {
            display: block !important;
        }

        .modal.fade .modal-dialog {
            transition: transform 0.3s ease-out;
            transform: translate(0, -50px);
        }

        .modal.show .modal-dialog {
            transform: none;
        }

        .modal-dialog {
            position: relative !important;
            width: auto !important;
            margin: 0 auto !important;
            max-width: 800px !important;
            pointer-events: none !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .modal-content {
            position: relative !important;
            display: flex !important;
            flex-direction: column !important;
            width: 100% !important;
            pointer-events: auto !important;
            background-color: var(--linear-bg-secondary) !important;
            background-clip: padding-box !important;
            border: 1px solid var(--linear-border-primary) !important;
            border-radius: var(--linear-border-radius-md) !important;
            outline: 0 !important;
        }

        .modal-content {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        }

        .modal-header {
            border-bottom: 1px solid var(--linear-border-primary);
            background: var(--linear-bg-tertiary);
        }

        .modal-body {
            background: var(--linear-bg-secondary);
        }

        .modal-footer {
            border-top: 1px solid var(--linear-border-primary);
            background: var(--linear-bg-tertiary);
        }

        .modal-title {
            color: var(--linear-text-primary);
            font-weight: var(--linear-font-weight-semibold);
        }

        .btn-close {
            filter: var(--linear-icon-filter);
        }

        /* 모달 내부 폼 요소 스타일 */
        .modal .form-control {
            background: var(--linear-bg-primary);
            border: 1px solid var(--linear-border-primary);
            color: var(--linear-text-primary);
        }

        .modal .form-control:focus {
            background: var(--linear-bg-primary);
            border-color: var(--linear-brand-primary);
            color: var(--linear-text-primary);
            box-shadow: 0 0 0 0.2rem rgba(var(--linear-brand-primary-rgb), 0.25);
        }

        .modal .form-label {
            color: var(--linear-text-primary);
            font-weight: var(--linear-font-weight-medium);
        }

        /* 모달 스크롤 영역 */
        .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }

        /* 모달 중앙 정렬 */
        .modal.show {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            z-index: 1055 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .modal.show .modal-dialog {
            position: relative !important;
            margin: 0 auto !important;
            transform: none !important;
        }

        /* 모달 반응형 */
        @media (max-width: 768px) {
            .modal {
                padding: 0.5rem !important;
            }
            
            .modal-dialog {
                margin: 0 !important;
                max-width: calc(100vw - 1rem) !important;
            }
            
            .modal-body {
                max-height: 60vh;
            }
        }
    </style>
</head>
<body>
    <?php
    // Linear 컴포넌트들 import
    require_once '../components/LinearComponent.php';
    require_once '../components/LinearNavigation.php';
    require_once '../components/LinearButton.php';

    $nav = LinearNavigation::withBrand(
        '<i class="bi bi-building"></i> OSEL',
        'index.php'
    )
    ->addAction('
        <button type="button" id="themeToggleBtn" class="linear-btn linear-btn-ghost linear-btn-sm"
                style="margin-right: 0.5rem; min-width: 40px; min-height: 40px;" 
                data-linear-theme-toggle=\'{"showIcons": true, "showLabels": false}\'
                title="테마 변경">
            <span id="themeIcon">🌙</span>
        </button>
    ')
    ->addAction('<span style="margin-right: 1rem; color: var(--linear-text-secondary);"> ' . htmlspecialchars($_SESSION["name"]) . '님</span>')
    ->addAction('<a href="../login/logout.php" style="color: var(--linear-text-secondary); text-decoration: none;">로그아웃</a>')
    ->fixed();

    echo $nav;
    ?>

    <div class="result-container" style="margin-top: var(--linear-header-height);">
        <!-- Breadcrumb -->
        <nav class="breadcrumb">
            <a href="index.php">대시보드</a>
            <span class="breadcrumb-separator">/</span>
            <span>현장 그룹 관리</span>
        </nav>

        <h2 class="page-title">
            <i class="bi bi-collection"></i> 현장 그룹 관리
        </h2>

        <!-- 그룹 검색 및 관리 -->
        <div class="search-container">
            <div class="section-header search-toggle" id="groupSearchToggle" style="cursor: pointer; user-select: none;">
                <i class="bi bi-search"></i>
                <h3>그룹 검색 및 관리</h3>
                <i class="bi bi-chevron-up toggle-icon" id="groupToggleIcon" style="margin-left: auto; transition: transform 0.3s ease;"></i>
            </div>

            <div class="search-form" id="groupSearchForm">
                <div class="search-grid">
                    <div>
                        <label for="searchGroupName">그룹명</label>
                        <input type="text" id="searchGroupName" name="search_group_name"
                               placeholder="그룹명 검색" onkeyup="filterGroups()">
                    </div>
                    <div>
                        <label for="searchMemberCount">멤버 수</label>
                        <select id="searchMemberCount" name="search_member_count" onchange="filterGroups()">
                            <option value="">전체</option>
                            <option value="0">빈 그룹 (0개)</option>
                            <option value="1-5">1-5개 현장</option>
                            <option value="6-10">6-10개 현장</option>
                            <option value="11+">11개 이상</option>
                        </select>
                    </div>
                    <div>
                        <label for="searchCreatedDate">생성일</label>
                        <select id="searchCreatedDate" name="search_created_date" onchange="filterGroups()">
                            <option value="">전체</option>
                            <option value="today">오늘</option>
                            <option value="week">최근 1주</option>
                            <option value="month">최근 1개월</option>
                            <option value="quarter">최근 3개월</option>
                        </select>
                    </div>
                    <div class="search-actions">
                        <button type="button" onclick="filterGroups()" class="linear-btn linear-btn-primary">
                            <i class="bi bi-search"></i> 검색
                        </button>
                        <button type="button" onclick="resetGroupSearch()" class="linear-btn linear-btn-secondary">
                            <i class="bi bi-arrow-clockwise"></i> 초기화
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 메인 콘텐츠 그리드 -->
        <div class="content-grid">
            <!-- 왼쪽: 그룹 목록 -->
            <div class="linear-card">
                <div class="card-header" style="display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <h2 class="card-title" style="margin: 0;">
                            <i class="bi bi-collection"></i>
                            그룹 목록
                        </h2>
                    </div>
                    <div class="action-buttons">
                        <?php
                        echo LinearButton::primary('새 그룹', ['onclick' => 'showCreateGroupModal()']);
                        ?>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($groups)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="bi bi-inbox"></i>
                            </div>
                            <div class="empty-state-title">생성된 그룹이 없습니다</div>
                            <div class="empty-state-description">현장들을 그룹으로 묶어서 관리해보세요.</div>
                            <?php
                            echo LinearButton::primary('첫 번째 그룹 만들기', ['onclick' => 'showCreateGroupModal()']);
                            ?>
                        </div>
                    <?php else: ?>
                        <?php foreach ($groups as $group): ?>
                            <div class="group-card" 
                                 data-group-id="<?= $group['id'] ?>"
                                 onclick="selectGroup(<?= $group['id'] ?>)">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h3 class="group-name"><?= htmlspecialchars($group['group_name']) ?></h3>
                                        <?php if ($group['group_description']): ?>
                                            <p class="group-description"><?= htmlspecialchars($group['group_description']) ?></p>
                                        <?php endif; ?>
                                        <div class="group-stats">
                                            <span><i class="bi bi-building"></i> <?= $group['member_count'] ?>개 현장</span>
                                            <span><i class="bi bi-calendar"></i> <?= date('Y-m-d', strtotime($group['created_at'])) ?></span>
                                        </div>
                                    </div>
                                    <div class="dropdown">
                                        <button class="dropdown-toggle" 
                                                type="button" 
                                                data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#" onclick="editGroup(<?= $group['id'] ?>)">
                                                <i class="bi bi-pencil"></i> 수정
                                            </a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" href="#" onclick="deleteGroup(<?= $group['id'] ?>)">
                                                <i class="bi bi-trash"></i> 삭제
                                            </a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                </div>
                        
            <!-- 오른쪽: 그룹 상세 및 현장 관리 -->
            <div class="linear-card">
                <?php if ($selected_group_id): ?>
                    <!-- 선택된 그룹의 현장 목록 -->
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="bi bi-building"></i> 
                            <?= htmlspecialchars($groups[array_search($selected_group_id, array_column($groups, 'id'))]['group_name'] ?? 'Unknown') ?>
                        </h2>
                        <div class="action-buttons">
                            <?php
                            echo LinearButton::secondary('현장 추가', ['onclick' => "showAddMeasurementsModal({$selected_group_id})"]);
                            echo LinearButton::primary('엑셀 내보내기', ['onclick' => "exportGroupToExcel({$selected_group_id})"]);
                            ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($group_measurements)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="bi bi-building"></i>
                                </div>
                                <div class="empty-state-title">이 그룹에 속한 현장이 없습니다</div>
                                <div class="empty-state-description">현장을 추가하여 그룹을 구성해보세요.</div>
                                <?php
                                echo LinearButton::primary('현장 추가하기', ['onclick' => "showAddMeasurementsModal({$selected_group_id})"]);
                                ?>
                            </div>
                        <?php else: ?>
                            <?php foreach ($group_measurements as $measurement): ?>
                                <div class="measurement-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h4 class="measurement-name"><?= htmlspecialchars($measurement['site_name']) ?></h4>
                                            <p class="measurement-meta">
                                                <i class="bi bi-person"></i> <?= htmlspecialchars($measurement['measurer_name']) ?>
                                            </p>
                                            <p class="measurement-meta">
                                                <i class="bi bi-calendar"></i> <?= $measurement['measurement_date'] ?>
                                            </p>
                                            <p class="measurement-dimensions">
                                                <i class="bi bi-rulers"></i> 
                                                <?= number_format($measurement['car_inside_width']) ?> × 
                                                <?= number_format($measurement['car_inside_depth']) ?> × 
                                                <?= number_format($measurement['car_inside_height']) ?>mm
                                            </p>
                                        </div>
                                        <div class="dropdown">
                                            <button class="dropdown-toggle" 
                                                    type="button" 
                                                    data-bs-toggle="dropdown">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="result.php?measurement_id=<?= $measurement['id'] ?>">
                                                    <i class="bi bi-eye"></i> 상세보기
                                                </a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item text-danger" href="#" 
                                                       onclick="removeFromGroup(<?= $selected_group_id ?>, <?= $measurement['id'] ?>)">
                                                    <i class="bi bi-x-circle"></i> 그룹에서 제거
                                                </a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    </div>
                    </div>
                <?php else: ?>
                    <!-- 그룹을 선택하지 않은 경우 -->
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="bi bi-arrow-left-circle"></i>
                            그룹 선택
                        </h2>
                    </div>
                    <div class="card-body">
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="bi bi-arrow-left-circle"></i>
                            </div>
                            <div class="empty-state-title">그룹을 선택해주세요</div>
                            <div class="empty-state-description">왼쪽에서 그룹을 선택하면 해당 그룹의 현장 목록을 볼 수 있습니다.</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 그룹 생성 모달 -->
    <div class="modal fade" id="createGroupModal" tabindex="-1" aria-labelledby="createGroupModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createGroupModalLabel">
                        <i class="bi bi-plus-circle"></i> 새 그룹 생성
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createGroupForm">
                        <!-- 그룹 기본 정보 -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="groupName" class="form-label">그룹명 *</label>
                                    <input type="text" class="form-control" id="groupName" name="group_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="groupDescription" class="form-label">그룹 설명</label>
                                    <input type="text" class="form-control" id="groupDescription" name="group_description">
                                </div>
                            </div>
                        </div>
                        
                        <!-- 현장 검색 및 선택 -->
                        <div class="mb-3">
                            <label class="form-label">현장 검색 및 선택</label>
                            <div class="row">
                                <div class="col-md-8">
                                    <input type="text" id="siteSearchInput" class="form-control" placeholder="현장명 또는 측정자로 검색..."
                                           onkeyup="searchSites()">
                                </div>
                                <div class="col-md-4">
                                    <select id="siteDateFilter" class="form-control" onchange="searchSites()">
                                        <option value="">전체 기간</option>
                                        <option value="today">오늘</option>
                                        <option value="week">최근 1주</option>
                                        <option value="month">최근 1개월</option>
                                        <option value="quarter">최근 3개월</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 검색 결과 -->
                        <div id="siteSearchResults" style="max-height: 300px; overflow-y: auto; border: 1px solid var(--linear-border-primary); border-radius: var(--linear-border-radius-sm); padding: var(--linear-spacing-sm); background: var(--linear-bg-primary);">
                            <!-- 검색 결과가 여기에 표시됩니다 -->
                        </div>
                        
                        <!-- 선택된 현장 목록 -->
                        <div class="mt-3" id="selectedSitesSection" style="display: none;">
                            <label class="form-label">선택된 현장 (<span id="selectedCount">0</span>개)</label>
                            <div id="selectedSitesList" style="max-height: 200px; overflow-y: auto; border: 1px solid var(--linear-border-primary); border-radius: var(--linear-border-radius-sm); padding: var(--linear-spacing-sm); background: var(--linear-bg-primary);">
                                <!-- 선택된 현장들이 여기에 표시됩니다 -->
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <?php
                    echo LinearButton::secondary('취소', ['data-bs-dismiss' => 'modal']);
                    echo LinearButton::primary('그룹 생성', ['onclick' => 'createGroupWithSites()']);
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 현장 추가 모달 -->
    <div class="modal fade" id="addMeasurementsModal" tabindex="-1" aria-labelledby="addMeasurementsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addMeasurementsModalLabel">
                        <i class="bi bi-plus-circle"></i> 현장 추가
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <input type="text" class="form-control" id="measurementSearch" placeholder="현장명으로 검색...">
                    </div>
                    <div id="measurementList" style="max-height: 400px; overflow-y: auto;">
                        <!-- JavaScript로 동적 생성 -->
                    </div>
                </div>
                <div class="modal-footer">
                    <?php
                    echo LinearButton::secondary('취소', ['data-bs-dismiss' => 'modal']);
                    echo LinearButton::primary('추가', ['onclick' => 'addSelectedMeasurements()']);
                    ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 테마 토글 기능 초기화
        document.addEventListener('DOMContentLoaded', function() {
            // Linear 테마 시스템이 자동으로 버튼을 초기화하므로
            // 추가적인 수동 처리는 필요하지 않습니다.
            // 단, 테마 변경 시 추가 작업이 필요한 경우에만 이벤트 리스너를 추가합니다.
            
            document.addEventListener('themechange', function(event) {
                console.log('테마 변경됨:', event.detail);
                // 필요시 추가 로직을 여기에 구현
            });
            
            // 모든 모달 요소를 강제로 숨김 상태로 설정
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                modal.classList.remove('show');
                modal.classList.add('fade');
                modal.style.display = 'none';
                modal.style.alignItems = '';
                modal.style.justifyContent = '';
                modal.setAttribute('aria-hidden', 'true');
                modal.removeAttribute('aria-modal');
            });
            
            // 백드롭 제거
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => backdrop.remove());
            
            // body 클래스 정리
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            document.body.style.zIndex = '';
            
            // 모달 초기화 및 이벤트 핸들러 정리
            initializeModals();
            
            // 검색 토글 기능 초기화
            initializeGroupSearch();
        });
        
        // 모달 초기화 함수
        function initializeModals() {
            // 모든 모달 요소 가져오기
            const modals = document.querySelectorAll('.modal');
            
            // 각 모달에 대해 이벤트 리스너 정리
            modals.forEach(function(modal) {
                // 기존 이벤트 리스너 제거
                modal.removeEventListener('show.bs.modal', arguments.callee);
                modal.removeEventListener('hide.bs.modal', arguments.callee);
                
                // 모달이 숨겨진 상태로 초기화
                modal.classList.remove('show');
                modal.classList.remove('fade');
                modal.style.display = 'none';
                modal.style.alignItems = '';
                modal.style.justifyContent = '';
                modal.setAttribute('aria-hidden', 'true');
                modal.removeAttribute('aria-modal');
                
                // 백드롭 제거
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(backdrop => {
                    backdrop.remove();
                });
                
                // body에서 modal-open 클래스 제거
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
                document.body.style.zIndex = '';
            });
            
            // 모달 외부 클릭시 닫기 방지
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('modal') && e.target.classList.contains('fade')) {
                    e.stopPropagation();
                }
            });
        }
        
        // 모든 모달을 닫는 함수
        function closeAllModals() {
            // 모든 모달 요소 가져오기
            const modals = document.querySelectorAll('.modal');
            
            modals.forEach(function(modal) {
                // Bootstrap 모달 인스턴스 가져오기
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                }
                
                // 강제로 모달 숨기기
                modal.classList.remove('show');
                modal.style.display = 'none';
                modal.setAttribute('aria-hidden', 'true');
                modal.removeAttribute('aria-modal');
            });
            
            // 백드롭 제거
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(function(backdrop) {
                backdrop.remove();
            });
            
            // body 스타일 정리
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        }
        
        let currentGroupId = null;
        let currentSelectedMeasurements = [];
        let allGroups = []; // 전체 그룹 데이터 저장
        let allMeasurements = []; // 전체 측정 데이터 저장 (그룹 생성용)
        let selectedSites = []; // 선택된 현장들

        // 그룹 검색 초기화
        function initializeGroupSearch() {
            // 모든 그룹 데이터 수집
            const groupCards = document.querySelectorAll('.group-card');
            allGroups = Array.from(groupCards).map(card => ({
                element: card,
                name: card.querySelector('.group-name').textContent.toLowerCase(),
                memberCount: parseInt(card.querySelector('.group-stats span').textContent.match(/(\d+)개/)[1]),
                createdDate: new Date(card.querySelector('.group-stats span:last-child').textContent.replace('생성일: ', ''))
            }));
            
            // 검색 토글 이벤트 리스너
            const searchToggle = document.getElementById('groupSearchToggle');
            const searchForm = document.getElementById('groupSearchForm');
            const toggleIcon = document.getElementById('groupToggleIcon');
            
            if (searchToggle && searchForm && toggleIcon) {
                searchToggle.addEventListener('click', function() {
                    const isCollapsed = searchForm.classList.contains('collapsed');
                    if (isCollapsed) {
                        searchForm.classList.remove('collapsed');
                        toggleIcon.style.transform = 'rotate(0deg)';
                    } else {
                        searchForm.classList.add('collapsed');
                        toggleIcon.style.transform = 'rotate(180deg)';
                    }
                });
            }
            
            // 측정 데이터 초기화
            allMeasurements = <?= json_encode($measurements) ?>;
        }

        // 현장 검색 함수
        function searchSites() {
            const searchTerm = document.getElementById('siteSearchInput').value.toLowerCase();
            const dateFilter = document.getElementById('siteDateFilter').value;
            
            let filteredMeasurements = allMeasurements.filter(measurement => {
                let matches = true;
                
                // 검색어 필터
                if (searchTerm) {
                    const matchesName = measurement.site_name.toLowerCase().includes(searchTerm);
                    const matchesMeasurer = measurement.measurer_name.toLowerCase().includes(searchTerm);
                    matches = matchesName || matchesMeasurer;
                }
                
                // 날짜 필터
                if (dateFilter && matches) {
                    const measurementDate = new Date(measurement.measurement_date);
                    const now = new Date();
                    const daysDiff = Math.floor((now - measurementDate) / (1000 * 60 * 60 * 24));
                    
                    switch (dateFilter) {
                        case 'today':
                            matches = daysDiff === 0;
                            break;
                        case 'week':
                            matches = daysDiff <= 7;
                            break;
                        case 'month':
                            matches = daysDiff <= 30;
                            break;
                        case 'quarter':
                            matches = daysDiff <= 90;
                            break;
                    }
                }
                
                return matches;
            });
            
            displaySearchResults(filteredMeasurements);
        }

        // 검색 결과 표시
        function displaySearchResults(measurements) {
            const resultsContainer = document.getElementById('siteSearchResults');
            
            if (measurements.length === 0) {
                resultsContainer.innerHTML = '<div style="text-align: center; color: var(--linear-text-secondary); padding: var(--linear-spacing-lg);">검색 결과가 없습니다.</div>';
                return;
            }
            
            const html = measurements.map(measurement => {
                const isSelected = selectedSites.some(site => site.id === measurement.id);
                const groupInfo = measurement.group_names ? `<small style="color: var(--linear-text-tertiary);">그룹: ${measurement.group_names}</small>` : '';
                
                return `
                    <div class="site-search-item" style="padding: var(--linear-spacing-sm); border-bottom: 1px solid var(--linear-border-secondary); display: flex; align-items: center; gap: var(--linear-spacing-sm);">
                        <input type="checkbox" 
                               id="site_${measurement.id}" 
                               ${isSelected ? 'checked' : ''} 
                               onchange="toggleSiteSelection(${measurement.id})"
                               style="flex-shrink: 0;">
                        <label for="site_${measurement.id}" style="flex: 1; cursor: pointer; margin: 0;">
                            <div style="font-weight: var(--linear-font-weight-medium); color: var(--linear-text-primary);">
                                ${measurement.site_name}
                            </div>
                            <div style="font-size: var(--linear-text-sm); color: var(--linear-text-secondary);">
                                ${measurement.measurement_date} | ${measurement.measurer_name}
                                ${measurement.car_inside_width ? ` | ${measurement.car_inside_width}×${measurement.car_inside_depth}×${measurement.car_inside_height}mm` : ''}
                            </div>
                            ${groupInfo}
                        </label>
                    </div>
                `;
            }).join('');
            
            resultsContainer.innerHTML = html;
        }

        // 현장 선택 토글
        function toggleSiteSelection(measurementId) {
            const measurement = allMeasurements.find(m => m.id == measurementId);
            if (!measurement) return;
            
            const existingIndex = selectedSites.findIndex(site => site.id === measurementId);
            
            if (existingIndex >= 0) {
                // 이미 선택된 경우 제거
                selectedSites.splice(existingIndex, 1);
            } else {
                // 선택되지 않은 경우 추가
                selectedSites.push(measurement);
            }
            
            updateSelectedSitesList();
        }

        // 선택된 현장 목록 업데이트
        function updateSelectedSitesList() {
            const selectedCount = document.getElementById('selectedCount');
            const selectedSitesSection = document.getElementById('selectedSitesSection');
            const selectedSitesList = document.getElementById('selectedSitesList');
            
            selectedCount.textContent = selectedSites.length;
            
            if (selectedSites.length === 0) {
                selectedSitesSection.style.display = 'none';
                return;
            }
            
            selectedSitesSection.style.display = 'block';
            
            const html = selectedSites.map(site => `
                <div style="padding: var(--linear-spacing-sm); border-bottom: 1px solid var(--linear-border-secondary); display: flex; align-items: center; gap: var(--linear-spacing-sm);">
                    <button type="button" onclick="removeSelectedSite(${site.id})" 
                            style="background: var(--linear-error-primary); color: white; border: none; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 12px;">
                        ×
                    </button>
                    <div style="flex: 1;">
                        <div style="font-weight: var(--linear-font-weight-medium); color: var(--linear-text-primary);">
                            ${site.site_name}
                        </div>
                        <div style="font-size: var(--linear-text-sm); color: var(--linear-text-secondary);">
                            ${site.measurement_date} | ${site.measurer_name}
                        </div>
                    </div>
                </div>
            `).join('');
            
            selectedSitesList.innerHTML = html;
        }

        // 선택된 현장 제거
        function removeSelectedSite(measurementId) {
            selectedSites = selectedSites.filter(site => site.id !== measurementId);
            updateSelectedSitesList();
            
            // 검색 결과의 체크박스도 업데이트
            const checkbox = document.getElementById(`site_${measurementId}`);
            if (checkbox) {
                checkbox.checked = false;
            }
        }

        // 그룹 필터링 함수
        function filterGroups() {
            const searchName = document.getElementById('searchGroupName').value.toLowerCase();
            const memberCountFilter = document.getElementById('searchMemberCount').value;
            const dateFilter = document.getElementById('searchCreatedDate').value;
            
            allGroups.forEach(group => {
                let show = true;
                
                // 그룹명 필터
                if (searchName && !group.name.includes(searchName)) {
                    show = false;
                }
                
                // 멤버 수 필터
                if (memberCountFilter) {
                    const count = group.memberCount;
                    switch (memberCountFilter) {
                        case '0':
                            if (count !== 0) show = false;
                            break;
                        case '1-5':
                            if (count < 1 || count > 5) show = false;
                            break;
                        case '6-10':
                            if (count < 6 || count > 10) show = false;
                            break;
                        case '11+':
                            if (count < 11) show = false;
                            break;
                    }
                }
                
                // 생성일 필터
                if (dateFilter) {
                    const now = new Date();
                    const createdDate = group.createdDate;
                    const daysDiff = Math.floor((now - createdDate) / (1000 * 60 * 60 * 24));
                    
                    switch (dateFilter) {
                        case 'today':
                            if (daysDiff !== 0) show = false;
                            break;
                        case 'week':
                            if (daysDiff > 7) show = false;
                            break;
                        case 'month':
                            if (daysDiff > 30) show = false;
                            break;
                        case 'quarter':
                            if (daysDiff > 90) show = false;
                            break;
                    }
                }
                
                // 그룹 표시/숨김
                group.element.style.display = show ? 'block' : 'none';
            });
            
            // 결과 카운트 업데이트
            updateGroupCount();
        }

        // 검색 초기화 함수
        function resetGroupSearch() {
            document.getElementById('searchGroupName').value = '';
            document.getElementById('searchMemberCount').value = '';
            document.getElementById('searchCreatedDate').value = '';
            
            // 모든 그룹 표시
            allGroups.forEach(group => {
                group.element.style.display = 'block';
            });
            
            updateGroupCount();
        }

        // 그룹 개수 업데이트 함수
        function updateGroupCount() {
            const visibleGroups = allGroups.filter(group => group.element.style.display !== 'none');
            const groupCountElement = document.querySelector('.card-title');
            
            if (groupCountElement && visibleGroups.length !== allGroups.length) {
                const originalText = groupCountElement.textContent.replace(/ \(\d+개\)/, '');
                groupCountElement.textContent = `${originalText} (${visibleGroups.length}개)`;
            } else if (groupCountElement && visibleGroups.length === allGroups.length) {
                groupCountElement.textContent = groupCountElement.textContent.replace(/ \(\d+개\)/, '');
            }
        }

        // 그룹 선택
        function selectGroup(groupId) {
            currentGroupId = groupId;
            
            // 모든 그룹 카드에서 selected 클래스 제거
            document.querySelectorAll('.group-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // 선택된 그룹 카드에 selected 클래스 추가
            document.querySelector(`[data-group-id="${groupId}"]`).classList.add('selected');
            
            // 페이지 새로고침하여 해당 그룹의 현장 목록 표시
            window.location.href = `site_groups.php?group_id=${groupId}`;
        }

        // 그룹 생성 모달 표시
        function showCreateGroupModal() {
            // 먼저 모든 모달을 정리
            closeAllModals();
            
            // 선택된 현장 초기화
            selectedSites = [];
            
            // 잠시 후 새 모달 열기
            setTimeout(function() {
                const modalElement = document.getElementById('createGroupModal');
                if (modalElement) {
                    // 폼 초기화
                    document.getElementById('groupName').value = '';
                    document.getElementById('groupDescription').value = '';
                    document.getElementById('siteSearchInput').value = '';
                    document.getElementById('siteDateFilter').value = '';
                    
                    // 선택된 현장 목록 숨기기
                    document.getElementById('selectedSitesSection').style.display = 'none';
                    
                    // 모든 현장 검색 결과 표시
                    displaySearchResults(allMeasurements);
                    
                    // 모달 요소 정리
                    modalElement.classList.add('fade');
                    modalElement.style.display = 'flex';
                    modalElement.style.alignItems = 'center';
                    modalElement.style.justifyContent = 'center';
                    
                    const modal = new bootstrap.Modal(modalElement, {
                        backdrop: true,
                        keyboard: true,
                        focus: true
                    });
                    
                    modal.show();
                }
            }, 150);
        }

        // 그룹 생성 (현장과 함께)
        function createGroupWithSites() {
            const groupName = document.getElementById('groupName').value.trim();
            const groupDescription = document.getElementById('groupDescription').value.trim();
            
            if (!groupName) {
                Swal.fire('알림', '그룹명을 입력해주세요.', 'warning');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'create_group');
            formData.append('group_name', groupName);
            formData.append('group_description', groupDescription);
            
            // 선택된 현장 ID들 추가
            selectedSites.forEach(site => {
                formData.append('measurement_ids[]', site.id);
            });

            fetch('site_groups.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('성공', data.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('오류', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('오류', '그룹 생성 중 오류가 발생했습니다.', 'error');
            });
        }

        // 기존 그룹 생성 함수 (호환성 유지)
        function createGroup() {
            createGroupWithSites();
        }

        // 그룹 삭제
        function deleteGroup(groupId) {
            Swal.fire({
                title: '그룹 삭제',
                text: '이 그룹을 삭제하시겠습니까? 그룹에 속한 현장 정보도 함께 제거됩니다.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: '삭제',
                cancelButtonText: '취소'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('action', 'delete_group');
                    formData.append('group_id', groupId);

                    fetch('site_groups.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('삭제됨', data.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('오류', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('오류', '그룹 삭제 중 오류가 발생했습니다.', 'error');
                    });
                }
            });
        }

        // 현장 추가 모달 표시
        function showAddMeasurementsModal(groupId) {
            // 먼저 모든 모달을 정리
            closeAllModals();
            
            currentGroupId = groupId;
            
            // 잠시 후 새 모달 열기
            setTimeout(function() {
                const modalElement = document.getElementById('addMeasurementsModal');
                if (modalElement) {
                    // 현장 목록 로드
                    loadMeasurementsForSelection();
                    
                    // 모달 요소 정리
                    modalElement.classList.add('fade');
                    modalElement.style.display = 'flex';
                    modalElement.style.alignItems = 'center';
                    modalElement.style.justifyContent = 'center';
                    
                    const modal = new bootstrap.Modal(modalElement, {
                        backdrop: true,
                        keyboard: true,
                        focus: true
                    });
                    
                    modal.show();
                }
            }, 150);
        }

        // 현장 목록 로드 (현장 추가용)
        function loadMeasurementsForSelection() {
            const measurementList = document.getElementById('measurementList');
            const searchTerm = document.getElementById('measurementSearch').value.toLowerCase();
            
            // PHP에서 전달된 measurements 데이터 사용
            const measurements = <?= json_encode($measurements) ?>;
            
            let html = '';
            measurements.forEach(measurement => {
                if (measurement.site_name.toLowerCase().includes(searchTerm)) {
                    html += `
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" 
                                   value="${measurement.id}" id="measurement_${measurement.id}">
                            <label class="form-check-label" for="measurement_${measurement.id}">
                                <strong>${measurement.site_name}</strong>
                                <small class="text-muted d-block">
                                    ${measurement.measurement_date} | ${measurement.measurer_name}
                                    ${measurement.group_names ? ' | 그룹: ' + measurement.group_names : ''}
                                </small>
                            </label>
                        </div>
                    `;
                }
            });
            
            measurementList.innerHTML = html;
        }

        // 현장 추가
        function addSelectedMeasurements() {
            const checkboxes = document.querySelectorAll('#measurementList input[type="checkbox"]:checked');
            const measurementIds = Array.from(checkboxes).map(cb => cb.value);
            
            if (measurementIds.length === 0) {
                Swal.fire('알림', '추가할 현장을 선택해주세요.', 'info');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'add_measurements_to_group');
            formData.append('group_id', currentGroupId);
            measurementIds.forEach(id => formData.append('measurement_ids[]', id));

            fetch('site_groups.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('성공', data.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('오류', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('오류', '현장 추가 중 오류가 발생했습니다.', 'error');
            });
        }

        // 그룹에서 현장 제거
        function removeFromGroup(groupId, measurementId) {
            Swal.fire({
                title: '현장 제거',
                text: '이 현장을 그룹에서 제거하시겠습니까?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: '제거',
                cancelButtonText: '취소'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('action', 'remove_measurement_from_group');
                    formData.append('group_id', groupId);
                    formData.append('measurement_id', measurementId);

                    fetch('site_groups.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('제거됨', data.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('오류', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('오류', '현장 제거 중 오류가 발생했습니다.', 'error');
                    });
                }
            });
        }

        // 그룹 엑셀 내보내기
        function exportGroupToExcel(groupId) {
            // 그룹의 현장들을 선택하여 합쳐진 제작 데이터 엑셀 내보내기
            window.open(`result.php?group_export=${groupId}`, '_blank');
        }

        // 현장 검색
        document.getElementById('measurementSearch').addEventListener('input', loadMeasurementsForSelection);

        // 페이지 로드 시 현재 선택된 그룹 표시
        <?php if ($selected_group_id): ?>
            document.addEventListener('DOMContentLoaded', function() {
                // 모달 초기화 후 그룹 선택
                setTimeout(function() {
                    selectGroup(<?= $selected_group_id ?>);
                }, 200);
            });
        <?php endif; ?>
    </script>
</body>
</html>
