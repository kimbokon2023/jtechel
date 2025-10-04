<?php
    // 환경별 기본 URL 설정
    require_once '../config/environment.php';
    
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $root_dir = '..';
?> 
 
<style>
    /* 🎨 Modern UI Base Styles */
    * {
        box-sizing: border-box;
    }
    
    html, body {
        overflow-x: hidden;
        width: 100%;
        max-width: 100%;
        padding-top: 90px;
        margin: 0;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        scroll-behavior: smooth;
    }
    
    /* 🚀 Modern PC Navigation - Fixed Dropdown */
    @media (min-width: 992px) {
        body {
            overflow-x: hidden !important;
            width: 100% !important;
            max-width: 100vw !important;
        }
        
        .container-fluid {
            overflow-x: hidden !important;
            max-width: 100% !important;
        }
        
        .navbar-custom {
            padding: 0 2rem;
        }
        
        .navbar-nav {
            gap: 8px;
        }
        
        .navbar-nav .nav-link {
            font-size: 1rem;
            font-weight: 600;
        }
        
        .navbar-nav .nav-link i {
            font-size: 1.1rem;
            margin-right: 8px;
            opacity: 0.8;
            transition: all 0.3s ease;
        }
        
        .navbar-nav .nav-link:hover i {
            opacity: 1;
            transform: scale(1.1);
        }
        
        /* 🎆 Fix PC Dropdown Interaction with Safe Zone */
        .dropdown {
            position: relative;
        }
        
        .dropdown-menu {
            position: absolute !important;
            top: calc(100% + 4px) !important;
            left: 0 !important;
            z-index: 9999 !important;
            display: none;
            pointer-events: auto !important;
            overflow: visible !important;
            opacity: 0 !important;
            visibility: hidden !important;
            transform: translateY(-10px) !important;
            transition: all 0.2s ease !important;
        }
        
        /* 드롭다운 사이에 보이지 않는 연결 다리 생성 */
        .dropdown::before {
            content: '';
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            height: 12px;
            background: transparent;
            z-index: 9998;
        }
        
        .dropdown:hover .dropdown-menu,
        .dropdown-menu.show {
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
            transform: translateY(0) !important;
        }
    } 

    .navbar-custom {
        /* 🌟 Glassmorphism Effect */
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 0 0 24px 24px;
        box-shadow: 
            0 8px 32px rgba(31, 38, 135, 0.37),
            inset 0 1px 0 rgba(255, 255, 255, 0.3);
        
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        width: 100%;
        z-index: 1030;
        min-height: 80px;
        overflow: visible !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        /* 강제로 드롭다운이 잘리지 않도록 */
        clip: unset !important;
        clip-path: none !important;
        contain: none !important;
    }
    
    /* 🎭 Scroll Effect */
    .navbar-custom.scrolled {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(30px);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }
    
    /* 컨테이너 최적화 - 드롭다운이 잘리지 않도록 */
    .navbar-custom .container-fluid {
        max-width: 100%;
        padding: 0 20px;
        margin: 0;
        width: 100%;
        box-sizing: border-box;
        overflow: visible !important;
        clip: unset !important;
        clip-path: none !important;
    }
    
    /* PC 화면 네비게이션 최적화 */
    @media (min-width: 992px) {
        html, body {
            overflow-x: hidden !important;
            max-width: 100vw !important;
        }
        
        .navbar-custom .container-fluid {
            padding: 0 40px;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            box-sizing: border-box;
        }
        
        .navbar-nav {
            margin-left: 0 !important;
        }
        
        .navbar-brand {
            margin-right: 3rem;
        }
        
        /* 🌈 Modern Dropdown Design - Force Visibility */
        .dropdown-menu {
            background: rgba(255, 255, 255, 0.98) !important;
            backdrop-filter: blur(25px) !important;
            border: 1px solid rgba(226, 232, 240, 0.6) !important;
            border-radius: 16px !important;
            box-shadow: 
                0 20px 25px -5px rgba(0, 0, 0, 0.1),
                0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
            margin-top: 8px !important;
            padding: 12px 8px !important;
            min-width: 220px !important;
            max-width: 280px !important;
            z-index: 99999 !important;
            position: fixed !important;
            transform: none !important;
            opacity: 1 !important;
            visibility: visible !important;
            overflow: visible !important;
            max-height: none !important;
            pointer-events: auto !important;
            clip: unset !important;
            clip-path: none !important;
            contain: none !important;
        }
        
        /* ✨ Modern Dropdown Items - Clickable */
        .dropdown-item {
            color: #374151 !important;
            padding: 10px 16px !important;
            font-size: 0.9rem !important;
            font-weight: 500 !important;
            border-radius: 10px !important;
            margin: 2px 4px !important;
            transition: all 0.2s ease !important;
            position: relative !important;
            display: flex !important;
            align-items: center !important;
            text-decoration: none !important;
            cursor: pointer !important;
            pointer-events: auto !important;
            z-index: 10000 !important;
            border: none !important;
            outline: none !important;
            user-select: none !important;
            -webkit-tap-highlight-color: transparent !important;
        }
        
        .dropdown-item:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.12), rgba(118, 75, 162, 0.08)) !important;
            color: #667eea !important;
            transform: translateX(2px) !important;
            text-decoration: none !important;
        }
        
        .dropdown-item:focus {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.12), rgba(118, 75, 162, 0.08)) !important;
            color: #667eea !important;
            box-shadow: 0 0 0 1px rgba(102, 126, 234, 0.4) !important;
            outline: none !important;
            text-decoration: none !important;
        }
        
        .dropdown-item:active {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.15), rgba(118, 75, 162, 0.1)) !important;
            transform: translateX(1px) !important;
        }
        
        .dropdown-divider {
            border: none !important;
            height: 1px !important;
            background: linear-gradient(90deg, transparent, rgba(0, 0, 0, 0.08), transparent) !important;
            margin: 8px 12px !important;
        }
        
        .dropdown-item.text-danger {
            color: #ef4444 !important;
        }
        
        .dropdown-item.text-danger:hover {
            background: rgba(239, 68, 68, 0.08) !important;
            color: #dc2626 !important;
            text-decoration: none !important;
        }
        
        .dropdown-item i {
            margin-right: 10px !important;
            font-size: 1em !important;
            opacity: 0.8 !important;
            transition: opacity 0.2s ease !important;
            width: 16px !important;
            text-align: center !important;
        }
        
        .dropdown-item:hover i {
            opacity: 1 !important;
        }
    }

    /* 🎨 Modern Typography & Colors */
    .navbar-custom .navbar-brand,
    .navbar-custom .navbar-nav .nav-link {
        color: #1a202c;
        font-weight: 600;
        letter-spacing: -0.025em;
        transition: all 0.3s ease;
    }
    
    .navbar-custom .navbar-brand {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-weight: 800;
        font-size: 1.75rem;
    }
    
    .navbar-custom .navbar-nav .nav-link {
        position: relative;
        padding: 12px 20px;
        border-radius: 16px;
        font-weight: 500;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .navbar-custom .navbar-nav .nav-link:hover {
        color: #667eea;
        background: rgba(102, 126, 234, 0.1);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }
    
    .navbar-custom .navbar-nav .nav-link::after {
        content: '';
        position: absolute;
        bottom: 8px;
        left: 20px;
        right: 20px;
        height: 2px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        border-radius: 1px;
        transform: scaleX(0);
        transition: transform 0.3s ease;
    }
    
    .navbar-custom .navbar-nav .nav-link:hover::after {
        transform: scaleX(1);
    }
    
    /* 태블릿 화면 최적화 */
    @media (max-width: 991px) and (min-width: 768px) {
        .navbar-custom {
            padding: 0 20px;
        }
        
        .navbar-nav {
            margin-left: 0 !important;
        }
    }
    
    /* 📱 Modern Mobile Navigation */
    @media (max-width: 767px) {
        html, body {
            overflow-x: hidden !important;
            max-width: 100% !important;
        }
        
        body {
            padding-top: 100px;
        }
        
        .navbar-custom {
            min-height: 80px;
            padding: 16px 20px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(30px);
            border-radius: 0 0 20px 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-custom .container-fluid {
            overflow-x: hidden !important;
        }
        
        .navbar-brand {
            font-size: 1.8rem !important;
            font-weight: 800 !important;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* 🌮 Modern Hamburger Menu */
        .navbar-toggler {
            border: none;
            padding: 12px;
            border-radius: 16px;
            background: rgba(102, 126, 234, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .navbar-toggler:hover {
            background: rgba(102, 126, 234, 0.2);
            transform: scale(1.05);
        }
        
        .navbar-toggler:focus {
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.3);
            outline: none;
        }
        
        .navbar-toggler-icon {
            width: 24px;
            height: 24px;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%2859, 78, 158, 1%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2.5' d='m4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
        
        .navbar-nav .nav-item {
            text-align: center;
            margin: 8px 0; /* 여백 증가 */
        }
        
        /* 🎆 Mobile Menu Items */
        .navbar-collapse {
            margin-top: 16px;
            padding: 20px 0;
            border-top: 1px solid rgba(102, 126, 234, 0.2);
        }
        
        .navbar-nav .nav-link {
            font-size: 1.2rem !important;
            padding: 16px 20px !important;
            border-radius: 16px !important;
            margin: 6px 0 !important;
            font-weight: 600 !important;
            color: #374151 !important;
            background: rgba(248, 250, 252, 0.8);
            border: 1px solid rgba(226, 232, 240, 0.5);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: flex-start !important;
        }
        
        .navbar-nav .nav-link:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)) !important;
            color: #667eea !important;
            transform: translateX(8px) !important;
            border-color: rgba(102, 126, 234, 0.3) !important;
        }
        
        /* 🌈 Modern Mobile Dropdown - No Scroll */
        .dropdown-menu {
            background: rgba(255, 255, 255, 0.98) !important;
            backdrop-filter: blur(20px) !important;
            border: 1px solid rgba(226, 232, 240, 0.5) !important;
            border-radius: 16px !important;
            box-shadow: 
                0 8px 20px -5px rgba(0, 0, 0, 0.1),
                0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
            margin-top: 8px !important;
            padding: 12px !important;
            position: static !important;
            width: 100% !important;
            transform: none !important;
            opacity: 1 !important;
            visibility: visible !important;
            display: block !important;
            overflow: visible !important;
            max-height: none !important;
            height: auto !important;
        }
        
        .dropdown-item {
            color: #374151 !important;
            padding: 12px 16px !important;
            font-size: 1rem !important;
            font-weight: 500 !important;
            border-radius: 10px !important;
            margin: 3px 0 !important;
            background: rgba(248, 250, 252, 0.7) !important;
            border: 1px solid rgba(226, 232, 240, 0.4) !important;
            transition: all 0.2s ease !important;
            display: flex !important;
            align-items: center !important;
            justify-content: flex-start !important;
            text-decoration: none !important;
            cursor: pointer !important;
            pointer-events: auto !important;
        }
        
        .dropdown-item:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.12), rgba(118, 75, 162, 0.08)) !important;
            color: #667eea !important;
            transform: translateX(2px) !important;
            border-color: rgba(102, 126, 234, 0.3) !important;
            text-decoration: none !important;
        }
        
        .dropdown-item:active {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.15), rgba(118, 75, 162, 0.1)) !important;
            transform: translateX(1px) !important;
        }
        
        .dropdown-divider {
            border: none !important;
            height: 1px !important;
            background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.2), transparent) !important;
            margin: 12px 8px !important;
        }
        
        /* 🎆 Mobile Icons & Effects */
        .navbar-nav .nav-link i {
            font-size: 1.3rem;
            margin-right: 12px;
            width: 24px;
            text-align: center;
            opacity: 0.8;
            transition: all 0.3s ease;
        }
        
        .navbar-nav .nav-link:hover i {
            opacity: 1;
            transform: scale(1.1);
        }
        
        .dropdown-item i {
            font-size: 1.2rem !important;
            margin-right: 12px !important;
            width: 20px !important;
            text-align: center !important;
            opacity: 0.7 !important;
        }
        
        .dropdown-item:hover i {
            opacity: 1 !important;
        }
    }
    
    /* 📱 Extra Small Mobile Screens */
    @media (max-width: 576px) {
        body {
            padding-top: 95px;
        }        
        .navbar-custom {
            min-height: 75px;
            padding: 12px 16px;
        }        
        .navbar-brand {
            font-size: 1.6rem !important;
        }        
        .navbar-nav .nav-link {
            font-size: 1.1rem !important;
            padding: 14px 18px !important;
        }        
        .dropdown-item {
            font-size: 1rem !important;
            padding: 12px 18px !important;
        }        
        .navbar-nav .nav-link i {
            font-size: 1.2rem;
            margin-right: 10px;
        }
    }
    
    /* ✨ Modern Animations & Effects */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    .navbar-nav .nav-item {
        animation: fadeInUp 0.6s ease forwards;
    }
    
    .navbar-nav .nav-item:nth-child(1) { animation-delay: 0.1s; }
    .navbar-nav .nav-item:nth-child(2) { animation-delay: 0.2s; }
    .navbar-nav .nav-item:nth-child(3) { animation-delay: 0.3s; }
    .navbar-nav .nav-item:nth-child(4) { animation-delay: 0.4s; }
    
    .dropdown-item {
        animation: slideInRight 0.4s ease forwards;
    }
    
    /* 🌟 Loading Effect */
    .navbar-custom::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #667eea, #764ba2, #667eea);
        background-size: 200% 100%;
        animation: shimmer 2s linear infinite;
        border-radius: 0 0 24px 24px;
    }    
    @keyframes shimmer {
        0% { background-position: -200% 0; }
        100% { background-position: 200% 0; }
    }
