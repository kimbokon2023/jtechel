<meta charset="utf-8">
 
 <?php
 session_start(); 
  
 $num=$_REQUEST["num"]; 
 $parent=$num;
 
  if(isset($_REQUEST["check"])) 
	 $check=$_REQUEST["check"]; 
   else
     $check=$_POST["check"]; 
 
 require_once("../lib/mydb.php");
 $pdo = db_connect();
	 
 try{
     $sql = "select * from mirae8440.work where num=?";
     $stmh = $pdo->prepare($sql);  
     $stmh->bindValue(1, $num, PDO::PARAM_STR);      
     $stmh->execute();            
      
     $row = $stmh->fetch(PDO::FETCH_ASSOC); 	 
  
     $content=$row["content"];
     $childnum=$row["num"];
     $workplacename=$row["workplacename"];
  $filename1=$row["filename1"];
  $filename2=$row["filename2"];
  $imgurl1="../imgwork/" . $filename1;
  $imgurl2="../imgwork/" . $filename2;	 
	 
	 if($childnum!=0)
		   $mode="modify";
	    else
		     $mode="insert";
		 
     }catch (PDOException $Exception) {
       print "오류: ".$Exception->getMessage();
  }  
   
 ?>
 <!DOCTYPE HTML>
 <html>
 <head> 
 <meta charset="utf-8">
 <head>
 <meta charset="UTF-8">

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" integrity="sha384-WskhaSGFgHYWDcbwN70/dfYBj47jz9qbsMId/iRN3ewGhXQFZCSftd1LZCfmhktB" crossorigin="anonymous">
<link rel="stylesheet" href="../css/partner.css" type="text/css" />

<style>

.progress {
  margin: 10px;
  width: 700px;
}

.blink {
	-webkit-animation: blink 1.05s linear infinite;
	-moz-animation: blink 1.05s linear infinite;
	-ms-animation: blink 1.05s linear infinite;
	-o-animation: blink 1.05s linear infinite;
	 animation: blink 1.05s linear infinite;
}
@-webkit-keyframes blink {
	0% { opacity: 1; }
	50% { opacity: 1; }
	50.01% { opacity: 0; }
	100% { opacity: 0; }
}
@-moz-keyframes blink {
	0% { opacity: 1; }
	50% { opacity: 1; }
	50.01% { opacity: 0; }
	100% { opacity: 0; }
}
@-ms-keyframes blink {
	0% { opacity: 1; }
	50% { opacity: 1; }
	50.01% { opacity: 0; }
	100% { opacity: 0; }
}
@-o-keyframes blink {
	0% { opacity: 1; }
	50% { opacity: 1; }
	50.01% { opacity: 0; }
	100% { opacity: 0; }
}
@keyframes blink {
	0% { opacity: 1; }
	50% { opacity: 1; }
	50.01% { opacity: 0; }
	100% { opacity: 0; }
}
</style>

 <title> 시공전후 사진등록/수정/삭제 </title>
 </head>
  <body>
<div id="top-menu">
<?php
    if(!isset($_SESSION["userid"]))
	{
?>
          <a href="../login/login_form.php">로그인</a> | <a href="../member/insertForm.php">회원가입</a>
<?php
	}
	else
	 {
?>
			<div class="row">           
			<div class="col">           
		         <h1 class="display-5 font-center text-left"> <br>
	<?=$_SESSION["name"]?> | 
		<a href="../login/logout.php">로그아웃</a> | <a href="../member/updateForm.php?id=<?=$_SESSION["userid"]?>">정보수정</a>
		
<?php
	 }
?>
</h1>
</div>
</div>
<br> 
			<div class="row">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			<h1 class="display-1  text-left">
  <input type="button" class="btn btn-secondary btn-lg " value="이전화면으로 돌아가기" onclick="location.href='./view.php?num=<?=$num?>&check=<?=$check?>'"> </h1> 
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
  </div>
<br>
<br>
<form id="board_form" name="board_form" method="post" action="pic_insert.php"   enctype="multipart/form-data">  
     
	 <input type="hidden" id=childnum name=childnum value="<?=$childnum?>" >
	 <input type="hidden" id=check name=check value="<?=$check?>" >
	 <input type="hidden" id=parent name=parent value="<?=$parent?>" >
	 <input type="hidden" id=num name=num value="<?=$num?>" >
	 <input type="hidden" id=mode name=mode value="<?=$mode?>" >
	 <input type="hidden" id=workplacename name=workplacename value="<?=$workplacename?>" >
	 <input type="hidden" id=filedelete name=filedelete >
	 <div  class="container">
			<div class="row">

	 <H1  class="display-5 font-center text-center" > 시공전후 사진 등록/수정/삭제 </H1> 
 </div>
 
 			<div class="row">				
			   <div id=progressbar class="blink" style="display:none;">
			 <!--  <div id=progressbar style="display:none;" class=blinking > -->
			   <div class="row">				 </div> <br>
					<h1 class="display-1  text-left"> 사진등록을 서버에 저장중입니다. <br> (잠시만 기다려주세요.) </h1>
					<div class="progress">
					  <div id="dynamic" class="progress-bar progress-bar-success progress-bar-striped active" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
						<span id="current-progress"></span>
					  </div>
				   	</div>			
								<div class="row">				 </div> <br>
				</div>
			</div>
			
			<br>
			<div class="row">

		         <h1 class="display-5 font-center text-left"> 		   
       현장명 :   <?=$workplacename?> 	   
