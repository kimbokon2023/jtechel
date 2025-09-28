<?php   

/// 개별로 신청하는 것에 대한 DB처리 구간 ///
/// 개별로 신청하는 것에 대한 DB처리 구간 ///

session_start();   

ini_set('display_errors','On');  // 화면에 warning 없애기	

$level= $_SESSION["level"];
$user_name= $_SESSION["name"];
$id= $_SESSION["userid"];

header("Content-Type: application/json");  //json을 사용하기 위해 필요한 구문  

isset($_REQUEST["mode"])  ? $mode = $_REQUEST["mode"] : $mode=""; 
isset($_REQUEST["num"])  ? $num = $_REQUEST["num"] : $num=""; 

include 'request.php';		  

$inputAmounts = $_REQUEST['input_amount'];
$outputAmounts1 = $_REQUEST['output_amount1'];
$outputAmounts2 = $_REQUEST['output_amount2'];
$outputAmounts3 = $_REQUEST['output_amount3'];
$outputAmounts4 = $_REQUEST['output_amount4'];
$outputAmounts5 = $_REQUEST['output_amount5'];

$namearr_arr1 = $_REQUEST['namearr_arr1'];
$namearr_arr2 = $_REQUEST['namearr_arr2'];
$namearr_arr3 = $_REQUEST['namearr_arr3'];
$namearr_arr4 = $_REQUEST['namearr_arr4'];
$namearr_arr5 = $_REQUEST['namearr_arr5'];

$textArr1 = $_REQUEST['text_arr1'];
$textArr2 = $_REQUEST['text_arr2'];
$textArr3 = $_REQUEST['text_arr3'];
$textArr4 = $_REQUEST['text_arr4'];
$textArr5 = $_REQUEST['text_arr5'];
$textArr6 = $_REQUEST['text_arr6'];

$memo = $_REQUEST['memo'];
$receivable = $_REQUEST['receivable'];

$input_arr = !empty($inputAmounts) ? implode(',', $inputAmounts) : '';
$output_arr1 = !empty($outputAmounts1) ? implode(',', $outputAmounts1) : '';
$output_arr2 = !empty($outputAmounts2) ? implode(',', $outputAmounts2) : '';
$output_arr3 = !empty($outputAmounts3) ? implode(',', $outputAmounts3) : '';
$output_arr4 = !empty($outputAmounts4) ? implode(',', $outputAmounts4) : '';
$output_arr5 = !empty($outputAmounts5) ? implode(',', $outputAmounts5) : '';


$text_arr1 = !empty($textArr1) ? implode(',', $textArr1) : '';
$text_arr2 = !empty($textArr2) ? implode(',', $textArr2) : '';
$text_arr3 = !empty($textArr3) ? implode(',', $textArr3) : '';
$text_arr4 = !empty($textArr4) ? implode(',', $textArr4) : '';
$text_arr5 = !empty($textArr5) ? implode(',', $textArr5) : '';
$text_arr6 = !empty($textArr6) ? implode(',', $textArr6) : '';


$input_plus_arr = !empty($input_plus) ? implode(',', $input_plus) : '';
$input_minus_arr = !empty($input_minus) ? implode(',', $input_minus) : '';
$dispose_plus_arr = !empty($dispose_plus) ? implode(',', $dispose_plus) : '';
$dispose_minus_arr = !empty($dispose_minus) ? implode(',', $dispose_minus) : '';

$mark = isset($_POST['checkedRows']) ? implode(',', $_POST['checkedRows']) : '';


// 이후 저장 등의 처리를 수행하면 됩니다.


$namearr1 = !empty($namearr_arr1) ? implode(',', $namearr_arr1) : '';
$namearr2 = !empty($namearr_arr2) ? implode(',', $namearr_arr2) : '';
$namearr3 = !empty($namearr_arr3) ? implode(',', $namearr_arr3) : '';
$namearr4 = !empty($namearr_arr4) ? implode(',', $namearr_arr4) : '';
$namearr5 = !empty($namearr_arr5) ? implode(',', $namearr_arr5) : '';



$branch = $_POST['branch'];
// $checksave = $_POST['checksave'];

