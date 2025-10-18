# Panel Measurement 개발 노트

## 프로젝트 개요
엘리베이터 패널 측정 시스템 - 아이파크 신규 프로젝트 기능 추가 및 UI/UX 개선

---

## 개발 이력

### 2025-10-01

#### 1. 버튼 디자인 시스템 통일
- **변경 사항**: 모든 버튼을 Linear Design System으로 통일
- **적용 버튼**:
  - 측정 저장: `linear-btn linear-btn-primary`
  - 측정값 검증: `linear-btn linear-btn-secondary`
  - 돌아가기: `linear-btn linear-btn-secondary`
  - 자동생성: `linear-btn linear-btn-secondary`
- **CSS 정리**: 기존 `responsive-button` 클래스 제거

#### 2. 시각화 화면 가독성 개선
- **글자 크기**: `0.6rem` → `1.2rem` (2배 증가)
- **패딩**: `1px 3px` → `2px 6px`
- **줄 높이**: `line-height: 1.2` 추가
- **타공 정보 표시**: 기존 코드 복원하여 타공 크기 표시

#### 3. 측벽 패널 텍스트 위치 조정
- **좌측 패널 (2,3,4번)**: 우측으로 2em 이동 (`left: calc(50% + 2em)`)
- **우측 패널 (8,9,10번)**: 좌측으로 2em 이동 (`left: calc(50% - 2em)`)
- **목적**: 가장자리에서 벗어나 가독성 향상

#### 4. Transom CPI 타공 정보 표시
- **추가 정보**: CPI 타공 width, height 표시
- **표시 형식**: `CPI⊙width×height` → 개선 후 `CPIwidth×height↑높이`
- **특징**: 상판이므로 출입구 거리(D) 대신 하단부터 거리(↑) 표시

#### 5. 아이파크 신규 프로젝트 기능
##### 5.1 체크박스 및 입력 필드
- **체크박스 텍스트**: "iPark 신규 프로젝트" → "아이파크 신규"
- **입력 필드 간소화**: 5개 → 2개
  - 3,8번 패널 폭 (mm)
  - 6번 패널 폭 (mm)
- **설정 적용 버튼**: 입력 후 수동으로 적용하도록 개선

##### 5.2 자동 계산 로직
- **참조 파일**: `panel_measurement_backup_20250930_164135.php`
- **계산 방식**:
  - D방향 (2,3,4,8,9,10번): 카 깊이 - 3,8번 폭 = 2,4,8,10번 폭 합계
  - W방향 (5,6,7번): 카 가로 - 6번 폭 = 5,7번 폭 합계
- **기본 치수**: 
  - 카 가로: 1600mm
  - 카 깊이: 1500mm
  - 카 높이: 2700mm

##### 5.3 시각화 자동 세팅
- **`forceUpdatePanelVisualization` 함수**: 계산된 데이터를 시각화에 반영
- **`updatePanelInfo` 함수**: 패널에 치수 정보 표시
- **스타일**: 초록색 배경, 흰색 텍스트, 하단 중앙 정렬

##### 5.4 시각화 체크박스 자동 연동
- **아이파크 체크 시**: 
  - "1,11번 제외" 자동 체크
  - "트랜섬 제외" 자동 체크
  - 패널 2-10번만 표시
- **아이파크 해제 시**: 
  - 체크박스 자동 해제
  - 모든 패널 표시
- **설정 적용 버튼**: 클릭 시에도 체크박스 자동 체크

#### 6. 현장명 생성 알고리즘 개선
- **기존**: `현장_20251001_J` (년월일 8자리)
- **개선**: `현장_2510010814_J` (년도2자리+월+일+시+분 10자리)
- **형식**:
  - 년도 뒷자리 2자리: 25 (2025년)
  - 월: 10
  - 일: 01
  - 시: 08
  - 분: 14
  - 측정자: J (성)

#### 7. 재질 정보 UI 개선
- **제목 제거**: "재질 정보" 제목 삭제
- **레이아웃**: 의장재질, 두께, 엘리베이터 대수를 한 행에 배치 (3열 그리드)
- **특이사항**: rows 3 → 2로 축소
- **두께 옵션 통일**: 모달창 기준으로 본문 수정
  - 옵션: 0.8, 1.0, 1.2, 1.5, 1.6mm

