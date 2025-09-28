<?php 
// 환경파일 읽어오기 (테이블명 작업 폴더 등)
include 'ini.php';    
session_start(); 

$level= $_SESSION["level"];
$user_name= $_SESSION["name"];
$id= $_SESSION["userid"];

ini_set('display_errors','0');  // 화면에 warning 없애기	

 if($level!==1) {
	 sleep(1);
         header("Location:http://j-techel.co.kr/game/login/login_form.php"); 
         exit;
   }  

// 모바일 사용여부 확인하는 루틴
$mAgent = array("iPhone","iPod","Android","Blackberry", 
    "Opera Mini", "Windows ce", "Nokia", "sony" );
$chkMobile = false;
for($i=0; $i<sizeof($mAgent); $i++){
    if(stripos( $_SERVER['HTTP_USER_AGENT'], $mAgent[$i] )){
        $chkMobile = true;
        break;
    }
}  

$root_dir = $_SERVER['DOCUMENT_ROOT'];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>사용자 관리</title>

<!-- CDN 라이브러리 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.10/dist/sweetalert2.min.css" rel="stylesheet">
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script> 
<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

<!-- Bootstrap -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.1/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.10/dist/sweetalert2.all.min.js"></script>

<!-- Tabulator -->
<link href="https://unpkg.com/tabulator-tables@5.5.0/dist/css/tabulator.min.css" rel="stylesheet">
<script type="text/javascript" src="https://unpkg.com/tabulator-tables@5.5.0/dist/js/tabulator.min.js"></script>

<!-- J-TECH 디자인 시스템 - 인라인으로 포함 -->

<style>
/* J-TECH 디자인 토큰 - 핵심 변수들 */
:root {
  /* 색상 */
  --color-primary: #2563eb;
  --color-primary-light: #60a5fa;
  --color-primary-dark: #1d4ed8;
  --color-secondary: #64748b;
  --color-success: #10b981;
  --color-warning: #f59e0b;
  --color-error: #ef4444;
  --color-info: #3b82f6;
  
  /* 그레이 팔레트 */
  --color-gray-50: #f8fafc;
  --color-gray-100: #f1f5f9;
  --color-gray-200: #e2e8f0;
  --color-gray-300: #cbd5e1;
  --color-gray-400: #94a3b8;
  --color-gray-500: #64748b;
  --color-gray-600: #475569;
  --color-gray-700: #334155;
  --color-gray-800: #1e293b;
  --color-gray-900: #0f172a;
  --color-white: #ffffff;
  --color-black: #000000;
  
  /* 보더 */
  --color-border: #e2e8f0;
  --color-border-light: #f1f5f9;
  --color-border-dark: #cbd5e1;
  
  /* 폰트 */
  --font-family-primary: "Malgun Gothic", "Apple SD Gothic Neo", "Noto Sans KR", sans-serif;
  --font-size-xs: 0.75rem;
  --font-size-sm: 0.875rem;
  --font-size-base: 1rem;
  --font-size-lg: 1.125rem;
  --font-size-xl: 1.25rem;
  --font-size-2xl: 1.5rem;
  --font-weight-normal: 400;
  --font-weight-medium: 500;
  --font-weight-semibold: 600;
  --font-weight-bold: 700;
  
  /* 간격 */
  --space-1: 0.25rem;
  --space-2: 0.5rem;
  --space-3: 0.75rem;
  --space-4: 1rem;
  --space-5: 1.25rem;
  --space-6: 1.5rem;
  --space-8: 2rem;
  
  /* 반지름 */
  --border-radius-sm: 0.25rem;
  --border-radius-md: 0.375rem;
  --border-radius-lg: 0.5rem;
  --border-radius-xl: 0.75rem;
  --border-radius-full: 9999px;
  
  /* 애니메이션 */
  --duration-150: 150ms;
  --duration-200: 200ms;
  --ease-out: cubic-bezier(0, 0, 0.2, 1);
  
  /* 그림자 */
  --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
  
  /* 크기 */
  --size-sm: 2rem;
  --size-md: 2.5rem;
  --size-lg: 3rem;
}

/* 기본 버튼 스타일 */
.jt-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-2);
  padding: var(--space-3) var(--space-4);
  font-family: var(--font-family-primary);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  line-height: 1;
  text-align: center;
  text-decoration: none;
  white-space: nowrap;
  border: 1px solid transparent;
  border-radius: var(--border-radius-md);
  background-color: transparent;
  cursor: pointer;
  user-select: none;
  transition: all var(--duration-150) var(--ease-out);
  min-height: var(--size-md);
}

.jt-btn--primary {
  background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
  color: var(--color-white);
  box-shadow: var(--shadow-sm);
}

.jt-btn--primary:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.jt-btn--ghost {
  background: transparent;
  color: var(--color-gray-600);
  border: 1px solid var(--color-border);
}

.jt-btn--ghost:hover {
  background: var(--color-gray-50);
  color: var(--color-gray-800);
}

.jt-btn--sm {
  padding: var(--space-2) var(--space-3);
  font-size: var(--font-size-xs);
  min-height: var(--size-sm);
}

