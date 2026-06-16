<?php
namespace Hiya\Auth\Access;

class Response
{
    protected $allowed;
    protected $message;
    protected $code;
    
    public function __construct($allowed, $message = null, $code = null)
    {
        $this->allowed = (bool) $allowed;
        $this->message = $message;
        $this->code = $code;
    }
    
    public static function allow($message = null)
    {
        return new static(true, $message);
    }
    
    public static function deny($message = null, $code = null)
    {
        return new static(false, $message, $code);
    }
    
    public function allowed()
    {
        return $this->allowed;
    }
    
    public function denied()
    {
        return !$this->allowed;
    }
    
    public function message()
    {
        return $this->message;
    }
    
    public function code()
    {
        return $this->code;
    }
    
    public function withMessage($message)
    {
        $this->message = $message;
        return $this;
    }
    
    public function withCode($code)
    {
        $this->code = $code;
        return $this;
    }
    
    public function authorize()
    {
        if ($this->denied()) {
            throw new AuthorizationException($this->message, $this->code);
        }
        return $this;
    }
    
    public function toArray()
    {
        return [
            'allowed' => $this->allowed,
            'message' => $this->message,
            'code' => $this->code,
        ];
    }
}