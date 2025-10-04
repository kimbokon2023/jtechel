<?php
require_once '../lib/mydb.php';
session_start();
$DB = 'jtechel';

// 실제 패널 개수 계산 함수
function calculateActualPanelCount($panel_data, $transom_data) {
    $panel_count = 9; // 기본 2-10번 패널

    // 1,11번 패널 확인 (각각 개별적으로 확인)
    if (!empty($panel_data) && $panel_data !== '{}') {
        $panel_json = json_decode($panel_data, true);
        if (is_array($panel_json)) {
            // 1번 패널 확인 (실제 측정 데이터가 있는지 확인)
            if (isset($panel_json['1']) && is_array($panel_json['1']) &&
                (isset($panel_json['1']['width']) || isset($panel_json['1']['height']) || isset($panel_json['1']['panelType']))) {
                $panel_count += 1;
            }
            // 11번 패널 확인 (실제 측정 데이터가 있는지 확인)
            if (isset($panel_json['11']) && is_array($panel_json['11']) &&
                (isset($panel_json['11']['width']) || isset($panel_json['11']['height']) || isset($panel_json['11']['panelType']))) {
                $panel_count += 1;
            }
        }
    }

    // 12번 transom 패널 확인 (transom 키 안의 width 필드가 실제로 존재하고 비어있지 않아야 함)
    if (!empty($transom_data) && $transom_data !== '{}') {
        $transom_json = json_decode($transom_data, true);
        if (is_array($transom_json)) {
            // transom 키 안의 데이터 확인
            if (isset($transom_json['transom']) && is_array($transom_json['transom'])) {
                $transom_info = $transom_json['transom'];
                // 중요한 필드들 중 하나라도 입력되어 있으면 transom이 있다고 판단
                if (!empty(trim($transom_info['width'] ?? '')) || 
                    !empty(trim($transom_info['height'] ?? '')) ||
                    !empty(trim($transom_info['transomPlateHeight'] ?? '')) ||
                    !empty(trim($transom_info['bottomDepthJD'] ?? '')) ||
                    !empty(trim($transom_info['wingValue'] ?? '')) ||
                    !empty(trim($transom_info['cpiDrillingWidth'] ?? '')) ||
                    !empty(trim($transom_info['cpiDrillingHeight'] ?? '')) ||
                    !empty(trim($transom_info['cpiDrillingHeightFromBottom'] ?? ''))) {
                    $panel_count += 1;
                }
            }
            // 이전 형식 호환성 (직접 width 필드가 있는 경우)
            else if (isset($transom_json['width']) && !empty(trim($transom_json['width']))) {
                $panel_count += 1;
            }
        }
    }

    return $panel_count;
}

// Check authentication
if (!isset($_SESSION["level"]) || $_SESSION["level"] > 8) {
    header("Location: ../login/login_form.php");
    exit;
}

