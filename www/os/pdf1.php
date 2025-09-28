<?php

// 파일을 읽어와서 서버에 저장함.
isset($_REQUEST["imageURL"])  ? $imageURL=$_REQUEST["imageURL"] :   $imageURL=''; 

$imageURL = 'http://j-techel.co.kr/os/' . $imageURL ;
// $imageURL = 'http://j-techel.co.kr/request/1.jpg';

print $imageURL;

// Include the main TCPDF library (search for installation path).
require_once "../tcpdf/tcpdf_import.php";

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// // set header and footer fonts
// $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
// $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
// $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
// $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
// $pdf->SetAutoPageBreak(FALSE, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
// if (@file_exists(dirname(__FILE__).'/lang/kor.php')) {
    // require_once(dirname(__FILE__).'/lang/kor.php');
    // $pdf->setLanguageArray($l);
// }

// -------------------------------------------------------------------

// add a page
$pdf->AddPage();

// set JPEG quality
$pdf->setJPEGQuality(75);

// Image method signature:
// Image($file, $x='', $y='', $w=0, $h=0, $type='', $link='', $align='', $resize=false, $dpi=300, $palign='', $ismask=false, $imgmask=false, $border=0, $fitbox=false, $hidden=false, $fitonpage=false)

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

// Example of Image from data stream ('PHP rules')
// $imgdata = base64_decode('iVBORw0KGgoAAAANSUhEUgAAABwAAAASCAMAAAB/2U7WAAAABlBMVEUAAAD///+l2Z/dAAAASUlEQVR4XqWQUQoAIAxC2/0vXZDrEX4IJTRkb7lobNUStXsB0jIXIAMSsQnWlsV+wULF4Avk9fLq2r8a5HSE35Q3eO2XP1A1wQkZSgETvDtKdQAAAABJRU5ErkJggg==');

// The '@' character is used to indicate that follows an image data stream and not an image file name
 // $pdf->Image('@'.$imgdata);

// Image example with resizing
// $pdf->Image($imageURL, 15, 15, 180, 250, 'JPG', 'http://www.tcpdf.org', '', true, 200, '', false, false, 1, false, false, false);
 $pdf->Image($imageURL, 15, 15, 180, 250, 'JPG', '', '', true, 200, '', false, false, 1, false, false, false);

ob_end_clean();  // how-to-solve-tcpdf-error-some-data-has-already-been-output-can-t-send-pdf-file

//Close and output PDF document
$new_file_name = date("YmdHis");  //년월일시간 표시
$pdf->Output('estimate_' . $new_file_name . '.pdf' , 'I');

var_dump($pdf);

// 이미지 화일 jpg 삭제
unlink($imageURL);


?>