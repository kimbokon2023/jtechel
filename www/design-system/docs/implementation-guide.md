# J-TECH 디자인 시스템 구현 가이드

기존 PHP 애플리케이션에 디자인 시스템을 통합하는 단계별 가이드입니다.

## 🚀 통합 전략

### Phase 1: 준비 단계 (1-2일)

**1.1 CSS 파일 추가**
```php
<!-- load_header.php 파일에 추가 -->
<link rel="stylesheet" href="<?$root_dir?>/design-system/components.css">
```

**1.2 디자인 토큰 검증**
```html
<!-- 테스트 페이지 생성하여 기본 스타일 확인 -->
<div style="background: var(--color-primary); padding: 1rem;">
    <p style="color: var(--color-text-inverse);">디자인 토큰 테스트</p>
</div>
```

### Phase 2: 핵심 컴포넌트 적용 (3-5일)

**2.1 버튼 스타일 통일**
```php
<!-- 기존 -->
<input type="submit" value="저장" class="btn btn-primary">

<!-- 새로운 방식 -->
<button type="submit" class="jt-btn jt-btn--primary">저장</button>
```

**2.2 폼 요소 업그레이드**
```php
<!-- 기존 -->
<input type="text" name="customer_name" placeholder="고객명">

<!-- 새로운 방식 -->
<div class="jt-input">
    <label class="jt-input__label jt-input__label--required">고객명</label>
    <input type="text" name="customer_name" class="jt-input__field" placeholder="고객명을 입력하세요">
</div>
```

**2.3 테이블 스타일 개선**
```php
<!-- 기존 -->
<table border="1">
    <tr>
        <th>고객명</th>
        <th>연락처</th>
    </tr>
</table>

<!-- 새로운 방식 -->
<table class="jt-table">
    <thead class="jt-table__header">
        <tr>
            <th class="jt-table__header-cell">고객명</th>
            <th class="jt-table__header-cell">연락처</th>
        </tr>
    </thead>
</table>
```

### Phase 3: 레이아웃 시스템 적용 (2-3일)

**3.1 그리드 레이아웃**
```php
<!-- 기존 테이블 기반 레이아웃 -->
<table width="100%">
    <tr>
        <td width="50%">왼쪽 콘텐츠</td>
        <td width="50%">오른쪽 콘텐츠</td>
    </tr>
</table>

<!-- 새로운 그리드 레이아웃 -->
<div class="jt-grid jt-grid--2">
    <div>왼쪽 콘텐츠</div>
    <div>오른쪽 콘텐츠</div>
</div>
```

**3.2 카드 컴포넌트로 섹션 구분**
```php
<div class="jt-card">
    <div class="jt-card__header">
        <h3 class="jt-card__title">고객 정보</h3>
    </div>
    <div class="jt-card__body">
        <!-- 폼 콘텐츠 -->
    </div>
    <div class="jt-card__footer">
        <button class="jt-btn jt-btn--primary">저장</button>
    </div>
</div>
```

### Phase 4: 고급 기능 통합 (3-4일)

**4.1 모달 다이얼로그**
```php
<!-- SweetAlert2를 디자인 시스템과 통합 -->
<script>
Swal.fire({
    title: '저장 완료',
    text: '고객 정보가 성공적으로 저장되었습니다.',
    icon: 'success',
    customClass: {
        popup: 'jt-modal__content',
        title: 'jt-modal__title',
        confirmButton: 'jt-btn jt-btn--primary'
    }
});
</script>
```

**4.2 상태 표시 및 알림**
```php
<?php if ($success): ?>
<div class="jt-alert jt-alert--success">
    <div class="jt-alert__icon">✓</div>
    <div class="jt-alert__content">
        <div class="jt-alert__title">성공</div>
        <div class="jt-alert__description">작업이 완료되었습니다.</div>
    </div>
</div>
<?php endif; ?>
```

## 🔧 실용적 구현 예제

### 고객 입력 폼 변환

**기존 코드 (customer_input.php)**
```php
<form method="post" action="customer_save.php">
    <table>
        <tr>
            <td>고객명:</td>
            <td><input type="text" name="name" required></td>
        </tr>
        <tr>
            <td>연락처:</td>
            <td><input type="tel" name="phone" required></td>
        </tr>
        <tr>
            <td colspan="2">
                <input type="submit" value="저장">
                <input type="button" value="취소" onclick="history.back()">
            </td>
        </tr>
    </table>
</form>
```

