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
     $sql = "select * from jtechel.os where num=?";
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

// 견적서 분할하는 로직 불러오기 공통사용
include 'load_estimate.php';

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
    
</head>

<title> 견적서 </title>

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
			
		<div class="row d-flex justify-content-center align-items-center h-10">	        
			<div class="card align-middle" >					
				<div class="card" style="padding:5px;margin:10px;">					
				<h4 class="display-5 card-title text-center" style="color:#113366;"> 
				  <input type="button" class="btn btn-outline-dark " value="닫기" onclick="self.close();"> 					
				  &nbsp;&nbsp;&nbsp;
			  견적서
			  </h4>
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
					<h6 class="text-center" > 
					작성일  &nbsp;
					<input  type="date" name="et_writeday" id="et_writeday" value="<?=$et_writeday?>"  > 	
					</h6> 
                    </span>
					<span class="form-control">  
					<h6 class="text-center" > 
					발주처(귀하)  &nbsp;
					<input  type="text" name="secondord" id="secondord" value="<?=$secondord?>"  > 	
					</h6>
                    </span>
					<span class="form-control">
					<h6 class="text-center" > 
					현장명 &nbsp;
					<input  type="text" name="et_wpname" id="et_wpname" value="<?=$et_wpname?>"  > 	
					</h6>
                    </span>
					<span class="form-control">
					<h6 class="text-center" > 
					공사명 &nbsp;
					<input  type="text" name="workplacename" id="workplacename" value="<?=$workplacename?>"  > 	
					</h6>
                    </span>
					<span class="form-control">
					<h6 class="text-center" > 
					공사일정 &nbsp;
					<input  type="text" name="et_schedule" id="et_schedule" value="<?=$et_schedule?>"  > 	
					</h6>
                    </span>
					<span class="form-control">
					<h6 class="text-center" > 
					공사인원 &nbsp;
					<input  type="text" name="et_person" id="et_person" value="<?=$et_person?>"  > 	
					</h6>
                    </span>

				<span class="form-control">
					<h6 class="display-5 text-left" > 
						세부 내역  &nbsp;&nbsp;
						<button  type="button" id="deldataBtn" class="btn btn-outline-dark btn-sm"> 선택삭제</button> &nbsp;						
					</h6>	
					<div id="grid">  </div>						
				</span>

				<span class="form-control">
				<h6 class="text-center" > 
				견적 합 &nbsp;
				<input  type="text" name="totalamount" id="totalamount" value="<?=$totalamount?>"  > 	
				</h6>
				</span>	
				
				<span class="form-control">
				<h6 class="text-center" > 
					비고 작성 &nbsp;
					<textarea name="et_note" id="et_note" class="form-control" placeholder="비고란 기록"><?=$et_note?></textarea>
				</h6>
				</span>			

				<br> 	  
				<button id="saveBtn" class="btn btn-lg btn-dark btn-block" type="button">
				<? if((int)$num>0) print '저장';  else print '저장'; ?></button>
				<? if($user_name=='김재구' || $user_name=='김보곤') {  ?>				
				<button id="delBtn" class="btn btn-lg btn-danger btn-block" type="button">삭제</button>
				<button id="showjpgBtn" class="btn btn-lg btn-outline-primary btn-block" type="button">PDF파일 만들기</button>
				<? } ?>
				<button id="backBtn" class="btn btn-lg btn-outline-dark btn-block" type="button" onclick="self.close();">
				창 닫기기</button>
			  </form>			  
				</div>
       	 
		

			</div>
		</div>		
				
     </div>

</div>		 
			  
		
  </body>
</html>    
 
 
 <script >
	