$mcno = '';

			  
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
				
     // $mark는 별도로 저장함...  체크박스
	try{
		$pdo->beginTransaction();   
		$sql = "update jtechel.game set  registedate=?, mcno=?, input_arr=?,  output_arr1=?,  output_arr2=?,  output_arr3=?,  output_arr4=?, output_arr5=?,  inputsum=?,  outputsum=? , mcount=? ,  item=?, updatetime=? , text1=? , text2=? , text3=? , text4=? , text5=? , text6=? , memo=? , receivable=? , input_plus=?  , input_minus=?  , dispose_plus=?  , dispose_minus=?  , mark=?  ,  namearr1=?  , namearr2=?  , namearr3=?  , namearr4=?  , namearr5=? ,  branch=?  ";
		$sql .= " where num=?  LIMIT 1";	
		
		$stmh = $pdo->prepare($sql); 	
		$stmh->bindValue(1, $registedate, PDO::PARAM_STR);  
		$stmh->bindValue(2, $mcno, PDO::PARAM_STR);  
		$stmh->bindValue(3, $input_arr, PDO::PARAM_STR);  
		$stmh->bindValue(4, $output_arr1, PDO::PARAM_STR);      
		$stmh->bindValue(5, $output_arr2, PDO::PARAM_STR);      
		$stmh->bindValue(6, $output_arr3, PDO::PARAM_STR);      
		$stmh->bindValue(7, $output_arr4, PDO::PARAM_STR);          
		$stmh->bindValue(8, $output_arr5, PDO::PARAM_STR);          
		$stmh->bindValue(9, $inputsum, PDO::PARAM_STR);        
		$stmh->bindValue(10, $outputsum, PDO::PARAM_STR);            
		$stmh->bindValue(11, $mcount, PDO::PARAM_STR);            
		$stmh->bindValue(12, $item, PDO::PARAM_STR);            
		$stmh->bindValue(13, $updatetime, PDO::PARAM_STR);              
		$stmh->bindValue(14, $text_arr1, PDO::PARAM_STR);              
		$stmh->bindValue(15, $text_arr2, PDO::PARAM_STR);              
		$stmh->bindValue(16, $text_arr3, PDO::PARAM_STR);              
		$stmh->bindValue(17, $text_arr4, PDO::PARAM_STR);              
		$stmh->bindValue(18, $text_arr5, PDO::PARAM_STR);              
		$stmh->bindValue(19, $text_arr6, PDO::PARAM_STR);              
		$stmh->bindValue(20, $memo, PDO::PARAM_STR);              
		$stmh->bindValue(21, $receivable, PDO::PARAM_STR);                  
		$stmh->bindValue(22, $input_plus_arr, PDO::PARAM_STR);                  
		$stmh->bindValue(23, $input_minus_arr, PDO::PARAM_STR);                  
		$stmh->bindValue(24, $dispose_plus_arr, PDO::PARAM_STR);                  
		$stmh->bindValue(25, $dispose_minus_arr, PDO::PARAM_STR);                  
		$stmh->bindValue(26, $mark, PDO::PARAM_STR);                  
		$stmh->bindValue(27, $namearr1, PDO::PARAM_STR);                  
		$stmh->bindValue(28, $namearr2, PDO::PARAM_STR);                  
		$stmh->bindValue(29, $namearr3, PDO::PARAM_STR);                  
		$stmh->bindValue(30, $namearr4, PDO::PARAM_STR);  
		$stmh->bindValue(31, $namearr5, PDO::PARAM_STR);  
		$stmh->bindValue(32, $branch, PDO::PARAM_STR);  
		$stmh->bindValue(33, $num, PDO::PARAM_STR);            
	 
	 $stmh->execute();
     $pdo->commit(); 
        } catch (PDOException $Exception) {
           $pdo->rollBack();
           print "오류: ".$Exception->getMessage();
       }                         
      $state = "자료수정" ; 
 } 
 if ($mode == "insert") {
    // 오늘 날짜 가져오기
    $today = date("Y-m-d");
    
    // registedate의 날짜와 item이 '신규'인 것이 있는지 확인
    $sql = "SELECT COUNT(*) FROM jtechel.game WHERE registedate = ? AND item = '신규'";
    $stmh = $pdo->prepare($sql);
    $stmh->bindValue(1, $today, PDO::PARAM_STR);
    $stmh->execute();
    $count = $stmh->fetchColumn();
    
    if ($count > 0) {
        $state = "신규입력 (중복)";
    } else {
        try {
            $pdo->beginTransaction();
            
            $sql = "INSERT INTO jtechel.game(registedate, mcno, input_arr, output_arr1, output_arr2, output_arr3, output_arr4,  output_arr5 , inputsum, outputsum, mcount, item, updatetime, text1, text2, text3, text4, text5, text6, memo, receivable, input_plus, input_minus, dispose_plus, dispose_minus, mark, namearr1, namearr2, namearr3, namearr4, namearr5, branch ) ";
            $sql .= "VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? , ? , ? , ? , ? , ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? , ? , ?)";
            
            $stmh = $pdo->prepare($sql);
            
            $stmh->bindValue(1, $registedate, PDO::PARAM_STR);
            $stmh->bindValue(2, $mcno, PDO::PARAM_STR);
            $stmh->bindValue(3, $input_arr, PDO::PARAM_STR);
            $stmh->bindValue(4, $output_arr1, PDO::PARAM_STR);
            $stmh->bindValue(5, $output_arr2, PDO::PARAM_STR);
            $stmh->bindValue(6, $output_arr3, PDO::PARAM_STR);
            $stmh->bindValue(7, $output_arr4, PDO::PARAM_STR);
            $stmh->bindValue(8, $output_arr5, PDO::PARAM_STR);
            $stmh->bindValue(9, $inputsum, PDO::PARAM_STR);
            $stmh->bindValue(10, $outputsum, PDO::PARAM_STR);
            $stmh->bindValue(11, $mcount, PDO::PARAM_STR);
            $stmh->bindValue(12, $item, PDO::PARAM_STR);
            $stmh->bindValue(13, $updatetime, PDO::PARAM_STR);
			$stmh->bindValue(14, $text_arr1, PDO::PARAM_STR);              
			$stmh->bindValue(15, $text_arr2, PDO::PARAM_STR);              
			$stmh->bindValue(16, $text_arr3, PDO::PARAM_STR);              
			$stmh->bindValue(17, $text_arr4, PDO::PARAM_STR);              
			$stmh->bindValue(18, $text_arr5, PDO::PARAM_STR);                           
			$stmh->bindValue(19, $text_arr6, PDO::PARAM_STR);              
			$stmh->bindValue(20, $memo, PDO::PARAM_STR);              
			$stmh->bindValue(21, $receivable, PDO::PARAM_STR);                  
			$stmh->bindValue(22, $input_plus_arr, PDO::PARAM_STR);                  
			$stmh->bindValue(23, $input_minus_arr, PDO::PARAM_STR);                  
			$stmh->bindValue(24, $dispose_plus_arr, PDO::PARAM_STR);                  
			$stmh->bindValue(25, $dispose_minus_arr, PDO::PARAM_STR);                  
			$stmh->bindValue(26, $mark, PDO::PARAM_STR);                  
			$stmh->bindValue(27, $namearr1, PDO::PARAM_STR);                  
			$stmh->bindValue(28, $namearr2, PDO::PARAM_STR);                  
			$stmh->bindValue(29, $namearr3, PDO::PARAM_STR);                  
			$stmh->bindValue(30, $namearr4, PDO::PARAM_STR);  
			$stmh->bindValue(31, $namearr5, PDO::PARAM_STR);  			
			$stmh->bindValue(32, $branch, PDO::PARAM_STR);  			
			
            $stmh->execute();
            $pdo->commit();
            
            $state = "신규입력";
        } catch (PDOException $Exception) {
            $pdo->rollBack();
            print "오류: " . $Exception->getMessage();
        }
    }
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
		"input_plus" =>  $input_plus_arr,		
		"input_minus" =>  $input_minus_arr,				
		"dispose_plus" =>  $dispose_plus_arr,		
		"dispose_minus" =>  $dispose_minus_arr,				
		"namearr1" =>  $namearr1,
		"namearr2" =>  $namearr2,
		"namearr3" =>  $namearr3,
		"namearr4" =>  $namearr4,
		"namearr5" =>  $namearr5,
		
		"input_arr"   =>  $input_arr  ,  
		"output_arr1" =>  $output_arr1,  
		"output_arr2" =>  $output_arr2,  
		"output_arr3" =>  $output_arr3,  
		"output_arr4" =>  $output_arr4,  
		"output_arr5" =>  $output_arr5,
		
		"mark" => $mark,

		"text_arr1" =>  $text_arr1,           
		"text_arr2" =>  $text_arr2,           
		"text_arr3" =>  $text_arr3,           
		"text_arr4" =>  $text_arr4,           
		"text_arr5" =>  $text_arr5,           
		"text_arr6" =>  $text_arr6,  		
		"branch" =>  $branch ,

		'outputAmounts1' => $outputAmounts1		
 		
);

//json 출력
echo(json_encode($data, JSON_UNESCAPED_UNICODE));   
   
 ?>