**개선된 코드**
```php
<form method="post" action="customer_save.php">
    <div class="jt-card">
        <div class="jt-card__header">
            <h3 class="jt-card__title">고객 정보 입력</h3>
            <p class="jt-card__subtitle">새로운 고객을 등록합니다</p>
        </div>
        <div class="jt-card__body">
            <div class="jt-grid jt-grid--2">
                <div class="jt-input">
                    <label class="jt-input__label jt-input__label--required">고객명</label>
                    <input type="text" name="name" class="jt-input__field" placeholder="고객명을 입력하세요" required>
                </div>
                <div class="jt-input">
                    <label class="jt-input__label jt-input__label--required">연락처</label>
                    <input type="tel" name="phone" class="jt-input__field" placeholder="010-0000-0000" required>
                </div>
            </div>
        </div>
        <div class="jt-card__footer">
            <div class="jt-flex jt-flex--between">
                <button type="button" class="jt-btn jt-btn--ghost" onclick="history.back()">취소</button>
                <button type="submit" class="jt-btn jt-btn--primary">저장</button>
            </div>
        </div>
    </div>
</form>
```

### 목록 페이지 변환

**기존 코드**
```php
<table border="1" style="width:100%">
    <tr style="background:#f0f0f0">
        <th>번호</th>
        <th>고객명</th>
        <th>연락처</th>
        <th>등록일</th>
        <th>작업</th>
    </tr>
    <?php while($row = $stmt->fetch()): ?>
    <tr>
        <td><?= $row['id'] ?></td>
        <td><?= $row['name'] ?></td>
        <td><?= $row['phone'] ?></td>
        <td><?= $row['created_at'] ?></td>
        <td>
            <a href="edit.php?id=<?= $row['id'] ?>">수정</a>
            <a href="delete.php?id=<?= $row['id'] ?>" onclick="return confirm('삭제하시겠습니까?')">삭제</a>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
```

**개선된 코드**
```php
<div class="jt-card">
    <div class="jt-card__header">
        <div class="jt-flex jt-flex--between">
            <div>
                <h3 class="jt-card__title">고객 목록</h3>
                <p class="jt-card__subtitle">등록된 고객 정보</p>
            </div>
            <button class="jt-btn jt-btn--primary" onclick="location.href='customer_input.php'">
                <span>➕</span>
                <span>새 고객 등록</span>
            </button>
        </div>
    </div>
    <div class="jt-card__body" style="padding: 0;">
        <table class="jt-table">
            <thead class="jt-table__header">
                <tr>
                    <th class="jt-table__header-cell">번호</th>
                    <th class="jt-table__header-cell">고객명</th>
                    <th class="jt-table__header-cell">연락처</th>
                    <th class="jt-table__header-cell">등록일</th>
                    <th class="jt-table__header-cell">작업</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $stmt->fetch()): ?>
                <tr class="jt-table__row">
                    <td class="jt-table__cell"><?= $row['id'] ?></td>
                    <td class="jt-table__cell">
                        <div class="font-medium"><?= htmlspecialchars($row['name']) ?></div>
                    </td>
                    <td class="jt-table__cell"><?= htmlspecialchars($row['phone']) ?></td>
                    <td class="jt-table__cell"><?= date('Y-m-d', strtotime($row['created_at'])) ?></td>
                    <td class="jt-table__cell">
                        <div class="jt-flex" style="gap: 0.5rem;">
                            <a href="edit.php?id=<?= $row['id'] ?>" class="jt-btn jt-btn--ghost jt-btn--sm">수정</a>
                            <button class="jt-btn jt-btn--ghost jt-btn--sm" onclick="confirmDelete(<?= $row['id'] ?>)">삭제</button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function confirmDelete(id) {
    Swal.fire({
        title: '정말 삭제하시겠습니까?',
        text: '삭제된 데이터는 복구할 수 없습니다.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '삭제',
        cancelButtonText: '취소',
        customClass: {
            confirmButton: 'jt-btn jt-btn--error',
            cancelButton: 'jt-btn jt-btn--ghost'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            location.href = `delete.php?id=${id}`;
        }
    });
}
</script>
```

## 📱 모바일 최적화

### 반응형 테이블
```php
<!-- 모바일에서 카드 형태로 변환 -->
<div class="hidden lg:block">
    <!-- 데스크톱용 테이블 -->
    <table class="jt-table">...</table>
</div>

<div class="block lg:hidden">
    <!-- 모바일용 카드 목록 -->
    <?php while($row = $stmt->fetch()): ?>
    <div class="jt-card mb-4">
        <div class="jt-card__body">
            <div class="font-medium"><?= $row['name'] ?></div>
            <div class="text-muted"><?= $row['phone'] ?></div>
            <div class="mt-2">
                <span class="jt-badge jt-badge--secondary"><?= $row['status'] ?></span>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
</div>
```