#### 8. Transom 패널 UI 개선
- **치수 표시**: 가로(폭)만 표시 (세로 제외)
- **모달 레이블**: "크기 (W폭×H높이) mm" → "크기 (W폭) mm"
- **입력 필드**: 세로 입력 필드 숨김
- **레이아웃**: 가로 입력 필드만 표시

#### 9. 타공 정보 조건부 표시
- **타공 없는 패널**: 2,4,5,7,8,10번 - 모달에서 타공 정보 섹션 숨김
- **타공 가능 패널**: 1,3,6,9,11번 - 타공 정보 섹션 표시
- **Transom**: CPI 타공 정보만 표시

#### 10. 타공 크기 표시 형식 개선
##### 일반 패널 (1-11번)
- **형식**: `⊙가로×세로H높이D거리`
- **예시**: `⊙500×600H800D100`
- **구성**:
  - ⊙: 타공 기호
  - 500×600: 가로×세로
  - H800: 바닥부터 타공센터 높이
  - D100: 출입구에서 측정 거리

##### Transom 패널 (12번)
- **형식**: `CPIwidth×height↑높이`
- **예시**: `CPI500×600↑800`
- **구성**:
  - CPI: CPI 타공
  - 500×600: 가로×세로
  - ↑800: 하단부터 거리 (상판이므로 위쪽 화살표 사용)

##### 타공 정보 위치 및 크기 조정 (3번, 9번)
- **3번 패널**: 타공 정보가 있을 때
  - 위치: 우측으로 4em 이동 (`left: calc(50% + 4em)`)
  - 크기: 1.2rem → 0.72rem (약 60%로 축소)
- **9번 패널**: 타공 정보가 있을 때
  - 위치: 좌측으로 4em 이동 (`left: calc(50% - 4em)`)
  - 크기: 1.2rem → 0.72rem (약 60%로 축소)
- **목적**: 타공 정보가 많을 때 패널 영역 내에서 깔끔하게 표시
- **조건**: `has-drilling` 클래스가 있을 때만 적용

#### 11. Alert 시스템 개선
- **전역 alert 오버라이드**: 모든 `alert()` 호출을 SweetAlert2로 변환
- **옵션**:
  - `timer: 2000` - 2초 후 자동 닫기
  - `showConfirmButton: false` - 확인 버튼 없음
  - `toast: true` - 작은 toast 알림
  - `position: 'center'` - 화면 중앙 표시 (상단에서 잘림 방지)

#### 12. 데이터 저장 및 복원
##### 전역 변수 수정
- **변경**: `let panelData` → `window.panelData`
- **변경**: `let transomData` → `window.transomData`
- **이유**: 전역 스코프 접근 보장

##### 아이파크 패널 데이터 저장
- **재질 정보**: 부모 페이지의 의장재질 자동 적용
- **두께 정보**: 부모 페이지의 두께 자동 적용
- **높이 정보**: 본 설정의 카 내부 높이 자동 적용 (없으면 빈 값)
- **모달 열기**: 기본값으로 부모 페이지 값 표시
- **편집 가능**: 개별 패널 수정 가능

##### 시각화 옵션 저장
- **project_type**: 'new' 또는 'mod' 저장
- **panel_corners_excluded**: 1,11번 제외 여부
- **transom_excluded**: 트랜섬 제외 여부
- **복원**: 편집 시 저장된 상태 그대로 표시

#### 13. 측정값 검증 기능 복원
- **검증 모달**: 상세한 검증 결과 표 형식으로 표시
- **검증 항목**:
  - 좌측벽 (2,3,4번): 실측값 vs D값
  - 후면벽 (5,6,7번): 실측값 vs W값
  - 우측벽 (8,9,10번): 실측값 vs D값
- **허용 공차**: ±3mm
- **판정**: 적합/부적합 색상 구분 표시

