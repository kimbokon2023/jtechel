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

<!-- Main jQuery -->
<script src="plugins/jquery/jquery.js"></script>
<!-- Bootstrap 4.3.1 -->
<script src="plugins/bootstrap/js/bootstrap.min.js"></script>
<!-- Slick Slider -->
<script src="plugins/slick-carousel/slick/slick.min.js"></script>
<!--  Magnific Popup-->
<script src="plugins/magnific-popup/dist/jquery.magnific-popup.min.js"></script>
<!-- Form Validator -->
<script src="http://cdnjs.cloudflare.com/ajax/libs/jquery.form/3.32/jquery.form.js"></script>
<script src="http://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.11.1/jquery.validate.min.js"></script>
<!-- Google Map -->
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBu5nZKbeK-WHQ70oqOWo-_4VmwOwKP9YQ"></script>
<script src="plugins/google-map/gmap.js"></script>

<script src="js/script.js"></script>


<link rel="stylesheet" href="../css/style.css"/>  
<link rel="stylesheet" href="./css/style.css"/>
<!-- navibarsub css -->  
<link rel="stylesheet" href="css/style2.css">

<body>
<title> 제이테크 사용자 메뉴얼(도움말) </title>
<style>
  
body{
font-family: "Helvetica Nene", Helvetica, Arial, "malgun gothic", sans-serif;
/* font-family: 'Nanum Myeongjo', serif; */
font-size:14px;
font-weight:500;
line-height:220%
} 
   

/* 접수 실측 시공예정 등 칸에 가져가면 변하기 */
  #registerBtn:hover, #measuredayBtn:hover, #forecastBtn:hover, #workdoneBtn:hover, #demandBtn:hover, #donedemandBtn:hover {
    background: silver;
    border-radius: 15%;
    border-bottom: 2px solid slateblue;
    cursor: pointer;
  }
 
   