.jt-btn--lg {
  padding: var(--space-4) var(--space-6);
  font-size: var(--font-size-base);
  min-height: var(--size-lg);
}

/* 카드 스타일 */
.jt-card {
  background: var(--color-white);
  border-radius: var(--border-radius-lg);
  border: 1px solid var(--color-border);
  box-shadow: var(--shadow-sm);
}

.jt-card__header {
  padding: var(--space-4) var(--space-6);
  border-bottom: 1px solid var(--color-border);
}

.jt-card__title {
  font-size: var(--font-size-lg);
  font-weight: var(--font-weight-bold);
  color: var(--color-gray-900);
  margin: 0;
}

.jt-card__subtitle {
  font-size: var(--font-size-sm);
  color: var(--color-gray-600);
  margin: var(--space-1) 0 0 0;
}

.jt-card__body {
  padding: var(--space-6);
}

.jt-card__footer {
  padding: var(--space-4) var(--space-6);
  background: var(--color-gray-50);
  border-top: 1px solid var(--color-border);
}

/* 레이아웃 */
.jt-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 var(--space-4);
}

.jt-flex {
  display: flex;
}

.jt-flex--between {
  justify-content: space-between;
}

/* 배지 */
.jt-badge {
  display: inline-flex;
  align-items: center;
  gap: var(--space-1);
  padding: var(--space-1) var(--space-3);
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-medium);
  border-radius: var(--border-radius-full);
  box-shadow: var(--shadow-sm);
}

