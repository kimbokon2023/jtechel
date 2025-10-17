<?php
require_once '../lib/mydb.php';
session_start();
$DB = 'jtechel';

// 실제 패널 개수 계산 함수
function calculateActualPanelCount($panel_data, $transom_data, $car_structure = '일반형') {
    // 기본 패널 개수: 일반형=9개(2-10번), 관통형=6개(2,3,4,8,9,10번, 5,6,7번 제외)
    $panel_count = ($car_structure === '관통형') ? 6 : 9;

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

    // 12번 transom 패널 확인 (transom_data가 존재하면 무조건 카운트)
    if (!empty($transom_data) && $transom_data !== '{}') {
        $transom_json = json_decode($transom_data, true);
        if (is_array($transom_json) && !empty($transom_json)) {
            $panel_count += 1;
        }
    }
    
    return $panel_count;
}

// 현장명에서 엘리베이터 대수 추출 함수
function extractElevatorCount($site_name) {
    // 패턴 1: #1~#14 형태 (1부터 14까지)
    if (preg_match('/#(\d+)~#(\d+)/', $site_name, $matches)) {
        $start = intval($matches[1]);
        $end = intval($matches[2]);
        return $end - $start + 1;
    }
    
    // 패턴 2: #15(장애인용) 형태 (단일 엘리베이터)
    if (preg_match('/#(\d+)/', $site_name, $matches)) {
        return 1;
    }
    
    // 패턴 3: 숫자만 있는 경우
    if (preg_match('/(\d+)대/', $site_name, $matches)) {
        return intval($matches[1]);
    }
    
    // 기본값: 1대
    return 1;
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
    $pdo->exec("USE $DB");
} catch (PDOException $e) {
    error_log("Database connection failed in list.php: " . $e->getMessage());
    die("데이터베이스 연결에 실패했습니다.");
}

