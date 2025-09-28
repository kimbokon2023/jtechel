 <?php
 
  $id = $_REQUEST["id"];
  $pass = $_REQUEST["pass"];
  $name = $_REQUEST["name"];
  $nick = $_REQUEST["nick"];

  require_once("../lib/mydb.php");
  $pdo = db_connect();

  try{
      $pdo->beginTransaction();
      $sql = "insert into jtechel.member(id,pass,name,nick,regist_day,level) VALUES(?, ?, ?, ?,now(),9)";
      $stmh = $pdo->prepare($sql);
      $stmh->bindValue(1, $id, PDO::PARAM_STR);
      $stmh->bindValue(2, $pass, PDO::PARAM_STR);
      $stmh->bindValue(3, $name, PDO::PARAM_STR);
      $stmh->bindValue(4, $nick, PDO::PARAM_STR);

      $stmh->execute();
      $pdo->commit();

	   } catch (PDOException $Exception) {
			$pdo->rollBack();
			print "오류: ".$Exception->getMessage();
	   }
      
	 // header("Location:http://8440.co.kr/index.php");	   
	 
 ?>
