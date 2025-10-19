<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

<style>
/* 서브 네비게이션 개선 스타일 */
.container-fluid.sticky-top {
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
}

.navbar.navigation {
    background: rgba(255, 255, 255, 0.98) !important;
    backdrop-filter: blur(10px);
    padding: 0.75rem 0;
}

.navbar-brand h3 {
    font-size: 1.5rem;
    font-weight: 800;
    color: #1f2937 !important;
    margin: 0;
    letter-spacing: -0.5px;
}

.navbar-brand .text-color {
    color: #2563eb !important;
    font-weight: 700;
}

.navbar-toggler {
    border: none;
    padding: 0.5rem;
    background: #f3f4f6;
    border-radius: 8px;
}

.navbar-nav .nav-item {
    margin: 0 0.5rem;
}

.navbar-nav .nav-link {
    color: #4b5563 !important;
    font-weight: 600;
    font-size: 0.95rem !important;
    padding: 0.5rem 1rem !important;
    border-radius: 8px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.navbar-nav .nav-link:hover,
.navbar-nav .nav-link.active {
    color: #2563eb !important;
    background: #eff6ff;
}

.navbar-nav .nav-link i {
    font-size: 1.1rem;
}

.navbar-nav .dropdown-menu {
    border: none;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    padding: 0.5rem;
    margin-top: 0.5rem;
}

.navbar-nav .dropdown-item {
    color: #4b5563;
    font-weight: 500;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    transition: all 0.2s ease;
    font-size: 0.9rem !important;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.navbar-nav .dropdown-item:hover {
    background: #eff6ff;
    color: #2563eb;
    transform: translateX(4px);
}

.navbar-nav .dropdown-item i {
    width: 20px;
    text-align: center;
}

.navbar .contact-info a {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1.25rem;
    background: linear-gradient(135deg, #2563eb, #0891b2);
    color: white !important;
    border-radius: 50px;
    font-size: 0.9rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.navbar .contact-info a:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
}

.user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 0.875rem;
}

/* 모바일 반응형 */
@media (max-width: 991px) {
    .navbar.navigation {
        padding: 0.75rem 0 1.5rem;
    }
    
    .navbar-collapse {
        margin-top: 1rem;
        padding-bottom: 1rem;
    }
    
    .navbar-nav {
        padding: 1rem 0;
        text-align: center;
    }
    
    .navbar-nav .nav-item {
        margin: 0.25rem 0;
    }
    
    .navbar-nav .nav-link {
        justify-content: center;
        padding: 0.75rem 1rem !important;
    }
    
    .navbar .contact-info {
        text-align: center;
        margin-top: 1rem;
        padding-bottom: 0.5rem;
    }
    
    .navbar-brand h3 {
        font-size: 1.25rem;
    }
    
    /* 드롭다운 메뉴 모바일 스타일 */
    .navbar-nav .dropdown-menu {
        position: static;
        float: none;
        width: auto;
        margin-top: 0.5rem;
        box-shadow: none;
        border: 1px solid rgba(37, 99, 235, 0.2);
    }
}

/* 더 작은 모바일 화면 */
@media (max-width: 575px) {
    .navbar.navigation {
        padding: 0.5rem 0 2rem;
    }
    
    .navbar-collapse {
        padding-bottom: 1.5rem;
    }
    
    .navbar-brand h3 {
        font-size: 1.1rem;
    }
    
    .navbar .contact-info a {
        font-size: 0.85rem;
        padding: 0.5rem 1rem;
    }
}
</style>

<!-- Navbar Start -->
<div class="container-fluid bg-white sticky-top">
<nav class="navbar navbar-expand-lg navigation" id="navbar">
	<div class="container-fluid">
		<a class="navbar-brand" href="http://j-techel.co.kr/mywork/index.php">
			<h3>오성이엘<span class="text-color">(OSEL)</span></h3>
		</a>
		<button type="button" class="navbar-toggler ms-auto me-0" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
			<ion-icon name="list-outline"></ion-icon>
		</button>
		<div class="collapse navbar-collapse" id="navbarCollapse">
			<ul class="navbar-nav mx-auto">
				<li class="nav-item">				  
					<a class="nav-link active" href="http://j-techel.co.kr/mywork/index.php">
						<i class="bi bi-house-door"></i> Home
					</a>					
				</li>				
				<li class="nav-item">
					<?php if(intval($_SESSION["level"]) <= 2 ) {  ?>
						<a class="nav-link" href="http://j-techel.co.kr/mywork/write_form.php?mode=new">
							<i class="bi bi-file-earmark-plus"></i> 수주/발주 등록
						</a>					
					<?php } ?>								
				</li>				
								
				<li class="nav-item">
					<a class="nav-link" href="http://j-techel.co.kr/mywork/list.php">
						<i class="bi bi-folder-open"></i> 수주/발주 관리
					</a>					
				</li>								
						
				<li class="nav-item">
					<a class="nav-link" href="http://j-techel.co.kr/mywork/schedule.php">
						<i class="bi bi-calendar-check"></i> 종합일정관리
					</a>
				</li>				
				
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
						<span class="user-avatar"><?=mb_substr($_SESSION["name"], 0, 1)?></span>
						<?=$_SESSION["name"]?>
					</a>
					<ul class="dropdown-menu">
						<li>
							<a class="dropdown-item" href="../../login/logout.php">
								<i class="bi bi-box-arrow-right"></i> 로그아웃
							</a>
						</li>
						<li>
							<a class="dropdown-item" href="../../member/updateForm.php?id=<?=$_SESSION["userid"]?>">
								<i class="bi bi-person-gear"></i> 정보수정
							</a>
						</li>
						<li>
							<a class="dropdown-item" href="#" onclick="popupCenter('help.php', '사용자 메뉴얼', 1900, 1000);">
								<i class="bi bi-question-circle"></i> 도움말
							</a>
						</li>								
						<?php if(intval($_SESSION["level"]) <= 2 ) {  ?>
						<li>
							<a class="dropdown-item" href="http://j-techel.co.kr/mywork/memberlist.php">
								<i class="bi bi-people"></i> 회원관리
							</a>
						</li>						
						<?php } ?>
					</ul>
				</li>
			</ul>
			<div class="contact-info my-md-0 ml-lg-4 mt-4 mt-lg-0 ml-auto text-lg-right mb-3 mb-lg-0">
				<a href="tel:+010-8313-9215">
					<i class="bi bi-telephone"></i>
					<span>010-8313-9215</span>
				</a>
			</div>
		</div>
	</div>
</nav>
</div>