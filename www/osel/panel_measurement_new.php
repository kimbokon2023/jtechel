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
$defaultWidth = $edit_mode ? $edit_data['car_inside_width'] : '';
$defaultDepth = $edit_mode ? $edit_data['car_inside_depth'] : '';
$defaultHeight = $edit_mode ? $edit_data['car_inside_height'] : '';
$defaultMaterialType = $edit_mode ? $edit_data['material_type'] : '';
$defaultMaterialThickness = $edit_mode ? $edit_data['material_thickness'] : '';
$defaultElevatorCount = $edit_mode ? $edit_data['elevator_count'] : '1';
$defaultNotes = $edit_mode ? $edit_data['notes'] : '';
$defaultIparkCheck = $edit_mode ? ($edit_data['ipark_check'] ?? 0) : 0;
$defaultPanelCornersExcluded = $edit_mode ? ($edit_data['panel_corners_excluded'] ?? 0) : 0;
$defaultTransomExcluded = $edit_mode ? ($edit_data['transom_excluded'] ?? 0) : 0;
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

        .responsive-button {
            padding: var(--linear-spacing-sm) var(--linear-spacing-md);
            border: none;
            border-radius: var(--linear-radius-sm);
            font-size: var(--linear-font-size-sm);
            font-weight: var(--linear-font-weight-medium);
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: var(--linear-spacing-xs);
        }

        .responsive-button-primary {
            background: var(--linear-primary);
            color: white;
        }

        .responsive-button-primary:hover {
            background: var(--linear-primary-hover);
        }

        .responsive-button-secondary {
            background: var(--linear-bg-tertiary);
            color: var(--linear-text-primary);
            border: 1px solid var(--linear-border-primary);
        }

        .responsive-button-secondary:hover {
            background: var(--linear-bg-quaternary);
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
        }

        /* 아이파크 체크박스 */
        #iparkCheckContainer {
            display: block;
            margin-bottom: 20px;
        }

        @media (max-width: 767px) {
            #iparkCheckContainer {
                display: none;
            }
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
        .car-wall-section {
            background: var(--linear-bg-secondary);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-md);
            padding: var(--linear-spacing-lg);
            margin-bottom: var(--linear-spacing-lg);
        }

        .panel-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: var(--linear-spacing-sm);
            max-width: 400px;
            margin: 0 auto;
        }

        .panel {
            aspect-ratio: 1;
            border: 2px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            background: var(--linear-bg-primary);
            color: var(--linear-text-primary);
            font-weight: var(--linear-font-weight-medium);
        }

        .panel:hover {
            border-color: var(--linear-primary);
            background: var(--linear-primary-alpha);
        }

        .panel.selected {
            border-color: var(--linear-primary);
            background: var(--linear-primary);
            color: white;
        }

        .panel.has-info {
            background: var(--linear-success);
            color: white;
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
            
            .responsive-button {
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

        /* 테마 토글 */
        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        .theme-toggle-btn {
            background: var(--linear-bg-secondary);
            border: 1px solid var(--linear-border-primary);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 18px;
        }

        .theme-toggle-btn:hover {
            background: var(--linear-bg-tertiary);
        }
    </style>
</head>
<body>
    <!-- Theme Toggle -->
    <div class="theme-toggle">
        <button id="themeToggleBtn" class="theme-toggle-btn" title="테마 변경">
            <span id="themeIcon">⚙️</span>
        </button>
    </div>

    <div class="container">
        <!-- Header -->
        <div class="responsive-card">
            <div class="responsive-card-header">
                <h1 class="responsive-card-title">
                    <i class="bi bi-rulers"></i>
                    <?= $edit_mode ? '카 판넬 측정 (편집)' : '카 판넬 측정' ?>
                </h1>
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
                        
                        <!-- iPark 체크박스 -->
                        <div id="iparkCheckContainer" class="responsive-input-group">
                            <label class="linear-label">
                                <input type="checkbox" id="iparkCheck" name="ipark_check" value="1" <?= $defaultIparkCheck ? 'checked' : '' ?>>
                                iPark 신규 프로젝트
                            </label>
                        </div>

                        <!-- 현장명 -->
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
                                    <input type="number" id="carInsideWidth" name="car_inside_width" class="linear-input responsive-input" value="<?= htmlspecialchars($defaultWidth) ?>" min="800" max="2500" step="5">
                                </div>
                                <!-- Car Inside Depth -->
                                <div class="responsive-input-group">
                                    <label for="carInsideDepth" class="linear-label">
                                        <i class="bi bi-arrow-up-down" style="margin-right: 4px;"></i>
                                        깊이 (D) <small style="color: var(--linear-text-tertiary);">mm</small>
                                    </label>
                                    <input type="number" id="carInsideDepth" name="car_inside_depth" class="linear-input responsive-input" value="<?= htmlspecialchars($defaultDepth) ?>" min="800" max="2000" step="5">
                                </div>
                                <!-- Car Inside Height -->
                                <div class="responsive-input-group">
                                    <label for="carInsideHeight" class="linear-label">
                                        <i class="bi bi-arrows-vertical" style="margin-right: 4px;"></i>
                                        높이 (H) <small style="color: var(--linear-text-tertiary);">mm</small>
                                    </label>
                                    <input type="number" id="carInsideHeight" name="car_inside_height" class="linear-input responsive-input" value="<?= htmlspecialchars($defaultHeight) ?>" min="2000" max="3000" step="5">
                                </div>
                            </div>
                        </div>

                        <!-- 재질 정보 -->
                        <div class="responsive-section">
                            <h6 class="responsive-section-title">재질 정보</h6>
                            <!-- Material Type and Thickness -->
                            <div class="responsive-grid responsive-grid-2">
                                <div class="responsive-input-group">
                                    <label for="materialType" class="linear-label">의장재질</label>
                                    <select class="linear-input responsive-input" id="materialType" name="material_type">
                                        <option value="">선택하세요</option>
                                        <option value="스테인리스" <?= $defaultMaterialType === '스테인리스' ? 'selected' : '' ?>>스테인리스</option>
                                        <option value="알루미늄" <?= $defaultMaterialType === '알루미늄' ? 'selected' : '' ?>>알루미늄</option>
                                        <option value="강판" <?= $defaultMaterialType === '강판' ? 'selected' : '' ?>>강판</option>
                                        <option value="기타" <?= $defaultMaterialType === '기타' ? 'selected' : '' ?>>기타</option>
                                    </select>
                                </div>
                                <div class="responsive-input-group">
                                    <label for="materialThickness" class="linear-label">두께 <span style="color: var(--linear-text-tertiary);">mm</span></label>
                                    <select class="linear-input responsive-input" id="materialThickness" name="material_thickness">
                                        <option value="">선택하세요</option>
                                        <option value="0.5" <?= $defaultMaterialThickness === '0.5' ? 'selected' : '' ?>>0.5mm</option>
                                        <option value="0.8" <?= $defaultMaterialThickness === '0.8' ? 'selected' : '' ?>>0.8mm</option>
                                        <option value="1.0" <?= $defaultMaterialThickness === '1.0' ? 'selected' : '' ?>>1.0mm</option>
                                        <option value="1.2" <?= $defaultMaterialThickness === '1.2' ? 'selected' : '' ?>>1.2mm</option>
                                        <option value="1.5" <?= $defaultMaterialThickness === '1.5' ? 'selected' : '' ?>>1.5mm</option>
                                        <option value="2.0" <?= $defaultMaterialThickness === '2.0' ? 'selected' : '' ?>>2.0mm</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Elevator Count -->
                            <div class="responsive-input-group">
                                <label for="elevatorCount" class="linear-label">엘리베이터 대수</label>
                                <input type="number" class="linear-input responsive-input" id="elevatorCount" name="elevator_count" value="<?= htmlspecialchars($defaultElevatorCount) ?>" min="1" max="20" step="1" placeholder="1">
                            </div>
                            
                            <!-- Notes -->
                            <div class="responsive-input-group">
                                <label for="notes" class="linear-label">특이사항</label>
                                <textarea class="linear-input responsive-input" id="notes" name="notes" rows="3" placeholder="측정 시 특이사항이나 주의사항을 입력하세요"><?= htmlspecialchars($defaultNotes) ?></textarea>
                            </div>
                        </div>

                        <!-- 옵션 설정 -->
                        <div class="responsive-section">
                            <h6 class="responsive-section-title">옵션 설정</h6>
                            <div class="responsive-grid responsive-grid-2">
                                <div class="responsive-input-group">
                                    <label class="linear-label">
                                        <input type="checkbox" name="panel_corners_excluded" value="1" <?= $defaultPanelCornersExcluded ? 'checked' : '' ?>>
                                        모서리 제외
                                    </label>
                                </div>
                                <div class="responsive-input-group">
                                    <label class="linear-label">
                                        <input type="checkbox" name="transom_excluded" value="1" <?= $defaultTransomExcluded ? 'checked' : '' ?>>
                                        트랜섬 제외
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 판넬 시각화 -->
                <div class="car-wall-section">
                    <h3 class="responsive-section-title">
                        <i class="bi bi-grid-3x3-gap"></i> 판넬 시각화
                    </h3>
                    
                    <div class="panel-grid">
                        <!-- Panel 1 -->
                        <div class="panel" data-panel="1" onclick="selectPanel(1)">
                            <span>1</span>
                        </div>
                        <!-- Panel 2 -->
                        <div class="panel" data-panel="2" onclick="selectPanel(2)">
                            <span>2</span>
                        </div>
                        <!-- Panel 3 -->
                        <div class="panel" data-panel="3" onclick="selectPanel(3)">
                            <span>3</span>
                        </div>
                        <!-- Panel 4 -->
                        <div class="panel" data-panel="4" onclick="selectPanel(4)">
                            <span>4</span>
                        </div>
                        <!-- Panel 5 -->
                        <div class="panel" data-panel="5" onclick="selectPanel(5)">
                            <span>5</span>
                        </div>
                        <!-- Panel 6 -->
                        <div class="panel" data-panel="6" onclick="selectPanel(6)">
                            <span>6</span>
                        </div>
                        <!-- Panel 7 -->
                        <div class="panel" data-panel="7" onclick="selectPanel(7)">
                            <span>7</span>
                        </div>
                        <!-- Panel 8 -->
                        <div class="panel" data-panel="8" onclick="selectPanel(8)">
                            <span>8</span>
                        </div>
                        <!-- Panel 9 -->
                        <div class="panel" data-panel="9" onclick="selectPanel(9)">
                            <span>9</span>
                        </div>
                        <!-- Panel 10 -->
                        <div class="panel" data-panel="10" onclick="selectPanel(10)">
                            <span>10</span>
                        </div>
                        <!-- Panel 11 -->
                        <div class="panel" data-panel="11" onclick="selectPanel(11)">
                            <span>11</span>
                        </div>
                        <!-- Panel 12 (Transom) -->
                        <div class="panel" data-panel="12" onclick="selectPanel(12)">
                            <span>T</span>
                        </div>
                    </div>

                    <!-- Panel Info Display -->
                    <div id="panelInfo" class="responsive-section" style="display: none;">
                        <h6 class="responsive-section-title">선택된 판넬 정보</h6>
                        <div id="panelInfoContent"></div>
                    </div>

                    <!-- Panel Action Buttons -->
                    <div class="button-group">
                        <button type="button" id="newBtn" class="responsive-button responsive-button-primary">
                            <i class="bi bi-plus-circle"></i> 새로 만들기
                        </button>
                        <button type="button" id="modBtn" class="responsive-button responsive-button-secondary">
                            <i class="bi bi-pencil"></i> 수정하기
                        </button>
                        <button type="button" id="delBtn" class="responsive-button responsive-button-secondary">
                            <i class="bi bi-trash"></i> 삭제하기
                        </button>
                    </div>
                </div>

                <!-- Hidden Fields -->
                <input type="hidden" id="panelJsonData" name="panel_data" value="">
                <input type="hidden" id="transomJsonData" name="transom_data" value="">

                <!-- Action Buttons -->
                <div class="button-group">
                    <button type="button" id="validateBtn" class="responsive-button responsive-button-secondary">
                        <i class="bi bi-check-circle"></i> 측정값 검증
                    </button>
                    <button type="submit" id="saveBtn" class="responsive-button responsive-button-primary">
                        <i class="bi bi-save"></i> 측정 저장
                    </button>
                    <button type="button" id="backBtn" class="responsive-button responsive-button-secondary">
                        <i class="bi bi-arrow-left"></i> 돌아가기
                    </button>
                </div>
            </form>
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
        let panelData = {};
        let transomData = {};

        // Edit mode data loading
        <?php if ($edit_mode && !empty($edit_panel_data)): ?>
        panelData = <?= json_encode($edit_panel_data) ?>;
        debugLog('Edit mode: Loaded panel data', panelData);
        <?php endif; ?>

        <?php if ($edit_mode && !empty($edit_transom_data)): ?>
        transomData = <?= json_encode($edit_transom_data) ?>;
        debugLog('Edit mode: Loaded transom data', transomData);
        <?php endif; ?>

        // Panel selection
        function selectPanel(panelNumber) {
            // Remove previous selection
            document.querySelectorAll('.panel').forEach(p => p.classList.remove('selected'));
            
            // Add selection to clicked panel
            const panel = document.querySelector(`[data-panel="${panelNumber}"]`);
            if (panel) {
                panel.classList.add('selected');
                selectedPanel = panelNumber;
                debugLog('Selected panel:', panelNumber);
            }
        }

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
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            
            // Generate calendar HTML
            const monthNames = ['1월', '2월', '3월', '4월', '5월', '6월', '7월', '8월', '9월', '10월', '11월', '12월'];
            const dayNames = ['일', '월', '화', '수', '목', '금', '토'];
            
            let html = `
                <div class="date-picker-header">
                    <button type="button" class="date-picker-nav" onclick="changeMonth(-1)">‹</button>
                    <div class="date-picker-title">${year}년 ${monthNames[month]}</div>
                    <button type="button" class="date-picker-nav" onclick="changeMonth(1)">›</button>
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
                html += `<div class="date-picker-day ${isToday ? 'selected' : ''}" onclick="selectDate(${year}, ${month}, ${day})">${day}</div>`;
            }
            
            html += '</div>';
            datePicker.innerHTML = html;
        }

        // Change month
        function changeMonth(direction) {
            // Implementation for month navigation
            updateDatePicker();
        }

        // Select date
        function selectDate(year, month, day) {
            const dateInput = document.getElementById('measurementDate');
            const datePicker = document.getElementById('datePicker');
            
            if (dateInput) {
                const selectedDate = new Date(year, month, day);
                const formattedDate = selectedDate.toISOString().split('T')[0];
                dateInput.value = formattedDate;
                datePicker.style.display = 'none';
                debugLog('Selected date:', formattedDate);
            }
        }

        // Update panel display
        function updatePanelDisplay() {
            // Update panel visual states based on data
            Object.keys(panelData).forEach(panelId => {
                const panel = document.querySelector(`[data-panel="${panelId}"]`);
                if (panel) {
                    panel.classList.add('has-info');
                }
            });
            
            // Update transom panel
            if (Object.keys(transomData).length > 0) {
                const transomPanel = document.querySelector('[data-panel="12"]');
                if (transomPanel) {
                    transomPanel.classList.add('has-info');
                }
            }
        }

        // Form validation
        function validateForm() {
            const requiredFields = ['siteName', 'measurementDate', 'measurer', 'carInsideWidth', 'carInsideDepth', 'carInsideHeight'];
            let isValid = true;
            
            requiredFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field && !field.value.trim()) {
                    field.style.borderColor = 'var(--linear-color-red)';
                    isValid = false;
                } else if (field) {
                    field.style.borderColor = 'var(--linear-border-primary)';
                }
            });
            
            if (!isValid) {
                Swal.fire({
                    icon: 'warning',
                    title: '입력 오류',
                    text: '필수 항목을 모두 입력해주세요.',
                    customClass: { popup: 'linear-swal-popup' }
                });
            }
            
            return isValid;
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Validate button
            const validateBtn = document.getElementById('validateBtn');
            if (validateBtn) {
                validateBtn.addEventListener('click', function() {
                    if (validateForm()) {
                        Swal.fire({
                            icon: 'success',
                            title: '검증 완료',
                            text: '모든 필수 항목이 올바르게 입력되었습니다.',
                            customClass: { popup: 'linear-swal-popup' }
                        });
                    }
                });
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
                    if (!validateForm()) {
                        e.preventDefault();
                    }
                });
            }
        });

        // Theme toggle
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggleBtn = document.getElementById('themeToggleBtn');
            const themeIcon = document.getElementById('themeIcon');
            
            if (!themeToggleBtn || !themeIcon) return;
            
            let currentTheme = localStorage.getItem('linear-theme') || 'auto';
            
            function updateThemeIcon() {
                const icons = {
                    'light': '☀️',
                    'dark': '🌙',
                    'auto': '⚙️'
                };
                themeIcon.textContent = icons[currentTheme] || '⚙️';
                
                const titles = {
                    'light': '라이트 모드 (다크 모드로 변경)',
                    'dark': '다크 모드 (자동 모드로 변경)', 
                    'auto': '자동 모드 (라이트 모드로 변경)'
                };
                themeToggleBtn.title = titles[currentTheme] || '테마 변경';
            }
            
            function applyTheme(theme) {
                document.documentElement.setAttribute('data-theme', theme);
                localStorage.setItem('linear-theme', theme);
                currentTheme = theme;
                updateThemeIcon();
            }
            
            themeToggleBtn.addEventListener('click', function() {
                const themes = ['light', 'dark', 'auto'];
                const currentIndex = themes.indexOf(currentTheme);
                const nextIndex = (currentIndex + 1) % themes.length;
                applyTheme(themes[nextIndex]);
            });
            
            // Apply saved theme on load
            applyTheme(currentTheme);
        });
    </script>

    <script src="assets/js/panel_measurement.js"></script>
</body>
</html>
