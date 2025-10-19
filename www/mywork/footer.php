<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

<style>
/* 푸터 개선 스타일 */
.footer {
    background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
    padding: 4rem 0 0;
    margin-top: 4rem;
    position: relative;
    overflow: hidden;
}

.footer::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #2563eb, #0891b2, #f59e0b);
}

.footer-main {
    padding-bottom: 3rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.footer-widget h4 {
    color: white;
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    position: relative;
    padding-bottom: 0.75rem;
}

.footer-widget h4::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 40px;
    height: 3px;
    background: linear-gradient(90deg, #2563eb, #0891b2);
    border-radius: 2px;
}

.footer-info {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.footer-info-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.95rem;
    line-height: 1.6;
}

.footer-info-item i {
    font-size: 1.25rem;
    color: #2563eb;
    margin-top: 2px;
    min-width: 24px;
}

.footer-info-item a {
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: color 0.3s ease;
}

.footer-info-item a:hover {
    color: #2563eb;
}

.footer-bottom {
    padding: 2rem 0;
    background: rgba(0, 0, 0, 0.2);
}

.footer-copyright {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
    margin: 0;
}

.footer-socials {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin: 0;
    padding: 0;
    list-style: none;
}

.footer-socials li {
    margin: 0;
}

.footer-socials a {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    color: white;
    font-size: 1.1rem;
    transition: all 0.3s ease;
}

.footer-socials a:hover {
    background: #2563eb;
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
}

.footer-brand {
    margin-bottom: 2rem;
}

.footer-brand h3 {
    color: white;
    font-size: 1.75rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
}

.footer-brand .text-color {
    color: #2563eb;
}

.footer-brand p {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.95rem;
    margin: 0;
}

/* 모바일 반응형 */
@media (max-width: 767px) {
    .footer {
        padding: 3rem 0 0;
        margin-top: 3rem;
    }
    
    .footer-main {
        padding-bottom: 2rem;
    }
    
    .footer-bottom {
        text-align: center;
    }
    
    .footer-socials {
        justify-content: center;
        margin-top: 1rem;
    }
    
    .footer-brand h3 {
        font-size: 1.5rem;
    }
}

/* 애니메이션 */
@keyframes float {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-5px);
    }
}

.footer-socials a {
    animation: float 3s ease-in-out infinite;
}

.footer-socials a:nth-child(2) {
    animation-delay: 0.3s;
}
</style>

<!-- Section Footer Start -->
<!-- footer Start -->
<footer class="footer">
	<div class="container">
		<div class="row footer-main">
			<div class="col-lg-4 col-md-6 mb-4">
				<div class="footer-brand">
					<h3>오성이엘<span class="text-color">(OSEL)</span></h3>
					<p>엘리베이터 전문 기업</p>
				</div>
				<div class="footer-widget">
					<h4>연락처 정보</h4>
					<div class="footer-info">
						<div class="footer-info-item">
							<i class="bi bi-geo-alt"></i>
							<span>경기도 김포시 양촌읍 흥신로 220-27</span>
						</div>
						<div class="footer-info-item">
							<i class="bi bi-telephone"></i>
							<a href="tel:+82-010-8313-9215">010-8313-9215</a>
						</div>
						<div class="footer-info-item">
							<i class="bi bi-envelope"></i>
							<a href="mailto:jk272@naver.com">jk272@naver.com</a>
						</div>
					</div>
				</div>
			</div>
			
			<div class="col-lg-3 col-md-6 mb-4">
				<div class="footer-widget">
					<h4>주요 서비스</h4>
					<div class="footer-info">
						<div class="footer-info-item">
							<i class="bi bi-check2-circle"></i>
							<span>카 판넬 측정</span>
						</div>
						<div class="footer-info-item">
							<i class="bi bi-check2-circle"></i>
							<span>수주/발주 관리</span>
						</div>
						<div class="footer-info-item">
							<i class="bi bi-check2-circle"></i>
							<span>일정 관리</span>
						</div>
						<div class="footer-info-item">
							<i class="bi bi-check2-circle"></i>
							<span>프로젝트 관리</span>
						</div>
					</div>
				</div>
			</div>
			
			<div class="col-lg-3 col-md-6 mb-4">
				<div class="footer-widget">
					<h4>운영 시간</h4>
					<div class="footer-info">
						<div class="footer-info-item">
							<i class="bi bi-clock"></i>
							<div>
								<div>평일: 09:00 - 18:00</div>
								<div style="margin-top: 0.5rem;">주말 및 공휴일 휴무</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="footer-bottom">
			<div class="row align-items-center">
				<div class="col-lg-6 col-md-12 text-center text-lg-left mb-3 mb-lg-0">
					<p class="footer-copyright">
						© <?= date('Y') ?> 오성이엘(OSEL). All rights reserved.
					</p>
				</div>
				<div class="col-lg-6 col-md-12">
					<ul class="footer-socials">
						<li><a href="https://www.facebook.com/" target="_blank" rel="noopener" aria-label="Facebook"><i class="bi bi-facebook"></i></a></li>
						<li><a href="https://twitter.com/" target="_blank" rel="noopener" aria-label="Twitter"><i class="bi bi-twitter"></i></a></li>
						<li><a href="https://www.instagram.com/" target="_blank" rel="noopener" aria-label="Instagram"><i class="bi bi-instagram"></i></a></li>
						<li><a href="https://www.youtube.com/" target="_blank" rel="noopener" aria-label="YouTube"><i class="bi bi-youtube"></i></a></li>
					</ul>
				</div>
			</div>
		</div>
	</div>
</footer>
<!-- Section Footer End -->
