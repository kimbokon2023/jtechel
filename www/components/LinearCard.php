<?php
require_once 'LinearComponent.php';

/**
 * Linear Card Component
 * 
 * Linear 디자인 시스템 기반의 카드 컴포넌트
 * 제목, 내용, 푸터 등을 포함할 수 있습니다.
 */
class LinearCard extends LinearComponent {
    
    protected $header = null;
    protected $footer = null;
    protected $padding = 'default';
    protected $shadow = 'low';
    protected $hover = false;
    
    public function __construct($content = '', $attributes = []) {
        parent::__construct($content, $attributes);
        $this->addClass('linear-card');
    }
    
    /**
     * 카드 헤더 설정
     */
    public function header($header) {
        $this->header = $header;
        return $this;
    }
    
    /**
     * 카드 푸터 설정
     */
    public function footer($footer) {
        $this->footer = $footer;
        return $this;
    }
    
    /**
     * 패딩 설정
     */
    public function padding($padding) {
        $this->padding = $padding;
        $this->addClass('linear-card-padding-' . $padding);
        return $this;
    }
    
    /**
     * 그림자 설정
     */
    public function shadow($shadow) {
        $this->shadow = $shadow;
        $this->addClass('linear-shadow-' . $shadow);
        return $this;
    }
    
    /**
     * 호버 효과 활성화
     */
    public function hoverable($hover = true) {
        $this->hover = $hover;
        if ($hover) {
            $this->addClass('linear-card-hoverable');
        }
        return $this;
    }
    
    /**
     * 클릭 가능한 카드로 설정
     */
    public function clickable($onClick = null) {
        $this->addClass('linear-card-clickable');
        $this->addAttribute('role', 'button');
        $this->addAttribute('tabindex', '0');
        if ($onClick) {
            $this->addAttribute('onclick', $onClick);
        }
        return $this;
    }
    
    /**
     * 보더 제거
     */
    public function noBorder() {
        $this->addClass('linear-card-no-border');
        return $this;
    }
    
    /**
     * 내용 렌더링
     */
    protected function renderContent() {
        $content = '';
        
        if ($this->header) {
            $content .= "<div class=\"linear-card-header\">{$this->header}</div>";
        }
        
        if ($this->content) {
            $content .= "<div class=\"linear-card-body\">{$this->content}</div>";
        }
        
        if ($this->footer) {
            $content .= "<div class=\"linear-card-footer\">{$this->footer}</div>";
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
    public static function withHeader($title, $content = '', $attributes = []) {
        return (new static($content, $attributes))->header($title);
    }
    
    public static function simple($content = '', $attributes = []) {
        return new static($content, $attributes);
    }
    
    public static function interactive($content = '', $onClick = null, $attributes = []) {
        return (new static($content, $attributes))
            ->hoverable()
            ->clickable($onClick);
    }
}