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
