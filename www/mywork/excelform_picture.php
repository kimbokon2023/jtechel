<?php

session_start();   

// 환경파일 읽어오기 (테이블명 작업 폴더 등)
include 'ini.php';    

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
	$workday=$row["workday"];
	$doneday=$row["doneday"];  // 시공완료일  	
	$worker=$row["worker"];
			 
// 1) $piclist 값을 연관 배열로 변환
$piclist = $row["piclist"];

} catch (PDOException $Exception) {
    print "오류: " . $Exception->getMessage();
}

$doneday = $row["doneday"];  // 시공완료일

// 시공완료일이 날짜가 공백이거나 '0000-00-00'인 경우 현재 날짜로 설정
if (empty($doneday) || $doneday == '0000-00-00') {
    $doneday = date('Y-m-d');  // 현재 날짜를 'YYYY-MM-DD' 형식으로 설정
}


$decodedPiclist = json_decode($piclist, true); // JSON 파싱

$piclistArray = [];

// col2 라는 키가 있는지 확인하고, 해당 키의 값을 처리합니다.
if(isset($decodedPiclist['col2']) && is_array($decodedPiclist['col2'])) {
    foreach ($decodedPiclist['col2'] as $key => $value) {
        $piclistArray[] = [
            'index' => $key + 1, 
            'value' => $value
        ];
    }
}

$sql = "SELECT * FROM jtechel.picuploads_mywork WHERE tablename = '$tablename' AND parentnum = '$num'";
try {
    $stmh = $pdo->query($sql);
    while ($row = $stmh->fetch(PDO::FETCH_ASSOC)) {
        $picsGroupedByItem[$row['idx']][$row['item']][] = $row['picname'];
    }
} catch (PDOException $Exception) {
    print "오류: " . $Exception->getMessage();
}

// 결과를 저장할 배열 초기화
$results = [];

// 연관 배열로 결과 생성
foreach ($piclistArray as $item) {
    $index = $item['index'];
    $value = $item['value'];
	
    $beforeArr = isset($picsGroupedByItem[$index]['beforeArr']) ? $picsGroupedByItem[$index]['beforeArr'] : [null];
    $midArr = isset($picsGroupedByItem[$index]['midArr']) ? $picsGroupedByItem[$index]['midArr'] : [null];
    $afterArr = isset($picsGroupedByItem[$index]['afterArr']) ? $picsGroupedByItem[$index]['afterArr'] : [null];
    
    $maxRows = max(count($beforeArr), count($midArr), count($afterArr));
    
    for ($i = 0; $i < $maxRows; $i++) {
        $results[] = [
            'index' => $index,
            'value' => $value,
            'before' => isset($beforeArr[$i]) ? $beforeArr[$i] : '',
            'mid' => isset($midArr[$i]) ? $midArr[$i] : '',
            'after' => isset($afterArr[$i]) ? $afterArr[$i] : ''
        ];
    }
}

function setImageSizeAndPosition($objDrawing, $imagePath, $cellWidth, $cellHeight, $topBottomMargin) {
    list($width, $height) = getimagesize($imagePath);

    // 이미지 비율에 따른 크기 조정
    if ($width > $height) { // 가로가 더 넓은 경우
        $objDrawing->setWidth(200);
        $objDrawing->setHeight(($height / $width) * 200);
    } else { // 세로가 더 큰 경우
        $objDrawing->setHeight(190);
        $objDrawing->setWidth(($width / $height) * 190);
    }

    // 위치 조정
    $offsetX = ($cellWidth - $objDrawing->getWidth()) / 2 + 12;
    $offsetY = ($cellHeight - $objDrawing->getHeight()) / 2;    

    $objDrawing->setOffsetX(max(0, $offsetX));
    $objDrawing->setOffsetY(max(0, $offsetY));
}


	include "../PHPExcel_1.8.0/Classes/PHPExcel.php";
	$objPHPExcel = new PHPExcel();

	$objPHPExcel->getActiveSheet()->getStyle("a1:h200")->getFont()->setName('Dotum')->setSize(11);	

	$objPHPExcel -> getActiveSheet() -> getColumnDimension("A") -> setWidth(10);
	$objPHPExcel -> getActiveSheet() -> getColumnDimension("B") -> setWidth(40);
	$objPHPExcel -> getActiveSheet() -> getColumnDimension("C") -> setWidth(35);
	$objPHPExcel -> getActiveSheet() -> getColumnDimension("D") -> setWidth(35);
	$objPHPExcel -> getActiveSheet() -> getColumnDimension("E") -> setWidth(35);


	$objPHPExcel -> setActiveSheetIndex(0)-> mergeCells('A2:E2');
		
	$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A3', "순번");
	$objPHPExcel->setActiveSheetIndex(0)->setCellValue('B3', "시공내역");
	$objPHPExcel->setActiveSheetIndex(0)->setCellValue('C3', "시공(전)");
	$objPHPExcel->setActiveSheetIndex(0)->setCellValue('D3', "시공(중)");
	$objPHPExcel->setActiveSheetIndex(0)->setCellValue('E3', "시공(후)");

	$objPHPExcel->getActiveSheet()->getRowDimension("3")->setRowHeight(30);
	$objPHPExcel->setActiveSheetIndex(0)->getStyle ("A3:E3")->getBorders ()->getAllBorders () ->setBorderStyle ( PHPExcel_Style_Border::BORDER_THIN);				

	$decodedPiclist = json_decode($piclist, true);
	$counter = 4; 


