<?
function gap_time($start_date, $end_date) {
	
	// $end_date = strtotime("2021-12-10 23:50:00"); // 끝나는 시간	
	// $today = strtotime(date("Ymd H:i:s")); //현재시간
	
	$start_time = strtotime($start_date);
	$end_time = strtotime($end_date);

	$diff = $end_time - $start_time;

	$hours = floor($diff/3600);

	$diff = $diff-($hours*3600);

	$min = floor($diff/60);

	$sec = $diff - ($min*60);

	// return $hours.":".$min.":".$sec;
	return sprintf("%02d:%02d:%02d", $hours, $min, $sec); 

}

function timeformat($str) {
	
	// $strtime = strtotime('H:i:s',$str);		
	
	$diff = $str;

	$hours = floor($diff/3600);

	$diff = $diff-($hours*3600);

	$min = floor($diff/60);

	$sec = $diff - ($min*60);

	// return $hours.":".$min.":".$sec;
	return sprintf("%02d:%02d:%02d", $hours, $min, $sec); 

}

 function trans_date($tdate) {
		      if($tdate!="0000-00-00" and $tdate!="1900-01-01" and $tdate!="")  $tdate = date("Y-m-d", strtotime( $tdate) );
					else $tdate="";							
				return $tdate;	
} 

 function trans_time($tdate) {
		      if($tdate!="00:00:00" and $tdate!="")  $tdate = date("H:i:s", strtotime( $tdate) );
					else $tdate="";							
				return $tdate;	
} 

function conv_num($num) {                        // ,콤마를 없애주는 함수
$number = (int)str_replace(',', '', $num);
return $number;
}

function write_ini_file($assoc_arr, $path, $has_sections=FALSE) {
	$content = "";
	if ($has_sections) {
		$i = 0;
		foreach ($assoc_arr as $key=>$elem) {
			if ($i > 0) {
				$content .= "\n";
			}
			$content .= "[".$key."]\n";
			foreach ($elem as $key2=>$elem2) {
				if(is_array($elem2))
				{
					for($i=0;$i<count($elem2);$i++)
					{
						$content .= $key2."[] = \"".$elem2[$i]."\"\n";
					}
				} else if($elem2=="") {
					$content .= $key2." = \n";
				} else {
					if (preg_match('/[^0-9]/i',$elem2)) {
						$content .= $key2." = \"".$elem2."\"\n";
					}else {
						$content .= $key2." = ".$elem2."\n";
					}
				}
			}
			$i++;
		}
	}
	else {
		foreach ($assoc_arr as $key=>$elem) {
			if(is_array($elem))
			{
				for($i=0;$i<count($elem);$i++)
				{
					$content .= $key."[] = \"".$elem[$i]."\"\n";
				}
			} else if($elem=="") {
				$content .= $key." = \n";
			} else {
				if (preg_match('/[^0-9]/i',$elem)) {
					$content .= $key." = \"".$elem."\"\n";
				}else {
					$content .= $key." = ".$elem."\n";
				}
			}
		}
	}

	if (!$handle = fopen($path, 'w')) {
		return false;
	}

	$success = fwrite($handle, $content);
	fclose($handle);

	return $success;
}	

function make_array($str) {          // 콤마로 이뤄진 배열을 풀어준다. decode
	$tmp_num= implode (",", $str);
	$tmp = explode( ',', $tmp_num );
	return $tmp;	 
}		
function decode_pipe($str) {          // javascript에서 넘어온 (콤마변환 문자 파이프 |를 다시 콤마로 변환해준다.

	$tmp= str_replace('|' , ',', $str);
	return $tmp;	

}	

?>