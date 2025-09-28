 <?php
header("Content-Type: application/json");  //json을 사용하기 위해 필요한 구문

isset($_REQUEST["codenum"])  ? $codenum=$_REQUEST["codenum"] :   $codenum=''; 
isset($_REQUEST["comname"])  ? $comname=$_REQUEST["comname"] :   $comname=''; 
isset($_REQUEST["chief"])  ? $chief=$_REQUEST["chief"] :   $chief=''; 
isset($_REQUEST["comsect"])  ? $comsect=$_REQUEST["comsect"] :   $comsect=''; 
isset($_REQUEST["comitem"])  ? $comitem=$_REQUEST["comitem"] :   $comitem=''; 
isset($_REQUEST["address"])  ? $address=$_REQUEST["address"] :   $address=''; 
isset($_REQUEST["email"])  ? $email=$_REQUEST["email"] :   $email=''; 
isset($_REQUEST["searchmemo"])  ? $searchmemo=$_REQUEST["searchmemo"] :   $searchmemo=''; 

require_once("../lib/mydb.php");
$pdo = db_connect();
	 
// 데이터 신규 등록하는 구간
   try{
     $pdo->beginTransaction();  	 
     $sql = "insert into mirae8440.escustreg(" ;
     $sql .="codenum, comname, chief, comsect, comitem, address, email, searchmemo";	
     $sql .= ") ";
     $sql .= " values(?, ?, ?, ?, ?, ?, ?, ?) "; // 총 8	   
     $stmh = $pdo->prepare($sql); 
     $stmh->bindValue(1, $codenum, PDO::PARAM_STR);             
     $stmh->bindValue(2, $comname, PDO::PARAM_STR);             
     $stmh->bindValue(3, $chief, PDO::PARAM_STR);             
     $stmh->bindValue(4, $comsect, PDO::PARAM_STR);             
     $stmh->bindValue(5, $comitem, PDO::PARAM_STR);             
     $stmh->bindValue(6, $address, PDO::PARAM_STR);             
     $stmh->bindValue(7, $email, PDO::PARAM_STR);             
     $stmh->bindValue(8, $searchmemo, PDO::PARAM_STR);                 
	
     $stmh->execute();
     $pdo->commit(); 
     } catch (PDOException $Exception) {
          $pdo->rollBack();
       print "오류: ".$Exception->getMessage();
     }   
	 
//각각의 정보를 하나의 배열 변수에 넣어준다.
$data = array(
		"codenum" =>         $codenum,
		"comname" =>         $comname,
		"chief" =>         $chief,
		"comsect" =>         $comsect,
		"comitem" =>         $comitem,
		"address" =>         $address,
		"email" =>         $email,
		"searchmemo" =>         $searchmemo,
);

//json 출력
echo(json_encode($data, JSON_UNESCAPED_UNICODE));

?>
 