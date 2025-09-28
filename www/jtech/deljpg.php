<?php

// 파일을 읽어와서 서버에 저장함.
isset($_REQUEST["imageURL"])  ? $imageURL=$_REQUEST["imageURL"] :   $imageURL=''; 

// 이미지 화일 jpg 삭제
unlink($imageURL);

?>