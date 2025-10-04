# Linear Components 사용 가이드

Linear.app 스타일을 기반으로 한 PHP 재사용 가능 컴포넌트 라이브러리

## 📁 파일 구조

```
components/
├── LinearComponent.php          # 기본 컴포넌트 클래스
├── LinearButton.php             # 버튼 컴포넌트
├── LinearCard.php               # 카드 컴포넌트  
├── LinearInput.php              # 입력 필드 컴포넌트
├── LinearAlert.php              # 알림 컴포넌트
├── LinearNavigation.php         # 네비게이션 컴포넌트
├── linear-theme.json           # 테마 데이터 (JSON)
├── linear-theme.css            # 기본 테마 스타일
├── linear-components.css       # 컴포넌트 전용 스타일
├── index.php                   # 데모 페이지
├── README.md                   # 프로젝트 개요
└── COMPONENT_GUIDE.md          # 이 파일
```

## 🚀 빠른 시작

### 1. 기본 설정

```php
<?php
// 모든 컴포넌트를 사용하려면 다음 파일들을 포함
require_once 'LinearComponent.php';
require_once 'LinearButton.php';
require_once 'LinearCard.php';
require_once 'LinearInput.php';
require_once 'LinearAlert.php';
require_once 'LinearNavigation.php';
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="linear-theme.css">
    <link rel="stylesheet" href="linear-components.css">
</head>
<body>
    <!-- 컴포넌트 사용 -->
</body>
</html>
```

### 2. 간단한 예제

```php
<?php
// 버튼 생성
echo LinearButton::primary('클릭하세요');

// 카드 생성  
echo LinearCard::withHeader('제목', '내용입니다.');

// 입력 필드 생성
echo LinearInput::text()
    ->label('이름')
    ->placeholder('이름을 입력하세요')
    ->renderGroup();
?>
```

## 🎨 컴포넌트별 사용법

### LinearButton

```php
// 기본 사용법
echo LinearButton::primary('Primary Button');
echo LinearButton::secondary('Secondary Button');
echo LinearButton::outline('Outline Button');
echo LinearButton::ghost('Ghost Button');

// 링크 버튼
echo LinearButton::link('Link Button', '/path/to/page');

// 크기 조정
echo LinearButton::primary('Small')->size(LinearButton::SIZE_SMALL);
echo LinearButton::primary('Large')->size(LinearButton::SIZE_LARGE);

// 상태 변경
echo LinearButton::primary('Loading')->loading();
echo LinearButton::primary('Disabled')->disabled();

// 전체 너비
echo LinearButton::primary('Full Width')->fullWidth();

// 클릭 이벤트
echo LinearButton::primary('Click Me')->onClick('alert("Clicked!")');

// 체이닝 방식으로 속성 설정
echo LinearButton::create('Custom Button')
    ->variant(LinearButton::VARIANT_PRIMARY)
    ->size(LinearButton::SIZE_LARGE)
    ->setId('my-button')
    ->addClass('custom-class')
    ->onClick('myFunction()');
```

### LinearCard

```php
// 간단한 카드
echo LinearCard::simple('<h3>제목</h3><p>내용입니다.</p>');

// 헤더가 있는 카드
echo LinearCard::withHeader('카드 제목', '<p>카드 내용입니다.</p>');

// 헤더, 내용, 푸터가 모두 있는 카드
echo LinearCard::simple('메인 콘텐츠')
    ->header('<h3>카드 헤더</h3>')
    ->footer('<div>카드 푸터</div>');

// 호버 효과
echo LinearCard::simple('내용')->hoverable();

// 클릭 가능한 카드
echo LinearCard::interactive('내용', 'location.href="/detail"');

// 패딩 조정
echo LinearCard::simple('내용')->padding('lg');

// 그림자 설정
echo LinearCard::simple('내용')->shadow('high');

// 보더 제거
echo LinearCard::simple('내용')->noBorder();
```

### LinearInput

