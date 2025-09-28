<?php
$file_name = $_FILES['upload_file']['name'];
$tmp_file = $_FILES['upload_file']['tmp_name'];

$file_path = './files/'.$file_name;

$r = move_uploaded_file($tmp_file, $file_path);
?> 