<br>
  <span style="color:blue;"> 시공전 사진 : </span> <br>      
		<?php 
			if($filename1!=null) {				
			  print "기존 업로드 파일 있음 " . $filename1 ;  
			  print " <br> " ;  
			  print "<button type='button' class='btn btn-secondary btn-lg ' id='delPicBefore' onclick=delPic('before')> 삭제 </button> <br>";		  			  
			  print " <br> " ;  			  
			  print "<div class='imagediv' > ";
			  echo "<img class='before_work' src='". $imgurl1  . "'>";			  			  
			  print "</div> <br> ";
			  
              
			  }
		?>
		   
  <div class="row">
  <input name="mainBefore" class="input" type="file" onchange="this.value" id="mainBefore"  required />
     
		   
	   </div>	    	

 			<div class="row">				
			   <div id=progressbar1 class="blink" style="display:none;">
			 <!--  <div id=progressbar style="display:none;" class=blinking > -->
			   <div class="row">				 </div> <br>
					<h1 class="display-1  text-left"> 사진등록을 서버에 저장중입니다. <br> (잠시만 기다려주세요.) </h1>
					<div class="progress">
					  <div id="dynamic" class="progress-bar progress-bar-success progress-bar-striped active" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
						<span id="current-progress"></span>
					  </div>
				   	</div>			
					
								<div class="row">				 </div> <br>
				</div>
			</div>   	   
	   	   							
		<div class="row">

		         <h1 class="display-5 font-center text-left"> 		   
       
						<br>
  <span style="color:red;"> 시공후 사진 : </span><br> 	 
		<?php 
			if($filename2!=null) {
			  print "기존 업로드 파일 있음 " . $filename2 ; 
			  print " <br> " ;  
			  print "<button type='button' class='btn btn-secondary btn-lg ' id='delPicAfter' onclick=delPic('after')> 삭제 </button> <br>";		  			  
			  print " <br> " ; 			  
			   print "<div class='imagediv' > ";
			  echo "<img class='after_work' src='". $imgurl2  . "'>";
			  print "</div>  <br> ";
			  }
		?> 		
		<div class="row">
  <input name="mainAfter" class="input" type="file"  id="mainAfter"  onchange="this.value" required />
       <br>
       <br>
		   
	   </div>	    					
	   <div class="row"> <div class="col">&nbsp;  <H1  class="display-2 font-center text-center" > </H1> </div></div>			
	   
 			<div class="row">				
			   <div id=progressbar2 class="blink" style="display:none;">
			 <!--  <div id=progressbar style="display:none;" class=blinking > -->
			   <div class="row">				 </div> <br>
					<h1 class="display-1  text-left"> 사진등록을 서버에 저장중입니다. <br> (잠시만 기다려주세요.) </h1>
					<div class="progress">
					  <div id="dynamic" class="progress-bar progress-bar-success progress-bar-striped active" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
						<span id="current-progress"></span>
					  </div>
				   	</div>			
					
								<div class="row">				 </div> <br>
				</div>
			</div>   

			<div class="row">							
			<h1 class="display-1  text-left">
  <input type="button" class="btn btn-primary btn-lg " value="서버에 저장하기" onclick="javascript:pro_submit()" > </h1>

	   </div>	    		
   
	   </div> 

 </div> 
 </form>
	 
 </body>
</html>    

 <script language="javascript">
 

/* function new(){
 window.open("viewimg.php","첨부이미지 보기", "width=300, height=200, left=30, top=30, scrollbars=no,titlebar=no,status=no,resizable=no,fullscreen=no");
} */
var imgObj = new Image();
function showImgWin(imgName) {
imgObj.src = imgName;
setTimeout("createImgWin(imgObj)", 100);
}
function createImgWin(imgObj) {
if (! imgObj.complete) {
setTimeout("createImgWin(imgObj)", 100);
return;
}
imageWin = window.open("", "imageWin",
"width=" + imgObj.width + ",height=" + imgObj.height);
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
}  

function copy_below(){	   
}  

function pro_submit()
     {
		 
           $('#progressbar').show();	
           $('#progressbar1').show();	
           $('#progressbar2').show();	
           $('#board_form').submit();	

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
}, 3000);

	  
function delPic(delChoice)
{
if(delChoice=='before')
    $("#filedelete").val('before');
if(delChoice=='after')
    $("#filedelete").val('after');
   
document.getElementById('board_form').submit();	

}

</script>
