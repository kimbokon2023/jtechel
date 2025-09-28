<?php

header("Content-Type:text/html; charset=utf-8");
header("Content-Encoding:utf-8");

session_start();   

 function conv_num($num) {
	$number = (int)str_replace(',', '', $num);
	return $number;
}


// 환경파일 읽어오기 (테이블명 작업 폴더 등)
include 'ini.php';    

// include '_request.php';

// num은 id로 변수 변환해서 처리함

isset($_REQUEST["num"])  ? $num = $_REQUEST["num"] : $num=""; 

require_once("../lib/mydb.php");
$pdo = db_connect();


try{
		 $sql = "select * from jtechel." . $tablename . " where num=? ";
		 $stmh = $pdo->prepare($sql);  
		 $stmh->bindValue(1, $num, PDO::PARAM_STR);      
		 $stmh->execute();            		 
		 $row = $stmh->fetch(PDO::FETCH_ASSOC); 	
		  
         // include 'rowDB.php';    
		 // phpExcel을 사용하려면 include rowDB 안됨 에러남 원인파악해야 함수를				  

		$num=$row["num"];	
		$workplacename=$row["workplacename"];
		$address=$row["address"];
		$firstord=$row["firstord"];
		$firstordman=$row["firstordman"];
		$firstordmantel=$row["firstordmantel"];
		$secondord=$row["secondord"];
		$secondordman=$row["secondordman"];
		$secondordmantel=$row["secondordmantel"];
		$chargedman=$row["chargedman"];
		$chargedmantel=$row["chargedmantel"];
		$orderday=$row["orderday"];
		$measureday=$row["measureday"];  
		$workday=$row["workday"];
		$worker=$row["worker"]; 
		$doneday=$row["doneday"];  // 시공완료일  
		$demand=$row["demand"];  // 청구일
		$donedemand=$row["donedemand"];  // 청구일

		$worker=$row["worker"];

		$material1=$row["material1"];
		$material2=$row["material2"];
		$material3=$row["material3"];
		$material4=$row["material4"];
		$material5=$row["material5"];

		$memo=$row["memo"];
		$memo2=$row["memo2"];
		$regist_day=$row["regist_day"];  
		$update_log=$row["update_log"];

		$demand=$row["demand"];   
		$et_writeday=$row["et_writeday"];    // 견적서 작성일 
		$et_wpname=$row["et_wpname"];   
		if($et_wpname==null)
		$et_wpname=$address;   // 현장명이 없을때는 주소로

		$et_schedule=$row["et_schedule"];
		$et_person=$row["et_person"];
		$et_content=$row["et_content"];	  
		$et_note=$row["et_note"];	  
		  

		 }catch (PDOException $Exception) {
		   print "오류: ".$Exception->getMessage();
	 }
	 
// 견적서 분할하는 로직 불러오기 공통사용
include 'load_estimate.php';	 
	 
include "../PHPExcel_1.8.0/Classes/PHPExcel.php";
$objPHPExcel = new PHPExcel();

$arr = array();	 

$objPHPExcel->getActiveSheet()->getStyle("a1:h200")->getFont()->setName('Dotum')->setSize(9);	

$objPHPExcel -> getActiveSheet() -> getColumnDimension("A") -> setWidth(4);
$objPHPExcel -> getActiveSheet() -> getColumnDimension("B") -> setWidth(30);
$objPHPExcel -> getActiveSheet() -> getColumnDimension("C") -> setWidth(4);
$objPHPExcel -> getActiveSheet() -> getColumnDimension("D") -> setWidth(4);
$objPHPExcel -> getActiveSheet() -> getColumnDimension("E") -> setWidth(10);
$objPHPExcel -> getActiveSheet() -> getColumnDimension("F") -> setWidth(12);
$objPHPExcel -> getActiveSheet() -> getColumnDimension("G") -> setWidth(12);
$objPHPExcel -> getActiveSheet() -> getColumnDimension("H") -> setWidth(14);

$objPHPExcel->getActiveSheet()->getStyle("A1")->getFont()->setName('Dotum')->setSize(28);	
$objPHPExcel->setActiveSheetIndex(0)->mergeCells('A1:H1')->setCellValue('A1', "見    積    書");
$objPHPExcel->getActiveSheet()->getStyle("A1")->getFont()->setName('Dotum')->setUnderline(true);	