#### 14. 폼 제출 AJAX 처리
- **변경**: 기본 폼 제출 → AJAX 요청
- **로딩 표시**: SweetAlert2 로딩 스피너
- **서버 응답 처리**:
  - 성공: 저장 완료 메시지 후 목록으로 이동
  - 실패: 에러 메시지 표시
- **디버깅**: 콘솔에 제출 데이터 및 서버 응답 출력

#### 15. 신규 작성 기본값 설정
- **가로**: 1600mm (아이파크 표준)
- **세로(깊이)**: 1500mm (아이파크 표준)
- **높이**: 2700mm (아이파크 표준)
- **적용**: `$edit_mode`가 false일 때만

---

## 주요 함수

### 1. 아이파크 관련
- `applyIparkAutoMeasurements(panel39Width, panel6Width)`: 아이파크 자동 계산
- `forceUpdatePanelVisualization(panelWidths)`: 시각화 강제 업데이트
- `updatePanelInfo(panelNum, data)`: 패널 정보 표시
- `clearIparkAutoMeasurements()`: 아이파크 자동계산 값 초기화
- `setupIparkInputListeners()`: 설정 적용 버튼 이벤트 등록

### 2. 패널 관리
- `openPanelModal(panelNumber)`: 패널 모달 열기
- `loadPanelDataToForm(panelNumber)`: 패널 데이터를 폼에 로드
- `savePanelData()`: 패널 데이터 저장
- `updatePanelVisualState(panelNumber)`: 패널 시각적 상태 업데이트
- `updatePanelVisibility()`: 패널 표시/숨김 업데이트

### 3. 검증 및 저장
- `performMeasurementValidation()`: 측정값 검증 실행
- `showValidationResultModal(results)`: 검증 결과 모달 표시
- `validateForm()`: 필수 항목 검증
- `updateJsonFields()`: JSON 필드 업데이트

### 4. UI 유틸리티
- `generateSiteName()`: 현장명 자동생성
- `updateSiteNameForIpark()`: 아이파크 체크 시 UI 업데이트
- `toggleMode(mode)`: 신규/MOD 토글
- `updatePanelDisplay()`: 전체 패널 표시 업데이트

---

## 데이터 구조

### window.panelData
```javascript
{
  "1": {
    width: 1500,
    height: 2700,
    material_type: "SUS H/L",
    thickness: "1.0",
    has_drilling: true,
    drilling_width: 500,
    drilling_height: 600,
    drilling_from_floor: 800,
    drilling_from_entrance: 100,
    panel_type: "일체형",
    front_thickness: 50,
    front_wing: 30,
    back_thickness: 50,
    back_wing: 30,
    notes: "특이사항"
  },
  // ... 2-11번
}
```

### window.transomData
```javascript
{
  "12": {
    width: 1500,
    plate_height: 200,
    bottom_depth_jd: 150,
    wing_value: 50,
    cpi_drilling_width: 500,
    cpi_drilling_height: 600,
    cpi_drilling_height_from_bottom: 800,
    material_type: "SUS H/L",
    thickness: "1.0",
    notes: "Transom 특이사항"
  }
}
```

### 아이파크 자동계산 데이터
```javascript
{
  "2": {
    width: 350,
    height: 2700,
    isIparkAuto: true,
    autoCalculatedAt: "2025-10-01T01:57:00.534Z",
    material_type: "SUS H/L",
    thickness: "1.0"
  },
  // ... 3-10번
}
```

---

## 폼 제출 데이터

### Hidden Fields
- `panel_data`: JSON 문자열 (window.panelData)
- `transom_data`: JSON 문자열 (window.transomData)
- `project_type`: 'new' 또는 'mod'

### 일반 필드
- `site_name`: 현장명 (필수)
- `measurement_date`: 측정일 (필수)
- `measurer`: 측정자 (필수)
- `car_inside_width`: 카 내부 가로 (필수)
- `car_inside_depth`: 카 내부 깊이 (필수)
- `car_inside_height`: 카 내부 높이 (필수)
- `material_type`: 의장재질
- `material_thickness`: 두께
- `elevator_count`: 엘리베이터 대수
- `notes`: 특이사항
- `ipark_check`: 아이파크 신규 여부 (0/1)
- `panel_corners_excluded`: 1,11번 제외 (0/1)
- `transom_excluded`: 트랜섬 제외 (0/1)

