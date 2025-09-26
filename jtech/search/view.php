 <?php
  session_start(); 
  
 $level= $_SESSION["level"];
 if(!isset($_SESSION["level"]) || $level>=5) {
         echo "<script> alert('관리자 승인이 필요합니다.') </script>";
		 sleep(2);
         header ("Location:http://8440.co.kr/login/logout.php");
         exit;
   }   
  
  if(isset($_REQUEST["mode"]))  //수정 버튼을 클릭해서 호출했는지 체크
   $mode=$_REQUEST["mode"];
  else
   $mode="";
  
  if(isset($_REQUEST["num"]))  //수정 버튼을 클릭해서 호출했는지 체크
   $num=$_REQUEST["num"];
  else
   $num="";

   if(isset($_REQUEST["page"]))  //수정 버튼을 클릭해서 호출했는지 체크
   $page=$_REQUEST["page"];
  else
   $page=1;   

  if(isset($_REQUEST["search"]))  //수정 버튼을 클릭해서 호출했는지 체크
   $search=$_REQUEST["search"];
  else
   $search="";
  
  if(isset($_REQUEST["find"]))  //수정 버튼을 클릭해서 호출했는지 체크
   $find=$_REQUEST["find"];
  else
   $find="";

  if(isset($_REQUEST["process"]))  //수정 버튼을 클릭해서 호출했는지 체크
   $process=$_REQUEST["process"];
  else
   $process="전체";

$fromdate=$_REQUEST["fromdate"];	 
$todate=$_REQUEST["todate"];

 $year=$_REQUEST["year"];   // 년도 체크박스
      
  require_once("../lib/mydb.php");
  $pdo = db_connect();

    try{
      $sql = "select * from mirae8440.automan where num = ? ";
      $stmh = $pdo->prepare($sql); 

      $stmh->bindValue(1,$num,PDO::PARAM_STR); 
      $stmh->execute();
      $count = $stmh->rowCount();            
	  $row = $stmh->fetch(PDO::FETCH_ASSOC);  // $row 배열로 DB 정보를 불러온다.
    if($count<1){  
      print "검색결과가 없습니다.<br>";
     }else{


              $num=$row["num"];
 			  $proDate=$row["proDate"];			  			  
 			  $writer=$row["writer"];			  			  
 			  $amount=$row["amount"];			  			  
 			  $memo=$row["memo"];
			  $which=$row["which"];							
			  
      }
     }catch (PDOException $Exception) {
       print "오류: ".$Exception->getMessage();
     }

    
  
?>

<!DOCTYPE HTML>
<html>
<head> 
<meta charset="utf-8">
<link  rel="stylesheet" type="text/css" href="../css/common.css?v=1"> 
<link  rel="stylesheet" type="text/css" href="../css/steel.css?v=1"> 	

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="http://8440.co.kr/order/order.js"></script>
<script src="http://8440.co.kr/common.js"></script>

 <!-- 최초화면에서 보여주는 상단메뉴 -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" >
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.1/font/bootstrap-icons.css">

 <title> 전산실장 정산 </title> 
 </head>
  
<body>
  
	<div class="container-fluid">  
<div class="d-flex mb-1 justify-content-center">    
  <a href="../index.php"><img src="../img/toplogo.jpg" style="width:100%;" ></a>	
</div>

<? include '../myheader.php'; ?>   

</div>   

<div class="container">    
<div class="d-flex mb-1 mt-5 justify-content-center">    	  
			      
       <input type="hidden" id="first_writer" name="first_writer" value="<?=$first_writer?>"  >
       <input type="hidden" id="update_log" name="update_log" value="<?=$update_log?>"  >
	
	  <h4> &nbsp; &nbsp; 전산실장 정산내용 등록/수정 	&nbsp;	&nbsp;	&nbsp;	&nbsp;
			<button type="button" id="gotoList"  class="btn btn-secondary" onclick="location.href='list.php?mode=search&search=<?=$search?>&find=<?=$find?>&page=<?=$page?>&year=<?=$year?>&search=<?=$search?>&process=<?=$process?>&asprocess=<?=$asprocess?>&fromdate=<?=$fromdate?>&todate=<?=$todate?>&separate_date=<?=$separate_date?>'" > 목록(List) </button>	&nbsp;       	   						
			<button type="button" id="updateBtn"  class="btn btn-success" onclick="location.href='write_form.php?mode=modify&num=<?=$num?>&page=<?=$page?>&search=<?=$search?>&find=<?=$find?>&year=<?=$year?>&search=<?=$search?>&process=<?=$process?>&asprocess=<?=$asprocess?>&fromdate=<?=$fromdate?>&todate=<?=$todate?>&separate_date=<?=$separate_date?>'" > 수정 </button>	&nbsp;       	   									
			<button type="button" id="deleteBtn"  class="btn btn-danger" onclick="javascript:del('delete.php?num=<?=$num?>&page=<?=$page?>')" > 삭제 </button>	&nbsp;       	   												
			<button type="button" id="writeBtn"  class="btn btn-dark" onclick="location.href='write_form.php'" > 글쓰기 </button>	&nbsp;       	   																			
	</h4> 
</div>
 <div class="d-flex mb-1 mt-2 justify-content-center">   		      
		  
 <?php	 		  
	 	 $aryreg=array();
	 	 $aryitem=array();
		 if($which=='') $which='2';
	     switch ($which) {
					case   "1"             : $aryreg[0] = "checked" ; break;
					case   "2"             :$aryreg[1] =  "checked" ; break;
					default: break;
				}		
	   ?>		  
	   구분 :  &nbsp; &nbsp;  <span class="text-primary"> 수입   </span>  &nbsp; 
			<input  type="radio" <?=$aryreg[0]?> name=which value="1"> &nbsp; &nbsp; 
			&nbsp;  <span class="text-danger"> 지출   </span>  &nbsp;  
			<input  type="radio" <?=$aryreg[1]?>  name=which value="2">  	   
