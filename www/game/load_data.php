<?php   

session_start();   

header("Content-Type: application/json");  //json을 사용하기 위해 필요한 구문  

isset($_REQUEST["num"])  ? $num = $_REQUEST["num"] : $num=""; 
		  
require_once($_SERVER['DOCUMENT_ROOT'] . "/lib/mydb.php");
$pdo = db_connect();
     
 
     try{
        $sql = "select * from jtechel.game where num=?";  // get target record
        $stmh = $pdo->prepare($sql); 
        $stmh->bindValue(1,$num,PDO::PARAM_STR); 
        $stmh->execute(); 
        $row = $stmh->fetch(PDO::FETCH_ASSOC);
		include 'rowDB.php';		
		
     } catch (PDOException $Exception) {
        $pdo->rollBack();
        print "오류: ".$Exception->getMessage();
     } 
 
 
 //  서버에 시간 저장
 $updatetime = date("H:i:s") ; 
 	try{
		$pdo->beginTransaction();   
		$sql = "update jtechel.checksave set  checksave=? ";
		$sql .= " where num=1  LIMIT 1";	
		
		$stmh = $pdo->prepare($sql); 	
		$stmh->bindValue(1, $updatetime, PDO::PARAM_STR);  		
	 
	 $stmh->execute();
     $pdo->commit(); 
        } catch (PDOException $Exception) {
           $pdo->rollBack();
           print "오류: ".$Exception->getMessage();
       } 
	   
 $savedTime = $updatetime;
 
//각각의 정보를 하나의 배열 변수에 넣어준다.
$data = array(
	 "num" => $num ,
	 "input_arr" => $input_arr,
	 "output_arr1" => $output_arr1,
	 "output_arr2" => $output_arr2,
	 "output_arr3" => $output_arr3,
	 "output_arr4" => $output_arr4,
	 "output_arr5" => $output_arr5,
	 "inputsum" => $inputsum,		
	 "outputsum" => $outputsum,
	 "updatetime" => $updatetime,
	 
	"text_arr1" => $text1,
	"text_arr2" => $text2,
	"text_arr3" => $text3,
	"text_arr4" => $text4,
	"text_arr5" => $text5,
	"text_arr6" => $text6,
	
	"memo" => $memo,
	"receivable" => $receivable,	
	"mark" =>  $mark,
	"namearr1" =>  $namearr1,
	"namearr2" =>  $namearr2,
	"namearr3" =>  $namearr3,
	"namearr4" =>  $namearr4,
	"namearr5" =>  $namearr5,
	"branch"   => $branch,
	"savedTime"   => $savedTime
	
);

//json 출력
echo(json_encode($data, JSON_UNESCAPED_UNICODE));   
   
 ?>