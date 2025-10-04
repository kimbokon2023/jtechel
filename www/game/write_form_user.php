<?php
// 환경별 기본 URL 설정
require_once '../config/environment.php';

session_start();

$root_dir = '..' ;

ini_set('display_errors','1');  // 화면에 warning 없애기	

 if(!isset($_SESSION["level"]) || $_SESSION["level"]>5) {
          /*   alert("관리자 승인이 필요합니다."); */
		 sleep(1);
         header("Location:" . getBaseUrl() . "/game/login/logout.php"); 
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
   

$level= $_SESSION["level"];
$user_name= $_SESSION["name"];
										  
isset($_REQUEST["num"])  ? $num=$_REQUEST["num"] :   $num=''; 
require_once("../lib/mydb.php");
$pdo = db_connect();

// 지점 헬퍼 함수 포함
require_once('branch_select_helper.php');

// 활성 지점 목록 가져오기
$activeBranches = getActiveBranches($pdo);	

 try{
	  $sql = "select * from jtechel.game_member where num = ? ";
	  $stmh = $pdo->prepare($sql); 
      $stmh->bindValue(1,$num,PDO::PARAM_STR); 
      $stmh->execute();
      $count = $stmh->rowCount();            
	  $row = $stmh->fetch(PDO::FETCH_ASSOC);  // $row 배열로 DB 정보를 불러온다.		
		 
		$num = $row["num"];	  
		$id = $row["id"];	  
		$pass = $row["pass"];	  
		$level = $row["level"];	  
		$name = $row["name"];		
		$branch = $row["branch"];		
	  
	 }catch (PDOException $Exception) {
	   print "오류: ".$Exception->getMessage();
	 }
 // end of if	

	
if($num=='')
{	
		$level = 4;	  

}
else // 값이 존재하면 수정모드
{
	$isEditMode = true; // 수정 모드 여부
	$inputvalues = explode(',', $input_arr);
	$outputvalues = explode(',', $output_arr);
	$input_arr = $inputvalues;
	$output_arr = $outputvalues;
	$mode="modify";
}

 
?>  

<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?=$num ? '사용자 정보 수정' : '새 사용자 등록'?> - J-TECH</title>

<!-- 최신 CDN 라이브러리 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.10/dist/sweetalert2.min.css" rel="stylesheet">
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script> 
<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

<!-- Bootstrap 5.3.0 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.1/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.10/dist/sweetalert2.all.min.js"></script>

<!-- 부가 애니메이션 라이브러리 -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
</head>
 
<body class="bg-light">

<style>
/* J-TECH 현대적 디자인 시스템 */
:root {
  /* 색상 팔레트 */
  --primary-color: #2563eb;
  --primary-dark: #1d4ed8;
  --primary-light: #60a5fa;
  --success-color: #10b981;
  --warning-color: #f59e0b;
  --error-color: #ef4444;
  --gray-50: #f8fafc;
  --gray-100: #f1f5f9;
  --gray-200: #e2e8f0;
  --gray-300: #cbd5e1;
  --gray-400: #94a3b8;
  --gray-500: #64748b;
  --gray-600: #475569;
  --gray-700: #334155;
  --gray-800: #1e293b;
  --gray-900: #0f172a;
  
  /* 그림자 */
  --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
  
  /* 폰트 */
  --font-family: "Malgun Gothic", "Apple SD Gothic Neo", "Noto Sans KR", sans-serif;
}

/* 배경 그라데이션 */
body {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  min-height: 100vh;
  font-family: var(--font-family);
}

/* 컨테이너 개선 */
.modern-container {
  max-width: 800px;
  margin: 0 auto;
  padding: 2rem 1rem;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* 모던 카드 */
.user-form-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 24px;
  box-shadow: var(--shadow-xl);
  border: 1px solid rgba(255, 255, 255, 0.2);
  overflow: hidden;
  width: 100%;
  max-width: 600px;
}

.card-header-modern {
  background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
  color: white;
  padding: 2rem;
  text-align: center;
  position: relative;
}

.card-header-modern::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>') repeat;
}

.card-title-modern {
  font-size: 2rem;
  font-weight: 700;
  margin: 0;
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
}

.card-body-modern {
  padding: 3rem 2rem;
}

/* 폼 그룹 현대화 */
.form-group-modern {
  margin-bottom: 2rem;
  position: relative;
}