---

## CSS 주요 클래스

### Linear Design System
- `linear-btn`: 기본 버튼
- `linear-btn-primary`: 주요 액션 버튼
- `linear-btn-secondary`: 보조 버튼
- `linear-btn-outline`: 아웃라인 버튼
- `linear-input`: 입력 필드
- `linear-label`: 레이블

### 반응형 레이아웃
- `responsive-container`: 반응형 컨테이너
- `responsive-card`: 반응형 카드
- `responsive-grid-2`: 2열 그리드 (모바일: 1열)
- `responsive-grid-3`: 3열 그리드 (모바일: 1열)
- `responsive-section`: 반응형 섹션

### 패널 관련
- `panel-{N}`: 패널 번호별 클래스
- `panel-dimensions`: 패널 치수 표시
- `panel-info`: 패널 정보 표시 (아이파크 자동계산)
- `has-info`: 패널에 정보가 있음을 나타내는 클래스
- `transom-panel`: Transom 패널 특별 스타일

---

## 반응형 미디어 쿼리

### 모바일 (<= 767px)
- 버튼: `width: 100%`
- 그리드: 단일 열
- 현장명 자동생성: 세로 배치

### 데스크톱 (>= 768px)
- 그리드-2: 2열
- 그리드-3: 3열

---

## SweetAlert2 설정

### Toast 알림
```javascript
Swal.fire({
  icon: 'info|warning|success|error',
  text: '메시지',
  timer: 2000,
  showConfirmButton: false,
  toast: true,
  position: 'center'
});
```

### 모달 알림
```javascript
Swal.fire({
  icon: 'error',
  title: '제목',
  html: 'HTML 내용',
  confirmButtonText: '확인'
});
```

### 로딩 스피너
```javascript
Swal.fire({
  title: '저장 중...',
  allowOutsideClick: false,
  showConfirmButton: false,
  didOpen: () => { Swal.showLoading(); }
});
```

---

## 패널 번호별 특성

| 패널 | 위치 | 타공 가능 | 특수 기능 |
|------|------|-----------|-----------|
| 1번 | 하단 좌측 | ✅ | 코너 패널, 11번 복사 |
| 2번 | 좌측 하단 | ❌ | - |
| 3번 | 좌측 중앙 | ✅ | - |
| 4번 | 좌측 상단 | ❌ | - |
| 5번 | 상단 좌측 | ❌ | - |
| 6번 | 상단 중앙 | ✅ | - |
| 7번 | 상단 우측 | ❌ | - |
| 8번 | 우측 상단 | ❌ | - |
| 9번 | 우측 중앙 | ✅ | - |
| 10번 | 우측 하단 | ❌ | - |
| 11번 | 하단 우측 | ✅ | 코너 패널, 1번 복사 |
| 12번 | 하단 중앙 | ✅ | Transom, CPI 타공 |

---

## 아이파크 프로젝트 특징

### 표준 치수
- 카 가로(W): 1600mm
- 카 깊이(D): 1500mm
- 카 높이(H): 2700mm

### 사용 패널
- **포함**: 2-10번 패널 (9개)
- **제외**: 1,11번 (코너), 12번 (Transom)

### 자동 계산 공식
```
D방향:
- 3번, 9번 폭 = 사용자 입력 (예: 800mm)
- 2,4,8,10번 폭 = (카 깊이 - 3번 폭) / 2

W방향:
- 6번 폭 = 사용자 입력 (예: 1000mm)
- 5,7번 폭 = (카 가로 - 6번 폭) / 2
```

---

## 검증 로직

### 측정값 검증
```javascript
좌측벽 합계 (2+3+4) ≈ 카 깊이(D) ±3mm
후면벽 합계 (5+6+7) ≈ 카 가로(W) ±3mm
우측벽 합계 (8+9+10) ≈ 카 깊이(D) ±3mm
```

