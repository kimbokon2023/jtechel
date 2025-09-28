<?php session_start();

?>

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
<!-- 화면에 UI창 알람창 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>

<!-- JavaScript Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
<body>
<title> 공급처 관리 </title>
<style>
   @import url("https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.1/font/bootstrap-icons.css");
</style>

	<section class ="d-flex fex-column align-items-left flex-md-row p-1">
	 <div class="p-2 pt-md-3 pb-md-3 text-left" style="width:100%;">	  
		 <form id="mainFrm" method="post" enctype="multipart/form-data" >		
            <input type="hidden" id="SelectWork" name="SelectWork" > 
            <input type="hidden" id="vacancy" name="vacancy" > 
            <input type="hidden" id="num" name="num" value=<?=$num?> > 
            <input type="hidden" id="page" name="page" value=<?=$page?> > 
            <input type="hidden" id="calculate" name="calculate" value=<?=$calculate?> > 
		    <div class="card-header"> 			                        	                         
						 <div class="input-group p-2 mb-1">							 
						<button  type="button" id="prependBtn"  class="btn btn-secondary" >DATA 추가</button> &nbsp;
						<button  type="button" id="deldataBtn" class="btn btn-outline-danger">   선택 삭제</button> &nbsp;						
						<button  type="button" id="closeBtn"  class="btn btn-outline-dark" > DATA 저장 후 창닫기 </button> 						
					    
						 <div class="input-group  justify-content-center p-5 mb-5" id="loading" style="display:none;" >							 
						   <img id="loading-image" src="/img/loading.gif" alt="Loading..." />						   
                         </div>
						
		   <?php						      						  
	
		require_once("./lib/mydb.php");
		$pdo = db_connect();	
			
	 try{
		  $sql = "select * from mirae8440.steelsupplier ";
		  $stmh = $pdo->prepare($sql); 
		  $stmh->execute();
		  $count = $stmh->rowCount();              
		if($count<1){  
		  print "검색결과가 없습니다.<br>";
		 }   else    {    
		 
			$count=0;
			$steelcompany = array();
			while($row = $stmh->fetch(PDO::FETCH_ASSOC)) {			
			$steelcompany[$count] =$row["company"];		
				
				  $count++;  // 배열에 담아두기			 
			 
				  } // end of while
			}  // end of else

		 }catch (PDOException $Exception) {
		   print "오류: ".$Exception->getMessage();
		 }
	 // end of if	
	
				
					
?>  
				</div>
						
				<div id="grid">  </div>		
					
	            <div id="tui-pagination-container" class="tui-pagination"></div>
			 <!-- 배열을 전달하기 위한 Grid 값
				<input id="steelcompany" name="steelcompany[]" type=hidden > -->			  	
			  			     
			<div id="tmpdiv"> </div>
	     	
			</form>		
			
  <form id=Form1 name="Form1">
    <input type=hidden id="steelcompany" name="steelcompany[]" >
  </form>  			
              
		  </div>
		  
 <script> 

