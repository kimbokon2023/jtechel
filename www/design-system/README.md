# J-TECH 사용자 중심 디자인 시스템

엘리베이터 업무 관리 시스템을 위한 사용자 중심 디자인 시스템입니다.

## 🎯 목표

- **사용자 경험 향상**: 직관적이고 효율적인 인터페이스 제공
- **일관성 확보**: 모든 화면에서 통일된 디자인 언어 사용  
- **접근성 보장**: WCAG 2.1 AA 수준의 접근성 준수
- **개발 효율성**: 재사용 가능한 컴포넌트로 개발 시간 단축
- **유지보수성**: 체계적인 디자인 토큰으로 쉬운 관리

## 📁 파일 구조

```
design-system/
├── tokens.css          # 디자인 토큰 (색상, 타이포그래피, 간격 등)
├── base.css            # 기본 스타일과 CSS 리셋
├── components.css      # UI 컴포넌트 라이브러리
├── README.md           # 이 문서
├── examples/           # 사용 예제
└── docs/               # 상세 문서
```

## 🚀 시작하기

### 1. CSS 파일 추가

기존 `load_header.php` 파일에 디자인 시스템 CSS를 추가합니다:

```php
<!-- J-TECH 디자인 시스템 -->
<link rel="stylesheet" href="<?$root_dir?>/design-system/components.css">
```

> **참고**: `components.css`가 `base.css`와 `tokens.css`를 자동으로 불러옵니다.

### 2. HTML 클래스 적용

기존 HTML 요소에 디자인 시스템 클래스를 적용합니다:

```html
<!-- 기존 버튼 -->
<button>저장</button>

<!-- 디자인 시스템 적용 -->
<button class="jt-btn jt-btn--primary">저장</button>
```

## 🎨 디자인 토큰

### 색상 시스템

- **주요 색상**: `--color-primary` (제이테크 블루)
- **보조 색상**: `--color-secondary` (슬레이트 그레이)  
- **시멘틱 색상**: `--color-success`, `--color-warning`, `--color-error`
- **중성 색상**: `--color-gray-50` ~ `--color-gray-900`

### 타이포그래피

- **폰트**: 맑은 고딕 기반 한국어 최적화 폰트 스택
- **크기**: `--font-size-xs` (12px) ~ `--font-size-5xl` (48px)
- **굵기**: `--font-weight-light` ~ `--font-weight-extrabold`

### 간격 시스템

- **기본 단위**: 4px (`--space-1`)
- **범위**: `--space-0` (0px) ~ `--space-32` (128px)
- **일관된 8px 그리드 시스템** 

## 🧩 주요 컴포넌트

### 버튼

```html
<!-- 기본 버튼 -->
<button class="jt-btn jt-btn--primary">주 액션</button>
<button class="jt-btn jt-btn--secondary">부 액션</button>
<button class="jt-btn jt-btn--outline">외곽선</button>

<!-- 크기 변형 -->
<button class="jt-btn jt-btn--primary jt-btn--sm">작은 버튼</button>
<button class="jt-btn jt-btn--primary jt-btn--lg">큰 버튼</button>
```

### 입력 필드

```html
<div class="jt-input">
  <label class="jt-input__label jt-input__label--required">고객명</label>
  <input type="text" class="jt-input__field" placeholder="고객명을 입력하세요">
  <span class="jt-input__help">필수 입력 항목입니다</span>
</div>
```

### 카드

```html
<div class="jt-card">
  <div class="jt-card__header">
    <h3 class="jt-card__title">작업 주문</h3>
    <p class="jt-card__subtitle">2024년 1월 15일</p>
  </div>
  <div class="jt-card__body">
    <p>작업 내용...</p>
  </div>
  <div class="jt-card__footer">
    <button class="jt-btn jt-btn--primary">확인</button>
  </div>
</div>
```

### 테이블

```html
<table class="jt-table">
  <thead class="jt-table__header">
    <tr>
      <th class="jt-table__header-cell">고객명</th>
      <th class="jt-table__header-cell">연락처</th>
      <th class="jt-table__header-cell">작업상태</th>
    </tr>
  </thead>
  <tbody>
    <tr class="jt-table__row">
      <td class="jt-table__cell">김철수</td>
      <td class="jt-table__cell">010-1234-5678</td>
      <td class="jt-table__cell">
        <span class="jt-badge jt-badge--success">완료</span>
      </td>
    </tr>
  </tbody>
</table>
```

