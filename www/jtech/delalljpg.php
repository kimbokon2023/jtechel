<?php

// 현재폴더내 jpg 전부 삭제하기

// 파일의 크기 구하기
foreach (glob("*.jpg") as $filename) {
    // echo "$filename size " . filesize($filename) . "\n";
    // echo "<br />";
}
// echo "<br />";

// 폴더내 파일 전부 구하기
// echo "<br />";
$dir = './';
$files = scandir($dir);
 
foreach ($files as $ind_file) {
  if (is_file($ind_file)) {
    ?>
    <!-- <li> -->
	<?php // echo $ind_file;?> 
	<!-- </li> -->
    <?php
  }
}


// 디렉토리에 있는 파일과 디렉토리의 갯수 구하기
$path = opendir("./"); // opendir 함수를 이용해서 현재 디렉토리의 핸들을 얻어옴

// readdir함수를 이용해서 현재 디렉토리에 있는 디렉토리와 파일들의 이름을 배열로 읽어들임
while($file=readdir($path)) {    
    if($file=="."||$file=="..") continue; // file명이 ".", ".." 이면 무시함
    $fileInfo = pathinfo($file);
    // print_r($fileInfo);  // 파일 경로 정보가 어떻게 되는지 확인하기 위해 출력
    // echo "<br />";
    $fileExt = $fileInfo['extension']; // 파일의 확장자를 구함

    if (empty($fileExt)){
        $dir_count++; // 파일에 확장자가 없으면 디렉토리로 판단하여 dir_count를 증가시킴
    } elseif($fileExt == 'jpg') {
        $jpg_count++;  // 파일 확장자가 jpg 이면 jpg_count를 증가시킴
        $_tmpdfile = $path.$file;
       // @chmod($_tempdfile,0707);    // 파일의 소유권이 있는 경우에만 권한변경
        $cmd = `rm -rf *.jpg`;    // 파일의 소유권에 상관없이 파일 모두 삭제처리
        if(is_writable($_tmpdfile)) {
            unlink($_tmpdfile);
            // echo "파일 삭제됨"."<br />";
        } else {
            // echo "파일 쓰기(삭제) 권한 없음"."<br />";
        }
       
    } else {
        $file_count++;  // 파일에 확장자가 있으면 file_count를 증가시킴
    }

}
@closedir($path);

// echo "<br />";
// echo "subfolder 수는 : ".$dir_count."<br />";
// echo "jpg 파일의 갯수는 : ".$jpg_count."<br />";
// echo "파일의 갯수는 : ".$file_count;
// echo "<br />";

// 폴더내 파일 전부 구하기
// echo "<br />";
$dir = './';
$files = scandir($dir);
 
foreach ($files as $ind_file) {
  if (is_file($ind_file)) {
    ?>
    <!-- <li> -->
	<?php // echo $ind_file;?>
	<!-- </li> -->
    <?php
  }
}

?>