$(document).ready(function(){

	let timer2 = setTimeout(function(){  // 시작과 동시에 계산이 이뤄진다.
	
	}, 500)
	    
 
					
					$("#closeBtn").click(function(){    // 저장하고 창닫기	
					
					   savegrid() ; // grid를 배열로 form에 전달한다.  						
					   // grid 배열 form에 전달하기		
					   
					   // data저장을 위한 ajax처리구문
						$.ajax({
							url: "registsupplier_process.php",
							type: "post",		
							data: $("#Form1").serialize(),
							dataType:"json",
							success : function( data ){
								console.log( data);
							},
							error : function( jqxhr , status , error ){
								console.log( jqxhr , status , error );
							} 			      		
						   });		
					
						    window.close();
					     });	
					
					$("#insertBtn").click(function(){    // DATA 저장버튼 누름					

						$("#SelectWork").val('new');							
						   // grid 배열 form에 전달하기						    						    
	                    $("#mainFrm").submit(); 								 
					     });					
										
					$("#saveBtn").click(function(){      // DATA 저장버튼 누름
							// savegrid() ; // grid를 배열로 form에 전달한다.
  						
						    // grid 배열 form에 전달하기						    						    
	                        // $("#mainFrm").submit(); 								 
					     });
	
							 
							 
					 $("#deldataBtn").click(function(){    deldataDo(); });	  
					 $("#SelInsertDataBtn").click(function(){    SelInsertData(); });	
							 

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
					var steelcompany = <?php echo json_encode($steelcompany);?> ;			
					
					let row_count = count;
					const COL_COUNT = 2;
					
					const data = [];
					const columns = [];	 					
					
				if(count>0) {
					for (let i = 0; i < row_count; i += 1) {
					  const row = { name: i };
					  for (let j = 0; j < COL_COUNT; j += 1) {
						row[`steelcompany`] = steelcompany[i] ;
												
					  }
						data.push(row);
					}
				  
				const grid = new tui.Grid({
					  el: document.getElementById('grid'),
					  data: data,
					  bodyHeight: 400,
					  bodyWidth:500,
					  header: {
						    height: 60,				
					  },					
					   columns: [ 				   
						{
						  header: '공급처',
						  name: 'steelcompany',
						  sortingType: 'desc',
						  sortable: true,
						  width:250,
						  editor: 'text',	
						  align: 'center'
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
										  
				 // 셀 자동계산 
					 calculate.addEventListener('click', () => {
					  calculateit();
					});
												
				// grid 변경된 내용을 php 넘기기 위해 input hidden에 넣는다.
					function savegrid() {		
					// 납품회사 숫자
					
						// 부모창 select 옵션제거 제이쿼리 기법
						$("#supplier option", opener.document).remove();

					
							let steelcompany   =  new Array(); 		
								
					        // console.log(grid.getRowCount());	//삭제시 숫자가 정상적으로 줄어든다.
						     const MAXcount=grid.getRowCount() + 20 ;  // + 20 적용여부 확인 20개 데이터를 rowkey 영향으로 더 검색한다.						     
							 let pushcount=0;
							 let tmp='';
							 
							 for(i=0;i<MAXcount;i++) {      // grid.value는 중간중간 데이터가 빠진다. rowkey가 삭제/ 추가된 것을 반영못함.    
							    tmp = grid.getValue(i, 'steelcompany');
							    if( tmp != null ) {									
								    steelcompany.push(tmp);
									// 부모창에 적용해 주기 opener 활용기법
									$("#supplier", opener.document).append("<option value='" + tmp + "'>" +tmp + "</option>");

									} // end of else
								   									
								 }	
								 
								 console.log(steelcompany);
								 
								$('#steelcompany').val(steelcompany);				
								 
					   }	

					
// Cell 변경이 발생할때 마다 계산
		 function calculateit() {			 
					
				   }					

				 function ChangeData() {
					 calculateit();
				      // grid.setValue(0, 'description' , '');  					  
				 }	 	
				 
			 // console에 이벤트를 출력한다. 
					grid.on('editingFinish', ev => {
					ChangeData();  // 자료가 변경되면 다시 계산하는 루틴작성을 위한 연습
					console.log('check!', ev);					  
					});

					grid.on('mouseout', ev => {
					//  console.log('uncheck!', ev);
					  ChangeData();  // 자료가 변경되면 다시 계산하는 루틴작성을 위한 연습
					});

					 // grid.on('mouseout', ev => {
					// //  console.log('change onGridUpdated cell!', ev);
					  // ChangeData();  // 그리드가 뭔가 변경되었을때 감지함
					// }); 
					grid.on('focusChange', ev => {
					 ChangeData();  // 그리드가 뭔가 변경되었을때 감지함
					 console.log('change onGridUpdated cell!', ev);
					 }); 		

              function deldataDo()  {
				    var tmp = grid.getCheckedRowKeys();
					tmp.forEach(function(e){
                        grid.removeRow(e);
                     });					           
                   // grid.resetOriginData(data);			 // 데이터 update
                  //  grid.resetData(data);			 // 데이터 update
					// console.log(grid.getCheckedRowKeys());
			  }				  

            function SelInsertData()  {    // 선택한 데이터 이후에 삽입
				    var tmp = grid.getCheckedRowKeys();
					tmp.forEach(function(e){
					 appendRow(e+1);        // 함수를 만들어서 한줄삽입처리함.
					  console.log(e);
					});	
                  // grid.resetOriginData(data);			 // 데이터 update					
			      //  grid.resetData(data);			 // 데이터 update				 
			     }					 
				 
			function appendRow(index) {
						var newRow = {
							eventId: '',
							localEvent: '',
							copyControl: ''
								};
						if (index== null) { // 행(row) 추가(끝에)
							grid.appendRow(epgCleanRow, null);
							} else { // 끝이 아닐때는 행(row) 삽입 실행
									var optionsOpt = {
											at: index,
											extendPrevRowSpan: false,
											focus: false
											};
									grid.appendRow(newRow , optionsOpt);
									}       				
				}

			 
					const prependBtn = document.getElementById('prependBtn');		
					
					const appendedData = {					          
					};		
					
					 // InsertRow 1 (Before row)
					prependBtn.addEventListener('click', () => {						
						  grid.prependRow(appendedData, {
															  at: 1,
															  extendPrevRowSpan: true,
															 focus: true	});
						}); 
				

    }	 //	end of count	

}); // end of ready document


  </script>
    </div>
  </div>	 
</section>
</body>
</html>

