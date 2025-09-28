<?php 

header("Content-Type: application/json");  //json을 사용하기 위해 필요한 구문

ini_set('display_errors','1');  // 화면에 warning 없애기


isset($_REQUEST["branch"])  ? $branch = $_REQUEST["branch"] :   $branch=""; 

require_once($_SERVER['DOCUMENT_ROOT'] . "/lib/mydb.php");
$pdo = db_connect();	

$guest_name = array();
$guest_tel = array();



// 별명 불러오기
 try{
	  $sql = "select * from jtechel.game_alias where branch='$branch'  ";
	  $stmh = $pdo->query($sql);       
      $stmh->execute();
      $count = $stmh->rowCount();            
	  $row = $stmh->fetch(PDO::FETCH_ASSOC);  // $row 배열로 DB 정보를 불러온다.		
		 
	  $alias_arr = explode(",", $row['alias']);
	  
	 }catch (PDOException $Exception) {
	   print "오류: ".$Exception->getMessage();
	 }





	
 try{
	 if($search ==='')
		$sql = "SELECT * FROM jtechel.game_guest order by num desc ";
	   else
		 $sql = "SELECT * FROM jtechel.game_guest WHERE REPLACE(guest_name, ' ', '') LIKE '%$search%' or  REPLACE(guest_tel, ' ', '') LIKE '%$search%'  order by num desc ";  
	   
      $stmh = $pdo->prepare($sql); 
      $stmh->execute();
      $count = $stmh->rowCount();   
	  
    if($count>0)    {      
		 while($row = $stmh->fetch(PDO::FETCH_ASSOC)) {	
	        	array_push($guest_name	, $row["guest_name"]) ;
	        	array_push($guest_tel	, $row["guest_tel"]) ;	        	

				}
	      											  		      										
		}
     }catch (PDOException $Exception) {
       print "오류: ".$Exception->getMessage();
    }

// 사람이름 + 전화번호 형태 저장
 			
//각각의 정보를 하나의 배열 변수에 넣어준다.
$data = array(
		"guest_name" => $guest_name,
		"guest_tel" => $guest_tel
);

//json 출력
echo(json_encode($data, JSON_UNESCAPED_UNICODE));

?>
 