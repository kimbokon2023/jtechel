<?php
 session_start();

 $level= $_SESSION["level"];
 $user_name= $_SESSION["name"];
 if(!isset($_SESSION["level"]) || $level>5) {
          /*   alert("관리자 승인이 필요합니다."); */
		 sleep(2);
	          header("Location:http://j-techel.co.kr/login/login_form.php"); 
         exit;
   }
  
// ctrl shift R 키를 누르지 않고 cache를 새로고침하는 구문....
header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

function conv_num($num) {
	$number = (float)str_replace(',', '', $num);
	return $number;
}

isset($_REQUEST["itemname"])  ? $itemname = $_REQUEST["itemname"] : $itemname="";   

if($itemname=="firstord")
   $itemtitle = array("원청", "담당자(PM)", "전화번호") ;
if($itemname=="secondord")
   $itemtitle = array("발주처", "담당자", "전화번호") ;
 

 ?>
 
 <!DOCTYPE HTML>
 <html>
 <head>
 <meta charset="UTF-8">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<link rel="stylesheet" href="https://uicdn.toast.com/tui.pagination/latest/tui-pagination.css" />
<script src="https://uicdn.toast.com/tui.pagination/latest/tui-pagination.js"></script>
<link rel="stylesheet" href="https://uicdn.toast.com/tui-grid/latest/tui-grid.css"/>
<script src="https://uicdn.toast.com/tui-grid/latest/tui-grid.js"></script>

<script src="https://code.highcharts.com/highcharts.js"></script>
 <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">   <!--날짜 선택 창 UI 필요 -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script> 
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" >
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.1/font/bootstrap-icons.css">
<script src="//cdn.jsdelivr.net/npm/alertifyjs@1.12.0/build/alertify.min.js"></script>
<link rel="stylesheet" href="//cdn.jsdelivr.net/npm/alertifyjs@1.12.0/build/css/alertify.min.css"/>
 <link rel="stylesheet" type="text/css" href="../../css/common.css">
 <link rel="stylesheet" type="text/css" href="../../css/steel.css"> 
 
 <script src="http://j-techel.co.kr/js/date.js"></script>  <!-- 기간을 설정하는 관련 js 포함 -->
<script src="http://j-techel.co.kr/common.js"></script> 
 
 <!-- 최초화면에서 보여주는 상단메뉴 -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" >
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.1/font/bootstrap-icons.css">

 <title> 검색 </title> 
 </head>
 
  
 <?php 

if(isset($_REQUEST["search"]))   //목록표에 제목,이름 등 나오는 부분
 $search=$_REQUEST["search"];
  
isset($_REQUEST["whichRadio"])  ? $whichRadio = $_REQUEST["whichRadio"] : $whichRadio="1";   
  
$mode="search";  
  
require_once("../../lib/mydb.php");
$pdo = db_connect();	
  
 // $find="firstord";	    //검색할때 고정시킬 부분 저장 ex) 전체/공사담당/건설사 등
 if(isset($_REQUEST["page"])) // $_REQUEST["page"]값이 없을 때에는 1로 지정 
 {
    $page=$_REQUEST["page"];  // 페이지 번호
 }
  else
  {
    $page=1;	 
  }
 
  $scale = 10;       // 한 페이지에 보여질 게시글 수
  $page_scale = 15;   // 한 페이지당 표시될 페이지 수  10페이지
  $first_num = ($page-1) * $scale;  // 리스트에 표시되는 게시글의 첫 순번.
 
	
$SettingDate = "num" ;

$common="   where itemname='$itemname' order by " . $SettingDate;
if($first_num>0)
     $a= $common . " desc  limit $first_num, $scale";    //내림차순
 else
	 $a= $common . " desc  ";    //내림차순 전체
 
$b= $common . " desc  ";    //내림차순 전체