### 모바일 네비게이션
```php
<!-- 모바일 메뉴 -->
<div class="block md:hidden">
    <button class="jt-btn jt-btn--ghost" onclick="toggleMobileMenu()">
        <span>☰</span>
        <span>메뉴</span>
    </button>
</div>

<nav id="mobileMenu" class="hidden md:block">
    <div class="jt-flex jt-flex--col md:jt-flex--row">
        <a href="dashboard.php" class="jt-btn jt-btn--ghost">대시보드</a>
        <a href="customers.php" class="jt-btn jt-btn--ghost">고객관리</a>
        <a href="orders.php" class="jt-btn jt-btn--ghost">작업주문</a>
    </div>
</nav>
```

## 🎨 기존 스타일과의 호환성

### CSS 우선순위 관리
```css
/* 기존 스타일을 덮어쓰지 않는 방법 */
.jt-card {
    /* 디자인 시스템 스타일 */
}

/* 기존 .card 클래스는 그대로 유지 */
.card {
    /* 기존 스타일 유지 */
}
```

### 점진적 마이그레이션
```php
<!-- 기존 페이지에서 점진적으로 적용 -->
<div class="component">
    <!-- 기존 스타일 유지 -->
</div>

<div class="jt-component">
    <!-- 새로운 디자인 시스템 적용 -->
</div>
```

## 🔍 디버깅 및 트러블슈팅

### 스타일 충돌 해결
```css
/* 특정도를 높여 기존 스타일 덮어쓰기 */
.jt-btn.jt-btn--primary {
    background-color: var(--color-primary) !important;
}
```

### 브라우저 호환성
```php
<!-- IE11 지원이 필요한 경우 -->
<!--[if IE]>
<link rel="stylesheet" href="<?$root_dir?>/design-system/ie-compatibility.css">
<![endif]-->
```

### 개발자 도구 활용
```html
<!-- 개발 환경에서만 표시되는 디버그 정보 -->
<?php if (defined('DEBUG') && DEBUG): ?>
<div style="position: fixed; bottom: 10px; right: 10px; background: yellow; padding: 5px; font-size: 11px;">
    디자인 시스템 v1.0
</div>
<?php endif; ?>
```

## 📊 성능 최적화

### CSS 최적화
```php
<!-- 프로덕션에서는 최적화된 CSS 사용 -->
<?php if (defined('PRODUCTION') && PRODUCTION): ?>
<link rel="stylesheet" href="<?$root_dir?>/design-system/components.min.css">
<?php else: ?>
<link rel="stylesheet" href="<?$root_dir?>/design-system/components.css">
<?php endif; ?>
```

### 조건부 로딩
```php
<!-- 특정 페이지에서만 필요한 스타일 -->
<?php if (in_array($current_page, ['dashboard', 'analytics'])): ?>
<link rel="stylesheet" href="<?$root_dir?>/design-system/charts.css">
<?php endif; ?>
```

## 🧪 테스트 가이드

### 시각적 테스트
```html
<!-- 컴포넌트 테스트 페이지 -->
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="../components.css">
</head>
<body>
    <h2>버튼 테스트</h2>
    <button class="jt-btn jt-btn--primary">주 버튼</button>
    <button class="jt-btn jt-btn--secondary">보조 버튼</button>
    
    <h2>폼 요소 테스트</h2>
    <div class="jt-input">
        <label class="jt-input__label">테스트 입력</label>
        <input type="text" class="jt-input__field" placeholder="테스트">
    </div>
</body>
</html>
```

### 접근성 테스트
```javascript
// 키보드 네비게이션 테스트
document.addEventListener('keydown', function(e) {
    if (e.key === 'Tab') {
        console.log('포커스:', document.activeElement);
    }
});
```

## 📈 점진적 도입 일정

### Week 1-2: 기초 설정
- [ ] CSS 파일 통합
- [ ] 기본 컴포넌트 테스트
- [ ] 중요 페이지 1-2개 적용

### Week 3-4: 핵심 기능
- [ ] 폼 요소 전면 적용
- [ ] 테이블 스타일 개선
- [ ] 버튼 스타일 통일

### Week 5-6: 고급 기능
- [ ] 모달, 알림 통합
- [ ] 반응형 레이아웃 적용
- [ ] 모바일 최적화

### Week 7-8: 완성 및 최적화
- [ ] 전체 시스템 통합
- [ ] 성능 최적화
- [ ] 사용자 피드백 반영

이 가이드를 따라 단계적으로 구현하면 기존 시스템을 중단 없이 현대적이고 사용자 친화적인 인터페이스로 전환할 수 있습니다.