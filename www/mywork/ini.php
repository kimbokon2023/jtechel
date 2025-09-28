<?
session_start(); 

header('Content-Type: text/html; charset=utf-8');

$level= $_SESSION["level"];
$user_name= $_SESSION["name"];  

 if(!isset($_SESSION["level"]) || $level>8) {
          /*   alert("관리자 승인이 필요합니다."); */
		 sleep(2);
         header ("Location:http://j-techel.co.kr/login/logout.php");
         exit;
   } 

// 환경파일 읽어오기, 메인 table명, 사진파일저장 table명, 작업폴더명 기록읽기
$tmp_name = "./settings.ini";
$readIni = array();   // 환경파일 불러오기
$readIni = parse_ini_file($tmp_name,false);	

$tablename=$readIni['tablename'];
$table_picuploads=$readIni['table_picuploads'];
$workdir=$readIni['workdir'];

// common read

isset($_REQUEST["mode"])  ? $mode = $_REQUEST["mode"] : $mode=""; 
isset($_REQUEST["id"])  ? $id = $_REQUEST["id"] : $id=""; 
isset($_REQUEST["num"])  ? $id = $_REQUEST["num"] : $id=""; 
isset($_REQUEST["contents"])  ? $contents = $_REQUEST["contents"] : $contents=""; 
isset($_REQUEST["parent_id"])  ? $parent_id = $_REQUEST["parent_id"] : $parent_id=""; 
isset($_REQUEST["check"])  ? $check = $_REQUEST["check"] : $check=""; 
isset($_REQUEST["search"])  ? $search = $_REQUEST["search"] : $search=""; 
isset($_REQUEST["page"])  ? $page = $_REQUEST["page"] : $page=1; 

?>