// $styleArray = array(
  // 'font' => array(
    // 'underline' => PHPExcel_Style_Font::UNDERLINE_SINGLE
  // )
// );

// $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray($styleArray);
// unset($styleArray);

// 이미지 삽입하는 방법

$iCol = 'F'; // 컬럼번호
$iRow = 3; // 행번호
$photo_path = "./img/jtechsign.png"; // 이미지 경로
$objDrawing = new PHPExcel_Worksheet_Drawing();
$objDrawing->setName('Photo '.$iRow);
$objDrawing->setDescription('Photo '.$iRow);
$objDrawing->setPath($photo_path);

$objDrawing->setResizeProportional(true);

$objDrawing->setWidth(250);

$objDrawing->setOffsetX(90);

$objDrawing->setOffsetY(90);

$objDrawing->setCoordinates($iCol.$iRow);

$objDrawing->setWorksheet($objPHPExcel->getActiveSheet());

$objPHPExcel->getActiveSheet()->getRowDimension($iRow)->setRowHeight(90); // 행높이 설정


$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(1)->setRowHeight(50);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(2)->setRowHeight(30);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(3)->setRowHeight(30);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(4)->setRowHeight(30);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(5)->setRowHeight(30);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(6)->setRowHeight(30);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(7)->setRowHeight(20);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(8)->setRowHeight(20);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(9)->setRowHeight(20);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(10)->setRowHeight(20);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(11)->setRowHeight(20);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(12)->setRowHeight(15);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(13)->setRowHeight(15);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(14)->setRowHeight(15);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(15)->setRowHeight(15);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(16)->setRowHeight(15);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(17)->setRowHeight(15);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(18)->setRowHeight(15);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(19)->setRowHeight(15);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(20)->setRowHeight(15);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(21)->setRowHeight(15);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(22)->setRowHeight(15);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(23)->setRowHeight(15);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(24)->setRowHeight(15);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(25)->setRowHeight(15);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(26)->setRowHeight(15);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(27)->setRowHeight(15);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(28)->setRowHeight(15);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(29)->setRowHeight(15);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(30)->setRowHeight(15);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(31)->setRowHeight(15);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(32)->setRowHeight(15);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(33)->setRowHeight(15);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(34)->setRowHeight(35);



//텍스트 줄바꿈 허용

$objPHPExcel->setActiveSheetIndex(0)->mergeCells('D2:D6')->setCellValue('d2', "공" . chr(13) . chr(13)  . "급" . chr(13). chr(13).  "자");
$objPHPExcel->getActiveSheet()->getStyle('D2:D6')->getAlignment()->setWrapText(true);

$objPHPExcel->setActiveSheetIndex(0)->setCellValue('E2', "등록번호");
$objPHPExcel->setActiveSheetIndex(0)->setCellValue('E3', "상 호");
$objPHPExcel->setActiveSheetIndex(0)->setCellValue('E4', "사업장" . chr(13)  . "소재지"  );
$objPHPExcel->getActiveSheet()->getStyle('E4')->getAlignment()->setWrapText(true);
$objPHPExcel->setActiveSheetIndex(0)->setCellValue('E5', "업태");
$objPHPExcel->setActiveSheetIndex(0)->setCellValue('E6', "전화번호");

$objPHPExcel->setActiveSheetIndex(0)->mergeCells('F2:H2')->setCellValue('f2', "346-05-01123");
$objPHPExcel->getActiveSheet()->getStyle("f2")->getFont()->setName('Dotum')->setSize(16);	
$objPHPExcel->setActiveSheetIndex(0)->setCellValue('F3', "제이테크");
$objPHPExcel->setActiveSheetIndex(0)->setCellValue('G3', "대표자");
$objPHPExcel->setActiveSheetIndex(0)->setCellValue('H3', "정동수");
$objPHPExcel->setActiveSheetIndex(0)->mergeCells('F4:H4')->setCellValue('f4', "인천광역시 서구 거북로 62-1 2층(석남동)");
$objPHPExcel->setActiveSheetIndex(0)->setCellValue('F5', "건설업");
$objPHPExcel->setActiveSheetIndex(0)->setCellValue('G5', "종목");
$objPHPExcel->setActiveSheetIndex(0)->setCellValue('H5', "의장공사," .  chr(13)  . "특수도장"  );
$objPHPExcel->getActiveSheet()->getStyle('H5')->getAlignment()->setWrapText(true);
$objPHPExcel->setActiveSheetIndex(0)->mergeCells('F6:H6')->setCellValue('f6', "기술팀장/이사 정동수 010-2432-7765");

