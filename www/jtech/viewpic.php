
<?php include $_SERVER['DOCUMENT_ROOT'] . '/jtech/load.php' ?>

<body>
<title> J-TECH 시공(전,후) 사진대장  </title>
<style>


<?php


session_start(); 

header('Content-Type: text/html; charset=utf-8');

$level= $_SESSION["level"];
$user_name= $_SESSION["name"];  

// 환경파일 읽어오기, 메인 table명, 사진파일저장 table명, 작업폴더명 기록읽기
$tmp_name = "./settings.ini";
$readIni = array();   // 환경파일 불러오기
$readIni = parse_ini_file($tmp_name,false);	

$tablename=$readIni['tablename'];
$table_picuploads=$readIni['table_picuploads'];
$workdir=$readIni['workdir'];

// common read

isset($_REQUEST["mode"])  ? $mode = $_REQUEST["mode"] : $mode=""; 
isset($_REQUEST["id"])  ? $id = $_REQUEST["id"] : $id=""; 
isset($_REQUEST["num"])  ? $id = $_REQUEST["num"] : $id=""; 
isset($_REQUEST["contents"])  ? $contents = $_REQUEST["contents"] : $contents=""; 
isset($_REQUEST["parent_id"])  ? $parent_id = $_REQUEST["parent_id"] : $parent_id=""; 
isset($_REQUEST["check"])  ? $check = $_REQUEST["check"] : $check=""; 
isset($_REQUEST["search"])  ? $search = $_REQUEST["search"] : $search=""; 
isset($_REQUEST["page"])  ? $page = $_REQUEST["page"] : $page=1; 



// 스케줄에서 띄울때는 네이바를 나타내지 않는다. navibar=1 이면 나타내지 않음
$navibar=$_REQUEST["navibar"];
  
 // $file_dir = './uploads/'; 
  
include "_request.php";

require_once("../lib/mydb.php");
$pdo = db_connect();
	 
if($num!=null && $num!=0)
{	  
	try{
		 $sql = "select * from jtechel.jtech where num=?";
		 $stmh = $pdo->prepare($sql);  
		 $stmh->bindValue(1, $num, PDO::PARAM_STR);      
		 $stmh->execute();            
		  
		 $row = $stmh->fetch(PDO::FETCH_ASSOC); 	
		  
		 include 'rowDB.php';  
		 
		 $piclist = $row["piclist"] ?? '{}'; // piclist 값이 없을 경우의 기본값

		 }catch (PDOException $Exception) {
		   print "오류: ".$Exception->getMessage();
		 }
}
	else   // 신규자료 등록일 경우
	{     
		$todate=date("Y-m-d");  // 현재일 저장   
		$regist_day=$todate;
	}


if($num != null && $num != 0) {
    $picsGroupedByItem = [];
    $tablename = 'jtech';

    // 모든 사진을 한 번의 쿼리로 가져오기
    $sql = "SELECT * FROM jtechel.picuploads WHERE tablename = '$tablename' AND parentnum = '$num'";

    try {
        $stmh = $pdo->query($sql);
        while ($row = $stmh->fetch(PDO::FETCH_ASSOC)) {
            $picsGroupedByItem[$row['item']][] = $row;
        }
    } catch (PDOException $Exception) {
        print "오류: " . $Exception->getMessage();
    }

    // 각 아이템별로 사진 개수 세기
    $picNum = isset($picsGroupedByItem['before']) ? count($picsGroupedByItem['before']) : 0;
    $MidpicNum = isset($picsGroupedByItem['mid']) ? count($picsGroupedByItem['mid']) : 0;
    $AfterpicNum = isset($picsGroupedByItem['after']) ? count($picsGroupedByItem['after']) : 0;

    // 사진명만을 포함하는 배열 생성
    $picData = isset($picsGroupedByItem['before']) ? array_column($picsGroupedByItem['before'], 'picname') : [];
    $MidpicData = isset($picsGroupedByItem['mid']) ? array_column($picsGroupedByItem['mid'], 'picname') : [];
    $AfterpicData = isset($picsGroupedByItem['after']) ? array_column($picsGroupedByItem['after'], 'picname') : [];
	
    // 목록 구성된 사진배열
    $picDataArr = isset($picsGroupedByItem['beforeArr']) ? array_column($picsGroupedByItem['beforeArr'], 'picname') : [];
    $MidpicDataArr = isset($picsGroupedByItem['midArr']) ? array_column($picsGroupedByItem['midArr'], 'picname') : [];
    $AfterpicDataArr = isset($picsGroupedByItem['afterArr']) ? array_column($picsGroupedByItem['afterArr'], 'picname') : [];
	
	$picIdx = isset($picsGroupedByItem['beforeArr']) ? array_column($picsGroupedByItem['beforeArr'], 'idx') : [];
	$MidpicIdx = isset($picsGroupedByItem['midArr']) ? array_column($picsGroupedByItem['midArr'], 'idx') : [];
	$AfterpicIdx = isset($picsGroupedByItem['afterArr']) ? array_column($picsGroupedByItem['afterArr'], 'idx') : [];
	
}

   
// 시공팀 배열로 가져오기      
$sql="select * from jtechel.member "; 					

