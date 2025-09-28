<?php 
// 환경파일 읽어오기 (테이블명 작업 폴더 등)
session_start(); 
ini_set('display_errors','1');  // 화면에 warning 없애기	

// 지점 선택 헬퍼 사용
require_once($_SERVER['DOCUMENT_ROOT'] . "/lib/mydb.php");
$pdo = db_connect();
require_once('branch_select_helper.php');
$branch = getBranchFromCookie($pdo);

$level= $_SESSION["level"];
$user_name= $_SESSION["name"];
$id= $_SESSION["userid"];
$sessionbranch = $_SESSION["branch"];

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

// 직원들은 해당 지점만 선택가능하게 만든다.
if($level > 3)
	$branch =  $sessionbranch ;

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


<title>YH 시스템 기간통계</title>
</head>

<style>
/* Modern Design System */
:root {
	--primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	--secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
	--success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
	--glass-bg: rgba(255, 255, 255, 0.15);
	--glass-border: rgba(255, 255, 255, 0.2);
	--shadow-soft: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
	--shadow-hover: 0 12px 40px 0 rgba(31, 38, 135, 0.5);
	--border-radius: 16px;
	--text-primary: #2d3748;
	--text-secondary: #4a5568;
	--bg-overlay: rgba(255, 255, 255, 0.9);
}

/* Glassmorphism Background */
body {
	background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
	background-size: 400% 400%;
	animation: gradientShift 15s ease infinite;
	min-height: 100vh;
}

@keyframes gradientShift {
	0% { background-position: 0% 50%; }
	50% { background-position: 100% 50%; }
	100% { background-position: 0% 50%; }
}

/* Modern Glass Card */
.card {
	background: var(--glass-bg);
	border: 1px solid var(--glass-border);
	box-shadow: var(--shadow-soft);
	backdrop-filter: blur(10px);
	-webkit-backdrop-filter: blur(10px);
	border-radius: var(--border-radius);
	transition: all 0.3s ease;
}

.card:hover {
	box-shadow: var(--shadow-hover);
	transform: translateY(-2px);
}

/* Modern Select Dropdown */
.modern-select {
	background: var(--glass-bg);
	border: 2px solid var(--glass-border);
	border-radius: 12px;
	padding: 12px 20px;
	color: var(--text-primary);
	font-weight: 600;
	backdrop-filter: blur(10px);
	-webkit-backdrop-filter: blur(10px);
	transition: all 0.3s ease;
	box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.modern-select:focus {
	border-color: #667eea;
	box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.3);
	outline: none;
}

.modern-select[data-theme="primary"]:focus {
	border-image: var(--primary-gradient) 1;
}

