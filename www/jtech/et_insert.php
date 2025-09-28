<?php   
session_start();    
  
$level= $_SESSION["level"];
$user_name= $_SESSION["name"];

isset($_REQUEST["mode"])  ? $mode = $_REQUEST["mode"] : $mode=""; 
isset($_REQUEST["num"])  ? $num = $_REQUEST["num"] : $num=""; 
	  
$table = 'jtech';

$fieldarr = array();
$strarr = array();

array_push($fieldarr, 'et_writeday');
array_push($fieldarr, 'et_wpname');
array_push($fieldarr, 'et_deadline');
array_push($fieldarr, 'et_content');
array_push($fieldarr, 'et_note');
array_push($fieldarr, 'et_paymethod');
array_push($fieldarr, 'et_validation');
array_push($fieldarr, 'et_itemname');
array_push($fieldarr, 'et_receiver');


if($mode=="modify" ) {
	array_push($strarr, $_REQUEST["et_writeday"]);
	array_push($strarr, $_REQUEST["et_wpname"]);
	array_push($strarr, $_REQUEST["et_deadline"]);	
	array_push($strarr, $_REQUEST["et_content"]);
	array_push($strarr, $_REQUEST["et_note"]);
	array_push($strarr, $_REQUEST["et_paymethod"]);
	array_push($strarr, $_REQUEST["et_validation"]);
	array_push($strarr, $_REQUEST["et_itemname"]);
	array_push($strarr, $_REQUEST["et_receiver"]);
}

if($mode=="delete" ) {
	array_push($strarr, null);
	array_push($strarr, null);
	array_push($strarr, null);
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
 

 

