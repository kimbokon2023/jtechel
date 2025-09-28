<meta charset="utf-8">
 
<?php
session_start(); 

$level= $_SESSION["level"];
$id_name= $_SESSION["name"];   
$user_name= $_SESSION["name"];  
    
 $num=$_REQUEST["num"];
 $search=$_REQUEST["search"];  //검색어

 if(isset($_REQUEST["check"])) 
	 $check=$_REQUEST["check"]; 
   else
     $check=$_POST["check"]; 
 
 if(isset($_REQUEST["page"]))
 {
    $page=$_REQUEST["page"]; 
 }
  else
  {
    $page=1;	 
  }
	 
$today=date("Y-m-d");  // 현재일 저장   	 

if($num!=null && $num!=0)
{	
 require_once("../lib/mydb.php");
 $pdo = db_connect();
  
 try{
     $sql = "select * from jtechel.jtech where num=?";
     $stmh = $pdo->prepare($sql);  
     $stmh->bindValue(1, $num, PDO::PARAM_STR);      
     $stmh->execute();            
      
     $row = $stmh->fetch(PDO::FETCH_ASSOC); 	
	
	 include 'rowDB.php';

		      if($measureday!="0000-00-00" and $measureday!="1970-01-01" and $measureday!="")   $measureday = date("Y-m-d", strtotime( $measureday) );
					else $measureday="";
		      if($workday!="0000-00-00" and $workday!="1970-01-01"  and $workday!="")  $workday = date("Y-m-d", strtotime( $workday) );
					else $workday="";			      
		      if($demand!="0000-00-00" and $demand!="1970-01-01" and $demand!="")  $demand = date("Y-m-d", strtotime( $demand) );
					else $demand="";			
		      if($doneday!="0000-00-00" and $doneday!="1970-01-01" and $doneday!="")  $doneday = date("Y-m-d", strtotime( $doneday) );
					else $doneday="";			
		      if($regist_day!="0000-00-00" and $regist_day!="1970-01-01" and $regist_day!="")  $regist_day = date("Y-m-d", strtotime( $regist_day) );
					else $regist_day="";		
		      if($et_writeday!="0000-00-00" and $et_writeday!="1970-01-01" and $et_writeday!="")  $et_writeday = date("Y-m-d", strtotime( $et_writeday) );
					else $et_writeday=$today;									
					
     }catch (PDOException $Exception) {
       print "오류: ".$Exception->getMessage();
     }
}

// print $sql;

// 견적서 분할하는 로직 불러오기 공통사용
include 'load_estimate.php';


// 품명이 없으면 기록해준다.

if($et_itemname=='')
	$et_itemname = "승강기 변색 및 스크래치 보수";

 ?>
 
<!DOCTYPE html>
<html lang="ko">
<head>

<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
<meta name="description" content="">
<meta name="author" content="">

<!-- Bootstrap CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" >
<!-- Optional JavaScript -->
<!-- jQuery first, then Popper.js, then Bootstrap JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>	    
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" ></script> 
<!-- toast Grid 사용을 위한 부분 -->
<link rel="stylesheet" href="https://uicdn.toast.com/tui.pagination/latest/tui-pagination.css" />
<script src="https://uicdn.toast.com/tui.pagination/latest/tui-pagination.js"></script>
<link rel="stylesheet" href="https://uicdn.toast.com/tui-grid/latest/tui-grid.css"/>
<script src="https://uicdn.toast.com/tui-grid/latest/tui-grid.js"></script>


<script src="http://j-techel.co.kr/common.js"></script>
	
<link rel="stylesheet" href="../css/partner.css" type="text/css" />
<link rel="stylesheet" href="./css/style.css" type="text/css" />	
    
<style>
/* 부트스트랩 아이콘 나오게 하기 */
@import url("https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.1/font/bootstrap-icons.css");
</style>	
	
</head>

<title> 견적서 작성 </title>

<body>