$objPHPExcel->setActiveSheetIndex(0)->mergeCells('A2:C3')->setCellValue('A2', $et_writeday);
$objPHPExcel->getActiveSheet()->getStyle ( "A2:C3" )->getAlignment()->setHorizontal (PHPExcel_Style_Alignment::HORIZONTAL_RIGHT );  // 가로 오른쪽 정렬
$objPHPExcel->getActiveSheet()->getStyle("A2")->getFont()->setName('Dotum')->setSize(16);	

$objPHPExcel->setActiveSheetIndex(0)->mergeCells('A4:C4')->setCellValue('A4', $secondord . " 귀하");
$objPHPExcel->getActiveSheet()->getStyle ( "A4:C4" )->getAlignment()->setHorizontal (PHPExcel_Style_Alignment::HORIZONTAL_RIGHT );  // 가로 오른쪽 정렬
// 밑줄치기
$objPHPExcel->getActiveSheet()->getStyle("A4")->getFont()->setName('Dotum')->setSize(16)->setUnderline(true);	


$objPHPExcel->setActiveSheetIndex(0)->mergeCells('A5:C6')->setCellValue('A5', " 아래와 같이 견적합니다.");
$objPHPExcel->getActiveSheet()->getStyle("A5")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);  // 가로 오른쪽 정렬

$objPHPExcel->setActiveSheetIndex(0)->mergeCells('A7:H7')->  setCellValue('A7', "     1. 현  장  명 : " . $et_wpname);
$objPHPExcel->setActiveSheetIndex(0)->mergeCells('A8:H8')->  setCellValue('A8', "     2. 공  사  명 : " . $workplacename);
$objPHPExcel->setActiveSheetIndex(0)->mergeCells('A9:H9')->  setCellValue('A9', "     3. 공사 일정 : " . $et_schedule);
$objPHPExcel->setActiveSheetIndex(0)->mergeCells('A10:H10')->setCellValue('A10', "     4. 공사 인원 : " . $et_person);
$objPHPExcel->getActiveSheet()->getStyle("A7:A10")->getFont()->setName('Dotum')->setSize(13);
$objPHPExcel->setActiveSheetIndex(0)->mergeCells('A11:H11')->setCellValue('A11', "세부내역");

// $objPHPExcel->getActiveSheet()->getStyle('E4:E10')->getAlignment()->setWrapText(true);
// $objPHPExcel->setActiveSheetIndex(0)->mergeCells('E4:E10')->setCellValue('E4', "발" . chr(10) . chr(10) . "주" . chr(10). chr(10) . "처");

// $objPHPExcel->getActiveSheet()->getStyle("F4")->getFont()->setName('Dotum')->setSize(16);	


// $objPHPExcel->getActiveSheet()->getStyle ( "G6:J10" )->getAlignment ()->setHorizontal ( PHPExcel_Style_Alignment::HORIZONTAL_LEFT );  // 가로 왼쪽 정렬



// 12~ 25까지 설정
for ($i=0;$i<20;$i++)
{
  $objPHPExcel->setActiveSheetIndex(0)->getRowDimension(12+$i)->setRowHeight(15);  
}
$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A12', "순번");
$objPHPExcel->setActiveSheetIndex(0)->setCellValue('B12', "품목");
$objPHPExcel->setActiveSheetIndex(0)->mergeCells('C12:D12')->setCellValue('C12', "규격");
$objPHPExcel->setActiveSheetIndex(0)->setCellValue('E12', "수량");
$objPHPExcel->setActiveSheetIndex(0)->setCellValue('F12', "단가");
$objPHPExcel->setActiveSheetIndex(0)->setCellValue('G12', "금액");
$objPHPExcel->setActiveSheetIndex(0)->setCellValue('H12', "비고");


