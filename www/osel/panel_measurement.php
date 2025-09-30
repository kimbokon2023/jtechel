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
                error_log("Edit mode: Loaded panel_data: " . print_r($edit_panel_data, true));
            }

            if (!empty($edit_data['transom_data'])) {
                $edit_transom_data = json_decode($edit_data['transom_data'], true) ?? [];
                error_log("Edit mode: Loaded transom_data: " . print_r($edit_transom_data, true));
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
?> 
<!DOCTYPE html>   
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Cache Busting for JS Bug Fix -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title><?= $edit_mode ? '카 판넬 측정' : '카 판넬 측정' ?></title>

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

    <!-- Mobile Modal Handler -->
    <script src="assets/js/mobile-modal-handler.js"></script>
    <script src="assets/js/mobile-modal-enhancement.js"></script>

    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        
        .panel-measurement-container {
            max-width: var(--linear-page-max-width);
            margin: 0 auto;
            padding: var(--linear-spacing-lg);
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
        }
        
        .measurement-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: var(--linear-spacing-xl);
            margin-bottom: var(--linear-spacing-3xl);
        }
        
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
        
        .date-picker {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--linear-bg-primary);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-md);
            box-shadow: var(--linear-shadow-medium);
            padding: var(--linear-spacing-md);
            z-index: 1000;
            display: none;
        }
        
        .date-picker-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--linear-spacing-md);
        }
        
        .date-picker-nav {
            background: var(--linear-bg-secondary);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-sm);
            padding: var(--linear-spacing-xs) var(--linear-spacing-sm);
            cursor: pointer;
            color: var(--linear-text-primary);
        }
        
        .date-picker-nav:hover {
            background: var(--linear-bg-tertiary);
        }
        
        .date-picker-title {
            font-weight: var(--linear-font-weight-semibold);
            color: var(--linear-text-primary);
        }
        
        .date-picker-calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 2px;
        }
        
        .date-picker-day-header {
            padding: var(--linear-spacing-xs);
            text-align: center;
            font-size: var(--linear-text-small);
            font-weight: var(--linear-font-weight-medium);
            color: var(--linear-text-secondary);
        }
        
        .date-picker-day {
            padding: var(--linear-spacing-sm);
            text-align: center;
            cursor: pointer;
            border-radius: var(--linear-radius-sm);
            font-size: var(--linear-text-small);
            color: var(--linear-text-primary);
            touch-action: manipulation;
            min-height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .date-picker-day:hover {
            background: var(--linear-bg-tertiary);
        }
        
        .date-picker-day.selected {
            background: var(--linear-brand-primary);
            color: white;
        }
        
        .date-picker-day.other-month {
            color: var(--linear-text-tertiary);
        }
        
        .date-picker-day.today {
            border: 2px solid var(--linear-brand-primary);
        }
        
        /* 판넬 토글 버튼 상태별 스타일 */
        .panel-toggle-excluded {
            background: var(--linear-color-orange) !important;
            color: white !important;
            border-color: var(--linear-color-orange) !important;
        }
        
        .panel-toggle-excluded:hover {
            background: var(--linear-color-orange-dark, #e67e22) !important;
            border-color: var(--linear-color-orange-dark, #e67e22) !important;
        }
        
        .panel-toggle-included {
            background: var(--linear-color-green) !important;
            color: white !important;
            border-color: var(--linear-color-green) !important;
        }
        
        .panel-toggle-included:hover {
            background: var(--linear-color-green-dark, #27ae60) !important;
            border-color: var(--linear-color-green-dark, #27ae60) !important;
        }
        
        /* 입력 필드 너비 제한 및 반응형 처리 */
        .linear-input-group {
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
        }
        
        .linear-input,
        .date-input-container input {
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box;
        }
        
        /* 모바일 카드 내부 요소들 너비 제한 */
        .mobile-card-1 .linear-input-group,
        .mobile-card-1 .date-input-container,
        .mobile-card-1 .linear-input,
        .mobile-card-3 .linear-input-group,
        .mobile-card-3 .linear-input {
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box;
            overflow: hidden;
        }
        
        /* PC에서 아이파크 체크박스 강제 표시 */
        #iparkCheckContainer {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            margin-bottom: 20px !important;
        }
        
        #iparkCheckContainer label {
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            cursor: pointer !important;
            padding: 12px !important; 
            border: 1px solid #ddd !important;
            border-radius: 6px !important;
            background: #f8f9fa !important;
            transition: all 0.2s ease !important;
        }
        
        #iparkCheckContainer label:hover {
            background: #e9ecef !important;
            border-color: #007bff !important;
        }
        
        #iparkCheckContainer label span {
            font-weight: 500 !important;
            color: #333 !important;
            font-size: 14px !important;
            display: inline !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        #iparkCheckContainer label i {
            color: #007bff !important;
            margin-right: 6px !important;
            display: inline !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        /* 다크 테마에서도 텍스트가 보이도록 강화 */
        body.dark-theme #iparkCheckContainer label span,
        [data-theme="dark"] #iparkCheckContainer label span {
            color: #ffffff !important;
            font-weight: 600 !important;
        }
        
        body.dark-theme #iparkCheckContainer label,
        [data-theme="dark"] #iparkCheckContainer label {
            background: #2d3748 !important;
            border-color: #4a5568 !important;
            color: #ffffff !important;
        }
        
        body.dark-theme #iparkCheckContainer label:hover,
        [data-theme="dark"] #iparkCheckContainer label:hover {
            background: #4a5568 !important;
            border-color: #007bff !important;
        }
        
        #iparkCheck {
            width: 18px !important;
            height: 18px !important;
            margin: 0 !important;
            cursor: pointer !important;
        }
        
        /* 모바일에서 PC 버전 카드들 숨기기 */
        @media (max-width: 768px) {
            #iparkCheckContainer {
                display: none !important;
            }
            
            /* 모바일 전용 아이파크 체크박스는 표시 */
            #mobileIparkCheckContainer {
                display: block !important;
                margin-bottom: 15px !important;
                visibility: visible !important;
                opacity: 1 !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }
            
            /* 모바일 아이파크 체크박스 내부 요소들도 표시 */
            #mobileIparkCheck,
            #mobileIparkCheckLabel {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                cursor: pointer !important;
            }
            
            /* 모바일 아이파크 체크박스 그룹도 표시 */
            #mobileIparkCheckContainer .linear-input-group {
                display: block !important;
                visibility: visible !important;
                width: 100% !important;
                margin-bottom: 10px !important;
            }
            
            /* 모바일 카드 전체 표시 강화 */
            .mobile-only-cards {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
            }
            
            .mobile-card-1,
            .mobile-card-3 {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
            }
            
            .mobile-card-1 .linear-card {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
            }
            
            .mobile-card-1 .linear-card-body,
            .mobile-card-3 .linear-card,
            .mobile-card-3 .linear-card-body {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
            }
            
            #mobile-site-info {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                width: 100% !important;
            }
            
            /* 🎯 PHASE 4: 모바일에서 반응형 컨테이너 표시 */
            .responsive-container {
                display: block !important;
            }
            
            .responsive-card,
            .responsive-section {
                display: block !important;
            }
        }
        
        /* 카드 내부 패딩 및 오버플로우 처리 */
        .linear-card-body {
            padding: 5px;
            overflow: hidden;
            box-sizing: border-box;
        }
        
        .form-section {
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
        }
        
        /* 🎯 PHASE 4: 모바일 전용 카드 CSS 제거됨 (더 이상 사용하지 않음) */

        /* ============================================
           📱 PHASE 1: 반응형 CSS 프레임워크
           모바일 퍼스트 리팩토링 진행 중
           ============================================ */
        
        /* 🎯 반응형 컨테이너 - 모바일 우선 */
        .responsive-container {
            width: 100%;
            padding: 1rem;
            box-sizing: border-box;
        }

        /* 🎯 반응형 섹션 */
        .responsive-section {
            width: 100%;
            margin-bottom: 1.5rem;
            background: var(--linear-bg-primary, #ffffff);
            border-radius: var(--linear-radius-lg, 12px);
            padding: 1rem;
            box-sizing: border-box;
        }

        .responsive-section-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--linear-text-primary, #1a1a1a);
        }

        /* 🎯 반응형 입력 필드 */
        .responsive-input-group {
            width: 100%;
            margin-bottom: 1rem;
        }

        .responsive-input-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.875rem;
            color: var(--linear-text-secondary, #666);
        }

        .responsive-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--linear-border-color, #e0e0e0);
            border-radius: var(--linear-radius-md, 8px);
            font-size: 1rem;
            box-sizing: border-box;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .responsive-input:focus {
            outline: none;
            border-color: var(--linear-color-primary, #5e6ad2);
            box-shadow: 0 0 0 3px rgba(94, 106, 210, 0.1);
        }

        /* 🎯 반응형 그리드 (모바일: 1열, 태블릿+: 2열) */
        .responsive-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        /* 🎯 반응형 버튼 */
        .responsive-button {
            width: 100%;
            padding: 0.875rem;
            font-size: 1rem;
            border-radius: var(--linear-radius-md, 8px);
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .responsive-button-primary {
            background: var(--linear-color-primary, #5e6ad2);
            color: white;
        }

        .responsive-button-primary:hover {
            background: var(--linear-color-primary-hover, #4c5ab8);
        }

        /* 🎯 반응형 카드 */
        .responsive-card {
            background: var(--linear-bg-primary, #ffffff);
            border-radius: var(--linear-radius-lg, 12px);
            border: 1px solid var(--linear-border-color, #e0e0e0);
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .responsive-card-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--linear-border-color, #e0e0e0);
        }

        .responsive-card-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--linear-text-primary, #1a1a1a);
            margin: 0;
        }

        .responsive-card-body {
            padding: 0;
        }

        /* 📱 모바일 전용 스타일 (max-width: 767px) */
        @media (max-width: 767px) {
            .responsive-container {
                padding: 0.75rem;
            }

            .responsive-section {
                padding: 0.875rem;
                margin-bottom: 1rem;
            }

            .responsive-section-title {
                font-size: 1rem;
            }

            .responsive-input {
                padding: 0.625rem;
                font-size: 16px; /* iOS zoom 방지 */
            }

            .responsive-button {
                padding: 0.75rem;
            }

            /* 모바일: 스티키 버튼 영역 */
            .responsive-sticky-bottom {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: white;
                padding: 1rem;
                box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
                z-index: 100;
            }
        }

        /* 💻 태블릿 (768px ~ 1023px) */
        @media (min-width: 768px) and (max-width: 1023px) {
            .responsive-container {
                padding: 1.5rem;
                max-width: 768px;
                margin: 0 auto;
            }

            .responsive-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1.25rem;
            }

            .responsive-section {
                padding: 1.25rem;
            }

            .responsive-button {
                width: auto;
                min-width: 120px;
            }
        }

        /* 🖥️ PC (1024px 이상) */
        @media (min-width: 1024px) {
            .responsive-container {
                padding: 2rem;
                max-width: 1200px;
                margin: 0 auto;
            }

            /* PC: 사이드바 + 메인 레이아웃 */
            .responsive-layout-sidebar {
                display: grid;
                grid-template-columns: 400px 1fr;
                gap: 2rem;
                align-items: start;
            }

            .responsive-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1.5rem;
            }

            .responsive-grid-3 {
                grid-template-columns: repeat(3, 1fr);
            }

            .responsive-section {
                padding: 1.5rem;
            }

            .responsive-button {
                width: auto;
                min-width: 140px;
                padding: 0.875rem 1.5rem;
            }

            /* PC: 버튼 그룹을 오른쪽 정렬 */
            .responsive-button-group {
                display: flex;
                gap: 1rem;
                justify-content: flex-end;
            }
        }

        /* 🖥️ 대형 화면 (1440px 이상) */
        @media (min-width: 1440px) {
            .responsive-container {
                max-width: 1400px;
            }

            .responsive-layout-sidebar {
                grid-template-columns: 450px 1fr;
                gap: 2.5rem;
            }
        }

        /* 🎨 유틸리티 클래스 */
        .hide-mobile {
            display: none !important;
        }

        .hide-desktop {
            display: block !important;
        }

        @media (min-width: 768px) {
            .hide-mobile {
                display: block !important;
            }

            .hide-desktop {
                display: none !important;
            }
        }

        /* 반응형 여백 */
        .responsive-spacing-sm {
            margin-bottom: 0.5rem;
        }

        .responsive-spacing-md {
            margin-bottom: 1rem;
        }

        .responsive-spacing-lg {
            margin-bottom: 1.5rem;
        }

        @media (min-width: 768px) {
            .responsive-spacing-sm {
                margin-bottom: 0.75rem;
            }

            .responsive-spacing-md {
                margin-bottom: 1.5rem;
            }

            .responsive-spacing-lg {
                margin-bottom: 2rem;
            }
        }

        @media (max-width: 768px) {
            .measurement-grid {
                grid-template-columns: 1fr;
            }
            
            .panel-measurement-container {
                padding: var(--linear-spacing-md);
            }
            
            .car-wall {
                height: 400px;
            }
            
            .dimensions-grid {
                grid-template-columns: 1fr 1fr 1fr;
                gap: var(--linear-spacing-sm);
            }
            
            /* 모바일에서 W×D×H 입력 필드 스타일 */
            .dimensions-grid input[type="number"] {
                width: 100% !important;
                max-width: 100%;
                text-align: center;
                font-size: 0.9rem;
                padding: 8px 4px;
            }
            
            /* 모바일에서 차원 레이블 조정 */
            .dimensions-grid .dimension-label {
                font-size: 0.8rem;
                margin-bottom: 4px;
                text-align: center;
            }
            
            .dimensions-grid .dimension-label .dimension-icon {
                font-size: 0.9rem;
            }
            
            /* 모바일에서 기존 그리드 숨기고 분리된 카드 표시 */
            .measurement-grid {
                display: none;
            }
            
            .mobile-only-cards {
                display: flex !important;
                flex-direction: column;
                gap: var(--linear-spacing-lg);
            }
            
            .mobile-card-1,
            .mobile-card-2,
            .mobile-card-3 {
                background: var(--linear-bg-primary);
                border: 1px solid var(--linear-border-primary);
                border-radius: var(--linear-radius-lg);
                overflow: hidden;
                box-shadow: var(--linear-shadow-low);
                width: 100%;
                max-width: 100%;
                box-sizing: border-box;
            }
            
            /* 모바일에서 입력 필드 추가 스타일 */
            .mobile-card-1 .linear-input-group {
                margin-bottom: 5px;
            }
            
            .mobile-card-1 .linear-input,
            .mobile-card-1 select,
            .mobile-card-1 textarea {
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box;
                font-size: var(--linear-text-body);
            }
            
            /* 모바일 카드 내부 여백 조정 */
            .mobile-card-1 .linear-card-body {
                padding: 5px;
            }

            /* 모바일에서 패널 텍스트 위치 조정 - 왼쪽 패널 (2,3,4번) */
            .panel-2, .panel-3, .panel-4 {
                text-align: center !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                padding: 2px !important;
                font-size: 0.7rem !important;
            }

            /* 모바일에서 패널 텍스트 위치 조정 - 오른쪽 패널 (8,9,10번) */
            .panel-8, .panel-9, .panel-10 {
                text-align: center !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                padding: 2px !important;
                font-size: 0.7rem !important;
                left: 89% !important; /* 기존 92%에서 3% 왼쪽으로 이동 (한글자 크기만큼) */
            }

            /* 모든 패널의 텍스트가 잘리지 않도록 */
            .panel-item {
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                line-height: 1.2;
                width: 100%;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }

        /* 패널 width 값 색상 설정 - 라이트/다크 모드 대응 */
        .panel-width-value {
            color: #1a73e8; /* 기본 파란색 */
        }

        /* 다크 모드에서 노란색으로 변경 */
        [data-theme="dark"] .panel-width-value {
            color: #ffd700; /* 노란색 */
        }

        /* 자동 모드에서 시스템 다크 모드일 때 노란색 */
        @media (prefers-color-scheme: dark) {
            html:not([data-theme="light"]) .panel-width-value {
                color: #ffd700; /* 노란색 */
            }
        }
    </style>
</head>
<body>
    <?php
    // Linear 네비게이션 생성
    require_once '../components/LinearComponent.php';
    require_once '../components/LinearNavigation.php';
    
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
    ->addAction('<a href="#" onclick="handlePageLeaveAttempt(\'navigation\', \'index.php\'); return false;" style="color: var(--linear-text-secondary); text-decoration: none; margin-right: 1rem;">대시보드</a>')
    ->addAction('<a href="../login/logout.php" style="color: var(--linear-text-secondary); text-decoration: none;">로그아웃</a>')
    ->fixed();
    
    echo $nav;
    ?>
    
    <div class="panel-measurement-container" style="margin-top: var(--linear-header-height);">
        <!-- Breadcrumb -->
        <nav class="breadcrumb">
            <a href="#" onclick="handlePageLeaveAttempt('breadcrumb', 'index.php'); return false;">대시보드</a>
            <span class="breadcrumb-separator">/</span>
            <span>판넬 측정</span>
        </nav>
        
        <div class="page-title-container" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--linear-spacing-xl);">
            <h2 class="page-title" style="margin: 0;">
                <i class="bi bi-grid-3x3-gap"></i>
                <?= $edit_mode ? 'EL 카 판넬 측정' : 'EL 카 판넬 측정' ?>
            </h2>
            
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

        <!-- 🎯 PHASE 4: 모바일 전용 카드 제거됨 (반응형으로 완전 통합) -->
        
        <!-- 🎯 PHASE 2: measurement-grid를 responsive-container로 변경 -->
        <div class="responsive-container">
            <!-- Measurement Form (현장정보 카드) -->
            <div class="responsive-card">
                <div class="responsive-card-header">
                    <h3 class="responsive-card-title"><i class="bi bi-building"></i> 현장 정보 입력</h3>
                </div>
                <div class="responsive-card-body">
                
                <form id="measurementForm" action="save_panel_measurement.php" method="POST">
                    <?php if ($edit_mode): ?>
                        <input type="hidden" name="edit_id" value="<?= $edit_id ?>">
                    <?php endif; ?>
                    <!-- Site Information -->
                    <!-- 🎯 PHASE 2: 반응형으로 전환 시작 -->
                    <div class="form-section responsive-section">

                        <?php
                        require_once '../components/LinearInput.php';

                        // iPark New Checkbox
                        $defaultIparkCheck = $edit_mode ? ($edit_data['ipark_check'] ?? 0) : 0;
                        ?>
                        <!-- 아이파크 신규 체크박스 -->
                        <div id="iparkCheckContainer" style="margin-bottom: var(--linear-spacing-lg);">
                            <label id="iparkCheckLabel" for="iparkCheck" style="display: flex; align-items: center; gap: var(--linear-spacing-sm); cursor: pointer; padding: var(--linear-spacing-md); border: 1px solid var(--linear-border-secondary); border-radius: var(--linear-radius-md); background: var(--linear-bg-secondary); transition: all 0.2s ease;">
                                <input type="checkbox" id="iparkCheck" name="ipark_check" value="1"
                                       <?= $defaultIparkCheck ? 'checked' : '' ?>
                                       style="width: 18px; height: 18px; accent-color: var(--linear-color-primary);">
                                <span style="font-weight: var(--linear-font-weight-medium); color: var(--linear-text-primary);">
                                    <i class="bi bi-building"></i> 아이파크 신규 체크
                                </span>
                            </label>

                            <!-- 아이파크 판넬폭 설정 (인라인 div 토글) -->
                            <div id="iparkSettingsDiv" class="ipark-settings-panel" style="display: none; margin-top: 15px; padding: 20px; border-radius: 8px;">
                                <div style="margin-bottom: 15px;">
                                    <h4 class="ipark-settings-title" style="margin: 0 0 10px 0; font-size: 16px;">
                                        <i class="bi bi-gear-fill ipark-settings-icon" style="margin-right: 8px;"></i>
                                        아이파크 판넬폭 설정
                                    </h4>
                                    <p class="ipark-settings-description" style="margin: 0; font-size: 14px;">아이파크 신규 설정 시 각 패널의 폭을 조정할 수 있습니다.</p>
                                </div>

                                <form id="iparkInfoForm">
                                    <!-- 3,9번 패널 폭 설정 -->
                                    <div style="margin-bottom: 15px;">
                                        <label class="ipark-settings-label" style="display: block; margin-bottom: 5px; font-weight: 500;">3,9번 패널 폭 (mm)</label>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <input type="number" class="form-control ipark-settings-input" id="iparkPanel39Width" name="panel_39_width"
                                                   placeholder="800" min="100" max="2000" step="10" value="800"
                                                   style="width: 120px; display: inline-block;">
                                            <span class="ipark-settings-unit" style="font-size: 14px;">mm</span>
                                        </div>
                                        <small class="ipark-settings-hint" style="font-size: 12px;">※ 기본값: 800mm</small>
                                    </div>

                                    <!-- 6번 패널 폭 설정 -->
                                    <div style="margin-bottom: 15px;">
                                        <label class="ipark-settings-label" style="display: block; margin-bottom: 5px; font-weight: 500;">6번 패널 폭 (mm)</label>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <input type="number" class="form-control ipark-settings-input" id="iparkPanel6Width" name="panel_6_width"
                                                   placeholder="1000" min="100" max="2000" step="10" value="1000"
                                                   style="width: 120px; display: inline-block;">
                                            <span class="ipark-settings-unit" style="font-size: 14px;">mm</span>
                                        </div>
                                        <small class="ipark-settings-hint" style="font-size: 12px;">※ 기본값: 1000mm</small>
                                    </div>
                                </form>

                                <!-- 버튼들 -->
                                <div style="margin-top: 20px; display: flex; gap: 10px;">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="iparkDivCancel">취소</button>
                                    <button type="button" class="btn btn-primary btn-sm" id="iparkDivSave">설정 완료</button>
                                </div>
                            </div>

                        </div>

                        <?php
                        // 🎯 PHASE 2: 반응형 현장명 입력 (PC/모바일 공통)
                        $defaultSiteName = $edit_mode ? $edit_data['site_name'] : '임시현장_' . date('ymdHi');
                        ?>
                        <div class="responsive-input-group">
                            <label for="siteName" class="linear-label">
                                현장명 <span style="color: var(--linear-color-red);">*</span>
                            </label>
                            <input type="text" 
                                   id="siteName" 
                                   name="site_name" 
                                   class="linear-input responsive-input" 
                                   placeholder="현장명을 입력하세요"
                                   value="<?= htmlspecialchars($defaultSiteName) ?>"
                                   list="existingSites"
                                   required>
                            <datalist id="existingSites">
                                <?php foreach ($existing_sites as $site): ?>
                                    <option value="<?= htmlspecialchars($site) ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        
                        <?php
                        // 🎯 PHASE 2: 반응형 측정일자/측정자 (모바일: 1열, PC: 2열)
                        ?>
                        <div class="responsive-grid">
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
                                        <div class="date-picker-header">
                                            <button type="button" class="date-picker-nav" id="prevMonth">◀</button>
                                            <div class="date-picker-title" id="monthYear"></div>
                                            <button type="button" class="date-picker-nav" id="nextMonth">▶</button>
                                        </div>
                                        <div class="date-picker-calendar" id="calendar">
                                            <div class="date-picker-day-header">일</div>
                                            <div class="date-picker-day-header">월</div>
                                            <div class="date-picker-day-header">화</div>
                                            <div class="date-picker-day-header">수</div>
                                            <div class="date-picker-day-header">목</div>
                                            <div class="date-picker-day-header">금</div>
                                            <div class="date-picker-day-header">토</div>
                                        </div>
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
                    </div> 

                    <!-- Selected Panel Info -->
                    <div id="selectedPanelInfo" class="selected-panel-info" style="display: none;">
                        <h6 class="mb-2">선택된 판넬: <span id="selectedPanelNumber">-</span></h6>
                        <p class="mb-0 small">판넬의 측정값을 입력해주세요</p>
                    </div>

                    <!-- 🎯 PHASE 2: 카 내부 W x D x H (반응형) -->
                    <div class="responsive-section">
                        <h6 class="responsive-section-title">카 내부 W x D x H</h6>
                        
                        <div id="carInsideInputs" class="responsive-grid responsive-grid-3">
                            <?php
                            // Car Inside Width
                            $defaultWidth = $edit_mode ? $edit_data['car_inside_width'] : '1600';
                            ?>
                            <div class="responsive-input-group">
                                <label for="carInsideWidth" class="linear-label">
                                    <i class="bi bi-arrows-horizontal" style="margin-right: 4px;"></i>
                                    가로 (W) <small style="color: var(--linear-text-tertiary);">mm</small>
                                </label>
                                <input type="number" 
                                       id="carInsideWidth" 
                                       name="car_inside_width" 
                                       class="linear-input responsive-input"
                                       value="<?= htmlspecialchars($defaultWidth) ?>"
                                       min="800" 
                                       max="2500" 
                                       step="5">
                            </div>
                            
                            <?php
                            // Car Inside Depth
                            $defaultDepth = $edit_mode ? $edit_data['car_inside_depth'] : '1500';
                            ?>
                            <div class="responsive-input-group">
                                <label for="carInsideDepth" class="linear-label">
                                    <i class="bi bi-arrow-up-down" style="margin-right: 4px;"></i>
                                    깊이 (D) <small style="color: var(--linear-text-tertiary);">mm</small>
                                </label>
                                <input type="number" 
                                       id="carInsideDepth" 
                                       name="car_inside_depth" 
                                       class="linear-input responsive-input"
                                       value="<?= htmlspecialchars($defaultDepth) ?>"
                                       min="800" 
                                       max="2000" 
                                       step="5">
                            </div>
                            
                            <?php
                            // Car Inside Height
                            $defaultHeight = $edit_mode ? $edit_data['car_inside_height'] : '2700';
                            ?>
                            <div class="responsive-input-group">
                                <label for="carInsideHeight" class="linear-label">
                                    <i class="bi bi-arrows-vertical" style="margin-right: 4px;"></i>
                                    높이 (H) <small style="color: var(--linear-text-tertiary);">mm</small>
                                </label>
                                <input type="number" 
                                       id="carInsideHeight" 
                                       name="car_inside_height" 
                                       class="linear-input responsive-input"
                                       value="<?= htmlspecialchars($defaultHeight) ?>"
                                       min="2000" 
                                       max="3000" 
                                       step="5">
                            </div>
                        </div>
                    </div>

                    <!-- Panel JSON Data Storage -->
                    <input type="hidden" id="panelJsonData" name="panel_data" value="" />
                    <input type="hidden" id="transomJsonData" name="transom_data" value="" />

                    <!-- Project Type and Panel Layout Information -->
                    <div class="form-section">
                        <!-- Hidden inputs for project type and panel layout (controlled by buttons) -->
                        <?php
                        // 편집 모드일 때는 저장된 값을 사용, 새로 만들 때는 기본값 사용
                        error_log("DEBUG: edit_mode = " . ($edit_mode ? 'true' : 'false'));
                        error_log("DEBUG: edit_data is " . ($edit_data ? 'not null' : 'null'));
                        error_log("DEBUG: edit_data['project_type'] = " . (isset($edit_data['project_type']) ? "'" . $edit_data['project_type'] . "'" : 'NOT SET'));

                        $defaultProjectType = $edit_mode && !empty($edit_data['project_type'])
                            ? $edit_data['project_type']
                            : '신규';

                        // 새로운 체크박스 기반 시스템 - 모든 체크박스 해제 상태로 시작
                        $defaultPanelCornersExcluded = 0; // 기본값: 1,11번 포함 (체크 해제)
                        $defaultTransomExcluded = 0;      // 기본값: 트랜섬 포함 (체크 해제)

                        if ($edit_mode) {
                            // 새로운 컬럼 값 사용
                            if (isset($edit_data['panel_corners_excluded'])) {
                                $defaultPanelCornersExcluded = intval($edit_data['panel_corners_excluded']);
                            }

                            if (isset($edit_data['transom_excluded'])) {
                                $defaultTransomExcluded = intval($edit_data['transom_excluded']);
                            }
                        }

                        error_log("DEBUG: Final defaultProjectType = '" . $defaultProjectType . "'");
                        error_log("DEBUG: Final defaultPanelCornersExcluded = '" . $defaultPanelCornersExcluded . "'");
                        error_log("DEBUG: Final defaultTransomExcluded = '" . $defaultTransomExcluded . "'");
                        ?>
                        <input type="hidden" id="projectType" name="project_type" value="<?= htmlspecialchars($defaultProjectType) ?>">
                        <input type="hidden" id="panelCornersExcluded" name="panel_corners_excluded" value="<?= htmlspecialchars($defaultPanelCornersExcluded) ?>">
                    </div>

                    <!-- 🎯 PHASE 2: 재질 정보 (반응형) -->
                    <div class="responsive-section">
                        <h6 class="responsive-section-title">재질 정보</h6>
                        
                        <!-- Material Type and Thickness (반응형 그리드: 모바일 1열, PC 2열) -->
                        <div class="responsive-grid">
                            <div class="responsive-input-group">
                                <label for="materialType" class="linear-label">의장재질</label>
                                <?php
                                $defaultMaterialType = $edit_mode ? ($edit_data['material_type'] ?? '') : '';
                                $materialOptions = [
                                    '' => '재질을 선택하세요',
                                    'SUS H/L' => 'SUS H/L',
                                    'SUS MR' => 'SUS MR',
                                    '강판' => '강판',
                                    '도장품' => '도장품',
                                    '시트지' => '시트지',
                                    '기타' => '기타'
                                ];
                                ?>
                                <select class="linear-input responsive-input" id="materialType" name="material_type">
                                    <?php foreach($materialOptions as $value => $text): ?>
                                        <option value="<?= htmlspecialchars($value) ?>" <?= $defaultMaterialType === $value ? 'selected' : '' ?>><?= htmlspecialchars($text) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="responsive-input-group">
                                <label for="materialThickness" class="linear-label">두께 <span style="color: var(--linear-text-tertiary);">mm</span></label>
                                <?php
                                $defaultMaterialThickness = $edit_mode ? ($edit_data['material_thickness'] ?? '') : '';
                                $thicknessOptions = [
                                    '' => '두께 선택',
                                    '0.8' => '0.8',
                                    '1.0' => '1.0',
                                    '1.2' => '1.2',
                                    '1.5' => '1.5',
                                    '1.6' => '1.6'
                                ];
                                ?>
                                <select class="linear-input responsive-input" id="materialThickness" name="material_thickness">
                                    <?php foreach($thicknessOptions as $value => $text): ?>
                                        <option value="<?= htmlspecialchars($value) ?>" <?= $defaultMaterialThickness === $value ? 'selected' : '' ?>><?= htmlspecialchars($text) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Elevator Count -->
                        <div class="responsive-input-group">
                            <label for="elevatorCount" class="linear-label">엘리베이터 대수</label>
                            <?php
                            $defaultElevatorCount = $edit_mode ? ($edit_data['elevator_count'] ?? 1) : 1;
                            ?>
                            <input type="number" 
                                   class="linear-input responsive-input" 
                                   id="elevatorCount" 
                                   name="elevator_count"
                                   value="<?= htmlspecialchars($defaultElevatorCount) ?>"
                                   min="1" 
                                   max="20" 
                                   step="1"
                                   placeholder="1">                            
                        </div>

                        <!-- Notes (특이사항) -->
                        <div class="responsive-input-group">
                            <label for="notes" class="linear-label">특이사항</label>
                            <?php $defaultNotes = $edit_mode ? ($edit_data['notes'] ?? '') : ''; ?>
                            <textarea class="linear-input responsive-input" 
                                      id="notes" 
                                      name="notes" 
                                      rows="3" 
                                      placeholder="측정 시 특이사항이나 주의사항을 입력하세요"><?= htmlspecialchars($defaultNotes) ?></textarea>
                        </div>
                    </div>

                    <!-- Hidden Fields -->
                    <input type="hidden" id="selectedPanel" name="panel_number" value="">
                    <input type="hidden" name="measurer_id" value="<?= $_SESSION["userid"] ?>">

                    <!-- Action Buttons -->
                    <div style="display: flex; flex-direction: column; gap: var(--linear-spacing-md);">
                        <?php
                        require_once '../components/LinearButton.php';
                        
                        // Validate Button
                        echo LinearButton::outline('<i class="bi bi-check-circle"></i> 측정값 검증')
                            ->addAttribute('type', 'button')
                            ->addAttribute('id', 'validateBtn')
                            ->fullWidth();
                        
                        // Save Button
                        echo LinearButton::primary('<i class="bi bi-save"></i> 측정 저장')
                            ->addAttribute('type', 'submit')
                            ->addAttribute('id', 'saveBtn')
                            ->fullWidth();
                        
                        // Back Button
                        echo LinearButton::secondary('<i class="bi bi-arrow-left"></i> 돌아가기')
                            ->addAttribute('onclick', 'handlePageLeaveAttempt("button", "index.php"); return false;')
                            ->fullWidth();
                        ?>
                    </div>
                </form>
                
                <?php
                // 현장정보 카드 닫기
                echo '</div>'; // linear-card-body 닫기
                echo '</div>'; // linear-card 닫기
                ?>
            </div>

            <!-- Car Wall Visual (판넬 시각화) -->
            <div class="car-wall-section">
                <?php 
                require_once '../components/LinearCard.php';

                // Build header content with PHP variables
                $headerContent =
                    '<i class="bi bi-eye"></i>시각화
                     <div style="display: inline-flex; align-items: center; gap: 10px; margin-left: 10px;">
                        <div style="display: inline-flex; align-items: center; background: var(--linear-bg-secondary); border-radius: var(--linear-radius-md); padding: 2px;">
                            <button type="button" id="newBtn" class="linear-btn linear-btn-sm"
                                    style="font-size: 0.75rem; padding: 4px 8px; margin: 0; border-radius: var(--linear-radius-sm);">
                                신규
                            </button>
                            <button type="button" id="modBtn" class="linear-btn linear-btn-sm linear-btn-outline"
                                    style="font-size: 0.75rem; padding: 4px 8px; margin: 0; border-radius: var(--linear-radius-sm);">
                                MOD
                            </button>
                        </div>
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <label class="checkbox-container" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" id="excludePanelCorners" name="panel_corners_excluded" value="1"' .
                    ($defaultPanelCornersExcluded ? ' checked' : '') .
                    ' style="margin: 0; width: 16px; height: 16px;">
                                <span style="font-size: 0.9rem; user-select: none;">1,11번 제외</span>
                            </label>
                            <label class="checkbox-container" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" id="excludeTransom" name="transom_excluded" value="1"' .
                    ($defaultTransomExcluded ? ' checked' : '') .
                    ' style="margin: 0; width: 16px; height: 16px;">
                                <span style="font-size: 0.9rem; user-select: none;">트랜섬 제외</span>
                            </label>
                        </div>
                        <span id="panelCount" style="font-size: 0.9rem; color: var(--linear-text-secondary);">
                            (12매)
                        </span>
                     </div>';

                $carWallCard = LinearCard::withHeader($headerContent,
                    '
                    <div class="car-wall-container">
                        <div class="car-wall" id="carWall">
                            <!-- Panels 1-11 and Transom 12 -->
                            <div class="panel panel-1" data-panel="1" title="판넬 1">1</div>
                            <div class="panel panel-2" data-panel="2" title="판넬 2">2</div>
                            <div class="panel panel-3" data-panel="3" title="판넬 3">3</div>
                            <div class="panel panel-4" data-panel="4" title="판넬 4">4</div>
                            <div class="panel panel-5" data-panel="5" title="판넬 5">5</div>
                            <div class="panel panel-6" data-panel="6" title="판넬 6">6</div>
                            <div class="panel panel-7" data-panel="7" title="판넬 7">7</div>
                            <div class="panel panel-8" data-panel="8" title="판넬 8">8</div>
                            <div class="panel panel-9" data-panel="9" title="판넬 9">9</div>
                            <div class="panel panel-10" data-panel="10" title="판넬 10">10</div>
                            <div class="panel panel-11" data-panel="11" title="판넬 11">11</div>
                            <div class="panel panel-12 transom-panel" data-panel="12" title="Transom 12"></div>
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
        </div>
    </div>
    
    <!-- Mobile Device Detection - Critical Global Function v2.1 -->
    <script>
        // CACHE BUSTING: 즉시 실행 함수로 전역 함수 안전하게 정의
        (function() {
            'use strict';

            // updateJsonFields 안전 호출 헬퍼 - 최우선 정의 v2.0
            window.safeUpdateJsonFields = function(context = '') {
                console.log('🔧 safeUpdateJsonFields v2.0 호출:', context);
                const isMobile = window.innerWidth <= 768;

                // 모바일 전용 상세 디버깅
                if (isMobile && context.includes('saveAndLeave')) {
                    console.log(`=== 📱 모바일 updateJsonFields 실행 전 (${context}) ===`);
                    console.log('🔍 실행 전 window.panelData:', window.panelData);
                    console.log('🔍 실행 전 패널 개수:', window.panelData ? Object.keys(window.panelData).length : 0);
                    console.log('🔍 실행 전 JSON 필드:', document.getElementById('panelJsonData')?.value);
                }

                let success = false;
                if (typeof window.updateJsonFields === 'function') {
                    try {
                        window.updateJsonFields();
                        success = true;
                    } catch (error) {
                        console.error(`❌ updateJsonFields 오류 (${context}):`, error);
                    }
                } else if (typeof updateJsonFields === 'function') {
                    try {
                        updateJsonFields();
                        success = true;
                    } catch (error) {
                        console.error(`❌ updateJsonFields(local) 오류 (${context}):`, error);
                    }
                } else {
                    console.warn(`⚠️ updateJsonFields 함수 없음 (${context})`);
                }

                // 모바일 전용 실행 후 상태 확인
                if (isMobile && context.includes('saveAndLeave')) {
                    console.log(`=== 📱 모바일 updateJsonFields 실행 후 (${context}) ===`);
                    console.log('🔍 실행 후 window.panelData:', window.panelData);
                    console.log('🔍 실행 후 패널 개수:', window.panelData ? Object.keys(window.panelData).length : 0);
                    console.log('🔍 실행 후 JSON 필드:', document.getElementById('panelJsonData')?.value);

                    // JSON 필드가 비어있는 경우 경고
                    const jsonValue = document.getElementById('panelJsonData')?.value;
                    if (!jsonValue || jsonValue.trim() === '{}' || jsonValue.trim() === '') {
                        console.error('🚨 모바일: updateJsonFields 실행 후에도 JSON 필드가 비어있음!');
                    }
                }

                return success;
            };

            // 전역 모바일 감지 함수 정의 - 최우선 로딩
            if (typeof window.isMobileDevice === 'undefined') {
                window.isMobileDevice = function() {
                    const userAgent = navigator.userAgent || navigator.vendor || window.opera;
                    const mobileRegex = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i;
                    const touchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
                    const screenSize = window.innerWidth <= 768;

                    return mobileRegex.test(userAgent) || (touchDevice && screenSize);
                };
            }

            // 전역 스코프에서 직접 접근 가능하도록 함수 할당
            if (typeof window.isMobileDevice === 'function') {
                // 전역 함수로 노출 (브라우저 호환성 확보)
                window.isMobileDevice = window.isMobileDevice;

                // 모든 스크립트에서 접근 가능하도록 전역 변수 설정
                try {
                    isMobileDevice = window.isMobileDevice;
                } catch (e) {
                    // Strict mode에서 실패할 수 있으므로 window 객체에 할당
                    window.isMobileDevice = window.isMobileDevice;
                }
            }

            // 추가 안전장치: 함수가 정의되지 않은 경우 기본 구현
            if (typeof window.isMobileDevice !== 'function') {
                window.isMobileDevice = function() {
                    return window.innerWidth <= 768 || /Mobi|Android/i.test(navigator.userAgent);
                };
            }

            // 디버그: 함수 정의 확인 (개발 환경용)
            if (typeof console !== 'undefined' && console.log) {
                console.log('✅ isMobileDevice function loaded:', typeof window.isMobileDevice === 'function');
                console.log('✅ Global isMobileDevice available:', typeof isMobileDevice !== 'undefined');
                console.log('🔄 If errors persist, clear browser cache (Ctrl+F5)');
            }
        })();
    </script>

    <!-- Date Picker Implementation -->
    <script>

        document.addEventListener('DOMContentLoaded', function() {
            // Date Picker 구현
            const dateInput = document.getElementById('measurementDate');
            const datePicker = document.getElementById('datePicker');
            const monthYearElement = document.getElementById('monthYear');
            const calendarElement = document.getElementById('calendar');
            const prevMonthBtn = document.getElementById('prevMonth');
            const nextMonthBtn = document.getElementById('nextMonth');
            
            let currentDate = new Date();
            let selectedDate = new Date(dateInput.value || new Date());
            
            // 한국어 월 이름
            const monthNames = [
                '1월', '2월', '3월', '4월', '5월', '6월',
                '7월', '8월', '9월', '10월', '11월', '12월'
            ];
            
            // 날짜 입력 필드 클릭 시 달력 표시/숨김
            dateInput.addEventListener('click', function() {
                const isVisible = datePicker.style.display === 'block';
                datePicker.style.display = isVisible ? 'none' : 'block';
                if (!isVisible) {
                    renderCalendar();
                }
            });
            
            // 외부 클릭 시 달력 숨김
            document.addEventListener('click', function(e) {
                if (!dateInput.contains(e.target) && !datePicker.contains(e.target)) {
                    datePicker.style.display = 'none';
                }
            });
            
            // 이전 달 버튼
            prevMonthBtn.addEventListener('click', function() {
                currentDate.setMonth(currentDate.getMonth() - 1);
                renderCalendar();
            });
            
            // 다음 달 버튼
            nextMonthBtn.addEventListener('click', function() {
                currentDate.setMonth(currentDate.getMonth() + 1);
                renderCalendar();
            });
            
            // 달력 렌더링
            function renderCalendar() {
                const year = currentDate.getFullYear();
                const month = currentDate.getMonth();
                
                // 월/년 표시
                monthYearElement.textContent = `${year}년 ${monthNames[month]}`;
                
                // 달력 그리드 초기화 (요일 헤더 제외)
                const dayHeaders = calendarElement.querySelectorAll('.date-picker-day-header');
                calendarElement.innerHTML = '';
                dayHeaders.forEach(header => calendarElement.appendChild(header));
                
                // 이번 달 첫째 날과 마지막 날
                const firstDay = new Date(year, month, 1);
                const lastDay = new Date(year, month + 1, 0);
                const daysInMonth = lastDay.getDate();
                const startingDayOfWeek = firstDay.getDay();
                
                // 이전 달 마지막 날들
                const prevLastDay = new Date(year, month, 0).getDate();
                for (let i = startingDayOfWeek - 1; i >= 0; i--) {
                    const dayElement = createDayElement(prevLastDay - i, true);
                    calendarElement.appendChild(dayElement);
                }
                
                // 이번 달 날들
                for (let day = 1; day <= daysInMonth; day++) {
                    const dayElement = createDayElement(day, false);
                    calendarElement.appendChild(dayElement);
                }
                
                // 다음 달 첫 날들 (42개 셀 채우기 위해)
                const totalCells = 42; // 6주 * 7일
                const currentCells = calendarElement.children.length - 7; // 요일 헤더 제외
                const remainingCells = totalCells - currentCells;
                for (let day = 1; day <= remainingCells; day++) {
                    const dayElement = createDayElement(day, true);
                    calendarElement.appendChild(dayElement);
                }
            }
            
            // 날짜 요소 생성
            function createDayElement(day, isOtherMonth) {
                const dayElement = document.createElement('div');
                dayElement.className = 'date-picker-day';
                dayElement.textContent = day;
                
                if (isOtherMonth) {
                    dayElement.classList.add('other-month');
                }
                
                // 오늘 날짜 표시
                const today = new Date();
                const currentYear = currentDate.getFullYear();
                const currentMonth = currentDate.getMonth();
                
                if (!isOtherMonth && 
                    currentYear === today.getFullYear() && 
                    currentMonth === today.getMonth() && 
                    day === today.getDate()) {
                    dayElement.classList.add('today');
                }
                
                // 선택된 날짜 표시
                if (!isOtherMonth &&
                    currentYear === selectedDate.getFullYear() &&
                    currentMonth === selectedDate.getMonth() &&
                    day === selectedDate.getDate()) {
                    dayElement.classList.add('selected');
                }
                
                // 클릭 이벤트
                dayElement.addEventListener('click', function() {
                    if (!isOtherMonth) {
                        selectedDate = new Date(currentYear, currentMonth, day);
                        
                        // 시간대 문제 해결을 위해 로컬 날짜 문자열 생성
                        const year = selectedDate.getFullYear();
                        const month = String(selectedDate.getMonth() + 1).padStart(2, '0');
                        const dayStr = String(selectedDate.getDate()).padStart(2, '0');
                        const formattedDate = `${year}-${month}-${dayStr}`;
                        
                        dateInput.value = formattedDate;
                        datePicker.style.display = 'none';
                        
                        // 모바일 카드의 날짜 입력도 동기화
                        const mobileDateInput = document.querySelector('#mobile-site-info input[name="measurement_date"]');
                        if (mobileDateInput) {
                            mobileDateInput.value = formattedDate;
                        }
                        
                        // 달력 다시 렌더링하여 선택된 날짜 표시 업데이트
                        renderCalendar();
                    }
                });
                
                return dayElement;
            }
            
            // 초기 달력 렌더링
            renderCalendar();
        });

        // 전역 디바운싱 변수
        let iparkCheckboxLastExecution = 0;

        // 아이파크 신규 체크박스 기능 초기화 (강화된 버전)
        function initializeIparkCheckbox() {
            console.log('🚀 initializeIparkCheckbox 함수 시작');
            
            // 신규 모드 확인을 위한 URL 파라미터 체크
            const urlParams = new URLSearchParams(window.location.search);
            const isEditMode = !!urlParams.get('edit');
            console.log('🔍 현재 모드:', isEditMode ? '편집 모드' : '신규 모드');

            // 여러 방법으로 요소 찾기
            let iparkCheck = document.getElementById('iparkCheck');
            let siteNameInput = document.getElementById('siteName');

            // 대체 방법으로 요소 찾기
            if (!iparkCheck) {
                iparkCheck = document.querySelector('input[name="ipark_check"]');
                console.log('📱 대체 방법으로 iparkCheck 찾기:', iparkCheck ? '✅ 발견' : '❌ 없음');
            }

            if (!siteNameInput) {
                siteNameInput = document.querySelector('input[name="site_name"]');
                console.log('📱 대체 방법으로 siteNameInput 찾기:', siteNameInput ? '✅ 발견' : '❌ 없음');
            }

            console.log('📱 최종 요소 확인:');
            console.log('  - iparkCheck:', iparkCheck ? `✅ ID: ${iparkCheck.id}, Type: ${iparkCheck.type}` : '❌ 없음');
            console.log('  - siteNameInput:', siteNameInput ? `✅ ID: ${siteNameInput.id}, Name: ${siteNameInput.name}` : '❌ 없음');

            if (iparkCheck && siteNameInput) {
                console.log('📱 DEBUG F4: 아이파크 체크박스 초기화 시작');
                
                // 신규 모드에서는 초기 로드 시에만 체크박스를 해제 상태로 설정 (재시도 시에는 상태 유지)
                if (!isEditMode && !window.iparkCheckboxInitialized) {
                    iparkCheck.checked = false;
                    console.log('🆕 신규 모드: 체크박스 초기 해제 완료');
                    window.iparkCheckboxInitialized = true; // 초기화 완료 플래그 설정
                } else if (!isEditMode && window.iparkCheckboxInitialized) {
                    console.log('🔄 재시도: 체크박스 상태 유지 (현재 상태:', iparkCheck.checked, ')');
                }
                
                // PC 환경에서 체크박스 속성 확인 및 강제 활성화
                console.log('🖥️ PC 체크박스 속성 확인:');
                console.log('  - disabled:', iparkCheck.disabled);
                console.log('  - readonly:', iparkCheck.readOnly);
                console.log('  - style.display:', iparkCheck.style.display);
                console.log('  - style.visibility:', iparkCheck.style.visibility);
                console.log('  - pointer-events:', window.getComputedStyle(iparkCheck).pointerEvents);
                
                // 체크박스가 비활성화되어 있다면 강제로 활성화
                if (iparkCheck.disabled) {
                    iparkCheck.disabled = false;
                    console.log('🖥️ PC 체크박스 강제 활성화 완료');
                }
                
                // 포인터 이벤트가 비활성화되어 있다면 활성화
                const computedStyle = window.getComputedStyle(iparkCheck);
                if (computedStyle.pointerEvents === 'none') {
                    iparkCheck.style.pointerEvents = 'auto';
                    console.log('🖥️ PC 체크박스 포인터 이벤트 활성화 완료');
                }
                
                // PC에서 아이파크 체크박스 컨테이너 강제 표시
                const iparkCheckContainer = document.getElementById('iparkCheckContainer');
                if (iparkCheckContainer) {
                    iparkCheckContainer.style.display = 'block';
                    iparkCheckContainer.style.visibility = 'visible';
                    iparkCheckContainer.style.opacity = '1';
                    console.log('🖥️ PC 아이파크 체크박스 컨테이너 강제 표시 완료');
                    
                    // 라벨과 텍스트 요소들도 강제 표시
                    const iparkLabel = document.getElementById('iparkCheckLabel');
                    if (iparkLabel) {
                        iparkLabel.style.display = 'flex';
                        iparkLabel.style.visibility = 'visible';
                        iparkLabel.style.opacity = '1';
                        iparkLabel.style.color = '#333';
                        console.log('🖥️ PC 아이파크 라벨 강제 표시 완료');
                        
                        // span 요소들도 강제 표시
                        const spans = iparkLabel.querySelectorAll('span');
                        spans.forEach(span => {
                            span.style.display = 'inline';
                            span.style.visibility = 'visible';
                            span.style.opacity = '1';
                            span.style.color = '#333';
                            span.style.fontWeight = '500';
                            span.style.fontSize = '14px';
                        });
                        
                        // 아이콘도 강제 표시
                        const icons = iparkLabel.querySelectorAll('i');
                        icons.forEach(icon => {
                            icon.style.display = 'inline';
                            icon.style.visibility = 'visible';
                            icon.style.opacity = '1';
                            icon.style.color = '#007bff';
                            icon.style.marginRight = '6px';
                        });
                        
                        console.log('🖥️ PC 아이파크 텍스트 및 아이콘 강제 표시 완료');
                    }
                }
                // 체크박스 변경 이벤트 (모바일 호환성을 위해 여러 이벤트 등록)
                const handleIparkCheckboxChange = function() {
                    // 강화된 디바운싱: 300ms 이내 중복 실행 방지
                    const now = Date.now();
                    if (now - iparkCheckboxLastExecution < 300) {
                        console.log('🚫 디바운싱: 중복 실행 방지 (300ms)', now - iparkCheckboxLastExecution + 'ms 경과');
                        return;
                    }
                    iparkCheckboxLastExecution = now;
                    
                    // 동기화 중인 경우 중복 실행 방지
                    if (this.hasAttribute('data-sync-in-progress')) {
                        console.log('🔄 동기화 중: 중복 실행 방지');
                        return;
                    }
                    
                    console.log('🔄 handleIparkCheckboxChange 함수 실행, 체크 상태:', this.checked);
                    console.log('🔍 CALLBACK STEP 1: 함수 컨텍스트 - this:', this.id || 'no-id');
                    console.log('🔍 CALLBACK STEP 2: 체크박스 타입:', this.type);
                    console.log('🔍 DEBUG: 체크박스 실제 DOM 상태:', {
                        checked: this.checked,
                        value: this.value,
                        id: this.id,
                        name: this.name
                    }); 

                    // 📱 DEBUG: 모바일 환경 확인
                    console.log('📱 DEBUG A1: isMobileDevice 함수 존재:', typeof isMobileDevice);
                    if (typeof isMobileDevice === 'function') {
                        console.log('📱 DEBUG A2: 모바일 환경 여부:', isMobileDevice());
                    }

                    // 📱 DEBUG: 현재 실행 환경 상세 정보
                    console.log('📱 DEBUG A3: User Agent:', navigator.userAgent);
                    console.log('📱 DEBUG A4: Touch 지원:', 'ontouchstart' in window);
                    console.log('📱 DEBUG A5: 화면 크기:', window.innerWidth + 'x' + window.innerHeight);

                    if (this.checked) {
                        console.log('✅ CALLBACK STEP 3: 체크박스가 체크된 상태 - 모달 표시 프로세스 시작');

                        // 체크된 경우: '임시현장'을 '아이파크'로 변경
                        const currentValue = siteNameInput.value;
                        console.log('🔍 CALLBACK STEP 4: 현재 현장명:', currentValue);

                        if (currentValue.includes('임시현장')) {
                            siteNameInput.value = currentValue.replace('임시현장', '아이파크');
                            console.log('🔍 CALLBACK STEP 5: 현장명 변경 완료:', siteNameInput.value);
                        }

                        // 1,11번 제외와 트랜섬 제외 체크박스 자동 체크
                        const excludePanelCornersCheckbox = document.getElementById('excludePanelCorners');
                        const excludeTransomCheckbox = document.getElementById('excludeTransom');

                        if (excludePanelCornersCheckbox && !excludePanelCornersCheckbox.checked) {
                            excludePanelCornersCheckbox.checked = true;
                            // 체크박스 change 이벤트 발생시켜서 화면 업데이트
                            excludePanelCornersCheckbox.dispatchEvent(new Event('change'));
                            console.log('🔍 CALLBACK STEP 6: 패널 모서리 제외 체크박스 활성화');
                        }

                        if (excludeTransomCheckbox && !excludeTransomCheckbox.checked) {
                            excludeTransomCheckbox.checked = true;
                            // 체크박스 change 이벤트 발생시켜서 화면 업데이트
                            excludeTransomCheckbox.dispatchEvent(new Event('change'));
                            console.log('🔍 CALLBACK STEP 7: 트랜섬 제외 체크박스 활성화');
                        }

                        // 숨겨진 div 표시 - PC/모바일 모두 div 토글 방식 사용
                        console.log('🚀 CALLBACK STEP 8: 아이파크 설정 화면 표시 시작');
                        
                        // 이미 div가 표시된 상태인지 확인
                        const settingsDiv = document.getElementById('iparkSettingsDiv');
                        if (settingsDiv && settingsDiv.style.display === 'block') {
                            console.log('🚫 이미 div가 표시된 상태 - 중복 표시 방지');
                            return;
                        }
                        
                        try {
                            console.log('🖥️ 모든 환경에서 showIparkSettingsDiv 호출 (숨겨진 div 표시)');
                            console.log('🔍 showIparkSettingsDiv 함수 존재:', typeof showIparkSettingsDiv);
                            showIparkSettingsDiv();
                            console.log('✅ 아이파크 설정 화면 표시 성공 (모달창 대신 div)');
                        } catch (error) {
                            console.error('❌ 아이파크 설정 화면 표시 실패:', error);
                            console.error('📱 에러 스택:', error.stack);
                        }
                    } else {
                        // 체크 해제된 경우: '아이파크'를 '임시현장'으로 변경
                        const currentValue = siteNameInput.value;
                        if (currentValue.includes('아이파크')) {
                            siteNameInput.value = currentValue.replace('아이파크', '임시현장');
                        }

                        // 아이파크 자동계산 값 초기화
                        clearIparkAutoMeasurements();
                        
                        // 설정 div 숨기기 (모든 환경 공통)
                        console.log('📱 체크 해제: 설정 div 숨기기 시도 (모달창 대신)');
                        hideIparkSettingsDiv();
                    }
                };

                // 모바일/PC 공통: change 이벤트 등록
                iparkCheck.addEventListener('change', handleIparkCheckboxChange);
                console.log('✅ 체크박스 change 이벤트 리스너 등록 완료');
                
                // 체크박스 클릭 이벤트도 추가 (상태 변경 확인용)
                iparkCheck.addEventListener('click', function(e) {
                    console.log('🖱️ 체크박스 직접 클릭 감지:', {
                        checked: this.checked,
                        target: e.target.id,
                        timestamp: new Date().toISOString()
                    });
                });
                
                // PC 환경에서 클릭 이벤트도 등록 (체크박스 클릭 감지용)
                // change 이벤트가 이미 있으므로 중복 실행 방지를 위해 클릭 이벤트는 제거
                
                // PC 환경에서 레이블 클릭 이벤트는 제거 (change 이벤트만 사용)
                console.log('🚫 PC 레이블 클릭 이벤트 제거 - change 이벤트만 사용');

                // 모바일에서 터치 이벤트도 추가 (시각화 패널과 동일한 방식)
                if (typeof isMobileDevice === 'function' && isMobileDevice()) {
                    console.log('🔧 모바일 환경 감지: 아이파크 체크박스 터치 이벤트 등록');

                    // 단계별 디버깅: 요소 존재 확인
                    console.log('🔍 STEP 1: 체크박스 요소 존재 확인:', iparkCheck ? '✅ 발견' : '❌ 없음');

                    if (iparkCheck) {
                        console.log('🔍 STEP 2: 체크박스 ID 확인:', iparkCheck.id);
                        console.log('🔍 STEP 3: 체크박스 타입 확인:', iparkCheck.type);
                        console.log('🔍 STEP 4: 체크박스 현재 상태:', iparkCheck.checked);

                        // 📱 DEBUG: 체크박스 요소 상세 정보
                        console.log('📱 DEBUG C1: 체크박스 disabled 상태:', iparkCheck.disabled);
                        console.log('📱 DEBUG C2: 체크박스 부모 요소:', iparkCheck.parentElement ? iparkCheck.parentElement.tagName : 'None');
                        console.log('📱 DEBUG C3: 체크박스 이벤트 리스너 수:', Object.keys(iparkCheck).filter(k => k.startsWith('on')).length);

                        const checkboxRect = iparkCheck.getBoundingClientRect();
                        console.log('📱 DEBUG C4: 체크박스 위치/크기:', {
                            x: checkboxRect.x,
                            y: checkboxRect.y,
                            width: checkboxRect.width,
                            height: checkboxRect.height,
                            visible: checkboxRect.width > 0 && checkboxRect.height > 0
                        });

                        // 모든 터치 이벤트 리스너 추가 (포괄적 접근)
                        ['touchstart', 'touchend', 'touchmove', 'click'].forEach(eventType => {
                            iparkCheck.addEventListener(eventType, function(e) {
                                console.log(`🚀 TOUCH EVENT: ${eventType} 발생 - 타겟:`, e.target.id || 'no-id');

                                // 📱 DEBUG: 터치 이벤트 상세 정보
                                console.log('📱 DEBUG D1: 이벤트 타입:', e.type);
                                console.log('📱 DEBUG D2: 이벤트 대상:', e.target.tagName);
                                console.log('📱 DEBUG D3: 현재 체크 상태:', this.checked);
                                console.log('📱 DEBUG D4: 이벤트 포인터:', e.pointerType || 'unknown');

                                if (eventType === 'touchend') {
                                    console.log('📱 아이파크 체크박스 touchend 이벤트 발생');

                                    // 📱 DEBUG: touchend 이벤트 상세 디버깅
                                    console.log('📱 DEBUG D5: preventDefault 실행 전');
                                    e.preventDefault();
                                    console.log('📱 DEBUG D6: stopPropagation 실행 전');
                                    e.stopPropagation();
                                    console.log('📱 DEBUG D7: 이벤트 차단 완료');

                                    // 📱 DEBUG: click() 호출 전 상태 확인
                                    console.log('📱 DEBUG D8: click() 호출 전 체크 상태:', this.checked);

                                    // 브라우저 기본 토글 대신 명시적으로 클릭 트리거 → change 핸들러 단일 경로
                                    this.click();

                                    console.log('📱 DEBUG D9: click() 호출 후 체크 상태:', this.checked);
                                    // change 이벤트가 자동으로 발생하므로 중복 호출 제거
                                    return;
                                }
                            }, { passive: false });
                        });
                    } 

                    // 레이블 터치 이벤트도 추가 (preventDefault 추가)
                    const iparkLabel = document.getElementById('iparkCheckLabel');
                    console.log('🔍 STEP 6: 레이블 요소 존재 확인:', iparkLabel ? '✅ 발견' : '❌ 없음');

                    if (iparkLabel) {
                        console.log('🔍 STEP 7: 레이블 ID 확인:', iparkLabel.id);
                        console.log('🔍 STEP 8: 레이블 텍스트 확인:', iparkLabel.textContent || iparkLabel.innerText);

                        ['touchstart', 'touchend', 'touchmove', 'click'].forEach(eventType => {
                            iparkLabel.addEventListener(eventType, function(e) {
                                console.log(`🚀 LABEL EVENT: ${eventType} 발생 - 타겟:`, e.target.id || 'no-id');
                                if (eventType === 'touchend') {
                                    console.log('📱 아이파크 레이블 touchend 이벤트 발생');
                                    e.preventDefault();
                                    e.stopPropagation();

                                    // 레이블 터치 시 체크박스 클릭만 트리거하여 change 경로로 통일
                                    iparkCheck.click();
                                    // change 이벤트가 자동으로 발생하므로 중복 호출 제거
                                    return;
                                }
                            }, { passive: false });
                        });
                    }

                    // 투명 오버레이 터치 이벤트 추가 (가장 확실한 방법)
                    const iparkOverlay = document.getElementById('iparkCheckOverlay');
                    console.log('🔍 STEP 12: 오버레이 요소 존재 확인:', iparkOverlay ? '✅ 발견' : '❌ 없음');

                    if (iparkOverlay) {
                        console.log('🔍 STEP 13: 오버레이에 터치 이벤트 등록');

                        // 오버레이 위치 정보 확인
                        const overlayRect = iparkOverlay.getBoundingClientRect();
                        console.log('🔍 STEP 14: 오버레이 위치 정보:', {
                            x: overlayRect.x,
                            y: overlayRect.y,
                            width: overlayRect.width,
                            height: overlayRect.height,
                            visible: overlayRect.width > 0 && overlayRect.height > 0
                        });

                        // 모든 터치 이벤트 등록
                        ['touchstart', 'touchend', 'touchmove', 'click'].forEach(eventType => {
                            iparkOverlay.addEventListener(eventType, function(e) {
                                console.log(`🌟 OVERLAY EVENT: ${eventType} 발생 - 타겟:`, e.target.id || 'no-id');
                                if (eventType === 'touchend' || eventType === 'click') {
                                    console.log('💥 오버레이 터치/클릭 이벤트 발생');
                                    e.preventDefault();
                                    e.stopPropagation();

                                    // 체크박스 클릭만 트리거하여 change 경로로 통일
                                    iparkCheck.click();
                                    // change 이벤트가 자동으로 발생하므로 중복 호출 제거
                                    return;
                                }
                            }, { passive: false });
                        });
                    }

                    // 추가 디버깅: 체크박스 영역의 스타일 정보
                    if (iparkCheck) {
                        const rect = iparkCheck.getBoundingClientRect();
                        console.log('🔍 STEP 10: 체크박스 위치 정보:', {
                            x: rect.x,
                            y: rect.y,
                            width: rect.width,
                            height: rect.height,
                            visible: rect.width > 0 && rect.height > 0
                        });
                    }

                    // 추가 디버깅: 터치 가능 영역 확인
                    console.log('🔍 STEP 11: 터치 가능 영역 스타일 확인');
                    if (iparkCheck) {
                        const styles = window.getComputedStyle(iparkCheck);
                        console.log('🔍 체크박스 pointer-events:', styles.pointerEvents);
                        console.log('🔍 체크박스 display:', styles.display);
                        console.log('🔍 체크박스 visibility:', styles.visibility);
                        console.log('🔍 체크박스 z-index:', styles.zIndex);
                        console.log('🔍 체크박스 position:', styles.position);
                    }

                    // 레이블 스타일도 확인
                    if (iparkLabel) {
                        const labelStyles = window.getComputedStyle(iparkLabel);
                        console.log('🔍 레이블 display:', labelStyles.display);
                        console.log('🔍 레이블 visibility:', labelStyles.visibility);
                        console.log('🔍 레이블 z-index:', labelStyles.zIndex);
                        console.log('🔍 레이블 position:', labelStyles.position);

                        const labelRect = iparkLabel.getBoundingClientRect();
                        console.log('🔍 레이블 위치 정보:', {
                            x: labelRect.x,
                            y: labelRect.y,
                            width: labelRect.width,
                            height: labelRect.height,
                            visible: labelRect.width > 0 && labelRect.height > 0
                        });
                    }

                // 모바일 전용 강제 터치 테스트 제거 (실동작 지연 및 중복 이벤트 유발)
                }
 
                // 디버깅용 테스트 버튼 (모바일에서만 표시)
                const debugBtn = document.getElementById('debugIparkBtn');
                if (debugBtn && typeof isMobileDevice === 'function' && isMobileDevice()) {
                    debugBtn.style.display = 'inline-block';
                    debugBtn.addEventListener('click', function() {
                        console.log('🔧 디버그 버튼 클릭 - showIparkSettingsDiv 호출 (숨겨진 div 표시)');
                        if (typeof showIparkSettingsDiv === 'function') {
                            showIparkSettingsDiv();
                        } else {
                            console.error('❌ showIparkSettingsDiv 함수가 정의되지 않음');
                        }
                    });
                }

                // 현재 체크박스 상태 로그
                console.log('📋 현재 체크박스 상태:', iparkCheck.checked);
                
                // 초기 로드시 체크 상태에 따른 현장명 설정
                if (iparkCheck.checked) {
                    const currentValue = siteNameInput.value;
                    if (currentValue.includes('임시현장')) {
                        siteNameInput.value = currentValue.replace('임시현장', '아이파크');
                    }

                    // 수정 모드에서 기존 아이파크 설정값 적용
                    setTimeout(() => {
                        // 먼저 로드된 패널 데이터에서 아이파크 자동계산 값 찾기
                        let panel39Width = null;
                        let panel6Width = null; 

                        if (window.panelData) {
                            // 3번 또는 9번 패널에서 너비값 찾기
                            if (window.panelData[3] && window.panelData[3].isIparkAuto) {
                                panel39Width = window.panelData[3].width;
                            } else if (window.panelData[9] && window.panelData[9].isIparkAuto) {
                                panel39Width = window.panelData[9].width;
                            }

                            // 6번 패널에서 너비값 찾기
                            if (window.panelData[6] && window.panelData[6].isIparkAuto) {
                                panel6Width = window.panelData[6].width;
                            }
                        }

                        // 패널 데이터에서 찾지 못했으면 localStorage에서 시도
                        if (!panel39Width || !panel6Width) {
                            const savedPanel39Width = localStorage.getItem('iparkPanel39Width');
                            const savedPanel6Width = localStorage.getItem('iparkPanel6Width');

                            panel39Width = panel39Width || parseInt(savedPanel39Width);
                            panel6Width = panel6Width || parseInt(savedPanel6Width);
                        }

                        if (panel39Width && panel6Width) {
                            applyIparkAutoMeasurements(panel39Width, panel6Width);
                        }
                    }, 1000); // DOM이 완전히 로드된 후 실행
                }
            } else {
                console.log('❌ 아이파크 체크박스 또는 현장명 입력 필드를 찾을 수 없습니다');
                console.log('📱 DEBUG F5: iparkCheck 요소:', iparkCheck);
                console.log('📱 DEBUG F6: siteNameInput 요소:', siteNameInput);

                // 📱 DEBUG: 대체 검색 시도
                console.log('📱 DEBUG F7: 대체 체크박스 검색 (name):', document.querySelector('input[name="ipark_check"]'));
                console.log('📱 DEBUG F8: 대체 현장명 검색 (name):', document.querySelector('input[name="site_name"]'));
                console.log('📱 DEBUG F9: 전체 input 요소 수:', document.querySelectorAll('input').length);
                
                // 재시도 로직
                console.log('🔄 3초 후 재시도 예약');
                setTimeout(() => {
                    console.log('🔄 재시도 시작');
                    initializeIparkCheckbox();
                }, 3000);
            }
        }

        // PC에서는 change 이벤트만 사용하고, 이벤트 위임은 제거 (중복 실행 방지)
        console.log('🚫 PC 이벤트 위임 제거 - change 이벤트만 사용');

        // 터치 이벤트 위임 (모바일에서만 동작)
        if (typeof isMobileDevice === 'function' && isMobileDevice()) {
            document.addEventListener('touchend', function(e) {
                if (e.target && (e.target.id === 'iparkCheck' || e.target.closest('#iparkCheckContainer'))) {
                    console.log('🎯 이벤트 위임으로 아이파크 터치 감지');
                    console.log('📱 터치된 요소:', e.target.tagName, e.target.id);
                    
                    const iparkCheck = document.getElementById('iparkCheck');
                    if (iparkCheck) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        // 체크박스 토글
                        const wasChecked = iparkCheck.checked;
                        iparkCheck.checked = !wasChecked;
                        console.log('📱 터치로 체크박스 상태 변경:', wasChecked, '→', iparkCheck.checked);
                        
                        // change 이벤트 발생
                        const changeEvent = new Event('change', { bubbles: true });
                        iparkCheck.dispatchEvent(changeEvent);
                        console.log('📱 터치에서 change 이벤트 발생');
                        
                        // 체크 해제 시 설정 화면 숨기기
                        if (!iparkCheck.checked) {
                            console.log('📱 터치로 체크 해제 감지: 설정 화면 숨기기 (모달창 대신)');
                            hideIparkSettingsDiv();
                        }
                    }
                }
            }, { passive: false });
        } else {
            console.log('🚫 PC 환경: 터치 이벤트 위임 제거');
        }

        // PC 버전 요소들을 모바일에서 숨기기
        function hidePCVersionElements() {
            console.log('📱 PC 버전 요소들 숨기기 시작');
            
            // PC 버전 요소들 선택 (아이파크 체크박스는 제외)
            const pcElements = [
                '.measurement-grid',
                '#measurementForm'
            ];
            
            pcElements.forEach(selector => {
                const elements = document.querySelectorAll(selector);
                elements.forEach(element => {
                    if (element && !element.closest('.mobile-only-cards')) {
                        element.style.display = 'none';
                        console.log('📱 PC 요소 숨김:', selector);
                    }
                });
            });
            
            // PC 버전 아이파크 체크박스는 모바일에서 숨기기 (CSS로 이미 처리됨)
            const iparkCheckContainer = document.getElementById('iparkCheckContainer');
            if (iparkCheckContainer && !iparkCheckContainer.closest('.mobile-only-cards')) {
                iparkCheckContainer.style.display = 'none';
                console.log('📱 PC 아이파크 체크박스 숨김');
            }
            
            // 모바일 아이파크 체크박스는 표시
            const mobileIparkCheckContainer = document.getElementById('mobileIparkCheckContainer');
            if (mobileIparkCheckContainer) {
                mobileIparkCheckContainer.style.display = 'block';
                console.log('📱 모바일 아이파크 체크박스 표시');
            }
            
            // 모바일 카드 표시
            const mobileCardsContainer = document.querySelector('.mobile-only-cards');
            if (mobileCardsContainer) {
                mobileCardsContainer.style.display = 'block';
                console.log('📱 모바일 카드 표시');
            }
            
            console.log('📱 PC 버전 요소들 숨기기 완료');
        }
        
        // PC 환경에서 요소들 표시하기
        function showPCVersionElements() {
            console.log('🖥️ PC 버전 요소들 표시 시작');
            
            // PC 버전 요소들 선택
            const pcElements = [
                '.measurement-grid',
                '#measurementForm',
                '#iparkCheckContainer'
            ];
            
            pcElements.forEach(selector => {
                const elements = document.querySelectorAll(selector);
                elements.forEach(element => {
                    if (element && !element.closest('.mobile-only-cards')) {
                        element.style.display = '';
                        console.log('🖥️ PC 요소 표시:', selector);
                    }
                });
            });
            
            // 모바일 카드 숨기기
            const mobileCardsContainer = document.querySelector('.mobile-only-cards');
            if (mobileCardsContainer) {
                mobileCardsContainer.style.display = 'none';
                console.log('🖥️ 모바일 카드 숨김');
            }
            
            // 모바일 아이파크 체크박스도 숨기기
            const mobileIparkCheckContainer = document.getElementById('mobileIparkCheckContainer');
            if (mobileIparkCheckContainer) {
                mobileIparkCheckContainer.style.display = 'none';
                console.log('🖥️ 모바일 아이파크 체크박스 숨김');
            }
            
            console.log('🖥️ PC 버전 요소들 표시 완료');
        }

        // DOMContentLoaded 이벤트 리스너 등록
        document.addEventListener('DOMContentLoaded', function() {
            console.log('📱 DEBUG F1: DOMContentLoaded 이벤트 발생');
            
            // 환경에 따라 요소들 표시/숨기기
            if (window.innerWidth <= 768) {
                setTimeout(() => {
                    console.log('📱 DOMContentLoaded 후 PC 버전 요소들 숨기기');
                    hidePCVersionElements();
                }, 100);
            } else {
                setTimeout(() => {
                    console.log('🖥️ DOMContentLoaded 후 PC 버전 요소들 표시');
                    showPCVersionElements();
                }, 100);
            }
            
            // 즉시 시도
            initializeIparkCheckbox();
            
            // 추가 재시도 (DOM이 완전히 로드되지 않은 경우를 대비)
            setTimeout(() => {
                console.log('🔄 1초 후 추가 재시도');
                initializeIparkCheckbox();
                
                // 환경에 따라 요소들 표시/숨기기
                if (window.innerWidth <= 768) {
                    hidePCVersionElements();
                } else {
                    showPCVersionElements();
                }
            }, 1000);
            
            setTimeout(() => {
                console.log('🔄 2초 후 최종 재시도');
                initializeIparkCheckbox();
                
                // 환경에 따라 요소들 표시/숨기기
                if (window.innerWidth <= 768) {
                    hidePCVersionElements();
                } else {
                    showPCVersionElements();
                }
            }, 2000);
        });

        // 아이파크 판넬폭 설정 모달창
        function showIparkPanelModal() {
            console.log('🚀 showIparkPanelModal 함수 진입 - 모달 열기 시작');

            // EMERGENCY: 함수 내부에서 직접 모바일 감지 함수 정의 (캐시 문제 해결)
            if (typeof window.isMobileDevice === 'undefined') {
                window.isMobileDevice = function() {
                    return window.innerWidth <= 768 || /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
                };
            }

            console.log('🔍 MODAL STEP 1: 모바일 감지 함수 정의 완료');
            console.log('🔍 MODAL STEP 2: 현재 환경 - 모바일:', typeof window.isMobileDevice === 'function' ? window.isMobileDevice() : 'undefined');

            // 모바일 환경에서 SweetAlert2 작동 여부 확인
            if (typeof Swal === 'undefined') {
                console.error('❌ MODAL ERROR: SweetAlert2가 로드되지 않았습니다');
                alert('모달 라이브러리가 로드되지 않았습니다. 페이지를 새로고침해주세요.');
                return;
            }

            Swal.fire({
                title: '<i class="bi bi-building"></i> 아이파크 신규 설정',
                html: `
                    <div style="text-align: left; margin: 20px 0;">
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">
                                <i class="bi bi-arrows-expand"></i> 3번, 9번 판넬폭 (mm)
                            </label>
                            <input type="number" id="iparkPanel39Width"
                                   style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px;"
                                   placeholder="3번, 9번 판넬의 폭을 입력하세요" min="1" max="3000" step="1">
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">
                                <i class="bi bi-arrows-expand"></i> 6번 판넬폭 (mm)
                            </label>
                            <input type="number" id="iparkPanel6Width"
                                   style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px;"
                                   placeholder="6번 판넬의 폭을 입력하세요" min="1" max="3000" step="1">
                        </div>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; font-size: 14px; color: #666;">
                            <i class="bi bi-info-circle"></i>
                            <strong>참고:</strong> 아이파크 신규 프로젝트의 특수 판넬폭을 설정합니다.
                            이 값들은 해당 판넬 측정시 기본값으로 사용됩니다.
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: '<i class="bi bi-check-lg"></i> 설정 완료',
                cancelButtonText: '<i class="bi bi-x-lg"></i> 취소',
                confirmButtonColor: '#007bff',
                cancelButtonColor: '#6c757d',
                width: (window.innerWidth <= 768) ? '95%' : '500px',
                padding: (window.innerWidth <= 768) ? '1rem' : '2rem',
                customClass: {
                    popup: 'ipark-modal',
                    title: 'ipark-modal-title',
                    htmlContainer: 'ipark-modal-content'
                },
                didOpen: () => {
                    console.log('🎉 SweetAlert2 아이파크 모달 열림');

                    // 모바일 모달 핸들러 등록 (개선된 방식)
                    if (window.mobileModalHandler) {
                        console.log('📱 모바일 모달 핸들러에 SweetAlert2 등록');
                        window.mobileModalHandler.registerModalOpen({
                            id: 'swal_ipark_panel_modal',
                            type: 'swal',
                            closeCallback: () => {
                                console.log('📱 모바일 핸들러에서 SweetAlert2 닫기 호출');
                                if (Swal.isVisible()) {
                                    Swal.close();
                                }
                            }
                        });
                    } else {
                        console.warn('⚠️ mobileModalHandler가 정의되지 않음');
                    }

                    // 모바일에서 입력 필드 포커스 처리
                    if (window.innerWidth <= 768) {
                        const firstInput = document.getElementById('iparkPanel39Width');
                        if (firstInput) {
                            setTimeout(() => {
                                firstInput.focus();
                            }, 300);
                        }
                    }
                },
                willClose: () => {
                    // 모바일 모달 핸들러 해제
                    if (window.mobileModalHandler) {
                        window.mobileModalHandler.registerModalClose('swal_ipark_panel_modal');
                    }
                },
                preConfirm: () => {
                    const panel39Width = document.getElementById('iparkPanel39Width').value;
                    const panel6Width = document.getElementById('iparkPanel6Width').value;

                    if (!panel39Width || !panel6Width) {
                        Swal.showValidationMessage('모든 판넬폭을 입력해주세요');
                        return false;
                    }

                    if (panel39Width < 1 || panel39Width > 3000) {
                        Swal.showValidationMessage('3번, 9번 판넬폭은 1~3000mm 범위로 입력해주세요');
                        return false;
                    }

                    if (panel6Width < 1 || panel6Width > 3000) {
                        Swal.showValidationMessage('6번 판넬폭은 1~3000mm 범위로 입력해주세요');
                        return false;
                    }

                    return {
                        panel39Width: parseInt(panel39Width),
                        panel6Width: parseInt(panel6Width)
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // 아이파크 판넬폭 설정 저장
                    localStorage.setItem('iparkPanel39Width', result.value.panel39Width);
                    localStorage.setItem('iparkPanel6Width', result.value.panel6Width);

                    // 아이파크 자동 실측값 계산 및 적용
                    applyIparkAutoMeasurements(result.value.panel39Width, result.value.panel6Width);

                    // 성공 메시지
                    Swal.fire({
                        icon: 'success',
                        title: '설정 완료',
                        text: `3번,9번 판넬폭: ${result.value.panel39Width}mm, 6번 판넬폭: ${result.value.panel6Width}mm로 설정되었습니다.`,
                        timer: 3000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end',
                        didOpen: () => {
                            if (window.mobileModalHandler) {
                                window.mobileModalHandler.registerModalOpen({
                                    id: 'swal_ipark_success',
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
                            if (window.mobileModalHandler) {
                                window.mobileModalHandler.registerModalClose('swal_ipark_success');
                            }
                        }
                    });
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    // 취소된 경우 체크박스 해제
                    const iparkCheck = document.getElementById('iparkCheck');
                    if (iparkCheck) {
                        iparkCheck.checked = false;

                        // 현장명도 원래대로 복원
                        const siteNameInput = document.getElementById('siteName');
                        if (siteNameInput) {
                            const currentValue = siteNameInput.value;
                            if (currentValue.includes('아이파크')) {
                                siteNameInput.value = currentValue.replace('아이파크', '임시현장');
                            }
                        }

                        // 자동계산 값 초기화
                        clearIparkAutoMeasurements();
                    }
                }
            });
        }

        // 아이파크 설정 div 표시 함수 (PC용)
        function showIparkSettingsDiv() {
            console.log('🚀 showIparkSettingsDiv 함수 진입 - div 토글 표시 시작');

            const settingsDiv = document.getElementById('iparkSettingsDiv');
            console.log('📱 DEBUG: iparkSettingsDiv 요소:', settingsDiv ? '✅ 발견' : '❌ 없음');

            const input39 = document.getElementById('iparkPanel39Width');
            const input6 = document.getElementById('iparkPanel6Width');
            console.log('📱 DEBUG: 입력 필드들:', input39 ? '✅ 39번 발견' : '❌ 39번 없음', input6 ? '✅ 6번 발견' : '❌ 6번 없음');

            if (!settingsDiv || !input39 || !input6) {
                console.error('❌ 아이파크 설정 div 요소들을 찾을 수 없습니다');
                return;
            }

            // 저장된 값 로드
            const saved39 = localStorage.getItem('iparkPanel39Width') || '800';
            const saved6 = localStorage.getItem('iparkPanel6Width') || '1000';
            console.log('🔍 저장된 값 로드 - 3,9번:', saved39, '6번:', saved6);

            // 입력 필드에 값 설정
            input39.value = saved39;
            input6.value = saved6;
            console.log('🔍 입력 필드 값 설정 완료');

            // 숨겨진 div를 표시 (모달창 대신)
            settingsDiv.style.display = 'block';
            console.log('✅ 아이파크 설정 div 표시 완료 (모달창 대신)');

            // 스크롤을 설정 div로 이동 (모바일 친화적)
            settingsDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
            console.log('🔍 설정 div로 스크롤 이동');
        }


        // 아이파크 설정 div ESC 키 핸들러
        function handleIparkEscapeKey(e) {
            if (e.key === 'Escape') {
                const settingsDiv = document.getElementById('iparkSettingsDiv');
                if (settingsDiv && settingsDiv.style.display === 'block') {
                    hideIparkSettingsDiv();
                }
            }
        }

        // 아이파크 설정 div 닫기 함수
        function hideIparkSettingsDiv() {
            console.log('🚪 hideIparkSettingsDiv 함수 실행');

            const settingsDiv = document.getElementById('iparkSettingsDiv');
            if (settingsDiv) {
                settingsDiv.style.display = 'none';
                console.log('✅ 아이파크 설정 div 숨김 처리 완료 (모달창 대신)');
            }

            // ESC 키 이벤트 리스너 제거
            document.removeEventListener('keydown', handleIparkEscapeKey);
            console.log('🔍 ESC 키 이벤트 리스너 제거');
        }

        // 아이파크 모달 저장 함수
        function saveIparkPanelSettingsDOM() {
            console.log('💾 saveIparkPanelSettingsDOM 함수 실행');

            const panel39Width = document.getElementById('iparkPanel39Width').value;
            const panel6Width = document.getElementById('iparkPanel6Width').value;

            console.log('🔍 입력된 값 - 3,9번:', panel39Width, '6번:', panel6Width);

            // 유효성 검사
            if (!panel39Width || !panel6Width) {
                alert('모든 값을 입력해주세요.');
                return;
            }

            if (panel39Width < 100 || panel39Width > 2000) {
                alert('3,9번 패널 폭은 100~2000mm 사이로 입력해주세요.');
                return;
            }

            if (panel6Width < 100 || panel6Width > 2000) {
                alert('6번 패널 폭은 100~2000mm 사이로 입력해주세요.');
                return;
            }

            console.log('✅ 유효성 검사 통과');

            // localStorage에 저장
            localStorage.setItem('iparkPanel39Width', panel39Width);
            localStorage.setItem('iparkPanel6Width', panel6Width);
            console.log('💾 localStorage에 저장 완료');

            // 자동계산 적용
            applyIparkAutoMeasurements(panel39Width, panel6Width);
            console.log('🔄 자동계산 적용 완료');

            // div 닫기
            hideIparkSettingsDiv();

            // 성공 알림 (SweetAlert2 사용)
            setTimeout(() => {
                Swal.fire({
                    icon: 'success',
                    title: '설정 완료',
                    text: `아이파크 자동계산이 적용되었습니다.\n(3,9번: ${panel39Width}mm, 6번: ${panel6Width}mm)`,
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            }, 100);
        }

        // 아이파크 모달 취소 함수
        function cancelIparkPanelSettingsDOM() {
            console.log('❌ cancelIparkPanelSettingsDOM 함수 실행');

            // 체크박스 해제
            const iparkCheck = document.getElementById('iparkCheck');
            if (iparkCheck) {
                iparkCheck.checked = false;
                console.log('🔍 체크박스 해제 완료');

                // 현장명도 원래대로 복원
                const siteNameInput = document.getElementById('siteName');
                if (siteNameInput) {
                    const currentValue = siteNameInput.value;
                    if (currentValue.includes('아이파크')) {
                        siteNameInput.value = currentValue.replace('아이파크', '임시현장');
                        console.log('🔍 현장명 복원:', siteNameInput.value);
                    }
                }

                // 변화 감지 보강: programmatic 변경(이벤트 미발행) 대응
                (function setupIparkChangeWatchers() {
                    try {
                        const ipark = document.getElementById('iparkCheck');
                        if (!ipark) return;

                        // 1) MutationObserver (checked attribute 변경 시)
                        try {
                            const mo = new MutationObserver(function(mutations) {
                                mutations.forEach(m => {
                                    if (m.type === 'attributes' && m.attributeName === 'checked') {
                                        console.log('👀 MutationObserver: checked attr changed → call handler');
                                        handleIparkCheckboxChange.call(ipark);
                                    }
                                });
                            });
                            mo.observe(ipark, { attributes: true, attributeFilter: ['checked'] });
                            window.__iparkMO = mo;
                        } catch (err) {
                            console.warn('MutationObserver not available or failed:', err);
                        }

                        // 2) Property polling (checked prop 변경 감지)
                        let lastCheckedState = !!ipark.checked;
                        const poll = () => {
                            const current = !!ipark.checked;
                            if (current !== lastCheckedState) {
                                console.log('⏱️ Poll detected checked change:', lastCheckedState, '→', current);
                                lastCheckedState = current;
                                handleIparkCheckboxChange.call(ipark);
                            }
                            window.__iparkRAF = requestAnimationFrame(poll);
                        };
                        window.__iparkRAF = requestAnimationFrame(poll);

                        // 3) 페이지 unload 시 정리
                        window.addEventListener('beforeunload', function() {
                            if (window.__iparkRAF) cancelAnimationFrame(window.__iparkRAF);
                            if (window.__iparkMO) try { window.__iparkMO.disconnect(); } catch (e) {}
                        });

                        console.log('✅ Ipark change watchers initialized (MO + rAF)');
                    } catch (err) {
                        console.error('setupIparkChangeWatchers error:', err);
                    }
                })();

                // 글로벌 이벤트 핸들러는 제거 (중복 실행 방지)
                console.log('🚫 글로벌 이벤트 핸들러 제거 - 중복 실행 방지');

                // 자동계산 값 초기화
                clearIparkAutoMeasurements();
                console.log('🔍 자동계산 값 초기화 완료');
            }

            // div 닫기
            hideIparkSettingsDiv();
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
                    carDepthInput.dispatchEvent(new Event('input', { bubbles: true })); // 폼 변경 감지
                }
            }
            if (!carWidthInput?.value || parseInt(carWidthInput.value) <= 0) {
                if (carWidthInput) {
                    carWidthInput.value = '1600'; // 아이파크 표준 가로
                    carWidthInput.dispatchEvent(new Event('input', { bubbles: true })); // 폼 변경 감지
                }
            }
            if (!carHeightInput?.value || parseInt(carHeightInput.value) <= 0) {
                if (carHeightInput) {
                    carHeightInput.value = '2700'; // 아이파크 표준 높이
                    carHeightInput.dispatchEvent(new Event('input', { bubbles: true })); // 폼 변경 감지
                }
            }

            // 설정된 카 내부 치수 가져오기
            const carDepth = parseInt(carDepthInput?.value) || 1500;
            const carWidth = parseInt(carWidthInput?.value) || 1600;

            console.log('🔍 카 내부 치수 설정 완료 - 가로:', carWidth, '깊이:', carDepth);

            // 폼 변경 강제 알림 (모든 카 내부 치수 필드에 대해)
            if (carWidthInput) {
                carWidthInput.dispatchEvent(new Event('change', { bubbles: true }));
                console.log('🔄 carInsideWidth change 이벤트 발생');
            }
            if (carDepthInput) {
                carDepthInput.dispatchEvent(new Event('change', { bubbles: true }));
                console.log('🔄 carInsideDepth change 이벤트 발생');
            }
            if (carHeightInput) {
                carHeightInput.dispatchEvent(new Event('change', { bubbles: true }));
                console.log('🔄 carInsideHeight change 이벤트 발생');
            }

            // D방향 계산 (2,3,4,8,9,10번 판넬)
            // 3번과 9번은 각각 같은 폭 (panel39Width는 하나의 폭)
            const remainingDepth = carDepth - panel39Width; // 3번 또는 9번 폭 하나만 빼기
            let panel2_4_8_10_width = Math.round(remainingDepth / 2); // 2,4,8,10번은 동일한 폭

            // W방향 계산 (5,6,7번 판넬)
            const remainingWidth = carWidth - panel6Width;
            let panel5_7_width = Math.round(remainingWidth / 2); // 5,7번은 동일한 폭


            // 유효성 검사 및 자동 조정
            let hasAdjustment = false;
            let adjustmentMessage = '';

            if (panel2_4_8_10_width <= 0) {
                // 음수인 경우 최소값 10mm로 강제 설정
                panel2_4_8_10_width = 10;
                hasAdjustment = true;
                adjustmentMessage += `D방향: 3번 판넬폭(${panel39Width}mm)이 카 깊이(${carDepth}mm)보다 커서 2,4,8,10번을 최소값 10mm로 조정\n`;
                console.log(`⚠️ D방향 조정: 2,4,8,10번 → ${panel2_4_8_10_width}mm (최소값)`);
            }

            if (panel5_7_width <= 0) {
                // 음수인 경우 최소값 10mm로 강제 설정
                panel5_7_width = 10;
                hasAdjustment = true;
                adjustmentMessage += `W방향: 5,7번 판넬폭을 최소값 10mm로 조정\n`;
                console.log(`⚠️ W방향 조정: 5,7번 → ${panel5_7_width}mm (최소값)`);
            }

            // 패널 데이터 객체가 없으면 초기화
            if (typeof window.panelData === 'undefined') {
                window.panelData = {};
            }

            // 2-10번 판넬에 자동 계산된 width 값 적용
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
            const defaultHeight = carHeight > 0 ? carHeight : 2700; // 카 높이가 있으면 사용, 없으면 기본값 2700mm

            // 각 판넬에 width와 height 값 설정
            for (let panelNum = 2; panelNum <= 10; panelNum++) {
                if (!window.panelData[panelNum]) {
                    window.panelData[panelNum] = {};
                }
                window.panelData[panelNum].width = panelWidths[panelNum];
                window.panelData[panelNum].height = defaultHeight; // 기본 높이 자동 설정

                // 아이파크 자동계산 플래그 추가
                window.panelData[panelNum].isIparkAuto = true;
                window.panelData[panelNum].autoCalculatedAt = new Date().toISOString();

            }

            // 실제 측정 시각화에 강제 업데이트
            forceUpdatePanelVisualization(panelWidths);

            // 기존 실측서 형태로 저장 (JSON 형태로 panelData에 저장)
            saveToExistingMeasurementFormat(panelWidths);
            
        }

        // 실제 측정 시각화에 강제 업데이트
        function forceUpdatePanelVisualization(panelWidths) {

            // 기본 높이 값 가져오기
            const carHeight = parseInt(document.getElementById('carInsideHeight')?.value) || 0;
            const defaultHeight = carHeight > 0 ? carHeight : 2700;

            // 각 판넬의 width와 height 값을 시각화에 반영
            for (let panelNum = 2; panelNum <= 10; panelNum++) {
                const width = panelWidths[panelNum];
                if (!width) continue;


                // 1. panelData 전역 변수에 강제 저장 (이미 위에서 설정되었지만 확실히)
                if (!window.panelData) window.panelData = {};
                if (!window.panelData[panelNum]) window.panelData[panelNum] = {};
                window.panelData[panelNum].width = width;
                window.panelData[panelNum].height = defaultHeight;

                // 2. 패널 요소에 데이터 속성으로 저장
                const panelElement = document.querySelector(`.panel-${panelNum}`);
                if (panelElement) {
                    panelElement.setAttribute('data-width', width);
                    panelElement.setAttribute('data-height', defaultHeight);
                    panelElement.setAttribute('data-ipark-auto', 'true');
                }

                // 3. 기존 모달 입력창이 있다면 업데이트
                const modalWidthInput = document.getElementById('modalWidth');
                if (modalWidthInput && modalWidthInput.getAttribute('data-panel') == panelNum) {
                    modalWidthInput.value = width;
                }

                // 4. 패널 정보 표시 업데이트
                updatePanelInfo(panelNum, { width: width, height: defaultHeight });
            }

            // 5. 이벤트 트리거 (다른 컴포넌트에 알림)
            const updateEvent = new CustomEvent('iparkAutoUpdate', {
                detail: { panelWidths: panelWidths, timestamp: Date.now() }
            });
            document.dispatchEvent(updateEvent);

        }

        // 패널 정보 업데이트 (시각화 반영)
        function updatePanelInfo(panelNum, data) {
            try {
                // 패널 요소 찾기
                const panelElement = document.querySelector(`.panel-${panelNum}`);
                if (!panelElement) return;

                // 패널 정보 표시 영역 찾기 또는 생성 - 기존 renderPanelInfo 방식 사용
                let infoDiv = panelElement.querySelector('.panel-info');
                if (!infoDiv) {
                    infoDiv = document.createElement('div');
                    infoDiv.className = 'panel-info';
                    infoDiv.style.cssText = `
                        position: absolute;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                        text-align: center;
                        color: #ffd700;
                        font-weight: 600;
                        font-size: 11px;
                        line-height: 1.2;
                        white-space: nowrap;
                        pointer-events: none;
                        z-index: 10;
                    `;
                    panelElement.appendChild(infoDiv);
                }

                // 정보 표시 (width x height 형태로)
                if (data.width && data.height) {
                    infoDiv.innerHTML = `${data.width}<br>×<br>${data.height}`;
                    infoDiv.title = `아이파크 자동계산: ${data.width}mm × ${data.height}mm`;
                } else if (data.width) {
                    infoDiv.innerHTML = `W: ${data.width}`;
                    infoDiv.title = `아이파크 자동계산: 폭 ${data.width}mm`;
                }

                // 패널에 has-info 클래스 추가
                panelElement.classList.add('has-info');

            } catch (error) {
                console.error(`패널 ${panelNum} 정보 업데이트 오류:`, error);
            }
        }

        // 기존 실측서 형태로 저장 (JSON 형태)
        function saveToExistingMeasurementFormat(panelWidths) {

            // panelData가 없으면 초기화
            if (!window.panelData) {
                window.panelData = {};
            }

            // 각 판넬에 기존 실측서 형태로 데이터 저장
            for (let panelNum = 2; panelNum <= 10; panelNum++) {
                const width = panelWidths[panelNum];
                if (!width) continue;

                // 기존 패널 데이터가 없으면 생성
                if (!window.panelData[panelNum]) {
                    window.panelData[panelNum] = {};
                }

                // 기존 실측서 형태의 필수 필드들 설정
                window.panelData[panelNum] = {
                    ...window.panelData[panelNum], // 기존 데이터 유지
                    width: width,
                    height: window.panelData[panelNum].height || 0, // 높이는 기존값 유지 또는 0
                    thickness: window.panelData[panelNum].thickness || 0, // 두께는 기존값 유지 또는 0
                    // 코너 타입은 1,11번에서만 의미가 있으므로 2-10은 빈 값 유지
                    panel_type_detail: '',
                    panelType: '',
                    materialType: window.panelData[panelNum].materialType || '', // 재질 타입
                    frontThickness: window.panelData[panelNum].frontThickness || 0,
                    frontWing: window.panelData[panelNum].frontWing || 0,
                    backThickness: window.panelData[panelNum].backThickness || 0,
                    backWing: window.panelData[panelNum].backWing || 0,
                    notes: window.panelData[panelNum].notes || '아이파크 자동계산',
                    isIparkAuto: true, // 아이파크 자동계산 플래그
                    autoCalculatedAt: new Date().toISOString() // 자동계산 시간
                };

            }

            // 전역 패널 데이터를 JSON 문자열로 변환하여 숨겨진 필드에 저장
            updatePanelDataInput();

        }

        // 패널 데이터를 숨겨진 입력 필드에 업데이트
        function updatePanelDataInput() {
            try {
                // panel_data 숨겨진 입력 필드 찾기
                const panelDataInput = document.getElementById('panelDataInput') ||
                                     document.querySelector('input[name="panel_data"]') ||
                                     document.querySelector('textarea[name="panel_data"]');

                if (panelDataInput && window.panelData) {
                    const jsonData = JSON.stringify(window.panelData, null, 2);
                    panelDataInput.value = jsonData;
                } else {
                    console.warn('⚠️ 패널 데이터 입력 필드를 찾을 수 없습니다');
                }
            } catch (error) {
                console.error('패널 데이터 입력 필드 업데이트 오류:', error);
            }
        }

        // 아이파크 자동계산 값 초기화 함수
        function clearIparkAutoMeasurements() {
            // 중복 실행 방지
            if (window.clearIparkInProgress) {
                console.log('🚫 clearIparkAutoMeasurements 중복 실행 방지');
                return;
            }
            window.clearIparkInProgress = true;

            // 2-10번 판넬의 자동계산 값 제거
            for (let panelNum = 2; panelNum <= 10; panelNum++) {
                // panelData에서 width 값 제거
                if (window.panelData && window.panelData[panelNum]) {
                    delete window.panelData[panelNum].width;

                    // 빈 객체가 되면 완전 삭제
                    if (Object.keys(window.panelData[panelNum]).length === 0) {
                        delete window.panelData[panelNum];
                    }
                }

                // 시각적 표시 제거
                const panelElement = document.querySelector(`.panel-${panelNum}`);
                if (panelElement) {
                    // 스타일 초기화
                    panelElement.style.border = '';
                    panelElement.style.backgroundColor = '';

                    // 툴팁 초기화
                    panelElement.title = `판넬 ${panelNum}`;

                    // 데이터 속성 제거
                    panelElement.removeAttribute('data-width');
                    panelElement.removeAttribute('data-ipark-auto');

                    // 자동계산 정보 표시 제거
                    const infoElement = panelElement.querySelector('.panel-auto-info');
                    if (infoElement) {
                        infoElement.remove();
                    }
                }
            }

            // localStorage에서 아이파크 설정값 제거
            localStorage.removeItem('iparkPanel39Width');
            localStorage.removeItem('iparkPanel6Width');

            // 성공 메시지 (모바일에서는 토스트/타이머로 인해 UI 지연 유발 가능하므로 alert로 단순화)
            if (typeof isMobileDevice === 'function' && isMobileDevice()) {
                console.log('ℹ️ 모바일: 초기화 안내 간단 알림으로 대체');
                try {
                    alert('아이파크 자동계산 값이 초기화되었습니다.');
                } catch (e) {}
            } else if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'info',
                    title: '자동 실측값 초기화 완료',
                    text: '아이파크 자동계산 값들이 모두 제거되었습니다.',
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            }

            // 중복 실행 방지 플래그 해제
            setTimeout(() => {
                window.clearIparkInProgress = false;
            }, 500);
        }

        // UI 상태 업데이트 함수
        function updateEditModeUI() {

            // 페이지 제목 업데이트
            const pageTitle = document.querySelector('h2.page-title');
            if (pageTitle) {
                const titleText = pageTitle.querySelector('span:not(.badge)') || pageTitle;
                if (titleText.textContent && !titleText.textContent.includes('(수정)')) {
                    titleText.textContent += ' (수정)';
                }
            }

            // 버튼 상태는 initializeCheckboxStates에서 project_type 값에 따라 설정되므로
            // 여기서는 버튼 상태를 변경하지 않음 (제거)
            console.log('🔍 DEBUG: updateEditModeUI - 버튼 상태는 initializeCheckboxStates에서 처리됨');

            // 브레드크럼 업데이트 (있다면)
            const breadcrumb = document.querySelector('.pm-breadcrumb span:last-child');
            if (breadcrumb && !breadcrumb.textContent.includes('(수정)')) {
                breadcrumb.textContent += ' (수정)';
            }
        }
        
        // 신규/MOD 및 패널 제어 기능
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize edit mode status based on URL
            const urlParams = new URLSearchParams(window.location.search);
            const editId = urlParams.get('edit');
            window.isEditMode = !!editId;
            window.editId = editId || null;


            // 수정 모드인 경우 UI 상태 업데이트
            if (window.isEditMode) {
                setTimeout(updateEditModeUI, 100); // DOM이 완전히 로드된 후 실행
            }

            // Hidden inputs for form data
            const projectTypeInput = document.getElementById('projectType');
            const panelCornersExcludedInput = document.getElementById('panelCornersExcluded');

            // UI controls
            const newBtn = document.getElementById('newBtn');
            const modBtn = document.getElementById('modBtn');
            const excludePanelCornersCheckbox = document.getElementById('excludePanelCorners');
            const excludeTransomCheckbox = document.getElementById('excludeTransom');
            const panelCount = document.getElementById('panelCount');
            const panelInfo = document.getElementById('panelInfo');
            const panel1 = document.querySelector('.panel-1');
            const panel11 = document.querySelector('.panel-11');
            const panel12 = document.querySelector('.panel-12');

            let projectType = 'new'; // 'new' 또는 'mod'

            // 페이지 로드 시 저장된 값에 따라 체크박스 상태 초기화
            function initializeCheckboxStates() {
                // 안전하게 저장된 값 읽기
                const savedProjectType = projectTypeInput ? projectTypeInput.value : '신규';

                // 디버깅을 위한 로그 추가
                console.log('🔍 DEBUG: initializeCheckboxStates 실행');
                console.log('🔍 DEBUG: projectTypeInput 존재:', !!projectTypeInput);
                console.log('🔍 DEBUG: projectTypeInput.value:', projectTypeInput ? projectTypeInput.value : 'N/A');
                console.log('🔍 DEBUG: savedProjectType:', savedProjectType);
                console.log('🔍 DEBUG: window.isEditMode:', window.isEditMode);
                console.log('🔍 DEBUG: window.editData:', window.editData);

                // 편집 모드에서는 editData에서, 아니면 hidden input 또는 체크박스에서 값 읽기
                let panelCornersExcluded = 0; // 기본값 - 1,11번 포함
                if (window.isEditMode && window.editData && typeof window.editData.panel_corners_excluded !== 'undefined') {
                    panelCornersExcluded = parseInt(window.editData.panel_corners_excluded) || 0;
                } else if (panelCornersExcludedInput) {
                    panelCornersExcluded = parseInt(panelCornersExcludedInput.value) || 0;
                } else {
                    // 새 항목일 때는 체크박스의 현재 상태 사용 (PHP에서 설정된 초기값)
                    panelCornersExcluded = excludePanelCornersCheckbox ? (excludePanelCornersCheckbox.checked ? 1 : 0) : 0;
                }

                let transomExcluded = 0;
                if (window.isEditMode && window.editData && typeof window.editData.transom_excluded !== 'undefined') {
                    transomExcluded = parseInt(window.editData.transom_excluded) || 0;
                } else {
                    // 새 항목일 때는 체크박스의 현재 상태 사용 (PHP에서 설정된 초기값)
                    transomExcluded = excludeTransomCheckbox ? (excludeTransomCheckbox.checked ? 1 : 0) : 0;
                }


                // 프로젝트 타입 버튼 상태 설정 (저장된 값에 따라)
                console.log('🔍 DEBUG: savedProjectType 비교 시작');
                console.log('🔍 DEBUG: savedProjectType === "신규":', savedProjectType === '신규');
                console.log('🔍 DEBUG: savedProjectType === "MOD":', savedProjectType === 'MOD');

                if (savedProjectType === '신규') {
                    console.log('🔍 DEBUG: setProjectTypeButton("new") 호출');
                    setProjectTypeButton('new');
                } else if (savedProjectType === 'MOD') {
                    console.log('🔍 DEBUG: setProjectTypeButton("mod") 호출');
                    setProjectTypeButton('mod');
                } else {
                    console.log('🔍 DEBUG: 알 수 없는 프로젝트 타입, 기본값(신규)으로 설정:', savedProjectType);
                    setProjectTypeButton('new');
                }

                // 체크박스 상태 설정
                if (excludePanelCornersCheckbox) {
                    excludePanelCornersCheckbox.checked = panelCornersExcluded === 1;
                }
                if (excludeTransomCheckbox) {
                    excludeTransomCheckbox.checked = transomExcluded === 1;
                }

                // UI 업데이트
                updatePanelDisplay();

                // 제외된 패널의 정보 확실히 정리 (초기 로딩 후)
                if (panelCornersExcluded === 1) {
                    clearPanelInfoAndCache('1');
                    clearPanelInfoAndCache('11');
                }
                if (transomExcluded === 1) {
                    clearPanelInfoAndCache('12');
                }

                // Transom 패널 기본 'T' 표시 (데이터가 없고 아직 renderPanelInfo가 호출되지 않은 경우)
                const transomPanel = document.querySelector('.panel-12');
                if (transomPanel && (!window.panelData || !window.panelData['12']) &&
                    !transomPanel.classList.contains('has-info') &&
                    transomPanel.children.length === 0) {
                    transomPanel.textContent = 'T';
                }
                
                // 모바일 Transom 패널 기본 'T' 표시
                const mobileTransomPanel = document.querySelector('#mobile-panel-visualization .panel-12');
                if (mobileTransomPanel && (!window.panelData || !window.panelData['12']) &&
                    !mobileTransomPanel.classList.contains('has-info') &&
                    mobileTransomPanel.children.length === 0) {
                    mobileTransomPanel.textContent = 'T';
                }

                console.log('=== CHECKBOX STATES INITIALIZED ===');
            }

            // 패널 정보 초기화 및 캐시 제거 함수
            function clearPanelInfoAndCache(panelNumber) {
                // 렌더링 캐시 제거
                if (window.panelRenderCache && window.panelRenderCache[panelNumber]) {
                    delete window.panelRenderCache[panelNumber];
                }

                // 패널 정보 초기화
                const selectors = [
                    `.panel-${panelNumber}`,
                    `[data-panel="${panelNumber}"]`
                ];

                selectors.forEach(selector => {
                    const panels = document.querySelectorAll(selector);
                    panels.forEach(panel => {
                        // 기존 정보 제거
                        const existingInfo = panel.querySelector('.panel-info');
                        if (existingInfo) {
                            existingInfo.remove();
                        }

                        // 기본 텍스트로 초기화
                        if (panelNumber === '12') {
                            panel.innerHTML = 'T';
                        } else {
                            panel.innerHTML = panelNumber;
                        }
                    });
                });
            }

            // 체크박스 상태에 따른 패널 표시 업데이트 함수
            function updatePanelDisplay() {
                const panelCornersExcluded = excludePanelCornersCheckbox ? excludePanelCornersCheckbox.checked : false;
                const transomExcluded = excludeTransomCheckbox ? excludeTransomCheckbox.checked : false;

                // 2-10번 패널은 항상 표시
                for (let i = 2; i <= 10; i++) {
                    const panel = document.querySelector(`.panel-${i}`);
                    if (panel) panel.style.display = 'flex';
                }

                // 1,11번 패널 제어
                if (panelCornersExcluded) {
                    if (panel1) {
                        panel1.style.display = 'none';
                        // 패널 정보 초기화 및 캐시 제거
                        clearPanelInfoAndCache('1');
                    }
                    if (panel11) {
                        panel11.style.display = 'none';
                        // 패널 정보 초기화 및 캐시 제거
                        clearPanelInfoAndCache('11');
                    }
                } else {
                    if (panel1) {
                        panel1.style.display = 'flex';
                        // 패널 데이터가 있으면 다시 렌더링
                        if (window.panelData && window.panelData['1']) {
                            window.renderPanelInfo('1', window.panelData['1']);
                        }
                    }
                    if (panel11) {
                        panel11.style.display = 'flex';
                        // 패널 데이터가 있으면 다시 렌더링
                        if (window.panelData && window.panelData['11']) {
                            window.renderPanelInfo('11', window.panelData['11']);
                        }
                    }
                }

                // 트랜섬 패널 제어 (체크박스 상태에 따라)
                if (transomExcluded) {
                    if (panel12) {
                        panel12.style.display = 'none';
                        // 패널 정보 초기화 및 캐시 제거
                        clearPanelInfoAndCache('12');
                    }
                } else {
                    if (panel12) {
                        panel12.style.display = 'flex';
                        // 패널 데이터가 있으면 다시 렌더링 (transom 키 또는 12 키 확인)
                        const transomData = window.panelData && (window.panelData['transom'] || window.panelData['12']);
                        if (transomData) {
                            window.renderPanelInfo('12', transomData);
                        }
                    }
                }

                // 패널 수 및 정보 업데이트
                updatePanelCountAndInfo(panelCornersExcluded, transomExcluded);

                // Hidden input 업데이트
                if (panelCornersExcludedInput) panelCornersExcludedInput.value = panelCornersExcluded ? '1' : '0';

                // 선택된 패널이 숨겨진 패널이면 해제
                const selectedPanel = document.getElementById('selectedPanel');
                if (selectedPanel) {
                    const selectedValue = selectedPanel.value;
                    if ((panelCornersExcluded && (selectedValue === '1' || selectedValue === '11')) ||
                        (transomExcluded && selectedValue === '12')) {
                        selectedPanel.value = '';
                        const selectedInfo = document.getElementById('selectedPanelInfo');
                        if (selectedInfo) selectedInfo.style.display = 'none';
                        document.querySelectorAll('.panel.selected').forEach(p => p.classList.remove('selected'));
                    }
                }

                // 모바일 동기화
                if (typeof window.syncMobilePanels === 'function') {
                    window.syncMobilePanels();
                }
            }

            // 트랜섬 데이터가 실제로 입력되었는지 확인하는 함수
            function hasTransomData() {
                // window.panelData에서 transom 데이터 확인 (12번 또는 transom 키)
                let transomData = null;
                if (window.panelData) {
                    transomData = window.panelData['12'] || window.panelData['transom'];
                }

                if (transomData) {
                    // 중요한 필드들 중 하나라도 입력되어 있으면 데이터가 있다고 판단
                    return !!(transomData.width ||
                              transomData.height ||
                              transomData.transomPlateHeight ||
                              transomData.bottomDepthJD ||
                              transomData.wingValue ||
                              transomData.cpiDrillingWidth ||
                              transomData.cpiDrillingHeight ||
                              transomData.cpiDrillingHeightFromBottom);
                }
                return false;
            }

            // 패널 수 및 정보 텍스트 업데이트 함수 (체크박스 상태와 실제 데이터에 따라)
            function updatePanelCountAndInfo(panelCornersExcluded, transomExcluded) {
                let panelCount_text = '';
                let panelInfo_text = '';

                // 체크박스 상태에 따른 패널 수 및 범위 계산
                let panelCountNum = panelCornersExcluded ? 9 : 11; // 기본 패널 수
                let panelRange = panelCornersExcluded ? '2-10' : '1-11';

                // 트랜섬 포함 조건: 체크박스가 체크되지 않았고(transomExcluded = false) 실제 트랜섬 데이터가 있는 경우
                const shouldIncludeTransom = !transomExcluded && hasTransomData();

                if (shouldIncludeTransom) {
                    // 트랜섬 포함
                    panelCountNum += 1;
                    panelCount_text = `(${panelCountNum}매)`;
                    panelInfo_text = `<i class="bi bi-info-circle"></i> 측정할 판넬을 클릭하세요. 판넬 ${panelRange}은 내벽 의장재질, T는 Transom 영역입니다. W, D 공차는 ±3입니다.`;
                } else {
                    // 트랜섬 제외
                    panelCount_text = `(${panelCountNum}매)`;
                    panelInfo_text = `<i class="bi bi-info-circle"></i> 측정할 판넬을 클릭하세요. 판넬 ${panelRange}은 내벽 의장재질 영역입니다. W, D 공차는 ±3입니다.`;
                }

                console.log('🔍 DEBUG: updatePanelCountAndInfo');
                console.log('  - panelCornersExcluded:', panelCornersExcluded);
                console.log('  - transomExcluded:', transomExcluded);
                console.log('  - hasTransomData():', hasTransomData());
                console.log('  - shouldIncludeTransom:', shouldIncludeTransom);
                console.log('  - final panelCountNum:', panelCountNum);
                console.log('  - window.panelData keys:', window.panelData ? Object.keys(window.panelData) : 'null');
                console.log('  - window.panelData[transom]:', window.panelData ? window.panelData['transom'] : 'null');
                console.log('  - window.panelData[12]:', window.panelData ? window.panelData['12'] : 'null');

                if (panelCount) panelCount.textContent = panelCount_text;
                if (panelInfo) panelInfo.innerHTML = panelInfo_text;
            }

            // 프로젝트 타입 버튼 상태 설정 함수
            function setProjectTypeButton(type) {
                if (type === 'new') {
                    projectType = 'new';
                    if (newBtn && modBtn) {
                        newBtn.classList.remove('linear-btn-outline');
                        newBtn.classList.add('linear-btn-primary');
                        modBtn.classList.remove('linear-btn-primary');
                        modBtn.classList.add('linear-btn-outline');
                    }
                    if (projectTypeInput) projectTypeInput.value = '신규';
                } else if (type === 'mod') {
                    projectType = 'mod';
                    if (newBtn && modBtn) {
                        modBtn.classList.remove('linear-btn-outline');
                        modBtn.classList.add('linear-btn-primary');
                        newBtn.classList.remove('linear-btn-primary');
                        newBtn.classList.add('linear-btn-outline');
                    }
                    if (projectTypeInput) projectTypeInput.value = 'MOD';
                }
            }

            // 패널 레이아웃 버튼 상태 설정 함수 

            // 체크박스 이벤트 리스너 추가
            if (excludePanelCornersCheckbox) {
                excludePanelCornersCheckbox.addEventListener('change', function() {
                    updatePanelDisplay();
                });
            }

            if (excludeTransomCheckbox) {
                excludeTransomCheckbox.addEventListener('change', function() {
                    updatePanelDisplay();
                });
            }

            // 페이지 로드 시 초기화 실행 (Edit 모드에서는 지연 실행)

            if (!editId) {
                // 일반 모드에서는 즉시 실행
                initializeCheckboxStates();
            } else {
                // Edit 모드에서는 Edit 스크립트 실행 후 약간 지연하여 실행
                setTimeout(() => {
                    initializeCheckboxStates();
                }, 200);
            }

            
            // 체크박스 상태에 따른 패널 표시 함수 (Legacy wrapper for updatePanelDisplay)
            function updatePanelsByProjectType() {
                // 체크박스 상태 기반 패널 업데이트로 대체
                updatePanelDisplay();
            }
            
            // 신규 버튼 클릭
            if (newBtn) {
                newBtn.addEventListener('click', function() {

                    // Edit 모드인 경우 URL 파라미터 유지 (edit 모드에서는 URL 변경하지 않음)
                    const urlParams = new URLSearchParams(window.location.search);
                    const editId = urlParams.get('edit');

                    if (editId) {
                        // Edit 모드에서는 URL을 변경하지 않고 버튼 상태만 변경
                    } else {
                        // 순수 신규 모드일 때만 상태 초기화
                        window.isEditMode = false;
                        window.editId = null;
                    }

                    // Edit 모드가 아닐 때만 edit_id hidden input 제거
                    if (!editId) {
                        const editIdInput = document.querySelector('input[name="edit_id"]');
                        if (editIdInput) {
                            editIdInput.remove();
                        }
                    }

                    // 프로젝트 타입 버튼 상태 설정 (신규 활성화) - Edit 모드에서도 실행
                    setProjectTypeButton('new');

                    // Edit 모드가 아닐 때만 신규 두께 1.5로 자동 설정
                    if (!editId) {
                        const materialThickness = document.getElementById('materialThickness');
                        if (materialThickness) {
                            materialThickness.value = '1.5';
                        }
                    }

                    // 체크박스 상태 유지하며 UI 업데이트
                    updatePanelDisplay();

                    // Edit 모드가 아닐 때만 UI 텍스트 업데이트
                    if (!editId) {
                        // 페이지 제목에서 (수정) 제거
                        const pageTitle = document.querySelector('h2.page-title');
                        if (pageTitle) {
                            const titleText = pageTitle.querySelector('span:not(.badge)') || pageTitle;
                            if (titleText.textContent) {
                                titleText.textContent = titleText.textContent.replace(' (수정)', '');
                            }
                        }

                        // 브레드크럼에서 (수정) 제거
                        const breadcrumb = document.querySelector('.pm-breadcrumb span:last-child');
                        if (breadcrumb) {
                            breadcrumb.textContent = breadcrumb.textContent.replace(' (수정)', '');
                        }

                    } else {
                        // Edit 모드 - UI 텍스트 유지
                    }
                });
            }

            // MOD 버튼 클릭
            if (modBtn) {
                modBtn.addEventListener('click', function() {
                    setProjectTypeButton('mod');

                    // Edit 모드가 아닐 때만 MOD 두께 1.2로 자동 설정
                    const urlParams = new URLSearchParams(window.location.search);
                    const editId = urlParams.get('edit');

                    if (!editId) {
                        const materialThickness = document.getElementById('materialThickness');
                        if (materialThickness) {
                            materialThickness.value = '1.2';
                        }
                    }

                    // 체크박스 상태 유지하며 UI 업데이트
                    updatePanelDisplay();
                });
            }
            
            // 초기 설정 (신규 모드로 시작)
            // PC 버전에서도 확실히 두께 1.5 설정
            const materialThickness = document.getElementById('materialThickness');
            if (materialThickness) {
                materialThickness.value = '1.5';
            }
            
            updatePanelsByProjectType();


                // 🎯 PHASE 3: syncMobilePanels 함수 비활성화 (반응형으로 통합됨)
                window.syncMobilePanels = function() {
                    console.log('⚠️ DEPRECATED: syncMobilePanels - 반응형 통합으로 더 이상 사용되지 않습니다.');
                    return; // 즉시 종료
                    
                    setTimeout(() => {
                        // 모바일 버튼들
                        const mobileNewBtn = document.querySelector('#mobile-panel-visualization #newBtn');
                        const mobileModBtn = document.querySelector('#mobile-panel-visualization #modBtn');
                        const mobilePanelCount = document.querySelector('#mobile-panel-visualization #panelCount');
                        const mobilePanelInfo = document.querySelector('#mobile-panel-visualization #panelInfo');

                        // 신규/MOD 버튼 상태 동기화
                        if (mobileNewBtn && mobileModBtn) {
                            if (projectType === 'new') {
                                mobileNewBtn.classList.remove('linear-btn-outline');
                                mobileNewBtn.classList.add('linear-btn-primary');
                                mobileModBtn.classList.remove('linear-btn-primary');
                                mobileModBtn.classList.add('linear-btn-outline');
                            } else {
                                mobileModBtn.classList.remove('linear-btn-outline');
                                mobileModBtn.classList.add('linear-btn-primary');
                                mobileNewBtn.classList.remove('linear-btn-primary');
                                mobileNewBtn.classList.add('linear-btn-outline');
                            }

                            // 모바일 신규/MOD 버튼 이벤트 추가
                            if (!mobileNewBtn.hasAttribute('data-event-added')) {
                                mobileNewBtn.setAttribute('data-event-added', 'true');
                                mobileNewBtn.addEventListener('click', function() {
                                    if (newBtn) newBtn.click();
                                });
                            }
                            if (!mobileModBtn.hasAttribute('data-event-added')) {
                                mobileModBtn.setAttribute('data-event-added', 'true');
                                mobileModBtn.addEventListener('click', function() {
                                    if (modBtn) modBtn.click();
                                });
                            }
                        }

                        // 체크박스 상태 동기화 - 기존 제외/포함 버튼을 체크박스로 대체
                        const mobileExcludePanelCornersCheckbox = document.querySelector('#mobile-panel-visualization #excludePanelCorners');
                        const mobileExcludeTransomCheckbox = document.querySelector('#mobile-panel-visualization #excludeTransom');

                        if (mobileExcludePanelCornersCheckbox && excludePanelCornersCheckbox) {
                            mobileExcludePanelCornersCheckbox.checked = excludePanelCornersCheckbox.checked;

                            if (!mobileExcludePanelCornersCheckbox.hasAttribute('data-event-added')) {
                                mobileExcludePanelCornersCheckbox.setAttribute('data-event-added', 'true');
                                mobileExcludePanelCornersCheckbox.addEventListener('change', function() {
                                    excludePanelCornersCheckbox.checked = this.checked;
                                    excludePanelCornersCheckbox.dispatchEvent(new Event('change'));
                                });
                            }
                        }

                        if (mobileExcludeTransomCheckbox && excludeTransomCheckbox) {
                            mobileExcludeTransomCheckbox.checked = excludeTransomCheckbox.checked;

                            if (!mobileExcludeTransomCheckbox.hasAttribute('data-event-added')) {
                                mobileExcludeTransomCheckbox.setAttribute('data-event-added', 'true');
                                mobileExcludeTransomCheckbox.addEventListener('change', function() {
                                    excludeTransomCheckbox.checked = this.checked;
                                    excludeTransomCheckbox.dispatchEvent(new Event('change'));
                                });
                            }
                        }

                        // 판넬 표시 동기화 (체크박스 상태 기반)
                        const mobilePanel1 = document.querySelector('#mobile-panel-visualization .panel-1');
                        const mobilePanel11 = document.querySelector('#mobile-panel-visualization .panel-11');
                        const mobilePanel12 = document.querySelector('#mobile-panel-visualization .panel-12');

                        // 체크박스 상태 확인
                        const panelCornersExcluded = excludePanelCornersCheckbox ? excludePanelCornersCheckbox.checked : false;
                        const transomExcluded = excludeTransomCheckbox ? excludeTransomCheckbox.checked : false;

                        // 2-10번 패널은 항상 표시
                        for (let i = 2; i <= 10; i++) {
                            const mobilePanel = document.querySelector(`#mobile-panel-visualization .panel-${i}`);
                            if (mobilePanel) mobilePanel.style.display = 'flex';
                        }

                        // 1,11번 패널 제어
                        if (panelCornersExcluded) {
                            if (mobilePanel1) mobilePanel1.style.display = 'none';
                            if (mobilePanel11) mobilePanel11.style.display = 'none';
                        } else {
                            if (mobilePanel1) mobilePanel1.style.display = 'flex';
                            if (mobilePanel11) mobilePanel11.style.display = 'flex';
                        }

                        // 트랜섬 패널 제어 (PC 버전과 동일하게 체크박스 상태만으로 제어)
                        if (transomExcluded) {
                            if (mobilePanel12) {
                                mobilePanel12.style.display = 'none';
                            }
                        } else {
                            if (mobilePanel12) {
                                mobilePanel12.style.display = 'flex';
                                // 패널 데이터가 있으면 다시 렌더링
                                const transomData = window.panelData && (window.panelData['transom'] || window.panelData['12']);
                                if (transomData) {
                                    // 데이터가 있으면 렌더링
                                    if (typeof window.renderPanelInfo === 'function') {
                                        window.renderPanelInfo('12', transomData);
                                    }
                                } else {
                                    // 데이터가 없으면 기본 'T' 텍스트 표시
                                    if (!mobilePanel12.classList.contains('has-info') && mobilePanel12.children.length === 0) {
                                        mobilePanel12.textContent = 'T';
                                    }
                                }
                            }
                        }
                        
                        // 트랜섬 포함 조건 (패널 수 계산용)
                        const shouldIncludeTransom = !transomExcluded && hasTransomData();

                        // 패널 수 및 정보 업데이트 (체크박스 상태 및 실제 데이터 기반)
                        let panelCountNum = panelCornersExcluded ? 9 : 11; // 기본 패널 수
                        let panelRange = panelCornersExcluded ? '2-10' : '1-11';

                        if (shouldIncludeTransom) {
                            // 트랜섬 포함
                            panelCountNum += 1;
                            const panelCount_text = `(${panelCountNum}매)`;
                            const panelInfo_text = `<i class="bi bi-info-circle"></i> 측정할 판넬을 클릭하세요. 판넬 ${panelRange}은 내벽 의장재질, T는 Transom 영역입니다. W, D 공차는 ±3입니다.`;
                            if (mobilePanelCount) mobilePanelCount.textContent = panelCount_text;
                            if (mobilePanelInfo) mobilePanelInfo.innerHTML = panelInfo_text;
                        } else {
                            // 트랜섬 제외
                            const panelCount_text = `(${panelCountNum}매)`;
                            const panelInfo_text = `<i class="bi bi-info-circle"></i> 측정할 판넬을 클릭하세요. 판넬 ${panelRange}은 내벽 의장재질 영역입니다. W, D 공차는 ±3입니다.`;
                            if (mobilePanelCount) mobilePanelCount.textContent = panelCount_text;
                            if (mobilePanelInfo) mobilePanelInfo.innerHTML = panelInfo_text;
                        }
                        
                        // 모바일 버튼 동기화 완료
                    }, 100);
                };
        });
    </script>
    
    <!-- Mobile Cards Content Population -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let mobileCardsPopulated = false;

            // 🎯 PHASE 3: 모바일 전용 이벤트 리스너 비활성화 (반응형으로 통합됨)
            function addMobileInputEventListeners() {
                console.log('⚠️ DEPRECATED: addMobileInputEventListeners - 반응형 통합으로 더 이상 사용되지 않습니다.');
                return; // 즉시 종료
                // 모바일 측정 입력 필드들
                const mobileInputFields = [
                    '#mobile-measurements input[name="panelWidth"]',
                    '#mobile-measurements input[name="panelHeight"]',
                    '#mobile-measurements input[name="panelThickness"]',
                    '#mobile-measurements input[name="transomWidth"]',
                    '#mobile-measurements input[name="transomHeight"]',
                    '#mobile-materials select[name="materialType"]',
                    '#mobile-materials textarea[name="notes"]'
                ];

                mobileInputFields.forEach(selector => {
                    const element = document.querySelector(selector);
                    if (element && !element.hasAttribute('data-mobile-listener-added')) {
                        element.setAttribute('data-mobile-listener-added', 'true');

                        const eventType = element.tagName.toLowerCase() === 'select' ? 'change' : 'input';

                        element.addEventListener(eventType, function() {
                            // 입력값이 변경될 때마다 JSON 필드 업데이트 (안전한 호출 사용)
                            window.safeUpdateJsonFields('모바일 입력 변경');

                            // PC 입력 필드와도 동기화 (있는 경우)
                            syncMobileToPC(this.name, this.value);
                        });
                    }
                });
            }

            // 모바일 입력값을 PC 입력 필드와 동기화
            function syncMobileToPC(fieldName, value) {
                const pcFieldMap = {
                    'panelWidth': 'panelWidth',
                    'panelHeight': 'panelHeight',
                    'panelThickness': 'panelThickness',
                    'transomWidth': 'transomWidth',
                    'transomHeight': 'transomHeight',
                    'materialType': 'materialType',
                    'notes': 'notes'
                };

                const pcFieldId = pcFieldMap[fieldName];
                if (pcFieldId) {
                    const pcElement = document.getElementById(pcFieldId);
                    if (pcElement && !pcElement.value) { // PC 필드가 비어있을 때만 동기화
                        pcElement.value = value;
                    }
                }
            }

            // 🎯 PHASE 3: 모바일 버튼 이벤트 리스너 비활성화 (반응형으로 통합됨)
            function addMobileButtonEventListeners() {
                console.log('⚠️ DEPRECATED: addMobileButtonEventListeners - 반응형 통합으로 더 이상 사용되지 않습니다.');
                return; // 즉시 종료
                // 모바일 버튼 컨테이너 확인
                const mobileButtonsContainer = document.getElementById('mobile-buttons');
                if (!mobileButtonsContainer) {
                    console.error('mobile-buttons 컨테이너를 찾을 수 없습니다');
                    return;
                }

                // 모바일 저장 버튼 찾기 (중복 ID 문제 방지를 위해 더 구체적인 선택자 사용)
                const mobileSaveBtn = document.querySelector('#mobile-buttons button[type="submit"]') ||
                                     document.querySelector('#mobile-buttons #saveBtn');
                const mobileValidateBtn = document.querySelector('#mobile-buttons button[onclick*="validateMeasurements"]') ||
                                         document.querySelector('#mobile-buttons #validateBtn');

                // PC 버튼들 참조
                const pcSaveBtn = document.getElementById('saveBtn');
                const pcValidateBtn = document.getElementById('validateBtn');
                const mainForm = document.querySelector('form');

                if (mobileSaveBtn && !mobileSaveBtn.hasAttribute('data-mobile-listener-added')) {
                    mobileSaveBtn.setAttribute('data-mobile-listener-added', 'true');

                    mobileSaveBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();

                        // PC 버튼 일시적으로 비활성화하여 충돌 방지
                        if (pcSaveBtn) {
                            pcSaveBtn.disabled = true;
                        }

                        // JSON 필드 업데이트 먼저 수행 (헬퍼 함수 사용)
                        window.safeUpdateJsonFields('모바일 저장 전');

                        // 모바일 저장 전 패널 데이터 백업
                        const backupPanelData = window.backupPanelData();

                        // 모바일에서 강화된 안전장치: panel_data가 비어있으면 window.panelData 강제 사용
                        const panelJsonField = document.getElementById('panelJsonData');
                        const transomJsonField = document.getElementById('transomJsonData');

                        const panelFieldEmpty = !panelJsonField || !panelJsonField.value || panelJsonField.value.trim() === '' || panelJsonField.value === '{}';

                        if (panelFieldEmpty && window.panelData && Object.keys(window.panelData).length > 0) {
                            // 패널 데이터와 transom 데이터 분리
                            const panels = {};
                            const transom = {};

                            for (const [panelNumber, data] of Object.entries(window.panelData)) {
                                if (panelNumber === '12') {
                                    transom[panelNumber] = data;
                                } else {
                                    panels[panelNumber] = data;
                                }
                            }

                            // 강제로 JSON 필드 설정
                            if (panelJsonField) {
                                panelJsonField.value = JSON.stringify(panels);
                            }

                            if (transomJsonField) {
                                transomJsonField.value = JSON.stringify(transom);
                            }
                        }



                        // 버튼 상태 변경
                        const originalText = this.innerHTML;
                        this.disabled = true;
                        this.innerHTML = '<i class="bi bi-hourglass-split"></i> 저장 중...';

                        // AJAX로 폼 제출 (서버 응답 확인을 위해)
                        if (mainForm) {
                            // 저장 전 최종 패널 데이터 상태 확인 (헬퍼 함수 사용)
                            window.checkPanelDataState('저장 전');

                            const formData = new FormData(mainForm);

                            // FormData에 강제로 JSON 값 설정 (히든 필드가 제대로 읽히지 않는 경우 대비)
                            if (panelJsonField && panelJsonField.value) {
                                formData.set('panel_data', panelJsonField.value);
                            }

                            if (transomJsonField && transomJsonField.value) {
                                formData.set('transom_data', transomJsonField.value);
                                console.log('🔍 DEBUG: 모바일 저장 - FormData에 transom_data 설정:', transomJsonField.value);
                            } else {
                                console.log('🔍 DEBUG: 모바일 저장 - transomJsonField가 비어있음:', transomJsonField?.value);
                            }

                            // 체크박스 값 명시적 처리 (체크되지 않은 경우 0으로 설정)
                            const excludeTransomCheckbox = document.getElementById('excludeTransom');
                            if (excludeTransomCheckbox) {
                                formData.set('transom_excluded', excludeTransomCheckbox.checked ? '1' : '0');
                            }

                            const excludePanelCornersCheckbox = document.getElementById('excludePanelCorners');
                            if (excludePanelCornersCheckbox) {
                                formData.set('panel_corners_excluded', excludePanelCornersCheckbox.checked ? '1' : '0');
                            }

                            // project_type 값 명시적 처리
                            const projectTypeInput = document.getElementById('projectType');
                            if (projectTypeInput) {
                                formData.set('project_type', projectTypeInput.value);
                                console.log('🔍 FormData에 project_type 설정:', projectTypeInput.value);
                            }

                            // material_type과 material_thickness 명시적 처리
                            const materialTypeInput = document.getElementById('materialType');
                            if (materialTypeInput) {
                                formData.set('material_type', materialTypeInput.value);
                            }
                            const materialThicknessInput = document.getElementById('materialThickness');
                            if (materialThicknessInput) {
                                formData.set('material_thickness', materialThicknessInput.value);
                            }
                            const elevatorCountInput = document.getElementById('elevatorCount');
                            if (elevatorCountInput) {
                                formData.set('elevator_count', elevatorCountInput.value);
                            }
                            // notes 명시적 처리
                            const notesInput = document.getElementById('notes');
                            if (notesInput) {
                                formData.set('notes', notesInput.value);
                            }

                            fetch('save_panel_measurement.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // 저장 직후 즉시 데이터 상태 확인 (헬퍼 함수 사용)
                                    window.checkPanelDataState('저장 직후');

                                    // Page leave protection - 저장 완료 처리
                                    if (typeof window.resetUnsavedChanges === 'function') {
                                        window.resetUnsavedChanges();
                                    }

                                    // 모바일 저장 후 패널 데이터 보존 확인 (헬퍼 함수 사용)
                                    const saveState = window.checkPanelDataState('저장 완료 후');

                                    // 모바일 저장 후 패널 UI 재렌더링
                                    if (window.panelData && Object.keys(window.panelData).length > 0) {
                                        if (typeof window.renderPanelInfo === 'function') {
                                            Object.keys(window.panelData).forEach(panelNumber => {
                                                const panelDataItem = window.panelData[panelNumber];
                                                if (panelDataItem) {
                                                    window.renderPanelInfo(panelNumber, panelDataItem);
                                                }
                                            });
                                        }
                                    } else {
                                        console.warn('모바일 저장 후 window.panelData가 비어있음! 복원 시도...');

                                        // JSON 필드에서 패널 데이터 복원 시도
                                        try {
                                            const panelJsonField = document.getElementById('panelJsonData');
                                            const transomJsonField = document.getElementById('transomJsonData');

                                            if (panelJsonField && panelJsonField.value) {
                                                const recoveredPanelData = JSON.parse(panelJsonField.value);
                                                if (Object.keys(recoveredPanelData).length > 0) {
                                                    window.panelData = window.panelData || {};
                                                    Object.assign(window.panelData, recoveredPanelData);
                                                }
                                            }

                                            if (transomJsonField && transomJsonField.value) {
                                                const recoveredTransomData = JSON.parse(transomJsonField.value);
                                                if (Object.keys(recoveredTransomData).length > 0) {
                                                    window.panelData = window.panelData || {};
                                                    Object.assign(window.panelData, recoveredTransomData);
                                                }
                                            }

                                                // 백업 데이터로 복원 시도 (헬퍼 함수 사용)
                                            if ((!window.panelData || Object.keys(window.panelData).length === 0) && backupPanelData) {
                                                window.restorePanelData(backupPanelData, '모바일 저장 후 JSON 복원 실패 시');
                                            }
                                        } catch (e) {
                                            console.error('패널 데이터 복원 중 오류:', e);
                                        }
                                    }

                                    // 리디렉션 처리
                                    if (data.should_redirect && data.redirect_url) {
                                        setTimeout(() => {
                                            window.location.href = data.redirect_url;
                                        }, 1000);
                                        return;
                                    }

                                } else {
                                    console.error('저장 실패:', data.message);
                                    alert('저장 실패: ' + (data.message || '알 수 없는 오류'));
                                }
                            })
                            .catch(error => {
                                console.error('모바일 저장 오류:', error);
                                alert('서버 통신 오류가 발생했습니다.');
                            })
                            .finally(() => {
                                // 버튼 상태 복원
                                this.disabled = false;
                                this.innerHTML = originalText;
                                // PC 버튼 다시 활성화
                                if (pcSaveBtn) {
                                    pcSaveBtn.disabled = false;
                                }

                                // 디버깅용 - 저장 완료 후 최종 상태 로그 (헬퍼 함수 사용)
                                const finalState = window.checkPanelDataState('모바일 저장 최종');
                            });
                        } else {
                            console.error('메인 폼을 찾을 수 없습니다');
                            // 버튼 상태 복원
                            this.disabled = false;
                            this.innerHTML = originalText;
                            // PC 버튼 다시 활성화
                            if (pcSaveBtn) {
                                pcSaveBtn.disabled = false;
                            }
                        }

                        // 모바일 저장 처리 완료 - 다른 이벤트 핸들러 실행 방지
                        return false;
                    });

                }

                if (mobileValidateBtn && !mobileValidateBtn.hasAttribute('data-mobile-listener-added')) {
                    mobileValidateBtn.setAttribute('data-mobile-listener-added', 'true');

                    mobileValidateBtn.addEventListener('click', function(e) {
                        e.preventDefault();

                        // PC 검증 버튼 클릭 이벤트 트리거
                        if (pcValidateBtn) {
                            pcValidateBtn.click();
                        } else {
                            console.error('PC 검증 버튼을 찾을 수 없습니다');
                        }
                    });
                }

                // 모바일 돌아가기 버튼 처리
                const mobileBackBtn = document.querySelector('#mobile-buttons button[onclick*="window.location"]');
                if (mobileBackBtn && !mobileBackBtn.hasAttribute('data-mobile-listener-added')) {
                    mobileBackBtn.setAttribute('data-mobile-listener-added', 'true');

                    // onclick 속성 제거하고 이벤트 리스너로 대체
                    const onclickValue = mobileBackBtn.getAttribute('onclick');
                    mobileBackBtn.removeAttribute('onclick');

                    mobileBackBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        handlePageLeaveAttempt('mobile-back', 'index.php');
                    });
                }
            }

            // 중복 생성 방지를 위한 체크 함수
            function isMobileCardAlreadyExists() {
                const mobileSiteInfo = document.getElementById('mobile-site-info');
                return mobileSiteInfo && mobileSiteInfo.children.length > 0;
            }
            
            // 모바일 아이파크 체크박스만 강제 생성하는 함수
            function createMobileIparkCheckbox() {
                console.log('📱 createMobileIparkCheckbox 함수 호출');
                
                let mobileSiteInfo = document.getElementById('mobile-site-info');
                if (!mobileSiteInfo) {
                    console.log('📱 mobile-site-info 요소를 찾을 수 없음 - 생성 시도');
                    const mobileCard1 = document.querySelector('.mobile-card-1 .linear-card-body');
                    if (mobileCard1) {
                        mobileSiteInfo = document.createElement('div');
                        mobileSiteInfo.id = 'mobile-site-info';
                        mobileSiteInfo.style.cssText = 'display: block !important; visibility: visible !important; opacity: 1 !important; width: 100% !important;';
                        mobileCard1.insertBefore(mobileSiteInfo, mobileCard1.firstChild);
                        console.log('📱 mobile-site-info 생성 완료');
                    } else {
                        console.error('📱 mobile-card-1을 찾을 수 없어서 mobile-site-info를 생성할 수 없습니다');
                        return;
                    }
                }
                
                // 이미 존재하는지 확인
                const existingIparkCheck = document.getElementById('mobileIparkCheck');
                if (existingIparkCheck) {
                    console.log('📱 모바일 아이파크 체크박스가 이미 존재함');
                    return;
                }
                
                console.log('📱 모바일 아이파크 체크박스 강제 생성 시작');
                
                // 아이파크 체크박스 컨테이너 생성
                const mobileIparkContainer = document.createElement('div');
                mobileIparkContainer.id = 'mobileIparkCheckContainer';
                mobileIparkContainer.style.cssText = 'margin-bottom: var(--linear-spacing-lg); display: block !important; visibility: visible !important;';
                
                // PC 버전과 동일한 스타일로 HTML 생성
                mobileIparkContainer.innerHTML = `
                    <label id="mobileIparkCheckLabel" for="mobileIparkCheck" style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 12px; border: 1px solid #ddd; border-radius: 6px; background: #f8f9fa; transition: all 0.2s ease; margin-bottom: 15px;">
                        <input type="checkbox" id="mobileIparkCheck" name="mobile_ipark_check" value="1" style="width: 18px; height: 18px; margin: 0; cursor: pointer;">
                        <span style="font-weight: 500; color: #333;">
                            <i class="bi bi-building"></i> 아이파크 신규 체크
                        </span>
                    </label>
                    <div id="mobileIparkSettingsDiv" style="display: none; margin-top: 15px; padding: 20px; border-radius: 8px; background: #f8f9fa; border: 1px solid #ddd;">
                        <div style="margin-bottom: 15px;">
                            <h4 style="margin: 0 0 10px 0; font-size: 16px; color: #333;">
                                <i class="bi bi-gear-fill" style="margin-right: 8px; color: #007bff;"></i>
                                아이파크 판넬폭 설정
                            </h4>
                            <p style="margin: 0; font-size: 14px; color: #666;">아이파크 신규 설정 시 각 패널의 폭을 조정할 수 있습니다.</p>
                        </div>
                        <form>
                            <div style="display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap;">
                                <div style="flex: 1; min-width: 150px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #333;">39번 패널폭 (mm)</label>
                                    <input type="number" id="mobileIparkPanel39Width" value="800" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                    <small style="color: #666; font-size: 12px;">※ 기본값: 800mm</small>
                                </div>
                                <div style="flex: 1; min-width: 150px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #333;">6번 패널폭 (mm)</label>
                                    <input type="number" id="mobileIparkPanel6Width" value="1000" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                    <small style="color: #666; font-size: 12px;">※ 기본값: 1000mm</small>
                                </div>
                            </div>
                            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                                <button type="button" id="mobileIparkCancelBtn" style="padding: 8px 16px; border: 1px solid #ddd; background: #f8f9fa; border-radius: 4px; cursor: pointer;">취소</button>
                                <button type="button" id="mobileIparkConfirmBtn" style="padding: 8px 16px; border: none; background: #007bff; color: white; border-radius: 4px; cursor: pointer;">설정 완료</button>
                            </div>
                        </form>
                    </div>
                `;
                
                // mobile-site-info 맨 위에 추가
                if (mobileSiteInfo.firstChild) {
                    mobileSiteInfo.insertBefore(mobileIparkContainer, mobileSiteInfo.firstChild);
                } else {
                    mobileSiteInfo.appendChild(mobileIparkContainer);
                }
                
                console.log('📱 모바일 아이파크 체크박스 강제 생성 완료');
                
                // PC 버전과 동기화
                setTimeout(() => {
                    const mobileIparkCheck = document.getElementById('mobileIparkCheck');
                    if (mobileIparkCheck) {
                        console.log('📱 모바일 아이파크 체크박스 동기화 시작');
                        
                        // PC 버전과 초기 동기화
                        const pcIparkCheck = document.getElementById('iparkCheck');
                        if (pcIparkCheck) {
                            mobileIparkCheck.checked = pcIparkCheck.checked;
                            console.log('📱 초기 상태 동기화:', mobileIparkCheck.checked);
                        }
                        
                        // 모바일 → PC 동기화
                        mobileIparkCheck.addEventListener('change', function() {
                            console.log('📱 모바일 아이파크 체크박스 변경:', this.checked);
                            
                            if (pcIparkCheck) {
                                pcIparkCheck.checked = this.checked;
                                
                                // PC 버전의 change 이벤트 발생시켜서 모든 기능 작동
                                const changeEvent = new Event('change', { bubbles: true });
                                pcIparkCheck.dispatchEvent(changeEvent);
                            }
                        });
                        
                        console.log('📱 모바일 아이파크 체크박스 동기화 완료');
                    }
                }, 100);
            }
            
            // 모바일 카드 내부 아이파크 체크박스 강화 함수
            function ensureMobileIparkCheckboxInCard() {
                console.log('📱 모바일 카드 내부 아이파크 체크박스 확인 및 강화');
                
                let mobileSiteInfo = document.getElementById('mobile-site-info');
                const mobileIparkCheck = document.getElementById('mobileIparkCheck');
                
                if (!mobileSiteInfo) {
                    console.log('📱 mobile-site-info 요소를 찾을 수 없음 - 생성 시도');
                    const mobileCard1 = document.querySelector('.mobile-card-1 .linear-card-body');
                    if (mobileCard1) {
                        mobileSiteInfo = document.createElement('div');
                        mobileSiteInfo.id = 'mobile-site-info';
                        mobileSiteInfo.style.cssText = 'display: block !important; visibility: visible !important; opacity: 1 !important; width: 100% !important;';
                        mobileCard1.insertBefore(mobileSiteInfo, mobileCard1.firstChild);
                        console.log('📱 mobile-site-info 생성 완료');
                    } else {
                        console.error('📱 mobile-card-1을 찾을 수 없어서 mobile-site-info를 생성할 수 없습니다');
                        return;
                    }
                }
                
                if (!mobileIparkCheck) {
                    console.log('📱 모바일 카드 내부에 아이파크 체크박스 생성');
                    createMobileIparkCheckbox();
                } else {
                    console.log('📱 모바일 아이파크 체크박스가 이미 존재함');
                    
                    // 표시 확인 및 강화
                    const mobileIparkContainer = document.getElementById('mobileIparkCheckContainer');
                    if (mobileIparkContainer) {
                        mobileIparkContainer.style.display = 'block';
                        mobileIparkContainer.style.visibility = 'visible';
                        mobileIparkContainer.style.opacity = '1';
                        console.log('📱 모바일 아이파크 체크박스 표시 강화 완료');
                    }
                }
            }
            
            // 강제로 모바일 아이파크 체크박스 생성하는 함수
            function forceCreateMobileIparkCheckbox() {
                console.log('📱 forceCreateMobileIparkCheckbox 함수 호출');
                
                // mobile-site-info 요소 찾기
                let mobileSiteInfo = document.getElementById('mobile-site-info');
                
                if (!mobileSiteInfo) {
                    console.error('📱 mobile-site-info 요소를 찾을 수 없음 - 직접 생성');
                    
                    // mobile-site-info 요소가 없으면 직접 생성
                    let mobileCard1 = document.querySelector('.mobile-card-1 .linear-card-body');
                    
                    // mobile-card-1이 없으면 mobile-only-cards 컨테이너에 직접 생성
                    if (!mobileCard1) {
                        const mobileCardsContainer = document.querySelector('.mobile-only-cards');
                        if (mobileCardsContainer) {
                            // mobile-card-1을 직접 생성
                            const mobileCard1Element = document.createElement('div');
                            mobileCard1Element.className = 'mobile-card-1';
                            mobileCard1Element.innerHTML = `
                                <div class="linear-card">
                                    <div class="linear-card-header">
                                        <h3 class="linear-card-title"><i class="bi bi-building"></i> 현장 정보 및 측정값</h3>
                                    </div>
                                    <div class="linear-card-body">
                                        <div id="mobile-site-info"></div>
                                    </div>
                                </div>
                            `;
                            mobileCardsContainer.insertBefore(mobileCard1Element, mobileCardsContainer.firstChild);
                            mobileCard1 = mobileCard1Element.querySelector('.linear-card-body');
                            console.log('📱 mobile-card-1 직접 생성 완료');
                        } else {
                            console.error('📱 mobile-only-cards 컨테이너도 찾을 수 없음');
                            return;
                        }
                    }
                    
                    if (mobileCard1) {
                        mobileSiteInfo = document.createElement('div');
                        mobileSiteInfo.id = 'mobile-site-info';
                        mobileSiteInfo.style.cssText = 'display: block !important; visibility: visible !important; opacity: 1 !important; width: 100% !important;';
                        mobileCard1.insertBefore(mobileSiteInfo, mobileCard1.firstChild);
                        console.log('📱 mobile-site-info 요소 직접 생성 완료');
                    } else {
                        console.error('📱 mobile-card-1 생성 실패');
                        return;
                    }
                }
                
                // 기존 아이파크 체크박스 제거
                const existingIparkCheck = document.getElementById('mobileIparkCheck');
                if (existingIparkCheck) {
                    const existingContainer = document.getElementById('mobileIparkCheckContainer');
                    if (existingContainer) {
                        existingContainer.remove();
                        console.log('📱 기존 아이파크 체크박스 제거');
                    }
                }
                
                // 새로운 아이파크 체크박스 컨테이너 생성
                const mobileIparkContainer = document.createElement('div');
                mobileIparkContainer.id = 'mobileIparkCheckContainer';
                mobileIparkContainer.style.cssText = `
                    display: block !important;
                    visibility: visible !important;
                    opacity: 1 !important;
                    margin-bottom: 15px !important;
                    width: 100% !important;
                    box-sizing: border-box !important;
                `;
                
                // PC 버전과 동일한 스타일로 HTML 생성
                mobileIparkContainer.innerHTML = `
                    <label id="mobileIparkCheckLabel" for="mobileIparkCheck" style="
                        display: flex !important;
                        align-items: center !important;
                        gap: 8px !important;
                        cursor: pointer !important;
                        padding: 12px !important;
                        border: 1px solid #ddd !important;
                        border-radius: 6px !important;
                        background: #f8f9fa !important;
                        transition: all 0.2s ease !important;
                        margin-bottom: 15px !important;
                        width: 100% !important;
                        box-sizing: border-box !important;
                    ">
                        <input type="checkbox" id="mobileIparkCheck" name="mobile_ipark_check" value="1" style="
                            width: 18px !important;
                            height: 18px !important;
                            margin: 0 !important;
                            cursor: pointer !important;
                        ">
                        <span style="
                            font-weight: 500 !important;
                            color: #333 !important;
                            font-size: 14px !important;
                            display: inline !important;
                            visibility: visible !important;
                            opacity: 1 !important;
                        ">
                            <i class="bi bi-building" style="
                                color: #007bff !important;
                                margin-right: 6px !important;
                                display: inline !important;
                                visibility: visible !important;
                                opacity: 1 !important;
                            "></i> 아이파크 신규 체크
                        </span>
                    </label>
                `;
                
                // mobile-site-info 맨 위에 추가
                mobileSiteInfo.insertBefore(mobileIparkContainer, mobileSiteInfo.firstChild);
                console.log('📱 강제 아이파크 체크박스 생성 완료');
                
                // 모바일용 아이파크 설정 div 생성
                const mobileIparkSettingsDiv = document.createElement('div');
                mobileIparkSettingsDiv.id = 'mobileIparkSettingsDiv';
                mobileIparkSettingsDiv.style.cssText = `
                    display: none !important;
                    margin-top: 15px !important;
                    padding: 20px !important;
                    border-radius: 8px !important;
                    background: #f8f9fa !important;
                    border: 1px solid #ddd !important;
                    width: 100% !important;
                    box-sizing: border-box !important;
                `;
                mobileIparkSettingsDiv.innerHTML = `
                    <div style="margin-bottom: 15px;">
                        <h4 style="margin: 0 0 10px 0; font-size: 16px; color: #333;">
                            <i class="bi bi-gear-fill" style="margin-right: 8px; color: #007bff;"></i>
                            아이파크 판넬폭 설정
                        </h4>
                        <p style="margin: 0; font-size: 14px; color: #666;">아이파크 신규 설정 시 각 패널의 폭을 조정할 수 있습니다.</p>
                    </div>
                    <form>
                        <div style="display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap;">
                            <div style="flex: 1; min-width: 150px;">
                                <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #333;">39번 패널폭 (mm)</label>
                                <input type="number" id="mobileIparkPanel39Width" value="800" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                <small style="color: #666; font-size: 12px;">※ 기본값: 800mm</small>
                            </div>
                            <div style="flex: 1; min-width: 150px;">
                                <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #333;">6번 패널폭 (mm)</label>
                                <input type="number" id="mobileIparkPanel6Width" value="1000" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                <small style="color: #666; font-size: 12px;">※ 기본값: 1000mm</small>
                            </div>
                        </div>
                        <div style="display: flex; gap: 10px; justify-content: flex-end;">
                            <button type="button" id="mobileIparkCancelBtn" style="padding: 8px 16px; border: 1px solid #ddd; background: #f8f9fa; border-radius: 4px; cursor: pointer;">취소</button>
                            <button type="button" id="mobileIparkConfirmBtn" style="padding: 8px 16px; border: none; background: #007bff; color: white; border-radius: 4px; cursor: pointer;">설정 완료</button>
                        </div>
                    </form>
                `;
                
                // 아이파크 체크박스 컨테이너에 설정 div 추가
                mobileIparkContainer.appendChild(mobileIparkSettingsDiv);
                
                // PC 버전과 동기화
                setTimeout(() => {
                    const mobileIparkCheck = document.getElementById('mobileIparkCheck');
                    const pcIparkCheck = document.getElementById('iparkCheck');
                    
                    if (mobileIparkCheck && pcIparkCheck) {
                        // 초기 상태 동기화
                        mobileIparkCheck.checked = pcIparkCheck.checked;
                        
                        // 모바일 → PC 동기화 및 토글 기능 (중복 방지)
                        if (!mobileIparkCheck.hasAttribute('data-force-listener-added')) {
                            mobileIparkCheck.addEventListener('change', function() {
                                console.log('📱 강제 생성된 모바일 아이파크 체크박스 변경:', this.checked);
                                
                                // 모바일 아이파크 설정 div 토글
                                const mobileSettingsDiv = document.getElementById('mobileIparkSettingsDiv');
                                if (this.checked) {
                                    if (mobileSettingsDiv) {
                                        mobileSettingsDiv.style.display = 'block';
                                        console.log('📱 모바일 아이파크 설정 div 표시');
                                    }
                                } else {
                                    if (mobileSettingsDiv) {
                                        mobileSettingsDiv.style.display = 'none';
                                        console.log('📱 모바일 아이파크 설정 div 숨김');
                                    }
                                }
                                
                                // PC 버전과 동기화 (중복 방지)
                                if (!pcIparkCheck.hasAttribute('data-sync-in-progress')) {
                                    pcIparkCheck.setAttribute('data-sync-in-progress', 'true');
                                    pcIparkCheck.checked = this.checked;
                                    
                                    // PC 버전의 change 이벤트 발생
                                    const changeEvent = new Event('change', { bubbles: true });
                                    pcIparkCheck.dispatchEvent(changeEvent);
                                    
                                    // 플래그 해제
                                    setTimeout(() => {
                                        pcIparkCheck.removeAttribute('data-sync-in-progress');
                                    }, 100);
                                }
                            });
                            
                            // 중복 리스너 방지 플래그 설정
                            mobileIparkCheck.setAttribute('data-force-listener-added', 'true');
                            console.log('📱 강제 생성된 모바일 아이파크 체크박스 이벤트 리스너 등록 완료 (중복 방지)');
                        }
                        
                        // 모바일 아이파크 설정 버튼 이벤트
                        const mobileCancelBtn = document.getElementById('mobileIparkCancelBtn');
                        const mobileConfirmBtn = document.getElementById('mobileIparkConfirmBtn');
                        
                        if (mobileCancelBtn) {
                            mobileCancelBtn.addEventListener('click', function() {
                                const mobileSettingsDiv = document.getElementById('mobileIparkSettingsDiv');
                                if (mobileSettingsDiv) {
                                    mobileSettingsDiv.style.display = 'none';
                                    console.log('📱 모바일 아이파크 설정 취소');
                                }
                            });
                        }
                        
                        if (mobileConfirmBtn) {
                            mobileConfirmBtn.addEventListener('click', function() {
                                const mobileSettingsDiv = document.getElementById('mobileIparkSettingsDiv');
                                const mobilePanel39Width = document.getElementById('mobileIparkPanel39Width');
                                const mobilePanel6Width = document.getElementById('mobileIparkPanel6Width');
                                
                                if (mobileSettingsDiv) {
                                    // 입력값 검증
                                    const panel39Width = parseInt(mobilePanel39Width?.value) || 800;
                                    const panel6Width = parseInt(mobilePanel6Width?.value) || 1000;
                                    
                                    // localStorage에 저장 (PC 버전과 동일)
                                    localStorage.setItem('iparkPanel39Width', panel39Width);
                                    localStorage.setItem('iparkPanel6Width', panel6Width);
                                    console.log('💾 모바일 localStorage에 저장 완료 (강제 생성)');
                                    
                                    // PC 버전과 동일한 자동계산 적용
                                    if (typeof applyIparkAutoMeasurements === 'function') {
                                        applyIparkAutoMeasurements(panel39Width, panel6Width);
                                        console.log('🔄 모바일 자동계산 적용 완료 (강제 생성)');
                                    } else {
                                        console.error('❌ applyIparkAutoMeasurements 함수를 찾을 수 없습니다 (강제 생성)');
                                    }
                                    
                                    // 설정 div 숨기기
                                    mobileSettingsDiv.style.display = 'none';
                                    
                                    // 성공 메시지 (모바일용)
                                    console.log('📱 모바일 아이파크 설정 완료 (강제 생성):', {
                                        panel39: panel39Width,
                                        panel6: panel6Width
                                    });
                                    
                                    // 간단한 성공 알림
                                    if (typeof Swal !== 'undefined') {
                                        Swal.fire({
                                            icon: 'success',
                                            title: '설정 완료',
                                            text: `아이파크 자동계산이 적용되었습니다.\n(3,9번: ${panel39Width}mm, 6번: ${panel6Width}mm)`,
                                            timer: 2000,
                                            showConfirmButton: false,
                                            toast: true,
                                            position: 'top-end'
                                        });
                                    } else {
                                        alert(`아이파크 설정 완료!\n3,9번: ${panel39Width}mm, 6번: ${panel6Width}mm`);
                                    }
                                }
                            });
                        }
                        
                        console.log('📱 강제 생성된 아이파크 체크박스 동기화 및 토글 기능 완료');
                    }
                }, 100);
            }
            
            
            // 모바일 아이파크 체크박스와 PC 버전 동기화
            function syncMobileIparkCheckbox() {
                console.log('📱 모바일 아이파크 체크박스 동기화 시작');
                
                const pcIparkCheck = document.getElementById('iparkCheck');
                const mobileIparkCheck = document.getElementById('mobileIparkCheck');
                
                if (!pcIparkCheck || !mobileIparkCheck) {
                    console.log('📱 PC 또는 모바일 아이파크 체크박스를 찾을 수 없음');
                    return;
                }
                
                // 초기 상태 동기화
                mobileIparkCheck.checked = pcIparkCheck.checked;
                console.log('📱 초기 상태 동기화:', mobileIparkCheck.checked);
                
                // PC → 모바일 동기화
                pcIparkCheck.addEventListener('change', function() {
                    mobileIparkCheck.checked = this.checked;
                    console.log('📱 PC → 모바일 동기화:', this.checked);
                });
                
                // 모바일 → PC 동기화
                mobileIparkCheck.addEventListener('change', function() {
                    pcIparkCheck.checked = this.checked;
                    console.log('📱 모바일 → PC 동기화:', this.checked);
                    
                    // PC 버전의 change 이벤트 발생시켜서 모든 기능 작동
                    const changeEvent = new Event('change', { bubbles: true });
                    pcIparkCheck.dispatchEvent(changeEvent);
                });
                
                console.log('📱 아이파크 체크박스 동기화 완료');
            }
            
            // 🎯 PHASE 3: populateMobileCards 함수 비활성화 (반응형으로 통합됨)
            function populateMobileCards() {
                console.log('⚠️ DEPRECATED: populateMobileCards - 반응형 통합으로 더 이상 사용되지 않습니다.');
                return; // 즉시 종료
                
                // 플래그와 실제 DOM 상태 모두 체크 (일시적으로 비활성화하여 강제 실행)
                const shouldSkip = mobileCardsPopulated || isMobileCardAlreadyExists();
                
                if (shouldSkip) {
                    // 아이파크 체크박스가 없으면 강제로 생성
                    const mobileIparkCheck = document.getElementById('mobileIparkCheck');
                    if (!mobileIparkCheck) {
                        createMobileIparkCheckbox();
                    }
                    return;
                }
                
                // 모바일 카드 컨테이너 존재 확인
                const mobileCardsContainer = document.querySelector('.mobile-only-cards');
                if (!mobileCardsContainer) {
                    console.error('❌ 모바일 카드 컨테이너를 찾을 수 없음');
                    return;
                }
                
                // 기존 모바일 카드 내용 초기화
                let mobileSiteInfo = document.getElementById('mobile-site-info');
                const mobileDimensions = document.getElementById('mobile-dimensions');
                const mobilePanelViz = document.getElementById('mobile-panel-visualization');
                const mobileMeasurements = document.getElementById('mobile-measurements');
                const mobileMaterials = document.getElementById('mobile-materials');
                const mobileButtons = document.getElementById('mobile-buttons');
                
                // mobileSiteInfo가 없으면 생성
                if (!mobileSiteInfo) {
                    let mobileCard1 = document.querySelector('.mobile-card-1 .linear-card-body');
                    
                    // mobile-card-1이 없으면 mobile-only-cards 컨테이너에 직접 생성
                    if (!mobileCard1) {
                        const mobileCardsContainer = document.querySelector('.mobile-only-cards');
                        if (mobileCardsContainer) {
                            // mobile-card-1을 직접 생성
                            const mobileCard1Element = document.createElement('div');
                            mobileCard1Element.className = 'mobile-card-1';
                            mobileCard1Element.innerHTML = `
                                <div class="linear-card">
                                    <div class="linear-card-header">
                                        <h3 class="linear-card-title"><i class="bi bi-building"></i> 현장 정보 및 측정값</h3>
                                    </div>
                                    <div class="linear-card-body">
                                        <div id="mobile-site-info"></div>
                                    </div>
                                </div>
                            `;
                            mobileCardsContainer.insertBefore(mobileCard1Element, mobileCardsContainer.firstChild);
                            mobileCard1 = mobileCard1Element.querySelector('.linear-card-body');
                        } else {
                            console.error('❌ mobile-only-cards 컨테이너도 찾을 수 없습니다');
                            return;
                        }
                    }
                    
                    if (mobileCard1) {
                        mobileSiteInfo = document.createElement('div');
                        mobileSiteInfo.id = 'mobile-site-info';
                        mobileSiteInfo.style.cssText = 'display: block !important; visibility: visible !important; opacity: 1 !important; width: 100% !important;';
                        mobileCard1.insertBefore(mobileSiteInfo, mobileCard1.firstChild);
                    } else {
                        console.error('❌ mobile-card-1 생성 실패');
                        return;
                    }
                }
                
                if (mobileSiteInfo) mobileSiteInfo.innerHTML = '';
                if (mobileDimensions) mobileDimensions.innerHTML = '';
                if (mobilePanelViz) mobilePanelViz.innerHTML = '';
                if (mobileMeasurements) mobileMeasurements.innerHTML = '';
                if (mobileMaterials) mobileMaterials.innerHTML = '';
                if (mobileButtons) mobileButtons.innerHTML = '';
                
                // 1. 현장정보 섹션 복사 제거 (이미 모바일 카드에 현장정보가 있으므로)
                if (false && siteInfoSection && mobileSiteInfo) { // 현장정보 섹션 복사 비활성화
                    console.log('📱 현장정보 섹션 복사 시작');
                    const siteInfoClone = siteInfoSection.cloneNode(true);
                    
                    // 모바일 복사본에서 중복 요소들 제거
                    const iparkCheckInClone = siteInfoClone.querySelector('#iparkCheckContainer');
                    if (iparkCheckInClone) {
                        console.log('📱 모바일 복사본에서 아이파크 체크박스 제거');
                        iparkCheckInClone.remove();
                    }
                    
                    // 중복된 현장명, 측정일, 측정자 필드 제거 (이미 모바일 카드에 있으므로)
                    const duplicateFields = siteInfoClone.querySelectorAll('input[name="site_name"], input[name="measurement_date"], input[name="measurer"]');
                    duplicateFields.forEach(field => {
                        console.log('📱 중복 필드 제거:', field.name);
                        // 부모 요소(linear-input-group)도 함께 제거
                        const parentGroup = field.closest('.linear-input-group');
                        if (parentGroup) {
                            parentGroup.remove();
                        } else {
                            field.remove();
                        }
                    });
                    
                    // 현장정보 관련 전체 섹션도 제거 (중복 방지)
                    const siteInfoFields = siteInfoClone.querySelectorAll('input[name="site_name"], input[name="measurement_date"], input[name="measurer"]');
                    if (siteInfoFields.length === 0) {
                        console.log('📱 모든 현장정보 필드가 제거됨 - 섹션 자체를 제거');
                        // 현장정보 섹션이 비어있으면 전체 섹션 제거
                        const emptySection = siteInfoClone.querySelector('.form-section');
                        if (emptySection && emptySection.children.length === 0) {
                            siteInfoClone.remove();
                            console.log('📱 빈 현장정보 섹션 제거 완료');
                        }
                    }
                    
                    mobileSiteInfo.appendChild(siteInfoClone);
                    console.log('📱 현장정보 섹션 복사 완료 (아이파크 체크박스 제외)');
                    
                    // 모바일 카드에 아이파크 체크박스 추가 (PC 버전과 동기화)
                    const mobileIparkContainer = document.createElement('div');
                    mobileIparkContainer.id = 'mobileIparkCheckContainer';
                    mobileIparkContainer.style.cssText = 'margin-bottom: var(--linear-spacing-lg);';
                    
                    // PC 버전 아이파크 체크박스 복사
                    const originalIparkContainer = document.getElementById('iparkCheckContainer');
                    if (originalIparkContainer) {
                        const mobileIparkClone = originalIparkContainer.cloneNode(true);
                        
                        // ID 변경하여 중복 방지
                        mobileIparkClone.id = 'mobileIparkCheckContainer';
                        const mobileIparkCheck = mobileIparkClone.querySelector('#iparkCheck');
                        const mobileIparkLabel = mobileIparkClone.querySelector('#iparkCheckLabel');
                        
                        if (mobileIparkCheck) {
                            mobileIparkCheck.id = 'mobileIparkCheck';
                            mobileIparkCheck.name = 'mobile_ipark_check';
                        }
                        if (mobileIparkLabel) {
                            mobileIparkLabel.id = 'mobileIparkCheckLabel';
                            mobileIparkLabel.setAttribute('for', 'mobileIparkCheck');
                        }
                        
                        // mobileIparkContainer가 정의되지 않았으므로 직접 mobileSiteInfo에 추가
                        if (mobileSiteInfo) {
                            try {
                                mobileSiteInfo.insertBefore(mobileIparkClone, mobileSiteInfo.firstChild);
                                console.log('📱 mobileIparkClone 추가 성공');
                            } catch (error) {
                                console.error('📱 mobileIparkClone 추가 실패:', error);
                                // 대안: appendChild 사용
                                try {
                                    mobileSiteInfo.appendChild(mobileIparkClone);
                                    console.log('📱 mobileIparkClone appendChild로 추가 성공');
                                } catch (appendError) {
                                    console.error('📱 mobileIparkClone appendChild도 실패:', appendError);
                                }
                            }
                        } else {
                            console.error('📱 mobileSiteInfo가 null입니다');
                        }
                        
                        console.log('📱 모바일 아이파크 체크박스 추가 완료');
                        
                        // 모바일 아이파크 체크박스와 PC 버전 동기화
                        setTimeout(() => {
                            syncMobileIparkCheckbox();
                            
                            // 모바일 아이파크 체크박스가 제대로 표시되는지 확인
                            const mobileIparkCheck = document.getElementById('mobileIparkCheck');
                            const mobileIparkCheckContainer = document.getElementById('mobileIparkCheckContainer');
                            if (mobileIparkCheck && mobileIparkCheckContainer) {
                                console.log('📱 모바일 아이파크 체크박스 생성 완료 및 표시');
                                mobileIparkCheck.style.display = 'block';
                                mobileIparkCheckContainer.style.display = 'block';
                            } else {
                                console.error('📱 모바일 아이파크 체크박스 생성 실패');
                            }
                        }, 100);
                    }
                } else {
                    console.log('📱 현장정보 섹션 복사 생략 - 아이파크 체크박스만 추가');
                    
                    // 모바일 카드에 아이파크 체크박스와 현장정보 필드들 추가
                    if (mobileSiteInfo) {
                        console.log('📱 모바일 카드에 아이파크 체크박스와 현장정보 추가');
                        
                        // 1. 아이파크 체크박스 추가
                        const mobileIparkContainer = document.createElement('div');
                        mobileIparkContainer.id = 'mobileIparkCheckContainer';
                        mobileIparkContainer.style.cssText = 'margin-bottom: var(--linear-spacing-lg);';
                        
                        // PC 버전 아이파크 체크박스 복사
                        const originalIparkContainer = document.getElementById('iparkCheckContainer');
                        
                        if (originalIparkContainer) {
                            const mobileIparkClone = originalIparkContainer.cloneNode(true);
                            
                            // ID 변경하여 모바일 버전으로 만들기
                            const mobileIparkCheck = mobileIparkClone.querySelector('#iparkCheck');
                            if (mobileIparkCheck) {
                                mobileIparkCheck.id = 'mobileIparkCheck';
                                mobileIparkCheck.name = 'mobile_ipark_check';
                            } else {
                                console.error('❌ 모바일 아이파크 체크박스를 찾을 수 없음');
                            }
                            
                            const mobileIparkLabel = mobileIparkClone.querySelector('#iparkCheckLabel');
                            if (mobileIparkLabel) {
                                mobileIparkLabel.id = 'mobileIparkCheckLabel';
                                mobileIparkLabel.setAttribute('for', 'mobileIparkCheck');
                            } else {
                                console.error('❌ 모바일 아이파크 라벨을 찾을 수 없음');
                            }
                            
                            // mobileIparkContainer가 정의되지 않았으므로 직접 mobileSiteInfo에 추가
                            if (mobileSiteInfo) {
                                try {
                                    mobileSiteInfo.appendChild(mobileIparkClone);
                                    console.log('✅ 모바일 아이파크 체크박스 추가 성공');
                                } catch (error) {
                                    console.error('❌ 모바일 아이파크 체크박스 추가 실패:', error);
                                }
                            } else {
                                console.error('❌ mobileSiteInfo가 null입니다');
                            }
                        } else {
                            console.error('❌ PC 아이파크 컨테이너를 찾을 수 없음');
                            
                            // PC 아이파크 컨테이너가 없으면 직접 생성
                            const mobileIparkContainer = document.createElement('div');
                            mobileIparkContainer.id = 'mobileIparkCheckContainer';
                            mobileIparkContainer.style.cssText = 'margin-bottom: var(--linear-spacing-lg);';
                            mobileIparkContainer.innerHTML = `
                                <div class="linear-input-group">
                                    <label class="linear-label" for="mobileIparkCheck" id="mobileIparkCheckLabel">
                                        <input type="checkbox" id="mobileIparkCheck" name="mobile_ipark_check" class="linear-checkbox">
                                        <span class="linear-checkbox-mark"></span>
                                        아이파크 신규
                                    </label>
                                </div>
                            `;
                            if (mobileSiteInfo) {
                                try {
                                    mobileSiteInfo.appendChild(mobileIparkContainer);
                                    console.log('✅ 모바일 아이파크 체크박스 직접 생성 성공');
                                } catch (error) {
                                    console.error('❌ 모바일 아이파크 체크박스 직접 생성 실패:', error);
                                }
                            } else {
                                console.error('❌ mobileSiteInfo가 null입니다');
                            }
                        }
                        
                        // 2. 현장정보 필드들 추가 (현장명, 측정일자, 측정자)
                        const siteInfoSection = Array.from(document.querySelectorAll('.form-section')).find(section => {
                            const hasSiteName = section.querySelector('input[name="site_name"]');
                            const hasMeasurementDate = section.querySelector('input[name="measurement_date"]');
                            const hasMeasurer = section.querySelector('input[name="measurer"]');
                            return hasSiteName && hasMeasurementDate && hasMeasurer;
                        });
                        
                        // 🎯 PHASE 2: 현장정보 필드 복사 비활성화 (반응형으로 통합됨)
                        if (false && siteInfoSection) {
                            const siteInfoClone = siteInfoSection.cloneNode(true);
                            
                            // 아이파크 체크박스는 제거 (이미 추가했으므로)
                            const iparkCheckInClone = siteInfoClone.querySelector('#iparkCheckContainer');
                            if (iparkCheckInClone) {
                                iparkCheckInClone.remove();
                            }
                            
                            if (mobileSiteInfo) {
                                try {
                                    mobileSiteInfo.appendChild(siteInfoClone);
                                    console.log('✅ 현장정보 필드들 복사 완료');
                                } catch (error) {
                                    console.error('❌ 현장정보 필드들 복사 실패:', error);
                                }
                            }
                        }
                        
                        // 🎯 PHASE 2: W×D×H 섹션 복사 비활성화 (반응형으로 통합됨)
                        if (false) {
                            const dimensionsSection = Array.from(document.querySelectorAll('.form-section')).find(section => 
                                section.querySelector('.form-section-title')?.textContent.includes('카 내부 W x D x H')
                            );
                            if (dimensionsSection && mobileSiteInfo) {
                                try {
                                    const dimensionsClone = dimensionsSection.cloneNode(true);
                                    mobileSiteInfo.appendChild(dimensionsClone);
                                    console.log('✅ W×D×H 섹션 추가 완료');
                                } catch (error) {
                                    console.error('❌ W×D×H 섹션 추가 실패:', error);
                                }
                            }
                        }
                        
                        // 🎯 PHASE 2: 재질 정보 복사 비활성화 (반응형으로 통합됨)
                        if (false) {
                            const materialsSection = Array.from(document.querySelectorAll('.form-section')).find(section => 
                                section.querySelector('.form-section-title')?.textContent.includes('재질 정보')
                            );
                            if (materialsSection && mobileSiteInfo) {
                                try {
                                    const materialsClone = materialsSection.cloneNode(true);
                                    mobileSiteInfo.appendChild(materialsClone);
                                    console.log('✅ 재질 정보 추가 완료');
                                } catch (error) {
                                    console.error('❌ 재질 정보 추가 실패:', error);
                                }
                            }
                        }
                        
                        // 모바일 아이파크 체크박스 표시 및 동기화
                        setTimeout(() => {
                            const mobileIparkCheck = document.getElementById('mobileIparkCheck');
                            const mobileIparkCheckContainer = document.getElementById('mobileIparkCheckContainer');
                            
                            if (mobileIparkCheck && mobileIparkCheckContainer) {
                                console.log('📱 모바일 아이파크 체크박스 생성 완료');
                                mobileIparkCheck.style.display = 'block';
                                mobileIparkCheckContainer.style.display = 'block';
                                
                                // PC 버전과 동기화
                                syncMobileIparkCheckbox();
                                
                        // 모바일 아이파크 체크박스에 직접 이벤트 리스너 추가 (중복 방지)
                        if (!mobileIparkCheck.hasAttribute('data-mobile-listener-added')) {
                            mobileIparkCheck.addEventListener('change', function() {
                                console.log('📱 모바일 아이파크 체크박스 변경:', this.checked);
                                
                                // 모바일 아이파크 설정 div 토글
                                const mobileSettingsDiv = document.getElementById('mobileIparkSettingsDiv');
                                if (this.checked) {
                                    if (mobileSettingsDiv) {
                                        mobileSettingsDiv.style.display = 'block';
                                        console.log('📱 모바일 아이파크 설정 div 표시');
                                    }
                                } else {
                                    if (mobileSettingsDiv) {
                                        mobileSettingsDiv.style.display = 'none';
                                        console.log('📱 모바일 아이파크 설정 div 숨김');
                                    }
                                }
                                
                                // PC 버전과 동기화 (중복 방지를 위해 플래그 설정)
                                const pcIparkCheck = document.getElementById('iparkCheck');
                                if (pcIparkCheck && !pcIparkCheck.hasAttribute('data-sync-in-progress')) {
                                    pcIparkCheck.setAttribute('data-sync-in-progress', 'true');
                                    pcIparkCheck.checked = this.checked;
                                    
                                    // PC 버전의 change 이벤트 발생시켜서 모든 기능 작동
                                    const changeEvent = new Event('change', { bubbles: true });
                                    pcIparkCheck.dispatchEvent(changeEvent);
                                    
                                    // 플래그 해제
                                    setTimeout(() => {
                                        pcIparkCheck.removeAttribute('data-sync-in-progress');
                                    }, 100);
                                }
                            });
                            
                            // 중복 리스너 방지 플래그 설정
                            mobileIparkCheck.setAttribute('data-mobile-listener-added', 'true');
                            console.log('📱 모바일 아이파크 체크박스 이벤트 리스너 등록 완료 (중복 방지)');
                        }
                        
                        // 모바일 아이파크 설정 버튼 이벤트 추가
                        const mobileCancelBtn = document.getElementById('mobileIparkCancelBtn');
                        const mobileConfirmBtn = document.getElementById('mobileIparkConfirmBtn');
                        
                        if (mobileCancelBtn) {
                            mobileCancelBtn.addEventListener('click', function() {
                                const mobileSettingsDiv = document.getElementById('mobileIparkSettingsDiv');
                                if (mobileSettingsDiv) {
                                    mobileSettingsDiv.style.display = 'none';
                                    console.log('📱 모바일 아이파크 설정 취소');
                                }
                            });
                        }
                        
                        if (mobileConfirmBtn) {
                            mobileConfirmBtn.addEventListener('click', function() {
                                const mobileSettingsDiv = document.getElementById('mobileIparkSettingsDiv');
                                const mobilePanel39Width = document.getElementById('mobileIparkPanel39Width');
                                const mobilePanel6Width = document.getElementById('mobileIparkPanel6Width');
                                
                                if (mobileSettingsDiv) {
                                    // 입력값 검증
                                    const panel39Width = parseInt(mobilePanel39Width?.value) || 800;
                                    const panel6Width = parseInt(mobilePanel6Width?.value) || 1000;
                                    
                                    // localStorage에 저장 (PC 버전과 동일)
                                    localStorage.setItem('iparkPanel39Width', panel39Width);
                                    localStorage.setItem('iparkPanel6Width', panel6Width);
                                    console.log('💾 모바일 localStorage에 저장 완료');
                                    
                                    // PC 버전과 동일한 자동계산 적용
                                    if (typeof applyIparkAutoMeasurements === 'function') {
                                        applyIparkAutoMeasurements(panel39Width, panel6Width);
                                        console.log('🔄 모바일 자동계산 적용 완료');
                                    } else {
                                        console.error('❌ applyIparkAutoMeasurements 함수를 찾을 수 없습니다');
                                    }
                                    
                                    // 설정 div 숨기기
                                    mobileSettingsDiv.style.display = 'none';
                                    
                                    // 성공 메시지 (모바일용)
                                    console.log('📱 모바일 아이파크 설정 완료:', {
                                        panel39: panel39Width,
                                        panel6: panel6Width
                                    });
                                    
                                    // 간단한 성공 알림
                                    if (typeof Swal !== 'undefined') {
                                        Swal.fire({
                                            icon: 'success',
                                            title: '설정 완료',
                                            text: `아이파크 자동계산이 적용되었습니다.\n(3,9번: ${panel39Width}mm, 6번: ${panel6Width}mm)`,
                                            timer: 2000,
                                            showConfirmButton: false,
                                            toast: true,
                                            position: 'top-end'
                                        });
                                    } else {
                                        alert(`아이파크 설정 완료!\n3,9번: ${panel39Width}mm, 6번: ${panel6Width}mm`);
                                    }
                                }
                            });
                        }
                            } else {
                                console.error('📱 모바일 아이파크 체크박스 생성 실패');
                            }
                        }, 100);
                    }
                }
                
                // 2. W×D×H와 재질 정보는 이미 mobile-site-info에 추가했으므로 여기서는 생략
                
                // 3. 판넬 시각화 복사 (전체 카드)
                const panelVizSection = document.querySelector('.car-wall-section');
                if (panelVizSection) {
                    const panelVizClone = panelVizSection.cloneNode(true);
                    const mobilePanelVizContainer = document.getElementById('mobile-panel-visualization');
                    if (mobilePanelVizContainer) {
                        try {
                            mobilePanelVizContainer.appendChild(panelVizClone);
                            console.log('✅ 판넬 시각화 섹션 복사 완료');
                            
                            // 트랜섬 패널에 기본 'T' 텍스트 초기화
                            setTimeout(() => {
                                const mobileTransomPanel = mobilePanelVizContainer.querySelector('.panel-12');
                                if (mobileTransomPanel && (!window.panelData || !window.panelData['12']) &&
                                    !mobileTransomPanel.classList.contains('has-info') &&
                                    mobileTransomPanel.children.length === 0) {
                                    mobileTransomPanel.textContent = 'T';
                                    console.log('✅ 모바일 트랜섬 패널 T 초기화 완료');
                                }
                            }, 100);
                        } catch (error) {
                            console.error('❌ 판넬 시각화 섹션 복사 실패:', error);
                        }
                    }
                }
                
                // 4. 측정값 섹션은 제거됨 (JSON으로 대체)
                
                // 6. 버튼 섹션 복사 - 안전한 처리
                const buttonsSection = document.querySelector('form > div:last-child');
                const mobileButtonsContainer = document.getElementById('mobile-buttons');

                if (buttonsSection && mobileButtonsContainer) {
                    try {
                        const buttonsClone = buttonsSection.cloneNode(true);
                        mobileButtonsContainer.appendChild(buttonsClone);
                        console.log('✅ 버튼 섹션 복사 완료');
                    } catch (error) {
                        console.error('❌ 버튼 섹션 복사 실패:', error);
                    }
                } else {
                    console.error('❌ 버튼 섹션 또는 mobile-buttons 컨테이너를 찾을 수 없음');
                }
                
                // hidden fields 복사 - 안전한 처리
                const hiddenFields = document.querySelectorAll('input[type="hidden"]');
                if (hiddenFields.length > 0 && mobileButtonsContainer) {
                    hiddenFields.forEach(field => {
                        try {
                            const hiddenClone = field.cloneNode(true);
                            mobileButtonsContainer.appendChild(hiddenClone);
                        } catch (error) {
                            console.error('❌ hidden field 복사 실패:', error);
                        }
                    });
                    console.log('✅ hidden fields 복사 완료');
                } else {
                    console.error('❌ mobile-buttons 컨테이너를 찾을 수 없음 - hidden fields 복사 생략');
                }
                
                // 모바일 카드의 날짜 입력에 간단한 달력 기능 추가
                setTimeout(() => {
                    const mobileDateInput = document.querySelector('#mobile-site-info input[name="measurement_date"]');
                    
                    if (mobileDateInput) {
                        // 모바일에서는 PC 버전 달력을 공유하는 방식으로 단순화
                        mobileDateInput.addEventListener('click', function(e) {
                            e.preventDefault();
                            
                            // PC 버전 달력 요소들 가져오기
                            const pcDateInput = document.getElementById('measurementDate');
                            const pcDatePicker = document.getElementById('datePicker');
                            
                            if (pcDatePicker && pcDateInput) {
                                // 현재 모바일 입력값을 PC 입력에 동기화
                                if (mobileDateInput.value) {
                                    pcDateInput.value = mobileDateInput.value;
                                }
                                
                                // PC 달력 표시
                                pcDatePicker.style.display = 'block';
                                pcDatePicker.style.position = 'fixed';
                                pcDatePicker.style.top = '50%';
                                pcDatePicker.style.left = '50%';
                                pcDatePicker.style.transform = 'translate(-50%, -50%)';
                                pcDatePicker.style.zIndex = '9999';
                                pcDatePicker.style.width = '280px';
                                
                                // 배경 오버레이 추가
                                const overlay = document.createElement('div');
                                overlay.id = 'mobile-calendar-overlay';
                                overlay.style.cssText = `
                                    position: fixed;
                                    top: 0;
                                    left: 0;
                                    width: 100%;
                                    height: 100%;
                                    background: rgba(0,0,0,0.5);
                                    z-index: 9998;
                                `;
                                document.body.appendChild(overlay);
                                
                                // 날짜 선택 시 모바일 입력 업데이트하는 임시 핸들러
                                const originalClickHandler = function(event) {
                                    if (event.target.classList.contains('date-picker-day') && 
                                        !event.target.classList.contains('other-month')) {
                                        
                                        // 선택된 날짜 직접 계산
                                        const selectedDay = parseInt(event.target.textContent);
                                        const monthYearText = document.querySelector('#datePicker .date-picker-title').textContent;
                                        const yearMatch = monthYearText.match(/(\d{4})년/);
                                        const monthMatch = monthYearText.match(/(\d{1,2})월/);
                                        
                                        if (yearMatch && monthMatch) {
                                            const year = yearMatch[1];
                                            const month = String(monthMatch[1]).padStart(2, '0');
                                            const day = String(selectedDay).padStart(2, '0');
                                            const formattedDate = `${year}-${month}-${day}`;

                                            // 모바일과 PC 입력 모두 업데이트
                                            mobileDateInput.value = formattedDate;
                                            pcDateInput.value = formattedDate;
                                            
                                            // 달력 닫기
                                            pcDatePicker.style.display = 'none';
                                            pcDatePicker.style.position = 'absolute';
                                            pcDatePicker.style.transform = 'none';
                                            pcDatePicker.style.top = '100%';
                                            pcDatePicker.style.left = '0';
                                            pcDatePicker.style.width = 'auto';
                                            
                                            // 오버레이 제거
                                            const overlayEl = document.getElementById('mobile-calendar-overlay');
                                            if (overlayEl) overlayEl.remove();
                                            
                                            // 이벤트 리스너 제거
                                            pcDatePicker.removeEventListener('click', originalClickHandler);
                                        }
                                    }
                                };
                                
                                // 달력에 클릭 이벤트 추가
                                pcDatePicker.addEventListener('click', originalClickHandler);
                                
                                // 오버레이 클릭 시 달력 닫기
                                overlay.addEventListener('click', function() {
                                    pcDatePicker.style.display = 'none';
                                    pcDatePicker.style.position = 'absolute';
                                    pcDatePicker.style.transform = 'none';
                                    pcDatePicker.style.top = '100%';
                                    pcDatePicker.style.left = '0';
                                    pcDatePicker.style.width = 'auto';
                                    overlay.remove();
                                    pcDatePicker.removeEventListener('click', originalClickHandler);
                                });
                                
                            }
                        });
                        
                        // 터치 이벤트도 동일하게 처리
                        mobileDateInput.addEventListener('touchend', function(e) {
                            e.preventDefault();
                            mobileDateInput.click();
                        });
                    }
                }, 1000); // 더 늘려서 확실히 DOM 로딩 완료 후 실행
                
                // PC 버전 요소들 명시적으로 숨기기 (모바일에서)
                hidePCVersionElements();
                
                // 모바일 카드 생성 완료 플래그 설정
                mobileCardsPopulated = true;
                console.log('📱 모바일 카드 생성 완료 - mobileCardsPopulated = true');

                // 모바일 입력 필드에 이벤트 리스너 추가 (데이터 수집을 위해)
                setTimeout(() => {
                    addMobileInputEventListeners();
                }, 1500);

                // 모바일 버튼들에 이벤트 리스너 추가
                setTimeout(() => {
                    addMobileButtonEventListeners();
                }, 2000);

                // 모바일 카드가 생성된 후 판넬 동기화
                if (window.syncMobilePanels) {
                    window.syncMobilePanels();
                }
            }
            
            // 모바일에서만 실행 (중복 방지)
            if (window.innerWidth <= 768 && !mobileCardsPopulated) {
                console.log('📱 초기 모바일 환경 감지 - 카드 생성');
                console.log('📱 창 크기:', window.innerWidth, 'x', window.innerHeight);
                
                // 먼저 PC 버전 요소들 숨기기
                hidePCVersionElements();
                
                // 모바일 카드 강제 표시
                const mobileCardsContainer = document.querySelector('.mobile-only-cards');
                if (mobileCardsContainer) {
                    mobileCardsContainer.style.display = 'block';
                    console.log('📱 모바일 카드 컨테이너 강제 표시');
                    
                    // 모바일 버튼 이벤트 설정
                    setTimeout(() => {
                        if (typeof setupMobileButtonEvents === 'function') {
                            setupMobileButtonEvents();
                        }
                    }, 300);
                }
                
                // 강제로 모바일 카드 생성
                console.log('📱 populateMobileCards 강제 호출');
                populateMobileCards();
                
                // 즉시 아이파크 체크박스 생성 시도
                setTimeout(() => {
                    console.log('📱 즉시 아이파크 체크박스 생성 시도');
                    createMobileIparkCheckbox();
                }, 100);
                
                // 모바일 카드 내부 아이파크 체크박스 강화
                setTimeout(() => {
                    console.log('📱 모바일 카드 내부 아이파크 체크박스 강화');
                    ensureMobileIparkCheckboxInCard();
                }, 300);
                
                // 추가 강제 생성 (1초 후)
                setTimeout(() => {
                    console.log('📱 1초 후 추가 아이파크 체크박스 생성');
                    forceCreateMobileIparkCheckbox();
                }, 1000);
                
                // 모바일 카드 내부에 자연스럽게 통합 (긴급 체크박스 제거)
                
                // 모바일 아이파크 체크박스가 생성되었는지 여러 번 확인
                let retryCount = 0;
                const maxRetries = 5;
                
                const checkMobileIparkCheckbox = () => {
                    const mobileIparkCheck = document.getElementById('mobileIparkCheck');
                    const mobileIparkCheckContainer = document.getElementById('mobileIparkCheckContainer');
                    
                    console.log('📱 모바일 아이파크 체크박스 확인 시도:', retryCount + 1);
                    console.log('📱 mobileIparkCheck:', mobileIparkCheck);
                    console.log('📱 mobileIparkCheckContainer:', mobileIparkCheckContainer);
                    
                    if (!mobileIparkCheck && retryCount < maxRetries) {
                        console.warn('📱 모바일 아이파크 체크박스가 생성되지 않음 - 재시도', retryCount + 1);
                        retryCount++;
                        
                        // 강제로 다시 생성
                        populateMobileCards();
                        
                        setTimeout(checkMobileIparkCheckbox, 1000);
                    } else if (mobileIparkCheck) {
                        console.log('📱 모바일 아이파크 체크박스 확인 완료');
                    } else {
                        console.error('📱 모바일 아이파크 체크박스 생성 최종 실패');
                    }
                };
                
                // 첫 번째 확인을 500ms 후에 실행
                setTimeout(checkMobileIparkCheckbox, 500);
                
                // 추가 안전장치: 2초 후에 강제로 아이파크 체크박스 생성
                setTimeout(() => {
                    const mobileIparkCheck = document.getElementById('mobileIparkCheck');
                    if (!mobileIparkCheck) {
                        console.log('📱 2초 후 강제 아이파크 체크박스 생성');
                        createMobileIparkCheckbox();
                        
                        // 모바일 카드 내부에서 재시도
                        setTimeout(() => {
                            const mobileIparkCheck2 = document.getElementById('mobileIparkCheck');
                            if (!mobileIparkCheck2) {
                                console.log('📱 모바일 카드 내부에서 아이파크 체크박스 재생성');
                                createMobileIparkCheckbox();
                            }
                        }, 1000);
                    }
                }, 2000);
                
            } else if (window.innerWidth > 768) {
                console.log('🖥️ 초기 PC 환경 감지 - PC 버전 요소들 표시');
                
                // PC 버전 요소들 표시
                showPCVersionElements();
            }
            
            // 창 크기 변경 시 처리 (디바운싱 적용)
            let resizeTimeout;
            window.addEventListener('resize', function() {
                console.log('📱 창 크기 변경 감지:', window.innerWidth, 'mobileCardsPopulated:', mobileCardsPopulated);
                
                // 디바운싱: 300ms 후에 실행
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(() => {
                    console.log('📱 resize 디바운싱 후 실행:', window.innerWidth);
                    
                    if (window.innerWidth <= 768 && !mobileCardsPopulated) {
                        console.log('📱 모바일 환경으로 변경 - 카드 생성');
                        
                        // PC 버전 요소들 숨기기
                        hidePCVersionElements();
                        
                        populateMobileCards();
                        
                        // 강제로 아이파크 체크박스 생성
                        setTimeout(() => {
                            forceCreateMobileIparkCheckbox();
                        }, 500);
                    } else if (window.innerWidth > 768) {
                        console.log('🖥️ PC 환경으로 변경 - 플래그 리셋');
                        mobileCardsPopulated = false;
                        
                        // PC 버전 요소들 표시
                        showPCVersionElements();
                    }
                    
                    // 모바일 버튼 이벤트 설정 (모바일 환경에서만)
                    if (window.innerWidth <= 768) {
                        setTimeout(() => {
                            if (typeof setupMobileButtonEvents === 'function') {
                                setupMobileButtonEvents();
                            }
                        }, 500);
                    }
                }, 300);
            });
        });
    </script>
    
    <!-- Theme Toggle Implementation -->
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
    </script>
    
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
                                <select class="linear-input" id="modalMaterialType" name="material_type" readonly style="background-color: var(--linear-bg-secondary); cursor: not-allowed;">
                                    <option value="">재질을 선택하세요</option>
                                    <option value="SUS H/L">SUS H/L</option>
                                    <option value="SUS MR">SUS MR</option>
                                    <option value="강판">강판</option>
                                    <option value="도장품">도장품</option>
                                    <option value="시트지">시트지</option>
                                    <option value="기타">기타</option>
                                </select>
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
                        <label class="panel-modal-label">크기 (W폭×H높이) mm</label>
                        <div style="display: grid; grid-template-columns: 104px auto 104px; gap: var(--linear-spacing-sm); align-items: center; justify-content: start;">
                            <input type="number" class="linear-input" id="modalPanelWidth" name="panel_width" placeholder="가로" min="50" max="3000" step="1">
                            <span style="color: var(--linear-text-secondary);">×</span>
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
                        <textarea class="linear-input" id="modalPanelNotes" name="panel_notes" rows="3" placeholder="특이사항, 추가 정보 등"></textarea>
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


    <style>
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
                padding: var(--linear-spacing-md);
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

            .panel-modal-footer {
                flex-direction: column;
            }

            .panel-modal-footer button {
                width: 100%;
            }
        }

        /* SweetAlert2 Linear Design System Integration */
        .linear-swal-popup {
            border-radius: var(--linear-border-radius-lg) !important;
            padding: 0 !important;
            border: 1px solid var(--linear-border-primary) !important;
            background-color: var(--linear-bg-primary) !important;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25) !important;
        }

        .linear-swal-popup .swal2-header {
            padding: var(--linear-spacing-lg) var(--linear-spacing-lg) var(--linear-spacing-md) !important;
            border-bottom: none !important;
        }

        .linear-swal-popup .swal2-title {
            color: var(--linear-text-primary) !important;
            font-size: var(--linear-text-lg) !important;
            font-weight: var(--linear-font-weight-semibold) !important;
            margin: 0 !important;
        }

        .linear-swal-popup .swal2-content {
            color: var(--linear-text-secondary) !important;
            font-size: var(--linear-text-base) !important;
            padding: 0 var(--linear-spacing-lg) var(--linear-spacing-md) !important;
        }

        .linear-swal-popup .swal2-html-container {
            color: var(--linear-text-secondary) !important;
            line-height: 1.5 !important;
        }

        .linear-swal-popup .swal2-actions {
            padding: var(--linear-spacing-md) var(--linear-spacing-lg) var(--linear-spacing-lg) !important;
            gap: var(--linear-spacing-md) !important;
            border-top: 1px solid var(--linear-border-secondary) !important;
            margin-top: var(--linear-spacing-md) !important;
        }

        .linear-swal-popup .swal2-loading {
            border-color: var(--linear-primary-500) !important;
            border-top-color: transparent !important;
        }

        /* Icon Colors for Different Alert Types */
        .linear-swal-popup .swal2-icon.swal2-success {
            border-color: var(--linear-success-border) !important;
            color: var(--linear-success-text) !important;
        }

        .linear-swal-popup .swal2-icon.swal2-error {
            border-color: var(--linear-danger-border) !important;
            color: var(--linear-danger-text) !important;
        }

        .linear-swal-popup .swal2-icon.swal2-warning {
            border-color: var(--linear-warning-border) !important;
            color: var(--linear-warning-text) !important;
        }

        .linear-swal-popup .swal2-icon.swal2-info {
            border-color: var(--linear-info-border) !important;
            color: var(--linear-info-text) !important;
        }

        /* Dark Mode Support */
        [data-theme="dark"] .linear-swal-popup {
            background-color: var(--linear-bg-primary) !important;
            border-color: var(--linear-border-primary) !important;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5) !important;
        }

        [data-theme="dark"] .linear-swal-popup .swal2-title {
            color: var(--linear-text-primary) !important;
        }

        [data-theme="dark"] .linear-swal-popup .swal2-content,
        [data-theme="dark"] .linear-swal-popup .swal2-html-container {
            color: var(--linear-text-secondary) !important;
        }

        [data-theme="dark"] .linear-swal-popup .swal2-actions {
            border-top-color: var(--linear-border-secondary) !important;
        }

        [data-theme="dark"] .linear-swal-popup .swal2-loading {
            border-color: var(--linear-primary-400) !important;
        }

        /* Auto Dark Mode Support (system preference) */
        @media (prefers-color-scheme: dark) {
            body:not([data-theme="light"]) .linear-swal-popup {
                background-color: var(--linear-bg-primary) !important;
                border-color: var(--linear-border-primary) !important;
                box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5) !important;
            }

            body:not([data-theme="light"]) .linear-swal-popup .swal2-title {
                color: var(--linear-text-primary) !important;
            }

            body:not([data-theme="light"]) .linear-swal-popup .swal2-content,
            body:not([data-theme="light"]) .linear-swal-popup .swal2-html-container {
                color: var(--linear-text-secondary) !important;
            }

            body:not([data-theme="light"]) .linear-swal-popup .swal2-actions {
                border-top-color: var(--linear-border-secondary) !important;
            }

            body:not([data-theme="light"]) .linear-swal-popup .swal2-loading {
                border-color: var(--linear-primary-400) !important;
            }
        }

        /* Button Colors in SweetAlert2 */
        .linear-swal-popup .linear-btn.linear-btn-primary {
            background-color: var(--linear-primary-500) !important;
            color: var(--linear-text-on-primary) !important;
        }

        .linear-swal-popup .linear-btn.linear-btn-danger {
            background-color: var(--linear-danger-500) !important;
            color: var(--linear-text-on-danger) !important;
        }

        .linear-swal-popup .linear-btn.linear-btn-secondary {
            background-color: var(--linear-secondary-500) !important;
            color: var(--linear-text-on-secondary) !important;
        }

        /* Enhanced Visual Polish */
        .linear-swal-popup .swal2-icon {
            margin: var(--linear-spacing-md) 0 !important;
        }

        .linear-swal-popup .swal2-timer-progress-bar {
            background-color: var(--linear-primary-500) !important;
        }
        
        /* Panel info display on panels */
        .panel-info {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 0.7rem;
            color: var(--linear-text-primary);
            text-align: center;
            pointer-events: none;
            line-height: 1.2;
            font-weight: var(--linear-font-weight-medium);
        }
        
        .panel-info .material {
            font-size: 0.6rem;
            opacity: 0.8;
        }
        
        .panel-info .dimensions {
            font-size: 0.5rem;
            opacity: 0.7;
        }
        
        .panel.has-info {
            background-color: var(--linear-success-bg) !important;
            border-color: var(--linear-success-border) !important;
            color: var(--linear-success-text) !important;
        }
        
        .transom-panel.has-info {
            background-color: var(--linear-accent-primary) !important;
            color: var(--linear-text-on-accent) !important;
        }

        /* 타공 정보 방향별 표시 스타일 */
        .panel-info .drilling-right {
            position: absolute;
            right: -150px;
            top: 50%;
            transform: translateY(-50%);
            white-space: nowrap;
        }

        .panel-info .drilling-left {
            position: absolute;
            left: -150px;
            top: 50%;
            transform: translateY(-50%);
            white-space: nowrap;
        }

        .panel-info .drilling-down {
            position: absolute;
            bottom: -6px;
            left: 50%;
            transform: translateX(-50%);
            white-space: nowrap;
        }

        .panel-info .drilling-up {
            position: absolute;
            top: -6px;
            left: 50%;
            transform: translateX(-50%);
            white-space: nowrap;
        }

        /* 타공 정보 텍스트 스타일 - 라이트모드 */
        .drilling-info {
            font-size: 1.26rem !important;
            font-weight: bold !important;
            color: var(--linear-accent-primary) !important;
            background: var(--linear-background-primary) !important;
            border: 2px solid var(--linear-accent-primary) !important;
            border-radius: 4px !important;
            padding: 3px 8px !important;
            text-shadow: 0 1px 3px rgba(0,0,0,0.4) !important;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2) !important;
            line-height: 1.1 !important;
        }

        /* 다크모드에서 타공 정보 스타일 */
        [data-theme="dark"] .drilling-info {
            color: var(--linear-warning-text) !important;
            background: var(--linear-background-secondary) !important;
            border-color: var(--linear-warning-border) !important;
            text-shadow: 0 1px 3px rgba(0,0,0,0.8) !important;
            box-shadow: 0 2px 6px rgba(0,0,0,0.5) !important;
        }

        /* 모바일에서 타공 정보 크기 축소 */
        @media (max-width: 768px) {
            .drilling-info {
                font-size: 0.63rem !important;
                padding: 2px 4px !important;
                border-width: 1px !important;
                border-radius: 3px !important;
            }

            /* 모바일에서 타공 정보 위치도 조정 */
            .panel-info .drilling-right {
                right: -75px;
            }

            .panel-info .drilling-left {
                left: -75px;
            }

            .panel-info .drilling-down {
                bottom: -4px;
            }

            .panel-info .drilling-up {
                top: -4px;
            }
        }

        /* SweetAlert2 최상위 z-index 강제 설정 */
        .swal2-top-container {
            z-index: 999999 !important;
        }

        .swal2-container.swal2-top-container {
            z-index: 999999 !important;
            position: fixed !important;
        }

        .swal2-top-container .swal2-popup {
            z-index: 999999 !important;
        }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            try {
                // Panel modal elements
                const panelModal = document.getElementById('panelModal');
                const panelModalTitle = document.getElementById('panelModalTitle');
                const panelModalClose = document.getElementById('panelModalClose');
                const panelModalCancel = document.getElementById('panelModalCancel');
                const panelModalSave = document.getElementById('panelModalSave');
                const panelInfoForm = document.getElementById('panelInfoForm');
                const modalPanelNumber = document.getElementById('modalPanelNumber');
                const panelTypeSection = document.getElementById('panelTypeSection');

                // Copy button elements
                const copyBtn = document.getElementById('copyBtn');
            
            // Store current panel number
            let currentPanelNumber = null;
            
            // 모바일 환경 감지 (전역 함수 사용)

            // Add click event to all panels
            function attachPanelEvents() {
                const panels = document.querySelectorAll('.panel[data-panel]');
                const isMobile = (typeof isMobileDevice === 'function') ? isMobileDevice() : false;

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
                const panelNumber = e.currentTarget.getAttribute('data-panel');
                openPanelModal(panelNumber);
            }
            
            // 더블 터치 처리를 위한 변수들
            let lastTouchTime = 0;
            let lastTouchTarget = null;
            const DOUBLE_TOUCH_DELAY = 300; // 300ms 이내 더블 터치

            function handlePanelTouch(e) {
                e.preventDefault();
                e.stopPropagation();

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
                modalPanelNumber.value = panelNumber;

                const isMobile = window.innerWidth <= 768;

                console.log(`Opening panel modal for panel ${panelNumber}`);
                console.log('Local panelData:', panelData);
                console.log('Global window.panelData:', window.panelData);

                // 모바일 전용 디버깅
                if (isMobile) {
                    console.log('=== 📱 모바일 패널 모달 열기 ===');
                    console.log('📱 패널 번호:', panelNumber);
                    console.log('📱 현재 window.panelData 상태:', window.panelData);
                    console.log('📱 해당 패널 데이터:', window.panelData ? window.panelData[panelNumber] : 'undefined');
                }

                // Set modal title
                if (panelNumber === '12') {
                    panelModalTitle.textContent = `Transom 정보`;
                    // Transom에서는 패널 타입 섹션을 사용하지 않음
                    panelTypeSection.style.display = 'none';
                } else {
                    panelModalTitle.textContent = `패널 ${panelNumber} 정보`;
                    panelTypeSection.style.display = 'none';
                }

                // Show corner panel type section only for panels 1 and 11
                const cornerPanelTypeSection = document.getElementById('cornerPanelTypeSection');
                const cornerPanelDetailsSection = document.getElementById('cornerPanelDetailsSection');
                const transomDetailsSection = document.getElementById('transomDetailsSection');

                // Show/hide copy button for panels 1 and 11
                const copyButtonContainer = document.getElementById('copyButtonContainer');
                const copyText = document.getElementById('copyText');

                if (panelNumber === '1' || panelNumber === '11') {
                    copyButtonContainer.style.display = 'flex';
                    const targetPanel = panelNumber === '1' ? '11' : '1';
                    copyText.textContent = `${targetPanel}번으로 복사`;
                } else {
                    copyButtonContainer.style.display = 'none';
                }

                if (panelNumber === '12') {
                    // Show Transom details section for panel 12
                    if (transomDetailsSection) {
                        transomDetailsSection.style.display = 'block';

                        // Clear previous values only if no existing data (will be loaded later)
                        const existingData = window.panelData && window.panelData[panelNumber];
                        if (!existingData) {
                            document.getElementById('modalTransomPlateHeight').value = '';
                            document.getElementById('modalBottomDepthJD').value = '';
                            document.getElementById('modalWingValue').value = '';
                            document.getElementById('modalCpiDrillingWidth').value = '';
                            document.getElementById('modalCpiDrillingHeight').value = '';
                            document.getElementById('modalCpiDrillingHeightFromBottom').value = '';
                            console.log('Transom 12번: 필드 초기화 (저장된 데이터 없음)');
                        } else {
                            console.log('Transom 12번: 저장된 데이터 있음 - 나중에 로드됨');
                        }
                    }

                    // Hide corner panel sections for Transom
                    if (cornerPanelTypeSection) cornerPanelTypeSection.style.display = 'none';
                    if (cornerPanelDetailsSection) cornerPanelDetailsSection.style.display = 'none';
                } else if (panelNumber === '1' || panelNumber === '11') {
                    // Hide Transom details section for 1,11번 panels
                    if (transomDetailsSection) {
                        transomDetailsSection.style.display = 'none';

                        // Clear Transom detail fields for 1,11번 panels
                        document.getElementById('modalTransomPlateHeight').value = '';
                        document.getElementById('modalBottomDepthJD').value = '';
                        document.getElementById('modalWingValue').value = '';
                        document.getElementById('modalCpiDrillingWidth').value = '';
                        document.getElementById('modalCpiDrillingHeight').value = '';
                        document.getElementById('modalCpiDrillingHeightFromBottom').value = '';
                    }

                    if (cornerPanelTypeSection) {
                        cornerPanelTypeSection.style.display = 'block';
                        console.log(`패널 ${panelNumber}: 코너 패널 타입 섹션 표시`);

                        // Reset radio buttons first
                        const integratedRadio = document.getElementById('modalPanelTypeIntegrated');
                        const separateRadio = document.getElementById('modalPanelTypeSeparate');

                        if (integratedRadio) integratedRadio.checked = false;
                        if (separateRadio) separateRadio.checked = false;

                        // 라디오 버튼 설정은 loadPanelDataToForm에서 처리하거나, 데이터가 없을 때만 기본값 설정
                        // 여기서는 라디오 버튼 초기화만 하고, 실제 설정은 나중에 처리
                    }

                    // Show corner panel details section
                    if (cornerPanelDetailsSection) {
                        cornerPanelDetailsSection.style.display = 'block';
                        console.log(`패널 ${panelNumber}: 코너 패널 상세 정보 섹션 표시`);

                        // Clear previous values only if no existing data (will be loaded later)
                        const existingData = window.panelData && window.panelData[panelNumber];
                        if (!existingData) {
                            document.getElementById('modalFrontThickness').value = '';
                            document.getElementById('modalFrontWing').value = '';
                            document.getElementById('modalBackThickness').value = '';
                            document.getElementById('modalBackWing').value = '';
                            console.log(`패널 ${panelNumber}: 상세정보 필드 초기화 (저장된 데이터 없음)`);
                        } else {
                            console.log(`패널 ${panelNumber}: 저장된 상세정보 있음 - 나중에 로드됨`);
                        }
                    }
                } else {
                    // Hide Transom details section for non-Transom panels
                    if (transomDetailsSection) {
                        transomDetailsSection.style.display = 'none';
                        // Clear Transom detail fields for non-Transom panels
                        document.getElementById('modalTransomPlateHeight').value = '';
                        document.getElementById('modalBottomDepthJD').value = '';
                        document.getElementById('modalWingValue').value = '';
                        document.getElementById('modalCpiDrillingWidth').value = '';
                        document.getElementById('modalCpiDrillingHeight').value = '';
                        document.getElementById('modalCpiDrillingHeightFromBottom').value = '';
                    }

                    if (cornerPanelTypeSection) {
                        cornerPanelTypeSection.style.display = 'none';
                        // Reset radio buttons for non-corner panels only
                        if (panelNumber !== '1' && panelNumber !== '11') {
                            document.getElementById('modalPanelTypeIntegrated').checked = false;
                            document.getElementById('modalPanelTypeSeparate').checked = false;
                        }
                    }

                    if (cornerPanelDetailsSection) {
                        cornerPanelDetailsSection.style.display = 'none';
                        // Clear corner panel detail fields for non-corner panels
                        document.getElementById('modalFrontThickness').value = '';
                        document.getElementById('modalFrontWing').value = '';
                        document.getElementById('modalBackThickness').value = '';
                        document.getElementById('modalBackWing').value = '';
                    }
                }

                // 특정 패널(2,4,5,7,8,10,12번)에서는 타공정보 섹션을 숨김
                const noDrillingPanels = ['2', '4', '5', '7', '8', '10', '12'];
                const hasDrillingCheckbox = document.getElementById('hasDrilling');
                const drillingFields = document.getElementById('drillingFields');
                const drillingSection = hasDrillingCheckbox ? hasDrillingCheckbox.closest('.panel-modal-field') : null;

                if (noDrillingPanels.includes(panelNumber)) {
                    // 타공정보가 필요 없는 패널들 - 섹션 전체를 숨김
                    if (drillingSection) {
                        drillingSection.style.display = 'none';
                    }
                    // 타공 체크박스 해제 및 필드 숨김
                    if (hasDrillingCheckbox) {
                        hasDrillingCheckbox.checked = false;
                        // 타공 데이터도 초기화
                        document.getElementById('modalDrillingWidth').value = '';
                        document.getElementById('modalDrillingHeight').value = '';
                        document.getElementById('modalDrillingFromFloor').value = '';
                        document.getElementById('modalDrillingFromEntrance').value = '';
                    }
                    if (drillingFields) {
                        drillingFields.style.display = 'none';
                    }
                } else {
                    // 타공정보가 필요한 패널들 - 섹션 표시
                    if (drillingSection) {
                        drillingSection.style.display = 'block';
                    }
                }
                
                // Load existing data if available from global panelData
                const existingData = window.panelData && window.panelData[panelNumber];

                // 부모창 참조 요소들
                const parentMaterialType = document.getElementById('materialType');
                const modalMaterialType = document.getElementById('modalMaterialType');
                const parentMaterialThickness = document.getElementById('materialThickness');
                const modalPanelThickness = document.getElementById('modalPanelThickness');
                const parentCarHeight = document.getElementById('carInsideHeight');
                const modalPanelHeight = document.getElementById('modalPanelHeight');

                // 재질 정보 설정: 저장된 데이터가 있으면 우선 사용, 없으면 부모창과 연동
                if (existingData && existingData.materialType) {
                    if (modalMaterialType) {
                        modalMaterialType.value = existingData.materialType;
                    }
                } else {
                    // 저장된 재질 정보가 없으면 부모창의 재질 정보 연동
                    if (parentMaterialType && modalMaterialType) {
                        modalMaterialType.value = parentMaterialType.value;
                    }
                }

                // 두께 정보 설정: 저장된 데이터가 있으면 우선 사용, 없으면 부모창과 연동
                if (existingData && existingData.thickness) {
                    if (modalPanelThickness) {
                        modalPanelThickness.value = existingData.thickness;
                    }
                } else {
                    // 저장된 두께 정보가 없으면 부모창의 두께 정보 연동
                    if (parentMaterialThickness && modalPanelThickness) {
                        modalPanelThickness.value = parentMaterialThickness.value;
                    }
                }

                // 부모창의 카 높이를 모달 세로(높이)에 자동 설정 (수정 가능)
                if (parentCarHeight && modalPanelHeight && !modalPanelHeight.value) {
                    modalPanelHeight.value = parentCarHeight.value;
                }

                if (existingData) {
                    document.getElementById('modalPanelWidth').value = existingData.width || '';
                    document.getElementById('modalPanelHeight').value = existingData.height || '';
                    document.getElementById('modalPanelNotes').value = existingData.notes || '';
                    
                    // 카 높이도 세로(높이)에 자동 설정 (수정 가능)
                    if (parentCarHeight && modalPanelHeight) {
                        modalPanelHeight.value = parentCarHeight.value;
                    }
                    
                    // 타공정보 로딩 (실제 데이터 확인)
                    const hasDrillingData = existingData.drillingWidth || existingData.drillingHeight ||
                                           existingData.drillingFromFloor || existingData.drillingFromEntrance;
                    
                    const hasDrillingCheckboxInner = document.getElementById('hasDrilling');
                    const drillingFields = document.getElementById('drillingFields');
                    
                    // 타공정보가 필요한 패널에서만 처리
                    const noDrillingPanels = ['2', '4', '5', '7', '8', '10', '12'];
                    if (!noDrillingPanels.includes(panelNumber)) {
                        if (hasDrillingData) {
                            // 저장된 타공 데이터 로딩
                            if (hasDrillingCheckboxInner) {
                                hasDrillingCheckboxInner.checked = true;
                            }
                            if (drillingFields) {
                                drillingFields.style.display = 'block';
                            }
                            
                            // 타공 데이터 입력
                            if (existingData.drillingWidth) document.getElementById('modalDrillingWidth').value = existingData.drillingWidth;
                            if (existingData.drillingHeight) document.getElementById('modalDrillingHeight').value = existingData.drillingHeight;
                            if (existingData.drillingFromFloor) document.getElementById('modalDrillingFromFloor').value = existingData.drillingFromFloor;
                            if (existingData.drillingFromEntrance) document.getElementById('modalDrillingFromEntrance').value = existingData.drillingFromEntrance;
                        } else {
                            // 타공 데이터가 없으면 초기화
                            if (hasDrillingCheckboxInner) {
                                hasDrillingCheckboxInner.checked = false;
                            }
                            if (drillingFields) {
                                drillingFields.style.display = 'none';
                            }
                        }
                    } else {
                        // 타공이 필요없는 패널은 무조건 초기화
                        if (hasDrillingCheckboxInner) {
                            hasDrillingCheckboxInner.checked = false;
                        }
                        if (drillingFields) {
                            drillingFields.style.display = 'none';
                        }
                    }
                    
                }

                // Load existing data if available (AFTER setting up UI sections)
                console.log('🔍 DEBUG: 저장된 데이터 로드 시도 - 패널번호:', panelNumber);
                console.log('🔍 DEBUG: window.panelData[' + panelNumber + ']:', window.panelData ? window.panelData[panelNumber] : 'undefined');
                if (window.panelData && window.panelData[panelNumber]) {
                    loadPanelDataToForm(panelNumber);
                    console.log('🔍 DEBUG: loadPanelDataToForm 호출 완료');
                } else {
                    console.log('🔍 DEBUG: 저장된 데이터 없음 - 기본값 사용');
                }

                // 1,11번 패널의 경우 라디오 버튼이 설정되지 않았다면 기본값 설정
                if (panelNumber === '1' || panelNumber === '11') {
                    const integratedRadio = document.getElementById('modalPanelTypeIntegrated');
                    const separateRadio = document.getElementById('modalPanelTypeSeparate');

                    if (integratedRadio && separateRadio &&
                        !integratedRadio.checked && !separateRadio.checked) {
                        integratedRadio.checked = true;
                        console.log(`패널 ${panelNumber}: 라디오 버튼 기본값 '일체형' 설정`);
                    }
                }

                // Show modal
                panelModal.style.display = 'flex';
                document.body.style.overflow = 'hidden';

                // Register modal open for mobile handler
                if (window.mobileModalHandler) {
                    window.mobileModalHandler.registerModalOpen({
                        id: `panel_modal_${panelNumber}`,
                        type: 'custom',
                        element: panelModal,
                        closeCallback: () => {
                            panelModal.style.display = 'none';
                            document.body.style.overflow = '';
                            currentPanelNumber = null;
                        }
                    });
                }

                // Focus on width input after modal is fully rendered
                setTimeout(() => {
                    const widthInput = document.getElementById('modalPanelWidth');
                    if (widthInput) {
                        widthInput.focus();
                        // Optional: select existing text for easy replacement
                        if (widthInput.value) {
                            widthInput.select();
                        }
                    }

                    // Add Enter key event listeners to all input fields for quick save
                    const inputFields = [
                        'modalPanelWidth',
                        'modalPanelHeight',
                        'modalDrillingWidth',
                        'modalDrillingHeight',
                        'modalDrillingFromFloor',
                        'modalDrillingFromEntrance',
                        'modalPanelNotes'
                    ];

                    inputFields.forEach(fieldId => {
                        const field = document.getElementById(fieldId);
                        if (field) {
                            // Remove any existing event listeners to prevent duplicates
                            field.removeEventListener('keydown', handlePanelModalEnterKey);
                            // Add new event listener
                            field.addEventListener('keydown', handlePanelModalEnterKey);
                        }
                    });
                }, 100);
            }

            // Enter key handler for panel modal input fields
            function handlePanelModalEnterKey(event) {
                if (event.key === 'Enter') {
                    // For textarea: Shift+Enter = new line, Enter = save
                    if (event.target.tagName.toLowerCase() === 'textarea') {
                        if (event.shiftKey) {
                            return; // Allow Shift+Enter for line breaks in textarea
                        } else {
                            event.preventDefault(); // Prevent default line break for Enter
                            savePanelInfo(); // Save on Enter in textarea
                        }
                    } else {
                        // For all other input fields: Enter = save
                        event.preventDefault(); // Prevent default form submission
                        savePanelInfo();
                    }
                }
            }

            function closePanelModal() {
                // Register modal close for mobile handler
                if (window.mobileModalHandler && currentPanelNumber) {
                    window.mobileModalHandler.registerModalClose(`panel_modal_${currentPanelNumber}`);
                }

                panelModal.style.display = 'none';
                document.body.style.overflow = '';
                currentPanelNumber = null;
            }

            // 패널 정보 초기화 함수
            function resetPanelInfo() {
                if (!currentPanelNumber) return;

                // SweetAlert2로 확인 대화상자 표시 (z-index 최상위 강제 설정)
                Swal.fire({
                    title: '패널 정보 초기화',
                    text: `패널 ${currentPanelNumber}번의 데이터를 삭제하시겠습니까?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: '네, 삭제합니다',
                    cancelButtonText: '취소',
                    reverseButtons: true,
                    heightAuto: false,
                    backdrop: 'rgba(0,0,0,0.8)',
                    customClass: {
                        container: 'swal2-top-container'
                    },
                    didOpen: () => {
                        // z-index를 직접 설정
                        const container = document.querySelector('.swal2-container');
                        if (container) {
                            container.style.zIndex = '999999';
                        }

                        // Register SweetAlert2 modal for mobile handler
                        if (window.mobileModalHandler) {
                            window.mobileModalHandler.registerModalOpen({
                                id: `swal_reset_panel_${currentPanelNumber}`,
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
                            window.mobileModalHandler.registerModalClose(`swal_reset_panel_${currentPanelNumber}`);
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // 패널 번호를 미리 저장 (closePanelModal에서 currentPanelNumber가 null이 되기 전에)
                        const deletedPanelNumber = currentPanelNumber;

                        // 글로벌 panelData에서 해당 패널 데이터 삭제
                        if (window.panelData && window.panelData[currentPanelNumber]) {
                            delete window.panelData[currentPanelNumber];
                        }

                        // Transom 데이터도 삭제 (패널 12번인 경우)
                        if (currentPanelNumber === '12' && window.transomData) {
                            window.transomData = {};
                        }

                        // JSON 필드 업데이트 (헬퍼 함수 사용)
                        window.safeUpdateJsonFields('패널 저장 시');

                        // 패널 표시 업데이트
                        renderPanelInfo(currentPanelNumber);

                        // 모달창 닫기
                        closePanelModal();

                        // 성공 메시지 (저장된 패널 번호 사용)
                        Swal.fire({
                            title: '삭제 완료',
                            text: `패널 ${deletedPanelNumber}번의 정보가 삭제되었습니다.`,
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false,
                            heightAuto: false,
                            backdrop: 'rgba(0,0,0,0.8)',
                            customClass: {
                                container: 'swal2-top-container'
                            },
                            didOpen: () => {
                                // z-index를 직접 설정
                                const container = document.querySelector('.swal2-container');
                                if (container) {
                                    container.style.zIndex = '999999';
                                }
                            }
                        });
                    }
                });
            }
            
            function savePanelInfo() {
                if (!currentPanelNumber) return;

                console.log('🔍 DEBUG: savePanelInfo 시작 - 패널번호:', currentPanelNumber);

                const isMobile = window.innerWidth <= 768;

                // 패널 번호에 따라 다른 필드만 포함
                const formData = {
                    materialType: document.getElementById('modalMaterialType').value,
                    thickness: document.getElementById('modalPanelThickness').value,
                    width: document.getElementById('modalPanelWidth').value,
                    height: document.getElementById('modalPanelHeight').value,
                    drillingWidth: document.getElementById('modalDrillingWidth').value,
                    drillingHeight: document.getElementById('modalDrillingHeight').value,
                    drillingFromFloor: document.getElementById('modalDrillingFromFloor').value,
                    drillingFromEntrance: document.getElementById('modalDrillingFromEntrance').value,
                    notes: document.getElementById('modalPanelNotes').value,
                    panelNumber: currentPanelNumber
                };

                // 1~11번 패널은 TR 관련 필드를 제외
                if (currentPanelNumber !== '12') {
                    // TR 관련 필드 제거 (1~11번 패널에서는 사용하지 않음)
                    delete formData.transomPlateHeight;
                    delete formData.bottomDepthJD;
                    delete formData.wingValue;
                    delete formData.cpiDrillingWidth;
                    delete formData.cpiDrillingHeight;
                    delete formData.cpiDrillingHeightFromBottom;
                }

                console.log('🔍 DEBUG: 기본 formData:', formData);

                // Add panel type for corner panels (1번, 11번)
                if (currentPanelNumber === '1' || currentPanelNumber === '11') {
                    console.log('🔍 DEBUG: 1,11번 패널 처리 중');

                    const integratedRadio = document.getElementById('modalPanelTypeIntegrated');
                    const separateRadio = document.getElementById('modalPanelTypeSeparate');

                    console.log('Checking panel type radio buttons for panel', currentPanelNumber);

                    if (integratedRadio && integratedRadio.checked) {
                        formData.panel_type_detail = '일체형';
                    } else if (separateRadio && separateRadio.checked) {
                        formData.panel_type_detail = '분리형';
                    } else {
                        formData.panel_type_detail = '일체형'; // 기본값 설정
                    }

                    // Add corner panel details (전면두께, 전면날개, 후면두께, 후면날개)
                    formData.frontThickness = document.getElementById('modalFrontThickness').value;
                    formData.frontWing = document.getElementById('modalFrontWing').value;
                    formData.backThickness = document.getElementById('modalBackThickness').value;
                    formData.backWing = document.getElementById('modalBackWing').value;

                    console.log('🔍 DEBUG: 패널 상세정보:');
                    console.log('  - frontThickness:', formData.frontThickness);
                    console.log('  - frontWing:', formData.frontWing);
                    console.log('  - backThickness:', formData.backThickness);
                    console.log('  - backWing:', formData.backWing);
                    console.log('  - panel_type_detail:', formData.panel_type_detail);

                } else if (currentPanelNumber === '12') {
                    console.log('🔍 DEBUG: 12번 Transom 패널 처리 중');

                    // Add Transom details (트랜섬 막판높이, 밑면깊이(JD), 날개값, CPI타공 가로/세로/높이)
                    formData.transomPlateHeight = document.getElementById('modalTransomPlateHeight').value;
                    formData.bottomDepthJD = document.getElementById('modalBottomDepthJD').value;
                    formData.wingValue = document.getElementById('modalWingValue').value;
                    formData.cpiDrillingWidth = document.getElementById('modalCpiDrillingWidth').value;
                    formData.cpiDrillingHeight = document.getElementById('modalCpiDrillingHeight').value;
                    formData.cpiDrillingHeightFromBottom = document.getElementById('modalCpiDrillingHeightFromBottom').value;

                    console.log('🔍 DEBUG: Transom 상세정보:');
                    console.log('  - transomPlateHeight:', formData.transomPlateHeight);
                    console.log('  - bottomDepthJD:', formData.bottomDepthJD);
                    console.log('  - wingValue:', formData.wingValue);
                    console.log('  - cpiDrillingWidth:', formData.cpiDrillingWidth);
                    console.log('  - cpiDrillingHeight:', formData.cpiDrillingHeight);
                    console.log('  - cpiDrillingHeightFromBottom:', formData.cpiDrillingHeightFromBottom);

                }

                // Store data in global panelData
                if (!window.panelData) {
                    window.panelData = {};
                }
                window.panelData[currentPanelNumber] = formData;

                console.log('🔍 DEBUG: window.panelData 저장 완료');
                console.log('🔍 DEBUG: window.panelData[' + currentPanelNumber + ']:', window.panelData[currentPanelNumber]);
                console.log('🔍 DEBUG: 전체 window.panelData:', window.panelData);


                // Update panel display
                updatePanelDisplay(currentPanelNumber, formData);
                // Also update with renderPanelInfo if available
                if (typeof window.renderPanelInfo === 'function') {
                    window.renderPanelInfo(currentPanelNumber, formData);
                }


                // Update hidden JSON fields (헬퍼 함수 사용)
                console.log('🔍 DEBUG: updateJsonFields 호출 전 JSON 필드 값:', document.getElementById('panelJsonData')?.value);
                // 라디오 선택 호환 키 동기화 (view, list 등에서 panelType 사용 가능)
                if (currentPanelNumber === '1' || currentPanelNumber === '11') {
                    if (window.panelData[currentPanelNumber]) {
                        const detail = window.panelData[currentPanelNumber].panel_type_detail;
                        if (detail && !window.panelData[currentPanelNumber].panelType) {
                            window.panelData[currentPanelNumber].panelType = detail;
                        }
                    }
                }

                window.safeUpdateJsonFields('모달 저장 시');
                console.log('🔍 DEBUG: updateJsonFields 호출 후 JSON 필드 값:', document.getElementById('panelJsonData')?.value);

                
                closePanelModal();
            }
            
            function updatePanelDisplay(panelNumber, data) {
                const panels = document.querySelectorAll(`.panel-${panelNumber}`);
                panels.forEach(panel => {
                    // Remove existing info
                    const existingInfo = panel.querySelector('.panel-info');
                    if (existingInfo) {
                        existingInfo.remove();
                    }
                    
                    // Check if there's meaningful data
                    const hasData = data.materialType || data.width || data.height || data.notes || 
                                   data.drillingWidth || data.drillingHeight;
                    
                    if (hasData) {
                        panel.classList.add('has-info');
                        
                        // Create info display
                        const infoDiv = document.createElement('div');
                        infoDiv.className = 'panel-info';
                        
                        let infoText = panelNumber === '12' ? 'T' : panelNumber;
                        if (data.materialType) {
                            infoText += `<br><span class="material">${data.materialType}</span>`;
                        }
                        if (data.width && data.height) {
                            infoText += `<br><span class="dimensions">${data.width}×${data.height}</span>`;
                        }
                        // 타공 정보가 있으면 방향에 따라 표시
                        if (data.drillingWidth || data.drillingHeight) {
                            // 타공 크기 정보 생성
                            const width = data.drillingWidth || '?';
                            const height = data.drillingHeight || '?';
                            const drillingInfo = `<span class="drilling-info">${width}×${height}타공</span>`;

                            // 패널 위치에 따른 타공정보 표시 방향
                            const panelNum = panelNumber === '12' ? '12' : panelNumber;

                            // 패널별 방향 정의
                            if (['2', '3', '4'].includes(panelNum)) {
                                // 2,3,4번 패널: 오른쪽으로 표시
                                infoText += `<span class="drilling-right">${drillingInfo}</span>`;
                            } else if (['5', '6', '7'].includes(panelNum)) {
                                // 5,6,7번 패널: 아래 방향으로 이동 후 표시
                                infoText += `<br><span class="drilling-down">${drillingInfo}</span>`;
                            } else if (['8', '9', '10'].includes(panelNum)) {
                                // 8,9,10번 패널: 왼쪽으로 표시
                                infoText += `<span class="drilling-left">${drillingInfo}</span>`;
                            } else if (['1', '11', '12'].includes(panelNum)) {
                                // 1번, 11번, 트랜썸: 위로 이동 후 표시
                                infoText = `<span class="drilling-up">${drillingInfo}</span><br>` + infoText;
                            }
                        }
                        
                        infoDiv.innerHTML = infoText;
                        panel.appendChild(infoDiv);
                    } else {
                        panel.classList.remove('has-info');
                        panel.innerHTML = panelNumber === '12' ? 'T' : panelNumber;
                    }
                });
            }

            // 모바일/PC에서 현재 입력된 패널 데이터를 수집하는 함수
            function collectCurrentPanelData() {
                const collectedData = {};

                // 현재 선택된 패널 번호 확인
                let selectedPanelNumber = null;

                // PC에서 선택된 패널 확인
                const selectedPanelPC = document.querySelector('.main-content .panel.selected');
                if (selectedPanelPC) {
                    selectedPanelNumber = selectedPanelPC.getAttribute('data-panel');
                }

                // 모바일에서 선택된 패널 확인 (PC에서 선택이 없는 경우)
                if (!selectedPanelNumber) {
                    const selectedPanelMobile = document.querySelector('#mobile-panel-visualization .panel.selected');
                    if (selectedPanelMobile) {
                        selectedPanelNumber = selectedPanelMobile.getAttribute('data-panel');
                    }
                }


                if (selectedPanelNumber) {
                    // PC와 모바일에서 입력값 수집 (우선순위: PC > 모바일)
                    const panelData = {};

                    // PC 입력 필드에서 수집
                    const pcWidth = document.getElementById('panelWidth')?.value;
                    const pcHeight = document.getElementById('panelHeight')?.value;
                    const pcThickness = document.getElementById('panelThickness')?.value;
                    const pcMaterialType = document.getElementById('materialType')?.value;
                    const pcNotes = document.getElementById('notes')?.value;

                    // 모바일 입력 필드에서 수집 (PC 값이 없는 경우 대체)
                    const mobileWidth = document.querySelector('#mobile-measurements input[name="panelWidth"]')?.value;
                    const mobileHeight = document.querySelector('#mobile-measurements input[name="panelHeight"]')?.value;
                    const mobileThickness = document.querySelector('#mobile-measurements input[name="panelThickness"]')?.value;
                    const mobileMaterialType = document.querySelector('#mobile-materials select[name="materialType"]')?.value;
                    const mobileNotes = document.querySelector('#mobile-materials textarea[name="notes"]')?.value;

                    // 값 우선순위 적용 (PC 우선, 모바일 대체)
                    const width = pcWidth || mobileWidth;
                    const height = pcHeight || mobileHeight;
                    const thickness = pcThickness || mobileThickness;
                    const materialType = pcMaterialType || mobileMaterialType;
                    const notes = pcNotes || mobileNotes;


                    // 유효한 값만 패널 데이터에 추가
                    if (width && parseFloat(width) > 0) panelData.width = parseFloat(width);
                    if (height && parseFloat(height) > 0) panelData.height = parseFloat(height);
                    if (thickness && parseFloat(thickness) > 0) panelData.thickness = parseFloat(thickness);
                    if (materialType) panelData.materialType = materialType;
                    if (notes) panelData.notes = notes;

                    // 패널 데이터가 있으면 수집 데이터에 추가
                    if (Object.keys(panelData).length > 0) {
                        collectedData[selectedPanelNumber] = panelData;
                    }
                }

                // Transom 데이터도 수집 (12번 패널인 경우) - 기존 상세정보 보존
                if (selectedPanelNumber === '12') {
                    let transomData = {};

                    // 기존 window.panelData['12']에서 상세 정보 먼저 보존
                    if (window.panelData && window.panelData['12']) {
                        transomData = { ...window.panelData['12'] };
                    }

                    // Transom 관련 PC 입력값 수집 (모달창 필드명 사용)
                    const pcTransomWidth = document.getElementById('transomWidth')?.value;
                    const pcTransomHeight = document.getElementById('transomHeight')?.value;
                    const pcDrillingWidth = document.getElementById('drillingWidth')?.value;
                    const pcDrillingHeight = document.getElementById('drillingHeight')?.value;
                    const pcDrillingFromFloor = document.getElementById('drillingFromFloor')?.value;
                    const pcDrillingFromEntrance = document.getElementById('drillingFromEntrance')?.value;

                    // Transom 모달창 필드들 수집 (PC에서 중요한 부분)
                    const modalTransomPlateHeight = document.getElementById('modalTransomPlateHeight')?.value;
                    const modalBottomDepthJD = document.getElementById('modalBottomDepthJD')?.value;
                    const modalWingValue = document.getElementById('modalWingValue')?.value;
                    const modalCpiDrillingWidth = document.getElementById('modalCpiDrillingWidth')?.value;
                    const modalCpiDrillingHeight = document.getElementById('modalCpiDrillingHeight')?.value;
                    const modalCpiDrillingHeightFromBottom = document.getElementById('modalCpiDrillingHeightFromBottom')?.value;

                    // 모바일에서 Transom 입력값 수집
                    const mobileTransomWidth = document.querySelector('#mobile-measurements input[name="transomWidth"]')?.value;
                    const mobileTransomHeight = document.querySelector('#mobile-measurements input[name="transomHeight"]')?.value;

                    // 우선순위 적용하여 Transom 데이터 구성 (기존 데이터에 덮어쓰기)
                    const transomWidth = pcTransomWidth || mobileTransomWidth;
                    const transomHeight = pcTransomHeight || mobileTransomHeight;

                    if (transomWidth && parseFloat(transomWidth) > 0) transomData.width = parseFloat(transomWidth);
                    if (transomHeight && parseFloat(transomHeight) > 0) transomData.height = parseFloat(transomHeight);
                    if (pcDrillingWidth && parseFloat(pcDrillingWidth) > 0) transomData.drillingWidth = parseFloat(pcDrillingWidth);
                    if (pcDrillingHeight && parseFloat(pcDrillingHeight) > 0) transomData.drillingHeight = parseFloat(pcDrillingHeight);
                    if (pcDrillingFromFloor && parseFloat(pcDrillingFromFloor) > 0) transomData.drillingFromFloor = parseFloat(pcDrillingFromFloor);
                    if (pcDrillingFromEntrance && parseFloat(pcDrillingFromEntrance) > 0) transomData.drillingFromEntrance = parseFloat(pcDrillingFromEntrance);

                    // 모달창에서 입력된 Transom 상세 정보 추가 (PC 핵심 수정)
                    if (modalTransomPlateHeight && parseFloat(modalTransomPlateHeight) > 0) {
                        transomData.transomPlateHeight = parseFloat(modalTransomPlateHeight);
                    }
                    if (modalBottomDepthJD && parseFloat(modalBottomDepthJD) > 0) {
                        transomData.bottomDepthJD = parseFloat(modalBottomDepthJD);
                    }
                    if (modalWingValue && parseFloat(modalWingValue) > 0) {
                        transomData.wingValue = parseFloat(modalWingValue);
                    }
                    if (modalCpiDrillingWidth && parseFloat(modalCpiDrillingWidth) > 0) {
                        transomData.cpiDrillingWidth = parseFloat(modalCpiDrillingWidth);
                    }
                    if (modalCpiDrillingHeight && parseFloat(modalCpiDrillingHeight) > 0) {
                        transomData.cpiDrillingHeight = parseFloat(modalCpiDrillingHeight);
                    }
                    if (modalCpiDrillingHeightFromBottom && parseFloat(modalCpiDrillingHeightFromBottom) > 0) {
                        transomData.cpiDrillingHeightFromBottom = parseFloat(modalCpiDrillingHeightFromBottom);
                    }

                    // 항상 수집 (기존 상세 정보 포함)
                    collectedData['12'] = transomData;
                }


                return collectedData;
            }

            // PC Transom 모달창 전용 데이터 수집 함수
            function collectModalTransomData() {
                const modalTransomData = {};

                // 모달창 필드들에서 값 수집 (빈 값도 포함하여 확실한 업데이트)
                const modalTransomPlateHeight = document.getElementById('modalTransomPlateHeight')?.value;
                const modalBottomDepthJD = document.getElementById('modalBottomDepthJD')?.value;
                const modalWingValue = document.getElementById('modalWingValue')?.value;
                const modalCpiDrillingWidth = document.getElementById('modalCpiDrillingWidth')?.value;
                const modalCpiDrillingHeight = document.getElementById('modalCpiDrillingHeight')?.value;
                const modalCpiDrillingHeightFromBottom = document.getElementById('modalCpiDrillingHeightFromBottom')?.value;

                // Transom 기본 정보도 수집 (PC에서 중요한 부분)
                const transomWidth = document.getElementById('transomWidth')?.value;
                const transomHeight = document.getElementById('transomHeight')?.value;
                const drillingWidth = document.getElementById('drillingWidth')?.value;
                const drillingHeight = document.getElementById('drillingHeight')?.value;
                const drillingFromFloor = document.getElementById('drillingFromFloor')?.value;
                const drillingFromEntrance = document.getElementById('drillingFromEntrance')?.value;

                // 필드가 존재하고 값이 있으면 Transom 데이터로 구성
                let hasData = false;
                let transomData = {};

                // 기존 window.panelData['12'] 보존
                if (window.panelData && window.panelData['12']) {
                    transomData = { ...window.panelData['12'] };
                }

                // 기본 transom 정보 수집
                if (transomWidth !== undefined && transomWidth !== null) {
                    transomData.width = transomWidth === '' ? '' : parseFloat(transomWidth) || 0;
                    hasData = true;
                }
                if (transomHeight !== undefined && transomHeight !== null) {
                    transomData.height = transomHeight === '' ? '' : parseFloat(transomHeight) || 0;
                    hasData = true;
                }
                if (drillingWidth !== undefined && drillingWidth !== null) {
                    transomData.drillingWidth = drillingWidth === '' ? '' : parseFloat(drillingWidth) || 0;
                    hasData = true;
                }
                if (drillingHeight !== undefined && drillingHeight !== null) {
                    transomData.drillingHeight = drillingHeight === '' ? '' : parseFloat(drillingHeight) || 0;
                    hasData = true;
                }
                if (drillingFromFloor !== undefined && drillingFromFloor !== null) {
                    transomData.drillingFromFloor = drillingFromFloor === '' ? '' : parseFloat(drillingFromFloor) || 0;
                    hasData = true;
                }
                if (drillingFromEntrance !== undefined && drillingFromEntrance !== null) {
                    transomData.drillingFromEntrance = drillingFromEntrance === '' ? '' : parseFloat(drillingFromEntrance) || 0;
                    hasData = true;
                }

                // 모달창 상세 정보 수집
                if (modalTransomPlateHeight !== undefined && modalTransomPlateHeight !== null) {
                    transomData.transomPlateHeight = modalTransomPlateHeight === '' ? '' : parseFloat(modalTransomPlateHeight) || 0;
                    hasData = true;
                }
                if (modalBottomDepthJD !== undefined && modalBottomDepthJD !== null) {
                    transomData.bottomDepthJD = modalBottomDepthJD === '' ? '' : parseFloat(modalBottomDepthJD) || 0;
                    hasData = true;
                }
                if (modalWingValue !== undefined && modalWingValue !== null) {
                    transomData.wingValue = modalWingValue === '' ? '' : parseFloat(modalWingValue) || 0;
                    hasData = true;
                }
                if (modalCpiDrillingWidth !== undefined && modalCpiDrillingWidth !== null) {
                    transomData.cpiDrillingWidth = modalCpiDrillingWidth === '' ? '' : parseFloat(modalCpiDrillingWidth) || 0;
                    hasData = true;
                }
                if (modalCpiDrillingHeight !== undefined && modalCpiDrillingHeight !== null) {
                    transomData.cpiDrillingHeight = modalCpiDrillingHeight === '' ? '' : parseFloat(modalCpiDrillingHeight) || 0;
                    hasData = true;
                }
                if (modalCpiDrillingHeightFromBottom !== undefined && modalCpiDrillingHeightFromBottom !== null) {
                    transomData.cpiDrillingHeightFromBottom = modalCpiDrillingHeightFromBottom === '' ? '' : parseFloat(modalCpiDrillingHeightFromBottom) || 0;
                    hasData = true;
                }

                // 모달창 필드가 하나라도 존재하면 Transom 데이터 반환
                if (hasData) {
                    // 저장 시에는 'transom' 키로 분리 저장 (내부 렌더링은 여전히 '12' 사용)
                    modalTransomData['transom'] = transomData;
                    console.log('🔍 DEBUG: collectModalTransomData - transom 데이터 수집됨:', transomData);
                } else {
                    console.log('🔍 DEBUG: collectModalTransomData - transom 데이터 없음 (hasData: false)');
                }

                console.log('🔍 DEBUG: collectModalTransomData - 반환값:', modalTransomData);
                return modalTransomData;
            }

            // JSON 인코딩/디코딩 함수들 - 모바일/PC 통합 데이터 수집 개선
            window.updateJsonFields = function() {
                // JSON 필드 업데이트

                // 기존 데이터를 먼저 로드 (수정 모드에서 중요)
                const panelJsonField = document.getElementById('panelJsonData');
                const transomJsonField = document.getElementById('transomJsonData');


                let existingPanels = {};
                let existingTransom = {};

                // 기존 저장된 데이터가 있으면 먼저 로드
                try {
                    if (panelJsonField && panelJsonField.value) {
                        existingPanels = JSON.parse(panelJsonField.value) || {};
                    }
                    if (transomJsonField && transomJsonField.value) {
                        existingTransom = JSON.parse(transomJsonField.value) || {};
                    }
                } catch (e) {
                    console.warn('기존 JSON 데이터 파싱 오류:', e);
                }

                // 모바일/PC에서 현재 입력된 패널 데이터를 수집
                const currentPanelData = collectCurrentPanelData();

                // PC에서 Transom 모달창 데이터를 별도로 수집 (선택 상태와 무관하게)
                const modalTransomData = collectModalTransomData();


                // window.panelData와 기존 데이터, 현재 입력 데이터를 병합 (패널별 깊은 병합)
                const allPanelData = { ...existingPanels };
                if (window.panelData && Object.keys(window.panelData).length > 0) {
                    Object.entries(window.panelData).forEach(([pn, data]) => {
                        const prev = allPanelData[pn] || {};
                        allPanelData[pn] = { ...prev, ...data };
                    });
                }
                if (currentPanelData && Object.keys(currentPanelData).length > 0) {
                    Object.entries(currentPanelData).forEach(([pn, data]) => {
                        const prev = allPanelData[pn] || {};
                        allPanelData[pn] = { ...prev, ...data };
                    });
                }
                // 모달창 Transom 데이터 병합 (PC 핵심 수정)
                if (modalTransomData && Object.keys(modalTransomData).length > 0) {
                    Object.assign(allPanelData, modalTransomData);
                }


                // 패널 데이터 (1-11번)와 transom 데이터 (12번) 분리
                const panels = {};
                const transom = {};

                for (const [panelNumber, data] of Object.entries(allPanelData)) {
                    if (panelNumber === '12' || panelNumber === 'transom') {
                        transom['transom'] = data; // 저장은 'transom' 키로 일원화
                    } else {
                        // 1~11번 패널에서 TR 관련 필드 제거
                        const cleanData = { ...data };
                        delete cleanData.transomPlateHeight;
                        delete cleanData.bottomDepthJD;
                        delete cleanData.wingValue;
                        delete cleanData.cpiDrillingWidth;
                        delete cleanData.cpiDrillingHeight;
                        delete cleanData.cpiDrillingHeightFromBottom;
                        panels[panelNumber] = cleanData;
                    }
                }


                // JSON 문자열로 변환하기 전에 타입 키 정리
                // 1,11번은 panel_type_detail과 panelType를 상호 보정,
                // 2-10번은 타입 키를 빈 문자열로 저장
                Object.keys(panels).forEach(function(key){
                    const item = panels[key]; 
                    if (!item) return;
                    if (key === '1' || key === '11') {
                        if (item.panel_type_detail && !item.panelType) {
                            item.panelType = item.panel_type_detail;
                        } else if (!item.panel_type_detail && item.panelType) {
                            item.panel_type_detail = item.panelType;
                        } else if (!item.panel_type_detail && !item.panelType) {
                            item.panel_type_detail = '일체형';
                            item.panelType = '일체형';
                        }
                    } else {
                        item.panel_type_detail = '';
                        item.panelType = '';
                    }
                });

                const panelJsonString = JSON.stringify(panels);
                const transomJsonString = JSON.stringify(transom);

                // 디버깅 로그 추가
                console.log('🔍 DEBUG: updateJsonFields - transom 데이터:', transom);
                console.log('🔍 DEBUG: updateJsonFields - transomJsonString:', transomJsonString);

                if (panelJsonField) {
                    panelJsonField.value = panelJsonString;
                }
                if (transomJsonField) {
                    transomJsonField.value = transomJsonString;
                    console.log('🔍 DEBUG: updateJsonFields - transomJsonField.value 설정됨:', transomJsonField.value);
                }

            }
            
            // 페이지 로드 시 기존 JSON 데이터 복원 - 개선된 동기화
            function loadJsonData() {
                const panelJsonField = document.getElementById('panelJsonData');
                const transomJsonField = document.getElementById('transomJsonData');

                try {
                    // 전역 window.panelData 초기화
                    if (!window.panelData) {
                        window.panelData = {};
                    }

                    // 패널 데이터 복원
                    if (panelJsonField.value) {
                        const panelsData = JSON.parse(panelJsonField.value);
                        Object.assign(panelData, panelsData);
                        Object.assign(window.panelData, panelsData);
                    }

                    // transom 데이터 복원 (신규 'transom' 키와 구형 '12' 키 모두 지원)
                    if (transomJsonField.value) {
                        const transomData = JSON.parse(transomJsonField.value);
                        if (Object.keys(transomData).length > 0) {
                            if (transomData['transom']) {
                                panelData['12'] = transomData['transom'];
                                window.panelData['12'] = transomData['transom'];
                            } else if (transomData['12']) {
                                panelData['12'] = transomData['12'];
                                window.panelData['12'] = transomData['12'];
                            }
                        }
                    }

                    // UI 업데이트
                    for (const [panelNumber, data] of Object.entries(panelData)) {
                        updatePanelDisplay(panelNumber, data);
                    }

                    // console.log('Loaded panel data (synchronized):', panelData);
                    // console.log('Window panel data (synchronized):', window.panelData);
                } catch (e) {
                    console.warn('Error loading panel data:', e);
                }
            }
            
            // 타공 체크박스 토글 이벤트 (안전한 요소 접근)
            try {
                const hasDrillingCheckboxEl = document.getElementById('hasDrilling');
                const drillingFields = document.getElementById('drillingFields');

                if (hasDrillingCheckboxEl && drillingFields) {
                hasDrillingCheckboxEl.addEventListener('change', function() {
                if (this.checked) {
                    drillingFields.style.display = 'block';
                } else {
                    drillingFields.style.display = 'none'; 
                    // 체크 해제 시 타공 필드들 초기화
                    document.getElementById('modalDrillingWidth').value = '';
                    document.getElementById('modalDrillingHeight').value = '';
                    document.getElementById('modalDrillingFromFloor').value = '';
                    document.getElementById('modalDrillingFromEntrance').value = '';

                    // 저장된 데이터에서도 타공정보 제거 (즉시 저장)
                    if (currentPanelNumber && window.panelData && window.panelData[currentPanelNumber]) {
                        window.panelData[currentPanelNumber].drillingWidth = '';
                        window.panelData[currentPanelNumber].drillingHeight = '';
                        window.panelData[currentPanelNumber].drillingFromFloor = '';
                        window.panelData[currentPanelNumber].drillingFromEntrance = '';

                        // 패널 시각화 업데이트
                        if (typeof window.renderPanelInfo === 'function') {
                            window.renderPanelInfo(currentPanelNumber, window.panelData[currentPanelNumber]);
                        }

                        // JSON 필드 업데이트
                        if (typeof window.updateJsonFields === 'function') {
                            window.updateJsonFields();
                        }

                    }
                }
            });
            }
            } catch (e) {
                console.warn('타공 체크박스 이벤트 리스너 설정 오류:', e);
            }

            // Event listeners
            if (panelModalClose) panelModalClose.addEventListener('click', closePanelModal);
            if (panelModalCancel) panelModalCancel.addEventListener('click', closePanelModal);
            if (panelModalSave) panelModalSave.addEventListener('click', savePanelInfo);

            // Copy button event listener
            if (copyBtn) {
                copyBtn.addEventListener('click', function() {
                    const targetPanel = currentPanelNumber === '1' ? '11' : '1';
                    copyPanelData(currentPanelNumber, targetPanel);
                });
            }

            // Copy panel data function
            function copyPanelData(fromPanel, toPanel) {
                const fromData = window.panelData[fromPanel];

                if (!fromData) {
                    Swal.fire({
                        title: '복사 실패',
                        text: `${fromPanel}번 패널에 복사할 데이터가 없습니다.`,
                        icon: 'warning',
                        confirmButtonText: '확인',
                        customClass: {
                            container: 'swal2-top-container'
                        },
                        didOpen: () => {
                            // z-index를 직접 설정
                            const container = document.querySelector('.swal2-container');
                            if (container) {
                                container.style.zIndex = '999999';
                            }

                            // Register SweetAlert2 modal for mobile handler
                            if (window.mobileModalHandler) {
                                window.mobileModalHandler.registerModalOpen({
                                    id: `swal_copy_fail_${fromPanel}`,
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
                                window.mobileModalHandler.registerModalClose(`swal_copy_fail_${fromPanel}`);
                            }
                        }
                    });
                    return;
                }

                Swal.fire({
                    title: '데이터 복사',
                    text: `${fromPanel}번 패널의 데이터를 ${toPanel}번 패널로 복사하시겠습니까?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#198754',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '복사',
                    cancelButtonText: '취소',
                    customClass: {
                        container: 'swal2-top-container'
                    },
                    didOpen: () => {
                        // z-index를 직접 설정
                        const container = document.querySelector('.swal2-container');
                        if (container) {
                            container.style.zIndex = '999999';
                        }

                        // Register SweetAlert2 modal for mobile handler
                        if (window.mobileModalHandler) {
                            window.mobileModalHandler.registerModalOpen({
                                id: `swal_copy_confirm_${fromPanel}_${toPanel}`,
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
                            window.mobileModalHandler.registerModalClose(`swal_copy_confirm_${fromPanel}_${toPanel}`);
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Copy the data
                        window.panelData[toPanel] = JSON.parse(JSON.stringify(fromData));

                        // If we're currently viewing the target panel, update the form
                        if (currentPanelNumber === toPanel) {
                            loadPanelDataToForm(toPanel);
                        }

                        // Update the panel display with renderPanelInfo for consistent styling
                        if (typeof window.renderPanelInfo === 'function') {
                            window.renderPanelInfo(toPanel, window.panelData[toPanel]);
                        } else {
                            updatePanelDisplay(toPanel, window.panelData[toPanel]);
                        }

                        Swal.fire({
                            title: '복사 완료',
                            text: `${fromPanel}번 패널의 데이터가 ${toPanel}번 패널로 복사되었습니다.`,
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false,
                            customClass: {
                                container: 'swal2-top-container'
                            },
                            didOpen: () => {
                                // z-index를 직접 설정
                                const container = document.querySelector('.swal2-container');
                                if (container) {
                                    container.style.zIndex = '999999';
                                }

                                // Register SweetAlert2 modal for mobile handler
                                if (window.mobileModalHandler) {
                                    window.mobileModalHandler.registerModalOpen({
                                        id: `swal_copy_success_${fromPanel}_${toPanel}`,
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
                                    window.mobileModalHandler.registerModalClose(`swal_copy_success_${fromPanel}_${toPanel}`);
                                }
                            }
                        });
                    }
                });
            }

            // Load panel data to form function
            function loadPanelDataToForm(panelNumber) {
                const data = window.panelData[panelNumber];
                if (!data) return;

                // Basic fields
                if (data.materialType) document.getElementById('modalMaterialType').value = data.materialType;
                if (data.thickness) document.getElementById('modalPanelThickness').value = data.thickness;
                if (data.width) document.getElementById('modalPanelWidth').value = data.width;
                if (data.height) document.getElementById('modalPanelHeight').value = data.height;
                if (data.notes) document.getElementById('modalPanelNotes').value = data.notes;

                // Corner panel specific fields (1번, 11번)
                if (panelNumber === '1' || panelNumber === '11') {
                    const typeValue = data.panel_type_detail || data.panelType;
                    if (typeValue) {
                        const integratedRadio = document.getElementById('modalPanelTypeIntegrated');
                        const separateRadio = document.getElementById('modalPanelTypeSeparate');

                        if (typeValue === '일체형' && integratedRadio) {
                            integratedRadio.checked = true;
                        } else if (typeValue === '분리형' && separateRadio) {
                            separateRadio.checked = true;
                        }
                    }
                    if (data.frontThickness) document.getElementById('modalFrontThickness').value = data.frontThickness;
                    if (data.frontWing) document.getElementById('modalFrontWing').value = data.frontWing;
                    if (data.backThickness) document.getElementById('modalBackThickness').value = data.backThickness;
                    if (data.backWing) document.getElementById('modalBackWing').value = data.backWing;
                }

                // Transom specific fields (12번)
                if (panelNumber === '12') {
                    console.log('🔍 DEBUG: Transom 데이터 로드 중:', data);
                    if (data.transomPlateHeight) {
                        document.getElementById('modalTransomPlateHeight').value = data.transomPlateHeight;
                        console.log('🔍 DEBUG: transomPlateHeight 로드:', data.transomPlateHeight);
                    }
                    if (data.bottomDepthJD) {
                        document.getElementById('modalBottomDepthJD').value = data.bottomDepthJD;
                        console.log('🔍 DEBUG: bottomDepthJD 로드:', data.bottomDepthJD);
                    }
                    if (data.wingValue) {
                        document.getElementById('modalWingValue').value = data.wingValue;
                        console.log('🔍 DEBUG: wingValue 로드:', data.wingValue);
                    }
                    if (data.cpiDrillingWidth) {
                        document.getElementById('modalCpiDrillingWidth').value = data.cpiDrillingWidth;
                        console.log('🔍 DEBUG: cpiDrillingWidth 로드:', data.cpiDrillingWidth);
                    }
                    if (data.cpiDrillingHeight) {
                        document.getElementById('modalCpiDrillingHeight').value = data.cpiDrillingHeight;
                        console.log('🔍 DEBUG: cpiDrillingHeight 로드:', data.cpiDrillingHeight);
                    }
                    if (data.cpiDrillingHeightFromBottom) {
                        document.getElementById('modalCpiDrillingHeightFromBottom').value = data.cpiDrillingHeightFromBottom;
                        console.log('🔍 DEBUG: cpiDrillingHeightFromBottom 로드:', data.cpiDrillingHeightFromBottom);
                    }
                }

                // Drilling fields (if applicable)
                const hasDrillingCheckboxForm = document.getElementById('hasDrilling');
                if (data.drillingWidth || data.drillingHeight) {
                    if (hasDrillingCheckboxForm) {
                        hasDrillingCheckboxForm.checked = true;
                        hasDrillingCheckboxForm.dispatchEvent(new Event('change'));
                    }
                    if (data.drillingWidth) document.getElementById('modalDrillingWidth').value = data.drillingWidth;
                    if (data.drillingHeight) document.getElementById('modalDrillingHeight').value = data.drillingHeight;
                    if (data.drillingFromFloor) document.getElementById('modalDrillingFromFloor').value = data.drillingFromFloor;
                    if (data.drillingFromEntrance) document.getElementById('modalDrillingFromEntrance').value = data.drillingFromEntrance;
                } else {
                    if (hasDrillingCheckboxForm) {
                        hasDrillingCheckboxForm.checked = false;
                        hasDrillingCheckboxForm.dispatchEvent(new Event('change'));
                    }
                }
            }

            // 초기화 버튼 이벤트 리스너
            const panelModalReset = document.getElementById('panelModalReset');
            if (panelModalReset) {
                panelModalReset.addEventListener('click', resetPanelInfo);
            }
            
            // Close modal on backdrop click - DISABLED (사용자가 실수로 모달을 닫는 것을 방지)
            // document.querySelector('.panel-modal-backdrop').addEventListener('click', closePanelModal);
            
            // Close modal on Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && panelModal.style.display === 'flex') {
                    closePanelModal();
                }
            });
            
            // Initialize panel events
            attachPanelEvents();
            
            // SweetAlert 래퍼 함수들 (alert 완전 제거)
            function showSuccessToast(title, text) {
                // PC 화면 중앙에 표시되는 성공 모달
                Swal.fire({
                    icon: 'success',
                    title: title,
                    text: text,
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true,
                    position: 'center',
                    backdrop: true,
                    allowOutsideClick: false
                });
            }

            function showWarning(title, text, callback) {
                Swal.fire({
                    icon: 'warning',
                    title: title,
                    text: text,
                    confirmButtonText: '확인'
                }).then(callback);
            }

            function showError(title, text) {
                Swal.fire({
                    icon: 'error',
                    title: title,
                    text: text,
                    confirmButtonText: '확인',
                    confirmButtonColor: '#d33'
                });
            }

            // 측정값 검증 함수 (측면별 검증)
            function performMeasurementValidation() {
                console.log('=== 측정값 검증 시작 ===');

                // 먼저 JSON 필드를 최신 데이터로 업데이트 (안전한 호출 사용)
                window.safeUpdateJsonFields('performMeasurementValidation');

                // 현재 입력된 패널 데이터 확인 (여러 소스에서 확인)
                let panelJsonData = document.getElementById('panelJsonData')?.value;


                // panelData 변수 안전하게 참조
                let globalPanelData = null;
                try {
                    globalPanelData = (typeof panelData !== 'undefined') ? panelData : null;
                    console.log('global panelData:', globalPanelData);
                } catch (e) {
                    console.log('global panelData access error:', e.message);
                    globalPanelData = null;
                }

                // panelJsonData가 비어있으면 전역 window.panelData에서 확인
                if (!panelJsonData && window.panelData && Object.keys(window.panelData).length > 0) {
                    panelJsonData = JSON.stringify(window.panelData);
                    console.log('Using window.panelData as data source:', panelJsonData);
                } else if (!panelJsonData && globalPanelData && Object.keys(globalPanelData).length > 0) {
                    panelJsonData = JSON.stringify(globalPanelData);
                    console.log('Using global panelData as data source:', panelJsonData);
                }

                // 검증 조건 확인
                const isEmpty = !panelJsonData;
                const isEmptyObject = panelJsonData === '{}';
                const isEmptyString = panelJsonData === '';

                if (isEmpty || isEmptyObject || isEmptyString) {
                    console.log('검증 실패: 패널 데이터 없음');
                    showWarning('검증 불가', '측정된 패널 데이터가 없습니다. 패널을 측정한 후 검증해주세요.');
                    return;
                }

                console.log('✅ 패널 데이터 존재 확인됨 - 검증 계속 진행');

                // 카 내부 치수 가져오기 (W: 가로, D: 깊이)
                const carInsideWidth = parseInt(document.getElementById('carInsideWidth')?.value || 0);
                const carInsideDepth = parseInt(document.getElementById('carInsideDepth')?.value || 0);

                if (carInsideWidth <= 0 || carInsideDepth <= 0) {
                    showWarning('검증 불가', '카 내부 치수(가로/깊이)를 먼저 입력해주세요.');
                    return;
                }

                let parsedPanelData;
                try {
                    parsedPanelData = JSON.parse(panelJsonData);
                    console.log('Parsed panel data:', parsedPanelData);
                } catch (e) {
                    console.error('패널 데이터 파싱 오류:', e);
                    showError('검증 실패', '패널 데이터 형식에 오류가 있습니다.');
                    return;
                }

                // 측면별 패널 width 합계 계산
                let leftSideTotal = 0;   // 왼쪽 측면벽: 2,3,4번
                let rightSideTotal = 0;  // 오른쪽 측면벽: 8,9,10번
                let backWallTotal = 0;   // 뒷벽: 5,6,7번

                // 각 패널별 측정값 수집
                const leftSidePanels = ['2', '3', '4'];
                const rightSidePanels = ['8', '9', '10'];
                const backWallPanels = ['5', '6', '7'];

                console.log('Available panel data:', Object.keys(parsedPanelData));

                leftSidePanels.forEach(panelNum => {
                    if (parsedPanelData[panelNum] && parsedPanelData[panelNum].width) {
                        const width = parseInt(parsedPanelData[panelNum].width);
                        leftSideTotal += width;
                        console.log(`왼쪽 측면벽 패널 ${panelNum}: ${width}mm`);
                    } else {
                        console.log(`왼쪽 측면벽 패널 ${panelNum}: 데이터 없음`);
                    }
                });

                rightSidePanels.forEach(panelNum => {
                    if (parsedPanelData[panelNum] && parsedPanelData[panelNum].width) {
                        const width = parseInt(parsedPanelData[panelNum].width);
                        rightSideTotal += width;
                        console.log(`오른쪽 측면벽 패널 ${panelNum}: ${width}mm`);
                    } else {
                        console.log(`오른쪽 측면벽 패널 ${panelNum}: 데이터 없음`);
                    }
                });

                backWallPanels.forEach(panelNum => {
                    if (parsedPanelData[panelNum] && parsedPanelData[panelNum].width) {
                        const width = parseInt(parsedPanelData[panelNum].width);
                        backWallTotal += width;
                        console.log(`뒷벽 패널 ${panelNum}: ${width}mm`);
                    } else {
                        console.log(`뒷벽 패널 ${panelNum}: 데이터 없음`);
                    }
                });

                // 측정된 패널이 있는지 확인
                const totalMeasuredPanels = leftSideTotal + rightSideTotal + backWallTotal;
                if (totalMeasuredPanels === 0) {
                    showWarning('검증 불가', '측정된 패널이 없습니다. 최소 한 개 이상의 패널을 측정한 후 검증해주세요.');
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

                console.log('=== 측면별 검증 결과 ===');
                console.log('왼쪽 측면벽 (2,3,4번):', leftSideTotal, 'vs D값:', carInsideDepth, '차이:', leftSideDiff, '합격:', leftSidePass);
                console.log('오른쪽 측면벽 (8,9,10번):', rightSideTotal, 'vs D값:', carInsideDepth, '차이:', rightSideDiff, '합격:', rightSidePass);
                console.log('뒷벽 (5,6,7번):', backWallTotal, 'vs W값:', carInsideWidth, '차이:', backWallDiff, '합격:', backWallPass);

                // 전체 결과 판정
                const allPass = leftSidePass && rightSidePass && backWallPass;
                const passCount = [leftSidePass, rightSidePass, backWallPass].filter(Boolean).length;

                // 간단한 결과 메시지 생성
                let resultMessage = '📊 **측정값 검증 결과**\\n\\n';

                // 측면별 간단한 상태 표시
                resultMessage += `왼쪽벽 (2,3,4): ${leftSideTotal}mm ${leftSidePass ? '✅' : '❌'}\\n`;
                resultMessage += `오른쪽벽 (8,9,10): ${rightSideTotal}mm ${rightSidePass ? '✅' : '❌'}\\n`;
                resultMessage += `뒷벽 (5,6,7): ${backWallTotal}mm ${backWallPass ? '✅' : '❌'}\\n\\n`;

                // 검증 결과 모달로 표시
                console.log('검증 결과 모달 호출 준비...');
                const validationData = {
                    leftSide: { total: leftSideTotal, diff: leftSideDiff, pass: leftSidePass, reference: carInsideDepth },
                    backWall: { total: backWallTotal, diff: backWallDiff, pass: backWallPass, reference: carInsideWidth },
                    rightSide: { total: rightSideTotal, diff: rightSideDiff, pass: rightSidePass, reference: carInsideDepth },
                    allPass: allPass,
                    passCount: passCount
                };
                console.log('검증 데이터:', validationData);

                try {
                    showValidationResultModal(validationData);
                    console.log('검증 결과 모달 호출 완료');
                } catch (e) {
                    console.error('검증 결과 모달 호출 오류:', e);
                    // 폴백으로 alert 사용
                    alert(`검증 결과:\n왼쪽벽: ${leftSideTotal}mm ${leftSidePass ? '✅' : '❌'}\n오른쪽벽: ${rightSideTotal}mm ${rightSidePass ? '✅' : '❌'}\n뒷벽: ${backWallTotal}mm ${backWallPass ? '✅' : '❌'}`);
                }
            }

            // Load existing JSON data
            loadJsonData();

            // 검증 결과 모달 함수들
            function showValidationResultModal(results) {
                console.log('=== showValidationResultModal 함수 시작 ===');
                console.log('받은 결과 데이터:', results);

                const modal = document.getElementById('validationResultModal');
                const content = document.getElementById('validationResultContent');

                console.log('모달 엘리먼트:', modal);
                console.log('컨텐츠 엘리먼트:', content);

                if (!modal) {
                    console.error('validationResultModal 엘리먼트를 찾을 수 없습니다!');
                    return;
                }
                if (!content) {
                    console.error('validationResultContent 엘리먼트를 찾을 수 없습니다!');
                    return;
                }

                // 깔끔한 Grid 형태의 테이블 보고서 생성
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
                        margin-bottom: 24px;
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
                            <div style="padding: 10px 12px; border-right: 1px solid var(--linear-border-secondary, #f1f5f9); text-align: center; font-weight: 600; color: var(--linear-text-primary, #1a202c);">${results.leftSide.total}mm</div>
                            <div style="padding: 10px 12px; border-right: 1px solid var(--linear-border-secondary, #f1f5f9); text-align: center;">${results.leftSide.reference}mm (D)</div>
                            <div style="padding: 10px 12px; border-right: 1px solid var(--linear-border-secondary, #f1f5f9); text-align: center; font-weight: 600; color: ${results.leftSide.pass ? 'var(--linear-success, #059669)' : 'var(--linear-danger, #dc2626)'};">${results.leftSide.diff}mm</div>
                            <div style="padding: 10px 12px; text-align: center;">
                                <span style="
                                    display: inline-flex;
                                    align-items: center;
                                    justify-content: center;
                                    padding: 4px 12px;
                                    border-radius: 20px;
                                    font-size: 12px;
                                    font-weight: 600;
                                    text-align: center;
                                    min-width: 60px;
                                    ${results.leftSide.pass
                                        ? 'background: var(--linear-success-bg, #dcfce7); color: var(--linear-success, #059669); border: 1px solid var(--linear-success-border, #bbf7d0);'
                                        : 'background: var(--linear-danger-bg, #fef2f2); color: var(--linear-danger, #dc2626); border: 1px solid var(--linear-danger-border, #fecaca);'}
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
                            <div style="padding: 10px 12px; border-right: 1px solid var(--linear-border-secondary, #f1f5f9); text-align: center; font-weight: 600; color: var(--linear-text-primary, #1a202c);">${results.backWall.total}mm</div>
                            <div style="padding: 10px 12px; border-right: 1px solid var(--linear-border-secondary, #f1f5f9); text-align: center;">${results.backWall.reference}mm (W)</div>
                            <div style="padding: 10px 12px; border-right: 1px solid var(--linear-border-secondary, #f1f5f9); text-align: center; font-weight: 600; color: ${results.backWall.pass ? 'var(--linear-success, #059669)' : 'var(--linear-danger, #dc2626)'};">${results.backWall.diff}mm</div>
                            <div style="padding: 10px 12px; text-align: center;">
                                <span style="
                                    display: inline-flex;
                                    align-items: center;
                                    justify-content: center;
                                    padding: 4px 12px;
                                    border-radius: 20px;
                                    font-size: 12px;
                                    font-weight: 600;
                                    text-align: center;
                                    min-width: 60px;
                                    ${results.backWall.pass
                                        ? 'background: var(--linear-success-bg, #dcfce7); color: var(--linear-success, #059669); border: 1px solid var(--linear-success-border, #bbf7d0);'
                                        : 'background: var(--linear-danger-bg, #fef2f2); color: var(--linear-danger, #dc2626); border: 1px solid var(--linear-danger-border, #fecaca);'}
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
                            <div style="padding: 10px 12px; border-right: 1px solid var(--linear-border-secondary, #f1f5f9); text-align: center; font-weight: 600; color: var(--linear-text-primary, #1a202c);">${results.rightSide.total}mm</div>
                            <div style="padding: 10px 12px; border-right: 1px solid var(--linear-border-secondary, #f1f5f9); text-align: center;">${results.rightSide.reference}mm (D)</div>
                            <div style="padding: 10px 12px; border-right: 1px solid var(--linear-border-secondary, #f1f5f9); text-align: center; font-weight: 600; color: ${results.rightSide.pass ? 'var(--linear-success, #059669)' : 'var(--linear-danger, #dc2626)'};">${results.rightSide.diff}mm</div>
                            <div style="padding: 10px 12px; text-align: center;">
                                <span style="
                                    display: inline-flex;
                                    align-items: center;
                                    justify-content: center;
                                    padding: 4px 12px;
                                    border-radius: 20px;
                                    font-size: 12px;
                                    font-weight: 600;
                                    text-align: center;
                                    min-width: 60px;
                                    ${results.rightSide.pass
                                        ? 'background: var(--linear-success-bg, #dcfce7); color: var(--linear-success, #059669); border: 1px solid var(--linear-success-border, #bbf7d0);'
                                        : 'background: var(--linear-danger-bg, #fef2f2); color: var(--linear-danger, #dc2626); border: 1px solid var(--linear-danger-border, #fecaca);'}
                                ">
                                    ${results.rightSide.pass ? '적합' : '부적합'}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="linear-card" style="
                        margin-top: 20px;
                        border-radius: 12px;
                        border: 1px solid var(--linear-border-primary, #e5e7eb);
                        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                    ">
                        <div class="linear-card-header" style="padding: 16px 20px; border-bottom: 1px solid var(--linear-border-secondary, #f1f5f9);">
                            <h5 class="linear-card-title" style="margin: 0; font-size: 16px; font-weight: 600; color: var(--linear-text-primary, #1a202c);">검증 기준</h5>
                        </div>
                        <div class="linear-card-content" style="padding: 16px 20px;">
                            <ul style="
                                list-style: none;
                                margin: 0;
                                padding: 0;
                                font-size: 14px;
                                line-height: 1.6;
                                color: var(--linear-text-secondary, #4a5568);
                            ">
                                <li style="margin-bottom: 8px; padding-left: 20px; position: relative;">
                                    <span style="
                                        position: absolute;
                                        left: 0;
                                        top: 0;
                                        width: 6px;
                                        height: 6px;
                                        background: var(--linear-primary, #3182ce);
                                        border-radius: 50%;
                                        margin-top: 8px;
                                    "></span>
                                    좌측벽/우측벽: 각 패널 폭의 합계가 카 깊이(D) 치수 대비 ±3mm 이내
                                </li>
                                <li style="margin-bottom: 8px; padding-left: 20px; position: relative;">
                                    <span style="
                                        position: absolute;
                                        left: 0;
                                        top: 0;
                                        width: 6px;
                                        height: 6px;
                                        background: var(--linear-primary, #3182ce);
                                        border-radius: 50%;
                                        margin-top: 8px;
                                    "></span>
                                    후면벽: 각 패널 폭의 합계가 카 폭(W) 치수 대비 ±3mm 이내
                                </li>
                                <li style="margin-bottom: 0; padding-left: 20px; position: relative;">
                                    <span style="
                                        position: absolute;
                                        left: 0;
                                        top: 0;
                                        width: 6px;
                                        height: 6px;
                                        background: var(--linear-primary, #3182ce);
                                        border-radius: 50%;
                                        margin-top: 8px;
                                    "></span>
                                    허용 공차를 벗어나는 경우 해당 부위는 부적합으로 판정됩니다
                                </li>
                            </ul>
                        </div>
                    </div>
                `;

                console.log('HTML 컨텐츠 설정 중...');
                content.innerHTML = reportHtml;
                console.log('HTML 컨텐츠 설정 완료');

                console.log('모달 표시 중...');
                modal.style.display = 'block';
                console.log('모달 display 스타일:', modal.style.display);

                // 추가로 확실하게 최상위에 표시되고 중앙에 위치하도록 설정
                modal.style.zIndex = '10000';
                modal.style.position = 'fixed';
                modal.style.top = '0';
                modal.style.left = '0';
                modal.style.width = '100vw';
                modal.style.height = '100vh';
                modal.style.backgroundColor = 'rgba(0, 0, 0, 0.7)';
                modal.style.backdropFilter = 'blur(3px)';

                // 모달 컨테이너 중앙 정렬 및 불투명 배경 적용
                const container = modal.querySelector('.linear-modal-container');
                if (container) {
                    container.style.position = 'absolute';
                    container.style.top = '50%';
                    container.style.left = '50%';
                    container.style.transform = 'translate(-50%, -50%)';
                    container.style.background = 'var(--linear-bg-primary, #ffffff)';
                    container.style.border = '2px solid var(--linear-border-primary, #e2e8f0)';
                    container.style.borderRadius = '12px';
                    container.style.boxShadow = '0 25px 50px rgba(0, 0, 0, 0.25)';
                    container.style.overflow = 'hidden';
                    container.style.isolation = 'isolate';
                    console.log('모달 컨테이너 중앙 정렬 및 불투명 배경 적용 완료');
                }

                // 페이지 스크롤 방지
                document.body.style.overflow = 'hidden';

                // ESC 키로 모달 닫기 (이벤트 리스너 중복 방지)
                const escHandler = function(e) {
                    if (e.key === 'Escape' && modal.style.display !== 'none') {
                        closeValidationResultModal();
                        document.removeEventListener('keydown', escHandler);
                    }
                };
                document.addEventListener('keydown', escHandler);

                // 모달 배경 클릭으로 닫기
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeValidationResultModal();
                    }
                });

                console.log('=== showValidationResultModal 함수 완료 ===');
            }

            function closeValidationResultModal() {
                console.log('모달 닫기 함수 호출됨');
                const modal = document.getElementById('validationResultModal');
                if (modal) {
                    modal.style.display = 'none';
                    // 페이지 스크롤 복원
                    document.body.style.overflow = '';
                    console.log('모달 닫기 및 스크롤 복원 완료');
                } else {
                    console.error('모달 엘리먼트를 찾을 수 없습니다!');
                }
            }

            // 전역 함수로도 등록 (HTML onclick에서 호출 가능하도록)
            window.closeValidationResultModal = closeValidationResultModal;
            window.updateJsonFields = updateJsonFields;

            // ===== 헬퍼 함수들 (코드 중복 제거) =====

            // (함수가 앞쪽으로 이동됨)

            // 패널 데이터 상태 확인 헬퍼
            window.checkPanelDataState = function(context = '') {
                const panelCount = window.panelData ? Object.keys(window.panelData).length : 0;
                const jsonValue = document.getElementById('panelJsonData')?.value;
                const hasJsonData = jsonValue && jsonValue.trim() !== '{}' && jsonValue.trim() !== '';

                console.log(`📊 패널 데이터 상태 (${context}):`);
                console.log(`  - window.panelData: ${panelCount}개 패널`);
                console.log(`  - JSON 필드: ${hasJsonData ? '있음' : '없음'}`);

                return { panelCount, hasJsonData, isEmpty: panelCount === 0 && !hasJsonData };
            };

            // 패널 데이터 백업/복원 헬퍼
            window.backupPanelData = function() {
                const backup = window.panelData ? JSON.parse(JSON.stringify(window.panelData)) : null;

                // 모바일에서는 추가로 로컬 스토리지에도 백업
                const isMobile = window.innerWidth <= 768;
                if (isMobile && backup) {
                    try {
                        localStorage.setItem('panelData_backup', JSON.stringify(backup));
                        console.log('📱 모바일 로컬 스토리지 백업 완료');
                    } catch (e) {
                        console.warn('📱 로컬 스토리지 백업 실패:', e);
                    }
                }

                return backup;
            };

            window.restorePanelData = function(backup, context = '') {
                if (!backup) return false;

                console.log(`🔧 패널 데이터 복원 (${context})`);
                window.panelData = backup;

                // UI 재렌더링
                if (typeof window.renderPanelInfo === 'function') {
                    Object.keys(backup).forEach(panelNumber => {
                        const panelDataItem = backup[panelNumber];
                        if (panelDataItem) {
                            window.renderPanelInfo(panelNumber, panelDataItem);
                        }
                    });
                }
                return true;
            };

            // Function to render panel info on the visual panel elements (available in all modes)
            window.renderPanelInfo = function(panelNumber, data) {
                // 패널 제외 상태 확인 - 제외된 패널은 정보를 렌더링하지 않음
                const excludePanelCornersCheckbox = document.getElementById('excludePanelCorners');
                const excludeTransomCheckbox = document.getElementById('excludeTransom');

                const panelCornersExcluded = excludePanelCornersCheckbox ? excludePanelCornersCheckbox.checked : false;
                const transomExcluded = excludeTransomCheckbox ? excludeTransomCheckbox.checked : false;

                // 제외된 패널인지 확인
                if ((panelCornersExcluded && (panelNumber === '1' || panelNumber === '11')) ||
                    (transomExcluded && (panelNumber === '12' || panelNumber === 'transom'))) {
                    // 제외된 패널은 정보를 초기화하고 렌더링하지 않음
                    const selectors = [
                        `.panel-${panelNumber}`,
                        `[data-panel="${panelNumber}"]`
                    ];

                    selectors.forEach(selector => {
                        const panels = document.querySelectorAll(selector);
                        panels.forEach(panel => {
                            const existingInfo = panel.querySelector('.panel-info');
                            if (existingInfo) {
                                existingInfo.remove();
                            }
                            // 기본 텍스트로 초기화
                            if (panelNumber === '12') {
                                panel.innerHTML = 'T';
                            } else {
                                panel.innerHTML = panelNumber;
                            }
                        });
                    });
                    return; // 제외된 패널은 더 이상 처리하지 않음
                }

                // 중복 렌더링 방지 캐시
                if (!window.panelRenderCache) {
                    window.panelRenderCache = {};
                }

                // 동일한 데이터로 중복 렌더링 방지
                const dataHash = JSON.stringify(data);
                if (window.panelRenderCache[panelNumber] === dataHash) {
                    return; // 이미 동일한 데이터로 렌더링됨, 스킵
                }
                window.panelRenderCache[panelNumber] = dataHash;

                // 데이터가 undefined이거나 null인 경우 처리
                if (!data || typeof data !== 'object') {
                    console.log(`Panel ${panelNumber}: 데이터가 없거나 유효하지 않음, 패널 초기화`);
                    // 패널을 기본 상태로 초기화
                    const selectors = [
                        `.panel-${panelNumber}`,
                        `[data-panel="${panelNumber}"]`
                    ];

                    selectors.forEach(selector => {
                        const panels = document.querySelectorAll(selector);
                        panels.forEach(panel => {
                            // 기존 정보 제거
                            const existingInfo = panel.querySelector('.panel-info');
                            if (existingInfo) {
                                existingInfo.remove();
                            }

                            // 기본 텍스트로 초기화
                            if (panelNumber === '12' || panelNumber === 'transom') {
                                panel.innerHTML = 'T';
                            } else {
                                panel.innerHTML = panelNumber;
                            }

                            // 클래스 정리
                            panel.classList.remove('has-info');
                        });
                    });
                    return;
                }


                // Find all panel elements with this number using multiple selectors
                const selectors = [
                    `.panel-${panelNumber}`,
                    `[data-panel="${panelNumber}"]`,
                    `#panel-${panelNumber}`,
                    `.panel[data-number="${panelNumber}"]`
                ];

                let panels = [];
                selectors.forEach(selector => {
                    const found = document.querySelectorAll(selector);
                    if (found.length > 0) {
                        panels.push(...found);
                    }
                });

                // Remove duplicates
                panels = [...new Set(panels)];

                if (panels.length === 0) {
                    console.warn(`No panel elements found for panel ${panelNumber}`);
                    return;
               }

                // Get parent values for comparison
                const parentMaterialType = document.getElementById('materialType')?.value || '';
                const parentCarHeight = document.getElementById('carInsideHeight')?.value || '';

                panels.forEach(panel => {
                    // Remove existing info and original panel numbers when data exists
                    const existingInfo = panel.querySelector('.panel-info');
                    if (existingInfo) {
                        existingInfo.remove();
                    }

                    // Check if there's meaningful data
                    const hasData = data.materialType || data.width || data.height || data.notes ||
                                   data.drillingWidth || data.drillingHeight;

                    if (hasData) {
                        panel.classList.add('has-info');

                        // Remove original panel number text nodes when data exists
                        const expectedText = panelNumber === '12' ? 'T' : panelNumber;
                        const textNodes = Array.from(panel.childNodes).filter(node =>
                            node.nodeType === Node.TEXT_NODE && (
                                node.textContent.trim() === panelNumber ||
                                node.textContent.trim() === expectedText
                            )
                        );
                        textNodes.forEach(node => node.remove());

                        // Remove any span or div elements that only contain the panel number or 'T'
                        const numberElements = Array.from(panel.children).filter(el =>
                            (el.textContent.trim() === panelNumber ||
                             el.textContent.trim() === expectedText) &&
                            !el.classList.contains('panel-info')
                        );
                        numberElements.forEach(el => el.remove());

                        // Remove existing panel-info elements to prevent duplication
                        const existingInfoElements = Array.from(panel.children).filter(el =>
                            el.classList.contains('panel-info')
                        );
                        existingInfoElements.forEach(el => el.remove());

                        // Create info display
                        const infoDiv = document.createElement('div');
                        infoDiv.className = 'panel-info';
                        infoDiv.style.cssText = 'text-align: center; font-size: 0.9rem; line-height: 1.2;';

                        // Start with panel number (or 'T' for transom)
                        let infoText = panelNumber === '12' ? 'T' : panelNumber;

                // Add panel type for corner panels (1번, 11번)
                if (panelNumber === '1' || panelNumber === '11') {
                    const typeValue = data.panelType || data.panel_type_detail;
                    if (typeValue) {
                        const typeAbbr = typeValue === '일체형' ? '일체' : '분리';
                        infoText += `<br><span class="panel-type" style="font-size: 0.8rem; color: var(--linear-text-secondary);">${typeAbbr}</span>`;
                    }
                        }

                        // Only show material type if different from parent
                        if (data.materialType && data.materialType !== parentMaterialType) {
                            infoText += `<br><span class="material">${data.materialType}</span>`;
                        }

                        // Show dimensions with width prominently displayed in blue
                        if (data.width && data.height) {
                            // Only show height if different from parent car height
                            const showHeight = !parentCarHeight || data.height !== parentCarHeight;

                            if (showHeight) {
                                infoText += `<br><span class="dimensions">
                                    <span class="panel-width-value" style="font-size: 1.2rem; font-weight: bold;">${data.width}</span>×${data.height}
                                </span>`;
                            } else {
                                // Only show width prominently when height is same as car height
                                infoText += `<br><span class="width-only panel-width-value" style="font-size: 1.4rem; font-weight: bold;">${data.width}</span>`;
                            }
                        }

                        // 타공 정보가 있으면 방향에 따라 표시
                        if (data.drillingWidth || data.drillingHeight) {

                            // 타공 크기 정보 생성
                            const width = data.drillingWidth || '?';
                            const height = data.drillingHeight || '?';
                            const drillingInfo = `<span class="drilling-info">${width}×${height}타공</span>`;

                            // 패널 위치에 따른 타공정보 표시 방향
                            const panelNum = panelNumber === '12' ? '12' : panelNumber;


                            // 패널별 방향 정의
                            if (['2', '3', '4'].includes(panelNum)) {
                                // 2,3,4번 패널: 오른쪽으로 표시
                                infoText += `<span class="drilling-right">${drillingInfo}</span>`;
                            } else if (['5', '6', '7'].includes(panelNum)) {
                                // 5,6,7번 패널: 아래 방향으로 이동 후 표시
                                infoText += `<br><span class="drilling-down">${drillingInfo}</span>`;
                            } else if (['8', '9', '10'].includes(panelNum)) {
                                // 8,9,10번 패널: 왼쪽으로 표시
                                infoText += `<span class="drilling-left">${drillingInfo}</span>`;
                            } else if (['1', '11', '12'].includes(panelNum)) {
                                // 1번, 11번, 트랜썸: 위로 이동 후 표시
                                infoText = `<span class="drilling-up">${drillingInfo}</span><br>` + infoText;
                            }

                        }

                        infoDiv.innerHTML = infoText;
                        panel.appendChild(infoDiv);

                        // console.log(`🔧 패널 ${panelNumber} DOM 업데이트 완료:`, {
                        //     panelHasInfo: panel.classList.contains('has-info'),
                        //     infoDiv: infoDiv,
                        //     finalHTML: infoDiv.innerHTML,
                        //     infoVisible: infoDiv.offsetWidth > 0 && infoDiv.offsetHeight > 0,
                        //     panelVisible: panel.offsetWidth > 0 && panel.offsetHeight > 0
                        // });
                    } else {
                        panel.classList.remove('has-info');
                        console.log(`Panel ${panelNumber} has no data to display`);
                    }
                });
            };

            // 저장 및 검증 버튼 이벤트 처리
            const saveBtn = document.getElementById('saveBtn');
            const validateBtn = document.getElementById('validateBtn');
            const mainForm = document.querySelector('form');
            
            
            // 측정값 검증 버튼 이벤트 핸들러 추가
            if (validateBtn) {
                validateBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    performMeasurementValidation();
                });
            } 

            if (saveBtn) {

                // 클릭 이벤트 리스너 추가
                saveBtn.addEventListener('click', function(e) {

                    // JSON 필드 업데이트 먼저 수행 (헬퍼 함수 사용)
                    window.safeUpdateJsonFields('폼 제출 전');

                    // preventDefault to avoid double submission
                    e.preventDefault();

                    // 저장 시작 시 플래그 설정 (모달 방지)
                    isFormSubmitting = true;
                    formHasUnsavedChanges = false;

                    // form submit 실행
                    if (mainForm) {
                        // 폼 제출 실행
                        mainForm.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
                    }
                });
            }
            
            if (mainForm) {
                // 폼 submit 이벤트 리스너 추가
                mainForm.addEventListener('submit', function(e) {
                    console.log('=== 폼 submit 이벤트 발생 ===');

                    // Edit mode 상태 확인
                    const editIdInput = document.querySelector('input[name="edit_id"]');
                    console.log('Is Edit Mode:', editIdInput?.value ? 'YES' : 'NO');

                    // 제출 전 마지막으로 JSON 필드 업데이트 (모바일 입력값 포함)
                    window.safeUpdateJsonFields('폼 제출 전');
                    console.log('제출 전 JSON 필드 업데이트 완료');

                    // 프로젝트 정보 디버그 - 더 상세하게
                    const projectTypeElement = document.getElementById('projectType');
                    const projectTypeValue = projectTypeElement?.value;
                    const panelLayoutValue = document.getElementById('panelLayout')?.value;

                    console.log('=== FORM SUBMIT PROJECT INFO DEBUG ===');
                    console.log('Project Type Element:', projectTypeElement);
                    console.log('Project Type Value:', projectTypeValue);
                    console.log('Project Type Name Attr:', projectTypeElement?.name);
                    console.log('Project Type Element HTML:', projectTypeElement?.outerHTML);
                    console.log('Panel Layout Value:', panelLayoutValue);
                    console.log('Panel Layout Name Attr:', document.getElementById('panelLayout')?.name);

                    // 버튼 상태도 확인 (디버그용)
                    const newBtnState = document.getElementById('newBtn')?.classList.contains('linear-btn-primary');
                    const modBtnState = document.getElementById('modBtn')?.classList.contains('linear-btn-primary');
                    console.log('신규 버튼 활성화:', newBtnState);
                    console.log('MOD 버튼 활성화:', modBtnState);

                    // 실제 전송되기 직전 값 확인 (수정하지 않음)
                    if (projectTypeElement) {
                        console.log('실제 전송될 project_type 값:', projectTypeElement.value);
                        console.log('✅ 버튼 상태와 값 확인 완료 - 강제 수정 없이 진행');
                    }

                    // FormData로 실제 전송되는 데이터 확인
                    let formData = new FormData(this);
                    console.log('=== ACTUAL FORM DATA ===');
                    for (let [key, value] of formData.entries()) {
                        if (key === 'project_type' || key === 'panel_layout' || key === 'edit_id') {
                            console.log(`${key}: "${value}"`);
                        }
                    }
                    console.log('=== END ACTUAL FORM DATA ===');

                    // Hidden input 값 확인
                    const projectInput = document.getElementById('projectType');
                    const panelInput = document.getElementById('panelLayout');

                    console.log('=== DOM DIRECT CHECK ===');
                    console.log('Project Input Value:', projectInput?.value);
                    console.log('Panel Input Value:', panelInput?.value);
                    console.log('=== END DOM DIRECT CHECK ===');

                    console.log('=== END FORM SUBMIT PROJECT INFO ===');

                    // Basic validation
                    const siteName = document.getElementById('siteName');
                    const measurer = document.getElementById('measurer');
                    const measurementDate = document.getElementById('measurementDate');
                    const carInsideWidth = document.getElementById('carInsideWidth');
                    const carInsideDepth = document.getElementById('carInsideDepth');
                    const carInsideHeight = document.getElementById('carInsideHeight');

                    if (!siteName?.value.trim()) {
                        e.preventDefault();
                        showWarning('입력 필요', '현장명을 입력해주세요.', () => {
                            siteName?.focus();
                        });
                        return;
                    }

                    if (!measurer?.value.trim()) {
                        e.preventDefault();
                        showWarning('입력 필요', '측정자명을 입력해주세요.', () => {
                            measurer?.focus();
                        });
                        return;
                    }

                    if (!measurementDate?.value) {
                        e.preventDefault();
                        showWarning('입력 필요', '측정일자를 선택해주세요.', () => {
                            measurementDate?.focus();
                        });
                        return;
                    }

                    if (!carInsideWidth?.value || parseInt(carInsideWidth.value) <= 0) {
                        e.preventDefault();
                        showWarning('입력 필요', '카 내부 가로 치수를 올바르게 입력해주세요.', () => {
                            carInsideWidth?.focus();
                        });
                        return;
                    }

                    if (!carInsideDepth?.value || parseInt(carInsideDepth.value) <= 0) {
                        e.preventDefault();
                        showWarning('입력 필요', '카 내부 깊이 치수를 올바르게 입력해주세요.', () => {
                            carInsideDepth?.focus();
                        });
                        return;
                    }

                    if (!carInsideHeight?.value || parseInt(carInsideHeight.value) <= 0) {
                        e.preventDefault();
                        showWarning('입력 필요', '카 내부 높이 치수를 올바르게 입력해주세요.', () => {
                            carInsideHeight?.focus();
                        });
                        return;
                    }

                    // Update JSON fields before submission (헬퍼 함수 사용)
                    window.safeUpdateJsonFields('최종 제출 전');

                    formData = new FormData(mainForm);
                    for (let [key, value] of formData.entries()) {
                        console.log(`${key}: ${value}`);
                        if (key === 'measurer') {
                            console.log('=== 측정자 필드 확인 ===');
                            console.log('POST로 전송될 measurer 값:', value);
                            console.log('input 요소의 실제 값:', document.getElementById('measurer')?.value);
                        }
                    }

                    // preventDefault로 기본 폼 제출을 막고 Ajax로 처리
                    e.preventDefault();

                    // 저장 버튼 비활성화 (중복 제출 방지)
                    const saveButton = document.getElementById('saveBtn');
                    if (saveButton) {
                        saveButton.disabled = true;
                        saveButton.innerHTML = '<i class="bi bi-hourglass-split"></i> 저장 중...';
                    }

                    console.log('폼 데이터 제출 중...');

                    // 카 내부 치수 디버깅
                    const carWidth = document.getElementById('carInsideWidth');
                    const carDepth = document.getElementById('carInsideDepth');
                    const carHeight = document.getElementById('carInsideHeight');
                    console.log('=== 카 내부 치수 디버깅 ===');
                    console.log('carInsideWidth 요소:', carWidth);
                    console.log('carInsideWidth 값:', carWidth?.value);
                    console.log('carInsideDepth 요소:', carDepth);
                    console.log('carInsideDepth 값:', carDepth?.value);
                    console.log('carInsideHeight 요소:', carHeight);
                    console.log('carInsideHeight 값:', carHeight?.value);

                    // Ajax 요청으로 데이터 전송
                    formData = new FormData(mainForm);

                    // 체크박스 값 명시적 처리 (체크되지 않은 경우 0으로 설정)
                    const excludeTransomCheckbox = document.getElementById('excludeTransom');
                    if (excludeTransomCheckbox) {
                        formData.set('transom_excluded', excludeTransomCheckbox.checked ? '1' : '0');
                    }

                    const excludePanelCornersCheckbox = document.getElementById('excludePanelCorners');
                    if (excludePanelCornersCheckbox) {
                        formData.set('panel_corners_excluded', excludePanelCornersCheckbox.checked ? '1' : '0');
                    }

                    // project_type 값 명시적 처리
                    const projectTypeInput = document.getElementById('projectType');
                    if (projectTypeInput) {
                        formData.set('project_type', projectTypeInput.value);
                        console.log('🔍 FormData에 project_type 설정:', projectTypeInput.value);
                    }

                    // material_type과 material_thickness 명시적 처리
                    const materialTypeInput = document.getElementById('materialType');
                    if (materialTypeInput) {
                        formData.set('material_type', materialTypeInput.value);
                    }
                    const materialThicknessInput = document.getElementById('materialThickness');
                    if (materialThicknessInput) {
                        formData.set('material_thickness', materialThicknessInput.value);
                    }
                    const elevatorCountInput = document.getElementById('elevatorCount');
                    if (elevatorCountInput) {
                        formData.set('elevator_count', elevatorCountInput.value);
                    }
                    // notes 명시적 처리
                    const notesInput = document.getElementById('notes');
                    if (notesInput) {
                        formData.set('notes', notesInput.value);
                    }

                    // FormData에서 카 내부 치수 확인
                    console.log('=== FormData 카 내부 치수 ===');
                    console.log('car_inside_width:', formData.get('car_inside_width'));
                    console.log('car_inside_depth:', formData.get('car_inside_depth'));
                    console.log('car_inside_height:', formData.get('car_inside_height'));

                    // 카 내부 치수가 FormData에 없는 경우 강제로 추가
                    if (!formData.get('car_inside_width') && carWidth?.value) {
                        formData.set('car_inside_width', carWidth.value);
                        console.log('강제 추가: car_inside_width =', carWidth.value);
                    }
                    if (!formData.get('car_inside_depth') && carDepth?.value) {
                        formData.set('car_inside_depth', carDepth.value);
                        console.log('강제 추가: car_inside_depth =', carDepth.value);
                    }
                    if (!formData.get('car_inside_height') && carHeight?.value) {
                        formData.set('car_inside_height', carHeight.value);
                        console.log('강제 추가: car_inside_height =', carHeight.value);
                    }

                    // PC 버전에도 FormData 강제 설정 적용
                    const panelJsonField = document.getElementById('panelJsonData');
                    const transomJsonField = document.getElementById('transomJsonData');


                    // FormData에 강제로 JSON 값 설정 (히든 필드가 제대로 읽히지 않는 경우 대비)
                    if (panelJsonField && panelJsonField.value) {
                        formData.set('panel_data', panelJsonField.value);
                    }

                    if (transomJsonField && transomJsonField.value) {
                        formData.set('transom_data', transomJsonField.value);
                        console.log('🔍 DEBUG: FormData에 transom_data 설정:', transomJsonField.value);
                    } else {
                        console.log('🔍 DEBUG: transomJsonField가 비어있음:', transomJsonField?.value);
                    }


                    fetch('save_panel_measurement.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            console.log('저장 성공, ID:', data.measurement_id);

                            // 성공 메시지 표시
                            showSuccessToast('저장 완료', data.message);

                            // Page leave protection - 저장 완료 처리
                            if (typeof window.resetUnsavedChanges === 'function') {
                                window.resetUnsavedChanges();
                            }

                            // 저장 완료 후 패널 데이터 보존 확인 (saveAndLeave)
                            console.log('=== saveAndLeave 저장 완료 후 패널 데이터 상태 확인 ===');
                            console.log('window.panelData 상태:', window.panelData);
                            console.log('window.panelData 키 개수:', window.panelData ? Object.keys(window.panelData).length : 0);

                            // 패널 데이터가 사라졌다면 JSON 필드에서 복원
                            if (!window.panelData || Object.keys(window.panelData).length === 0) {
                                console.warn('saveAndLeave 저장 후 window.panelData가 비어있음! 복원 시도...');
                                try {
                                    const panelJsonField = document.getElementById('panelJsonData');
                                    const transomJsonField = document.getElementById('transomJsonData');

                                    if (panelJsonField && panelJsonField.value) {
                                        const recoveredPanelData = JSON.parse(panelJsonField.value);
                                        if (Object.keys(recoveredPanelData).length > 0) {
                                            console.log('JSON 필드에서 패널 데이터 복원:', recoveredPanelData);
                                            window.panelData = window.panelData || {};
                                            Object.assign(window.panelData, recoveredPanelData);
                                        }
                                    }

                                    if (transomJsonField && transomJsonField.value) {
                                        const recoveredTransomData = JSON.parse(transomJsonField.value);
                                        if (Object.keys(recoveredTransomData).length > 0) {
                                            window.panelData = window.panelData || {};
                                            Object.assign(window.panelData, recoveredTransomData);
                                        }
                                    }
                                } catch (e) {
                                    console.error('saveAndLeave 패널 데이터 복원 중 오류:', e);
                                }
                            }

                            // 리디렉션 처리
                            if (data.should_redirect && data.redirect_url) {
                                console.log('페이지 리디렉션:', data.redirect_url);
                                setTimeout(() => {
                                    window.location.href = data.redirect_url;
                                }, 1500); // 성공 토스트를 보여주기 위해 1.5초 대기
                                return;
                            }

                            // 신규 저장 후 수정 모드로 전환
                            if (!window.location.search.includes('edit=') && data.measurement_id) {
                                console.log('신규 저장 완료, 수정 모드로 전환:', data.measurement_id);

                                // URL을 수정 모드로 변경 (페이지 리로드 없이)
                                const newUrl = new URL(window.location);
                                newUrl.searchParams.set('edit', data.measurement_id);
                                window.history.replaceState({}, '', newUrl);

                                // 전역 변수로 수정 모드 상태 업데이트
                                window.isEditMode = true;
                                window.editId = data.measurement_id;

                                // 폼의 hidden input 업데이트
                                const editIdInput = document.querySelector('input[name="edit_id"]');
                                if (editIdInput) {
                                    editIdInput.value = data.measurement_id;
                                } else {
                                    // hidden input이 없으면 생성
                                    const hiddenInput = document.createElement('input');
                                    hiddenInput.type = 'hidden';
                                    hiddenInput.name = 'edit_id';
                                    hiddenInput.value = data.measurement_id;
                                    document.getElementById('measurementForm').appendChild(hiddenInput);
                                }

                                console.log('수정 모드로 전환 완료 - URL:', newUrl.toString());

                                // UI 상태 업데이트
                                updateEditModeUI();
                            }
                        } else {
                            console.error('저장 실패:', data.message);

                            // 저장 실패 시 플래그 복원
                            isFormSubmitting = false;
                            formHasUnsavedChanges = true;

                            // 오류 메시지 표시
                            showError('저장 실패', data.message);
                        }
                    })
                    .catch(error => {
                        console.error('네트워크 오류:', error);

                        // 네트워크 오류 시 플래그 복원
                        isFormSubmitting = false;
                        formHasUnsavedChanges = true;

                        // 네트워크 오류 메시지 표시
                        showError('네트워크 오류', '네트워크 오류가 발생했습니다. 다시 시도해주세요.');
                    })
                    .finally(() => {
                        // 저장 버튼 복원
                        if (saveButton) {
                            saveButton.disabled = false;
                            saveButton.innerHTML = '<i class="bi bi-save"></i> 측정 저장';
                        }
                    });
                });
            }
            
            // 모바일 버튼 이벤트 리스너 등록 함수
            function setupMobileButtonEvents() {
                console.log('📱 모바일 버튼 이벤트 리스너 설정 시작');
                
                // 측정값 검증 버튼
                const mobileValidateBtn = document.getElementById('mobileValidateBtn');
                if (mobileValidateBtn && !mobileValidateBtn.hasAttribute('data-mobile-listener-added')) {
                    mobileValidateBtn.setAttribute('data-mobile-listener-added', 'true');
                    mobileValidateBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        console.log('📱 모바일 측정값 검증 버튼 클릭');
                        if (typeof performMeasurementValidation === 'function') {
                            performMeasurementValidation();
                        } else {
                            console.error('❌ performMeasurementValidation 함수를 찾을 수 없습니다');
                        }
                    });
                    console.log('📱 모바일 측정값 검증 버튼 이벤트 등록 완료');
                }
                
                // 측정 저장 버튼
                const mobileSaveBtn = document.getElementById('mobileSaveBtn');
                if (mobileSaveBtn && !mobileSaveBtn.hasAttribute('data-mobile-listener-added')) {
                    mobileSaveBtn.setAttribute('data-mobile-listener-added', 'true');
                    mobileSaveBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        console.log('📱 모바일 측정 저장 버튼 클릭');
                        
                        // JSON 필드 업데이트 먼저 수행
                        if (typeof window.safeUpdateJsonFields === 'function') {
                            window.safeUpdateJsonFields('모바일 폼 제출 전');
                        }
                        
                        // PC 버전과 동일한 저장 로직 실행
                        const saveBtn = document.getElementById('saveBtn');
                        if (saveBtn) {
                            saveBtn.click(); // PC 버전의 저장 버튼 클릭
                        } else {
                            console.error('❌ PC 저장 버튼을 찾을 수 없습니다');
                        }
                    });
                    console.log('📱 모바일 측정 저장 버튼 이벤트 등록 완료');
                }
                
                // 돌아가기 버튼
                const mobileBackBtn = document.getElementById('mobileBackBtn');
                if (mobileBackBtn && !mobileBackBtn.hasAttribute('data-mobile-listener-added')) {
                    mobileBackBtn.setAttribute('data-mobile-listener-added', 'true');
                    mobileBackBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        console.log('📱 모바일 돌아가기 버튼 클릭');
                        if (typeof handlePageLeaveAttempt === 'function') {
                            handlePageLeaveAttempt('mobile-back', 'index.php');
                        } else {
                            console.error('❌ handlePageLeaveAttempt 함수를 찾을 수 없습니다');
                            window.location.href = 'index.php';
                        }
                    });
                    console.log('📱 모바일 돌아가기 버튼 이벤트 등록 완료');
                }
                
                console.log('📱 모바일 버튼 이벤트 리스너 설정 완료');
            }

            // Re-attach events when mobile cards are populated
            const originalSyncFunction = window.syncMobilePanels;
            window.syncMobilePanels = function() {
                if (originalSyncFunction) {
                    originalSyncFunction();
                }
                setTimeout(attachPanelEvents, 100);
                
                // 모바일 버튼 이벤트 설정
                setTimeout(setupMobileButtonEvents, 200);
            };
            } catch (e) {
                console.error('DOMContentLoaded 이벤트 리스너 오류:', e);
            }
        });
    </script>

    <?php if ($edit_mode): ?>
    <script>

        // Verify edit_id hidden input exists
        const editIdInput = document.querySelector('input[name="edit_id"]');

        // Load existing panel and transom data in edit mode
        document.addEventListener('DOMContentLoaded', function() {
            // Wait for panelData to be initialized
            setTimeout(function() {
                // Panel data
                const editPanelData = <?= json_encode($edit_panel_data, JSON_HEX_QUOT | JSON_HEX_TAG) ?>;
                const editTransomData = <?= json_encode($edit_transom_data, JSON_HEX_QUOT | JSON_HEX_TAG) ?>;


                // Initialize global panelData object if not exists
                if (typeof window.panelData === 'undefined') {
                    window.panelData = {};
                }

                // Merge panel data into global panelData object
                if (editPanelData && typeof editPanelData === 'object') {
                    Object.keys(editPanelData).forEach(panelNumber => {
                        const panelDataItem = editPanelData[panelNumber];
                        if (panelDataItem && typeof panelDataItem === 'object') {

                            // Store in global panelData
                            window.panelData[panelNumber] = panelDataItem;

                            // Update panel display - force render panel info
                            if (typeof window.renderPanelInfo === 'function') {
                                window.renderPanelInfo(panelNumber, panelDataItem);
                            } else {
                                console.warn('renderPanelInfo function not available yet, will retry later');
                            }
                        }
                    });
                }

                // Merge transom data (panel 12) - 호환성 지원
                if (editTransomData && typeof editTransomData === 'object') {
                    console.log('🔍 DEBUG: Edit 모드 - editTransomData:', editTransomData);
                    
                    // 새 형식인지 확인 (키가 'transom'인 객체)
                    if (editTransomData['transom']) {
                        window.panelData['transom'] = editTransomData['transom'];
                        // 12번 패널로도 저장 (호환성을 위해)
                        window.panelData['12'] = editTransomData['transom'];

                        if (typeof window.renderPanelInfo === 'function') {
                            window.renderPanelInfo('12', editTransomData['transom']);
                        }
                        console.log('🔍 DEBUG: Edit 모드 - transom 데이터 로드됨 (transom 키)');
                    }
                    // 이전 형식인지 확인 (키가 '12'인 객체)
                    else if (editTransomData['12']) {
                        window.panelData['12'] = editTransomData['12'];
                        // transom 키로도 저장
                        window.panelData['transom'] = editTransomData['12'];

                        if (typeof window.renderPanelInfo === 'function') {
                            window.renderPanelInfo('12', editTransomData['12']);
                        }
                        console.log('🔍 DEBUG: Edit 모드 - transom 데이터 로드됨 (12 키)');
                    }
                    // 직접적인 transom 데이터
                    else if (editTransomData.width || editTransomData.height || editTransomData.materialType) {
                        window.panelData['12'] = editTransomData;
                        // transom 키로도 저장
                        window.panelData['transom'] = editTransomData;

                        if (typeof window.renderPanelInfo === 'function') {
                            window.renderPanelInfo('12', editTransomData);
                        }
                        console.log('🔍 DEBUG: Edit 모드 - transom 데이터 로드됨 (직접 데이터)');
                    }
                    // 키-값 객체 형식 (여러 패널이 있을 수 있음)
                    else {
                        Object.keys(editTransomData).forEach(panelNumber => {
                            const transomDataItem = editTransomData[panelNumber];
                            if (transomDataItem && typeof transomDataItem === 'object') {
                                // Store in global panelData
                                window.panelData[panelNumber] = transomDataItem;
                                
                                // transom 데이터인 경우 transom 키로도 저장
                                if (panelNumber === 'transom') {
                                    window.panelData['12'] = transomDataItem;
                                } else if (panelNumber === '12') {
                                    window.panelData['transom'] = transomDataItem;
                                }

                                // Update panel display - force render panel info
                                if (typeof window.renderPanelInfo === 'function') {
                                    window.renderPanelInfo(panelNumber === 'transom' ? '12' : panelNumber, transomDataItem);
                                } else {
                                    console.warn('renderPanelInfo function not available yet, will retry later');
                                }
                            }
                        });
                    }
                }

                // Update hidden JSON fields after loading all data (안전한 호출 사용)
                window.safeUpdateJsonFields('Edit 모드 로딩 완료');

                console.log('Edit mode: Final panelData object', window.panelData);

                // Load basic form fields from edit data
                const editData = <?= json_encode($edit_data, JSON_HEX_QUOT | JSON_HEX_TAG) ?>;
                console.log('Edit mode: Loading basic form data', editData);

                if (editData) {
                    // 현장명 (PC와 모바일 모두)
                    const siteNameInput = document.getElementById('siteName');
                    if (siteNameInput && editData.site_name) {
                        siteNameInput.value = editData.site_name;
                    }
                    // 🎯 PHASE 2: 모바일 필드 값 설정 비활성화 (반응형으로 통합됨)
                    // 더 이상 모바일 전용 필드가 없으므로 이 로직 불필요
                    if (false) {
                        const setMobileFieldValues = function() {
                            console.log('⚠️ DEPRECATED: 모바일 필드 값 설정 로직은 더 이상 사용되지 않습니다.');
                        };
                        setMobileFieldValues();
                    }

                    // 측정일자 (PC와 모바일 모두)
                    const measurementDateInput = document.getElementById('measurementDate');
                    if (measurementDateInput && editData.measurement_date) {
                        measurementDateInput.value = editData.measurement_date;
                    }

                    // 측정자 (PC와 모바일 모두)
                    const measurerInput = document.getElementById('measurer');
                    if (measurerInput && editData.measurer_name) {
                        measurerInput.value = editData.measurer_name;
                        console.log('Edit mode: Set measurer to', editData.measurer_name);
                    }

                    // 카 내부 치수 (PC와 모바일 모두)
                    const carWidthInput = document.getElementById('carInsideWidth');
                    if (carWidthInput && editData.car_inside_width) {
                        carWidthInput.value = editData.car_inside_width;
                    }

                    const carDepthInput = document.getElementById('carInsideDepth');
                    if (carDepthInput && editData.car_inside_depth) {
                        carDepthInput.value = editData.car_inside_depth;
                    }

                    const carHeightInput = document.getElementById('carInsideHeight');
                    if (carHeightInput && editData.car_inside_height) {
                        carHeightInput.value = editData.car_inside_height;
                    }

                    // 재질 정보
                    const materialTypeInput = document.getElementById('materialType');
                    if (materialTypeInput && editData.material_type) {
                        materialTypeInput.value = editData.material_type;
                    }

                    const materialThicknessInput = document.getElementById('materialThickness');
                    if (materialThicknessInput && editData.material_thickness) {
                        materialThicknessInput.value = editData.material_thickness;
                    }

                    // 엘리베이터 대수
                    const elevatorCountInput = document.getElementById('elevatorCount');
                    if (elevatorCountInput && editData.elevator_count) {
                        elevatorCountInput.value = editData.elevator_count;
                    }

                    // 특이사항
                    const notesInput = document.getElementById('notes');
                    if (notesInput && editData.notes) {
                        notesInput.value = editData.notes;
                    }

                    // Project Type 버튼 상태 설정은 initializeCheckboxStates 함수에서 처리됨
                    // (중복 실행 방지를 위해 제거 - hidden input 값이 이미 설정되어 있음)
                    console.log('Edit mode: Project type will be set by initializeCheckboxStates from hidden input:', editData.project_type);

                    // Edit 모드에서 체크박스 상태도 초기화
                    const panelCornersExcluded = editData.panel_corners_excluded || 0;
                    const transomExcluded = editData.transom_excluded || 0;

                    const excludePanelCornersCheckbox = document.getElementById('excludePanelCorners');
                    const excludeTransomCheckbox = document.getElementById('excludeTransom');

                    if (excludePanelCornersCheckbox) {
                        excludePanelCornersCheckbox.checked = panelCornersExcluded === 1;
                    }
                    if (excludeTransomCheckbox) {
                        excludeTransomCheckbox.checked = transomExcluded === 1;
                    }

                    // 체크박스 상태 변경 후 UI 업데이트
                    if (typeof updatePanelDisplay === 'function') {
                        updatePanelDisplay();
                    }

                    }

                // Optimized: Single delayed render for any panels that might need visual corrections
                setTimeout(function() {
                    if (typeof window.renderPanelInfo === 'function') {
                        // Only re-render panels with drilling info that might need visual correction
                        Object.keys(window.panelData || {}).forEach(panelNumber => {
                            const panelDataItem = window.panelData[panelNumber];
                            if (panelDataItem && (panelDataItem.drillingWidth || panelDataItem.drillingHeight)) {
                                window.renderPanelInfo(panelNumber, panelDataItem);
                            }
                        });
                    } else {
                        console.error('renderPanelInfo function still not available after timeout');
                    }
                }, 1500); // Additional 1.5 second delay for complete DOM readiness
            }, 500); // Wait 500ms for other scripts to initialize
        });
    </script>
    <?php endif; ?>

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

    <!-- Page Leave Protection System -->
    <script>
        // 수정 상태 추적 시스템
        let formHasUnsavedChanges = false;
        let initialFormData = {};
        let isFormSubmitting = false;
        let userHasActuallyInteracted = false;  // 사용자가 실제로 상호작용했는지 추적

        document.addEventListener('DOMContentLoaded', function() {
            console.log('Page Leave Protection: Initializing...');

            // 초기 폼 데이터 저장
            captureInitialFormData();

            // 폼 변경 감지 이벤트 등록
            setupChangeDetection();

            // 페이지 이탈 방지 이벤트 등록
            setupPageLeaveProtection();

            // 폼 제출 시 플래그 설정
            setupFormSubmitTracking();

            console.log('Page Leave Protection: Initialized successfully');
        });

        // 초기 폼 데이터 캡처
        function captureInitialFormData() {
            const form = document.getElementById('measurementForm');
            if (form) {
                initialFormData = {};
                const formData = new FormData(form);

                // FormData를 일반 객체로 변환하여 저장
                for (let [key, value] of formData.entries()) {
                    // 시스템 필드나 숨겨진 필드는 제외
                    if (key.includes('_token') || key === 'edit_id') continue;
                    initialFormData[key] = value;
                }

                // 패널 데이터도 포함
                if (window.panelData) {
                    initialFormData['panel_data'] = JSON.stringify(window.panelData);
                }

                console.log('Initial form data captured:', Object.keys(initialFormData).length, 'fields');
            }
        }

        // 현재 폼 데이터와 초기 데이터 비교
        function hasActualChanges() {
            const form = document.getElementById('measurementForm');
            if (!form || !initialFormData) return false;

            const currentFormData = {};
            const formData = new FormData(form);

            // 현재 폼 데이터 수집
            for (let [key, value] of formData.entries()) {
                if (key.includes('_token') || key === 'edit_id') continue;
                currentFormData[key] = value;
            }

            // 패널 데이터 포함
            if (window.panelData) {
                currentFormData['panel_data'] = JSON.stringify(window.panelData);
            }

            // 초기 데이터와 비교
            const initialKeys = Object.keys(initialFormData);
            const currentKeys = Object.keys(currentFormData);

            // 키 개수가 다르면 변경됨
            if (initialKeys.length !== currentKeys.length) {
                console.log('Form has changes: different field count');
                return true;
            }

            // 각 필드 값 비교
            for (let key of initialKeys) {
                const initialValue = initialFormData[key] || '';
                const currentValue = currentFormData[key] || '';

                if (initialValue !== currentValue) {
                    console.log(`Form has changes: ${key} changed from "${initialValue}" to "${currentValue}"`);
                    return true;
                }
            }

            console.log('Form has no actual changes');
            return false;
        }

        // 폼 변경 감지 설정
        function setupChangeDetection() {
            // 일반 input, select, textarea 요소들
            const inputs = document.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                // skip hidden inputs, system fields, and specific system names
                if (input.type === 'hidden' ||
                    input.classList.contains('system-field') ||
                    input.name === 'edit_id' ||
                    input.name === '_token' ||
                    input.name === 'csrf_token' ||
                    input.id === 'panelJsonData' ||
                    input.id === 'transomJsonData') {
                    return;
                }

                input.addEventListener('change', function() {
                    // 사용자가 직접 변경한 것만 추적 (프로그래밍적 변경 제외)
                    if (document.activeElement === input) {
                        userHasActuallyInteracted = true;
                        console.log('User interaction detected on:', input.name || input.id);
                    }

                    // 실제 변경사항이 있는지 확인
                    setTimeout(() => {
                        const actuallyChanged = hasActualChanges();
                        if (actuallyChanged && userHasActuallyInteracted) {
                            formHasUnsavedChanges = true;
                            console.log('Actual form change detected:', input.name || input.id);
                            updatePageTitle();
                        } else if (!actuallyChanged) {
                            formHasUnsavedChanges = false;
                            console.log('Form reverted to original state');
                            updatePageTitle();
                        }
                    }, 10); // DOM 업데이트 대기
                });

                input.addEventListener('input', function() {
                    // 사용자가 직접 입력하는 경우만 추적
                    if (document.activeElement === input) {
                        userHasActuallyInteracted = true;
                        console.log('User input detected on:', input.name || input.id);

                        if (!formHasUnsavedChanges) {
                            formHasUnsavedChanges = true;
                            updatePageTitle();
                        }
                    }
                });
            });

            // 패널 데이터 변경 감지 (패널 정보 저장 시)
            const originalSavePanelInfo = window.savePanelInfo;
            if (originalSavePanelInfo) {
                window.savePanelInfo = function(panelNumber) {
                    formHasUnsavedChanges = true;
                    updatePageTitle();
                    console.log('Panel data changed for panel:', panelNumber);
                    return originalSavePanelInfo.apply(this, arguments);
                };
            }
        }

        // 페이지 제목 업데이트 (수정 표시)
        function updatePageTitle() {
            const title = document.title;
            if (formHasUnsavedChanges && !title.includes(' *')) {
                document.title = title + ' *';
            }
        }

        // 페이지 이탈 방지 설정
        function setupPageLeaveProtection() {
            // beforeunload 이벤트 (새로고침, 창 닫기, URL 변경)
            window.addEventListener('beforeunload', function(e) {
                // 사용자 상호작용과 실제 변경사항이 있는지 확인
                if (userHasActuallyInteracted && hasActualChanges() && !isFormSubmitting) {
                    e.preventDefault();
                    // 표준 메시지 (브라우저가 자체 메시지 사용)
                    e.returnValue = '';
                    return '';
                }
            });

            // popstate 이벤트 (뒤로가기 버튼)
            window.addEventListener('popstate', function(e) {
                if (userHasActuallyInteracted && hasActualChanges() && !isFormSubmitting) {
                    e.preventDefault();
                    handlePageLeaveAttempt('back');
                    // 히스토리 상태 복원
                    history.pushState(null, null, window.location.href);
                }
            });

            // 모바일 브라우저 대응: pagehide 이벤트
            window.addEventListener('pagehide', function(e) {
                if (formHasUnsavedChanges && !isFormSubmitting) {
                    // pagehide는 preventDefault 불가능하므로
                    // 데이터 손실 방지를 위한 임시 저장 등을 수행할 수 있음
                    console.log('Page hiding with unsaved changes');
                }
            });

            // 링크 클릭 감지 및 확인
            document.addEventListener('click', function(e) {
                const target = e.target.closest('a');
                if (target && !isFormSubmitting) {
                    // 내부 앵커나 모달 링크는 제외
                    const href = target.getAttribute('href');
                    if (href && !href.startsWith('#') && !target.classList.contains('modal-link')) {
                        if (userHasActuallyInteracted && hasActualChanges()) {
                            e.preventDefault();
                            handlePageLeaveAttempt('link', href);
                        }
                    }
                }
            });
        }

        // 폼 제출 추적 설정
        function setupFormSubmitTracking() {
            const form = document.getElementById('measurementForm');
            if (form) {
                form.addEventListener('submit', function() {
                    isFormSubmitting = true;
                    formHasUnsavedChanges = false;
                    console.log('Form is being submitted');
                });
            }

            // 저장 버튼들 클릭 시 플래그 설정 (메인 저장 버튼 제외 - 중복 방지)
            const saveButtons = document.querySelectorAll('[onclick*="submitForm"], .save-btn');
            saveButtons.forEach(button => {
                // 메인 저장 버튼이 아닌 경우에만 이벤트 추가
                if (button.id !== 'saveBtn') {
                    button.addEventListener('click', function() {
                        console.log('추가 저장 버튼 클릭:', button.id);
                        isFormSubmitting = true;
                        formHasUnsavedChanges = false;
                    });
                }
            });
        }

        // 페이지 이탈 시도 처리 (SweetAlert2 사용)
        function handlePageLeaveAttempt(type, destination = null) {
            // 사용자가 실제로 상호작용을 했고 변경사항이 있는지 확인
            if (!userHasActuallyInteracted) {
                console.log('No user interaction detected, allowing page leave');
                formHasUnsavedChanges = false;
                discardChangesAndLeave(type, destination);
                return;
            }

            // 실제 변경사항이 있는지 다시 한 번 확인
            const actuallyChanged = hasActualChanges();
            if (!actuallyChanged) {
                console.log('No actual changes detected, allowing page leave');
                formHasUnsavedChanges = false;
                userHasActuallyInteracted = false;
                discardChangesAndLeave(type, destination);
                return;
            }
            const isMobile = window.innerWidth <= 768;

            // 모바일 전용 디버깅
            if (isMobile) {
                console.log('=== 📱 모바일 handlePageLeaveAttempt 호출 ===');
                console.log('📱 Type:', type, 'Destination:', destination);
                console.log('📱 현재 window.panelData:', window.panelData);
                console.log('📱 현재 패널 개수:', window.panelData ? Object.keys(window.panelData).length : 0);
                console.log('📱 JSON 필드 값:', document.getElementById('panelJsonData')?.value);

                // 모바일에서 이미 데이터가 없다면 경고
                if (!window.panelData || Object.keys(window.panelData).length === 0) {
                    console.error('🚨 모바일: handlePageLeaveAttempt 호출 시점에 이미 패널 데이터가 없음!');
                }
            }

            Swal.fire({
                title: '저장되지 않은 변경사항이 있습니다',
                html: `
                    <div style="text-align: left; margin: 16px 0;">
                        <p style="margin-bottom: 12px; color: var(--linear-text-primary); line-height: 1.5;">현재 작성 중인 내용이 저장되지 않았습니다.</p>
                        <p style="margin-bottom: 0; color: var(--linear-text-secondary); font-size: 14px; line-height: 1.4;">
                            어떻게 하시겠습니까?
                        </p>
                    </div>
                `,
                icon: 'warning',
                iconColor: 'var(--linear-warning-500)',
                showCancelButton: true,
                showCloseButton: false,
                confirmButtonText: '<i class="bi bi-save"></i> 저장하고 나가기',
                cancelButtonText: '<i class="bi bi-x-circle"></i> 저장하지 않고 나가기',
                showDenyButton: true,
                denyButtonText: '<i class="bi bi-pencil"></i> 계속 작성하기',
                allowOutsideClick: false,
                allowEscapeKey: false,
                reverseButtons: false,
                focusConfirm: true,
                customClass: {
                    popup: 'linear-swal-popup',
                    confirmButton: 'linear-btn linear-btn-primary',
                    cancelButton: 'linear-btn linear-btn-danger',
                    denyButton: 'linear-btn linear-btn-secondary'
                },
                buttonsStyling: false,
                backdrop: `
                    rgba(0, 0, 0, 0.6)
                    left top
                    no-repeat
                `
            }).then((result) => {
                if (result.isConfirmed) {
                    // 저장하고 나가기
                    saveAndLeave(type, destination);
                } else if (result.isDismissed && result.dismiss === Swal.DismissReason.cancel) {
                    // 저장하지 않고 나가기
                    discardChangesAndLeave(type, destination);
                } else if (result.isDenied) {
                    // 계속 작성하기 - 아무것도 안 함
                    console.log('User chose to continue editing');
                }
            });
        }

        // 저장하고 나가기
        function saveAndLeave(type, destination) {
            console.log('🚀 saveAndLeave 시작 - type:', type, 'destination:', destination);

            // 모바일 디버깅: 호출 시점의 상세한 상태 확인
            const isMobile = window.innerWidth <= 768;
            console.log(`📱 디바이스 타입: ${isMobile ? 'MOBILE' : 'PC'} (width: ${window.innerWidth})`);

            // === 강화된 디버깅: 데이터 소스 전체 분석 ===
            console.log('=== 🔍 COMPREHENSIVE DATA SOURCE ANALYSIS ===');
            console.log('🔍 1. window.panelData:', window.panelData);
            console.log('🔍 2. window.panelData type:', typeof window.panelData);
            console.log('🔍 3. window.panelData keys:', window.panelData ? Object.keys(window.panelData) : 'NULL');
            console.log('🔍 4. JSON 필드 값:', document.getElementById('panelJsonData')?.value);
            console.log('🔍 5. JSON 필드 존재:', !!document.getElementById('panelJsonData'));

            // 모바일 전용: 모든 패널 관련 DOM 요소 확인
            if (isMobile) {
                console.log('=== 📱 모바일 DOM 상태 전체 분석 ===');

                // 모바일 카드들의 입력값 확인
                const mobileCards = document.querySelectorAll('.mobile-only-cards .panel-card');
                console.log('📱 모바일 카드 개수:', mobileCards.length);

                mobileCards.forEach((card, index) => {
                    const panelNum = card.dataset.panel;
                    console.log(`📱 모바일 카드 ${index + 1} (패널 ${panelNum}):`);

                    // 각 필드값 확인
                    const fields = ['panel_width', 'panel_height', 'material_type', 'material_thickness'];
                    fields.forEach(field => {
                        const input = card.querySelector(`[name="${field}"]`);
                        console.log(`  - ${field}: ${input?.value || 'NOT_FOUND'}`);
                    });
                });

                // PC 카드들의 입력값도 확인 (숨겨져 있어도)
                const pcPanels = document.querySelectorAll('.desktop-only .panel-card');
                console.log('💻 PC 카드 개수:', pcPanels.length);

                pcPanels.forEach((card, index) => {
                    const panelNum = card.dataset.panel;
                    console.log(`💻 PC 카드 ${index + 1} (패널 ${panelNum}):`);

                    const fields = ['panel_width', 'panel_height', 'material_type', 'material_thickness'];
                    fields.forEach(field => {
                        const input = card.querySelector(`[name="${field}"]`);
                        console.log(`  - ${field}: ${input?.value || 'NOT_FOUND'}`);
                    });
                });
            }

            // 페이지 나가기 전 패널 데이터 백업 (모바일 보호)
            const saveAndLeaveBackup = window.panelData ? JSON.parse(JSON.stringify(window.panelData)) : null;
            console.log('📦 saveAndLeave 패널 데이터 백업:', saveAndLeaveBackup);

            // === 디버깅 목적으로 저장 과정 일시정지 ===
            if (isMobile) {                

                // 일시정지 플래그 설정
                window.continueSave = false;

                // 5초 대기 또는 사용자가 수동으로 계속하기
                const waitForContinue = () => {
                    if (window.continueSave) {
                        console.log('✅ 사용자가 수동으로 계속하기를 선택했습니다.');
                        proceedWithSave();
                    } else {
                        setTimeout(() => {
                            console.log('⏰ 5초 타이머 만료, 자동으로 계속합니다.');
                            proceedWithSave();
                        }, 500);
                    }
                };

                // 실제 저장 로직을 별도 함수로 분리
                const proceedWithSave = () => {
                    console.log('🔄 저장 과정을 계속합니다...');
                    executeSaveProcess(type, destination);
                };

                waitForContinue();
                return; // 여기서 일시정지
            } else {
                // PC의 경우 바로 저장 진행
                executeSaveProcess(type, destination);
            }
        }

        // 실제 저장 실행 함수
        function executeSaveProcess(type, destination) {
            // 모바일 검사와 백업 데이터 다시 확인
            const isMobile = window.innerWidth <= 768;
            const saveAndLeaveBackup = window.panelData ? JSON.parse(JSON.stringify(window.panelData)) : null;

            console.log('🔄 === executeSaveProcess 시작 ===');
            console.log('📱 모바일 여부:', isMobile);
            console.log('💾 백업 데이터:', saveAndLeaveBackup);
            console.log('🎯 type:', type, 'destination:', destination);

            if (isMobile && (!window.panelData || Object.keys(window.panelData).length === 0)) {
                console.error('🚨 모바일에서 saveAndLeave 시작 시 패널 데이터가 이미 비어있음!');

                // JSON 필드에서 즉시 복원 시도
                const panelJsonField = document.getElementById('panelJsonData');
                if (panelJsonField && panelJsonField.value && panelJsonField.value.trim() !== '{}') {
                    try {
                        const recoveredData = JSON.parse(panelJsonField.value);
                        console.log('🔧 모바일 saveAndLeave 시작 시 JSON에서 데이터 복원:', recoveredData);
                        window.panelData = recoveredData;
                        saveAndLeaveBackup = recoveredData;
                    } catch (e) {
                        console.error('❌ 모바일 saveAndLeave 시작 시 JSON 복원 실패:', e);
                    }
                }
            }

            // 폼 유효성 검사
            if (!validateBasicInfo()) {
                Swal.fire({
                    title: '저장할 수 없습니다',
                    text: '필수 정보를 입력해주세요 (현장명, 측정자, 측정일자, 카 치수)',
                    icon: 'error',
                    confirmButtonText: '확인',
                    customClass: {
                        popup: 'linear-swal-popup',
                        confirmButton: 'linear-btn linear-btn-primary'
                    },
                    buttonsStyling: false
                });
                return;
            }

            // 저장 실행 (formHasUnsavedChanges는 저장 성공 후에만 해제)
            isFormSubmitting = true;

            Swal.fire({
                title: '저장 중...',
                html: '데이터를 저장하고 있습니다. 잠시만 기다려주세요.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                customClass: {
                    popup: 'linear-swal-popup'
                },
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            // AJAX로 폼 데이터 제출 (기존 로직 재사용)
            const form = document.getElementById('measurementForm');
            if (!form) {
                console.error('Form not found');
                return;
            }

            // === 모바일 전용: JSON 필드 업데이트 전에 강제로 패널 데이터 수집 ===
            if (isMobile) {
                console.log('📱 === 모바일 강제 데이터 수집 시작 ===');

                // 모바일 카드에서 직접 데이터 수집
                const mobileCards = document.querySelectorAll('.mobile-only-cards .panel-card');
                console.log('📱 발견된 모바일 카드 수:', mobileCards.length);

                mobileCards.forEach((card, index) => {
                    const panelNum = card.dataset.panel;
                    console.log(`📱 모바일 카드 ${panelNum} 데이터 수집:`);

                    // 필드값 직접 추출
                    const widthInput = card.querySelector('[name="panel_width"]');
                    const heightInput = card.querySelector('[name="panel_height"]');
                    const materialInput = card.querySelector('[name="material_type"]');
                    const thicknessInput = card.querySelector('[name="material_thickness"]');

                    const panelInfo = {
                        materialType: materialInput?.value || '',
                        thickness: thicknessInput?.value || '1.5',
                        width: widthInput?.value || '',
                        height: heightInput?.value || '2700',
                        drillingWidth: '',
                        drillingHeight: '',
                        drillingFromFloor: '',
                        drillingFromEntrance: '',
                        notes: ''
                    };

                    console.log(`  📱 패널 ${panelNum} 수집된 데이터:`, panelInfo);

                    // window.panelData 없으면 생성
                    if (!window.panelData) {
                        window.panelData = {};
                    }

                    // 강제로 데이터 저장
                    window.panelData[panelNum] = panelInfo;
                });

                console.log('📱 강제 수집 완료 후 window.panelData:', window.panelData);
            }

            // JSON 필드 업데이트 (헬퍼 함수 사용)
            window.safeUpdateJsonFields('saveAndLeave JSON 업데이트');

            // 데이터 상태 확인 후 필요시 백업으로 복원
            setTimeout(() => {
                const state = window.checkPanelDataState('saveAndLeave 실행 후');
                if (state.isEmpty && saveAndLeaveBackup) {
                    window.restorePanelData(saveAndLeaveBackup, 'saveAndLeave 복원');
                    window.safeUpdateJsonFields('복원 후 재시도');
                }
            }, 100);

            const formData = new FormData(form);

            // 체크박스 값 명시적 처리 (체크되지 않은 경우 0으로 설정)
            const excludeTransomCheckbox = document.getElementById('excludeTransom');
            if (excludeTransomCheckbox) {
                formData.set('transom_excluded', excludeTransomCheckbox.checked ? '1' : '0');
            }

            const excludePanelCornersCheckbox = document.getElementById('excludePanelCorners');
            if (excludePanelCornersCheckbox) {
                formData.set('panel_corners_excluded', excludePanelCornersCheckbox.checked ? '1' : '0');
            }

            // project_type 값 명시적 처리
            const projectTypeInput = document.getElementById('projectType');
            if (projectTypeInput) {
                formData.set('project_type', projectTypeInput.value);
                console.log('🔍 FormData에 project_type 설정:', projectTypeInput.value);
            }

            // material_type과 material_thickness 명시적 처리
            const materialTypeInput = document.getElementById('materialType');
            if (materialTypeInput) {
                formData.set('material_type', materialTypeInput.value);
            }
            const materialThicknessInput = document.getElementById('materialThickness');
            if (materialThicknessInput) {
                formData.set('material_thickness', materialThicknessInput.value);
            }
            const elevatorCountInput = document.getElementById('elevatorCount');
            if (elevatorCountInput) {
                formData.set('elevator_count', elevatorCountInput.value);
            }
            // notes 명시적 처리
            const notesInput = document.getElementById('notes');
            if (notesInput) {
                formData.set('notes', notesInput.value);
            }

            // === 🚨 모바일 FormData 수정: hidden 필드 강제 추가 ===
            if (isMobile) {
                console.log('📱 === 모바일 FormData 수정 시작 ===');

                // panelJsonData 필드 강제 추가
                const panelJsonField = document.getElementById('panelJsonData');
                const transomJsonField = document.getElementById('transomJsonData');

                if (panelJsonField && panelJsonField.value) {
                    console.log('📱 FormData에 panel_data 강제 추가:', panelJsonField.value);
                    formData.set('panel_data', panelJsonField.value);  // set으로 덮어쓰기
                } else {
                    console.error('📱 panelJsonData 필드 없음 또는 비어있음');
                }

                if (transomJsonField && transomJsonField.value) {
                    formData.set('transom_data', transomJsonField.value);  // set으로 덮어쓰기
                    console.log('📱 FormData에 transom_data 강제 추가:', transomJsonField.value);
                } else {
                    formData.set('transom_data', '{}');
                    console.log('📱 transomJsonField가 비어있어서 빈 JSON 설정');
                }

            }

            // 리디렉션 정보 추가
            if (destination && destination !== 'stay') {
                formData.append('redirect_after_save', destination);
            }

            // === 강화된 FormData 디버깅 ===
            console.log('=== 📋 COMPLETE FORM DATA ANALYSIS ===');

            // 패널 데이터와 transom 데이터 특별히 확인
            const panelDataField = document.getElementById('panelJsonData');
            const transomDataField = document.getElementById('transomJsonData');


            for (let [key, value] of formData.entries()) {
                if (key === 'panel_data' || key === 'transom_data') {
                } else {
                    console.log(`📋 ${key}: ${value}`);
                }
            }

            fetch('save_panel_measurement.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('저장 성공, 리디렉션:', data.should_redirect ? data.redirect_url : 'none');

                    // saveAndLeave에서도 모바일 데이터 보존 확인
                    const saveLeaveState = window.checkPanelDataState('saveAndLeave 저장 후');

                    // 데이터 손실 시 백업으로 복원 (헬퍼 함수 사용)
                    if (saveLeaveState.isEmpty && saveAndLeaveBackup) {
                        window.restorePanelData(saveAndLeaveBackup, 'saveAndLeave 데이터 손실 시');
                    }

                    Swal.fire({
                        title: '저장 완료',
                        text: data.message,
                        icon: 'success', 
                        timer: 1500,
                        showConfirmButton: false,
                        customClass: {
                            popup: 'linear-swal-popup'
                        }
                    }).then(() => {
                        // 리디렉션 직전 최종 데이터 상태 확인
                        console.log('🔄 리디렉션 직전 최종 패널 데이터 상태:', window.panelData);

                        // 최종 확인 후 여전히 데이터가 없으면 백업에서 복원
                        if ((!window.panelData || Object.keys(window.panelData).length === 0) && saveAndLeaveBackup) {
                            console.log('🆘 리디렉션 직전 최종 백업 데이터 복원:', saveAndLeaveBackup);
                            window.panelData = saveAndLeaveBackup;
                        }

                        // 페이지 나가기 플래그 설정 (페이지 보호 시스템 해제)
                        isFormSubmitting = true;
                        formHasUnsavedChanges = false;

                        // 리디렉션 처리
                        if (data.should_redirect && data.redirect_url) {
                            window.location.href = data.redirect_url;
                        } else if (type === 'link' && destination) {
                            window.location.href = destination;
                        } else if (type === 'back') {
                            history.back();
                        } else {
                            // 기본 동작: 페이지 새로고침 또는 목록으로
                           //  window.location.href = 'list.php';
                           console.log('href 실행구간');
                        }                      
                    }); 
                } else {
                    isFormSubmitting = false;
                    formHasUnsavedChanges = true;

                    // 저장 실패 시에도 데이터 복원 시도 (헬퍼 함수 사용)
                    const failState = window.checkPanelDataState('저장 실패 시');
                    if (failState.isEmpty && saveAndLeaveBackup) {
                        window.restorePanelData(saveAndLeaveBackup, '저장 실패 시');
                    }

                    Swal.fire({
                        title: '저장 실패',
                        text: data.message || '저장 중 오류가 발생했습니다.',
                        icon: 'error',
                        confirmButtonText: '확인',
                        customClass: {
                            popup: 'linear-swal-popup',
                            confirmButton: 'linear-btn linear-btn-primary'
                        },
                        buttonsStyling: false
                    });
                }
            })
            .catch(error => {
                console.error('Save error:', error);
                isFormSubmitting = false;
                formHasUnsavedChanges = true;

                // 네트워크 오류 시에도 데이터 복원 시도 (헬퍼 함수 사용)
                console.log('🚨 saveAndLeave 네트워크 오류');
                const errorState = window.checkPanelDataState('네트워크 오류 시');
                if (errorState.isEmpty && saveAndLeaveBackup) {
                    window.restorePanelData(saveAndLeaveBackup, '네트워크 오류 시');
                }

                Swal.fire({
                    title: '네트워크 오류',
                    text: '서버와의 통신 중 오류가 발생했습니다.',
                    icon: 'error',
                    confirmButtonText: '확인',
                    customClass: {
                        popup: 'linear-swal-popup',
                        confirmButton: 'linear-btn linear-btn-primary'
                    },
                    buttonsStyling: false
                });
            });
        }

        // 저장하지 않고 나가기
        function discardChangesAndLeave(type, destination) {
            console.log('discardChangesAndLeave 호출 - type:', type, 'destination:', destination);

            // 변경사항 플래그 해제
            formHasUnsavedChanges = false;
            isFormSubmitting = false;

            // URL 이동 처리
            if (type === 'link' && destination) {
                console.log('링크로 이동:', destination);
                window.location.href = destination;
            } else if (type === 'back') {
                console.log('뒤로가기 실행');
                history.back();
            } else if (type === 'breadcrumb' && destination) {
                console.log('브레드크럼 링크로 이동:', destination);
                window.location.href = destination;
            } else if (type === 'navigation' && destination) {
                console.log('네비게이션으로 이동:', destination);
                window.location.href = destination;
            } else if (type === 'mobile-back' && destination) {
                console.log('모바일 뒤로가기로 이동:', destination);
                window.location.href = destination;
            } else {
                // 기본 동작 - destination이 있으면 이동, 없으면 새로고침
                if (destination) {
                    console.log('기본 동작으로 이동:', destination);
                    window.location.href = destination;
                } else {
                    console.log('기본 동작으로 새로고침');
                    window.location.reload();
                }
            }
        }

        // 기본 정보 유효성 검사
        function validateBasicInfo() {
            // 안전한 요소 접근을 위한 헬퍼 함수 
            const getElementValue = (id) => {
                const element = document.getElementById(id);
                return element ? element.value.trim() : '';
            };

            const siteName = getElementValue('siteName');
            const measurerName = getElementValue('measurer');
            const measurementDate = getElementValue('measurementDate');
            const carWidth = getElementValue('carInsideWidth');
            const carDepth = getElementValue('carInsideDepth');
            const carHeight = getElementValue('carInsideHeight');

            console.log('Validation check:', {
                siteName: siteName,
                measurerName: measurerName,
                measurementDate: measurementDate,
                carWidth: carWidth,
                carDepth: carDepth,
                carHeight: carHeight
            });

            return siteName && measurerName && measurementDate && carWidth && carDepth && carHeight;
        }

        // 수동으로 저장 상태 리셋 (저장 성공 후 호출)
        function resetUnsavedChanges() {
            formHasUnsavedChanges = false;
            userHasActuallyInteracted = false;  // 상호작용 플래그도 리셋
            const title = document.title.replace(' *', '');
            document.title = title;

            // 저장 후 새로운 초기 상태로 업데이트
            setTimeout(() => {
                captureInitialFormData();
                console.log('Unsaved changes reset, interaction flag reset, and initial data updated');
            }, 100); // DOM 업데이트 완료 후 실행
        }

        // 수동으로 저장 상태 설정 (외부에서 변경 감지 시 호출)
        function markFormAsChanged() {
            formHasUnsavedChanges = true;
            updatePageTitle();
        }

        // 전역 함수로 노출 (다른 스크립트에서 사용 가능)
        window.resetUnsavedChanges = resetUnsavedChanges;
        window.markFormAsChanged = markFormAsChanged;

    </script>

    <!-- Panel Modal Handler Script -->
    <script>
        // Panel Modal Functions
        let currentPanelNumber = null;

        function openPanelModal(panelNumber) {
            console.log('Opening panel modal for panel:', panelNumber);
            currentPanelNumber = panelNumber;

            const modal = document.getElementById('panelModal');
            const modalTitle = document.getElementById('panelModalTitle');
            const modalPanelNumber = document.getElementById('modalPanelNumber');

            if (!modal || !modalTitle || !modalPanelNumber) {
                console.error('Panel modal elements not found');
                return;
            }

            // Set panel number
            modalPanelNumber.value = panelNumber;

            // Set modal title
            if (panelNumber === 12) {
                modalTitle.textContent = 'Transom 정보 입력';
            } else {
                modalTitle.textContent = `패널 ${panelNumber}번 정보 입력`;
            }

            // Load existing data if available
            loadPanelData(panelNumber);

            // Show modal
            modal.style.display = 'flex';

            // Add escape key listener
            document.addEventListener('keydown', handleModalEscape);

            // Prevent body scroll
            document.body.style.overflow = 'hidden';
        }

        function closePanelModal() {
            const modal = document.getElementById('panelModal');

            // 모달을 닫기 전에 마지막으로 현재 패널 데이터 렌더링 보장
            if (currentPanelNumber && window.panelData && window.panelData[currentPanelNumber]) {
                const panelData = window.panelData[currentPanelNumber];
                if (panelData.drillingWidth || panelData.drillingHeight) {
                    if (typeof window.renderPanelInfo === 'function') {
                        window.renderPanelInfo(currentPanelNumber, panelData);
                    }
                }
            }

            if (modal) {
                modal.style.display = 'none';
            }

            // Remove escape key listener
            document.removeEventListener('keydown', handleModalEscape);

            // Restore body scroll
            document.body.style.overflow = '';

            // currentPanelNumber를 null로 설정하기 전에 마지막 렌더링 시도
            const lastPanelNumber = currentPanelNumber;
            currentPanelNumber = null;

            // 모달 닫힌 후 지연 렌더링
            if (lastPanelNumber && window.panelData && window.panelData[lastPanelNumber]) {
                const panelData = window.panelData[lastPanelNumber];
                if (panelData.drillingWidth || panelData.drillingHeight) {
                    setTimeout(() => {
                        if (typeof window.renderPanelInfo === 'function') {
                            window.renderPanelInfo(lastPanelNumber, panelData);
                        }
                    }, 200);
                }
            }
        }

        function handleModalEscape(e) {
            if (e.key === 'Escape') {
                closePanelModal();
            }
        }

        function loadPanelData(panelNumber) {
            // Load existing panel data from window.panelData if available
            if (window.panelData && window.panelData[panelNumber]) {
                const data = window.panelData[panelNumber];

                // Fill form fields with existing data
                const fields = [
                    'modalPanelWidth', 'modalPanelHeight', 'modalPanelThickness',
                    'modalMaterialType', 'modalFrontThickness', 'modalFrontWing',
                    'modalBackThickness', 'modalBackWing', 'modalPanelNotes'
                ];

                fields.forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    const dataKey = fieldId.replace('modal', '').replace('Panel', '').toLowerCase();

                    if (field && data[dataKey] !== undefined) {
                        field.value = data[dataKey];
                    }
                });
            }

            // Sync material type from main form
            syncMaterialType();
        }

        function syncMaterialType() {
            const mainMaterialType = document.getElementById('materialType');
            const modalMaterialType = document.getElementById('modalMaterialType');

            if (mainMaterialType && modalMaterialType) {
                modalMaterialType.value = mainMaterialType.value;
            }
        }

        function savePanelData() {
            // currentPanelNumber가 null인 경우, 모달 타이틀에서 패널 번호 추출
            let panelNumber = currentPanelNumber;
            if (!panelNumber) {
                const modalTitle = document.getElementById('panelModalTitle');
                if (modalTitle && modalTitle.textContent) {
                    const match = modalTitle.textContent.match(/패널\s*(\d+)/);
                    if (match) {
                        panelNumber = match[1];
                    }
                }
            }

            if (!panelNumber) {
                console.error('패널 번호를 확인할 수 없습니다');
                return;
            }

            // Initialize panelData if not exists
            if (!window.panelData) {
                window.panelData = {};
            }

            // Get form data
            const formData = {
                panelNumber: panelNumber,
                width: document.getElementById('modalPanelWidth')?.value || '',
                height: document.getElementById('modalPanelHeight')?.value || '',
                thickness: document.getElementById('modalPanelThickness')?.value || '',
                materialType: document.getElementById('modalMaterialType')?.value || '',
                frontThickness: document.getElementById('modalFrontThickness')?.value || '',
                frontWing: document.getElementById('modalFrontWing')?.value || '',
                backThickness: document.getElementById('modalBackThickness')?.value || '',
                backWing: document.getElementById('modalBackWing')?.value || '',
                notes: document.getElementById('modalPanelNotes')?.value || '',
                // 타공정보 추가
                drillingWidth: document.getElementById('modalDrillingWidth')?.value || '',
                drillingHeight: document.getElementById('modalDrillingHeight')?.value || '',
                drillingFromFloor: document.getElementById('modalDrillingFromFloor')?.value || '',
                drillingFromEntrance: document.getElementById('modalDrillingFromEntrance')?.value || ''
            };

            // Save to window.panelData
            window.panelData[panelNumber] = formData;

            // Update panel visualization - 즉시 렌더링
            if (typeof window.renderPanelInfo === 'function') {
                window.renderPanelInfo(panelNumber, formData);

                // 타공정보가 있는 경우 모달 닫힌 후 추가 렌더링 보장
                if (formData.drillingWidth || formData.drillingHeight) {
                    // 모달 닫힌 직후 렌더링
                    setTimeout(() => {
                        window.renderPanelInfo(panelNumber, formData);
                    }, 100);

                    // 추가 보장 렌더링
                    setTimeout(() => {
                        window.renderPanelInfo(panelNumber, formData);
                    }, 500);

                    // 최종 보장 렌더링
                    setTimeout(() => {
                        window.renderPanelInfo(panelNumber, formData);
                    }, 1000);
                }
            }

            // Update JSON fields
            if (typeof window.updateJsonFields === 'function') {
                window.updateJsonFields();
            }


            // Close modal
            closePanelModal();

            // Show success message with correct panel number
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: '저장 완료',
                    text: `패널 ${panelNumber}번 정보가 저장되었습니다.`,
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        }

        // Initialize modal event listeners when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            // Close button
            const closeBtn = document.getElementById('panelModalClose');
            if (closeBtn) {
                closeBtn.addEventListener('click', closePanelModal);
            }

            // 아이파크 div 이벤트 리스너 등록
            const iparkDivCancel = document.getElementById('iparkDivCancel');
            const iparkDivSave = document.getElementById('iparkDivSave');

            if (iparkDivCancel) {
                iparkDivCancel.addEventListener('click', cancelIparkPanelSettingsDOM);
                console.log('🔗 아이파크 div 취소 버튼 이벤트 리스너 등록 완료');
            }

            if (iparkDivSave) {
                iparkDivSave.addEventListener('click', saveIparkPanelSettingsDOM);
                console.log('🔗 아이파크 div 저장 버튼 이벤트 리스너 등록 완료');
            }

            // Backdrop click to close
            const modal = document.getElementById('panelModal');
            if (modal) {
                // Modal backdrop click to close - DISABLED (사용자가 실수로 모달을 닫는 것을 방지)
                /*
                modal.addEventListener('click', function(e) {
                    if (e.target === modal || e.target.classList.contains('panel-modal-backdrop')) {
                        closePanelModal();
                    }
                });
                */
            }

            // Save button
            const saveBtn = document.getElementById('panelModalSave');
            if (saveBtn) {
                saveBtn.addEventListener('click', savePanelData);
            }

            // Cancel button
            const cancelBtn = document.getElementById('panelModalCancel');
            if (cancelBtn) {
                cancelBtn.addEventListener('click', closePanelModal);
            }

            // Reset button
            const resetBtn = document.getElementById('panelModalReset');
            if (resetBtn) {
                resetBtn.addEventListener('click', function() {
                    if (confirm('입력한 내용을 모두 초기화하시겠습니까?')) {
                        const form = document.getElementById('panelInfoForm');
                        if (form) {
                            form.reset();
                        }
                    }
                });
            }

            // Material type sync when main form changes
            const mainMaterialType = document.getElementById('materialType');
            if (mainMaterialType) {
                mainMaterialType.addEventListener('change', syncMaterialType);
            }
        });

        // Make functions globally available
        window.openPanelModal = openPanelModal;
        window.closePanelModal = closePanelModal;
        window.savePanelData = savePanelData;

        // 📱 DEBUG: 전역 디버그 함수들
        window.debugIparkModal = function() {
            console.log('📱 === 아이파크 모달 디버그 정보 ===');

            const iparkCheck = document.getElementById('iparkCheck');
            const iparkModal = document.getElementById('iparkModal');
            const iparkLabel = document.getElementById('iparkCheckLabel');

            console.log('📱 체크박스 요소:', iparkCheck ? '✅ 발견' : '❌ 없음');
            console.log('📱 모달 요소:', iparkModal ? '✅ 발견' : '❌ 없음');
            console.log('📱 레이블 요소:', iparkLabel ? '✅ 발견' : '❌ 없음');

            if (iparkCheck) {
                console.log('📱 체크박스 상태:', iparkCheck.checked);
                console.log('📱 체크박스 disabled:', iparkCheck.disabled);
                console.log('📱 체크박스 스타일:', window.getComputedStyle(iparkCheck).display);
            }

            if (iparkModal) {
                console.log('📱 모달 display:', iparkModal.style.display);
                console.log('📱 모달 computed display:', window.getComputedStyle(iparkModal).display);
                console.log('📱 모달 위치:', iparkModal.getBoundingClientRect());
            }

            console.log('📱 모바일 환경:', typeof isMobileDevice === 'function' ? isMobileDevice() : 'unknown');
            console.log('📱 화면 크기:', window.innerWidth + 'x' + window.innerHeight);
            console.log('📱 User Agent:', navigator.userAgent);
        };

        window.testIparkDiv = function() {
            console.log('📱 === 아이파크 div 토글 테스트 실행 ===');
            try {
                if (typeof showIparkSettingsDiv === 'function') {
                    showIparkSettingsDiv();
                    console.log('📱 ✅ showIparkSettingsDiv 테스트 성공');
                } else {
                    console.error('📱 ❌ showIparkSettingsDiv 함수가 정의되지 않음');
                }
            } catch (error) {
                console.error('📱 ❌ showIparkSettingsDiv 테스트 실패:', error);
            }
        };

        window.forceIparkCheck = function() {
            console.log('📱 === 아이파크 체크박스 강제 실행 ===');
            const iparkCheck = document.getElementById('iparkCheck');
            if (iparkCheck) {
                iparkCheck.checked = true;
                iparkCheck.dispatchEvent(new Event('change'));
                console.log('📱 ✅ 체크박스 강제 체크 및 change 이벤트 발생');
            } else {
                console.error('📱 ❌ 체크박스를 찾을 수 없습니다');
            }
        };

        // 복사 및 삭제 기능 (편집 모드 전용)
        document.addEventListener('DOMContentLoaded', function() {
            // 복사 버튼 이벤트 리스너
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
                        confirmButtonColor: 'var(--linear-primary)',
                        cancelButtonColor: 'var(--linear-secondary)',
                        customClass: {
                            popup: 'linear-swal-popup'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            copyCurrentData();
                        }
                    });
                });
            }

            // 삭제 버튼 이벤트 리스너
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
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: 'var(--linear-secondary)',
                        customClass: {
                            popup: 'linear-swal-popup'
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
                    customClass: { popup: 'linear-swal-popup' }
                });
                return;
            }

            // 로딩 표시
            Swal.fire({
                title: '데이터 복사 중...',
                text: '잠시만 기다려주세요.',
                allowOutsideClick: false,
                showConfirmButton: false,
                customClass: { popup: 'linear-swal-popup' },
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // 복사 요청
            fetch('copy_measurement.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    source_id: editId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '복사 완료',
                        text: '새로운 측정 데이터가 생성되었습니다.',
                        confirmButtonText: '새 데이터로 이동',
                        customClass: { popup: 'linear-swal-popup' }
                    }).then(() => {
                        // 새로 생성된 데이터의 편집 페이지로 이동
                        window.location.href = `panel_measurement.php?edit=${data.new_id}`;
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: '복사 실패',
                        text: data.message || '데이터 복사 중 오류가 발생했습니다.',
                        customClass: { popup: 'linear-swal-popup' }
                    });
                } 
            }) 
            .catch(error => {
                console.error('Copy error:', error);
                Swal.fire({
                    icon: 'error',
                    title: '오류',
                    text: '데이터 복사 중 오류가 발생했습니다.',
                    customClass: { popup: 'linear-swal-popup' }
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
                    customClass: { popup: 'linear-swal-popup' }
                });
                return;
            }

            // 로딩 표시
            Swal.fire({ 
                title: '데이터 삭제 중...',
                text: '잠시만 기다려주세요.',
                allowOutsideClick: false,
                showConfirmButton: false,
                customClass: { popup: 'linear-swal-popup' },
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // 삭제 요청
            fetch('delete_measurement.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: editId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '삭제 완료',
                        text: '측정 데이터가 성공적으로 삭제되었습니다.',
                        confirmButtonText: '대시보드로 이동',
                        customClass: { popup: 'linear-swal-popup' }
                    }).then(() => {
                        // 대시보드로 이동
                        window.location.href = 'index.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: '삭제 실패',
                        text: data.message || '데이터 삭제 중 오류가 발생했습니다.',
                        customClass: { popup: 'linear-swal-popup' }
                    });
                }
            })
            .catch(error => {
                console.error('Delete error:', error);
                Swal.fire({
                    icon: 'error',
                    title: '오류',
                    text: '데이터 삭제 중 오류가 발생했습니다.',
                    customClass: { popup: 'linear-swal-popup' }
                });
            });
        } 
    </script>

    <script src="assets/js/panel_measurement.js"></script>
</body>
</html> 
 