<?php
header("Content-Type: application/json");  //json을 사용하기 위해 필요한 구문

isset($_REQUEST["num"])  ? $num=$_REQUEST["num"] :   $num=''; 
isset($_REQUEST["tablename"])  ? $tablename=$_REQUEST["tablename"] :  $tablename=''; 
isset($_REQUEST["item"])  ? $item=$_REQUEST["item"] :   $item=''; 

require_once("../lib/mydb.php");
$pdo = db_connect();	

$now = date("Y-m-d");	     // 현재 날짜와 크거나 같으면 출고예정으로 구분
$nowtime = date("H:i:s");	 // 현재시간	

$sql=" select * from jtechel.picuploads where tablename ='$tablename' and item ='$item' and parentnum ='$num' ";	

$recnum=0; 
$num_arr=array(); 
$parentnum_arr=array(); 
$img_arr=array(); 

 try{  
// 레코드 전체 sql 설정
   $stmh = $pdo->query($sql);            // 검색조건에 맞는글 stmh
   
   $i= 0;    
   while($row = $stmh->fetch(PDO::FETCH_ASSOC)) {

            $num_arr[$i]        =$row["num"];			
			$parentnum_arr[$i] = $row["parentnum"];
			$img_arr[$i]       = $row["picname"];
			
			$i++;
        }		 
   } catch (PDOException $Exception) {
    print "오류: ".$Exception->getMessage();
}  

$recnum = $i;

//각각의 정보를 하나의 배열 변수에 넣어준다.
$data = array(
	"recnum"=>           $recnum,
	"num_arr" =>         $num_arr,
	"parentnum_arr" =>   $parentnum_arr,
	"img_arr" =>         $img_arr,

);   

//json 출력
echo(json_encode($data, JSON_UNESCAPED_UNICODE));

?>