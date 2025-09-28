
function inputNumberFormat(obj) { 
obj.value = comma(uncomma(obj.value)); 
} 
function comma(str) { 
    str = String(str); 
    return str.replace(/(\d)(?=(?:\d{3})+(?!\d))/g, '$1,'); 
} 
function uncomma(str) { 
    str = String(str); 
    str = str.replace(/\./g, ''); 
    return Number(str.replace(/[^\d]+/g, '')); 
}

$(document).ready(function(){
	
					 $("#upload_file").change(function(){   
						   $("#SelectWork").val('uploadfile');
	                     //  tmp="./process.php";			
			             //  $("#vacancy").load(tmp);     							 
						   $("#mainFrm").submit();
					 });		
				 
					 $("#upload_fileplus").change(function(){   
						 $("#SelectWork").val('uploadfile_second');
							$("#mainFrm").submit();
					 });						  					 
					 
					 $("#SavesettingsBtn").click(function(){   
					    // 저장 취소 선택
							Swal.fire({ 
							       title: '환경파일 변경 후 내용저장', 
								   text: " 모든DATA에 새로운 환경변수가 적용됩니다. '\n 진행하시겠습니까?", 
								   icon: 'warning', 
								   showCancelButton: true, 
								   confirmButtonColor: '#3085d6', 
								   cancelButtonColor: '#d33', 
								   confirmButtonText: '저장', 
								   cancelButtonText: '취소' })
								   .then((result) => { if (result.isConfirmed) { 
								    $("#SelectWork").val('saveini'); 						 
							        $("#mainFrm").submit();  
								   
								   Swal.fire( '저장이 완료되었습니다.', '변경완료!', 'success' ) } })							
					 });		// 환경설정 저장클릭			  
							 
				 $("#EcountsendBtn").click(function(){   
					    // Ecount 화면 보이기
						// $("#EcountsendFrm").submit(); 						
						const ecounton= $("#Call_Ecount").val(); 
					if(ecounton != '0')
					   {
						$('#show_Ecount').css('display','inline');						
						$("#Call_Ecount").val('0'); 				
					   }
					  else
					   {
						$('#show_Ecount').css('display','none');						
						$("#Call_Ecount").val('1'); 				
					   }					   
						
					 });	
														 
							 
					 $("#deldataBtn").click(function(){    deldataDo(); });	
					  
					$("#testmode").change(function(){   
							console.log($("#testmode").val());
							
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
					var description = <?php echo json_encode($description);?> ;
					var material = <?php echo json_encode($material);?> ;
					var length = <?php echo json_encode($length);?> ;
					var width = <?php echo json_encode($width);?> ;
					var thickness = <?php echo json_encode($thickness);?> ;
					var weights = <?php echo json_encode($weights);?> ;
					var donenum = <?php echo json_encode($donenum);?> ;
					var donetime = <?php echo json_encode($donetime);?> ;
					var unitfee = <?php echo json_encode($unitfee_arr);?> ;
					var bendingNum = <?php echo json_encode($bendingNum);?> ;
					var bendingFee = <?php echo json_encode($bendingFee);?> ;
					var bendingAmount = <?php echo json_encode($bendingAmount);?> ;
					
					var paintingArea = <?php echo json_encode($paintingArea);?> ;
					var paintingChoice = <?php echo json_encode($paintingChoice);?> ;
					var paintingUnit = <?php echo json_encode($paintingUnit);?> ;
					var paintingAmount = <?php echo json_encode($paintingAmount);?> ;
					var afterwork = <?php echo json_encode($afterwork);?> ;
					var laserfee = <?php echo json_encode($laserfee);?> ;
					var unit_Amount = <?php echo json_encode($unit_Amount);?> ;
					var est_Amount = <?php echo json_encode($est_Amount);?> ;
					
					let row_count = count;
					const COL_COUNT = 12;
					
					const data = [];
					const columns = [];
					
				  if(count>0) {
					for (let i = 0; i < row_count; i += 1) {
					  const row = { name: i };
					  for (let j = 0; j < COL_COUNT; j += 1) {
						row[`description`] = description[i] ;						 						
						row['material'] = material[i] ;
						row['length'] = length[i] + 20 ;      // 20키움
						row[`width`] = width[i] + 20 ;        // 20키움
						row[`thickness`] = thickness[i] ;
						row[`weights`] = weights[i] ;
						row[`donenum`] = donenum[i] ;
						row[`donetime`] = donetime[i] ;
						row[`unitfee`] = unitfee[i] ;
						row[`bendingNum`] = bendingNum[i] ;
						row[`bendingFee`] = bendingFee[i] ;
						row[`bendingAmount`] = bendingAmount[i] ;
						
						row[`paintingArea`] = paintingArea[i] ;
						row[`paintingChoice`] = paintingChoice[i] ;
						row[`paintingUnit`] = paintingUnit[i] ;
						row[`paintingAmount`] = paintingAmount[i] ;
						
						row[`afterwork`] = afterwork[i] ;
						row[`laserfee`] = laserfee[i] ;
						row[`est_Amount`] = est_Amount[i] ;
						row[`unit_Amount`] = unit_Amount[i] ;

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
					  bodyHeight: 470,
					   columns: [ 				   
						{
						  header: 'Job구분',
						  name: 'description',
						  sortingType: 'desc',
						  sortable: true,
						  width:350,
						  editor: {
							type: CustomTextEditor,
							options: {
							  maxLength: 40
							}
						  },	 		
						  align: 'center'
						},
						{
						  header: '재질',
						  name: 'material',
					   	  width:100,						  
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
						  width:65,
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
					      width:60,						  
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
						  header: '두께(mm)',
						  name: 'thickness',
					      width:65,						  
						  // sortingType: 'desc',
						  // sortable: true,
						  editor: {
							type: CustomTextEditor,
							options: {
							  maxLength: 10
							}
						  }	, 		  
						  align: 'center'		  
						},		
						{
						  header: '중량(kg)',
						  name: 'weights',
					      width:60,						  
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
						  header: '수량',
						  name: 'donenum',
					      width:60,						  
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
						  header: '시간(초)',
						  name: 'donetime',
					      width:60,						  						  
						  // sortingType: 'desc',
						  // sortable: true ,
						  // editor: {
							// type: CustomTextEditor,
							// options: {
							  // maxLength: 10
							// }
						  // }	, 		  
						  align: 'center'		  
						},
						{
						  header: '재료비',
						  name: 'unitfee',
					      width:80,							  
						  // editor: {
							// type: CustomTextEditor,
							// options: {
							  // maxLength: 10
							// }
						  // }	, 		  
						  sortingType: 'desc',
						  sortable: true,
						  align: 'right'		  
						},		
						{
						  header: '레이져비',
						  name: 'laserfee',
					      width:80,							  
						  sortingType: 'desc',
						  sortable: true,
						  align: 'right'		  
						},						
						{
						  header: '절곡_칼수',
						  name: 'bendingNum',
				          width:70,							  
						  editor: {
							type: CustomTextEditor,
							options: {
							  maxLength: 10
							}
						  }	, 		  
						  // sortingType: 'desc',
						  // sortable: true,
						  align: 'center'		  
						},						
						{
						  header: '절곡_단가',
						  name: 'bendingFee',
				          width:70,							  
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
						  header: '절곡비',
						  name: 'bendingAmount',
				          width:80,							  
						  align: 'right'		  
						},						
						{
						  header: '도장면적(㎟)',
						  name: 'paintingArea',
						  width:80,	
						  editor: {
							type: CustomTextEditor,
							options: {
							  maxLength: 10
							}
						  }	, 		  
						  align: 'center'		  		  
						},						
						{
						  header: '도장면선택',
						  name: 'paintingChoice',
						  formatter: 'listItemText',
				          width:75,						  
						  editor: {
							type: 'select',
							options: {
							  listItems: [
								{ text: '양면', value: '1' },
								{ text: '한면', value: '2' }								
							  ]
							}
						  }	, 		  
						  align: 'center'		  		  
						},						
						{
						  header: '도장단가',
						  name: 'paintingUnit',
						  editor: {
							type: CustomTextEditor,
							options: {
							  maxLength: 10
							}
						  }	, 		  
						  align: 'right'		  		  
						},						
						{
						  header: '도장비',
						  name: 'paintingAmount',				  
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
						  header: '단위단가',
						  name: 'unit_Amount',
				          width:90,							  
						  align: 'right'		  
						},						
						{
						  header: '견적합',
						  name: 'est_Amount',
						  sortingType: 'desc',
						  sortable: true,
				          width:90,							  
						  align: 'right'		  
						}					
					  ],
					  rowHeaders: ['rowNum','checkbox'],
					  pageOptions: {
						useClient: false,
						perPage: 20
					  },
					});
				  
					
					const appendBtn = document.getElementById('appendBtn');		
					
					const appendedData = {
					  description : ' ',		     
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
					
// Cell 변경이 발생할때 마다 계산
		 function calculateit() {					 
					 let unitweight;
					 let length ;
					 let width ;	 
					 let set_donenum ;
					 let set_donetime ;
					 let set_thickness ;
					 let set_painting ;
					 let set_material ;
					 let set_materialfee ;
					 let set_afterwork ;					 
					 let set_weight ;
					 let set_laserfee ;
					 let set_bendingNum ;					 
					 let set_bendingFee ;
					 
					 let set_bendingAmount ;
					 let set_paintingArea ;
					 let set_paintingChoice ;
					 let set_paintingUnit ;
					 let set_paintingAmount ;
					 let paintingFee;
					 
					 let materialSum=0;
					 let laserSum=0;
					 let bendingSum=0;
					 let paintingSum=0;
					 let afterworkSum=0;
					 
					 
					 // var choice = new Object()
					 
					 // choice.text = '양면';
					 // choice.value = '1';

					 let amountval=0;   // 개당 견적금액 산출
					 let unitamountval=0;   // 단위단가 산출
					 let totalamountval=0;   // 견적총액 산출
					 
					 for(i=0;i<grid.getRowCount();i++) {
						 set_thickness = Number(grid.getValue(i, 'thickness')) ;   // 
						 length = Number(grid.getValue(i, 'length')) ;   // 20mm 치수키운 상태
						 width = Number(grid.getValue(i, 'width')) ;     // 20mm 치수키운 상태
						 set_donenum = Number(grid.getValue(i, 'donenum'));  						 
						 set_donetime = Number(grid.getValue(i, 'donetime'));  
						 set_bendingNum  = uncomma(grid.getValue(i, 'bendingNum'));  						 
						 set_bendingFee  = uncomma(grid.getValue(i, 'bendingFee'));  
						 
						 set_paintingArea = uncomma(grid.getValue(i, 'paintingArea'));  						 
						 set_paintingChoice = grid.getValue(i, 'paintingChoice');
						 set_paintingUnit = uncomma(grid.getValue(i, 'paintingUnit'));  						 
						 set_paintingAmount = uncomma(grid.getValue(i, 'paintingAmount'));  						 					
 						 
						 set_afterwork = uncomma(grid.getValue(i, 'afterwork'));
                         
                         set_material = grid.getValue(i, 'material');
						 tmp_material = set_material ; 					//두께 계산을 위해 임시저장
						 set_material = set_material.replace(/\s+/g, '');      //  white space symbol (공백, 탭, 개행) 을 제거
						// set_material = set_material.replace(/\./gi,'');  //  . 제거
						// set_material = set_material.replace(/[0-9]|T/gi,'');  //  . 제거
						 set_material = set_material.replace(/\=/g,'');  //  = 제거
						 
						 // 두께 다시 재계산
						// set_thickness = tmp_material.match(/[a-zA-Z]?T/gi) ;  
						 
						 set_thickness = Number(set_material.replace(/[a-zA-Z]|T/gi,''));  // 알파벳,  T 제거 
						 console.log(set_thickness);						 
							// set_thickness = set_thickness.replace(/[0-9]|T/gi,'');  //  . 제거
						 
						 // set_thickness = Number(set_thickness);  
						 // set_bendingFee 값이 0이면 화면에서 읽어옴
						     if(Number(set_bendingFee)<1)
						          set_bendingFee = Number($('#bendingFeePerUnit').val());	
							  
						 // set_paintingUnit 값이 0이면 화면에서 읽어옴
						     if(Number(set_paintingUnit)=='1')
						          set_paintingUnit = uncomma($('#PaintingFeePerUnit').val());								  
							  
						 // console.log(set_bendingFee );
						 // 재질별 계산 part
						    if(set_material=='PO' || set_material=='CR' || set_material=='EGI' || set_material=='ST') {
							    set_weight = Number($('#relativedensity').val());    // 재질별 무게 비중적용 계산
								set_materialfee = uncomma($('#iron_fee').val());  	 // 재료기준에 따른 재료비 산출부분
								set_laserfee = uncomma($('#laserfee_PO').val());     // 재료별 레이져 가공단가 변수저장
						   	 }
								  else if (set_material=='SUS' || set_material=='SS')
								  {
									  set_weight = Number($('#sus_relativedensity').val());
									  set_materialfee = uncomma($('#sus_fee').val());
									  set_laserfee = uncomma($('#laserfee_SUS').val());
								  }
										else {
											set_weight = Number($('#al_relativedensity').val());						 
											set_materialfee = uncomma($('#al_fee').val());	
										    set_laserfee = uncomma($('#laserfee_AL').val());
										}
 						 
						 unitweight = (set_weight* length * width * set_thickness) / 1000000;   //비중*길이*폭*두께 = 중량
						 unitweight = unitweight.toFixed(2);
						 // 재료비 산출
						 unitfee = Math.ceil(set_materialfee * set_donenum * unitweight) ;	
						 materialSum += unitfee;

						 // 레이져비용 공식(임시) 중량 * 시간 * 기준단가 -> 추후 수정함.						 
						 laserfee = Math.ceil(set_laserfee * set_donenum * set_donetime) ;	// 레이져 가공비
						 laserSum += laserfee;
								
						 // 절곡비용 계산
						 set_bendingAmount = set_bendingNum * set_bendingFee;
						 bendingSum += 	set_bendingAmount;	
								
						 //도장비용 산출			 
						  set_paintingUnit = uncomma($('#PaintingFeePerUnit').val());						 
						  if(set_paintingChoice=='2' && uncomma(set_paintingArea)>0)						   
             							   set_paintingUnit = set_paintingUnit / 2;  				    // 한면일때는 단가 /2 함.
                         set_paintingAmount = set_paintingArea * set_paintingUnit / 1000000 ; 			// ㎟ 단위 계산					  
                         set_paintingAmount = Math.ceil(set_paintingAmount);							  
						  paintingSum += set_paintingAmount;	
						  
						  // 후가공은 단순히 누계함.
						    afterworkSum += set_afterwork;	
									 
						 amountval = unitfee + laserfee + set_bendingAmount + set_paintingAmount + set_afterwork ;  // 5개항을 더해서 산출

						 unitamountval = amountval / set_donenum;
						 unitamountval = Math.ceil(unitamountval/10)*10; //원단위 절사
						 amountval = unitamountval * set_donenum;         // 품목별 금액은 원단위 버림으로 새로 계산함
						 
						 totalamountval += amountval;							 

						 grid.setValue(i, 'material', set_material);
						 grid.setValue(i, 'thickness', set_thickness);
						 grid.setValue(i, 'weights', unitweight);
						 grid.setValue(i, 'unitfee', unitfee.toLocaleString('en-US'));
						 grid.setValue(i, 'bendingNum', set_bendingNum.toLocaleString('en-US'));
						 grid.setValue(i, 'bendingFee', set_bendingFee.toLocaleString('en-US'));
						 grid.setValue(i, 'bendingAmount', set_bendingAmount.toLocaleString('en-US'));
						 grid.setValue(i, 'paintingArea', set_paintingArea.toLocaleString('en-US'));
				         grid.setValue(i, 'paintingChoice', set_paintingChoice);
						 grid.setValue(i, 'paintingUnit', set_paintingUnit.toLocaleString('en-US'));
						 grid.setValue(i, 'paintingAmount', set_paintingAmount.toLocaleString('en-US'));
						 grid.setValue(i, 'afterwork', set_afterwork.toLocaleString('en-US'));
						 grid.setValue(i, 'laserfee', laserfee.toLocaleString('en-US'));
						 grid.setValue(i, 'unit_Amount', unitamountval.toLocaleString('en-US'));		  		
						 grid.setValue(i, 'est_Amount', amountval.toLocaleString('en-US'));		  		

						 $('#materialSum').val(comma(materialSum));
						 $('#laserSum').val(comma(laserSum));
						 $('#bendingSum').val(comma(bendingSum));
						 $('#paintingSum').val(comma(paintingSum));
						 $('#afterworkSum').val(comma(afterworkSum));
					
					 }	 
					 $('#totalamount').val(totalamountval.toLocaleString('en-US'));				 
				   }	 
				 
				// cell data read
				// getValue(rowKey, columnName)	   
				  
				// cell data 변경 모듈

				 function ChangeData() {
					  calculateit();
				      // grid.setValue(0, 'description' , '');  					  
				 }	 	
				 
			 // console에 이벤트를 출력한다. 
					grid.on('editingFinish', ev => {
					//  console.log('check!', ev);
					  ChangeData();  // 자료가 변경되면 다시 계산하는 루틴작성을 위한 연습
					});

					grid.on('mouseout', ev => {
					//  console.log('uncheck!', ev);
					  ChangeData();  // 자료가 변경되면 다시 계산하는 루틴작성을 위한 연습
					});

					 // grid.on('mouseout', ev => {
					// //  console.log('change onGridUpdated cell!', ev);
					  // ChangeData();  // 그리드가 뭔가 변경되었을때 감지함
					// }); 
					 // grid.on('focusChange', ev => {
					 // // console.log('change onGridUpdated cell!', ev);
					  // ChangeData();  // 그리드가 뭔가 변경되었을때 감지함
					// }); 		

              function deldataDo()  {
				    var tmp = grid.getCheckedRowKeys();
					tmp.forEach(function(e){
                        grid.removeRow(e);
                     });
					                    
					console.log(grid.getCheckedRowKeys());
			  }				  
				 
	function testitgrid() {
// Json 결과값 연속배열로 실제값을 넣기 연구자료 주문서를 기준으로 일단 코딩한다.
      console.log.clear;
     let i;
	 for(i=0;i<grid.getRowCount();i++) 
						 console.log(Number(grid.getValue(i, 'thickness'))) ;   			 
				 
	} // end of function
				 
				 
				 
				 
				 
		} // end of grid						
});	 // end of readydocument