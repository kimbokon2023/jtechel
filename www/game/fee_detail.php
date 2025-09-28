<?php 
// 환경파일 읽어오기 (테이블명 작업 폴더 등)
include 'ini.php';    
session_start(); 
ini_set('display_errors','1');  // 화면에 warning 없애기	  1은 보이기

require_once('branch_select_helper.php');
$branch = isset($_COOKIE['branch']) ? $_COOKIE['branch'] : (intval($_SESSION["level"]) <= 3 ? '전체' : getFirstActiveBranch($pdo));

$choicebranch = $branch ;

$level= $_SESSION["level"];
$user_name= $_SESSION["name"];
$id= $_SESSION["userid"]; 

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


<title>YH 시스템 지출 상세내역 조회</title>

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

$nowday=date("Y-m-d");   // 현재일자 변수지정   

if($fromdate=="")
{
	// $fromdate=substr(date("Y-m-d",time()),0,4) ;
	$fromdate=$nowday;
    //$fromdate="2023-06-01";
}
if($todate=="")
{
	
	$Transtodate=$nowday;
	$todate=$nowday;
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
  
if ($mode == "search") {
  if ($search == "") {
    $branchCondition = ($branch !== 'false' && $branch !== '전체') ? " and branch = '$branch'" : '';
    $sql = "select * from jtechel.game where 1 $branchCondition";
    $sql .= " order by registedate desc, num desc limit $first_num, $scale ";
    $sqlcon = "select * from jtechel.game where 1 $branchCondition";
    $sqlcon .= " order by registedate desc, num desc ";
  } elseif ($search != "") {
    $branchCondition = ($branch !== 'false' && $branch !== '전체') ? " and branch = '$branch'" : '';
    $sql = "select * from jtechel.game where ((mcno like '%$search%') or (registedate like '%$search%') or (item like '%$search%')) $branchCondition";
    $sql .= " order by registedate desc, num desc limit $first_num, $scale ";
    $sqlcon = "select * from jtechel.game where ((mcno like '%$search%') or (registedate like '%$search%') or (item like '%$search%')) $branchCondition";
    $sqlcon .= " order by registedate desc, num desc ";
  }
} elseif ($mode == "") {
  $branchCondition = ($branch !== 'false' && $branch !== '전체') ? " and branch = '$branch'" : '';
  $sql = "select * from jtechel.game where 1 $branchCondition";
  $sql .= " order by registedate desc, num desc limit $first_num, $scale ";
  $sqlcon = "select * from jtechel.game where 1 $branchCondition";
  $sqlcon .= " order by registedate desc, num desc ";
}

$nowday = date("Y-m-d");   // 현재일자 변수지정
		
 ?>
		 

<div class="container justify-content-center align-items-center mt-5 mb-2">  	
	<div class="d-flex  mb-1 mt-4 justify-content-center align-items-center">	
	        <span class="text-secondary fs-2" > 지출 상세 조회 </span>		
            <!-- 새로고침 버튼 추가 -->
            <button type="button" class="btn btn-outline-primary ms-3" onclick="location.reload();">
                <i class="bi bi-arrow-clockwise"></i> 새로고침
            </button>            		  
				
	</div>			
    <div class="d-flex mb-4 mt-4 justify-content-center align-items-center">
			<div class="card shadow-lg border-0" style="background: linear-gradient(90deg, #e3ffe6 0%, #e3f0ff 100%); min-width: 350px;">
				<div class="card-body d-flex flex-column flex-md-row align-items-center gap-3">
					<i class="bi bi-building fs-1 text-primary me-3"></i>
					<div class="d-flex flex-column">
						<label for="branch" class="form-label fw-bold fs-4 mb-2 text-primary">지점 선택</label>
						<select name="branch" id="branch" class="form-select form-select-lg border-2 border-primary shadow-sm" style="width: 250px; font-size: 1.3rem;">
							<?php
								// branches 테이블에서 지점 목록 가져오기
								echo renderBranchSelect($pdo, $branch, true, intval($_SESSION["level"]));
							?>
						</select>
					</div>
				</div>
			</div>
		</div>	
</div>	 
<div class="container justify-content-center align-items-center mt-5 mb-2">  		 
  <form name="board_form" id="board_form"  method="post" action="fee_detail.php?mode=search&search=<?=$search?>&find=<?=$find?>&year=<?=$year?>&search=<?=$search?>&process=<?=$process?>&done_check=<?=$done_check?>&fromdate=<?=$fromdate?>&todate=<?=$todate?>&up_fromdate=<?=$up_fromdate?>&up_todate=<?=$up_todate?>&separate_date=<?=$separate_date?>&view_table=<?=$view_table?>&scale=<?=$scale?>&done_check=<?=$done_check?>">  
  
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
			<input type="hidden" id="search" name="search" > 			
			
			
			<div id="vacancy" style="display:none">  </div>	 

				
	<div class="d-flex mb-1 mt-1 justify-content-center align-items-center">
	</div>	
	
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
				<input type="date" id="fromdate" name="fromdate" size="12" value="<?=$fromdate?>" placeholder="기간 시작일" onchange="submitFormOnDateChange(this)" />
				&nbsp; 부터 &nbsp;
				<input type="date" id="todate" name="todate" size="12" value="<?=$todate?>" placeholder="기간 끝" onchange="submitFormOnDateChange(this)" />
				&nbsp; 까지

            <?php 
			
			  if($chkMobile)
			  {
				  print '</div>
								<div class="d-flex mb-1 mt-3 justify-content-center align-items-center">  					';
			  }
			  ?>
			 			
		  
		  <?php } 
		     
			 else
			 {  
		       print '<div class="d-flex mb-5 mt-5 justify-content-center align-items-center">  	';
			 }
		  ?>
			

			
			   </div>
      </div>
	      
