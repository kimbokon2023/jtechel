<?php
require_once 'LinearComponent.php';

/**
 * Linear Button Component
 * 
 * Linear 디자인 시스템 기반의 버튼 컴포넌트
 * 다양한 스타일과 상태를 지원합니다.
 */
class LinearButton extends LinearComponent {
    
    const VARIANT_PRIMARY = 'primary';
    const VARIANT_SECONDARY = 'secondary';
    const VARIANT_OUTLINE = 'outline';
    const VARIANT_GHOST = 'ghost';
    const VARIANT_LINK = 'link';
    const VARIANT_DANGER = 'danger';
    
    const SIZE_SMALL = 'sm';
    const SIZE_MEDIUM = 'md';
    const SIZE_LARGE = 'lg';
    
    protected $tag = 'button';
    protected $variant = self::VARIANT_PRIMARY;
    protected $size = self::SIZE_MEDIUM;
    protected $loading = false;
    protected $disabled = false;
    protected $icon = null;
    protected $iconPosition = 'left';
    
    public function __construct($content = '', $attributes = []) {
        parent::__construct($content, $attributes);
        $this->addClass('linear-btn');
        $this->addAttribute('type', 'button');
    }
    
    /**
     * 버튼 변형 설정
     */
    public function variant($variant) {
        $this->variant = $variant;
        $this->addClass('linear-btn-' . $variant);
        return $this;
    }
    
    /**
     * 버튼 크기 설정
     */
    public function size($size) {
        $this->size = $size;
        $this->addClass('linear-btn-' . $size);
        return $this;
    }
    
    /**
     * 로딩 상태 설정
     */
    public function loading($loading = true) {
        $this->loading = $loading;
        if ($loading) {
            $this->addClass('linear-btn-loading');
            $this->addAttribute('disabled', true);
        }
        return $this;
    }
    
    /**
     * 비활성 상태 설정
     */
    public function disabled($disabled = true) {
        $this->disabled = $disabled;
        if ($disabled) {
            $this->addClass('linear-btn-disabled');
            $this->addAttribute('disabled', true);
        }
        return $this;
    }
    
    /**
     * 아이콘 설정
     */
    public function icon($icon, $position = 'left') {
        $this->icon = $icon;
        $this->iconPosition = $position;
        return $this;
    }
    
    /**
     * 링크 버튼으로 변환
     */
    public function asLink($href, $target = null) {
        $this->tag = 'a';
        $this->addAttribute('href', $href);
        if ($target) {
            $this->addAttribute('target', $target);
        }
        $this->addAttribute('role', 'button');
        return $this;
    }
    
    /**
     * 전체 너비 버튼
     */
    public function fullWidth() {
        $this->addClass('linear-btn-full');
        return $this;
    }
    
    /**
     * 클릭 이벤트 핸들러 추가
     */
    public function onClick($handler) {
        $this->addAttribute('onclick', $handler);
        return $this;
    }
    
    /**
     * 내용 렌더링 (아이콘 포함)
     */
    protected function renderContent() {
        $content = '';
        
        if ($this->loading) {
            $content .= '<span class="linear-btn-spinner"></span>';
        } else if ($this->icon) {
            if ($this->iconPosition === 'left') {
                $content .= "<span class=\"linear-btn-icon linear-btn-icon-left\">{$this->icon}</span>";
            }
        }
        
        if ($this->content) {
            $content .= "<span class=\"linear-btn-text\">{$this->content}</span>";
        }
        
        if ($this->icon && $this->iconPosition === 'right' && !$this->loading) {
            $content .= "<span class=\"linear-btn-icon linear-btn-icon-right\">{$this->icon}</span>";
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
    public static function primary($content = '', $attributes = []) {
        return (new static($content, $attributes))->variant(self::VARIANT_PRIMARY);
    }
    
    public static function secondary($content = '', $attributes = []) {
        return (new static($content, $attributes))->variant(self::VARIANT_SECONDARY);
    }
    
    public static function outline($content = '', $attributes = []) {
        return (new static($content, $attributes))->variant(self::VARIANT_OUTLINE);
    }
    
    public static function ghost($content = '', $attributes = []) {
        return (new static($content, $attributes))->variant(self::VARIANT_GHOST);
    }
    
    public static function link($content = '', $href = '#', $attributes = []) {
        return (new static($content, $attributes))
            ->variant(self::VARIANT_LINK)
            ->asLink($href);
    }

    public static function danger($content = '', $attributes = []) {
        return (new static($content, $attributes))->variant(self::VARIANT_DANGER);
    }
}