<?php
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
			$et_deadline=$row["et_deadline"];   
			$et_paymethod=$row["et_paymethod"];   
			$et_validation=$row["et_validation"];   
			$et_itemname=$row["et_itemname"];   
			$et_receiver=$row["et_receiver"];   
			$et_content=$row["et_content"];	  
			$et_note=$row["et_note"];	  

			if(trim($et_wpname)==null)
			   $et_wpname=$address;   // 현장명이 없을때는 주소로

			// 품명이 없다면  workplacename이 품명이 됨
			if(trim($et_receiver) == null)
				$et_receiver = $workplacename;

			// 납기일이 없으면
			if(trim($et_deadline) == null)
				$et_deadline = '발주 후 20일(추후 협의)';

			// 결재방식이 없으면
			if(trim($et_paymethod) == null)
				$et_paymethod = '현금';

			// 유효기간이 없으면
			if(trim($et_validation) == null)
				$et_validation = '견적일로부터 15 日';


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
$objPHPExcel -> getActiveSheet() -> getColumnDimension("B") -> setWidth(31);
$objPHPExcel -> getActiveSheet() -> getColumnDimension("C") -> setWidth(15);
$objPHPExcel -> getActiveSheet() -> getColumnDimension("D") -> setWidth(6);
$objPHPExcel -> getActiveSheet() -> getColumnDimension("E") -> setWidth(6);
$objPHPExcel -> getActiveSheet() -> getColumnDimension("F") -> setWidth(10);
$objPHPExcel -> getActiveSheet() -> getColumnDimension("G") -> setWidth(10);
$objPHPExcel -> getActiveSheet() -> getColumnDimension("H") -> setWidth(10);


$objPHPExcel -> setActiveSheetIndex(0)-> mergeCells('A2:H2');
// $objPHPExcel->setActiveSheetIndex(0)->getStyle('A2')->getNumberFormat()-> setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_YYYYMMDD3);
// $objPHPExcel -> getActiveSheet()-> getStyle("A2") -> getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_YYYYMMDD3);

	
// $styleArray = array(
  // 'font' => array(
    // 'underline' => PHPExcel_Style_Font::UNDERLINE_SINGLE
  // )
// );

// $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray($styleArray);
// unset($styleArray);

// 이미지 삽입하는 방법

$iCol = 'E'; // 컬럼번호
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

$objPHPExcel->getActiveSheet()->getRowDimension($iRow)->setRowHeight(80); // 행높이 설정

$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(1)->setRowHeight(50);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(2)->setRowHeight(15);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(3)->setRowHeight(25);  // 귀하
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(4)->setRowHeight(23);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(5)->setRowHeight(18);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(6)->setRowHeight(18);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(7)->setRowHeight(18);
$objPHPExcel->setActiveSheetIndex(0)->getRowDimension(8)->setRowHeight(18);

for ($i=0;$i<24;$i++)	
     $objPHPExcel->setActiveSheetIndex(0)->getRowDimension($i+10)->setRowHeight(15);

$objPHPExcel -> setActiveSheetIndex(0)-> getStyle ( "A1:H100" )-> getAlignment ()-> setHorizontal ( PHPExcel_Style_Alignment::HORIZONTAL_CENTER );

$objPHPExcel->setActiveSheetIndex(0)->mergeCells('D3:H4');
$objPHPExcel->setActiveSheetIndex(0)->mergeCells('D5:H5')->setCellValue('D5', "인천광역시 서구 거북로62-1,2층(석남동)");
$objPHPExcel->setActiveSheetIndex(0)->mergeCells('D6:H6')->setCellValue('D6', "Tel : 032)225-7765 Fax : 032)225-7765");
$objPHPExcel->setActiveSheetIndex(0)->mergeCells('D7:H7')->setCellValue('D7', "Mobile : 010) 2432-7765");
$objPHPExcel->setActiveSheetIndex(0)->mergeCells('D8:H8')->setCellValue('D8', "E-Mail : j-tech.el@daum.net");

