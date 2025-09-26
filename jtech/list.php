
<?php include $_SERVER['DOCUMENT_ROOT'] . '/jtech/load.php' ?>

<title> 제이테크 수주/발주 리스트 </title>
<style>
  
body{
font-family: "Helvetica Nene", Helvetica, Arial, "malgun gothic", sans-serif;
/* font-family: 'Nanum Myeongjo', serif; */
font-size:14px;
font-weight:500;
line-height:220%
} 
   

/* 접수 실측 시공예정 등 칸에 가져가면 변하기 */
  #registerBtn:hover, #measuredayBtn:hover, #forecastBtn:hover, #workdoneBtn:hover, #demandBtn:hover, #donedemandBtn:hover {
    background: silver;
    border-radius: 15%;
    border-bottom: 2px solid slateblue;
    cursor: pointer;
  }
 
   
</style>

</head>

<body>
 <?php

include "_request.php";
  
// 리스트의 접수일 클릭, 실측일 클릭등에 대한 데이터 검색조건식 만듬
if($sortof!='0')
{

	if($sortof==1) {      //접수일 클릭되었을때
		
	 if($cursort!=1)
	    $cursort=1;
      else
	     $cursort=2;
	    } 
	if($sortof==2  ) {     //실측일 클릭되었을때
		
	 if($cursort!=3)
	    $cursort=3;
      else
		 $cursort=4;			
	   }	   
	if($sortof==3  ) {     //시공예정 클릭되었을때
		
	 if($cursort!=5)
	    $cursort=5;
      else
		 $cursort=6;			
	   }	   	   
	if($sortof==4  ) {     // 시공완료 클릭되었을때
		
	 if($cursort!=7)
	    $cursort=7;
      else
		 $cursort=8;			
	   }	   
	if($sortof==5  ) {     //대금청구 클릭되었을때
		
	 if($cursort!=9)
	    $cursort=9;
      else
		 $cursort=10;			
	   }		   
	if($sortof==6  ) {     //대금지급 클릭되었을때
		
	 if($cursort!=11)
	    $cursort=11;
      else
		 $cursort=12;			
	   }		   
}	   
 else
	 	 $cursort=0;			
    
   
switch($cursort)
{
	   case 1 :
	   $orderby="order by regist_day desc, num desc  ";
	   break;   
	   case 2 :
	   $orderby="order by regist_day asc, num desc  ";
	   break;      
	   case 3 :
	   $orderby="order by measureday desc, num desc  ";
	   break;   
	   case 4 :
	   $orderby="order by measureday asc, num desc  ";
	   break;      	
	   case 5 :
	   $orderby="order by workday desc, num desc  ";
	   break;   
	   case 6 :
	   $orderby="order by workday asc, num desc  ";
	   break;      		
	   case 7:
	   $orderby="order by doneday desc, num desc  ";
	   break;   
	   case 8 :
	   $orderby="order by doneday asc, num desc  ";
	   break;
	   case 9 :
	   $orderby="order by demand desc, num desc  ";
	   break;   
	   case 10:
	   $orderby="order by demand asc, num desc  ";
	   break;    
	   case 11 :
	   $orderby="order by donedemand desc, num desc";
	   break;   
	   case 12:
	   $orderby="order by donedemand asc, num desc";
	   break;   
	   
	default:
	   $orderby=" order by num desc  ";	
	break;
}


// 제이테크가 아닌 경우 이름으로 구분해서