.form-label-modern {
  font-weight: 600;
  color: var(--gray-700);
  margin-bottom: 0.75rem;
  display: block;
  font-size: 0.95rem;
}

.form-control-modern {
  width: 100%;
  padding: 1rem 1.25rem;
  font-size: 1rem;
  border: 2px solid var(--gray-300);
  border-radius: 12px;
  background: white;
  transition: all 0.3s ease;
  font-family: inherit;
}

.form-control-modern:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
  transform: translateY(-1px);
}

.form-control-modern:hover {
  border-color: var(--gray-400);
}

.form-control-error {
  border-color: var(--error-color) !important;
  animation: shake 0.5s ease-in-out;
}

.form-control-success {
  border-color: var(--success-color) !important;
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-5px); }
  75% { transform: translateX(5px); }
}

.form-error {
  color: var(--error-color);
  font-size: 0.875rem;
  margin-top: 0.5rem;
  display: none;
}

/* 버튼 시스템 */
.btn-modern {
  padding: 1rem 2rem;
  font-weight: 600;
  border-radius: 12px;
  border: none;
  font-size: 1rem;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
  min-width: 140px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  text-decoration: none;
  cursor: pointer;
}

.btn-modern:before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
  transition: left 0.5s;
}

.btn-modern:hover:before {
  left: 100%;
}

.btn-primary-modern {
  background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
  color: white;
  box-shadow: var(--shadow-md);
}