try{  
   $stmh = $pdo->query($sql);               
   $worker_arr=array();

   while($row = $stmh->fetch(PDO::FETCH_ASSOC)) {	   
 			  array_push($worker_arr,$row["name"]);			 
	 } 	 
   } catch (PDOException $Exception) {
    print "오류: ".$Exception->getMessage();
}     

// var_dump($worker_arr);
sort($worker_arr);  // 오름차순으로 배열 정렬   
$worker_arr = array_unique($worker_arr);      
sort($worker_arr);  // 오름차순으로 배열 정렬  


if(!in_array($worker, $worker_arr))   // 배열값에 없으면 넣어준다
		array_push($worker_arr,$worker);	 // 마지막에 공백하나 넣기
   else
		array_push($worker_arr,"");	 // 마지막에 공백하나 넣기
   
 ?>
 
<style>

.rotated {
  transform: rotate(90deg);
  -ms-transform: rotate(90deg); /* IE 9 */
  -moz-transform: rotate(90deg); /* Firefox */
  -webkit-transform: rotate(90deg); /* Safari and Chrome */
  -o-transform: rotate(90deg); /* Opera */
}
  
.uploaded-image {
    width: 25vw; /* 화면의 4분의 1 가로폭 */
    max-width: 100%; /* 이미지가 컨테이너를 넘어가지 않게 함 */
    height: auto; /* 원본 비율 유지 */
}
  
</style>

<form id="board_form"  name="board_form"  method="post" enctype="multipart/form-data"  >

<div class="container-fluid">
	
	<div class="card justify-content-center">	  
			<div class="card-header text-center"> 	
				  <div class="row"> 	             			
					<div class="col-sm-2"> 	             			
					  <span class="badge bg-dark fs-3 text-center "> 제이테크(J-TECH) </span>			 			  
					</div>		
					<div class="col-sm-8"> 	             							  
					</div>		
					<div class="col-sm-2"> 	             							 
					</div>	
				 </div>		
				  <div class="row"> 	             			
					<div class="col-sm-2"> 	             							 
					</div>		
					<div class="col-sm-8"> 	             			
					  <span class="badge bg-secondary fs-1 text-center "> 시공(전,후) 사진대장  </span>			 			  
					</div>		
					<div class="col-sm-2"> 	             							 
					</div>	
				 </div>	
			</div>	
		 <div class="card-body justify-content-center">	 
			<div class="d-flex justify-content-center" >	 		 
				<table class="table" style="width:80%;">
					<tbody>
						<tr>
							<td class="text-center fs-3">공사(현장)명</td>
							<td class="text-left fs-3"><?=$workplacename?></td>
						</tr>
						<tr>
							<td class="text-center fs-3">시공완료일</td>
							<td class="text-left fs-3"><?=$doneday?></td>
						</tr>
					</tbody>
				</table>
		</div>
					
			
 <div class="d-flex" >	  
 
	<div class="card-header mt-2 mb-2 justify-content-center text-center"> 				
					<span class="badge bg-primary text-center fs-2 mt-2 mb-2"> 해당 목록별 시공사진 List </span>		&nbsp;  &nbsp;  &nbsp;  &nbsp;  
					
			
<!-- 수정된 HTML / PHP 코드 -->
<?php
$decodedPiclist = json_decode($piclist, true);
$counter = 1; 

