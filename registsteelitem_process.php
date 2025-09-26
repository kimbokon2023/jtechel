<?php
header("Content-Type: application/json");  //json을 사용하기 위해 필요한 구문

isset($_REQUEST["steelitem"])  ? $steelitem=$_REQUEST["steelitem"] :   $steelitem=''; 

$steelitem_arr = explode(",",$steelitem[0]);

// var_dump($steelitem_arr);

// die();

// 데이터 수정하는 구간			
	  
// Grid는 update인 경우 삭제하고 추가하는 방식으로 해야 함. 기존의 데이터의 개수가 다를 수 있으니 이렇게 처리함.
   require_once("./lib/mydb.php");
   $pdo = db_connect();
   
   try{									
     $pdo->beginTransaction();
     $sql = "delete from mirae8440.steelitem ";  
     $stmh = $pdo->prepare($sql);
     $stmh->execute();   
     $pdo->commit();	 
     } catch (Exception $ex) {
        $pdo->rollBack();
        print "오류: ".$Exception->getMessage();
   }  

// 삭제 후 insert grid 구문
	$rec_num = count($steelitem_arr); 
	
	for($i=0; $i<$rec_num; $i++) {	 
	   try{
		 $pdo->beginTransaction();		 
		 $sql = "insert into mirae8440.steelitem (item) ";		 
		 $sql .= "  values(?) ";
		 
		$stmh = $pdo->prepare($sql); 
		$stmh->bindValue(1, $steelitem_arr[$i] , PDO::PARAM_STR);               
		
		 $stmh->execute();
		 $pdo->commit(); 
		 } catch (PDOException $Exception) {
			  $pdo->rollBack();
		   print "오류: ".$Exception->getMessage();
		 }   	 
	}   
 
//각각의 정보를 하나의 배열 변수에 넣어준다.
$data = array(
		"steelitem_arr" =>  $steelitem_arr,
);

//json 출력
echo(json_encode($data, JSON_UNESCAPED_UNICODE));

?>

