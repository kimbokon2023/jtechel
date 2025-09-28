$(document).ready(function() {
    // 초기 데이터 로드
    loadBranches();
    
    // 신규 지점 등록 버튼 클릭
    $('#btnAddBranch').click(function() {
        resetForm();
        $('#modalTitle').text('신규 지점 등록');
        $('#submitBtnText').text('등록');
        $('#mode').val('insert');
        $('#branchCode').prop('readonly', false);
        $('#branchModal').modal('show');
    });
    
    // 검색 버튼 클릭
    $('#btnSearch').click(function() {
        loadBranches(1);
    });
    
    // 초기화 버튼 클릭
    $('#btnReset').click(function() {
        $('#searchStatus').val('');
        $('#searchType').val('');
        $('#searchKeyword').val('');
        loadBranches(1);
    });
    
    // 엔터키로 검색
    $('#searchKeyword').keypress(function(e) {
        if(e.which === 13) {
            loadBranches(1);
        }
    });
    
    // 폼 제출 처리
    $('#branchForm').submit(function(e) {
        e.preventDefault();
        
        // 유효성 검사
        if(!validateForm()) {
            return false;
        }
        
        const formData = new FormData(this);
        const mode = $('#mode').val();
        
        $.ajax({
            url: 'branch_process.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if(response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '성공',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    $('#branchModal').modal('hide');
                    loadBranches();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: '오류',
                        text: response.message
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: '오류',
                    text: '처리 중 오류가 발생했습니다.'
                });
            }
        });
    });
});

// 지점 목록 로드
function loadBranches(page = 1) {
    const status = $('#searchStatus').val();
    const type = $('#searchType').val();
    const keyword = $('#searchKeyword').val();
    
    $.ajax({
        url: 'branch_process.php',
        type: 'GET',
        data: {
            mode: 'list',
            page: page,
            limit: 10,
            status: status,
            type: type,
            keyword: keyword
        },
        success: function(response) {
            if(response.success) {
                displayBranches(response.data);
                displayPagination(response.page, response.totalPages);
            } else {
                console.error('AJAX Error:', response);
                Swal.fire({
                    icon: 'error',
                    title: '데이터 로드 실패',
                    text: response.message || '데이터를 불러올 수 없습니다.'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Request Failed:', {
                status: xhr.status,
                statusText: xhr.statusText,
                responseText: xhr.responseText,
                error: error
            });
            
            if(xhr.status === 500) {
                Swal.fire({
                    icon: 'error',
                    title: '서버 오류',
                    html: '데이터베이스 테이블이 존재하지 않을 수 있습니다.<br><br><a href="create_branches_table.php" target="_blank" class="btn btn-primary">테이블 생성하기</a>',
                    showConfirmButton: true
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: '네트워크 오류',
                    text: `데이터 로드 중 오류가 발생했습니다. (Status: ${xhr.status})`
                });
            }
        }
    });
}

// 지점 목록 표시
function displayBranches(branches) {
    let html = ''; 
    
    if(branches.length === 0) {
        html = '<tr><td colspan="10" class="text-center py-4">등록된 지점이 없습니다.</td></tr>';
    } else {
        branches.forEach(function(branch, index) {
            const statusBadge = branch.status === 'active' 
                ? '<span class="badge bg-success">활성</span>' 
                : '<span class="badge bg-secondary">비활성</span>';
            
            const createdDate = branch.created_at ? branch.created_at.split(' ')[0] : '';
            
            html += `
                <tr>
                    <td class="text-center">${branch.sort_order || '-'}</td>
                    <td>${branch.branch_code}</td>
                    <td><strong>${branch.branch_name}</strong></td>
                    <td>${branch.branch_type || '-'}</td>
                    <td>${branch.manager_name || '-'}</td>
                    <td>${branch.phone || '-'}</td>
                    <td>${branch.email || '-'}</td>
                    <td class="text-center">${statusBadge}</td>
                    <td>${createdDate}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary" onclick="editBranch(${branch.id})" title="수정">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteBranch(${branch.id}, '${branch.branch_name}')" title="삭제">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
    }
    
    $('#branchTableBody').html(html);
}

// 페이지네이션 표시
function displayPagination(currentPage, totalPages) {
    let html = '';
    
    if(totalPages <= 1) {
        $('#pagination').html('');
        return;
    }
    
    // 이전 버튼
    html += `
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadBranches(${currentPage - 1}); return false;">이전</a>
        </li>
    `;
    
    // 페이지 번호
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, startPage + 4);
    
    for(let i = startPage; i <= endPage; i++) {
        html += `
            <li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" onclick="loadBranches(${i}); return false;">${i}</a>
            </li>
        `;
    }
    
    // 다음 버튼
    html += `
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadBranches(${currentPage + 1}); return false;">다음</a>
        </li>
    `;
    
    $('#pagination').html(html);
}

// 지점 수정
function editBranch(id) {
    $.ajax({
        url: 'branch_process.php',
        type: 'GET',
        data: {
            mode: 'get',
            id: id
        },
        success: function(response) {
            if(response.success) {
                const branch = response.data;
                
                $('#modalTitle').text('지점 정보 수정');
                $('#submitBtnText').text('수정');
                $('#mode').val('update');
                $('#branchId').val(branch.id);
                
                $('#branchCode').val(branch.branch_code).prop('readonly', true);
                $('#branchName').val(branch.branch_name);
                $('#branchType').val(branch.branch_type);
                $('#managerName').val(branch.manager_name);
                $('#phone').val(branch.phone);
                $('#fax').val(branch.fax);
                $('#email').val(branch.email);
                $('#zipCode').val(branch.zip_code);
                $('#address').val(branch.address);
                $('#detailAddress').val(branch.detail_address);
                $('#status').val(branch.status);
                $('#sortOrder').val(branch.sort_order);
                $('#note').val(branch.note);
                
                $('#branchModal').modal('show');
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: '오류',
                text: '지점 정보를 불러올 수 없습니다.'
            });
        }
    });
}

// 지점 삭제
function deleteBranch(id, branchName) {
    Swal.fire({
        title: '삭제 확인',
        text: `'${branchName}' 지점을 삭제하시겠습니까?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: '삭제',
        cancelButtonText: '취소'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'branch_process.php',
                type: 'POST',
                data: {
                    mode: 'delete',
                    id: id
                },
                success: function(response) {
                    if(response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '삭제 완료',
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        loadBranches();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: '오류',
                            text: response.message
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: '오류',
                        text: '삭제 처리 중 오류가 발생했습니다.'
                    });
                }
            });
        }
    });
}