$total = 0;
// 품명/규격/수량/단가 비고 출력
for ($i=0; $i<20; $i++)
{
	$j=$i+13;		
	$objPHPExcel->setActiveSheetIndex(0)->mergeCells('C' . $j . ':D' . $j );	
	
	if($description[$i] !='')	 // 품목이 null이 아닌경우
	{
		$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A' . $j , $i + 1);
		$objPHPExcel->setActiveSheetIndex(0)->setCellValue('B' . $j , $description[$i]);
		$objPHPExcel->setActiveSheetIndex(0)->setCellValue('C' . $j , $spec[$i]);
		$objPHPExcel->setActiveSheetIndex(0)->setCellValue('E' . $j , $ea[$i]);
		$objPHPExcel->setActiveSheetIndex(0)->setCellValue('F' . $j, $unit[$i]);
		$objPHPExcel->setActiveSheetIndex(0)->setCellValue('G' . $j, $amount[$i]);
		$objPHPExcel->setActiveSheetIndex(0)->setCellValue('H' . $j, $comment[$i]);
		
	  $total += (int)conv_num($amount[$i]);
		
	}
	
	// 숫자형으로 나타내기 숫자 중간 콤마 구현
	$objPHPExcel->setActiveSheetIndex(0)->getStyle('E' . $j)->getNumberFormat()->setFormatCode('#,##0');
	$objPHPExcel->setActiveSheetIndex(0)->getStyle('F' . $j)->getNumberFormat()->setFormatCode('#,##0');
	$objPHPExcel->setActiveSheetIndex(0)->getStyle('G' . $j)->getNumberFormat()->setFormatCode('#,##0');
}
	
// 합계 숫자형으로 보여주기 콤마유지
$objPHPExcel->setActiveSheetIndex(0)->getStyle('G33')->getNumberFormat()->setFormatCode('#,##0');
// 	36 ROW 기술하기
$objPHPExcel->setActiveSheetIndex(0)->mergeCells('A33:B33')->setCellValue('A33', '합계');
$objPHPExcel->setActiveSheetIndex(0)->mergeCells('C33:D33');

$objPHPExcel->setActiveSheetIndex(0)->mergeCells('A34:H34')->setCellValue('G33', $total); // 합계
$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A34', '비고');


// for ($i=0;$i<23;$i++)
  // $objPHPExcel->setActiveSheetIndex(0)->getRowDimension(15+$i)->setRowHeight(13.5);

// for ($i=0;$i<6;$i++)
  // $objPHPExcel->setActiveSheetIndex(0)->getRowDimension(38+$i)->setRowHeight(18);

// $objPHPExcel->getActiveSheet()->getStyle("A3")->getFont()->setName('Dotum')->setSize(15);	
// $objPHPExcel -> setActiveSheetIndex(0)-> setCellValue("A3", "㈜미래기업 貴中");
// $objPHPExcel -> getActiveSheet() -> getStyle("A3") -> getFont() -> setBold(true);

// $styleArray = array(
  // 'font' => array(
    // 'underline' => PHPExcel_Style_Font::UNDERLINE_SINGLE
  // )
// );

// $objPHPExcel->getActiveSheet()->getStyle('A3')->applyFromArray($styleArray);   // 밑줄치는 것

// // $objPHPExcel -> setActiveSheetIndex(0) -> setCellValue("B5", date("Y-m-d", time()));  // 날짜형식으로 보이기




// $objPHPExcel -> getActiveSheet() -> getStyle("A5:C9") -> getFont() -> setBold(true);

// $objPHPExcel->getActiveSheet()->getStyle ( "A5:G10" )->getAlignment ()->setHorizontal ( PHPExcel_Style_Alignment::HORIZONTAL_CENTER );  // 가로 가운데 정렬
// $objPHPExcel->getActiveSheet()->getStyle ( "A5:G10" )->getAlignment ()->setVertical (PHPExcel_Style_Alignment::VERTICAL_CENTER );  // 세로 가운데 정렬

// // 셀의 테두리 지정 (바깥쪽 테두리 - 진하게) 4각 테두리
// $objPHPExcel->getActiveSheet()->getStyle ( "A5:C9" )->getBorders ()->getInside () ->setBorderStyle ( PHPExcel_Style_Border::BORDER_THIN );
// $objPHPExcel->getActiveSheet()->getStyle ( "A5:C9" )->getBorders ()->getOutline () ->setBorderStyle ( PHPExcel_Style_Border::BORDER_THICK );

