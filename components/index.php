<?php
// Linear Components Demo Page
// 모든 컴포넌트를 import
require_once 'LinearComponent.php';
require_once 'LinearButton.php';
require_once 'LinearCard.php';
require_once 'LinearInput.php';
require_once 'LinearAlert.php';
require_once 'LinearNavigation.php';

// 현재 활성 섹션 확인 (URL 파라미터 기반)
$activeSection = $_GET['section'] ?? 'overview';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Linear Design System - Component Demo</title>
    
    <!-- Linear Theme CSS -->
    <link rel="stylesheet" href="linear-theme.css">
    <link rel="stylesheet" href="linear-components.css">
    
    <!-- Theme Toggle JavaScript -->
    <script src="linear-theme-toggle.js"></script>
    
    <!-- Demo specific styles -->
    <style>
        body {
            margin: 0;
            padding-top: var(--linear-header-height);
        }
        
        .demo-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: var(--linear-spacing-3xl) var(--linear-spacing-lg);
        }
        
        .demo-section {
            margin-bottom: var(--linear-spacing-3xl);
        }
        
        .demo-section-title {
            font-size: 2rem;
            font-weight: var(--linear-font-weight-semibold);
            margin-bottom: var(--linear-spacing-lg);
            color: var(--linear-text-primary);
            border-bottom: 2px solid var(--linear-brand-primary);
            padding-bottom: var(--linear-spacing-md);
        }
        
        .demo-grid {
            display: grid;
            gap: var(--linear-spacing-lg);
            margin-bottom: var(--linear-spacing-xl);
        }
        
        .demo-grid-2 {
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        }
        
        .demo-grid-3 {
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        }
        
        .demo-item {
            padding: var(--linear-spacing-lg);
            background-color: var(--linear-bg-secondary);
            border-radius: var(--linear-radius-lg);
            border: 1px solid var(--linear-border-primary);
        }
        
        .demo-item-title {
            font-weight: var(--linear-font-weight-medium);
            margin-bottom: var(--linear-spacing-md);
            color: var(--linear-text-primary);
        }
        
        .demo-showcase {
            display: flex;
            flex-wrap: wrap;
            gap: var(--linear-spacing-md);
            align-items: center;
        }
        
        .code-block {
            background-color: var(--linear-bg-tertiary);
            border: 1px solid var(--linear-border-primary);
            border-radius: var(--linear-radius-md);
            padding: var(--linear-spacing-md);
            font-family: var(--linear-font-monospace);
            font-size: var(--linear-text-small);
            color: var(--linear-text-secondary);
            overflow-x: auto;
            margin-top: var(--linear-spacing-md);
        }
        
        .hero-section {
            text-align: center;
            margin-bottom: var(--linear-spacing-3xl);
            padding: var(--linear-spacing-3xl) 0;
            background: linear-gradient(135deg, var(--linear-brand-primary-tint) 0%, var(--linear-bg-primary) 100%);
            border-radius: var(--linear-radius-lg);
            border: 1px solid var(--linear-border-primary);
        }
        
        .hero-title {
            font-size: 3rem;
            font-weight: var(--linear-font-weight-semibold);
            margin-bottom: var(--linear-spacing-md);
            color: var(--linear-text-primary);
        }
        
        .hero-subtitle {
            font-size: var(--linear-text-large);
            color: var(--linear-text-secondary);
            margin-bottom: var(--linear-spacing-xl);
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .color-swatch {
            display: inline-block;
            width: 40px;
            height: 40px;
            border-radius: var(--linear-radius-md);
            border: 1px solid var(--linear-border-primary);
            margin-right: var(--linear-spacing-sm);
            vertical-align: middle;
        }
        
        .typography-sample {
            margin-bottom: var(--linear-spacing-md);
            padding: var(--linear-spacing-md) 0;
            border-bottom: 1px solid var(--linear-border-secondary);
        }
        
        .typography-sample:last-child {
            border-bottom: none;
        }
        
        .spacing-demo {
            display: flex;
            align-items: center;
            margin-bottom: var(--linear-spacing-sm);
        }
        
        .spacing-box {
            background-color: var(--linear-brand-primary);
            height: 20px;
            margin-right: var(--linear-spacing-md);
        }
    </style>
</head>
<body>
    <?php
    // 네비게이션 생성
    $nav = LinearNavigation::withBrand('Linear Components', '#')
        ->setMenuItems([
            ['label' => '개요', 'href' => '?section=overview', 'active' => $activeSection === 'overview'],
            ['label' => '버튼', 'href' => '?section=buttons', 'active' => $activeSection === 'buttons'],
            ['label' => '카드', 'href' => '?section=cards', 'active' => $activeSection === 'cards'],
            ['label' => '입력', 'href' => '?section=inputs', 'active' => $activeSection === 'inputs'],
            ['label' => '알림', 'href' => '?section=alerts', 'active' => $activeSection === 'alerts'],
            ['label' => '디자인', 'href' => '?section=design', 'active' => $activeSection === 'design']
        ])
        ->addAction('<button type="button" data-linear-theme-toggle style="margin-right: 0.5rem;"></button>')
        ->addAction('<a href="https://linear.app" target="_blank" class="linear-btn linear-btn-primary linear-btn-sm">Linear.app</a>')
        ->fixed()
        ->mobileMenu();
    
    echo $nav;
    ?>
    
    <div class="demo-container">
        <?php if ($activeSection === 'overview'): ?>
            <!-- 개요 섹션 -->
            <div class="hero-section">
                <h1 class="hero-title">Linear Design System</h1>
                <p class="hero-subtitle">
                    Linear.app에서 추출한 디자인 시스템을 기반으로 한 재사용 가능한 PHP 컴포넌트 라이브러리
                </p>
                <div class="demo-showcase">
                    <?php
                    echo LinearButton::primary('시작하기', ['onclick' => 'window.location="?section=buttons"']);
                    echo LinearButton::secondary('문서 보기', ['onclick' => 'window.open("README.md", "_blank")', 'style' => 'margin-left: 1rem;']);
                    ?>
                </div>
            </div>
            
            <div class="demo-section">
                <h2 class="demo-section-title">사용 가능한 컴포넌트</h2>
                <div class="demo-grid demo-grid-3">
                    <div class="demo-item">
                        <h3 class="demo-item-title">LinearButton</h3>
                        <p>다양한 스타일과 상태를 지원하는 버튼 컴포넌트</p>
                        <?php echo LinearButton::primary('예시 버튼')->size('sm'); ?>
                    </div>
                    <div class="demo-item">
                        <h3 class="demo-item-title">LinearCard</h3>
                        <p>헤더, 콘텐츠, 푸터를 포함할 수 있는 카드 컴포넌트</p>
                    </div>
                    <div class="demo-item">
                        <h3 class="demo-item-title">LinearInput</h3>
                        <p>라벨, 에러, 도움말을 지원하는 입력 필드 컴포넌트</p>
                        <?php echo LinearInput::text()->placeholder('예시 입력')->size('sm'); ?>
                    </div>
                    <div class="demo-item">
                        <h3 class="demo-item-title">LinearAlert</h3>
                        <p>정보, 성공, 경고, 에러 메시지를 표시하는 알림 컴포넌트</p>
                    </div>
                    <div class="demo-item">
                        <h3 class="demo-item-title">LinearNavigation</h3>
                        <p>브랜드, 메뉴, 액션을 포함하는 네비게이션 컴포넌트</p>
                    </div>
                </div>
            </div>
            
        <?php elseif ($activeSection === 'buttons'): ?>
            <!-- 버튼 섹션 -->
            <div class="demo-section">
                <h2 class="demo-section-title">Button Components</h2>
                
                <div class="demo-item">
                    <h3 class="demo-item-title">버튼 변형</h3>
                    <div class="demo-showcase">
                        <?php
                        echo LinearButton::primary('Primary');
                        echo LinearButton::secondary('Secondary');
                        echo LinearButton::outline('Outline');
                        echo LinearButton::ghost('Ghost');
                        echo LinearButton::link('Link', '#');
                        ?>
                    </div>
                    <div class="code-block">LinearButton::primary('Primary')
LinearButton::secondary('Secondary')
LinearButton::outline('Outline')
LinearButton::ghost('Ghost')
LinearButton::link('Link', '#')</div>
                </div>
                
                <div class="demo-item">
                    <h3 class="demo-item-title">버튼 크기</h3>
                    <div class="demo-showcase">
                        <?php
                        echo LinearButton::primary('Small')->size(LinearButton::SIZE_SMALL);
                        echo LinearButton::primary('Medium')->size(LinearButton::SIZE_MEDIUM);
                        echo LinearButton::primary('Large')->size(LinearButton::SIZE_LARGE);
                        ?>
                    </div>
                    <div class="code-block">LinearButton::primary('Small')->size(LinearButton::SIZE_SMALL)
LinearButton::primary('Medium')->size(LinearButton::SIZE_MEDIUM)
LinearButton::primary('Large')->size(LinearButton::SIZE_LARGE)</div>
                </div>
                
                <div class="demo-item">
                    <h3 class="demo-item-title">버튼 상태</h3>
                    <div class="demo-showcase">
                        <?php
                        echo LinearButton::primary('Normal');
                        echo LinearButton::primary('Loading')->loading();
                        echo LinearButton::primary('Disabled')->disabled();
                        ?>
                    </div>
                    <div class="code-block">LinearButton::primary('Normal')
LinearButton::primary('Loading')->loading()
LinearButton::primary('Disabled')->disabled()</div>
                </div>
                
                <div class="demo-item">
                    <h3 class="demo-item-title">전체 너비 버튼</h3>
                    <div>
                        <?php echo LinearButton::primary('Full Width Button')->fullWidth(); ?>
                    </div>
                    <div class="code-block">LinearButton::primary('Full Width Button')->fullWidth()</div>
                </div>
            </div>
            
        <?php elseif ($activeSection === 'cards'): ?>
            <!-- 카드 섹션 -->
            <div class="demo-section">
                <h2 class="demo-section-title">Card Components</h2>
                
                <div class="demo-grid demo-grid-2">
                    <div>
                        <h3 class="demo-item-title">기본 카드</h3>
                        <?php
                        echo LinearCard::simple('
                            <h4>Simple Card</h4>
                            <p>기본적인 카드 컴포넌트입니다. 간단한 콘텐츠를 담을 수 있습니다.</p>
                        ');
                        ?>
                        <div class="code-block">LinearCard::simple('콘텐츠')</div>
                    </div>
                    
                    <div>
                        <h3 class="demo-item-title">헤더가 있는 카드</h3>
                        <?php
                        echo LinearCard::withHeader('Card Title', '
                            <p>헤더가 있는 카드 컴포넌트입니다.</p>
                        ');
                        ?>
                        <div class="code-block">LinearCard::withHeader('Card Title', '콘텐츠')</div>
                    </div>
                    
                    <div>
                        <h3 class="demo-item-title">호버 효과 카드</h3>
                        <?php
                        echo LinearCard::simple('
                            <h4>Hoverable Card</h4>
                            <p>마우스를 올리면 호버 효과가 나타납니다.</p>
                        ')->hoverable();
                        ?>
                        <div class="code-block">LinearCard::simple('콘텐츠')->hoverable()</div>
                    </div>
                    
                    <div>
                        <h3 class="demo-item-title">클릭 가능한 카드</h3>
                        <?php
                        echo LinearCard::interactive('
                            <h4>Interactive Card</h4>
                            <p>클릭할 수 있는 카드입니다.</p>
                        ', 'alert("카드가 클릭되었습니다!")');
                        ?>
                        <div class="code-block">LinearCard::interactive('콘텐츠', 'onClick')</div>
                    </div>
                </div>
                
                <div class="demo-item">
                    <h3 class="demo-item-title">푸터가 있는 카드</h3>
                    <?php
                    $cardWithFooter = LinearCard::simple('
                        <h4>프로젝트 카드</h4>
                        <p>이 카드는 헤더, 콘텐츠, 푸터를 모두 포함합니다.</p>
                    ')
                    ->header('프로젝트 제목')
                    ->footer('<div class="linear-flex linear-justify-between">
                        <span class="linear-text-tertiary">2025-01-13</span>
                        ' . LinearButton::primary('액션', ['style' => 'margin-left: auto;'])->size('sm') . '
                    </div>');
                    
                    echo $cardWithFooter;
                    ?>
                    <div class="code-block">LinearCard::simple('콘텐츠')
    ->header('헤더')
    ->footer('푸터')</div>
                </div>
            </div>
            
        <?php elseif ($activeSection === 'inputs'): ?>
            <!-- 입력 섹션 -->
            <div class="demo-section">
                <h2 class="demo-section-title">Input Components</h2>
                
                <div class="demo-grid demo-grid-2">
                    <div class="demo-item">
                        <h3 class="demo-item-title">기본 텍스트 입력</h3>
                        <?php
                        $textInput = LinearInput::text()
                            ->setId('demo-text')
                            ->name('demo-text')
                            ->label('이름')
                            ->placeholder('이름을 입력하세요')
                            ->fullWidth();
                        echo $textInput->renderGroup();
                        ?>
                        <div class="code-block">LinearInput::text()
    ->label('이름')
    ->placeholder('이름을 입력하세요')
    ->fullWidth()
    ->renderGroup()</div>
                    </div>
                    
                    <div class="demo-item">
                        <h3 class="demo-item-title">필수 입력 필드</h3>
                        <?php
                        $requiredInput = LinearInput::email()
                            ->setId('demo-email')
                            ->name('demo-email')
                            ->label('이메일')
                            ->placeholder('email@example.com')
                            ->required()
                            ->fullWidth();
                        echo $requiredInput->renderGroup();
                        ?>
                        <div class="code-block">LinearInput::email()
    ->label('이메일')
    ->required()
    ->renderGroup()</div>
                    </div>
                    
                    <div class="demo-item">
                        <h3 class="demo-item-title">에러 상태</h3>
                        <?php
                        $errorInput = LinearInput::password()
                            ->setId('demo-password')
                            ->name('demo-password')
                            ->label('비밀번호')
                            ->placeholder('비밀번호 입력')
                            ->error('비밀번호는 8자 이상이어야 합니다.')
                            ->fullWidth();
                        echo $errorInput->renderGroup();
                        ?>
                        <div class="code-block">LinearInput::password()
    ->label('비밀번호')
    ->error('에러 메시지')
    ->renderGroup()</div>
                    </div>
                    
                    <div class="demo-item">
                        <h3 class="demo-item-title">도움말 텍스트</h3>
                        <?php
                        $helpInput = LinearInput::text()
                            ->setId('demo-help')
                            ->name('demo-help')
                            ->label('사용자명')
                            ->placeholder('username')
                            ->help('영문자, 숫자, 언더스코어만 사용 가능합니다.')
                            ->fullWidth();
                        echo $helpInput->renderGroup();
                        ?>
                        <div class="code-block">LinearInput::text()
    ->label('사용자명')
    ->help('도움말 텍스트')
    ->renderGroup()</div>
                    </div>
                    
                    <div class="demo-item">
                        <h3 class="demo-item-title">텍스트 영역</h3>
                        <?php
                        $textareaInput = LinearInput::textarea()
                            ->setId('demo-textarea')
                            ->name('demo-textarea')
                            ->label('메모')
                            ->placeholder('메모를 입력하세요...')
                            ->fullWidth();
                        echo $textareaInput->renderGroup();
                        ?>
                        <div class="code-block">LinearInput::textarea()
    ->label('메모')
    ->placeholder('메모를 입력하세요...')
    ->renderGroup()</div>
                    </div>
                    
                    <div class="demo-item">
                        <h3 class="demo-item-title">입력 크기</h3>
                        <?php
                        echo '<div class="linear-mb-md">';
                        echo LinearInput::text()->placeholder('Small')->size('sm');
                        echo '</div>';
                        
                        echo '<div class="linear-mb-md">';
                        echo LinearInput::text()->placeholder('Medium (기본)')->size('md');
                        echo '</div>';
                        
                        echo '<div class="linear-mb-md">';
                        echo LinearInput::text()->placeholder('Large')->size('lg');
                        echo '</div>';
                        ?>
                        <div class="code-block">LinearInput::text()->size('sm')
LinearInput::text()->size('md')
LinearInput::text()->size('lg')</div>
                    </div>
                </div>
                
                <div class="demo-item">
                    <h3 class="demo-item-title">폼 예제</h3>
                    <form style="max-width: 500px;">
                        <?php
                        echo LinearInput::text()
                            ->setId('form-name')
                            ->name('name')
                            ->label('이름')
                            ->placeholder('홍길동')
                            ->required()
                            ->fullWidth()
                            ->renderGroup();
                        
                        echo '<div style="margin: 1rem 0;"></div>';
                        
                        echo LinearInput::email()
                            ->setId('form-email')
                            ->name('email')
                            ->label('이메일')
                            ->placeholder('hong@example.com')
                            ->required()
                            ->fullWidth()
                            ->renderGroup();
                        
                        echo '<div style="margin: 1rem 0;"></div>';
                        
                        echo LinearInput::textarea()
                            ->setId('form-message')
                            ->name('message')
                            ->label('메시지')
                            ->placeholder('메시지를 입력하세요...')
                            ->help('최대 500자까지 입력 가능합니다.')
                            ->fullWidth()
                            ->renderGroup();
                        
                        echo '<div style="margin: 1.5rem 0;"></div>';
                        
                        echo '<div class="demo-showcase">';
                        echo LinearButton::primary('전송');
                        echo LinearButton::secondary('취소');
                        echo '</div>';
                        ?>
                    </form>
                </div>
            </div>
            
        <?php elseif ($activeSection === 'alerts'): ?>
            <!-- 알림 섹션 -->
            <div class="demo-section">
                <h2 class="demo-section-title">Alert Components</h2>
                
                <div class="demo-section">
                    <h3 class="demo-item-title">알림 타입</h3>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <?php
                        echo LinearAlert::info('정보 메시지입니다. 사용자에게 유용한 정보를 제공합니다.');
                        echo LinearAlert::success('성공 메시지입니다. 작업이 성공적으로 완료되었습니다.');
                        echo LinearAlert::warning('경고 메시지입니다. 주의가 필요한 상황입니다.');
                        echo LinearAlert::error('오류 메시지입니다. 문제가 발생했습니다.');
                        ?>
                    </div>
                    <div class="code-block">LinearAlert::info('정보 메시지')
LinearAlert::success('성공 메시지')
LinearAlert::warning('경고 메시지')
LinearAlert::error('오류 메시지')</div>
                </div>
                
                <div class="demo-section">
                    <h3 class="demo-item-title">제목이 있는 알림</h3>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <?php
                        echo LinearAlert::success('계정이 성공적으로 생성되었습니다.', '가입 완료');
                        echo LinearAlert::warning('이 작업은 되돌릴 수 없습니다.', '주의 필요');
                        echo LinearAlert::error('네트워크 연결을 확인해주세요.', '연결 오류');
                        ?>
                    </div>
                    <div class="code-block">LinearAlert::success('메시지', '제목')
LinearAlert::warning('메시지', '제목')
LinearAlert::error('메시지', '제목')</div>
                </div>
                
                <div class="demo-section">
                    <h3 class="demo-item-title">닫기 가능한 알림</h3>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <?php
                        echo LinearAlert::info('이 알림은 닫을 수 있습니다.', '알림')->dismissible();
                        echo LinearAlert::warning('중요한 업데이트가 있습니다.', '업데이트 알림')->dismissible();
                        ?>
                    </div>
                    <div class="code-block">LinearAlert::info('메시지', '제목')->dismissible()</div>
                </div>
            </div>
            
        <?php elseif ($activeSection === 'design'): ?>
            <!-- 디자인 토큰 섹션 -->
            <div class="demo-section">
                <h2 class="demo-section-title">Design Tokens</h2>
                
                <div class="demo-grid demo-grid-2">
                    <div class="demo-item">
                        <h3 class="demo-item-title">색상 팔레트</h3>
                        <div>
                            <h4>브랜드 색상</h4>
                            <div style="margin-bottom: 1rem;">
                                <span class="color-swatch" style="background-color: var(--linear-brand-primary);"></span>
                                Primary (#7170ff)
                            </div>
                            <div style="margin-bottom: 1rem;">
                                <span class="color-swatch" style="background-color: var(--linear-brand-primary-hover);"></span>
                                Primary Hover (#8989f0)
                            </div>
                            
                            <h4 style="margin-top: 1.5rem;">텍스트 색상</h4>
                            <div style="margin-bottom: 1rem;">
                                <span class="color-swatch" style="background-color: var(--linear-text-primary);"></span>
                                Primary Text
                            </div>
                            <div style="margin-bottom: 1rem;">
                                <span class="color-swatch" style="background-color: var(--linear-text-secondary);"></span>
                                Secondary Text  
                            </div>
                            <div style="margin-bottom: 1rem;">
                                <span class="color-swatch" style="background-color: var(--linear-text-tertiary);"></span>
                                Tertiary Text
                            </div>
                            
                            <h4 style="margin-top: 1.5rem;">배경 색상</h4>
                            <div style="margin-bottom: 1rem;">
                                <span class="color-swatch" style="background-color: var(--linear-bg-primary); border: 2px solid var(--linear-border-primary);"></span>
                                Primary Background
                            </div>
                            <div style="margin-bottom: 1rem;">
                                <span class="color-swatch" style="background-color: var(--linear-bg-secondary);"></span>
                                Secondary Background
                            </div>
                        </div>
                    </div>
                    
                    <div class="demo-item">
                        <h3 class="demo-item-title">타이포그래피</h3>
                        <div class="typography-sample">
                            <h1 style="margin: 0;">Heading 1</h1>
                            <small>4rem, font-weight: 510</small>
                        </div>
                        <div class="typography-sample">
                            <h2 style="margin: 0;">Heading 2</h2>
                            <small>3.5rem, font-weight: 510</small>
                        </div>
                        <div class="typography-sample">
                            <h3 style="margin: 0;">Heading 3</h3>
                            <small>1.3125rem, font-weight: 510</small>
                        </div>
                        <div class="typography-sample">
                            <h4 style="margin: 0;">Heading 4</h4>
                            <small>0.875rem, font-weight: 510</small>
                        </div>
                        <div class="typography-sample">
                            <p style="margin: 0;">Body Text</p>
                            <small>1rem, font-weight: 400</small>
                        </div>
                    </div>
                    
                    <div class="demo-item">
                        <h3 class="demo-item-title">간격 시스템</h3>
                        <div class="spacing-demo">
                            <div class="spacing-box" style="width: 4px;"></div>
                            <span>XS (4px)</span>
                        </div>
                        <div class="spacing-demo">
                            <div class="spacing-box" style="width: 8px;"></div>
                            <span>SM (8px)</span>
                        </div>
                        <div class="spacing-demo">
                            <div class="spacing-box" style="width: 16px;"></div>
                            <span>MD (16px)</span>
                        </div>
                        <div class="spacing-demo">
                            <div class="spacing-box" style="width: 24px;"></div>
                            <span>LG (24px)</span>
                        </div>
                        <div class="spacing-demo">
                            <div class="spacing-box" style="width: 32px;"></div>
                            <span>XL (32px)</span>
                        </div>
                        <div class="spacing-demo">
                            <div class="spacing-box" style="width: 48px;"></div>
                            <span>2XL (48px)</span>
                        </div>
                        <div class="spacing-demo">
                            <div class="spacing-box" style="width: 64px;"></div>
                            <span>3XL (64px)</span>
                        </div>
                    </div>
                    
                    <div class="demo-item">
                        <h3 class="demo-item-title">Border Radius</h3>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                            <div style="width: 60px; height: 60px; background-color: var(--linear-brand-primary); border-radius: var(--linear-radius-sm);"></div>
                            <span>SM (4px)</span>
                            <div style="width: 60px; height: 60px; background-color: var(--linear-brand-primary); border-radius: var(--linear-radius-md);"></div>
                            <span>MD (8px)</span>
                            <div style="width: 60px; height: 60px; background-color: var(--linear-brand-primary); border-radius: var(--linear-radius-lg);"></div>
                            <span>LG (12px)</span>
                            <div style="width: 60px; height: 60px; background-color: var(--linear-brand-primary); border-radius: var(--linear-radius-xl);"></div>
                            <span>XL (16px)</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- JavaScript for mobile navigation and alert dismissal -->
    <script>
        // Mobile navigation toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileToggle = document.querySelector('.linear-nav-mobile-toggle');
            const nav = document.querySelector('.linear-nav');
            
            if (mobileToggle) {
                mobileToggle.addEventListener('click', function() {
                    nav.classList.toggle('linear-nav-mobile-open');
                });
            }
            
            // Alert dismissal
            document.querySelectorAll('.linear-alert-close').forEach(button => {
                button.addEventListener('click', function() {
                    this.closest('.linear-alert').style.display = 'none';
                });
            });
        });
    </script>
</body>
</html>