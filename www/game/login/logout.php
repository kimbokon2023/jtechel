<?php
// 환경별 기본 URL 설정
require_once '../../config/environment.php';

session_start();
unset($_SESSION["userid"]);
unset($_SESSION["name"]);
unset($_SESSION["nick"]);
unset($_SESSION["level"]);
unset($_SESSION["weather"]);
    header ("Location:" . getBaseUrl() . "/game/login/login_form.php");
?>