// 테두리 두께 관련 기타 옵션
// PHPExcel_Style_Border::BORDER_MEDIUM : 일반 두께
// PHPExcel_Style_Border::BORDER_THIN : 얅은 두께
// 셀 테두리 종류 관련 옵션
// 바깥쪽 테두리 : 예제의 소스와 동일  ->getOutline()->
// 셀 전체 (바깥 + 안쪽) :  ->getAllBorders()->
// 안쪽 : ->getInside()->
// 세로선 : ->getVertical()->
// 가로선 : ->getHorizontal()->


// $objPHPExcel -> getActiveSheet() -> getStyle("E5:G10") -> getFont() -> setBold(true);

// 글씨 크기 넘어가는 것 자동조절
// $objPHPExcel->getActiveSheet()->getStyle("E8:F8")->getFont()->setName('Dotum')->setSize(7);	


// $objPHPExcel -> setActiveSheetIndex(0)-> setCellValue("A13", "=F40");
// $objPHPExcel -> setActiveSheetIndex(0)-> setCellValue("B13", "원)-VAT.별도");
// $objPHPExcel->setActiveSheetIndex(0)->mergeCells('C13:G13')->setCellValue('C13',"*현장별 잠설치공사 막판유/막판무/쪽잠 구분 청구");
// $objPHPExcel -> getActiveSheet() -> getStyle("C13") -> getFill() -> setFillType(PHPExcel_Style_Fill::FILL_SOLID) -> getStartColor() -> setRGB("FFFF00");
 // // 폰트색상 변경
// $objPHPExcel ->getActiveSheet()->getStyle ( "C13" )->getFont ()->getColor ()->setRGB ( 'FF0000' );  // 빨간색
// $objPHPExcel->getActiveSheet()->getStyle("C13")->getFont()->setName('Dotum')->setSize(11);	
// $objPHPExcel -> getActiveSheet() -> getStyle("C13") -> getFont() -> setBold(true);

// 셀의 테두리 지정 (바깥쪽 테두리 - 진하게) 4각 테두리

$objPHPExcel->getActiveSheet()->getStyle ( "A1:H200" )->getAlignment ()->setHorizontal ( PHPExcel_Style_Alignment::HORIZONTAL_CENTER );  // 가로 가운데 정렬
$objPHPExcel->getActiveSheet()->getStyle ( "A1:H200" )->getAlignment ()->setVertical (PHPExcel_Style_Alignment::VERTICAL_CENTER );  // 세로 가운데 정렬
// 가로정렬이 다른 경우만 가져온다.
$objPHPExcel->getActiveSheet()->getStyle("A7:A11")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT); 
$objPHPExcel->getActiveSheet()->getStyle("A34")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT); 
$objPHPExcel->getActiveSheet()->getStyle("F13:G33")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT); 


$objPHPExcel->getActiveSheet()->getStyle ( "A2:C6" )->getBorders ()->getOutline () ->setBorderStyle ( PHPExcel_Style_Border::BORDER_MEDIUM );

$objPHPExcel->getActiveSheet()->getStyle ( "D2:H6" )->getBorders ()->getInside () ->setBorderStyle ( PHPExcel_Style_Border::BORDER_THIN );
$objPHPExcel->getActiveSheet()->getStyle ( "D2:H6" )->getBorders ()->getOutline () ->setBorderStyle ( PHPExcel_Style_Border::BORDER_MEDIUM );
$objPHPExcel->getActiveSheet()->getStyle ( "A12:H34" )->getBorders ()->getInside () ->setBorderStyle ( PHPExcel_Style_Border::BORDER_THIN );
$objPHPExcel->getActiveSheet()->getStyle ( "A12:H34" )->getBorders ()->getOutline () ->setBorderStyle ( PHPExcel_Style_Border::BORDER_MEDIUM );

