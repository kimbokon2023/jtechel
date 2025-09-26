<?php
require_once 'LinearComponent.php';

/**
 * Linear Input Component
 * 
 * Linear 디자인 시스템 기반의 입력 필드 컴포넌트
 * 다양한 타입과 상태를 지원합니다.
 */
class LinearInput extends LinearComponent {
    
    const TYPE_TEXT = 'text';
    const TYPE_EMAIL = 'email';
    const TYPE_PASSWORD = 'password';
    const TYPE_NUMBER = 'number';
    const TYPE_TEL = 'tel';
    const TYPE_URL = 'url';
    const TYPE_SEARCH = 'search';
    const TYPE_TEXTAREA = 'textarea';
    
    const SIZE_SMALL = 'sm';
    const SIZE_MEDIUM = 'md';
    const SIZE_LARGE = 'lg';
    
    protected $tag = 'input';
    protected $label = null;
    protected $error = null;
    protected $help = null;
    protected $size = self::SIZE_MEDIUM;
    protected $required = false;
    protected $disabled = false;
    protected $readonly = false;
    protected $icon = null;
    protected $iconPosition = 'left';
    
    public function __construct($type = self::TYPE_TEXT, $attributes = []) {
        parent::__construct('', $attributes);
        $this->addClass('linear-input');
        $this->addAttribute('type', $type);
        
        // textarea인 경우 태그 변경
        if ($type === self::TYPE_TEXTAREA) {
            $this->tag = 'textarea';
            $this->attributes = array_diff_key($this->attributes, ['type' => null]);
        }
    }
    
    /**
     * 입력 필드 크기 설정
     */
    public function size($size) {
        $this->size = $size;
        $this->addClass('linear-input-' . $size);
        return $this;
    }
    
    /**
     * 라벨 설정
     */
    public function label($label, $required = false) {
        $this->label = $label;
        $this->required = $required;
        return $this;
    }
    
    /**
     * 플레이스홀더 설정
     */
    public function placeholder($placeholder) {
        $this->addAttribute('placeholder', $placeholder);
        return $this;
    }
    
    /**
     * 값 설정
     */
    public function value($value) {
        if ($this->tag === 'textarea') {
            $this->content = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        } else {
            $this->addAttribute('value', $value);
        }
        return $this;
    }
    
    /**
     * 이름 설정
     */
    public function name($name) {
        $this->addAttribute('name', $name);
        return $this;
    }
    
    /**
     * 필수 필드 설정
     */
    public function required($required = true) {
        $this->required = $required;
        if ($required) {
            $this->addAttribute('required', true);
            $this->addClass('linear-input-required');
        }
        return $this;
    }
    
    /**
     * 비활성 상태 설정
     */
    public function disabled($disabled = true) {
        $this->disabled = $disabled;
        if ($disabled) {
            $this->addAttribute('disabled', true);
            $this->addClass('linear-input-disabled');
        }
        return $this;
    }
    
    /**
     * 읽기 전용 설정
     */
    public function readonly($readonly = true) {
        $this->readonly = $readonly;
        if ($readonly) {
            $this->addAttribute('readonly', true);
            $this->addClass('linear-input-readonly');
        }
        return $this;
    }
    
    /**
     * 에러 메시지 설정
     */
    public function error($error) {
        $this->error = $error;
        $this->addClass('linear-input-error');
        return $this;
    }
    
    /**
     * 도움말 텍스트 설정
     */
    public function help($help) {
        $this->help = $help;
        return $this;
    }
    
    /**
     * textarea 행 수 설정
     */
    public function rows($rows) {
        $this->addAttribute('rows', $rows);
        return $this;
    }
    
    /**
     * 아이콘 설정
     */
    public function icon($icon, $position = 'left') {
        $this->icon = $icon;
        $this->iconPosition = $position;
        $this->addClass('linear-input-with-icon');
        $this->addClass('linear-input-icon-' . $position);
        return $this;
    }
    
    /**
     * 전체 너비 설정
     */
    public function fullWidth() {
        $this->addClass('linear-input-full');
        return $this;
    }
    
