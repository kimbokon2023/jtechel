<?php
	include ( 'PdfToText.phpclass' );
	$mydir = 'pdf';
	$myfiles = scandir($mydir);
	//print_r($myfiles);
	//die;
	$total_pdf=count($myfiles);
	//die;
	$i=2;
	while($i < $total_pdf){
		//echo $i;
		$file	=  "pdf/".$myfiles[$i];
		
		//echo $file;
//		$pdf	=  new PdfToText ( "$file" );
		$pdf	=  new PdfToText ( "sample.pdf" );
		
		$string = "computer";
		$data= $pdf -> Text;
		if(strpos($data,$string) !== false){
			//echo $string;
			echo "Found on:-<a target='_blank' href='$file'> $file</a>";
			echo "<br>";
		}else{
			//echo "faild to search";
		}
		$i++;
	}
	
	echo $pdf -> Text ; 