### 알림

```html
<div class="jt-alert jt-alert--success">
  <div class="jt-alert__icon">✓</div>
  <div class="jt-alert__content">
    <div class="jt-alert__title">저장 완료</div>
    <div class="jt-alert__description">데이터가 성공적으로 저장되었습니다.</div>
  </div>
</div>
```

## 🌐 접근성 기능

### 키보드 네비게이션
- 모든 대화형 요소에 포커스 표시
- 탭 순서 최적화
- 키보드로 완전한 조작 가능

### 스크린 리더 지원
- 적절한 ARIA 레이블
- 구조적 마크업
- 의미 있는 대체 텍스트

### 시각적 접근성
- 충분한 색상 대비 (4.5:1 이상)
- 고대비 모드 지원
- 움직임 줄이기 옵션 지원

## 📱 반응형 디자인

### 중단점
- **모바일**: 0~639px
- **태블릿**: 640~767px  
- **데스크톱**: 768px 이상

### 반응형 클래스
```html
<!-- 모바일에서는 숨김, 데스크톱에서 표시 -->
<div class="hidden md:block">데스크톱 전용 콘텐츠</div>

<!-- 모바일에서는 세로 배치, 데스크톱에서 가로 배치 -->
<div class="flex flex-col md:flex-row">
  <!-- 콘텐츠 -->
</div>
```

## 🌙 다크 모드

시스템의 다크 모드 설정을 자동으로 감지하여 적용됩니다:

```css
/* 사용자 시스템 설정에 따라 자동 전환 */
@media (prefers-color-scheme: dark) {
  /* 다크 모드 색상 적용 */
}
```

## 🛠️ 개발 가이드라인

### CSS 클래스 명명 규칙

- **BEM 방법론** 사용: `.jt-block__element--modifier`
- **네임스페이스**: 모든 클래스는 `jt-` 접두사 사용
- **의미 있는 이름**: 용도와 역할을 명확히 표현

### 커스터마이징

디자인 토큰을 수정하여 시스템을 커스터마이징할 수 있습니다:

```css
:root {
  /* 브랜드 색상 변경 */
  --color-primary: #your-brand-color;
  
  /* 폰트 변경 */
  --font-family-primary: "Your Font", sans-serif;
}
```

## 🔄 기존 시스템과의 통합

### 점진적 적용

기존 CSS와 충돌 없이 점진적으로 적용 가능합니다:

1. **1단계**: 새로운 페이지에 우선 적용
2. **2단계**: 주요 페이지 순차적 적용  
3. **3단계**: 전체 시스템 통합

### 기존 스타일과의 호환성

```css
/* 기존 스타일을 덮어쓰지 않는 네임스페이스 사용 */
.jt-btn { /* 새로운 버튼 스타일 */ }

/* 기존 버튼 스타일은 그대로 유지 */
.ui-button { /* 기존 스타일 유지 */ }
```

## 📚 추가 자료

### 사용 예제
- `examples/` 폴더에서 다양한 사용 예제 확인
- 실제 업무 시나리오 기반 예제 제공

### 상세 문서
- `docs/` 폴더에서 각 컴포넌트별 상세 문서
- 접근성 가이드라인 및 모범 사례

## ❓ 자주 묻는 질문

### Q: 기존 Bootstrap과 함께 사용할 수 있나요?
A: 네, 네임스페이스를 사용하므로 충돌 없이 함께 사용 가능합니다.

### Q: 모바일에서도 잘 동작하나요?
A: 모바일 우선 설계로 모든 기기에서 최적화된 경험을 제공합니다.

### Q: 접근성은 어떻게 보장되나요?
A: WCAG 2.1 AA 수준을 준수하며, 키보드 네비게이션과 스크린 리더를 완전히 지원합니다.

### Q: 커스터마이징이 어렵지 않나요?
A: CSS 커스텀 속성(변수)을 사용하여 쉽게 테마를 변경할 수 있습니다.

## 🤝 기여하기

디자인 시스템 개선에 기여하고 싶으시다면:

1. 새로운 컴포넌트 요청
2. 접근성 개선 제안
3. 버그 신고 및 수정
4. 사용 사례 공유

## 📄 라이선스

이 디자인 시스템은 J-TECH 내부 사용을 위해 제작되었습니다.