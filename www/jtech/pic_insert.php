 <?php session_start();  
 
 include "../php/common.php"; 

 if(isset($_REQUEST["num"]))
    $num=$_REQUEST["num"];
		 else 
			$num="";	
		
 if(isset($_REQUEST["idx"]))
    $idx=$_REQUEST["idx"];
		 else 
			$idx="";			
 
  if(isset($_REQUEST["check"])) 
	 $check=$_REQUEST["check"]; 
   else
     $check=$_POST["check"]; 	


 if(isset($_REQUEST["tablename"]))
    $tablename=$_REQUEST["tablename"];
		 else 
			$tablename="";	

	if (isset($_REQUEST["item"])) {
		$item = $_REQUEST["item"];
	} else {
		$item = "";
	}

$arrayPic = 0;
$response = array();	
$filepaths = array();

$uploads_dir = './uploads'; //업로드 폴더 -현재 처리하는 폴더 하부로 imgtest 폴더


	switch ($item) {
		case 'before':
			$filechoice = 'upfile';
			break;
		case 'mid':
			$filechoice = 'Midupfile';
			break;
		case 'after':
			$filechoice = 'Afterupfile';
			break;
		case 'beforeArr':
		case 'midArr':
		case 'afterArr':
			$filechoice = 'file';
			$arrayPic = 1;
			break;
		default:
			$filechoice = '';
			break;
	}


$countfiles = count($_FILES[$filechoice]['name']);
for($i=0;$i<$countfiles;$i++){
	$filename = $_FILES[$filechoice]['name'][$i];
//	$target_file = 'uploads/'.$filename;
//	move_uploaded_file($_FILES['file']['tmp_name'][$i],$target_file);
//	$statement->execute(array($filename,$target_file));
// print $filename;

if($filename !='') {   			
	//Auth key
	define('UPLOAD_ERR_INI_SIZE',"100000000");

    $allowed_ext = array('jpg','jpeg','png','gif','JPG','JPEG','PNG','GIF'); //이미지 파일만 허용
 
 
 	//첨부파일이 있다면
	$uploadSize = 100000000;
	@mkdir("$upload_dir", 0707);
  	@chmod("$upload_dir", 0707);
	
  	// 올라간 파일의 퍼미션을 변경합니다.
  	chmod("$uploads_dir", 0755);

    // 변수 정리
    $error = $_FILES[$filechoice]['name'][$i];
    $name = $_FILES[$filechoice]['name'][$i];     
    $tmpNm =  explode( '.', $name );
    $ext = strtolower(end($tmpNm));

     // echo "$ext <br>";
    // 확장자 확인
    if( !in_array($ext, $allowed_ext) ) {
       //  echo "허용되지 않는 확장자입니다.";
        exit;
    }
	
	
   // $newfile=$tmpNm[0].".".$ext ;
	$new_file_name = date("Y_m_d_H_i_s");
	$newfilename1 = $new_file_name."_" . $i . "." . $ext;      
    $url1 = $uploads_dir . '/' . $newfilename1; //올린 파일 명 그대로 복사해라.  시간초 등으로 파일이름 만들기
	$filepaths[] = $url1 ;
	
	//요기부분 수정했습니다.
	$filename1 = compress_image($_FILES[$filechoice]["tmp_name"][$i], $url1, 70); //실제 파일용량 줄이는 부분

	list($width, $height, $type, $attr) = getImagesize($_FILES[$filechoice]["tmp_name"][$i]);
	// echo $width."<br>";
	// echo $height."<br>";
	// echo $type."<br>";
	// echo $attr."<br>";

	if($width > 700){
	 $switch_s=80;
	}else{
	 $switch_s=100;
	}
    $buffer = file_get_contents($url1);
     
	$re_image = new Image($filename1);
	$rate=$width/$height;
	if($width>$height) {
			$re_image -> width(800);
			$re_image -> height(800/$rate);
		}
        else
		{
			$re_image -> width(800*$rate);
			$re_image -> height(800);
		}
		$re_image -> save();

		 require_once("../lib/mydb.php");
		 $pdo = db_connect();
		 // insert
			try{		 
				$pdo->beginTransaction();   
				$sql = "insert into jtechel.picuploads ";
				$sql .=" (tablename, item, parentnum, picname, idx) " ;        
				$sql .=" values(?, ?, ?, ?, ?) " ;        
				   
				 $stmh = $pdo->prepare($sql); 
				 
				 $stmh->bindValue(1, $tablename, PDO::PARAM_STR);   
				 $stmh->bindValue(2, $item, PDO::PARAM_STR);   
				 $stmh->bindValue(3, $num, PDO::PARAM_STR);             
				 $stmh->bindValue(4, $newfilename1, PDO::PARAM_STR);   
				 $stmh->bindValue(5, $idx, PDO::PARAM_STR);   			 
				 
				 $stmh->execute();
				 $pdo->commit(); 
					} catch (PDOException $Exception) {
					   $pdo->rollBack();
					   print "오류: ".$Exception->getMessage();
				 }  	
   
    }
} // end of for statement    
 
// 1000개 배열의 사진파일인 경우
if($arrayPic)
{
	if(!empty($filepaths)) {
		$response['status'] = 'array';
		$response['filepaths'] = $filepaths;
	} else {
		$response['status'] = 'error';
	}
	
	echo json_encode($response);
	
}
 else
 {
	if(!empty($filepaths)) {
		 $response['status'] = 'basic';  // 기본 시공전 시공중 시공후인 경우
		$response['filepaths'] = $filepaths;
	} else {
		$response['status'] = 'error';
	} 
	 
	 echo json_encode($response);
	 
 }

?>  
  
  
 

