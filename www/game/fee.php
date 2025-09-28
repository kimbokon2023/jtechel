<?php 
// 환경파일 읽어오기 (테이블명 작업 폴더 등)
include 'ini.php';    
session_start(); 

$level= $_SESSION["level"];
$user_name= $_SESSION["name"];
$id= $_SESSION["userid"];

ini_set('display_errors','0');  // 화면에 warning 없애기	

 if(!isset($_SESSION["level"]) || $_SESSION["level"]>5) {
          /*   alert("관리자 승인이 필요합니다."); */
		 sleep(1);
         header("Location:http://j-techel.co.kr/game/login/login_form.php"); 
         exit;
   }  

// 모바일 사용여부 확인하는 루틴
$mAgent = array("iPhone","iPod","Android","Blackberry", 
    "Opera Mini", "Windows ce", "Nokia", "sony" );
$chkMobile = false;
for($i=0; $i<sizeof($mAgent); $i++){
    if(stripos( $_SERVER['HTTP_USER_AGENT'], $mAgent[$i] )){
        $chkMobile = true;
        break;
    }
}   
   


?>

<!doctype html>

<html lang="ko">
<head>
<!-- Required meta tags -->
<meta charset="utf-8">


<title>YH 시스템 지출</title>

<?php
$root_dir = $_SERVER['DOCUMENT_ROOT'] ;

?>


<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">

<!-- viewport
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />	
 -->
 
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>

<!-- 화면에 UI창 알람창 -->
<link href="
https://cdn.jsdelivr.net/npm/sweetalert2@11.7.10/dist/sweetalert2.min.css" rel="stylesheet">

<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script> 
<script src="https://code.highcharts.com/highcharts.js"></script>
 <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">   <!--날짜 선택 창 UI 필요 -->

<!-- 최초화면에서 보여주는 상단메뉴 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" >
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.css">


<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js" ></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.10/dist/sweetalert2.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.js"></script>


<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js" integrity="sha512-d9xgZrVZpmmQlfonhQUvTR7lMPtO7NkZMkA0ABN3PHCbKA5nqylQ/yWlFAyY6hYgdF1Qh6nYiuADWwKB4C2WSw==" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.bundle.js" integrity="sha512-zO8oeHCxetPn1Hd9PdDleg5Tw1bAaP0YmNvPY8CwcRyUk7d7/+nyElmFrB6f7vg4f7Fv4sui1mcep8RIEShczg==" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.bundle.min.js" integrity="sha512-SuxO9djzjML6b9w9/I07IWnLnQhgyYVSpHZx0JV97kGBfTIsUYlWflyuW4ypnvhBrslz1yJ3R+S14fdCWmSmSA==" crossorigin="anonymous"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.css" integrity="sha512-C7hOmCgGzihKXzyPU/z4nv97W0d9bv4ALuuEbSf6hm93myico9qa0hv4dODThvCsqQUmKmLcJmlpRmCaApr83g==" crossorigin="anonymous" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.js" integrity="sha512-hZf9Qhp3rlDJBvAKvmiG+goaaKRZA6LKUO35oK6EsM0/kjPK32Yw7URqrq3Q+Nvbbt8Usss+IekL7CRn83dYmw==" crossorigin="anonymous"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.css" integrity="sha512-/zs32ZEJh+/EO2N1b0PEdoA10JkdC3zJ8L5FTiQu82LR9S/rOQNfQN7U59U9BC12swNeRAz3HSzIL2vpp4fv3w==" crossorigin="anonymous" />

<link rel="stylesheet" href="https://uicdn.toast.com/tui.pagination/latest/tui-pagination.css" />
<script src="https://uicdn.toast.com/tui.pagination/latest/tui-pagination.js"></script>
<link rel="stylesheet" href="https://uicdn.toast.com/tui-grid/latest/tui-grid.css"/>
 <script src="https://uicdn.toast.com/tui-grid/latest/tui-grid.js"></script>	

