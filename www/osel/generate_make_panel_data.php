<?php
/**
 * 제작 사이즈가 적용된 패널 데이터 생성 함수
 *
 * @param array $original_panel_data 원본 panel_data (JSON 디코드된 배열)
 * @param array $production_settings 제작 설정 정보
 * @return array 제작 사이즈가 적용된 패널 데이터
 */
function generateMakePanelData($original_panel_data, $production_settings) {
    if (empty($original_panel_data) || !is_array($original_panel_data)) {
        return [];
    }

    $make_panel_data = [];

    // 제작 설정값 추출
    $production_height = intval($production_settings['production_height'] ?? 0);
    $production_height1_11 = intval($production_settings['production_height1_11'] ?? 0);
    $panel_corners_excluded = intval($production_settings['panel_corners_excluded'] ?? 0);
    $transom_excluded = intval($production_settings['transom_excluded'] ?? 0);
    $molding_included = intval($production_settings['molding_included'] ?? 0);

    error_log("=== GENERATE MAKE PANEL DATA ===");
    error_log("Production height (2-10): " . $production_height);
    error_log("Production height (1,11): " . $production_height1_11);
    error_log("Panel corners excluded: " . $panel_corners_excluded);
    error_log("Transom excluded: " . $transom_excluded);
    error_log("Molding included: " . $molding_included);
    error_log("Original panel count: " . count($original_panel_data));

    foreach ($original_panel_data as $panel_number => $panel_info) {
        // 1,11번 패널이 제외된 경우 건너뛰기
        if ($panel_corners_excluded && ($panel_number === '1' || $panel_number === '11')) {
            error_log("Skipping panel {$panel_number} (corners excluded)");
            continue;
        }

        // 트랜섬(12번)이 제외된 경우 건너뛰기
        if ($transom_excluded && $panel_number === '12') {
            error_log("Skipping panel {$panel_number} (transom excluded)");
            continue;
        }

        // 원본 데이터를 복사하여 시작
        $make_panel_info = $panel_info;

        // 제작 높이 적용
        $new_height = null;

        if ($panel_number === '1' || $panel_number === '11') {
            // 1,11번 패널: production_height1_11 우선, 없으면 production_height 사용
            if ($production_height1_11 > 0) {
                $new_height = $production_height1_11;
                error_log("Panel {$panel_number}: Using specific height {$production_height1_11}");
            } elseif ($production_height > 0) {
                $new_height = $production_height;
                error_log("Panel {$panel_number}: Using general height {$production_height}");
            }
        } elseif ($panel_number >= '2' && $panel_number <= '10') {
            // 2~10번 패널: production_height 사용
            if ($production_height > 0) {
                $new_height = $production_height;
                error_log("Panel {$panel_number}: Using production height {$production_height}");
            }
        }

        // 높이 적용
        if ($new_height !== null) {
            $original_height = $make_panel_info['height'] ?? 0;
            $make_panel_info['height'] = $new_height;
            $make_panel_info['production_height_applied'] = true;
            $make_panel_info['original_height'] = $original_height;

            error_log("Panel {$panel_number}: Height changed from {$original_height} to {$new_height}");
        }

        // 몰딩 적용 (필요시 구현)
        if ($molding_included) {
            $make_panel_info['molding_included'] = true;
            // 몰딩 적용으로 인한 치수 변경 로직이 필요하면 여기서 구현
        }

        // 제작 데이터임을 표시
        $make_panel_info['is_production_data'] = true;
        $make_panel_info['generated_at'] = date('Y-m-d H:i:s');

        $make_panel_data[$panel_number] = $make_panel_info;
    }

    error_log("Generated make panel count: " . count($make_panel_data));

    return $make_panel_data;
}

/**
 * 데이터베이스에서 원본 패널 데이터를 읽어와서 제작 패널 데이터를 생성하고 저장
 *
 * @param PDO $pdo 데이터베이스 연결
 * @param int $measurement_id 측정 ID
 * @param array $production_settings 제작 설정
 * @return bool 성공 여부
 */
function updateMakePanelDataInDB($pdo, $measurement_id, $production_settings) {
    try {
        // 원본 패널 데이터 조회
        $stmt = $pdo->prepare("SELECT panel_data FROM panel_measurements WHERE id = ?");
        $stmt->execute([$measurement_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result || empty($result['panel_data'])) {
            error_log("No original panel_data found for measurement_id: {$measurement_id}");
            return false;
        }

        $original_panel_data = json_decode($result['panel_data'], true);
        if (!$original_panel_data) {
            error_log("Failed to decode original panel_data for measurement_id: {$measurement_id}");
            return false;
        }

        // 제작 패널 데이터 생성
        $make_panel_data = generateMakePanelData($original_panel_data, $production_settings);

        if (empty($make_panel_data)) {
            error_log("Generated make_panel_data is empty for measurement_id: {$measurement_id}");
            // 빈 데이터라도 저장 (제외 옵션으로 인해 모든 패널이 제외될 수 있음)
        }

        // make_panel_data 저장
        $make_panel_json = json_encode($make_panel_data, JSON_UNESCAPED_UNICODE);
        $update_stmt = $pdo->prepare("UPDATE panel_measurements SET make_panel_data = ? WHERE id = ?");
        $result = $update_stmt->execute([$make_panel_json, $measurement_id]);

        if ($result) {
            error_log("Successfully updated make_panel_data for measurement_id: {$measurement_id}");
            error_log("Make panel data: " . $make_panel_json);
            return true;
        } else {
            error_log("Failed to update make_panel_data for measurement_id: {$measurement_id}");
            return false;
        }

    } catch (Exception $e) {
        error_log("Error updating make_panel_data: " . $e->getMessage());
        return false;
    }
}
?>