<?php
 
// 환경별 기본 URL 설정
require_once '../config/environment.php';

session_start(); 
 
$level= $_SESSION["level"];
$user_name= $_SESSION["name"];
$id= $_SESSION["userid"];
	  
require_once("../lib/mydb.php");
$pdo = db_connect();	
   
 // 기간을 정하는 구간
 
$todate=date("Y-m-d");   // 현재일자 변수지정   

$common=" order by num desc ";  // 출고예정일이 현재일보다 클때 조건

$sql = "select * from jtechel.gamelog " . $common; 							

$nowday=date("Y-m-d");   // 현재일자 변수지정   
$counter=0;
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

<?php 
 
$num_arr=array();
$data_arr=array();

 try{      
   $stmh = $pdo->query($sql);            // 검색조건에 맞는글 stmh
   $rowNum = $stmh->rowCount();  


   while($row = $stmh->fetch(PDO::FETCH_ASSOC)) {	

			  $num_arr[$counter]=$row["num"];
			  $data_arr[$counter]=$row["data"];
   		
	   $counter++;	   
	 } 	 
   } catch (PDOException $Exception) {   
    print "오류: ".$Exception->getMessage();    
}  
		 
?>
		 
<body >


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
		
.sideBanner {
  position: absolute;
  width: calc(100vw - 90vw);
  height:calc(100vh - 70vh);
  top: calc(100vh - 50vh);
  left: calc(100vw - 20vw);  
  
}	
	
	
}

</style>	



<?php include 'myheader.php'; ?>


<div class="container-fluid justify-content-center align-items-center mt-5 mb-2"> 
	<div class="d-flex mb-5 mt-5 justify-content-center align-items-center">
	</div>	
</div> 	
<div class="container-fluid justify-content-center align-items-center mt-5 mb-2">  	
  <h2> 시스템 로그인 기록 <br> </H2>
	 <div id="grid" style="font-size:30px;" >
  
  </div>

   <div class="clear"></div>	
   
   </div> 	   
   
  </body>

  </html>	 
	 

<script>
$(document).ready(function(){
	
var arr1 = <?php echo json_encode($num_arr);?> ;
var arr2 = <?php echo json_encode($data_arr);?> ; 

var rowNum = "<? echo $counter; ?>" ; 	
let row_count = 200;
const COL_COUNT = 2;

	const data = [];
	const columns = [];
	
	for (let i = 0; i < row_count; i += 1) {
	  const row = { name: i };
	  for (let j = 0; j < COL_COUNT; j += 1) {
		row[`num`] = arr1[i] ;						 						
		row['details'] = arr2[i] ;			

	  }
		data.push(row);
	}	


	 class CustomTextEditor {
	  constructor(props) {
		const el = document.createElement('input');
		const { maxLength } = props.columnInfo.editor.options;

		el.type = 'text';
		el.maxLength = maxLength;
		el.value = String(props.value);

		this.el = el;
	  }

	  getElement() {
		return this.el;
	  }

	  getValue() {
		return this.el.value;
	  }

	  mounted() {
		this.el.select();
	  }
	}	
const grid = new tui.Grid({
  el: document.getElementById('grid'),
  data: data,
  bodyHeight: 800,
  columns: [
    {
      header: '번호',
      name: 'num',
      sortingType: 'desc',
      sortable: true,
      width: 100,
      editor: {
        type: CustomTextEditor,
        options: {
          maxLength: 20
        }
      },
      align: 'center'
    },
    {
      header: '로그인 시간 및 이력',
      name: 'details',
      width: 500,
      editor: {
        type: CustomTextEditor,
        options: {
          maxLength: 100
        }
      },
      align: 'center'
    }
  ],
  columnOptions: {
    resizable: true
  },
  rowHeaders: ['rowNum', 'checkbox'],
  pageOptions: {
    useClient: false,
    perPage: 20
  },
  style: {
    fontSize: '50px'  // 폰트 크기 설정
  }
});


});

   </script> 
 

