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
    error_log("Database connection failed in panel_measurement.php: " . $e->getMessage());
    die("데이터베이스 연결에 실패했습니다.");
}

// Check if in edit mode
$edit_mode = false;
$edit_data = null;
$edit_id = $_GET['edit'] ?? ''; 

if (!empty($edit_id)) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, site_name, measurement_date, measurer_name, measurer_id,
                   car_inside_width, car_inside_depth, car_inside_height,
                   material_type, material_thickness,
                   panel_data, transom_data, notes, project_type,
                   panel_corners_excluded, transom_excluded, elevator_count, ipark_check,
                   created_at, updated_at
            FROM panel_measurements
            WHERE id = ?
        ");
        $stmt->execute([$edit_id]);
        $edit_data = $stmt->fetch();

        if ($edit_data) {
            $edit_mode = true;

            // Parse JSON data
            $edit_panel_data = [];
            $edit_transom_data = [];

            if (!empty($edit_data['panel_data'])) {
                $edit_panel_data = json_decode($edit_data['panel_data'], true) ?? [];
            }

            if (!empty($edit_data['transom_data'])) {
                $edit_transom_data = json_decode($edit_data['transom_data'], true) ?? [];
            }

            // Derive defaults for iPark panel widths from saved data when available
            $iparkPanel39WidthDefault = '800';
            $iparkPanel6WidthDefault = '1000';
            try {
                $w3 = isset($edit_panel_data['3']['width']) ? intval($edit_panel_data['3']['width']) : null;
                $w9 = isset($edit_panel_data['9']['width']) ? intval($edit_panel_data['9']['width']) : null;
                $w6 = isset($edit_panel_data['6']['width']) ? intval($edit_panel_data['6']['width']) : null;

                if (!empty($w3)) {
                    $iparkPanel39WidthDefault = (string)$w3;
                } elseif (!empty($w9)) {
                    $iparkPanel39WidthDefault = (string)$w9;
                }
                if (!empty($w6)) {
                    $iparkPanel6WidthDefault = (string)$w6;
                }
            } catch (Exception $e) {
                // If parsing fails, keep hardcoded defaults
            }
        }
    } catch (PDOException $e) {
        error_log("Edit data fetch failed: " . $e->getMessage());
    }
}

// Get site list for dropdown
try {
    $stmt = $pdo->prepare("SELECT DISTINCT site_name FROM panel_measurements ORDER BY site_name");
    $stmt->execute();
    $existing_sites = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $existing_sites = [];
}

