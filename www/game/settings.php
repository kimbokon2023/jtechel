


<!DOCTYPE html>
<meta charset="UTF-8">
<html>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<link rel="stylesheet" href="https://uicdn.toast.com/tui.pagination/latest/tui-pagination.css" />
<script src="https://uicdn.toast.com/tui.pagination/latest/tui-pagination.js"></script>
<link rel="stylesheet" href="https://uicdn.toast.com/tui-grid/latest/tui-grid.css"/>
<script src="https://uicdn.toast.com/tui-grid/latest/tui-grid.js"></script>
<!-- CSS only -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="./css/style.css"/>
<!-- JavaScript Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
<body>
<style>
   @import url("https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.1/font/bootstrap-icons.css");
</style>

	<header class ="d-flex fex-column align-items-center flex-md-row p-1 bg-success" >
    <h1 class="h4 my-0 me-md-auto"> 
		<img src="./img/eunsung_logo.jpg" alt="은성레이져">
	은성레이져 견적산출(환경설정) </h1>
	<div class="d-flex align-items-center">	  
	  <div class="flex-grow-1 ms-3">
		<a href="http://8440.co.kr/es/uploadfiles/211105013_01.xml" download="211105013_01.xml"> 은성레이져 견적 산출 프로그램 </a>	  
	  </div>

	  
</div>
	</header>
	<section class ="d-flex fex-column align-items-left flex-md-row p-1">
	 <div class="p-2 pt-md-3 pb-md-3 text-left" style="width:100%;">	  
		     <form method="post" enctype="multipart/form-data" action="upload.php"  >			
		    <div class="card-header" style="width:100%;">    											
						<button  type="button" class="btn btn-primary" id="HomeBtn">메인화면 복귀</button>	   
						<button  type="button" class="btn btn-dark" id="SavesettingsBtn">설정저장</button>	   

					<!--	<button  type="button" id="changeData"> 자료강제 변경</button>
						<button  type="button" id="readData"> 자료 읽기</button>  -->
						<br>
			            <div class="input-group p-3 mb-1">
						  <span class="input-group-text">   <i class="bi bi-bookmark"></i> </span>
						  <span class="input-group-text">견적서 작성에 영향을 주는 환경변수들 설정창</span>	
						  	<span class="input-group-text"><i class="bi bi-megaphone-fill"></i> </span>
					    <span class="input-group-text"> 메시지 표시 </span>							
						<input type="text" id="inputval" name="inputval" size="50" > 	
					    </div>						 
						<?php
						if(isset($_REQUEST["myfiles"]))
							$myfiles=$_REQUEST["myfiles"];
						  else 
							$myfiles="";   // 초기화
						if(isset($_REQUEST["file_name_copy"]))
							$file_name=$_REQUEST["file_name_copy"];
						  else 
							$file_name="";   // xml파일 이름
												
				?>  
				</div>
						
				<div id="grid">  </div>		
					
	            <div id="tui-pagination-container" class="tui-pagination"></div>
	     	</form>
			<form id="HomeFrm" action="./index.php" method="POST">
               <input type="hidden" name="file_name" value="<?=$file_name?>">
               <input type="hidden" name="myfiles" value="<?=$myfiles?>">
            </form>
									
		  </div>		
<script>
function inputNumberFormat(obj) { 
obj.value = comma(uncomma(obj.value)); 
} 
function comma(str) { 
    str = String(str); 
    return str.replace(/(\d)(?=(?:\d{3})+(?!\d))/g, '$1,'); 
} 
function uncomma(str) { 
    str = String(str); 
    return str.replace(/[^\d]+/g, ''); 
}