```php
// 기본 텍스트 입력
echo LinearInput::text()
    ->name('username')
    ->label('사용자명')
    ->placeholder('사용자명을 입력하세요')
    ->renderGroup();

// 이메일 입력
echo LinearInput::email()
    ->name('email')
    ->label('이메일')
    ->placeholder('email@example.com')
    ->required()
    ->renderGroup();

// 비밀번호 입력
echo LinearInput::password()
    ->name('password')
    ->label('비밀번호')
    ->minLength(8)
    ->required()
    ->renderGroup();

// 텍스트 영역
echo LinearInput::textarea()
    ->name('message')
    ->label('메시지')
    ->placeholder('메시지를 입력하세요...')
    ->help('최대 500자까지 입력 가능합니다.')
    ->renderGroup();

// 에러 상태
echo LinearInput::text()
    ->name('field')
    ->label('필드')
    ->error('이 필드는 필수입니다.')
    ->renderGroup();

// 크기 설정
echo LinearInput::text()->size(LinearInput::SIZE_LARGE);

// 비활성화
echo LinearInput::text()->disabled();

// 읽기 전용
echo LinearInput::text()->readonly()->value('읽기 전용 값');

// 속성 체이닝
echo LinearInput::text()
    ->setId('custom-input')
    ->name('custom')
    ->label('커스텀 입력')
    ->placeholder('입력하세요')
    ->required()
    ->maxLength(100)
    ->fullWidth()
    ->renderGroup();
```

### LinearAlert

```php
// 기본 알림 타입
echo LinearAlert::info('정보 메시지입니다.');
echo LinearAlert::success('성공 메시지입니다.');
echo LinearAlert::warning('경고 메시지입니다.');
echo LinearAlert::error('오류 메시지입니다.');

// 제목이 있는 알림
echo LinearAlert::success('작업이 완료되었습니다.', '성공');

// 닫기 가능한 알림
echo LinearAlert::info('이 알림을 닫을 수 있습니다.')->dismissible();

// 커스텀 알림
echo LinearAlert::create('커스텀 메시지', LinearAlert::TYPE_WARNING)
    ->title('커스텀 제목')
    ->dismissible()
    ->setId('my-alert');
```

### LinearNavigation

```php
// 기본 네비게이션
echo LinearNavigation::withBrand('My Site', '/')
    ->addMenuItem('홈', '/', true)  // 세번째 파라미터는 active 상태
    ->addMenuItem('소개', '/about')
    ->addMenuItem('연락처', '/contact');

// 액션 버튼이 있는 네비게이션  
$nav = LinearNavigation::withBrand('My Site', '/')
    ->setMenuItems([
        ['label' => '홈', 'href' => '/', 'active' => true],
        ['label' => '제품', 'href' => '/products'],
        ['label' => '블로그', 'href' => '/blog']
    ])
    ->addAction('<a href="/login" class="linear-btn linear-btn-outline linear-btn-sm">로그인</a>')
    ->addAction('<a href="/signup" class="linear-btn linear-btn-primary linear-btn-sm">회원가입</a>');

// 고정 네비게이션
echo $nav->fixed();

// 투명 배경
echo $nav->transparent();

// 모바일 메뉴 지원
echo $nav->mobileMenu();
```

## 🎯 고급 사용법

### 1. 컴포넌트 상속 및 커스터마이징

```php
class MyCustomButton extends LinearButton {
    protected function renderContent() {
        // 커스텀 렌더링 로직
        return parent::renderContent();
    }
}

// 사용
echo MyCustomButton::primary('Custom Button');
```

### 2. 동적 속성 설정

```php
$button = LinearButton::primary('Dynamic Button');

if ($user->isAdmin()) {
    $button->addClass('admin-button');
}

if ($isLoading) {
    $button->loading();
}

echo $button;
```

### 3. 조건부 렌더링

```php
function renderUserCard($user) {
    $card = LinearCard::withHeader($user->name);
    
    if ($user->isActive()) {
        $card->addClass('active-user');
    }
    
    if ($user->hasPermission('edit')) {
        $card->footer(LinearButton::primary('편집', ['onclick' => "editUser({$user->id})"]));
    }
    
    return $card;
}
```

### 4. 폼 빌더 패턴

```php
class FormBuilder {
    private $fields = [];
    
    public function addField($input) {
        $this->fields[] = $input;
        return $this;
    }
    
    public function render() {
        $html = '<form>';
        foreach ($this->fields as $field) {
            $html .= $field->renderGroup();
        }
        $html .= '</form>';
        return $html;
    }
}

// 사용 예
$form = new FormBuilder();
$form->addField(LinearInput::text()->name('name')->label('이름')->required())
     ->addField(LinearInput::email()->name('email')->label('이메일')->required())
     ->addField(LinearInput::textarea()->name('message')->label('메시지'));

echo $form->render();
```