$(document).ready(function(){

	
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
	
	class CustomNumberEditor {
	  constructor(props) {
		const el = document.createElement('input');
		const { maxLength } = props.columnInfo.editor.options;

		el.type = 'number';
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
	  
	// var count = "<? echo $count; ?>"; 
	
	var description = <?php echo json_encode($description);?> ;
	var spec = <?php echo json_encode($spec);?> ;
	var ea = <?php echo json_encode($ea);?> ;
	var unit = <?php echo json_encode($unit);?> ;
	var amount = <?php echo json_encode($amount);?> ;
	var comment = <?php echo json_encode($comment);?> ;  
   
   console.log('자료수 : ' + description.length);
   
	let row_count = description.length;				   
	
	const COL_COUNT = 6;
	
	const data = [];
	const columns = [];
	 
	for (let i = 0; i < row_count; i += 1) {
		const row = { name: i };
		row[`description`] = description[i] ;						 						
		row[`spec`] = spec[i] ;						 						
		row[`ea`] = ea[i] ;						 						
		row[`unit`] = unit[i] ;						 						
		row[`amount`] = amount[i] ;						 						
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
		  width:200,
		  editor: {
			type: CustomTextEditor,
			options: {
			  maxLength: 40
			}
		  },	 		
		  align: 'left'
		},
		{
		  header: '규격',
		  name: 'spec',
		  width:100,						  
		  editor: {
			type: CustomTextEditor,
			options: {
			  maxLength: 20
			} 			
		  }	,  
			align: 'center'
		  // sortingType: 'desc',
		  // sortable: true,          
		  // editingEvent :  'Click'		  
		},			
		{
		  header: '수량',
		  name: 'ea',
		  width:40,						  
		  editor: {
			type: CustomNumberEditor,
			options: {
			  maxLength: 20
			} 			
		  }	,  
			align: 'right'
		  // sortingType: 'desc',
		  // sortable: true,          
		  // editingEvent :  'Click'		  
		},
		{
		  header: '단가',
		  name: 'unit',
		  width:100,						  
		  // sortingType: 'desc',
		  // sortable: true,
		  editor: {
			type: CustomNumberEditor,
			options: {
			  maxLength: 10
			}
		  }	, 		  
		  align: 'right'		  
		},						
		{
		  header: '금액',
		  name: 'amount',
		  width:100,
		  // sortingType: 'desc',
		  // sortable: true,
		  editor: {
			type: CustomNumberEditor,
			options: {
			  maxLength: 10
			}
		  }	, 		  
		  align: 'right'
		},
		{
		  header: '비고',
		  name: 'comment',
		  width:150,						  
		  // sortingType: 'desc',
		  // sortable: true,
		  editor: {
			type: CustomTextEditor,
			options: {
			  maxLength: 20
			}
		  }	, 		  
		  align: 'left'
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
	
// grid 꾸미기
var Grid = tui.Grid; // or require('tui-grid')
	Grid.applyTheme('striped', {
			selection: {
				background: '#4555f9',
				border: '#004082'
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
				  background: '#ccc'
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
				  border: '#418ed4'
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

								
// grid 변경된 내용을 php 넘기기 위해 input hidden에 넣는다.
function savegrid()   {
	  
		let description  =  new Array();  
		let spec  =  new Array();  
		let ea  =  new Array();  
		let unit  =  new Array();  
		let amount  =  new Array();  				
		let comment  =  new Array(); 
		let tmpstr ;  // 배열을 저장할 변수	  
		
		// console.log(grid.getRowCount());	//삭제시 숫자가 정상적으로 줄어든다.
		 const MAXcount = grid.getRowCount() + 20 ;  // 20개 데이터를 rowkey 영향으로 더 검색한다.						     		 
		 for(i=0; i < MAXcount; i++) {      // grid.value는 중간중간 데이터가 빠진다. rowkey가 삭제/ 추가된 것을 반영못함.    
			if( grid.getValue(i, 'description' ) != null  ) 
				{				 								   
					description.push(grid.getValue(i, 'description' ));																 
					spec.push(grid.getValue(i, 'spec' ));																 
					ea.push(uncomma(grid.getValue(i, 'ea' )));																 
					unit.push(uncomma(grid.getValue(i, 'unit' )));																 
					amount.push(uncomma(grid.getValue(i, 'amount' )));																 
					comment.push(grid.getValue(i, 'comment' ));		
				} // end of else				
			 }
         // 배열에 자료를 넣기 전에 일단 초기화해준다.
		 console.log(description);
		 console.log(spec);
		 console.log(ea);
		//  grid.clear();  // 그리드 전체 삭제 clear
			 
		 // 배열형식을 구분자를 줘서 저장하는 루틴임 AAA | AAAA | BBBB | 이런식으로 저장한다.
		 tmpstr = '';
		 
		 for(i=0; i < description.length ; i++) {      // grid.value는 중간중간 데이터가 빠진다. rowkey가 삭제/ 추가된 것을 반영못함.   
             if( description[i] !== null ) 		 
				{					
					tmpstr += description[i];																 
					tmpstr += '|';
					tmpstr += spec[i];	
					tmpstr += '|';
					tmpstr += uncomma(ea[i] );
					tmpstr += '|';
					tmpstr += uncomma(unit[i] );
					tmpstr += '|';
					tmpstr += uncomma(amount[i] );	
					tmpstr += '|';
					tmpstr += comment[i]; 
				   if(description.length -1 != i)  // 마지막에 | 표시 없앰
						tmpstr += '|';
				}
			 }	
              
			// console.log(description.length);
			console.log(tmpstr);
			
			$('#et_content').val(tmpstr);					
	   }	
	   
	function calculateit() {
		
		let set_ea = 0;
		let set_unit = 0;
		let set_amount = 0;

		let totalamount = 0;   // 견적총액 산출		
			   
        const MAXcount = grid.getRowCount() + 20 ;  // 20개 데이터를 rowkey 영향으로 더 검색한다.						     		 
	      for(i = 0; i < MAXcount ; i++) {
				set_ea = Number(uncomma(grid.getValue(i, 'ea')));
				set_unit = Number(uncomma(grid.getValue(i, 'unit')));
				set_amount = set_ea * set_unit ;
				  if(set_ea > 0) 
					{
					grid.setValue(i, 'ea', comma(set_ea));	 
					grid.setValue(i, 'unit', comma(set_unit)) ;	 
					grid.setValue(i, 'amount', comma(set_amount));	 
					totalamount += set_amount ;
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
      
		var tmp = grid.getCheckedRowKeys();
			console.log(tmp);
			tmp.forEach(function(e){
					 grid.removeRow(e);			
			 });	 
						
		   calculateit();  

		  
	  });	   			  
 
	 
				 
	function SelInsertData()  {    // 선택한 데이터 이후에 삽입
			var tmp = grid.getCheckedRowKeys();
			tmp.forEach(function(e){
			 appendRow(e+1);        // 함수를 만들어서 한줄삽입처리함.
			  console.log(e);
			});	
		  // grid.resetOriginData(data);			 // 데이터 update					
		  //  grid.resetData(data);			 // 데이터 update				 
		 }					 
		 
	function appendRow(index=null) {
				var newRow = {
					eventId: '',
					localEvent: '',
					copyControl: ''
						};
				if (index== null) { // 행(row) 추가(끝에)
					grid.appendRow(newRow);
					} else { // 끝이 아닐때는 행(row) 삽입 실행
							var optionsOpt = {
									at: index,
									extendPrevRowSpan: false,
									focus: false
									};
							grid.appendRow(newRow , optionsOpt);
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
	link = 'http://j-techel.co.kr/os/showjpg.php?num=' + num;
	window.open(link, "_blank", "toolbar=yes,scrollbars=yes,resizable=yes,top=30,left=20,width=1200,height=900");
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

	if( (user_name=='김재구') || user_name=='김보곤') {	
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