// 외주업체인 경우 해당업체만 볼 수 있도록 화면구성함
if($user_name != '제이테크' )
{
	switch($searchOpt) {  		 
	  case '1' :  // 금일접수
					$attached="  and (regist_day between date('$now') and date('$now') ) and (worker='$user_name') "  ;
					$whereattached="  where (regist_day between date('$now') and date('$now') )  and (worker='$user_name') "  ;			    
					break;		
	  case '2' :  // 금일시공예정
					$attached="  and  (workday between date('$now') and date('$now') ) and (worker='$user_name') "  ;
					$whereattached="  where (workday between date('$now') and date('$now') )  and (worker='$user_name') "  ;			    
					break;		
	  case '4' :  // 금일이후 시공
					$attached= " and ( workday > CURDATE() )  and (worker='$user_name')  " ;
					$whereattached= " where ( workday > CURDATE() )  and (worker='$user_name')   " ;			    
					break;		
		default:	
				$attached=" and (worker='$user_name') ";	
                $whereattached= " where (worker='$user_name') " ;					
	}
	
	// print '제이테크 아닌경우' . $attached;
	
}
else
{
	switch($searchOpt) {  		 
	  case '1' :  // 금일접수
					$attached="  and (regist_day between date('$now') and date('$now') ) "  ;
					$whereattached="  where (regist_day between date('$now') and date('$now') ) "  ;			    
					break;		
	  case '2' :  // 금일시공예정
					$attached="  and  (workday between date('$now') and date('$now') ) "  ;
					$whereattached="  where (workday between date('$now') and date('$now') ) "  ;			    
					break;		
	  case '4' :  // 금일이후 시공
					$attached= " and ( workday > CURDATE() )  " ;
					$whereattached= " where ( workday > CURDATE() )  " ;			    
					break;		
		default:	
				$attached=" ";	  	    
	}
}


 
$page_scale = 10;   // 한 페이지당 표시될 페이지 수  10페이지
$first_num = ($page-1) * $scale;  // 리스트에 표시되는 게시글의 첫 순번.
    
 
$time = time();  
$today=date("Y-m-d");  // 현재일 저장   	  
$yearAfter=date("Y-m-d",strtotime("+12 month", $time)) ; // 1년 후 산출

// 조건별 DB검색루틴
$now = date("Y-m-d");	 // 현재 날짜와 크거나 같으면 출고예정으로 구분	   

	 
$a= $attached . " " .  $orderby . " limit $first_num, $scale"; 
$b= $attached .  " " . $orderby;
$c= $whereattached . " " .  $orderby . " limit $first_num, $scale"; 
$d= $whereattached .  " " . $orderby;

		  if($search==""){
					 $sql="select * from jtechel.jtech  " . $c; 					
	                 $sqlcon = "select * from jtechel.jtech  "  . $d; 
		  }
			else						 
		  			 
        {
	   // 필드별 검색하기
			$fields = ["workplacename", "firstordman", "secondordman", "chargedman", "address", "firstord", "secondord", "worker", "memo", "memo2", "material1" , "material2" , "material3" , "material4" , "material5"   ];

			$search_conditions = array_map(function($field) use ($search) {
				return "$field like '%$search%'";
			}, $fields);

			$where_clause = implode(" or ", $search_conditions);

			$sql = "select * from jtechel.jtech where ($where_clause) $a";
			$sqlcon = "select * from jtechel.jtech where ($where_clause) $b";
		  } 
		  