// Search parameters
$search_site = $_GET['search_site'] ?? '';
// 기본값: 시작일은 6개월 전, 종료일은 오늘
$search_date_from = $_GET['search_date_from'] ?? date('Y-m-d', strtotime('-6 months'));
$search_date_to = $_GET['search_date_to'] ?? date('Y-m-d');
$search_measurer = $_GET['search_measurer'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build search query
$where_conditions = [];
$params = [];

if (!empty($search_site)) {
    $where_conditions[] = "site_name LIKE ?";
    $params[] = "%{$search_site}%";
}

if (!empty($search_date_from)) {
    $where_conditions[] = "measurement_date >= ?";
    $params[] = $search_date_from;
}

if (!empty($search_date_to)) {
    $where_conditions[] = "measurement_date <= ?";
    $params[] = $search_date_to;
}

if (!empty($search_measurer)) {
    $where_conditions[] = "measurer_name LIKE ?";
    $params[] = "%{$search_measurer}%";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

try {
    // First check if table exists
    $table_check = $pdo->query("SHOW TABLES LIKE 'panel_measurements'");

    if ($table_check->rowCount() == 0) {
        $measurements = [];
        $total_records = 0;
        $total_pages = 0;
        $error_message = 'panel_measurements 테이블이 존재하지 않습니다. 패널 측정을 먼저 실행해주세요.';
    } else {
        // Get total count for pagination
        $count_query = "
            SELECT COUNT(*) as total
            FROM panel_measurements
            $where_clause
        ";
        $count_stmt = $pdo->prepare($count_query);
        $count_stmt->execute($params);
        $total_records = $count_stmt->fetch()['total'];
        $total_pages = ceil($total_records / $per_page);

        // Get measurement data
        $query = "
            SELECT
                id,
                site_name,
                measurement_date,
                measurer_name,
                measurer_id,
                car_inside_width,
                car_inside_depth,
                car_inside_height,
                car_structure,
                material_type,
                material_thickness,
                panel_data,
                transom_data,
                notes,
                created_at,
                updated_at
            FROM panel_measurements
            $where_clause
            ORDER BY measurement_date DESC, id DESC
            LIMIT ? OFFSET ?
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute(array_merge($params, [$per_page, $offset]));
        $measurements = $stmt->fetchAll();

        // 각 측정 데이터의 실제 패널 개수 계산 및 엘리베이터 대수 추가
        foreach ($measurements as &$measurement) {
            $car_structure = $measurement['car_structure'] ?? '일반형';
            $measurement['actual_panel_count'] = calculateActualPanelCount($measurement['panel_data'], $measurement['transom_data'], $car_structure);
            $measurement['elevator_count'] = extractElevatorCount($measurement['site_name']);
        }
        unset($measurement); // 참조 제거
    }

} catch (PDOException $e) {
    $measurements = [];
    $total_records = 0;
    $total_pages = 0;
    $error_message = '데이터베이스 오류가 발생했습니다: ' . $e->getMessage();
}

// Get unique measurers for filter
try {
    if ($table_check && $table_check->rowCount() > 0) {
        $measurer_stmt = $pdo->prepare("SELECT DISTINCT measurer_name FROM panel_measurements WHERE measurer_name IS NOT NULL AND measurer_name != '' ORDER BY measurer_name");
        $measurer_stmt->execute();
        $measurers = $measurer_stmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $measurers = [];
    }
} catch (PDOException $e) {
    $measurers = [];
}

// echo '<pre>';
// print_r($measurements);
// echo '</pre>';

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">    
    <title>EL 판넬 측정</title>
    
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
        }
        
        .list-container {
            max-width: var(--linear-page-max-width);
            margin: 0 auto;
            padding: var(--linear-spacing-lg);
        }
        
        .search-container {
            background-color: var(--linear-bg-primary);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-lg);
            padding: var(--linear-spacing-2xl);
            box-shadow: var(--linear-shadow-low);
            margin-bottom: var(--linear-spacing-xl);
        }
        
        .table-container {
            background-color: var(--linear-bg-primary);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-lg);
            padding: var(--linear-spacing-2xl);
            box-shadow: var(--linear-shadow-low);
        }
        
        .measurement-row {
            cursor: pointer;
            transition: background-color var(--linear-transition-fast);
            border-bottom: 1px solid var(--linear-border-secondary);
        }
        
        .measurement-row:hover {
            background-color: var(--linear-bg-secondary);
        }
        
        .measurement-row:last-child {
            border-bottom: none;
        }
        
        .panel-badges .badge {
            margin-right: var(--linear-spacing-xs);
            margin-bottom: var(--linear-spacing-xs);
            background-color: var(--linear-brand-primary);
            color: white;
            padding: 2px 8px;
            border-radius: var(--linear-radius-sm);
            font-size: var(--linear-text-mini);
            font-weight: var(--linear-font-weight-medium);
        }
        .panel-badges {
            display: flex;
            flex-wrap: wrap;
            gap: var(--linear-spacing-xs);
            max-width: 100%;
        }
        
        .search-form {
            background-color: var(--linear-bg-secondary);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-lg);
            padding: var(--linear-spacing-lg);
        }
        .search-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 2fr 1fr;
            gap: var(--linear-spacing-md);
            align-items: end;
        }
        
        .search-form input,
        .search-form select {
            padding: var(--linear-spacing-sm);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-md);
            background-color: var(--linear-bg-primary);
            color: var(--linear-text-primary);
            font-size: var(--linear-text-body);
        }
        
        .search-form input:focus,
        .search-form select:focus {
            outline: 2px solid var(--linear-focus-ring);
            outline-offset: -2px;
            border-color: var(--linear-brand-primary);
        }
        
        .search-form label {
            color: var(--linear-text-primary);
            font-size: var(--linear-text-small);
            font-weight: var(--linear-font-weight-medium);
            margin-bottom: var(--linear-spacing-xs);
        }
        
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: var(--linear-spacing-md);
        }
        
        .data-table th {
            background-color: var(--linear-bg-secondary);
            color: var(--linear-text-primary);
            padding: var(--linear-spacing-md);
            text-align: left;
            font-weight: var(--linear-font-weight-medium);
            border-bottom: 1px solid var(--linear-border-primary);
            font-size: var(--linear-text-small);
        }
        
        .data-table td {
            padding: var(--linear-spacing-md);
            border-bottom: 1px solid var(--linear-border-secondary);
            color: var(--linear-text-primary);
        }
        
        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: var(--linear-spacing-lg);
        }
        
        .section-header i {
            color: var(--linear-brand-primary);
            margin-right: var(--linear-spacing-sm);
            font-size: 1.5rem;
        }
        
        .section-header h5 {
            color: var(--linear-text-primary);
            font-size: var(--linear-text-title3);
            font-weight: var(--linear-font-weight-semibold);
            margin: 0;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: var(--linear-font-weight-semibold);
            color: var(--linear-text-primary);
            margin-bottom: var(--linear-spacing-xl);
        }
        
        /* panel_measurement-style breadcrumb */
        .pm-breadcrumb {
            display: flex;
            align-items: center;
            gap: var(--linear-spacing-sm);
            margin-bottom: var(--linear-spacing-lg);
            font-size: var(--linear-text-small);
        }
        .pm-breadcrumb a {
            color: var(--linear-brand-primary);
            text-decoration: none;
        }
        .pm-breadcrumb a:hover { text-decoration: underline; }
        .pm-breadcrumb .sep { color: var(--linear-text-tertiary); }
        
        .page-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: var(--linear-spacing-xl);
        }
        
        .breadcrumb {
            background: transparent;
            padding: 0;
            margin-bottom: var(--linear-spacing-md);
        }
        
        .breadcrumb-item {
            color: var(--linear-text-secondary);
            font-size: var(--linear-text-small);
        }
        
        .breadcrumb-item a {
            color: var(--linear-brand-primary);
            text-decoration: none;
        }
        
        .breadcrumb-item a:hover {
            color: var(--linear-brand-primary-hover);
        }
        
        .breadcrumb-item.active {
            color: var(--linear-text-primary);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: var(--linear-spacing-xs);
            margin-top: var(--linear-spacing-xl);
        }
        
        .page-link {
            padding: var(--linear-spacing-sm) var(--linear-spacing-md);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-md);
            background-color: var(--linear-bg-primary);
            color: var(--linear-text-primary);
            text-decoration: none;
            transition: all var(--linear-transition-fast);
        }
        
        .page-link:hover {
            background-color: var(--linear-bg-secondary);
            border-color: var(--linear-brand-primary);
            color: var(--linear-brand-primary);
        }
        
        .page-item.active .page-link {
            background-color: var(--linear-brand-primary);
            border-color: var(--linear-brand-primary);
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: var(--linear-spacing-3xl);
            color: var(--linear-text-secondary);
        }
        
        .empty-state i {
            font-size: 3rem;
            color: var(--linear-text-tertiary);
            margin-bottom: var(--linear-spacing-md);
        }
        
        .success-badge {
            background-color: var(--linear-color-green);
            color: white;
            padding: 2px 8px;
            border-radius: var(--linear-radius-sm);
            font-size: var(--linear-text-mini);
            font-weight: var(--linear-font-weight-medium);
        }
        
        @media (max-width: 768px) {
            .list-container {
                padding: var(--linear-spacing-md);
            }
            
            .search-container,
            .table-container {
                padding: var(--linear-spacing-lg);
            }
            .search-grid {
                grid-template-columns: 1fr 1fr;
            }
            .search-form input,
            .search-form select {
                width: 100%;
                box-sizing: border-box;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: var(--linear-spacing-md);
            }
            
            .data-table {
                font-size: var(--linear-text-small);
            }
            
            .data-table th,
            .data-table td {
                padding: var(--linear-spacing-sm);
            }
            /* ensure cards and tables never overflow viewport */
            .table-container { overflow-x: auto; }
        }
        @media (max-width: 576px) {
            .search-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php
    // Linear 네비게이션 생성
    require_once '../components/LinearComponent.php';
    require_once '../components/LinearNavigation.php';
    
    $nav = LinearNavigation::withBrand(
        '<div style="display: flex; align-items: center;">
            <a href="../mywork/index.php" style="color: inherit; text-decoration: none; display: flex; align-items: center; margin-right: 1rem;" title="홈으로 이동">
                <i class="bi bi-house" style="font-size: 2rem;"></i>
            </a>
            <div style="display: flex; align-items: center;">
                <a href="index.php" style="color: inherit; text-decoration: none; display: flex; align-items: center;">
                    <i class="bi bi-building"></i> OSEL
                </a>
            </div>
        </div>', 
        'index.php'
    )
    ->addAction('
        <button type="button" id="themeToggleBtn" class="linear-btn linear-btn-ghost linear-btn-sm" 
                style="margin-right: 0.5rem; min-width: 40px; min-height: 40px;" title="테마 변경">
            <span id="themeIcon">🌙</span>
        </button>
    ')
    ->addAction('<span style="margin-right: 1rem; color: var(--linear-text-secondary);"> ' . htmlspecialchars($_SESSION["name"]) . '님</span>')
    ->addAction('<a href="../login/logout.php" style="color: var(--linear-text-secondary); text-decoration: none;">로그아웃</a>')
    ->fixed();
    
    echo $nav;
    ?>
    
    <div class="list-container" style="margin-top: var(--linear-header-height);">
        <!-- Breadcrumb (panel_measurement style) -->
        <nav class="pm-breadcrumb">
            <a href="index.php">대시보드</a>
            <span class="sep">/</span>
            <span>판넬 측정</span>
        </nav>
        
        <h2 class="page-title">
            <i class="bi bi-grid-3x3-gap"></i> EL 판넬 측정
        </h2>
        
        <div style="display:flex; justify-content:flex-end; margin-bottom: var(--linear-spacing-lg);">
            <?php
            require_once '../components/LinearButton.php';
            echo '<a href="panel_measurement.php" style="text-decoration: none;">' .
                 LinearButton::primary('<i class="bi bi-plus-circle"></i> 새 측정') .
                 '</a>';
            ?>
        </div>

        <!-- Search Form -->
        <div class="search-container">
            <div class="section-header">
                <i class="bi bi-search"></i>
                <h5>검색 조건</h5>
            </div>
            
            <form method="GET" class="search-form">
                <div class="search-grid">
                    <div>
                        <label for="searchSite">현장명</label>
                        <input type="text" id="searchSite" name="search_site" 
                               value="<?= htmlspecialchars($search_site) ?>" placeholder="현장명 검색">
                    </div>
                    <div>
                        <label for="searchDateFrom">측정일 (시작)</label>
                        <input type="date" id="searchDateFrom" name="search_date_from" 
                               value="<?= htmlspecialchars($search_date_from) ?>">
                    </div>
                    <div>
                        <label for="searchDateTo">측정일 (종료)</label>
                        <input type="date" id="searchDateTo" name="search_date_to" 
                               value="<?= htmlspecialchars($search_date_to) ?>">
                    </div>
                    <div>
                        <label for="searchMeasurer">측정자</label>
                        <select id="searchMeasurer" name="search_measurer">
                            <option value="">전체 측정자</option>
                            <?php foreach ($measurers as $measurer): ?>
                                <option value="<?= htmlspecialchars($measurer) ?>" 
                                        <?= $search_measurer === $measurer ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($measurer) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: var(--linear-spacing-xs);">
                        <?php
                        echo LinearButton::primary('<i class="bi bi-search"></i> 검색')
                            ->addAttribute('type', 'submit')
                            ->addAttribute('style', 'width: 100%;');
                        
                        echo '<a href="list.php" style="text-decoration: none;">' .
                             LinearButton::outline('초기화')
                                ->size('sm')
                                ->addAttribute('style', 'width: 100%;') .
                             '</a>';
                        ?>
                    </div>
                </div>
            </form>
        </div>


        <!-- Results Table -->
        <div class="table-container">
            <?php if (isset($error_message)): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error_message) ?>
                </div>
            <?php elseif (empty($measurements)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 3rem; color: #dee2e6;"></i>
                    <h5 class="text-muted mt-3">검색 결과가 없습니다</h5>
                    <p class="text-muted">다른 조건으로 검색해보시거나 새로운 측정을 시작해보세요.</p>
                    <a href="panel_measurement.php" class="btn btn-primary">새 측정 시작</a>
                </div>
            <?php else: ?>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--linear-spacing-md);">
                    <h5 style="margin: 0; color: var(--linear-text-primary); font-size: var(--linear-text-title3); font-weight: var(--linear-font-weight-semibold);">
                        <i class="bi bi-table" style="color: var(--linear-brand-primary); margin-right: var(--linear-spacing-xs);"></i> 측정 결과 
                        <small style="color: var(--linear-text-secondary);">(총 <?= number_format($total_records) ?>건)</small>
                    </h5>
                    <div>
                        <?php
                        echo LinearButton::outline('<i class="bi bi-file-earmark-excel"></i> 체크 엑셀 내보내기')
                            ->size('sm')
                            ->addAttribute('id', 'exportBtn')
                            ->addAttribute('onclick', 'exportSelectedToExcel()')
                            ->addAttribute('disabled', 'true');
                        ?>
                    </div>
                </div>
                
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" id="selectAll" style="transform: scale(1.2);" title="전체 선택/해제">
                                </th>
                                <th>현장명/측정자</th>
                                <th>측정일</th>
                                <th>카 구조</th>
                                <th>카치수/대수/재질</th>
                                <th>측정 판넬</th>
                                <th>판넬</th>
                                <th>작업</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($measurements as $measurement):
                                // Parse JSON data to count panels
                                // 실제 패널 개수 사용 (이미 계산됨)
                                $actual_panel_count = $measurement['actual_panel_count'];

                                // 패널 리스트 생성 (표시용)
                                $panel_list = [];
                                $has_transom = false;

                                if (!empty($measurement['panel_data'])) {
                                    $panel_data = json_decode($measurement['panel_data'], true);
                                    if ($panel_data && is_array($panel_data)) {
                                        $panel_list = array_keys($panel_data);
                                    }
                                }

                                if (!empty($measurement['transom_data']) && $measurement['transom_data'] !== '{}') {
                                    $transom_data = json_decode($measurement['transom_data'], true);
                                    if (is_array($transom_data) && !empty($transom_data)) {
                                        $has_transom = true;
                                        $panel_list[] = 'T';
                                    }
                                }

                                // 총 패널 수는 이미 계산된 actual_panel_count 사용
                                $total_panels = $actual_panel_count;
                            ?>
                            <tr class="measurement-row" onclick="viewMeasurement(<?= $measurement['id'] ?>)">
                                <td onclick="event.stopPropagation();">
                                    <input type="checkbox" class="row-checkbox" value="<?= $measurement['id'] ?>" style="transform: scale(1.2);">
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($measurement['site_name']) ?></strong>
                                    <small class="text-muted d-block"><?= htmlspecialchars($measurement['measurer_name']) ?></small>
                                </td>
                                <td><?= date('Y-m-d', strtotime($measurement['measurement_date'])) ?></td>
                                <td style="text-align: center;">
                                    <?php 
                                    $car_structure = $measurement['car_structure'] ?? '일반형';
                                    $car_structure_color = ($car_structure === '관통형') ? '#dc2626' : '#059669';
                                    $car_structure_bg = ($car_structure === '관통형') ? '#fef2f2' : '#dcfce7';
                                    ?>
                                    <span style="display: inline-flex; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; 
                                          background: <?= $car_structure_bg ?>; color: <?= $car_structure_color ?>;">
                                        <?= htmlspecialchars($car_structure) ?>
                                    </span>
                                </td>
                                <td>
                                    <div><strong>W<?= $measurement['car_inside_width'] ?>D<?= $measurement['car_inside_depth'] ?>H<?= $measurement['car_inside_height'] ?></strong></div>
                                    <div><strong><?= $measurement['elevator_count'] ?>대</strong></div>
                                    <?php if (!empty($measurement['material_type'])): ?>
                                        <small class="text-muted"><?= htmlspecialchars($measurement['material_type']) ?>
                                        <?php if (!empty($measurement['material_thickness'])): ?>
                                            t<?= $measurement['material_thickness'] ?>
                                        <?php endif; ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($total_panels > 0): ?>
                                        <div class="panel-badges">
                                            <?php
                                            // Panel data for detailed display
                                            $panel_data_decoded = [];
                                            if (!empty($measurement['panel_data'])) {
                                                $panel_data_decoded = json_decode($measurement['panel_data'], true) ?: [];
                                            }

                                            foreach ($panel_list as $panel_num):
                                                if ($panel_num === 'T') {
                                                    echo '<span class="badge" style="background-color: var(--linear-color-purple);">T</span>';
                                                } elseif (($panel_num === '1' || $panel_num === '11') && isset($panel_data_decoded[$panel_num]['panelType'])) {
                                                    $panelType = $panel_data_decoded[$panel_num]['panelType'];
                                                    $typeDisplay = $panelType === '일체형' ? '일체' : ($panelType === '분리형' ? '분리' : '');
                                                    echo '<span class="badge" title="' . htmlspecialchars($panelType) . '">' . $panel_num . ($typeDisplay ? '(' . $typeDisplay . ')' : '') . '</span>';
                                                } else {
                                                    echo '<span class="badge">' . $panel_num . '</span>';
                                                }
                                            endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: var(--linear-text-tertiary);">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($total_panels > 0): ?>
                                        <span class="success-badge"><?= $total_panels ?></span>
                                    <?php else: ?>
                                        <span style="color: var(--linear-text-tertiary);">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: var(--linear-spacing-xs);">
                                        <?php
                                        echo LinearButton::ghost('<i class="bi bi-pencil"></i>')
                                            ->size('sm')
                                            ->addAttribute('onclick', "event.stopPropagation(); editMeasurement({$measurement['id']})")
                                            ->addAttribute('title', '수정');

                                        echo LinearButton::ghost('<i class="bi bi-trash" style="color: var(--linear-color-red);"></i>')
                                            ->size('sm')
                                            ->addAttribute('onclick', "event.stopPropagation(); deleteMeasurement({$measurement['id']}, '" . htmlspecialchars($measurement['site_name'], ENT_QUOTES) . "', '{$measurement['measurement_date']}')")
                                            ->addAttribute('title', '삭제');
                                        ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="측정 목록 페이지네이션">
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li style="list-style: none;">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <li style="list-style: none; <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li style="list-style: none;">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
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
        
        function viewMeasurement(measurementId) {
            // Quick view - could redirect to a dedicated view page
            viewDetails(measurementId);
        }

        function viewDetails(measurementId) {
            // 상세 페이지로 이동
            window.location.href = `measurement_detail.php?id=${measurementId}`;
        }

        function editMeasurement(measurementId) {
            window.location.href = `panel_measurement.php?edit=${measurementId}`;
        }

        function deleteMeasurement(measurementId, siteName, measurementDate) {
            Swal.fire({
                title: '측정 데이터 삭제',
                html: `<p>다음 측정 데이터를 삭제하시겠습니까?</p>
                       <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; margin: 16px 0; border-left: 4px solid #dc3545;">
                           <strong>현장명:</strong> ${siteName}<br>
                           <strong>측정일:</strong> ${measurementDate}<br>
                           <strong>ID:</strong> ${measurementId}
                       </div>
                       <p style="color: #dc3545; font-weight: 500;">⚠️ 이 작업은 되돌릴 수 없습니다.</p>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bi bi-trash"></i> 삭제',
                cancelButtonText: '<i class="bi bi-x-lg"></i> 취소',
                reverseButtons: true,
                focusCancel: true,
                didOpen: () => {
                    // Register SweetAlert2 modal for mobile handler
                    if (window.mobileModalHandler) {
                        window.mobileModalHandler.registerModalOpen({
                            id: `swal_delete_${measurementId}`,
                            type: 'swal',
                            closeCallback: () => {
                                if (Swal.isVisible()) {
                                    Swal.close();
                                }
                            }
                        });
                    }
                },
                willClose: () => {
                    // Register modal close when SweetAlert2 closes
                    if (window.mobileModalHandler) {
                        window.mobileModalHandler.registerModalClose(`swal_delete_${measurementId}`);
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Loading spinner 표시
                    Swal.fire({
                        title: '삭제 중...',
                        html: '측정 데이터를 삭제하고 있습니다.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();

                            // Register SweetAlert2 modal for mobile handler
                            if (window.mobileModalHandler) {
                                window.mobileModalHandler.registerModalOpen({
                                    id: `swal_loading_delete_${measurementId}`,
                                    type: 'swal',
                                    closeCallback: () => {
                                        if (Swal.isVisible()) {
                                            Swal.close();
                                        }
                                    }
                                });
                            }
                        },
                        willClose: () => {
                            // Register modal close when SweetAlert2 closes
                            if (window.mobileModalHandler) {
                                window.mobileModalHandler.registerModalClose(`swal_loading_delete_${measurementId}`);
                            }
                        }
                    });

                    fetch('delete_measurement.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            id: measurementId,
                            site_name: siteName,
                            measurement_date: measurementDate
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: '삭제 완료!',
                                text: data.message,
                                icon: 'success',
                                confirmButtonText: '확인',
                                didOpen: () => {
                                    // Register SweetAlert2 modal for mobile handler
                                    if (window.mobileModalHandler) {
                                        window.mobileModalHandler.registerModalOpen({
                                            id: `swal_success_delete_${measurementId}`,
                                            type: 'swal',
                                            closeCallback: () => {
                                                if (Swal.isVisible()) {
                                                    Swal.close();
                                                }
                                            }
                                        });
                                    }
                                },
                                willClose: () => {
                                    // Register modal close when SweetAlert2 closes
                                    if (window.mobileModalHandler) {
                                        window.mobileModalHandler.registerModalClose(`swal_success_delete_${measurementId}`);
                                    }
                                }
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: '삭제 실패',
                                text: '삭제 중 오류가 발생했습니다: ' + data.message,
                                icon: 'error',
                                confirmButtonText: '확인',
                                didOpen: () => {
                                    // Register SweetAlert2 modal for mobile handler
                                    if (window.mobileModalHandler) {
                                        window.mobileModalHandler.registerModalOpen({
                                            id: `swal_error_delete_${measurementId}`,
                                            type: 'swal',
                                            closeCallback: () => {
                                                if (Swal.isVisible()) {
                                                    Swal.close();
                                                }
                                            }
                                        });
                                    }
                                },
                                willClose: () => {
                                    // Register modal close when SweetAlert2 closes
                                    if (window.mobileModalHandler) {
                                        window.mobileModalHandler.registerModalClose(`swal_error_delete_${measurementId}`);
                                    }
                                }
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: '오류 발생',
                            text: '삭제 중 네트워크 오류가 발생했습니다.',
                            icon: 'error',
                            confirmButtonText: '확인',
                            didOpen: () => {
                                // Register SweetAlert2 modal for mobile handler
                                if (window.mobileModalHandler) {
                                    window.mobileModalHandler.registerModalOpen({
                                        id: `swal_network_error_${measurementId}`,
                                        type: 'swal',
                                        closeCallback: () => {
                                            if (Swal.isVisible()) {
                                                Swal.close();
                                            }
                                        }
                                    });
                                }
                            },
                            willClose: () => {
                                // Register modal close when SweetAlert2 closes
                                if (window.mobileModalHandler) {
                                    window.mobileModalHandler.registerModalClose(`swal_network_error_${measurementId}`);
                                }
                            }
                        });
                    });
                }
            });
        }

        // 전체 선택/해제 기능
        document.addEventListener('DOMContentLoaded', function() {
            const selectAllCheckbox = document.getElementById('selectAll');
            const rowCheckboxes = document.querySelectorAll('.row-checkbox');
            const exportBtn = document.getElementById('exportBtn');

            // 전체 선택/해제 체크박스 이벤트
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    rowCheckboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                    updateExportButton();
                });
            }

            // 개별 체크박스 이벤트
            rowCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updateSelectAllStatus();
                    updateExportButton();
                });
            });

            // 전체 선택 체크박스 상태 업데이트
            function updateSelectAllStatus() {
                const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;
                const totalCount = rowCheckboxes.length;

                if (checkedCount === 0) {
                    selectAllCheckbox.indeterminate = false;
                    selectAllCheckbox.checked = false;
                } else if (checkedCount === totalCount) {
                    selectAllCheckbox.indeterminate = false;
                    selectAllCheckbox.checked = true;
                } else {
                    selectAllCheckbox.indeterminate = true;
                }
            }

            // 엑셀 내보내기 버튼 상태 업데이트
            function updateExportButton() {
                const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;
                if (exportBtn) {
                    if (checkedCount > 0) {
                        exportBtn.disabled = false;
                        exportBtn.style.opacity = '1';
                        exportBtn.style.cursor = 'pointer';
                        exportBtn.querySelector('i').className = 'bi bi-file-earmark-excel';
                        exportBtn.innerHTML = `<i class="bi bi-file-earmark-excel"></i> 체크 엑셀 내보내기 (${checkedCount}건)`;
                    } else {
                        exportBtn.disabled = true;
                        exportBtn.style.opacity = '0.5';
                        exportBtn.style.cursor = 'not-allowed';
                        exportBtn.innerHTML = '<i class="bi bi-file-earmark-excel"></i> 체크 엑셀 내보내기';
                    }
                }
            }
        });

        function exportSelectedToExcel() {
            const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
            if (checkedBoxes.length === 0) {
                Swal.fire({
                    title: '선택된 항목이 없습니다',
                    text: '엑셀로 내보낼 측정 결과를 선택해주세요.',
                    icon: 'warning',
                    confirmButtonText: '확인',
                    didOpen: () => {
                        // Register SweetAlert2 modal for mobile handler
                        if (window.mobileModalHandler) {
                            window.mobileModalHandler.registerModalOpen({
                                id: 'swal_export_no_selection',
                                type: 'swal',
                                closeCallback: () => {
                                    if (Swal.isVisible()) {
                                        Swal.close();
                                    }
                                }
                            });
                        }
                    },
                    willClose: () => {
                        // Register modal close when SweetAlert2 closes
                        if (window.mobileModalHandler) {
                            window.mobileModalHandler.registerModalClose('swal_export_no_selection');
                        }
                    }
                });
                return;
            }

            const selectedIds = Array.from(checkedBoxes).map(cb => cb.value);
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'excel');
            params.set('selected_ids', selectedIds.join(','));

            // 확인 대화상자
            Swal.fire({
                title: '엑셀 내보내기',
                text: `선택된 ${selectedIds.length}건의 측정 결과를 엑셀로 내보내시겠습니까?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bi bi-file-earmark-excel"></i> 내보내기',
                cancelButtonText: '취소',
                didOpen: () => {
                    // Register SweetAlert2 modal for mobile handler
                    if (window.mobileModalHandler) {
                        window.mobileModalHandler.registerModalOpen({
                            id: `swal_export_confirm_${selectedIds.length}`,
                            type: 'swal',
                            closeCallback: () => {
                                if (Swal.isVisible()) {
                                    Swal.close();
                                }
                            }
                        });
                    }
                },
                willClose: () => {
                    // Register modal close when SweetAlert2 closes
                    if (window.mobileModalHandler) {
                        window.mobileModalHandler.registerModalClose(`swal_export_confirm_${selectedIds.length}`);
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'export_measurements.php?' + params.toString();
                }
            });
        }

        function exportToExcel() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'excel');
            window.location.href = 'export_measurements.php?' + params.toString();
        }
    </script>
</body>
</html>