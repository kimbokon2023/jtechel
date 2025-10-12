-- 카 구조(car_structure) 컬럼 추가
-- 일반형: 5,6,7번 패널 있음 (기본 구조)
-- 관통형: 5,6,7번 패널 대신 앞면에도 1번, transom, 11번 있음

USE `jtechel`;

-- car_structure 컬럼 추가
ALTER TABLE `panel_measurements`
ADD COLUMN `car_structure` VARCHAR(20) NOT NULL DEFAULT '일반형' 
COMMENT '카 구조 (일반형/관통형)' 
AFTER `car_inside_height`;

-- 인덱스 추가 (검색 최적화)
ALTER TABLE `panel_measurements`
ADD KEY `idx_car_structure` (`car_structure`);

-- 결과 확인
SELECT 
    COLUMN_NAME, 
    COLUMN_TYPE, 
    IS_NULLABLE, 
    COLUMN_DEFAULT, 
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'jtechel' 
  AND TABLE_NAME = 'panel_measurements'
  AND COLUMN_NAME = 'car_structure';