// 폼 초기화
function resetForm() {
    $('#branchForm')[0].reset();
    $('#branchId').val('');
    $('#mode').val('insert');
    $('.is-invalid').removeClass('is-invalid');
}

// 폼 유효성 검사
function validateForm() {
    let isValid = true;
    
    // 필수 필드 검사
    const requiredFields = ['branchCode', 'branchName'];
    
    requiredFields.forEach(function(fieldId) {
        const field = $('#' + fieldId);
        if(field.val().trim() === '') {
            field.addClass('is-invalid');
            isValid = false;
        } else {
            field.removeClass('is-invalid');
        }
    });
    
    // 이메일 형식 검사
    const email = $('#email').val().trim();
    if(email && !isValidEmail(email)) {
        $('#email').addClass('is-invalid');
        isValid = false;
    }
    
    if(!isValid) {
        Swal.fire({
            icon: 'warning',
            title: '입력 오류',
            text: '필수 입력 항목을 확인해주세요.'
        });
    }
    
    return isValid;
}

// 이메일 유효성 검사
function isValidEmail(email) {
    const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return pattern.test(email);
}

// 우편번호 검색
function searchAddress() {
    new daum.Postcode({
        oncomplete: function(data) {
            // 우편번호와 주소 정보를 해당 필드에 넣는다.
            $('#zipCode').val(data.zonecode);
            
            // 도로명 주소 우선, 없으면 지번 주소
            let addr = data.userSelectedType === 'R' ? data.roadAddress : data.jibunAddress;
            
            // 건물명이 있으면 추가
            if(data.userSelectedType === 'R' && data.buildingName !== '') {
                addr += ' (' + data.buildingName + ')';
            }
            
            $('#address').val(addr);
            
            // 커서를 상세주소 필드로 이동
            $('#detailAddress').focus();
        }
    }).open(); 
} 

// 지점 선택용 옵션 로드 (다른 페이지에서 사용)
function loadBranchOptions(selectId, selectedValue = '') {
    $.ajax({
        url: 'branch_process.php',
        type: 'GET',
        data: {
            mode: 'select_options'
        },
        success: function(response) {
            if(response.success) {
                let html = '<option value="">지점 선택</option>';
                response.data.forEach(function(branch) {
                    const selected = branch.id == selectedValue ? 'selected' : '';
                    const typeText = branch.branch_type ? ` (${branch.branch_type})` : '';
                    html += `<option value="${branch.id}" ${selected}>${branch.branch_name}${typeText}</option>`;
                });
                $('#' + selectId).html(html);
            }
        }
    });
}