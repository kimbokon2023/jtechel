<?php 
// 환경파일 읽어오기 (테이블명 작업 폴더 등)
include 'ini.php';    
session_start(); 

$level= $_SESSION["level"];
$user_name= $_SESSION["name"];
$id= $_SESSION["userid"];

ini_set('display_errors','1');  // 화면에 warning 없애기	

$branch = isset($_COOKIE['branch']) ? $_COOKIE['branch'] : '전체';

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
      
isset($_REQUEST["num"])  ? $num=$_REQUEST["num"] :   $num=''; 	  
	  
// 자정을 넘긴시간에 하나 데이터를 만들어준다.
require_once("../lib/mydb.php");
$pdo = db_connect();


	 

 try{
	  $sql = "select * from jtechel.game_guest where num = ? ";
	  $stmh = $pdo->prepare($sql); 
      $stmh->bindValue(1,$num,PDO::PARAM_STR); 
      $stmh->execute();
      $count = $stmh->rowCount();            
	  $row = $stmh->fetch(PDO::FETCH_ASSOC);  // $row 배열로 DB 정보를 불러온다.		
		 
	     $guest_registedate = $row["guest_registedate"];
	     $guest_name = $row["guest_name"];
	     $guest_tel = $row["guest_tel"];
	     $branch = $row["branch"];
		 
		 $savedbranch = $branch; // 해당지점 저장
	  
	 }catch (PDOException $Exception) {
	   print "오류: ".$Exception->getMessage();
	 }
	 
// 별명 불러오기
 try{
	  if($branch =='') 
		     $branch = '아우디';
	  $sql = "select * from jtechel.game_alias where branch='$branch'  ";
	  $stmh = $pdo->query($sql);       
      $stmh->execute();
      $count = $stmh->rowCount();            
	  $row = $stmh->fetch(PDO::FETCH_ASSOC);  // $row 배열로 DB 정보를 불러온다.		
		 
	  $alias_arr = explode(",", $row['alias']);
	  
	 }catch (PDOException $Exception) {
	   print "오류: ".$Exception->getMessage();
	 }	 
	 
?>

<!doctype html>

<html lang="ko">
<head>
<!-- Required meta tags -->
<meta charset="utf-8">


<title>YH 회원 통계</title>

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
	font-size: 40px; /* or any other size that you want */
}

