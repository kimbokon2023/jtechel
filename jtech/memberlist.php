 <?php
 session_start();

$level= $_SESSION["level"];
 if(!isset($_SESSION["level"]) || $level>2) {
          /*   alert("관리자 승인이 필요합니다."); */
		 sleep(2);
         header ("Location:http://j-techel.co.kr/login/logout.php");
         exit;
   }   
 $DB = "member";   
	
 ?>
  
<?php include $_SERVER['DOCUMENT_ROOT'] . '/jtech/load.php' ?>
 
  
<title> 회원관리 </title> 

    <style>
        .table-hover tbody tr:hover {
            cursor: pointer;
        }
    </style> 
 
 </head>
 
<?php
  
require_once("../lib/mydb.php");
  $pdo = db_connect();
	 
 if(isset($_REQUEST["page"])) // $_REQUEST["page"]값이 없을 때에는 1로 지정 
    $page=(int)$_REQUEST["page"];  // 페이지 번호
  else
    $page=1;	 
	 
  if(isset($_REQUEST["mode"]))
     $mode=$_REQUEST["mode"];
  else 
     $mode="";

       if(isset($_REQUEST["search"]))   // search 쿼리스트링 값 할당 체크
         $search=$_REQUEST["search"];
       else 
         $search="";
     
       if(isset($_REQUEST["find"]))   //목록표에 제목,이름 등 나오는 부분
         $find=$_REQUEST["find"];
       else
         $find="";
	  

  $scale = 50;       // 한 페이지에 보여질 게시글 수
  $page_scale = 10;   // 한 페이지당 표시될 페이지 수
  $first_num = ($page-1) * $scale;  // 리스트에 표시되는 게시글의 첫 순번.	 
		  
	if(!empty($search)) {
		$sql = "select * from jtechel." . $DB . " where part = 'jtech' and (id like '%$search%' or name like '%$search%' or nick like '%$search%') order by id desc limit $first_num, $scale";
		$sqlcon = "select * from jtechel." . $DB . " where part = 'jtech' and (id like '%$search%' or name like '%$search%' or nick like '%$search%') order by id desc";
	} else {
		$sql = "select * from jtechel." . $DB . " where part = 'jtech' order by id desc limit $first_num, $scale";
		$sqlcon = "select * from jtechel." . $DB . " where part = 'jtech' order by id desc";
	}

   // if($mode=="search"){
         // if(!$search) {
				// $sql ="select * from jtechel." . $DB . " order  by id desc  limit $first_num, $scale"; 
				// $sqlcon ="select * from jtechel." . $DB . " order  by id desc" ;
             // }
              // $sql="select * from jtechel." . $DB . " where id like '%$search%' or name like '%$search%'  or nick like '%$search%'   order by id desc  limit $first_num, $scale";
              // $sqlcon="select * from jtechel." . $DB . " where id like '%$search%' or name like '%$search%'  or nick like '%$search%'   order by id desc";
       // } else {
              // $sql="select * from jtechel." . $DB . " order  by id desc limit $first_num, $scale";
              // $sqlcon="select * from jtechel." . $DB . " order  by id desc ";
       // }


  // 전체 레코드수를 파악한다.
 try{  
	$allstmh = $pdo->query($sqlcon);         // 검색 조건에 맞는 쿼리 전체 개수
	$temp2=$allstmh->rowCount();  
	$stmh = $pdo->query($sql);            // 검색조건에 맞는글 stmh
	$temp1=$stmh->rowCount();

	$total_row = $temp2;     // 전체 글수	  		
					 
	$total_page = ceil($total_row / $scale); // 검색 전체 페이지 블록 수
	$current_page = ceil($page/$page_scale); //현재 페이지 블록 위치계산		
		 	 
 ?>
 
	 
<body>

<? include 'navbarsub.php'; ?>

<form name="board_form" id="board_form"  method="post" action="memberlist.php?mode=search&search=<?=$search?>">

<div class="container justify-content-center">  

  <input type="hidden" id="page" name="page" value="<?=$page?>"  > 
  
  
 <div class="d-flex mt-2 mb-1 justify-content-center">  
       <span class="badge bg-secondary text-white fs-4 " > &nbsp; 회원 정보관리 &nbsp;</span>
  </div>	 
 <div class="d-flex mt-3 mb-3 justify-content-center">  
       <span class="text-primary fs-5 " > 관리자는 레벨 1, 시공소장은 기본 레벨 4 지정</span>
  </div>	 
 
 <div class="d-flex mt-1 mb-1 justify-content-center">  
 
    <div class="input-group p-2 mb-2 justify-content-center">	  
	   <button type="button"   class="btn btn-dark btn-sm" onclick="popupCenter('member_write_form.php?id=null', '회원 등록', 600, 750);return false;" > 등록 </button>		&nbsp;&nbsp;
	   <input type="text" name="search" id="search" value="<?=$search?>" size="30" onkeydown="JavaScript:SearchEnter();" placeholder="검색어"> 
		<button type="button" id="searchBtn" class="btn btn-dark"  > 검색 </button>	
		
		</div>
       </div>
 
<div class="row d-flex"  >
<table class="table table-hover">
   <thead class="table-secondary" >
	    <tr>
			 <th class="text-center" > ID    </th>
			 <th class="text-center" > P/W   </th>
			 <th class="text-center" > 이름   </th>
			 <th class="text-center" > 레벨   </th>			 			 
		 </tr>
       </thead>
	<tbody>  
	
<?php  
  if ($page==1)  
    $start_num=$total_row;    // 페이지당 표시되는 첫번째 글순번
  else 
    $start_num=$total_row-($page-1) * $scale;
			 
 while($row = $stmh->fetch(PDO::FETCH_ASSOC)) {  
  $item_id=$row["id"];  
  $item_pass=$row["pass"];  
  $item_name=$row["name"];  
  $item_level=$row["level"];  
  $item_part=$row["part"];  
  $item_hp=$row["hp"];  
  $item_numorder=$row["numorder"];  
 ?>
	<tr onclick="redirectToView('<?=$item_id?>', '<?=$page?>', '<?=$DB?>')">  
		<td class="text-center" >  <?= $item_id ?>      </td>
		<td class="text-center" >  <input type="password" name="password" value="<?= $item_pass ?>" disabled>    </td>
		<td class="text-center" >  <?= $item_name ?>   </td>	   
		<td class="text-center" >  <?= $item_level ?>      </td>	  
	</tr>      
  
 <?php
	$start_num--;
    }
  } catch (PDOException $Exception) {
  print "오류: ".$Exception->getMessage();
  }  

   // 페이지 구분 블럭의 첫 페이지 수 계산 ($start_page)
      $start_page = ($current_page - 1) * $page_scale + 1;
   // 페이지 구분 블럭의 마지막 페이지 수 계산 ($end_page)
      $end_page = $start_page + $page_scale - 1;
  
 ?>  
  	  </tbody>
		  </table>  
