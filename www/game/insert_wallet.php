<?php   
/// 개별로 신청하는 것에 대한 DB처리 구간 ///
/// 개별로 신청하는 것에 대한 DB처리 구간 ///

session_start();   

$level= $_SESSION["level"];
$user_name= $_SESSION["name"];
$id= $_SESSION["userid"];

// header("Content-Type: application/json");  //json을 사용하기 위해 필요한 구문  

isset($_REQUEST["mode"])  ? $mode = $_REQUEST["mode"] : $mode=""; 
isset($_REQUEST["num"])  ? $num = $_REQUEST["num"] : $num=""; 
isset($_REQUEST["branch"])  ? $branch = $_REQUEST["branch"] : $branch=""; 

include 'request.php';		  

$inputAmounts = isset($_POST['input_amount']) ? $_POST['input_amount'] : array();
$outputAmounts1 = isset($_POST['output_amount1']) ? $_POST['output_amount1'] : array();

$input_arr = !empty($inputAmounts) ? implode(',', $inputAmounts) : '';
$output_arr1 = !empty($outputAmounts1) ? implode(',', $outputAmounts1) : '';

$alias_arr = !empty($alias) ? implode(',', $alias) : '';

$input_plus_arr = implode(',', $input_plus);
$input_minus_arr = implode(',', $input_minus);
$dispose_plus_arr = implode(',', $dispose_plus);
$dispose_minus_arr = implode(',', $dispose_minus);
			  
require_once($_SERVER['DOCUMENT_ROOT'] . "/lib/mydb.php");
$pdo = db_connect();

list($microseconds, $seconds) = explode(' ', microtime());
$milliseconds = round($microseconds * 1000);

