<?php

session_start();

$level= $_SESSION["level"];
$id_name= $_SESSION["name"];   
$user_name= $_SESSION["name"];   
   
 if(!isset($_SESSION["level"]) || $level>8) {
          /*   alert("관리자 승인이 필요합니다."); */
		 sleep(2);
         header ("Location:http://j-techel.co.kr/login/logout.php");
         exit;
   }  
   
?>
<!DOCTYPE HTML>
<html>

<head>
<meta charset="UTF-8">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" >
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<link rel="stylesheet" href="../css/partner.css" type="text/css" />
<link rel="stylesheet" href="./css/style.css" type="text/css" />

 <title> 공사현장 관리시스템 </title>
 </head>
 <?php
if(isset($_REQUEST["search"]))   //목록표에 제목,이름 등 나오는 부분
	 $search=$_REQUEST["search"];
	  
  require_once("../lib/mydb.php");
  $pdo = db_connect();	


 if(isset($_REQUEST["check"])) 
	 $check=$_REQUEST["check"]; // request 사용 페이지 이동버튼 누를시`
   else
     $check=$_POST["check"]; //  POST사용 

if($check==null) $check=0;	 
    
  $sum=array();

	 
  if(isset($_REQUEST["mode"]))
     $mode=$_REQUEST["mode"];
  else 
     $mode="";       
 
$time = time();  
$today=date("Y-m-d");  // 현재일 저장   	  
$yearAfter=date("Y-m-d",strtotime("+12 month", $time)) ; // 1년 후 산출
   
switch($check) {  
  case '0' :  //전체 체크된 경우
  				$attached=" ";
			    $orderby=" order by num desc  ";																	
				break;		 
  case '1' :  // 미실측 체크된 경우
  				$attached=" where (measureday='') or (measureday='0000-00-00') ";
			    $orderby=" order by num desc  ";																	
				break;		
  case '2' :  // 미시공 체크된 경우
  				$attached=" where (doneday='') or (doneday='0000-00-00') ";
			    $orderby=" order by num desc  ";																	
				break;		
  case '3' :  // 미청구 체크된 경우
  				$attached=" where (demand='') or (demand='0000-00-00') or (demand between date('$today') and date('$yearAfter')) ";
			    $orderby=" order by num desc  ";																	
				break;		
	default:	
	        $attached=" ";
	  	    $orderby=" order by num desc  ";															
		}		 
	 
$a= $attached . " " . $orderby . " ";  
$b= $attached .  " " . $orderby;

		  if($search==""){
					 $sql="select * from jtechel.os  " . $a; 					
	                 $sqlcon = "select * from jtechel.os  "  . $b;   // 전체 레코드수를 파악하기 위함.					 				
		  }
			else						 
		  			 
        { // 필드별 검색하기
					  $sql ="select * from jtechel.os where ((workplacename like '%$search%' ) or (firstordman like '%$search%' )  or (secondordman like '%$search%' )  or (chargedman like '%$search%' ) ";
					  $sql .="or (firstord like '%$search%' ) or (secondord like '%$search%' ) or (worker like '%$search%' ) or (memo like '%$search%' ) or (memo2 like '%$search%' ) ) " . $a;
					  
                      $sqlcon ="select * from jtechel.os where ((workplacename like '%$search%' )  or (firstordman like '%$search%' )  or (secondordman like '%$search%' )  or (chargedman like '%$search%' ) ";
					  $sqlcon .=" or (firstord like '%$search%' ) or (secondord like '%$search%' ) or (worker like '%$search%' ) or (memo like '%$search%' ) or (memo2 like '%$search%' )) " . $b;
				  } 

	 try{  

	  $stmh = $pdo->query($sql);            // 검색조건에 맞는글 stmh
      $temp1=$stmh->rowCount();
	      
				$total_row = $temp1;     // 전체 글수	   
			 
			?>
		 
<body>
<div  class="container-fluid">
<br>
<br>
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
   <div class="col-6"> 
		         <h3 class="display-5 font-center text-left"> 
	<?=$_SESSION["nick"]?> | 
		<a href="../login/logout.php">로그아웃</a> | <a href="../member/updateForm.php?id=<?=$_SESSION["userid"]?>">정보수정</a>
		
<?php
	 }