### 필수 입력 항목
1. 현장명
2. 측정일
3. 측정자
4. 카 내부 가로(W)
5. 카 내부 깊이(D)
6. 카 내부 높이(H)

---

## 이벤트 흐름

### 아이파크 설정 적용
```
1. 아이파크 신규 체크
   ↓
2. 체크박스 자동 체크 (1,11번 제외, 트랜섬 제외)
   ↓
3. 패널 설정 입력 (3,8번 폭, 6번 폭)
   ↓
4. 설정 적용 버튼 클릭
   ↓
5. 카 치수 자동 설정 (1600×1500×2700)
   ↓
6. 패널 2-10번 자동 계산
   ↓
7. 부모 페이지 재질/두께 적용
   ↓
8. 시각화 업데이트
   ↓
9. JSON 필드 업데이트
   ↓
10. 성공 알림 표시
```

### 패널 데이터 편집
```
1. 패널 클릭
   ↓
2. 모달 열기
   ↓
3. panelData에서 데이터 로드
   ↓
4. 재질/두께 없으면 부모 값 사용
   ↓
5. 폼에 데이터 표시
   ↓
6. 사용자 편집
   ↓
7. 저장 버튼 클릭
   ↓
8. panelData 업데이트
   ↓
9. 시각화 상태 업데이트
   ↓
10. JSON 필드 업데이트
```

### 폼 제출
```
1. 측정 저장 버튼 클릭
   ↓
2. updateJsonFields() 실행
   ↓
3. 필수 항목 검증
   ↓
4. AJAX로 서버 전송
   ↓
5. 로딩 스피너 표시
   ↓
6. 서버 응답 대기
   ↓
7. 성공: 저장 완료 메시지 → site_list.php
8. 실패: 에러 메시지 표시
```

---

## 디버깅 팁

### 콘솔 로그 확인
- `💾`: 데이터 저장
- `📋`: 데이터 로드
- `📦`: 전체 데이터
- `✅`: 성공
- `⚠️`: 경고
- `❌`: 에러
- `🔍`: 상세 정보
- `📤`: 폼 제출
- `📥`: 서버 응답

### 주요 확인 사항
1. `window.panelData` 객체 확인
2. `panelJsonData` hidden field 값 확인
3. 서버 응답 JSON 형식 확인
4. 체크박스 상태 확인
5. 패널 시각화 상태 확인

---

## 알려진 이슈 및 해결

### 이슈 1: panelData undefined 에러
- **원인**: 지역 변수 선언
- **해결**: `window.panelData`로 전역 변수 사용

### 이슈 2: 모바일에서 체크박스 안 보임
- **원인**: CSS `display: none` 규칙
- **해결**: 모바일 미디어 쿼리에서 숨김 규칙 제거

### 이슈 3: 알림창 상단에서 잘림
- **원인**: `position: 'top'` 설정
- **해결**: `position: 'center'`로 변경

### 이슈 4: Transom 모달에 세로 필드 표시
- **원인**: 조건부 처리 없음
- **해결**: panelNumber === '12'일 때 세로 필드 숨김

### 이슈 5: 타공 정보 변수 undefined
- **원인**: `drillingFromFloor`, `drillingFromEntrance` 변수 선언 누락
- **해결**: `updatePanelVisualState` 함수에 변수 선언 추가

---

## 향후 개선 사항

### 고려 중인 기능
- [ ] 패널 데이터 일괄 편집
- [ ] 패널 복사 기능 확장 (1↔11 외 다른 패널)
- [ ] 측정 이력 관리
- [ ] 엑셀 내보내기 기능 개선
- [ ] 모바일 최적화 추가
- [ ] 오프라인 모드 지원

### 성능 최적화
- [ ] 대량 패널 데이터 처리 최적화
- [ ] 시각화 렌더링 성능 개선
- [ ] 이미지 lazy loading

---

## 참고 파일

- `panel_measurement.php`: 메인 측정 페이지
- `panel_measurement_backup_20250930_164135.php`: 백업 (리팩토링 전)
- `save_panel_measurement.php`: 서버 저장 처리
- `site_list.php`: 현장 목록
- `assets/js/panel_measurement.js`: 외부 JavaScript

