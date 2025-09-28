/**
 * Mobile Modal Handler
 * 모바일 환경에서 모달창 뒤로가기 버튼 처리를 위한 통합 솔루션
 *
 * 기능:
 * - 모바일 환경 감지
 * - 히스토리 스택 관리
 * - 모달창별 뒤로가기 처리
 * - SweetAlert2, 커스텀 모달, CSS 모달 지원
 */

class MobileModalHandler {
    constructor() {
        this.isMobile = this.detectMobile();
        this.modalStack = [];
        this.historyState = {};
        this.isHandlingBack = false;

        // 초기화
        this.init();
    }

    /**
     * 모바일 환경 감지
     */
    detectMobile() {
        const userAgent = navigator.userAgent || navigator.vendor || window.opera;
        const mobileRegex = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i;
        const touchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
        const screenSize = window.innerWidth <= 768;

        return mobileRegex.test(userAgent) || (touchDevice && screenSize);
    }

    /**
     * 초기화
     */
    init() {
        if (!this.isMobile) {
            return;
        }


        // popstate 이벤트 리스너 등록
        window.addEventListener('popstate', (event) => {
            this.handlePopState(event);
        });

        // 초기 히스토리 상태 설정
        this.setInitialHistoryState();
    }

    /**
     * 초기 히스토리 상태 설정
     */
    setInitialHistoryState() {
        const currentState = {
            modalHandler: true,
            modalStack: [],
            timestamp: Date.now()
        };

        // 현재 URL에 상태 추가
        history.replaceState(currentState, '', window.location.href);
        this.historyState = currentState;
    }

    /**
     * 모달 열기 등록
     */
    registerModalOpen(modalInfo) {
        if (!this.isMobile) return;

        const modalData = {
            id: modalInfo.id || this.generateModalId(),
            type: modalInfo.type || 'custom', // 'custom', 'css', 'swal'
            element: modalInfo.element || null,
            closeCallback: modalInfo.closeCallback || null,
            timestamp: Date.now()
        };

        this.modalStack.push(modalData);

        // 히스토리에 새 상태 추가 - 현재 URL 유지하면서 모달 상태만 추가
        // HTMLElement는 직렬화할 수 없으므로 제외하고 히스토리에 저장
        const serializableModalStack = this.modalStack.map(modal => ({
            id: modal.id,
            type: modal.type,
            timestamp: modal.timestamp
            // element와 closeCallback은 제외 (직렬화 불가능)
        }));

        const newState = {
            modalHandler: true,
            modalStack: serializableModalStack,
            timestamp: Date.now(),
            originalUrl: window.location.href
        };

        history.pushState(newState, '', window.location.href);
        this.historyState = newState;


        // 추가 이벤트 리스너 등록으로 페이지 이탈 완전 차단
        this.setupPageLeaveBlocking();
    }

    /**
     * 모달 닫기 등록
     */
    registerModalClose(modalId) {
        if (!this.isMobile) return;

        const index = this.modalStack.findIndex(modal => modal.id === modalId);
        if (index !== -1) {
            this.modalStack.splice(index, 1);
        }

        // 모든 모달이 닫혔으면 페이지 이탈 차단 해제
        if (this.modalStack.length === 0) {
            this.removePageLeaveBlocking();
        }
    }

    /**
     * popstate 이벤트 처리
     */
    handlePopState(event) {
        if (this.isHandlingBack) return;

        this.isHandlingBack = true;

        try {
            // 현재 열린 모달이 있는 경우 - 무조건 모달만 닫고 페이지 이동 차단
            if (this.modalStack.length > 0) {

                // 페이지 이동을 완전히 차단
                event.preventDefault();
                event.stopPropagation();

                // 히스토리를 현재 상태로 다시 푸시하여 뒤로가기를 무효화
                // HTMLElement는 직렬화할 수 없으므로 제외하고 히스토리에 저장
                const serializableModalStack = this.modalStack.map(modal => ({
                    id: modal.id,
                    type: modal.type,
                    timestamp: modal.timestamp
                }));

                const currentState = {
                    modalHandler: true,
                    modalStack: serializableModalStack,
                    timestamp: Date.now()
                };
                history.pushState(currentState, '', window.location.href);

                // 최상위 모달만 닫기
                this.closeTopModal();

                return false; // 추가 이벤트 전파 차단
            } else {
                // 모달이 없으면 정상적인 뒤로가기 허용
                this.handleNormalNavigation();
            }

        } catch (error) {
            console.error('PopState handling error:', error);
            // 에러 발생 시에도 모달이 있으면 페이지 이동 차단
            if (this.modalStack.length > 0) {
                event.preventDefault();
                this.closeAllModals();
            }
        } finally {
            // 약간의 지연 후 플래그 해제
            setTimeout(() => {
                this.isHandlingBack = false;
            }, 100);
        }
    }