$objPHPExcel->getActiveSheet()->getStyle ( "D5:H8" )->getAlignment()->setHorizontal (PHPExcel_Style_Alignment::HORIZONTAL_RIGHT );  // 가로 오른쪽 정렬

 
$objPHPExcel->setActiveSheetIndex(0)->mergeCells('A3:C3')->setCellValue('A3', $et_receiver . "  貴下");
$objPHPExcel->setActiveSheetIndex(0)->mergeCells('A4:C4')->setCellValue('A4', "품   명 : " . $et_itemname );
$objPHPExcel->setActiveSheetIndex(0)->mergeCells('A5:C5')->setCellValue('A5', "납   기 : " . $et_deadline );
$objPHPExcel->setActiveSheetIndex(0)->mergeCells('A6:C6')->setCellValue('A6', "현 장 명 : " . $et_wpname );
$objPHPExcel->setActiveSheetIndex(0)->mergeCells('A7:C7')->setCellValue('A7', "결재방법 : "  . $et_paymethod );
$objPHPExcel->setActiveSheetIndex(0)->mergeCells('A8:C8')->setCellValue('A8', "유효기간 : "  . $et_validation );


$objPHPExcel->getActiveSheet()->getStyle ( "A3:C8" )->getAlignment()->setHorizontal (PHPExcel_Style_Alignment::HORIZONTAL_LEFT );  // 가로 오른쪽 정렬
$objPHPExcel -> setActiveSheetIndex(0)-> getStyle ( "B11:B36" )-> getAlignment ()-> setHorizontal ( PHPExcel_Style_Alignment::HORIZONTAL_LEFT );

// 9~ + 27  36까지 설정
for ($i=0;$i<25;$i++)
{
  $objPHPExcel->setActiveSheetIndex(0)->getRowDimension(10+$i)->setRowHeight(15);  
}

$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A10', "順番");
$objPHPExcel->setActiveSheetIndex(0)->setCellValue('B10', "品     名");
$objPHPExcel->setActiveSheetIndex(0)->setCellValue('C10', "規     格");
$objPHPExcel->setActiveSheetIndex(0)->setCellValue('D10', "單位");
$objPHPExcel->setActiveSheetIndex(0)->setCellValue('E10', "數量");
$objPHPExcel->setActiveSheetIndex(0)->setCellValue('F10', "單 價");
$objPHPExcel->setActiveSheetIndex(0)->setCellValue('G10', "金 額");
$objPHPExcel->setActiveSheetIndex(0)->setCellValue('H10', "備 考");


