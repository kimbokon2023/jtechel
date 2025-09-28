<?php   

/// 개별로 신청하는 것에 대한 DB처리 구간 ///
/// 개별로 신청하는 것에 대한 DB처리 구간 ///

// 중복 이름과 전화번호가 입력되지 않도록 하기 위한 로직을 추가한다.

session_start();   

$level= $_SESSION["level"];
$user_name= $_SESSION["name"];
$id= $_SESSION["userid"];

header("Content-Type: application/json");  //json을 사용하기 위해 필요한 구문  

isset($_REQUEST["mode"])  ? $mode = $_REQUEST["mode"] : $mode=""; 
isset($_REQUEST["num"])  ? $num = $_REQUEST["num"] : $num=""; 
isset($_REQUEST["guest_registedate"])  ? $guest_registedate = $_REQUEST["guest_registedate"] : $guest_registedate=""; 
isset($_REQUEST["guest_name"])  ? $guest_name = $_REQUEST["guest_name"] : $guest_name=""; 
isset($_REQUEST["guest_tel"])  ? $guest_tel = $_REQUEST["guest_tel"] : $guest_tel=""; 
isset($_REQUEST["branch"])  ? $branch = $_REQUEST["branch"] : $branch=""; 
			  
require_once($_SERVER['DOCUMENT_ROOT'] . "/lib/mydb.php");
$pdo = db_connect();

list($microseconds, $seconds) = explode(' ', microtime());
$milliseconds = round($microseconds * 1000);

$updatetime = date("H:i:s") . '.' . $milliseconds; 

$isNotExist = true; 
 
 if ($mode == "insert") {
    // 오늘 날짜 가져오기
    $today = date("Y-m-d");  

     try{
        $sql = "select * from jtechel.game_guest ";  // get target record
        $stmh = $pdo->prepare($sql); 
        $stmh->bindValue(1,$num,PDO::PARAM_STR); 
        $stmh->execute(); 
        while($row = $stmh->fetch(PDO::FETCH_ASSOC)) {
		
		  if($row["branch"] ==  $branch && $row["guest_name"] ==  $guest_name && $row["guest_tel"] ==  $guest_tel)
			    $isNotExist = false; 
			
		}
		
     } catch (PDOException $Exception) {
        $pdo->rollBack();
        print "오류: ".$Exception->getMessage();
     } 

    // 같은 자료가 없다면?
if($isNotExist)
	{

        try {
            $pdo->beginTransaction();
            
            $sql = "INSERT INTO jtechel.game_guest(guest_registedate, guest_name,guest_tel, branch  ) ";
            $sql .= "VALUES(?, ?, ?, ? )";
            
            $stmh = $pdo->prepare($sql);
            
		$stmh->bindValue(1, $guest_registedate, PDO::PARAM_STR);  
		$stmh->bindValue(2, $guest_name, PDO::PARAM_STR);  
		$stmh->bindValue(3, $guest_tel, PDO::PARAM_STR);  		 			
		$stmh->bindValue(4, $branch, PDO::PARAM_STR);  		 			
            
            $stmh->execute();
            $pdo->commit();
            
            $state = "회원 신규입력";
        } catch (PDOException $Exception) {
            $pdo->rollBack();
            print "오류: " . $Exception->getMessage();
        }
    }
 } // end of if insert

     
 if ($mode=="modify"){ 
     try{
        $sql = "select * from jtechel.game_guest where num=?";  // get target record
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
		$sql = "update jtechel.game_guest set  guest_registedate=?, guest_name=?, guest_tel=?, branch=? ";
		$sql .= " where num=?  LIMIT 1";	
		
		$stmh = $pdo->prepare($sql); 	
		$stmh->bindValue(1, $guest_registedate, PDO::PARAM_STR);  
		$stmh->bindValue(2, $guest_name, PDO::PARAM_STR);  
		$stmh->bindValue(3, $guest_tel, PDO::PARAM_STR);  		
		$stmh->bindValue(4, $branch, PDO::PARAM_STR);  		
		$stmh->bindValue(5, $num, PDO::PARAM_STR);            
	 
	 $stmh->execute();
     $pdo->commit(); 
        } catch (PDOException $Exception) {
           $pdo->rollBack();
           print "오류: ".$Exception->getMessage();
       }                         
      $state = "회원 자료수정" ; 
 } 

 if ($mode=="delete"){	 	 
   try{
     $pdo->beginTransaction();
  	 
     $sql = "delete from  jtechel.game_guest where num = ?";  
     $stmh = $pdo->prepare($sql);
     $stmh->bindValue(1,$num,PDO::PARAM_STR);      
     $stmh->execute();   
     $pdo->commit();	 
     } catch (PDOException $Exception) {
          $pdo->rollBack();
       print "오류: ".$Exception->getMessage();
     }  
	 
	 $state = "회원명단 삭제" ; 	 
}

if( $state !== "자료수정") 
{
	 $data=date("Y-m-d H:i:s") . " - " . $_SESSION["userid"] . " - " . $_SESSION["name"] . " - " . $state ;	
	 require_once("../lib/mydb.php");
	 $pdo = db_connect();
	 $pdo->beginTransaction();
	 $sql = "insert into jtechel.gamelog(data) values(?) " ;
	 $stmh = $pdo->prepare($sql); 
	 $stmh->bindValue(1, $data, PDO::PARAM_STR);   
	 $stmh->execute();
	 $pdo->commit(); 
 }


//각각의 정보를 하나의 배열 변수에 넣어준다.
$data = array(
		"registedate" =>  $registedate,		
		"guest_name" =>  $guest_name,		
		"guest_tel" =>  $guest_tel,		
		"branch" =>  $branch		
);

//json 출력
echo(json_encode($data, JSON_UNESCAPED_UNICODE));   
   
 ?>