.jt-badge--info {
  background: linear-gradient(135deg, #dbeafe, #bfdbfe);
  color: var(--color-info);
}

/* 스피너 */
.jt-spinner {
  display: inline-block;
  width: 20px;
  height: 20px;
  border: 2px solid var(--color-gray-300);
  border-radius: 50%;
  border-top-color: var(--color-primary);
  animation: spin 1s ease-in-out infinite;
}

.jt-spinner--lg {
  width: 40px;
  height: 40px;
  border-width: 4px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* 기존 스타일과의 호환성을 위한 추가 스타일 */
.legacy-compatibility {
    font-family: var(--font-family-primary);
}

/* 메인 컨테이너 중앙 정렬 */
.jt-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

/* 사용자 테이블 특별 스타일 */
.user-table-row {
    transition: all var(--duration-150) var(--ease-out);
    border-bottom: 1px solid var(--color-border) !important;
}

.user-table-row:hover {
    background-color: var(--color-gray-50);
    transform: translateY(-1px);
    box-shadow: var(--shadow-sm);
}

/* 테이블 테두리 강화 */
.jt-table {
    border: 2px solid var(--color-border);
    border-radius: var(--border-radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-md);
}

.jt-table th {
    border-right: 1px solid var(--color-border);
    border-bottom: 2px solid var(--color-border);
    background-color: var(--color-gray-100);
    font-weight: var(--font-weight-semibold);
}

.jt-table th:last-child {
    border-right: none;
}

.jt-table td {
    border-right: 1px solid var(--color-border-light);
    vertical-align: middle;
}

.jt-table td:last-child {
    border-right: none;
}

.user-level-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.user-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}

/* 모던한 버튼 스타일 개선 */
.jt-btn {
    font-weight: var(--font-weight-medium);
    letter-spacing: 0.025em;
    box-shadow: var(--shadow-sm);
    transition: all var(--duration-200) var(--ease-out);
}

.jt-btn:hover {
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

.jt-btn--primary {
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
    border: none;
}

.jt-btn--primary:hover {
    background: linear-gradient(135deg, var(--color-primary-dark), #1e40af);
}

.jt-btn--ghost:hover {
    background-color: var(--color-gray-100);
    border-color: var(--color-gray-300);
}

/* 검색 및 필터 영역 개선 */
.search-section {
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    border: 1px solid var(--color-border);
}

/* 카드 그림자 강화 */
.jt-card {
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--color-border);
}

.jt-card__header {
    background: linear-gradient(135deg, #f8fafc, #ffffff);
    border-bottom: 2px solid var(--color-border);
}

/* 배지 스타일 개선 */
.jt-badge {
    font-weight: var(--font-weight-medium);
    box-shadow: var(--shadow-sm);
}

.jt-badge--primary {
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
}

.jt-badge--error {
    background: linear-gradient(135deg, var(--color-error), #dc2626);
}

.jt-badge--success {
    background: linear-gradient(135deg, var(--color-success), #059669);
}

.jt-badge--secondary {
    background: linear-gradient(135deg, var(--color-gray-200), var(--color-gray-300));
    color: var(--color-gray-700);
}

/* 사용자 아바타 개선 */
.user-avatar {
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
    box-shadow: var(--shadow-sm);
}

/* 반응형 개선 */
@media (max-width: 768px) {
    .mobile-stack {
        flex-direction: column;
        gap: 1rem;
    }
    
    .mobile-full-width {
        width: 100% !important;
    }
    
    .jt-container {
        padding: 0 0.5rem;
    }
}

/* 로딩 상태 */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

/* 페이지네이션 스타일 개선 */
.pagination-controls .jt-btn {
    min-width: 40px;
    height: 40px;
}

.pagination-controls .jt-btn--secondary {
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
    color: white;
}

/* Tabulator 커스텀 스타일 - 가독성 개선 */
.tabulator {
    border: 2px solid var(--color-border);
    border-radius: var(--border-radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-lg);
    font-family: var(--font-family-primary);
    font-size: 14px;
    line-height: 1.5;
}

/* 헤더 스타일 개선 */
.tabulator .tabulator-header {
    background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
    border-bottom: 2px solid var(--color-primary);
    font-weight: var(--font-weight-bold);
    color: var(--color-gray-700);
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.5px;
}

.tabulator .tabulator-header .tabulator-col {
    background: transparent;
    border-right: 1px solid var(--color-border);
    padding: 16px 12px;
}

.tabulator .tabulator-header .tabulator-col:last-child {
    border-right: none;
}

.tabulator .tabulator-header .tabulator-col .tabulator-col-content {
    justify-content: center;
}

/* 행 스타일 개선 */
.tabulator .tabulator-row {
    border-bottom: 1px solid var(--color-border-light);
    transition: all var(--duration-200) var(--ease-out);
    min-height: 56px;
}

.tabulator .tabulator-row:nth-child(even) {
    background-color: #f8fafc;
}

.tabulator .tabulator-row:hover {
    background-color: #e0f2fe;
    border-left: 4px solid var(--color-primary);
    transform: translateX(2px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.tabulator .tabulator-row.tabulator-selected {
    background-color: rgba(37, 99, 235, 0.15);
    border-left: 4px solid var(--color-primary);
}

/* 셀 스타일 개선 */
.tabulator .tabulator-cell {
    border-right: 1px solid var(--color-border-light);
    vertical-align: middle;
    padding: 12px;
    color: var(--color-gray-700);
}

.tabulator .tabulator-cell:last-child {
    border-right: none;
}

/* 행 클릭 힌트 */
.tabulator .tabulator-row::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 0;
    background: var(--color-primary);
    transition: width var(--duration-200) var(--ease-out);
}

.tabulator .tabulator-row:hover::before {
    width: 4px;
}

/* Tabulator 페이지네이션 스타일 개선 */
.tabulator .tabulator-footer {
    background: linear-gradient(135deg, #f8fafc, #ffffff);
    border-top: 2px solid var(--color-border);
    padding: 16px;
}

.tabulator .tabulator-footer .tabulator-page {
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius-md);
    margin: 0 2px;
    padding: 8px 12px;
    transition: all var(--duration-150) var(--ease-out);
}

.tabulator .tabulator-footer .tabulator-page:hover {
    background: var(--color-primary);
    color: white;
    transform: translateY(-1px);
}

.tabulator .tabulator-footer .tabulator-page.active {
    background: var(--color-primary);
    color: white;
    font-weight: var(--font-weight-semibold);
}

/* 행 클릭 가능 표시 */
.tabulator .tabulator-row {
    position: relative;
    cursor: pointer !important;
}

.tabulator .tabulator-row:hover::after {
    content: '';
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: var(--color-primary);
    color: white;
    padding: 4px 8px;
    border-radius: var(--border-radius-full);
    font-size: 11px;
    font-weight: var(--font-weight-medium);
    opacity: 0.9;
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-50%) translateX(10px); }
    to { opacity: 0.9; transform: translateY(-50%) translateX(0); }
}

/* Tabulator 반응형 */
@media (max-width: 768px) {
    .tabulator {
        font-size: var(--font-size-sm);
    }
    
    .tabulator .tabulator-cell {
        padding: 8px;
    }
    
    .tabulator .tabulator-row:hover::after {
        display: none; /* 모바일에서는 힌트 텍스트 숨기기 */
    }
}

/* 사용자 아바타 in Tabulator */
.user-avatar-small {
    width: 32px;
    height: 32px;
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 14px;
    margin-right: 8px;
    box-shadow: var(--shadow-sm);
}

.user-info {
    display: flex;
    align-items: center;
}

.user-details {
    display: flex;
    flex-direction: column;
}

.user-name {
    font-weight: var(--font-weight-medium);
    color: var(--color-text-primary);
}

.user-id {
    font-size: var(--font-size-xs);
    color: var(--color-text-muted);
}

/* 액션 버튼 그룹 */
.action-buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}

.action-btn {
    padding: 0.25rem 0.5rem;
    border: 1px solid var(--color-border);
    background: white;
    border-radius: var(--border-radius-sm);
    cursor: pointer;
    transition: all var(--duration-150) var(--ease-out);
    font-size: var(--font-size-xs);
}

.action-btn:hover {
    background: var(--color-gray-100);
    transform: translateY(-1px);
    box-shadow: var(--shadow-sm);
}

.action-btn--edit {
    color: var(--color-primary);
    border-color: var(--color-primary);
}

.action-btn--delete {
    color: var(--color-error);
    border-color: var(--color-error);
}

/* 배지 스타일 for Tabulator */
.level-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    border-radius: var(--border-radius-full);
    font-size: var(--font-size-xs);
    font-weight: var(--font-weight-medium);
    box-shadow: var(--shadow-sm);
}

.level-badge--admin {
    background: linear-gradient(135deg, var(--color-error), #dc2626);
    color: white;
}

.level-badge--staff {
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
    color: white;
}

.branch-badge {
    background: linear-gradient(135deg, var(--color-gray-200), var(--color-gray-300));
    color: var(--color-gray-700);
    padding: 0.25rem 0.5rem;
    border-radius: var(--border-radius-full);
    font-size: var(--font-size-xs);
    font-weight: var(--font-weight-medium);
    box-shadow: var(--shadow-sm);
}

.status-badge {
    background: linear-gradient(135deg, var(--color-success), #059669);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: var(--border-radius-full);
    font-size: var(--font-size-xs);
    font-weight: var(--font-weight-medium);
    box-shadow: var(--shadow-sm);
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}
</style>
</head>

<body class="legacy-compatibility">

<?php include 'myheader.php'; ?>

<?php
include "_request.php"; 

// 지점 헬퍼 함수 포함
require_once('branch_select_helper.php');

if($fromdate=="") {
    $fromdate=$fromdate . "2020-01-01";
}
if($todate=="") {
    $todate=substr(date("Y-m-d",time()),0,4) . "-12-31" ;
    $Transtodate=strtotime($todate.'+1 days');
    $Transtodate=date("Y-m-d",$Transtodate);
} else {
    $Transtodate=strtotime($todate);
    $Transtodate=date("Y-m-d",$Transtodate);
}

// 활성 지점 목록 가져오기
$activeBranches = getActiveBranches($pdo);
		  
if(isset($_REQUEST["find"]))
    $find=$_REQUEST["find"];

$common=" ";
$a= $common . "  ";
$b= $common . "  ";

// 검색을 위해 모든 검색변수 공백제거
$search = str_replace(' ', '', $search);    
  
// 지점 정보와 함께 사용자 데이터 조회 (LEFT JOIN 사용)
$sql="SELECT gm.*, 
       COALESCE(b.branch_name, gm.branch, '미지정') as branch_display_name
       FROM jtechel.game_member gm 
       LEFT JOIN jtechel.branches b ON gm.branch = b.branch_name 
       " . $a; 					
$sqlcon = "SELECT gm.*, 
           COALESCE(b.branch_name, gm.branch, '미지정') as branch_display_name
           FROM jtechel.game_member gm 
           LEFT JOIN jtechel.branches b ON gm.branch = b.branch_name 
           " . $b;
						
$nowday=date("Y-m-d");
?>

<!-- 로딩 오버레이 -->
<div id="loadingOverlay" class="loading-overlay" style="display: none;">
    <div class="jt-spinner jt-spinner--lg"></div>
</div>

<div class="jt-container">
    <!-- 페이지 헤더: 최신 트렌드형 대시보드 스타일 -->
    <section class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 mt-4 mb-2 px-2 py-3 rounded-4 shadow-sm bg-white border border-2 border-primary-subtle" style="min-height: 90px;">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width:56px;height:56px;">
                <i class="bi bi-people-fill text-primary fs-2"></i>
            </div>
            <div>
                <h1 class="fw-bold mb-1 fs-3">사용자 관리</h1>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-secondary small">시스템 사용자 계정 관리</span>
                    <span class="badge bg-info-subtle text-info-emphasis rounded-pill px-3 py-1 ms-1" style="font-size:0.95rem;">
                        관리자: 레벨 1 <span class="mx-1">|</span> 직원: 레벨 4
                    </span>
                </div>
            </div>
        </div>
        <div>
            <button type="button" class="btn btn-primary btn-lg d-flex align-items-center gap-2 px-4 shadow-sm"
                    style="border-radius: 2rem;" onclick="openUserForm()">
                <i class="bi bi-person-plus-fill"></i>
                <span class="fw-semibold">새 사용자 등록</span>
            </button>
        </div>
    </section>

    <!-- 검색 및 필터: 카드+그리드+모바일 스택형, 아이콘/애니메이션 강조 -->
    <section class="bg-white rounded-4 shadow-sm border border-2 border-primary-subtle mt-4 px-3 py-4">
        <form name="board_form" id="board_form" method="post" action="user.php" autocomplete="off">
            <input type="hidden" id="page" name="page" value="<?=$page?>">
            <input type="hidden" id="scale" name="scale" value="<?=$scale?>">
            <!-- 기타 숨겨진 필드들... -->

            <div class="row g-3 align-items-end">
                <!-- 검색어 -->
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold text-primary-emphasis mb-1" for="search">
                        <i class="bi bi-search me-1"></i>검색어
                    </label>
                    <input type="text" name="search" id="search" class="form-control form-control-lg rounded-pill shadow-sm"
                        placeholder="이름, 아이디로 검색" value="<?=htmlspecialchars($search)?>"
                        onkeydown="SearchEnter()" autocomplete="off">
                </div>
                <!-- 지점 -->
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold text-primary-emphasis mb-1" for="branch_filter">
                        <i class="bi bi-building me-1"></i>지점
                    </label>
                    <select name="branch_filter" id="branch_filter" class="form-select form-select-lg rounded-pill shadow-sm">
                        <option value="">전체 지점</option>
                        <?php 
                        // 동적으로 활성 지점 목록 출력
                        foreach ($activeBranches as $branch): ?>
                            <option value="<?=htmlspecialchars($branch['branch_name'])?>"><?=htmlspecialchars($branch['branch_name'])?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- 레벨 -->
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold text-primary-emphasis mb-1" for="level_filter">
                        <i class="bi bi-person-badge me-1"></i>사용자 레벨
                    </label>
                    <select name="level_filter" id="level_filter" class="form-select form-select-lg rounded-pill shadow-sm">
                        <option value="">전체 레벨</option>
                        <option value="1">관리자 (레벨 1)</option>
                        <option value="4">직원 (레벨 4)</option>
                    </select>
                </div>
                <!-- 버튼 -->
                <div class="col-12 col-md-2 d-flex gap-2 justify-content-md-end justify-content-center">
                    <button type="button" class="btn btn-outline-secondary btn-lg rounded-pill px-4 d-flex align-items-center gap-1"
                        onclick="resetSearch()" title="검색 조건 초기화">
                        <i class="bi bi-arrow-clockwise"></i>
                        <span class="d-none d-md-inline">초기화</span>
                    </button>
                    <button type="button" id="searchBtn" class="btn btn-primary btn-lg rounded-pill px-4 d-flex align-items-center gap-1"
                        title="검색">
                        <i class="bi bi-search"></i>
                        <span class="d-none d-md-inline">검색</span>
                    </button>
                </div>
            </div>
        </form>
    </section>

    <!-- 사용자 목록 (UI/UX 개선) -->
    <div class="jt-card mt-4 shadow-lg border-0 rounded-4 overflow-hidden">
        <div class="jt-card__header bg-gradient-primary-to-blue px-4 py-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                <h3 class="jt-card__title fw-bold text-primary mb-0">
                    <i class="bi bi-people-fill me-2"></i>사용자 목록
                </h3>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted fs-5">                      
                    </span>
                    <button class="jt-btn jt-btn--ghost jt-btn--sm border-0" onclick="refreshUserList()" title="새로고침">
                        <i class="bi bi-arrow-clockwise fs-5"></i>
                    </button>
                    <button class="jt-btn jt-btn--primary jt-btn--sm d-flex align-items-center gap-1" onclick="openUserForm()" title="사용자 추가">
                        <i class="bi bi-person-plus-fill"></i>
                        <span class="d-none d-md-inline">사용자 등록</span>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- 반응형: 모바일에서는 카드, 데스크톱에서는 테이블 -->
        <div id="user-table" class="mt-3"></div>
        
        <?php
        // 사용자 데이터를 JavaScript 배열로 준비
        $userData = array();
        try {  
            $allstmh = $pdo->query($sqlcon);
            $total_users = $allstmh->rowCount();  
            $stmh = $pdo->query($sql);
            $start_num = $stmh->rowCount();      
            
            while($row = $stmh->fetch(PDO::FETCH_ASSOC)) {
                $userData[] = array(
                    'num' => $row["num"],
                    'id' => $row["id"],
                    'pass' => $row["pass"],
                    'level' => $row["level"],
                    'name' => $row["name"],
                    'branch' => $row["branch_display_name"] // 지점 표시명 사용
                );
            }
        } catch (PDOException $Exception) {
            echo '<div class="alert alert-danger">오류: '.$Exception->getMessage().'</div>';
        }
        ?>

        <!-- 빈 상태 UX 개선 -->
        <div id="emptyState" class="text-center py-5" style="display: none;">
            <div class="mb-3">
                <i class="bi bi-person-x text-secondary" style="font-size: 4rem;"></i>
            </div>
            <h4 class="text-muted mb-2 fw-bold">등록된 사용자가 없습니다</h4>
            <p class="text-muted mb-4">아직 등록된 사용자가 없습니다.<br>지금 바로 첫 사용자를 추가해보세요!</p>
            <button class="jt-btn jt-btn--primary btn-lg rounded-pill px-4" onclick="openUserForm()">
                <i class="bi bi-person-plus-fill me-2"></i>
                첫 번째 사용자 등록
            </button>
        </div>

        <!-- Tabulator 통계 정보 (UX 개선) -->
        <div class="jt-card__footer bg-light px-4 py-3 border-top">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                <div class="text-muted fs-6">
                    <i class="bi bi-info-circle me-1"></i>
                    <span class="d-none d-md-inline">총</span>
                    <span id="total_users_count" class="fw-semibold text-primary"><?=$total_users ?? 0?></span>명의 사용자
                </div>
                <div class="text-muted fs-6">
                    <span id="table_info">
                        <i class="bi bi-table me-1"></i>
                        <span class="d-none d-md-inline">Tabulator</span> 데이터 테이블 사용
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 푸터 -->
<div class="mt-5">
    <?php include './footer.php'; ?>
</div>
</div>

<script>
// 전역 변수
let isLoading = false;
let userTable;

// 사용자 데이터 (PHP에서 전달)
const userData = <?php echo json_encode($userData, JSON_UNESCAPED_UNICODE); ?>;

// 페이지 로드 시 초기화
$(document).ready(function() { 
    initializeTable();
    
    // 검색 버튼 이벤트
    $("#searchBtn").click(function(){ 	
        filterTable();
    });
    
    // 실시간 검색 (디바운싱)
    let searchTimeout;
    $("#search").on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            if ($("#search").val().length >= 1 || $("#search").val().length === 0) {
                filterTable();
            }
        }, 300);
    });
    
    // 필터 변경 이벤트
    $('[name="branch_filter"], [name="level_filter"]').on('change', function() {
        filterTable();
    });
});