if($search==""){
				 $sql="select * from jtechel.DB  " . $a; 					
				 $sqlcon = "select * from jtechel.DB  " . $b;   // 전체 레코드수를 파악하기 위함.					
	   }
	 else { 
				  // 각 필드별로 검색어가 있는지 쿼리주는 부분	                                                      							   
				  $sql ="select * from jtechel.DB  where   (item1 like '%$search%') or (item2 like '%$search%') or (item3 like '%$search%') or (item4 like '%$search%') or (item5 like '%$search%') or (item6 like '%$search%') or (item7 like '%$search%') or (item8 like '%$search%') or (item9 like '%$search%')  " ;
				  $sql .=" order by " . $SettingDate . " desc limit $first_num, $scale ";
				  $sqlcon ="select * from jtechel.DB  where   (item1 like '%$search%') or (item2 like '%$search%') or (item3 like '%$search%') or (item4 like '%$search%') or (item5 like '%$search%') or (item6 like '%$search%') or (item7 like '%$search%') or (item8 like '%$search%') or (item9 like '%$search%')  " ;
				  $sqlcon .="  order by " . $SettingDate . " desc  "; 
				  }   
  
  //  print $sql;
   
	 try{  
	  $allstmh = $pdo->query($sqlcon);         // 검색 조건에 맞는 쿼리 전체 개수
      $temp2=$allstmh->rowCount();  
	  $stmh = $pdo->query($sql);            // 검색조건에 맞는글 stmh
      $recCount=$stmh->rowCount();
	      
	  $total_row = $temp2;     // 전체 글수	    
         					 
     $total_page = ceil($total_row / $scale); // 검색 전체 페이지 블록 수
	 $current_page = ceil($page/$page_scale); //현재 페이지 블록 위치계산			 
   		 
			?>
		 
		 
<body>

<div class="container">  			
	 
  <form name="board_form" id="board_form"  method="post" action="list.php?mode=search&search=<?=$search?>&itemname=<?=$itemname?>&whichRadio=<?=$whichRadio?>">  

		<input type="hidden" id="page" name="page" value="<?=$page?>" size="5" > 	
		<input type="hidden" id="mode" name="mode" value="<?=$mode?>" size="5" > 	
		<input type="hidden" id="itemname" name="itemname" value="<?=$itemname?>" size="5" > 	
		
    <div class="d-flex mb-1 mt-1 justify-content-left align-items-center">   
		<?= $total_row ?> 개 자료  &nbsp;&nbsp;&nbsp;&nbsp;	
    </div>
    <div class="d-flex mb-1 mt-1 justify-content-center align-items-center">  

<?php
   if(isset($_SESSION["userid"]))
   {
  ?>
        <button type="button" id=writebtn class="btn btn-dark  btn-sm" > 등록 </button>  &nbsp;&nbsp;	
		
  <?php
   }
  ?> 
			  
		  
	   구분 :  &nbsp; 
	   <?php if($whichRadio=='1') { ?>
			<span class="text-dark"> 조회   </span>  &nbsp; 
			<input  type="radio" checked name=whichRadio value="1"> &nbsp;
			&nbsp;  <span class="text-primary"> 수정   </span>  &nbsp;  
			<input  type="radio"  name=whichRadio value="2">  	  &nbsp;  
			&nbsp;  <span class="text-danger"> 삭제   </span>  &nbsp;  
			<input  type="radio"  name=whichRadio value="3">  	  &nbsp;  
	   <?php } else if($whichRadio=='2')  { ?>
			<span class="text-dark"> 조회   </span>  &nbsp; 
			<input  type="radio"  name=whichRadio value="1"> &nbsp;
			&nbsp;  <span class="text-primary"> 수정   </span>  &nbsp;  
			<input  type="radio"  checked name=whichRadio value="2">  	  &nbsp;  
			&nbsp;  <span class="text-danger"> 삭제   </span>  &nbsp;  
			<input  type="radio"  name=whichRadio value="3">  	  &nbsp;  
		<?php } else { ?>
			<span class="text-dark"> 조회   </span>  &nbsp; 
			<input  type="radio"  name=whichRadio value="1"> &nbsp;
			&nbsp;  <span class="text-primary"> 수정   </span>  &nbsp;  
			<input  type="radio"   name=whichRadio value="2">  	  &nbsp;  
			&nbsp;  <span class="text-danger"> 삭제   </span>  &nbsp;  
			<input  type="radio" checked  name=whichRadio value="3">  	  &nbsp;  
		<?php } ?>			

	     &nbsp;  &nbsp;  
			   <input type="text" name="search" id="search" value="<?=$search?>" onkeydown="JavaScript:SearchEnter();" placeholder="검색어"> 
				<button type="button" id="searchBtn" class="btn btn-dark  btn-sm "  > 검색 </button>	&nbsp;&nbsp;
		</div>
	<div class="d-flex mb-1 mt-1 justify-content-center align-items-center">     
	   
      <div class="limit">
        <ul class="list-group">
          <li class="list-row list-row--header">
            <div class="list-cell list-cell--100 text-center">순번</div>
			 <?php
			    for($i=0;$i<count($itemtitle);$i++)
				{
				  print '<div class="list-cell list-cell--150 text-center"> ' . $itemtitle[$i] . '</div>  ';
                }
			   ?>				
          </li>	       
	 <?php
		  if ($page<=1)  
			$start_num=$total_row;    // 페이지당 표시되는 첫번째 글순번
		     else 
		      	$start_num=$total_row-($page-1) * $scale;	    

       $arr0 = array();
       $arr1 = array();
       $arr2 = array();
       $arr3 = array();
       $arr4 = array();
       $arr5 = array();
       $arr6 = array();
       $arr7 = array();
       $arr8 = array();
       $arr9 = array();
	   $counter=0;	   

	   while($row = $stmh->fetch(PDO::FETCH_ASSOC)) {

		  $num=$row["num"];
		  $item_arr = array($row["item1"],$row["item2"],$row["item3"],$row["item4"],$row["item5"],$row["item6"],$row["item7"],$row["item8"],$row["item9"]);
		  
		  array_push($arr0, $num);
		  array_push($arr1, $row["item1"]);
		  array_push($arr2, $row["item2"]);
		  array_push($arr3, $row["item3"]);
		  array_push($arr4, $row["item4"]);
		  array_push($arr5, $row["item5"]);
		  array_push($arr6, $row["item6"]);
		  array_push($arr7, $row["item7"]);
		  array_push($arr8, $row["item8"]);
		  array_push($arr9, $row["item9"]);
		  
		  $itemtitlecount = count($itemtitle);
 
		  
				?>
		    <li class="list-row">
				<a class="list-link" style="text-decoration:none;" href="#" onclick="sel_item(<?=$counter?>);">
             <div class="list-cell list-cell--100 text-center"><?=$start_num?>				</div>
			 <?php
			    for($i=0;$i<count($itemtitle);$i++)
				{
				  print '<div class="list-cell list-cell--150 text-center"> ' . $item_arr[$i] . '</div>  ';
                }
			   ?>            
			</a>
          </li>			
			    
		<?php
		  $start_num--;  
		  $counter++;	 
			 } 	   
  } catch (PDOException $Exception) {
  print "오류: ".$Exception->getMessage();
  }  
   // 페이지 구분 블럭의 첫 페이지 수 계산 ($start_page)
      $start_page = ($current_page - 1) * $page_scale + 1;
   // 페이지 구분 블럭의 마지막 페이지 수 계산 ($end_page)
      $end_page = $start_page + $page_scale - 1;  
 ?>
       </ul>
	   </div>
   </div>
  
 
