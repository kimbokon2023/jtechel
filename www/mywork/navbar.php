
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

<style>
/* 네비게이션 개선 스타일 */
.navbar.navigation {
    background: rgba(255, 255, 255, 0.98) !important;
    backdrop-filter: blur(10px);
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    padding: 0.75rem 0;
}

.navbar.navigation.scrolled {
    background: white !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
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

.navbar-toggler:focus {
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
}

.navbar-toggler span {
    color: #1f2937;
    font-size: 1.5rem;
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
    position: relative;
}

.navbar-nav .nav-link:hover,
.navbar-nav .nav-link.active {
    color: #2563eb !important;
    background: #eff6ff;
}

.navbar-nav .nav-link::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 2px;
    background: #2563eb;
    transition: width 0.3s ease;
}

.navbar-nav .nav-link:hover::after {
    width: 80%;
}

.navbar-nav .dropdown-menu {
    border: none;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    padding: 0.5rem;
    margin-top: 0.5rem;
    min-width: 200px;
}

.navbar-nav .dropdown-item {
    color: #4b5563;
    font-weight: 500;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    transition: all 0.2s ease;
    font-size: 0.9rem !important;
}

.navbar-nav .dropdown-item:hover {
    background: #eff6ff;
    color: #2563eb;
    transform: translateX(4px);
}

.navbar-nav .dropdown-item i {
    margin-right: 8px;
    width: 20px;
    text-align: center;
}

.navbar .contact-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
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

.navbar .contact-info i {
    font-size: 1.1rem;
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
    }
    
    .navbar-nav .nav-item {
        margin: 0.25rem 0;
    }
    
    .navbar-nav .nav-link {
        text-align: center;
        padding: 0.75rem 1rem !important;
    }
    
    .navbar .contact-info {
        justify-content: center;
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
        padding: 0.5rem 0 2.5rem;
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

/* 사용자 아이콘 스타일 */
.nav-item.dropdown > a {
    display: flex;
    align-items: center;
    gap: 0.5rem;
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
</style>

<!-- Section Menu Start -->
<!-- Header Start -->
<nav class="navbar navbar-expand-lg navigation fixed-top" id="navbar">
	<div class="container-fluid">
		<a class="navbar-brand" href="<?php echo getBaseUrl(); ?>/mywork/index.php">
			<h3>오성이엘<span class="text-color">(OSEL)</span></h3>
		</a>
		<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarsid"
			aria-controls="navbarsid" aria-expanded="false" aria-label="Toggle navigation">
			<span class="ti-view-list"></span>
		</button>
		<div class="collapse text-center navbar-collapse" id="navbarsid">
			<ul class="navbar-nav mx-auto">
				<li class="nav-item">
					<a class="nav-link active" href="<?php echo getBaseUrl(); ?>/mywork/index.php">
						<i class="bi bi-house-door"></i> Home
					</a>
				</li>				
				<li class="nav-item">
					<?php if(intval($_SESSION["level"]) <= 2 ) {  ?>
						<a class="nav-link" href="<?php echo getBaseUrl(); ?>/mywork/write_form.php?mode=new">
							<i class="bi bi-file-earmark-plus"></i> 수주/발주 등록
						</a>												
					<?php } ?>
				</li>				
								
				<li class="nav-item">
					<a class="nav-link" href="<?php echo getBaseUrl(); ?>/mywork/list.php">
						<i class="bi bi-folder-open"></i> 수주/발주 관리
					</a>					
				</li>				
						
				<li class="nav-item">				
					<a class="nav-link" href="<?php echo getBaseUrl(); ?>/mywork/schedule.php">
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
							<a class="dropdown-item" href="<?php echo getBaseUrl(); ?>/mywork/memberlist.php">
								<i class="bi bi-people"></i> 회원관리
							</a>
						</li>											
						<?php } ?>						
					</ul>
				</li>
			</ul>
			<div class="contact-info">
				<a href="tel:+010-8313-9215">
					<i class="bi bi-telephone"></i>
					<span>010-8313-9215</span>
				</a>
			</div>
		</div>
	</div>
</nav>

<script>
// 스크롤 시 네비게이션 스타일 변경
window.addEventListener('scroll', function() {
    const navbar = document.getElementById('navbar');
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
});
</script>
