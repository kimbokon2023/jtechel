<meta charset="utf-8">
 
 <?php
 session_start(); 
  
 $num=$_REQUEST["num"];
 $search=$_REQUEST["search"];  //검색어
 $find=$_REQUEST["find"];      // 검색항목
 $page=$_REQUEST["page"];   //페이지번호
 $process=$_REQUEST["process"];   // 진행현황
 // 기간을 정하는 구간
$fromdate=$_REQUEST["fromdate"];	 
$todate=$_REQUEST["todate"];
$separate_date=$_REQUEST["separate_date"];	 

 $year=$_REQUEST["year"];   // 년도 체크박스

 require_once("../lib/mydb.php");
 $pdo = db_connect();
 
 try{
     $sql = "select * from mirae8440.automan order by num desc limit 1";
     $stmh = $pdo->prepare($sql);  
     $stmh->execute();                  
     $row = $stmh->fetch(PDO::FETCH_ASSOC);	 
     $num=$row["num"];
	 
	print "마지막 레코드 번호 : " . $num;		 

	}
   catch (PDOException $Exception) {
       print "오류: ".$Exception->getMessage();
  }
  header("Location:http://8440.co.kr/automan/view.php?num=$num&page=$page&search=$search&find=$find&process=$process&yearcheckbox=$yearcheckbox&year=$year&fromdate=$fromdate&todate=$todate&separate_date=$separate_date");  
 ?>  
	