---

## 버전 정보

- **마지막 업데이트**: 2025-10-18
- **주요 변경**: 아이파크 프로젝트 타공 사이즈 계산 로직 수정
- **다음 마일스톤**: 서버 저장 로직 검증 및 안정화

---

### 2025-10-18

#### 16. 아이파크 프로젝트 타공 사이즈 계산 로직 수정

##### 배경 및 문제점
- **증상**: 아이파크 프로젝트에서 실측 타공 사이즈를 제작 사이즈로 변환하는 로직이 제대로 작동하지 않음
  - Width 900mm 패널 (3번, 6번, 9번)에서 실측 가로 700mm가 제작 가로 630mm (700-70)로 변환되지 않음
  - Width 800mm, 1000mm 패널은 정상 작동
  - 바닥높이 표시 버그: H7526으로 표시 (7522+6의 결과가 아닌 문자열 연결 "7526")

##### 근본 원인 분석
1. **아이파크 프로젝트 감지 로직 문제**
   - 기존: `site_name.includes('아이파크')` 문자열 검색 방식 사용
   - 문제점: 현장명이 정확히 "아이파크"를 포함하지 않거나 데이터 불일치 시 감지 실패
   - 해결: 데이터베이스의 `ipark_check` 필드 사용으로 변경

2. **JavaScript 타입 변환 문제**
   - 기존: `data.drilling_from_floor + 6` 연산 시 문자열 연결 발생
   - 문제점: drilling 데이터가 문자열로 저장되어 "7522" + 6 = "75226"으로 계산
   - 해결: `parseInt()`로 명시적 숫자 변환 후 계산

##### 수정 내역

###### 1. result.php (Lines 6474-6503)
**웹 화면 표시 및 '설정 적용' 버튼 로직 수정**

```javascript
// 아이파크 프로젝트 확인 (ipark_check 필드 사용)
const isIparkProject = window.currentSelectedData &&
    (window.currentSelectedData.ipark_check == 1 || window.currentSelectedData.ipark_check === true);

if (isIparkProject) {
    // 제작가로 = 실측가로 - 70
    displayWidth = parseInt(data.drilling_width) - 70;
    // 제작세로 = 실측세로 - 13
    displayHeight = parseInt(data.drilling_height) - 13;
    // 제작바닥높이 = 실측바닥높이 + 6
    if (data.drilling_from_floor) {
        displayFromFloor = parseInt(data.drilling_from_floor) + 6;
    }
}
```

**변경 사항**:
- ❌ 변경 전: `site_name.includes('아이파크')` 문자열 검색
- ✅ 변경 후: `ipark_check` 필드 사용 (1 또는 true)
- ❌ 변경 전: `data.drilling_from_floor + 6` (문자열 연결)
- ✅ 변경 후: `parseInt(data.drilling_from_floor) + 6` (숫자 계산)
- 모든 drilling 계산에 `parseInt()` 적용하여 일관성 확보

###### 2. export_production_results.php (Lines 501-506)
**단일 현장 엑셀 내보내기 로직 수정**

```php
// 아이파크 프로젝트 확인 (ipark_check 필드 사용)
$is_ipark_project = false;
if (isset($selected_data['ipark_check']) && ($selected_data['ipark_check'] == 1 || $selected_data['ipark_check'] === true)) {
    $is_ipark_project = true;
    error_log("아이파크 프로젝트 감지됨 (ipark_check: " . $selected_data['ipark_check'] . ")");
}
```

**변경 사항**:
- ❌ 변경 전: `strpos($selected_data['site_name'], '아이파크') !== false`
- ✅ 변경 후: `$selected_data['ipark_check'] == 1 || $selected_data['ipark_check'] === true`
- 디버깅을 위한 error_log 추가

###### 3. export_group_production_data.php (3곳 수정)
**그룹 현장 엑셀 내보내기 로직 수정**

**3-1. Lines 204-208 (현장기초정보 시트)**
```php
// 아이파크 체크 여부 확인 (ipark_check 필드 사용)
$ipark_check = 'N';
if (isset($measurement['ipark_check']) && ($measurement['ipark_check'] == 1 || $measurement['ipark_check'] === true)) {
    $ipark_check = 'Y';
}
```