// Tabulator 초기화
function initializeTable() {
    userTable = new Tabulator("#user-table", {
        data: userData,
        layout: "fitColumns",
        responsiveLayout: "collapse",
        responsiveLayoutCollapseStartOpen: false,
        responsiveLayoutCollapseUseFormatters: false,
        pagination: "local",
        paginationSize: 10,
        paginationSizeSelector: [5, 10, 15, 20, 25, 50],
        paginationCounter: "rows",
        movableColumns: true,
        resizableRows: true,
        initialSort: [
            {column: "num", dir: "desc"}
        ],
        rowClick: function(e, row) {
            // 행 클릭 시 사용자 편집 함수 호출
            const userData = row.getData();
            editUser(userData.num);
        },
        rowFormatter: function(row) {
            // 행 hover 효과를 위한 커서 스타일
            row.getElement().style.cursor = "pointer";
        },
        locale: "ko-kr",
        langs: {
            "ko-kr": {
                "pagination": {
                    "page_size": "페이지 크기:",
                    "page_title": "페이지 표시",
                    "first": "처음",
                    "first_title": "첫 페이지",
                    "last": "마지막",
                    "last_title": "마지막 페이지", 
                    "prev": "이전",
                    "prev_title": "이전 페이지",
                    "next": "다음",
                    "next_title": "다음 페이지",
                    "counter": {
                        "showing": "표시:",
                        "of": "/",
                        "rows": "행",
                        "pages": "페이지"
                    }
                },
                "headerFilters": {
                    "default": "필터..."
                }
            }
        },
        columns: [
            {
                title: "번호", 
                field: "num", 
                width: 80, 
                hozAlign: "center",
                sorter: "number",
                formatter: function(cell) {
                    return '<div style="font-weight: 600; color: #2563eb; font-size: 14px; padding: 4px;">' + 
                           cell.getValue() + 
                           '</div>';
                }
            },
            {
                title: "사용자", 
                field: "name", 
                width: 220,
                formatter: function(cell, formatterParams, onRendered) {
                    const rowData = cell.getRow().getData();
                    const initial = rowData.name ? rowData.name.charAt(0) : '?';
                    const avatarColors = ['#ef4444', '#f97316', '#eab308', '#22c55e', '#3b82f6', '#8b5cf6', '#ec4899'];
                    const colorIndex = (rowData.name || '').length % avatarColors.length;
                    const avatarColor = avatarColors[colorIndex];
                    
                    return '<div style="display: flex; align-items: center; gap: 12px; padding: 4px 0;">' +
                           '<div style="width: 36px; height: 36px; background: ' + avatarColor + '; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">' + initial + '</div>' +
                           '<div>' +
                           '<div style="font-weight: 600; color: #1f2937; font-size: 14px;">' + (rowData.name || '') + '</div>' +
                           '<div style="color: #6b7280; font-size: 12px; margin-top: 2px;">@' + (rowData.id || '') + '</div>' +
                           '</div>' +
                           '</div>';
                }
            },
            {
                title: "지점", 
                field: "branch", 
                width: 120, 
                hozAlign: "center",
                formatter: function(cell) {
                    const value = cell.getValue() || '';
                    if (!value) return '<span style="color: #9ca3af;">-</span>';
                    return '<div style="background: linear-gradient(135deg, #ddd6fe, #c4b5fd); color: #5b21b6; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">' + 
                           value + 
                           '</div>';
                }
            },
            {
                title: "아이디", 
                field: "id", 
                width: 150,
                formatter: function(cell) {
                    const value = cell.getValue() || '';
                    if (!value) return '<span style="color: #9ca3af;">-</span>';
                    return '<div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 6px 10px; border-radius: 6px; font-family: monospace; font-size: 13px; color: #374151; font-weight: 500;">' + 
                           value + 
                           '</div>';
                }
            },
            {
                title: "레벨", 
                field: "level", 
                width: 130, 
                hozAlign: "center",
                sorter: "number",
                formatter: function(cell) {
                    const level = cell.getValue();
                    const isAdmin = level == 1;
                    const badgeColor = isAdmin ? '#dc2626' : '#059669';
                    const badgeBg = isAdmin ? 'linear-gradient(135deg, #fecaca, #fca5a5)' : 'linear-gradient(135deg, #d1fae5, #a7f3d0)';
                    const icon = isAdmin ? '🛡️' : '👤';
                    const text = isAdmin ? '관리자' : '직원';
                    
                    return '<div style="display: inline-flex; align-items: center; gap: 6px; background: ' + badgeBg + '; color: ' + badgeColor + '; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">' + 
                           '<span style="font-size: 14px;">' + icon + '</span>' +
                           '<span>' + text + '</span>' +
                           '<span style="opacity: 0.8;">(' + level + ')</span>' +
                           '</div>';
                }
            },
            {
                title: "상태", 
                field: "status", 
                width: 100, 
                hozAlign: "center",
                formatter: function(cell) {
                    return '<div style="display: inline-flex; align-items: center; gap: 4px; background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #059669; padding: 6px 10px; border-radius: 16px; font-size: 11px; font-weight: 600; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">' +
                           '<span style="width: 6px; height: 6px; background: #10b981; border-radius: 50%; box-shadow: 0 0 4px #10b981;"></span>' +
                           '<span>활성</span>' +
                           '</div>';
                }
            },
            {
                title: "관리", 
                field: "actions", 
                width: 120, 
                hozAlign: "center",
                headerSort: false,
                formatter: function(cell) {
                    const rowData = cell.getRow().getData();
                    return '<div style="display: flex; gap: 6px; justify-content: center; align-items: center;">' +
                           '<button onclick="editUser(' + rowData.num + ')" title="사용자 편집" style="background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; border: none; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);" onmouseover="this.style.transform=\'scale(1.1)\'; this.style.boxShadow=\'0 4px 8px rgba(59, 130, 246, 0.4)\';" onmouseout="this.style.transform=\'scale(1)\'; this.style.boxShadow=\'0 2px 4px rgba(59, 130, 246, 0.3)\';">' +
                           '<i class="bi bi-pencil-square" style="font-size: 13px;"></i>' +
                           '</button>' +
                           '<button onclick="confirmDeleteUser(' + rowData.num + ', \'' + (rowData.name || '') + '\')" title="사용자 삭제" style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white; border: none; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);" onmouseover="this.style.transform=\'scale(1.1)\'; this.style.boxShadow=\'0 4px 8px rgba(239, 68, 68, 0.4)\';" onmouseout="this.style.transform=\'scale(1)\'; this.style.boxShadow=\'0 2px 4px rgba(239, 68, 68, 0.3)\';">' +
                           '<i class="bi bi-trash" style="font-size: 13px;"></i>' +
                           '</button>' +
                           '</div>';
                }
            }
        ],
        rowClick: function(e, row) {
            // 행 클릭 시 편집 (버튼이 아닌 경우만)
            if (!e.target.closest('button') && e.target.tagName !== 'BUTTON') {
                const rowData = row.getData();
                editUser(rowData.num);
            }
        },
        dataChanged: function(data) {
            updateTableInfo();
        },
        dataFiltered: function(filters, rows) {
            updateTableInfo();
        },
        tableBuilt: function() {
            // 테이블 초기화 완료 표시
            this.initialized = true;
            console.log('Tabulator 초기화 완료');
            // 초기화 완료 후 정보 업데이트
            setTimeout(() => {
                updateTableInfo();
            }, 100);
        }
    });
}

