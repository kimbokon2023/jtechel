
<?php 
// 환경별 기본 URL 설정
require_once '../config/environment.php';
include 'load.php' 
?>

  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">


<body>
<title> 오성이엘 시공 사진대장  </title>
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
$navibar = isset($_REQUEST["navibar"]) ? $_REQUEST["navibar"] : '';
  
 // $file_dir = './uploads/'; 
  
include "_request.php";

require_once("../lib/mydb.php");
$pdo = db_connect();
	 
if($num!=null && $num!=0)
{	  
	try{
		 $sql = "select * from jtechel.mywork where num=?";
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
	
    // 모든 사진을 한 번의 쿼리로 가져오기
    $sql = "SELECT * FROM jtechel.picuploads_mywork WHERE tablename = '$tablename' AND parentnum = '$num'";

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
	margin-top:10px;
}
  
</style>

<form id="board_form"  name="board_form"  method="post" enctype="multipart/form-data"  >

<div class="container-fluid">	
<div class="card justify-content-center">	  
			<div class="card-header text-center"> 	
				  <div class="row mt-2 mb-2 justify-content-center align-items-center"> 	             			
					  <span class="fs-5 text-center me-2"> 시공 사진  </span>							
					</div>							
					<div class="row mt-2 mb-2 justify-content-center align-items-center"> 	             			
						<button type="button" class="btn btn-outline-dark btn-sm" title="Excel형식으로 Export합니다." onclick="location.href='excelform_picture.php?num=<?=$num?>'" >Excel 저장</button>					  
					</div>
			 </div>	
		</div>	
		 <div class="card-body justify-content-center">	 		
		 
		<div class="row table-reponsive justify-content-center mt-3 mb-3" >	 		 
		<table class="table table-bordered" >
			<tbody>
				<tr>
					<td class="col text-center ">공사(현장)명</td>
					<td class="col text-left "><?=$workplacename?> &nbsp;</td>
				</tr>
				<tr>
					<td class="col text-center">시공완료일 </td>
					<td class="col text-left "> <?=$doneday?> &nbsp;</td>
				</tr>
			</tbody>
		</table>
		</div>
	</div>
	</div>
<div class="card justify-content-center">	  
	<div class="card-header text-center"> 		 		
			<span class="text-center fs-6 mt-2 mb-3"> 목록별 시공사진 </span>				   
			
<!-- 수정된 HTML / PHP 코드 -->
<?php
$decodedPiclist = json_decode($piclist, true);
$counter = 1; 

if(intval($num) > 0 && isset($decodedPiclist['col2'])) {
    foreach ($decodedPiclist['col2'] as $item) {
        if ($item != '') {
                    echo '<div class="card">';
                    echo '<div class="card-body">';
                    echo '<div class="row">';
                    
                    echo '<div class="col-sm-1 text-dark">' . $counter . '</div>';
                    echo '<div class="col text-dark">' . htmlspecialchars($item) . '</div>'; // XSS 방지
                    
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

function createImageInputAndButton($counter, $type, $btnColor, $picData) {    
 
		
	switch (trim($type))
	{
		case 'beforeArr' :
		   $msg = '전';
		   $color ='text-secondary' ;
		   break;
		case 'midArr' :
		   $msg = '중';
		   $color ='text-primary' ;
		   break;
		case 'afterArr' :
		   $msg = '후';
		   $color ='text-danger' ;
		   break;
		default:
		   $msg = '알 수 없음';
		   break;
	}
	echo '<div class="col text-center  border '.$color.'">';   
    echo ' 시공(' . $msg . ')</button>';
    echo '<div class="col mt-3 mb-5 border-1" id="'.$type.'Images_'.$counter.'"  >';    
    echo '</div>';
    echo '</div>';
}
?>

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



 
$(document).ready(function(){	
	 
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
	
			 
	$("#closeModalBtn").click(function(){ 
		$('#myModal').modal('hide');
	});
			
	$("#closeBtn").click(function(){    // 저장하고 창닫기	
		 });	
				


}); // end of ready document
 
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


</script>