// Initialize database connection
try {
    $pdo = db_connect();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Explicitly select database
    $pdo->exec("USE $DB");
} catch (PDOException $e) {
    error_log("Database connection failed in index.php: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OSEL 카 판넬 측정 시스템</title>
    
    <!-- Linear Theme CSS -->
    <link rel="stylesheet" href="../components/linear-theme.css">
    <link rel="stylesheet" href="../components/linear-components.css">
    
    <!-- Theme Toggle JavaScript -->
    <script src="../components/linear-theme-toggle.js"></script>
    
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
        }
        
        .dashboard-container {
            max-width: var(--linear-page-max-width);
            margin: 0 auto;
            padding: var(--linear-spacing-lg);
        }
        
        .dashboard-card {
            background-color: var(--linear-bg-primary);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-lg);
            padding: var(--linear-spacing-xl);
            box-shadow: var(--linear-shadow-low);
            transition: all var(--linear-transition-fast) var(--linear-ease-out);
            height: 100%;
            text-align: center;
            text-decoration: none;
            color: inherit;
        }
        
        .dashboard-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--linear-shadow-medium);
            text-decoration: none;
            color: inherit;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: var(--linear-spacing-xl);
            margin-bottom: var(--linear-spacing-3xl);
        }
        
        .card-icon {
            font-size: 3rem;
            margin-bottom: var(--linear-spacing-lg);
            color: var(--linear-brand-primary);
        }
        
        .card-title {
            font-size: var(--linear-text-title3);
            font-weight: var(--linear-font-weight-semibold);
            color: var(--linear-text-primary);
            margin-bottom: var(--linear-spacing-md);
        }
        
        .card-description {
            color: var(--linear-text-secondary);
            margin-bottom: var(--linear-spacing-xl);
            line-height: 1.6;
        }
        
        .recent-activity-table {
            background-color: var(--linear-bg-primary);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-lg);
            overflow: hidden;
        }
        
        .table-header {
            background-color: var(--linear-bg-secondary);
            padding: var(--linear-spacing-lg);
            border-bottom: 1px solid var(--linear-border-primary);
        }
        
        .table-content {
            padding: var(--linear-spacing-lg);
        }
        
        .activity-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .activity-table th {
            padding: var(--linear-spacing-md);
            text-align: left;
            font-weight: var(--linear-font-weight-medium);
            color: var(--linear-text-secondary);
            border-bottom: 1px solid var(--linear-border-secondary);
            vertical-align: middle;
        }

        .activity-table td {
            padding: var(--linear-spacing-md);
            border-bottom: 1px solid var(--linear-border-secondary);
            vertical-align: middle;
            height: auto;
            min-height: 60px;
        }

        .activity-table td:last-child {
            width: 80px;
            text-align: center;
        }

        .activity-table th:last-child {
            width: 80px;
            text-align: center;
        }
        
        .activity-table tbody tr {
            transition: background-color var(--linear-transition-fast);
            height: auto;
        }

        .activity-table tbody tr:hover {
            background-color: var(--linear-bg-secondary);
        }

        .activity-table tbody td {
            cursor: pointer;
        }

        .activity-table tbody td:last-child {
            cursor: default;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            background-color: var(--linear-brand-primary);
            color: white;
            border-radius: var(--linear-radius-sm);
            font-size: var(--linear-text-mini);
            font-weight: var(--linear-font-weight-medium);
        }
        
        .info-text {
            color: var(--linear-text-tertiary);
            font-size: var(--linear-text-small);
            margin-top: var(--linear-spacing-md);
        }
        
        .warning-text {
            color: var(--linear-color-orange);
        }
        
        .section-title {
            font-size: 2rem;
            font-weight: var(--linear-font-weight-semibold);
            color: var(--linear-text-primary);
            margin-bottom: var(--linear-spacing-xl);
            text-align: center;
        }
        
        .no-data {
            text-align: center;
            color: var(--linear-text-secondary);
            padding: var(--linear-spacing-xl);
        }

        /* Desktop Table View */
        .desktop-table-view {
            display: block;
        }

        /* Mobile Card Layout */
        .mobile-activity-cards {
            display: none !important;
        }

        .activity-card {
            background: var(--linear-bg-primary);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-lg);
            padding: var(--linear-spacing-lg);
            margin-bottom: var(--linear-spacing-md);
            cursor: pointer;
            transition: all var(--linear-transition-fast);
        }

        .activity-card:hover {
            box-shadow: var(--linear-shadow-medium);
            transform: translateY(-2px);
        }

        .activity-card-row1 {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: var(--linear-spacing-md);
            gap: var(--linear-spacing-sm);
        }

        .activity-card-title {
            flex: 1;
            min-width: 0;
        }

        .activity-card-site {
            font-size: var(--linear-text-body);
            font-weight: var(--linear-font-weight-semibold);
            color: var(--linear-text-primary);
            margin-bottom: 4px;
            word-break: break-all;
        }

        .activity-card-measurer {
            font-size: var(--linear-text-small);
            color: var(--linear-text-secondary);
        }

        .activity-card-status {
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 4px;
        }

        .activity-card-row2 {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: var(--linear-spacing-sm);
        }

        .activity-card-info {
            display: flex;
            align-items: center;
            gap: var(--linear-spacing-md);
            flex-wrap: wrap;
            flex: 1;
            min-width: 0;
        }

        .activity-card-info-item {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: var(--linear-text-small);
            color: var(--linear-text-secondary);
            white-space: nowrap;
        }

        .activity-card-info-item i {
            font-size: 12px;
            width: 14px;
            text-align: center;
        }

        .activity-card-car-size {
            font-family: var(--linear-font-mono, monospace);
            font-weight: var(--linear-font-weight-semibold, 600);
            font-size: var(--linear-text-mini);
        }

        /* Mobile tweaks */
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: var(--linear-spacing-lg);
            }

            .dashboard-container {
                padding: var(--linear-spacing-md);
            }

            .dashboard-card {
                padding: var(--linear-spacing-lg);
            }

            .card-icon {
                font-size: 2.4rem;
                margin-bottom: var(--linear-spacing-md);
            }

            /* Hide desktop table and show mobile cards on mobile */
            .desktop-table-view {
                display: none !important;
            }

            .mobile-activity-cards {
                display: block !important;
            }

            .section-title {
                font-size: 1.3rem !important;
                margin-bottom: 1.2rem !important;
                padding-left: 0.2rem;
                padding-right: 0.2rem;
                word-break: keep-all;
            }
        }

        /* Mobile landscape navbar adjustments */
        @media (max-width: 768px) and (orientation: landscape) {
            .linear-navbar {
                flex-wrap: wrap !important;
                min-height: 56px !important;
                padding-left: 0.5rem !important;
                padding-right: 0.5rem !important;
            }
            .linear-navbar .linear-navbar-brand {
                font-size: 1.1rem !important;
                min-width: 0;
                flex: 1 1 100%;
                margin-bottom: 0.2rem;
                white-space: normal;
                word-break: keep-all;
            }
            .linear-navbar .linear-navbar-actions {
                flex: 1 1 100%;
                justify-content: flex-end;
                margin-top: 0.2rem;
            }
            .linear-navbar .linear-btn,
            .linear-navbar .linear-navbar-actions > * {
                font-size: 0.95rem !important;
                min-width: 36px !important;
                min-height: 36px !important;
                margin-right: 0.3rem !important;
            }
        }
    </style>
