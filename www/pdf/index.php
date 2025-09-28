<?php
   
require('class.pdf2text.php');
extract($_POST);
if(isset($readpdf)){
      
    if($_FILES['file']['type']=="application/pdf") {
        $a = new PDF2Text();
        $a->setFilename($_FILES['file']['tmp_name']); 
        $a->decodePDF();
        echo $a->output(); 
    }
       
    else {
        echo "<p style='color:red;text-align:center'>
            Wrong file format</p>";
    }
}    
   
?>
  
<html>
  
<head>
    <title>Read pdf php</title>
</head>
  
<body>
    <form method="post" enctype="multipart/form-data">
        Choose Your File
        <input type="file" name="file" />
        <br>
        <input type="submit" value="Read PDF" name="readpdf" />
    </form>
	
<?

phpinfo();

?> 
	
</body>
  
</html>