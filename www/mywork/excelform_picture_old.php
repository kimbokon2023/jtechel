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
			$workday=$row["workday"];
			
			$worker=$row["worker"];
						 
			$piclist = $row["piclist"];
			
			$customer_data = $row["customer"];

			// JSON 데이터를 PHP 객체로 디코딩
			$customer_object = json_decode($customer_data);

			if ($customer_object === null) {
				// JSON 디코딩에 실패한 경우 처리
				// (올바르지 않은 JSON 형식일 경우, null을 반환합니다)
				// echo "JSON 디코딩에 실패했습니다.";
			} else {
				// 디코딩된 데이터를 각 변수에 할당
				$customer_date = $customer_object->customer_date;
				$customer_company = $customer_object->customer_company;
				$customer_address = $customer_object->customer_address;
				$customer_group = $customer_object->customer_group;
				$customer_name = $customer_object->customer_name;
				$customer_worklist1 = $customer_object->customer_worklist1;
				$customer_worklist2 = $customer_object->customer_worklist2;
				$image_url = $customer_object->image_url;
				
				// var_dump($image_url);
				
				// 날짜를 '-'를 기준으로 분할하여 배열로 저장
				$date_parts = explode('-', $customer_date);

				// 연도, 월, 일 추출
				$year = $date_parts[0];
				$month = $date_parts[1];
				$day = $date_parts[2];

				// 이제 각 변수에 할당된 값들을 사용할 수 있습니다.
			}
					
		// 시공전후 데이터 파일경로등을 읽어오는 구문
		if($num != 0) {
			$picsGroupedByItem = [];
			$tablename = 'mywork';

			// 모든 사진을 한 번의 쿼리로 가져오기
			$sql = "SELECT * FROM jtechel.picuploads_mywork WHERE tablename = '$tablename' AND parentnum = '$num'";

			try {
				$stmh = $pdo->query($sql);
				while ($row = $stmh->fetch(PDO::FETCH_ASSOC)) {
					$picsGroupedByItem[$row['item']][] = $row;
				}
			} catch (PDOException $Exception) {
				print "오류: " . $Exception->getMessage();
			}

			function sortByIdx($a, $b) {
				return $a['idx'] - $b['idx'];
			}

			if (isset($picsGroupedByItem['beforeArr'])) {
				usort($picsGroupedByItem['beforeArr'], 'sortByIdx');
				$picDataArr = array_column($picsGroupedByItem['beforeArr'], 'picname');
				$picIdx = array_column($picsGroupedByItem['beforeArr'], 'idx');
			}

			if (isset($picsGroupedByItem['midArr'])) {
				usort($picsGroupedByItem['midArr'], 'sortByIdx');
				$MidpicDataArr = array_column($picsGroupedByItem['midArr'], 'picname');
				$MidpicIdx = array_column($picsGroupedByItem['midArr'], 'idx');
			}

			if (isset($picsGroupedByItem['afterArr'])) {
				usort($picsGroupedByItem['afterArr'], 'sortByIdx');
				$AfterpicDataArr = array_column($picsGroupedByItem['afterArr'], 'picname');
				$AfterpicIdx = array_column($picsGroupedByItem['afterArr'], 'idx');
			}
			
			// 초기 배열 설정
			$resultPicData = [];
			$resultMidPicData = [];
			$resultAfterPicData = [];

			// picDataArr의 각 idx를 기준으로 다른 배열에서 해당 idx를 검사
			foreach ($picIdx as $index => $idxValue) {
				$resultPicData[] = $picDataArr[$index];
								
				// midArr에서 해당 idx가 있는지 확인
				if (is_array($MidpicIdx)) {
					$midKey = array_search($idxValue, $MidpicIdx);
					if ($midKey !== false) {
						$resultMidPicData[] = $MidpicDataArr[$midKey];
					} else {
						$resultMidPicData[] = null;  // 또는 필요한 기본 값을 설정
					}
				} else {
					$resultMidPicData[] = null;  // 또는 필요한 기본 값을 설정
				}


			if (is_array($AfterpicIdx)) {
				// afterArr에서 해당 idx가 있는지 확인
				$afterKey = array_search($idxValue, $AfterpicIdx);
				if ($afterKey !== false) {
					$resultAfterPicData[] = $AfterpicDataArr[$afterKey];
				} else {
					$resultAfterPicData[] = null;  // 또는 필요한 기본 값을 설정
				}
			} else {
				$resultAfterPicData[] = null;  // 또는 필요한 기본 값을 설정
			}

			}
		

		}			

		 }catch (PDOException $Exception) {
		   print "오류: ".$Exception->getMessage();
	 }
	 		 		 
	include "../PHPExcel_1.8.0/Classes/PHPExcel.php";
	$objPHPExcel = new PHPExcel();

	$objPHPExcel->getActiveSheet()->getStyle("a1:h200")->getFont()->setName('Dotum')->setSize(11);	

	$objPHPExcel -> getActiveSheet() -> getColumnDimension("A") -> setWidth(10);
	$objPHPExcel -> getActiveSheet() -> getColumnDimension("B") -> setWidth(40);
	$objPHPExcel -> getActiveSheet() -> getColumnDimension("C") -> setWidth(50);
	$objPHPExcel -> getActiveSheet() -> getColumnDimension("D") -> setWidth(50);
	$objPHPExcel -> getActiveSheet() -> getColumnDimension("E") -> setWidth(50);


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
		
    // Define image types and their respective data sources
    $imageTypes = [
        'before' => ['data' => $resultPicData, 'column' => 'C'],
        'mid' => ['data' => $resultMidPicData, 'column' => 'D'],
        'after' => ['data' => $resultAfterPicData, 'column' => 'E']
    ];		
		
    foreach ($decodedPiclist as $column => $items) {
        if (is_array($items)) {
            foreach ($items as $item) {
                if ($item != '') {
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A' . $counter, $counter - 3);
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B' . $counter, $item);
													  
					foreach ($imageTypes as $type => $info) {
						if (isset($info['data'][$counter - 4])) {
							$imagePath = './uploads/' . $info['data'][$counter - 4];
							$objDrawing = new PHPExcel_Worksheet_Drawing();
							$objDrawing->setPath($imagePath);

							$colCoordinate = $info['column'] . $counter;

							// Get the width and height of the cell (in pixels)
							$cellWidth = $objPHPExcel->getActiveSheet()->getColumnDimension($info['column'])->getWidth() * 6.25;  // 1 width unit in excel is approximately equal to 6.25 pixels
							$cellHeight = $objPHPExcel->getActiveSheet()->getRowDimension($counter)->getRowHeight();

							// Calculate image offsets to place it at the center of the cell
							$offsetX = ($cellWidth - 200) / 2;  // 180 is the image's width
							$offsetY = ($cellHeight - 195) / 2;  // 168 is the image's height

							$objDrawing->setCoordinates($colCoordinate);
							$objDrawing->setOffsetX(max(0, $offsetX));
							$objDrawing->setOffsetY(max(0, $offsetY));
							$objDrawing->setWidth(200);
							$objDrawing->setHeight(195);
							$objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
						}
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
    }
}



	$objPHPExcel->setActiveSheetIndex(0)->getStyle("A1")->getFont()->setName('Dotum')->setSize(20);    
	$objPHPExcel->setActiveSheetIndex(0)->mergeCells('A1:E1')->setCellValue('A1', $workplacename . " (사진대지)");
	$objPHPExcel -> setActiveSheetIndex(0)-> setCellValue('A2', "시공완료일 : " . date("Y", strtotime($workday)) . "년" . date("m", strtotime($workday)) . "월".date("d", strtotime($workday)) . "일" );	
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

	$objPHPExcel -> getActiveSheet() -> setTitle($companyName . " 오성이엘 현장 사진대지");
	$objPHPExcel -> setActiveSheetIndex(0);
	$filename = iconv("UTF-8", "EUC-KR", "오성이엘 사진대지(" . $companyName . ") ");

	header("Content-Type:application/vnd.ms-excel");
	header("Content-Disposition: attachment;filename=".$filename.".xls");
	header("Cache-Control:max-age=0");

	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, "Excel5");
	$objWriter -> save("php://output");
	
?>