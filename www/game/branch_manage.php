<?php
// 환경별 기본 URL 설정
require_once '../config/environment.php';

session_start();
header('Content-Type: text/html; charset=utf-8');

// 권한 체크
if(!isset($_SESSION["level"]) || $_SESSION["level"] > 5) {
    sleep(1);
    header("Location:" . getBaseUrl() . "/login/login_form.php");
    exit;
}

require_once("../lib/mydb.php");
$pdo = db_connect();
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>지점 관리 </title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/branch_manage.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
</head>
<body>
    <?php include 'myheader.php'; ?>

    <div class="container" >
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0"><i class="bi bi-building"></i> 지점 관리</h4>
                            <button type="button" class="btn btn-light" id="btnAddBranch">
                                <i class="bi bi-plus-circle"></i> 신규 지점 등록
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- 검색 영역 -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <select class="form-select" id="searchStatus">
                                    <option value="">전체 상태</option>
                                    <option value="active">활성</option>
                                    <option value="inactive">비활성</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="searchType">
                                    <option value="">전체 유형</option>
                                    <option value="본사">본사</option>
                                    <option value="지점">지점</option>
                                    <option value="영업소">영업소</option>
                                    <option value="대리점">대리점</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="searchKeyword" placeholder="지점명, 지점코드, 지점장명 검색">
                                    <button class="btn btn-outline-secondary" type="button" id="btnSearch">
                                        <i class="bi bi-search"></i> 검색
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-outline-primary w-100" type="button" id="btnReset">
                                    <i class="bi bi-arrow-clockwise"></i> 초기화
                                </button>
                            </div>
                        </div>

                        <!-- 지점 목록 테이블 -->
                        <div class="table-responsive">
                            <table id="branchTable" class="table table-hover table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="5%">순서</th>
                                        <th width="10%">지점코드</th>
                                        <th width="15%">지점명</th>
                                        <th width="8%">유형</th>
                                        <th width="10%">지점장</th>
                                        <th width="12%">전화번호</th>
                                        <th width="15%">이메일</th>
                                        <th width="8%">상태</th>
                                        <th width="10%">등록일</th>
                                        <th width="7%">관리</th>
                                    </tr>
                                </thead>
                                <tbody id="branchTableBody">
                                    <!-- 데이터는 AJAX로 로드 -->
                                </tbody>
                            </table>
                        </div>

                        <!-- 페이지네이션 -->
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center" id="pagination">
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 지점 등록/수정 모달 -->
    <div class="modal fade" id="branchModal" tabindex="-1" aria-labelledby="branchModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="branchModalLabel">
                        <i class="bi bi-building"></i> <span id="modalTitle">신규 지점 등록</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="branchForm">
                    <div class="modal-body">
                        <input type="hidden" id="branchId" name="id">
                        <input type="hidden" id="mode" name="mode" value="insert">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="branchCode" class="form-label">지점코드 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="branchCode" name="branch_code" required maxlength="20" placeholder="예: BR001">
                            </div>
                            <div class="col-md-6">
                                <label for="branchName" class="form-label">지점명 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="branchName" name="branch_name" required maxlength="100" placeholder="예: 서울본사">
                            </div>
                            
                            <div class="col-md-4">
                                <label for="branchType" class="form-label">지점유형</label>
                                <select class="form-select" id="branchType" name="branch_type">
                                    <option value="">선택</option>
                                    <option value="본사">본사</option>
                                    <option value="지점">지점</option>
                                    <option value="영업소">영업소</option>
                                    <option value="대리점">대리점</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="managerName" class="form-label">지점장명</label>
                                <input type="text" class="form-control" id="managerName" name="manager_name" maxlength="50">
                            </div>
                            <div class="col-md-4">
                                <label for="sortOrder" class="form-label">정렬순서</label>
                                <input type="number" class="form-control" id="sortOrder" name="sort_order" min="0" value="0">
                            </div>
                            
                            <div class="col-md-4">
                                <label for="phone" class="form-label">전화번호</label>
                                <input type="text" class="form-control" id="phone" name="phone" maxlength="20" placeholder="예: 02-1234-5678">
                            </div>
                            <div class="col-md-4">
                                <label for="fax" class="form-label">팩스번호</label>
                                <input type="text" class="form-control" id="fax" name="fax" maxlength="20" placeholder="예: 02-1234-5679">
                            </div>
                            <div class="col-md-4">
                                <label for="email" class="form-label">이메일</label>
                                <input type="email" class="form-control" id="email" name="email" maxlength="100" placeholder="예: branch@company.com">
                            </div>
                            
                            <div class="col-md-3">
                                <label for="zipCode" class="form-label">우편번호</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="zipCode" name="zip_code" maxlength="10" readonly>
                                    <button class="btn btn-outline-secondary" type="button" onclick="searchAddress()">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-9">
                                <label for="address" class="form-label">주소</label>
                                <input type="text" class="form-control" id="address" name="address" maxlength="200" readonly>
                            </div>
                            
                            <div class="col-12">
                                <label for="detailAddress" class="form-label">상세주소</label>
                                <input type="text" class="form-control" id="detailAddress" name="detail_address" maxlength="100" placeholder="상세주소를 입력하세요">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="status" class="form-label">상태</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active">활성</option>
                                    <option value="inactive">비활성</option>
                                </select>
                            </div>
                            
                            <div class="col-12">
                                <label for="note" class="form-label">비고</label>
                                <textarea class="form-control" id="note" name="note" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> 취소
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> <span id="submitBtnText">등록</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
 
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <!-- Daum 우편번호 API -->
    <script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
    <!-- Custom JS -->
    <script src="../game/branch_manage.js"></script>
</body>
</html>