// print $user_name;		  
//  print $sql;		  
   
 try{  
	$allstmh = $pdo->query($sqlcon);         // 검색 조건에 맞는 쿼리 전체 개수
	$temp2=$allstmh->rowCount();  
	$stmh = $pdo->query($sql);            // 검색조건에 맞는글 stmh
	$temp1=$stmh->rowCount();
	  
	$total_row = $temp2;     // 전체 글수	
         					 
  	 $total_page = ceil($total_row / $scale); // 검색 전체 페이지 블록 수
	 $current_page = ceil($page/$page_scale); //현재 페이지 블록 위치계산			 
	  //   print "$page&nbsp;$total_page&nbsp;$current_page&nbsp;$search&nbsp;$mode";	  		 
	  
?>

<form  id="board_form" name="board_form" method="post" enctype="multipart/form-data" action="list.php"  >		

<div class="container-fluid">	

<? include 'navbarsub.php'; ?>
	  
			<form id="board_form" method="post" enctype="multipart/form-data" action="list.php"  >		
            <input type="hidden" id="SelectWork" name="SelectWork" value="<?=$SelectWork?>">  
            <input type="hidden" id="vacancy" name="vacancy" > 
            <input type="hidden" id="searchOpt" name="searchOpt" >  
            <input type="hidden" id="partOpt" name="partOpt" value="<?=$partOpt?>"> 
			<input type="hidden" id="cursort" name="cursort" value="<?=$cursort?>" size="5" > 	
			<input type="hidden" id="sortof" name="sortof" value="<?=$sortof?>" size="5" > 				
            <input type="hidden" id="page" name="page" value="<?=$page?>"> 
            <input type="hidden" id="scale" name="scale" value="<?=$scale?>"> 
			
			
     <div class="d-flex p-2 m-2 mt-3 mb-1 justify-content-center">				
			<span class="fs-4"> <strong> (수주 LIST) </strong> </span>
			     &nbsp;&nbsp;&nbsp;  								
				    <span class="input-group-text text-primary align-items-center bg-white"  > 목록개수 </span>  &nbsp; 
				   <select name="scaleval" id="scaleval" >
					   <?php		 
								
					   $scalearr = array();
					   array_push($scalearr,'50','100','200','300','500');
					   
					   for($i=0; $i<count($scalearr); $i++) {
								 if($scale==$scalearr[$i])
											print "<option selected value='" . $$scalearr[$i] . "'> " . $scalearr[$i] .   "</option>";
									 else   
							   print "<option value='" . $scalearr[$i] . "'> " . $scalearr[$i] .   "</option>";
						   } 		   
						   

								?>	  
						</select> 
				 &nbsp;&nbsp;
								 
			 
                 <button class="btn btn-dark btn-sm " type="button" id=AlldataBtn > 전체 </button>&nbsp;&nbsp;
                 <button class="btn btn-outline-secondary btn-sm  " type="button" id=todayregistBtn > 금일접수 </button>  &nbsp;&nbsp;
                 <button class="btn btn-outline-danger btn-sm  " type="button" id=todayoutputBtn > 금일시공 </button>  &nbsp;&nbsp;                 
                 <button class="btn btn-outline-primary btn-sm  " type="button" id=aftertodayoutputBtn > 금일이후 시공 </button> &nbsp;&nbsp;
				<span class="input-group-text">   ▷ 총 <?= $total_row ?> 개 </span>				 
					<input type="hidden" id="check" name="check" value="<?=$check?>" size="5" > 		
					<input type="hidden" id="plan_output_check" name="plan_output_check" value="<?=$plan_output_check?>" size="5" > 				
					<input type="hidden" id="output_check" name="output_check" value="<?=$output_check?>" size="5" > 				
					<input type="hidden" id="team_check" name="team_check" value="<?=$team_check?>" size="5" > 				
					<input type="hidden" id="measure_check" name="measure_check" value="<?=$measure_check?>" size="5" > 				
					<input type="hidden" id="sqltext" name="sqltext" value="<?=$sqltext?>" > 	
					
					<span class="input-group-text"> 				  
					<input type="text" id="search" name="search" value="<?=$search?>" onkeydown="JavaScript:SearchEnter();"  > &nbsp;
                    <button id="searchBtn" type="button" class="btn btn-dark  btn-sm"  > 검색  </button> &nbsp;&nbsp;&nbsp;&nbsp;
													 			 
		</div>
 
 <div class="row p-4 mt-3 mb-5 justify-content-center">	
	<table class="table table-bordered table-striped table-hover " style="cursor:pointer;"> 	
          <thead class="table-secondary">
				<tr class="table-hover">
				<th class="text-center">번호</th>
				<th class="text-center  text-center" id="registerBtn" >접수</th>
				<th class="text-center" id="measuredayBtn" >실측</th>
				<th class="text-center text-primary"  id="forecastBtn" >시공예정</th>
				<th class="text-center text-success"  id="workdoneBtn" >시공완료</th>
				<th class="text-center"  id="demandBtn" >대금청구</th>
				<th class="text-center"  id="donedemandBtn" >대금지급</th>
				<th class=" text-center"> PJ num </th>
				<th class=" text-center"> 공사명 </th>
				<th class=" text-center">주소</th>            
				<th class=" text-center">원청</th>
				<th class=" text-center">발주처</th>
				<th class="text-center">시공팀</th>
				<?php if($level < 2)
				   print '<th class="text-secondary text-center">세부발주내역</th>';
				 ?>
				 <th class=" text-secondary text-center">시공팀 기록</th>
          </tr>	  			  
		  </thead>
	  <tbody>
	<?php  
		  if ($page<=1)  
			$start_num=$total_row;    // 페이지당 표시되는 첫번째 글순번
		  else 
			$start_num=$total_row-($page-1) * $scale;
		
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
	       <tr onclick="redirect('write_form.php?num=<?=$num?>&page=<?=$page?>&scale=<?=$scale?>')">
		   			 
            <td class="text-center"><?=$start_num?> </td>
            <td class=" text-center"> <?=$regist_day?>  </td>
            <td class="text-center"> <?=$measureday?> </td>
            <td class="text-center text-primary"> <?=$workday?> </td>
            <td class="text-center  text-success">  <?=$doneday?> </td>
            <td class="text-center ">  <?=$demand?> </td>
            <td class="text-center ">  <?=$donedemand?> </td>
            <td class=" "> <?=$pjnum?> </td>
            <td class=" "> <strong> <?=$workplacename?>  </strong> </td>
            <td class=" ">		<?=$address?> 		</td> 
            <td class=" text-center ">		<?=$firstord?> 	</td>	
            <td class=" text-center ">		<?=$secondord?> 	</td>	
            <td class="text-center ">		<?=$worker?> 		</td>
			<?php if($level < 2)
             print '<td class=" text-secondary ">' .  iconv_substr($memo,0,15,"utf-8") . '	</td>	 ';
		    ?>
			<td class=" text-secondary ">		 <?=iconv_substr($memo2,0,15,"utf-8")?> 	</td>
			
          </tr>			
					<?php
					$start_num--;
					 } 
		  } catch (PDOException $Exception) {
		  print "오류: ".$Exception->getMessage();
		  }  
		   // 페이지 구분 블럭의 첫 페이지 수 계산 ($start_page)
			  $start_page = ($current_page - 1) * $page_scale + 1;
		   // 페이지 구분 블럭의 마지막 페이지 수 계산 ($end_page)
			  $end_page = $start_page + $page_scale - 1;  
		 ?>
		   
		   </tbody>
	  </table>	 
 
 <div class="row  mt-5 justify-content-center text-center">	
         <?php
            if($page!=1 && $page>$page_scale){
              $prev_page = $page - $page_scale;    
              // 이전 페이지값은 해당 페이지 수에서 리스트에 표시될 페이지수 만큼 감소
              if($prev_page <= 0) 
              $prev_page = 1;  // 만약 감소한 값이 0보다 작거나 같으면 1로 고정
		      print '<button class="btn btn-outline-secondary btn-sm" type="button" id=previousListBtn  onclick="movetoPage(' . $prev_page . ')"> ◀ </button> &nbsp;' ;              
            }
            for($i=$start_page; $i<=$end_page && $i<= $total_page; $i++) {        // [1][2][3] 페이지 번호 목록 출력
              if($page==$i) // 현재 위치한 페이지는 링크 출력을 하지 않도록 설정.
                print '<span class="text-secondary" >  ' . $i . '  </span>'; 
              else 
                   print '<button class="btn btn-outline-secondary btn-sm" type="button" id=moveListBtn onclick="movetoPage(' . $i . ')"> ' . $i . '</button> &nbsp;' ;     			
            }

            if($page<$total_page){
              $next_page = $page + $page_scale;
              if($next_page > $total_page) 
                     $next_page = $total_page;
                // netx_page 값이 전체 페이지수 보다 크면 맨 뒤 페이지로 이동시킴
				  print '<button class="btn btn-outline-secondary btn-sm" type="button" id=nextListBtn onclick="movetoPage(' . $next_page . ')"> ▶ </button> &nbsp;' ; 
            }
            ?>              
    </div>	 
