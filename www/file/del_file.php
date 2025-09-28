<?php
header("Content-Type: application/json");  //json을 사용하기 위해 필요한 구문

isset($_REQUEST["savename"])  ? $savename=$_REQUEST["savename"] : $savename=''; 

require_once("../lib/mydb.php");
$pdo = db_connect();	

// 물리적 자료를 지워주고, DB를 지워준다.
  
   $upload_dir = '../uploads/';    //물리적 저장위치   
   $made_name = $upload_dir . $savename;
   unlink($made_name); 
    
   try{									
     $pdo->beginTransaction();
     $sql = "delete from mirae8440.fileuploads where savename = ?";  
     $stmh = $pdo->prepare($sql);
     $stmh->bindValue(1,$savename,PDO::PARAM_STR);      
     $stmh->execute();  

     $pdo->commit();
	 
     } catch (Exception $ex) {
        $pdo->rollBack();
        print "오류: ".$Exception->getMessage();
   } 

//각각의 정보를 하나의 배열 변수에 넣어준다.
$data = array(
	"savename"=>           $savename,
);   
//json 출력
echo(json_encode($data, JSON_UNESCAPED_UNICODE));

?>