if (intval($num) > 0) {
    foreach ($results as $row) {
        $index = $row['index'];
        $value = $row['value'];
        $before = $row['before'];
        $mid = $row['mid'];
        $after = $row['after'];
		
		if ($value != '') {
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A' . $counter, $counter - 3);
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B' . $counter, $value);
													  
	    $topBottomMargin = -5; // 상단 및 하단 여백
				
		if ($before != '') {                                            
			$imagePath = './uploads/' .  $before;
			$objDrawing = new PHPExcel_Worksheet_Drawing();
			$objDrawing->setPath($imagePath);

			$colCoordinate = 'C' . $counter;
			$cellWidth = $objPHPExcel->getActiveSheet()->getColumnDimension('C')->getWidth() * 6.25;
			$cellHeight = $objPHPExcel->getActiveSheet()->getRowDimension($counter)->getRowHeight();

			setImageSizeAndPosition($objDrawing, $imagePath, $cellWidth, $cellHeight, $topBottomMargin);

			$objDrawing->setCoordinates($colCoordinate);
			$objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
		}		
				
		if ($mid != '') {                                            
			$imagePath = './uploads/' .  $mid;
			$objDrawing = new PHPExcel_Worksheet_Drawing();
			$objDrawing->setPath($imagePath);

			$colCoordinate = 'D' . $counter;
			$cellWidth = $objPHPExcel->getActiveSheet()->getColumnDimension('D')->getWidth() * 6.25;
			$cellHeight = $objPHPExcel->getActiveSheet()->getRowDimension($counter)->getRowHeight();

			setImageSizeAndPosition($objDrawing, $imagePath, $cellWidth, $cellHeight, $topBottomMargin);

			$objDrawing->setCoordinates($colCoordinate);
			$objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
		}		
				
		if ($after != '') {                                            
			$imagePath = './uploads/' .  $after;
			$objDrawing = new PHPExcel_Worksheet_Drawing();
			$objDrawing->setPath($imagePath);

			$colCoordinate = 'E' . $counter;
			$cellWidth = $objPHPExcel->getActiveSheet()->getColumnDimension('E')->getWidth() * 6.25;
			$cellHeight = $objPHPExcel->getActiveSheet()->getRowDimension($counter)->getRowHeight();

			setImageSizeAndPosition($objDrawing, $imagePath, $cellWidth, $cellHeight, $topBottomMargin);

			$objDrawing->setCoordinates($colCoordinate);
			$objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
		}		
							
			$objPHPExcel->setActiveSheetIndex(0)->getStyle("A" . $counter . ":E" . $counter)->getAlignment()
                        ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)
                        ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                    $objPHPExcel->getActiveSheet()->getRowDimension($counter)->setRowHeight(150);
                    $objPHPExcel->setActiveSheetIndex(0)->getStyle("A" . $counter . ":E" . $counter)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

                    $counter++;
                }
			}
	}



	$objPHPExcel->setActiveSheetIndex(0)->getStyle("A1")->getFont()->setName('Dotum')->setSize(20);    
	$objPHPExcel->setActiveSheetIndex(0)->mergeCells('A1:E1')->setCellValue('A1', $workplacename . " (사진대지)");
	$objPHPExcel -> setActiveSheetIndex(0)-> setCellValue('A2', "시공완료일 : " . date("Y", strtotime($doneday)) . "년" . date("m", strtotime($doneday)) . "월".date("d", strtotime($workday)) . "일" );	
	$objPHPExcel -> setActiveSheetIndex(0)-> getStyle("A2:E2" )->getFont()->setName('Dotum') -> setUnderline(true);

	// 가로, 세로 중앙 정렬
	$objPHPExcel->setActiveSheetIndex(0)->getStyle("A1:E3")->getAlignment()
		->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)
		->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
	// 가로 중앙 세로 오른쪽 정렬
	$objPHPExcel->setActiveSheetIndex(0)->getStyle("A2:E2")->getAlignment()
		->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT)
		->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

	$objPHPExcel->setActiveSheetIndex(0)->getStyle("A1:E1")->getFont()->setName('Dotum')->setBold(true);

	// 셀 높이 설정
	$objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(40);
	$objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(30);

	$companyName = mb_substr($workplacename, 0, 15, 'UTF-8');

	$objPHPExcel -> getActiveSheet() -> setTitle($companyName . " JK-테크 현장 사진대지");
	$objPHPExcel -> setActiveSheetIndex(0);
	$filename = iconv("UTF-8", "EUC-KR", "JK-테크 사진대지(" . $companyName . ") ");

	header("Content-Type:application/vnd.ms-excel");
	header("Content-Disposition: attachment;filename=".$filename.".xls");
	header("Cache-Control:max-age=0");

	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, "Excel5");
	$objWriter -> save("php://output");
	
?>