/* Enhanced Buttons */
.btn-modern {
	background: var(--primary-gradient);
	border: none;
	border-radius: 25px;
	padding: 12px 30px;
	color: white;
	font-weight: 600;
	text-transform: uppercase;
	letter-spacing: 0.5px;
	transition: all 0.3s ease;
	box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.btn-modern:hover {
	transform: translateY(-2px);
	box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
	color: white;
}

.btn-secondary.rounded-pill {
	background: var(--glass-bg);
	border: 2px solid var(--glass-border);
	color: var(--text-primary);
	backdrop-filter: blur(10px);
	-webkit-backdrop-filter: blur(10px);
	transition: all 0.3s ease;
}

.btn-secondary.rounded-pill:hover {
	background: rgba(255, 255, 255, 0.3);
	transform: translateY(-1px);
	box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

/* Modern Tables */
.table {
	background: var(--bg-overlay);
	border-radius: var(--border-radius);
	overflow: hidden;
	box-shadow: var(--shadow-soft);
	backdrop-filter: blur(10px);
	-webkit-backdrop-filter: blur(10px);
	border: none;
}

.table thead {
	background: var(--primary-gradient) !important;
	color: white;
}

.table thead th {
	border: none;
	padding: 20px;
	font-weight: 600;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

.table tbody tr {
	transition: all 0.3s ease;
	border: none;
}

.table tbody tr:hover {
	background-color: rgba(102, 126, 234, 0.1);
	transform: scale(1.01);
}

.table tbody td {
	border: none;
	padding: 18px;
	vertical-align: middle;
}

/* Input Group Styling */
.input-group {
	background: var(--glass-bg);
	border-radius: 12px;
	padding: 8px 16px;
	margin: 8px;
	backdrop-filter: blur(10px);
	-webkit-backdrop-filter: blur(10px);
	box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.text-secondary {
	color: var(--text-secondary) !important;
	font-weight: 600;
}

/* Typography Enhancements */
h1, h2, h3 {
	color: white;
	text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
	font-weight: 700;
}

h1 {
	font-size: 3.5rem;
	margin-bottom: 1rem;
}

h2 {
	font-size: 2.5rem;
	color: var(--text-primary);
	text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
}

/* Responsive Enhancements */
@media (max-width: 768px) {
	.card {
		margin: 10px;
		border-radius: 12px;
	}
	
	h1 {
		font-size: 2.5rem;
	}
	
	.modern-select {
		width: 100% !important;
		margin-bottom: 15px;
	}
}

/* Animation Utilities */
.fade-in {
	animation: fadeIn 0.8s ease-in;
}

@keyframes fadeIn {
	from { opacity: 0; transform: translateY(20px); }
	to { opacity: 1; transform: translateY(0); }
}

.slide-up {
	animation: slideUp 0.6s ease-out;
}

@keyframes slideUp {
	from { transform: translateY(50px); opacity: 0; }
	to { transform: translateY(0); opacity: 1; }
}

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

.form-check-label, .form-check-input {
	font-size: 35px; /* or any other size that you want */		
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
        font-size: 30px; /* or any other size that you want */
    }
	
	.table td input{
        font-size: 30px; /* or any other size that you want */
    }	

    .input-group {
        font-size: 35px; /* or any other size that you want */		
    }  
	
	input {
		font-size: 30px; /* or any other size that you want */		
		height: 45px;
	} 
	
	h1 {
		font-size: 50px; /* or any other size that you want */				
	}  
		
.sideBanner {
  position: absolute;
  width: calc(100vw - 90vw);
  height:calc(100vh - 70vh);
  top: calc(100vh - 50vh);
  left: calc(100vw - 20vw);  
  
}	
	
	
}

/* 추가된 CSS 스타일 */
.table-responsive {
	overflow-x: auto;
}

/* 반응형 테이블 스타일 */
.table-responsive table {
	width: auto;
	min-width: 100%;
}

</style>	

<body>
    <?php
    $fromdate = isset($_GET["fromdate"]) ? $_GET["fromdate"] : '';
    $todate = isset($_GET["todate"]) ? $_GET["todate"] : '';
    $amountType = isset($_GET["amountType"]) ? $_GET["amountType"] : 'input';

    if ($fromdate && $todate) {
		
		require_once("../lib/mydb.php");
        $pdo = db_connect();
		

		$alias_arr =array();	

		// 별명 불러오기
		 try{
			  $sql = "select * from jtechel.game_alias where branch = '$branch'  ";
			  $stmh = $pdo->query($sql);       
			  $stmh->execute();
			  $count = $stmh->rowCount();            
			  $row = $stmh->fetch(PDO::FETCH_ASSOC);  // $row 배열로 DB 정보를 불러온다.		
				 
			  $alias_arr = explode(",", $row['alias']);
			  
			 }catch (PDOException $Exception) {
			   print "오류: ".$Exception->getMessage();
			 }
		
				
			
			
	$SettingDate = "registedate";

	$common = "WHERE $SettingDate BETWEEN DATE('$fromdate') AND DATE('$todate')";

	// $branch 값이 'false'가 아니고 '전체'가 아닌 경우 branch 컬럼과 일치하는 조건 추가
	if ($branch !== 'false' && $branch !== '전체') {
	  $common .= " AND branch = '$branch' ";
	}

	$b = $common . " ORDER BY $SettingDate, num ASC"; // 오름차순 전체

	$sql = "SELECT * FROM jtechel.game $b";

    
        // 데이터 가져오기
        $inputArr = array();
        $outputArr = array();
    
        try {
            $stmh = $pdo->query($sql);
            while ($row = $stmh->fetch(PDO::FETCH_ASSOC)) {
                $inputArr[] = explode(",", $row['input_arr']);
                $outputArr1[] = explode(",", $row['output_arr1']);
                $outputArr2[] = explode(",", $row['output_arr2']);
                $outputArr3[] = explode(",", $row['output_arr3']);
                $outputArr4[] = explode(",", $row['output_arr4']);
                $outputArr5[] = explode(",", $row['output_arr5']);
            }
        } catch (PDOException $Exception) {
            print "오류: " . $Exception->getMessage();
        }
    }
    ?>

    <div class="container-fluid justify-content-center align-items-center mt-5 mb-2">
        <div class="d-flex mb-1 mt-5 justify-content-center align-items-center fade-in">
		 <div class="col-sm-11 text-center">
            <div class="card align-middle justify-content-center w-70 slide-up">
                <div class="card-body">
		             <div class="d-flex mb-1 mt-5 justify-content-center align-items-center">
						<H1>🎰 기계 총합계표</H1>
						<button type="button" class="btn btn-secondary rounded-pill closeBtn fs-3 ms-4">
							<i class="bi bi-x-circle"></i> 닫기
						</button>
					  </div>
	        
		<div class="d-flex mt-4 justify-content-center align-items-center">

			<div class="input-group p-3 justify-content-center align-items-center">
				   <span class="text-secondary me-3">
					   <i class="bi bi-geo-alt-fill"></i> 지점선택
				   </span>
				   <select name="branch" id="branch" class="modern-select fs-1 p-2" data-theme="primary">
					   <?php
					   $activeBranches = getActiveBranches($pdo);
					   
					   foreach ($activeBranches as $branchData) {
						   $selected = ($branch === $branchData['branch_name']) ? 'selected' : '';
						   echo "<option value='{$branchData['branch_name']}' {$selected}>{$branchData['branch_name']}</option>";
					   }
					   ?>
						</select> 	
			  </div>
	  </div>

        <div class="d-flex mb-1 mt-4 justify-content-center align-items-center">
            <div class="input-group p-3 mb-1 justify-content-center align-items-center">
                <span class="text-secondary">
					<i class="bi bi-calendar-range"></i> 기간 : 
					<strong class="text-primary"><?= $fromdate ?> ~ <?= $todate ?></strong>
				</span>
                
            </div>
        </div>
		
		<style>
		  .vertical-align-th {
			vertical-align: middle;
		  }
		</style>
<div class="table-responsive slide-up">
	<table class="table table-bordered modern-table">
		<thead class="table-dark">
			<tr>                        
				<th class='text-center bg-secondary text-white'>총 투입</th>
				<th class='text-center bg-secondary text-white'>총 배출</th>		
				<th class='text-center bg-secondary text-white'>계산식</th>
				<th class='text-center bg-secondary text-white'>총 순익</th>
			</tr>                 
		</thead>
<tbody>
<?php
		require_once("../lib/mydb.php");
        $pdo = db_connect();

			// 데이터 가져오기
			$inputArr = array_fill(0, 150, 0); // 투입합을 저장하기 위한 배열
			$inputAllArr = array_fill(0, 150, 0); // 투입합을 저장하기 위한 배열
			$outputArr = array_fill(0, 150, array_fill(0, 5, 0)); // 배출합을 저장하기 위한 2차원 배열
			$disposeAllArr = array_fill(0, 150, 0); // 배출합 저장
			$calArr = array_fill(0, 150, ''); // 수식을 기억함

			$input_plus_arr = array_fill(0, 150, 0); // 누계수정치를 기억하기 위한 배열
			$input_minus_arr = array_fill(0, 150, 0); // 누계수정치를 기억하기 위한 배열
			$dispose_plus_arr = array_fill(0, 150, 0); // 누계수정치를 기억하기 위한 배열
			$dispose_minus_arr = array_fill(0, 150, 0); // 누계수정치를 기억하기 위한 배열

			$total1 = 0;
			$total2 = 0;
			$total3 = 0;
			$total4 = 0;
			$total5 = 0;
			$total6 = 0;

			try {
				$stmh = $pdo->query($sql);
				while ($row = $stmh->fetch(PDO::FETCH_ASSOC)) {
					$in = explode(",", $row['input_arr']);
					$out1 = explode(",", $row['output_arr1']);
					$out2 = explode(",", $row['output_arr2']);
					$out3 = explode(",", $row['output_arr3']);
					$out4 = explode(",", $row['output_arr4']);
					$out5 = explode(",", $row['output_arr5']);

					$input_plus_explode = explode(",", $row['input_plus']);
					$input_minus_explode = explode(",", $row['input_minus']);
					$dispose_plus_explode = explode(",", $row['dispose_plus']);
					$dispose_minus_explode = explode(",", $row['dispose_minus']);

					$item = $row['item'];

					for ($i = 0; $i < 150; $i++) {
						$sumval1 = 0;
						$sumval2 = 0;
						
						$input = intval($in[$i]);
						$output1 = intval($out1[$i]);
						$output2 = intval($out2[$i]);
						$output3 = intval($out3[$i]);
						$output4 = intval($out4[$i]);
						$output5 = intval($out5[$i]);

						$input_plus_val = intval($input_plus_explode[$i]);
						$input_minus_val = intval($input_minus_explode[$i]);

						$dispose_plus_val = intval($dispose_plus_explode[$i]);
						$dispose_minus_val = intval($dispose_minus_explode[$i]);

						if ($item !== '최초자산') {
							$inputArr[$i] += $input;
							$outputArr[$i][0] += $output1;
							$outputArr[$i][1] += $output2;
							$outputArr[$i][2] += $output3;
							$outputArr[$i][3] += $output4;
							$outputArr[$i][4] += $output5;

							$input_plus_arr[$i] += $input_plus_val;
							$input_minus_arr[$i] += $input_minus_val;
							$dispose_plus_arr[$i] += $dispose_plus_val;
							$dispose_minus_arr[$i] += $dispose_minus_val;

							$sumval1 = $input + $input_plus_val - $input_minus_val;
							$sumval2 = $output1 + $output2 + $output3 + $output4 + + $output5 + $dispose_plus_val - $dispose_minus_val;
							
							$total1 += $sumval1 ;
							$total2 += $sumval2 ;
							
							
							
							// 배출합 더하기
							$inputAllArr[$i] += $sumval1;
							$disposeAllArr[$i] += $sumval2;
						}

						// 현재 투입합을 전일기준 투입합으로 저장
						$previousInputArr[$i] += $input;
					}
				}

				// 결과 출력
				for ($i = 0; $i < 150; $i++) {
					// 순익 계산: 투입합 - (배출합 + 배출자르기합)
					$netProfit = ($inputArr[$i] + $input_plus_arr[$i] - $input_minus_arr[$i]) - (array_sum($outputArr[$i]) + $dispose_plus_arr[$i] - $dispose_minus_arr[$i]);

					// 전일기준 투입합 계산
					$previousInputSum = $previousInputArr[$i];
					if ($netProfit !== 0) {
						$calArr[$i] = ($inputArr[$i] + $input_plus_arr[$i] - $input_minus_arr[$i]) . " - " . (array_sum($outputArr[$i]) + $dispose_plus_arr[$i] - $dispose_minus_arr[$i]) . ")";
					} 
				}
			} catch (PDOException $Exception) {
				print "오류: " . $Exception->getMessage();
			}

			$total4 = $total1 - ($total2 + $total3);
			$totalcalstr = number_format($total1) . " - " . number_format($total2);

			echo "<tr>";        
			echo "<td class='text-center'>" . number_format($total1) . "</td>"; 
			echo "<td class='text-center'>" . number_format($total2) . "</td>";         
			echo "<td class='text-center'>" . $totalcalstr . "</td>"; 
			echo "<td class='text-center'>" . number_format($total4) . "</td>";
			echo "</tr>";

			?>
		</tbody>
	</table>
</div>

<div class="d-flex mb-4 mt-4 justify-content-center align-items-center">
	<H2>🔧 기계별 상세내역</H2>
</div>
	
<div class="table-responsive slide-up">
	<table class="table table-bordered modern-table">
		<thead>
			<tr>
				<th class="vertical-align-th text-center bg-secondary text-white">지점</th>
				<th class="vertical-align-th text-center bg-secondary text-white">기계</th>
				<th class="vertical-align-th text-center bg-secondary text-white">별명</th>
				<th class='text-center bg-secondary text-white'>투입합</th>
				<th class='text-center bg-secondary text-white'>배출합</th>
				<th class='text-center bg-secondary text-white'>계산식</th>
				<th class='text-center bg-secondary text-white'>순익</th>
			</tr>
		</thead>
<tbody>		
<?php
		
		// 데이터 가져오기		
				
		$calArr = array_fill(0, 150, ''); // 수식을 기억함
				
		// foreach ($inputAllArr as $value) {
			// if ($value !== 0 && $value !== '') {
				// var_dump($value);
			// }
		// }

		// foreach ($disposeAllArr as $subArray) {
			// foreach ($subArray as $value) {
				// if ($value !== 0 && $value !== '') {
					// var_dump($value);
				// }
			// }
		// }

		
			// 결과 출력
			for ($i = 0; $i < 150; $i++) {
				
				$inputTotal = intval($inputAllArr[$i]);
				$outputTotal = intval($disposeAllArr[$i]);

				if ($inputTotal !== 0 or $outputTotal !== 0 ) {
					$calArr[$i] = number_format($inputTotal)  . " - " . number_format($outputTotal) ;				
				}

                $netprofit = ($inputTotal   -  $outputTotal) ;


				echo "<tr>";
				echo "<td class='text-center text-secondary'>" . $branch . "</td>"; // 기계 번호 열    
				echo "<td class='text-center text-primary'>" . ($i + 1) . "</td>"; // 열    				
				echo '<td class="text-center text-success">' . $alias_arr[$i] . '</td>';


					echo "<td class='text-center'>" . ($inputTotal !== 0 ? number_format($inputTotal) : "") . "</td>"; // 투입합 열
					echo "<td class='text-center'>" . ($outputTotal !== 0 ? number_format($outputTotal) : "") . "</td>"; // 배출합 열        
					echo "<td class='text-center'>" . $calArr[$i] . "</td>"; // 계산식 열
					echo "<td class='text-center'>" . ($netprofit !== 0 ? number_format($netprofit) : "") . "</td>"; // 순익 열
					echo "</tr>";


			}

		?>
		</tbody>
	</table>
</div>

    
	
	
	
	
	</div>
    </div>
    </div>
    </div>
	
<div class="container">
<? include './footer.php'; ?>
</div>	
    
</body>

</html>

<script>
$(document).ready(function() { 

      $(".closeBtn").click(function(){    // 저장하고 창닫기															        
		window.close();			
	 });	
document.getElementById("branch").addEventListener("change", function() {
    // 이곳에 선택 변경 시 실행할 자바스크립트 코드를 작성합니다.
    // 예: 선택된 값에 따라 다른 동작을 수행하거나 AJAX 요청을 보내는 등의 작업을 수행할 수 있습니다.    
	document.cookie = "branch=" + this.value ;    
    // 추가적인 동작을 수행하도록 코드를 작성합니다.
	location.reload();
  });
	 
	 
	 

});

 

</script>
