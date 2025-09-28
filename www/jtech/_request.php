<?php

  if(isset($_REQUEST["search"]))   
	 $search=$_REQUEST["search"];
   if(isset($_REQUEST["list"]))   
	 $list=$_REQUEST["list"];
    else
		  $list=0;
	  
   if(isset($_REQUEST["scale"]))   
	 $scale=$_REQUEST["scale"];
    else
		  $scale=50;	  	  

 if(isset($_REQUEST["check"]))        // 버튼 클릭시 읽는 값
	 $check=$_REQUEST["check"];
   else
     $check=$_POST["check"]; 
 
  
 if(isset($_REQUEST["choiceitem"]))
 {
    $choiceitem=$_REQUEST["choiceitem"];  
 }  
  
 if(isset($_REQUEST["searchOpt"]))
 {
    $searchOpt=$_REQUEST["searchOpt"];  
 } 
  
 if(isset($_REQUEST["num"]))
 {
    $num=$_REQUEST["num"];  
 }  
 else
	   $num=0;  
    
 
 if(isset($_REQUEST["tmpKey"]))
 {
    $tmpKey=$_REQUEST["tmpKey"];  
 }
   else
	   $tmpKey=0;  
    
 if(isset($_REQUEST["mode"]))
 {
    $mode=$_REQUEST["mode"];  
 }
  
  
 if(isset($_REQUEST["page"])) // $_REQUEST["page"]값이 없을 때에는 1로 지정 
 {
    $page=$_REQUEST["page"];  // 페이지 번호
 }
  else
  {
    $page=1;	 
  }
  
// print $output_check;

if(isset($_REQUEST["cursort"])) 
	 $cursort=$_REQUEST["cursort"]; // 미실측리스트
   else
	if(isset($_POST["cursort"]))   
         $cursort=$_POST["cursort"]; // 미실측리스트
	 else
		 $cursort='0';		  


if(isset($_REQUEST["sortof"])) 
	 $sortof=$_REQUEST["sortof"]; // 미실측리스트
   else
	if(isset($_POST["sortof"]))   
         $sortof=$_POST["sortof"]; // 미실측리스트
	 else
		 $sortof='0';		 
	 
 if(isset($_REQUEST["stable"])) 
	 $stable=$_REQUEST["stable"]; // 미실측리스트
   else
	if(isset($_POST["stable"]))   
         $stable=$_POST["stable"]; // 미실측리스트
	 else
		 $stable='0';	


 if(isset($_REQUEST["check_draw"])) 
	 $check_draw=$_REQUEST["check_draw"];   // 도면 미설계List
	   else
		 $check_draw=$_POST["check_draw"];    

 if(isset($_REQUEST["check"])) 
	 $check=$_REQUEST["check"]; // 미출고 리스트 request 사용 페이지 이동버튼 누를시`
   else
     $check=$_POST["check"]; // 미출고 리스트 POST사용 
 
 if(isset($_REQUEST["output_check"])) 
	 $output_check=$_REQUEST["output_check"]; // 미출고 리스트 request 사용 페이지 이동버튼 누를시`
   else
	if(isset($_POST["output_check"]))   
         $output_check=$_POST["output_check"]; // 미출고 리스트 POST사용  
	 else
		 $output_check='0';
	 
 if(isset($_REQUEST["team_check"])) 
	 $team_check=$_REQUEST["team_check"]; // 시공팀미지정
   else
	if(isset($_POST["team_check"]))   
         $team_check=$_POST["team_check"]; // 시공팀미지정
	 else
		 $team_check='0';	
	 
   if(isset($_REQUEST["plan_output_check"])) 
	 $plan_output_check=$_REQUEST["plan_output_check"]; // 출고예정`
   else
	if(isset($_POST["plan_output_check"]))   
         $plan_output_check=$_POST["plan_output_check"]; // 출고예정  
	 else
		 $plan_output_check='0';


?>