// Default values
$defaultSiteName = $edit_mode ? $edit_data['site_name'] : '';
$defaultWidth = $edit_mode ? $edit_data['car_inside_width'] : '1600'; // 신규 작성 시 아이파크 표준 가로
$defaultDepth = $edit_mode ? $edit_data['car_inside_depth'] : '1500'; // 신규 작성 시 아이파크 표준 깊이
$defaultHeight = $edit_mode ? $edit_data['car_inside_height'] : '2700'; // 신규 작성 시 아이파크 표준 높이
$defaultMaterialType = $edit_mode ? $edit_data['material_type'] : '';
$defaultMaterialThickness = $edit_mode ? $edit_data['material_thickness'] : '';
$defaultElevatorCount = $edit_mode ? $edit_data['elevator_count'] : '1';
$defaultNotes = $edit_mode ? $edit_data['notes'] : '';
$defaultIparkCheck = $edit_mode ? ($edit_data['ipark_check'] ?? 0) : 0;
$defaultPanelCornersExcluded = $edit_mode ? ($edit_data['panel_corners_excluded'] ?? 0) : 0;
$defaultTransomExcluded = $edit_mode ? ($edit_data['transom_excluded'] ?? 0) : 0;
$defaultProjectType = $edit_mode ? ($edit_data['project_type'] ?? '신규') : '신규';
?> 
<!DOCTYPE html>   
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title><?= $edit_mode ? '카 판넬 측정 (편집)' : '카 판넬 측정' ?></title>

    <!-- SweetAlert2 CSS/JS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.all.min.js"></script>

    <!-- Linear Theme CSS -->
    <link rel="stylesheet" href="../components/linear-theme.css">
    <link rel="stylesheet" href="../components/linear-components.css">

    <!-- Theme Toggle JavaScript -->
    <script src="../components/linear-theme-toggle.js"></script>

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">

    <style>
        /* ============================================
           🎯 CLEAN RESPONSIVE CSS FRAMEWORK
           모바일 퍼스트 리팩토링 완료
           ============================================ */
        
        /* 기본 스타일 */
        body {
            font-family: var(--linear-font-family);
            background: var(--linear-bg-primary);
            color: var(--linear-text-primary);
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* 반응형 컨테이너 */
        .responsive-container {
            display: block;
            width: 100%;
            max-width: 100%;
        }

        .responsive-card {
            background: var(--linear-bg-secondary);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-md);
            margin-bottom: var(--linear-spacing-lg);
            overflow: hidden;
        }

        .responsive-card-header {
            background: var(--linear-bg-tertiary);
            padding: var(--linear-spacing-md);
            border-bottom: 1px solid var(--linear-border-primary);
        }

        .responsive-card-title {
            margin: 0;
            font-size: var(--linear-font-size-lg);
            font-weight: var(--linear-font-weight-semibold);
            color: var(--linear-text-primary);
        }

        .responsive-card-body {
            padding: var(--linear-spacing-lg);
        }

        .responsive-section {
            margin-bottom: var(--linear-spacing-xl);
        }

        .responsive-section-title {
            font-size: var(--linear-font-size-md);
            font-weight: var(--linear-font-weight-semibold);
            color: var(--linear-text-primary);
            margin-bottom: var(--linear-spacing-md);
            padding-bottom: var(--linear-spacing-sm);
            border-bottom: 1px solid var(--linear-border-secondary);
        }

        .responsive-input-group {
            margin-bottom: var(--linear-spacing-md);
        }

        .responsive-input {
            width: 100%;
            padding: var(--linear-spacing-sm);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-sm);
            font-size: var(--linear-font-size-sm);
            background: var(--linear-bg-primary);
            color: var(--linear-text-primary);
            box-sizing: border-box;
        }

        .responsive-input:focus {
            outline: none;
            border-color: var(--linear-primary);
            box-shadow: 0 0 0 2px var(--linear-primary-alpha);
        }

        .responsive-grid {
            display: grid;
            gap: var(--linear-spacing-md);
        }

        .responsive-grid-2 {
            grid-template-columns: 1fr;
        }

        .responsive-grid-3 {
            grid-template-columns: 1fr;
        }


        /* 반응형 브레이크포인트 */
        @media (min-width: 768px) {
            .responsive-grid-2 {
                grid-template-columns: 1fr 1fr;
            }
            
            .responsive-grid-3 {
                grid-template-columns: 1fr 1fr 1fr;
            }
        }

        @media (min-width: 1024px) {
            .container {
                padding: 40px;
            }
        }

        /* 유틸리티 클래스 */
        .hide-mobile {
            display: block;
        }

        .hide-desktop {
            display: none;
        }

        @media (max-width: 767px) {
            .hide-mobile {
                display: none !important;
            }
            
            .hide-desktop {
                display: block !important;
            }
            
            /* 모바일에서 현장명 자동생성 버튼 스타일 */
            .responsive-input-group div[style*="display: flex"] {
                flex-direction: column !important;
                gap: var(--linear-spacing-sm) !important;
            }
            
            .responsive-input-group div[style*="display: flex"] input {
                width: 100% !important;
            }
            
            .responsive-input-group div[style*="display: flex"] button {
                width: 100% !important;
                justify-content: center !important;
            }
        }

        /* 아이파크 체크박스 */
        #iparkCheckContainer {
            display: block;
            margin-bottom: 20px;
        }

        /* iPark 프로젝트 정보 입력창 */
        #iparkProjectInfo {
            background: var(--linear-bg-tertiary);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-lg);
            padding: var(--linear-spacing-lg);
            margin-top: var(--linear-spacing-md);
            transition: all var(--linear-transition-fast) var(--linear-ease-out);
            overflow: hidden;
        }

        #iparkProjectInfo[style*="display: none"] {
            max-height: 0;
            padding: 0;
            margin: 0;
            border: none;
            opacity: 0;
        }

        #iparkProjectInfo[style*="display: block"] {
            max-height: 500px;
            opacity: 1;
        }

        /* 날짜 선택기 */
        .date-input-container {
            position: relative;
        }

        .date-picker {
            position: absolute;
            top: 100%;
            left: 0;
            background: var(--linear-bg-secondary);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-sm);
            padding: var(--linear-spacing-md);
            z-index: 1000;
            display: none;
            min-width: 280px;
        }

        .date-picker-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--linear-spacing-sm);
        }

        .date-picker-title {
            font-weight: var(--linear-font-weight-semibold);
            color: var(--linear-text-primary);
        }

        .date-picker-nav {
            background: none;
            border: none;
            color: var(--linear-text-primary);
            cursor: pointer;
            padding: var(--linear-spacing-xs);
            border-radius: var(--linear-radius-sm);
        }

        .date-picker-nav:hover {
            background: var(--linear-bg-tertiary);
        }

        .date-picker-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 2px;
        }

        .date-picker-day {
            padding: var(--linear-spacing-xs);
            text-align: center;
            cursor: pointer;
            border-radius: var(--linear-radius-sm);
            font-size: var(--linear-font-size-xs);
        }

        .date-picker-day:hover {
            background: var(--linear-bg-tertiary);
        }

        .date-picker-day.selected {
            background: var(--linear-primary);
            color: white;
        }

        .date-picker-day.other-month {
            color: var(--linear-text-tertiary);
        }

        /* 판넬 시각화 */
        .car-wall-container {
            position: relative;
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            background-color: var(--linear-bg-secondary);
            border: 2px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-lg);
            overflow: hidden;
            box-shadow: var(--linear-shadow-low);
        }
        
        .car-wall {
            width: 100%;
            height: 500px;
            position: relative;
            background: linear-gradient(135deg, var(--linear-bg-tertiary), var(--linear-bg-secondary));
            border: 1px solid var(--linear-border-secondary);
        }
        
        .panel {
            position: absolute;
            border: 2px solid var(--linear-border-primary);
            background-color: var(--linear-bg-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: var(--linear-font-weight-semibold);
            color: var(--linear-text-primary);
            cursor: pointer;
            transition: all var(--linear-transition-fast) var(--linear-ease-out);
            font-size: var(--linear-text-small);
        }
        
        .panel:hover {
            background-color: var(--linear-brand-primary-tint);
            border-color: var(--linear-brand-primary);
            transform: scale(1.05);
        }
        
        .panel.selected {
            background-color: var(--linear-brand-primary);
            color: white;
            border-color: var(--linear-brand-primary);
            border-width: 3px;
            transform: scale(1.1);
            z-index: 10;
        }
        
        /* Panel positioning */
        /* 상단 패널 5,6,7 - 균등 분할 */
        .panel-5 { top: 2%; left: 5%; width: 28%; height: 7%; }
        .panel-6 { top: 2%; left: 35%; width: 30%; height: 7%; }
        .panel-7 { top: 2%; left: 67%; width: 28%; height: 7%; }
        
        /* 좌측 패널 2,3,4 - 균등 분할 */
        .panel-4 { top: 12%; left: 2%; width: 6%; height: 23%; }
        .panel-3 { top: 37.5%; left: 2%; width: 6%; height: 23%; }
        .panel-2 { top: 63%; left: 2%; width: 6%; height: 23%; }
        
        /* 우측 패널 8,9,10 - 균등 분할 */
        .panel-8 { top: 12%; left: 92%; width: 6%; height: 23%; }
        .panel-9 { top: 37.5%; left: 92%; width: 6%; height: 23%; }
        .panel-10 { top: 63%; left: 92%; width: 6%; height: 23%; }
        /* 하단 패널 1,12,11 - 균등 분할 */
        .panel-1 { bottom: 2%; left: 5%; width: 28%; height: 8%; }
        .panel-12 { bottom: 2%; left: 35%; width: 30%; height: 8%; background-color: var(--linear-accent-bg); border-color: var(--linear-accent-border); }
        .panel-11 { bottom: 2%; left: 67%; width: 28%; height: 8%; }
        
        /* Transom 패널 특별 스타일 */
        .transom-panel {
            background-color: var(--linear-accent-bg, #f0f9ff) !important;
            border-color: var(--linear-accent-border, #0ea5e9) !important;
            color: var(--linear-accent-text, #0369a1) !important;
            font-weight: var(--linear-font-weight-bold) !important;
        }
        
        .transom-panel:hover {
            background-color: var(--linear-accent-bg-hover, #e0f2fe) !important;
            border-color: var(--linear-accent-border-hover, #0284c7) !important;
        }
        
        .transom-panel.selected {
            background-color: var(--linear-accent-primary, #0ea5e9) !important;
            color: var(--linear-text-on-accent, #ffffff) !important;
        }
        
        .measurement-form {
            background-color: var(--linear-bg-primary);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-lg);
            padding: var(--linear-spacing-xl);
            box-shadow: var(--linear-shadow-low);
            height: fit-content;
        }
        
        .form-section {
            margin-bottom: 5px;
        }
        
        .form-section-title {
            font-size: var(--linear-text-large);
            font-weight: var(--linear-font-weight-semibold);
            color: var(--linear-brand-primary);
            margin-bottom: var(--linear-spacing-md);
        }
        
        .selected-panel-info {
            background: linear-gradient(135deg, var(--linear-brand-primary), var(--linear-brand-primary-hover));
            color: white;
            border-radius: var(--linear-radius-md);
            padding: var(--linear-spacing-md);
            margin-bottom: var(--linear-spacing-lg);
        }
        
        .selected-panel-info h6 {
            margin-bottom: var(--linear-spacing-sm);
            font-weight: var(--linear-font-weight-semibold);
        }
        
        .info-text {
            color: var(--linear-text-tertiary);
            font-size: var(--linear-text-small);
            margin-top: var(--linear-spacing-md);
        }
        
        .validation-error {
            border-color: var(--linear-color-red) !important;
            box-shadow: 0 0 0 0.2rem rgba(235, 87, 87, 0.25) !important;
        }
        
        .validation-success {
            border-color: var(--linear-color-green) !important;
            box-shadow: 0 0 0 0.2rem rgba(76, 183, 130, 0.25) !important;
        }
        
        /* W x D x H 입력 그리드 */
        .dimensions-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: var(--linear-spacing-md);
            margin-top: var(--linear-spacing-sm);
        }
        
        .dimensions-description {
            background: var(--linear-bg-tertiary);
            padding: var(--linear-spacing-md);
            border-radius: var(--linear-radius-md);
            margin-bottom: var(--linear-spacing-md);
            border-left: 4px solid var(--linear-brand-primary);
        }
        
        .dimensions-description p {
            margin: 0;
            font-size: var(--linear-text-small);
            color: var(--linear-text-secondary);
            line-height: 1.5;
        }
        
        .dimension-label {
            display: flex;
            align-items: center;
            gap: var(--linear-spacing-xs);
            margin-bottom: var(--linear-spacing-xs);
            font-weight: var(--linear-font-weight-medium);
            color: var(--linear-text-primary);
        }
        
        .dimension-icon {
            font-size: 1.1rem;
            color: var(--linear-brand-primary);
        }
        
        /* W x D x H 입력 필드 너비 제한 */
        .dimensions-grid input[type="number"] {
            width: 60px !important;
            max-width: 60px;
            text-align: center;
            font-size: var(--linear-text-body);
            font-weight: var(--linear-font-weight-medium);
        }
        
        /* 날짜 입력 필드 스타일 */
        .date-input-container {
            position: relative;
            display: inline-block;
            width: 100%;
        }
        
        /* 모바일 터치 이벤트 개선 */
        input[name="measurement_date"] {
            touch-action: manipulation;
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            -khtml-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        .panel.has-info {
            background: var(--linear-success);
            color: white;
        }

        /* 패널 치수 표시 */
        .panel-dimensions {
            position: absolute;
            bottom: 2px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 1.2rem;
            font-weight: var(--linear-font-weight-medium);
            color: var(--linear-text-primary);
            background: rgba(255, 255, 255, 0.9);
            padding: 2px 6px;
            border-radius: 3px;
            white-space: nowrap;
            z-index: 5;
            pointer-events: none;
            line-height: 1.2;
        }

        /* 좌측 패널 (2,3,4번) - 우측으로 이동 */
        .panel-2 .panel-dimensions,
        .panel-3 .panel-dimensions,
        .panel-4 .panel-dimensions {
            left: calc(50% + 2em);
        }

        /* 우측 패널 (8,9,10번) - 좌측으로 이동 */
        .panel-8 .panel-dimensions,
        .panel-9 .panel-dimensions,
        .panel-10 .panel-dimensions {
            left: calc(50% - 2em);
        }

        /* 3번, 9번 패널 타공 정보가 있을 때 추가 조정 */
        .panel-3.has-drilling .panel-dimensions {
            left: calc(50% + 7em) !important; /* 우측으로 더 많이 이동하여 텍스트 잘림 방지 */
            font-size: 0.9rem !important; /* 글자 크기 더 줄임 */
            max-width: 150px !important; /* 최대 너비 확장 */
            text-align: left !important; /* 텍스트 좌측 정렬로 변경 */
            white-space: normal !important; /* 텍스트 줄바꿈 허용 */
            line-height: 1.1 !important; /* 줄 간격 조정 */
        }

        .panel-9.has-drilling .panel-dimensions {
            left: calc(50% - 7em) !important; /* 좌측으로 더 많이 이동하여 텍스트 잘림 방지 */
            font-size: 0.9rem !important; /* 글자 크기 더 줄임 */
            max-width: 150px !important; /* 최대 너비 확장 */
            text-align: left !important; /* 텍스트 좌측 정렬로 변경 */
            white-space: normal !important; /* 텍스트 줄바꿈 허용 */
            line-height: 1.1 !important; /* 줄 간격 조정 */
        }

        .panel.has-info .panel-dimensions {
            color: white;
            background: rgba(0, 0, 0, 0.7);
            padding: 4px 8px;
            border-radius: 4px;
            white-space: normal;
            overflow: visible;
            text-overflow: unset;
            word-wrap: break-word;
        }

        /* 트랜섬 패널 치수 표시 */
        .transom-panel .panel-dimensions {
            color: var(--linear-accent-text);
            background: rgba(255, 255, 255, 0.9);
        }

        .transom-panel.has-info .panel-dimensions {
            color: white;
            background: rgba(0, 0, 0, 0.7);
        }

        /* Panel Modal Styles */
        .panel-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--linear-spacing-lg);
            box-sizing: border-box;
        }
        
        .panel-modal-backdrop {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(2px);
        }
        
        .panel-modal-content {
            position: relative;
            background: var(--linear-bg-primary);
            border-radius: var(--linear-radius-lg);
            box-shadow: var(--linear-shadow-high);
            width: 100%;
            max-width: 480px;
            max-height: 90vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .panel-modal-header {
            padding: var(--linear-spacing-lg);
            border-bottom: 1px solid var(--linear-border-primary);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--linear-bg-secondary);
        }
        
        .panel-modal-header h3 {
            margin: 0;
            font-size: var(--linear-text-title2);
            font-weight: var(--linear-font-weight-semibold);
            color: var(--linear-text-primary);
        }
        
        .panel-modal-close {
            background: none;
            border: none;
            padding: var(--linear-spacing-xs);
            color: var(--linear-text-secondary);
            cursor: pointer;
            border-radius: var(--linear-radius-sm);
            transition: all var(--linear-transition-fast) var(--linear-ease-out);
        }
        
        .panel-modal-close:hover {
            background: var(--linear-bg-tertiary);
            color: var(--linear-text-primary);
        }
        
        .panel-modal-body {
            padding: var(--linear-spacing-lg);
            overflow-y: auto;
            flex: 1;
        }
        
        .panel-modal-field {
            margin-bottom: var(--linear-spacing-lg);
        }
        
        .panel-modal-label {
            display: block;
            margin-bottom: var(--linear-spacing-sm);
            font-weight: var(--linear-font-weight-medium);
            color: var(--linear-text-primary);
            font-size: var(--linear-text-small);
        }
        
        .panel-modal-footer {
            padding: var(--linear-spacing-lg);
            border-top: 1px solid var(--linear-border-primary);
            display: flex;
            gap: var(--linear-spacing-md);
            justify-content: flex-end;
            background: var(--linear-bg-secondary);
        }
        
        /* Mobile optimization */
        @media (max-width: 768px) {
            .panel-modal {
                padding: var(--linear-spacing-sm);
            }
            
            .panel-modal-content {
                max-width: 100%;
                max-height: 95vh;
            }
            
            .panel-modal-header,
            .panel-modal-body,
            .panel-modal-footer {
                padding: var(--linear-spacing-md);
            }
        }

        /* Swal high z-index for copy messages */
        .swal-high-zindex {
            z-index: 10001 !important;
        }

        /* 버튼 그룹 */
        .button-group {
            display: flex;
            gap: var(--linear-spacing-sm);
            flex-wrap: wrap;
            justify-content: center;
            margin-top: var(--linear-spacing-lg);
        }

        @media (max-width: 767px) {
            .button-group {
                flex-direction: column;
            }
            
            .button-group .linear-btn {
                width: 100%;
            }
        }

        /* 폼 스타일 */
        .linear-label {
            display: block;
            margin-bottom: var(--linear-spacing-xs);
            font-weight: var(--linear-font-weight-medium);
            color: var(--linear-text-primary);
        }

        .linear-input {
            width: 100%;
            padding: var(--linear-spacing-sm);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-sm);
            font-size: var(--linear-font-size-sm);
            background: var(--linear-bg-primary);
            color: var(--linear-text-primary);
            box-sizing: border-box;
        }

        .linear-input:focus {
            outline: none;
            border-color: var(--linear-primary);
            box-shadow: 0 0 0 2px var(--linear-primary-alpha);
        }

        /* 테마 토글 - LinearNavigation에서 처리됨 */
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
    ->addAction('<span style="margin-right: 1rem; color: var(--linear-text-secondary); white-space:nowrap;">' . htmlspecialchars($_SESSION["name"]) . '님</span>')
    ->addAction('<a href="../login/logout.php" style="color: var(--linear-text-secondary); text-decoration: none; white-space:nowrap;">로그아웃</a>')
    ->fixed();

    echo $nav;
    ?>
    <div class="container" style="margin-top: var(--linear-header-height);">
        <!-- Header -->
        <div class="responsive-card">
            <div class="responsive-card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h1 class="responsive-card-title">
                    <i class="bi bi-rulers"></i>
                    <?= $edit_mode ? '카 판넬 측정 (편집)' : '카 판넬 측정' ?>
                </h1>
                
                <?php if ($edit_mode): ?>
                <div class="title-actions" style="display: flex; gap: var(--linear-spacing-sm);">
                    <button type="button" id="copyDataBtn" class="linear-btn linear-btn-outline linear-btn-sm" 
                            title="현재 데이터를 복사해서 신규 작성">
                        <i class="bi bi-copy"></i> 복사
                    </button>
                    <button type="button" id="deleteDataBtn" class="linear-btn linear-btn-danger linear-btn-sm" 
                            title="현재 데이터를 삭제">
                        <i class="bi bi-trash"></i> 삭제
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Form -->
        <div class="responsive-container">
            <form id="measurementForm" action="save_panel_measurement.php" method="POST">
                <?php if ($edit_mode): ?>
                    <input type="hidden" name="edit_id" value="<?= $edit_id ?>">
                <?php endif; ?>

                <!-- 현장 정보 입력 -->
                <div class="responsive-card">
                    <div class="responsive-card-header">
                        <h3 class="responsive-card-title"><i class="bi bi-building"></i> 현장 정보 입력</h3>
                    </div>
                    <div class="responsive-card-body">
                        

                        <!-- 현장명 -->
                        <div class="responsive-input-group">
                            <label for="siteName" class="linear-label">
                                현장명 <span style="color: var(--linear-color-red);">*</span>
                            </label>
                            <div style="display: flex; gap: var(--linear-spacing-sm); align-items: center;">
                                <input type="text"
                                       id="siteName"
                                       name="site_name"
                                       class="linear-input responsive-input"
                                       placeholder="현장명을 입력하세요"
                                       value="<?= htmlspecialchars($defaultSiteName) ?>"
                                       list="existingSites"
                                       required
                                       style="flex: 1;">
                                <button type="button" id="generateSiteNameBtn" class="linear-btn linear-btn-secondary" style="white-space: nowrap;">
                                    <i class="bi bi-magic"></i> 자동생성
                                </button>
                            </div>
                            <datalist id="existingSites">
                                <?php foreach ($existing_sites as $site): ?>
                                    <option value="<?= htmlspecialchars($site) ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>

                        <!-- 측정일자/측정자 -->
                        <div class="responsive-grid responsive-grid-2">
                            <!-- 측정일자 -->
                            <div class="responsive-input-group">
                                <label for="measurementDate" class="linear-label">
                                    측정일자 <span style="color: var(--linear-color-red);">*</span>
                                </label>
                                <div class="date-input-container">
                                    <input type="text"
                                           id="measurementDate"
                                           name="measurement_date"
                                           class="linear-input responsive-input"
                                           placeholder="날짜를 선택하세요"
                                           value="<?= $edit_mode ? $edit_data['measurement_date'] : date('Y-m-d') ?>"
                                           readonly
                                           required
                                           style="cursor: pointer;">
                                    <div id="datePicker" class="date-picker">
                                        <!-- Date picker will be populated by JavaScript -->
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 측정자 -->
                            <div class="responsive-input-group">
                                <label for="measurer" class="linear-label">
                                    측정자 <span style="color: var(--linear-color-red);">*</span>
                                </label>
                                <?php
                                $defaultMeasurer = ($edit_mode && isset($edit_data['measurer_name']) && !empty($edit_data['measurer_name']))
                                    ? $edit_data['measurer_name']
                                    : $_SESSION["name"];
                                ?>
                                <input type="text"
                                       id="measurer"
                                       name="measurer"
                                       class="linear-input responsive-input"
                                       value="<?= htmlspecialchars($defaultMeasurer) ?>"
                                       required>
                            </div>
                        </div>

                        <!-- 카 내부 W x D x H -->
                        <div class="responsive-section">
                            <h6 class="responsive-section-title">카 내부 W x D x H</h6>
                            <div id="carInsideInputs" class="responsive-grid responsive-grid-3">
                                <!-- Car Inside Width -->
                                <div class="responsive-input-group">
                                    <label for="carInsideWidth" class="linear-label">
                                        <i class="bi bi-arrows-horizontal" style="margin-right: 4px;"></i>
                                        가로 (W) <small style="color: var(--linear-text-tertiary);">mm</small>
                                    </label>
                                    <input type="number" id="carInsideWidth" name="car_inside_width" class="linear-input responsive-input" value="<?= htmlspecialchars($defaultWidth) ?>" min="800" max="2500" step="1">
                                </div>
                                <!-- Car Inside Depth -->
                                <div class="responsive-input-group">
                                    <label for="carInsideDepth" class="linear-label">
                                        <i class="bi bi-arrow-up-down" style="margin-right: 4px;"></i>
                                        깊이 (D) <small style="color: var(--linear-text-tertiary);">mm</small>
                                    </label>
                                    <input type="number" id="carInsideDepth" name="car_inside_depth" class="linear-input responsive-input" value="<?= htmlspecialchars($defaultDepth) ?>" min="800" max="3000" step="1">
                                </div>
                                <!-- Car Inside Height -->
                                <div class="responsive-input-group">
                                    <label for="carInsideHeight" class="linear-label">
                                        <i class="bi bi-arrows-vertical" style="margin-right: 4px;"></i>
                                        높이 (H) <small style="color: var(--linear-text-tertiary);">mm</small>
                                    </label>
                                    <input type="number" id="carInsideHeight" name="car_inside_height" class="linear-input responsive-input" value="<?= htmlspecialchars($defaultHeight) ?>" min="2000" max="3000" step="1">
                                </div>
                            </div>
                        </div>

                        <!-- 재질 정보 -->
                        <div class="responsive-section">
                            <!-- Material Type, Thickness, and Elevator Count -->
                            <div class="responsive-grid responsive-grid-3">
                                <div class="responsive-input-group">
                                    <label for="materialType" class="linear-label">의장재질</label>
                                    <select class="linear-input responsive-input" id="materialType" name="material_type">
                                        <option value="">선택하세요</option>
                                        <option value="SUS H/L" <?= $defaultMaterialType === 'SUS H/L' ? 'selected' : '' ?>>SUS H/L</option>
                                        <option value="SUS MR" <?= $defaultMaterialType === 'SUS MR' ? 'selected' : '' ?>>SUS MR</option>
                                        <option value="강판" <?= $defaultMaterialType === '강판' ? 'selected' : '' ?>>강판</option>
                                        <option value="도장품" <?= $defaultMaterialType === '도장품' ? 'selected' : '' ?>>도장품</option>
                                        <option value="시트지" <?= $defaultMaterialType === '시트지' ? 'selected' : '' ?>>시트지</option>
                                        <option value="기타" <?= $defaultMaterialType === '기타' ? 'selected' : '' ?>>기타</option>
                                    </select>
                                    <div id="mainCustomMaterialContainer" style="display: none; margin-top: var(--linear-spacing-xs);">
                                        <input type="text" class="linear-input responsive-input" id="materialTypeCustom" name="material_type_custom" placeholder="재질명을 입력하세요" style="background-color: var(--linear-bg-primary); cursor: text;">
                                    </div>
                                </div>
                                <div class="responsive-input-group">
                                    <label for="materialThickness" class="linear-label">두께 <span style="color: var(--linear-text-tertiary);">mm</span></label>
                                    <select class="linear-input responsive-input" id="materialThickness" name="material_thickness">
                                        <option value="">선택하세요</option>
                                        <option value="0.8" <?= $defaultMaterialThickness === '0.8' ? 'selected' : '' ?>>0.8mm</option>
                                        <option value="1.0" <?= $defaultMaterialThickness === '1.0' ? 'selected' : '' ?>>1.0mm</option>
                                        <option value="1.2" <?= $defaultMaterialThickness === '1.2' ? 'selected' : '' ?>>1.2mm</option>
                                        <option value="1.5" <?= $defaultMaterialThickness === '1.5' ? 'selected' : '' ?>>1.5mm</option>
                                        <option value="1.6" <?= $defaultMaterialThickness === '1.6' ? 'selected' : '' ?>>1.6mm</option>
                                    </select>
                                </div>
                                <div class="responsive-input-group">
                                    <label for="elevatorCount" class="linear-label">엘리베이터 대수</label>
                                    <input type="number" class="linear-input responsive-input" id="elevatorCount" name="elevator_count" value="<?= htmlspecialchars($defaultElevatorCount) ?>" min="1" max="20" step="1" placeholder="1">
                                </div>
                            </div>
                            
                            <!-- Notes -->
                            <div class="responsive-input-group">
                                <label for="notes" class="linear-label">특이사항</label>
                                <textarea class="linear-input responsive-input" id="notes" name="notes" rows="2" placeholder="측정 시 특이사항이나 주의사항을 입력하세요"><?= htmlspecialchars($defaultNotes) ?></textarea>
                            </div>
                        </div>

                        <!-- 아이파크 체크박스 -->
                        <div id="iparkCheckContainer" class="responsive-input-group">
                            <label class="linear-label">
                                <input type="checkbox" id="iparkCheck" name="ipark_check" value="1" <?= $defaultIparkCheck ? 'checked' : '' ?>>
                                아이파크 신규
                            </label>
                        </div>

                        <!-- 아이파크 프로젝트 정보 입력창 -->
                        <div id="iparkProjectInfo" class="responsive-section" style="display: none;">
                            <h6 class="responsive-section-title">아이파크 패널 설정</h6>
                            <div class="responsive-grid responsive-grid-2">
                                <div class="responsive-input-group">
                                    <label for="iparkPanel39Width" class="linear-label">3,9번 패널 폭 (mm)</label>
                                    <input type="number" class="linear-input responsive-input" id="iparkPanel39Width" name="ipark_panel_39_width" 
                                           placeholder="800" min="100" max="2000" step="10" value="<?= isset($iparkPanel39WidthDefault) ? htmlspecialchars($iparkPanel39WidthDefault) : '800' ?>">
                                    <small style="color: var(--linear-text-tertiary); font-size: 0.8rem;">※ 3번과 9번 패널의 폭</small>
                                </div>
                                <div class="responsive-input-group">
                                    <label for="iparkPanel6Width" class="linear-label">6번 패널 폭 (mm)</label>
                                    <input type="number" class="linear-input responsive-input" id="iparkPanel6Width" name="ipark_panel_6_width" 
                                           placeholder="1000" min="100" max="2000" step="10" value="<?= isset($iparkPanel6WidthDefault) ? htmlspecialchars($iparkPanel6WidthDefault) : '1000' ?>">
                                    <small style="color: var(--linear-text-tertiary); font-size: 0.8rem;">※ 6번 패널의 폭</small>
                                </div>
                                </div>
                            <div style="margin-top: var(--linear-spacing-md); padding: var(--linear-spacing-md); background: var(--linear-bg-secondary); border-radius: var(--linear-radius-md);">
                                <small style="color: var(--linear-text-tertiary); font-size: 0.8rem;">
                                    <i class="bi bi-info-circle"></i> 
                                    입력한 값으로 2번부터 10번까지의 패널 폭이 자동으로 계산됩니다.
                                </small>
                                </div>
                            <div style="margin-top: var(--linear-spacing-md); display: flex; justify-content: flex-end;">
                                <button type="button" id="applyIparkSettingsBtn" class="linear-btn linear-btn-primary">
                                    <i class="bi bi-check-circle"></i> 설정 적용
                                </button>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Car Wall Visual (판넬 시각화) -->
                <div class="car-wall-section">
                    <?php 
                    require_once '../components/LinearCard.php';

                    // Build header content with PHP variables
                    $headerContent =
                        '<div style="display: flex; align-items: center; flex-wrap: wrap; gap: 10px; width: 100%;">
                            <i class="bi bi-eye"></i><span>시각화</span>
                            <div style="display: inline-flex; align-items: center; background: var(--linear-bg-secondary); border-radius: var(--linear-radius-md); padding: 2px;">
                                <button type="button" id="newBtn" class="linear-btn linear-btn-sm"
                                        style="font-size: 0.75rem; padding: 4px 8px; margin: 0; border-radius: var(--linear-radius-sm); background-color: var(--linear-brand-primary); color: white;">
                                    신규
                                </button>
                                <button type="button" id="modBtn" class="linear-btn linear-btn-sm linear-btn-outline"
                                        style="font-size: 0.75rem; padding: 4px 8px; margin: 0; border-radius: var(--linear-radius-sm);">
                                    MOD
                                </button>
                            </div>
                            <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                                <label class="checkbox-container" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" id="excludePanelCorners" name="panel_corners_excluded" value="1"' .
                        ($defaultPanelCornersExcluded ? ' checked' : '') .
                        ' style="margin: 0; width: 16px; height: 16px;">
                                    <span style="font-size: 0.9rem; user-select: none; white-space: nowrap;">1,11번 제외</span>
                                </label>
                                <label class="checkbox-container" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" id="excludeTransom" name="transom_excluded" value="1"' .
                        ($defaultTransomExcluded ? ' checked' : '') .
                        ' style="margin: 0; width: 16px; height: 16px;">
                                    <span style="font-size: 0.9rem; user-select: none; white-space: nowrap;">트랜섬 제외</span>
                                </label>
                            </div>
                            <span id="panelCount" style="font-size: 0.9rem; color: var(--linear-text-secondary); white-space: nowrap;">
                                (12매)
                            </span>
                         </div>';

                    $carWallCard = LinearCard::withHeader($headerContent,
                        '
                        <div class="car-wall-container">
                            <div class="car-wall" id="carWall">
                                <!-- Panels 1-11 and Transom 12 -->
                                <div class="panel panel-1" data-panel="1" title="판넬 1">1<div class="panel-dimensions"></div></div>
                                <div class="panel panel-2" data-panel="2" title="판넬 2">2<div class="panel-dimensions"></div></div>
                                <div class="panel panel-3" data-panel="3" title="판넬 3">3<div class="panel-dimensions"></div></div>
                                <div class="panel panel-4" data-panel="4" title="판넬 4">4<div class="panel-dimensions"></div></div>
                                <div class="panel panel-5" data-panel="5" title="판넬 5">5<div class="panel-dimensions"></div></div>
                                <div class="panel panel-6" data-panel="6" title="판넬 6">6<div class="panel-dimensions"></div></div>
                                <div class="panel panel-7" data-panel="7" title="판넬 7">7<div class="panel-dimensions"></div></div>
                                <div class="panel panel-8" data-panel="8" title="판넬 8">8<div class="panel-dimensions"></div></div>
                                <div class="panel panel-9" data-panel="9" title="판넬 9">9<div class="panel-dimensions"></div></div>
                                <div class="panel panel-10" data-panel="10" title="판넬 10">10<div class="panel-dimensions"></div></div>
                                <div class="panel panel-11" data-panel="11" title="판넬 11">11<div class="panel-dimensions"></div></div>
                                <div class="panel panel-12 transom-panel" data-panel="12" title="Transom 12">T<div class="panel-dimensions"></div></div>
                            </div>
                        </div>
                        <div class="info-text" id="panelInfo">
                            <i class="bi bi-info-circle"></i>
                            측정할 판넬을 클릭하세요. 판넬 1-11은 내벽 의장재질 영역입니다. W, D 공차는 ±3입니다.
                        </div>
                        '
                    );
                    
                    echo $carWallCard;
                    ?>
                </div>

                <!-- Hidden Fields -->
                <input type="hidden" id="panelJsonData" name="panel_data" value="">
                <input type="hidden" id="transomJsonData" name="transom_data" value="">
                <input type="hidden" id="projectType" name="project_type" value="<?= htmlspecialchars($defaultProjectType) ?>">

                <!-- Action Buttons -->
                <div class="button-group">
                    <button type="button" id="validateBtn" class="linear-btn linear-btn-secondary">
                        <i class="bi bi-check-circle"></i> 측정값 검증
                    </button>
                    <button type="submit" id="saveBtn" class="linear-btn linear-btn-primary">
                        <i class="bi bi-save"></i> 측정 저장
                    </button>
                    <button type="button" id="backBtn" class="linear-btn linear-btn-secondary">
                        <i class="bi bi-arrow-left"></i> 돌아가기
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Panel Modal -->
    <div id="panelModal" class="panel-modal" style="display: none;">
        <div class="panel-modal-backdrop"></div>
        <div class="panel-modal-content">
            <div class="panel-modal-header">
                <h3 id="panelModalTitle">패널 정보</h3>
                <!-- 복사 버튼 (1번, 11번 패널용) -->
                <div id="copyButtonContainer" style="display: none; margin-right: auto; margin-left: var(--linear-spacing-lg);">
                    <button type="button" class="linear-btn linear-btn-outline linear-btn-xs" id="copyBtn" title="패널 데이터 복사">
                        <i class="bi bi-copy"></i> <span id="copyText">1번으로 복사</span>
                    </button>
                </div>
                <button type="button" class="panel-modal-close" id="panelModalClose">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="panel-modal-body">
                <form id="panelInfoForm">
                    <input type="hidden" id="modalPanelNumber" name="panel_number">
                    
                    <!-- Panel Type (only for transom) -->
                    <div id="panelTypeSection" style="display: none;">
                        <label class="panel-modal-label">패널 타입</label>
                        <select class="linear-input" id="modalPanelType" name="panel_type">
                            <option value="panel">일반 패널</option>
                            <option value="transom">Transom</option>
                        </select>
                    </div>
                    
                    <!-- Material Type and Thickness (한 행에 배치) -->
                    <div class="panel-modal-field">
                        <label class="panel-modal-label">재질 및 두께</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--linear-spacing-md);">
                            <div>
                                <label class="panel-modal-label" style="font-size: 0.85rem; margin-bottom: var(--linear-spacing-xs);">재질 (부모창 연동)</label>
                                <select class="linear-input" id="modalMaterialType" name="material_type" style="background-color: var(--linear-bg-secondary); cursor: not-allowed;">
                                    <option value="">재질을 선택하세요</option>
                                    <option value="SUS H/L">SUS H/L</option>
                                    <option value="SUS MR">SUS MR</option>
                                    <option value="강판">강판</option>
                                    <option value="도장품">도장품</option>
                                    <option value="시트지">시트지</option>
                                    <option value="기타">기타</option>
                                </select>
                                <div id="customMaterialContainer" style="display: none; margin-top: var(--linear-spacing-xs);">
                                    <input type="text" class="linear-input" id="modalMaterialTypeCustom" name="material_type_custom" placeholder="재질명을 입력하세요" style="background-color: var(--linear-bg-primary); cursor: text;">
                                </div>
                            </div>
                            <div>
                                <label class="panel-modal-label" style="font-size: 0.85rem; margin-bottom: var(--linear-spacing-xs);">두께 (수정 가능)</label>
                                <select class="linear-input" id="modalPanelThickness" name="panel_thickness">
                                    <option value="">두께 선택</option>
                                    <option value="0.8">0.8</option>
                                    <option value="1.0">1.0</option>
                                    <option value="1.2">1.2</option>
                                    <option value="1.5">1.5</option>
                                    <option value="1.6">1.6</option>
                                </select>
                            </div>
                        </div>
                        <small style="color: var(--linear-text-tertiary); font-size: 0.8rem; margin-top: 4px; display: block;">※ 재질은 부모창과 연동, 두께는 개별 수정 가능</small>
                    </div>

                    <!-- Panel Type for corner panels (1번, 11번 only) -->
                    <div id="cornerPanelTypeSection" class="panel-modal-field" style="display: none;">
                        <label class="panel-modal-label">패널 타입</label>
                        <div style="display: flex; gap: var(--linear-spacing-lg); align-items: center;">
                            <label style="display: flex; align-items: center; gap: var(--linear-spacing-xs); cursor: pointer;">
                                <input type="radio" name="cornerPanelType" id="modalPanelTypeIntegrated" value="일체형" style="transform: scale(1.2);">
                                <span>일체형</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: var(--linear-spacing-xs); cursor: pointer;">
                                <input type="radio" name="cornerPanelType" id="modalPanelTypeSeparate" value="분리형" style="transform: scale(1.2);">
                                <span>분리형</span>
                            </label>
                        </div>
                        <small style="color: var(--linear-text-tertiary); font-size: 0.8rem; margin-top: 4px; display: block;">※ 1번, 11번 패널의 카주와 일체여부 선택하세요.</small>
                    </div>

                    <!-- Corner Panel Details (1번, 11번 only) -->
                    <div id="cornerPanelDetailsSection" class="panel-modal-field" style="display: none;">
                        <label class="panel-modal-label">패널 상세 정보</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--linear-spacing-md);">
                            <div>
                                <label class="panel-modal-label" style="font-size: 0.85rem; margin-bottom: var(--linear-spacing-xs);">전면두께 (mm)</label>
                                <input type="number" id="modalFrontThickness" step="1" min="0"
                                       style="width: 80px; padding: var(--linear-spacing-sm); border: 1px solid var(--linear-border-primary); border-radius: var(--linear-radius-md); background-color: var(--linear-bg-primary); color: var(--linear-text-primary);">
                            </div>
                            <div>
                                <label class="panel-modal-label" style="font-size: 0.85rem; margin-bottom: var(--linear-spacing-xs);">전면날개 (mm)</label>
                                <input type="number" id="modalFrontWing" step="1" min="0"
                                       style="width: 80px; padding: var(--linear-spacing-sm); border: 1px solid var(--linear-border-primary); border-radius: var(--linear-radius-md); background-color: var(--linear-bg-primary); color: var(--linear-text-primary);">
                            </div>
                            <div>
                                <label class="panel-modal-label" style="font-size: 0.85rem; margin-bottom: var(--linear-spacing-xs);">후면두께 (mm)</label>
                                <input type="number" id="modalBackThickness" step="1" min="0"
                                       style="width: 80px; padding: var(--linear-spacing-sm); border: 1px solid var(--linear-border-primary); border-radius: var(--linear-radius-md); background-color: var(--linear-bg-primary); color: var(--linear-text-primary);">
                            </div>
                            <div>
                                <label class="panel-modal-label" style="font-size: 0.85rem; margin-bottom: var(--linear-spacing-xs);">후면날개 (mm)</label>
                                <input type="number" id="modalBackWing" step="1" min="0"
                                       style="width: 80px; padding: var(--linear-spacing-sm); border: 1px solid var(--linear-border-primary); border-radius: var(--linear-radius-md); background-color: var(--linear-bg-primary); color: var(--linear-text-primary);">
                            </div>
                        </div>
                        <small style="color: var(--linear-text-tertiary); font-size: 0.8rem; margin-top: 4px; display: block;">※ 1번, 11번 패널의 두께 및 날개 치수를 입력하세요</small>
                    </div>

                    <!-- Dimensions -->
                    <div class="panel-modal-field">
                        <label class="panel-modal-label" id="sizeLabel">크기 (W폭×H높이) mm</label>
                        <div id="sizeInputContainer" style="display: grid; grid-template-columns: 104px auto 104px; gap: var(--linear-spacing-sm); align-items: center; justify-content: start;">
                            <input type="number" class="linear-input" id="modalPanelWidth" name="panel_width" placeholder="가로" min="50" max="3000" step="1">
                            <span id="multiplySign" style="color: var(--linear-text-secondary);">×</span>
                            <input type="number" class="linear-input" id="modalPanelHeight" name="panel_height" placeholder="세로" min="50" max="3000" step="1">
                        </div>                        
                    </div>
 
                    <!-- Transom Specific Fields (panel 12 only) -->
                    <div id="transomDetailsSection" class="panel-modal-field" style="display: none;">
                        <label class="panel-modal-label">Transom 상세 정보</label>

                        <!-- 기본 정보 (3열) -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--linear-spacing-md); margin-bottom: var(--linear-spacing-md);">
                            <div>
                                <label class="panel-modal-label" style="font-size: 0.85rem; margin-bottom: var(--linear-spacing-xs);">트랜섬 막판높이 (mm)</label>
                                <input type="number" id="modalTransomPlateHeight" step="1" min="0"
                                       style="width: 80px; padding: var(--linear-spacing-sm); border: 1px solid var(--linear-border-primary); border-radius: var(--linear-radius-md); background-color: var(--linear-bg-primary); color: var(--linear-text-primary);">
                            </div>
                            <div>
                                <label class="panel-modal-label" style="font-size: 0.85rem; margin-bottom: var(--linear-spacing-xs);">밑면깊이(JD) (mm)</label>
                                <input type="number" id="modalBottomDepthJD" step="1" min="0"
                                       style="width: 80px; padding: var(--linear-spacing-sm); border: 1px solid var(--linear-border-primary); border-radius: var(--linear-radius-md); background-color: var(--linear-bg-primary); color: var(--linear-text-primary);">
                            </div>
                            <div>
                                <label class="panel-modal-label" style="font-size: 0.85rem; margin-bottom: var(--linear-spacing-xs);">날개값 (mm)</label>
                                <input type="number" id="modalWingValue" step="1" min="0"
                                       style="width: 80px; padding: var(--linear-spacing-sm); border: 1px solid var(--linear-border-primary); border-radius: var(--linear-radius-md); background-color: var(--linear-bg-primary); color: var(--linear-text-primary);">
                            </div>
                        </div>

                        <!-- CPI 타공 정보 (3열 한 행) -->
                        <label class="panel-modal-label" style="font-size: 0.9rem; margin-bottom: var(--linear-spacing-xs); display: block;">CPI 타공 정보</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--linear-spacing-md);">
                            <div>
                                <label class="panel-modal-label" style="font-size: 0.85rem; margin-bottom: var(--linear-spacing-xs);">가로 (mm)</label>
                                <input type="number" id="modalCpiDrillingWidth" step="1" min="0"
                                style="width: 80px; padding: var(--linear-spacing-sm); border: 1px solid var(--linear-border-primary); border-radius: var(--linear-radius-md); background-color: var(--linear-bg-primary); color: var(--linear-text-primary);">
                            </div>
                            <div>
                                <label class="panel-modal-label" style="font-size: 0.85rem; margin-bottom: var(--linear-spacing-xs);">세로 (mm)</label>
                                <input type="number" id="modalCpiDrillingHeight" step="1" min="0"
                                style="width: 80px; padding: var(--linear-spacing-sm); border: 1px solid var(--linear-border-primary); border-radius: var(--linear-radius-md); background-color: var(--linear-bg-primary); color: var(--linear-text-primary);">
                            </div>
                            <div>
                                <label class="panel-modal-label" style="font-size: 0.85rem; margin-bottom: var(--linear-spacing-xs);">높이(밑면기준) (mm)</label>
                                <input type="number" id="modalCpiDrillingHeightFromBottom" step="1" min="0"
                                style="width: 80px; padding: var(--linear-spacing-sm); border: 1px solid var(--linear-border-primary); border-radius: var(--linear-radius-md); background-color: var(--linear-bg-primary); color: var(--linear-text-primary);">
                            </div>
                        </div>

                        <small style="color: var(--linear-text-tertiary); font-size: 0.8rem; margin-top: 4px; display: block;">※ Transom 패널의 상세 치수 정보를 입력하세요</small>
                    </div>
                    
                    <!-- Drilling Information -->
                    <div class="panel-modal-field">
                        <div style="display: flex; align-items: center; gap: var(--linear-spacing-sm); margin-bottom: var(--linear-spacing-md);">
                            <input type="checkbox" id="hasDrilling" name="has_drilling" style="transform: scale(1.2);">
                            <label for="hasDrilling" class="panel-modal-label" style="margin: 0; cursor: pointer;">타공 정보</label>
                        </div>
                        
                        <div id="drillingFields" style="display: none;">
                            <div style="display: grid; grid-template-columns: 160px auto 160px; gap: var(--linear-spacing-sm); align-items: center; margin-bottom: var(--linear-spacing-md); justify-content: start;">
                                <input type="number" class="linear-input" id="modalDrillingWidth" name="drilling_width" placeholder="타공 가로(mm)" min="0" max="1000" step="1">
                                <span style="color: var(--linear-text-secondary);">×</span>
                                <input type="number" class="linear-input" id="modalDrillingHeight" name="drilling_height" placeholder="타공 세로(mm)" min="0" max="1000" step="1">
                            </div>
                            <div style="display: grid; grid-template-columns: 280px; gap: var(--linear-spacing-sm); margin-bottom: var(--linear-spacing-sm); justify-content: start;">
                                <input type="number" class="linear-input" id="modalDrillingFromFloor" name="drilling_from_floor" placeholder="타공기준점 바닥부터 타공센터높이(mm)" min="0" max="3000" step="1">
                            </div>
                            <div style="display: grid; grid-template-columns: 280px; gap: var(--linear-spacing-sm); justify-content: start;">
                                <input type="number" class="linear-input" id="modalDrillingFromEntrance" name="drilling_from_entrance" placeholder="출입구쪽 방향에서 측정거리(mm)" min="0" max="3000" step="1">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Notes -->
                    <div class="panel-modal-field">
                        <label class="panel-modal-label">비고</label>
                        <textarea class="linear-input" id="modalPanelNotes" name="panel_notes" rows="2" placeholder="특이사항, 추가 정보 등"></textarea>
                    </div>
                </form>
            </div>
            <div class="panel-modal-footer">
                <button type="button" class="linear-btn linear-btn-secondary" id="panelModalReset">초기화</button>
                <button type="button" class="linear-btn linear-btn-outline" id="panelModalCancel">취소</button>
                <button type="button" class="linear-btn linear-btn-primary" id="panelModalSave">저장</button>
            </div>
        </div>
    </div>
    
    <!-- 검증 결과 모달 -->
    <div id="validationResultModal" style="
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background-color: rgba(0, 0, 0, 0.7);
        z-index: 10000;
        backdrop-filter: blur(3px);
    " class="linear-modal-overlay">
        <div style="
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            max-width: 700px;
            width: 90%;
            max-height: 90vh;
            background: var(--linear-bg-primary, #ffffff);
            border: 2px solid var(--linear-border-primary, #e2e8f0);
            border-radius: 12px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            isolation: isolate;
        " class="linear-modal-container">
            <div style="
                background: var(--linear-bg-secondary, #f8fafc);
                border-bottom: 1px solid var(--linear-border-primary, #e2e8f0);
                padding: 20px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            " class="linear-modal-header">
                <h3 style="
                    margin: 0;
                    color: var(--linear-text-primary, #1a202c);
                    font-size: 18px;
                    font-weight: 600;
                " class="linear-modal-title">측정값 검증 결과</h3>
                <button type="button" style="
                    background: none;
                    border: none;
                    color: var(--linear-text-secondary, #718096);
                    cursor: pointer;
                    padding: 4px;
                    border-radius: 4px;
                    transition: background-color 0.2s;
                " class="linear-modal-close" onclick="closeValidationResultModal()" onmouseover="this.style.backgroundColor='var(--linear-bg-hover, #f1f5f9)'" onmouseout="this.style.backgroundColor='transparent'">
                    <i class="bi bi-x" style="font-size: 20px;"></i>
                </button>
            </div>
            <div style="
                padding: 24px;
                background: var(--linear-bg-primary, #ffffff);
                overflow-y: auto;
                max-height: calc(90vh - 140px);
            " class="linear-modal-body">
                <div id="validationResultContent">
                    <!-- 검증 결과가 여기에 동적으로 생성됩니다 -->
                </div>
                <div style="
                    text-align: center;
                    margin-top: 24px;
                    padding-top: 20px;
                    border-top: 1px solid var(--linear-border-secondary, #f1f5f9);
                " class="linear-modal-actions">
                    <button type="button" class="linear-btn linear-btn-outline" onclick="closeValidationResultModal()">
                        확인
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script>
        // 🎯 CLEAN JAVASCRIPT - 디버그 모드 제어
        const DEBUG_MODE = false; // 프로덕션에서는 false로 설정
        
        function debugLog(...args) {
            if (DEBUG_MODE) {
                console.log(...args);
            }
        }

        // 전역 변수
        let selectedPanel = null;
        window.panelData = {};
        window.transomData = {};

        // Edit mode data loading
        <?php if ($edit_mode && !empty($edit_panel_data)): ?>
        window.panelData = <?= json_encode($edit_panel_data) ?>;
        debugLog('Edit mode: Loaded panel data', window.panelData);
        <?php endif; ?>

        <?php if ($edit_mode && !empty($edit_transom_data)): ?>
        window.transomData = <?= json_encode($edit_transom_data) ?>;
        debugLog('Edit mode: Loaded transom data', window.transomData);
        <?php endif; ?>

        // Panel selection - handled by external JavaScript file

        // Global alert override - 모든 alert를 SweetAlert2로 변환
        window.alert = function(message) {
            Swal.fire({
                icon: 'info',
                text: message,
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'center'
            });
        };

        // Date picker implementation
        document.addEventListener('DOMContentLoaded', function() {
            const dateInput = document.getElementById('measurementDate');
            const datePicker = document.getElementById('datePicker');
            
            if (dateInput && datePicker) {
                // Initialize date picker
                updateDatePicker();
                
                // Show date picker on input click
                dateInput.addEventListener('click', function() {
                    datePicker.style.display = datePicker.style.display === 'block' ? 'none' : 'block';
                });
                
                // Hide date picker when clicking outside
                document.addEventListener('click', function(e) {
                    if (!dateInput.contains(e.target) && !datePicker.contains(e.target)) {
                        datePicker.style.display = 'none';
                    }
                });
            }
            
            // Initialize panel data display
            updatePanelDisplay();
        });

        // Update date picker
        function updateDatePicker() {
            const datePicker = document.getElementById('datePicker');
            if (!datePicker) return;
            
            const currentDate = new Date();
            const year = currentPickerDate.getFullYear();
            const month = currentPickerDate.getMonth();
            
            // Generate calendar HTML
            const monthNames = ['1월', '2월', '3월', '4월', '5월', '6월', '7월', '8월', '9월', '10월', '11월', '12월'];
            const dayNames = ['일', '월', '화', '수', '목', '금', '토'];
            
            let html = `
                <div class="date-picker-header">
                    <button type="button" class="date-picker-nav">‹</button>
                    <div class="date-picker-title">${year}년 ${monthNames[month]}</div>
                    <button type="button" class="date-picker-nav">›</button>
                </div>
                <div class="date-picker-grid">
            `;
            
            // Add day headers
            dayNames.forEach(day => {
                html += `<div class="date-picker-day" style="font-weight: bold;">${day}</div>`;
            });
            
            // Add days
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            
            // Previous month days
            for (let i = 0; i < firstDay; i++) {
                const prevMonth = new Date(year, month, -i);
                html += `<div class="date-picker-day other-month">${prevMonth.getDate()}</div>`;
            }
            
            // Current month days
            for (let day = 1; day <= daysInMonth; day++) {
                const isToday = day === currentDate.getDate() && month === currentDate.getMonth() && year === currentDate.getFullYear();
                html += `<div class="date-picker-day ${isToday ? 'selected' : ''}" data-year="${year}" data-month="${month}" data-day="${day}">${day}</div>`;
            }
            
            html += '</div>';
            datePicker.innerHTML = html;
            
            // Add event listeners to date cells
            const dateCells = datePicker.querySelectorAll('.date-picker-day[data-year]');
            dateCells.forEach(cell => {
                cell.addEventListener('click', function(e) {
                    e.stopPropagation(); // Prevent event bubbling
                    const year = parseInt(this.dataset.year);
                    const month = parseInt(this.dataset.month);
                    const day = parseInt(this.dataset.day);
                    selectDate(year, month, day);
                });
            });
            
            // Add event listeners to month navigation buttons
            const prevBtn = datePicker.querySelector('.date-picker-nav:first-child');
            const nextBtn = datePicker.querySelector('.date-picker-nav:last-child');
            
            if (prevBtn) {
                prevBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    changeMonth(-1);
                });
            }
            
            if (nextBtn) {
                nextBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    changeMonth(1);
                });
            }
        }

        // Change month
        let currentPickerDate = new Date();
        
        function changeMonth(direction) {
            currentPickerDate.setMonth(currentPickerDate.getMonth() + direction);
            updateDatePicker();
        }

        // Select date
        function selectDate(year, month, day) {
            const dateInput = document.getElementById('measurementDate');
            const datePicker = document.getElementById('datePicker');
            
            if (dateInput) {
                // 시간대 문제 해결을 위해 로컬 날짜 문자열 생성
                const formattedDate = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                dateInput.value = formattedDate;
                datePicker.style.display = 'none';
                debugLog('Selected date:', formattedDate);
            }
        }

        // Update panel display
        function updatePanelDisplay() {
            // Update panel visual states based on data
            Object.keys(panelData).forEach(panelId => {
                updatePanelVisualState(panelId);
            });
            
            // Update transom panel
            Object.keys(transomData).forEach(panelId => {
                updatePanelVisualState(panelId);
            });
        }

        // Form validation
        function validateForm() {
            const requiredFields = [
                { id: 'siteName', label: '현장명' },
                { id: 'measurementDate', label: '측정일' },
                { id: 'measurer', label: '측정자' },
                { id: 'carInsideWidth', label: '카 내부 가로(W)' },
                { id: 'carInsideDepth', label: '카 내부 깊이(D)' },
                { id: 'carInsideHeight', label: '카 내부 높이(H)' }
            ];
            
            let isValid = true;
            const missingFields = [];
            
            requiredFields.forEach(field => {
                const element = document.getElementById(field.id);
                if (element && !element.value.trim()) {
                    element.style.borderColor = 'var(--linear-color-red)';
                    missingFields.push(field.label);
                    isValid = false;
                } else if (element) {
                    element.style.borderColor = 'var(--linear-border-primary)';
                }
            });
            
            if (!isValid) {
                Swal.fire({
                    icon: 'warning',
                    title: '입력 오류',
                    html: `다음 필수 항목을 입력해주세요:<br><br><strong>${missingFields.join('<br>')}</strong>`,
                    timer: 3000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'center'
                });
            }
            
            console.log('📋 폼 검증:', isValid ? '✅ 통과' : '❌ 실패', missingFields);
            
            return isValid;
        }

        // 현장명 자동생성 함수
        function generateSiteName() {
            const iparkCheck = document.getElementById('iparkCheck');
            const siteNameInput = document.getElementById('siteName');
            const measurementDate = document.getElementById('measurementDate');
            const measurer = document.getElementById('measurer');
            
            if (!siteNameInput) return;
            
            // 현재 날짜와 시간
            const now = new Date();
            const year = String(now.getFullYear()).slice(-2); // 년도 뒷자리 2자리
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hour = String(now.getHours()).padStart(2, '0');
            const minute = String(now.getMinutes()).padStart(2, '0');
            const dateTimeStr = `${year}${month}${day}${hour}${minute}`;
            
            // 측정자 이름 (성만 추출)
            let measurerName = '';
            if (measurer && measurer.value.trim()) {
                measurerName = measurer.value.trim().charAt(0);
            } else {
                // 측정자 필드가 비어있으면 세션에서 가져오기 시도
                <?php if (isset($_SESSION["name"])): ?>
                measurerName = '<?= htmlspecialchars($_SESSION["name"]) ?>'.charAt(0);
                <?php else: ?>
                measurerName = '측정자';
                <?php endif; ?>
            }
            
            // 아이파크 체크 여부에 따른 현장명 생성
            let siteName = '';
            if (iparkCheck && iparkCheck.checked) {
                // 아이파크 프로젝트
                siteName = `아이파크_${dateTimeStr}_${measurerName}`;
            } else {
                // 일반 프로젝트
                siteName = `현장_${dateTimeStr}_${measurerName}`;
            }
            
            siteNameInput.value = siteName;
            debugLog('현장명 자동생성:', siteName);
            
            // 자동생성 버튼에 시각적 피드백
            const generateBtn = document.getElementById('generateSiteNameBtn');
            if (generateBtn) {
                const originalText = generateBtn.innerHTML;
                generateBtn.innerHTML = '<i class="bi bi-check-circle"></i> 생성완료';
                generateBtn.style.backgroundColor = 'var(--linear-success)';
                generateBtn.style.color = 'white';
                
                // 2초 후 원래 상태로 복원
                setTimeout(() => {
                    generateBtn.innerHTML = originalText;
                    generateBtn.style.backgroundColor = '';
                    generateBtn.style.color = '';
                }, 2000);
            }
        }
        
        // 아이파크 체크박스 변경 시 현장명 업데이트 및 입력창 표시/숨김
        function updateSiteNameForIpark() {
            const iparkCheck = document.getElementById('iparkCheck');
            const siteNameInput = document.getElementById('siteName');
            const iparkProjectInfo = document.getElementById('iparkProjectInfo');
            const excludePanelCorners = document.getElementById('excludePanelCorners');
            const excludeTransom = document.getElementById('excludeTransom');
            
            if (!iparkCheck || !siteNameInput) return;
            
            // 아이파크 프로젝트 정보 입력창 표시/숨김
            if (iparkProjectInfo) {
                if (iparkCheck.checked) {
                    iparkProjectInfo.style.display = 'block';
                    iparkProjectInfo.style.maxHeight = '500px';
                    iparkProjectInfo.style.opacity = '1';
                    iparkProjectInfo.style.padding = 'var(--linear-spacing-lg)';
                    iparkProjectInfo.style.marginTop = 'var(--linear-spacing-md)';
                    iparkProjectInfo.style.border = '1px solid var(--linear-border-primary)';
                    debugLog('아이파크 프로젝트 정보 입력창 표시');
                    
                    // 아이파크 체크 시 시각화 체크박스 자동 체크
                    if (excludePanelCorners) {
                        excludePanelCorners.checked = true;
                        // 체크박스 변경 이벤트 트리거
                        excludePanelCorners.dispatchEvent(new Event('change'));
                    }
                    if (excludeTransom) {
                        excludeTransom.checked = true;
                        // 체크박스 변경 이벤트 트리거
                        excludeTransom.dispatchEvent(new Event('change'));
                    }
                    
                    console.log('✅ 아이파크 모드: 1,11번 제외 및 트랜섬 제외 자동 체크');
                    
                    // 아이파크 입력 필드에 이벤트 리스너 추가
                    setupIparkInputListeners();
                } else {
                    iparkProjectInfo.style.maxHeight = '0';
                    iparkProjectInfo.style.opacity = '0';
                    iparkProjectInfo.style.padding = '0';
                    iparkProjectInfo.style.marginTop = '0';
                    iparkProjectInfo.style.border = 'none';
                    setTimeout(() => {
                        if (!iparkCheck.checked) {
                            iparkProjectInfo.style.display = 'none';
                        }
                    }, 300);
                    debugLog('아이파크 프로젝트 정보 입력창 숨김');
                    
                    // 아이파크 해제 시 시각화 체크박스 자동 해제
                    if (excludePanelCorners) {
                        excludePanelCorners.checked = false;
                        excludePanelCorners.dispatchEvent(new Event('change'));
                    }
                    if (excludeTransom) {
                        excludeTransom.checked = false;
                        excludeTransom.dispatchEvent(new Event('change'));
                    }
                    
                    console.log('✅ 일반 모드: 1,11번 제외 및 트랜섬 제외 자동 해제');
                    
                    // 아이파크 자동계산 값 초기화
                    clearIparkAutoMeasurements();
                }
            }
            
            const currentValue = siteNameInput.value.trim();
            
            // 기존 현장명이 자동생성된 형식인지 확인 (년도2자리+월+일+시+분 = 10자리)
            const isAutoGenerated = /^(아이파크_|현장_)\d{10}_/.test(currentValue);
            
            if (isAutoGenerated) {
                // 자동생성된 현장명인 경우 아이파크 상태에 따라 업데이트
                if (iparkCheck.checked) {
                    // 아이파크로 변경
                    siteNameInput.value = currentValue.replace(/^현장_/, '아이파크_');
                } else {
                    // 일반으로 변경
                    siteNameInput.value = currentValue.replace(/^아이파크_/, '현장_');
                }
                debugLog('아이파크 상태 변경으로 현장명 업데이트:', siteNameInput.value);
            }
        }

        // 아이파크 입력 필드 이벤트 리스너 설정
        function setupIparkInputListeners() {
            const applyBtn = document.getElementById('applyIparkSettingsBtn');
            
            if (applyBtn) {
                applyBtn.addEventListener('click', () => {
                    const panel39WidthInput = document.getElementById('iparkPanel39Width');
                    const panel6WidthInput = document.getElementById('iparkPanel6Width');
                    
                    if (panel39WidthInput && panel6WidthInput) {
                        const panel39Width = parseInt(panel39WidthInput.value) || 0;
                        const panel6Width = parseInt(panel6WidthInput.value) || 0;
                        
                        if (panel39Width <= 0 || panel6Width <= 0) {
                            Swal.fire({
                                icon: 'warning',
                                text: '패널 폭을 올바르게 입력해주세요.',
                                timer: 2000,
                                showConfirmButton: false,
                                toast: true,
                                position: 'center'
                            });
                            return;
                        }
                        
                        if (panel39Width < 100 || panel39Width > 2000) {
                            Swal.fire({
                                icon: 'warning',
                                text: '3,9번 패널 폭은 100~2000mm 범위로 입력해주세요.',
                                timer: 2000,
                                showConfirmButton: false,
                                toast: true,
                                position: 'center'
                            });
                            return;
                        }
                        
                        if (panel6Width < 100 || panel6Width > 2000) {
                            Swal.fire({
                                icon: 'warning',
                                text: '6번 패널 폭은 100~2000mm 범위로 입력해주세요.',
                                timer: 2000,
                                showConfirmButton: false,
                                toast: true,
                                position: 'center'
                            });
                            return;
                        }
                        
                        applyIparkAutoMeasurements(panel39Width, panel6Width);
                        
                        // 시각화 체크박스 자동 체크
                        const excludePanelCorners = document.getElementById('excludePanelCorners');
                        const excludeTransom = document.getElementById('excludeTransom');
                        
                        if (excludePanelCorners && !excludePanelCorners.checked) {
                            excludePanelCorners.checked = true;
                            excludePanelCorners.dispatchEvent(new Event('change'));
                        }
                        if (excludeTransom && !excludeTransom.checked) {
                            excludeTransom.checked = true;
                            excludeTransom.dispatchEvent(new Event('change'));
                        }
                        
                        console.log('✅ 설정 적용 시 체크박스 자동 체크 완료');
                        
                        // 성공 알림
                        Swal.fire({
                            icon: 'success',
                            title: '설정 적용 완료',
                            html: `아이파크 설정이 적용되었습니다.<br>3,9번: ${panel39Width}mm, 6번: ${panel6Width}mm`,
                            timer: 2500,
                            showConfirmButton: false,
                            toast: true,
                            position: 'center'
                        });
                    }
                });
            }
        }

        // 아이파크 자동 실측값 계산 및 적용 함수
        function applyIparkAutoMeasurements(panel39Width, panel6Width) {
            // 카 내부 치수 자동 설정 (아이파크 표준 치수)
            const carDepthInput = document.getElementById('carInsideDepth');
            const carWidthInput = document.getElementById('carInsideWidth');
            const carHeightInput = document.getElementById('carInsideHeight');

            // 카 내부 치수가 없거나 0인 경우 아이파크 표준 치수로 설정
            if (!carDepthInput?.value || parseInt(carDepthInput.value) <= 0) {
                if (carDepthInput) {
                    carDepthInput.value = '1500'; // 아이파크 표준 깊이
                    carDepthInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }
            if (!carWidthInput?.value || parseInt(carWidthInput.value) <= 0) {
                if (carWidthInput) {
                    carWidthInput.value = '1600'; // 아이파크 표준 가로
                    carWidthInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }
            if (!carHeightInput?.value || parseInt(carHeightInput.value) <= 0) {
                if (carHeightInput) {
                    carHeightInput.value = '2700'; // 아이파크 표준 높이
                    carHeightInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }

            // 설정된 카 내부 치수 가져오기
            const carDepth = parseInt(carDepthInput?.value) || 1500;
            const carWidth = parseInt(carWidthInput?.value) || 1600;

            console.log('🔍 카 내부 치수 설정 완료 - 가로:', carWidth, '깊이:', carDepth);

            // D방향 계산 (2,3,4,8,9,10번 패널)
            // 3번과 8번은 각각 같은 폭 (panel39Width는 하나의 폭)
            const remainingDepth = carDepth - panel39Width;
            let panel2_4_8_10_width = Math.round(remainingDepth / 2); // 2,4,8,10번은 동일한 폭

            // W방향 계산 (5,6,7번 패널)
            const remainingWidth = carWidth - panel6Width;
            let panel5_7_width = Math.round(remainingWidth / 2); // 5,7번은 동일한 폭

            // 유효성 검사 및 자동 조정
            let hasAdjustment = false;
            let adjustmentMessage = '';

            if (panel2_4_8_10_width <= 0) {
                panel2_4_8_10_width = 10;
                hasAdjustment = true;
                adjustmentMessage += `D방향: 3,9번 패널폭(${panel39Width}mm)이 카 깊이(${carDepth}mm)보다 커서 2,4,8,10번을 최소값 10mm로 조정\n`;
                console.log(`⚠️ D방향 조정: 2,4,8,10번 → ${panel2_4_8_10_width}mm (최소값)`);
            }

            if (panel5_7_width <= 0) {
                panel5_7_width = 10;
                hasAdjustment = true;
                adjustmentMessage += `W방향: 5,7번 패널폭을 최소값 10mm로 조정\n`;
                console.log(`⚠️ W방향 조정: 5,7번 → ${panel5_7_width}mm (최소값)`);
            }

            // 패널 데이터 객체가 없으면 초기화
            if (typeof window.panelData === 'undefined') {
                window.panelData = {};
            }

            // 2-10번 패널에 자동 계산된 width 값 적용
            const panelWidths = {
                2: panel2_4_8_10_width,   // D방향 좌측 하단
                3: panel39Width,          // D방향 좌측 상단 (사용자 입력)
                4: panel2_4_8_10_width,   // D방향 좌측 상단 연결
                5: panel5_7_width,        // W방향 상단 좌측
                6: panel6Width,           // W방향 상단 중앙 (사용자 입력)
                7: panel5_7_width,        // W방향 상단 우측
                8: panel2_4_8_10_width,   // D방향 우측 상단 연결
                9: panel39Width,          // D방향 우측 상단 (사용자 입력)
                10: panel2_4_8_10_width   // D방향 우측 하단
            };

            // 기본 높이 값 설정 (카 내부 높이 사용)
            const carHeight = parseInt(document.getElementById('carInsideHeight')?.value) || 0;
            const defaultHeight = carHeight > 0 ? carHeight : 2700;

            // 부모 페이지의 재질 및 두께 정보 가져오기
            const parentMaterialType = document.getElementById('materialType')?.value || '';
            const parentMaterialThickness = document.getElementById('materialThickness')?.value || '';
            
            // 각 패널에 width, height, 재질, 두께 값 설정
            for (let panelNum = 2; panelNum <= 10; panelNum++) {
                if (!window.panelData[panelNum]) {
                    window.panelData[panelNum] = {};
                }
                window.panelData[panelNum].width = panelWidths[panelNum];
                window.panelData[panelNum].height = defaultHeight;
                window.panelData[panelNum].isIparkAuto = true;
                window.panelData[panelNum].autoCalculatedAt = new Date().toISOString();
                
                // 재질 정보가 없으면 부모 페이지 값 설정
                if (!window.panelData[panelNum].material_type && parentMaterialType) {
                    window.panelData[panelNum].material_type = parentMaterialType;
                }
                
                // 두께 정보가 없으면 부모 페이지 값 설정
                if (!window.panelData[panelNum].thickness && parentMaterialThickness) {
                    window.panelData[panelNum].thickness = parentMaterialThickness;
                }
            }
            
            // 실제 측정 시각화에 강제 업데이트 (내부에서 updatePanelDisplay, updateJsonFields 포함)
            forceUpdatePanelVisualization(panelWidths);

            console.log('✅ 아이파크 자동계산 완료:', panelWidths);
            
            if (hasAdjustment) {
                console.warn('⚠️ 조정 사항:', adjustmentMessage);
            }
        }

        // 실제 측정 시각화에 강제 업데이트
        function forceUpdatePanelVisualization(panelWidths) {
            // 기본 높이 값 가져오기
            const carHeight = parseInt(document.getElementById('carInsideHeight')?.value) || 0;
            const defaultHeight = carHeight > 0 ? carHeight : 2700;
            
            // 부모 페이지의 재질 및 두께 정보 가져오기
            const parentMaterialType = document.getElementById('materialType')?.value || '';
            const parentMaterialThickness = document.getElementById('materialThickness')?.value || '';

            // 각 패널의 width와 height 값을 시각화에 반영
            for (let panelNum = 2; panelNum <= 10; panelNum++) {
                const width = panelWidths[panelNum];
                if (!width) continue;

                // 1. panelData 전역 변수에 강제 저장 (이미 위에서 설정되었지만 확실히)
                if (!window.panelData) window.panelData = {};
                if (!window.panelData[panelNum]) window.panelData[panelNum] = {};
                
                // 기존 데이터 유지하면서 업데이트
                window.panelData[panelNum].width = width;
                window.panelData[panelNum].height = defaultHeight;
                window.panelData[panelNum].isIparkAuto = true;
                
                // 재질 정보가 없으면 부모 페이지 값 설정
                if (!window.panelData[panelNum].material_type && parentMaterialType) {
                    window.panelData[panelNum].material_type = parentMaterialType;
                }
                
                // 두께 정보가 없으면 부모 페이지 값 설정
                if (!window.panelData[panelNum].thickness && parentMaterialThickness) {
                    window.panelData[panelNum].thickness = parentMaterialThickness;
                }
                
                console.log(`💾 패널 ${panelNum} 데이터 저장:`, {
                    width: window.panelData[panelNum].width,
                    height: window.panelData[panelNum].height,
                    material_type: window.panelData[panelNum].material_type,
                    thickness: window.panelData[panelNum].thickness,
                    isIparkAuto: window.panelData[panelNum].isIparkAuto
                });

                // 2. 패널 요소에 데이터 속성으로 저장
                const panelElement = document.querySelector(`.panel-${panelNum}`);
                if (panelElement) {
                    panelElement.setAttribute('data-width', width);
                    panelElement.setAttribute('data-height', defaultHeight);
                    panelElement.setAttribute('data-ipark-auto', 'true');
                }

                // 3. 패널 정보 표시 업데이트
                updatePanelInfo(panelNum, { width: width, height: defaultHeight });
            }

            // 4. 각 패널의 시각적 상태 업데이트
            for (let panelNum = 2; panelNum <= 10; panelNum++) {
                updatePanelVisualState(panelNum);
            }

            // 5. JSON 필드 업데이트 (폼 제출 시 전송될 데이터)
            updateJsonFields();

            // 6. 이벤트 트리거 (다른 컴포넌트에 알림)
            const updateEvent = new CustomEvent('iparkAutoUpdate', {
                detail: { panelWidths: panelWidths, timestamp: Date.now() }
            });
            document.dispatchEvent(updateEvent);
            
            console.log('✅ 아이파크 패널 데이터 완전 적용 완료');
        }

        // 패널 정보 업데이트 (시각화 반영)
        function updatePanelInfo(panelNum, data) {
            try {
                // 패널 요소 찾기
                const panelElement = document.querySelector(`.panel-${panelNum}`);
                if (!panelElement) return;

                // 패널 정보 표시 영역 찾기 또는 생성
                let infoDiv = panelElement.querySelector('.panel-info');
                if (!infoDiv) {
                    infoDiv = document.createElement('div');
                    infoDiv.className = 'panel-info';
                    
                    // 측벽 패널(2,3,4,8,9,10번)의 위치 조정
                    let leftPosition = '50%';
                    let transform = 'translateX(-50%)';
                    
                    if ([2, 3, 4].includes(panelNum)) {
                        // 좌측 패널 (2,3,4번) - 우측으로 이동
                        leftPosition = 'calc(50% + 2em)';
                        transform = 'translateX(-50%)';
                    } else if ([8, 9, 10].includes(panelNum)) {
                        // 우측 패널 (8,9,10번) - 좌측으로 이동
                        leftPosition = 'calc(50% - 2em)';
                        transform = 'translateX(-50%)';
                    }
                    
                    infoDiv.style.cssText = `
                        position: absolute;
                        bottom: 2px;
                        left: ${leftPosition};
                        transform: ${transform};
                        font-size: 1.2rem;
                        font-weight: var(--linear-font-weight-medium);
                        color: white;
                        background: rgba(0, 0, 0, 0.7);
                        padding: 2px 6px;
                        border-radius: 3px;
                        white-space: nowrap;
                        z-index: 5;
                        pointer-events: none;
                        line-height: 1.2;
                    `;
                    panelElement.appendChild(infoDiv);
                }

                // 정보 표시 (기존 패널 치수 표시와 동일한 형식)
                if (data.width && data.height) {
                    infoDiv.innerHTML = `${data.width}×${data.height}`;
                    infoDiv.title = `아이파크 자동계산: ${data.width}mm × ${data.height}mm`;
                } else if (data.width) {
                    infoDiv.innerHTML = `W:${data.width}`;
                    infoDiv.title = `아이파크 자동계산: ${data.width}mm`;
                } else if (data.height) {
                    infoDiv.innerHTML = `H:${data.height}`;
                    infoDiv.title = `아이파크 자동계산: ${data.height}mm`;
                }

                // 패널 스타일 업데이트 (기존 패널 치수 표시와 동일)
                panelElement.classList.add('has-info');
                panelElement.style.backgroundColor = 'var(--linear-success)';
                panelElement.style.color = 'white';

                console.log(`✅ 패널 ${panelNum} 정보 업데이트: ${data.width}mm × ${data.height}mm`);
            } catch (error) {
                console.error(`❌ 패널 ${panelNum} 정보 업데이트 실패:`, error);
            }
        }

        // 아이파크 자동계산 값 초기화 함수
        function clearIparkAutoMeasurements() {
            // 2-10번 패널의 자동계산 값 제거
            for (let panelNum = 2; panelNum <= 10; panelNum++) {
                // panelData에서 width 값 제거
                if (window.panelData && window.panelData[panelNum]) {
                    delete window.panelData[panelNum].width;
                    delete window.panelData[panelNum].isIparkAuto;
                    delete window.panelData[panelNum].autoCalculatedAt;

                    // 빈 객체가 되면 완전 삭제
                    if (Object.keys(window.panelData[panelNum]).length === 0) {
                        delete window.panelData[panelNum];
                    }
                }

                // 시각적 표시 제거
                const panelElement = document.querySelector(`.panel-${panelNum}`);
                if (panelElement) {
                    panelElement.style.backgroundColor = '';
                    panelElement.style.color = '';
                    panelElement.style.border = '';
                    panelElement.classList.remove('has-info');
                    
                    // 데이터 속성 제거
                    panelElement.removeAttribute('data-width');
                    panelElement.removeAttribute('data-height');
                    panelElement.removeAttribute('data-ipark-auto');
                    
                    // 패널 정보 표시 제거
                    const infoDiv = panelElement.querySelector('.panel-info');
                    if (infoDiv) {
                        infoDiv.remove();
                    }
                }
            }

            // 시각화 업데이트
            updatePanelDisplay();
            updateJsonFields();

            console.log('✅ 아이파크 자동계산 값 초기화 완료');
        }

        // Panel Data Management
        let currentPanelNumber = null;
        let currentMode = 'new'; // 'new' or 'mod'
        
        // 더블 터치 처리를 위한 변수들
        let lastTouchTime = 0;
        let lastTouchTarget = null;
        const DOUBLE_TOUCH_DELAY = 300; // 300ms 이내 더블 터치

        // 커스텀 재질 옵션 추가 함수 (모달용)
        function addCustomMaterialOption(value) {
            const modalMaterialType = document.getElementById('modalMaterialType');
            const modalMaterialTypeCustom = document.getElementById('modalMaterialTypeCustom');
            const customMaterialContainer = document.getElementById('customMaterialContainer');
            
            if (!modalMaterialType || !value.trim()) return;
            
            // 기존 옵션에 있는지 확인
            const existingOption = Array.from(modalMaterialType.options).find(option => 
                option.value === value.trim()
            );
            
            if (!existingOption) {
                // "기타" 옵션 앞에 새 옵션 추가
                const otherOption = modalMaterialType.querySelector('option[value="기타"]');
                const newOption = document.createElement('option');
                newOption.value = value.trim();
                newOption.textContent = value.trim();
                newOption.selected = true;
                
                if (otherOption) {
                    modalMaterialType.insertBefore(newOption, otherOption);
                } else {
                    modalMaterialType.appendChild(newOption);
                }
                
                debugLog(`새로운 재질 추가됨 (모달): ${value.trim()}`);
            } else {
                // 기존 옵션이 있으면 선택
                existingOption.selected = true;
            }
            
            // 직접 입력 필드 숨기기
            if (customMaterialContainer) {
                customMaterialContainer.style.display = 'none';
            }
            if (modalMaterialTypeCustom) {
                modalMaterialTypeCustom.value = '';
            }
        }

        // 커스텀 재질 옵션 추가 함수 (본문용)
        function addMainCustomMaterialOption(value) {
            const mainMaterialType = document.getElementById('materialType');
            const mainMaterialTypeCustom = document.getElementById('materialTypeCustom');
            const mainCustomMaterialContainer = document.getElementById('mainCustomMaterialContainer');
            
            if (!mainMaterialType || !value.trim()) return;
            
            // 기존 옵션에 있는지 확인
            const existingOption = Array.from(mainMaterialType.options).find(option => 
                option.value === value.trim()
            );
            
            if (!existingOption) {
                // "기타" 옵션 앞에 새 옵션 추가
                const otherOption = mainMaterialType.querySelector('option[value="기타"]');
                const newOption = document.createElement('option');
                newOption.value = value.trim();
                newOption.textContent = value.trim();
                newOption.selected = true;
                
                if (otherOption) {
                    mainMaterialType.insertBefore(newOption, otherOption);
                } else {
                    mainMaterialType.appendChild(newOption);
                }
                
                debugLog(`새로운 재질 추가됨 (본문): ${value.trim()}`);
            } else {
                // 기존 옵션이 있으면 선택
                existingOption.selected = true;
            }
            
            // 직접 입력 필드 숨기기
            if (mainCustomMaterialContainer) {
                mainCustomMaterialContainer.style.display = 'none';
            }
            if (mainMaterialTypeCustom) {
                mainMaterialTypeCustom.value = '';
            }
        }

        // 본문 재질을 모달로 동기화
        function syncMaterialToModal() {
            const mainMaterialType = document.getElementById('materialType');
            const modalMaterialType = document.getElementById('modalMaterialType');
            
            if (!mainMaterialType || !modalMaterialType) return;
            
            const mainValue = mainMaterialType.value;
            const modalCustomContainer = document.getElementById('customMaterialContainer');
            const modalCustomInput = document.getElementById('modalMaterialTypeCustom');
            
            // 모달의 기존 옵션에 있는지 확인
            const existingOption = Array.from(modalMaterialType.options).find(option => 
                option.value === mainValue
            );
            
            if (existingOption) {
                modalMaterialType.value = mainValue;
                if (modalCustomContainer) modalCustomContainer.style.display = 'none';
                if (modalCustomInput) modalCustomInput.value = '';
            } else if (mainValue) {
                // 기존 옵션에 없으면 "기타"로 설정하고 직접 입력 필드에 표시
                modalMaterialType.value = '기타';
                if (modalCustomContainer) modalCustomContainer.style.display = 'block';
                if (modalCustomInput) modalCustomInput.value = mainValue;
            } else {
                modalMaterialType.value = '';
                if (modalCustomContainer) modalCustomContainer.style.display = 'none';
                if (modalCustomInput) modalCustomInput.value = '';
            }
        }

        // 모달 재질을 본문으로 동기화
        function syncMaterialToMain() {
            const modalMaterialType = document.getElementById('modalMaterialType');
            const mainMaterialType = document.getElementById('materialType');
            
            if (!modalMaterialType || !mainMaterialType) return;
            
            const modalValue = modalMaterialType.value;
            const mainCustomContainer = document.getElementById('mainCustomMaterialContainer');
            const mainCustomInput = document.getElementById('materialTypeCustom');
            
            // 본문의 기존 옵션에 있는지 확인
            const existingOption = Array.from(mainMaterialType.options).find(option => 
                option.value === modalValue
            );
            
            if (existingOption) {
                mainMaterialType.value = modalValue;
                if (mainCustomContainer) mainCustomContainer.style.display = 'none';
                if (mainCustomInput) mainCustomInput.value = '';
            } else if (modalValue) {
                // 기존 옵션에 없으면 "기타"로 설정하고 직접 입력 필드에 표시
                mainMaterialType.value = '기타';
                if (mainCustomContainer) mainCustomContainer.style.display = 'block';
                if (mainCustomInput) mainCustomInput.value = modalValue;
            } else {
                mainMaterialType.value = '';
                if (mainCustomContainer) mainCustomContainer.style.display = 'none';
                if (mainCustomInput) mainCustomInput.value = '';
            }
        }

        // 신규/MOD 토글 기능
        function toggleMode(mode) {
            const newBtn = document.getElementById('newBtn');
            const modBtn = document.getElementById('modBtn');
            const projectTypeInput = document.getElementById('projectType');
            
            if (!newBtn || !modBtn) return;
            
            currentMode = mode;
            
            // Hidden field 업데이트
            if (projectTypeInput) {
                // 'new' 대신 '신규'로 설정
                projectTypeInput.value = mode === 'new' ? '신규' : 'MOD';
            }
            
            if (mode === 'new') {
                newBtn.classList.remove('linear-btn-outline');
                newBtn.style.backgroundColor = 'var(--linear-brand-primary)';
                newBtn.style.color = 'white';
                
                modBtn.classList.add('linear-btn-outline');
                modBtn.style.backgroundColor = '';
                modBtn.style.color = '';
            } else {
                modBtn.classList.remove('linear-btn-outline');
                modBtn.style.backgroundColor = 'var(--linear-brand-primary)';
                modBtn.style.color = 'white';
                
                newBtn.classList.add('linear-btn-outline');
                newBtn.style.backgroundColor = '';
                newBtn.style.color = '';
            }
            
            debugLog(`모드 변경: ${mode}`);
        }

        // 패널 표시/숨김 업데이트
        function updatePanelVisibility() {
            const excludePanelCorners = document.getElementById('excludePanelCorners');
            const excludeTransom = document.getElementById('excludeTransom');
            const panelCount = document.getElementById('panelCount');
            
            if (!excludePanelCorners || !excludeTransom || !panelCount) return;
            
            const panels = document.querySelectorAll('.panel[data-panel]');
            let visibleCount = 0;
            
            panels.forEach(panel => {
                const panelNumber = panel.getAttribute('data-panel');
                let shouldShow = true;
                
                // 1번, 11번 패널 제외 체크
                if (excludePanelCorners.checked && (panelNumber === '1' || panelNumber === '11')) {
                    shouldShow = false;
                }
                
                // 트랜섬(12번) 제외 체크
                if (excludeTransom.checked && panelNumber === '12') {
                    shouldShow = false;
                }
                
                // 패널 표시/숨김
                if (shouldShow) {
                    panel.style.display = 'flex';
                    panel.style.visibility = 'visible';
                    panel.style.opacity = '1';
                    visibleCount++;
                } else {
                    panel.style.display = 'none';
                    panel.style.visibility = 'hidden';
                    panel.style.opacity = '0';
                }
                
                // 패널 상태 업데이트 (치수 정보 포함)
                updatePanelVisualState(panelNumber);
            });
            
            // 패널 개수 업데이트
            panelCount.textContent = `(${visibleCount}매)`;
            
            debugLog(`패널 가시성 업데이트: ${visibleCount}개 표시`);
        }

        // Add click event to all panels
        function attachPanelEvents() {
            const panels = document.querySelectorAll('.panel[data-panel]');
            const isMobile = window.innerWidth <= 768;

            panels.forEach(panel => {
                // Remove existing listeners
                panel.removeEventListener('click', handlePanelClick);
                panel.removeEventListener('touchend', handlePanelTouch);

                // Add appropriate listeners based on device type
                if (isMobile) {
                    // 모바일: 더블 터치만 사용
                    panel.addEventListener('touchend', handlePanelTouch);
                    // 클릭은 제거하여 실수 방지

                    // 모바일에서 title 속성 업데이트
                    const originalTitle = panel.getAttribute('title');
                    if (originalTitle && !originalTitle.includes('더블터치')) {
                        panel.setAttribute('title', originalTitle + ' (더블터치로 열기)');
                    }
                } else {
                    // PC: 클릭 사용
                    panel.addEventListener('click', handlePanelClick);
                }
            });
        }
        
        function handlePanelClick(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // 숨겨진 패널은 클릭할 수 없음
            if (e.currentTarget.style.display === 'none' || e.currentTarget.style.visibility === 'hidden') {
                return;
            }
            
            const panelNumber = e.currentTarget.getAttribute('data-panel');
            openPanelModal(panelNumber);
        }

        function handlePanelTouch(e) {
            e.preventDefault();
            e.stopPropagation();

            // 숨겨진 패널은 터치할 수 없음
            if (e.currentTarget.style.display === 'none' || e.currentTarget.style.visibility === 'hidden') {
                return;
            }

            const currentTime = new Date().getTime();
            const currentTarget = e.currentTarget;
            const panelNumber = currentTarget.getAttribute('data-panel');

            // 더블 터치 감지
            if (lastTouchTarget === currentTarget &&
                currentTime - lastTouchTime < DOUBLE_TOUCH_DELAY) {
                // 더블 터치 - 모달 열기
                openPanelModal(panelNumber);

                // 리셋
                lastTouchTime = 0;
                lastTouchTarget = null;
            } else {
                // 첫 번째 터치 - 기록만 하고 모달은 열지 않음
                lastTouchTime = currentTime;
                lastTouchTarget = currentTarget;

                // 시각적 피드백 - 더블 터치 힌트
                currentTarget.style.transform = 'scale(0.95)';
                currentTarget.style.backgroundColor = '#e3f2fd';
                currentTarget.style.border = '2px solid #2196f3';

                setTimeout(() => {
                    currentTarget.style.transform = '';
                    currentTarget.style.backgroundColor = '';
                    currentTarget.style.border = '';
                }, 250);

                // 첫 터치 후 힌트 표시 (한 번만)
                if (!window.doubleTouchHintShown) {
                    const panelInfo = document.getElementById('panelInfo');
                    if (panelInfo) {
                        const originalText = panelInfo.innerHTML;
                        panelInfo.innerHTML = '<i class="bi bi-hand-index"></i> 모바일에서는 더블 터치로 패널 정보를 수정할 수 있습니다.';
                        panelInfo.style.color = '#2196f3';
                        panelInfo.style.fontWeight = 'bold';

                        setTimeout(() => {
                            panelInfo.innerHTML = originalText;
                            panelInfo.style.color = '';
                            panelInfo.style.fontWeight = '';
                        }, 2000);
                    }
                    window.doubleTouchHintShown = true;
                }
            }
        }
        
        function openPanelModal(panelNumber) {
            currentPanelNumber = panelNumber;
            const modalPanelNumber = document.getElementById('modalPanelNumber');
            if (modalPanelNumber) {
                modalPanelNumber.value = panelNumber;
            }

            const isMobile = window.innerWidth <= 768;
            debugLog(`Opening panel modal for panel ${panelNumber}`);

            // 부모창의 재질 및 두께 정보를 패널 데이터에 기본값으로 설정
            const parentMaterialType = document.getElementById('materialType');
            const parentMaterialThickness = document.getElementById('materialThickness');
            
            // panelData 및 transomData 전역 객체 초기화 (없으면 생성)
            if (typeof window.panelData === 'undefined') {
                window.panelData = {};
            }
            if (typeof window.transomData === 'undefined') {
                window.transomData = {};
            }
            
            // 패널 데이터가 없으면 빈 객체로 초기화
            const dataObject = panelNumber === '12' ? window.transomData : window.panelData;
            if (!dataObject[panelNumber]) {
                dataObject[panelNumber] = {};
            }
            
            // 재질 정보가 없으면 부모 페이지 값 설정
            if (!dataObject[panelNumber].material_type && parentMaterialType?.value) {
                dataObject[panelNumber].material_type = parentMaterialType.value;
            }
            
            // 두께 정보가 없으면 부모 페이지 값 설정
            if (!dataObject[panelNumber].thickness && parentMaterialThickness?.value) {
                dataObject[panelNumber].thickness = parentMaterialThickness.value;
            }
            
            // 높이 정보가 없으면 본 설정(카 내부 높이)에서 가져오기 (일반 패널만)
            if (panelNumber !== '12') {
                const carInsideHeight = document.getElementById('carInsideHeight');
                if (!dataObject[panelNumber].height && carInsideHeight?.value) {
                    dataObject[panelNumber].height = carInsideHeight.value;
                }
            }
            
            console.log(`📝 패널 ${panelNumber} 모달 열기 - 기본값 설정:`, {
                material_type: dataObject[panelNumber]?.material_type,
                thickness: dataObject[panelNumber]?.thickness,
                height: dataObject[panelNumber]?.height
            });
            
            // 모달 드롭박스 설정
            const modalMaterialType = document.getElementById('modalMaterialType');
            if (parentMaterialType && modalMaterialType) {
                const parentValue = parentMaterialType.value;
                if (parentValue) {
                    // 기존 옵션에 있는지 확인
                    const existingOption = Array.from(modalMaterialType.options).find(option => 
                        option.value === parentValue
                    );
                    
                    if (existingOption) {
                        modalMaterialType.value = parentValue;
                    } else {
                        // 기존 옵션에 없으면 "기타"로 설정하고 직접 입력 필드에 표시
                        modalMaterialType.value = '기타';
                        const customMaterialContainer = document.getElementById('customMaterialContainer');
                        const modalMaterialTypeCustom = document.getElementById('modalMaterialTypeCustom');
                        if (customMaterialContainer) {
                            customMaterialContainer.style.display = 'block';
                        }
                        if (modalMaterialTypeCustom) {
                            modalMaterialTypeCustom.value = parentValue;
                        }
                    }
                }
            }

            // Set modal title
            const panelModalTitle = document.getElementById('panelModalTitle');
            const panelTypeSection = document.getElementById('panelTypeSection');
            
            // 크기 입력 필드 관련 요소 찾기
            const modalPanelHeight = document.getElementById('modalPanelHeight');
            const sizeLabel = document.getElementById('sizeLabel');
            const sizeInputContainer = document.getElementById('sizeInputContainer');
            const multiplySign = document.getElementById('multiplySign');
            
            if (panelNumber === '12') {
                if (panelModalTitle) panelModalTitle.textContent = `Transom 정보`;
                if (panelTypeSection) panelTypeSection.style.display = 'none';
                
                // Transom 패널: 세로 입력 필드 숨기기 및 레이아웃 조정
                if (modalPanelHeight) {
                    modalPanelHeight.style.display = 'none';
                }
                if (multiplySign) {
                    multiplySign.style.display = 'none';
                }
                if (sizeLabel) {
                    sizeLabel.textContent = '크기 (W폭) mm';
                }
                if (sizeInputContainer) {
                    sizeInputContainer.style.gridTemplateColumns = '104px';
                    sizeInputContainer.style.justifyContent = 'start';
                }
            } else {
                if (panelModalTitle) panelModalTitle.textContent = `패널 ${panelNumber} 정보`;
                if (panelTypeSection) panelTypeSection.style.display = 'none';
                
                // 일반 패널: 세로 입력 필드 표시 및 레이아웃 복원
                if (modalPanelHeight) {
                    modalPanelHeight.style.display = 'block';
                }
                if (multiplySign) {
                    multiplySign.style.display = 'inline';
                }
                if (sizeLabel) {
                    sizeLabel.textContent = '크기 (W폭×H높이) mm';
                }
                if (sizeInputContainer) {
                    sizeInputContainer.style.gridTemplateColumns = '104px auto 104px';
                    sizeInputContainer.style.justifyContent = 'start';
                }
            }

            // 타공 정보 섹션 요소
            const drillingSection = document.querySelector('.panel-modal-field:has(#hasDrilling)');
            const hasDrilling = document.getElementById('hasDrilling');
            const drillingFields = document.getElementById('drillingFields');
            
            // 타공이 없는 패널 번호: 2,4,5,7,8,10,12(Transom)
            const noDrillingPanels = ['2', '4', '5', '7', '8', '10', '12'];
            
            if (noDrillingPanels.includes(panelNumber)) {
                // 타공이 없는 패널: 타공 정보 섹션 숨김
                if (drillingSection) {
                    drillingSection.style.display = 'none';
                }
                if (hasDrilling) {
                    hasDrilling.checked = false;
                }
                if (drillingFields) {
                    drillingFields.style.display = 'none';
                }
                console.log(`🚫 패널 ${panelNumber}: 타공 정보 숨김`);
            } else {
                // 타공이 가능한 패널: 타공 정보 섹션 표시
                if (drillingSection) {
                    drillingSection.style.display = 'block';
                }
                console.log(`✅ 패널 ${panelNumber}: 타공 정보 표시`);
            }

            // Show corner panel type section only for panels 1 and 11
            const cornerPanelTypeSection = document.getElementById('cornerPanelTypeSection');
            const cornerPanelDetailsSection = document.getElementById('cornerPanelDetailsSection');
            const transomDetailsSection = document.getElementById('transomDetailsSection');

            // Show/hide copy button for panels 1 and 11
            const copyButtonContainer = document.getElementById('copyButtonContainer');
            const copyText = document.getElementById('copyText');

            if (panelNumber === '1' || panelNumber === '11') {
                if (copyButtonContainer) copyButtonContainer.style.display = 'flex';
                if (copyText) {
                    const targetPanel = panelNumber === '1' ? '11' : '1';
                    copyText.textContent = `${targetPanel}번으로 복사`;
                }
            } else {
                if (copyButtonContainer) copyButtonContainer.style.display = 'none';
            }

            if (panelNumber === '12') {
                // Show Transom details section for panel 12
                if (transomDetailsSection) {
                    transomDetailsSection.style.display = 'block';
                }
                // Hide corner panel sections for Transom
                if (cornerPanelTypeSection) cornerPanelTypeSection.style.display = 'none';
                if (cornerPanelDetailsSection) cornerPanelDetailsSection.style.display = 'none';
            } else if (panelNumber === '1' || panelNumber === '11') {
                // Hide Transom details section for 1,11번 panels
                if (transomDetailsSection) transomDetailsSection.style.display = 'none';
                
                if (cornerPanelTypeSection) {
                    cornerPanelTypeSection.style.display = 'block';
                }
                // Show corner panel details section
                if (cornerPanelDetailsSection) {
                    cornerPanelDetailsSection.style.display = 'block';
                }
            } else {
                // Hide all special sections for other panels
                if (transomDetailsSection) transomDetailsSection.style.display = 'none';
                if (cornerPanelTypeSection) cornerPanelTypeSection.style.display = 'none';
                if (cornerPanelDetailsSection) cornerPanelDetailsSection.style.display = 'none';
            }

            // Load existing data if available
            loadPanelDataToForm(panelNumber);

            // Show modal
            const panelModal = document.getElementById('panelModal');
            if (panelModal) {
                panelModal.style.display = 'flex';
                
                // 모달이 표시된 후 가로 너비 입력 필드에 포커스
                setTimeout(() => {
                    const modalPanelWidth = document.getElementById('modalPanelWidth');
                    if (modalPanelWidth) {
                        modalPanelWidth.focus();
                        modalPanelWidth.select(); // 기존 텍스트 선택
                    }
                }, 100); // 모달 애니메이션 완료 후 포커스
            }
        }

        function loadPanelDataToForm(panelNumber) {
            // panelData 초기화 확인
            if (typeof window.panelData === 'undefined') {
                window.panelData = {};
            }
            if (typeof window.transomData === 'undefined') {
                window.transomData = {};
            }
            
            const data = panelNumber === '12' ? window.transomData : window.panelData;
            const panelInfo = data ? data[panelNumber] : null;
            
            console.log(`📋 패널 ${panelNumber} 데이터 로드:`, panelInfo);
            console.log(`📦 전체 panelData:`, window.panelData);
            
            if (!panelInfo) {
                console.log(`⚠️ 패널 ${panelNumber} 데이터 없음 - 폼 초기화`);
                // Clear all fields if no data
                clearPanelForm();
                return;
            }

            // Load basic fields
            const modalPanelWidth = document.getElementById('modalPanelWidth');
            const modalPanelHeight = document.getElementById('modalPanelHeight');
            const modalMaterialType = document.getElementById('modalMaterialType');
            const modalPanelThickness = document.getElementById('modalPanelThickness');
            const modalPanelNotes = document.getElementById('modalPanelNotes');

            if (modalPanelWidth) modalPanelWidth.value = panelInfo.width || '';
            if (modalPanelHeight) modalPanelHeight.value = panelInfo.height || '';
            
            // 재질 처리 - 기존 옵션에 있으면 선택, 없으면 "기타"로 설정하고 직접 입력 필드에 표시
            if (modalMaterialType) {
                const materialType = panelInfo.material_type || '';
                const customMaterialContainer = document.getElementById('customMaterialContainer');
                const existingOption = Array.from(modalMaterialType.options).find(option => 
                    option.value === materialType
                );
                
                if (existingOption) {
                    modalMaterialType.value = materialType;
                    if (customMaterialContainer) {
                        customMaterialContainer.style.display = 'none';
                    }
                    if (modalMaterialTypeCustom) {
                        modalMaterialTypeCustom.value = '';
                    }
                } else if (materialType) {
                    // 기존 옵션에 없는 재질인 경우
                    modalMaterialType.value = '기타';
                    if (customMaterialContainer) {
                        customMaterialContainer.style.display = 'block';
                    }
                    if (modalMaterialTypeCustom) {
                        modalMaterialTypeCustom.value = materialType;
                    }
                } else {
                    modalMaterialType.value = '';
                    if (customMaterialContainer) {
                        customMaterialContainer.style.display = 'none';
                    }
                    if (modalMaterialTypeCustom) {
                        modalMaterialTypeCustom.value = '';
                    }
                }
            }
            
            if (modalPanelThickness) modalPanelThickness.value = panelInfo.thickness || '';
            if (modalPanelNotes) modalPanelNotes.value = panelInfo.notes || '';

            // Load drilling information
            const hasDrilling = document.getElementById('hasDrilling');
            const drillingFields = document.getElementById('drillingFields');
            const modalDrillingWidth = document.getElementById('modalDrillingWidth');
            const modalDrillingHeight = document.getElementById('modalDrillingHeight');
            const modalDrillingFromFloor = document.getElementById('modalDrillingFromFloor');
            const modalDrillingFromEntrance = document.getElementById('modalDrillingFromEntrance');

            if (hasDrilling && panelInfo.has_drilling) {
                hasDrilling.checked = true;
                if (drillingFields) drillingFields.style.display = 'block';
                if (modalDrillingWidth) modalDrillingWidth.value = panelInfo.drilling_width || '';
                if (modalDrillingHeight) modalDrillingHeight.value = panelInfo.drilling_height || '';
                if (modalDrillingFromFloor) modalDrillingFromFloor.value = panelInfo.drilling_from_floor || '';
                if (modalDrillingFromEntrance) modalDrillingFromEntrance.value = panelInfo.drilling_from_entrance || '';
            } else {
                if (hasDrilling) hasDrilling.checked = false;
                if (drillingFields) drillingFields.style.display = 'none';
            }

            // Load corner panel specific data
            if (panelNumber === '1' || panelNumber === '11') {
                const modalPanelTypeIntegrated = document.getElementById('modalPanelTypeIntegrated');
                const modalPanelTypeSeparate = document.getElementById('modalPanelTypeSeparate');
                const modalFrontThickness = document.getElementById('modalFrontThickness');
                const modalFrontWing = document.getElementById('modalFrontWing');
                const modalBackThickness = document.getElementById('modalBackThickness');
                const modalBackWing = document.getElementById('modalBackWing');

                if (modalPanelTypeIntegrated && panelInfo.panel_type === '일체형') {
                    modalPanelTypeIntegrated.checked = true;
                } else if (modalPanelTypeSeparate && panelInfo.panel_type === '분리형') {
                    modalPanelTypeSeparate.checked = true;
                }

                if (modalFrontThickness) modalFrontThickness.value = panelInfo.front_thickness || '';
                if (modalFrontWing) modalFrontWing.value = panelInfo.front_wing || '';
                if (modalBackThickness) modalBackThickness.value = panelInfo.back_thickness || '';
                if (modalBackWing) modalBackWing.value = panelInfo.back_wing || '';
            }

            // Load transom specific data
            if (panelNumber === '12') {
                const modalTransomPlateHeight = document.getElementById('modalTransomPlateHeight');
                const modalBottomDepthJD = document.getElementById('modalBottomDepthJD');
                const modalWingValue = document.getElementById('modalWingValue');
                const modalCpiDrillingWidth = document.getElementById('modalCpiDrillingWidth');
                const modalCpiDrillingHeight = document.getElementById('modalCpiDrillingHeight');
                const modalCpiDrillingHeightFromBottom = document.getElementById('modalCpiDrillingHeightFromBottom');

                if (modalTransomPlateHeight) modalTransomPlateHeight.value = panelInfo.plate_height || '';
                if (modalBottomDepthJD) modalBottomDepthJD.value = panelInfo.bottom_depth_jd || '';
                if (modalWingValue) modalWingValue.value = panelInfo.wing_value || '';
                if (modalCpiDrillingWidth) modalCpiDrillingWidth.value = panelInfo.cpi_drilling_width || '';
                if (modalCpiDrillingHeight) modalCpiDrillingHeight.value = panelInfo.cpi_drilling_height || '';
                if (modalCpiDrillingHeightFromBottom) modalCpiDrillingHeightFromBottom.value = panelInfo.cpi_drilling_height_from_bottom || '';
            }
        }

        function clearPanelForm() {
            // Clear all form fields
            const form = document.getElementById('panelInfoForm');
            if (form) {
                form.reset();
            }
            
            // 재질 직접 입력 필드 숨기기
            const customMaterialContainer = document.getElementById('customMaterialContainer');
            const modalMaterialTypeCustom = document.getElementById('modalMaterialTypeCustom');
            if (customMaterialContainer) {
                customMaterialContainer.style.display = 'none';
            }
            if (modalMaterialTypeCustom) {
                modalMaterialTypeCustom.value = '';
            }
        }

        function savePanelData() {
            if (!currentPanelNumber) return;

            const formData = new FormData(document.getElementById('panelInfoForm'));
            const panelInfo = {};

            // Basic fields
            panelInfo.width = formData.get('panel_width') || '';
            panelInfo.height = formData.get('panel_height') || '';
            
            // 재질 처리 - 직접 입력 필드가 있으면 그 값을 사용, 없으면 드롭박스 값 사용
            const modalMaterialTypeCustom = document.getElementById('modalMaterialTypeCustom');
            if (modalMaterialTypeCustom && modalMaterialTypeCustom.style.display !== 'none' && modalMaterialTypeCustom.value.trim()) {
                panelInfo.material_type = modalMaterialTypeCustom.value.trim();
            } else {
                panelInfo.material_type = formData.get('material_type') || '';
            }
            
            panelInfo.thickness = formData.get('panel_thickness') || '';
            panelInfo.notes = formData.get('panel_notes') || '';

            // Drilling information
            panelInfo.has_drilling = document.getElementById('hasDrilling')?.checked || false;
            panelInfo.drilling_width = formData.get('drilling_width') || '';
            panelInfo.drilling_height = formData.get('drilling_height') || '';
            panelInfo.drilling_from_floor = formData.get('drilling_from_floor') || '';
            panelInfo.drilling_from_entrance = formData.get('drilling_from_entrance') || '';

            // Corner panel specific data
            if (currentPanelNumber === '1' || currentPanelNumber === '11') {
                panelInfo.panel_type = formData.get('cornerPanelType') || '';
                panelInfo.front_thickness = document.getElementById('modalFrontThickness')?.value || '';
                panelInfo.front_wing = document.getElementById('modalFrontWing')?.value || '';
                panelInfo.back_thickness = document.getElementById('modalBackThickness')?.value || '';
                panelInfo.back_wing = document.getElementById('modalBackWing')?.value || '';
            }

            // Transom specific data
            if (currentPanelNumber === '12') {
                panelInfo.plate_height = document.getElementById('modalTransomPlateHeight')?.value || '';
                panelInfo.bottom_depth_jd = document.getElementById('modalBottomDepthJD')?.value || '';
                panelInfo.wing_value = document.getElementById('modalWingValue')?.value || '';
                panelInfo.cpi_drilling_width = document.getElementById('modalCpiDrillingWidth')?.value || '';
                panelInfo.cpi_drilling_height = document.getElementById('modalCpiDrillingHeight')?.value || '';
                panelInfo.cpi_drilling_height_from_bottom = document.getElementById('modalCpiDrillingHeightFromBottom')?.value || '';
            }

            // Save to appropriate data object
            if (currentPanelNumber === '12') {
                window.transomData[currentPanelNumber] = panelInfo;
            } else {
                window.panelData[currentPanelNumber] = panelInfo;
            }

            debugLog(`Panel ${currentPanelNumber} data saved:`, panelInfo);
            
            // 참고: 시각화 업데이트는 모달 저장 버튼에서 호출됨
        }

        function updatePanelVisualState(panelNumber) {
            const panel = document.querySelector(`[data-panel="${panelNumber}"]`);
            if (!panel) return;

            const data = panelNumber === '12' ? window.transomData : window.panelData;
            const panelInfo = data ? data[panelNumber] : null;
            const dimensionsElement = panel.querySelector('.panel-dimensions');

            if (panelInfo && (panelInfo.width || panelInfo.height)) {
                panel.classList.add('has-info');
                panel.style.backgroundColor = 'var(--linear-success)';
                panel.style.color = 'white';
                
                // 타공 정보가 있으면 has-drilling 클래스 추가
                if (panelInfo.has_drilling && panelInfo.drilling_width && panelInfo.drilling_height) {
                    panel.classList.add('has-drilling');
                } else {
                    panel.classList.remove('has-drilling');
                }
                
                // 치수 정보 표시
                if (dimensionsElement) {
                    const width = panelInfo.width || '';
                    const height = panelInfo.height || '';
                    const hasDrilling = panelInfo.has_drilling;
                    const drillingWidth = panelInfo.drilling_width || '';
                    const drillingHeight = panelInfo.drilling_height || '';
                    const drillingFromFloor = panelInfo.drilling_from_floor || '';
                    const drillingFromEntrance = panelInfo.drilling_from_entrance || '';
                    
                    // Transom 패널의 경우 CPI 타공 정보도 확인
                    const cpiDrillingWidth = panelInfo.cpi_drilling_width || '';
                    const cpiDrillingHeight = panelInfo.cpi_drilling_height || '';
                    const cpiDrillingHeightFromBottom = panelInfo.cpi_drilling_height_from_bottom || '';
                    
                    let displayText = '';
                    
                    // 패널 치수 표시 (Transom은 가로만 표시)
                    if (panelNumber === '12') {
                        // Transom 패널: 가로(폭)만 표시
                        if (width) {
                            displayText = `<span style="font-size: 1.1em; font-weight: bold;">${width}</span>`;
                        }
                    } else {
                        // 일반 패널: 가로×세로 표시
                        if (width && height) {
                            displayText = `<span style="font-size: 1.1em; font-weight: bold;">${width}×${height}</span>`;
                        } else if (width) {
                            displayText = `<span style="font-size: 1.1em; font-weight: bold;">W:${width}</span>`;
                        } else if (height) {
                            displayText = `<span style="font-size: 1.1em; font-weight: bold;">H:${height}</span>`;
                        }
                    }
                    
                    // 일반 타공 정보 추가 (패널 1-11번) - 개선된 형식: 가로×세로H높이D거리
                    if (panelNumber !== '12' && hasDrilling && drillingWidth && drillingHeight) {
                        if (displayText) displayText += '<br>';
                        
                        // 타공 정보 문자열 생성: 가로×세로H높이D거리
                        let drillingText = `⊙${drillingWidth}×${drillingHeight}`;
                        
                        // 높이(바닥부터) 추가
                        if (drillingFromFloor) {
                            drillingText += `H${drillingFromFloor}`;
                        }
                        
                        // 거리(출입구에서) 추가
                        if (drillingFromEntrance) {
                            drillingText += `D${drillingFromEntrance}`;
                        }
                        
                        displayText += `<span style="font-size: 0.85em; color: #ff6b6b; display: block; margin-top: 2px;">${drillingText}</span>`;
                    }
                    
                    // CPI 타공 정보 추가 (Transom 패널 12번) - 상판 타공 표시
                    if (panelNumber === '12' && cpiDrillingWidth && cpiDrillingHeight) {
                        if (displayText) displayText += '<br>';
                        
                        // CPI 타공 정보: Transom은 상판이므로 가로×세로만 표시 (높이 정보는 하단부터 거리로 표시)
                        let cpiDrillingText = `CPI${cpiDrillingWidth}×${cpiDrillingHeight}`;
                        
                        // 하단부터 높이 추가 (Transom은 바닥이 아닌 하단부터의 거리)
                        if (cpiDrillingHeightFromBottom) {
                            cpiDrillingText += `↑${cpiDrillingHeightFromBottom}`;
                        }
                        
                        displayText += `<span style="font-size: 0.9em; color: #ff6b6b;">${cpiDrillingText}</span>`;
                    }
                    
                    dimensionsElement.innerHTML = displayText;
                }
            } else {
                panel.classList.remove('has-info');
                panel.style.backgroundColor = '';
                panel.style.color = '';
                
                // 치수 정보 숨김
                if (dimensionsElement) {
                    dimensionsElement.innerHTML = '';
                }
            }
        }

        function updateJsonFields() {
            const panelJsonData = document.getElementById('panelJsonData');
            const transomJsonData = document.getElementById('transomJsonData');
            
            if (panelJsonData) {
                panelJsonData.value = JSON.stringify(window.panelData || {});
            }
            if (transomJsonData) {
                transomJsonData.value = JSON.stringify(window.transomData || {});
            }
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // 현장명 자동생성 버튼
            const generateBtn = document.getElementById('generateSiteNameBtn');
            if (generateBtn) {
                generateBtn.addEventListener('click', generateSiteName);
            }
            
            // iPark 체크박스 변경 이벤트
            const iparkCheckElement = document.getElementById('iparkCheck');
            if (iparkCheckElement) {
                iparkCheckElement.addEventListener('change', updateSiteNameForIpark);
            }
            
            // 신규 모드일 때 자동으로 현장명 생성
            const siteNameInput = document.getElementById('siteName');
            if (siteNameInput && !siteNameInput.value.trim()) {
                // 현장명이 비어있으면 자동생성 실행
                setTimeout(() => {
                    generateSiteName();
                    debugLog('신규 모드: 현장명 자동생성 완료');
                }, 100);
            }

            // Panel events
            attachPanelEvents();

            // 신규/MOD 버튼 이벤트
            const newBtn = document.getElementById('newBtn');
            const modBtn = document.getElementById('modBtn');
            
            if (newBtn) {
                newBtn.addEventListener('click', function() {
                    toggleMode('new');
                });
            }
            
            if (modBtn) {
                modBtn.addEventListener('click', function() {
                    toggleMode('mod');
                });
            }

            // 체크박스 이벤트
            const excludePanelCorners = document.getElementById('excludePanelCorners');
            const excludeTransom = document.getElementById('excludeTransom');
            
            if (excludePanelCorners) {
                excludePanelCorners.addEventListener('change', function() {
                    updatePanelVisibility();
                });
            }
            
            if (excludeTransom) {
                excludeTransom.addEventListener('change', function() {
                    updatePanelVisibility();
                });
            }

            // 초기 상태 설정 - 저장된 값 또는 기본값 적용
            <?php if ($edit_mode): ?>
            // 편집 모드: 저장된 project_type 값으로 초기화
            toggleMode('<?= $defaultProjectType === 'MOD' ? 'mod' : 'new' ?>');
            console.log('📝 편집 모드: project_type = <?= $defaultProjectType ?>');
            <?php else: ?>
            // 신규 모드: 기본값 '신규'로 설정
            toggleMode('new');
            <?php endif; ?>
            
            updatePanelVisibility();
            
            // iPark 체크박스 초기 상태 설정
            const iparkCheckInitial = document.getElementById('iparkCheck');
            if (iparkCheckInitial && iparkCheckInitial.checked) {
                updateSiteNameForIpark();
            }
            
            // 체크박스 초기 상태 로그
            console.log('📋 초기 체크박스 상태:', {
                panelCornersExcluded: excludePanelCorners?.checked,
                transomExcluded: excludeTransom?.checked,
                iparkCheck: iparkCheckInitial?.checked
            });

            // Panel modal events
            const panelModal = document.getElementById('panelModal');
            const panelModalClose = document.getElementById('panelModalClose');
            const panelModalCancel = document.getElementById('panelModalCancel');
            const panelModalSave = document.getElementById('panelModalSave');
            const panelModalReset = document.getElementById('panelModalReset');
            const hasDrilling = document.getElementById('hasDrilling');
            const drillingFields = document.getElementById('drillingFields');

            if (panelModalClose) {
                panelModalClose.addEventListener('click', () => {
                    if (panelModal) panelModal.style.display = 'none';
                });
            }

            if (panelModalCancel) {
                panelModalCancel.addEventListener('click', () => {
                    if (panelModal) panelModal.style.display = 'none';
                });
            }

            if (panelModalSave) {
                panelModalSave.addEventListener('click', () => {
                    savePanelData();
                    
                    // 시각화 즉시 업데이트 (비동기 처리 방지)
                    if (currentPanelNumber) {
                        updatePanelVisualState(currentPanelNumber);
                        updateJsonFields();
                        console.log('✅ 패널 저장 및 시각화 업데이트 완료:', currentPanelNumber);
                    }
                    
                    // 모달 닫기
                    if (panelModal) {
                        panelModal.style.display = 'none';
                    }
                });
            }

            if (panelModalReset) {
                panelModalReset.addEventListener('click', () => {
                    clearPanelForm();
                });
            }

            if (hasDrilling && drillingFields) {
                hasDrilling.addEventListener('change', function() {
                    drillingFields.style.display = this.checked ? 'block' : 'none';
                });
            }

            // 모달 내부 입력 필드들에 엔터키 이벤트 추가
            const modalPanelWidth = document.getElementById('modalPanelWidth');
            const modalPanelHeight = document.getElementById('modalPanelHeight');
            
            if (modalPanelWidth) {
                modalPanelWidth.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        savePanelData();
                        
                        // 시각화 즉시 업데이트
                        if (currentPanelNumber) {
                            updatePanelVisualState(currentPanelNumber);
                            updateJsonFields();
                            console.log('✅ 패널 저장 및 시각화 업데이트 완료 (엔터키):', currentPanelNumber);
                        }
                        
                        // 모달 닫기
                        if (panelModal) {
                            panelModal.style.display = 'none';
                        }
                    }
                });
            }
            
            if (modalPanelHeight) {
                modalPanelHeight.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        savePanelData();
                        
                        // 시각화 즉시 업데이트
                        if (currentPanelNumber) {
                            updatePanelVisualState(currentPanelNumber);
                            updateJsonFields();
                            console.log('✅ 패널 저장 및 시각화 업데이트 완료 (엔터키):', currentPanelNumber);
                        }
                        
                        // 모달 닫기
                        if (panelModal) {
                            panelModal.style.display = 'none';
                        }
                    }
                });
            }

            // 재질 드롭박스와 직접 입력 필드 연동 (모달용)
            const modalMaterialType = document.getElementById('modalMaterialType');
            const modalMaterialTypeCustom = document.getElementById('modalMaterialTypeCustom');
            const customMaterialContainer = document.getElementById('customMaterialContainer');
            
            if (modalMaterialType && modalMaterialTypeCustom && customMaterialContainer) {
                modalMaterialType.addEventListener('change', function() {
                    if (this.value === '기타') {
                        customMaterialContainer.style.display = 'block';
                        modalMaterialTypeCustom.focus();
                    } else {
                        customMaterialContainer.style.display = 'none';
                        modalMaterialTypeCustom.value = '';
                    }
                    
                    // 모달 재질 변경 시 본문으로 동기화
                    syncMaterialToMain();
                });

                // 직접 입력 필드에서 Enter 키로 입력 완료
                modalMaterialTypeCustom.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        if (this.value.trim()) {
                            // 입력된 값을 드롭박스에 새 옵션으로 추가
                            addCustomMaterialOption(this.value.trim());
                        }
                    }
                });

                // 포커스 아웃 시에도 처리
                modalMaterialTypeCustom.addEventListener('blur', function() {
                    if (this.value.trim()) {
                        addCustomMaterialOption(this.value.trim());
                        // 본문으로도 동기화
                        syncMaterialToMain();
                    }
                });
            }

            // 재질 드롭박스와 직접 입력 필드 연동 (본문용)
            const mainMaterialType = document.getElementById('materialType');
            const mainMaterialTypeCustom = document.getElementById('materialTypeCustom');
            const mainCustomMaterialContainer = document.getElementById('mainCustomMaterialContainer');
            
            if (mainMaterialType && mainMaterialTypeCustom && mainCustomMaterialContainer) {
                mainMaterialType.addEventListener('change', function() {
                    if (this.value === '기타') {
                        mainCustomMaterialContainer.style.display = 'block';
                        mainMaterialTypeCustom.focus();
                    } else {
                        mainCustomMaterialContainer.style.display = 'none';
                        mainMaterialTypeCustom.value = '';
                    }
                });

                // 직접 입력 필드에서 Enter 키로 입력 완료
                mainMaterialTypeCustom.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        if (this.value.trim()) {
                            // 입력된 값을 드롭박스에 새 옵션으로 추가
                            addMainCustomMaterialOption(this.value.trim());
                        }
                    }
                });

                // 포커스 아웃 시에도 처리
                mainMaterialTypeCustom.addEventListener('blur', function() {
                    if (this.value.trim()) {
                        addMainCustomMaterialOption(this.value.trim());
                    }
                });

                // 본문 재질 변경 시 모달 재질도 동기화
                mainMaterialType.addEventListener('change', function() {
                    syncMaterialToModal();
                });
            }

            // Copy button for corner panels
            const copyBtn = document.getElementById('copyBtn');
            if (copyBtn) {
                copyBtn.addEventListener('click', function() {
                    if (!currentPanelNumber) return;
                    
                    const targetPanel = currentPanelNumber === '1' ? '11' : '1';
                    const sourceData = window.panelData[currentPanelNumber];
                    
                    if (sourceData) {
                        window.panelData[targetPanel] = { ...sourceData };
                        updatePanelVisualState(targetPanel);
                        updateJsonFields();
                        debugLog(`Panel ${currentPanelNumber} data copied to panel ${targetPanel}`);
                        
                        // 복사 성공 메시지 표시
                        Swal.fire({
                            icon: 'success',
                            title: '복사 완료',
                            text: `${currentPanelNumber}번 패널 데이터가 ${targetPanel}번 패널로 복사되었습니다.`,
                            timer: 2000,
                            showConfirmButton: false,
                            toast: true,
                            position: 'center',
                            customClass: {
                                container: 'swal-high-zindex'
                            }
                        });
                    } else {
                        // 복사할 데이터가 없는 경우 메시지 표시
                        Swal.fire({
                            icon: 'warning',
                            title: '복사 불가',
                            text: `${currentPanelNumber}번 패널에 복사할 데이터가 없습니다.`,
                            timer: 2000,
                            showConfirmButton: false,
                            toast: true,
                            position: 'center',
                            customClass: {
                                container: 'swal-high-zindex'
                            }
                        });
                    }
                });
            }
            
            // 측정값 검증 함수
            function performMeasurementValidation() {
                console.log('=== 측정값 검증 시작 ===');

                // JSON 필드 업데이트
                updateJsonFields();

                // 카 내부 치수 가져오기
                const carInsideWidth = parseInt(document.getElementById('carInsideWidth')?.value) || 0;
                const carInsideDepth = parseInt(document.getElementById('carInsideDepth')?.value) || 0;

                if (!carInsideWidth || !carInsideDepth) {
                    Swal.fire({
                        icon: 'warning',
                        title: '검증 불가',
                        text: '카 내부 치수(W×D)를 먼저 입력해주세요.',
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'center'
                    });
                    return;
                }

                // 패널 데이터에서 폭 합산 (2-10번만)
                let leftSideTotal = 0;  // 2,3,4번
                let backWallTotal = 0;  // 5,6,7번
                let rightSideTotal = 0; // 8,9,10번

                [2, 3, 4].forEach(num => {
                    const width = parseInt(window.panelData[num]?.width) || 0;
                    leftSideTotal += width;
                });

                [5, 6, 7].forEach(num => {
                    const width = parseInt(window.panelData[num]?.width) || 0;
                    backWallTotal += width;
                });

                [8, 9, 10].forEach(num => {
                    const width = parseInt(window.panelData[num]?.width) || 0;
                    rightSideTotal += width;
                });

                // 측정된 패널이 있는지 확인
                if (leftSideTotal === 0 && rightSideTotal === 0 && backWallTotal === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: '검증 불가',
                        text: '측정된 패널이 없습니다. 최소 한 개 이상의 패널을 측정한 후 검증해주세요.',
                        timer: 2500,
                        showConfirmButton: false,
                        toast: true,
                        position: 'center'
                    });
                    return;
                }

                // 공차 계산 (±3mm)
                const leftSideDiff = Math.abs(leftSideTotal - carInsideDepth);
                const rightSideDiff = Math.abs(rightSideTotal - carInsideDepth);
                const backWallDiff = Math.abs(backWallTotal - carInsideWidth);

                // 합격/불합격 판정
                const leftSidePass = leftSideDiff <= 3;
                const rightSidePass = rightSideDiff <= 3;
                const backWallPass = backWallDiff <= 3;

                const allPass = leftSidePass && rightSidePass && backWallPass;
                const passCount = [leftSidePass, rightSidePass, backWallPass].filter(Boolean).length;

                console.log('=== 측면별 검증 결과 ===');
                console.log('좌측벽:', leftSideTotal, 'vs', carInsideDepth, '차이:', leftSideDiff, '합격:', leftSidePass);
                console.log('후면벽:', backWallTotal, 'vs', carInsideWidth, '차이:', backWallDiff, '합격:', backWallPass);
                console.log('우측벽:', rightSideTotal, 'vs', carInsideDepth, '차이:', rightSideDiff, '합격:', rightSidePass);

                // 검증 데이터 생성
                const validationData = {
                    leftSide: { total: leftSideTotal, diff: leftSideDiff, pass: leftSidePass, reference: carInsideDepth },
                    backWall: { total: backWallTotal, diff: backWallDiff, pass: backWallPass, reference: carInsideWidth },
                    rightSide: { total: rightSideTotal, diff: rightSideDiff, pass: rightSidePass, reference: carInsideDepth },
                    allPass: allPass,
                    passCount: passCount
                };

                showValidationResultModal(validationData);
            }

            // 검증 결과 모달 표시 함수
            function showValidationResultModal(results) {
                const modal = document.getElementById('validationResultModal');
                const content = document.getElementById('validationResultContent');

                if (!modal || !content) {
                    console.error('검증 모달 엘리먼트를 찾을 수 없습니다');
                    return;
                }

                // 상세 검증 결과 HTML 생성
                const reportHtml = `
                    <div class="linear-alert ${results.allPass ? 'linear-alert-success' : 'linear-alert-warning'}" style="margin-bottom: 24px; border-radius: 8px;">
                        <div class="linear-alert-content">
                            <h4 class="linear-alert-title" style="margin-bottom: 8px; font-size: 16px;">
                                검증 완료 - ${results.allPass ? '모든 측면이 기준을 충족합니다' : '일부 측면이 기준을 벗어납니다'} (${results.passCount}/3개 측면 적합)
                            </h4>
                            <p class="linear-alert-description" style="margin: 0; font-size: 14px;">
                                허용 공차: ±3mm
                            </p>
                        </div>
                    </div>

                    <div style="
                        background: var(--linear-bg-primary, #ffffff);
                        border: 1px solid var(--linear-border-primary, #e5e7eb);
                        border-radius: 12px;
                        overflow: hidden;
                        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                    ">
                        <div style="
                            display: grid;
                            grid-template-columns: 1fr 1fr 1fr 1fr 80px 100px;
                            gap: 0;
                            background: var(--linear-bg-secondary, #f8fafc);
                            border-bottom: 2px solid var(--linear-border-primary, #e5e7eb);
                            font-weight: 600;
                            font-size: 14px;
                            color: var(--linear-text-primary, #1a202c);
                        ">
                            <div style="padding: 10px 12px; border-right: 1px solid var(--linear-border-secondary, #f1f5f9);">검증부위</div>
                            <div style="padding: 10px 12px; border-right: 1px solid var(--linear-border-secondary, #f1f5f9); text-align: center;">해당 패널</div>
                            <div style="padding: 10px 12px; border-right: 1px solid var(--linear-border-secondary, #f1f5f9); text-align: center;">실측값</div>
                            <div style="padding: 10px 12px; border-right: 1px solid var(--linear-border-secondary, #f1f5f9); text-align: center;">설계값</div>
                            <div style="padding: 10px 12px; border-right: 1px solid var(--linear-border-secondary, #f1f5f9); text-align: center;">편차</div>
                            <div style="padding: 10px 12px; text-align: center;">판정</div>
                        </div>

                        <div style="
                            display: grid;
                            grid-template-columns: 1fr 1fr 1fr 1fr 80px 100px;
                            gap: 0;
                            border-bottom: 1px solid var(--linear-border-secondary, #f1f5f9);
                            font-size: 14px;
                            align-items: center;
                            min-height: 45px;
                        ">
                            <div style="padding: 10px 12px; border-right: 1px solid var(--linear-border-secondary, #f1f5f9); font-weight: 500;">좌측벽</div>
                            <div style="padding: 10px 12px; border-right: 1px solid var(--linear-border-secondary, #f1f5f9); text-align: center;">2, 3, 4번</div>
                            <div style="padding: 10px 12px; border-right: 1px solid var(--linear-border-secondary, #f1f5f9); text-align: center; font-weight: 600;">${results.leftSide.total}mm</div>
                            <div style="padding: 10px 12px; border-right: 1px solid var(--linear-border-secondary, #f1f5f9); text-align: center;">${results.leftSide.reference}mm (D)</div>
                            <div style="padding: 10px 12px; border-right: 1px solid var(--linear-border-secondary, #f1f5f9); text-align: center; font-weight: 600; color: ${results.leftSide.pass ? '#059669' : '#dc2626'};">${results.leftSide.diff}mm</div>
                            <div style="padding: 10px 12px; text-align: center;">
                                <span style="
                                    display: inline-flex;
                                    padding: 4px 12px;
                                    border-radius: 20px;
                                    font-size: 12px;
                                    font-weight: 600;
                                    ${results.leftSide.pass ? 'background: #dcfce7; color: #059669; border: 1px solid #bbf7d0;' : 'background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;'}
                                ">
                                    ${results.leftSide.pass ? '적합' : '부적합'}
                                </span>
                            </div>
                        </div>

                        <div style="
                            display: grid;
                            grid-template-columns: 1fr 1fr 1fr 1fr 80px 100px;
                            gap: 0;
                            border-bottom: 1px solid var(--linear-border-secondary, #f1f5f9);
                            font-size: 14px;
                            align-items: center;
                            min-height: 45px;
                        ">
                            <div style="padding: 10px 12px; border-right: 1px solid var(--linear-border-secondary, #f1f5f9); font-weight: 500;">후면벽</div>
                            <div style="padding: 10px 12px; border-right: 1px solid var(--linear-border-secondary, #f1f5f9); text-align: center;">5, 6, 7번</div>
                            <div style="padding: 10px 12px; border-right: 1px solid var(--linear-border-secondary, #f1f5f9); text-align: center; font-weight: 600;">${results.backWall.total}mm</div>
                            <div style="padding: 10px 12px; border-right: 1px solid var(--linear-border-secondary, #f1f5f9); text-align: center;">${results.backWall.reference}mm (W)</div>
                            <div style="padding: 10px 12px; border-right: 1px solid var(--linear-border-secondary, #f1f5f9); text-align: center; font-weight: 600; color: ${results.backWall.pass ? '#059669' : '#dc2626'};">${results.backWall.diff}mm</div>
                            <div style="padding: 10px 12px; text-align: center;">
                                <span style="
                                    display: inline-flex;
                                    padding: 4px 12px;
                                    border-radius: 20px;
                                    font-size: 12px;
                                    font-weight: 600;
                                    ${results.backWall.pass ? 'background: #dcfce7; color: #059669; border: 1px solid #bbf7d0;' : 'background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;'}
                                ">
                                    ${results.backWall.pass ? '적합' : '부적합'}
                                </span>
                            </div>
                        </div>

                        <div style="
                            display: grid;
                            grid-template-columns: 1fr 1fr 1fr 1fr 80px 100px;
                            gap: 0;
                            font-size: 14px;
                            align-items: center;
                            min-height: 45px;
                        ">
                            <div style="padding: 10px 12px; border-right: 1px solid var(--linear-border-secondary, #f1f5f9); font-weight: 500;">우측벽</div>
                            <div style="padding: 10px 12px; border-right: 1px solid var(--linear-border-secondary, #f1f5f9); text-align: center;">8, 9, 10번</div>
                            <div style="padding: 10px 12px; border-right: 1px solid var(--linear-border-secondary, #f1f5f9); text-align: center; font-weight: 600;">${results.rightSide.total}mm</div>
                            <div style="padding: 10px 12px; border-right: 1px solid var(--linear-border-secondary, #f1f5f9); text-align: center;">${results.rightSide.reference}mm (D)</div>
                            <div style="padding: 10px 12px; border-right: 1px solid var(--linear-border-secondary, #f1f5f9); text-align: center; font-weight: 600; color: ${results.rightSide.pass ? '#059669' : '#dc2626'};">${results.rightSide.diff}mm</div>
                            <div style="padding: 10px 12px; text-align: center;">
                                <span style="
                                    display: inline-flex;
                                    padding: 4px 12px;
                                    border-radius: 20px;
                                    font-size: 12px;
                                    font-weight: 600;
                                    ${results.rightSide.pass ? 'background: #dcfce7; color: #059669; border: 1px solid #bbf7d0;' : 'background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;'}
                                ">
                                    ${results.rightSide.pass ? '적합' : '부적합'}
                                </span>
                            </div>
                        </div>
                    </div>
                `;

                content.innerHTML = reportHtml;
                modal.style.display = 'block';
            }

            // 검증 결과 모달 닫기 함수
            window.closeValidationResultModal = function() {
                const modal = document.getElementById('validationResultModal');
                if (modal) {
                    modal.style.display = 'none';
                }
            }

            // Validate button
            const validateBtn = document.getElementById('validateBtn');
            if (validateBtn) {
                validateBtn.addEventListener('click', performMeasurementValidation);
            }
            
            // Back button
            const backBtn = document.getElementById('backBtn');
            if (backBtn) {
                backBtn.addEventListener('click', function() {
                    window.history.back();
                });
            }
            
            // Form submission
            const form = document.getElementById('measurementForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault(); // 기본 폼 제출 중단
                    
                    // 폼 제출 전에 JSON 필드 업데이트
                    updateJsonFields();
                    
                    console.log('📤 폼 제출 준비:');
                    console.log('panelData:', window.panelData);
                    console.log('transomData:', window.transomData);
                    console.log('panelJsonData:', document.getElementById('panelJsonData')?.value);
                    console.log('transomJsonData:', document.getElementById('transomJsonData')?.value);
                    
                    if (!validateForm()) {
                        return;
                    }
                    
                    // AJAX로 서버에 전송
                    const formData = new FormData(form);
                    
                    // 체크박스가 체크되지 않았을 때도 값을 명시적으로 설정
                    const excludePanelCorners = document.getElementById('excludePanelCorners');
                    const excludeTransom = document.getElementById('excludeTransom');
                    
                    if (excludePanelCorners) {
                        if (excludePanelCorners.checked) {
                            formData.set('panel_corners_excluded', '1');
                        } else {
                            formData.set('panel_corners_excluded', '0');
                        }
                    }
                    
                    if (excludeTransom) {
                        if (excludeTransom.checked) {
                            formData.set('transom_excluded', '1');
                        } else {
                            formData.set('transom_excluded', '0');
                        }
                    }
                    
                    // 로딩 표시
                    Swal.fire({
                        title: '저장 중...',
                        text: '측정 데이터를 저장하고 있습니다.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    fetch('save_panel_measurement.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log('📥 서버 응답:', data);
                        
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '저장 완료',
                                text: data.message || '측정 데이터가 성공적으로 저장되었습니다.',
                                timer: 2000,
                                showConfirmButton: false,
                                toast: true,
                                position: 'center'
                            }).then(() => {
                                // 저장 성공 후 목록으로 이동
                                if (data.redirect) {
                                    window.location.href = data.redirect;
                                } else {
                                    window.location.href = 'site_list.php';
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: '저장 실패',
                                html: data.message || '저장 중 오류가 발생했습니다.<br>다시 시도해주세요.',
                                confirmButtonText: '확인'
                            });
                            console.error('❌ 저장 실패:', data);
                        }
                    })
                    .catch(error => {
                        console.error('❌ 서버 오류:', error);
                        Swal.fire({
                            icon: 'error',
                            title: '서버 오류',
                            text: '서버와 통신 중 오류가 발생했습니다.',
                            confirmButtonText: '확인'
                        });
                    });
                });
            }
        });

        // Theme toggle - index.php와 동일한 로직
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

            // 복사 및 삭제 기능 (편집 모드 전용)
            const copyDataBtn = document.getElementById('copyDataBtn');
            if (copyDataBtn) {
                copyDataBtn.addEventListener('click', function() {
                    Swal.fire({
                        title: '<i class="bi bi-copy"></i> 데이터 복사',
                        text: '현재 측정 데이터를 복사해서 새로운 측정으로 생성하시겠습니까?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: '<i class="bi bi-check"></i> 복사하기',
                        cancelButtonText: '<i class="bi bi-x"></i> 취소',
                        confirmButtonColor: '#2563eb',
                        cancelButtonColor: '#6b7280',
                        customClass: {
                            container: 'swal-high-zindex'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            copyCurrentData();
                        }
                    });
                });
            }

            const deleteDataBtn = document.getElementById('deleteDataBtn');
            if (deleteDataBtn) {
                deleteDataBtn.addEventListener('click', function() {
                    Swal.fire({
                        title: '<i class="bi bi-exclamation-triangle text-danger"></i> 데이터 삭제',
                        text: '이 측정 데이터를 완전히 삭제하시겠습니까? 삭제된 데이터는 복구할 수 없습니다.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: '<i class="bi bi-trash"></i> 삭제하기',
                        cancelButtonText: '<i class="bi bi-x"></i> 취소',
                        confirmButtonColor: '#dc2626',
                        cancelButtonColor: '#6b7280',
                        customClass: {
                            container: 'swal-high-zindex'
                        },
                        dangerMode: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            deleteCurrentData();
                        }
                    });
                });
            }
        });

        // 현재 데이터 복사 함수
        function copyCurrentData() {
            const urlParams = new URLSearchParams(window.location.search);
            const editId = urlParams.get('edit');
            
            if (!editId) {
                Swal.fire({
                    icon: 'error',
                    title: '오류',
                    text: '복사할 데이터를 찾을 수 없습니다.',
                    customClass: { container: 'swal-high-zindex' }
                });
                return;
            }

            // 로딩 표시
            Swal.fire({
                title: '데이터 복사 중...',
                text: '잠시만 기다려주세요.',
                allowOutsideClick: false,
                showConfirmButton: false,
                customClass: { container: 'swal-high-zindex' },
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // 복사 요청
            fetch('copy_panel_measurement.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    edit_id: editId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '복사 완료',
                        text: data.message || '데이터가 성공적으로 복사되었습니다.',
                        customClass: { container: 'swal-high-zindex' }
                    }).then(() => {
                        // 복사된 데이터로 이동
                        if (data.new_id) {
                            window.location.href = `panel_measurement.php?edit=${data.new_id}`;
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: '복사 실패',
                        text: data.message || '데이터 복사 중 오류가 발생했습니다.',
                        customClass: { container: 'swal-high-zindex' }
                    });
                } 
            }) 
            .catch(error => {
                console.error('Copy error:', error);
                Swal.fire({
                    icon: 'error',
                    title: '오류',
                    text: '데이터 복사 중 오류가 발생했습니다.',
                    customClass: { container: 'swal-high-zindex' }
                });
            });
        }

        // 현재 데이터 삭제 함수
        function deleteCurrentData() {
            const urlParams = new URLSearchParams(window.location.search);
            const editId = urlParams.get('edit');
            
            if (!editId) {
                Swal.fire({
                    icon: 'error',
                    title: '오류',
                    text: '삭제할 데이터를 찾을 수 없습니다.',
                    customClass: { container: 'swal-high-zindex' }
                });
                return;
            }

            // 로딩 표시
            Swal.fire({ 
                title: '데이터 삭제 중...',
                text: '잠시만 기다려주세요.',
                allowOutsideClick: false,
                showConfirmButton: false,
                customClass: { container: 'swal-high-zindex' },
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // 삭제 요청
            fetch('delete_panel_measurement.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    edit_id: editId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '삭제 완료',
                        text: data.message || '데이터가 성공적으로 삭제되었습니다.',
                        customClass: { container: 'swal-high-zindex' }
                    }).then(() => {
                        // 목록 페이지로 이동
                        window.location.href = 'list.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: '삭제 실패',
                        text: data.message || '데이터 삭제 중 오류가 발생했습니다.',
                        customClass: { container: 'swal-high-zindex' }
                    });
                }
            })
            .catch(error => {
                console.error('Delete error:', error);
                Swal.fire({
                    icon: 'error',
                    title: '오류',
                    text: '데이터 삭제 중 오류가 발생했습니다.',
                    customClass: { container: 'swal-high-zindex' }
                });
            });
        }
    </script>

    <script src="assets/js/panel_measurement.js"></script>
</body>
</html> 