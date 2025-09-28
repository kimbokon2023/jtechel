<?


function number2hangul($number){

        $num = array('', '일', '이', '삼', '사', '오', '육', '칠', '팔', '구');
        $unit4 = array('', '만', '억', '조', '경');
        $unit1 = array('', '십', '백', '천');

        $res = array();

        $number = str_replace(',','',$number);
        $split4 = str_split(strrev((string)$number),4);

        for($i=0;$i<count($split4);$i++){
                $temp = array();
                $split1 = str_split((string)$split4[$i], 1);
                for($j=0;$j<count($split1);$j++){
                        $u = (int)$split1[$j];
                        if($u > 0) $temp[] = $num[$u].$unit1[$j];
                }
                if(count($temp) > 0) $res[] = implode('', array_reverse($temp)).$unit4[$i];
        }
        return implode('', array_reverse($res));
}


$description = array();
$spec = array();
$unit = array();
$quantity = array();
$unitprice = array();
$amount = array();
$comment = array();

$sogaenumbering = array();

$title = array();
$title[0] = $material1;
$title[1] = $material2;
$title[2] = $material3;
$title[3] = $material4;
$title[4] = $material5;

// 견적자료가 있는 경우는 불러온다.
if($et_content==null)
{
	
// null  제거	


// 오성자료는 다름	
// array_push($title, $material1,$material2,$material3,$material4,$material5);

for($i=0;$i<count($title);$i++)
  if($title[$i]!=null) {	  
	  array_push($description, "    " . $title[$i]);   
	  array_push($description, ' 1) 연마 및 세척');   	  
	  array_push($description, ' 2) 하도(프라이머)');   	  
	  array_push($description, ' 3) 상도 및 코팅');   
	  array_push($description, ' 4) 도료 및 소모품');   
	  array_push($description, '  소  계 ' );   
	  array_push($spec, '' );  
	  array_push($spec, '' );  
	  array_push($spec, '' );  
	  array_push($spec, '' );  
	  array_push($spec, '' );  
	  array_push($spec, '' );  
	  array_push($unit, '' );  
	  array_push($unit, '대' );  
	  array_push($unit, '대' );  
	  array_push($unit, '대' );  
	  array_push($unit, '식');  
	  array_push($unit, '' );  
	  array_push($quantity, '' );  
	  array_push($quantity, '' );  
	  array_push($quantity, '' );  
	  array_push($quantity, '' );  
	  array_push($quantity, '' );  
	  array_push($quantity, '' );  
	  array_push($unitprice, '' );  
	  array_push($unitprice, '' );  
	  array_push($unitprice, '' );  
	  array_push($unitprice, '' );  
	  array_push($unitprice, '' );  
	  array_push($unitprice, '' );  
	  array_push($amount, '' );  
	  array_push($amount, '' );  
	  array_push($amount, '' );  
	  array_push($amount, '' );  
	  array_push($amount, '' );  
	  array_push($amount, '' );  
	  array_push($comment, '' );  
	  array_push($comment, '' );  
	  array_push($comment, '' );  
	  array_push($comment, '' );  
	  array_push($comment, '' );  
	  array_push($comment, '' );  
     }
} // end of if
else
{		
    
	$etexplode = explode( '|', str_replace('null','',$et_content));	
		
	for($i=0;$i<count($etexplode);$i++)  
	{
	   $content_count = 7;
	   $mod = ($content_count + $i) % $content_count ;
	   if($mod == 0 )
		  array_push($description, $etexplode[$i]);
	   if($mod == 1 )
		  array_push($spec, $etexplode[$i]);
	   if($mod == 2 )
		  array_push($unit, $etexplode[$i]);
	   if($mod == 3 )
		  array_push($quantity, $etexplode[$i]);
	   if($mod == 4 )
		  array_push($unitprice, $etexplode[$i]);
	   if($mod == 5 )
		  array_push($amount, $etexplode[$i]);
	   if($mod == 6 )
		  array_push($comment, $etexplode[$i]);
		 }
}
	 
// 견적합 계산 (소계합을 구한다)
$totalamount_int = 0 ;
$innerCount = 0;
for($i=0 ; $i < count($amount); $i++) 	
{
	
	if($description[$i] !='')	 // 품목이 null이 아닌경우
	{
		
	  	if($i==0 || $foundSogae == 1)
			{
				$sogaenumbering[$i] =  $innerCount + 1 ;				
				$foundSogae = 0;
			}		
		
		// 모든 공백제거 $out=preg_replace("/\s+/","",$in);
		$des = preg_replace("/\s+/","",$description[$i]);
	 if($des=="소계") 
	    {
		 $totalamount_int += (int)str_replace(',', '', $amount[$i]);
		 $foundSogae = 1;   // 소개 찾음 변수 적용
		 $innerCount++ ;    // 순번 증가		 
		}

	}
}

$totalamount = number_format($totalamount_int);


// var_dump($title);
?>