    /**
     * 최소/최대값 설정 (숫자 타입용)
     */
    public function min($min) {
        $this->addAttribute('min', $min);
        return $this;
    }
    
    public function max($max) {
        $this->addAttribute('max', $max);
        return $this;
    }
    
    /**
     * 최소/최대 길이 설정
     */
    public function minLength($minLength) {
        $this->addAttribute('minlength', $minLength);
        return $this;
    }
    
    public function maxLength($maxLength) {
        $this->addAttribute('maxlength', $maxLength);
        return $this;
    }
    
    /**
     * 패턴 설정 (정규식)
     */
    public function pattern($pattern) {
        $this->addAttribute('pattern', $pattern);
        return $this;
    }
    
    /**
     * 자동완성 설정
     */
    public function autocomplete($value) {
        $this->addAttribute('autocomplete', $value);
        return $this;
    }
    
    /**
     * 입력 필드 그룹 렌더링
     */
    public function renderGroup() {
        $html = '<div class="linear-input-group">';
        
        // 라벨
        if ($this->label) {
            $requiredMark = $this->required ? ' <span class="linear-input-required-mark">*</span>' : '';
            $forAttr = isset($this->attributes['id']) ? ' for="' . $this->attributes['id'] . '"' : '';
            $html .= "<label class=\"linear-input-label\"{$forAttr}>{$this->label}{$requiredMark}</label>";
        }
        
        // 입력 필드 컨테이너
        $html .= '<div class="linear-input-container">';
        
        // 왼쪽 아이콘
        if ($this->icon && $this->iconPosition === 'left') {
            $html .= "<span class=\"linear-input-icon linear-input-icon-left\">{$this->icon}</span>";
        }
        
        // 입력 필드
        $html .= $this->render();
        
        // 오른쪽 아이콘
        if ($this->icon && $this->iconPosition === 'right') {
            $html .= "<span class=\"linear-input-icon linear-input-icon-right\">{$this->icon}</span>";
        }
        
        $html .= '</div>';
        
        // 에러 메시지
        if ($this->error) {
            $html .= "<div class=\"linear-input-error-message\">{$this->error}</div>";
        }
        
        // 도움말
        if ($this->help) {
            $html .= "<div class=\"linear-input-help\">{$this->help}</div>";
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * 정적 팩토리 메소드들
     */
    public static function text($name = '', $attributes = []) {
        $input = new static(self::TYPE_TEXT, $attributes);
        if ($name) {
            $input->addAttribute('name', $name);
            $input->addAttribute('id', $name);
        }
        return $input;
    }
    
    public static function email($name = '', $attributes = []) {
        $input = new static(self::TYPE_EMAIL, $attributes);
        if ($name) {
            $input->addAttribute('name', $name);
            $input->addAttribute('id', $name);
        }
        return $input;
    }
    
    public static function password($name = '', $attributes = []) {
        $input = new static(self::TYPE_PASSWORD, $attributes);
        if ($name) {
            $input->addAttribute('name', $name);
            $input->addAttribute('id', $name);
        }
        return $input;
    }
    
    public static function number($name = '', $attributes = []) {
        $input = new static(self::TYPE_NUMBER, $attributes);
        if ($name) {
            $input->addAttribute('name', $name);
            $input->addAttribute('id', $name);
        }
        return $input;
    }
    
    public static function tel($name = '', $attributes = []) {
        $input = new static(self::TYPE_TEL, $attributes);
        if ($name) {
            $input->addAttribute('name', $name);
            $input->addAttribute('id', $name);
        }
        return $input;
    }
    
    public static function textarea($name = '', $attributes = []) {
        $input = new static(self::TYPE_TEXTAREA, $attributes);
        if ($name) {
            $input->addAttribute('name', $name);
            $input->addAttribute('id', $name);
        }
        return $input;
    }
    
    public static function search($name = '', $attributes = []) {
        $input = new static(self::TYPE_SEARCH, $attributes);
        if ($name) {
            $input->addAttribute('name', $name);
            $input->addAttribute('id', $name);
        }
        return $input;
    }
}