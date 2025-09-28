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
    error_log("Database connection failed in measurement_detail.php: " . $e->getMessage());
    die("데이터베이스 연결에 실패했습니다.");
}

// Get measurement ID
$measurement_id = $_GET['id'] ?? '';

if (empty($measurement_id)) {
    header("Location: index.php");
    exit;
}

// Fetch measurement data
try {
    $stmt = $pdo->prepare("
        SELECT id, site_name, measurement_date, measurer_name, measurer_id,
               car_inside_width, car_inside_depth, car_inside_height,
               material_type, material_thickness, elevator_count,
               panel_data, transom_data, notes, created_at, updated_at
        FROM panel_measurements
        WHERE id = ?
    ");
    $stmt->execute([$measurement_id]);
    $measurement = $stmt->fetch();

    if (!$measurement) {
        header("Location: index.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Database query failed: " . $e->getMessage());
    die("데이터를 불러오는 중 오류가 발생했습니다.");
}

// Parse JSON data
$panel_data = [];
$transom_data = [];

if (!empty($measurement['panel_data'])) {
    $panel_data = json_decode($measurement['panel_data'], true) ?? [];
}

if (!empty($measurement['transom_data'])) {
    $transom_data = json_decode($measurement['transom_data'], true) ?? [];
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>측정 상세보기 - <?= htmlspecialchars($measurement['site_name']) ?></title>

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

        .detail-container {
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

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--linear-spacing-xl);
            margin-bottom: var(--linear-spacing-xl);
        }

        .info-card {
            background-color: var(--linear-bg-primary);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-lg);
            padding: var(--linear-spacing-xl);
            box-shadow: var(--linear-shadow-low);
        }

        .info-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--linear-spacing-lg);
            gap: var(--linear-spacing-md);
        }

        @media (max-width: 768px) {
            .info-card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: var(--linear-spacing-sm);
            }

            .info-card-header h3 {
                width: 100%;
            }
        }

        .info-card h3 {
            font-size: var(--linear-text-title3);
            font-weight: var(--linear-font-weight-semibold);
            color: var(--linear-text-primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: var(--linear-spacing-sm);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--linear-spacing-md) 0;
            border-bottom: 1px solid var(--linear-border-secondary);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: var(--linear-font-weight-medium);
            color: var(--linear-text-secondary);
        }

        .info-value {
            color: var(--linear-text-primary);
            font-weight: var(--linear-font-weight-medium);
        }

        .panel-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: var(--linear-spacing-lg);
            margin-bottom: var(--linear-spacing-xl);
        }

        .panel-card {
            background-color: var(--linear-bg-primary);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-lg);
            padding: var(--linear-spacing-lg);
            box-shadow: var(--linear-shadow-low);
        }

        .panel-card h4 {
            font-size: var(--linear-text-large);
            font-weight: var(--linear-font-weight-semibold);
            color: var(--linear-text-primary);
            margin-bottom: var(--linear-spacing-md);
            display: flex;
            align-items: center;
            gap: var(--linear-spacing-sm);
        }

        .panel-no-data {
            text-align: center;
            color: var(--linear-text-tertiary);
            padding: var(--linear-spacing-xl);
            background-color: var(--linear-bg-secondary);
            border-radius: var(--linear-radius-md);
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

        .badge-secondary {
            background-color: var(--linear-color-gray);
        }

        .badge-success {
            background-color: var(--linear-color-green);
        }

        @media (max-width: 768px) {
            .detail-container {
                padding: var(--linear-spacing-md);
            }

            .detail-grid {
                grid-template-columns: 1fr;
                gap: var(--linear-spacing-lg);
            }

            .panel-grid {
                grid-template-columns: 1fr;
                gap: var(--linear-spacing-md);
            }

            .info-card {
                padding: var(--linear-spacing-lg);
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
                style="margin-right: 0.5rem;" title="테마 변경">
            <span id="themeIcon">🌙</span>
        </button>
    ')
    ->addAction('<span style="margin-right: 1rem; color: var(--linear-text-secondary);">' . htmlspecialchars($_SESSION["name"]) . '님</span>')
    ->addAction('<a href="../login/logout.php" style="color: var(--linear-text-secondary); text-decoration: none;">로그아웃</a>')
    ->fixed();

    echo $nav;
    ?>
    <div class="detail-container" style="margin-top: var(--linear-header-height);">

        <nav class="breadcrumb">
            <a href="index.php">홈</a>
            <span class="breadcrumb-separator">/</span>
            <span>측정 상세보기</span>
        </nav>

        <h2 class="page-title">
            <i class="bi bi-grid-3x3-gap"></i>
            <?= htmlspecialchars($measurement['site_name']) ?> 측정 상세보기
        </h2>

        <!-- Basic Information -->
        <div class="detail-grid">
            <!-- Site Information -->
            <div class="info-card">
                <div class="info-card-header">
                    <h3><i class="bi bi-building"></i> 현장 정보</h3>
                    <div style="display: flex; gap: var(--linear-spacing-sm);">
                        <?php
                        require_once '../components/LinearButton.php';

                        echo LinearButton::primary('<i class="bi bi-pencil"></i> 수정하기')
                            ->size('sm')
                            ->addAttribute('onclick', 'window.location.href="panel_measurement.php?edit=' . $measurement['id'] . '"')
                            ->addAttribute('title', '측정 데이터 수정하기');

                        echo LinearButton::secondary('<i class="bi bi-trash"></i> 삭제하기')
                            ->size('sm')
                            ->addAttribute('onclick', 'deleteMeasurement(' . $measurement['id'] . ')')
                            ->addAttribute('title', '측정 데이터 삭제하기')
                            ->addAttribute('style', 'background-color: #dc3545; color: white; border-color: #dc3545;')
                            ->addAttribute('onmouseover', 'this.style.backgroundColor="#c82333"; this.style.borderColor="#bd2130";')
                            ->addAttribute('onmouseout', 'this.style.backgroundColor="#dc3545"; this.style.borderColor="#dc3545";');
                        ?>
                    </div>
                </div>
                <div class="info-row">
                    <span class="info-label">현장명</span>
                    <span class="info-value"><?= htmlspecialchars($measurement['site_name']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">측정일자</span>
                    <span class="info-value"><?= htmlspecialchars($measurement['measurement_date']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">측정자</span>
                    <span class="info-value"><?= htmlspecialchars($measurement['measurer_name']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">등록일시</span>
                    <span class="info-value"><?= date('Y-m-d H:i:s', strtotime($measurement['created_at'])) ?></span>
                </div>
                <?php if (!empty($measurement['updated_at']) && $measurement['updated_at'] !== $measurement['created_at']): ?>
                <div class="info-row">
                    <span class="info-label">최종수정일시</span>
                    <span class="info-value" style="color: var(--linear-brand-primary); font-weight: var(--linear-font-weight-medium);">
                        <?= date('Y-m-d H:i:s', strtotime($measurement['updated_at'])) ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Car Information -->
            <div class="info-card">
                <h3><i class="bi bi-rulers"></i> 카 정보</h3>
                <div class="info-row">
                    <span class="info-label">가로 (W)</span>
                    <span class="info-value"><?= number_format($measurement['car_inside_width']) ?>mm</span>
                </div>
                <div class="info-row">
                    <span class="info-label">깊이 (D)</span>
                    <span class="info-value"><?= number_format($measurement['car_inside_depth']) ?>mm</span>
                </div>
                <div class="info-row">
                    <span class="info-label">높이 (H)</span>
                    <span class="info-value"><?= number_format($measurement['car_inside_height']) ?>mm</span>
                </div>
                <div class="info-row">
                    <span class="info-label">의장재질</span>
                    <span class="info-value">
                        <?= !empty($measurement['material_type']) ? htmlspecialchars($measurement['material_type']) : '미설정' ?>
                        <?php if (!empty($measurement['material_thickness'])): ?>
                            <span class="badge badge-secondary"><?= $measurement['material_thickness'] ?>t</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">엘리베이터 대수</span>
                    <span class="info-value">
                        <?= !empty($measurement['elevator_count']) ? number_format($measurement['elevator_count']) : '1' ?>대
                        <?php if (!empty($measurement['elevator_count']) && $measurement['elevator_count'] > 1): ?>
                            <span class="badge" style="background-color: var(--linear-color-orange); margin-left: 8px;">
                                다중 엘리베이터
                            </span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Panel Information -->
        <div class="info-card">
            <h3><i class="bi bi-grid-3x3-gap"></i> 패널 정보</h3>

            <?php if (!empty($panel_data) || !empty($transom_data)): ?>
                <div class="panel-grid">
                    <?php foreach ($panel_data as $panel_num => $panel_info): ?>
                        <?php if ($panel_num == '12') continue; // 12번은 따로 Transom으로 처리 ?>
                        <div class="panel-card">
                            <h4>
                                <i class="bi bi-square"></i>
                                패널 <?= htmlspecialchars($panel_num) ?>번
                                <?php if (($panel_num == '1' || $panel_num == '11') && !empty($panel_info['panelType'])): ?>
                                    <span class="badge badge-secondary" style="margin-left: 8px; font-size: 0.75rem;">
                                        <?= htmlspecialchars($panel_info['panelType']) ?>
                                    </span>
                                <?php endif; ?>
                            </h4>

                            <?php if (!empty($panel_info['width']) || !empty($panel_info['height'])): ?>
                                <div class="info-row">
                                    <span class="info-label">크기 (W×H)</span>
                                    <span class="info-value">
                                        <?= !empty($panel_info['width']) ? number_format($panel_info['width']) : '0' ?>×<?= !empty($panel_info['height']) ? number_format($panel_info['height']) : '0' ?>mm
                                    </span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($panel_info['materialType'])): ?>
                                <div class="info-row">
                                    <span class="info-label">재질</span>
                                    <span class="info-value"><?= htmlspecialchars($panel_info['materialType']) ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($panel_info['thickness'])): ?>
                                <div class="info-row">
                                    <span class="info-label">두께</span>
                                    <span class="info-value"><?= htmlspecialchars($panel_info['thickness']) ?>t</span>
                                </div>
                            <?php endif; ?>

                            <?php
                            // 1번, 11번 패널의 추가 정보 표시
                            if ($panel_num == '1' || $panel_num == '11'):
                                $corner_details = [];
                                if (!empty($panel_info['frontThickness'])) $corner_details['전면두께'] = $panel_info['frontThickness'] . 'mm';
                                if (!empty($panel_info['frontWing'])) $corner_details['전면날개'] = $panel_info['frontWing'] . 'mm';
                                if (!empty($panel_info['backThickness'])) $corner_details['후면두께'] = $panel_info['backThickness'] . 'mm';
                                if (!empty($panel_info['backWing'])) $corner_details['후면날개'] = $panel_info['backWing'] . 'mm';

                                if (!empty($corner_details)):
                            ?>
                                <div style="background-color: var(--linear-bg-secondary); border-radius: var(--linear-radius-md); padding: var(--linear-spacing-md); margin: var(--linear-spacing-md) 0;">
                                    <strong style="color: var(--linear-brand-primary); font-size: 0.9rem;">
                                        <i class="bi bi-info-circle"></i> 코너 패널 상세정보
                                    </strong>
                                    <?php foreach ($corner_details as $label => $value): ?>
                                        <div class="info-row" style="padding: var(--linear-spacing-xs) 0;">
                                            <span class="info-label" style="font-size: 0.85rem;"><?= $label ?></span>
                                            <span class="info-value" style="font-size: 0.85rem;"><?= $value ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php
                                endif;
                            endif;
                            ?>

                            <?php if (!empty($panel_info['drillingWidth']) || !empty($panel_info['drillingHeight'])): ?>
                                <div class="info-row">
                                    <span class="info-label">타공</span>
                                    <span class="info-value">
                                        <span class="badge badge-success">
                                            <?= number_format($panel_info['drillingWidth']) ?>×<?= number_format($panel_info['drillingHeight']) ?>mm
                                        </span>
                                    </span>
                                </div>

                                <?php if (!empty($panel_info['drillingFromFloor'])): ?>
                                    <div class="info-row">
                                        <span class="info-label">바닥부터 높이</span>
                                        <span class="info-value"><?= number_format($panel_info['drillingFromFloor']) ?>mm</span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($panel_info['drillingFromEntrance'])): ?>
                                    <div class="info-row">
                                        <span class="info-label">입구부터 거리</span>
                                        <span class="info-value"><?= number_format($panel_info['drillingFromEntrance']) ?>mm</span>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if (!empty($panel_info['notes'])): ?>
                                <div class="info-row">
                                    <span class="info-label">특이사항</span>
                                    <span class="info-value"><?= htmlspecialchars($panel_info['notes']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <?php
                    // Transom 데이터는 transom_data['12'] 또는 panel_data['12']에 있을 수 있음
                    $transom_info = [];
                    if (!empty($transom_data['12'])) {
                        $transom_info = $transom_data['12'];
                    } elseif (!empty($panel_data['12'])) {
                        $transom_info = $panel_data['12'];
                    }

                    if (!empty($transom_info)):
                    ?>
                        <div class="panel-card">
                            <h4>
                                <i class="bi bi-triangle" style="color: var(--linear-color-purple);"></i>
                                Transom (12번)
                                <span class="badge" style="background-color: var(--linear-color-purple); margin-left: 8px; font-size: 0.75rem;">
                                    Transom
                                </span>
                            </h4>

                            <?php if (!empty($transom_info['width']) || !empty($transom_info['height'])): ?>
                                <div class="info-row">
                                    <span class="info-label">크기 (W×H)</span>
                                    <span class="info-value">
                                        <?= !empty($transom_info['width']) ? number_format($transom_info['width']) : '0' ?>×<?= !empty($transom_info['height']) ? number_format($transom_info['height']) : '0' ?>mm
                                    </span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($transom_info['materialType'])): ?>
                                <div class="info-row">
                                    <span class="info-label">재질</span>
                                    <span class="info-value"><?= htmlspecialchars($transom_info['materialType']) ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($transom_info['thickness'])): ?>
                                <div class="info-row">
                                    <span class="info-label">두께</span>
                                    <span class="info-value"><?= htmlspecialchars($transom_info['thickness']) ?>t</span>
                                </div>
                            <?php endif; ?>

                            <?php
                            // Transom 상세 정보 표시
                            $transom_details = [];
                            if (!empty($transom_info['transomPlateHeight'])) $transom_details['트랜섬 막판높이'] = $transom_info['transomPlateHeight'] . 'mm';
                            if (!empty($transom_info['bottomDepthJD'])) $transom_details['밑면깊이(JD)'] = $transom_info['bottomDepthJD'] . 'mm';
                            if (!empty($transom_info['wingValue'])) $transom_details['날개값'] = $transom_info['wingValue'] . 'mm';

                            if (!empty($transom_details)):
                            ?>
                                <div style="background-color: var(--linear-bg-secondary); border-radius: var(--linear-radius-md); padding: var(--linear-spacing-md); margin: var(--linear-spacing-md) 0;">
                                    <strong style="color: var(--linear-color-purple); font-size: 0.9rem;">
                                        <i class="bi bi-info-circle"></i> Transom 상세정보
                                    </strong>
                                    <?php foreach ($transom_details as $label => $value): ?>
                                        <div class="info-row" style="padding: var(--linear-spacing-xs) 0;">
                                            <span class="info-label" style="font-size: 0.85rem;"><?= $label ?></span>
                                            <span class="info-value" style="font-size: 0.85rem;"><?= $value ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php
                            // CPI 타공 정보 표시
                            $cpi_drilling = [];
                            if (!empty($transom_info['cpiDrillingWidth'])) $cpi_drilling['CPI타공 가로'] = $transom_info['cpiDrillingWidth'] . 'mm';
                            if (!empty($transom_info['cpiDrillingHeight'])) $cpi_drilling['CPI타공 세로'] = $transom_info['cpiDrillingHeight'] . 'mm';
                            if (!empty($transom_info['cpiDrillingHeightFromBottom'])) $cpi_drilling['CPI타공높이(밑면기준)'] = $transom_info['cpiDrillingHeightFromBottom'] . 'mm';

                            if (!empty($cpi_drilling)):
                            ?>
                                <div style="background-color: var(--linear-bg-tertiary); border-radius: var(--linear-radius-md); padding: var(--linear-spacing-md); margin: var(--linear-spacing-md) 0;">
                                    <strong style="color: var(--linear-color-orange); font-size: 0.9rem;">
                                        <i class="bi bi-tools"></i> CPI 타공 정보
                                    </strong>
                                    <?php foreach ($cpi_drilling as $label => $value): ?>
                                        <div class="info-row" style="padding: var(--linear-spacing-xs) 0;">
                                            <span class="info-label" style="font-size: 0.85rem;"><?= $label ?></span>
                                            <span class="info-value" style="font-size: 0.85rem;"><?= $value ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($transom_info['drillingWidth']) || !empty($transom_info['drillingHeight'])): ?>
                                <div class="info-row">
                                    <span class="info-label">일반 타공</span>
                                    <span class="info-value">
                                        <span class="badge badge-success">
                                            <?= number_format($transom_info['drillingWidth']) ?>×<?= number_format($transom_info['drillingHeight']) ?>mm
                                        </span>
                                    </span>
                                </div>

                                <?php if (!empty($transom_info['drillingFromFloor'])): ?>
                                    <div class="info-row">
                                        <span class="info-label">바닥부터 높이</span>
                                        <span class="info-value"><?= number_format($transom_info['drillingFromFloor']) ?>mm</span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($transom_info['drillingFromEntrance'])): ?>
                                    <div class="info-row">
                                        <span class="info-label">입구부터 거리</span>
                                        <span class="info-value"><?= number_format($transom_info['drillingFromEntrance']) ?>mm</span>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if (!empty($transom_info['notes'])): ?>
                                <div class="info-row">
                                    <span class="info-label">특이사항</span>
                                    <span class="info-value"><?= htmlspecialchars($transom_info['notes']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="panel-no-data">
                    <p>등록된 패널 정보가 없습니다.</p>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($measurement['notes'])): ?>
            <!-- Notes -->
            <div class="info-card">
                <h3><i class="bi bi-sticky"></i> 특이사항</h3>
                <p style="color: var(--linear-text-primary); line-height: 1.6; margin: 0;">
                    <?= nl2br(htmlspecialchars($measurement['notes'])) ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Actions -->
        <div style="display: flex; gap: var(--linear-spacing-md); margin-top: var(--linear-spacing-xl); justify-content: center; flex-wrap: wrap;">
            <?php
            require_once '../components/LinearButton.php';

            echo LinearButton::secondary('<i class="bi bi-arrow-left"></i> 목록으로 돌아가기')
                ->addAttribute('onclick', 'window.location.href="index.php"');

            echo LinearButton::primary('<i class="bi bi-pencil"></i> 수정하기')
                ->addAttribute('onclick', 'window.location.href="panel_measurement.php?edit=' . $measurement['id'] . '"');

            echo LinearButton::secondary('<i class="bi bi-trash"></i> 삭제하기')
                ->addAttribute('onclick', 'deleteMeasurement(' . $measurement['id'] . ')')
                ->addAttribute('title', '측정 데이터를 완전히 삭제합니다')
                ->addAttribute('style', 'background-color: #dc3545; color: white; border-color: #dc3545;')
                ->addAttribute('onmouseover', 'this.style.backgroundColor="#c82333"; this.style.borderColor="#bd2130";')
                ->addAttribute('onmouseout', 'this.style.backgroundColor="#dc3545"; this.style.borderColor="#dc3545";');
            ?>
        </div>
    </div>

    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Delete Measurement Logic -->
    <script>
        function deleteMeasurement(measurementId) {
            Swal.fire({
                title: '측정 데이터 삭제',
                text: '이 측정 데이터를 정말 삭제하시겠습니까?\n삭제된 데이터는 복구할 수 없습니다.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '삭제하기',
                cancelButtonText: '취소',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // 삭제 진행 중 표시
                    Swal.fire({
                        title: '삭제 중...',
                        text: '측정 데이터를 삭제하고 있습니다.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // 서버에 삭제 요청
                    fetch('delete_measurement.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            id: measurementId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: '삭제 완료',
                                text: '측정 데이터가 성공적으로 삭제되었습니다.',
                                icon: 'success',
                                confirmButtonText: '확인'
                            }).then(() => {
                                // 목록 페이지로 이동
                                window.location.href = 'index.php';
                            });
                        } else {
                            Swal.fire({
                                title: '삭제 실패',
                                text: data.message || '측정 데이터 삭제 중 오류가 발생했습니다.',
                                icon: 'error',
                                confirmButtonText: '확인'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Delete error:', error);
                        Swal.fire({
                            title: '삭제 실패',
                            text: '서버 오류가 발생했습니다. 잠시 후 다시 시도해주세요.',
                            icon: 'error',
                            confirmButtonText: '확인'
                        });
                    });
                }
            });
        }
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
        });
    </script>
</body>
</html>