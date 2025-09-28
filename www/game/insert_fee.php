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

include 'request.php';		  

$num=$_REQUEST["num"];
$registedate=$_REQUEST["registedate"];	
$text1=$_REQUEST["text1"];
$text2=$_REQUEST["text2"];
$text3=$_REQUEST["text3"];
$text4=$_REQUEST["text4"];
$text5=$_REQUEST["text5"];	
$memo=$_REQUEST["memo"];
$receivable=$_REQUEST["receivable"];

		  
require_once($_SERVER['DOCUMENT_ROOT'] . "/lib/mydb.php");
$pdo = db_connect();
     
 if ($mode=="modify"){      
     try{
        $sql = "select * from jtechel.game_fee where num=?";  // get target record
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
        $sql = "update jtechel.game_fee set  registedate=?, text1=?, text2=?,  text3=?,  text4=?,  text5=? ,  memo=? ,  receivable=? ";
        $sql .= " where num=?  LIMIT 1";		
		
    
	$stmh = $pdo->prepare($sql); 	
	$stmh->bindValue(1, $registedate, PDO::PARAM_STR);  
	$stmh->bindValue(2, $text1, PDO::PARAM_STR);  
	$stmh->bindValue(3, $text2, PDO::PARAM_STR);  
    $stmh->bindValue(4, $text3, PDO::PARAM_STR);      
    $stmh->bindValue(5, $text4, PDO::PARAM_STR);        
    $stmh->bindValue(6, $text5, PDO::PARAM_STR);            
    $stmh->bindValue(7, $memo, PDO::PARAM_STR);            
    $stmh->bindValue(8, $receivable, PDO::PARAM_STR);            
    $stmh->bindValue(9, $num, PDO::PARAM_STR);            
	 
	 $stmh->execute();
     $pdo->commit(); 
        } catch (PDOException $Exception) {
           $pdo->rollBack();
           print "오류: ".$Exception->getMessage();
       }                         
      $state = "지출자료 수정" ; 
 } 
 
 if ($mode=="insert"){	 	 
   try{
     $pdo->beginTransaction();
  	 
     $sql = "insert into jtechel.game_fee(registedate , text1 , text2 ,  text3 ,  text4 , text5, memo, receivable  ) "; 
     $sql .= " values(?, ?, ?, ?, ?, ?, ?, ?) ";
	
	  
    $stmh = $pdo->prepare($sql); 
	 
	$stmh->bindValue(1, $registedate, PDO::PARAM_STR);  
	$stmh->bindValue(2, $text1, PDO::PARAM_STR);  
	$stmh->bindValue(3, $text2, PDO::PARAM_STR);  
    $stmh->bindValue(4, $text3, PDO::PARAM_STR);      
    $stmh->bindValue(5, $text4, PDO::PARAM_STR);        
    $stmh->bindValue(6, $text5, PDO::PARAM_STR);            
    $stmh->bindValue(7, $memo, PDO::PARAM_STR);            
    $stmh->bindValue(8, $receivable, PDO::PARAM_STR);      
	 
     $stmh->execute();
     $pdo->commit(); 
     } catch (PDOException $Exception) {
          $pdo->rollBack();
       print "오류: ".$Exception->getMessage();
     }   
	 
	 $state = "지출자료 신규입력" ; 
}

 if ($mode=="delete"){	 	 
   try{
     $pdo->beginTransaction();
  	 
     $sql = "delete from  jtechel.game_fee where num = ?";  
     $stmh = $pdo->prepare($sql);
     $stmh->bindValue(1,$num,PDO::PARAM_STR);      
     $stmh->execute();   
     $pdo->commit();	 
     } catch (PDOException $Exception) {
          $pdo->rollBack();
       print "오류: ".$Exception->getMessage();
     }  
	 
	 $state = "지출자료 삭제" ; 
	 
}


 $data=date("Y-m-d H:i:s") . " - " . $_SESSION["userid"] . " - " . $_SESSION["name"] . " - " . $state ;	
 require_once("../lib/mydb.php");
 $pdo = db_connect();
 $pdo->beginTransaction();
 $sql = "insert into jtechel.gamelog(data) values(?) " ;
 $stmh = $pdo->prepare($sql); 
 $stmh->bindValue(1, $data, PDO::PARAM_STR);   
 $stmh->execute();
 $pdo->commit(); 


//각각의 정보를 하나의 배열 변수에 넣어준다.
$data = array(
		"registedate" =>  $registedate,		
		"mcount" =>  $mcount		
);

//json 출력
echo(json_encode($data, JSON_UNESCAPED_UNICODE));   
   
 ?>