</style>
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <!-- 🚀 Modern Brand -->
            <a class="navbar-brand" href="<?php echo getBaseUrl(); ?>/game/index.php?home=1">
                <i class="bi bi-lightning-charge-fill me-2"></i>YH 시스템
            </a>
        
            <!-- 📱 Modern Mobile Toggle -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="메뉴 열기">
                <span class="navbar-toggler-icon"></span>
            </button>
        
            <!-- 🌟 Modern Navigation Menu -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo getBaseUrl(); ?>/game/index.php?home=1">
                            <i class="bi bi-house-door-fill"></i>
                            <span>홈</span>
                        </a>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" role="button" aria-expanded="false">
                            <i class="bi bi-gear-wide-connected"></i>
                            <span>기계</span>
                        </a>
                        <ul class="dropdown-menu">
                           <li><a class="dropdown-item" href="./index.php">
                               <i class="bi bi-plus-circle-fill"></i>자료등록
                           </a></li>
                           <li><a class="dropdown-item" href="./guest.php">
                               <i class="bi bi-people-fill"></i>회원관리
                           </a></li>
                        </ul>
                    </li>

                    <?php if($_SESSION["level"]=='1' || intval($_SESSION["level"]) == 1) {  ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" role="button" aria-expanded="false">
                            <i class="bi bi-shield-lock-fill"></i>
                            <span>관리자</span>
                        </a>
                        <ul class="dropdown-menu">
                           <li><a class="dropdown-item" href="./fee_detail.php">
                               <i class="bi bi-receipt"></i>지출 상세 내역조회
                           </a></li>
                           <li><a class="dropdown-item" href="./daily.php">
                               <i class="bi bi-graph-up"></i>수입지출 결산보고
                           </a></li>
                           <li><a class="dropdown-item" href="./user.php">
                               <i class="bi bi-person-gear"></i>사용자관리
                           </a></li>
                           <li><a class="dropdown-item" href="./branch_manage.php">
                               <i class="bi bi-building"></i>지점 관리
                           </a></li>
                        </ul>
                    </li>
                    <?php }  ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" role="button" aria-expanded="false">
                            <i class="bi bi-three-dots-vertical"></i>
                            <span>공통</span>
                        </a>
                        <ul class="dropdown-menu">
                            <?php if(isset($_SESSION["level"]) && $_SESSION["level"]=='1') {  ?>
                           <li><a class="dropdown-item" href="./logdata.php">
                               <i class="bi bi-journal-text"></i>Log 기록
                           </a></li>
                            <?php }  ?>
                           <li><hr class="dropdown-divider"></li>
                           <li><a class="dropdown-item text-danger" href="./login/logout.php">
                               <i class="bi bi-box-arrow-right"></i>Log Out
                           </a></li>
                        </ul>
                    </li>

                </ul>
            </div>
        </div>
    </nav>
    
    <!-- 🚀 Modern Interactive JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const navbar = document.querySelector('.navbar-custom');
        const navLinks = document.querySelectorAll('.nav-link');
        
        // 📱 Scroll Effect
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
        
        // ✨ Interactive Link Effects
        navLinks.forEach(link => {
            link.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px) scale(1.02)';
            });
            
            link.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
        
        // 🎯 Active State Management
        const currentPage = window.location.pathname;
        navLinks.forEach(link => {
            if (link.getAttribute('href') && currentPage.includes(link.getAttribute('href'))) {
                link.classList.add('active');
                link.style.background = 'rgba(102, 126, 234, 0.15)';
                link.style.color = '#667eea';
            }
        });
        
        // 🎆 Enhanced Dropdown Click Handling
        const dropdownItems = document.querySelectorAll('.dropdown-item');
        const dropdowns = document.querySelectorAll('.dropdown');
        
        // Ensure all dropdown items are clickable
        dropdownItems.forEach(item => {
            // Remove any event listeners that might be blocking
            item.style.pointerEvents = 'auto';
            item.style.position = 'relative';
            item.style.zIndex = '10001';
            
            // Add click event listener directly
            item.addEventListener('click', function(e) {
                e.stopPropagation();
                const href = this.getAttribute('href');
                if (href && href !== '#') {
                    window.location.href = href;
                }
            });
            
            // Enhanced hover effects
            item.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(4px)';
                this.style.background = 'linear-gradient(135deg, rgba(102, 126, 234, 0.15), rgba(118, 75, 162, 0.1))';
            });
            
            item.addEventListener('mouseleave', function() {
                this.style.transform = 'translateX(0)';
            });
        });
        
        // PC Dropdown hover management with safe zone
        if (window.innerWidth >= 992) {
            dropdowns.forEach(dropdown => {
                const menu = dropdown.querySelector('.dropdown-menu');
                const toggle = dropdown.querySelector('.dropdown-toggle');
                let isHovering = false;
                let timeout = null;
                
                if (menu && toggle) {
                    const showMenu = () => {
                        if (timeout) {
                            clearTimeout(timeout);
                            timeout = null;
                        }
                        
                        // Get toggle button position
                        const rect = toggle.getBoundingClientRect();
                        
                        // Position dropdown menu relative to viewport
                        menu.style.position = 'fixed';
                        menu.style.top = (rect.bottom + 8) + 'px';
                        menu.style.left = rect.left + 'px';
                        menu.style.zIndex = '99999';
                        
                        // Ensure it doesn't go off screen
                        const menuRect = menu.getBoundingClientRect();
                        if (menuRect.right > window.innerWidth) {
                            menu.style.left = (window.innerWidth - menuRect.width - 20) + 'px';
                        }
                        
                        menu.classList.add('show');
                        isHovering = true;
                    };
                    
                    const hideMenu = () => {
                        timeout = setTimeout(() => {
                            if (!isHovering) {
                                menu.classList.remove('show');
                            }
                        }, 150); // 150ms 지연으로 안전 구역 제공
                    };
                    
                    // 드롭다운 버튼에 마우스 올릴 때
                    dropdown.addEventListener('mouseenter', function() {
                        isHovering = true;
                        showMenu();
                    });
                    
                    // 드롭다운에서 마우스 나갈 때
                    dropdown.addEventListener('mouseleave', function() {
                        isHovering = false;
                        hideMenu();
                    });
                    
                    // 메뉴에 마우스 올릴 때
                    menu.addEventListener('mouseenter', function() {
                        isHovering = true;
                        if (timeout) {
                            clearTimeout(timeout);
                            timeout = null;
                        }
                    });
                    
                    // 메뉴에서 마우스 나갈 때
                    menu.addEventListener('mouseleave', function() {
                        isHovering = false;
                        hideMenu();
                    });
                }
            });
        }
        
        // 📱 Mobile Menu Enhancement
        const navToggler = document.querySelector('.navbar-toggler');
        const navCollapse = document.querySelector('.navbar-collapse');
        
        navToggler.addEventListener('click', function() {
            setTimeout(() => {
                const menuItems = navCollapse.querySelectorAll('.nav-item');
                menuItems.forEach((item, index) => {
                    item.style.animationDelay = `${index * 0.1}s`;
                });
            }, 100);
        });
        
        // 🌟 Parallax Effect for Brand
        const brand = document.querySelector('.navbar-brand');
        window.addEventListener('scroll', function() {
            const scrolled = window.pageYOffset;
            const rate = scrolled * -0.5;
            brand.style.transform = `translateY(${rate}px)`;
        });
    });
    </script> 
