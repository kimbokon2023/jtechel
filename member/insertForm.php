<?php
 session_start();
 ?>
 <!DOCTYPE html>
 <html>
 <head>
 <meta charset="UTF-8">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" >
	    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" ></script>  

 </head>
 
  <body cellpadding="0" cellspacing="0" width="100%" height="100%" align="center">
	<div class="card align-middle" style="width:40rem; border-radius:20px;">
		<div class="card-title" style="margin-top:30px;">
			<h3 class="card-title text-center"  style="color:#113366;"> 회원가입 </h3>
		</div>
		<div id=group class="card-body align-middle">
		  <form class="form-signin" name="member_form" method="post" action="insertPro.php">
		  
		   <h4 class="form-signin-heading p-5"> 
		
				<div class="input-group mb-3">
				  <div class="input-group-prepend">
					<span class="input-group-text p-3" id="basic-addon3">* 아이디 </span>
				  </div>
				  <input type="text" class="form-control" name="id" placeholder="영문자 가능" required aria-describedby="basic-addon3">
						  <a href="#" ><img src="../img/check_id.gif" onclick="check_id()"></a> </div>
						  
				<div class="input-group mb-3">
				  <div class="input-group-prepend">
					<span class="input-group-text" id="basic-addon3"> * 비 밀 번 호  </span>
				  </div>
				  <input  class="form-control" type="password" name="pass" required aria-describedby="basic-addon3">   </div>
				  
				<div class="input-group mb-3">
				  <div class="input-group-prepend">
					<span class="input-group-text" id="basic-addon3"> * 비밀번호 확인  </span>
				  </div>
				  <input  class="form-control" type="password" name="pass_confirm" required aria-describedby="basic-addon3">   </div>				  

				<div class="input-group mb-3">
				  <div class="input-group-prepend">
					<span class="input-group-text" id="basic-addon3">  * 이름  </span>
				  </div>
				  <input class="form-control" type="text" name="name" required aria-describedby="basic-addon3">   </div>				  				  
				  
				<div class="input-group mb-3">
				  <div class="input-group-prepend">
				  <span class="input-group-text" id="basic-addon3">  * 닉네임  </span>
				  </div>
				  <input class="form-control" type="text" name="nick" placeholder="글작성시 화면표시" required aria-describedby="basic-addon3"> 
						<a href="#"><img src="../img/check_id.gif"  onclick="check_nick()"></a>
				  </div>					  
						  	 
						  </h4>
						 <div class="clear"></div>
						 
						   
		           <h5 class="form-signin-heading">                     
						  * 는 필수 입력항목입니다.^^   </h5>						 
	        <h4 class="card-title text-center"  style="color:#113366;"> 
						 <div id="button">			 
						 <a href="#"><img src="../img/button_save.gif"  onclick="check_input()"></a>&nbsp;&nbsp;
				  <a href="#"><img src="../img/button_reset.gif" onclick="reset_form()"></a> </div>	  </h4>
			 </form>					 
     </div>
   
  </div> <!-- card body -->
  </div> <!-- end of content -->
  </div> <!-- end of wrap -->



  <script>
			  function check_id()
			  {
				window.open("check_id.php?id="+document.member_form.id.value,"IDcheck","left=20, top=200,width=300,height=100,scrollbars=no, resizable=yes");
			  }

			  function check_nick()
			  {
				window.open("check_nick.php?nick="+document.member_form.nick.value,"NICKcheck", "left=20,top=200,width=300,height=100, scrollbars=no, resizable=yes");
			  }

				 function check_input()
				 {

				   if(document.member_form.pass.value != document.member_form.pass_confirm.value)
				   {
					 alert("비밀번호가 일치하지 않습니다.\n다시 입력해주세요.");
					 document.member_form.pass.focus();
					 document.member_form.pass.select();
					 return;
				   }
						document.member_form.submit();
					}
			  function reset_form()
					  {
						document.member_form.id.value = "";
						document.member_form.pass.value = "";
						document.member_form.pass_confirm.value = "";
						document.member_form.name.value = "";
						document.member_form.nick.value = "";	
						document.member_form.id.focus();
						return;
				  }
 </script>

  </body>
  </html>
