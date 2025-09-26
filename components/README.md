# Linear Design System Theme

Linear.app에서 추출한 디자인 시스템 테마입니다. 모던한 웹 애플리케이션에서 Linear의 세련된 디자인을 적용할 수 있습니다.

## 파일 구조

```
components/
├── linear-theme.json    # 완전한 테마 데이터 (JSON 형태)
├── linear-theme.css     # CSS 변수 및 컴포넌트 스타일
└── README.md           # 사용법 가이드
```

## 주요 특징

- **색상 시스템**: 브랜드 색상, 시맨틱 색상, 텍스트/배경/보더 색상 체계
- **타이포그래피**: Inter Variable 폰트 패밀리 기반의 완전한 텍스트 스타일
- **간격 시스템**: 일관된 spacing scale (4px 기반)
- **컴포넌트**: 버튼, 카드, 입력 필드, 네비게이션 등 기본 컴포넌트
- **다크모드 지원**: prefers-color-scheme 기반 자동 다크모드
- **반응형**: 모바일 친화적인 breakpoint 시스템

## 사용법

### 1. CSS 직접 사용

```html
<!DOCTYPE html>
<html lang="ko">
<head>
    <link rel="stylesheet" href="components/linear-theme.css">
</head>
<body>
    <nav class="linear-nav">
        <div class="linear-container">
            <!-- 네비게이션 내용 -->
        </div>
    </nav>
    
    <main class="linear-container">
        <div class="linear-card">
            <h1>Linear 스타일 제목</h1>
            <p class="linear-text-secondary">설명 텍스트입니다.</p>
            <button class="linear-btn linear-btn-primary">시작하기</button>
        </div>
    </main>
</body>
</html>
```

### 2. CSS 변수만 사용

```css
/* 기존 프로젝트에 Linear 색상과 스타일 적용 */
.my-button {
    background-color: var(--linear-brand-primary);
    color: var(--linear-color-white);
    border-radius: var(--linear-radius-md);
    padding: var(--linear-spacing-sm) var(--linear-spacing-md);
    font-family: var(--linear-font-regular);
    font-weight: var(--linear-font-weight-medium);
    transition: all var(--linear-transition-fast) var(--linear-ease-out);
}

.my-button:hover {
    background-color: var(--linear-brand-primary-hover);
}
```

### 3. JSON 데이터 활용 (JavaScript/React 등)

```javascript
// linear-theme.json을 import하여 사용
import linearTheme from './components/linear-theme.json';

// React에서 사용 예시
const MyComponent = () => {
    const buttonStyle = {
        backgroundColor: linearTheme.colors.brand.primary,
        color: linearTheme.colors.semantic.white,
        borderRadius: linearTheme.borderRadius.md,
        padding: `${linearTheme.spacing.system.sm} ${linearTheme.spacing.system.md}`,
        fontFamily: linearTheme.typography.fontFamilies.regular,
        fontWeight: linearTheme.typography.fontWeights.medium,
        transition: `all ${linearTheme.transitions.duration.fast} ${linearTheme.transitions.easing.easeOut}`
    };
    
    return <button style={buttonStyle}>Linear 버튼</button>;
};
```

### 4. Tailwind CSS 통합

```javascript
// tailwind.config.js
const linearTheme = require('./components/linear-theme.json');

module.exports = {
    theme: {
        extend: {
            colors: {
                'linear-brand': linearTheme.colors.brand.primary,
                'linear-text': linearTheme.colors.text.primary,
                'linear-bg': linearTheme.colors.background.primary,
            },
            fontFamily: {
                'linear': linearTheme.typography.fontFamilies.regular.split(',').map(f => f.trim().replace(/"/g, '')),
            },
            spacing: linearTheme.spacing.system,
            borderRadius: linearTheme.borderRadius,
            boxShadow: linearTheme.shadows,
        }
    }
};
```

## 컴포넌트 클래스

### 버튼
```html
<button class="linear-btn linear-btn-primary">Primary Button</button>
<button class="linear-btn linear-btn-secondary">Secondary Button</button>
```

### 카드
```html
<div class="linear-card">
    <h3>카드 제목</h3>
    <p>카드 내용</p>
</div>
```

### 입력 필드
```html
<input type="text" class="linear-input" placeholder="입력하세요">
```

### 네비게이션
```html
<nav class="linear-nav">
    <div class="linear-container">
        <!-- 네비게이션 내용 -->
    </div>
</nav>
```

## 색상 팔레트

### 브랜드 색상
- **Primary**: `#7170ff` - Linear의 메인 브랜드 색상
- **Primary Hover**: `#8989f0` - 호버 상태
- **Primary Tint**: `#f1f1ff` - 연한 브랜드 색상

### 텍스트 색상
- **Primary**: `#282a30` - 주요 텍스트
- **Secondary**: `#3c4149` - 보조 텍스트  
- **Tertiary**: `#6f6e77` - 비활성 텍스트
- **Quaternary**: `#86848d` - 미미한 텍스트

### 배경 색상
- **Primary**: `#fff` - 주요 배경
- **Secondary**: `#f9f8f9` - 보조 배경
- **Tertiary**: `#f4f2f4` - 3차 배경

## 타이포그래피

- **Font Family**: Inter Variable, SF Pro Display, system fonts
- **Font Weights**: 300 (light), 400 (normal), 510 (medium), 590 (semibold), 680 (bold)
- **Font Sizes**: 0.625rem ~ 4rem까지 단계별 크기

## 간격 시스템

4px을 기준으로 한 일관된 spacing scale:
- **xs**: 4px
- **sm**: 8px  
- **md**: 16px
- **lg**: 24px
- **xl**: 32px
- **2xl**: 48px
- **3xl**: 64px

## 브레이크포인트

- **sm**: 640px
- **md**: 768px
- **lg**: 1024px (Linear 기본 컨테이너 width)
- **xl**: 1280px
- **2xl**: 1536px

## 다크모드

다크모드는 `prefers-color-scheme: dark` 미디어 쿼리를 통해 자동으로 적용됩니다. 수동으로 제어하려면 해당 CSS 규칙을 클래스 기반으로 수정하면 됩니다.

## 접근성

- **최소 터치 타겟**: 44px
- **포커스 링**: 2px solid #5e6ad2, offset 2px
- **색상 대비**: AA 기준 4.5:1, AAA 기준 7:1 권장

## 라이선스

이 테마는 Linear.app의 공개된 디자인을 분석하여 생성되었습니다. 상업적 사용 시 Linear의 브랜드 가이드라인을 확인하시기 바랍니다.

## 업데이트

- **v1.0.0** (2025-01-13): 초기 버전 - Linear.app에서 추출한 완전한 디자인 시스템