</div>			
</div>			
</form>		
		   
		  
<script> 

function redirect(url) {
    window.location.href = url;
}
	
	
function SearchEnter(){
	
    if(event.keyCode == 13){		
		$("#page").val('1');
		document.getElementById('board_form').submit(); 
    }
}

	
function movetoPage(page){ 	  
	  $("#page").val(page); 
      // var echo="<?php echo $partOpt; ?>"; 
      // var searchOpt="<?php echo $searchOpt; ?>"; 
      // var search="<?php echo $search; ?>"; 

     // $("#partOpt").val(partOpt);
     // $("#searchOpt").val(searchOpt);
     // $("#search").val(search);
	 $("#board_form").submit();  
	}	
	
$(document).ready(function(){
	
$("#scaleval").on("change", function(){
    //selected value
    $("#scale").val($(this).val());
	// 화면고정
	$('#page').val('1');      
	$('#stable').val('1');      
	$('#sortof').val('0');	
	$('#board_form').submit();			
    
});	
	
	
// div 접수일 실측일 등 클릭했을때 이벤트 발생
$("#registerBtn").click(function(){ 
	  $("#sortof").val('1');
	  $("#page").val('1');
	  $("#board_form").submit();   
	});		
// div 접수일 실측일 등 클릭했을때 이벤트 발생	
 $("#measuredayBtn").click(function(){ 
	  $("#sortof").val('2');
	  $("#page").val('1');
	  $("#board_form").submit();   
	});		
// div 접수일 실측일 등 클릭했을때 이벤트 발생	
 $("#forecastBtn").click(function(){ 
	  $("#sortof").val('3');
	  $("#page").val('1');
	  $("#board_form").submit();   
	});		
// div 접수일 실측일 등 클릭했을때 이벤트 발생	
 $("#workdoneBtn").click(function(){ 
	  $("#sortof").val('4');
	  $("#page").val('1');
	  $("#board_form").submit();   
	});		
// div 접수일 실측일 등 클릭했을때 이벤트 발생	
 $("#demandBtn").click(function(){ 
	  $("#sortof").val('5');
	  $("#page").val('1');
	  $("#board_form").submit();   
	});		
// div 접수일 실측일 등 클릭했을때 이벤트 발생	
 $("#donedemandBtn").click(function(){ 
	  $("#sortof").val('6');
	  $("#page").val('1');
	  $("#board_form").submit();   
	});		



	$("#searchBtn").click(function(){ 
	  $("#SelectWork").val('listsearch');
	  $("#page").val('1');
	  $("#board_form").submit();   
	});	
	$("#AlldataBtn").click(function(){ 
	  $("#searchOpt").val('0');  // 전체로 검색함.
	  $("#page").val('1');	  
      $("#search").val('');
      $("#sortof").val('0');
	  $("#board_form").submit();   
	});	 	
	$("#todayregistBtn").click(function(){ 
	  $("#searchOpt").val('1');  // 금일접수로 검색함.
	  $("#page").val('1');	  
	  $("#board_form").submit();   
	});		
       	
	$("#todayoutputBtn").click(function(){ 
	  $("#searchOpt").val('2');  // 금일시공예정로 검색함.
	  $("#page").val('1');	  
	  $("#board_form").submit();   
	});		

	$("#aftertodayoutputBtn").click(function(){ 
	  $("#searchOpt").val('4');  // 금일 이후 시공예정로 검색함.
	  $("#page").val('1');	  
	  $("#board_form").submit();   
	});		
       
	
});			

