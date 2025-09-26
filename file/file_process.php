 <?php   
 session_start(); 
 
 if(isset($_REQUEST["num"]))
    $num=$_REQUEST["num"]; 

 if(isset($_REQUEST["measureday"]))
    $measureday=$_REQUEST["measureday"];

// if($measureday!="0000-00-00" and $measureday!="1970-01-01" and $measureday!="")   $measureday = date("Y-m-d", strtotime( $measureday) );
	// else 
		// $measureday=date("Y-m-d");


 if(isset($_REQUEST["check"])) 
	 $check=$_REQUEST["check"]; 
   else
     $check=$_POST["check"]; 

 		
 require_once("../lib/mydb.php");
 $pdo = db_connect();      
 
 try{
     $sql = "select * from mirae8440.work where num=?";
     $stmh = $pdo->prepare($sql);  
     $stmh->bindValue(1, $num, PDO::PARAM_STR);      
     $stmh->execute();            
      
     $row = $stmh->fetch(PDO::FETCH_ASSOC); 	 
     $update_log=$row["update_log"];
	 
     }catch (PDOException $Exception) {
       print "오류: ".$Exception->getMessage();
     }
	 
 $data=date("Y-m-d H:i:s") . " - "  . $_SESSION["name"] . "  " ;	
 $update_log = $data . $update_log . "&#10";  // 개행문자 Textarea      
 
	try{
        $pdo->beginTransaction();   
        $sql = "update mirae8440.work set measureday=?, update_log=?  where num=?  LIMIT 1";            
	   
     $stmh = $pdo->prepare($sql); 
     $stmh->bindValue(1, $measureday, PDO::PARAM_STR);         
     $stmh->bindValue(2, $update_log, PDO::PARAM_STR);         
     $stmh->bindValue(3, $num, PDO::PARAM_STR);           //고유키값이 같나?의 의미로 ?로 num으로 맞춰야 합니다. where 구문 
	 
	 $stmh->execute();
     $pdo->commit(); 
        } catch (PDOException $Exception) {
           $pdo->rollBack();
           print "오류: ".$Exception->getMessage();
       }     
	   
	   
     // echo '<script>alert("실측일이 입력되었습니다."");</script>';	    
	
    header("Location:http://8440.co.kr/p/view.php?num=$num&check=$check");  
 ?>