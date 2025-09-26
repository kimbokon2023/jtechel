<?php
/**
 * Linear Design System Component Base Class
 * 
 * Linear.app 스타일을 기반으로 한 재사용 가능한 UI 컴포넌트 기본 클래스
 * 모든 Linear 컴포넌트는 이 클래스를 상속받아 일관된 구조를 가집니다.
 */
abstract class LinearComponent {
    protected $attributes = [];
    protected $classes = [];
    protected $content = '';
    protected $tag = 'div';
    
    public function __construct($content = '', $attributes = []) {
        $this->content = $content;
        $this->attributes = $attributes;
    }
    
    /**
     * 클래스 추가
     */
    public function addClass($class) {
        if (is_array($class)) {
            $this->classes = array_merge($this->classes, $class);
        } else {
            $this->classes[] = $class;
        }
        return $this;
    }
    
    /**
     * 속성 추가
     */
    public function addAttribute($name, $value) {
        $this->attributes[$name] = $value;
        return $this;
    }
    
    /**
     * ID 설정
     */
    public function setId($id) {
        $this->attributes['id'] = $id;
        return $this;
    }
    
    /**
     * 내용 설정
     */
    public function setContent($content) {
        $this->content = $content;
        return $this;
    }
    
    /**
     * 속성 문자열 생성
     */
    protected function buildAttributes() {
        $attrs = [];
        
        // 클래스 처리
        if (!empty($this->classes)) {
            $this->attributes['class'] = implode(' ', array_unique($this->classes));
        }
        
        // 속성 문자열 생성
        foreach ($this->attributes as $name => $value) {
            if ($value !== null && $value !== false) {
                if ($value === true) {
                    $attrs[] = $name;
                } else {
                    $attrs[] = $name . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
                }
            }
        }
        
        return implode(' ', $attrs);
    }
    
    /**
     * HTML 렌더링
     */
    public function render() {
        $attributes = $this->buildAttributes();
        $attributeString = $attributes ? ' ' . $attributes : '';
        
        if (in_array($this->tag, ['input', 'img', 'br', 'hr', 'meta', 'link'])) {
            return "<{$this->tag}{$attributeString}>";
        }
        
        return "<{$this->tag}{$attributeString}>{$this->content}</{$this->tag}>";
    }
    
    /**
     * 문자열 변환시 자동 렌더링
     */
    public function __toString() {
        return $this->render();
    }
    
    /**
     * 정적 팩토리 메소드
     */
    public static function create($content = '', $attributes = []) {
        return new static($content, $attributes);
    }
}