function 	alert_msg(titlemsg,contextmsg) {
// 화면에 메시지창
	Swal.fire({ 
		   title: titlemsg, 
		   text: contextmsg , 
		   icon: 'success',                  // success, error, warning, info, question  5가지 가능함.
		   showCancelButton: true, 
		   confirmButtonColor: '#3085d6', 
		   cancelButtonColor: '#d33', 
		   confirmButtonText: '저장', 
		   cancelButtonText: '취소' })
		   .then((result) => { if (result.isConfirmed) { 
			$("#SelectWork").val('saveini'); 						 
			$("#board_form").submit();  
		   
		   Swal.fire( '수고하세요.', '알림완료!', 'success' ) } })		
}
	
function alert_confirm(titlemsg,contextmsg) {
// 화면에 알림 (성공)
	  Swal({ icon: 'success', // Alert 타입 
	  title: titlemsg, // Alert 제목 
	  text: contextmsg // Alert 내용 
		}); 	
      
}	
	
					 
function getToday(){   // 2021-01-28 형태리턴
    var now = new Date();
    var year = now.getFullYear();
    var month = now.getMonth() + 1;    //1월이 0으로 되기때문에 +1을 함.
    var date = now.getDate();

    month = month >=10 ? month : "0" + month;
    date  = date  >= 10 ? date : "0" + date;
     // ""을 빼면 year + month (숫자+숫자) 됨.. ex) 2018 + 12 = 2030이 리턴됨.

    //console.log(""+year + month + date);
    return today = ""+year + "-" + month + "-" + date; 
}


function inputNumberFormat(obj) { 
obj.value = comma(uncomma(obj.value)); 
} 
function comma(str) { 
    str = String(str); 
    return str.replace(/(\d)(?=(?:\d{3})+(?!\d))/g, '$1,'); 
} 
function uncomma(str) { 
    str = String(str); 
    // str = str.replace(/\./g, ''); 
	tmp = Number(str.replace(/[^\d]+/g, ''))
    return tmp; 
}

  </script>
</body>

</html>