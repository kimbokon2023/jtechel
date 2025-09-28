<?php   
session_start();    
  
$level= $_SESSION["level"];
$user_name= $_SESSION["name"];

isset($_REQUEST["mode"])  ? $mode = $_REQUEST["mode"] : $mode=""; 
isset($_REQUEST["num"])  ? $num = $_REQUEST["num"] : $num=0; 
isset($_REQUEST["tmpKey"])  ? $tmpKey = $_REQUEST["tmpKey"] : $tmpKey=0; 
	  
$table = 'jtech';

if($mode=="modify" || $mode=="insert"){   
$fieldarr = array(
    'workplacename',
    'address',
    'regist_day',
    'measureday',
    'workday',
    'doneday',
    'demand',
    'donedemand',
    'firstord',
    'firstordman',
    'firstordmantel',
    'secondord',
    'secondordman',
    'secondordmantel',
    'chargedman',
    'chargedmantel',
    'worker',
    'memo',
    'memo2',
    'material1',
    'material2',
    'material3',
    'material4',
    'material5',
    'material6',
    'material7',
    'material8',
    'material9',
    'material10',
    'update_log',
    'pjnum',
    'customer'
);

$strarr = array();
foreach ($fieldarr as $field) {
    $value = isset($_REQUEST[$field]) ? $_REQUEST[$field] : "";
    array_push($strarr, $value);
}


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
			 if($i!==0)
				  $sql .= ' , ';
		   $sql .= $fieldarr[$i] . '=? ' ;
		 }
		    $sql .= " where num=?  LIMIT 1";	

       // print $sql;			
    
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
 

 

