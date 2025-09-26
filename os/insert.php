<?php   
session_start();    
  
$level= $_SESSION["level"];
$user_name= $_SESSION["name"];

isset($_REQUEST["mode"])  ? $mode = $_REQUEST["mode"] : $mode=""; 
isset($_REQUEST["num"])  ? $num = $_REQUEST["num"] : $num=""; 
	  
$table = 'os';

if($mode=="modify" || $mode=="insert"){   

$fieldarr = array();
$strarr = array();

array_push($fieldarr, 'workplacename');
array_push($fieldarr, 'address');
array_push($fieldarr, 'regist_day');
array_push($fieldarr, 'measureday');
array_push($fieldarr, 'workday');
array_push($fieldarr, 'doneday');
array_push($fieldarr, 'demand');
array_push($fieldarr, 'firstord');
array_push($fieldarr, 'firstordman');
array_push($fieldarr, 'firstordmantel');
array_push($fieldarr, 'secondord');
array_push($fieldarr, 'secondordman');
array_push($fieldarr, 'secondordmantel');
array_push($fieldarr, 'chargedman');
array_push($fieldarr, 'chargedmantel');
array_push($fieldarr, 'worker');
array_push($fieldarr, 'memo');
array_push($fieldarr, 'memo2');
array_push($fieldarr, 'material1');
array_push($fieldarr, 'material2');
array_push($fieldarr, 'material3');
array_push($fieldarr, 'material4');
array_push($fieldarr, 'material5');
array_push($fieldarr, 'material6');
array_push($fieldarr, 'material7');
array_push($fieldarr, 'material8');
array_push($fieldarr, 'material9');
array_push($fieldarr, 'material10');
array_push($fieldarr, 'update_log');
// array_push($fieldarr, 'filename1');
// array_push($fieldarr, 'filename2');

array_push($strarr, $_REQUEST["workplacename"]);
array_push($strarr, $_REQUEST["address"]);
array_push($strarr, $_REQUEST["regist_day"]);
array_push($strarr, $_REQUEST["measureday"]);
array_push($strarr, $_REQUEST["workday"]);
array_push($strarr, $_REQUEST["doneday"]);
array_push($strarr, $_REQUEST["demand"]);
array_push($strarr, $_REQUEST["firstord"]);
array_push($strarr, $_REQUEST["firstordman"]);
array_push($strarr, $_REQUEST["firstordmantel"]);
array_push($strarr, $_REQUEST["secondord"]);
array_push($strarr, $_REQUEST["secondordman"]);
array_push($strarr, $_REQUEST["secondordmantel"]);
array_push($strarr, $_REQUEST["chargedman"]);
array_push($strarr, $_REQUEST["chargedmantel"]);
array_push($strarr, $_REQUEST["worker"]);
array_push($strarr, $_REQUEST["memo"]);
array_push($strarr, $_REQUEST["memo2"]);
array_push($strarr, $_REQUEST["material1"]);
array_push($strarr, $_REQUEST["material2"]);
array_push($strarr, $_REQUEST["material3"]);
array_push($strarr, $_REQUEST["material4"]);
array_push($strarr, $_REQUEST["material5"]);
array_push($strarr, $_REQUEST["material6"]);
array_push($strarr, $_REQUEST["material7"]);
array_push($strarr, $_REQUEST["material8"]);
array_push($strarr, $_REQUEST["material9"]);
array_push($strarr, $_REQUEST["material10"]);
array_push($strarr, $_REQUEST["update_log"]);

if($mode=="modify")
    array_push($strarr, $num);
}
			  
 require_once("../lib/mydb.php");
 $pdo = db_connect();
     
 if ($mode=="modify"){      
     try{
        $sql = "select * from jtechel." . $table . " where num=?";  // get target record
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
		$sql = "update jtechel." . $table . " set " ;     	
		for($i=0;$i<count($fieldarr);$i++)  //  필드 배열수 만큼 반복
		 {
			 if($i!=0)
				  $sql .= ' , ';
		   $sql .= $fieldarr[$i] . '=? ' ;
		 }
		    $sql .= " where num=?  LIMIT 1";	

        print $sql;			
    
		$stmh = $pdo->prepare($sql); 
		for($i=0;$i<count($strarr);$i++)  //  필드 배열수 만큼 반복	
			$stmh->bindValue($i + 1, $strarr[$i], PDO::PARAM_STR);  
		 
		 $stmh->execute();
		 $pdo->commit(); 
        } catch (PDOException $Exception) {
           $pdo->rollBack();
           print "오류: ".$Exception->getMessage();
       }                         
       
 } 
 
 // insert인 경우 
 if ($mode=="insert")	 	  
{	
     $sql = "insert into jtechel." . $table . "  ( " ;     	

	for($j=0;$j<count($fieldarr);$j++)  //  필드 배열수 만큼 반복
	 {
		 if($j!=0)
			  $sql .= ' , ';
	   $sql .= $fieldarr[$j] ;
	 }
	  $sql .= ' )  values( ';

	for($j=0;$j<count($fieldarr);$j++)  //  필드 배열수 만큼 반복
	 {
		 if($j!=0 ) 
			  $sql .= ' , ';
	   $sql .= '?';
	 }	      
	$sql .= ' ) ';
	
   try{
     $pdo->beginTransaction();
     $stmh = $pdo->prepare($sql);
	 for($i=0;$i<count($strarr);$i++)
       $stmh->bindValue($i+1,$strarr[$i],PDO::PARAM_STR);      	 
	 
     $stmh->execute();   
     $pdo->commit(); 
		} catch (PDOException $Exception) {
		   $pdo->rollBack();
		   print "오류: ".$Exception->getMessage();
	   }    
	   
// insert 후 레코드 번호를 추출한다.	   
	try{
        $sql = "select * from jtechel." . $table . " order by num desc "; 
        $stmh = $pdo->prepare($sql);
        $stmh->execute();  		
        $row = $stmh->fetch(PDO::FETCH_ASSOC);
		$num=$row["num"];
     } catch (PDOException $Exception) {
        $pdo->rollBack();
        print "오류: ".$Exception->getMessage();
     }    
	   
}
   
 if ($mode=="delete"){	 	 
   try{	   
		// 파일 삭제시 첨부파일을 먼저 삭제 한다.
		 require_once("../lib/mydb.php");
		 $pdo = db_connect();

	     $sql = "select * from jtechel." . $table . " where num = ? ";
		  $stmh = $pdo->prepare($sql); 
		  $stmh->bindValue(1,$num,PDO::PARAM_STR); 
		  $stmh->execute();      
		  $row = $stmh->fetch(PDO::FETCH_ASSOC);  // $row 배열로 DB 정보를 불러온다.		
		  // 서버의 파일이름을 삭제한다.
		  $serverfilename=$row["serverfilename"];				 
		  
				} catch (PDOException $Exception) {
				   $pdo->rollBack();
				   print "오류: ".$Exception->getMessage();
			 } 

      // 서버에서 파일삭제
    //  unlink('./img/' .  $serverfilename);			 
	   
	try{	 
     $pdo->beginTransaction();
  	 
     $sql = "delete from jtechel." . $table . " where num = ? ";
     $stmh = $pdo->prepare($sql);
     $stmh->bindValue(1,$num,PDO::PARAM_STR);      
     $stmh->execute();   
     $pdo->commit();	 
     } catch (PDOException $Exception) {
          $pdo->rollBack();
       print "오류: ".$Exception->getMessage();
     }   
}

echo $num;
// echo "<script> document.getElementById('num').value='" . $num . "'; </script>";   // 부모창에 num 기록

// var_dump($_FILES['upfile']['name']);
//var_dump($fieldarr]);
//var_dump($strarr);

 ?>
 

 

