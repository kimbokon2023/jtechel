
 <?php
	  
require_once("./lib/mydb.php");
$pdo = db_connect();	
   
 // 기간을 정하는 구간
 
$todate=date("Y-m-d");   // 현재일자 변수지정   

$common=" order by num desc ";  // 출고예정일이 현재일보다 클때 조건

$sql = "select * from jtechel.logdata " . $common; 							

$nowday=date("Y-m-d");   // 현재일자 변수지정   
$counter=0;
   ?>

 <!DOCTYPE HTML>
 <html>
 <head>
 <meta charset="UTF-8">
 <link rel="stylesheet" type="text/css" href="./css/common.css">
 <link rel="stylesheet" type="text/css" href="./css/steel.css">
 <link rel="stylesheet" type="text/css" href="./css/jexcel.css"> 
 
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<link rel="stylesheet" href="https://uicdn.toast.com/tui.pagination/latest/tui-pagination.css" />
<script src="https://uicdn.toast.com/tui.pagination/latest/tui-pagination.js"></script>
<link rel="stylesheet" href="https://uicdn.toast.com/tui-grid/latest/tui-grid.css"/>
<script src="https://uicdn.toast.com/tui-grid/latest/tui-grid.js"></script>

<script src="https://code.highcharts.com/highcharts.js"></script>
 <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">   <!--날짜 선택 창 UI 필요 -->
 <title> 시스템 로그인 기록 </title> 
 </head>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script> 

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

 <div id="wrap">
  <h2> 시스템 로그인 기록 <br> </H2>
	 <div id="grid" >
  
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
		  width:100,
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
		  width:600,						  
		  editor: {
			type: CustomTextEditor,
			options: {
			  maxLength: 100
			} 			
		  }	,  
			align: 'center'
		  // sortingType: 'desc',
		  // sortable: true,          
		  // editingEvent :  'Click'		  
		}
						
	  ],
	columnOptions: {
			resizable: true
		  },
	  rowHeaders: ['rowNum','checkbox'],
	  pageOptions: {
		useClient: false,
		perPage: 20
	  },
	});	

});

   </script> 
 