</head>
<body>
    <?php
    // Linear 네비게이션 생성
    require_once '../components/LinearComponent.php';
    require_once '../components/LinearNavigation.php';

    // 모바일 가로 화면에서 네비게이션이 자연스럽게 보이도록 스타일 추가
    ?>
    <style>
        @media (max-width: 768px) and (orientation: landscape) {
            .linear-navbar {
                flex-wrap: wrap !important;
                min-height: 56px !important;
                padding-left: 0.5rem !important;
                padding-right: 0.5rem !important;
            }
            .linear-navbar .linear-navbar-brand {
                font-size: 1.1rem !important;
                min-width: 0;
                flex: 1 1 100%;
                margin-bottom: 0.2rem;
                white-space: normal;
                word-break: keep-all;
            }
            .linear-navbar .linear-navbar-actions {
                flex: 1 1 100%;
                justify-content: flex-end;
                margin-top: 0.2rem;
            }
            .linear-navbar .linear-btn,
            .linear-navbar .linear-navbar-actions > * {
                font-size: 0.95rem !important;
                min-width: 36px !important;
                min-height: 36px !important;
                margin-right: 0.3rem !important;
            }
        }
    </style>
    <?php
    $nav = LinearNavigation::withBrand(
        '<i class="bi bi-building"></i> OSEL',
        'index.php'
    )
    ->addAction('
        <button type="button" id="themeToggleBtn" class="linear-btn linear-btn-ghost linear-btn-sm"
                style="margin-right: 0.5rem; min-width: 40px; min-height: 40px;" title="테마 변경">
            <span id="themeIcon">🌙</span>
        </button>
    ')
    ->addAction('<span style="margin-right: 1rem; color: var(--linear-text-secondary); white-space:nowrap;">' . htmlspecialchars($_SESSION["name"]) . '님</span>')
    ->addAction('<a href="../login/logout.php" style="color: var(--linear-text-secondary); text-decoration: none; white-space:nowrap;">로그아웃</a>')
    ->fixed();

    echo $nav;
    ?>
    <div class="dashboard-container" style="margin-top: var(--linear-header-height);">
        
        <h1 class="section-title" style="font-size: 2.2rem; margin-bottom: 2rem; word-break: keep-all;">
            엘리베이터 카 판넬 측정 시스템
        </h1>
        <style>
            @media (max-width: 768px) {
                .section-title {
                    font-size: 1.3rem !important;
                    margin-bottom: 1.2rem !important;
                    padding-left: 0.2rem;
                    padding-right: 0.2rem;
                    word-break: keep-all;
                }
            }
        </style>
        
        <!-- Main Dashboard -->
        <div class="dashboard-grid">
            <!-- Panel Measurement -->
            <a href="panel_measurement.php" class="dashboard-card">
                <div class="card-icon">
                    <i class="bi bi-grid-3x3-gap"></i>
                </div>
                <h4 class="card-title">판넬 측정</h4>
                <p class="card-description">엘리베이터 카 판넬을 시각적으로 측정하고 검증</p>
                <?php 
                require_once '../components/LinearButton.php';
                echo LinearButton::primary('측정 시작')->addAttribute('style', 'pointer-events: none;');
                ?>
            </a>

            <!-- Site Management -->
            <div class="dashboard-card" style="cursor: default;">
                <div class="card-icon">
                    <i class="bi bi-building"></i>
                </div>
                <h4 class="card-title">현장 관리</h4>
                <p class="card-description">기존 현장 정보를 관리하고 측정 데이터를 확인</p>
                <div style="display: flex; gap: var(--linear-spacing-sm, 8px); justify-content: center; flex-wrap: wrap;">
                    <a href="site_list.php" style="text-decoration: none;">
                        <?php
                        echo LinearButton::primary('현장 리스트');
                        ?>
                    </a>
                    <a href="list.php" style="text-decoration: none;">
                        <?php
                        echo LinearButton::secondary('측정보기(엑셀변환)');
                        ?>
                    </a>
                </div>
            </div>

            <!-- Production Result -->
            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="bi bi-calculator"></i>
                </div>
                <h4 class="card-title">제작산출</h4>
                <p class="card-description">실측 데이터로 제작사이즈 생성</p>
                <div style="display: flex; gap: var(--linear-spacing-sm, 8px); justify-content: center; flex-wrap: wrap;">
                    <a href="result.php" style="text-decoration: none;">
                        <?php
                        echo LinearButton::primary('개별현장');
                        ?>
                    </a>
                    <a href="site_groups.php" style="text-decoration: none;">
                        <?php
                        echo LinearButton::secondary('현장 묶음(그룹)');
                        ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Activity Section -->
        <div class="recent-activity-table">
            <div class="table-header">
                <h5 style="margin: 0; font-size: var(--linear-text-title3); font-weight: var(--linear-font-weight-semibold); color: var(--linear-text-primary);">
                    <i class="bi bi-clock-history"></i> 최근 측정 활동
                </h5>
            </div>
            <div class="table-content">
                <?php
                try {
                    // First check if table exists
                    $table_check = $pdo->query("SHOW TABLES LIKE 'panel_measurements'");

                    if ($table_check->rowCount() == 0) {
                        echo '<div class="no-data">';
                        echo '<p class="warning-text">panel_measurements 테이블이 존재하지 않습니다.</p>';
                        echo '<p style="color: var(--linear-text-secondary); font-size: 0.9rem; margin-top: 8px;">패널 측정을 한 번 실행하면 테이블이 자동으로 생성됩니다.</p>';
                        echo '</div>';
                    } else {
                        // 최근 6개월 데이터 중 최대 10개만 가져오기 (최근 측정한 것이 상단)
                        $stmt = $pdo->prepare("
                            SELECT id, site_name, measurement_date, measurer_name,
                                   material_type, panel_data, transom_data, created_at, updated_at,
                                   car_inside_width, car_inside_depth, car_inside_height, elevator_count
                            FROM panel_measurements
                            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                            ORDER BY measurement_date DESC, created_at DESC
                            LIMIT 10
                        ");
                        $stmt->execute();
                        $recent_measurements = $stmt->fetchAll();

                        if ($recent_measurements) {
                            // 데이터 처리 함수
                            function processCardData($measurement) {
                                // 실제 패널 개수 계산 (이미 transom 포함됨)
                                $actual_panel_count = calculateActualPanelCount($measurement['panel_data'], $measurement['transom_data']);

                                // transom 존재 여부만 확인 (개수는 이미 actual_panel_count에 포함됨)
                                $has_transom = false;
                                if (!empty($measurement['transom_data']) && $measurement['transom_data'] !== '{}') {
                                    $transom_data = json_decode($measurement['transom_data'], true);
                                    if (is_array($transom_data)) {
                                        // transom 키 안의 데이터 확인
                                        if (isset($transom_data['transom']) && is_array($transom_data['transom'])) {
                                            $transom_info = $transom_data['transom'];
                                            // 중요한 필드들 중 하나라도 입력되어 있으면 transom이 있다고 판단
                                            if (!empty(trim($transom_info['width'] ?? '')) || 
                                                !empty(trim($transom_info['height'] ?? '')) ||
                                                !empty(trim($transom_info['transomPlateHeight'] ?? '')) ||
                                                !empty(trim($transom_info['bottomDepthJD'] ?? '')) ||
                                                !empty(trim($transom_info['wingValue'] ?? '')) ||
                                                !empty(trim($transom_info['cpiDrillingWidth'] ?? '')) ||
                                                !empty(trim($transom_info['cpiDrillingHeight'] ?? '')) ||
                                                !empty(trim($transom_info['cpiDrillingHeightFromBottom'] ?? ''))) {
                                                $has_transom = true;
                                            }
                                        }
                                        // 이전 형식 호환성 (직접 width 필드가 있는 경우)
                                        else if (isset($transom_data['width']) && !empty(trim($transom_data['width']))) {
                                            $has_transom = true;
                                        }
                                    }
                                }

                                return [
                                    'panel_count' => $actual_panel_count,
                                    'has_transom' => $has_transom,
                                    'total_panels' => $actual_panel_count, // 중복 계산 제거
                                    'last_modified' => !empty($measurement['updated_at']) ? $measurement['updated_at'] : $measurement['created_at']
                                ];
                            }

                            // PC Table View (데스크톱에서만 표시)
                            echo '<div class="desktop-table-view">';
                            echo '<table class="activity-table">';
                            echo '<thead><tr><th>현장명</th><th>측정자</th><th>측정일자</th><th>패널 정보</th><th>최근수정일시</th><th>CAR INSIDE</th><th>작업</th></tr></thead>';
                            echo '<tbody>';

                            foreach ($recent_measurements as $measurement) {
                                $data = processCardData($measurement);

                                echo '<tr>';
                                echo '<td style="color: var(--linear-text-primary); font-weight: var(--linear-font-weight-medium); cursor: pointer;" onclick="window.location.href=\'measurement_detail.php?id=' . $measurement['id'] . '\'">' . htmlspecialchars($measurement['site_name']) . '</td>';
                                echo '<td style="color: var(--linear-text-secondary); cursor: pointer;" onclick="window.location.href=\'measurement_detail.php?id=' . $measurement['id'] . '\'">' . htmlspecialchars($measurement['measurer_name']) . '</td>';
                                echo '<td style="color: var(--linear-text-secondary); cursor: pointer;" onclick="window.location.href=\'measurement_detail.php?id=' . $measurement['id'] . '\'">' . htmlspecialchars($measurement['measurement_date']) . '</td>';

                                // 패널 정보
                                echo '<td style="cursor: pointer;" onclick="window.location.href=\'measurement_detail.php?id=' . $measurement['id'] . '\'">';
                                if ($data['total_panels'] > 0) {
                                    // transom이 있으면 패널 개수에서 1개 빼고 표시
                                    $display_panel_count = $data['has_transom'] ? $data['total_panels'] - 1 : $data['total_panels'];
                                    echo '<span class="badge">' . $display_panel_count . '개</span>';
                                    if ($data['has_transom']) {
                                        echo '<div style="font-size: 0.8rem; color: var(--linear-brand-primary); margin-top: 2px;">+ Transom</div>';
                                    }
                                    if (!empty($measurement['material_type'])) {
                                        echo '<div style="font-size: 0.8rem; color: var(--linear-text-tertiary); margin-top: 2px;">' . htmlspecialchars($measurement['material_type']) . '</div>';
                                    }
                                } else {
                                    echo '<span style="color: var(--linear-text-tertiary);">정보 없음</span>';
                                }
                                echo '</td>';

                                // 최근수정일시
                                echo '<td style="color: var(--linear-text-secondary); cursor: pointer;" onclick="window.location.href=\'measurement_detail.php?id=' . $measurement['id'] . '\'">' . date('Y-m-d H:i', strtotime($data['last_modified'])) . '</td>';

                                // CAR INSIDE
                                echo '<td style="cursor: pointer;" onclick="window.location.href=\'measurement_detail.php?id=' . $measurement['id'] . '\'">';
                                if (!empty($measurement['car_inside_width']) && !empty($measurement['car_inside_depth']) && !empty($measurement['car_inside_height'])) {
                                    echo '<span style="font-family: var(--linear-font-mono, monospace); font-weight: var(--linear-font-weight-semibold, 600); font-size: var(--linear-text-small, 0.875rem);">';
                                    echo '<span style="color: var(--linear-brand-primary, #3182ce);">W</span>' . intval($measurement['car_inside_width']);
                                    echo ' <span style="color: var(--linear-success, #38a169);">D</span>' . intval($measurement['car_inside_depth']);
                                    echo ' <span style="color: var(--linear-warning, #dd6b20);">H</span>' . intval($measurement['car_inside_height']);
                                    // 엘리베이터 대수 추가
                                    $elevator_count = !empty($measurement['elevator_count']) ? intval($measurement['elevator_count']) : 1;
                                    echo ' ' . $elevator_count . '대';
                                    echo '</span>';
                                } else {
                                    echo '<span style="color: var(--linear-text-tertiary);">정보 없음</span>';
                                }
                                echo '</td>';

                                // 작업 버튼
                                echo '<td style="text-align: center; vertical-align: middle;">';
                                echo '<button onclick="editMeasurement(' . $measurement['id'] . '); event.stopPropagation();" ';
                                echo 'style="background-color: var(--linear-brand-primary); color: white; border: none; border-radius: var(--linear-radius-sm); ';
                                echo 'padding: 6px 10px; font-size: var(--linear-text-mini); cursor: pointer; transition: background-color 0.2s; ';
                                echo 'white-space: nowrap; display: inline-flex; align-items: center; gap: 4px;" ';
                                echo 'title="측정 데이터 수정">';
                                echo '<i class="bi bi-pencil" style="font-size: 12px;"></i>';
                                echo '<span>수정</span>';
                                echo '</button>';
                                echo '</td>';
                                echo '</tr>';
                            }

                            echo '</tbody></table>';
                            echo '</div>';

                            // Mobile Card View (모바일에서만 표시)
                            echo '<div class="mobile-activity-cards">';
                            foreach ($recent_measurements as $measurement) {
                                $data = processCardData($measurement);

                                echo '<div class="activity-card" onclick="window.location.href=\'measurement_detail.php?id=' . $measurement['id'] . '\'">';

                                // Row 1: 현장명 + 측정자, 패널 정보
                                echo '<div class="activity-card-row1">';
                                echo '<div class="activity-card-title">';
                                echo '<div class="activity-card-site">' . htmlspecialchars($measurement['site_name']) . '</div>';
                                echo '<div class="activity-card-measurer">' . htmlspecialchars($measurement['measurer_name']) . '</div>';
                                echo '</div>';
                                echo '<div class="activity-card-status">';
                                if ($data['total_panels'] > 0) {
                                    // transom이 있으면 패널 개수에서 1개 빼고 표시
                                    $display_panel_count = $data['has_transom'] ? $data['total_panels'] - 1 : $data['total_panels'];
                                    echo '<span class="badge">' . $display_panel_count . '개</span>';
                                    if ($data['has_transom']) {
                                        echo '<div style="font-size: 0.7rem; color: var(--linear-brand-primary); margin-top: 2px; text-align: right;">+ Transom</div>';
                                    }
                                } else {
                                    echo '<span class="badge" style="background-color: var(--linear-text-tertiary);">0개</span>';
                                }
                                echo '</div>';
                                echo '</div>';

                                // Row 2: 정보들 + 수정 버튼
                                echo '<div class="activity-card-row2">';
                                echo '<div class="activity-card-info">';

                                // 측정일자
                                echo '<div class="activity-card-info-item">';
                                echo '<i class="bi bi-calendar3"></i>';
                                echo '<span>' . htmlspecialchars($measurement['measurement_date']) . '</span>';
                                echo '</div>';

                                // 최근수정일시
                                echo '<div class="activity-card-info-item">';
                                echo '<i class="bi bi-clock"></i>';
                                echo '<span>' . date('m-d H:i', strtotime($data['last_modified'])) . '</span>';
                                echo '</div>';

                                // CAR INSIDE
                                if (!empty($measurement['car_inside_width']) && !empty($measurement['car_inside_depth']) && !empty($measurement['car_inside_height'])) {
                                    echo '<div class="activity-card-info-item">';
                                    echo '<i class="bi bi-rulers"></i>';
                                    echo '<span class="activity-card-car-size">';
                                    echo 'W' . intval($measurement['car_inside_width']) . ' D' . intval($measurement['car_inside_depth']) . ' H' . intval($measurement['car_inside_height']);
                                    // 엘리베이터 대수 추가
                                    $elevator_count = !empty($measurement['elevator_count']) ? intval($measurement['elevator_count']) : 1;
                                    echo ' ' . $elevator_count . '대';
                                    echo '</span>';
                                    echo '</div>';
                                }

                                echo '</div>';

                                // 수정 버튼
                                echo '<div>';
                                echo '<button onclick="editMeasurement(' . $measurement['id'] . '); event.stopPropagation();" ';
                                echo 'style="background-color: var(--linear-brand-primary); color: white; border: none; border-radius: var(--linear-radius-sm); ';
                                echo 'padding: 8px 12px; font-size: var(--linear-text-mini); cursor: pointer;" ';
                                echo 'title="측정 데이터 수정">';
                                echo '<i class="bi bi-pencil"></i> 수정';
                                echo '</button>';
                                echo '</div>';

                                echo '</div>';
                                echo '</div>';
                            }
                            echo '</div>';

                            echo '<div class="info-text">';
                            echo '<i class="bi bi-info-circle"></i> 항목을 클릭하여 상세 정보를 확인할 수 있습니다.';
                            echo '</div>';
                        } else {
                            echo '<div class="no-data">';
                            echo '<p>아직 측정 데이터가 없습니다. 새로운 측정을 시작해보세요.</p>';
                            echo '</div>';
                        }
                    }
                } catch (PDOException $e) {
                    echo '<div class="no-data">';
                    echo '<p class="warning-text">데이터를 불러오는 중 오류가 발생했습니다: ' . htmlspecialchars($e->getMessage()) . '</p>';
                    echo '<p style="color: var(--linear-text-secondary); font-size: 0.9rem; margin-top: 8px;">panel_measurements 테이블이 존재하지 않을 수 있습니다.</p>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Theme Toggle Implementation -->
    <script>
        // 측정 데이터 수정 함수
        function editMeasurement(measurementId) {
            // panel_measurement.php로 수정 모드로 이동 (고유번호 사용)
            const url = 'panel_measurement.php?edit=' + measurementId;
            window.location.href = url;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const themeToggleBtn = document.getElementById('themeToggleBtn');
            const themeIcon = document.getElementById('themeIcon');
            
            if (!themeToggleBtn || !themeIcon) return;
            
            // 테마 상태 관리
            let currentTheme = localStorage.getItem('linear-theme') || 'auto';
            
            // 아이콘 업데이트 함수
            function updateThemeIcon() {
                const icons = {
                    'light': '☀️',
                    'dark': '🌙',
                    'auto': '⚙️'
                };
                themeIcon.textContent = icons[currentTheme] || '⚙️';
                
                // title 업데이트
                const titles = {
                    'light': '라이트 모드 (다크 모드로 변경)',
                    'dark': '다크 모드 (자동 모드로 변경)', 
                    'auto': '자동 모드 (라이트 모드로 변경)'
                };
                themeToggleBtn.title = titles[currentTheme] || '테마 변경';
            }
            
            // 테마 적용 함수
            function applyTheme(theme) {
                if (theme === 'auto') {
                    document.documentElement.removeAttribute('data-theme');
                } else {
                    document.documentElement.setAttribute('data-theme', theme);
                }
                localStorage.setItem('linear-theme', theme);
                currentTheme = theme;
                updateThemeIcon();
            }
            
            // 초기 테마 적용
            applyTheme(currentTheme); 
            
            // 버튼 클릭 이벤트
            themeToggleBtn.addEventListener('click', function() {
                const themeOrder = ['light', 'dark', 'auto'];
                const currentIndex = themeOrder.indexOf(currentTheme);
                const nextIndex = (currentIndex + 1) % themeOrder.length;
                applyTheme(themeOrder[nextIndex]);
            });
            
            // 시스템 테마 변경 감지 (auto 모드일 때)
            if (window.matchMedia) {
                const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
                darkModeQuery.addEventListener('change', function() {
                    if (currentTheme === 'auto') {
                        // auto 모드에서는 data-theme 속성을 제거하여 CSS의 @media 규칙이 적용되도록 함
                        document.documentElement.removeAttribute('data-theme');
                    }
                });
            }
        });
    </script>
</body>
</html>