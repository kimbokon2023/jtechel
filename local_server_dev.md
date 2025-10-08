# 로컬/서버 환경 구분 개발 시스템 기술문서

## 개요

이 문서는 로컬 개발 환경과 서버 운영 환경을 자동으로 구분하여 각각의 환경에 맞는 설정으로 동작하는 PHP 웹 애플리케이션 시스템의 구현 방법과 필수 구성 요소에 대해 설명합니다.

## 시스템 아키텍처

### 핵심 개념
- **환경 자동 감지**: HTTP_HOST를 기반으로 로컬/서버 환경을 자동으로 구분
- **환경별 설정 분리**: 데이터베이스, URL, 경로 등 환경별로 다른 설정 적용
- **통합 함수 제공**: 환경에 관계없이 일관된 인터페이스 제공

## 필수 폴더 구조

```
프로젝트 루트/
├── config/
│   └── environment.php          # 환경 설정 핵심 파일
├── common/
│   ├── functions.php            # 공통 함수 모음
│   └── modal.php               # 공통 모달 컴포넌트
├── lib/
│   └── mydb.php                # 데이터베이스 연결 함수
├── login/
│   ├── login_form.php          # 로그인 폼
│   ├── login_result.php        # 로그인 처리
│   └── logout.php              # 로그아웃 처리
├── js/
│   └── [각종 JavaScript 파일들]
├── css/
│   └── [각종 CSS 파일들]
└── load_header.php             # 공통 헤더 로드
```

## 핵심 파일 상세

### 1. config/environment.php

환경 감지 및 설정의 핵심 파일입니다.

```php
<?php
// 환경별 설정 파일
class Environment {
    const LOCAL = 'local';
    const SERVER = 'server';
    
    private static $current = null;
    
    public static function getCurrent() {
        if (self::$current === null) {
            // 로컬 환경 감지
            $host = $_SERVER['HTTP_HOST'];
            if (strpos($host, 'jtechel.local') !== false || 
                strpos($host, 'localhost') !== false ||
                strpos($host, '127.0.0.1') !== false ||
                strpos($host, '192.168.') !== false ||
                strpos($host, '10.0.') !== false ||
                strpos($host, '172.') !== false) {
                self::$current = self::LOCAL;
            } else {
                self::$current = self::SERVER;
            }
        }
        return self::$current;
    }
    
    public static function isLocal() {
        return self::getCurrent() === self::LOCAL;
    }
    
    public static function isServer() {
        return self::getCurrent() === self::SERVER;
    }
}

// 환경별 데이터베이스 설정
function getDatabaseConfig() {
    if (Environment::isLocal()) {
        return [
            'host' => 'localhost',
            'user' => 'root',
            'pass' => '',
            'name' => 'jtechel'
        ];
    } else {
        return [
            'host' => 'localhost',
            'user' => 'jtechel',
            'pass' => 'jung@122904',
            'name' => 'jtechel'
        ];
    }
}

// 환경별 URL 설정
function getBaseUrl() {
    if (Environment::isLocal()) {
        // 로컬 환경에서는 현재 도메인을 사용
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        return $protocol . '://' . $host;
    } else {
        return 'http://j-techel.co.kr';
    }
}

// 환경별 경로 설정
function getPath($path = '') {
    $baseUrl = getBaseUrl();
    return $baseUrl . ($path ? '/' . ltrim($path, '/') : '');
}
?>
```

**주요 기능:**
- HTTP_HOST 기반 환경 자동 감지
- 로컬 환경: localhost, 127.0.0.1, 사설 IP 대역 감지
- 환경별 데이터베이스 설정 분리
- 환경별 기본 URL 설정

### 2. common/functions.php

환경에 관계없이 일관된 인터페이스를 제공하는 공통 함수들입니다.

