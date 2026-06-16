<?php
/*
 * Copyright (c) Yusuf Hermanto <github.com/hermans>
 * @link https://www.taktikspace.com/hiya
 * @package Hiya\Components\Guard
 * @since 1.0
 */


namespace Hiya\Components;

use Hiya\Auth\Guards\SessionGuard;
use Hiya\Auth\Gate;

/**
 * Guard Component for Hiya Framework
 * 
 * Usage:
 *   Hiya::app()->auth->attempt($username, $password, $authenticator);
 *   Hiya::app()->auth->check();
 *   Hiya::app()->auth->user();
 *   Hiya::app()->auth->can('update', $post);
 *   Hiya::app()->auth->authorize('delete', $post);
 */
class Guard extends \CApplicationComponent
{
    /**
     * @var SessionGuard
     */
    protected $guard;
    
    /**
     * @var callable User provider
     */
    public $userProvider;
    
    /**
     * @var array Policies
     */
    public $policies = [];
    
    /**
     * @var array Abilities
     */
    public $abilities = [];
    
    /**
     * @var array Before callbacks
     */
    public $beforeCallbacks = [];
    
    /**
     * @var array After callbacks
     */
    public $afterCallbacks = [];
    
    /**
     * Initialize component
     */
    public function init()
    {
        parent::init();
        
        // Create Gate
        $gate = new Gate();
        
        // Register configurations
        foreach ($this->beforeCallbacks as $callback) {
            $gate->before($callback);
        }
        
        foreach ($this->afterCallbacks as $callback) {
            $gate->after($callback);
        }
        
        foreach ($this->policies as $model => $policy) {
            $gate->policy($model, $policy);
        }
        
        foreach ($this->abilities as $ability => $callback) {
            $gate->define($ability, $callback);
        }
        
        // Create guard
        $this->guard = new SessionGuard($gate, $this->userProvider);
    }
    
    /**
     * Get guard instance
     * 
     * @return SessionGuard
     */
    public function getGuard()
    {
        return $this->guard;
    }
    
    /**
     * Delegate calls to guard
     */
    public function __call($name, $arguments)
    {
        if (method_exists($this->guard, $name)) {
            return call_user_func_array([$this->guard, $name], $arguments);
        }
        
        throw new \CException("Method '{$name}' not found in GuardComponent");
    }
}