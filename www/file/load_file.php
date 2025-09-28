<?php
header("Content-Type: application/json");  //json을 사용하기 위해 필요한 구문

isset($_REQUEST["id"])  ? $id=$_REQUEST["id"] :   $id=''; 
isset($_REQUEST["fileorimage"])  ? $fileorimage=$_REQUEST["fileorimage"] :   $fileorimage=''; // file or image
isset($_REQUEST["item"])  ? $item=$_REQUEST["item"] :   $item=''; 
isset($_REQUEST["upfilename"])  ? $upfilename=$_REQUEST["upfilename"] :   $upfilename=''; 
isset($_REQUEST["tablename"])  ? $tablename=$_REQUEST["tablename"] :  $tablename=''; 
isset($_REQUEST["savetitle"])  ? $savetitle=$_REQUEST["savetitle"] :  $savetitle='';   // log기록 저장 타이틀

require_once("../lib/mydb.php");
$pdo = db_connect();	

$now = date("Y-m-d");	     // 현재 날짜와 크거나 같으면 출고예정으로 구분
$nowtime = date("H:i:s");	 // 현재시간	

$sql=" select * from mirae8440.fileuploads where tablename ='$tablename' and item ='$item' and parentid ='$id' ";	

$recid=0; 
$id_arr=array(); 
$parentid_arr=array(); 
$realfile_arr=array(); 
$file_arr=array(); 

 try{  
// 레코드 전체 sql 설정
   $stmh = $pdo->query($sql);            // 검색조건에 맞는글 stmh
   
   $i= 0;    
   while($row = $stmh->fetch(PDO::FETCH_ASSOC)) {

            $id_arr[$i]        =$row["id"];			
			$parentid_arr[$i] = $row["parentid"];
			$realfile_arr[$i]       = $row["realname"];
			$file_arr[$i]       = $row["savename"];
			
			$i++;
        }		 
   } catch (PDOException $Exception) {
    print "오류: ".$Exception->getMessage();
}  

$recid = $i;

//각각의 정보를 하나의 배열 변수에 넣어준다.
$data = array(
	"recid"=>           $recid,
	"id_arr" =>         $id_arr,
	"parentid_arr" =>   $parentid_arr,
	"file_arr" =>       $file_arr,
	"realfile_arr" =>    $realfile_arr,

);   

//json 출력
echo(json_encode($data, JSON_UNESCAPED_UNICODE));

?>