?>
</h3>
</div> </div> 
<br>
<form id="board_form" name="board_form" method="get" action="index.php?mode=search&search=<?=$search?>&check=<?=$check?>">  
					<div class="row">
	 <H1> &nbsp;&nbsp;  오성이엘 현장관리 </H1> &nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp; 
	 <div class="row">
	 <button type="button" id="outputplan" class="btn btn-secondary btn-lg " onclick="location.href='schedule.php'"> 작업스케줄(예정)   </button>
	 </div>

		  <!--  <div class="d-flex">
			  <div class="p-2 flex-fill ">			  
			  <h2 class="display-4  bg-dark text-light ">★필독★ 공지사항</h2></div>
			  <div class="p-2 flex-fill ">			  
			  <h2 class="display-4  bg-dark text-light">☆☆☆ 10월 시공내역서 마감은 10월29일(토)까지 부탁드립니다. </h2></div>	
			  <?
			  // <div class="p-2 flex-fill ">				  
			  // <h2 class="display-5 text-light bg-secondary">2. 7월 시공비 내역은 7월31일까지 이메일로 보내주시면 감사하겠습니다.</h2></div>			   
			  ?>
			  </div>`````   -->
			  </div>
			
						<div class="row">
			  <!--  <button id="btn-1" type="button" class="btn btn-default" >Open</button>
					  <!-- Modal -->
				  <div class="modal fade" id="myModal" role="dialog">
					<div class="modal-dialog">
					
					  <!-- Modal content-->
					  <div class="modal-content">
						<div class="modal-header">
						  <button type="button" class="close" data-dismiss="modal">&times;</button>
						  <h3 class="modal-title">미래기업 공지사항</h3>
						</div>
						<div class="modal-body">
						  <p> <h1> 소장님들 고생많으십니다!!!  <br>
						  
						   참고로, 요즘 쟘 출고량도 무척 많습니다. <br> <br> 힘드시겠지만, 출고일이 지연될 수 있음을 양해 부탁드립니다.</h1> 
						  
						  </p>
						</div>
						<div class="modal-footer">
						  <button type="button" class="btn btn-default btn-lg" data-dismiss="modal">닫기</button>
						</div>
					  </div>
					  
					</div>
				  </div> 
				  </div>
			
			<br>
			<div class="row">

<div class="col-6">
      <h4 class="display-4 font-center text-center">    <div class="inputcontainer">    <input type="text" id="search" name="search" value="<?=$search?>" size="30"   placeholder="검색어">		</div>  </h4> 	  
        </div>  		
<div class="col-5 text-left">

      <button type="button"  class="btn btn-dark btn-lg" onclick="document.getElementById('board_form').submit();"> 검색   </button>
        </div>  		
  </div>
		<br> <br>
		<div class="row">
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 
<button type="button" id="showall" class="btn btn-dark btn-lg " onclick="location.href='index.php?mode=search&search=<?=$search?>&check=0'"> 전체   </button>  &nbsp;&nbsp;&nbsp;&nbsp;
<button id="showNomeasure"  type="button" class="btn btn-success btn-lg btn-lg " onclick="location.href='index.php?mode=search&search=<?=$search?>&check=1'"> 미실측  </button> &nbsp;&nbsp;&nbsp;&nbsp;
<button id="showNowork" type="button" class="btn btn-info btn-lg " onclick="location.href='index.php?mode=search&search=<?=$search?>&check=2'"> 미시공   </button>  &nbsp;&nbsp;&nbsp;&nbsp;
<button type="button" id="outputplan" class="btn btn-danger btn-lg " onclick="location.href='index.php?mode=search&search=<?=$search?>&check=3'">  미청구   </button>  &nbsp;&nbsp;&nbsp;&nbsp;
<button type="button" id="outputplan" class="btn btn-secondary btn-lg " onclick="location.href='write_form.php?mode=new&search=<?=$search?>&check=2'"> 글쓰기   </button>  &nbsp;&nbsp;&nbsp;&nbsp;
		</div>
<br>		

		<input type="hidden" id="check" name="check" value="<?=$check?>" size="5" > 						
		<input type="hidden" id="sqltext" name="sqltext" value="<?=$sqltext?>" > 			
		<input type="hidden" id="voc_alert" name="voc_alert" value="<?=$voc_alert?>" size="5" > 	
		<input type="hidden" id="ma_alert" name="ma_alert" value="<?=$ma_alert?>" size="5" > 	
         <div id="vacancy" style="display:none">  </div>
			                
		<?php
			?>
        <div id="list_search4"></div>

        <div id="list_search5"></div> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		
 <div id="list_search11">		
		    
      </div> <!-- end of list_search11  -->
	  
<div id="list_search12">			  	
	  
      </div> <!-- end of list_search12  -->
	  
		<div class="row">
        <div class="col-1">
        <h4 class="display-5 font-center text-center"> No </h4>
        </div>
		 <div class="col-sm-1">
		<h4 class="display-5 font-center text-center"> 접수</h4> 
		</div>	  
		 <div class="col-sm-1">
		<h4 class="display-5 font-center text-center"> 실측</h4> 
		</div>    
		 <div class="col-sm-1">
		<h4 class="display-5 font-center text-center"> 예정일</h4> 
		</div>     
	  
        <div class="col-sm-1">
      <h4 class="display-5 font-center text-center" > 시공완료 </h4>
        </div>
        <div class="col-sm-1">
      <h4 class="display-5 font-center text-center"  > 청구 </h4>
        </div>		
        <div class="col-sm">
      <h4 class="display-5 font-center text-center"> 공사명</h4>
        </div>		
      </div>
	  
    <?php  
	
			$start_num=$total_row;    // 페이지당 표시되는 첫번째 글순번
			
	       while($row = $stmh->fetch(PDO::FETCH_ASSOC)) {
			   
			  $num=$row["num"];			  
			  $checkstep=$row["checkstep"];
			  $workplacename=$row["workplacename"];
			  $address=$row["address"];
			  $firstord=$row["firstord"];
			  $firstordman=$row["firstordman"];
			  $firstordmantel=$row["firstordmantel"];
			  $secondord=$row["secondord"];
			  $secondordman=$row["secondordman"];
			  $secondordmantel=$row["secondordmantel"];
			  $chargedman=$row["chargedman"];
			  $regist_day=$row["regist_day"];
			  $measureday=$row["measureday"];			  			  			  
			  
			  $workday=$row["workday"];
			  $doneday=$row["doneday"];
			  $worker=$row["worker"];
			  $material1=$row["material1"];
			  $material2=$row["material2"];
			  $material3=$row["material3"];
			  $material4=$row["material4"];
			  $material5=$row["material5"];

			  $memo=$row["memo"];
			  $regist_day=$row["regist_day"];
			  $update_day=$row["update_day"];
			  $demand=$row["demand"];
			  
			  $filename1=$row["filename1"];
			  $filename2=$row["filename2"];
			  $imgurl1="../imgwork/" . $filename1;
			  $imgurl2="../imgwork/" . $filename2;			  
			  
		      if($regist_day!="0000-00-00" and $regist_day!="1970-01-01"  and $regist_day!="") $regist_day = date("Y-m-d", strtotime( $regist_day) );
					else $regist_day="";
		      if($measureday!="0000-00-00" and $measureday!="1970-01-01" and $measureday!="")   $measureday = date("Y-m-d", strtotime( $measureday) );
					else $measureday="";
		      if($workday!="0000-00-00" and $workday!="1970-01-01"  and $workday!="")  $workday = date("Y-m-d", strtotime( $workday) );
					else $workday="";		
		      if($demand!="0000-00-00" and $demand!="1970-01-01" and $demand!="")  $demand = date("Y-m-d", strtotime( $demand) );
					else $demand="";		  			  	  
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
						
			  if(substr($row["doneday"],0,2)=="20")  $doneday = iconv_substr($doneday,5,5,"utf-8");
			            else $doneday="    ";			  
              
			 ?>
		
<a href="write_form.php?num=<?=$num?>&check=<?=$check?>"  >		
	<div class="row">
	    
        <div class="col-sm-1">		  
          <h4 class="display-5 font-center text-center"> <?=$start_num?> </h4>
        </div>
        <div class="col-sm-1"> 							
			<h4 class="display-5 font-center text-center"> <?=$regist_day?> &nbsp;</h4> 
			
        </div>		
        <div class="col-sm-1">  
		<h4 class="display-5 font-center text-center"> <?=$measureday?>&nbsp;</h4> 			
        </div>				
        <div class="col-sm-1">  
		<h4 class="display-5 font-center text-center"> <?=$workday?>&nbsp;</h4> 			
        </div>				
        <div class="col-sm-1">
          <h4 class="display-5 font-center text-center text-success"> <?=$doneday?>&nbsp;</h4>
        </div>
        <div class="col-sm-1">
          <h4 class="display-5 font-center text-center text-secondary">  <?=$demand?>&nbsp;</h4>
        </div>
        <div class="col-sm-6">
          <h3 class="display-5 font-center text-left"> <?=iconv_substr($workplacename,0,25,"utf-8")?>&nbsp; </h3>
        </div>				
		 
      </div>	 
	  </a>
				  
</a>
            <div class="clear" > </div>
			<?php
			$start_num--;
			 } 
  } catch (PDOException $Exception) {
  print "오류: ".$Exception->getMessage();
  }  

 ?>
 <br>
 <br>

	</form>	
         </div> <!-- end of  container -->     

  </body>  

  
  </html>