$total = 0;
$innerCount = 1;
$foundSogae = 0;  // 소계를 찾으면 다음에 순번을 찍어준다.
// 품명/규격/수량/단가 비고 출력
for ($i=0; $i<25; $i++)
{
	$j=$i+11;		
	
	if($description[$i] !='')	 // 품목이 null이 아닌경우
	{
		
	  	if($i==0 || $foundSogae == 1)
			{
				$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A' . $j , $innerCount );
				$foundSogae = 0;
			}
		$objPHPExcel->setActiveSheetIndex(0)->setCellValue('B' . $j , $description[$i]);
		$objPHPExcel->setActiveSheetIndex(0)->setCellValue('C' . $j , $spec[$i]);
		$objPHPExcel->setActiveSheetIndex(0)->setCellValue('D' . $j , $unit[$i]);
		$objPHPExcel->setActiveSheetIndex(0)->setCellValue('E' . $j , $quantity[$i]);
		$objPHPExcel->setActiveSheetIndex(0)->setCellValue('F' . $j, $unitprice[$i]);
		$objPHPExcel->setActiveSheetIndex(0)->setCellValue('G' . $j, $amount[$i]);
		$objPHPExcel->setActiveSheetIndex(0)->setCellValue('H' . $j, $comment[$i]);
		
		
		// 모든 공백제거 $out=preg_replace("/\s+/","",$in);
		$des = preg_replace("/\s+/","",$description[$i]);
	 if($des=="소계")
	    {
		 $total += (int)conv_num($amount[$i]);
		 $foundSogae = 1;   // 소개 찾음 변수 적용
		 $innerCount++ ;    // 순번 증가
		 
		 // 소계인 곳의 정렬 가운데로 하기
		 $objPHPExcel -> setActiveSheetIndex(0)-> getStyle ( 'B' . $j )-> getAlignment ()-> setHorizontal ( PHPExcel_Style_Alignment::HORIZONTAL_CENTER );
		 $objPHPExcel -> setActiveSheetIndex(0)-> getStyle( 'B' . $j ) -> getFont() -> setBold(true);
		 // 소계 색상변경
		  $objPHPExcel ->getActiveSheet()-> getStyle( 'B' . $j ) ->getFont ()->getColor ()->setRGB ( 'FF0000' );  // 빨간색
		 
		}

		
	}
	

// 합계금액 나타내기
$hangulAmountStr = "一金" . number2hangul($total) . "원整" ;
$hangulAmount = "￦ " . number_format($total) .  " (VAT별도)" ;



$objPHPExcel->setActiveSheetIndex(0)->mergeCells('A9:B9')->setCellValue('A9', '합계금액 :'  . $hangulAmountStr );	
$objPHPExcel->setActiveSheetIndex(0)->mergeCells('C9:H9')->setCellValue('C9',  $hangulAmount );		
	
	// 숫자형으로 나타내기 숫자 중간 콤마 구현
	$objPHPExcel->setActiveSheetIndex(0)->getStyle('E' . $j)->getNumberFormat()->setFormatCode('#,##0');
	$objPHPExcel->setActiveSheetIndex(0)->getStyle('F' . $j)->getNumberFormat()->setFormatCode('#,##0');
	$objPHPExcel->setActiveSheetIndex(0)->getStyle('G' . $j)->getNumberFormat()->setFormatCode('#,##0');
}
	
// 합계 숫자형으로 보여주기 콤마유지
$objPHPExcel->setActiveSheetIndex(0)->getStyle('G36')->getNumberFormat()->setFormatCode('#,##0');
// 	36 ROW 기술하기
$objPHPExcel->setActiveSheetIndex(0)->mergeCells('A36:B36')->setCellValue('A36', '합 계');

$objPHPExcel->setActiveSheetIndex(0)->setCellValue('G36', $total); // 합계

// 비고는 높이 셀의 합치는 형태
//텍스트 줄바꿈 허용 예제
 $objPHPExcel->setActiveSheetIndex(0)->mergeCells('A37:A43')->setCellValue('A37', "備" . chr(13) . chr(13)  . "考");
 $objPHPExcel->getActiveSheet()->getStyle('A37:A43')->getAlignment()->setWrapText(true);
 
$objPHPExcel->setActiveSheetIndex(0)->mergeCells('B37:H37')->setCellValue('B37', '1. 부가세 별도.');
$objPHPExcel->setActiveSheetIndex(0)->mergeCells('B38:H38')->setCellValue('B38', '');
$objPHPExcel->setActiveSheetIndex(0)->mergeCells('B39:H39')->setCellValue('B39', '');
$objPHPExcel->setActiveSheetIndex(0)->mergeCells('B40:H40')->setCellValue('B40', '');
$objPHPExcel->setActiveSheetIndex(0)->mergeCells('B41:H41')->setCellValue('B41', '');
$objPHPExcel->setActiveSheetIndex(0)->mergeCells('B42:H42')->setCellValue('B42', '');
$objPHPExcel->setActiveSheetIndex(0)->mergeCells('B43:H43')->setCellValue('B43', '');

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

