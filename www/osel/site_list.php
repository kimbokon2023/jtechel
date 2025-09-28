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

// transom 데이터 존재 여부 확인 함수
function hasTransomData($transom_data) {
    if (empty($transom_data) || $transom_data === '{}') {
        return false;
    }
    
    $transom_json = json_decode($transom_data, true);
    if (!is_array($transom_json)) {
        return false;
    }
    
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
            return true;
        }
    }
    // 이전 형식 호환성 (직접 width 필드가 있는 경우)
    else if (isset($transom_json['width']) && !empty(trim($transom_json['width']))) {
        return true;
    }
    
    return false;
}

// Check authentication
if (!isset($_SESSION["level"]) || $_SESSION["level"] > 8) {
    header("Location: ../login/login_form.php");
    exit;
}

// Initialize database connection
$pdo = db_connect();

$message = '';
$messageType = ''; 

// Handle DELETE request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    try {
        $site_name = trim($_POST['site_name']);

        if (empty($site_name)) {
            throw new Exception('현장명이 지정되지 않았습니다.');
        }

        // Delete all measurements for this site
        $stmt = $pdo->prepare("DELETE FROM $DB.panel_measurements WHERE site_name = ?");
        $stmt->execute([$site_name]);

        $deleted_count = $stmt->rowCount();

        $message = "현장 '{$site_name}'의 모든 측정 데이터({$deleted_count}개)가 성공적으로 삭제되었습니다.";
        $messageType = 'success';

    } catch (Exception $e) {
        $message = '오류: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Handle UPDATE request - Update all measurements for a site
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    try {
        $old_site_name = trim($_POST['old_site_name']);
        $new_site_name = trim($_POST['site_name']);
        $site_address = trim($_POST['site_address']);
        $client_name = trim($_POST['client_name']);
        $client_phone = trim($_POST['client_phone']);
        $project_manager = trim($_POST['project_manager']);
        $elevator_count = intval($_POST['elevator_count']);
        $notes = trim($_POST['notes']);

        if (empty($new_site_name)) {
            throw new Exception('현장명은 필수입니다.');
        }

        if (empty($old_site_name)) {
            throw new Exception('기존 현장명이 지정되지 않았습니다.');
        }

        // Check if new site name already exists (only if changing name)
        if ($old_site_name !== $new_site_name) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM $DB.panel_measurements WHERE site_name = ?");
            $stmt->execute([$new_site_name]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('이미 등록된 현장명입니다.');
            }
        }

        // Update all measurements for this site with new information
        $stmt = $pdo->prepare("
            UPDATE $DB.panel_measurements
            SET site_name = ?, updated_at = NOW()
            WHERE site_name = ?
        ");

        $stmt->execute([$new_site_name, $old_site_name]);

        $updated_count = $stmt->rowCount();

        $message = "현장 정보가 성공적으로 수정되었습니다. (영향받은 측정 데이터: {$updated_count}개)";
        $messageType = 'success';

    } catch (Exception $e) {
        $message = '오류: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'asc' : 'desc';

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build query conditions
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(s.site_name LIKE ? OR s.client_name LIKE ? OR s.project_manager LIKE ? OR s.site_address LIKE ?)";
    $search_param = "%$search%";
    $params = array_fill(0, 4, $search_param);
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count of unique sites from panel_measurements
try {
    $count_stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT site_name)
        FROM $DB.panel_measurements
        " . (!empty($where_conditions) ? "WHERE " . str_replace(['s.site_name', 's.client_name', 's.project_manager', 's.site_address'], ['site_name', 'site_name', 'measurer_name', 'site_name'], implode(' AND ', $where_conditions)) : '') . "
    ");
    $count_stmt->execute($params);
    $total_count = $count_stmt->fetchColumn();
} catch (PDOException $e) {
    $total_count = 0;
}

// Calculate pagination
$total_pages = ceil($total_count / $per_page);

// Get sites directly from panel_measurements with aggregated data
try {
    $valid_sort_columns = ['site_name', 'measurer_name', 'created_at'];
    $sort_column = in_array($sort, $valid_sort_columns) ? $sort : 'created_at';
    $sort_order = $order === 'asc' ? 'ASC' : 'DESC';

    $where_clause_fixed = '';
    if (!empty($where_conditions)) {
        // Fix the where conditions to match panel_measurements table structure
        $fixed_conditions = [];
        foreach ($where_conditions as $condition) {
            $fixed_condition = str_replace(
                ['s.site_name', 's.client_name', 's.project_manager', 's.site_address'],
                ['site_name', 'site_name', 'measurer_name', 'site_name'],
                $condition
            );
            $fixed_conditions[] = $fixed_condition;
        }
        $where_clause_fixed = 'WHERE ' . implode(' AND ', $fixed_conditions);
    }

    $stmt = $pdo->prepare("
        SELECT
            site_name,
            MAX(id) as latest_id,
            COUNT(DISTINCT measurement_date) as measurement_sessions,
            COUNT(*) as total_panels,
            MIN(created_at) as created_at,
            MAX(updated_at) as updated_at,
            GROUP_CONCAT(DISTINCT measurer_name ORDER BY created_at DESC SEPARATOR ', ') as all_measurers,
            SUBSTRING_INDEX(GROUP_CONCAT(DISTINCT measurer_name ORDER BY created_at DESC SEPARATOR ', '), ',', 1) as primary_measurer,
            AVG(car_inside_width) as avg_width,
            AVG(car_inside_depth) as avg_depth,
            AVG(car_inside_height) as avg_height,
            GROUP_CONCAT(DISTINCT material_type SEPARATOR ', ') as material_types,
            SUBSTRING_INDEX(GROUP_CONCAT(panel_data ORDER BY id DESC SEPARATOR '|||'), '|||', 1) as latest_panel_data,
            SUBSTRING_INDEX(GROUP_CONCAT(transom_data ORDER BY id DESC SEPARATOR '|||'), '|||', 1) as latest_transom_data
        FROM $DB.panel_measurements
        $where_clause_fixed
        GROUP BY site_name
        ORDER BY $sort_column $sort_order
        LIMIT $per_page OFFSET $offset
    ");
    $stmt->execute($params);
    $sites = $stmt->fetchAll();

    // 각 사이트의 실제 패널 개수 계산 및 transom 정보 추가
    foreach ($sites as &$site) {
        $site['actual_panel_count'] = calculateActualPanelCount($site['latest_panel_data'], $site['latest_transom_data']);
        $site['has_transom'] = hasTransomData($site['latest_transom_data']);
    }
    unset($site); // 참조 제거
} catch (PDOException $e) {
    $sites = [];
    $message = '데이터를 불러오는 중 오류가 발생했습니다: ' . $e->getMessage();
    $messageType = 'danger';
}

// Component requirements
require_once '../components/LinearComponent.php';
require_once '../components/LinearInput.php';
require_once '../components/LinearAlert.php';
require_once '../components/LinearButton.php';
require_once '../components/LinearNavigation.php';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>현장 리스트</title>
    
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
        
        .site-management-container {
            max-width: var(--linear-page-max-width);
            margin: 0 auto;
            padding: var(--linear-spacing-lg);
        }
        
        /* Consistent breadcrumb and title (tone & manner) */
        .pm-breadcrumb {
            display: flex;
            align-items: center;
            gap: var(--linear-spacing-sm);
            margin-bottom: var(--linear-spacing-lg);
            font-size: var(--linear-text-small);
        }
        .pm-breadcrumb a { color: var(--linear-brand-primary); text-decoration: none; }
        .pm-breadcrumb a:hover { text-decoration: underline; }
        .pm-breadcrumb .sep { color: var(--linear-text-tertiary); }
        .page-title {
            font-size: 2rem;
            font-weight: var(--linear-font-weight-semibold);
            color: var(--linear-text-primary);
            margin-bottom: var(--linear-spacing-xl);
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: var(--linear-spacing-xl);
            flex-wrap: wrap;
            gap: var(--linear-spacing-md);
        }
        
        .search-section {
            background: var(--linear-bg-primary);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-lg);
            padding: var(--linear-spacing-lg);
            margin-bottom: var(--linear-spacing-xl);
            box-shadow: var(--linear-shadow-low);
        }
        
        .search-form {
            display: flex;
            gap: var(--linear-spacing-md);
            align-items: end;
            flex-wrap: wrap;
        }
        
        .search-input-group {
            flex: 1;
            min-width: 200px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--linear-bg-primary);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-lg);
            overflow: hidden;
            box-shadow: var(--linear-shadow-low);
            margin-bottom: var(--linear-spacing-xl);
        }
        
        .data-table th {
            background: var(--linear-bg-tertiary);
            color: var(--linear-text-secondary);
            font-weight: var(--linear-font-weight-medium);
            padding: var(--linear-spacing-md) var(--linear-spacing-sm);
            text-align: left;
            border-bottom: 1px solid var(--linear-border-primary);
            font-size: var(--linear-text-small);
            user-select: none;
            cursor: pointer;
            position: relative;
        }
        
        .data-table th:hover {
            background: var(--linear-bg-secondary);
        }
        
        .data-table th.sortable::after {
            content: '⇅';
            margin-left: var(--linear-spacing-xs);
            opacity: 0.5;
        }
        
        .data-table th.sort-asc::after {
            content: '↑';
            opacity: 1;
        }
        
        .data-table th.sort-desc::after {
            content: '↓';
            opacity: 1;
        }
        
        .data-table td {
            padding: var(--linear-spacing-md) var(--linear-spacing-sm);
            border-bottom: 1px solid var(--linear-border-secondary);
            vertical-align: middle;
        }
        
        .data-table tbody tr {
            transition: background-color var(--linear-transition-fast);
        }
        
        .data-table tbody tr:hover {
            background: var(--linear-bg-secondary);
        }
        
        .data-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .badge {
            background-color: var(--linear-bg-tertiary);
            color: var(--linear-text-secondary);
            padding: 2px 8px;
            border-radius: var(--linear-radius-sm);
            font-size: var(--linear-text-mini);
            font-weight: var(--linear-font-weight-medium);
            border: 1px solid var(--linear-border-secondary);
            display: inline-flex;
            align-items: center;
            min-width: 0;
        }
        
        .badge.success {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border-color: rgba(40, 167, 69, 0.3);
        }
        
        .badge.warning {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
            border-color: rgba(255, 193, 7, 0.3);
        }
        
        .action-buttons {
            display: flex;
            gap: var(--linear-spacing-xs, 4px);
            align-items: center;
        }
        
        .action-button {
            padding: var(--linear-spacing-xs, 4px) var(--linear-spacing-sm, 8px);
            border: 1px solid;
            border-radius: var(--linear-radius-sm, 4px);
            font-size: var(--linear-text-small, 0.875rem);
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all var(--linear-transition-fast, 0.1s);
        }
        
        .action-button.edit {
            background: var(--linear-bg-primary, #fff);
            border-color: var(--linear-border-primary, #e9e8ea);
            color: var(--linear-text-primary, #282a30);
        }
        
        .action-button.edit:hover {
            background: var(--linear-bg-secondary, #f9f8f9);
            border-color: var(--linear-border-secondary, #e4e2e4);
        }
        
        .action-button.delete {
            background: rgba(220, 53, 69, 0.1);
            border-color: rgba(220, 53, 69, 0.3);
            color: #dc3545;
        }
        
        .action-button.delete:hover {
            background: rgba(220, 53, 69, 0.2);
            border-color: rgba(220, 53, 69, 0.5);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: var(--linear-spacing-xs, 4px);
            margin-top: var(--linear-spacing-lg, 24px);
        }
        
        .pagination-button {
            padding: var(--linear-spacing-sm, 8px) var(--linear-spacing-md, 16px);
            border: 1px solid var(--linear-border-primary, #e9e8ea);
            background: var(--linear-bg-primary, #fff);
            color: var(--linear-text-primary, #282a30);
            border-radius: var(--linear-radius-sm, 4px);
            text-decoration: none;
            font-size: var(--linear-text-small, 0.875rem);
            transition: all var(--linear-transition-fast, 0.1s);
        }
        
        .pagination-button:hover:not(.disabled) {
            background: var(--linear-bg-secondary, #f9f8f9);
            border-color: var(--linear-border-secondary, #e4e2e4);
            text-decoration: none;
        }
        
        .pagination-button.active {
            background: var(--linear-primary, #5e6ad2);
            border-color: var(--linear-primary, #5e6ad2);
            color: white;
        }
        
        .pagination-button.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show {
            display: flex !important;
        }
        
        .modal-content {
            background: var(--linear-bg-primary, #fff);
            border-radius: var(--linear-radius-lg, 12px);
            padding: 0;
            max-width: 600px;
            width: 95%;
            height: 85vh;
            max-height: 85vh;
            overflow: hidden;
            box-shadow: var(--linear-shadow-lg, 0 8px 32px rgba(0, 0, 0, 0.12));
            display: flex;
            flex-direction: column;
        }
        
        .modal-header {
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            padding: var(--linear-spacing-xl, 32px) var(--linear-spacing-xl, 32px) var(--linear-spacing-lg, 24px);
            border-bottom: 1px solid var(--linear-border-secondary, #e4e2e4);
            background: var(--linear-bg-tertiary, #f9f8f9);
            min-height: 100px;
        }
        
        .modal-header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--linear-spacing-md, 16px);
        }
        
        .modal-title {
            font-size: var(--linear-text-title2, 1.5rem);
            font-weight: var(--linear-font-semibold, 600);
            margin: 0;
            color: var(--linear-text-primary, #282a30);
        }
        
        .modal-description {
            color: var(--linear-text-secondary, #3c4149);
            font-size: var(--linear-text-small, 0.875rem);
            margin: 0;
            line-height: 1.5;
        }
        
        .modal-body {
            flex: 1;
            padding: var(--linear-spacing-xl, 32px);
            padding-bottom: 0;
            overflow-y: auto;
            overflow-x: hidden;
            max-height: calc(85vh - 180px);
            min-height: 250px;
        }
        
        .modal-footer {
            flex-shrink: 0;
            padding: var(--linear-spacing-lg, 24px) var(--linear-spacing-xl, 32px);
            border-top: 1px solid var(--linear-border-secondary, #e4e2e4);
            background: var(--linear-bg-secondary, #f9f8f9);
            display: flex;
            gap: var(--linear-spacing-sm, 8px);
            justify-content: flex-end;
            min-height: 80px;
            align-items: center;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--linear-text-secondary, #3c4149);
            padding: var(--linear-spacing-xs, 4px);
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--linear-radius-sm, 4px);
            transition: all var(--linear-transition-fast, 0.1s);
        }
        
        .modal-close:hover {
            color: var(--linear-text-primary, #282a30);
            background: var(--linear-bg-secondary, #f9f8f9);
        }
        
        /* 모달 헤더 액션 버튼들 */
        .modal-header-actions {
            display: flex;
            align-items: center;
            gap: var(--linear-spacing-sm, 8px);
        }
        
        .modal-header-save-btn {
            font-size: var(--linear-text-small, 0.875rem) !important;
            padding: var(--linear-spacing-xs, 4px) var(--linear-spacing-sm, 8px) !important;
            min-height: auto !important;
            white-space: nowrap;
        }
        
        .form-group {
            margin-bottom: var(--linear-spacing-lg, 24px);
        }
        
        .form-group:last-child {
            margin-bottom: 0;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--linear-spacing-lg, 24px);
            align-items: start;
            width: 100%;
        }
        
        .form-row .form-group {
            margin-bottom: var(--linear-spacing-md, 16px);
        }
        
        .form-section {
            margin-bottom: var(--linear-spacing-2xl, 48px);
        }
        
        .form-section:last-child {
            margin-bottom: 0;
        }
        
        .modal-form-section {
            border-left: 3px solid var(--linear-brand-primary);
            padding-left: var(--linear-spacing-lg, 24px);
            margin-bottom: var(--linear-spacing-xl, 32px);
            width: 100%;
            box-sizing: border-box;
        }
        
        .modal-form-section:last-child {
            margin-bottom: 0;
        }
        
        .modal-form-section h6 {
            color: var(--linear-brand-primary);
            font-weight: var(--linear-font-weight-semibold, 600);
            font-size: var(--linear-text-title3, 1.25rem);
            margin-bottom: var(--linear-spacing-md, 16px);
            display: flex;
            align-items: center;
            gap: var(--linear-spacing-xs, 4px);
        }
        
        /* 모달 내 입력 필드 정렬 */
        .modal-form-section .linear-input-group {
            width: 100%;
            margin-bottom: var(--linear-spacing-md, 16px);
        }
        
        .modal-form-section .linear-input {
            width: 100%;
            box-sizing: border-box;
        }
        
        .modal-form-section select {
            width: 100%;
            box-sizing: border-box;
        }
        
        /* 모달 스크롤바 스타일링 - 스마트 표시 */
        .modal-body {
            scrollbar-width: auto;
            scrollbar-color: var(--linear-brand-primary, #5e6ad2) var(--linear-bg-secondary, #f9f8f9);
        }
        
        .modal-body::-webkit-scrollbar {
            width: 12px;
            height: 12px;
        }
        
        .modal-body::-webkit-scrollbar-track {
            background: var(--linear-bg-secondary, #f9f8f9);
            border-radius: 6px;
            border: 1px solid var(--linear-border-primary, #e9e8ea);
        }
        
        .modal-body::-webkit-scrollbar-thumb {
            background: var(--linear-brand-primary, #5e6ad2);
            border-radius: 6px;
            border: 2px solid var(--linear-bg-secondary, #f9f8f9);
            min-height: 30px;
        }
        
        .modal-body::-webkit-scrollbar-thumb:hover {
            background: var(--linear-brand-primary-hover, #4c5ac7);
        }
        
        .modal-body::-webkit-scrollbar-thumb:active {
            background: var(--linear-brand-primary, #5e6ad2);
        }
        
        .modal-body::-webkit-scrollbar-corner {
            background: var(--linear-bg-secondary, #f9f8f9);
        }
        
        /* 스크롤바가 항상 보이도록 강제 */
        .modal-body::-webkit-scrollbar {
            -webkit-appearance: none;
        }
        
        /* 큰 화면에서는 더 넓은 모달 */
        @media (min-width: 1200px) {
            .modal-content {
                max-width: 700px;
                height: 85vh;
                max-height: 85vh;
            }
            
            .modal-body {
                max-height: calc(85vh - 180px);
                min-height: 300px;
            }
            
            .modal-footer {
                min-height: 80px;
                flex-shrink: 0;
            }
        }
        
        @media (max-width: 768px) {
            .site-management-container {
                padding: var(--linear-spacing-md);
            }
            
            .page-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-form {
                flex-direction: row;
                gap: var(--linear-spacing-sm, 8px);
                align-items: stretch;
            }
            
            .search-input-group {
                min-width: auto;
                flex: 1;
            }
            
            .search-form > div:last-child {
                flex-shrink: 0;
                display: flex;
                align-items: end;
                gap: var(--linear-spacing-xs, 4px);
            }
            
            .data-table {
                font-size: var(--linear-text-small);
            }
            
            .data-table th,
            .data-table td {
                padding: var(--linear-spacing-sm) var(--linear-spacing-xs);
            }
            
            .action-buttons {
                flex-direction: column;
                gap: var(--linear-spacing-xs);
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: var(--linear-spacing-md, 16px);
            }
            
            .modal-content {
                width: 98%;
                height: 80vh;
                max-height: 80vh;
            }
            
            .modal-header {
                padding: var(--linear-spacing-lg, 24px);
                min-height: 70px;
                flex-shrink: 0;
            }
            
            .modal-body {
                padding: var(--linear-spacing-lg, 24px);
                padding-bottom: 0;
                max-height: calc(80vh - 150px);
                min-height: 150px;
                overflow-y: auto;
                flex: 1;
            }
            
            .modal-footer {
                flex-direction: column;
                gap: var(--linear-spacing-sm, 8px);
                min-height: 80px;
                max-height: 80px;
                padding: var(--linear-spacing-md, 16px) var(--linear-spacing-lg, 24px);
                flex-shrink: 0;
            }
            
            .modal-form-section {
                padding-left: var(--linear-spacing-md, 16px);
                margin-bottom: var(--linear-spacing-lg, 24px);
            }
            
            /* 모바일에서 헤더 액션 최적화 */
            .modal-header-actions {
                gap: var(--linear-spacing-xs, 4px);
            }
            
            .modal-header-save-btn {
                font-size: var(--linear-text-mini, 0.75rem) !important;
                padding: var(--linear-spacing-xs, 4px) var(--linear-spacing-xs, 4px) !important;
            }
            
            /* 모바일에서 통계 섹션 최적화 */
            .statistics-section {
                gap: var(--linear-spacing-sm, 8px) !important;
                margin-bottom: var(--linear-spacing-lg, 24px) !important;
            }
            
            .stat-card {
                min-width: auto !important;
                padding: var(--linear-spacing-md, 16px) !important;
                flex: 1 !important;
            }
            
            .stat-card > div:first-child {
                font-size: 1.5rem !important;
                margin-bottom: var(--linear-spacing-xs, 4px) !important;
            }
            
            .stat-card > div:last-child {
                font-size: var(--linear-text-mini, 0.75rem) !important;
            }
            
            /* 모바일에서 테이블 숨기기, 카드 표시 */
            .desktop-table {
                display: none !important;
            }
            
            .mobile-cards {
                display: block !important;
            }
            
            /* 모바일 카드 최적화 */
            .site-card {
                margin-bottom: var(--linear-spacing-sm, 8px) !important;
            }
            
            .card-row {
                padding: var(--linear-spacing-sm, 8px) var(--linear-spacing-md, 16px) !important;
            }
            
            .card-info {
                gap: var(--linear-spacing-xs, 4px) !important;
            }
            
            .info-item {
                font-size: var(--linear-text-mini, 0.75rem) !important;
            }
        }
        
        .no-data {
            text-align: center;
            padding: var(--linear-spacing-3xl, 64px) var(--linear-spacing-lg, 24px);
            color: var(--linear-text-secondary, #3c4149);
        }
        
        .no-data i {
            font-size: 4rem;
            margin-bottom: var(--linear-spacing-lg, 24px);
            opacity: 0.3;
        }
        
        .truncate {
            max-width: 150px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* 기본적으로 모바일 카드 숨기기 */
        .mobile-cards {
            display: none;
        }
        
        /* 모바일 카드 스타일 */
        .site-card {
            background: var(--linear-bg-primary, #fff);
            border: 1px solid var(--linear-border-primary, #e9e8ea);
            border-radius: var(--linear-radius-lg, 12px);
            margin-bottom: var(--linear-spacing-md, 16px);
            overflow: hidden;
            box-shadow: var(--linear-shadow-low, 0 1px 3px rgba(0, 0, 0, 0.1));
        }
        
        .card-row {
            padding: var(--linear-spacing-md, 16px);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-main {
            border-bottom: 1px solid var(--linear-border-secondary, #e4e2e4);
            background: var(--linear-bg-tertiary, #f9f8f9);
        }
        
        .card-primary {
            flex: 1;
        }
        
        .site-name {
            margin: 0;
            font-size: var(--linear-text-body, 1rem);
            font-weight: var(--linear-font-semibold, 600);
            color: var(--linear-text-primary, #282a30);
            margin-bottom: var(--linear-spacing-xs, 4px);
        }
        
        .site-manager {
            font-size: var(--linear-text-small, 0.875rem);
            color: var(--linear-text-secondary, #3c4149);
        }
        
        .card-status {
            text-align: right;
        }
        
        .panel-count {
            font-size: var(--linear-text-small, 0.875rem);
            color: var(--linear-text-secondary, #3c4149);
            margin-top: var(--linear-spacing-xs, 4px);
        }
        
        .card-details {
            padding-top: var(--linear-spacing-sm, 8px);
        }
        
        .card-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: var(--linear-spacing-xs, 4px);
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: var(--linear-spacing-xs, 4px);
            font-size: var(--linear-text-small, 0.875rem);
        }
        
        .info-item i {
            width: 16px;
            color: var(--linear-text-tertiary, #6b7785);
            flex-shrink: 0;
        }
        
        .info-item span {
            color: var(--linear-text-primary, #282a30);
            flex: 1;
        }
        
        .info-item small {
            color: var(--linear-text-secondary, #3c4149);
            font-size: var(--linear-text-mini, 0.75rem);
            margin-left: auto;
        }
        
        .card-actions {
            display: flex;
            gap: var(--linear-spacing-xs, 4px);
            flex-shrink: 0;
        }
        
        .card-actions .action-button {
            padding: var(--linear-spacing-xs, 4px);
            min-width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .card-actions .action-button i {
            font-size: 0.875rem;
        }
        
        @media (max-width: 768px) {
            .truncate {
                max-width: 100px;
            }
        }
    </style>
</head>
<body>
    <?php
    // Linear 네비게이션 생성 (상단 공통 톤앤매너)
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
    ->addAction('<a href="index.php" style="color: var(--linear-text-secondary); text-decoration: none; margin-right: 1rem;">대시보드</a>')
    ->addAction('<a href="../login/logout.php" style="color: var(--linear-text-secondary); text-decoration: none;">로그아웃</a>')
    ->fixed();
    
    echo $nav;
    ?>

    <div class="site-management-container" style="margin-top: var(--linear-header-height);">

        <!-- Alert Messages -->
        <?php if ($message): ?>
        <?php
        $alertType = $messageType === 'success' ? 'success' : ($messageType === 'danger' ? 'error' : 'warning');
        echo LinearAlert::create($message, $alertType)->dismissible();
        ?>
        <?php endif; ?>

        <!-- Breadcrumb & Title -->
        <nav class="pm-breadcrumb">
            <a href="index.php">대시보드</a>
            <span class="sep">/</span>
            <span>현장 리스트</span>
        </nav>
        <h2 class="page-title"><i class="bi bi-buildings"></i> 현장 리스트</h2>
        
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <p style="color: var(--linear-text-secondary); margin: 0;">
                    측정 데이터를 기반으로 한 현장 정보를 조회
                </p>
            </div>
            <div>
                <a href="panel_measurement.php" style="text-decoration: none; margin-right: var(--linear-spacing-sm);">
                    <?= LinearButton::primary('<i class="bi bi-plus-lg"></i> 새 측정') ?>
                </a>
                <a href="index.php" style="text-decoration: none;">
                    <?= LinearButton::outline('<i class="bi bi-house"></i> 대시보드') ?>
                </a>
            </div>
        </div>
        
        <!-- Search Section -->
        <div class="search-section">
            <form method="GET" class="search-form">
                <div class="search-input-group">
                    <?= LinearInput::search('search')
                        ->placeholder('현장명, 측정자로 검색...')
                        ->value($search)
                        ->addAttribute('onkeydown', 'if(event.key === "Enter") { event.preventDefault(); this.form.submit(); }') ?>
                </div>
                <div>
                    <?= LinearButton::primary('<i class="bi bi-search"></i> 검색')
                        ->addAttribute('type', 'submit') ?>
                    <?php if (!empty($search)): ?>
                        <a href="site_list.php" style="text-decoration: none; margin-left: var(--linear-spacing-sm, 8px);">
                            <?= LinearButton::secondary('초기화') ?>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Statistics -->
        <div class="statistics-section" style="display: flex; gap: var(--linear-spacing-lg, 24px); margin-bottom: var(--linear-spacing-xl, 32px); flex-wrap: wrap;">
            <div class="stat-card" style="flex: 1; min-width: 200px; background: var(--linear-bg-primary, #fff); border: 1px solid var(--linear-border-primary, #e9e8ea); border-radius: var(--linear-radius-lg, 12px); padding: var(--linear-spacing-lg, 24px); text-align: center;">
                <div style="font-size: 2rem; font-weight: var(--linear-font-bold, 600); color: var(--linear-primary, #5e6ad2); margin-bottom: var(--linear-spacing-sm, 8px);">
                    <?= $total_count ?>
                </div>
                <div style="color: var(--linear-text-secondary, #3c4149); font-size: var(--linear-text-small, 0.875rem);">
                    총 현장 수
                </div>
            </div>
            
            <div class="stat-card" style="flex: 1; min-width: 200px; background: var(--linear-bg-primary, #fff); border: 1px solid var(--linear-border-primary, #e9e8ea); border-radius: var(--linear-radius-lg, 12px); padding: var(--linear-spacing-lg, 24px); text-align: center;">
                <div style="font-size: 2rem; font-weight: var(--linear-font-bold, 600); color: #28a745; margin-bottom: var(--linear-spacing-sm, 8px);">
                    <?php
                    $measured_sites = 0;
                    foreach ($sites as $site) {
                        if ($site['measurement_sessions'] > 0) $measured_sites++;
                    }
                    echo $measured_sites;
                    ?>
                </div>
                <div style="color: var(--linear-text-secondary, #3c4149); font-size: var(--linear-text-small, 0.875rem);">
                    측정 완료 현장
                </div>
            </div>
        </div>
        
        <!-- Data Table -->
        <?php if (empty($sites)): ?>
            <div class="no-data">
                <i class="bi bi-building"></i>
                <h3>등록된 현장이 없습니다</h3>
                <?php if (!empty($search)): ?>
                    <p>검색 조건에 맞는 현장이 없습니다. 다른 검색어를 시도해보세요.</p>
                <?php else: ?>
                    <p>새로운 현장을 등록하여 시작하세요.</p>
                    <a href="site_management.php" style="text-decoration: none;">
                        <?= LinearButton::primary('<i class="bi bi-plus-lg"></i> 첫 현장 등록하기') ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Desktop Table View -->
            <table class="data-table desktop-table">
                <thead>
                    <tr>
                        <th class="sortable <?= $sort === 'site_name' ? 'sort-' . $order : '' ?>" 
                            onclick="sortTable('site_name')">현장명</th>
                        <th class="sortable <?= $sort === 'measurer_name' ? 'sort-' . $order : '' ?>"
                            onclick="sortTable('measurer_name')">측정자</th>
                        <th>카 내부 치수</th>
                        <th>의장재질</th>
                        <th>측정 현황</th>
                        <th class="sortable <?= $sort === 'created_at' ? 'sort-' . $order : '' ?>" 
                            onclick="sortTable('created_at')">등록일</th>
                        <th>작업</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sites as $site): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($site['site_name']) ?></strong>
                                <br><small style="color: var(--linear-text-secondary, #3c4149);">
                                    최신ID: <?= $site['latest_id'] ?>
                                </small>
                            </td>
                            <td>
                                <?= htmlspecialchars($site['primary_measurer'] ?: '-') ?>
                                <?php if (strpos($site['all_measurers'], ',') !== false): ?>
                                    <br><small style="color: var(--linear-text-secondary, #3c4149);" title="<?= htmlspecialchars($site['all_measurers']) ?>">
                                        외 <?= substr_count($site['all_measurers'], ',') ?>명
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($site['avg_width'] && $site['avg_depth'] && $site['avg_height']): ?>
                                    <span style="font-family: 'JetBrains Mono', 'SF Mono', 'Monaco', 'Cascadia Code', 'Roboto Mono', 'Courier New', monospace; font-size: var(--linear-text-small);">
                                        <span style="color: #e74c3c; font-weight: var(--linear-font-weight-semibold);">W<?= intval($site['avg_width']) ?></span>
                                        <span style="color: #3498db; font-weight: var(--linear-font-weight-semibold);">D<?= intval($site['avg_depth']) ?></span>
                                        <span style="color: #2ecc71; font-weight: var(--linear-font-weight-semibold);">H<?= intval($site['avg_height']) ?></span>
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--linear-text-tertiary);">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="truncate" title="<?= htmlspecialchars($site['material_types']) ?>">
                                    <?= htmlspecialchars($site['material_types'] ?: '-') ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($site['measurement_sessions'] > 0): ?>
                                    <?php
                                    // transom이 있으면 패널 개수에서 1개 빼고 표시
                                    $display_panel_count = $site['has_transom'] ? ($site['actual_panel_count'] ?? 0) - 1 : ($site['actual_panel_count'] ?? 0);
                                    ?>
                                    <div class="badge success">
                                        <?= $display_panel_count ?>개
                                    </div>
                                    <?php if ($site['has_transom']): ?>
                                        <div style="font-size: 0.8rem; color: var(--linear-brand-primary); margin-top: 2px;">+ Transom</div>
                                    <?php endif; ?>
                                    <br><small style="color: var(--linear-text-secondary, #3c4149);">
                                        <?= $site['measurement_sessions'] ?>대
                                    </small>
                                <?php else: ?>
                                    <div class="badge warning">미측정</div>
                                <?php endif; ?>
                            </td>
                            <td style="color: var(--linear-text-secondary, #3c4149); font-size: var(--linear-text-small, 0.875rem);">
                                <?= date('Y-m-d', strtotime($site['created_at'])) ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="panel_measurement.php?edit=<?= $site['latest_id'] ?>" class="action-button edit">
                                        <i class="bi bi-eye"></i> 보기
                                    </a>
                                    <button type="button" class="action-button delete"
                                            onclick="deleteSite('<?= htmlspecialchars($site['site_name'], ENT_QUOTES, 'UTF-8') ?>')">
                                        <i class="bi bi-trash"></i> 삭제
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Mobile Card View -->
            <div class="mobile-cards">
                <?php foreach ($sites as $site): ?>
                    <div class="site-card">
                        <!-- First Row: Main Info -->
                        <div class="card-row card-main">
                            <div class="card-primary">
                                <h4 class="site-name"><?= htmlspecialchars($site['site_name']) ?></h4>
                                <div class="site-manager">측정자: <?= htmlspecialchars($site['primary_measurer'] ?: '없음') ?></div>
                            </div>
                            <div class="card-status">
                                <?php if ($site['measurement_sessions'] > 0): ?>
                                    <?php
                                    // transom이 있으면 패널 개수에서 1개 빼고 표시
                                    $display_panel_count = $site['has_transom'] ? ($site['actual_panel_count'] ?? 0) - 1 : ($site['actual_panel_count'] ?? 0);
                                    ?>
                                    <div class="badge success">
                                        <?= $display_panel_count ?>개
                                    </div>
                                    <?php if ($site['has_transom']): ?>
                                        <div style="font-size: 0.7rem; color: var(--linear-brand-primary); margin-top: 2px; text-align: right;">+ Transom</div>
                                    <?php endif; ?>
                                    <div class="panel-count"><?= $site['measurement_sessions'] ?>대</div>
                                <?php else: ?>
                                    <div class="badge warning">미측정</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Second Row: Details & Actions -->
                        <div class="card-row card-details">
                            <div class="card-info">
                                <div class="info-item">
                                    <i class="bi bi-rulers"></i>
                                    <span>
                                        <?php if ($site['avg_width'] && $site['avg_depth'] && $site['avg_height']): ?>
                                            <span style="font-family: monospace; font-size: var(--linear-text-small);">
                                                <span style="color: #e74c3c;">W<?= intval($site['avg_width']) ?></span>
                                                <span style="color: #3498db;">D<?= intval($site['avg_depth']) ?></span>
                                                <span style="color: #2ecc71;">H<?= intval($site['avg_height']) ?></span>
                                            </span>
                                        <?php else: ?>
                                            치수 정보 없음
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <i class="bi bi-tools"></i>
                                    <span><?= htmlspecialchars($site['material_types'] ?: '의장재질 없음') ?></span>
                                </div>
                                <div class="info-item">
                                    <i class="bi bi-calendar"></i>
                                    <span>등록: <?= date('Y-m-d', strtotime($site['created_at'])) ?></span>
                                    <?php if ($site['updated_at'] && $site['updated_at'] !== $site['created_at']): ?>
                                        <small>수정: <?= date('Y-m-d', strtotime($site['updated_at'])) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-actions">
                                <a href="panel_measurement.php?edit=<?= $site['latest_id'] ?>" class="action-button edit">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <button type="button" class="action-button delete"
                                        onclick="deleteSite('<?= htmlspecialchars($site['site_name'], ENT_QUOTES, 'UTF-8') ?>')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php
                    $current_url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
                    $query_params = $_GET;
                    
                    // Previous page
                    if ($page > 1):
                        $query_params['page'] = $page - 1;
                        $prev_url = $current_url . '?' . http_build_query($query_params);
                    ?>
                        <a href="<?= $prev_url ?>" class="pagination-button">
                            <i class="bi bi-chevron-left"></i> 이전
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    // Page numbers
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                        $query_params['page'] = $i;
                        $page_url = $current_url . '?' . http_build_query($query_params);
                    ?>
                        <a href="<?= $page_url ?>" 
                           class="pagination-button <?= $i === $page ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php
                    // Next page
                    if ($page < $total_pages):
                        $query_params['page'] = $page + 1;
                        $next_url = $current_url . '?' . http_build_query($query_params);
                    ?>
                        <a href="<?= $next_url ?>" class="pagination-button">
                            다음 <i class="bi bi-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    
    <script>
        // 요소 존재 확인 헬퍼 함수
        function safeGetElement(id) {
            try {
                const element = document.getElementById(id);
                return element || null;
            } catch (e) {
                console.warn(`Failed to get element with id '${id}':`, e);
                return null;
            }
        }
        
        function safeQuerySelector(selector) {
            try {
                const element = document.querySelector(selector);
                return element || null;
            } catch (e) {
                console.warn(`Failed to query selector '${selector}':`, e);
                return null;
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // DOM 완전 로드 확인
            if (!document.body || !document.documentElement) {
                console.error('Document not ready');
                return;
            }
            
            // 모달 엘리먼트 존재 확인
            // Site list initialized
            
            // 테마 토글 기능 초기화 (안전한 방식)
            function initializeThemeToggle() {
                const themeToggleBtn = safeGetElement('themeToggleBtn');
                const themeIcon = safeGetElement('themeIcon');
                
                if (!themeToggleBtn) {
                    return;
                }
                
                if (!themeIcon) {
                    return;
                }
                
                if (!document.documentElement || !document.body) {
                    return;
                }
                
                try {
                    // 테마 상태 관리
                    let currentTheme = localStorage.getItem('linear-theme') || 'auto';
                    
                    // 아이콘 업데이트 함수 (안전한 방식)
                    function updateThemeIcon() {
                        if (!themeIcon || !themeToggleBtn) return;
                        
                        try {
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
                        } catch (e) {
                            console.warn('Theme icon update failed:', e);
                        }
                    }
                    
                    // 테마 적용 함수
                    function applyTheme(theme) {
                        try {
                            if (theme === 'auto') {
                                document.documentElement.removeAttribute('data-theme');
                            } else {
                                document.documentElement.setAttribute('data-theme', theme);
                            }
                            localStorage.setItem('linear-theme', theme);
                            currentTheme = theme;
                            updateThemeIcon();
                        } catch (e) {
                            console.warn('Theme application failed:', e);
                        }
                    }
                    
                    // 초기 테마 적용
                    applyTheme(currentTheme);
                    
                    // 버튼 클릭 이벤트 (안전한 방식)
                    if (themeToggleBtn && typeof themeToggleBtn.addEventListener === 'function') {
                        themeToggleBtn.addEventListener('click', function() {
                            try {
                                const themeOrder = ['light', 'dark', 'auto'];
                                const currentIndex = themeOrder.indexOf(currentTheme);
                                const nextIndex = (currentIndex + 1) % themeOrder.length;
                                applyTheme(themeOrder[nextIndex]);
                            } catch (e) {
                                console.warn('Theme toggle click failed:', e);
                            }
                        });
                    }
                    
                    // 시스템 테마 변경 감지 (auto 모드일 때)
                    if (window.matchMedia) {
                        const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
                        darkModeQuery.addEventListener('change', function() {
                            if (currentTheme === 'auto') {
                                // auto 모드에서는 data-theme 속성을 제거하여 CSS의 @media 규칙이 적용되도록 함
                                try {
                                    document.documentElement.removeAttribute('data-theme');
                                } catch (e) {
                                    console.warn('Theme attribute removal failed:', e);
                                }
                            }
                        });
                    }
                } catch (error) {
                    console.error('Theme initialization failed:', error);
                }
            }
            
            // 테마 초기화 실행
            initializeThemeToggle();
            
            // 모달 이벤트 리스너 설정
            setupModalEventListeners();
        });
        
        // 모달 관련 이벤트 리스너 설정 (안전한 방식)
        function setupModalEventListeners() {
            try {
                // 전화번호 포맷팅
                const phoneInput = safeGetElement('edit_client_phone');
                if (phoneInput && typeof phoneInput.addEventListener === 'function') {
                    phoneInput.addEventListener('input', function(e) {
                        try {
                            if (!e.target) return;
                            let value = e.target.value.replace(/\D/g, '');
                            if (value.length >= 3) {
                                if (value.length <= 7) {
                                    value = value.replace(/(\d{3})(\d{1,4})/, '$1-$2');
                                } else {
                                    value = value.replace(/(\d{3})(\d{4})(\d{1,4})/, '$1-$2-$3');
                                }
                            }
                            e.target.value = value;
                        } catch (err) {
                            console.warn('Phone formatting failed:', err);
                        }
                    });
                }
                
                // 메모 글자 수 카운터
                const notesTextarea = safeGetElement('edit_notes');
                if (notesTextarea && typeof notesTextarea.addEventListener === 'function') {
                    notesTextarea.addEventListener('input', function(e) {
                        try {
                            if (e.target) {
                                updateNotesCounter(e.target);
                            }
                        } catch (err) {
                            console.warn('Notes counter failed:', err);
                        }
                    });
                }
            } catch (error) {
                console.error('Modal event listeners setup failed:', error);
            }
        }
        
        // Sorting functionality
        function sortTable(column) {
            const urlParams = new URLSearchParams(window.location.search);
            const currentSort = urlParams.get('sort');
            const currentOrder = urlParams.get('order') || 'desc';
            
            if (currentSort === column) {
                urlParams.set('order', currentOrder === 'asc' ? 'desc' : 'asc');
            } else {
                urlParams.set('sort', column);
                urlParams.set('order', 'asc');
            }
            
            window.location.search = urlParams.toString();
        }
        
        // Edit modal functionality (안전한 방식)
        function editSiteFromButton(button) {
            if (!button || typeof button.getAttribute !== 'function') {
                console.error('Invalid button element');
                alert('버튼 요소를 찾을 수 없습니다.');
                return;
            }
            
            try {
                const siteInfo = button.getAttribute('data-site-info');
                if (!siteInfo) {
                    throw new Error('Site information not found');
                }
                
                const site = JSON.parse(siteInfo);
                if (!site || typeof site !== 'object') {
                    throw new Error('Invalid site data');
                }
                
                editSite(site);
            } catch (error) {
                console.error('Error parsing site data:', error);
                alert('현장 정보를 불러오는 중 오류가 발생했습니다.');
            }
        }
        
        function editSite(site) {
            if (!site || typeof site !== 'object') {
                console.error('Invalid site data provided');
                alert('잘못된 현장 데이터입니다.');
                return;
            }
            
            
            try {
                // 각 필드에 값 설정 (안전한 방식)
                const fields = [
                    ['edit_site_id', site.id || ''],
                    ['edit_site_name', site.site_name || ''],
                    ['edit_site_address', site.site_address || ''],
                    ['edit_client_name', site.client_name || ''],
                    ['edit_client_phone', site.client_phone || ''],
                    ['edit_project_manager', site.project_manager || ''],
                    ['edit_elevator_count', site.elevator_count || 1],
                    ['edit_notes', site.notes || '']
                ];
                
                fields.forEach(([id, value]) => {
                    const element = safeGetElement(id);
                    if (element) {
                        try {
                            element.value = value;
                            
                            // 셀렉트박스인 경우 옵션 선택
                            if (element.tagName === 'SELECT') {
                                const option = element.querySelector(`option[value="${value}"]`);
                                if (option) {
                                    option.selected = true;
                                }
                            }
                        } catch (e) {
                            console.warn(`Failed to set value for element '${id}':`, e);
                        }
                    } else {
                        console.warn(`Element with id '${id}' not found`);
                    }
                });
                
                // 전화번호 포맷팅 적용 (안전한 방식)
                const phoneInput = safeGetElement('edit_client_phone');
                if (phoneInput && phoneInput.value && typeof phoneInput.dispatchEvent === 'function') {
                    try {
                        phoneInput.dispatchEvent(new Event('input'));
                    } catch (e) {
                        console.warn('Phone formatting trigger failed:', e);
                    }
                }
                
                // 메모 글자 수 카운터 초기화 (안전한 방식)
                const notesTextarea = safeGetElement('edit_notes');
                if (notesTextarea) {
                    try {
                        updateNotesCounter(notesTextarea);
                    } catch (e) {
                        console.warn('Notes counter initialization failed:', e);
                    }
                }
                
                // 모달 표시 (안전한 방식)
                const modal = safeGetElement('editModal');
                if (modal) {
                    try {
                        // 모달 바디 스크롤을 맨 위로 초기화하고 스크롤 확인
                        const modalBody = modal.querySelector('.modal-body');
                        if (modalBody) {
                            // 스크롤 초기화
                            modalBody.scrollTop = 0;
                            
                            // 화면 크기에 따른 동적 높이 설정
                            const screenWidth = window.innerWidth;
                            const screenHeight = window.innerHeight;
                            
                            let maxHeight, minHeight;
                            if (screenWidth >= 1200) {
                                maxHeight = 'calc(90vh - 220px)';
                                minHeight = '400px';
                            } else if (screenWidth <= 768) {
                                maxHeight = 'calc(85vh - 180px)';
                                minHeight = '200px';
                            } else {
                                maxHeight = 'calc(95vh - 200px)';
                                minHeight = '300px';
                            }
                            
                            // 스크롤 스타일 적용
                            modalBody.style.overflowY = 'auto';
                            modalBody.style.maxHeight = maxHeight;
                            modalBody.style.minHeight = minHeight;
                            
                            // 스크롤 상태 확인
                            setTimeout(() => {
                                
                                if (modalBody.scrollHeight > modalBody.clientHeight) {
                                    modalBody.style.overflowY = 'scroll';
                                } else {
                                    modalBody.style.overflowY = 'auto';
                                }
                            }, 100);
                        } else {
                            console.error('Modal body not found');
                        }
                        
                        if (modal.classList && typeof modal.classList.add === 'function') {
                            modal.classList.add('show');

                            // Register modal open for mobile handler
                            if (window.mobileModalHandler) {
                                window.mobileModalHandler.registerModalOpen({
                                    id: `edit_modal_${site.site_name}`,
                                    type: 'css',
                                    element: modal,
                                    closeCallback: () => {
                                        modal.classList.remove('show');
                                    }
                                });
                            }
                        }
                        
                        // 첫 번째 입력 필드에 포커스 (안전한 방식)
                        setTimeout(() => {
                            const firstInput = safeGetElement('edit_site_name');
                            if (firstInput && typeof firstInput.focus === 'function') {
                                try {
                                    firstInput.focus();
                                } catch (e) {
                                    console.warn('Focus failed:', e);
                                }
                            }
                            
                            // 스크롤바 재확인
                            if (modalBody) {
                            }
                        }, 100);
                    } catch (e) {
                        console.error('Modal display failed:', e);
                    }
                } else {
                    console.error('Modal element not found');
                }
                
            } catch (error) {
                console.error('Error opening edit modal:', error);
                alert('모달을 여는 중 오류가 발생했습니다.');
            }
        }
        
        // 메모 글자 수 카운터 (안전한 방식)
        function updateNotesCounter(textarea) {
            if (!textarea || !textarea.parentElement || typeof textarea.value !== 'string') {
                console.warn('Invalid textarea element for notes counter');
                return;
            }
            
            try {
                const helpDiv = textarea.parentElement.querySelector('.linear-input-help');
                if (helpDiv) {
                    const maxLength = 500;
                    const currentLength = textarea.value.length;
                    helpDiv.textContent = `${currentLength}/${maxLength}자 (${maxLength - currentLength}자 남음)`;
                    
                    if (currentLength > maxLength * 0.9) {
                        helpDiv.style.color = 'var(--linear-color-orange)';
                    } else {
                        helpDiv.style.color = 'var(--linear-text-tertiary)';
                    }
                }
            } catch (e) {
                console.warn('Notes counter update failed:', e);
            }
        }
        
        function closeModal() {
            const modal = safeGetElement('editModal');
            if (modal && modal.classList && typeof modal.classList.remove === 'function') {
                try {
                    // Register modal close for mobile handler
                    if (window.mobileModalHandler) {
                        // Get site name from modal content or use generic ID
                        const siteNameInput = safeGetElement('edit_site_name');
                        const siteName = siteNameInput ? siteNameInput.value : 'unknown';
                        window.mobileModalHandler.registerModalClose(`edit_modal_${siteName}`);
                    }

                    modal.classList.remove('show');
                } catch (e) {
                    console.warn('Modal close failed:', e);
                }
            } else {
                console.warn('Modal element not found or invalid for closing');
            }
        }
        
        // Close modal when clicking outside (안전한 방식)
        function setupModalClickOutside() {
            const modal = safeGetElement('editModal');
            if (modal && typeof modal.addEventListener === 'function') {
                try {
                    modal.addEventListener('click', function(e) {
                        if (e && e.target === this) {
                            closeModal();
                        }
                    });
                } catch (e) {
                    console.warn('Modal outside click setup failed:', e);
                }
            }
        }
        
        // 모달 외부 클릭 이벤트 설정
        setupModalClickOutside();
        
        // Delete confirmation
        function deleteSite(siteName) {
            if (confirm(`정말로 "${siteName}" 현장의 모든 측정 데이터를 삭제하시겠습니까?\n\n주의: 이 작업은 되돌릴 수 없습니다.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete';
                form.appendChild(actionInput);

                const siteNameInput = document.createElement('input');
                siteNameInput.type = 'hidden';
                siteNameInput.name = 'site_name';
                siteNameInput.value = siteName;
                form.appendChild(siteNameInput);

                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // 헤더 저장 버튼에서 폼 제출
        function submitEditForm() {
            try {
                const form = safeGetElement('editForm');
                if (form && typeof form.submit === 'function') {
                    // 폼 유효성 검사
                    const siteNameInput = safeGetElement('edit_site_name');
                    if (siteNameInput && !siteNameInput.value.trim()) {
                        alert('현장명을 입력해주세요.');
                        siteNameInput.focus();
                        return false;
                    }
                    
                    // 폼 제출
                    form.submit();
                    return true;
                } else {
                    console.error('Edit form not found or invalid');
                    alert('폼을 찾을 수 없습니다. 페이지를 새로고침 후 다시 시도해주세요.');
                    return false;
                }
            } catch (error) {
                console.error('Form submission failed:', error);
                alert('저장 중 오류가 발생했습니다. 다시 시도해주세요.');
                return false;
            }
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Escape to close modal
            if (e.key === 'Escape') {
                closeModal();
            }
            
            // Ctrl+S (또는 Cmd+S) to save form (모달이 열려있을 때만)
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                const modal = safeGetElement('editModal');
                if (modal && modal.style.display === 'flex') {
                    e.preventDefault(); // 브라우저 기본 저장 동작 방지
                    submitEditForm();
                }
            }
        });
    </script>
</body>
</html>