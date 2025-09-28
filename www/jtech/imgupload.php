<?php session_start();     
  
$files = $_FILES["file"];    //첨부파일	  
$upload_dir = '';   //물리적 저장위치     \\ 폴더명에 주의

$upfile_name     = $files["name"] ;         
$upfile_tmp_name = $files["tmp_name"] ;
$upfile_type     = $files["type"] ;
$upfile_size     = $files["size"] ;
$upfile_error    = $files["error"] ;
$file = explode(".", $upfile_name);
$file_name = $file[0];
$file_ext  = $file[1];

 if (!$upfile_error )
 {
	$new_file_name = date("YmdHis");	
	$copied_file_name  = $new_file_name.".".$file_ext;      
	$uploaded_file  = $upload_dir . $copied_file_name ;

		if (!move_uploaded_file($upfile_tmp_name , $uploaded_file ) )
		{
		print("<script>
				alert('파일을 지정한 디렉토리에 복사하는데 실패했습니다.');
				history.back();
			  </script>");
		 exit;
		}
  }
  
//각각의 정보를 하나의 배열 변수에 넣어준다.
$data =  $uploaded_file;
//json 출력
echo(json_encode($data, JSON_UNESCAPED_UNICODE));	  
      
 ?>

