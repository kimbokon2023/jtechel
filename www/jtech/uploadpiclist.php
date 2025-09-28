<?php include $_SERVER['DOCUMENT_ROOT'] . '/load_header.php' ?>

<title> 사진대지 리스트 일괄등록 </title> 

<style>
/* 모바일 환경을 위한 스타일 */
@media (max-width: 767px) {
    .tui-grid-cell {
        font-size: 36px; /* 원하는 폰트 크기로 조절 */
    }

    /* 다른 그리드 관련 요소의 스타일도 여기에 추가할 수 있습니다. */
}
</style>

</head>

 <?php 
if (isset($_REQUEST["num"])) {
    $num = $_REQUEST["num"];
} else {
    $num = 0; // num 값이 없을 경우의 기본값
}
   
$sql="select * from jtechel.jtech where num=? ";
	
$num_Array=array();     
$piclist_Array=array();  


try {
    $sql = "select * from jtechel.jtech where num = ?";
    $stmh = $pdo->prepare($sql);
    $stmh->execute([$num]); // $num 값을 바인딩하여 쿼리 실행

    $row = $stmh->fetch(PDO::FETCH_ASSOC);
    $piclist = $row["piclist"] ?? '{}'; // piclist 값이 없을 경우의 기본값

} catch (PDOException $Exception) {
    print "오류: " . $Exception->getMessage();
}


if($chkMobile)      
       {
          $btn = " btn-lg ";	
          $fs = "fs-2"	  ;
          $fs_small = "fs-4"	  ;
		  $size = "40";
	   }
	   else
       {          
          $fs = "fs-5"	  ;
          $fs_small = "fs-5"	  ;
		  $size = "60";
	   }		   
	  
?>
	 
<body >


<form name="regform" id="regform"  method="post" >  
<input type="hidden" id="num" name="num" value="<?=$num?>" >
<div class="container-fluid">

<div class="card">    
<div class="card-body">    
<div class="d-flex mt-2 mb-2 justify-content-center">    
 <span class="badge bg-primary <?=$fs?> "> &nbsp; 사진대지 리스트 목록만들기 &nbsp; 		</span>&nbsp;&nbsp; 	&nbsp;&nbsp; 	
 <button  type="button" class="btn btn-secondary" id="savegridBtn"> 일괄등록 </button>	 &nbsp; 
 
</div> 
<div class="d-flex mt-2 mb-2 justify-content-center">   		
    <div class="input-group p-2 mb-2  justify-content-center">
		<span style="margin-left:20px;color:brown;" class=" <?=$fs?>" > ※ 펀치리스트를 입력해서 사진대지를 만드는 방식입니다. </span>
       </div>
</div> 
<div class="d-flex mt-2 mb-2 justify-content-center  <?=$fs?> ">  			   
	 <div id="grid" style="width:1000px;" class="tui-grid-cell <?=$fs?>">   </div>     
  
  <?php
  for($i=1;$i<=1;$i++)
     echo '<input id="col' . $i . '" name="col' . $i . '[]" type="hidden" >';
   ?>   
	 
	 </div>   
   </div> 	   
  </div> 
  </div> <!-- end of container -->
</form>   
   
<script>

$(document).ready(function(){
	
    const Row_COUNT = 1000;
    const COL_NAMES = 2;
    const column = Array.from({ length: COL_NAMES }, (_, i) => `col${i + 1}`);
    
    // PHP에서 가져온 piclist 데이터를 JavaScript 객체로 변환
    var piclistObj = {}; 
    try {
        piclistObj = JSON.parse('<?php echo addslashes($piclist); ?>');
    } catch (e) {
        console.error("JSON 파싱 오류: ", e);
    } 
    
    // 기존의 data 배열 생성 로직
    const data = Array.from({ length: Row_COUNT }, (_, i) => {
        const row = { name: i };
        column.forEach((col, index) => {
            if (index === 0) { // 첫 번째 열인 경우
                row[col] = i + 1; // i+1 값을 할당
            } else {
                // piclistObj에서 해당 row에 해당하는 데이터가 있으면 사용
                row[col] = piclistObj[col] && piclistObj[col][i] ? piclistObj[col][i] : '';
            }
        });
        return row;
    });

    console.log(data); // 수정된 데이터 출력


 const commonSettings = {
    // sortingType: 'desc',
    // sortable: true,
    editor: 'text',
    align: 'center',	
};

const columnsConfig = [
    { header: '고유번호(키)', name: 'col1', width: 120 },
    { header: '목록 리스트', name: 'col2', width: 880 , align: 'left' }
];


const columns = columnsConfig.map(config => ({ ...commonSettings, ...config }));

const grid = new tui.Grid({
    el: document.getElementById('grid'),
    data: data,
    bodyHeight: 650,	
    columns,
    columnOptions: {
        resizable: true
    },
    pageOptions: {
        useClient: false,
        perPage: 20
    }
});
	
	
var Grid = tui.Grid; // or require('tui-grid')
Grid.applyTheme('default', {
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

	
function savegrid() {
    let columns = {};
	const Cols = 2
    for(let i = 1; i <= Cols; i++) {
        columns[`col${i}`] = [];
    }
    
    const MAXcount = grid.getRowCount();
    for(let i = 0; i < MAXcount; i++) {        
            let colName = `col2`;
            let value = grid.getValue(i, colName);
			let secondcol = grid.getValue(i, 'col2');
            if(secondcol != null) {
                columns[colName].push(swapcommatopipe(value));
            }        
    }

    const dataToSend = {
        num: $("#num").val() ,
        data: columns
    };


	 $.ajax({
			url: "makepiclist.php",
			type: "post",		
		    data: JSON.stringify(dataToSend), // num과 columns를 모두 포함한 객체를 문자열로 변환
			dataType:"json",
			success : function( data ){
				console.log( data);						
				Swal.fire(
				  '등록완료',
				  '데이터가 성공적으로 등록되었습니다.',
				  'success'
				);
			},
			error : function( jqxhr , status , error ){
				console.log( jqxhr , status , error );
				Swal.fire(
				  '오류 발생',
				  '데이터 등록 중 오류가 발생했습니다. 다시 시도해주세요.',
				  'error'
						)
				}
		   });
			
		setTimeout(function() { 
		   self.close();
			window.opener.location.reload();  // 부모창 새로고침
		   }, 2000);		
	
		
}	


$("#savegridBtn").click(function(){  savegrid();   });	  


});


function swapcommatopipe(strtmp) {
    if (typeof strtmp !== 'string') {
        console.error('strtmp is not a string:', strtmp);
        return strtmp; // or you can return some default value or convert strtmp to string
    }
    
    let replaced_str = strtmp.replace(/,/g, '|');
    return replaced_str;	   
}



</script>

  </html>

</body>