// Tabulator 필터링
function filterTable() {
    if (!userTable) return;
    
    const searchValue = $("#search").val();
    const branchFilter = $('[name="branch_filter"]').val();
    const levelFilter = $('[name="level_filter"]').val();
    
    // 필터 초기화
    userTable.clearFilter();
    
    // 검색어 필터
    if (searchValue) {
        userTable.addFilter([
            {field: "name", type: "like", value: searchValue},
            {field: "id", type: "like", value: searchValue}
        ], "or");
    }
    
    // 지점 필터
    if (branchFilter) {
        userTable.addFilter("branch", "=", branchFilter);
    }
    
    // 레벨 필터
    if (levelFilter) {
        userTable.addFilter("level", "=", levelFilter);
    }
    
    updateTableInfo();
}

// 테이블 정보 업데이트
function updateTableInfo() {
    if (!userTable || !userTable.initialized) return;
    
    try {
        const totalRows = userTable.getDataCount();
        const filteredRows = userTable.getDataCount("visible");
    
    // 총 사용자 수 업데이트
    const totalElement = document.getElementById('total_users_count');
    if (totalElement) {
        totalElement.textContent = totalRows;
    }
    
    // 테이블 정보 업데이트
    const infoElement = document.getElementById('table_info');
    if (infoElement) {
        if (filteredRows !== totalRows) {
            infoElement.textContent = `필터링된 결과: ${filteredRows}/${totalRows}행`;
        } else {
            infoElement.textContent = `${totalRows}행 표시 중`;
        }
    }
    
    // 빈 상태 확인
    const emptyState = document.getElementById('emptyState');
    if (emptyState) {
        if (filteredRows === 0) {
            emptyState.style.display = 'block';
            document.getElementById('user-table').style.display = 'none';
        } else {
            emptyState.style.display = 'none';
            document.getElementById('user-table').style.display = 'block';
        }
    }
    } catch (error) {
        console.warn('updateTableInfo error:', error);
    }
}

