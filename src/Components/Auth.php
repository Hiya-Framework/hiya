<?php
/*
 * Copyright (c) Yusuf Hermanto <github.com/hermans>
 * @link https://www.taktikspace.com/hiya
 * @package Hiya\Components\Auth
 * @since 1.0
 */

namespace Hiya\Components;

use Hiya\Auth\Gate;

class Auth extends \CApplicationComponent
{
    public $policies = [];
    public $abilities = [];
    public $beforeCallbacks = [];
    public $afterCallbacks = [];
    
    protected $gate;
    
    public function init()
    {
        parent::init();
        
        $this->gate = new Gate();
        
        foreach ($this->beforeCallbacks as $callback) {
            $this->gate->before($callback);
        }
        
        foreach ($this->afterCallbacks as $callback) {
            $this->gate->after($callback);
        }
        
        foreach ($this->policies as $model => $policy) {
            $this->gate->policy($model, $policy);
        }
        
        foreach ($this->abilities as $ability => $callback) {
            $this->gate->define($ability, $callback);
        }
        
        if (!\Hiya::app()->user->isGuest) {
            $this->gate->forUser(\Hiya::app()->user);
        }
    }
    
    public function gate()
    {
        return $this->gate;
    }
    
    public function can($ability, ...$arguments)
    {
        return $this->gate->check($ability, ...$arguments);
    }
    
    public function cannot($ability, ...$arguments)
    {
        return $this->gate->denies($ability, ...$arguments);
    }
    
    public function authorize($ability, ...$arguments)
    {
        return $this->gate->authorize($ability, ...$arguments)->authorize();
    }
    
    public function allows($ability, ...$arguments)
    {
        return $this->can($ability, ...$arguments);
    }
    
    public function inspect($ability, ...$arguments)
    {
        return $this->gate->inspect($ability, ...$arguments);
    }
    
    public function __call($name, $arguments)
    {
        if (strpos($name, 'can') === 0) {
            $ability = lcfirst(substr($name, 3));
            return $this->can($ability, ...$arguments);
        }
        
        if (strpos($name, 'cannot') === 0) {
            $ability = lcfirst(substr($name, 6));
            return $this->cannot($ability, ...$arguments);
        }
        
        throw new \CException("Method '{$name}' not found");
    }
}