**3-2. Lines 390-394 (제작산출결과 시트 - 표시용)**
```php
// 아이파크 체크 여부 확인 (ipark_check 필드 사용)
$ipark_check = 'N';
if (isset($measurement['ipark_check']) && ($measurement['ipark_check'] == 1 || $measurement['ipark_check'] === true)) {
    $ipark_check = 'Y';
}
```

**3-3. Lines 457-461 (제작산출결과 시트 - 계산용)**
```php
// 아이파크 프로젝트 확인 (ipark_check 필드 사용)
$is_ipark_project = false;
if (isset($measurement['ipark_check']) && ($measurement['ipark_check'] == 1 || $measurement['ipark_check'] === true)) {
    $is_ipark_project = true;
}
```

**변경 사항**:
- 3개 위치 모두 `site_name` 문자열 검색 → `ipark_check` 필드 검사로 변경
- 화면 표시용('Y'/'N')과 실제 계산용(`$is_ipark_project`) 로직 통일
- 타공 계산: `$hole_width - 70`, `$hole_height - 13`, `$hole_floor_height + 6`

###### 4. export_merged_production_data.php (Lines 350-355)
**병합 엑셀 내보내기 로직 수정**

```php
// 아이파크 프로젝트 확인 (ipark_check 필드 사용)
$is_ipark_project = false;
if (isset($measurement['ipark_check']) && ($measurement['ipark_check'] == 1 || $measurement['ipark_check'] === true)) {
    $is_ipark_project = true;
    error_log("아이파크 프로젝트 감지됨 (ipark_check: " . $measurement['ipark_check'] . ")");
}
```

**변경 사항**:
- ❌ 변경 전: `strpos($measurement['site_name'], '아이파크') !== false`
- ✅ 변경 후: `$measurement['ipark_check'] == 1 || $measurement['ipark_check'] === true`

##### 아이파크 제작 사이즈 변환 규칙
```
아이파크 프로젝트 (ipark_check == 1):
  제작 가로 = 실측 가로 - 70mm
  제작 세로 = 실측 세로 - 13mm
  제작 바닥높이 = 실측 바닥높이 + 6mm
  제작 출입구거리 = (생산폭 - 제작가로) / 2

일반 프로젝트:
  제작 사이즈 = 실측 사이즈 (변환 없음)
```

##### 적용 범위
- ✅ **웹 화면**: result.php의 '설정 적용' 버튼 클릭 시
- ✅ **단일 엑셀**: export_production_results.php (개별 현장 내보내기)
- ✅ **그룹 엑셀**: export_group_production_data.php (현장 그룹 내보내기)
- ✅ **병합 엑셀**: export_merged_production_data.php (병합 내보내기)

##### 예상 효과
1. **정확한 프로젝트 감지**: 현장명에 의존하지 않고 DB 필드로 판단
2. **타입 안정성**: parseInt()로 숫자 계산 보장
3. **일관된 동작**: 웹/엑셀 모든 출력에서 동일한 변환 적용
4. **유지보수성**: 단일 필드(ipark_check) 기반으로 로직 통일

##### 테스트 체크리스트
- [ ] Width 900mm 패널에서 700→630 변환 확인
- [ ] H7526 버그 수정 확인 (7522+6=7528 정상 표시)
- [ ] Width 800mm, 1000mm 패널 정상 작동 확인
- [ ] 단일 현장 엑셀 내보내기 제작 사이즈 확인
- [ ] 그룹 현장 엑셀 내보내기 제작 사이즈 확인
- [ ] 병합 엑셀 내보내기 제작 사이즈 확인
- [ ] 일반 프로젝트(ipark_check=0)에서 실측=제작 확인

##### 관련 파일
- `result.php`: 웹 화면 표시 및 설정 적용 로직
- `export_production_results.php`: 단일 현장 엑셀 내보내기
- `export_group_production_data.php`: 그룹 현장 엑셀 내보내기
- `export_merged_production_data.php`: 병합 엑셀 내보내기

---

