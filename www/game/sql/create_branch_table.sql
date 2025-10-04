-- 지점 관리 테이블 생성
CREATE TABLE IF NOT EXISTS `branches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `branch_code` varchar(20) NOT NULL UNIQUE COMMENT '지점코드',
  `branch_name` varchar(100) NOT NULL COMMENT '지점명',
  `branch_type` varchar(50) DEFAULT NULL COMMENT '지점유형',
  `manager_name` varchar(50) DEFAULT NULL COMMENT '지점장명',
  `phone` varchar(20) DEFAULT NULL COMMENT '전화번호',
  `fax` varchar(20) DEFAULT NULL COMMENT '팩스번호',
  `email` varchar(100) DEFAULT NULL COMMENT '이메일',
  `address` varchar(200) DEFAULT NULL COMMENT '주소',
  `detail_address` varchar(100) DEFAULT NULL COMMENT '상세주소',
  `zip_code` varchar(10) DEFAULT NULL COMMENT '우편번호',
  `status` enum('active','inactive') DEFAULT 'active' COMMENT '상태',
  `sort_order` int(11) DEFAULT 0 COMMENT '정렬순서',
  `note` text DEFAULT NULL COMMENT '비고',
  `created_by` varchar(50) DEFAULT NULL COMMENT '등록자',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '등록일시',
  `updated_by` varchar(50) DEFAULT NULL COMMENT '수정자',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
  PRIMARY KEY (`id`),
  KEY `idx_branch_code` (`branch_code`),
  KEY `idx_branch_name` (`branch_name`),
  KEY `idx_status` (`status`),
  KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='지점 관리 테이블';

-- 초기 데이터 삽입 (예시)
INSERT INTO `branches` (`branch_code`, `branch_name`, `branch_type`, `manager_name`, `phone`, `status`, `sort_order`) VALUES
('BR001', '서울본사', '본사', '홍길동', '02-1234-5678', 'active', 1),
('BR002', '부산지점', '지점', '김철수', '051-1234-5678', 'active', 2),
('BR003', '대구지점', '지점', '이영희', '053-1234-5678', 'active', 3),
('BR004', '인천지점', '지점', '박민수', '032-1234-5678', 'active', 4),
('BR005', '광주지점', '지점', '최정호', '062-1234-5678', 'active', 5),
('BR006', '대전지점', '지점', '정미라', '042-1234-5678', 'active', 6);