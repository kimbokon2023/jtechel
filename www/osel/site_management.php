<?php
require_once '../lib/mydb.php';
session_start();

// Check authentication
if (!isset($_SESSION["level"]) || $_SESSION["level"] > 8) {
    header("Location: ../login/login_form.php");
    exit;
}

// Initialize database connection
$pdo = db_connect();
$DB = 'jtechel';

$message = '';
$messageType = '';
 
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $site_name = trim($_POST['site_name']);
        $site_address = trim($_POST['site_address']);
        $client_name = trim($_POST['client_name']);
        $client_phone = trim($_POST['client_phone']);
        $project_manager = trim($_POST['project_manager']);
        $elevator_count = intval($_POST['elevator_count']);
        $notes = trim($_POST['notes']);
        
        if (empty($site_name)) {
            throw new Exception('현장명은 필수입니다.');
        }
        
        // Check if site already exists in panel_measurements table
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM $DB.panel_measurements WHERE site_name = ?");
        $stmt->execute([$site_name]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('이미 등록된 현장명입니다.');
        }

        // Insert new site with basic measurement record (will be updated when actual measurement data is added)
        $stmt = $pdo->prepare("
            INSERT INTO $DB.panel_measurements
            (site_name, site_address, client_name, client_phone, project_manager,
             elevator_count, notes, created_by, measurer_name, measurer_id,
             measurement_date, car_inside_width, car_inside_depth, car_inside_height)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 0, 0, 0)
        ");

        $stmt->execute([
            $site_name, $site_address, $client_name, $client_phone,
            $project_manager, $elevator_count, $notes, $_SESSION["userid"],
            $_SESSION["name"], $_SESSION["userid"]
        ]);
        
        $message = '현장이 성공적으로 등록되었습니다.';
        $messageType = 'success';
        
        // Clear form
        $_POST = [];
        
    } catch (Exception $e) {
        $message = '오류: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get existing sites for reference from panel_measurements table
try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT site_name, client_name, elevator_count, created_at
        FROM $DB.panel_measurements
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recent_sites = $stmt->fetchAll();
} catch (PDOException $e) {
    $recent_sites = [];
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>현장 관리</title>
    
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
        
        .form-container {
            background-color: var(--linear-bg-primary);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-lg);
            padding: var(--linear-spacing-2xl);
            box-shadow: var(--linear-shadow-low);
            margin-bottom: var(--linear-spacing-xl);
        }
        
        .form-section {
            border-left: 3px solid var(--linear-brand-primary);
            padding-left: var(--linear-spacing-lg);
            margin-bottom: var(--linear-spacing-2xl);
        }
        
        .form-section h6 {
            color: var(--linear-brand-primary);
            font-weight: var(--linear-font-weight-semibold);
            font-size: var(--linear-text-title3);
            margin-bottom: var(--linear-spacing-md);
        }
        
        .recent-sites {
            background-color: var(--linear-bg-secondary);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-lg);
            padding: var(--linear-spacing-lg);
            margin-bottom: var(--linear-spacing-xl);
        }
        
        .site-item {
            border-bottom: 1px solid var(--linear-border-secondary);
            padding: var(--linear-spacing-md) 0;
            transition: background-color var(--linear-transition-fast);
        }
        
        .site-item:last-child {
            border-bottom: none;
        }
        
        .site-item:hover {
            background-color: var(--linear-bg-tertiary);
            border-radius: var(--linear-radius-sm);
            margin: 0 calc(-1 * var(--linear-spacing-sm));
            padding-left: var(--linear-spacing-sm);
            padding-right: var(--linear-spacing-sm);
        }
        
        .section-title {
            font-size: var(--linear-text-display1);
            font-weight: var(--linear-font-weight-semibold);
            color: var(--linear-text-primary);
            margin-bottom: var(--linear-spacing-xl);
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
        
        .form-header {
            display: flex;
            align-items: center;
            margin-bottom: var(--linear-spacing-xl);
            padding-bottom: var(--linear-spacing-lg);
            border-bottom: 1px solid var(--linear-border-secondary);
        }
        
        .form-header i {
            font-size: 2rem;
            color: var(--linear-brand-primary);
            margin-right: var(--linear-spacing-md);
        }
        
        .form-header h4 {
            color: var(--linear-text-primary);
            font-size: var(--linear-text-title2);
            font-weight: var(--linear-font-weight-semibold);
            margin: 0;
        }
        
        .form-header p {
            color: var(--linear-text-secondary);
            margin: var(--linear-spacing-xs) 0 0 0;
            font-size: var(--linear-text-small);
        }
        
        .quick-tips {
            background-color: var(--linear-bg-secondary);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-lg);
            padding: var(--linear-spacing-lg);
        }
        
        .quick-tips h5 {
            color: var(--linear-text-primary);
            font-size: var(--linear-text-title3);
            font-weight: var(--linear-font-weight-semibold);
            margin-bottom: var(--linear-spacing-md);
        }
        
        .tips-item {
            display: flex;
            align-items: center;
            margin-bottom: var(--linear-spacing-sm);
            font-size: var(--linear-text-small);
            color: var(--linear-text-secondary);
        }
        
        .tips-item:last-child {
            margin-bottom: 0;
        }
        
        .tips-item i {
            color: var(--linear-color-green);
            margin-right: var(--linear-spacing-xs);
        }
        
        .site-item h6 {
            color: var(--linear-text-primary);
            font-size: var(--linear-text-body);
            font-weight: var(--linear-font-weight-medium);
            margin-bottom: var(--linear-spacing-xs);
        }
        
        .site-item small {
            color: var(--linear-text-secondary);
            font-size: var(--linear-text-mini);
        }
        
        .badge {
            background-color: var(--linear-bg-tertiary);
            color: var(--linear-text-secondary);
            padding: 2px 8px;
            border-radius: var(--linear-radius-sm);
            font-size: var(--linear-text-mini);
            font-weight: var(--linear-font-weight-medium);
            border: 1px solid var(--linear-border-secondary);
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
        
        @media (max-width: 768px) {
            .site-management-container {
                padding: var(--linear-spacing-md);
            }
            
            .site-management-container > div:first-child {
                grid-template-columns: 1fr;
                gap: var(--linear-spacing-lg);
            }
            
            .form-container {
                padding: var(--linear-spacing-lg);
            }
            
            .form-header {
                flex-direction: column;
                text-align: center;
                align-items: flex-start;
            }
            
            .form-header i {
                margin-right: 0;
                margin-bottom: var(--linear-spacing-sm);
            }
            
            .form-section > div[style*="grid-template-columns"] {
                grid-template-columns: 1fr !important;
            }
        }

        /* Desktop/Large: form left, recent+tips right. Mobile: recent -> form -> tips */
        .grid-layout {
            display: grid;
            grid-template-columns: 1fr 300px;
            grid-template-areas:
                "form recent"
                "form tips";
            gap: var(--linear-spacing-xl);
            align-items: start;
        }
        .grid-area-form { grid-area: form; }
        .grid-area-recent { grid-area: recent; }
        .grid-area-tips { grid-area: tips; }
        @media (max-width: 768px) {
            .grid-layout {
                grid-template-columns: 1fr;
                grid-template-areas:
                    "recent"
                    "form"
                    "tips";
                gap: var(--linear-spacing-lg);
            }
        }
    </style>
</head>
<body>
    <?php
    // Linear 네비게이션 생성 (상단 공통 톤앤매너)
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
    ->addAction('<a href="index.php" style="color: var(--linear-text-secondary); text-decoration: none; margin-right: 1rem;">대시보드</a>')
    ->addAction('<a href="../login/logout.php" style="color: var(--linear-text-secondary); text-decoration: none;">로그아웃</a>')
    ->fixed();
    
    echo $nav;
    ?>

    <div class="site-management-container" style="margin-top: var(--linear-header-height);">

        <!-- Alert Messages -->
        <?php if ($message): ?>
        <?php
        require_once '../components/LinearAlert.php';
        $alertType = $messageType === 'success' ? 'success' : ($messageType === 'danger' ? 'error' : 'warning');
        echo LinearAlert::create($message, $alertType)->dismissible();
        ?>
        <?php endif; ?>

        <!-- Breadcrumb & Title -->
        <nav class="pm-breadcrumb">
            <a href="index.php">대시보드</a>
            <span class="sep">/</span>
            <span>현장 관리</span>
        </nav>
        <h2 class="page-title"><i class="bi bi-building-add"></i> 현장 관리</h2>

        <div class="grid-layout">
            <!-- Site Registration Form -->
            <div class="grid-area-form">
                <div class="form-container">
                    <div class="form-header">
                        <i class="bi bi-building"></i>
                        <div>
                            <h4>새 현장 등록</h4>
                            <p>엘리베이터 측정 프로젝트를 위한 현장 정보를 등록합니다.</p>
                        </div>
                    </div>

                    <form method="POST" id="siteForm">
                        <!-- Basic Site Information -->
                        <div class="form-section">
                            <h6><i class="bi bi-geo-alt"></i> 기본 현장 정보</h6>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--linear-spacing-lg); margin-bottom: var(--linear-spacing-lg);">
                                <div>
                                    <?php
                                    require_once '../components/LinearInput.php';
                                    echo LinearInput::text('site_name')
                                        ->label('현장명 *')
                                        ->value(htmlspecialchars($_POST['site_name'] ?? ''))
                                        ->placeholder('예: 서울역 오피스텔')
                                        ->required()
                                        ->maxLength(100)
                                        ->help('고유한 현장명을 입력하세요')
                                        ->renderGroup();
                                    ?>
                                </div>
                                <div>
                                    <label for="elevatorCount" style="display: block; margin-bottom: var(--linear-spacing-xs); font-size: var(--linear-text-small); font-weight: var(--linear-font-weight-medium); color: var(--linear-text-primary);">엘리베이터 대수</label>
                                    <select id="elevatorCount" name="elevator_count" style="width: 100%; padding: var(--linear-spacing-sm); border: 1px solid var(--linear-border-primary); border-radius: var(--linear-radius-md); background-color: var(--linear-bg-primary); color: var(--linear-text-primary); font-size: var(--linear-text-body);">
                                        <option value="1" <?= ($_POST['elevator_count'] ?? '') == '1' ? 'selected' : '' ?>>1대</option>
                                        <option value="2" <?= ($_POST['elevator_count'] ?? '') == '2' ? 'selected' : '' ?>>2대</option>
                                        <option value="3" <?= ($_POST['elevator_count'] ?? '') == '3' ? 'selected' : '' ?>>3대</option>
                                        <option value="4" <?= ($_POST['elevator_count'] ?? '') == '4' ? 'selected' : '' ?>>4대</option>
                                        <option value="5" <?= ($_POST['elevator_count'] ?? '') == '5' ? 'selected' : '' ?>>5대 이상</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <?php
                                echo LinearInput::text('site_address')
                                    ->label('현장 주소')
                                    ->value(htmlspecialchars($_POST['site_address'] ?? ''))
                                    ->placeholder('예: 서울시 중구 한강대로 405')
                                    ->maxLength(200)
                                    ->renderGroup();
                                ?>
                            </div>
                        </div>

                        <!-- Client Information -->
                        <div class="form-section">
                            <h6><i class="bi bi-person-lines-fill"></i> 고객 정보</h6>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--linear-spacing-lg);">
                                <div>
                                    <?php
                                    echo LinearInput::text('client_name')
                                        ->label('고객명/업체명')
                                        ->value(htmlspecialchars($_POST['client_name'] ?? ''))
                                        ->placeholder('예: 홍길동 또는 ㈜엘리베이터건설')
                                        ->maxLength(100)
                                        ->renderGroup();
                                    ?>
                                </div>
                                <div>
                                    <?php
                                    echo LinearInput::tel('client_phone')
                                        ->label('연락처')
                                        ->value(htmlspecialchars($_POST['client_phone'] ?? ''))
                                        ->placeholder('예: 010-1234-5678')
                                        ->maxLength(20)
                                        ->renderGroup();
                                    ?>
                                </div>
                            </div>
                        </div>

                        <!-- Project Management -->
                        <div class="form-section">
                            <h6><i class="bi bi-person-workspace"></i> 프로젝트 관리</h6>
                            <div style="max-width: 50%;">
                                <?php
                                echo LinearInput::text('project_manager')
                                    ->label('담당자')
                                    ->value(htmlspecialchars($_POST['project_manager'] ?? $_SESSION["name"]))
                                    ->placeholder('프로젝트 담당자명')
                                    ->maxLength(50)
                                    ->renderGroup();
                                ?>
                            </div>
                        </div>

                        <!-- Additional Notes -->
                        <div class="form-section">
                            <h6><i class="bi bi-chat-text"></i> 추가 정보</h6>
                            <div>
                                <?php
                                echo LinearInput::textarea('notes')
                                    ->label('특이사항 및 메모')
                                    ->value(htmlspecialchars($_POST['notes'] ?? ''))
                                    ->placeholder('현장의 특이사항, 주의사항, 기타 메모 등을 입력하세요')
                                    ->rows(4)
                                    ->maxLength(500)
                                    ->help('최대 500자까지 입력 가능')
                                    ->renderGroup();
                                ?>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div style="display: flex; gap: var(--linear-spacing-sm); justify-content: flex-end; flex-wrap: wrap;">
                            <?php
                            require_once '../components/LinearButton.php';
                            
                            echo '<a href="index.php" style="text-decoration: none;">' . 
                                 LinearButton::outline('<i class="bi bi-arrow-left"></i> 취소') . 
                                 '</a>';
                            
                            echo LinearButton::outline('<i class="bi bi-arrow-clockwise"></i> 초기화')
                                ->addAttribute('type', 'reset');
                            
                            echo LinearButton::primary('<i class="bi bi-save"></i> 현장 등록')
                                ->addAttribute('type', 'submit');
                            ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Recent Sites Sidebar -->
            <div class="grid-area-recent">
                <div class="recent-sites">
                    <h5 style="color: var(--linear-text-primary); font-size: var(--linear-text-title3); font-weight: var(--linear-font-weight-semibold); margin-bottom: var(--linear-spacing-md); display: flex; align-items: center; gap: var(--linear-spacing-xs);">
                        <i class="bi bi-clock-history"></i> 최근 등록된 현장
                    </h5>
                    
                    <?php if (!empty($recent_sites)): ?>
                        <?php foreach ($recent_sites as $site): ?>
                        <div class="site-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?= htmlspecialchars($site['site_name']) ?></h6>
                                    <?php if ($site['client_name']): ?>
                                    <small class="text-muted">
                                        <i class="bi bi-person"></i> <?= htmlspecialchars($site['client_name']) ?>
                                    </small>
                                    <?php endif; ?>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted d-block">
                                        <?= date('m/d', strtotime($site['created_at'])) ?>
                                    </small>
                                    <small class="badge bg-light text-dark">
                                        <?= $site['elevator_count'] ?>대
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">등록된 현장이 없습니다.</p>
                    <?php endif; ?>

                    <div style="margin-top: var(--linear-spacing-md);">
                        <?php
                        echo '<a href="list.php" style="text-decoration: none; width: 100%; display: block;">' .
                             LinearButton::outline('<i class="bi bi-list"></i> 전체 현장 보기')
                                ->size('sm')
                                ->addAttribute('style', 'width: 100%;') .
                             '</a>';
                        ?>
                    </div>
                </div>

                <!-- Quick Tips -->
                <div class="quick-tips grid-area-tips">
                    <h5>
                        <i class="bi bi-lightbulb"></i> 현장 등록 가이드
                    </h5>
                    <div>
                        <div class="tips-item">
                            <i class="bi bi-check-circle"></i>
                            현장명은 고유하게 설정하세요
                        </div>
                        <div class="tips-item">
                            <i class="bi bi-check-circle"></i>
                            엘리베이터 대수를 정확히 입력하세요
                        </div>
                        <div class="tips-item">
                            <i class="bi bi-check-circle"></i>
                            고객 연락처는 정확히 기입하세요
                        </div>
                        <div class="tips-item">
                            <i class="bi bi-check-circle"></i>
                            특이사항은 상세히 메모해두세요
                        </div>
                    </div>
                </div>
            </div>
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
            
            // Form validation and enhancement
            const siteForm = document.getElementById('siteForm');
            if (siteForm) {
                siteForm.addEventListener('submit', function(e) {
                    const siteNameInput = document.querySelector('input[name="site_name"]');
                    if (siteNameInput && !siteNameInput.value.trim()) {
                        e.preventDefault();
                        alert('현장명을 입력해주세요.');
                        siteNameInput.focus();
                        return;
                    }
                });
            }

            // Phone number formatting
            const clientPhone = document.querySelector('input[name="client_phone"]');
            if (clientPhone) {
                clientPhone.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length >= 3) {
                        if (value.length <= 7) {
                            value = value.replace(/(\d{3})(\d{1,4})/, '$1-$2');
                        } else {
                            value = value.replace(/(\d{3})(\d{4})(\d{1,4})/, '$1-$2-$3');
                        }
                    }
                    e.target.value = value;
                });
            }

            // Character counter for notes
            const notesTextarea = document.querySelector('textarea[name="notes"]');
            if (notesTextarea) {
                const helpText = notesTextarea.parentElement.querySelector('.linear-input-help');
                if (helpText) {
                    function updateCharCounter() {
                        const maxLength = 500;
                        const currentLength = notesTextarea.value.length;
                        helpText.textContent = `${currentLength}/${maxLength}자 (${maxLength - currentLength}자 남음)`;
                        
                        if (currentLength > maxLength * 0.9) {
                            helpText.style.color = 'var(--linear-color-orange)';
                        } else {
                            helpText.style.color = 'var(--linear-text-tertiary)';
                        }
                    }
                    
                    notesTextarea.addEventListener('input', updateCharCounter);
                    updateCharCounter(); // Initial count
                }
            }
        });
    </script>
</body>
</html>