    /**
     * 최상위 모달 닫기
     */
    closeTopModal() {
        if (this.modalStack.length === 0) return;

        const topModal = this.modalStack[this.modalStack.length - 1];

        try {
            switch (topModal.type) {
                case 'swal':
                    this.closeSwalModal(topModal);
                    break;
                case 'css':
                    this.closeCssModal(topModal);
                    break;
                case 'custom':
                    this.closeCustomModal(topModal);
                    break;
                default:
                    this.closeGenericModal(topModal);
            }
        } catch (error) {
            console.error('Modal close error:', error);
            // 에러 발생 시 강제로 스택에서 제거
            this.modalStack.pop();
        }
    }

    /**
     * SweetAlert2 모달 닫기
     */
    closeSwalModal(modalData) {
        if (typeof Swal !== 'undefined' && Swal.isVisible()) {
            Swal.close();
        }
        this.modalStack.pop();
    }

    /**
     * CSS 모달 닫기
     */
    closeCssModal(modalData) {
        const element = modalData.element || document.querySelector('.modal.show');
        if (element) {
            element.classList.remove('show');
            element.style.display = 'none';
        }

        if (modalData.closeCallback && typeof modalData.closeCallback === 'function') {
            modalData.closeCallback();
        }

        this.modalStack.pop();
    }

    /**
     * 커스텀 모달 닫기 (panel_measurement.php용)
     */
    closeCustomModal(modalData) {
        const element = modalData.element || document.getElementById('panelModal');
        if (element) {
            element.style.display = 'none';
        }

        if (modalData.closeCallback && typeof modalData.closeCallback === 'function') {
            modalData.closeCallback();
        }

        this.modalStack.pop();
    }

    /**
     * 일반 모달 닫기
     */
    closeGenericModal(modalData) {
        if (modalData.closeCallback && typeof modalData.closeCallback === 'function') {
            modalData.closeCallback();
        } else if (modalData.element) {
            modalData.element.style.display = 'none';
        }

        this.modalStack.pop();
    }

    /**
     * 페이지 이탈 차단 설정
     */
    setupPageLeaveBlocking() {
        if (!this.pageLeaveBlockingActive) {
            this.pageLeaveBlockingActive = true;

            // beforeunload 이벤트로 페이지 이탈 차단
            this.beforeUnloadHandler = (e) => {
                if (this.modalStack.length > 0) {
                    e.preventDefault();
                    e.returnValue = ''; // Chrome에서 필요
                    return ''; // 일부 브라우저에서 필요
                }
            };

            window.addEventListener('beforeunload', this.beforeUnloadHandler);

            // 추가적인 네비게이션 차단
            this.hashChangeHandler = (e) => {
                if (this.modalStack.length > 0) {
                    e.preventDefault();
                    this.closeTopModal();
                }
            };

            window.addEventListener('hashchange', this.hashChangeHandler);
        }
    }

    /**
     * 페이지 이탈 차단 해제
     */
    removePageLeaveBlocking() {
        if (this.pageLeaveBlockingActive) {
            this.pageLeaveBlockingActive = false;

            if (this.beforeUnloadHandler) {
                window.removeEventListener('beforeunload', this.beforeUnloadHandler);
                this.beforeUnloadHandler = null;
            }

            if (this.hashChangeHandler) {
                window.removeEventListener('hashchange', this.hashChangeHandler);
                this.hashChangeHandler = null;
            }
        }
    }

    /**
     * 정상적인 네비게이션 처리
     */
    handleNormalNavigation() {
        // 현재 스택 클리어
        this.modalStack = [];
        // 페이지 이탈 차단 해제
        this.removePageLeaveBlocking();
        // 정상적인 뒤로가기 수행 (이미 발생함)
    }

    /**
     * 모달 ID 생성
     */
    generateModalId() {
        return 'modal_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    /**
     * 수동으로 모든 모달 닫기
     */
    closeAllModals() {
        while (this.modalStack.length > 0) {
            this.closeTopModal();
        }

        // 페이지 이탈 차단 해제
        this.removePageLeaveBlocking();
    }

    /**
     * 디버그 정보
     */
    getDebugInfo() {
        return {
            isMobile: this.isMobile,
            modalStack: this.modalStack,
            stackSize: this.modalStack.length,
            historyState: this.historyState
        };
    }
}

// 전역 인스턴스 생성
window.mobileModalHandler = new MobileModalHandler();

// 편의 함수들
window.registerModalOpen = function(modalInfo) {
    if (window.mobileModalHandler) {
        window.mobileModalHandler.registerModalOpen(modalInfo);
    }
};

window.registerModalClose = function(modalId) {
    if (window.mobileModalHandler) {
        window.mobileModalHandler.registerModalClose(modalId);
    }
};

// jQuery 지원
if (typeof $ !== 'undefined') {
    $(document).ready(function() {
    });
}

