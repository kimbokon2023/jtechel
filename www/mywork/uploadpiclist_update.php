<?php include $_SERVER['DOCUMENT_ROOT'] . '/load_header.php' ?>

<title> 사진대장 리스트 일괄등록 </title> 

<style>
/* 모바일 환경을 위한 스타일 */
@media (max-width: 767px) {
    .tui-grid-cell {
        font-size: 36px; /* 원하는 폰트 크기로 조절 */
    }

    /* 다른 그리드 관련 요소의 스타일도 여기에 추가할 수 있습니다. */
}

.toastui-editor-contents {
  font-size: 25px!important;
}
</style>

</head>

 <?php 
if (isset($_REQUEST["num"])) {
    $num = $_REQUEST["num"];
} else {
    $num = 0; // num 값이 없을 경우의 기본값
}
   
$sql="select * from jtechel.mywork where num=? ";
	
$num_Array=array();     
$piclist_Array=array();  


try {
    $sql = "select * from jtechel.mywork where num = ?";
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
<div class="container">

<div class="card p-2">    
<div class="card-body">    
<div class="d-flex mt-2 mb-2 justify-content-center">    
 <span class="badge bg-primary <?=$fs?> text-center"> &nbsp; 사진대지 리스트 목록만들기 &nbsp; 		</span>&nbsp;&nbsp; 	&nbsp;&nbsp; 	
 <button  type="button" class="btn btn-secondary" id="saveBtn"> 일괄등록 </button>	 &nbsp; 
 
</div> 
<div class="row mt-2 mb-2 justify-content-center">   		
    <div class="input-group p-2 mb-2  justify-content-center">
		<span style="margin-left:20px;color:brown;" class=" <?=$fs?>" > ※ 펀치리스트를 입력해서 사진대지를 만드는 방식입니다. </span>
       </div>
</div> 
<div class="table-responsive">
	<table class="table table-bordered" id="dynamicTable">
		<thead>
			<tr>
				<th style="width:15%;">고유(키)</th>
				<th style="width:65%;">사진대지 목록 리스트</th>
				<th>작업</th>
			</tr>
		</thead>
		<tbody>
		</tbody>
	</table>

</div>
  	 
	 </div>   
   </div> 	   
  </div> 
  </div> <!-- end of container -->
</form>   
   
<script>
$(document).ready(function() {
    var i = 1;

    // '+' 버튼 이벤트 핸들러: 현재 행 바로 아래에 새 행 추가
    $(document).on('click', '.add', function() {
        var currentRow = $(this).closest('tr');
        var rowIndex = currentRow.index(); // 현재 행의 인덱스를 구합니다.
        var newRow = '<tr>' +
            '<td><input type="text" name="col1[]" class="form-control" value="' + (rowIndex + 2) + '" /></td>' + // 인덱스 + 2를 고유키로 설정
            '<td><input type="text" name="col2[]" class="form-control" /></td>' +
            '<td><button type="button" class="btn btn-success add">+</button> ' +
            '<button type="button" class="btn btn-danger remove">-</button></td>' +
        '</tr>';
        $(newRow).insertAfter(currentRow);
    });

    // '-' 버튼 이벤트 핸들러: 현재 행 삭제
    $(document).on('click', '.remove', function() {
        $(this).closest('tr').remove();
    });
	
    var piclistObj = {}; 
    try {
        piclistObj = JSON.parse('<?php echo addslashes($piclist); ?>');
    } catch (e) {
        console.error("JSON 파싱 오류: ", e);
    }

    // Row_COUNT를 piclistObj의 col2 배열 길이에 따라 동적으로 설정
    const Row_COUNT = piclistObj.col2 ? piclistObj.col2.length : 0;
    const COL_NAMES = 2;
    const column = Array.from({ length: COL_NAMES }, function(_, i) { return 'col' + (i+1); });

    // 데이터가 없는 경우에만 초기 행 추가
    if (!piclistObj.col2 || piclistObj.col2.length === 0) {
        $('#dynamicTable tbody').append('<tr id="row1">' +
            '<td><input type="text" name="col1[]" class="form-control"  value="1" /></td>' +
            '<td><input type="text" name="col2[]" class="form-control" /></td>' +
            '<td><button type="button" class="btn btn-success add">+</button></td>' +
        '</tr>');
    }	
		
    const data = Array.from({ length: Row_COUNT }, function(_, i) {
        var row = {};
        column.forEach(function(col, index) {
            row[col] = (piclistObj[col] && piclistObj[col][i] ? piclistObj[col][i] : '');
        });
        return row;
    });

    data.forEach(function(row, index) {
        // col2에 값이 있는 경우에만 행을 추가합니다.
        if(row.col2) {
            $('#dynamicTable tbody').append('<tr>' +
                '<td><input type="text" name="col1[]" class="form-control" value="' + (index + 1) + '" /></td>' +
                '<td><input type="text" name="col2[]" class="form-control" value="' + row.col2 + '" /></td>' +
                '<td><button type="button" class="btn btn-success add">+</button> ' +
                '<button type="button" class="btn btn-danger remove">-</button></td>' +
            '</tr>');
        }
    });

function savegrid() {
    let columns = {
        col1: [],
        col2: []
    };

    // 각 행에 대해 반복하여 데이터 수집
    $('#dynamicTable tbody tr').each(function() {
        var col1 = $(this).find('input[name="col1[]"]').val();
        var col2 = $(this).find('input[name="col2[]"]').val();

        if (col1 && col2) {
            columns.col1.push(col1);
            columns.col2.push(col2);
        }
    });

    const dataToSend = {
        num: $("#num").val(),
        data: columns
    };

    $.ajax({
        url: "makepiclist.php",
        type: "post",
        data: JSON.stringify(dataToSend),
        dataType: "json",
        success: function(data) {
            console.log(data);
            Swal.fire(
                '등록완료',
                '데이터가 성공적으로 등록되었습니다.',
                'success'
            );
        },
        error: function(jqxhr, status, error) {
            console.log(jqxhr, status, error);
            Swal.fire(
                '오류 발생',
                '데이터 등록 중 오류가 발생했습니다. 다시 시도해주세요.',
                'error'
            );
        }
    });

    setTimeout(function() {
        self.close();
        window.opener.location.reload(); // 부모창 새로고침
    }, 2000);
}


$("#saveBtn").click(function(){  savegrid();   });	  


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