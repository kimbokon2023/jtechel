<?php 
// 환경파일 읽어오기 (테이블명 작업 폴더 등)
include 'ini.php';    
session_start(); 

$level= $_SESSION["level"];
$user_name= $_SESSION["name"];
$id= $_SESSION["userid"];

ini_set('display_errors','1');  // 화면에 warning 없애기	

// 기존 쿠키 기본값 설정은 제거하고 daily.php 방식으로 아래에서 재설정
//$branch = isset($_COOKIE['branch']) ? $_COOKIE['branch'] : '전체';

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
     
// 자정을 넘긴시간에 하나 데이터를 만들어준다.
require_once("../lib/mydb.php");
$pdo = db_connect();
require_once('branch_select_helper.php');
$branch = isset($_COOKIE['branch']) ? $_COOKIE['branch'] : (intval($_SESSION["level"]) <= 3 ? '전체' : getFirstActiveBranch($pdo));


?>

<!doctype html>

<html lang="ko">
<head>
<!-- Required meta tags -->
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

<title>YH 회원관리</title>

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

@media (max-width: 576px) {
	.container-fluid, .container, .container-fluid .row {
		padding-left: 0.5rem !important;
		padding-right: 0.5rem !important;
	}
	h1 span.text-secondary {
		font-size: 1.4rem !important;
	}
	.btn.btn-outline-primary {
		font-size: 0.95rem !important;
		padding: 0.35rem 0.6rem !important;
	}
	.card .card-body {
		padding: 0.85rem !important;
	}
	.form-select.form-select-lg {
		font-size: 1rem !important;
		height: 48px !important;
	}
	#scaleval, input#search {
		font-size: 1rem !important;
		height: 42px !important;
	}
	.table {
		font-size: 0.95rem !important;
	}
	.table thead th, .table td {
		padding-top: 0.5rem !important;
		padding-bottom: 0.5rem !important;
	}
	.btn.btn-lg {
		font-size: 1rem !important;
		padding: 0.5rem 0.8rem !important;
	}
}
</style>	


<body>

<?php include 'myheader.php'; ?>
<?php
 
// 오늘 날짜 가져오기
$today = date('Y-m-d');

isset($_REQUEST["search"]) ? $search = $_REQUEST["search"] : $search="";
isset($_REQUEST["scale"]) ? $scale = $_REQUEST["scale"] : $scale=30;
isset($_REQUEST["page"]) ? $page = $_REQUEST["page"] : $page=1;
isset($_REQUEST["mode"]) ? $mode = $_REQUEST["mode"] : $mode="";

 
$page_scale = 10;   // 한 페이지당 표시될 페이지 수  10페이지
$first_num = ($page-1) * $scale;  // 리스트에 표시되는 게시글의 첫 순번.

$common = " ORDER BY num DESC ";
$a = $common . " LIMIT $first_num, $scale";
$b = $common;

// 검색을 위해 모든 검색변수 공백제거
$search = str_replace(' ', '', $search);

$branchCondition = '';

if ($mode == "search") {
  if ($search == "") {
    $branchCondition = ($branch !== 'false' && $branch !== '전체') ? " and branch = '$branch'" : '';
    $sql = "select * from jtechel.game_guest where 1 $branchCondition";
    $sql .= " order by guest_registedate desc, num desc limit $first_num, $scale ";
    $sqlcon = "select * from jtechel.game_guest where 1 $branchCondition";
    $sqlcon .= " order by guest_registedate desc, num desc ";
  } elseif ($search != "") {
    $branchCondition = ($branch !== 'false' && $branch !== '전체') ? " and branch = '$branch'" : '';
    $sql = "select * from jtechel.game_guest where ((guest_name LIKE '%$search%') OR (guest_tel LIKE '%$search%')) $branchCondition";
    $sql .= " order by guest_registedate desc, num desc limit $first_num, $scale ";
    $sqlcon = "select * from jtechel.game_guest where ((guest_name LIKE '%$search%') OR (guest_tel LIKE '%$search%')) $branchCondition";
    $sqlcon .= " order by guest_registedate desc, num desc ";
  }
} elseif ($mode == "") {
  $branchCondition = ($branch !== 'false' && $branch !== '전체') ? " and branch = '$branch'" : '';
  $sql = "select * from jtechel.game_guest where 1 $branchCondition";
  $sql .= " order by guest_registedate desc, num desc limit $first_num, $scale ";
  $sqlcon = "select * from jtechel.game_guest where 1 $branchCondition";
  $sqlcon .= " order by guest_registedate desc, num desc ";
}