```php
<?php
// 공통 함수 파일
require_once __DIR__ . '/../config/environment.php';

/**
 * 환경별 URL 생성
 * @param string $path 경로
 * @return string 완전한 URL
 */
function url($path = '') {
    return getPath($path);
}

/**
 * 환경별 자산(assets) URL 생성
 * @param string $path 자산 경로
 * @return string 완전한 자산 URL
 */
function asset($path) {
    return getPath($path);
}

/**
 * 현재 환경이 로컬인지 확인
 * @return bool
 */
function isLocal() {
    return Environment::isLocal();
}

/**
 * 현재 환경이 서버인지 확인
 * @return bool
 */
function isServer() {
    return Environment::isServer();
}

/**
 * 환경별 리다이렉트
 * @param string $path 리다이렉트할 경로
 */
function redirect($path) {
    header("Location: " . getPath($path));
    exit;
}

/**
 * 환경별 JavaScript 파일 로드
 * @param string $path JS 파일 경로
 * @return string script 태그
 */
function js($path) {
    return '<script src="' . getPath($path) . '"></script>';
}

/**
 * 환경별 CSS 파일 로드
 * @param string $path CSS 파일 경로
 * @return string link 태그
 */
function css($path) {
    return '<link rel="stylesheet" href="' . getPath($path) . '">';
}
?>
```

**주요 기능:**
- URL 생성 함수 (url, asset)
- 환경 확인 함수 (isLocal, isServer)
- 리다이렉트 함수
- 자산 로드 함수 (js, css)

### 3. lib/mydb.php

환경별 데이터베이스 연결을 처리하는 파일입니다.

```php
<?php
require_once __DIR__ . '/../config/environment.php';

function db_connect(){  //DB연결을 함수로 정의
    $config = getDatabaseConfig();
    
    $db_user = $config['user'];
    $db_pass = $config['pass'];
    $db_host = $config['host'];
    $db_name = $config['name'];
    $db_type = "mysql";
    $dsn = "$db_type:host=$db_host;dbname=$db_name;charset=utf8mb4";

    try{ 
        $pdo=new PDO($dsn,$db_user,$db_pass);  
        $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);  
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES,FALSE);
        
    } catch (PDOException $Exception) {  
        die('오류:'.$Exception->getMessage());
    }
    return $pdo;
}
?>
```

**주요 기능:**
- 환경별 데이터베이스 설정 자동 적용
- PDO를 사용한 안전한 데이터베이스 연결
- 에러 처리 및 예외 관리

### 4. login/login_result.php

세션 관리 및 사용자 인증을 처리하는 파일입니다.

```php
<?php
// 환경별 기본 URL 설정
require_once '../config/environment.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 로그인 처리 로직
$id = $_REQUEST["uid"] ?? '';
$pw = $_REQUEST["upw"] ?? '';
require_once("../lib/mydb.php");
$pdo = db_connect();

// 사용자 인증
try {
    $sql = "select * from jtechel.member where id=?";
    $stmh = $pdo->prepare($sql);
    $stmh->bindValue(1, $id, PDO::PARAM_STR);
    $stmh->execute();
    $count = $stmh->rowCount();
} catch (PDOException $Exception) {
    print "오류: " . $Exception->getMessage();
}

$row = $stmh->fetch(PDO::FETCH_ASSOC);

if ($count < 1) {
    // 아이디 오류 처리
} elseif ($pw != $row["pass"]) {
    // 비밀번호 오류 처리
} else {
    // 세션 설정
    $_SESSION["userid"] = $row["id"];
    $_SESSION["name"] = $row["name"];
    $_SESSION["nick"] = $row["nick"];
    $_SESSION["level"] = $row["level"];
    $_SESSION["ecountID"] = $row["ecountID"];
    $_SESSION["part"] = $row["part"];
    
    // 로그 기록
    $data = date("Y-m-d H:i:s") . " - " . $_SESSION["userid"] . " - " . $_SESSION["name"];
    $pdo->beginTransaction();
    $sql = "insert into jtechel.logdata(data) values(?)";
    $stmh = $pdo->prepare($sql);
    $stmh->bindValue(1, $data, PDO::PARAM_STR);
    $stmh->execute();
    $pdo->commit();
    
    // 권한별 리다이렉트
    if (isset($_SESSION["part"]) && $_SESSION["part"] == 'mywork') {
        header("Location:../mywork/index.php");
        exit;
    }
    
    if (isset($_SESSION["level"]) && $_SESSION["level"] > 3) {
        header("Location:../index.php");
        exit;
    }
}
?>
```

**주요 기능:**
- 환경별 설정 자동 로드
- 세션 관리
- 사용자 인증 및 권한 확인
- 로그 기록
- 권한별 리다이렉트

## 구현 가이드

### 1. 새 프로젝트에 적용하기

#### 단계 1: 기본 폴더 구조 생성
```
your_project/
├── config/
├── common/
├── lib/
├── login/
├── js/
├── css/
└── index.php
```