$objPHPExcel->setActiveSheetIndex(0)->getStyle ( "A1:H200" )->getAlignment ()->setVertical (PHPExcel_Style_Alignment::VERTICAL_CENTER );  // 세로 가운데 정렬
// 가로정렬이 다른 경우만 가져온다.
$objPHPExcel->setActiveSheetIndex(0)->getStyle("A3:A9")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT); 
$objPHPExcel->setActiveSheetIndex(0)->getStyle("A34")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT); 

$objPHPExcel->setActiveSheetIndex(0)->getStyle ( "A10:H36" )->getBorders ()->getInside () ->setBorderStyle ( PHPExcel_Style_Border::BORDER_DOTTED);
$objPHPExcel->setActiveSheetIndex(0)->getStyle ( "A10:H36" )->getBorders ()->getOutline () ->setBorderStyle ( PHPExcel_Style_Border::BORDER_MEDIUM );


// $objPHPExcel->getActiveSheet()->getStyle ( "A2:C6" )->getBorders ()->getOutline () ->setBorderStyle ( PHPExcel_Style_Border::BORDER_MEDIUM );

// $objPHPExcel->getActiveSheet()->getStyle ( "D2:H6" )->getBorders ()->getInside () ->setBorderStyle ( PHPExcel_Style_Border::BORDER_THIN );
// $objPHPExcel->getActiveSheet()->getStyle ( "D2:H6" )->getBorders ()->getOutline () ->setBorderStyle ( PHPExcel_Style_Border::BORDER_MEDIUM );
//$objPHPExcel->getActiveSheet()->getStyle ( "A12:H34" )->getBorders ()->getInside () ->setBorderStyle ( PHPExcel_Style_Border::BORDER_THIN );
//$objPHPExcel->getActiveSheet()->getStyle ( "A12:H34" )->getBorders ()->getOutline () ->setBorderStyle ( PHPExcel_Style_Border::BORDER_MEDIUM );

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

// $objPHPExcel->setActiveSheetIndex(0)->getStyle("A1:H200")->getFont()->setName('Dotum');	 // 
$objPHPExcel-> setActiveSheetIndex(0)->getStyle("A2")->getFont()->setName('Dotum')->setSize(10);	
$objPHPExcel-> setActiveSheetIndex(0)->getStyle("A3")->getFont()->setName('Dotum')->setSize(14);	
$objPHPExcel -> setActiveSheetIndex(0) -> getStyle("A3") -> getFont() -> setBold(true);

$objPHPExcel -> setActiveSheetIndex(0)-> getStyle ( "A2" )-> getAlignment ()-> setHorizontal ( PHPExcel_Style_Alignment::HORIZONTAL_RIGHT );  // 가로 가운데 정렬
$objPHPExcel -> setActiveSheetIndex(0)-> getStyle ( "B37" )-> getAlignment ()-> setHorizontal ( PHPExcel_Style_Alignment::HORIZONTAL_LEFT );
$objPHPExcel -> setActiveSheetIndex(0)-> getStyle ( "C11:D36" )-> getAlignment ()-> setHorizontal ( PHPExcel_Style_Alignment::HORIZONTAL_CENTER );
$objPHPExcel -> setActiveSheetIndex(0)-> getStyle ( "E11:G36" )-> getAlignment ()-> setHorizontal ( PHPExcel_Style_Alignment::HORIZONTAL_RIGHT );

$objPHPExcel -> setActiveSheetIndex(0)-> setCellValue('A2', "견적작성일 : " . date("Y", strtotime($et_writeday)) . "년" . date("m", strtotime($et_writeday)) . "월".date("d", strtotime($et_writeday)) . "일" );	
$objPHPExcel -> setActiveSheetIndex(0)-> getStyle("A2:H2" )->getFont()->setName('Dotum') -> setUnderline(true);
$objPHPExcel -> setActiveSheetIndex(0) -> getStyle("G12:G36") -> getFont() -> setBold(true);
$objPHPExcel -> setActiveSheetIndex(0) -> getStyle("A11:A35") -> getFont() -> setBold(true);
$objPHPExcel -> setActiveSheetIndex(0) -> getStyle("A36:H36") -> getFont() -> setBold(true);
$objPHPExcel -> setActiveSheetIndex(0) -> getStyle("A9:H9") -> getFont() -> setBold(true);

