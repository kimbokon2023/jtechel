/**
 * Linear Theme Toggle System
 * 다크모드/라이트모드 토글 및 시스템 테마 감지 기능
 */

class LinearThemeManager {
    constructor() {
        this.storageKey = 'linear-theme';
        this.themes = {
            LIGHT: 'light',
            DARK: 'dark',
            AUTO: 'auto'
        };
        
        // 현재 테마 상태
        this.currentTheme = this.getStoredTheme() || this.themes.AUTO;
        
        // 시스템 다크모드 감지
        this.systemDarkMode = window.matchMedia('(prefers-color-scheme: dark)');
        
        // 초기화
        this.init();
    }
    
    /**
     * 초기화
     */
    init() {
        // 저장된 테마 적용
        this.applyTheme(this.currentTheme);
        
        // 시스템 테마 변경 감지
        this.systemDarkMode.addEventListener('change', (e) => {
            if (this.currentTheme === this.themes.AUTO) {
                this.updateDocumentTheme(e.matches ? this.themes.DARK : this.themes.LIGHT);
            }
        });
        
        // 테마 변경 이벤트 디스패치
        this.dispatchThemeChangeEvent();
    }
    
    /**
     * 저장된 테마 가져오기
     */
    getStoredTheme() {
        try {
            return localStorage.getItem(this.storageKey);
        } catch (e) {
            return null;
        }
    }
    
    /**
     * 테마 저장
     */
    storeTheme(theme) {
        try {
            localStorage.setItem(this.storageKey, theme);
        } catch (e) {
            console.warn('Unable to save theme preference:', e);
        }
    }
    
    /**
     * 현재 적용된 테마 확인 (실제 적용된 라이트/다크)
     */
    getAppliedTheme() {
        if (this.currentTheme === this.themes.AUTO) {
            return this.systemDarkMode.matches ? this.themes.DARK : this.themes.LIGHT;
        }
        return this.currentTheme;
    }
    
    /**
     * 테마 적용
     */
    applyTheme(theme) {
        this.currentTheme = theme;
        this.storeTheme(theme);
        
        let appliedTheme;
        if (theme === this.themes.AUTO) {
            appliedTheme = this.systemDarkMode.matches ? this.themes.DARK : this.themes.LIGHT;
            document.documentElement.removeAttribute('data-theme');
        } else {
            appliedTheme = theme;
            document.documentElement.setAttribute('data-theme', theme);
        }
        
        this.updateDocumentTheme(appliedTheme);
        this.dispatchThemeChangeEvent();
    }
    
    /**
     * 문서 테마 업데이트
     */
    updateDocumentTheme(appliedTheme) {
        // body가 아직 준비되지 않았다면 DOMContentLoaded 이후로 지연
        if (!document.body) {
            const applyWhenReady = () => {
                document.removeEventListener('DOMContentLoaded', applyWhenReady);
                this.updateDocumentTheme(appliedTheme);
            };
            document.addEventListener('DOMContentLoaded', applyWhenReady);
        } else {
            // body 클래스 업데이트
            document.body.classList.remove('theme-light', 'theme-dark');
            document.body.classList.add(`theme-${appliedTheme}`);
        }
        
        // 메타 테마 색상 업데이트
        this.updateMetaThemeColor(appliedTheme);
    }
    
    /**
     * 메타 테마 색상 업데이트 (모바일 브라우저용)
     */
    updateMetaThemeColor(appliedTheme) {
        // head 요소가 없으면 중단
        if (!document.head) return;
        
        let themeColorMeta = document.querySelector('meta[name="theme-color"]');
        if (!themeColorMeta) {
            themeColorMeta = document.createElement('meta');
            themeColorMeta.name = 'theme-color';
            document.head.appendChild(themeColorMeta);
        }
        
        const themeColors = {
            light: '#ffffff',
            dark: '#101012'
        };
        
        themeColorMeta.content = themeColors[appliedTheme] || themeColors.light;
    }
    
    /**
     * 테마 변경 이벤트 디스패치
     */
    dispatchThemeChangeEvent() {
        const event = new CustomEvent('themechange', {
            detail: {
                currentTheme: this.currentTheme,
                appliedTheme: this.getAppliedTheme()
            }
        });
        document.dispatchEvent(event);
    }
    