.btn-primary-modern:hover {
  background: linear-gradient(135deg, var(--primary-dark), #1e40af);
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
}

.btn-secondary-modern {
  background: var(--gray-100);
  color: var(--gray-700);
  border: 2px solid var(--gray-300);
}

.btn-secondary-modern:hover {
  background: var(--gray-200);
  border-color: var(--gray-400);
  transform: translateY(-1px);
}

.btn-danger-modern {
  background: linear-gradient(135deg, var(--error-color), #dc2626);
  color: white;
}

.btn-danger-modern:hover {
  background: linear-gradient(135deg, #dc2626, #b91c1c);
  transform: translateY(-2px);
}

/* 로딩 스피너 */
.loading-spinner {
  width: 20px;
  height: 20px;
  border: 2px solid transparent;
  border-top: 2px solid currentColor;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  display: none;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* 버튼 그룹 */
.btn-group-modern {
  display: flex;
  gap: 1rem;
  justify-content: center;
  margin-top: 2rem;
  flex-wrap: wrap;
}

/* 반응형 디자인 */
@media (max-width: 768px) {
  .modern-container {
    padding: 1rem 0.5rem;
  }
  
  .user-form-card {
    margin: 0.5rem;
    border-radius: 16px;
  }
  
  .card-body-modern {
    padding: 2rem 1.5rem;
  }
  
  .card-title-modern {
    font-size: 1.5rem;
  }
  
  .btn-group-modern {
    flex-direction: column;
  }
  
  .btn-modern {
    width: 100%;
    justify-content: center;
  }
}

/* 접근성 개선 */
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

/* 포커스 표시 개선 */
*:focus-visible {
  outline: 2px solid var(--primary-color);
  outline-offset: 2px;
}
</style>

<div class="modern-container">
    <div class="user-form-card">
        <div class="card-header-modern">
            <h1 class="card-title-modern">
                <i class="bi bi-person-<?=$num ? 'gear' : 'plus'?>"></i>
                <?=$num ? '사용자 정보 수정' : '새 사용자 등록'?>
            </h1>
        </div>
        
        <div class="card-body-modern">
            <form id="board_form" method="post">
                <?php if($num): ?>
                    <input type="hidden" id="num" name="num" value="<?=$num?>">
                    <input type="hidden" name="mode" value="modify">
                <?php endif; ?>
                
                <!-- 이름 -->
                <div class="form-group-modern">
                    <label for="name" class="form-label-modern">
                        <i class="bi bi-person"></i> 이름 *
                    </label>
                    <input type="text" id="name" name="name" 
                           class="form-control-modern" 
                           value="<?=htmlspecialchars($name ?? '')?>" 
                           placeholder="사용자 이름을 입력하세요" 
                           maxlength="50" required>
                    <div id="name-error" class="form-error"></div>
                </div>
                
                <!-- 지점 -->
                <div class="form-group-modern">
                    <label for="branch" class="form-label-modern">
                        <i class="bi bi-building"></i> 지점 *
                    </label>
                    <select id="branch" name="branch" class="form-control-modern" required>
                        <option value="">지점을 선택하세요</option>
                        <?php foreach($activeBranches as $branchData): ?>
                            <option value="<?=htmlspecialchars($branchData['branch_name'])?>" 
                                    <?=($branch == $branchData['branch_name']) ? 'selected' : ''?>>
                                <?=htmlspecialchars($branchData['branch_name'])?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="branch-error" class="form-error"></div>
                </div>
                
                <!-- 아이디 -->
                <div class="form-group-modern">
                    <label for="id" class="form-label-modern">
                        <i class="bi bi-at"></i> 아이디 *
                    </label>
                    <input type="text" id="id" name="id" 
                           class="form-control-modern" 
                           value="<?=htmlspecialchars($id ?? '')?>" 
                           placeholder="영문, 숫자, 밑줄만 사용 가능" 
                           maxlength="20" required>
                    <div id="id-error" class="form-error"></div>
                </div>
                
                <!-- 패스워드 -->
                <div class="form-group-modern">
                    <label for="pass" class="form-label-modern">
                        <i class="bi bi-lock"></i> 패스워드 *
                    </label>
                    <input type="password" id="pass" name="pass" 
                           class="form-control-modern" 
                           value="<?=htmlspecialchars($pass ?? '')?>" 
                           placeholder="최소 4자 이상 입력하세요" 
                           maxlength="100" required>
                    <div id="pass-error" class="form-error"></div>
                </div>
                
                <!-- 권한 레벨 -->
                <div class="form-group-modern">
                    <label for="level" class="form-label-modern">
                        <i class="bi bi-shield-check"></i> 권한 레벨 *
                    </label>
                    <select id="level" name="level" class="form-control-modern" required>
                        <option value="">권한 레벨을 선택하세요</option>
                        <option value="1" <?=($level == 1) ? 'selected' : ''?>>1 - 관리자</option>
                        <option value="4" <?=($level == 4) ? 'selected' : ''?>>4 - 일반사용자</option>
                    </select>
                    <div id="level-error" class="form-error"></div>
                </div>
                
                <!-- 버튼 그룹 -->
                <div class="btn-group-modern">
                    <button type="button" id="saveBtn" class="btn-modern btn-primary-modern">
                        <span class="loading-spinner"></span>
                        <span class="btn-text">
                            <i class="bi bi-check-lg"></i>
                            <?=$num ? '수정' : '등록'?>
                        </span>
                    </button>
                    
                    <?php if($num): ?>
                        <button type="button" id="delBtn" class="btn-modern btn-danger-modern">
                            <i class="bi bi-trash"></i>
                            삭제
                        </button>
                    <?php endif; ?>
                    
                    <button type="button" id="closeBtn" class="btn-modern btn-secondary-modern">
                        <i class="bi bi-x-lg"></i>
                        취소
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// jQuery 기반 사용자 폼 관리 시스템
$(document).ready(function() {
    // 전역 변수
    var isSubmitting = false;
    
    // 유효성 검사 함수들
    var validators = {
        name: function(value) {
            if (!value || value.trim().length < 2) {
                return '이름은 최소 2글자 이상이어야 합니다.';
            }
            if (value.length > 50) {
                return '이름은 50자를 초과할 수 없습니다.';
            }
            return null;
        },
        
        branch: function(value) {
            if (!value || value.trim() === '') {
                return '지점을 선택해주세요.';
            }
            return null;
        },
        
        id: function(value) {
            if (!value || value.length < 3) {
                return '아이디는 최소 3글자 이상이어야 합니다.';
            }
            if (value.length > 20) {
                return '아이디는 20자를 초과할 수 없습니다.';
            }
            if (!/^[a-zA-Z0-9_]+$/.test(value)) {
                return '아이디는 영문, 숫자, 밑줄(_)만 사용 가능합니다.';
            }
            return null;
        },
        
        pass: function(value) {
            if (!value || value.length < 4) {
                return '패스워드는 최소 4자 이상이어야 합니다.';
            }
            if (value.length > 100) {
                return '패스워드는 100자를 초과할 수 없습니다.';
            }
            return null;
        },
        
        level: function(value) {
            var levelNum = parseInt(value);
            if (!levelNum || (levelNum !== 1 && levelNum !== 4)) {
                return '올바른 권한 레벨을 선택해주세요. (1: 관리자, 4: 일반사용자)';
            }
            return null;
        }
    };
    
    // 초기화
    initUserForm();
    
    // 초기화 함수
    function initUserForm() {
        setupEventListeners();
        setupValidation();
        animateEntry();
        console.log('사용자 폼 관리 시스템이 초기화되었습니다.');
    }
    
    // 이벤트 리스너 설정
    function setupEventListeners() {
        // 저장 버튼
        $('#saveBtn').on('click', function(e) {
            e.preventDefault();
            handleSave();
        });
        
        // 삭제 버튼 (수정 모드에서만)
        $('#delBtn').on('click', function(e) {
            e.preventDefault();
            handleDelete();
        });
        
        // 닫기 버튼
        $('#closeBtn').on('click', function(e) {
            e.preventDefault();
            handleClose();
        }); 
        
        // 실시간 유효성 검사
        $.each(validators, function(fieldName, validator) {
            $('#' + fieldName).on('blur', function() {
                validateField(fieldName);
            }).on('input', function() {
                clearFieldError(fieldName);
            });
        });
        
        // 키보드 단축키
        $(document).on('keydown', function(e) {
            if (e.ctrlKey && e.which === 13) { // Ctrl + Enter
                e.preventDefault();
                handleSave();
            } else if (e.which === 27) { // Escape
                e.preventDefault();
                handleClose();
            }
        });
    }
    
    // 유효성 검사 설정
    function setupValidation() {
        // 아이디 필드 - 영문, 숫자, 밑줄만 허용
        $('#id').on('input', function() {
            this.value = this.value.replace(/[^a-zA-Z0-9_]/g, '');
        });
        
        // 레벨 필드 - 숫자만 허용
        var $levelField = $('#level');
        if ($levelField.attr('type') === 'text') {
            $levelField.on('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        }
    }
    
    // 애니메이션 효과
    function animateEntry() {
        $('.form-group-modern').each(function(index) {
            var $group = $(this);
            $group.css({
                'opacity': '0',
                'transform': 'translateY(20px)'
            });
            
            setTimeout(function() {
                $group.css({
                    'transition': 'all 0.3s ease',
                    'opacity': '1',
                    'transform': 'translateY(0)'
                });
            }, index * 100);
        });
    }
    
    // 유효성 검사 메서드들
    function validateField(fieldName) {
        var $field = $('#' + fieldName);
        if ($field.length === 0) return false;
        
        var value = $field.val();
        var validator = validators[fieldName];
        var error = validator ? validator(value) : null;
        
        showFieldError(fieldName, error);
        return !error;
    }
    
    function validateAllFields() {
        var isValid = true;
        
        $.each(validators, function(fieldName, validator) {
            if (!validateField(fieldName)) {
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    function showFieldError(fieldName, error) {
        var $field = $('#' + fieldName);
        var $errorDiv = $('#' + fieldName + '-error');
        
        if (error) {
            $field.addClass('form-control-error').removeClass('form-control-success');
            $errorDiv.text(error).show();
        } else {
            $field.removeClass('form-control-error').addClass('form-control-success');
            $errorDiv.hide();
        }
    }
    
    function clearFieldError(fieldName) {
        var $field = $('#' + fieldName);
        var $errorDiv = $('#' + fieldName + '-error');
        
        $field.removeClass('form-control-error');
        $errorDiv.hide();
    }
    
    function showLoading($button) {
        var $spinner = $button.find('.loading-spinner');
        var $text = $button.find('.btn-text');
        
        $button.prop('disabled', true);
        $spinner.show();
        $text.css('opacity', '0.7');
    }
    
    function hideLoading($button) {
        var $spinner = $button.find('.loading-spinner');
        var $text = $button.find('.btn-text');
        
        $button.prop('disabled', false);
        $spinner.hide();
        $text.css('opacity', '1');
    }
    
    // 저장 처리 함수
    function handleSave() {
        if (isSubmitting) return;
        
        var $saveBtn = $('#saveBtn');
        
        // 유효성 검사
        if (!validateAllFields()) {
            Swal.fire({
                title: '입력 오류',
                text: '모든 필드를 올바르게 입력해주세요.',
                icon: 'warning',
                customClass: {
                    confirmButton: 'btn-modern btn-primary-modern'
                },
                buttonsStyling: false
            });
            return;
        }
        
        isSubmitting = true;
        showLoading($saveBtn);
        
        // 폼 데이터 수집
        var formData = $('#board_form').serialize();
        var isEdit = $('#num').val() ? true : false;
        
        // 저장 확인
        Swal.fire({
            title: isEdit ? '사용자 정보 수정' : '새 사용자 등록',
            text: isEdit ? '사용자 정보를 수정하시겠습니까?' : '새 사용자를 등록하시겠습니까?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: isEdit ? '수정' : '등록',
            cancelButtonText: '취소',
            customClass: {
                confirmButton: 'btn-modern btn-primary-modern',
                cancelButton: 'btn-modern btn-secondary-modern'
            },
            buttonsStyling: false
        }).then(function(result) {
            if (result.isConfirmed) {
                submitForm(formData);
            } else {
                isSubmitting = false;
                hideLoading($saveBtn);
            }
        });
    }
    
    // 폼 전송 함수
    function submitForm(formData) {
        $.ajax({
            url: 'save_user.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            timeout: 10000,
            success: function(result) {
                if (result.success) {
                    Swal.fire({
                        title: '저장 완료',
                        text: result.message,
                        icon: 'success',
                        customClass: {
                            confirmButton: 'btn-modern btn-primary-modern'
                        },
                        buttonsStyling: false
                    }).then(function() {
                        if (window.opener) {
                            window.opener.location.reload();
                            window.close();
                        } else {
                            location.reload();
                        }
                    });
                } else {
                    // 구체적인 오류 정보 처리
                    var errorTitle = '오류 발생';
                    var errorMessage = result.message || '알 수 없는 오류가 발생했습니다.';
                    var debugInfo = '';
                    
                    // 데이터베이스 오류인지 확인
                    if (result.error && result.error_code) {
                        errorTitle = '데이터베이스 오류 (코드: ' + result.error_code + ')';
                        errorMessage = result.error;
                    }
                    
                    // 디버그 정보 추가
                    if (result.debug_info) {
                        debugInfo += '<hr style="margin: 15px 0;">';
                        debugInfo += '<small><strong>디버그 정보:</strong><br>';
                        debugInfo += '파일: ' + result.debug_info.file + '<br>';
                        debugInfo += '라인: ' + result.debug_info.line + '<br>';
                        
                        if (result.debug_info.sql_state) {
                            debugInfo += 'SQL 상태: ' + result.debug_info.sql_state + '<br>';
                        }
                        
                        if (result.debug_info.received_data) {
                            debugInfo += '<br><strong>전송된 데이터:</strong><br>';
                            $.each(result.debug_info.received_data, function(key, value) {
                                debugInfo += key + ': ' + value + '<br>';
                            });
                        }
                        debugInfo += '</small>';
                    }
                    
                    // 유효성 검사 오류인 경우
                    if (result.errors && $.isArray(result.errors)) {
                        Swal.fire({
                            title: result.message || '입력 오류',
                            html: result.errors.join('<br>') + debugInfo,
                            icon: 'warning',
                            customClass: {
                                confirmButton: 'btn-modern btn-primary-modern'
                            },
                            buttonsStyling: false,
                            width: debugInfo ? '600px' : '400px'
                        });
                    } else {
                        // 기타 오류
                        Swal.fire({
                            title: errorTitle,
                            html: errorMessage + debugInfo,
                            icon: 'error',
                            customClass: {
                                confirmButton: 'btn-modern btn-primary-modern'
                            },
                            buttonsStyling: false,
                            width: debugInfo ? '600px' : '400px'
                        });
                    }
                    
                    // 콘솔에도 상세 오류 출력
                    console.error('서버 오류:', result);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {
                    status: status,
                    error: error,
                    statusCode: xhr.status,
                    responseText: xhr.responseText,
                    statusText: xhr.statusText
                });
                
                var errorMessage = '서버와의 통신 중 오류가 발생했습니다.';
                var debugInfo = '';
                
                // 상태별 오류 메시지
                if (status === 'timeout') {
                    errorMessage = '요청 시간이 초과되었습니다. 다시 시도해주세요.';
                } else if (status === 'error') {
                    if (xhr.status === 500) {
                        errorMessage = '서버 내부 오류가 발생했습니다.';
                    } else if (xhr.status === 404) {
                        errorMessage = '요청한 페이지를 찾을 수 없습니다.';
                    } else if (xhr.status === 403) {
                        errorMessage = '접근 권한이 없습니다.';
                    } else {
                        errorMessage = '서버 오류가 발생했습니다. (HTTP ' + xhr.status + ')';
                    }
                } else if (status === 'parsererror') {
                    errorMessage = '서버 응답을 처리하는 중 오류가 발생했습니다.';
                }
                
                // 디버그 정보 추가 (응답 텍스트가 있는 경우)
                if (xhr.responseText && xhr.responseText.length > 0) {
                    debugInfo += '<hr style="margin: 15px 0;">';
                    debugInfo += '<small><strong>디버그 정보:</strong><br>';
                    debugInfo += 'HTTP 상태: ' + xhr.status + ' ' + xhr.statusText + '<br>';
                    debugInfo += 'AJAX 상태: ' + status + '<br>';
                    
                    // JSON 파싱 시도
                    try {
                        var responseData = JSON.parse(xhr.responseText);
                        debugInfo += '서버 응답: ' + JSON.stringify(responseData, null, 2) + '<br>';
                    } catch (e) {
                        // 텍스트가 너무 길면 자르기
                        var responsePreview = xhr.responseText.length > 300 ? 
                            xhr.responseText.substring(0, 300) + '...' : 
                            xhr.responseText;
                        debugInfo += '서버 응답 (원본): ' + responsePreview + '<br>';
                    }
                    debugInfo += '</small>';
                }
                
                Swal.fire({
                    title: '통신 오류 (' + xhr.status + ')',
                    html: errorMessage + debugInfo,
                    icon: 'error',
                    customClass: {
                        confirmButton: 'btn-modern btn-primary-modern'
                    },
                    buttonsStyling: false,
                    width: debugInfo ? '600px' : '400px'
                });
            },
            complete: function() {
                isSubmitting = false;
                hideLoading($('#saveBtn'));
            }
        });
    }
    
    // 삭제 처리 함수
    function handleDelete() {
        var userName = $('#name').val();
        var userNum = $('#num').val();
        
        if (!userNum) {
            Swal.fire({
                title: '오류',
                text: '삭제할 사용자 정보를 찾을 수 없습니다.',
                icon: 'error',
                customClass: {
                    confirmButton: 'btn-modern btn-primary-modern'
                },
                buttonsStyling: false
            });
            return;
        }
        
        Swal.fire({
            title: '사용자 삭제',
            html: '<strong>' + userName + '</strong> 사용자를 정말 삭제하시겠습니까?<br><small class="text-muted">이 작업은 되돌릴 수 없습니다.</small>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '삭제',
            cancelButtonText: '취소',
            customClass: {
                confirmButton: 'btn-modern',
                cancelButton: 'btn-modern btn-secondary-modern'
            },
            confirmButtonColor: '#ef4444',
            buttonsStyling: false
        }).then(function(result) {
            if (result.isConfirmed) {
                // 삭제 중 로딩 표시
                Swal.fire({
                    title: '삭제 중...',
                    text: '사용자를 삭제하고 있습니다.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: function() {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: 'delete_user.php',
                    type: 'POST',
                    data: { num: userNum },
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            Swal.fire({
                                title: '삭제 완료',
                                text: data.message,
                                icon: 'success',
                                customClass: {
                                    confirmButton: 'btn-modern btn-primary-modern'
                                },
                                buttonsStyling: false
                            }).then(function() {
                                if (window.opener) {
                                    window.opener.location.reload();
                                    window.close();
                                } else {
                                    history.back();
                                }
                            });
                        } else {
                            Swal.fire({
                                title: '삭제 실패',
                                text: data.message || '사용자 삭제 중 오류가 발생했습니다.',
                                icon: 'error',
                                customClass: {
                                    confirmButton: 'btn-modern btn-primary-modern'
                                },
                                buttonsStyling: false
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Delete error:', status, error);
                        
                        Swal.fire({
                            title: '삭제 오류',
                            text: '사용자 삭제 중 오류가 발생했습니다.',
                            icon: 'error',
                            customClass: {
                                confirmButton: 'btn-modern btn-primary-modern'
                            },
                            buttonsStyling: false
                        });
                    }
                });
            }
        });
    }
    
    // 닫기 처리 함수
    function handleClose() {
        if (window.opener) {
            window.close();
        } else {
            history.back();
        }
    }

});
</script>

</body>
</html>