<div class="row row-cols-auto mt-3 mb-5 justify-content-center align-items-center"> 
 <?php
 
 	if($page!=1 && $page>$page_scale){
              $prev_page = $page - $page_scale;    
              // 이전 페이지값은 해당 페이지 수에서 리스트에 표시될 페이지수 만큼 감소
              if($prev_page <= 0) 
              $prev_page = 1;  // 만약 감소한 값이 0보다 작거나 같으면 1로 고정
		      print '<button class="btn btn-outline-secondary  btn-sm" type="button" id=previousListBtn  onclick="javascript:movetoPage(' . $prev_page . ')"> ◀ </button> &nbsp;' ;              
            }
            for($i=$start_page; $i<=$end_page && $i<= $total_page; $i++) {        // [1][2][3] 페이지 번호 목록 출력
              if($page==$i) // 현재 위치한 페이지는 링크 출력을 하지 않도록 설정.
                print '<span class="text-secondary" >  ' . $i . '  </span>'; 
              else 
                   print '<button class="btn btn-outline-secondary btn-sm" type="button" id=moveListBtn onclick="javascript:movetoPage(' . $i . ')"> ' . $i . '</button> &nbsp;' ;     			
            }

            if($page<$total_page){
              $next_page = $page + $page_scale;
              if($next_page > $total_page) 
                     $next_page = $total_page;
                // netx_page 값이 전체 페이지수 보다 크면 맨 뒤 페이지로 이동시킴
				  print '<button class="btn btn-outline-secondary  btn-sm" type="button" id=nextListBtn onclick="javascript:movetoPage(' . $next_page . ')"> ▶ </button> &nbsp;' ; 
            }
            ?>         
</div>

     </div>   
 
	</form>	 

<br>
<br>
	 
</body>
</html>	 
	 
<script>