    /**
     * 다음 테마로 토글
     */
    toggle() {
        const themeOrder = [this.themes.LIGHT, this.themes.DARK, this.themes.AUTO];
        const currentIndex = themeOrder.indexOf(this.currentTheme);
        const nextIndex = (currentIndex + 1) % themeOrder.length;
        this.applyTheme(themeOrder[nextIndex]);
    }
    
    /**
     * 라이트모드로 설정
     */
    setLight() {
        this.applyTheme(this.themes.LIGHT);
    }
    
    /**
     * 다크모드로 설정
     */
    setDark() {
        this.applyTheme(this.themes.DARK);
    }
    
    /**
     * 시스템 테마 자동 설정
     */
    setAuto() {
        this.applyTheme(this.themes.AUTO);
    }
    
    /**
     * 현재 테마가 다크모드인지 확인
     */
    isDark() {
        return this.getAppliedTheme() === this.themes.DARK;
    }
    
    /**
     * 현재 테마가 라이트모드인지 확인
     */
    isLight() {
        return this.getAppliedTheme() === this.themes.LIGHT;
    }
    
    /**
     * 현재 테마가 자동 모드인지 확인
     */
    isAuto() {
        return this.currentTheme === this.themes.AUTO;
    }
}

/**
 * 테마 토글 버튼 컴포넌트
 */
class LinearThemeToggleButton {
    constructor(buttonElement, options = {}) {
        this.button = buttonElement;
        this.options = {
            showLabels: true,
            showIcons: true,
            style: 'button', // 'button' | 'switch'
            ...options
        };
        
        this.themeManager = window.LinearTheme || new LinearThemeManager();
        this.init();
    }
    
    init() {
        this.setupButton();
        this.updateButton();
        
        // 테마 변경 이벤트 리스너
        document.addEventListener('themechange', () => {
            this.updateButton();
        });
        
        // 클릭 이벤트
        this.button.addEventListener('click', () => {
            this.themeManager.toggle();
        });
    }
    
    setupButton() {
        this.button.classList.add('linear-theme-toggle');
        if (this.options.style) {
            this.button.classList.add(`linear-theme-toggle-${this.options.style}`);
        }
        
        this.button.setAttribute('aria-label', '테마 변경');
        this.button.setAttribute('title', '테마 변경');
    }
    
    updateButton() {
        const currentTheme = this.themeManager.currentTheme;
        const appliedTheme = this.themeManager.getAppliedTheme();
        
        // 버튼 상태 업데이트
        this.button.setAttribute('data-theme', currentTheme);
        this.button.setAttribute('data-applied-theme', appliedTheme);
        
        // 버튼 내용 업데이트
        if (this.options.showIcons || this.options.showLabels) {
            this.button.innerHTML = this.getButtonContent(currentTheme, appliedTheme);
        }
        
        // 접근성 업데이트
        const labels = {
            light: '라이트 모드',
            dark: '다크 모드',
            auto: '자동 모드'
        };
        
        this.button.setAttribute('aria-label', `현재: ${labels[currentTheme]}, 클릭하여 변경`);
        this.button.setAttribute('title', `테마 변경 (현재: ${labels[currentTheme]})`);
    }
    
    getButtonContent(currentTheme, appliedTheme) {
        const icons = {
            light: '<svg class="linear-theme-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>',
            dark: '<svg class="linear-theme-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>',
            auto: '<svg class="linear-theme-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>'
        };
        
        const labels = {
            light: '라이트',
            dark: '다크', 
            auto: '자동'
        };
        
        let content = '';
        
        if (this.options.showIcons) {
            content += icons[currentTheme];
        }
        
        if (this.options.showLabels) {
            content += `<span class="linear-theme-label">${labels[currentTheme]}</span>`;
        }
        
        return content;
    }
}

// 전역 테마 매니저 인스턴스 생성
window.LinearTheme = new LinearThemeManager();

