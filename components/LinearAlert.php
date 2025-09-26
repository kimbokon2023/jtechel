<?php
require_once 'LinearComponent.php';

/**
 * Linear Alert Component
 * 
 * 알림, 경고, 성공, 에러 메시지를 표시하는 컴포넌트
 */
class LinearAlert extends LinearComponent {
    
    const TYPE_INFO = 'info';
    const TYPE_SUCCESS = 'success';
    const TYPE_WARNING = 'warning';
    const TYPE_ERROR = 'error';
    
    protected $type = self::TYPE_INFO;
    protected $dismissible = false;
    protected $icon = null;
    protected $title = null;
    
    public function __construct($content = '', $type = self::TYPE_INFO, $attributes = []) {
        parent::__construct($content, $attributes);
        $this->type = $type;
        $this->addClass('linear-alert');
        $this->addClass('linear-alert-' . $type);
        $this->addAttribute('role', 'alert');
    }
    
    /**
     * 알림 타입 설정
     */
    public function type($type) {
        // 기존 타입 클래스 제거
        $this->classes = array_filter($this->classes, function($class) {
            return !preg_match('/^linear-alert-(info|success|warning|error)$/', $class);
        });
        
        $this->type = $type;
        $this->addClass('linear-alert-' . $type);
        return $this;
    }
    
    /**
     * 제목 설정
     */
    public function title($title) {
        $this->title = $title;
        return $this;
    }
    
    /**
     * 아이콘 설정
     */
    public function icon($icon) {
        $this->icon = $icon;
        return $this;
    }
    
    /**
     * 닫기 가능 설정
     */
    public function dismissible($dismissible = true) {
        $this->dismissible = $dismissible;
        if ($dismissible) {
            $this->addClass('linear-alert-dismissible');
        }
        return $this;
    }
    
    /**
     * 자동 아이콘 설정
     */
    protected function getDefaultIcon() {
        $icons = [
            self::TYPE_INFO => '<svg class="linear-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" /></svg>',
            self::TYPE_SUCCESS => '<svg class="linear-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>',
            self::TYPE_WARNING => '<svg class="linear-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>',
            self::TYPE_ERROR => '<svg class="linear-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>'
        ];
        
        return $icons[$this->type] ?? $icons[self::TYPE_INFO];
    }
    
    /**
     * 내용 렌더링
     */
    protected function renderContent() {
        $content = '';
        
        // 아이콘
        $iconToUse = $this->icon ?? $this->getDefaultIcon();
        if ($iconToUse) {
            $content .= "<div class=\"linear-alert-icon\">{$iconToUse}</div>";
        }
        
        // 메인 콘텐츠
        $content .= '<div class="linear-alert-content">';
        
        // 제목
        if ($this->title) {
            $content .= "<div class=\"linear-alert-title\">{$this->title}</div>";
        }
        
        // 메시지
        if ($this->content) {
            $content .= "<div class=\"linear-alert-message\">{$this->content}</div>";
        }
        
        $content .= '</div>';
        
        // 닫기 버튼
        if ($this->dismissible) {
            $content .= '<button type="button" class="linear-alert-close" aria-label="닫기">';
            $content .= '<svg class="linear-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>';
            $content .= '</button>';
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
    public static function info($message, $title = null, $attributes = []) {
        $alert = new static($message, self::TYPE_INFO, $attributes);
        if ($title) $alert->title($title);
        return $alert;
    }
    
    public static function success($message, $title = null, $attributes = []) {
        $alert = new static($message, self::TYPE_SUCCESS, $attributes);
        if ($title) $alert->title($title);
        return $alert;
    }
    
    public static function warning($message, $title = null, $attributes = []) {
        $alert = new static($message, self::TYPE_WARNING, $attributes);
        if ($title) $alert->title($title);
        return $alert;
    }
    
    public static function error($message, $title = null, $attributes = []) {
        $alert = new static($message, self::TYPE_ERROR, $attributes);
        if ($title) $alert->title($title);
        return $alert;
    }
    
    /**
     * 기본 create 메소드 오버라이드
     */
    public static function create($content = '', $type = self::TYPE_INFO, $attributes = []) {
        return new static($content, $type, $attributes);
    }
}