<?php   
session_start();    
  
$level= $_SESSION["level"];
$user_name= $_SESSION["name"];

isset($_REQUEST["mode"])  ? $mode = $_REQUEST["mode"] : $mode=""; 
isset($_REQUEST["num"])  ? $num = $_REQUEST["num"] : $num=""; 
	  
// 테이블명 명시	  
$table = 'DB';
		  
 require_once("../../lib/mydb.php");
 $pdo = db_connect();
     

if($mode=="modify" || $mode=="insert"){   

$fieldarr = array();
$strarr = array();

array_push($fieldarr, 'itemname');
if(isset($_REQUEST["item1"])) array_push($fieldarr, 'item1')     ; 
if(isset($_REQUEST["item2"])) array_push($fieldarr, 'item2')     ; 
if(isset($_REQUEST["item3"])) array_push($fieldarr, 'item3')     ; 
if(isset($_REQUEST["item4"])) array_push($fieldarr, 'item4')     ; 
if(isset($_REQUEST["item5"])) array_push($fieldarr, 'item5')     ; 
if(isset($_REQUEST["item6"])) array_push($fieldarr, 'item6')     ; 
if(isset($_REQUEST["item7"])) array_push($fieldarr, 'item7')     ; 
if(isset($_REQUEST["item8"])) array_push($fieldarr, 'item8')     ; 
if(isset($_REQUEST["item9"])) array_push($fieldarr, 'item9')     ; 
// array_push($fieldarr, 'filename1');
// array_push($fieldarr, 'filename2');

array_push($strarr, $_REQUEST["itemname"]);
if(isset($_REQUEST["item1"])) array_push($strarr, $_REQUEST["item1"])     ; 
if(isset($_REQUEST["item2"])) array_push($strarr, $_REQUEST["item2"])     ; 
if(isset($_REQUEST["item3"])) array_push($strarr, $_REQUEST["item3"])     ; 
if(isset($_REQUEST["item4"])) array_push($strarr, $_REQUEST["item4"])     ; 
if(isset($_REQUEST["item5"])) array_push($strarr, $_REQUEST["item5"])     ; 
if(isset($_REQUEST["item6"])) array_push($strarr, $_REQUEST["item6"])     ; 
if(isset($_REQUEST["item7"])) array_push($strarr, $_REQUEST["item7"])     ; 
if(isset($_REQUEST["item8"])) array_push($strarr, $_REQUEST["item8"])     ; 
if(isset($_REQUEST["item9"])) array_push($strarr, $_REQUEST["item9"])     ; 


if($mode=="modify")
    array_push($strarr, $num);
}

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
 

 

