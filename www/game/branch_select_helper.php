<?php
/**
 * 지점 선택 헬퍼 함수들
 * branches 테이블 기반으로 지점 관련 기능 제공
 */

// 첫 번째 활성 지점명 가져오기
function getFirstActiveBranch($pdo) {
    try {
        $sql = "SELECT branch_name FROM jtechel.branches WHERE status = 'active' ORDER BY sort_order ASC, branch_name ASC LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['branch_name'] : '서울본사';
    } catch (PDOException $e) {
        return '아우디';
    }
} 

// 활성 지점 목록 가져오기
function getActiveBranches($pdo) {
    try {
        $sql = "SELECT branch_code, branch_name FROM jtechel.branches WHERE status = 'active' ORDER BY sort_order ASC, branch_name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // 에러 시 기본값 반환
        return [
            ['branch_code' => 'DEFAULT', 'branch_name' => '아우디'],
            ['branch_code' => 'DEFAULT2', 'branch_name' => '빅']
        ];
    }
}

// 지점 선택 select HTML 생성
function renderBranchSelect($pdo, $selectedBranch = '', $includeAll = false, $level = 5) {
    $branches = getActiveBranches($pdo);
    $defaultBranch = getFirstActiveBranch($pdo);
    
    // 선택된 지점이 없으면 첫 번째 지점을 기본값으로
    if (empty($selectedBranch)) {
        $selectedBranch = $defaultBranch;
    }
    
    $html = '';
    
    // 전체 옵션 추가 (관리자만)
    if ($includeAll && $level <= 3) {
        $selected = ($selectedBranch == '전체') ? 'selected' : '';
        $html .= "<option value='전체' $selected>전체</option>";
    }
    
    // 지점 목록 출력
    foreach ($branches as $branch) {
        $selected = ($selectedBranch == $branch['branch_name']) ? 'selected' : '';
        $html .= "<option value='{$branch['branch_name']}' $selected>{$branch['branch_name']}</option>";
    }
    
    return $html;
}

// 쿠키에서 지점 가져오기 (없으면 첫 번째 지점 반환)
function getBranchFromCookie($pdo) {
    if (isset($_COOKIE['branch']) && !empty($_COOKIE['branch'])) {
        return $_COOKIE['branch'];
    }
    return getFirstActiveBranch($pdo);
}
?>