<?php	  
			 
 try{  
	  $allstmh = $pdo->query($sqlcon);         // 검색 조건에 맞는 쿼리 전체 개수
      $temp2=$allstmh->rowCount();  
	  $stmh = $pdo->query($sql);            // 검색조건에 맞는글 stmh
      $temp1=$stmh->rowCount();
	  
echo ' <div class="d-flex justify-content-center align-items-center"> <h1 class="mt-2 mb-2" > 기간 합계  </h1> </div> ';	  
echo ' <div class="d-flex justify-content-center align-items-center"> ';	  

echo '<table class="table table-bordered">';
echo '<thead>';
echo '<tr>';
echo '<th class="text-center  text-success" >지점</th>';
echo '<th class="text-center  text-primary" >지출합계</th>';
echo '<th class="text-center" >일비</th>';
echo '<th class="text-center" >식대</th>';
echo '<th class="text-center" >집세</th>';
echo '<th class="text-center" >서비스</th>';
echo '<th class="text-center" >간식</th>';
echo '<th class="text-center" >기타</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

$total0 = 0;
$total1 = 0;
$total2 = 0;
$total3 = 0;
$total4 = 0;
$total5 = 0;
$total6 = 0;

// while 루프 예시
while ($row = $stmh->fetch(PDO::FETCH_ASSOC)) {
    $text1 = $row["text1"] * 1000;
    $text2 = $row["text2"] * 1000;
    $text3 = $row["text3"] * 1000;
    $text4 = $row["text4"] * 1000;
    $text5 = $row["text5"] * 1000;
    $text6 = $row["text6"] * 1000;

    $total1 += $text1;
    $total2 += $text2;
    $total3 += $text3;
    $total4 += $text4;
    $total5 += $text5;
    $total6 += $text6;
	
	$total0 += $text1 + $text2 + $text3 + $text4 + $text5 + $text6 ;
}


// 누계 출력

echo '<tbody>';
echo '<tr>';
echo '<td class="text-center  text-success" ><strong>' . $branch . '</strong></td>';
echo '<td class="text-center  text-primary" ><strong>' . number_format($total0) . '</strong></td>';
echo '<td class="text-center" ><strong>' . number_format($total1) . '</strong></td>';
echo '<td class="text-center" ><strong>' . number_format($total2) . '</strong></td>';
echo '<td class="text-center" ><strong>' . number_format($total3) . '</strong></td>';
echo '<td class="text-center" ><strong>' . number_format($total4) . '</strong></td>';
echo '<td class="text-center" ><strong>' . number_format($total5) . '</strong></td>';
echo '<td class="text-center" ><strong>' . number_format($total6) . '</strong></td>';
echo '</tr>';
echo '</tbody>';
echo '</table>';
echo '</div>';

  } catch (PDOException $Exception) {
  print "오류: ".$Exception->getMessage();
  }  

 try{  
	  $allstmh = $pdo->query($sqlcon);         // 검색 조건에 맞는 쿼리 전체 개수
      $temp2=$allstmh->rowCount();  
	  $stmh = $pdo->query($sql);            // 검색조건에 맞는글 stmh
      $temp1=$stmh->rowCount();

echo ' <div class="d-flex justify-content-center align-items-center"> <h1 class="mt-2 mb-2" > 상세내역  </h1> </div> ';

echo ' <div class="d-flex justify-content-center align-items-center"> ';


echo '<table class="table table-bordered">';
echo '<thead>';
echo '<tr>';
echo '<th class="text-center text-success"> 지점</th>';
echo '<th class="text-center">일자</th>';
echo '<th class="text-center text-primary ">지출합계</th>';
echo '<th class="text-center">일비</th>';
echo '<th class="text-center">식대</th>';
echo '<th class="text-center">집세</th>';
echo '<th class="text-center">서비스</th>';
echo '<th class="text-center">간식</th>';
echo '<th class="text-center">기타</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';



$stmh = $pdo->query($sql);            // 검색조건에 맞는글 stmh
while ($row = $stmh->fetch(PDO::FETCH_ASSOC)) {
    $total0 = 0;
    $registedate = $row["registedate"];

    $text1 = $row["text1"] * 1000;
    $text2 = $row["text2"] * 1000;
    $text3 = $row["text3"] * 1000;
    $text4 = $row["text4"] * 1000;
    $text5 = $row["text5"] * 1000;
    $text6 = $row["text6"] * 1000;

    $total1 += $text1;
    $total2 += $text2;
    $total3 += $text3;
    $total4 += $text4;
    $total5 += $text5;
    $total6 += $text6;

    $total0 += $text1 + $text2 + $text3 + $text4 + $text5 + $text6;
	
	if( $choicebranch =='전체')
		$branch = $row["branch"]; 

    // 값이 있는 경우에만 출력
    if ($text1 || $text2 || $text3 || $text4 || $text5 || $text6) {
        echo '<tr>';
        echo '<td class="text-center">' . $branch . '</td>';
        echo '<td class="text-center">' . $registedate . '</td>';
        echo '<td class="text-center text-primary">' . number_format($total0) . '</td>';
        echo '<td class="text-center">' . number_format($text1) . '</td>';
        echo '<td class="text-center">' . number_format($text2) . '</td>';
        echo '<td class="text-center">' . number_format($text3) . '</td>';
        echo '<td class="text-center">' . number_format($text4) . '</td>';
        echo '<td class="text-center">' . number_format($text5) . '</td>';
        echo '<td class="text-center">' . number_format($text6) . '</td>';
        echo '</tr>';
    }
}
		  

echo '</tbody>';
echo '</table>';


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
	  
	


     </div>   
 
	</form>	 

   
<br>
<br>
<div class="container">
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

function submitFormOnDateChange(element) {
    document.getElementById('board_form').submit();
    element.blur(); // 다른 필드로 이동하여 form 전송 후 focus 해제
}




$(document).ready(function() { 



document.getElementById("branch").addEventListener("change", function() {
    // 이곳에 선택 변경 시 실행할 자바스크립트 코드를 작성합니다.
    // 예: 선택된 값에 따라 다른 동작을 수행하거나 AJAX 요청을 보내는 등의 작업을 수행할 수 있습니다.    
	document.cookie = "branch=" + this.value ;    
    // 추가적인 동작을 수행하도록 코드를 작성합니다.
	location.reload();
  });
	

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
