<?

$description = array();
$spec = array();
$ea = array();
$unit = array();
$amount = array();
$comment = array();

$title = array();

// 견적자료가 있는 경우는 불러온다.
if($et_content==null)
{
array_push($title, $material1,$material2,$material3,$material4,$material5);

for($i=0;$i<count($title);$i++)
  if($title[$i]!=null) {	  
	  array_push($description, $i+1 . ") ". $title[$i]);   
	  array_push($description, ' - 자재비');   	  
	  array_push($description, ' - 가공비');   	  
	  array_push($description, ' - 시공비');   
	  array_push($spec, ' ' );  
	  array_push($spec, '식' );  
	  array_push($spec, '식' );  
	  array_push($spec, '식' );  
	  array_push($ea, '' );  
	  array_push($ea, 1 );  
	  array_push($ea, 1 );  
	  array_push($ea, 1 );  
	  array_push($unit, '' );  
	  array_push($unit, 1 );  
	  array_push($unit, 1 );  
	  array_push($unit, 1 );  
	  array_push($amount, '' );  
	  array_push($amount, 1 );  
	  array_push($amount, 1 );  
	  array_push($amount, 1 );  
	  array_push($comment, '' );  
	  array_push($comment, '' );  
	  array_push($comment, '' );  
	  array_push($comment, '' );  
     }
} // end of if
else
{
		
	$etexplode = explode( '|', $et_content );	
		
	for($i=0;$i<count($etexplode);$i++)  
	{
	   $mod = (6 + $i) % 6 ;
	   if($mod == 0 )
		  array_push($description, $etexplode[$i]);
	   if($mod == 1 )
		  array_push($spec, $etexplode[$i]);
	   if($mod == 2 )
		  array_push($ea, $etexplode[$i]);
	   if($mod == 3 )
		  array_push($unit, $etexplode[$i]);
	   if($mod == 4 )
		  array_push($amount, $etexplode[$i]);
	   if($mod == 5 )
		  array_push($comment, $etexplode[$i]);
		 }
}
	
// 견적합 계산 
$totalamount_int = 0 ;
for($i=0 ; $i < count($amount); $i++) 	
	$totalamount_int += (int)str_replace(',', '', $amount[$i]);

$totalamount = number_format($totalamount_int);
?>