//$objPHPExcel->getActiveSheet()->getStyle ( "A27:J34"  )->getBorders ()->getOutline () ->setBorderStyle ( PHPExcel_Style_Border::BORDER_MEDIUM );
//$objPHPExcel->getActiveSheet()->getStyle ( "A36:J46"  )->getBorders ()->getOutline () ->setBorderStyle ( PHPExcel_Style_Border::BORDER_MEDIUM );


// // Set active sheet index to the first sheet, so Excel opens this as the first sheet
// $objPHPExcel->setActiveSheetIndex(0);
// // Set the page layout view as page layout
// $objPHPExcel->getActiveSheet()->getSheetView()->setView(PHPExcel_Worksheet_SheetView::SHEETVIEW_PAGE_LAYOUT);

// $objPHPExcel->getActiveSheet()->getStyle ( "B15:D40" )->getAlignment ()->setHorizontal ( PHPExcel_Style_Alignment::HORIZONTAL_CENTER );  // 가로 가운데 정렬

// // $objPHPExcel -> getActiveSheet() -> getStyle(sprintf("A1:D%s", $count)) -> getAlignment() -> setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
// // $objPHPExcel -> getActiveSheet() -> getStyle(sprintf("A1:D%s", $count)) -> getBorders() -> getAllBorders() -> setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
// // $objPHPExcel -> getActiveSheet() -> getStyle("A1:D1") -> getFont() -> setBold(true);
// // $objPHPExcel -> getActiveSheet() -> getStyle("A1:D1") -> getFill() -> setFillType(PHPExcel_Style_Fill::FILL_SOLID) -> getStartColor() -> setRGB("CECBCA");
// // $objPHPExcel -> getActiveSheet() -> getStyle(sprintf("A2:D%s", $count)) -> getFill() -> setFillType(PHPExcel_Style_Fill::FILL_SOLID) -> getStartColor() -> setRGB("F4F4F4");

// $objPHPExcel -> setActiveSheetIndex(0)-> setCellValue("A40", "합  계");
// $objPHPExcel->getActiveSheet()->getStyle ( "A40" )->getAlignment ()->setHorizontal ( PHPExcel_Style_Alignment::HORIZONTAL_CENTER );  // 가로 가운데 정렬
// $objPHPExcel->getActiveSheet()->getStyle("A40")->getFont()->setName('Dotum')->setSize(13);	
// $objPHPExcel->getActiveSheet()->getStyle("B40:G40")->getFont()->setName('Dotum')->setSize(8);	
// $objPHPExcel -> getActiveSheet() -> getStyle("A40:A41") -> getFont() -> setBold(true);
// $objPHPExcel -> setActiveSheetIndex(0)-> setCellValue("A41", "* 특기사항");

// $objPHPExcel->getActiveSheet()->getStyle("A13")->getFont()->setName('Dotum')->setSize(14);	
// $objPHPExcel -> getActiveSheet() -> getStyle("A13") -> getFont() -> setBold(true);

// // getNumberFormat(), setFormatCode() 함수를 사용한다.
// // setFormatCode() 함수에 앞자리 0이 출력되게끔 문자열의 자리수 만큼 0을 입력한다.
// // $objPHPExcel -> getActiveSheet() -> getStyle(sprintf("A2:A%s", $count)) -> getNumberFormat() -> setFormatCode("00000");

// $objPHPExcel->getActiveSheet()->getStyle("A15:A39")->getFont()->setName('Dotum')->setSize(7);	

$companyName = $secondord;

$objPHPExcel->getActiveSheet()->getStyle("A1:H200")->getFont()->setName('Dotum');	 // 전체 폰트 적용
$objPHPExcel->getActiveSheet()->getStyle("F4")->getFont()->setName('Dotum')->setSize(10);	
$objPHPExcel->getActiveSheet()->getStyle("F6")->getFont()->setName('Dotum')->setSize(10);	


$objPHPExcel -> getActiveSheet() -> setTitle($companyName . " 제이테크 견적서");
$objPHPExcel -> setActiveSheetIndex(0);
$filename = iconv("UTF-8", "EUC-KR", "제이테크 견적서(" . $companyName . ") ");


// pdf는 여기부분이 excel과 다름
header("Content-Type:application/pdf");
header("Content-Disposition: attachment;filename=".$filename.".pdf");
header("Cache-Control:max-age=0");

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, "PDF");
$objWriter -> save("php://output");
?>