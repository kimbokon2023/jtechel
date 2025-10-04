# 모바일 모달창 뒤로가기 개선 가이드

## 📱 개선 내용

모바일 환경에서 모달창을 띄운 후 뒤로가기 버튼을 누르면 모달창만 닫히고 페이지는 이탈하지 않도록 개선했습니다.

## 🛠️ 기술적 구현

### 1. 통합 모바일 모달 핸들러 (`assets/js/mobile-modal-handler.js`)

**주요 기능:**
- 모바일 환경 자동 감지
- 히스토리 스택 관리
- 모달창별 뒤로가기 처리
- SweetAlert2, 커스텀 모달, CSS 모달 지원

**동작 원리:**
1. 모바일 환경 감지 (User Agent + Touch + 화면 크기)
2. 모달창 열릴 때 히스토리 스택에 등록
3. 뒤로가기 버튼 감지 시 최상위 모달창만 닫기
4. 모든 모달창이 닫히면 정상적인 뒤로가기 허용

### 2. 적용된 파일 목록

#### ✅ `panel_measurement.php`
- **커스텀 패널 모달**: 패널 정보 입력 모달
- **SweetAlert2 모달**: 패널 삭제 확인, 복사 확인/성공 알림

#### ✅ `site_list.php`
- **CSS 모달**: 현장 정보 편집 모달

#### ✅ `list.php`
- **SweetAlert2 모달**: 측정 데이터 삭제, 엑셀 내보내기 확인

#### ✅ `result.php`
- **SweetAlert2 모달**: 설정 적용, 엑셀 내보내기, 링크 복사

## 🔧 사용법

### 자동 적용
모든 모달창에 자동으로 적용되므로 별도 설정 불필요

### 수동 적용 (새로운 모달창 추가 시)

#### 1. SweetAlert2 모달
```javascript
Swal.fire({
    title: '제목',
    text: '내용',
    icon: 'info',
    didOpen: () => {
        // 모바일 핸들러 등록
        if (window.mobileModalHandler) {
            window.mobileModalHandler.registerModalOpen({
                id: 'unique_modal_id',
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
        // 모달 닫힘 등록
        if (window.mobileModalHandler) {
            window.mobileModalHandler.registerModalClose('unique_modal_id');
        }
    }
});
```

#### 2. 커스텀 모달
```javascript
function openCustomModal() {
    modal.style.display = 'flex';

    // 모바일 핸들러 등록
    if (window.mobileModalHandler) {
        window.mobileModalHandler.registerModalOpen({
            id: 'custom_modal_id',
            type: 'custom',
            element: modal,
            closeCallback: () => {
                modal.style.display = 'none';
            }
        });
    }
}

function closeCustomModal() {
    // 모달 닫힘 등록
    if (window.mobileModalHandler) {
        window.mobileModalHandler.registerModalClose('custom_modal_id');
    }

    modal.style.display = 'none';
}
```

#### 3. CSS 모달
```javascript
function openCssModal() {
    modal.classList.add('show');

    // 모바일 핸들러 등록
    if (window.mobileModalHandler) {
        window.mobileModalHandler.registerModalOpen({
            id: 'css_modal_id',
            type: 'css',
            element: modal,
            closeCallback: () => {
                modal.classList.remove('show');
            }
        });
    }
}
```

## 📱 지원 환경

### 모바일 환경 감지 조건
- **User Agent**: Android, iOS, 기타 모바일 기기
- **터치 지원**: `ontouchstart` 이벤트 지원
- **화면 크기**: 768px 이하

### 지원 브라우저
- **모바일**: Safari (iOS), Chrome (Android), Samsung Internet, 기타 모바일 브라우저
- **데스크톱**: 자동 비활성화 (기존 동작 유지)

## 🐛 디버깅

### 콘솔 로그 확인
```javascript
// 모바일 환경 감지 확인
// MobileModalHandler: 모바일 환경 감지됨, 핸들러 활성화

// 모달 등록/해제 확인
// Modal opened: modalId, Stack size: stackSize
// Modal closed: modalId, Stack size: stackSize

// 뒤로가기 처리 확인
// Closing top modal: modalId, Type: modalType
```

### 디버그 정보 조회
```javascript
// 현재 상태 확인
// window.mobileModalHandler.getDebugInfo();
```

## ⚠️ 주의사항

1. **모달 ID 중복 방지**: 각 모달마다 고유한 ID 사용
2. **정확한 타입 지정**: 'swal', 'custom', 'css' 중 정확한 타입 사용
3. **닫기 콜백**: 모달을 올바르게 닫는 함수 제공
4. **스크립트 로드 순서**: `mobile-modal-handler.js`를 먼저 로드

## 🔄 호환성

### 기존 코드와의 호환성
- 데스크톱 환경에서는 기존 동작 유지
- 모바일에서만 새로운 뒤로가기 처리 적용
- 기존 모달 닫기 로직에 영향 없음

### 업데이트 필요 시
새로운 모달창 추가 시에만 위의 사용법에 따라 핸들러 등록 코드 추가

## 📊 성능 영향

- **메모리 사용량**: 최소 (모달 스택 정보만 저장)
- **CPU 사용량**: 무시할 수준 (이벤트 리스너 등록/해제)
- **네트워크**: 영향 없음 (클라이언트 사이드만 처리)

---

**최종 업데이트**: 2025-09-19
**개발자**: Claude Code AI
**버전**: 1.0.0