<div class="container-fluid"> 

    <!-- Modal -->
  <div class="modal fade" id="myModal" role="dialog">
    <div class="modal-dialog  modal-lg modal-center" >
    
	<!-- Modal content-->
      <div class="modal-content modal-lg">
        <div class="modal-header">          
          <h5 class="modal-title">알림</h5>
        </div>
        <div class="modal-body">		
		   <div id=alertmsg class="fs-1 mb-5 justify-content-center" >
		     결재가 진행중입니다. <br> 
		   <br> 
			 수정사항이 있으면 결재권자에게 말씀해 주세요.
			</div>
        </div>
        <div class="modal-footer">
          <button type="button" id="closeModalBtn" class="btn btn-default" data-dismiss="modal">닫기</button>
        </div>
      </div>
      
    </div>
  </div>
			
		<div class="row d-flex justify-content-center align-items-center ">	        
			<div class="card align-middle " style="width: 85rem;">					
				<div class="d-flex justify-content-center align-items-center mt-2 mb-2 p-2 m-2" >					
				
				<h3>	견적서    </h3>   &nbsp; &nbsp;  &nbsp; &nbsp; 	
				
					<button type="button" class="btn btn-outline-dark   " onclick="self.close();" > <i class="bi bi-box-arrow-left"></i> 창닫기 </button>	&nbsp; 				
					<button id="saveBtn" class="btn  btn-dark " type="button">&nbsp; 	
					<? if((int)$num>0) print ' <i class="bi bi-hdd-fill"></i> 저장';  else print ' <i class="bi bi-hdd-fill"></i> 저장'; ?></button>&nbsp; 	
					<? if($user_name=='제이테크') {  ?>				
					<button id="delBtn" class="btn btn-danger" type="button"><i class="bi bi-trash2"></i> 삭제</button>&nbsp; 	
					<button id="showjpgBtn" class="btn btn-outline-primary   " type="button">PDF파일 만들기</button>&nbsp; 	
					<button id="ExcelexportBtn" class="btn btn-outline-dark " data-bs-toggle="tooltip" data-bs-placement="bottom" title="발주서를 Excel형식으로 Export합니다."  type="button" onclick="location.href='excelform.php?num=<?=$num?>'">Excel 변환</button>						 
					
					<? } ?>
				
				 
			</div>	
		<div class="card-body text-center">
			  <form id="board_form" method="post" enctype="multipart/form-data"  action="et_insert.php" >
				<input type="hidden" id="mode" name="mode">
				<input type="hidden" id="num" name="num" value="<?=$num?>" >			  								
				<input type="hidden" id="user_name" name="user_name" value="<?=$user_name?>" > 					
				<input type="hidden" id="update_log" name="update_log" value="<?=$update_log?>"  > 					
				<input type="hidden" id="tablename" name="tablename" value="<?=$tablename?>"  > 					
				<input type="hidden" id="item" name="item" value="<?=$item?>"  > 					
				<input type="hidden" id="et_content" name="et_content" value="<?=$et_content?>"  > 					
			  
				<span class="form-control">
				<div class="input-group p-1 mb-1  justify-content-center ">

							 <span class="input-group-text ">  견적작성일  &nbsp; </span>	 
							 <input   class="input-form"  type="date" name="et_writeday" id="et_writeday" value="<?=$et_writeday?>"  > 		 &nbsp; &nbsp; 						 
							 <span class="input-group-text ">  견적서 수신처(귀하)  &nbsp; </span>	  
							<input class="input-form"  type="text" name="et_receiver" id="et_receiver" value="<?=$et_receiver?>"  > &nbsp; &nbsp; 	
							<span class="input-group-text ">  품명 &nbsp; </span>	 
							<input class="input-form"  size=30 type="text" name="et_itemname" id="et_itemname" value="<?=$et_itemname?>"  > &nbsp; &nbsp; 								 							
							<span class="input-group-text ">  납기일 &nbsp; </span>	  
							 <input   class="input-form" size=20 type="text" name="et_deadline" id="et_deadline" value="<?=$et_deadline?>"  > 	
							 
					</div> 
				</span>
				<span class="form-control">
				<div class="input-group p-1 mb-1  justify-content-center ">
							
				<span class="input-group-text ">  현장명 &nbsp; </span>	  
				 <input   class="input-form" size=30 type="text" name="et_wpname" id="et_wpname" value="<?=$et_wpname?>"  > 	&nbsp; &nbsp; 								 
							 <span class="input-group-text ">  결재방식 &nbsp; </span>	  
							<input class="input-form"  type="text" name="et_paymethod" id="et_paymethod" value="<?=$et_paymethod?>"  > &nbsp; &nbsp; 	
							 <span class="input-group-text ">  유효기간 &nbsp; </span>	  
							 <input   class="input-form" type="text" name="et_validation" id="et_validation" value="<?=$et_validation?>"  > 	
					</div> 
				</span>
				

				<span class="form-control">		
						<div class="input-group p-1 mb-1 ">
						<button  type="button"  id="insertdataBtn" class="btn btn-outline-dark btn-sm"> 행삽입</button> &nbsp;	&nbsp;	
						<button  type="button"  id="deldataBtn" class="btn btn-outline-dark btn-sm"> 선택삭제</button> &nbsp;	&nbsp;	
							 <span class="input-group-text "> 견적금액 합  &nbsp; </span>&nbsp; 
								<input  type="text" class="input-form text-right" size=12 name="totalamount" id="totalamount" value="<?=$totalamount?>"  > 	
							  
								  &nbsp;&nbsp;						 &nbsp;&nbsp;						
																		
						</div>
				</span>

				<span class="form-control">
					<ul class="nav nav-pills">
						<li class="nav-item">	
							<a class="nav-link" href="#">						
								(품명1~5)
							</a>
						</li>
						<li class="nav-item">
						<a class="nav-link active" aria-current="page" href="#"> 
						<?php
						   if($material1!='')
							     print '1. ' . $material1;
							 ?> </a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="#">
							<?php
							   if($material2!='')
									 print '2. ' . $material2;
								 ?> </a>
						</li>
		
						<li class="nav-item">
							<a class="nav-link" href="#">
							<?php
							   if($material3!='')
									 print '3. ' . $material3;
								 ?> </a>
						</li>
		
						<li class="nav-item">
							<a class="nav-link" href="#">
							<?php
							   if($material4!='')
									 print '4. ' . $material4;
								 ?> </a>
						</li>
		
						<li class="nav-item">
							<a class="nav-link" href="#">
							<?php
							   if($material5!='')
									 print '5. ' . $material5;
								 ?> </a>
						</li>
		
						</ul>	
						
				</span>

				<span class="form-control">
				<div id="grid">  </div>						
				</span>
				<span class="form-control">
					<div class="input-group p-1 mb-1  justify-content-center ">
							<span class="input-group-text ">  	 비고 </span>	
				
					<textarea name="et_note" id="et_note" rows=3 class="form-control" placeholder="(비고 기록)"><?=$et_note?></textarea>
				    </div>
				</span>			

			  </form>			  
				</div>
       	 
		

			</div>
		</div>		
				
   </div>		  
		
  </body>