$(document).ready(function(){
					 $("#upload_file").change(function(){
					  $("form").submit();
					 // tmp="upload.php";			 
					 // $("#display").load(tmp);   	  	  
					  });	
					 $("#HomeBtn").click(function(){
					  $("#HomeFrm").submit();
					  });						  
					  
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
					  
					  
					var count = "<? echo $count; ?>"; 
					var jobnum = <?php echo json_encode($jobnum);?> ;
					var material = <?php echo json_encode($material);?> ;
					var length = <?php echo json_encode($length);?> ;
					var width = <?php echo json_encode($width);?> ;
					var weights = <?php echo json_encode($weights);?> ;
					var donenum = <?php echo json_encode($donenum);?> ;
					var donetime = <?php echo json_encode($donetime);?> ;
					var unitfee = <?php echo json_encode($unitfee_arr);?> ;
					var bending = <?php echo json_encode($bending);?> ;
					var painting = <?php echo json_encode($painting);?> ;
					var afterwork = <?php echo json_encode($afterwork);?> ;
					var estimate = <?php echo json_encode($estimate);?> ;
					var estimateAmount = <?php echo json_encode($estimateAmount);?> ;
				 
					console.log(count);
					console.log(jobnum[0]);
				 
					const ROW_COUNT = count;
					const COL_COUNT = 12;
					
					const data = [];
					const columns = [];
					
				  if(count>0) {
					for (let i = 0; i < ROW_COUNT; i += 1) {
					  const row = { name: i };
					  for (let j = 0; j < COL_COUNT; j += 1) {
						row['jobnum'] = jobnum[i] ;
						row['material'] = material[i] ;
						row['length'] = length[i] +20 ;  // 20키움
						row[`width`] = width[i] +20 ;        // 20키움
						row[`weights`] = weights[i] ;
						row[`donenum`] = donenum[i] ;
						row[`donetime`] = donetime[i] ;
						row[`unitfee`] = unitfee[i] ;
						row[`bending`] = bending[i] ;
						row[`painting`] = painting[i] ;
						row[`afterwork`] = afterwork[i] ;
						row[`estimate`] = estimate[i] ;
						row[`estimateAmount`] = estimateAmount[i] ;

					  }
						data.push(row);
					}

					// for (let i = 0; i <= COL_COUNT; i += 1) {
					  // const name = `c${i}`;
					  // columns.push({ name, header: name });
					// }
				  
				const grid = new tui.Grid({
					  el: document.getElementById('grid'),
					  data: data,
					  bodyHeight: 500,
					   columns: [
						{
						  header: 'Job번호',
						  name: 'jobnum',
						  sortingType: 'desc',
						  sortable: true,
						  width:160,
						  editor: {
							type: CustomTextEditor,
							options: {
							  maxLength: 20
							}
						  },	 		
						  align: 'center'
						},
						{
						  header: '재질',
						  name: 'material',
						  editor: {
							type: CustomTextEditor,
							options: {
							  maxLength: 10
							} 			
						  }	,  
							align: 'center'
						  // sortingType: 'desc',
						  // sortable: true,          
						  // editingEvent :  'Click'		  
						},
						{
						  header: '길이(mm)',
						  name: 'length',
						  // sortingType: 'desc',
						  // sortable: true,
						  editor: {
							type: CustomTextEditor,
							options: {
							  maxLength: 10
							}
						  }	, 		  
						  align: 'right'
						},
						{
						  header: '폭(mm)',
						  name: 'width',
						  // sortingType: 'desc',
						  // sortable: true,
						  editor: {
							type: CustomTextEditor,
							options: {
							  maxLength: 10
							}
						  }	, 		  
						  align: 'right'
						},
						{
						  header: '중량(kg)',
						  name: 'weights',
						  // sortingType: 'desc',
						  // sortable: true,
						  // editor: {
							// type: CustomTextEditor,
							// options: {
							  // maxLength: 10
							// }
						  // }	, 		  
						  align: 'right'		  
						},
						{
						  header: '수량(EA)',
						  name: 'donenum',
						  // sortingType: 'desc',
						  // sortable: true,
						  // editor: {
							// type: CustomTextEditor,
							// options: {
							  // maxLength: 10
							// }
						  // }	, 		  
						  align: 'center'		  
						},
						{
						  header: '작업시간(분)',
						  name: 'donetime',
						  // sortingType: 'desc',
						  // sortable: true ,
						  // editor: {
							// type: CustomTextEditor,
							// options: {
							  // maxLength: 10
							// }
						  // }	, 		  
						  align: 'right'		  
						},
						{
						  header: '가공단가',
						  name: 'unitfee',
						  // editor: {
							// type: CustomTextEditor,
							// options: {
							  // maxLength: 10
							// }
						  // }	, 		  
						  // sortingType: 'desc',
						  // sortable: true,
						  align: 'right'		  
						},		
						{
						  header: '절곡',
						  name: 'bending',
						  editor: {
							type: CustomTextEditor,
							options: {
							  maxLength: 10
							}
						  }	, 		  
						  // sortingType: 'desc',
						  // sortable: true,
						  align: 'right'		  
						},
						
						{
						  header: '도장(mm2)',
						  name: 'painting',
						  editor: {
							type: CustomTextEditor,
							options: {
							  maxLength: 10
							}
						  }	, 		  
						  align: 'right'		  		  
						},
						{
						  header: '후가공',
						  name: 'afterwork',
						  editor: {
							type: CustomTextEditor,
							options: {
							  maxLength: 10
							}
						  }	, 		  
						//  sortingType: 'desc',
						//  sortable: true,
						  align: 'right'		  
						},	
						{
						  header: '레이져비용',
						  name: 'estimate',
						  sortingType: 'desc',
						  sortable: true,
						  align: 'right'		  
						},
						{
						  header: '견적합계',
						  name: 'estimateAmount',
						  sortingType: 'desc',
						  sortable: true,
						  align: 'right'		  
						}					
					  ],
					  rowHeaders: ['rowNum'],
					  pageOptions: {
						useClient: false,
						perPage: 20
					  }
					});
				  
					
					const appendBtn = document.getElementById('appendBtn');		
					
					const appendedData = {
					  material: '',
					  length: '',
					  width: '',
					  donenum: '',
					  donetime: '',
					  unitfee: '',
					  bending: '',
					  painting: '',
					  afterwork: '',
					  estimate: '',
					  estimateAmount: ''      
					};
					 // InsertRow 1 (After row)
					appendBtn.addEventListener('click', () => {
					  grid.appendRow(appendedData);
					});
					 // InsertRow 1 (Before row)
					prependBtn.addEventListener('click', () => {
						  grid.prependRow(appendedData);
					});  
				  
				 // 셀 자동계산 
					 calculate.addEventListener('click', () => {
					  calculateit();
					});
					
				 // 셀 값 설정 
					 changeData.addEventListener('click', () => {
					  ChangeData();
					});	
				 // 셀 값 Read 
					 readData.addEventListener('click', () => {
					  readDataCell();
					});		
				 


					// grid.on('focusChange', ev => {
					  // console.log('change focused cell!', ev);
					  // ChangeData();  // 자료가 변경되면 다시 계산하는 루틴작성을 위한 연습
					  // readDataCell();
					// });
					/*	
								click : When a mouse button is clicked on a table cell
								dblclick : When a mouse button is double clicked on a table cell
								mousedown : When a mouse button is pressed on a table cell
								mouseover : When a mouse pointer is moved onto a table cell
								mouseout : When a mouse pointer is moved off a table cell
								focusChange : When a table cell focus is selected
								columnResize : When the width of a column is resized
								check: When a row header checkbox is filled
								uncheck: When a row header checkbox is cleared
								checkAll: When a header checkbox is filled, all the checkboxes in the row headers are filled
								uncheckAll: When a header checkbox is cleared, all the checkboxes in the row headers are cleared
								selection: When the selection area of the table is changed
								editingStart: When the editing of cell is started
								editingFinish: When the editing of cell is finished
								beforeSort : Before the data is sorted
								afterSort : After the data is sorted
								beforeUnsort : Before the data is unsorted
								afterUnsort : After the data is unsorted
								sort : After the data is sorted (this event will be deprecated, use afterSort event)
								beforeFilter : Before the data is filtered
								afterFilter : After the data is filtered
								beforeUnfilter : Before the data is unfiltered
								afterUnfilter : After the data is unfiltered
								filter : After the data is filtered (this event will be deprecated, use afterFilter event)
								beforePageMove : Before moving the page
								afterPageMove : After moving the page
								scrollEnd : When scrolling at the bottommost
								beforeChange: Before one or more cells is changed
								afterChange: After one or more cells is changed
								dragStart: Drag to start the movement of the row (only occurs if the dragable option is enabled)
								drag: Dragging to move row (only occurs if the dragable option is enabled)
								drop: When the drag is over and the row movement is complete. (only occurs if the dragable option is enabled)
								keydown: When a key is pressed. (Does not occur during editing)

								*/
					
				 // console에 이벤트를 출력한다. 
					grid.on('mouseover', ev => {
					//  console.log('check!', ev);
					  ChangeData();  // 자료가 변경되면 다시 계산하는 루틴작성을 위한 연습
					  readDataCell();
					});

					grid.on('keydown', ev => {
					//  console.log('uncheck!', ev);
					  ChangeData();  // 자료가 변경되면 다시 계산하는 루틴작성을 위한 연습
					  readDataCell();	  
					});

					 grid.on('mouseout', ev => {
					//  console.log('change onGridUpdated cell!', ev);
					  ChangeData();  // 그리드가 뭔가 변경되었을때 감지함
					  readDataCell();
					}); 
					 grid.on('focusChange', ev => {
					 // console.log('change onGridUpdated cell!', ev);
					  ChangeData();  // 그리드가 뭔가 변경되었을때 감지함
					  readDataCell();
					}); 	
					
					
// Cell 변경이 발생할때 마다 계산
				 function calculateit() {
					 
					 let unitweight = [];
					 let length=[];
					 let width=[];	 
					 let set_donetime = [];
					 let set_weight = Number($('#relativedensity').val());
					 let set_unitfee = Number(uncomma($('#unitfee').val()));
					 let amountval=0;   // 개당 견적금액 산출
					 let totalamountval=0;   // 견적총액 산출
					 
					 for(i=0;i<grid.getRowCount();i++) {
						 length[i] = Number(grid.getValue(i, 'length')) ;   // 20mm 치수키운 상태
						 width[i] = Number(grid.getValue(i, 'width')) ;     // 20mm 치수키운 상태
						 set_donetime[i] = Number(grid.getValue(i, 'donetime'));  
						 unitweight[i] = (set_weight * length[i] * width[i])/1000000;  
						 unitweight[i] = unitweight[i].toFixed(2);
						 amountval = unitfee[i] + Number(grid.getValue(i, 'bending')) + Number(grid.getValue(i, 'painting')) + Number(grid.getValue(i, 'afterwork')) ;
						 totalamountval += amountval;		 
						 
						 // 가공단가 공식(임시) 중량 * 시간 * 기준단가 -> 추후 수정함.
						 unitfee[i] = Math.ceil(set_unitfee * unitweight[i] * set_donetime[i]) ;		 
						 grid.setValue(i, 'weights', unitweight[i]);
						 grid.setValue(i, 'unitfee', set_unitfee.toLocaleString('en-US'));
						 grid.setValue(i, 'estimate', unitfee[i].toLocaleString('en-US'));
						 grid.setValue(i, 'estimateAmount', amountval.toLocaleString('en-US'));		 

						 // console.log(i);
						 // console.log(length[i]);
						 // console.log(width[i]);
						 // console.log(unitweight[i]);	 		 
						 // console.log(unitfee[i]);	 		 
					 }	 
					 $('#totalamount').val(totalamountval.toLocaleString('en-US'));
					  console.log(totalamountval);
					 
				 }	 
				 
				// cell data read
				// getValue(rowKey, columnName)	   
				function  readDataCell(){
					  $('#inputval').val(grid.getValue(0, 'material'));  
				}
				  
				// cell data 변경 모듈

				 function ChangeData() {
					 //  grid.setValue(0, 'weights' , 'dkdd');  
					  // console.log('auto update');
					  calculateit();
				      // grid.setValue(0, 'jobnum' , '');  					  
				 }	 	
		} // end of grid	
					


					
});	 // end of readydocument

					


					
</script>
    </div>
  </div>	
</section>

</body>

</html>