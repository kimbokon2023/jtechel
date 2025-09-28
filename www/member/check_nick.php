<meta charset="UTF-8">
 <?php $nick = $_REQUEST["nick"];
    if(!$nick)
    {
     print "닉네임을 입력하세요.<p>";
     }
   else
    {
     require_once("../lib/mydb.php");
     $pdo = db_connect();

   try{
      $sql = "select * from jtechel.member where nick = ? ";
      $stmh = $pdo->prepare($sql);
      $stmh->bindValue(1,$nick,PDO::PARAM_STR);
      $stmh->execute();
      $count = $stmh->rowCount();
    } catch (PDOException $Exception) {
      print "오류: ".$Exception->getMessage();
         }

	        if($count<1){
	                  print "사용가능한 닉네임입니다.<p>";
	                  } else
                          {
	                       print "닉네임이 중복됩니다.<br>다른 닉네임을 사용해 주세요.<p>";
	                      }
	     }
         print "<center> <input type=button value=창닫기 onClick=self.close()> </center>" ;

	 ?>
