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

$material1=$row["material1"];
$material2=$row["material2"];
$material3=$row["material3"];
$material4=$row["material4"];
$material5=$row["material5"];
$material6=$row["material6"];
$material7=$row["material7"];
$material8=$row["material8"];
$material9=$row["material9"];
$material10=$row["material10"];

$memo=$row["memo"];
$memo2=$row["memo2"];
$regist_day=$row["regist_day"];  
$update_log=$row["update_log"];

$demand=$row["demand"];   
$et_writeday=$row["et_writeday"];    // 견적서 작성일 
$et_wpname=$row["et_wpname"];   
if($et_wpname==null)
$et_wpname=$address;   // 현장명이 없을때는 주소로

$et_schedule=$row["et_schedule"];
$et_person=$row["et_person"];
$et_content=$row["et_content"];	  
$et_note=$row["et_note"];	  

?>	  