if(intval($num) > 0) {
    foreach ($decodedPiclist as $column => $items) {
        if (is_array($items)) {
            foreach ($items as $item) {
                if ($item != '') {
                    echo '<div class="card">';
                    echo '<div class="card-body">';
                    echo '<div class="row">';
                    
                    echo '<div class="col-sm-1  fs-3 text-dark">' . $counter . '</div>';
                    echo '<div class="col fs-3 text-dark">' . htmlspecialchars($item) . '</div>'; // XSS 방지
                    
                    // 시공전
                    createImageInputAndButton($counter, 'beforeArr', 'secondary', $picDataArr);
                    // 시공중
                    createImageInputAndButton($counter, 'midArr', 'dark', $MidpicDataArr);
                    // 시공후
                    createImageInputAndButton($counter, 'afterArr', 'primary', $AfterpicDataArr);
                    
                    echo '</div></div></div>'; // Close divs
                    
                    $counter++;
                }
            }
        }
    }
}

function createImageInputAndButton($counter, $type, $btnColor, $picData) {    
 
		
	switch (trim($type))
	{
		case 'beforeArr' :
		   $msg = '전';
		   $color ='text-secondary' ;
		   break;
		case 'midArr' :
		   $msg = '중';
		   $color ='text-dark' ;
		   break;
		case 'afterArr' :
		   $msg = '후';
		   $color ='text-danger' ;
		   break;
		default:
		   $msg = '알 수 없음';
		   break;
	}
	echo '<div class="col text-center fs-4  border '.$color.'">';   
    echo ' 시공(' . $msg . ')</button>';
    echo '<div class="col mt-3 mb-5 border-1" id="'.$type.'Images_'.$counter.'"  >';    
    echo '</div>';
    echo '</div>';
}
?>

  <div class="d-flex p-2 pt-md-3 pb-md-3 mt-5 mb-5 " style="width:100%;">	 
 
  </div> 
	</div>
		</div>		
	</form>		
  </body>
</html>    
 
<script>

document.addEventListener("DOMContentLoaded", function() {
    let picIdx = <?php echo json_encode($picIdx); ?>;
    let MidpicIdx = <?php echo json_encode($MidpicIdx); ?>;
    let AfterpicIdx = <?php echo json_encode($AfterpicIdx); ?>;
    
    let picDataArr = <?php echo json_encode($picDataArr); ?>;
    let MidpicDataArr = <?php echo json_encode($MidpicDataArr); ?>;
    let AfterpicDataArr = <?php echo json_encode($AfterpicDataArr); ?>;

    ['beforeArr', 'midArr', 'afterArr'].forEach(type => {
        let currentData, currentIdx, itemType;

        switch (type) {
            case 'beforeArr':
                currentData = picDataArr;
                currentIdx = picIdx;
                itemType = 'before';
                break;
            case 'midArr':
                currentData = MidpicDataArr;
                currentIdx = MidpicIdx;
                itemType = 'mid';
                break;
            case 'afterArr':
                currentData = AfterpicDataArr;
                currentIdx = AfterpicIdx;
                itemType = 'after';
                break;
        }

        currentData.forEach((picName, index) => {
            if (currentIdx[index]) { // idx가 있을 경우에만
                let container = document.getElementById(`${type}Images_${currentIdx[index]}`);
                
                // Create the image element
                let img = document.createElement('img');
                img.src = './uploads/' + picName;
                img.classList.add('uploaded-image');
                img.id = itemType + 'Pic' + currentIdx[index];
                container.appendChild(img);

            }
        });
    });
});



// 클릭시 동작하도록 trigger
function SearchFristordEnter(){
    if(event.keyCode == 13){		
		$("#searchFirstordBtn").trigger("click");        
    }	
}	
// 클릭시 동작하도록 trigger
function SearchSecondordEnter(){
    if(event.keyCode == 13){		
		$("#searchSecondordBtn").trigger("click");        
    }	
}	
 
