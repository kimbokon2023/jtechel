<?php
session_start();
unset($_SESSION["userid"]);
unset($_SESSION["name"]);
unset($_SESSION["nick"]);
unset($_SESSION["level"]);
unset($_SESSION["weather"]);
    header ("Location:http://j-techel.co.kr/login/login_form.php");
?>