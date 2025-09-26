<?php   

session_start();   

header("Content-Type: application/json");  //json을 사용하기 위해 필요한 구문  

isset($_REQUEST["mode"])  ? $mode = $_REQUEST["mode"] : $mode=""; 
isset($_REQUEST["id"])  ? $id = $_REQUEST["id"] : $id=""; 

$pass=$_REQUEST["pass"];  
$name=$_REQUEST["name"];  
$level=$_REQUEST["level"];  
$part=$_REQUEST["part"];  
$part='jtech';  // jtech 강제 파트 지정 (오성은 오성으로 해야 함) 
			  
require_once($_SERVER['DOCUMENT_ROOT'] . "/lib/mydb.php");
 $pdo = db_connect();
     
 if ($mode=="modify"){      
     try{
        $sql = "select * from jtechel.member where id=?";  // get target record
        $stmh = $pdo->prepare($sql); 
        $stmh->bindValue(1,$id,PDO::PARAM_STR); 
        $stmh->execute(); 
        $row = $stmh->fetch(PDO::FETCH_ASSOC);
     } catch (PDOException $Exception) {
        $pdo->rollBack();
        print "오류: ".$Exception->getMessage();
     } 
       			  
     try{
        $pdo->beginTransaction();   
        $sql = "update jtechel.member set name=?, pass=?, level=?,  part=?    ";
        $sql .= " where id=?  LIMIT 1";		
		
    
	$stmh = $pdo->prepare($sql); 	
	$stmh->bindValue(1, $name, PDO::PARAM_STR);  
	$stmh->bindValue(2, $pass, PDO::PARAM_STR);  
	$stmh->bindValue(3, $level, PDO::PARAM_STR);  
	$stmh->bindValue(4, $part, PDO::PARAM_STR);  
    $stmh->bindValue(5, $id, PDO::PARAM_STR);       
	 
	 $stmh->execute();
     $pdo->commit(); 
        } catch (PDOException $Exception) {
           $pdo->rollBack();
           print "오류: ".$Exception->getMessage();
       }                         
       
 } 
 
 if ($mode=="insert"){	 	 
   try{
     $pdo->beginTransaction();
  	 
	$sql = "insert into jtechel.member( name , pass , level , part ,  id ) ";     
	$sql .= " values(?, ?, ?, ?, ?) ";  
	  
    $stmh = $pdo->prepare($sql); 
	   
	$stmh->bindValue(1, $name, PDO::PARAM_STR);  
	$stmh->bindValue(2, $pass, PDO::PARAM_STR);  
	$stmh->bindValue(3, $level, PDO::PARAM_STR);  
	$stmh->bindValue(4, $part, PDO::PARAM_STR);      
    $stmh->bindValue(5, $id, PDO::PARAM_STR);    	   
	 
     $stmh->execute();
     $pdo->commit(); 
     }   catch (PDOException $Exception) {
           $pdo->rollBack();
           print "오류: ".$Exception->getMessage();
       }       
}

 if ($mode=="delete"){	 	 
   try{
     $pdo->beginTransaction();
  	 
     $sql = "delete from  jtechel.member where id = ?";  
     $stmh = $pdo->prepare($sql);
     $stmh->bindValue(1,$id,PDO::PARAM_STR);      
     $stmh->execute();   
     $pdo->commit();	 
     } catch (PDOException $Exception) {
           $pdo->rollBack();
           print "오류: ".$Exception->getMessage();
	  }       
}

//각각의 정보를 하나의 배열 변수에 넣어준다.
$data = array(
    "status" => "success",
    "message" => "Operation completed successfully.",
    "id" => $id,
);

//json 출력
echo json_encode($data, JSON_UNESCAPED_UNICODE);

   
 ?>