</div>
<div class="row row-cols-auto mt-5 justify-content-center align-items-center"> 
 <?php
	if($page!=1 && $page>$page_scale){
              $prev_page = $page - $page_scale;    
              // 이전 페이지값은 해당 페이지 수에서 리스트에 표시될 페이지수 만큼 감소
              if($prev_page <= 0) 
              $prev_page = 1;  // 만약 감소한 값이 0보다 작거나 같으면 1로 고정
		      print '<button class="btn btn-outline-secondary btn-sm" type="button" id=previousListBtn  onclick="javascript:movetoPage(' . $prev_page . ')"> ◀ </button> &nbsp;' ;              
            }
            for($i=$start_page; $i<=$end_page && $i<= $total_page; $i++) {        // [1][2][3] 페이지 번호 목록 출력
              if($page==$i) // 현재 위치한 페이지는 링크 출력을 하지 않도록 설정.
                print '<span class="text-secondary" >  ' . $i . '  </span>'; 
              else 
                   print '<button class="btn btn-outline-secondary btn-sm" type="button" id=moveListBtn onclick="javascript:movetoPage(' . $i . ')">' . $i . '</button> &nbsp;' ;     			
            }

            if($page<$total_page){
              $next_page = $page + $page_scale;
              if($next_page > $total_page) 
                     $next_page = $total_page;
                // netx_page 값이 전체 페이지수 보다 크면 맨 뒤 페이지로 이동시킴
				  print '<button class="btn btn-outline-secondary btn-sm" type="button" id=nextListBtn onclick="javascript:movetoPage(' . $next_page . ')"> ▶ </button> &nbsp;' ; 
            }
            ?>         
   </div>
      
   
</div>
   

</form>   

</body>
</html>

<script>


function redirectToView(id, page, db) {
	popupCenter('member_write_form.php?id=' + id, '회원정보 수정', 600, 750);
    // window.location.href = "write_form.php?num=" + num + "&page=" + page + "&DB=" + db;
}

$(document).ready(function(){
	
$("#searchBtn").click(function(){ 	
	  // page 1로 초기화 해야함
     $("#page").val('1');
	 document.getElementById('board_form').submit();    
 
 });	
		
	
	movetoPage = function(page){ 	  
	  $("#page").val(page); 
      // var echo="<?php echo $partOpt; ?>"; 
      // var searchOpt="<?php echo $searchOpt; ?>"; 
      // var search="<?php echo $search; ?>"; 

     // $("#partOpt").val(partOpt);
     // $("#searchOpt").val(searchOpt);
     // $("#search").val(search);
	 $("#board_form").submit();  
	}			
	
	
});	
	
function SearchEnter(){

    if(event.keyCode == 13){	
		$("#page").val('1');		
		document.getElementById('board_form').submit(); 
    }
}


	
</script>