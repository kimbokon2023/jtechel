<?php   
session_start();    
  
$level= $_SESSION["level"];
$user_name= $_SESSION["name"];

isset($_REQUEST["mode"])  ? $mode = $_REQUEST["mode"] : $mode=""; 
isset($_REQUEST["num"])  ? $num = $_REQUEST["num"] : $num=""; 
	  
$table = 'os';

$fieldarr = array();
$strarr = array();

array_push($fieldarr, 'et_writeday');
array_push($fieldarr, 'et_wpname');
array_push($fieldarr, 'et_schedule');
array_push($fieldarr, 'et_person');
array_push($fieldarr, 'et_content');
array_push($fieldarr, 'et_note');


if($mode=="modify" ) {
	array_push($strarr, $_REQUEST["et_writeday"]);
	array_push($strarr, $_REQUEST["et_wpname"]);
	array_push($strarr, $_REQUEST["et_schedule"]);
	array_push($strarr, $_REQUEST["et_person"]);
	array_push($strarr, $_REQUEST["et_content"]);
	array_push($strarr, $_REQUEST["et_note"]);
}

if($mode=="delete" ) {
	array_push($strarr, null);
	array_push($strarr, null);
	array_push($strarr, null);
	array_push($strarr, null);
	array_push($strarr, null);
	array_push($strarr, null);
}

array_push($strarr, $num);

			  
 require_once("../lib/mydb.php");
 $pdo = db_connect();
  
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
       
 
echo $num;
// echo "<script> document.getElementById('num').value='" . $num . "'; </script>";   // 부모창에 num 기록

// var_dump($_FILES['upfile']['name']);
//var_dump($fieldarr]);
//var_dump($strarr);

 ?>
 

 

