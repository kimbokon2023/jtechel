 <?php session_start();  
 
 include "../php/common.php";

 if(isset($_REQUEST["num"]))
    $num=$_REQUEST["num"];
		 else 
			$num="";			
 
  if(isset($_REQUEST["check"])) 
	 $check=$_REQUEST["check"]; 
   else
     $check=$_POST["check"]; 	
 
 		
 if(isset($_REQUEST["workplacename"]))
    $workplacename=$_REQUEST["workplacename"];
		 else 
			$workplacename="";	


 if(isset($_REQUEST["tablename"]))
    $tablename=$_REQUEST["tablename"];
		 else 
			$tablename="";	

 if(isset($_REQUEST["item"]))
    $item=$_REQUEST["item"];
		 else 
			$item="";	 
		
$filechoice = 'upfile';

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

    $uploads_dir = '../uploads'; //업로드 폴더 상위 uploads 폴더선택
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

     echo "$ext <br>";
    // 확장자 확인
    if( !in_array($ext, $allowed_ext) ) {
        echo "허용되지 않는 확장자입니다.";
        exit;
    }
	
	
   // $newfile=$tmpNm[0].".".$ext ;
	$new_file_name = date("Y_m_d_H_i_s");
	$newfilename1 = $new_file_name."_" . $i . "." . $ext;      
    $url1 = $uploads_dir.'/'.$newfilename1; //올린 파일 명 그대로 복사해라.  시간초 등으로 파일이름 만들기
	
	// 사진회전시키기
	    
     $tmpimage = imagecreatefromjpeg($url1);
	 $exif = exif_read_data($url1);
	 
	 print '사진 정보' . $exif['Orientation'] . "<br>";
	 
			if(!empty($exif['Orientation']))
				{
					switch($exif['Orientation'])
					{
						case 8:
							$url1 = imagerotate($url1,90,0);
							break;
						case 3:
							$url1 = imagerotate($url1,180,0);
							break;
						case 6:
							$url1 = imagerotate($url1,-90,0);
							break;

					}
				}


	//요기부분 수정했습니다.
	$filename1 = compress_image($_FILES[$filechoice]["tmp_name"][$i], $url1, 70); //실제 파일용량 줄이는 부분

	list($width, $height, $type, $attr) = getImagesize($_FILES[$filechoice]["tmp_name"][$i]);
	echo $width."<br>";
	echo $height."<br>";
	echo $type."<br>";
	echo $attr."<br>";

	if($width > 700){
	 $switch_s=80;
	}else{
	 $switch_s=100;
	}
    $buffer = file_get_contents($url1);
 
    // 파일 정보 출력
    echo "<h2>파일 정보</h2> <h1>
        <ul>
            <li>자료번호: $num</li>
            <li>파일명: $name</li>
            <li>확장자: $ext</li>
            <li>url: {$url1}</li>
            <li>filename: {$filename1}</li>
        </ul> </h1>";
    
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
			$sql = "insert into mirae8440.picuploads ";
			$sql .=" (tablename, item, parentnum, picname) " ;        
			$sql .=" values(?, ?, ?, ?) " ;        
			   
			 $stmh = $pdo->prepare($sql); 
			 
			 $stmh->bindValue(1, $tablename, PDO::PARAM_STR);   
			 $stmh->bindValue(2, $item, PDO::PARAM_STR);   
			 $stmh->bindValue(3, $num, PDO::PARAM_STR);             
			 $stmh->bindValue(4, $newfilename1, PDO::PARAM_STR);   
			 
			 
			 $stmh->execute();
			 $pdo->commit(); 
				} catch (PDOException $Exception) {
				   $pdo->rollBack();
				   print "오류: ".$Exception->getMessage();
			 }  	
   
    }
} // end of for statement    
 


// log 기록 남기기

 $data=date("Y-m-d H:i:s") . " - " . $_SESSION["userid"] . " - " . $_SESSION["name"] . "  " . $workplacename . " - 사진기록" ;	
 require_once("../lib/mydb.php");
 $pdo = db_connect();
 $pdo->beginTransaction();
 $sql = "insert into mirae8440.logdata(data) values(?) " ;
 $stmh = $pdo->prepare($sql); 
 $stmh->bindValue(1, $data, PDO::PARAM_STR);   
 $stmh->execute();
 $pdo->commit(); 

// echo "<script> opener.document.getElementById('pInput').value='100'; </script>";   // 부모창에 100 기록해보기


?>  
  
  
 

