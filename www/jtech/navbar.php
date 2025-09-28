

<!-- Section Menu Start -->
<!-- Header Start -->
<nav class="navbar navbar-expand-lg navigation fixed-top" id="navbar">
	<div class="container-fluid">
		<a class="navbar-brand" href="http://j-techel.co.kr/jtech/index.php">
			<h3 class="text-white text-capitalize"></i>제이테크<span class="text-color">(J-TECH)</span></h3>
		</a>
		<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarsid"
			aria-controls="navbarsid" aria-expanded="false" aria-label="Toggle navigation">
			<span class="ti-view-list"></span>
		</button>
		<div class="collapse text-center navbar-collapse" id="navbarsid">
			<ul class="navbar-nav mx-auto">
				<li class="nav-item active">
					<a class="nav-link fs-2" href="http://j-techel.co.kr/jtech/index.php">Home <span class="sr-only">(current)</span></a>
				</li>				
				<li class="nav-item">				
						<?php if(intval($_SESSION["level"]) <= 2 ) {  ?>
							<a class="nav-link fs-2" href="http://j-techel.co.kr/jtech/write_form.php?mode=new" aria-haspopup="true"					aria-expanded="false">수주/발주 등록 </a>												
							<?php } ?>
				</li>				
								
				<li class="nav-item ">
					<a class="nav-link fs-2" href="http://j-techel.co.kr/jtech/list.php" aria-haspopup="true"
						aria-expanded="false"> 수주/발주 관리 </a>					
				</li>				
				
						
				<li class="nav-item">				
					<a class="nav-link fs-2 dropdown-toggle" href="http://j-techel.co.kr/jtech/schedule.php" aria-haspopup="true"
						aria-expanded="false"> 종합일정관리 </a>
				</li>				
				
				<li class="nav-item dropdown">
					<a class="nav-link fs-2 dropdown-toggle" href="#" data-toggle="dropdown" aria-haspopup="true"
						aria-expanded="false"><?=$_SESSION["name"]?> 로그인</a>
					<ul class="dropdown-menu">
						<li><a class="dropdown-item" href="../../login/logout.php">로그아웃</a></li>
						<li><a class="dropdown-item" href="../../member/updateForm.php?id=<?=$_SESSION["userid"]?>">정보수정</a></li>		
						<li><a class="dropdown-item" href="#" onclick="popupCenter('help.php', '사용자 메뉴얼', 1900, 1000);" >도움말</a></li>
						<?php if(intval($_SESSION["level"]) <= 2 ) {  ?>
						<<li><a class="dropdown-item fs-2" href="http://j-techel.co.kr/jtech/memberlist.php">회원관리</a></li>											
							<?php } ?>						
					</ul>
				</li>
			</ul>
			<div class="my-md-0 ml-lg-4 mt-4 mt-lg-0 ml-auto text-lg-right mb-3 mb-lg-0">
				<a href="tel:+032-225-7765">
					<h3 class="text-color mb-0"><i class="ti-mobile mr-2"></i>+032-225-7765</h3>
				</a>
			</div>
		</div>
	</div>
</nav>
