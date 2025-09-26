<?php
require_once 'LinearComponent.php';

/**
 * Linear Navigation Component
 * 
 * Linear 스타일의 네비게이션 바 컴포넌트
 */
class LinearNavigation extends LinearComponent {
    
    protected $tag = 'nav';
    protected $brand = null;
    protected $menuItems = [];
    protected $actions = [];
    protected $fixed = false;
    protected $transparent = false;
    
    public function __construct($attributes = []) {
        parent::__construct('', $attributes);
        $this->addClass('linear-nav');
    }
    
    /**
     * 브랜드/로고 설정
     */
    public function brand($brand, $href = '#') {
        $this->brand = [
            'content' => $brand,
            'href' => $href
        ];
        return $this;
    }
    
    /**
     * 메뉴 아이템 추가
     */
    public function addMenuItem($label, $href = '#', $active = false, $attributes = []) {
        $this->menuItems[] = [
            'label' => $label,
            'href' => $href,
            'active' => $active,
            'attributes' => $attributes
        ];
        return $this;
    }
    
    /**
     * 여러 메뉴 아이템 일괄 추가
     */
    public function setMenuItems($items) {
        $this->menuItems = [];
        foreach ($items as $item) {
            $this->addMenuItem(
                $item['label'],
                $item['href'] ?? '#',
                $item['active'] ?? false,
                $item['attributes'] ?? []
            );
        }
        return $this;
    }
    
    /**
     * 액션 버튼 추가 (오른쪽 영역)
     */
    public function addAction($content, $attributes = []) {
        $this->actions[] = [
            'content' => $content,
            'attributes' => $attributes
        ];
        return $this;
    }
    
    /**
     * 고정 네비게이션 설정
     */
    public function fixed($fixed = true) {
        $this->fixed = $fixed;
        if ($fixed) {
            $this->addClass('linear-nav-fixed');
        }
        return $this;
    }
    
    /**
     * 투명 네비게이션 설정
     */
    public function transparent($transparent = true) {
        $this->transparent = $transparent;
        if ($transparent) {
            $this->addClass('linear-nav-transparent');
        }
        return $this;
    }
    
    /**
     * 모바일 메뉴 토글 활성화
     */
    public function mobileMenu($enabled = true) {
        if ($enabled) {
            $this->addClass('linear-nav-mobile-enabled');
        }
        return $this;
    }
    
    /**
     * 내용 렌더링
     */
    protected function renderContent() {
        $content = '<div class="linear-nav-container">';
        
        // 브랜드/로고
        if ($this->brand) {
            $content .= '<div class="linear-nav-brand">';
            $content .= "<a href=\"{$this->brand['href']}\" class=\"linear-nav-brand-link\">";
            $content .= $this->brand['content'];
            $content .= '</a>';
            $content .= '</div>';
        }
        
        // 메인 메뉴
        if (!empty($this->menuItems)) {
            $content .= '<div class="linear-nav-menu">';
            $content .= '<ul class="linear-nav-menu-list">';
            
            foreach ($this->menuItems as $item) {
                $activeClass = $item['active'] ? ' linear-nav-menu-item-active' : '';
                $content .= "<li class=\"linear-nav-menu-item{$activeClass}\">";
                
                $attrs = '';
                foreach ($item['attributes'] as $name => $value) {
                    $attrs .= " {$name}=\"" . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . "\"";
                }
                
                $content .= "<a href=\"{$item['href']}\" class=\"linear-nav-menu-link\"{$attrs}>";
                $content .= $item['label'];
                $content .= '</a>';
                $content .= '</li>';
            }
            
            $content .= '</ul>';
            $content .= '</div>';
        }
        
        // 액션 영역
        if (!empty($this->actions)) {
            $content .= '<div class="linear-nav-actions">';
            foreach ($this->actions as $action) {
                $attrs = '';
                foreach ($action['attributes'] as $name => $value) {
                    $attrs .= " {$name}=\"" . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . "\"";
                }
                $content .= "<div class=\"linear-nav-action\"{$attrs}>";
                $content .= $action['content'];
                $content .= '</div>';
            }
            $content .= '</div>';
        }
        
        // 모바일 메뉴 버튼
        if (in_array('linear-nav-mobile-enabled', $this->classes)) {
            $content .= '<button class="linear-nav-mobile-toggle" type="button" aria-label="메뉴 토글">';
            $content .= '<span class="linear-nav-mobile-toggle-icon"></span>';
            $content .= '</button>';
        }
        
        $content .= '</div>';
        
        // 모바일 메뉴 (숨김)
        if (in_array('linear-nav-mobile-enabled', $this->classes) && !empty($this->menuItems)) {
            $content .= '<div class="linear-nav-mobile-menu">';
            $content .= '<ul class="linear-nav-mobile-menu-list">';
            
            foreach ($this->menuItems as $item) {
                $activeClass = $item['active'] ? ' linear-nav-mobile-menu-item-active' : '';
                $content .= "<li class=\"linear-nav-mobile-menu-item{$activeClass}\">";
                $content .= "<a href=\"{$item['href']}\" class=\"linear-nav-mobile-menu-link\">";
                $content .= $item['label'];
                $content .= '</a>';
                $content .= '</li>';
            }
            
            $content .= '</ul>';
            $content .= '</div>';
        }
        
        return $content;
    }
    
    public function render() {
        $this->content = $this->renderContent();
        return parent::render();
    }
    
    /**
     * 정적 팩토리 메소드들
     */
    public static function create($content = '', $attributes = []) {
        return new static($attributes);
    }
    
    public static function withBrand($brand, $brandHref = '#', $attributes = []) {
        return (new static($attributes))->brand($brand, $brandHref);
    }
}