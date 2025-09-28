  
    <!-- Navbar Start -->
<div class="container-fluid bg-white sticky-top">
<nav class="navbar navbar-expand-lg navigation " id="navbar">
	<div class="container-fluid">
		<a class="navbar-brand" href="http://j-techel.co.kr/mywork/index.php">
			<h3 class="text-white text-capitalize"></i>JK-테크<span class="text-color">(JK-TECH)</span></h3>
		</a>
                <button type="button" class="navbar-toggler ms-auto me-0" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                   <ion-icon name="list-outline"></ion-icon>
                </button>
		<div class="collapse navbar-collapse" id="navbarCollapse">
			<ul class="navbar-nav mx-auto">
				<li class="nav-item active">				  
					<a class="nav-link fs-4" href="http://j-techel.co.kr/mywork/index.php">Home </a>					
				</li>				
				<li class="nav-item">
						<?php if(intval($_SESSION["level"]) <= 2 ) {  ?>
							<a class="nav-link fs-4" href="http://j-techel.co.kr/mywork/write_form.php?mode=new"> 수주/발주 등록 </a>					
						<?php } ?>								
					
				</li>				
								
				<li class="nav-item">
					<a class="nav-link fs-4" href="http://j-techel.co.kr/mywork/list.php"> 수주/발주 관리 </a>					
				</li>								
						
				<li class="nav-item">
					<a class="nav-link fs-4" href="http://j-techel.co.kr/mywork/schedule.php"> 종합일정관리 </a>
				</li>				
				
				<li class="nav-item dropdown">
					<a class="nav-link fs-4 dropdown-toggle" href="#" data-toggle="dropdown" aria-haspopup="true"
						aria-expanded="false"><?=$_SESSION["name"]?> 로그인</a>
					<ul class="dropdown-menu">
						<li><a class="dropdown-item fs-4" href="../../login/logout.php">로그아웃</a></li>
						<li><a class="dropdown-item fs-4" href="../../member/updateForm.php?id=<?=$_SESSION["userid"]?>">정보수정</a></li>
						<li><a class="dropdown-item" href="#" onclick="popupCenter('help.php', '사용자 메뉴얼', 1900, 1000);" >도움말</a></li>								
						<?php if(intval($_SESSION["level"]) <= 2 ) {  ?>
						<li><a class="dropdown-item fs-4" href="http://j-techel.co.kr/mywork/memberlist.php">회원관리</a></li>						
							<?php } ?>
					</ul>
				</li>
			</ul>
			<div class="my-md-0 ml-lg-4 mt-4 mt-lg-0 ml-auto text-lg-right mb-3 mb-lg-0">
				<a href="tel:+010-8313-9215">
					<h3 class="text-color mb-0"><i class="ti-mobile mr-2"></i>+010-8313-9215</h3>
				</a>
			</div>
		</div>
	</div>
</nav>
</div>