$objPHPExcel -> setActiveSheetIndex(0)->getStyle("A1")->getFont()->setName('Dotum')->setSize(28);	
$objPHPExcel -> setActiveSheetIndex(0)->mergeCells('A1:H1')->setCellValue('A1', "見    積    書");
$objPHPExcel -> setActiveSheetIndex(0)-> getStyle ("A1:H1" )-> getAlignment ()-> setHorizontal ( PHPExcel_Style_Alignment::HORIZONTAL_CENTER );
$objPHPExcel -> setActiveSheetIndex(0)-> getStyle("A1:H1" )->getFont()->setName('Dotum') -> setBold(true) ;
$objPHPExcel -> setActiveSheetIndex(0)-> getStyle("A1:H1" )->getFont()->setName('Dotum') -> setUnderline(true);


// 상단표의 테두리 정함
// 밑단만 점선으로 표현하기
$objPHPExcel->setActiveSheetIndex(0)->getStyle ( "A3:C9" )->getBorders ()-> getHorizontal() ->setBorderStyle ( PHPExcel_Style_Border::BORDER_DOTTED);
$objPHPExcel->setActiveSheetIndex(0)->getStyle ( "A36:h37" )->getBorders ()-> getHorizontal() ->setBorderStyle ( PHPExcel_Style_Border::BORDER_MEDIUM);

// 비고란 테두리
$objPHPExcel->setActiveSheetIndex(0)->getStyle ( "A37:H43" )->getBorders ()-> getVertical() ->setBorderStyle ( PHPExcel_Style_Border::BORDER_DOTTED);
$objPHPExcel->setActiveSheetIndex(0)->getStyle ( "A37:H43" )->getBorders ()-> getOutline() ->setBorderStyle ( PHPExcel_Style_Border::BORDER_MEDIUM);


$objPHPExcel->setActiveSheetIndex(0)->getStyle ( "A9:H9" )->getBorders ()->getAllBorders() ->setBorderStyle ( PHPExcel_Style_Border::BORDER_MEDIUM);
//$objPHPExcel->setActiveSheetIndex(0)->getStyle ( "A10:H10" )->getBorders ()->getInside () ->setBorderStyle ( PHPExcel_Style_Border::BORDER_THIN);
//$objPHPExcel->setActiveSheetIndex(0)->getStyle ( "A10:H10" )->getBorders ()->getOutline () ->setBorderStyle ( PHPExcel_Style_Border::BORDER_MEDIUM );

// 셀 색상 넣어주기
$objPHPExcel -> getActiveSheet() -> getStyle("A36:H36") -> getFill() -> setFillType(PHPExcel_Style_Fill::FILL_SOLID) -> getStartColor() -> setRGB("d2d2d2");
$objPHPExcel -> getActiveSheet() -> getStyle("A9:H9") -> getFill() -> setFillType(PHPExcel_Style_Fill::FILL_SOLID) -> getStartColor() -> setRGB("d2d2d2");


$companyName = $et_itemname;

$objPHPExcel -> getActiveSheet() -> setTitle($companyName . " 제이테크 견적서");
$objPHPExcel -> setActiveSheetIndex(0);
$filename = iconv("UTF-8", "EUC-KR", "제이테크 견적서(" . $companyName . ") ");

header("Content-Type:application/vnd.ms-excel");
header("Content-Disposition: attachment;filename=".$filename.".xls");
header("Cache-Control:max-age=0");

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, "Excel5");
$objWriter -> save("php://output");
?>