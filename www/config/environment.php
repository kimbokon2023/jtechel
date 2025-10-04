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

// 편의 함수들
function isLocalEnvironment() {
    return Environment::isLocal();
}

function isServerEnvironment() {
    return Environment::isServer();
}
?>
