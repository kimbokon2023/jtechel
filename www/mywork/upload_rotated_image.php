<?php

// 오류 리포팅 활성화
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 이미지 파일을 저장할 디렉토리 지정
$targetDir = "uploads/";

// 올라간 파일의 퍼미션을 변경합니다.
// chmod("$targetDir", 0755);

// 클라이언트에서 전송된 이미지 파일 받기
if (isset($_FILES['rotatedImage'])) {
    $file = $_FILES['rotatedImage'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];	
	
	$tmpNm =  explode( '.', basename($fileName));
	$ext = strtolower(end($tmpNm));

	$new_file_name = date("Y_m_d_H_i_s");
	$newfilename1 = $new_file_name."_update" . "." . $ext;      	

    // 파일 저장 경로 설정
    // $targetFilePath = $targetDir . basename($fileName);  // 원래 이름으로
    $targetFilePath = $targetDir . $newfilename1 ;  // 새로운 이름 부여
	
	// sql DB 내용도 수정해야 한다.
	 require_once("../lib/mydb.php");
	 $pdo = db_connect();	 
	 
	 // update
	 try{		 		
		$pdo->beginTransaction();
		$sql = "update jtechel.picuploads_mywork set picname = ? where picname=? LIMIT 1" ;    
		    $stmh = $pdo->prepare($sql); 
			$stmh->bindValue(1, $newfilename1 , PDO::PARAM_STR);  		 
			$stmh->bindValue(2, $fileName , PDO::PARAM_STR);  		 
		 $stmh->execute();
		 $pdo->commit(); 
        } catch (PDOException $Exception) {
           $pdo->rollBack();
           print "오류: ".$Exception->getMessage();
       }                	
	   
    // 파일을 지정된 경로에 저장
    if (move_uploaded_file($fileTmpName, $targetFilePath)) {
		// 파일을 올렸으면 기존 파일은 삭제 한다.
		   $upload_dir = './uploads/';    //물리적 저장위치   
		   $made_name = $targetDir . $fileName;
		   unlink($made_name); 
		
        echo json_encode(["status" => "success", "message" => "File uploaded successfully" , "rotatedImage filename" => $fileName , "targetFilePath" => $targetFilePath ]);
    } else {
        echo json_encode(["status" => "error", "message" => "There was an error uploading your file"]);
		}
	} 
	else {
			echo json_encode(["status" => "error", "message" => "No file uploaded"]);
		}
?>
