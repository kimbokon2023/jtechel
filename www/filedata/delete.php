  <?php   
   session_start();
   
   // 환경별 기본 URL 설정
   require_once '../config/environment.php';
   
   $num=$_REQUEST["num"];
         
   require_once("../lib/MYDB.php");
   $pdo = db_connect();
 
   $upload_dir = '../data/';   //물리적 저장위치   
 
   try{
     $sql = "select * from jtechel.concert where num = ? ";
     $stmh = $pdo->prepare($sql); 
     $stmh->bindValue(1,$num,PDO::PARAM_STR); 
     $stmh->execute();
     $count = $stmh->rowCount();              
 
     $row = $stmh->fetch(PDO::FETCH_ASSOC);
     $copied_name[0] = $row['file_copied_0'];
     $copied_name[1] = $row['file_copied_1'];
     $copied_name[2] = $row['file_copied_2'];
      
     for ($i=0; $i<3; $i++)
     { 
         if ($copied_name[$i])
         {
	     $image_name = $upload_dir.$copied_name[$i];
	     unlink($image_name);
	  }
     }
   }catch (PDOException $Exception) {
        print "오류: ".$Exception->getMessage();
   }
 
   try{
     $pdo->beginTransaction();
     $sql = "delete from jtechel.concert where num = ?";  
     $stmh = $pdo->prepare($sql);
     $stmh->bindValue(1,$num,PDO::PARAM_STR);      
     $stmh->execute();   
     $pdo->commit();
 
     header("Location:http://jtechel.local/concert/list.php");
                         
     } catch (Exception $ex) {
        $pdo->rollBack();
        print "오류: ".$Exception->getMessage();
   }
?>