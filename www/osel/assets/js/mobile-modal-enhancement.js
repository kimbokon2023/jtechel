/**
 * 모바일 모달 UX 개선 스크립트
 * 기존 모바일 핸들러와 함께 사용하여 더 나은 모바일 경험 제공
 */

class MobileModalEnhancement {
    constructor() {
        this.isMobile = this.detectMobile();
        this.init();
    }

    detectMobile() {
        const userAgent = navigator.userAgent || navigator.vendor || window.opera;
        const mobileRegex = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i;
        const touchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
        const screenSize = window.innerWidth <= 768;

        return mobileRegex.test(userAgent) || (touchDevice && screenSize);
    }

    init() {
        if (!this.isMobile) return;

        this.setupMobileOptimizations();
        this.setupGestureHandlers();
        this.setupKeyboardHandling();
    }

    /**
     * 모바일 최적화 설정
     */
    setupMobileOptimizations() {
        // 뷰포트 메타 태그 확인 및 추가
        this.ensureViewportMeta();

        // 모바일 스타일 로드
        this.loadMobileStyles();

        // 모달 타입별 최적화 적용
        this.optimizeExistingModals();
    }

    ensureViewportMeta() {
        let viewport = document.querySelector('meta[name="viewport"]');
        if (!viewport) {
            viewport = document.createElement('meta');
            viewport.name = 'viewport';
            viewport.content = 'width=device-width, initial-scale=1.0, user-scalable=no';
            document.head.appendChild(viewport);
        }
    }

    loadMobileStyles() {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'assets/css/mobile-modal-enhancement.css';
        document.head.appendChild(link);
    }

    /**
     * 기존 모달들을 모바일에 최적화
     */
    optimizeExistingModals() {
        // 패널 모달 최적화
        const panelModal = document.getElementById('panelModal');
        if (panelModal) {
            this.optimizePanelModal(panelModal);
        }

        // CSS 모달들 최적화
        const cssModals = document.querySelectorAll('.modal');
        cssModals.forEach(modal => this.optimizeCssModal(modal));
    }

    optimizePanelModal(modal) {
        const content = modal.querySelector('.panel-modal-content');
        const header = modal.querySelector('.panel-modal-header');
        const body = modal.querySelector('.panel-modal-body');
        const footer = modal.querySelector('.panel-modal-footer');

        if (window.innerHeight < 600) {
            // 작은 화면에서는 풀스크린
            modal.classList.add('mobile-fullscreen');
        } else {
            // 큰 화면에서는 바텀 시트
            modal.classList.add('mobile-bottom-sheet');
            this.addDragHandle(content);
        }

        if (header) header.classList.add('mobile-optimized');
        if (body) body.classList.add('mobile-optimized');
        if (footer) footer.classList.add('mobile-optimized');

        // 뒤로가기 힌트 표시
        this.showBackHint();
    }

    optimizeCssModal(modal) {
        const content = modal.querySelector('.modal-content');
        if (content) {
            content.style.maxWidth = '95vw';
            content.style.margin = '10px';
        }
    }

    addDragHandle(content) {
        if (content.querySelector('.modal-drag-handle')) return;

        const handle = document.createElement('div');
        handle.className = 'modal-drag-handle';
        content.insertBefore(handle, content.firstChild);

        // 드래그로 닫기 기능
        let startY = 0;
        let currentY = 0;

        handle.addEventListener('touchstart', (e) => {
            startY = e.touches[0].clientY;
        });

        handle.addEventListener('touchmove', (e) => {
            currentY = e.touches[0].clientY;
            const diff = currentY - startY;

            if (diff > 0) {
                content.style.transform = `translateY(${diff}px)`;
            }
        });

        handle.addEventListener('touchend', () => {
            const diff = currentY - startY;

            if (diff > 100) {
                // 100px 이상 드래그하면 모달 닫기
                this.closeModal(content.closest('.panel-modal') || content.closest('.modal'));
            } else {
                // 원래 위치로 복원
                content.style.transform = 'translateY(0)';
            }
        });
    }