.card-body {
	font-size: 25px; /* or any other size that you want */
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
        font-size: 35px; /* or any other size that you want */
    }
	
    .card-body {
        font-size: 25px; /* or any other size that you want */
    }

    .form-group label {
        font-size: 30px; /* or any other size that you want */
    }

    .form-group input {
        font-size: 40px; /* or any other size that you want */
    }

    .table th, .table tr, .table td {
        font-size: 30px; /* or any other size that you want */
    }
	
	.table td input{
        font-size: 35px; /* or any other size that you want */
    }	

    .input-group {
        font-size: 35px; /* or any other size that you want */		
    }  
	
	input {
		font-size: 35px; /* or any other size that you want */		
		height: 45px;
	}  
	span {
		font-size: 35px; /* or any other size that you want */		
		height: 50px;
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

<?php
 
// 오늘 날짜 가져오기
$today = date('Y-m-d');

isset($_REQUEST["search"]) ? $search = $_REQUEST["search"] : $search="";
isset($_REQUEST["scale"]) ? $scale = $_REQUEST["scale"] : $scale=30;
isset($_REQUEST["page"]) ? $page = $_REQUEST["page"] : $page=1;
isset($_REQUEST["mode"]) ? $mode = $_REQUEST["mode"] : $mode="";
isset($_REQUEST["fromdate"]) ? $fromdate = $_REQUEST["fromdate"] : $fromdate="";
isset($_REQUEST["todate"]) ? $todate = $_REQUEST["todate"] : $todate="";

$page_scale = 10;   // 한 페이지당 표시될 페이지 수  10페이지
$first_num = ($page-1) * $scale;  // 리스트에 표시되는 게시글의 첫 순번.


if ($fromdate == "") {
    $fromdate = date("Y-m-d", strtotime("-15 day"));
}

if ($todate == "") {
  $todate = date("Y-m-d", time());
  $Transtodate = $todate;
} else {
  $Transtodate = strtotime($todate);
  $Transtodate = date("Y-m-d", $Transtodate);
}

$SettingDate = " and registedate ";

$common = $SettingDate . " between date('$fromdate') and date('$Transtodate')  order by registedate desc, num desc ";
$a = $common . " limit $first_num, $scale";    //내림차순
$b = $common ;    //내림차순 전체

$sqlall = "select * from jtechel.game where ((namearr1 like '%$guest_name%') or (namearr2 like '%$guest_name%') or (namearr3 like '%$guest_name%')  or (namearr4 like '%$guest_name%')  or (namearr5 like '%$guest_name%') )  and branch = '$savedbranch' ";
$sql = $sqlall. $a;     
$sqlcon =  $sqlall . $b;

$nowday = date("Y-m-d");   // 현재일자 변수지정

// print '$sql  <br>';
// print $sql;


?>

<div class="container-fluid justify-content-center align-items-center mt-5 mb-2">
    <div class="d-flex mb-3 mt-5 justify-content-center align-items-center">
       <h1> <span class="text-secondary mt-5"></span> </h1>
    </div>
	
		     
	<div class="d-flex mb-3 mt-5 justify-content-center align-items-center">
       <h1> <span class="text-primary mt-5">  회원 통계 조회 </span> </h1>
    </div>
		     
	<div class="d-flex mb-3 mt-5 justify-content-center align-items-center">
       <h2> <span class="text-secondary mt-5"> (<?=$branch?>  지점) &nbsp; 성명: <?=$guest_name?> &nbsp; <?=$guest_tel?>  </span> </h2>
    </div>
        
		<div class="d-flex mt-2 justify-content-center align-items-center">  

	  </div>
	  </div>
	  
 <form name="board_form" id="board_form"  method="post" action="./guest_statistics.php?mode=search&search=<?=$search?>">  
  
			<input type="hidden" id="done_check_val" name="done_check_val" value="<?=$done_check_val?>" >
			<input type="hidden" id="page" name="page" value="<?=$page?>" size="5" > 	
			<input type="hidden" id="scale" name="scale" value="<?=$scale?>" size="5" > 	
			<input type="hidden" id="yearcheckbox" name="yearcheckbox" value="<?=$yearcheckbox?>" size="5" > 	
			<input type="hidden" id="cursort" name="cursort" value="<?=$cursort?>" size="5" > 	
			<input type="hidden" id="sortof" name="sortof" value="<?=$sortof?>" size="5" > 	
			<input type="hidden" id="stable" name="stable" value="<?=$stable?>" size="5" > 	
			<input type="hidden" id="sqltext" name="sqltext" value="<?=$sqltext?>" > 
			<input type="hidden" id="num" name="num" value="<?=$num?>" > 
			
			<input type="hidden" id="updatetime" name="updatetime" value="<?=$updatetime?>" >	

		   
			<div class="d-flex mb-1 mt-2 justify-content-center align-items-center">  		
				
			 &nbsp; &nbsp;
				<button type="button" id="preyear" class="btn btn-secondary btn-lg fs-2"   onclick='pre_year()' > 전년도 </button>  &nbsp;  	
				<button type="button" id="three_month" class="btn btn-secondary btn-lg  fs-2"  onclick='three_month_ago()' > M-3월 </button> &nbsp;  	
				<button type="button" id="prepremonth" class="btn btn-secondary btn-lg fs-2 "  onclick='prepre_month()' > 전전월 </button>	&nbsp;  
				<button type="button" id="premonth" class="btn btn-secondary btn-lg fs-2 "  onclick='pre_month()' > 전월 </button>  &nbsp; 					
				<button type="button" id="thismonth" class="btn btn-dark btn-lg fs-2 "  onclick='this_month()' > 당월 </button>	&nbsp;  	   
				<button type="button" id="thisyear" class="btn btn-dark btn-lg fs-2 "  onclick='this_year()' > 당해년도 </button> &nbsp;  			
			</div>
			<div class="d-flex mb-1 mt-2 justify-content-center align-items-center">  					
			   <span class='input-group-text align-items-center'>  
			   <input type="date" id="fromdate" name="fromdate"  value="<?=$fromdate?>" placeholder="기간 시작일">  &nbsp; 부터 &nbsp;  
			   <input type="date" id="todate" name="todate"   value="<?=$todate?>" placeholder="기간 끝">  &nbsp;  까지    </span>  &nbsp;

         
				<button type="button" id="searchBtn" class="btn btn-dark btn-lg"  > 검색 </button>	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;	
		
			
			   </div>      

        <div class="d-flex justify-content-center align-items-center">            
                <table class="table table-hover">
                    <thead class="table-secondary">
                        <tr>
                            <th class="text-center">투입합</th>
                            <th class="text-center">배출합</th>
                            <th class="text-center">수익</th>
                        </tr>
                    </thead>
                    <tbody>
					        <td class="text-center text-primary"> <span id="inputsum_text"  class="text-primary" ></span> </td>
					        <td class="text-center text-secondary"> <span id ="outputsum_text" > </span> </td>
					        <td class="text-center"> <span id ="totalsum_text" > </span> </td>
					</tbody>
                </table>
            </div>		
					
					
        <div class="d-flex justify-content-center align-items-center">            
                <table class="table table-reponsive table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center">번호</th>
                            <th class="text-center">지점</th>
                            <th class="text-center">기계</th>
                            <th class="text-center">별명</th>
                            <th class="text-center">사용일</th>
                            <th class="text-center">투입합</th>
                            <th class="text-center">배출합</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $allstmh = $pdo->query($sqlcon); // 검색 조건에 맞는 쿼리 전체 개수
                            $temp2 = $allstmh->rowCount();
                            $stmh = $pdo->query($sql); // 검색조건에 맞는글 stmh
                            $temp1 = $stmh->rowCount();

                            $total_row = $temp2; // 전체 글수

                            $total_page = ceil($total_row / $scale); // 검색 전체 페이지 블록 수
                            $current_page = ceil($page / $page_scale); //현재 페이지 블록 위치계산

                            if ($page <= 1)
                                $start_num = 1 ;
                            else
                                $start_num = 1 ;

                            while ($row = $stmh->fetch(PDO::FETCH_ASSOC)) {
								   include 'rowDB.php';
								    
									// var_dump($namearr1);
/// 배열 생성 및 초기화
$dataArray = array();

// ','를 기준으로 문자열 분할 후 배열 생성
$namearrArray1 = explode(',', $namearr1);
$namearrArray2 = explode(',', $namearr2);
$namearrArray3 = explode(',', $namearr3);
$namearrArray4 = explode(',', $namearr4);
$namearrArray5 = explode(',', $namearr5);

// 총투입
$input_arrArray = explode(',', $input_arr);

// 배출배열
$output_arrArray1 = explode(',', $output_arr1);
$output_arrArray2 = explode(',', $output_arr2);
$output_arrArray3 = explode(',', $output_arr3);
$output_arrArray4 = explode(',', $output_arr4);
$output_arrArray5 = explode(',', $output_arr5);

$NameTotal_arrArrays = array(
    explode(',', $namearr1),
    explode(',', $namearr2),
    explode(',', $namearr3),
    explode(',', $namearr4),
    explode(',', $namearr5)
);


// 배열의 각 원소에 $guest_name 값을 가지는 $mc_number 변수 생성
$sum_row_output = 0;
$sum_row_input = 0;

for ($index = 0; $index < 150; $index++) {
    $row_output = 0;
    $row_input = 0;
	
	$isBeing = false; // 자료 존재여부 체크

    // 문구가 있다면 이름이 존재한다면?
    if (strpos($namearrArray1[$index], $guest_name) !== false)   {	
		$row_input = intval($input_arrArray[$index]);
        $row_output += intval($output_arrArray1[$index]);                    		
		$isBeing = true;
	}
    if (strpos($namearrArray2[$index], $guest_name) !== false)   {	
		$row_input = intval($input_arrArray[$index]);
        $row_output += intval($output_arrArray2[$index]);                    		
		$isBeing = true;
	}
    if (strpos($namearrArray3[$index], $guest_name) !== false)   {	
		$row_input = intval($input_arrArray[$index]);
        $row_output += intval($output_arrArray3[$index]);                    		
		$isBeing = true;
	}
    if (strpos($namearrArray4[$index], $guest_name) !== false)   {	
		$row_input = intval($input_arrArray[$index]);
        $row_output += intval($output_arrArray4[$index]);                    		
		$isBeing = true;
	}
    if (strpos($namearrArray5[$index], $guest_name) !== false)   {	
		$row_input = intval($input_arrArray[$index]);
        $row_output += intval($output_arrArray5[$index]);                    		
		$isBeing = true;
	}
		
	
	if($isBeing) {

        ?>
        <tr >
            <td class="text-center"><?= $start_num ?></td>
            <td class="<?= $date_font ?> text-center"><?= $row["branch"] ?></td>
            <td class="<?= $date_font ?> text-center"><?= ($index + 1) ?> </td>
            <td class="<?= $date_font ?> text-center"><?= $alias_arr[$index + 1]?> </td>
            <td class="text-center"><?= iconv_substr($row["registedate"], 0, 10, "utf-8") ?></td>
            <td class="text-center"><?= number_format($row_input) ?></td>
            <td class="text-center"><?= number_format($row_output) ?></td>
        </tr>
        <?php
        $start_num++;
    }

    $sum_row_input += $row_input;
    $sum_row_output += $row_output;
}


									
								
                            }
                        } catch (PDOException $Exception) {
                            print "오류: " . $Exception->getMessage();
                        }
                        $start_page = ($current_page - 1) * $page_scale + 1;
                        $end_page = $start_page + $page_scale - 1;
                        ?>
                    </tbody>
                </table>
            </div>

        <div class="row row-cols-auto mt-5 mb-5 justify-content-center align-items-center">
            <?php
            if ($page != 1 && $page > $page_scale) {
                $prev_page = $page - $page_scale;
                // 이전 페이지값은 해당 페이지 수에서 리스트에 표시될 페이지수 만큼 감소
                if ($prev_page <= 0) 
                    $prev_page = 1;  // 만약 감소한 값이 0보다 작거나 같으면 1로 고정
                echo '<button class="btn btn-outline-secondary btn-lg" type="button" id="previousListBtn" onclick="javascript:movetoPage('.$prev_page.')">◀</button> &nbsp;';
            }

            for ($i = $start_page; $i <= $end_page && $i <= $total_page; $i++) {        // [1][2][3] 페이지 번호 목록 출력
                if ($page == $i) { // 현재 위치한 페이지는 링크 출력을 하지 않도록 설정.
                    echo '<span class="text-secondary fs-3">' . $i . '</span>';
                } else {
                    echo '<button class="btn btn-outline-secondary btn-lg" type="button" id="moveListBtn" onclick="javascript:movetoPage('.$i.')">'.$i.'</button> &nbsp;';
                }
            }

            if ($page < $total_page) {
                $next_page = $page + $page_scale;
                if ($next_page > $total_page) 
                    $next_page = $total_page;
                echo '<button class="btn btn-outline-secondary btn-lg" type="button" id="nextListBtn" onclick="javascript:movetoPage('.$next_page.')">▶</button> &nbsp;';
            }
            ?>
        </div>
    </form>
</div>

   
<br>
<br>
<div class="container-fluid">
<? include './footer.php'; ?>
</div>
  
   
<script>

$(document).ready(function() { 

// 합을 계산해서 화면에 보여주기
  function numberWithCommas(x) {
        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    // 값을 요소에 삽입
    document.getElementById("inputsum_text").textContent = numberWithCommas('<?php echo $sum_row_input; ?>');
    document.getElementById("outputsum_text").textContent = numberWithCommas('<?php echo $sum_row_output; ?>');
    document.getElementById("totalsum_text").textContent = numberWithCommas('<?php echo ($sum_row_output - $sum_row_input); ?>');
	
	
$("#searchBtn").click(function(){ 	
	  // page 1로 초기화 해야함
     $("#page").val('1');
	 document.getElementById('board_form').submit();    
 
 });	
 
}); // end of ready document


function movetoPage(page){ 	  
	  $("#page").val(page); 
	 $("#board_form").submit();  
	}		
	
	

</script>




</body>

</html>