$updatetime = date("H:i:s") . '.' . $milliseconds; 


     
 if ($mode=="modify"){ 
     try{
        $sql = "select * from jtechel.game where num=?";  // get target record
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
        $sql = "update jtechel.game set  registedate=?, mcno=?, input_arr=?,  output_arr1=?,  inputsum=?,  outputsum=? ,  item=?, updatetime=?  , input_plus=?  , input_minus=?  , dispose_plus=?  , dispose_minus=?  , branch = ?  ";
        $sql .= " where num=?  LIMIT 1";		
		
    
	$stmh = $pdo->prepare($sql); 	
	$stmh->bindValue(1, $registedate, PDO::PARAM_STR);  
	$stmh->bindValue(2, $mcno, PDO::PARAM_STR);  
	$stmh->bindValue(3, $input_arr, PDO::PARAM_STR);  
    $stmh->bindValue(4, $output_arr1, PDO::PARAM_STR);      
    $stmh->bindValue(5, $inputsum, PDO::PARAM_STR);        
    $stmh->bindValue(6, $outputsum, PDO::PARAM_STR);                
    $stmh->bindValue(7, $item, PDO::PARAM_STR);            
    $stmh->bindValue(8, $updatetime, PDO::PARAM_STR);     
	$stmh->bindValue(9, $input_plus_arr, PDO::PARAM_STR);                  
	$stmh->bindValue(10, $input_minus_arr, PDO::PARAM_STR);                  
	$stmh->bindValue(11, $dispose_plus_arr, PDO::PARAM_STR);                  
	$stmh->bindValue(12, $dispose_minus_arr, PDO::PARAM_STR);  	
	$stmh->bindValue(13, $branch, PDO::PARAM_STR);  	
    $stmh->bindValue(14, $num, PDO::PARAM_STR);            
	 
	 $stmh->execute();
     $pdo->commit(); 
        } catch (PDOException $Exception) {
           $pdo->rollBack();
           print "오류: ".$Exception->getMessage();
       }                         
      $state = "자료수정" ; 
	  
// 별명저장	  

  if($branch=="아우디")
	    $branchNum = 1;
	  else
		  $branchNum = 2;
	 require_once("../lib/mydb.php");
	 $pdo = db_connect();
	 $pdo->beginTransaction();
	 $sql = "update jtechel.game_alias set alias = ? where num=? " ;
	 $stmh = $pdo->prepare($sql); 
	 $stmh->bindValue(1, $alias_arr, PDO::PARAM_STR);   
	 $stmh->bindValue(2, $branchNum , PDO::PARAM_STR);   
	 $stmh->execute();
	 $pdo->commit(); 	  
	  
	  
 } 
 if ($mode == "insert") {
    // 오늘 날짜 가져오기
    $today = date("Y-m-d");
        try {
            $pdo->beginTransaction();
            
            $sql = "INSERT INTO jtechel.game(registedate, mcno, input_arr, output_arr1, inputsum, outputsum, item, updatetime, input_plus, input_minus, dispose_plus, dispose_minus , branch ) ";
            $sql .= "VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )";
            
            $stmh = $pdo->prepare($sql);
            
            $stmh->bindValue(1, $registedate, PDO::PARAM_STR);
            $stmh->bindValue(2, $mcno, PDO::PARAM_STR);
            $stmh->bindValue(3, $input_arr, PDO::PARAM_STR);
            $stmh->bindValue(4, $output_arr1, PDO::PARAM_STR);
            $stmh->bindValue(5, $inputsum, PDO::PARAM_STR);
            $stmh->bindValue(6, $outputsum, PDO::PARAM_STR);            
            $stmh->bindValue(7, $item, PDO::PARAM_STR);
            $stmh->bindValue(8, $updatetime, PDO::PARAM_STR);
			$stmh->bindValue(9, $input_plus_arr, PDO::PARAM_STR);                  
			$stmh->bindValue(10, $input_minus_arr, PDO::PARAM_STR);                  
			$stmh->bindValue(11, $dispose_plus_arr, PDO::PARAM_STR);                  
			$stmh->bindValue(12, $dispose_minus_arr, PDO::PARAM_STR);  			
			$stmh->bindValue(13, $branch, PDO::PARAM_STR);  				
            
            $stmh->execute();
            $pdo->commit();
            
            $state = "신규입력";
        } catch (PDOException $Exception) {
            $pdo->rollBack();
            print "오류: " . $Exception->getMessage();
        }
		
// 별명저장	  
// 별명저장	  

  if($branch=="아우디")
	    $branchNum = 1;
	  else
		  $branchNum = 2;
	 require_once("../lib/mydb.php");
	 $pdo = db_connect();
	 $pdo->beginTransaction();
	 $sql = "update jtechel.game_alias set alias = ? where num=? " ;
	 $stmh = $pdo->prepare($sql); 
	 $stmh->bindValue(1, $alias_arr, PDO::PARAM_STR);   
	 $stmh->bindValue(2, $branchNum , PDO::PARAM_STR);   
	 $stmh->execute();
	 $pdo->commit(); 	 		
   
}


 if ($mode=="delete"){	 	 
   try{
     $pdo->beginTransaction();
  	 
     $sql = "delete from  jtechel.game where num = ?";  
     $stmh = $pdo->prepare($sql);
     $stmh->bindValue(1,$num,PDO::PARAM_STR);      
     $stmh->execute();   
     $pdo->commit();	 
     } catch (PDOException $Exception) {
          $pdo->rollBack();
       print "오류: ".$Exception->getMessage();
     }  
	 
	 $state = "삭제" ; 
	 
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
		"receivable" =>  $receivable,		
		"memo" =>  $memo,		
		"mcount" =>  $mcount,		
		"input_plus" =>  $input_plus_arr,		
		"input_minus" =>  $input_minus_arr,				
		"dispose_plus" =>  $dispose_plus_arr,		
		"dispose_minus" =>  $dispose_minus_arr,
		"branch" =>  $branch,
           		
);

//json 출력
echo(json_encode($data, JSON_UNESCAPED_UNICODE));   
   
 ?>