## 🎨 스타일 커스터마이징

### 1. CSS 변수 오버라이드

```css
:root {
    --linear-brand-primary: #your-color;
    --linear-text-primary: #your-text-color;
    --linear-bg-primary: #your-bg-color;
}
```

### 2. 컴포넌트별 스타일 확장

```css
.linear-btn-custom {
    background: linear-gradient(45deg, #ff6b6b, #feca57);
    border: none;
    color: white;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
}

.linear-btn-custom:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.2);
}
```

```php
echo LinearButton::primary('Custom Style')->addClass('linear-btn-custom');
```

### 3. 다크모드 지원

```css
[data-theme="dark"] {
    --linear-text-primary: #f7f8f8;
    --linear-bg-primary: #101012;
    --linear-border-primary: #3a3a3f;
}
```

```javascript
// 다크모드 토글
function toggleDarkMode() {
    document.documentElement.setAttribute(
        'data-theme', 
        document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark'
    );
}
```

## 📱 반응형 지원

```php
// 모바일에서 전체 너비 버튼
echo LinearButton::primary('Submit')
    ->addClass('linear-btn-responsive')
    ->fullWidth();
```

```css
@media (min-width: 768px) {
    .linear-btn-responsive {
        width: auto;
        display: inline-flex;
    }
}
```

## 🔧 디버깅 및 문제 해결

### 1. 일반적인 문제

```php
// ❌ 잘못된 사용
echo LinearInput::text()->renderGroup()->label('Label'); // 체이닝 순서 오류

// ✅ 올바른 사용  
echo LinearInput::text()->label('Label')->renderGroup();
```

### 2. 개발자 도구 활용

```php
// 디버그 정보 출력
$button = LinearButton::primary('Debug Button')
    ->setId('debug-btn')
    ->addClass('debug-class');

// HTML 출력 전 확인
echo "<!-- Debug: " . htmlspecialchars($button->render()) . " -->";
echo $button;
```

## 🌐 접근성 고려사항

```php
// ARIA 라벨 추가
echo LinearButton::primary('Submit')
    ->addAttribute('aria-label', '폼 제출')
    ->addAttribute('aria-describedby', 'submit-help');

// 키보드 네비게이션
echo LinearCard::interactive('Content')
    ->addAttribute('tabindex', '0')
    ->addAttribute('role', 'button')
    ->addAttribute('aria-pressed', 'false');

// 스크린 리더 지원
echo LinearInput::text()
    ->label('Password')
    ->addAttribute('aria-describedby', 'pwd-help')
    ->help('Must be at least 8 characters')
    ->renderGroup();
```

## 🚀 성능 최적화

### 1. 지연 로딩

```php
// 조건부 컴포넌트 로딩
if ($needsAdvancedFeatures) {
    require_once 'AdvancedLinearComponents.php';
}
```

### 2. 캐싱

```php
class ComponentCache {
    private static $cache = [];
    
    public static function get($key, $generator) {
        if (!isset(self::$cache[$key])) {
            self::$cache[$key] = $generator();
        }
        return self::$cache[$key];
    }
}

// 사용
$complexCard = ComponentCache::get('user-card-' . $user->id, function() use ($user) {
    return LinearCard::withHeader($user->name, $user->bio);
});
```

## 📈 마이그레이션 가이드

### 기존 HTML에서 Linear Components로

```php
// Before (HTML)
// <button class="btn btn-primary">Click me</button>

// After (Linear Components)
echo LinearButton::primary('Click me');

// Before (HTML Form)
// <div class="form-group">
//   <label for="email">Email</label>  
//   <input type="email" id="email" class="form-control">
// </div>

// After (Linear Components)
echo LinearInput::email()
    ->setId('email')
    ->label('Email')
    ->renderGroup();
```

이 가이드를 참고하여 Linear Components를 효과적으로 활용하세요! 추가 질문이나 특별한 요구사항이 있으면 언제든지 문의해주세요.