$(document).ready(function(){	
	
	$("#listBtn").click(function(){    
		  page = $("#page").val();
		  scale = $("#scale").val();
		  
		  location.href='list.php?page=' + page + '&scale=' + scale;
	 });	
		
	$("#estimateBtn").click(function(){    // 견적서 클릭하면 이동하기
			popupCenter('estimate.php?num=' + $("#num").val(), '견적서 작성하기', 1400, 950);
	 });	
		
	// 처음 실행한 후 한번 실행	
	  displayPictureLoad();		
		 

	window.delPicFn = function(index, picName, itemType) {
		// console.log(index, picName);

		if (confirm("한번 삭제한 자료는 복구할 방법이 없습니다.\n\n정말 삭제하시겠습니까?")) {
			$.ajax({
				url: 'delpic.php?picname=' + picName,
				type: 'post',
				data: $("board_form").serialize(),
				dataType: 'json',
			}).done(function(data) {
				const picname = data["picname"];

				let imgId, btnId, inputId;
				switch (itemType) {
					case 'before':
						imgId = 'beforePic' + index;
						btnId = 'delbeforePic' + index;						
						break;

					case 'mid':
						imgId = 'midPic' + index;
						btnId = 'delmidPic' + index;						
						break;

					case 'after':
						imgId = 'afterPic' + index;
						btnId = 'delafterPic' + index;						
						break;
						
					case 'beforeArr':
						imgId = 'beforeArrImages_' + index;
						btnId = 'delbeforeArrImages_' + index;						
						break;

					case 'midArr':
						imgId = 'midArrImages_' + index;
						btnId = 'delmidArrImages_' + index;						
						break;

					case 'afterArr':
						imgId = 'afterArrImages_' + index;
						btnId = 'delafterArrImages_' + index;						
						break;
				}

				$("#" + imgId).remove();
				$("#" + btnId).remove();
			});
		}
	}
				 
	// 새입력 누를시
	$("#newBtn").click(function(e) {	    
		 $("#mode").val('new');
		 location.href='write_form.php?mode=new';			
	});	 	 
	
	// 시공전 사진 멀티업로드	
	$("#upfile").change(function(e) {	    
		var item = 'before';
		FileProcess(item, '', this); // 'this'는 현재 선택된 입력 요소를 참조합니다.
	});	 
			
	// 시공 중간 사진 멀티업로드		
	$("#Midupfile").change(function(e) {	    
		var item = 'mid';
		FileProcess(item, '', this); 
	});	

	// 시공 후 사진 멀티업로드		
	$("#Afterupfile").change(function(e) {	    
		var item = 'after';
		FileProcess(item, '', this); 
	});
	 
 window.FileProcess = function(item, idx, inputElement) {  // 전역함수 선언
	
	$('#item').val(item);
	
	   //do whatever you want here
    	num = $("#num").val();
	
		if(Number(num)==0) {
			 if(confirm("사진을 저장하려면 자료를 생성해야 합니다.\n\n 지금 자료를 등록하시겠습니까?")) {
				 
				$("#mode").val('insert');  // 삽입모드로 변경함.
				// 자료 삽입/수정하는 모듈		  
				Fninsert();				 
				if($("#mode").val()=='insert')  
					   {					      
							  location.href='write_form.php?num=' + data ;	// 실행되면 수정모드가 됨.		
					   }
			   }
		   }
		  // 사진 서버에 저장하는 구간			  
		  //tablename 설정
		   $("#tablename").val('jtech');  
			// 폼데이터 전송시 사용함 Get form         
			var form = $('#board_form')[0];  	    
			// Create an FormData object          
			var data = new FormData(form); 				
						
			data.append('idx', idx);    			
				
			// 선택한 파일들을 FormData에 추가
			var files = inputElement.files;
			for (var i = 0; i < files.length; i++) {
				data.append('file[]', files[i]);
			}		

            // console.log('files : ', files);			

			// tmp='사진을 저장중입니다. 잠시만 기다려주세요.';		
			// $('#alertmsg').html(tmp); 			  
			// $('#myModal').modal('show'); 	

				$.ajax({
					enctype: 'multipart/form-data',  // file을 서버에 전송하려면 이렇게 해야 함 주의
					processData: false,    
					contentType: false,      
					cache: false,           
					timeout: 600000, 			
					url: "pic_insert.php",
					type: "post",		
					data: data,	
					dataType : 'json',
					success : function(response){
						
					 // console.log('response.status ', response.status);
					  if(response.status == "array") {							
							// Update the display with new images and delete buttons
							switch (item) {
								case 'beforeArr':
									AdddisplayImagesArray('#beforeArrImages_' + idx, response.filepaths, item);
									break;
								case 'midArr':
									AdddisplayImagesArray('#midArrImages_' + idx, response.filepaths, item);
									break;
								case 'afterArr':
									AdddisplayImagesArray('#afterArrImages_' + idx, response.filepaths, item);
									break;
							}							
							
							
						}
						
					  if(response.status == "basic") {
							// Update the display with new images and delete buttons
							switch (item) {
								case 'before':
									AdddisplayImagesMain('#displayBeforePicture', response.filepaths, item);
									break;
								case 'mid':
									AdddisplayImagesMain('#displayMidPicture', response.filepaths, item);
									break;
								case 'after':
									AdddisplayImagesMain('#displayAfterPicture', response.filepaths, item);
									break;
							}
						}
						
						// console.log('실행결과 ', data);
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

	}


window.displayImages = function(filepaths, type, counter) {  // 전역함수 선언
    let container;
    switch(type) {
        case 'beforeArr':
            container = $('#beforeImages_'+counter);
            break;
        case 'midArr':
            container = $('#midImages_'+counter);
            break;
        case 'afterArr':
            container = $('#afterImages_'+counter);
            break;
        default:
            console.error("Invalid type provided to displayImages:", type);
            return;
    } 

    container.empty(); // Clear the container

    for(let i = 0; i < filepaths.length; i++) {
        container.append('<img src="' + filepaths[i] + '" width="25vw" height="auto" alt="Image ' + (i+1) + '">');
    }
	
    // console.log('filepaths', filepaths);
}

	
			 
	$("#closeModalBtn").click(function(){ 
		$('#myModal').modal('hide');
	});
			
	$("#closeBtn").click(function(){    // 저장하고 창닫기	
		 });	
				
	$("#saveBtn").click(function(){      // DATA 저장버튼 누름
		var num = $("#num").val();  	
		
	  if( $("#workplacename").val() == '')
	  {  
	   alertmodal("현장명은 필수입력사항입니다.", 1500);
	  }
	  else
		{	
			// 결재상신이 아닌경우 수정안됨	 
			if(Number(num)>0) 
					   $("#mode").val('modify');     
					  else
						  $("#mode").val('insert');     
					  
			// 자료 삽입/수정하는 모듈		  
			Fninsert();	
		}
			
	 }); 
	 

	// 삽입/수정하는 모듈 
	function Fninsert() {	 
		   
	//  console.log($("#mode").val());    

	// 폼데이터 전송시 사용함 Get form         
	var form = $('#board_form')[0];  	    
	// Create an FormData object          
	var data = new FormData(form); 

	console.log(data);

	$.ajax({
		enctype: 'multipart/form-data',    // file을 서버에 전송하려면 이렇게 해야 함 주의
		processData: false,    
		contentType: false,      
		cache: false,           
		timeout: 600000, 			
		url: "insert.php",
		type: "post",		
		data: data,			
		// dataType:"text",  // text형태로 보냄
		success : function(data){
			//  console.log(data);
			// opener.location.reload();
			// window.close();	
			  // 메시지 창 띄우기  문구, 해당초
			  alertmodal("파일 저장중입니다. 잠시 기다려주세요.", 1500);			   

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
		var reporter = $("#reporter").val();    
		var approve = $("#approve").val();  
		var user_name = $("#user_name").val();  

		if( (user_name=='제이테크') || user_name=='김보곤') {   
		
			 if(confirm("한번 삭제한 자료는 복구할 방법이 없습니다.\n\n정말 삭제하시겠습니까?")) {
		   $("#mode").val('delete');     
			  
			$.ajax({
				url: "insert.php",
				type: "post",		
				data: $("#board_form").serialize(),
				dataType:"text",  // text형태로 보냄
				success : function( data ){			
							
				  // 순서가 중요함 이동할 주소가 먼저 나와야 함 (모달창보다 먼저 나와야 화면에 나타냄)
				  setTimeout(function() {
						location.href='http://j-techel.co.kr/jtech/list.php';	
				   }, 1500);
				  
				  // 메시지 창 띄우기  문구, 해당초
				  alertmodal("자료가 삭제되었습니다.", 1500);				
								
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
 

function displayPictureLoad() {    
    // 시공전 이미지 화면에 보여주기
    var picNum = "<?php echo $picNum; ?>";                     
    var picData = <?php echo json_encode($picData);?> ;   
    displayImagesMain('#displayBeforePicture', picNum, picData, 'before');

    // 시공중 이미지 화면에 보여주기
    var midPicNum = "<?php echo $MidpicNum; ?>";                     
    var midPicData = <?php echo json_encode($MidpicData);?> ;   
    displayImagesMain('#displayMidPicture', midPicNum, midPicData, 'mid');

    // 시공후 이미지 화면에 보여주기
    var afterPicNum = "<?php echo $AfterpicNum; ?>";                     
    var afterPicData = <?php echo json_encode($AfterpicData);?> ;   
    displayImagesMain('#displayAfterPicture', afterPicNum, afterPicData, 'after');
	
	console.log('picData', picData);
	console.log('midPicData', midPicData);
	
}

function displayImagesMain(containerId, picNum, picData, itemType) {
    $(containerId).empty(); // 현재 내용을 지워줍니다.

    for(var i = 0; i < picNum; i++) {
        var imgSrc = './uploads/' + picData[i];
		console.log(imgSrc);
        $(containerId).append('<img id="' + itemType + 'Pic' + i + '" src="' + imgSrc + '" style="width:100%; height:100%" class="mb-1 mt-1">');
        $(containerId).append('&nbsp;<button type="button" class="btn btn-outline-danger" id="del' + itemType + 'Pic' + i + '" onclick="delPicFn(\'' + i + '\',\'' + picData[i] + '\', \'' + itemType + '\')" ><ion-icon name="trash-bin-outline"></ion-icon></button>');
		
		//  console.log('&nbsp;<button type="button" class="btn btn-outline-danger" id="del' + itemType + 'Pic' + i + '" onclick="delPicFn(\'' + i + '\',\'' + picData[i] + '\', \'' + itemType + '\')" ><ion-icon name="trash-bin-outline"></ion-icon></button>');
    }
}

function AdddisplayImagesMain(containerId, filepaths, itemType) {
    // Get the starting index based on how many <img> tags already exist in the container
    var startingIndex = $(containerId).children('img').length;

    for (var i = 0; i < filepaths.length; i++) {
        var imgSrc = filepaths[i];
        var currentIndex = startingIndex + i;
        var uniqueId = itemType + 'Pic' + currentIndex; 
        
        $(containerId).append('<img id="' + uniqueId + '" src="' + imgSrc + '" style="width:100%; height:100%" class="mb-1 mt-1">');
		var cleanedPath = filepaths[i].replace('./uploads/', '');
		$(containerId).append('&nbsp;<button type="button" class="btn btn-outline-danger" id="del' + uniqueId + '" onclick="delPicFn(\'' + currentIndex + '\',\'' + cleanedPath + '\', \'' + itemType + '\')" ><ion-icon name="trash-bin-outline"></ion-icon></button>');

    }
}

function AdddisplayImagesArray(containerId, filepaths, itemType) {
    // 이미 컨테이너 내에 존재하는 <img> 태그의 수를 기반으로 시작 인덱스를 얻습니다.
    var startingIndex = $(containerId).children('img').length;

    for (var i = 0; i < filepaths.length; i++) {
        var imgSrc = filepaths[i];
        var currentIndex = startingIndex + i;
        var uniqueId = itemType + 'Images_' + currentIndex;

        // 이미지 추가
        $(containerId).append('<img id="' + uniqueId + '" src="' + imgSrc + '" style="width:100%; height:100%" class="mb-1 mt-1">');
        var cleanedPath = imgSrc.replace('./uploads/', '');

        // 삭제 버튼 추가
        var deleteButton = '<button type="button" class="btn btn-outline-danger" id="del' + uniqueId + '" onclick="delPicFn(\'' + currentIndex + '\',\'' + cleanedPath + '\', \'' + itemType + '\')"><ion-icon name="trash-bin-outline"></ion-icon></button>';
        $(containerId).append(deleteButton);
    }
}


	
 function del(href) 
     {
		 var level=Number($('#session_level').val());
		 if(level>2)
		     alert("삭제하려면 관리자에게 문의해 주세요");
		 else {
         if(confirm("한번 삭제한 자료는 복구할 방법이 없습니다.\n\n정말 삭제하시겠습니까?")) {
           document.location.href = href;
          } 
		 }

     } 


// 사진 회전하기
function rotate_image()
{	
	 var box = $('.imagediv');
	 var imgObj = new Image();
	 var imgObj2 = new Image();
	 imgObj.src = "<? echo $imgurl1; ?>" ; 
	 imgObj2.src = "<? echo $imgurl2; ?>" ; 
	 box.css('width','800px');
	 box.css('height','1000px');
	 box.css('margin-top','200px');
	 
	 if( imgObj.width > imgObj.height  ||  imgObj2.width > imgObj2.height)
	   {
			$('.before_work').addClass('rotated');
			$('.after_work').addClass('rotated');		
	   }

}

setTimeout(function() {
 // console.log('Works!');
 rotate_image();
}, 500);

function alertmodal(tmp, second)
{	
	$('#alertmsg').html(tmp); 			  
	$('#myModal').modal('show'); 	
	
	setTimeout(function() {
	$('#myModal').modal('hide');  
	}, second);		
	
}


</script>