</style>


	  <div class="d-flex  mt-1 mb-1 justify-content-center ">	
			
			
         <div class="d-flex p-2 m-2 mt-3 mb-1 justify-content-center">	
				<div class="input-group ">     				
				<span class="input-group-text align-items-center"> <strong> (수주/발주 LIST) </strong> </span>
			     &nbsp;&nbsp;&nbsp;
				    <span class="input-group-text text-primary align-items-center bg-white"  > 목록개수 </span>  &nbsp; 
				   <select name="scaleval" id="scaleval" >
					   <?php		 
								
					   $scalearr = array();
					   array_push($scalearr,'10','20','30','50','100');
					   
					   for($i=0; $i<count($scalearr); $i++) {
								 if($scale==$scalearr[$i])
											print "<option selected value='" . $$scalearr[$i] . "'> " . $scalearr[$i] .   "</option>";
									 else   
							   print "<option value='" . $scalearr[$i] . "'> " . $scalearr[$i] .   "</option>";
						   } 		   
						   

								?>	  
						</select> 
				 &nbsp;&nbsp;
								 
				 
                 <button class="btn btn-dark btn-sm " type="button" id=AlldataBtn > 전체 </button>&nbsp;&nbsp;
                 <button class="btn btn-outline-secondary btn-sm  " type="button" id=todayregistBtn > 금일접수 </button>  &nbsp;&nbsp;
                 <button class="btn btn-outline-danger btn-sm  " type="button" id=todayoutputBtn > 금일시공예정 </button>  &nbsp;&nbsp;                 
                 <button class="btn btn-outline-primary btn-sm  " type="button" id=aftertodayoutputBtn > 금일이후 시공예정 </button> &nbsp;&nbsp;
				<span class="input-group-text">   ▷ 총 <?= $total_row ?> 개의 자료. </span>				 
					<input type="hidden" id="check" name="check" value="<?=$check?>" size="5" > 		
					<input type="hidden" id="plan_output_check" name="plan_output_check" value="<?=$plan_output_check?>" size="5" > 				
					<input type="hidden" id="output_check" name="output_check" value="<?=$output_check?>" size="5" > 				
					<input type="hidden" id="team_check" name="team_check" value="<?=$team_check?>" size="5" > 				
					<input type="hidden" id="measure_check" name="measure_check" value="<?=$measure_check?>" size="5" > 				
					<input type="hidden" id="sqltext" name="sqltext" value="<?=$sqltext?>" > 	
					
					<span class="input-group-text"> 				  
					<input type="text" id="search" name="search" value="<?=$search?>" onkeydown="JavaScript:SearchEnter();"  > &nbsp;
                    <button id="searchBtn" type="button" class="btn btn-dark  btn-sm"  > 검색  </button> &nbsp;&nbsp;&nbsp;&nbsp;
					
			  </div>								 			 
		</div>
	</div>
  <div class="d-flex  mt-1 mb-1 justify-content-center">	
	<div class="input-group text-left p-2 mb-1"> 
      <div class="limit">
        <ul class="list-group">
          <li class="list-row list-row--header">
            <div class="list-cell list-cell--70 text-center">번호</div>
            <div class="list-cell list-cell--80 text-center  text-center" id="registerBtn" >접수</div>
            <div class="list-cell list-cell--80 text-center" id="measuredayBtn" >실측</div>
            <div class="list-cell list-cell--80 text-center  text-primary"  id="forecastBtn" >시공예정</div>
            <div class="list-cell list-cell--80 text-center text-success"  id="workdoneBtn" >시공완료</div>
            <div class="list-cell list-cell--80 text-center"  id="demandBtn" >대금청구</div>
            <div class="list-cell list-cell--80 text-center"  id="donedemandBtn" >대금지급</div>
            <div class="list-cell list-cell--250 text-center"><strong>  공사명  </strong> </div>
            <div class="list-cell list-cell--250 text-center">주소</div>            
            <div class="list-cell list-cell--130 text-center">원청</div>
            <div class="list-cell list-cell--130 text-center">발주처</div>
            <div class="list-cell list-cell--80 text-center">시공팀</div>
            <div class="list-cell list-cell--200 text-secondary text-center">세부발주내역</div>
          </li>	  			  
	<?php  
		  if ($page<=1)  
			$start_num=$total_row;    // 페이지당 표시되는 첫번째 글순번
		  else 
			$start_num=$total_row-($page-1) * $scale;
		
	       while($row = $stmh->fetch(PDO::FETCH_ASSOC)) {
			  
              include 'rowDB.php';			  
			  
		      if($regist_day!="0000-00-00" and $regist_day!="1970-01-01"  and $regist_day!="") $regist_day = date("Y-m-d", strtotime( $regist_day) );
					else $regist_day="";
		      if($measureday!="0000-00-00" and $measureday!="1970-01-01" and $measureday!="")   $measureday = date("Y-m-d", strtotime( $measureday) );
					else $measureday="";
		      if($workday!="0000-00-00" and $workday!="1970-01-01"  and $workday!="")  $workday = date("Y-m-d", strtotime( $workday) );
					else $workday="";		
		      if($demand!="0000-00-00" and $demand!="1970-01-01" and $demand!="")  $demand = date("Y-m-d", strtotime( $demand) );
					else $demand="";			
		      if($donedemand!="0000-00-00" and $donedemand!="1970-01-01" and $donedemand!="")  $donedemand = date("Y-m-d", strtotime( $donedemand) );
					else $donedemand="";		  			  	  
		      if($doneday!="0000-00-00" and $doneday!="1970-01-01" and $doneday!="")  $doneday = date("Y-m-d", strtotime( $doneday) );
					else $doneday="";		  
			  	  				  
			  $state_work=0;
			  if($row["checkbox"]==0) $state_work=1;
			  if(substr($row["workday"],0,2)=="20") $state_work=2;			  
	 	  
			                
			  if(substr($row["regist_day"],0,2)=="20")  $regist_day = iconv_substr($regist_day,5,5,"utf-8");
			            else $regist_day="    ";	
						
			  if(substr($row["workday"],0,2)=="20")  $workday = iconv_substr($workday,5,5,"utf-8");
			            else $workday="    ";		
						
			  if(substr($row["measureday"],0,2)=="20")  $measureday = iconv_substr($measureday,5,5,"utf-8");
			            else $measureday="    ";	
			                
			  if(substr($row["demand"],0,2)=="20")  $demand = iconv_substr($demand,5,5,"utf-8");
			            else $demand="    ";
						
			  if(substr($row["donedemand"],0,2)=="20")  $donedemand = iconv_substr($donedemand,5,5,"utf-8");
			            else $donedemand="    ";	 
						
			  if(substr($row["doneday"],0,2)=="20")  $doneday = iconv_substr($doneday,5,5,"utf-8");
			            else $doneday="    ";			
			 ?>
	       <li class="list-row">		   
		   
		     <a class="list-link" href="write_form.php?num=<?=$num?>&page=<?=$page?>&scale=<?=$scale?>">		   
			 
            <div class="list-cell list-cell--70 text-center"><?=$start_num?>&nbsp;</div>
            <div class="list-cell list-cell--80  text-center"> <?=$regist_day?> &nbsp;</div>
            <div class="list-cell list-cell--80 text-center"> <?=$measureday?>&nbsp;</div>
            <div class="list-cell list-cell--80 text-center text-primary"> <?=$workday?>&nbsp;</div>
            <div class="list-cell list-cell--80 text-center  text-success">  <?=$doneday?>&nbsp;</div>
            <div class="list-cell list-cell--80 text-center ">  <?=$demand?>&nbsp;</div>
            <div class="list-cell list-cell--80 text-center ">  <?=$donedemand?>&nbsp;</div>
            <div class="list-cell list-cell--250  "> <strong> <?=$workplacename?>&nbsp; </strong> </div>
            <div class="list-cell list-cell--250  ">		<?=$address?>&nbsp;		</div> 
            <div class="list-cell list-cell--130 text-center ">		<?=$firstord?>&nbsp;	</div>	
            <div class="list-cell list-cell--130 text-center ">		<?=$secondord?>&nbsp;	</div>	
            <div class="list-cell list-cell--80 text-center ">		<?=$worker?>&nbsp;		</div>
            <div class="list-cell list-cell--200 text-secondary ">		 <?=iconv_substr($memo,0,15,"utf-8")?> 	</div>	      
		   
		     
			</a>
          </li>			
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
		   </ul>
		   </div>
	  </div>	 
	</div>	 
  <div class="row row-cols-auto mt-4 justify-content-center align-items-center">  
         <?php
            if($page!=1 && $page>$page_scale){
              $prev_page = $page - $page_scale;    
              // 이전 페이지값은 해당 페이지 수에서 리스트에 표시될 페이지수 만큼 감소
              if($prev_page <= 0) 
              $prev_page = 1;  // 만약 감소한 값이 0보다 작거나 같으면 1로 고정
		      print '<button class="btn btn-outline-secondary btn-sm" type="button" id=previousListBtn  onclick="movetoPage(' . $prev_page . ')"> ◀ </button> &nbsp;' ;              
            }
            for($i=$start_page; $i<=$end_page && $i<= $total_page; $i++) {        // [1][2][3] 페이지 번호 목록 출력
              if($page==$i) // 현재 위치한 페이지는 링크 출력을 하지 않도록 설정.
                print '<span class="text-secondary" >  ' . $i . '  </span>'; 
              else 
                   print '<button class="btn btn-outline-secondary btn-sm" type="button" id=moveListBtn onclick="movetoPage(' . $i . ')"> ' . $i . '</button> &nbsp;' ;     			
            }

            if($page<$total_page){
              $next_page = $page + $page_scale;
              if($next_page > $total_page) 
                     $next_page = $total_page;
                // netx_page 값이 전체 페이지수 보다 크면 맨 뒤 페이지로 이동시킴
				  print '<button class="btn btn-outline-secondary btn-sm" type="button" id=nextListBtn onclick="movetoPage(' . $next_page . ')"> ▶ </button> &nbsp;' ; 
            }
            ?>              
    </div>	 
			
		</form>		
		     <!-- 거래처정보를 전달하기 위한 임시폼이다. -->			  
		     <form id="subFrm" method="post" enctype="multipart/form-data" action="listprocess.php?SelectWork=index"  >	 </form>
		     <form id="subFrmLaser" method="post" enctype="multipart/form-data" action="listprocess.php?SelectWork=laserwork"  >	 </form>
		     <form id="subFrmBending" method="post" enctype="multipart/form-data" action="listprocess.php?SelectWork=bendingwork"  >	 </form>
		     <form id="subFrmPainting" method="post" enctype="multipart/form-data" action="listprocess.php?SelectWork=paintingwork"  >	 </form>
		     <form id="subFrmLastwork" method="post" enctype="multipart/form-data" action="listprocess.php?SelectWork=lastwork"  >	 </form>
		     <form id="subFrmBoard" method="post" enctype="multipart/form-data" action="listprocess.php?SelectWork=boardwork"  >	 </form>
			 
			 </form>
              
			   <!-- 거래처정보를 전달하기 위한 임시변수입니다.-->			  
              <input id="Call_Ecount" type=hidden value="0" >			  
		  
		  
<script> 

	
function SearchEnter(){
	
    if(event.keyCode == 13){		
		$("#page").val('1');
		document.getElementById('board_form').submit(); 
    }
}

	
function movetoPage(page){ 	  
	  $("#page").val(page); 
      // var echo="<?php echo $partOpt; ?>"; 
      // var searchOpt="<?php echo $searchOpt; ?>"; 
      // var search="<?php echo $search; ?>"; 

     // $("#partOpt").val(partOpt);
     // $("#searchOpt").val(searchOpt);
     // $("#search").val(search);
	 $("#board_form").submit();  
	}	
	
$(document).ready(function(){
	
$("#scaleval").on("change", function(){
    //selected value
    $("#scale").val($(this).val());
	// 화면고정
	$('#page').val('1');      
	$('#stable').val('1');      
	$('#sortof').val('0');	
	$('#board_form').submit();			
    
});	
	
	
// div 접수일 실측일 등 클릭했을때 이벤트 발생
$("#registerBtn").click(function(){ 
	  $("#sortof").val('1');
	  $("#page").val('1');
	  $("#board_form").submit();   
	});		
// div 접수일 실측일 등 클릭했을때 이벤트 발생	
 $("#measuredayBtn").click(function(){ 
	  $("#sortof").val('2');
	  $("#page").val('1');
	  $("#board_form").submit();   
	});		
// div 접수일 실측일 등 클릭했을때 이벤트 발생	
 $("#forecastBtn").click(function(){ 
	  $("#sortof").val('3');
	  $("#page").val('1');
	  $("#board_form").submit();   
	});		
// div 접수일 실측일 등 클릭했을때 이벤트 발생	
 $("#workdoneBtn").click(function(){ 
	  $("#sortof").val('4');
	  $("#page").val('1');
	  $("#board_form").submit();   
	});		
// div 접수일 실측일 등 클릭했을때 이벤트 발생	
 $("#demandBtn").click(function(){ 
	  $("#sortof").val('5');
	  $("#page").val('1');
	  $("#board_form").submit();   
	});		
// div 접수일 실측일 등 클릭했을때 이벤트 발생	
 $("#donedemandBtn").click(function(){ 
	  $("#sortof").val('6');
	  $("#page").val('1');
	  $("#board_form").submit();   
	});		

















	$("#searchBtn").click(function(){ 
	  $("#SelectWork").val('listsearch');
	  $("#page").val('1');
	  $("#board_form").submit();   
	});	
	$("#AlldataBtn").click(function(){ 
	  $("#searchOpt").val('0');  // 전체로 검색함.
	  $("#page").val('1');	  
      $("#search").val('');
      $("#sortof").val('0');
	  $("#board_form").submit();   
	});	 	
	$("#todayregistBtn").click(function(){ 
	  $("#searchOpt").val('1');  // 금일접수로 검색함.
	  $("#page").val('1');	  
	  $("#board_form").submit();   
	});		
       	
	$("#todayoutputBtn").click(function(){ 
	  $("#searchOpt").val('2');  // 금일시공예정로 검색함.
	  $("#page").val('1');	  
	  $("#board_form").submit();   
	});		

	$("#aftertodayoutputBtn").click(function(){ 
	  $("#searchOpt").val('4');  // 금일 이후 시공예정로 검색함.
	  $("#page").val('1');	  
	  $("#board_form").submit();   
	});		
       
	
});			

function 	alert_msg(titlemsg,contextmsg) {
// 화면에 메시지창
	Swal.fire({ 
		   title: titlemsg, 
		   text: contextmsg , 
		   icon: 'success',                  // success, error, warning, info, question  5가지 가능함.
		   showCancelButton: true, 
		   confirmButtonColor: '#3085d6', 
		   cancelButtonColor: '#d33', 
		   confirmButtonText: '저장', 
		   cancelButtonText: '취소' })
		   .then((result) => { if (result.isConfirmed) { 
			$("#SelectWork").val('saveini'); 						 
			$("#board_form").submit();  
		   
		   Swal.fire( '수고하세요.', '알림완료!', 'success' ) } })		
}
	
function alert_confirm(titlemsg,contextmsg) {
// 화면에 알림 (성공)
	  Swal({ icon: 'success', // Alert 타입 
	  title: titlemsg, // Alert 제목 
	  text: contextmsg // Alert 내용 
		}); 	
      
}	
	
					 
function getToday(){   // 2021-01-28 형태리턴
    var now = new Date();
    var year = now.getFullYear();
    var month = now.getMonth() + 1;    //1월이 0으로 되기때문에 +1을 함.
    var date = now.getDate();

    month = month >=10 ? month : "0" + month;
    date  = date  >= 10 ? date : "0" + date;
     // ""을 빼면 year + month (숫자+숫자) 됨.. ex) 2018 + 12 = 2030이 리턴됨.

    //console.log(""+year + month + date);
    return today = ""+year + "-" + month + "-" + date; 
}


function inputNumberFormat(obj) { 
obj.value = comma(uncomma(obj.value)); 
} 
function comma(str) { 
    str = String(str); 
    return str.replace(/(\d)(?=(?:\d{3})+(?!\d))/g, '$1,'); 
} 
function uncomma(str) { 
    str = String(str); 
    // str = str.replace(/\./g, ''); 
	tmp = Number(str.replace(/[^\d]+/g, ''))
    return tmp; 
}

function custreg_Enter_Check(){  // 거래처코드 입력창에서 엔터키
        // 엔터키의 코드는 13입니다.
    if(event.keyCode == 13){
      custreg_search();  // 실행할 이벤트
    }
}

function warecode_Enter_Check(){  // 창고검색 입력창에서 엔터키
        // 엔터키의 코드는 13입니다.
    if(event.keyCode == 13){
      warecode_search();  // 실행할 이벤트
    }
}

function custreg_search()
{
	  var ua = window.navigator.userAgent;
      var postData; 	 
	  var text1= document.getElementById("CUST_DES").value;
	
	     if (ua.indexOf('MSIE') > 0 || ua.indexOf('Trident') > 0) {
                postData = encodeURI(text1);
            } else {
                postData = text1;
            }

      $("#custreg_search").show();
      $("#custreg_search").load("./custreg_search.php?mode=search&search=" + postData);
} 

function warecode_search()
{
	  var ua = window.navigator.userAgent;
      var postData; 	 
	  var text1= document.getElementById("warename_input").value;
	
	     if (ua.indexOf('MSIE') > 0 || ua.indexOf('Trident') > 0) {
                postData = encodeURI(text1);
            } else {
                postData = text1;
            }

      $("#custreg_search").show();
      $("#custreg_search").load("./warecode_search.php?mode=search&search=" + postData);
} 


  </script>
    </div>
  </div>	 
</section>

</body>

</html>


 
<h1>하이퍼링크</h1>
<p align="center" 가운데정렬>
<img src="img/jpg/01.jpg - 이미지주소" title="딸기그림 - 그림에보이고싶은 글씨" />
<a href="http://naver.com  - 원하는 주소" target="_blank - 새창열기">네이버바로가기</a>
<a href="가고싶은주소.html">가고싶은주소</a></p>

<p>Copyright &copy; - copy 특수문자
       <a href="mailto:메일주소@gmail.com">메일보내기<img src="이미지주소" border="0"></a></p>

<a name="top">
<h1>책갈피</h1><p>
<a href="#numone">1. ㄱ이란? </a><br>
2. ㄴ이란?<br>
3. ㄷ이란?<br>
4. ㄹ이란?<br>

<a name="numone"> - 가상의 이름 만들기
1. ㄱ이란?<p>
내용<br>내용<br>내용<br>내용<br>내용<br>내용<br>내용<br>내용<br>내용<br>내용<br>내용<br>내용<br>내용<br>내용<br>내용<br>내용<br>내용<br>내용<br>내용<br>내용<br>내용<br>내용<br>내용<br>내용<br>내용<br>내용<br>내용<br>내용<br><p>

 <a href="#">위로</a> <p>
 <a href="#top">위로</a> <p> - #을 안할경우  top을 만들어서 해도됨


</body>

</html>