    showBackHint() {
        if (document.querySelector('.mobile-back-hint')) return;

        const hint = document.createElement('div');
        hint.className = 'mobile-back-hint';
        hint.textContent = '뒤로가기로 닫기';
        document.body.appendChild(hint);

        setTimeout(() => {
            hint.remove();
        }, 3000);
    }

    /**
     * 제스처 핸들러 설정
     */
    setupGestureHandlers() {
        // 스와이프로 모달 닫기
        document.addEventListener('touchstart', (e) => {
            this.touchStartY = e.touches[0].clientY;
        });

        document.addEventListener('touchend', (e) => {
            if (!this.touchStartY) return;

            const touchEndY = e.changedTouches[0].clientY;
            const diff = touchEndY - this.touchStartY;

            // 아래로 스와이프 시 모달 닫기 (바텀 시트의 경우)
            if (diff > 150) {
                const activeModal = document.querySelector('.panel-modal:not([style*="display: none"])') ||
                                 document.querySelector('.modal.show');

                if (activeModal && activeModal.classList.contains('mobile-bottom-sheet')) {
                    this.closeModal(activeModal);
                }
            }

            this.touchStartY = null;
        });
    }

    /**
     * 키보드 처리 개선
     */
    setupKeyboardHandling() {
        // iOS에서 키보드 올라올 때 뷰포트 조정
        if (/iPhone|iPad|iPod/.test(navigator.userAgent)) {
            window.addEventListener('resize', () => {
                const activeModal = document.querySelector('.panel-modal:not([style*="display: none"])');
                if (activeModal) {
                    this.adjustForKeyboard(activeModal);
                }
            });
        }

        // 입력 필드 포커스 시 스크롤 조정
        document.addEventListener('focusin', (e) => {
            if (e.target.matches('input, textarea, select')) {
                setTimeout(() => {
                    e.target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 300);
            }
        });
    }

    adjustForKeyboard(modal) {
        const modalContent = modal.querySelector('.panel-modal-content') ||
                           modal.querySelector('.modal-content');

        if (modalContent) {
            const viewportHeight = window.innerHeight;
            modalContent.style.maxHeight = `${viewportHeight - 40}px`;
        }
    }

    closeModal(modal) {
        if (!modal) return;

        if (modal.classList.contains('panel-modal')) {
            // 패널 모달 닫기
            if (typeof closePanelModal === 'function') {
                closePanelModal();
            } else {
                modal.style.display = 'none';
            }
        } else if (modal.classList.contains('modal')) {
            // CSS 모달 닫기
            if (typeof closeModal === 'function') {
                closeModal();
            } else {
                modal.classList.remove('show');
            }
        }
    }

    /**
     * 모달 타입 감지 및 자동 최적화
     */
    autoOptimizeModal(modal) {
        if (!this.isMobile) return;

        const rect = modal.getBoundingClientRect();
        const viewportHeight = window.innerHeight;

        // 모달 높이가 뷰포트의 80% 이상이면 풀스크린으로
        if (rect.height > viewportHeight * 0.8) {
            modal.classList.add('mobile-fullscreen');
        } else {
            modal.classList.add('mobile-bottom-sheet');
        }
    }
}

// 전역 인스턴스 생성
window.mobileModalEnhancement = new MobileModalEnhancement();

// 기존 모달 열기 함수들을 확장
if (window.mobileModalEnhancement.isMobile) {
    // 패널 모달 열기 시 자동 최적화
    const originalOpenPanelModal = window.openPanelModal;
    if (originalOpenPanelModal) {
        window.openPanelModal = function(...args) {
            originalOpenPanelModal.apply(this, args);

            setTimeout(() => {
                const modal = document.getElementById('panelModal');
                if (modal && modal.style.display !== 'none') {
                    window.mobileModalEnhancement.autoOptimizeModal(modal);
                }
            }, 100);
        };
    }
}