</html>    

 
<script>

$(document).ready(function(){		  
		
	var description = <?php echo json_encode($description);?> ;
	var spec = <?php echo json_encode($spec);?> ;
	var unit = <?php echo json_encode($unit);?> ;
	var quantity = <?php echo json_encode($quantity);?> ;
	var unitprice = <?php echo json_encode($unitprice);?> ;
	var amount = <?php echo json_encode($amount);?> ;
	var comment = <?php echo json_encode($comment);?> ;  
   
    console.log('자료수 : ' + description.length);
   
	let row_count = description.length;				   
	
	const COL_COUNT = 7;
	
	const data = [];
	const columns = [];
	 
	for (let i = 0; i < row_count; i += 1) {
		const row = { name: i };
		row[`description`] = description[i] ;						 						
		row[`spec`] = spec[i] ;						 						
		row[`unit`] = unit[i] ;						 						
		row[`quantity`] = comma(quantity[i]) ;						 						
		row[`unitprice`] = comma(unitprice[i]) ;						 						
		row[`amount`] = comma(amount[i]) ;						 						
		row[`comment`] = comment[i] ;			
		data.push(row);
				
	  }	  
	  

   const grid = new tui.Grid({
	  el: document.getElementById('grid'),
	  data: data,
	  bodyHeight: 300,	
	   columns: [ 				   
		{
		  header: '품목',
		  name: 'description',
		 // sortingType: 'desc',
		 // sortable: true,
		  width:320,
		  editor: {
			type: 'text',
			
		  },	 		
		  align: 'left'
		},
		{
		  header: '규격',
		  name: 'spec',
		  width:200,						  
		  editor: {
			type: 'text',			
		  }	,  
			align: 'center'
		  // sortingType: 'desc',
		  // sortable: true,          
		  // editingEvent :  'Click'		  
		},			
		{
		  header: '단위',
		  name: 'unit',
		  width:50,						  
		  editor: {			
			type: 'text',
			
		  }	,  
			align: 'right'
		  // sortingType: 'desc',
		  // sortable: true,          
		  // editingEvent :  'Click'		  
		},		
		{
		  header: '수량',
		  name: 'quantity',
		  width:40,						  
		  editor: {			
			type: 'text',			
			 			
		  }	,  
			align: 'right'
		  // sortingType: 'desc',
		  // sortable: true,          
		  // editingEvent :  'Click'		  
		},
		{
		  header: '단가',
		  name: 'unitprice',
		  width:100,						  
		  // sortingType: 'desc',
		  // sortable: true,
		  editor: {
			type: 'text',			
		  }	, 		  
		  align: 'right'		  
		},						
		{
		  header: '금액',
		  name: 'amount',
		  width:130,
		  // sortingType: 'desc',
		  // sortable: true,
		  editor: {
			type: 'text',
			
		  }	, 		  
		  align: 'right'
		},
		{
		  header: '비고',
		  name: 'comment',
		  width:200,						  
		  // sortingType: 'desc',
		  // sortable: true,
		  editor: {
			type: 'text',
			
		  }	, 		  
		  align: 'left'
		}		
	  ],
// 	  draggable: true,
		columnOptions: {
			resizable: true
			  },
		  rowHeaders: ['rowNum','checkbox'],
		  pageOptions: {
			useClient: false,
			perPage: 20
	  },
	  
	});	
		
	// grid 꾸미기
	var Grid = tui.Grid; // or require('tui-grid')
		Grid.applyTheme('striped', {
				selection: {
					background: '#7d7575',
					border: '#00000c'
				  },
				  scrollbar: {
					background: '#f5f5f5',
					thumb: '#d9d9d9',
					active: '#c1c1c1'
				  },
				  row: {
					even: {
					  background: '#EEFAEE'
					},
					hover: {
					  background: '#e8e8e8'
					}
				  },
				  cell: {
					normal: {
					  background: '#fbfbfb',
					  border: '#e0e0e0',
					  showVerticalBorder: true
					},
					header: {
					  background: '#eee',
					  border: '#ccc',
					  showVerticalBorder: true
					},
					rowHeader: {
					  border: '#ccc',
					  showVerticalBorder: true
					},
					editable: {
					  background: '#fbfbfb'
					},
					selectedHeader: {
					  background: '#d8d8d8'
					},
					focused: {
					  border: '#b0b0b0'
					},
					disabled: {
					  text: '#b0b0b0'
					}
				  }	
	});
	 
	 
		 // console에 이벤트를 출력한다. 
		grid.on('editingFinish', ev => {
			ChangeData();  // 자료가 변경되면 다시 계산하는 루틴작성을 위한 연습
		console.log('check!', ev);					  
		});

		grid.on('mouseout', ev => {
		//  console.log('uncheck!', ev);
		  ChangeData();  // 자료가 변경되면 다시 계산하는 루틴작성을 위한 연습
		});

		grid.on('focusChange', ev => {
		 ChangeData();  // 그리드가 뭔가 변경되었을때 감지함
		 console.log('change onGridUpdated cell!', ev);
		 }); 	
	
	// 로드하면 재계산한다.
	calculateit()
	   								
// grid 변경된 내용을 php 넘기기 위해 input hidden에 넣는다.
function savegrid()   {	  
		let description  =  new Array();  
		let spec  =  new Array();  
		let unit  =  new Array();  
		let quantity  =  new Array();  
		let unitprice  =  new Array();  
		let amount  =  new Array();  				
		let comment  =  new Array(); 
		let tmpstr ;  // 배열을 저장할 변수	  
		
		// console.log(grid.getRowCount());	//삭제시 숫자가 정상적으로 줄어든다.
		 const MAXcount = grid.getRowCount() ;  // 5개 데이터를 rowkey 영향으로 더 검색한다.						     		 
		 for(i=0; i < MAXcount; i++) {      // grid.value는 중간중간 데이터가 빠진다. rowkey가 삭제/ 추가된 것을 반영못함.    
			
					description.push(grid.getValue(i, 'description' ));																 
					spec.push(grid.getValue(i, 'spec' ));																 
					unit.push(grid.getValue(i, 'unit' ));																 
					quantity.push(uncomma(grid.getValue(i, 'quantity' )));																 
					unitprice.push(uncomma(grid.getValue(i, 'unitprice' )));																 
					amount.push(uncomma(grid.getValue(i, 'amount' )));																 
					comment.push(grid.getValue(i, 'comment' ));						
			 }
         // 배열에 자료를 넣기 전에 일단 초기화해준다.
		 console.log(description);
		 console.log(spec);
		 console.log(quantity);
		//  grid.clear();  // 그리드 전체 삭제 clear
			 
		 // 배열형식을 구분자를 줘서 저장하는 루틴임 AAA | AAAA | BBBB | 이런식으로 저장한다.
		 tmpstr = '';
		 
		 for(i=0; i < description.length ; i++) {      // grid.value는 중간중간 데이터가 빠진다. rowkey가 삭제/ 추가된 것을 반영못함.   
             
					tmpstr += description[i];																 
					tmpstr += '|';
					tmpstr += spec[i];	
					tmpstr += '|';
					tmpstr += unit[i];	
					tmpstr += '|';
					tmpstr += uncomma(quantity[i] );
					tmpstr += '|';
					tmpstr += uncomma(unitprice[i] );
					tmpstr += '|';
					tmpstr += uncomma(amount[i] );	
					tmpstr += '|';
					tmpstr += comment[i]; 
				   if(description.length -1 != i)  // 마지막에 | 표시 없앰
						tmpstr += '|';			
			 }	
              
			// console.log(description.length);
			console.log(tmpstr);
			
			$('#et_content').val(tmpstr);					
	   }	
	   
	 function zero(str) {
        if(str=='0')
            return '';	
           else
			return str;			   
	 }		 
	   
	function calculateit() {
		
		let set_quantity = 0;
		let set_unitprice = 0;
		let set_amount = 0;
		let set_des, result;

		let totalamount = 0;   // 견적총액 산출		
			   
        const MAXcount = grid.getRowCount() ;  // 20개 데이터를 rowkey 영향으로 더 검색한다.		
        let smallsum = 0;		
		
	      for(i = 0; i < MAXcount ; i++) {
				set_quantity = Number(uncomma(grid.getValue(i, 'quantity')));
				set_unitprice = Number(uncomma(grid.getValue(i, 'unitprice')));
				set_amount = set_quantity * set_unitprice ;
				set_des = grid.getValue(i, 'description');
				result = set_des.replace(/ /g, ''); // 공백제거
				// console.log(result);				 					
						if(result!=='소계')
						{
							grid.setValue(i, 'quantity', zero(comma(set_quantity)));	 
							grid.setValue(i, 'unitprice', zero(comma(set_unitprice))) ;	 
							grid.setValue(i, 'amount', zero(comma(set_amount)));	 
							smallsum += set_amount ;
							// console.log(smallsum);
						}
						else  // 소계인 경우
						{
							grid.setValue(i, 'amount', zero(comma(smallsum)));
							totalamount += smallsum ;
							//console.log(totalamount);
							smallsum = 0;
						}
							
					
				}	 
					
			  $('#totalamount').val(totalamount.toLocaleString('en-US'));	
			  // $("#laserRunTime").val(laserRunTime);  // 레이져 가동시간 화면에 보여주기

		 }	 

	 function ChangeData() {
		 calculateit();
		  // grid.setValue(0, 'description' , '');  					  
	 }	 	
	 

   $("#deldataBtn").click(function(){  
    // 삭제시 삽입과 마찬가지로 데이터를 화면상 지워주고 rowKey를 제거하는 방식으로 진행한다.
	
	  var tmp = grid.getCheckedRowKeys();
	  tmp.forEach(function(e){
		 deleteRow(e);        // 함수를 만들어서 한줄삽입처리함.		
		  console.log(e);
		  
			});	
	});	
	
	 function deleteRow(index=null) {		
   
		let description  =  new Array();  
		let spec  =  new Array();  
		let unit  =  new Array();  
		let quantity  =  new Array();  
		let unitprice  =  new Array();  
		let amount  =  new Array();  				
		let comment  =  new Array(); 
		
		// console.log(grid.getRowCount());	//삭제시 숫자가 정상적으로 줄어든다.
		 let MAXcount = grid.getRowCount() ;  
         console.log(MAXcount);
		 
		 for(i=0; i < MAXcount; i++) {      // grid.value는 중간중간 데이터가 빠진다. rowkey가 삭제/ 추가된 것을 반영못함.    
			if( index != i  )  // 행 삭제한다.
				{	
					description.push(grid.getValue(i, 'description' ));																 
					spec.push(grid.getValue(i, 'spec' ));																 
					unit.push(grid.getValue(i, 'unit' ));																 
					quantity.push(uncomma(grid.getValue(i, 'quantity' )));																 
					unitprice.push(uncomma(grid.getValue(i, 'unitprice' )));																 
					amount.push(uncomma(grid.getValue(i, 'amount' )));																 
					comment.push(grid.getValue(i, 'comment' ));		
					
			   }				 
			 }
			 
         
		 // 화면 지워준다
		      for(i = 0; i < MAXcount ; i++) {				
					grid.setValue(i, 'description', '');
					grid.setValue(i, 'spec', '');
					grid.setValue(i, 'unit', '');
					grid.setValue(i, 'quantity', '');
					grid.setValue(i, 'unitprice', '');
					grid.setValue(i, 'amount', '');
					grid.setValue(i, 'comment', '');					
				}        


           // 실제 데이터를 지워준다.
		   //  grid.removeRow(index);	

 			MAXcount = grid.getRowCount()  ;  // 1+ 1개 데이터를 추가				 
		  
		  // 새로 삽입되는 행을 포함한 데이터를 새로 넣어준다
			for(i = 0; i < description.length ; i++) {				
					grid.setValue(i, 'description', description[i]);
					grid.setValue(i, 'spec', spec[i]);
					grid.setValue(i, 'unit', unit[i]);
					grid.setValue(i, 'quantity', quantity[i]);
					grid.setValue(i, 'unitprice', unitprice[i]);
					grid.setValue(i, 'amount', amount[i]);
					grid.setValue(i, 'comment', comment[i]);					
				}
		  
						
		   calculateit();  
		  
	  }	   	
	  
  // 행삽입	  
   $("#insertdataBtn").click(function(){  
      
		var tmp = grid.getCheckedRowKeys();
		tmp.forEach(function(e){
		 appendRow(e+1);        // 함수를 만들어서 한줄삽입처리함.
		  console.log(e);
						
	     calculateit();  
		  
			});	
		  
	  });	   			   				 
	
		 
	function appendRow(index= null) {
		
		// 레코드를 중간에 삽입하면 rowKey가 적용안되는 문제를 해결하기 위해서 저장 후 다시 불러주는 루틴작성
		
		        // 마지막에 한줄 추가 후 삽입작업
				var newRow = {
					eventId: '',
					localEvent: '',
					copyControl: ''
						};
				grid.appendRow(newRow);
				

				// var newRow = {
					// eventId: '',
					// localEvent: '',
					// copyControl: ''
						// };
				// if (index== null) { // 행(row) 추가(끝에)
					// grid.appendRow(newRow);
					// } else { // 끝이 아닐때는 행(row) 삽입 실행
							// var optionsOpt = {
									// at: index,
									// extendPrevRowSpan: false,
									// focus: false
									// };
							// grid.appendRow(newRow , optionsOpt);
							// }   		


		let description  =  new Array();  
		let spec  =  new Array();  
		let unit  =  new Array();  
		let quantity  =  new Array();  
		let unitprice  =  new Array();  
		let amount  =  new Array();  				
		let comment  =  new Array(); 
		
		// console.log(grid.getRowCount());	//삭제시 숫자가 정상적으로 줄어든다.
		 const MAXcount = grid.getRowCount() + 1 ;  // 1+ 1개 데이터를 추가	
         console.log(MAXcount);
		 
		 for(i=0; i < MAXcount; i++) {      // grid.value는 중간중간 데이터가 빠진다. rowkey가 삭제/ 추가된 것을 반영못함.    
			if( index != i  )  // 행삽입을 해준다.
				{				 								   
					description.push(grid.getValue(i, 'description' ));																 
					spec.push(grid.getValue(i, 'spec' ));																 
					unit.push(grid.getValue(i, 'unit' ));																 
					quantity.push(grid.getValue(i, 'quantity' ));																 
					unitprice.push(grid.getValue(i, 'unitprice' ));																 
					amount.push(grid.getValue(i, 'amount' ));																 
					comment.push(grid.getValue(i, 'comment' ));		
				} // end of else				
			 else  // 삽입행과 만나면 공백을 넣어준다.
			  {
				  
					description.push('');																 
					spec.push('');																 
					unit.push('');																 
					quantity.push('');																 
					unitprice.push('');																 
					amount.push('');																 
					comment.push('');
					
					description.push(grid.getValue(i, 'description' ));																 
					spec.push(grid.getValue(i, 'spec' ));																 
					unit.push(grid.getValue(i, 'unit' ));																 
					quantity.push(uncomma(grid.getValue(i, 'quantity' )));																 
					unitprice.push(uncomma(grid.getValue(i, 'unitprice' )));																 
					amount.push(uncomma(grid.getValue(i, 'amount' )));																 
					comment.push(grid.getValue(i, 'comment' ));		
					
			   }				 
			 }
         
		 // 화면 지워준다
		      for(i = 0; i < MAXcount ; i++) {				
					grid.setValue(i, 'description', '');
					grid.setValue(i, 'spec', '');
					grid.setValue(i, 'unit', '');
					grid.setValue(i, 'quantity', '');
					grid.setValue(i, 'unitprice', '');
					grid.setValue(i, 'amount', '');
					grid.setValue(i, 'comment', '');					
				}        
		  		 
		  
		  // 새로 삽입되는 행을 포함한 데이터를 새로 넣어준다
			for(i = 0; i < MAXcount ; i++) {				
					grid.setValue(i, 'description', description[i]);
					grid.setValue(i, 'spec', spec[i]);
					grid.setValue(i, 'unit', unit[i]);
					grid.setValue(i, 'quantity', quantity[i]);
					grid.setValue(i, 'unitprice', unitprice[i]);
					grid.setValue(i, 'amount', amount[i]);
					grid.setValue(i, 'comment', comment[i]);					
				}
		  
	
		
         
		}				 
			
			 
		$("#closeModalBtn").click(function(){ 
			$('#myModal').modal('hide');
		});

	$("#closeBtn").click(function(){    // 저장하고 창닫기	
		 });	

		// 견적서 PDF파일 만들기 버튼	 
		$("#showjpgBtn").click(function(){    // jpg보여주기 그리고 pdf파일 생성	
			var num = '<?php echo $num; ?>' ; 
			var link ;
			link = 'http://j-techel.co.kr/jtech/showjpg.php?num=' + num;
			  popupCenter(link , '원청 검색', 1500, 900);
			
		});	
			
	$("#saveBtn").click(function(){      // DATA 저장버튼 누름
	   // 견적서 그리드 저장
		savegrid();
		var num = $("#num").val();  	
			   
	// 결재상신이 아닌경우 수정안됨	 
	if(Number(num)>0) 
			   $("#mode").val('modify');     
			  else
				  $("#mode").val('insert');     
			  
		// 자료 삽입/수정하는 모듈		  
		Fninsert();	
			
	 }); 
 

	// 삽입/수정하는 모듈 
	function Fninsert() {	 

	$("#mode").val('modify');

	// 폼데이터 전송시 사용함 Get form         
	var form = $('#board_form')[0];  	    
	// Create an FormData object          
	var data = new FormData(form); 

		tmp='파일을 저장중입니다. 잠시만 기다려주세요.';		
		$('#alertmsg').html(tmp); 			  
		$('#myModal').modal('show'); 	

		console.log(data);		  

	$.ajax({	
		enctype: 'multipart/form-data',    // file을 서버에 전송하려면 이렇게 해야 함 주의
		processData: false,    
		contentType: false,      
		cache: false,           
		timeout: 600000, 			
		url: "et_insert.php",
		type: "post",		
		data: data,			
		// dataType:"text",  // text형태로 보냄
		success : function(data){
			console.log(data);
			// opener.location.reload();
			// window.close();	
			setTimeout(function() {
				$('#myModal').modal('hide');  
				}, 1000);		
			   
		},
		error : function( jqxhr , status , error ){
			console.log( jqxhr , status , error );
					} 			      		
	   });		

		// else
		  // {
		  // tmp='보고자만 결재상신 상태가 아닌 경우 수정이 가능합니다.';		
		  // $('#alertmsg').html(tmp); 			  
			// $('#myModal').modal('show');  
		  // }
		  
	} 
		 
$("#delBtn").click(function(){      // del
	var num = $("#num").val();    
	var user_name = $("#user_name").val();  

	if( (user_name=='제이테크') || user_name=='김보곤') {	
         if(confirm("견적서 기록 내용을 삭제하면 복구할 방법이 없습니다.\n\n정말 삭제하시겠습니까?")) {
	   $("#mode").val('delete');    	   
	   
		$.ajax({
			url: "et_insert.php",
			type: "post",		
			data: $("#board_form").serialize(),			
			success : function( data ){
				console.log( data);
				opener.location.reload();
				window.close();					
			},
			  error : function( jqxhr , status , error ){
				   console.log( jqxhr , status , error );
			} 			      		
		   });	
		 }		   
		} // end of if
		else
		  {
	      tmp='삭제권한이 없습니다.';		
		  $('#alertmsg').html(tmp); 			  
			$('#myModal').modal('show');  
		  }
			
 }); // end of function
			 
 


}); // end of ready document
  
  
</script>