// DOM이 로드된 후 자동으로 버튼들을 초기화
document.addEventListener('DOMContentLoaded', () => {
    // 자동 초기화: data-linear-theme-toggle 속성을 가진 모든 요소
    document.querySelectorAll('[data-linear-theme-toggle]').forEach(button => {
        const options = {};
        
        // 데이터 속성에서 옵션 읽기
        if (button.dataset.linearThemeToggle) {
            try {
                Object.assign(options, JSON.parse(button.dataset.linearThemeToggle));
            } catch (e) {
                // JSON 파싱 실패 시 기본 옵션 사용
            }
        }
        
        new LinearThemeToggleButton(button, options);
    });
});

// 유틸리티 함수들
window.LinearThemeUtils = {
    /**
     * 수동으로 테마 토글 버튼 생성
     */
    createToggleButton: (element, options) => {
        return new LinearThemeToggleButton(element, options);
    },
    
    /**
     * 현재 테마 상태 가져오기
     */
    getCurrentTheme: () => {
        return window.LinearTheme.currentTheme;
    },
    
    /**
     * 적용된 테마 가져오기
     */
    getAppliedTheme: () => {
        return window.LinearTheme.getAppliedTheme();
    },
    
    /**
     * 테마 변경 이벤트 리스너 추가
     */
    onThemeChange: (callback) => {
        document.addEventListener('themechange', callback);
    }
};

// CSS 추가 (스타일링)
if (!document.querySelector('#linear-theme-toggle-styles')) {
    const injectStyles = () => {
        if (document.querySelector('#linear-theme-toggle-styles')) return;
        if (!document.head) return;
        const style = document.createElement('style');
        style.id = 'linear-theme-toggle-styles';
        style.textContent = `
        .linear-theme-toggle {
            display: inline-flex;
            align-items: center;
            gap: var(--linear-spacing-xs, 4px);
            padding: var(--linear-spacing-xs, 4px) var(--linear-spacing-sm, 8px);
            background: transparent;
            border: 1px solid var(--linear-border-primary, #e9e8ea);
            border-radius: var(--linear-radius-md, 8px);
            color: var(--linear-text-secondary, #3c4149);
            cursor: pointer;
            transition: all var(--linear-transition-fast, 0.1s) var(--linear-ease-out, ease-out);
            font-size: var(--linear-text-small, 0.875rem);
            font-family: var(--linear-font-regular, inherit);
            user-select: none;
        }
        
        .linear-theme-toggle:hover {
            background-color: var(--linear-bg-secondary, #f9f8f9);
            border-color: var(--linear-border-secondary, #e4e2e4);
            color: var(--linear-text-primary, #282a30);
        }
        
        .linear-theme-toggle:focus {
            outline: 2px solid var(--linear-focus-ring, #5e6ad2);
            outline-offset: 2px;
        }
        
        .linear-theme-icon {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }
        
        .linear-theme-label {
            font-size: inherit;
        }
        
        .linear-theme-toggle-switch {
            width: 52px;
            height: 28px;
            padding: 2px;
            background-color: var(--linear-bg-tertiary, #f4f2f4);
            border-radius: 14px;
            border: 1px solid var(--linear-border-primary, #e9e8ea);
            position: relative;
            justify-content: flex-start;
        }
        
        .linear-theme-toggle-switch .linear-theme-icon {
            width: 20px;
            height: 20px;
            background-color: var(--linear-bg-primary, #fff);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform var(--linear-transition-fast, 0.1s) var(--linear-ease-out, ease-out);
            box-shadow: var(--linear-shadow-tiny, 0px 1px 1px 0px rgba(0, 0, 0, 0.09));
        }
        
        .linear-theme-toggle-switch[data-applied-theme="dark"] .linear-theme-icon {
            transform: translateX(24px);
        }
        
        .linear-theme-toggle-switch .linear-theme-label {
            display: none;
        }
        
        /* 다크모드에서 토글 버튼 스타일 */
        [data-theme="dark"] .linear-theme-toggle:hover,
        @media (prefers-color-scheme: dark) {
            :root:not([data-theme]) .linear-theme-toggle:hover {
                background-color: var(--linear-bg-secondary);
                border-color: var(--linear-border-secondary);
            }
        }
    `;
        document.head.appendChild(style);
    };
    if (document.head) {
        injectStyles();
    } else {
        document.addEventListener('DOMContentLoaded', injectStyles);
    }
}