</div>
<div class="d-flex p-3 m-4 mb-2 mt-2 justify-content-center">   		           		  
	 기록일 :  &nbsp;
	 <input type="date" id="proDate" name="proDate" value="<?=$proDate?>" size="14" > &nbsp;
	작성자 :  &nbsp;
	 <input type="text" id="writer" name="writer" value="<?=$writer?>" size="14" > &nbsp;
</div>
<div class="d-flex p-3 m-4 mb-2 mt-2 justify-content-center">   		           		   		           		  
	  &nbsp; 내 역 :  &nbsp;	 
	 <input type="text"  id="memo" name="memo" value="<?=$memo?>" size="45" placeholder="내역"> 	 
</div>        
<div class="d-flex p-3 m-4 mb-2 mt-2 justify-content-center">   		           		   		           		  
	금 액 : &nbsp;	 
     <input type="text" name="amount" id="amount" value="<?=$amount?>" size="10" style="text-align:right;" placeholder="비용" />	 

	 </div>
	
		</form>
	 
	  </div> 

<script>


$(document).ready(function(){
		$("#saveBtn").click(function(){   	
		   // grid 배열 form에 전달하기						    						    
		  $("#board_form").submit(); 								 
		 });	
		
   $("div *").find("input,textarea").prop("disabled",true);	
 });		 
	

    function inputNumberFormat(obj) { 
    obj.value = comma(uncomma(obj.value)); 
} 
function comma(str) { 
    str = String(str); 
    return str.replace(/(\d)(?=(?:\d{3})+(?!\d))/g, '$1,'); 
} 
function uncomma(str) { 
    str = String(str); 
    return str.replace(/[^\d]+/g, ''); 
}


function date_mask(formd, textid) {

/*
input onkeyup에서
formd == this.form.name
textid == this.name
*/

var form = eval("document."+formd);
var text = eval("form."+textid);

var textlength = text.value.length;

if (textlength == 4) {
text.value = text.value + "-";
} else if (textlength == 7) {
text.value = text.value + "-";
} else if (textlength > 9) {
//날짜 수동 입력 Validation 체크
var chk_date = checkdate(text);

if (chk_date == false) {
return;
}
}
}
function del(href) 
     {
        if(confirm("한번 삭제한 자료는 복구할 방법이 없습니다.\n\n정말 삭제하시겠습니까?")) {
           document.location.href = href;
          }
     }
function checkdate(input) {
   var validformat = /^\d{4}\-\d{2}\-\d{2}$/; //Basic check for format validity 
   var returnval = false;

   if (!validformat.test(input.value)) {
    alert("날짜 형식이 올바르지 않습니다. YYYY-MM-DD");
   } else { //Detailed check for valid date ranges 
    var yearfield = input.value.split("-")[0];
    var monthfield = input.value.split("-")[1];
    var dayfield = input.value.split("-")[2];
    var dayobj = new Date(yearfield, monthfield - 1, dayfield);
   }

   if ((dayobj.getMonth() + 1 != monthfield)
     || (dayobj.getDate() != dayfield)
     || (dayobj.getFullYear() != yearfield)) {
    alert("날짜 형식이 올바르지 않습니다. YYYY-MM-DD");
   } else {
    //alert ('Correct date'); 
    returnval = true;
   }
   if (returnval == false) {
    input.select();
   }
   return returnval;
  }
  
function input_Text(){
    document.getElementById("test").value = comma(Math.floor(uncomma(document.getElementById("test").value)*1.1));   // 콤마를 계산해 주고 다시 붙여주고
  var copyText = document.getElementById("test");   // 클립보드 복사 
  copyText.select();
  document.execCommand("Copy");
}  

function captureReturnKey(e) {
    if(e.keyCode==13 && e.srcElement.type != 'textarea')
    return false;
}

function recaptureReturnKey(e) {
    if (e.keyCode==13)
        exe_search();
}
function Enter_Check(){
        // 엔터키의 코드는 13입니다.
    if(event.keyCode == 13){
      exe_search();  // 실행할 이벤트
    }
}
function Enter_CheckTel(){
        // 엔터키의 코드는 13입니다.
    if(event.keyCode == 13){
      exe_searchTel();  // 실행할 이벤트
    }
}

/* function exe_search()
{
	  var ua = window.navigator.userAgent;
      var postData; 	 
	  var text1= document.getElementById("outworkplace").value;
	
	     if (ua.indexOf('MSIE') > 0 || ua.indexOf('Trident') > 0) {
                postData = encodeURI(text1);
            } else {
                postData = text1;
            }

      $("#displaysearch").show();
      $("#displaysearch").load("./search.php?mode=search&search=" + postData);
} */

function exe_search()
{
      var postData = changeUri(document.getElementById("outworkplace").value);
      var sendData = $(":input:radio[name=root]:checked").val();

      $("#displaysearch").show();
	 if(sendData=='주일')
         $("#displaysearch").load("./search.php?mode=search&search=" + postData);
	 if(sendData=='경동') 
         $("#displaysearch").load("./searchkd.php?mode=search&search=" + postData);	  
}
function exe_searchTel()
{
	  var postData =  changeUri(document.getElementById("receiver").value);
      $("#displaysearchworker").show();
      $("#displaysearchworker").load("./workerlist.php?mode=search&search=" + postData);
}
</script> 
	</body>
 </html>
