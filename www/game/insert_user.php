<?php   
/// 개별로 신청하는 것에 대한 DB처리 구간 ///
/// 개별로 신청하는 것에 대한 DB처리 구간 ///

session_start();   

$level= $_SESSION["level"];
$user_name= $_SESSION["name"];
$id= $_SESSION["userid"];

// header("Content-Type: application/json");  //json을 사용하기 위해 필요한 구문  

isset($_REQUEST["mode"])  ? $mode = $_REQUEST["mode"] : $mode=""; 
isset($_REQUEST["num"])  ? $num = $_REQUEST["num"] : $num=""; 
  
	$id = $_REQUEST["id"];	  
	$pass = $_REQUEST["pass"];	  
	$level = $_REQUEST["level"];	  
	$name = $_REQUEST["name"];	
	$branch = $_REQUEST["branch"];	

			  
require_once($_SERVER['DOCUMENT_ROOT'] . "/lib/mydb.php");
$pdo = db_connect();
     
 if ($mode=="modify"){      
     try{
        $sql = "select * from jtechel.game_member where num=?";  // get target record
        $stmh = $pdo->prepare($sql); 
        $stmh->bindValue(1,$num,PDO::PARAM_STR); 
        $stmh->execute(); 
        $row = $stmh->fetch(PDO::FETCH_ASSOC);
     } catch (PDOException $Exception) {
        $pdo->rollBack();
        print "오류: ".$Exception->getMessage();
     } 
       			  
     try{
        $pdo->beginTransaction();   
        $sql = "update jtechel.game_member set  name=?, id=?, pass=?,  level=?, branch =?  ";
        $sql .= " where num=?  LIMIT 1";		
		
    
	$stmh = $pdo->prepare($sql); 	
	$stmh->bindValue(1, $name, PDO::PARAM_STR);  
	$stmh->bindValue(2, $id, PDO::PARAM_STR);  
	$stmh->bindValue(3, $pass, PDO::PARAM_STR);  
    $stmh->bindValue(4, $level, PDO::PARAM_STR);          
    $stmh->bindValue(5, $branch, PDO::PARAM_STR);            
    $stmh->bindValue(6, $num, PDO::PARAM_STR);            
	 
	 $stmh->execute();
     $pdo->commit(); 
        } catch (PDOException $Exception) {
           $pdo->rollBack();
           print "오류: ".$Exception->getMessage();
       }                         
      $state = "사용자 정보수정" ; 
 } 
 
 if ($mode=="insert"){	 	 
   try{
     $pdo->beginTransaction();
  	 
     $sql = "insert into jtechel.game_member(name, id, pass, level, branch ) "; 
     $sql .= " values(?, ?, ?, ?, ? ) ";
	
	  
    $stmh = $pdo->prepare($sql); 
	 
	$stmh->bindValue(1, $name , PDO::PARAM_STR);  
	$stmh->bindValue(2, $id , PDO::PARAM_STR);  
	$stmh->bindValue(3, $pass , PDO::PARAM_STR);  
    $stmh->bindValue(4, $level , PDO::PARAM_STR);             
    $stmh->bindValue(5, $branch , PDO::PARAM_STR);             
	 
     $stmh->execute();
     $pdo->commit(); 
     } catch (PDOException $Exception) {
          $pdo->rollBack();
       print "오류: ".$Exception->getMessage();
     }   
	 
	 $state = "사용자 신규입력" ; 
}

 if ($mode=="delete"){	 	 
   try{
     $pdo->beginTransaction();
  	 
     $sql = "delete from  jtechel.game_member where num = ?";  
     $stmh = $pdo->prepare($sql);
     $stmh->bindValue(1,$num,PDO::PARAM_STR);      
     $stmh->execute();   
     $pdo->commit();	 
     } catch (PDOException $Exception) {
          $pdo->rollBack();
       print "오류: ".$Exception->getMessage();
     }  
	 
	 $state = "사용자 정보 삭제" ; 
	 
}


 $data=date("Y-m-d H:i:s") . " - " . $_SESSION["userid"] . " - " . $_SESSION["name"] . " - " . $state ;	
 require_once("../lib/mydb.php");
 $pdo = db_connect();
 $pdo->beginTransaction();
 $sql = "insert into jtechel.game_memberlog(data) values(?) " ;
 $stmh = $pdo->prepare($sql); 
 $stmh->bindValue(1, $data, PDO::PARAM_STR);   
 $stmh->execute();
 $pdo->commit(); 


//각각의 정보를 하나의 배열 변수에 넣어준다.
$data = array(
		"registedate" =>  $registedate
		
);

//json 출력
echo(json_encode($data, JSON_UNESCAPED_UNICODE));   
   
 ?>