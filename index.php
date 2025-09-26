<?php

session_start();

$mAgent = array("iPhone","iPod","Android","Blackberry", 
    "Opera Mini", "Windows ce", "Nokia", "sony" );
$chkMobile = false;
for($i=0; $i<sizeof($mAgent); $i++){
    if(stripos( $_SERVER['HTTP_USER_AGENT'], $mAgent[$i] )){
        $chkMobile = true;
        break;
    }
}


// 제이테크 분기	
	if($_SESSION["part"]=='jtech') 
	{
		header ("Location:../jtech/index.php");
		exit;  
	}	

// 김재구 팀장 분기	
	if($_SESSION["part"]=='mywork') 
	{
		header ("Location:../mywork/index.php");
		exit;  
	}	
	
  	if($_SESSION["userid"]=='9225' || $_SESSION["part"]=='mywork' )  
	{
       header ("Location:../mywork/index.php");
	exit;  
	} 		
	else
	{
		header ("Location:../jtech/index.php");	
	}
	
?>
