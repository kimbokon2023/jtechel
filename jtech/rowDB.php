<?

$num=$row["num"];	
$workplacename=$row["workplacename"];
$address=$row["address"];
$firstord=$row["firstord"];
$firstordman=$row["firstordman"];
$firstordmantel=$row["firstordmantel"];
$secondord=$row["secondord"];
$secondordman=$row["secondordman"];
$secondordmantel=$row["secondordmantel"];
$chargedman=$row["chargedman"];
$chargedmantel=$row["chargedmantel"];
$orderday=$row["orderday"];
$measureday=$row["measureday"];  
$workday=$row["workday"];
$worker=$row["worker"]; 
$doneday=$row["doneday"];  // 시공완료일  
$demand=$row["demand"];  // 청구일
$donedemand=$row["donedemand"];  // 청구일

$worker=$row["worker"];

$material1=$row["material1"];
$material2=$row["material2"];
$material3=$row["material3"];
$material4=$row["material4"];
$material5=$row["material5"];

$memo=$row["memo"];
$memo2=$row["memo2"];
$regist_day=$row["regist_day"];  
$update_log=$row["update_log"];

$demand=$row["demand"];   
$et_writeday=$row["et_writeday"];    // 견적서 작성일 
$et_wpname=$row["et_wpname"];   
$et_deadline=$row["et_deadline"];   
$et_paymethod=$row["et_paymethod"];   
$et_validation=$row["et_validation"];   
$et_itemname=$row["et_itemname"];   
$et_receiver=$row["et_receiver"];   
$et_content=$row["et_content"];	  
$et_note=$row["et_note"];	  
$piclist=$row["piclist"];	  
$pjnum=$row["pjnum"];	  
$customer=$row["customer"];	  

if(trim($et_wpname)==null)
   $et_wpname=$workplacename;   // 현장명이 없을때는 주소로

// 품명이 없다면  workplacename이 품명이 됨
if(trim($et_receiver) == null)
	$et_receiver = $secondord;

// 납기일이 없으면
if(trim($et_deadline) == null)
	$et_deadline = '발주 후 20일(추후 협의)';

// 결재방식이 없으면
if(trim($et_paymethod) == null)
	$et_paymethod = '현금';

// 유효기간이 없으면
if(trim($et_validation) == null)
	$et_validation = '견적일로부터 15 日';



?>	  