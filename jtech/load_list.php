<?php 

 function trans_date($tdate) {
		      if($tdate!="0000-00-00" and $tdate!="1900-01-01" and $tdate!="")  $tdate = date("Y-m-d", strtotime( $tdate) );
					else $tdate="";							
				return $tdate;	
}

  require_once("../lib/mydb.php");
  $pdo = db_connect();	
 
  $page=1;	 
  
  $scale = 10;       // 한 페이지에 보여질 게시글 수
  $page_scale = 10;   // 한 페이지당 표시될 페이지 수  10페이지
  $first_num = ($page-1) * $scale;  // 리스트에 표시되는 게시글의 첫 순번.
  
  $sum_ceiling = array();
	 
  $now = date("Y-m-d",time()) ;

   $where = " where ( workday between date('$now') and date('$now') ) "  ;
   $username = $_SESSION["name"];
   // 본사가 아니면  해당 팀장만 볼 수 있도록 함
   if($_SESSION["level"]!='2') {
	   $where = " where ( workday between date('$now') and date('$now') ) and worker='$username' "  ;
   }

	$orderby=" order by workday desc, num desc ";				
		 
	$a= " " . $orderby  ;  
	
	$sql="select * from jtechel.jtech " . $where . $a; 				
	   
 try{  
  $stmh = $pdo->query($sql);            // 검색조건에 맞는글 stmh
  $temp1=$stmh->rowCount();
  $total_row=$temp1;

// 자료가 있다면 실행  
if(  $total_row > 0) {
	  
?>

<div class="container-fluid align-items-center" style="width:95%;"> 	
 
<div class="p-0 mb-2 mt-4 d-flex justify-content-center align-items-center"> 	
	<h4> 금일 시공일정 LIST </h4>
</div>


<div class="d-flex row mt-2 justify-content-center ">
	<span class="col-sm-1  text-center text-white"  style=" border:1px solid #ccc; font-weight:bold; background : #3C3C8C   " > 번호</span>         
	<span class="col-sm-4  text-center text-white " style=" border:1px solid #ccc; font-weight:bold; background : #3C3C8C  " > 공사명</span>         
	<span class="col-sm-1  text-center text-white " style=" border:1px solid #ccc; font-weight:bold; background : #3C3C8C   " > 원청</span>     	
	<span class="col-sm-1  text-center text-white " style=" border:1px solid #ccc; font-weight:bold; background : #3C3C8C  " > 발주처</span>     
	<span class="col-sm-1  text-center text-white " style=" border:1px solid #ccc; font-weight:bold; background : #3C3C8C  " > 시공팀</span>     
	<span class="col-sm-4  text-center text-white " style=" border:1px solid #ccc; font-weight:bold; background : #3C3C8C   "> 시공내역 </span>
</div>		 

<?php  
if ($page<=1)  
	$start_num=$total_row;    // 페이지당 표시되는 첫번째 글순번
else 
	$start_num=$total_row-($page-1) * $scale;
		
		   $sum = array();
	      
	   while($row = $stmh->fetch(PDO::FETCH_ASSOC)) {
		 include 'rowDB.php';
	  
			  
		      if($regist_day!="0000-00-00" and $regist_day!="1970-01-01"  and $regist_day!="") $regist_day = date("Y-m-d", strtotime( $regist_day) );
					else $regist_day="";
		      if($measureday!="0000-00-00" and $measureday!="1970-01-01" and $measureday!="")   $measureday = date("Y-m-d", strtotime( $measureday) );
					else $measureday="";
		      if($workday!="0000-00-00" and $workday!="1970-01-01"  and $workday!="")  $workday = date("Y-m-d", strtotime( $workday) );
					else $workday="";		
		      if($demand!="0000-00-00" and $demand!="1970-01-01" and $demand!="")  $demand = date("Y-m-d", strtotime( $demand) );
					else $demand="";			
		      if($donedemand!="0000-00-00" and $donedemand!="1970-01-01" and $donedemand!="")  $donedemand = date("Y-m-d", strtotime( $donedemand) );
					else $donedemand="";		  			  	  
		      if($doneday!="0000-00-00" and $doneday!="1970-01-01" and $doneday!="")  $doneday = date("Y-m-d", strtotime( $doneday) );
					else $doneday="";		  
			  	  				  
			  $state_work=0;
			  if($row["checkbox"]==0) $state_work=1;
			  if(substr($row["workday"],0,2)=="20") $state_work=2;			  
	 	  
			                
			  if(substr($row["regist_day"],0,2)=="20")  $regist_day = iconv_substr($regist_day,5,5,"utf-8");
			            else $regist_day="    ";	
						
			  if(substr($row["workday"],0,2)=="20")  $workday = iconv_substr($workday,5,5,"utf-8");
			            else $workday="    ";		
						
			  if(substr($row["measureday"],0,2)=="20")  $measureday = iconv_substr($measureday,5,5,"utf-8");
			            else $measureday="    ";	
			                
			  if(substr($row["demand"],0,2)=="20")  $demand = iconv_substr($demand,5,5,"utf-8");
			            else $demand="    ";
						
			  if(substr($row["donedemand"],0,2)=="20")  $donedemand = iconv_substr($donedemand,5,5,"utf-8");
			            else $donedemand="    ";	 
						
			  if(substr($row["doneday"],0,2)=="20")  $doneday = iconv_substr($doneday,5,5,"utf-8");
			            else $doneday="    ";	
			 ?> 

				
	        <a href="./write_form.php?num=<?=$num?>&page=<?=$page?>&scale=<?=$scale?>&find=<?=$find?>&search=<?=$search?>&process=<?=$process?>&yearcheckbox=<?=$yearcheckbox?>&year=<?=$year?>&output_check=<?=$output_check?>&team_check=<?=$team_check?>&measure_check=<?=$measure_check?>&plan_output_check=<?=$plan_output_check?>&page=<?=$page?>&cursort=<?=$cursort?>&sortof=<?=$sortof?>&stable=<?=$stable?>&check=<?=$check?>&notorder=<?=$notorder?>" >				 
				<div class="row ">	
  			
				 <span class="col-sm-1  text-center text-secondary  "  style="border:1px solid #ccc; " > <?=$start_num?> &nbsp;</span>				 
				 <span class="col-sm-4  text-center text-secondary  "  style="border:1px solid #ccc; " > <?=$workplacename?>&nbsp; </span>				 
				 <span class="col-sm-1  text-center  text-secondary "  style="border:1px solid #ccc; " > <?=$firstord?> &nbsp;</span>     
				 <span class="col-sm-1  text-center  text-secondary "  style="border:1px solid #ccc; " > <?=$secondord?> &nbsp; </span>     
				 <span class="col-sm-1  text-center text-secondary  "   style="border:1px solid #ccc;" > <?=$worker?> &nbsp;</span>
				 <span class="col-sm-4  text-center text-secondary  "   style="border:1px solid #ccc;" > <?=iconv_substr($memo,0,30,"utf-8")?>&nbsp;</span>
				</div>	
			</a>									 								 

		  
			<?php
			$start_num--;
			 } 
      } // end of if - total_row
  } catch (PDOException $Exception) {
  print "오류: ".$Exception->getMessage();
  }  
 ?>
 		  
</div>