// 사용자 수 업데이트 (호환성을 위해 유지)
function updateUserCount() {
    updateTableInfo();
}

// 로딩 표시
function showLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'flex';
        isLoading = true;
    }
}

function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'none';
        isLoading = false;
    }
}

// 사용자 폼 열기
function openUserForm() {
    if (isLoading) return;
    
    const width = window.innerWidth > 768 ? 768 : window.innerWidth - 40;
    const height = window.innerHeight > 600 ? 900 : window.innerHeight - 100;
    
    popupCenter('./write_form_user.php', '새 사용자 등록', width, height);
}

// 사용자 편집
function editUser(num) {
    if (isLoading) return;
    
    const width = window.innerWidth > 768 ? 768 : window.innerWidth - 40;
    const height = window.innerHeight > 600 ? 900 : window.innerHeight - 100;
    
    popupCenter(`./write_form_user.php?num=${num}&mode=update`, '사용자 정보 수정', width, height);
}

// 사용자 삭제 확인
function confirmDeleteUser(num, name) {
    if (isLoading) return;
    
    Swal.fire({
        title: '사용자 삭제',
        html: `<strong>${name}</strong> 사용자를 정말 삭제하시겠습니까?<br><small class="text-muted">이 작업은 되돌릴 수 없습니다.</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '삭제',
        cancelButtonText: '취소',
        customClass: {
            confirmButton: 'jt-btn jt-btn--error',
            cancelButton: 'jt-btn jt-btn--ghost'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            deleteUser(num, name);
        }
    });
}

// 사용자 삭제 실행
async function deleteUser(num, name) {
    const result = await Swal.fire({
        title: '사용자 삭제',
        html: `<strong>${name}</strong> 사용자를 정말 삭제하시겠습니까?<br><small class="text-muted">이 작업은 되돌릴 수 없습니다.</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '삭제',
        cancelButtonText: '취소',
        customClass: {
            confirmButton: 'jt-btn jt-btn--danger',
            cancelButton: 'jt-btn jt-btn--secondary'
        },
        buttonsStyling: false
    });
    
    if (!result.isConfirmed) {
        return;
    }
    
    showLoading();
    
    try {
        const formData = new FormData();
        formData.append('num', num);

        const response = await fetch('delete_user.php', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        hideLoading();

        if (data.success) {
            Swal.fire({
                title: '삭제 완료',
                text: data.message,
                icon: 'success',
                customClass: {
                    confirmButton: 'jt-btn jt-btn--primary'
                },
                buttonsStyling: false
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                title: '삭제 실패',
                text: data.message || '사용자 삭제 중 오류가 발생했습니다.',
                icon: 'error',
                customClass: {
                    confirmButton: 'jt-btn jt-btn--primary'
                },
                buttonsStyling: false
            });
        }

    } catch (error) {
        console.error('Delete error:', error);
        hideLoading();
        
        let errorMessage = '사용자 삭제 중 오류가 발생했습니다.';
        
        if (error.name === 'TypeError' && error.message.includes('fetch')) {
            errorMessage = '네트워크 연결을 확인해주세요.';
        } else if (error.message.includes('HTTP error')) {
            errorMessage = '서버 오류가 발생했습니다. 관리자에게 문의해주세요.';
        }
        
        Swal.fire({
            title: '삭제 오류',
            text: errorMessage,
            icon: 'error',
            customClass: {
                confirmButton: 'jt-btn jt-btn--primary'
            },
            buttonsStyling: false
        });
    }
}

// 검색 초기화
function resetSearch() {
    document.getElementById('search').value = '';
    document.querySelector('[name="branch_filter"]').value = '';
    document.querySelector('[name="level_filter"]').value = '';
    
    // Tabulator 필터 초기화
    if (userTable) {
        userTable.clearFilter();
        updateTableInfo();
    }
}

// 사용자 목록 새로고침
function refreshUserList() {
    if (isLoading) return;
    
    showLoading();
    setTimeout(() => {
        location.reload();
    }, 500);
}

// Enter 키 검색
function SearchEnter() {
    if(event.keyCode == 13) {
        event.preventDefault();
        $("#searchBtn").click();
    }
}

// 페이지 이동
function movetoPage(page) { 	  
    if (isLoading) return;
    
    $("#page").val(page); 
    showLoading();
    $("#board_form").submit();  
}

// 팝업 센터
function popupCenter(href, pop_name, w, h) {
    const xPos = (window.innerWidth/2) - (w/2);
    const yPos = (window.innerHeight/2) - (h/2);
    
    const popup = window.open(href, pop_name, `width=${w}, height=${h}, left=${xPos}, top=${yPos}, menubar=no, status=yes, titlebar=yes, resizable=yes`);
    
    // 팝업 닫힘 감지하여 새로고침
    const checkClosed = setInterval(() => {
        if (popup.closed) {
            clearInterval(checkClosed);
            location.reload();
        }
    }, 1000);
}

// 페이지 언로드 시 로딩 숨기기
window.addEventListener('beforeunload', hideLoading);
window.addEventListener('load', hideLoading);

// 키보드 접근성
document.addEventListener('keydown', function(e) {
    // Escape 키로 로딩 취소
    if (e.key === 'Escape' && isLoading) {
        hideLoading();
    }
});
</script>

</body>
</html>