$(document).ready(function(){
	
		
	$("#writebtn").click(function() { 
	    
	   let itemname = "<?php echo $itemname; ?>" ;
	   
	   // // 폼데이터 전송시 사용함 Get form         
		// var form = $('#board_form')[0];  	    
		// // Create an FormData object          
		// var data = new FormData(form); 
		
		// console.log(data);   
		// console.log(itemname);   
	   
		  popupCenter('./write_form.php?itemname=' + itemname + '&search=' +  $("#search").val() , '입력/수정', 500, 300);
	});
    	
	$("#saveBtn").click(function() { 
		document.getElementById('board_form').submit();  // form의 검색버튼 누른 효과  
	});
		
	$("#searchBtn").click(function() { 
		document.getElementById('board_form').submit();  // form의 검색버튼 누른 효과  
	});
		
});

function movetoPage(page){ 	  
	  $("#page").val(page); 
	  $("#board_form").submit();  
}		
	
function SearchEnter(){
    if(event.keyCode == 13){
		document.getElementById('board_form').submit(); 
    }	
}	

// 선택하면 opener에게 전달해 준다.	
function sel_item(num){
				
	var arr0 = <?php echo json_encode($arr0);?> ;		
	var arr1 = <?php echo json_encode($arr1);?> ;		
	var arr2 = <?php echo json_encode($arr2);?> ;		
	var arr3 = <?php echo json_encode($arr3);?> ;		
	var arr4 = <?php echo json_encode($arr4);?> ;		
	var arr5 = <?php echo json_encode($arr5);?> ;		
	var arr6 = <?php echo json_encode($arr6);?> ;		
	var arr7 = <?php echo json_encode($arr7);?> ;		
	var arr8 = <?php echo json_encode($arr8);?> ;		
	var arr9 = <?php echo json_encode($arr9);?> ;		

	
	 let itemname = "<?php echo $itemname; ?>" ;
	 let choice = [];
	 
	 // itemname에 따라 opener 위치 지정함 (여러개에 응용하기 위함)
	 if(itemname == 'firstord')
	   {
		  choice.push('#firstord');
		  choice.push('#firstordman');
		  choice.push('#firstordmantel');
	   }
	 if(itemname == 'secondord')
	   {
		  choice.push('#secondord');
		  choice.push('#secondordman');
		  choice.push('#secondordmantel');
	   }
	
	// console.log(num);
	// console.log(arr1);
	// console.log(arr2);
	// console.log(arr3);
	
	// radio 버튼 체크된 값 받기
	let whichRadio = $('input[name="whichRadio"]:checked').val();
	
	console.log(whichRadio);
		
	// 조회 라디오버튼일 경우
  	if(whichRadio=='1') {
		$(choice[0], opener.document).val(arr1[num]);	
		$(choice[1], opener.document).val(arr2[num]);	
		$(choice[2], opener.document).val(arr3[num]);	
		self.close();
	}
	else if(whichRadio=='2')   // 수정일 경우
	{
		
	   let itemname = '<?php echo $itemname; ?>';	   
	      
	   let tag = '';	   
		   for (i=0;i<9;i++)
		   {
			       tag += '&item' + (i+1) + '=' ;
					  switch (i) {
						 case 0 :
							tag += arr1[num];
							break;			
						 case 1 :
							tag += arr2[num];
							break;			
						 case 2 :
							tag += arr3[num];
							break;			
						 case 3 :
							tag += arr4[num];
							break;			
						 case 4 :
							tag += arr5[num];
							break;			
						 case 5 :
							tag += arr6[num];
							break;			
						 case 6 :
							tag += arr7[num];
							break;			
						 case 7 :
							tag += arr8[num];
							break;			
						 case 8 :
							tag += arr9[num];
							break;		
						  
					  }
		   }
	      //  console.log('레코드 번호 : ' + arr0[num] );
	      // arr0[num] => 고유 레코드 num 보관
		  popupCenter('./write_form.php?itemname=' + itemname + '&num=' + arr0[num] + '&mode=modify&search=' +  $("#search").val() + tag , '수정', 500, 300);	
		
	}
	
	else if(whichRadio=='3')   // 삭제일 경우
	{ 
		// console.log( arr0[num]);
			
		if(confirm("한번 삭제한 자료는 복구할 방법이 없습니다.\n\n정말 삭제하시겠습니까?")) {
		   $("#mode").val('delete');     
			  
			$.ajax({
				url: "insert.php?num=" + arr0[num] + "&mode=delete" ,
				type: "post",		
				data: '',
				dataType: "text",  // text형태로 보냄
				success : function( data ){		
				location.reload();
				// console.log(data);
				
				},
				error : function( jqxhr , status , error ){
					console.log( jqxhr , status , error );
				} 			      		
			   });	
			 }		   
	
		
	}
}	


</script>