#### 단계 2: 환경 설정 파일 생성
`config/environment.php` 파일을 생성하고 프로젝트에 맞게 수정:

```php
// 환경 감지 조건 수정
if (strpos($host, 'yourproject.local') !== false || 
    strpos($host, 'localhost') !== false ||
    // 기타 로컬 환경 조건들...

// 데이터베이스 설정 수정
function getDatabaseConfig() {
    if (Environment::isLocal()) {
        return [
            'host' => 'localhost',
            'user' => 'your_local_user',
            'pass' => 'your_local_pass',
            'name' => 'your_local_db'
        ];
    } else {
        return [
            'host' => 'your_server_host',
            'user' => 'your_server_user',
            'pass' => 'your_server_pass',
            'name' => 'your_server_db'
        ];
    }
}

// 기본 URL 설정 수정
function getBaseUrl() {
    if (Environment::isLocal()) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        return $protocol . '://' . $host;
    } else {
        return 'https://yourdomain.com';  // 실제 도메인으로 변경
    }
}
```

#### 단계 3: 공통 함수 파일 생성
`common/functions.php` 파일을 복사하고 프로젝트에 맞게 수정합니다.

#### 단계 4: 데이터베이스 연결 파일 생성
`lib/mydb.php` 파일을 복사하고 데이터베이스 설정을 확인합니다.

### 2. 기존 프로젝트에 통합하기

#### 단계 1: 환경 설정 파일 추가
기존 프로젝트에 `config/environment.php` 파일을 추가합니다.

#### 단계 2: 기존 파일 수정
기존 PHP 파일들의 상단에 다음 코드를 추가:

```php
<?php
// 환경별 기본 URL 설정
require_once 'config/environment.php';  // 또는 적절한 경로
```

#### 단계 3: 데이터베이스 연결 수정
기존 데이터베이스 연결 코드를 `lib/mydb.php`의 `db_connect()` 함수를 사용하도록 수정합니다.

#### 단계 4: URL 생성 함수 적용
하드코딩된 URL들을 `url()` 또는 `asset()` 함수를 사용하도록 수정합니다.

### 3. 사용 예시

#### URL 생성
```php
// 기존 방식
echo "http://localhost/project/css/style.css";

// 새로운 방식
echo asset('css/style.css');
```

#### 환경 확인
```php
if (isLocal()) {
    // 로컬 환경에서만 실행할 코드
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    // 서버 환경에서만 실행할 코드
    error_reporting(0);
    ini_set('display_errors', 0);
}
```

#### 데이터베이스 연결
```php
require_once 'lib/mydb.php';
$pdo = db_connect();  // 환경에 맞는 설정으로 자동 연결
```

## 보안 고려사항

### 1. 데이터베이스 보안
- 서버 환경의 데이터베이스 비밀번호는 환경 변수나 별도 설정 파일로 관리
- PDO Prepared Statement 사용으로 SQL 인젝션 방지

### 2. 세션 보안
- 세션 쿠키의 보안 설정
- 세션 타임아웃 설정
- CSRF 토큰 사용 고려

### 3. 환경별 설정 분리
- 민감한 정보는 환경별로 분리하여 관리
- 로컬 환경에서는 디버그 정보 표시, 서버 환경에서는 숨김

## 확장 가능성

### 1. 추가 환경 지원
개발, 스테이징, 운영 환경 등 더 많은 환경을 지원하려면 `Environment` 클래스를 확장합니다.

### 2. 설정 파일 분리
환경별 설정을 별도 파일로 분리하여 관리할 수 있습니다.

### 3. 캐싱 시스템
환경 감지 결과를 캐싱하여 성능을 향상시킬 수 있습니다.

## 문제 해결

### 1. 환경 감지 오류
- HTTP_HOST 값 확인
- 환경 감지 조건 수정

### 2. 데이터베이스 연결 오류
- 환경별 데이터베이스 설정 확인
- 네트워크 연결 상태 확인

### 3. URL 생성 오류
- getBaseUrl() 함수의 반환값 확인
- 경로 설정 확인

## 결론

이 시스템을 통해 로컬 개발 환경과 서버 운영 환경을 자동으로 구분하여 각각의 환경에 맞는 설정으로 동작하는 웹 애플리케이션을 구축할 수 있습니다. 이를 통해 개발 생산성을 향상시키고 배포 과정을 단순화할 수 있습니다.
