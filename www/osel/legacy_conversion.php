<?php
// 레거시 panel_layout을 새로운 체크박스 시스템으로 변환하는 함수들

/**
 * 레거시 panel_layout 값을 새로운 체크박스 값으로 변환
 *
 * @param string $panel_layout 레거시 panel_layout 값
 * @return array ['panel_corners_excluded' => int, 'transom_excluded' => int]
 */
function convertLegacyPanelLayout($panel_layout) {
    $panel_corners_excluded = 1; // 기본값: 1,11번 제외
    $transom_excluded = 0;       // 기본값: 트랜섬 포함

    switch ($panel_layout) {
        case 'with_corner_panels':
            $panel_corners_excluded = 0; // 1,11번 포함
            break;
        case 'standard':
        default:
            $panel_corners_excluded = 1; // 1,11번 제외
            break;
    }

    return [
        'panel_corners_excluded' => $panel_corners_excluded,
        'transom_excluded' => $transom_excluded
    ];
}

/**
 * 새로운 체크박스 값을 레거시 panel_layout으로 변환 (호환성용)
 *
 * @param int $panel_corners_excluded 1,11번 제외 여부 (0=포함, 1=제외)
 * @param int $transom_excluded 트랜섬 제외 여부 (0=포함, 1=제외)
 * @return string 레거시 panel_layout 값
 */
function convertToLegacyPanelLayout($panel_corners_excluded, $transom_excluded) {
    // 트랜섬 제외 상태는 레거시 panel_layout에는 반영되지 않음 (새로운 기능)
    if ($panel_corners_excluded == 0) {
        return 'with_corner_panels'; // 1,11번 포함
    } else {
        return 'standard';           // 1,11번 제외
    }
}

/**
 * 데이터베이스에서 편집할 데이터를 가져올 때 레거시 변환
 *
 * @param array $edit_data 데이터베이스에서 가져온 편집 데이터
 * @return array 변환된 데이터
 */
function processEditDataForCheckboxSystem($edit_data) {
    // 새로운 컬럼이 있으면 우선 사용
    if (isset($edit_data['panel_corners_excluded']) && isset($edit_data['transom_excluded'])) {
        return [
            'panel_corners_excluded' => intval($edit_data['panel_corners_excluded']),
            'transom_excluded' => intval($edit_data['transom_excluded'])
        ];
    }

    // 레거시 panel_layout에서 변환
    if (isset($edit_data['panel_layout'])) {
        return convertLegacyPanelLayout($edit_data['panel_layout']);
    }

    // 기본값
    return [
        'panel_corners_excluded' => 1, // 기본: 1,11번 제외
        'transom_excluded' => 0        // 기본: 트랜섬 포함
    ];
}

// 사용 예시:
/*
// 편집 모드에서 사용
$checkbox_values = processEditDataForCheckboxSystem($edit_data);
$defaultPanelCornersExcluded = $checkbox_values['panel_corners_excluded'];
$defaultTransomExcluded = $checkbox_values['transom_excluded'];

// 저장 시 레거시 호환성 유지
$legacy_panel_layout = convertToLegacyPanelLayout($panel_corners_excluded, $transom_excluded);
*/
?>