// print $sql;
// print $sql;
// print $sql;
// print $sql;
// print $sql;


$nowday = date("Y-m-d");   // 현재일자 변수지정
?>

<div class="container-fluid mt-3 mb-2">
	<div class="d-flex mb-3 justify-content-center align-items-center">
       <h1> <span class="text-secondary ">회원관리</span> </h1>
	<!-- 새로고침 버튼 추가 -->
	<button type="button" class="btn btn-outline-primary ms-3" onclick="location.reload();">
		<i class="bi bi-arrow-clockwise"></i> 새로고침
	</button>
	</div>	
		<div class="d-flex mb-4 mt-4 justify-content-center align-items-center px-2">
			<div class="card shadow-lg border-0 w-100" style="max-width: 680px; background: linear-gradient(90deg, var(--bs-light, #e3ffe6) 0%, var(--bs-body-bg, #e3f0ff) 100%);">
				<div class="card-body d-flex flex-column flex-md-row align-items-stretch gap-3">
					<div class="d-flex align-items-center justify-content-center justify-content-md-start mb-2 mb-md-0">
						<i class="bi bi-building fs-1 text-primary me-0 me-md-3"></i>
					</div>
					<div class="d-flex flex-column flex-grow-1">
						<label for="branch" class="form-label fw-bold fs-5 fs-md-4 mb-2 text-primary">지점 선택</label>
						<select name="branch" id="branch" class="form-select form-select-lg border-2 border-primary shadow-sm" style="max-width: 100%; font-size: 1.15rem;">
							<?php
								// branches 테이블에서 지점 목록 가져오기
								echo renderBranchSelect($pdo, $branch, true, intval($_SESSION["level"]));
							?>
						</select>
					</div>
				</div>
			</div>
		</div>
    <form name="board_form" id="board_form" method="post" action="guest.php?mode=search&search=<?=$search?>&scale=<?=$scale?>&page=<?=$page?>">
        <input type="hidden" id="done_check_val" name="done_check_val" value="<?=$done_check_val?>">
        <input type="hidden" id="voc_alert" name="voc_alert" value="<?=$voc_alert?>" size="5">
        <input type="hidden" id="ma_alert" name="ma_alert" value="<?=$ma_alert?>" size="5">
        <input type="hidden" id="order_alert" name="order_alert" value="<?=$order_alert?>" size="5">
        <input type="hidden" id="page" name="page" value="<?=$page?>" size="5">
        <input type="hidden" id="scale" name="scale" value="<?=$scale?>" size="5">
        <input type="hidden" id="yearcheckbox" name="yearcheckbox" value="<?=$yearcheckbox?>" size="5">
        <input type="hidden" id="year" name="year" value="<?=$year?>" size="5">
        <input type="hidden" id="check" name="check" value="<?=$check?>" size="5">
        <input type="hidden" id="output_check" name="output_check" value="<?=$output_check?>" size="5">
        <input type="hidden" id="plan_output_check" name="plan_output_check" value="<?=$plan_output_check?>" size="5">
        <input type="hidden" id="team_check" name="team_check" value="<?=$team_check?>" size="5">
        <input type="hidden" id="measure_check" name="measure_check" value="<?=$measure_check?>" size="5">
        <input type="hidden" id="cursort" name="cursort" value="<?=$cursort?>" size="5">
        <input type="hidden" id="sortof" name="sortof" value="<?=$sortof?>" size="5">
        <input type="hidden" id="stable" name="stable" value="<?=$stable?>" size="5">
        <input type="hidden" id="sqltext" name="sqltext" value="<?=$sqltext?>">

        <input type="hidden" id="updatetime" name="updatetime" value="<?=$updatetime?>">

        <div id="vacancy" style="display:none"></div>

        <div class="d-flex mb-5 mt-5 justify-content-center align-items-center">
            <div class="input-group p-2 mb-2  justify-content-center align-items-center">
                <span class="text-secondary">▷ 총&nbsp;<span id="total_row"></span>&nbsp;개 자료</span>&nbsp;&nbsp;&nbsp;&nbsp;
                <span class="text-secondary">화면 목록수&nbsp;</span>
                <select name="scaleval" id="scaleval">
                    <?php
                    $scalearr = array('10', '20', '30', '50', '100');
                    foreach ($scalearr as $val) {
                        if ($scale == $val) {
                            echo "<option selected value='$val'>$val</option>";
                        } else {
                            echo "<option value='$val'>$val</option>";
                        }
                    }
                    ?>
                </select>
            </div>
        </div>

        <div class="d-flex mb-5 mt-5 justify-content-center align-items-center">
            <input type="text" name="search" id="search" value="<?=$search?>" onkeydown="JavaScript:SearchEnter();" placeholder="검색어">
            &nbsp; &nbsp;
            <button type="button" id="searchBtn" class="btn btn-dark btn-lg">검색</button> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <button type="button" class="btn btn-success btn-lg" <?=$buttonState?> onclick="popupCenter('./guest_write_form.php', '회원등록', 1050, 900)">회원등록</button> &nbsp;
        </div>

        <div class="d-flex justify-content-center align-items-center">            
                <table class="table table-reponsive table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center">번호</th>
                            <th class="text-center">지점</th>
                            <th class="text-center">등록일</th>
                            <th class="text-center">이름</th>
                            <th class="text-center">전화번호</th>
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
                                $start_num = $total_row; // 페이지당 표시되는 첫번째 글순번
                            else
                                $start_num = $total_row - ($page - 1) * $scale;
							
							if($temp2 === 0) {
								  ?>
								<tr >								
								  <td class="text-center" colspan="5">
                                       검색결과가 없습니다.
									   </td>
                                </tr>
						<?php
							}
							
							
							else{
								

                            while ($row = $stmh->fetch(PDO::FETCH_ASSOC)) {
								  $num= $row["num"];
								   
                                ?>
								
                                <tr onclick="choice_popup('<?=$num?>')">								
                                    <td class="text-center"><?=$start_num?></td>
									<td class="<?=$date_font?> text-center"><?=$row["branch"]?></td>
                                    <td class="text-center"><?=iconv_substr($row["guest_registedate"], 0, 10, "utf-8")?></td>                                    
                                    <td class="<?=$date_font?> text-center"><?=iconv_substr($row["guest_name"], 0, 10, "utf-8")?></td>
                                    <td class="text-center"><?=$row["guest_tel"]?></td>
                                </tr>
                                <?php
                                $start_num--;
                              } 
							}  // end of if
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

