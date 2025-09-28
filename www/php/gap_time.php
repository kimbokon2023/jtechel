<?php
// 두 날짜의 차이를 구한다.
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

print gap_time("2021-12-10 18:00:00","2021-12-10 23:50:00");


?>
