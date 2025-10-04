-- 현장 그룹 관리 테이블 생성 SQL
-- 현장들을 그룹으로 묶어서 관리하기 위한 테이블

-- --------------------------------------------------------

--
-- 테이블 구조 `site_groups`
--

CREATE TABLE `site_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '그룹 고유 ID',
  `group_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '그룹명',
  `group_description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '그룹 설명',
  `created_by` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '그룹 생성자 ID',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '최종 수정일시',
  `is_deleted` tinyint(1) DEFAULT '0' COMMENT '삭제 여부 (0: 활성, 1: 삭제됨)',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '삭제일시',
  `deleted_by` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '삭제자 ID',
  PRIMARY KEY (`id`),
  KEY `idx_group_name` (`group_name`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_is_deleted` (`is_deleted`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='현장 그룹 관리 테이블';

-- --------------------------------------------------------

--
-- 테이블 구조 `site_group_members`
--

CREATE TABLE `site_group_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '멤버십 고유 ID',
  `group_id` int(11) NOT NULL COMMENT '그룹 ID (site_groups.id 참조)',
  `measurement_id` int(11) NOT NULL COMMENT '측정 ID (panel_measurements.id 참조)',
  `added_by` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '그룹에 추가한 사용자 ID',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '그룹 추가일시',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '최종 수정일시',
  `is_deleted` tinyint(1) DEFAULT '0' COMMENT '삭제 여부 (0: 활성, 1: 삭제됨)',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '삭제일시',
  `deleted_by` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '삭제자 ID',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_group_measurement` (`group_id`, `measurement_id`),
  KEY `idx_group_id` (`group_id`),
  KEY `idx_measurement_id` (`measurement_id`),
  KEY `idx_added_by` (`added_by`),
  KEY `idx_is_deleted` (`is_deleted`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_site_group_members_group` FOREIGN KEY (`group_id`) REFERENCES `site_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_site_group_members_measurement` FOREIGN KEY (`measurement_id`) REFERENCES `panel_measurements` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='현장 그룹 멤버십 테이블';

-- --------------------------------------------------------

--
-- 샘플 데이터 삽입 (선택사항)
--

-- 샘플 그룹 데이터
INSERT INTO `site_groups` (`group_name`, `group_description`, `created_by`) VALUES
('2024년 1분기 프로젝트', '2024년 1분기에 진행된 모든 현장', 'admin'),
('아파트 신축 현장', '아파트 신축 관련 현장들', 'admin'),
('상업용 빌딩 현장', '상업용 빌딩 관련 현장들', 'admin'),
('리모델링 현장', '기존 건물 리모델링 현장들', 'admin');

-- --------------------------------------------------------

--
-- 유용한 뷰 생성 (선택사항)
--

-- 그룹별 현장 목록을 보여주는 뷰
CREATE VIEW `v_site_groups_with_members` AS
SELECT 
    sg.id as group_id,
    sg.group_name,
    sg.group_description,
    sg.created_by as group_created_by,
    sg.created_at as group_created_at,
    sg.updated_at as group_updated_at,
    COUNT(sgm.measurement_id) as member_count,
    GROUP_CONCAT(pm.site_name ORDER BY pm.site_name SEPARATOR ', ') as site_names
FROM site_groups sg
LEFT JOIN site_group_members sgm ON sg.id = sgm.group_id AND sgm.is_deleted = 0
LEFT JOIN panel_measurements pm ON sgm.measurement_id = pm.id
WHERE sg.is_deleted = 0
GROUP BY sg.id, sg.group_name, sg.group_description, sg.created_by, sg.created_at, sg.updated_at;

-- 현장별 그룹 정보를 보여주는 뷰
CREATE VIEW `v_measurements_with_groups` AS
SELECT 
    pm.id as measurement_id,
    pm.site_name,
    pm.measurement_date,
    pm.measurer_name,
    pm.car_inside_width,
    pm.car_inside_depth,
    pm.car_inside_height,
    pm.created_at,
    pm.updated_at,
    GROUP_CONCAT(sg.group_name ORDER BY sg.group_name SEPARATOR ', ') as group_names,
    GROUP_CONCAT(sg.id ORDER BY sg.id SEPARATOR ', ') as group_ids
FROM panel_measurements pm
LEFT JOIN site_group_members sgm ON pm.id = sgm.measurement_id AND sgm.is_deleted = 0
LEFT JOIN site_groups sg ON sgm.group_id = sg.id AND sg.is_deleted = 0
GROUP BY pm.id, pm.site_name, pm.measurement_date, pm.measurer_name, 
         pm.car_inside_width, pm.car_inside_depth, pm.car_inside_height, 
         pm.created_at, pm.updated_at;

-- --------------------------------------------------------

--
-- 유용한 저장 프로시저 (선택사항)
--

DELIMITER $$

-- 현장을 그룹에 추가하는 프로시저
CREATE PROCEDURE `sp_add_measurement_to_group`(
    IN p_group_id INT,
    IN p_measurement_id INT,
    IN p_added_by VARCHAR(50)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- 이미 그룹에 속해있는지 확인
    IF EXISTS (
        SELECT 1 FROM site_group_members 
        WHERE group_id = p_group_id 
        AND measurement_id = p_measurement_id 
        AND is_deleted = 0
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '이미 해당 그룹에 속해있는 현장입니다.';
    END IF;
    
    -- 그룹에 현장 추가
    INSERT INTO site_group_members (group_id, measurement_id, added_by)
    VALUES (p_group_id, p_measurement_id, p_added_by);
    
    COMMIT;
END$$

-- 현장을 그룹에서 제거하는 프로시저
CREATE PROCEDURE `sp_remove_measurement_from_group`(
    IN p_group_id INT,
    IN p_measurement_id INT,
    IN p_deleted_by VARCHAR(50)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- 그룹에서 현장 제거 (소프트 삭제)
    UPDATE site_group_members 
    SET is_deleted = 1, 
        deleted_at = CURRENT_TIMESTAMP, 
        deleted_by = p_deleted_by
    WHERE group_id = p_group_id 
    AND measurement_id = p_measurement_id 
    AND is_deleted = 0;
    
    COMMIT;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- 인덱스 최적화를 위한 추가 인덱스
--

-- 복합 인덱스 추가
CREATE INDEX `idx_site_groups_name_deleted` ON `site_groups` (`group_name`, `is_deleted`);
CREATE INDEX `idx_site_group_members_group_deleted` ON `site_group_members` (`group_id`, `is_deleted`);
CREATE INDEX `idx_site_group_members_measurement_deleted` ON `site_group_members` (`measurement_id`, `is_deleted`);

-- --------------------------------------------------------

--
-- 테이블 코멘트 및 설명
--

-- site_groups: 현장 그룹의 기본 정보를 저장
-- - 각 그룹은 고유한 ID와 이름을 가짐
-- - 그룹 설명, 생성자, 생성/수정 시간 등 메타데이터 포함
-- - 소프트 삭제 지원 (is_deleted 플래그)

-- site_group_members: 현장과 그룹 간의 관계를 저장
-- - 하나의 현장은 여러 그룹에 속할 수 있음
-- - 하나의 그룹은 여러 현장을 포함할 수 있음
-- - 다대다 관계를 구현
-- - 소프트 삭제 지원

-- 사용 예시:
-- 1. 새 그룹 생성: INSERT INTO site_groups (group_name, group_description, created_by) VALUES ('2024년 프로젝트', '설명', 'admin');
-- 2. 현장을 그룹에 추가: CALL sp_add_measurement_to_group(1, 123, 'admin');
-- 3. 그룹별 현장 목록 조회: SELECT * FROM v_site_groups_with_members WHERE group_id = 1;
-- 4. 현장별 그룹 정보 조회: SELECT * FROM v_measurements_with_groups WHERE measurement_id = 123;