function choice_popup(num) {
	 // 창에서 선택함 통게 또는 수정
	 // popupCenter('./guest_write_form.php?.php?num=' + num , '누계수정', 1050, 900);
	 popupCenter('./guest_choice_menu.php?num=' + num , '통계 및 수정 선택', 1050, 900);
	
}

function view(num, item) {
	var level =<?php echo $level; ?>;	
	
	if(item == '신규')
	    popupCenter('./write_form.php?num=' + num + '&mode=update', '자료수정', 1050, 900);
	
	if(level==1 && item == '최초자산')
	    popupCenter('./write_form.php?num=' + num + '&mode=update', '자료수정', 1050, 900);
	
	if(level==1 && item == '누계수정')
	    popupCenter('./mywallet.php?num=' + num + '&mode=update', '누계수정', 1050, 900);
	
}

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


document.getElementById("branch").addEventListener("change", function() {    
	document.cookie = "branch=" + this.value ;        
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
 
$("#statistics").click(function(){ 	
    var fromdate = $('#fromdate').val();
    var todate = $('#todate').val();
	
    popupCenter('./statistics.php?fromdate=' + fromdate + '&todate=' + todate, '기간통계', 1050, 900);   
 
 });	

$("#mywallet").click(function(){ 	
    var fromdate = $('#fromdate').val();
    var todate = $('#todate').val();
	
    popupCenter('./mywallet.php?fromdate=' + fromdate + '&todate=' + todate, '기계별 총계', 1050, 900);   
 
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