<link rel="stylesheet" href="<?$root_dir?>/css/style.css">
  
<script src="http://j-techel.co.kr/common.js"></script>  
<script src="http://j-techel.co.kr/js/date.js"></script>   <!-- 기간을 설정하는 관련 js 포함 -->

</head>



<style>

.fixed-table {
	position: sticky;
	top: 0;
	background-color: #fff;
	z-index: 1;
	margin-bottom: 10px;
	box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.form-group {
	display: flex;
	justify-content: center;
}

.form-group input {
	margin: auto;
	text-align : center;
}	
.list-cell {
	font-size: 25px; /* or any other size that you want */
	font-weight:normal;
}	


/* 우측배너 제작 */

.sideBanner {
  position: absolute;
  width: calc(100vw - 90vw);
  height:calc(100vh - 70vh);
  top: calc(100vh - 50vh);
  left: calc(100vw - 20vw);  
}

.card-title {
	font-size: 30px; /* or any other size that you want */
}

.card-body {
	font-size: 30px; /* or any other size that you want */
}

.form-group label {
	font-size: 30px; /* or any other size that you want */
}

.form-group input {
	font-size: 25px; /* or any other size that you want */
	width : calc(100vw - 65vw) ;
}

.table th, .table tr, .table td {
	font-size: 20px; /* or any other size that you want */
}

.sideBanner {
	font-size: 30px; /* or any other size that you want */		
}  

.table td input{
        font-size: 25px; /* or any other size that you want */
    }

.input-group {
	font-size: 25px; /* or any other size that you want */		
}  

input {
	font-size: 25px; /* or any other size that you want */		
	height: 40px;
}  
	
	
	
@media (max-width: 1000px) {
    .card-title {
        font-size: 40px; /* or any other size that you want */
    }
	
    .card-body {
        font-size: 30px; /* or any other size that you want */
    }

    .form-group label {
        font-size: 30px; /* or any other size that you want */
    }

    .form-group input {
        font-size: 40px; /* or any other size that you want */
    }

    .table th, .table tr, .table td {
        font-size: 40px; /* or any other size that you want */
    }
	
	.table td input{
        font-size: 35px; /* or any other size that you want */
    }	

    .input-group {
        font-size: 45px; /* or any other size that you want */		
    }  
	
	input {
		font-size: 35px; /* or any other size that you want */		
		height: 45px;
	}  
	span {
		font-size: 45px; /* or any other size that you want */		
		height: 55px;
	}  
		
.sideBanner {
  position: absolute;
  width: calc(100vw - 90vw);
  height:calc(100vh - 70vh);
  top: calc(100vh - 50vh);
  left: calc(100vw - 20vw);  
  
}	
	
	
}

</style>	



<body>

<?php include 'myheader.php'; ?>


 <?php
 
include "_request.php"; 

if($fromdate=="")
{
	// $fromdate=substr(date("Y-m-d",time()),0,4) ;
	$fromdate=$fromdate . "2023-01-01";
}
if($todate=="")
{
	$todate=substr(date("Y-m-d",time()),0,4) . "-12-31" ;
	$Transtodate=strtotime($todate.'+1 days');
	$Transtodate=date("Y-m-d",$Transtodate);
}
    else
	{
	$Transtodate=strtotime($todate);
	$Transtodate=date("Y-m-d",$Transtodate);
	}
		  
$SettingDate="registedate ";

$common="   where " . $SettingDate . " between date('$fromdate') and date('$Transtodate') order by " . $SettingDate;
$a= $common . " desc, num desc limit $first_num, $scale";    //내림차순
$b= $common . " desc, num desc ";    //내림차순 전체
 

// 검색을 위해 모든 검색변수 공백제거
$search = str_replace(' ', '', $search);    
  
  if($mode=="search"){
		  if($search==""){			  
									 $sql="select * from jtechel.game_fee " . $a; 					
									 $sqlcon = "select * from jtechel.game_fee " . $b;   // 전체 레코드수를 파악하기 위함.					
							}											
			       
             elseif($search!="") { 
			    
										  $sqlAll ="select * from jtechel.game_fee where (mcno like '%$search%')  or (registedate like '%$search%' ) or (text1 like '%$search%' ) or (text2 like '%$search%' ) or (text3 like '%$search%' ) or (text4 like '%$search%' ) or (service like '%$search%' )  or (memo like '%$search%' )  or (receivable like '%$search%' ) order by registedate desc, num desc  ";
										  $sql = $sqlAll . " limit $first_num, $scale ";
										  $sqlcon = $sqlAll ;
								}	
						}								
  if($mode=="") {
					 $sql="select * from jtechel.game_fee " . $a; 					
					 $sqlcon = "select * from jtechel.game_fee " . $b;   // 전체 레코드수를 파악하기 위함.					
                }		
				
		$nowday=date("Y-m-d");   // 현재일자 변수지정   
						 
		 // // 전체합계(입고부분)를 산출하는 부분 
		// $sum_title=array(); 
		// $sum=array();
		// $num_arr = array();
		// $mcno_arr = array();
		// $input_arr = array();
		// $output_arr = array();

		// $sql="select * from jtechel.game_fee " . $b; 	 
		 
		 // try{  
		// // 레코드 전체 sql 설정
		   // $stmh = $pdo->query($sql);            // 검색조건에 맞는글 stmh
		
		    // $total_row = $stmh->rowCount();
		   
		   // while($row = $stmh->fetch(PDO::FETCH_ASSOC)) {

					 // array_push($num_arr, $row["num"]);
					 // array_push($mcno_arr, $row["mcno"]);
					 // array_push($input_arr, $row["input_arr"]);
					 // array_push($output_arr, $row["output_arr"]);
					// }		 
		   // } catch (PDOException $Exception) {
			// print "오류: ".$Exception->getMessage();
		// }  
		
		
		
 ?>
		 

<div class="container-fluid justify-content-center align-items-center mt-5 mb-2">  	
	<div class="d-flex mb-1 mt-4 justify-content-center align-items-center">
	        <span class="text-secondary fs-2 mt-5" > 지출 자료 리스트 (단위:천원) </span>
	</div>	
</div>	 
<div class="container-fluid justify-content-center align-items-center mt-5 mb-2">  		 
  <form name="board_form" id="board_form"  method="post" action="fee.php?mode=search&search=<?=$search?>&find=<?=$find?>&year=<?=$year?>&search=<?=$search?>&process=<?=$process?>&done_check=<?=$done_check?>&fromdate=<?=$fromdate?>&todate=<?=$todate?>&up_fromdate=<?=$up_fromdate?>&up_todate=<?=$up_todate?>&separate_date=<?=$separate_date?>&view_table=<?=$view_table?>&scale=<?=$scale?>&done_check=<?=$done_check?>">  
  
			<input type="hidden" id="done_check_val" name="done_check_val" value="<?=$done_check_val?>" >
			<input type="hidden" id="voc_alert" name="voc_alert" value="<?=$voc_alert?>" size="5" > 	
			<input type="hidden" id="ma_alert" name="ma_alert" value="<?=$ma_alert?>" size="5" > 	
			<input type="hidden" id="order_alert" name="order_alert" value="<?=$order_alert?>" size="5" > 	
			<input type="hidden" id="page" name="page" value="<?=$page?>" size="5" > 	
			<input type="hidden" id="scale" name="scale" value="<?=$scale?>" size="5" > 	
			<input type="hidden" id="yearcheckbox" name="yearcheckbox" value="<?=$yearcheckbox?>" size="5" > 	
			<input type="hidden" id="year" name="year" value="<?=$year?>" size="5" > 	
			<input type="hidden" id="check" name="check" value="<?=$check?>" size="5" > 	
			<input type="hidden" id="output_check" name="output_check" value="<?=$output_check?>" size="5" > 	
			<input type="hidden" id="plan_output_check" name="plan_output_check" value="<?=$plan_output_check?>" size="5" > 	
			<input type="hidden" id="team_check" name="team_check" value="<?=$team_check?>" size="5" > 	
			<input type="hidden" id="measure_check" name="measure_check" value="<?=$measure_check?>" size="5" > 	
			<input type="hidden" id="cursort" name="cursort" value="<?=$cursort?>" size="5" > 	
			<input type="hidden" id="sortof" name="sortof" value="<?=$sortof?>" size="5" > 	
			<input type="hidden" id="stable" name="stable" value="<?=$stable?>" size="5" > 	
			<input type="hidden" id="sqltext" name="sqltext" value="<?=$sqltext?>" > 
			
			
			<div id="vacancy" style="display:none">  </div>	 

				
	<div class="d-flex mb-1 mt-1 justify-content-center align-items-center">
	</div>	
	
   <?php if($level=='1') {  ?>
	
	<div class="d-flex mb-1 mt-2 justify-content-center align-items-center">  

			<div class="input-group p-1 mb-1  justify-content-center align-items-center">	  

				   <span class="text-secondary" >  ▷ 총&nbsp; <span id="total_row"> </span>&nbsp; 개 자료 	</span>&nbsp; &nbsp;  &nbsp; &nbsp;  &nbsp; &nbsp; 
				   <span class="text-secondary" > 화면 목록수 &nbsp; </span>
				   <select name="scaleval" id="scaleval" >
					   <?php		 
								
					   $scalearr = array();
					   array_push($scalearr,'10','20','30','50','100');
					   
					   for($i=0; $i<count($scalearr); $i++) {
								 if($scale==$scalearr[$i])
											print "<option selected value='" . $$scalearr[$i] . "'> " . $scalearr[$i] .   "</option>";
									 else   
							   print "<option value='" . $scalearr[$i] . "'> " . $scalearr[$i] .   "</option>";
						   } 		   
						   

								?>	  
						</select> 	
		     	</div>
	  </div>
	    <?php } ?>
		   <?php if($level=='1') {  ?>
			<div class="d-flex mb-1 mt-2 justify-content-center align-items-center">  		
				
			 &nbsp; &nbsp;
				<button type="button" id="preyear" class="btn btn-secondary btn-lg "   onclick='pre_year()' > 전년도 </button>  &nbsp;  	
				<button type="button" id="three_month" class="btn btn-secondary btn-lg "  onclick='three_month_ago()' > M-3월 </button> &nbsp;  	
				<button type="button" id="prepremonth" class="btn btn-secondary btn-lg "  onclick='prepre_month()' > 전전월 </button>	&nbsp;  
				<button type="button" id="premonth" class="btn btn-secondary btn-lg "  onclick='pre_month()' > 전월 </button>  &nbsp; 	
				<button type="button" class="btn btn-secondary btn-lg "  onclick='yesterday()' > 전일 </button>  &nbsp; 					
				<button type="button" class="btn btn-dark btn-lg "  onclick='javascript:today()' > 당일</button>	&nbsp;  				
				<button type="button" id="thismonth" class="btn btn-dark btn-lg "  onclick='this_month()' > 당월 </button>	&nbsp;  	   
				<button type="button" id="thisyear" class="btn btn-dark btn-lg "  onclick='this_year()' > 당해년도 </button> &nbsp;  			
			</div>
			<div class="d-flex mb-1 mt-2 justify-content-center align-items-center">  					
			   <span class='input-group-text align-items-center' style='width:400 px;'>  
			   <input type="date" id="fromdate" name="fromdate" size="12" value="<?=$fromdate?>" placeholder="기간 시작일">  &nbsp; 부터 &nbsp;  
			   <input type="date" id="todate" name="todate" size="12"  value="<?=$todate?>" placeholder="기간 끝">  &nbsp;  까지    </span>  &nbsp;

            <?php 
			
			  if($chkMobile)
			  {
				  print '</div>
								<div class="d-flex mb-1 mt-3 justify-content-center align-items-center">  					';
			  }
			  ?>
			   <input type="text" name="search" id="search" style="width:200px;" value="<?=$search?>" onkeydown="JavaScript:SearchEnter();" placeholder="검색어"> 
			    &nbsp; &nbsp; 
				<button type="button" id="searchBtn" class="btn btn-dark  btn-lg "  > 검색 </button>	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				
			
		  
		  <?php } 
		     
			 else
			 {  
		       print '<div class="d-flex mb-5 mt-5 justify-content-center align-items-center">  	';
			 }
		  ?>
			
			<button type="button" class="btn btn-success  btn-lg " onclick="popupCenter('./write_form_fee.php', '투입/배출', 1050, 900)" > 지출등록 </button> &nbsp;		   		
			
			   </div>
      </div>
	  
	  <div class="d-flex justify-content-center align-items-center"> 		
	  
      <div class="limit">
        <ul class="list-group">
          <li class="list-row list-row--header ">
            <div class="list-cell list-cell--80 text-center">번호</div>
            <div class="list-cell list-cell--200 text-center">  기준일(등록일)   </div>            
            <div class="list-cell list-cell--120 text-center">  지출합   </div>             
            <div class="list-cell list-cell--200 text-center">  지출내역   </div>             
            <div class="list-cell list-cell--200 text-center">  메모   </div> 
            <div class="list-cell list-cell--200 text-center">  미수금   </div>             
          </li>	     

 <?php
	 try{  
	  $allstmh = $pdo->query($sqlcon);         // 검색 조건에 맞는 쿼리 전체 개수
      $temp2=$allstmh->rowCount();  
	  $stmh = $pdo->query($sql);            // 검색조건에 맞는글 stmh
      $temp1=$stmh->rowCount();
	      
	  $total_row = $temp2;     // 전체 글수	  		
         					 
     $total_page = ceil($total_row / $scale); // 검색 전체 페이지 블록 수
	 $current_page = ceil($page/$page_scale); //현재 페이지 블록 위치계산			 
   //   print "$page&nbsp;$total_page&nbsp;$current_page&nbsp;$search&nbsp;$mode"; 
 
 
		  if ($page<=1)  
			$start_num=$total_row;    // 페이지당 표시되는 첫번째 글순번
		     else 
		      	$start_num=$total_row-($page-1) * $scale;
	    
	       while($row = $stmh->fetch(PDO::FETCH_ASSOC)) {
	  
                 include 'rowDB_fee.php';			  

	
	 if($outdate!="") {
    $week = array("(일)" , "(월)"  , "(화)" , "(수)" , "(목)" , "(금)" ,"(토)") ;
    $outdate = $outdate . $week[ date('w',  strtotime($outdate)  ) ] ;
		}
			
    if($text1!==null)	
	    $str = '일비 ' . number_format($text1) . "<br>";
	if($text2!==null)	
		$str .= '식대 ' . number_format($text2) . "<br>";
	if($text3!==null)	
		$str .= '집세 ' . number_format($text3) . "<br>";
	if($text4!==null)	
		$str .= '서비스 ' . number_format($text4) . "<br>";
	if($text5!==null)	
		$str .= '기타 ' . number_format($text5);

	$sum = number_format(intval($text1) + intval($text2) + intval($text3) + intval($text4) + intval($text5));
		 
?>
			  
		<li class="list-row">
		  <a class="list-link" style="text-decoration:none;" href="#" onclick="popupCenter('./write_form_fee.php?num=<?=$num?>&mode=update', '자료수정', 1050, 900)">
			<div class="list-cell list-cell--80 text-center"><?=$start_num?></div>
			<div class="list-cell list-cell--200 <?=$date_font?> text-center"><?=iconv_substr($outdate, 0, 15, "utf-8")?></div>			
			<div class="list-cell list-cell--120 text-center"><?=$sum?></div>
			<div class="list-cell list-cell--200 "><?=$str?></div>
			<div class="list-cell list-cell--200 text-center"><?=$memo?></div>
			<div class="list-cell list-cell--200 text-center"><?=$receivable?></div>
		  </a>
		</li>
	
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
       </ul>
	   </div>
   </div>
  
 
<div class="row row-cols-auto mt-5 mb-5 justify-content-center align-items-center"> 
 <?php
 
 
   $BigsearchTag = str_replace(' ','|',$Bigsearch);
 
	if($page!=1 && $page>$page_scale){
              $prev_page = $page - $page_scale;    
              // 이전 페이지값은 해당 페이지 수에서 리스트에 표시될 페이지수 만큼 감소
              if($prev_page <= 0) 
              $prev_page = 1;  // 만약 감소한 값이 0보다 작거나 같으면 1로 고정
		      print '<button class="btn btn-outline-secondary  btn-lg" type="button" id=previousListBtn  onclick="javascript:movetoPage(' . $prev_page . ')"> ◀ </button> &nbsp;' ;              
            }
            for($i=$start_page; $i<=$end_page && $i<= $total_page; $i++) {        // [1][2][3] 페이지 번호 목록 출력
              if($page==$i) // 현재 위치한 페이지는 링크 출력을 하지 않도록 설정.
                print '<span class="text-secondary fs-3" >  ' . $i . '  </span>'; 
              else 
                   print '<button class="btn btn-outline-secondary btn-lg" type="button" id=moveListBtn onclick="javascript:movetoPage(' . $i . ')"> ' . $i . '</button> &nbsp;' ;     			
            }

            if($page<$total_page){
              $next_page = $page + $page_scale;
              if($next_page > $total_page) 
                     $next_page = $total_page;
                // netx_page 값이 전체 페이지수 보다 크면 맨 뒤 페이지로 이동시킴
				  print '<button class="btn btn-outline-secondary  btn-lg" type="button" id=nextListBtn onclick="javascript:movetoPage(' . $next_page . ')"> ▶ </button> &nbsp;' ; 
            }
            ?>         
</div>



     </div>   
 
	</form>	 

   
<br>
<br>
<div class="container-fluid">
<? include './footer.php'; ?>
</div>
  
   
<script>

function blinker() {
	$('.blinking').fadeOut(500);
	$('.blinking').fadeIn(500);
}
setInterval(blinker, 1000);

function SearchEnter(){

    if(event.keyCode == 13){
	
    $("#page").val('1');		
	document.getElementById('board_form').submit(); 
    }
}


$(document).ready(function() { 

$("#total_row").text('<?php echo $total_row; ?>');  // 화면표시 유지

	// 화면보기 변경시 적용  
	$("#scaleval").on("change", function(){
		//selected value
		$("#scale").val($(this).val());
		$("#stable").val('1');  // 화면표시 유지
		$('#board_form').submit();			
		
	});	

$("#searchBtn").click(function(){ 	
	  // page 1로 초기화 해야함
     $("#page").val('1');
	 document.getElementById('board_form').submit();    
 
 });	
 
      

});

 
function comma(str) { 
    str = String(str); 
    return str.replace(/(\d)(?=(?:\d{3})+(?!\d))/g, '$1,'); 
} 
function uncomma(str) { 
    str = String(str); 
    return str.replace(/[^\d]+/g, ''); 
}


function movetoPage(page){ 	  
	  $("#page").val(page); 
	 $("#board_form").submit();  
	}		
	
	
function popupCenter(href, pop_name, w, h) {
	var xPos = (document.body.offsetWidth/2) - (w/2); // 가운데 정렬
	xPos += window.screenLeft; // 듀얼 모니터일 때
	var yPos = (document.body.offsetHeight/2) - (h/2);

	window.open(href, pop_name, "width="+w+", height="+h+", left="+xPos+", top="+yPos+", target=_blank , menubar=yes, status=yes, titlebar=yes, resizable=yes");
}
	

</script>




</body>

</html>
